<?php
namespace Local\Api\Controllers\V1;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\ORM;
use Bitrix\Sale\Order;
use Bitrix\Main\Type\DateTime;

Loader::includeModule('highloadblock');
Loader::includeModule('custom.core');
Loader::includeModule('sale');

class Orders{
    private int $userId;
    private array $groups;
    private const ALLOWED_GROUPS = [1, 8];
    private const TEMP_DIR = '/upload/tmp/';

    private const LEGAL_PERSON_TYPE_ID = 2;

    public function __construct()
    {
        $request = request()->get();
        $this->userId = (int)$request['_user']['ID'];
        $this->groups = \CUser::GetUserGroup($request['_user']['ID']) ?? [];
    }

    public function setPaid()
    {
        try {
            $this->checkAccess();
            $request = request()->get();

            $order = $this->getOrder($request['account_number']);
            $propertyCollection = $order->getPropertyCollection();
            $serverName = 'https://' . \Bitrix\Main\Config\Option::get('main', 'server_name', '');
            // Обрабатываем файлы для разных свойств
            $fileProperties = [
                'act_for_tickets' => 'ACT_FOR_TICKETS',
                'act_for_service_fee' => 'ACT_FOR_SERVICE_FEE'
            ];

            foreach ($fileProperties as $requestKey => $propertyCode) {
                $this->processFiles($request, $requestKey, $propertyCollection, $propertyCode);
            }

            // Устанавливаем дату оплаты если передана
            if (!empty($request['date_payment'])) {
                $this->setPaymentDate($order, $request['date_payment']);

                // Устанавливаем заказ как оплаченный
                $this->markOrderAsPaid($order);
            }

            if (isset($request['expired'])) {
                //Устанавливаем статус брони
                $propertyCollection->getItemByOrderPropertyCode('RESERVATION_STATUS')->setValue('OP');
            }

            // Сохраняем все изменения заказа
            $result = $order->save();
            if (!$result->isSuccess()) {
                throw new \Exception(implode(', ', $result->getErrorMessages()));
            }

            // Отправляем письмо с актами если есть файлы
            $this->sendActsEmail($order, $request);

            $this->sendSuccessResponse($request);

        } catch(\Exception $e) {
            $this->sendErrorResponse($e->getMessage());
        }
    }

    /**
     * Проверка прав доступа
     */
    private function checkAccess(): void
    {
        if (!array_intersect(self::ALLOWED_GROUPS, $this->groups)) {
            throw new \Exception('Access denied');
        }
    }

    /**
     * Получение заказа по номеру
     */
    private function getOrder(string $accountNumber): Order
    {
        $order = Order::loadByAccountNumber($accountNumber);
        if (!$order) {
            throw new \Exception('Order not found');
        }
        return $order;
    }

    /**
     * Обработка файлов для конкретного свойства
     */
    private function processFiles(array $request, string $requestKey, $propertyCollection, string $propertyCode): void
    {
        if (!is_array($request[$requestKey]) || empty($request[$requestKey])) {
            return;
        }

        $property = $propertyCollection->getItemByOrderPropertyCode($propertyCode);
        if (!$property) {
            throw new \Exception("Свойство {$propertyCode} не найдено");
        }

        $fileIds = $this->processBase64Files($request[$requestKey]);

        if (!empty($fileIds)) {
            $property->setValue($fileIds);
        }
    }

    /**
     * Обработка base64 файлов
     */
    private function processBase64Files(array $files): array
    {
        $fileIds = [];
        $tempDir = $_SERVER['DOCUMENT_ROOT'] . self::TEMP_DIR;

        // Создаем временную директорию если её нет
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        foreach ($files as $name => $base64String) {
            $fileId = $this->processBase64File($name, $base64String, $tempDir);
            if ($fileId) {
                $fileIds[] = $fileId;
            }
        }

        return $fileIds;
    }

    /**
     * Обработка одного base64 файла
     */
    private function processBase64File(string $name, string $base64String, string $tempDir): ?int
    {
        // Декодируем base64 данные
        $data = base64_decode($base64String);

        if ($data === false) {
            return null;
        }

        // Определяем MIME-тип и расширение по имени файла
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (empty($extension)) {
            return null;
        }

        // Определяем MIME-тип по расширению
        $mimeTypes = [
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp'
        ];

        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        $fileName = $name;
        $tempFilePath = $tempDir . $fileName;

        // Сохраняем временный файл
        if (!file_put_contents($tempFilePath, $data)) {
            return null;
        }

        try {
            // Создаем массив для CFile::SaveFile
            $fileArray = [
                "name" => $fileName,
                "size" => strlen($data),
                "tmp_name" => $tempFilePath,
                "type" => $mimeType
            ];

            // Сохраняем файл через Bitrix API
            $fileId = \CFile::SaveFile($fileArray, "sale");

            return $fileId ?: null;

        } finally {
            // Удаляем временный файл в любом случае
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
        }
    }

    /**
     * Устанавливает дату оплаты заказа
     */
    private function setPaymentDate(Order $order, string $datePayment): void
    {
        // Поддерживаемые форматы дат
        $formats = [
            'Y-m-d H:i:s',  // 2024-08-28 14:30:00
            'Y-m-d',        // 2024-08-28
            'd.m.Y H:i:s',  // 28.08.2024 14:30:00
            'd.m.Y'         // 28.08.2024
        ];

        $date = null;

        // Пробуем парсить дату в разных форматах
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $datePayment);
            if ($date !== false) {
                // Если формат без времени, устанавливаем 12:00:00
                if (strpos($format, 'H:i:s') === false) {
                    $date->setTime(12, 0, 0);
                }
                break;
            }
        }

        if (!$date) {
            throw new \Exception('Invalid date_payment format. Use Y-m-d, Y-m-d H:i:s, d.m.Y or d.m.Y H:i:s');
        }

        // Проверяем разумные ограничения дат
        $minDate = new \DateTime('1990-01-01');
        $maxDate = new \DateTime('2030-12-31');

        if ($date < $minDate || $date > $maxDate) {
            throw new \Exception('Date payment must be between 1990-01-01 and 2030-12-31');
        }

        // Устанавливаем дату через объект платежа, а не заказа
        $paymentCollection = $order->getPaymentCollection();
        $payment           = $paymentCollection[0] ?? null;

        if ($payment) {
            // Пробуем создать Bitrix DateTime из временной метки
            $bitrixDate = DateTime::createFromTimestamp($date->getTimestamp());
            $payment->setField('DATE_PAID', $bitrixDate);
        }
    }

    /**
     * Помечает заказ как оплаченный
     */
    private function markOrderAsPaid(Order $order): void
    {
        $paymentCollection = $order->getPaymentCollection();
        $payment = $paymentCollection[0] ?? null;

        if (!$payment) {
            throw new \Exception('Payment not found');
        }

        $payment->setPaid("Y");
    }

    /**
     * Отправка успешного ответа
     */
    private function sendSuccessResponse(array $request): void
    {
        response()->json([
                             'status' => 'success',
                             'result' => true,
                         ], 200, [], ['Content-Type' => 'application/json']);
    }

    /**
     * Отправка ответа с ошибкой
     */
    private function sendErrorResponse(string $message): void
    {
        response()->json([
                             'status' => 'error',
                             'message' => $message,
                         ], 400, [], ['Content-Type' => 'application/json']);
    }

    /**
     * Отправка письма с актами покупателю
     */
    private function sendActsEmail(Order $order, array $request): void
    {
        $propertyCollection = $order->getPropertyCollection();

        // Проверяем наличие файлов актов
        $actForTickets    = $propertyCollection->getItemByOrderPropertyCode('ACT_FOR_TICKETS');
        $actForServiceFee = $propertyCollection->getItemByOrderPropertyCode('ACT_FOR_SERVICE_FEE');

        $hasActTickets    = $actForTickets && !empty($actForTickets->getValue());
        $hasActServiceFee = $actForServiceFee && !empty($actForServiceFee->getValue());

        // Отправляем письмо только если есть хотя бы один файл акта
        if (!$hasActTickets && !$hasActServiceFee) {
            return;
        }

        $serverName = 'https://' . \Bitrix\Main\Config\Option::get('main', 'server_name', '');

        // Получаем данные заказа
        $orderData = $this->getOrderEmailData($order);

        // Формируем ссылки на файлы
        $actsTicketsLink    = '';
        $actsServiceFeeLink = '';

        if ($hasActTickets) {
            $fileIds = $actForTickets->getValue();

            // Проверяем разные форматы значений
            if (is_array($fileIds)) {
                $fileData = !empty($fileIds) ? $fileIds[0] : null;
            } else {
                $fileData = $fileIds;
            }

            // Извлекаем ID файла из данных
            $fileId = null;
            if (is_array($fileData) && isset($fileData['ID'])) {
                $fileId = $fileData['ID'];
            } elseif (is_numeric($fileData)) {
                $fileId = $fileData;
            }

            if ($fileId && is_numeric($fileId)) {
                $filePath = \CFile::GetPath($fileId);
                if ($filePath) {
                    $actsTicketsLink = ' <a class="text-center link fw500" href="' . $serverName . $filePath . '" style="Margin:default;color:#ff2020;font-family:Montserrat,sans-serif;font-weight:500;line-height:150%;margin:default;padding:0;text-align:center;text-decoration:underline">Скачать акт на билеты</a>';
                }
            }
        }

        if ($hasActServiceFee) {
            $fileIds = $actForServiceFee->getValue();

            // Проверяем разные форматы значений
            if (is_array($fileIds)) {
                $fileData = !empty($fileIds) ? $fileIds[0] : null;
            } else {
                $fileData = $fileIds;
            }

            // Извлекаем ID файла из данных
            $fileId = null;
            if (is_array($fileData) && isset($fileData['ID'])) {
                $fileId = $fileData['ID'];
            } elseif (is_numeric($fileData)) {
                $fileId = $fileData;
            }

            if ($fileId && is_numeric($fileId)) {
                $filePath = \CFile::GetPath($fileId);
                if ($filePath) {
                    $actsServiceFeeLink = ' <a class="text-center link fw500" href="' . $serverName . $filePath . '" style="Margin:default;color:#ff2020;font-family:Montserrat,sans-serif;font-weight:500;line-height:150%;margin:default;padding:0;text-align:center;text-decoration:underline">Скачать акт на сервисный сбор</a>';
                }
            }
        }

        // Поля для отправки письма
        $fields = [
            'SERVER_NAME'          => $serverName,
            'ORDER_NUM'            => $order->getField('ACCOUNT_NUMBER'),
            'EVENT_NAME'           => $orderData['EVENT_NAME'],
            'FULL_NAME'            => $orderData['FIO'],
            'EMAIL'                => $orderData['EMAIL'],
            'ACTS_ON_TICKETS_LINK' => $actsTicketsLink,
            'ACTS_ON_SERVICE_FEE'  => $actsServiceFeeLink,
        ];


        // Отправляем почтовое событие
        \CEvent::Send('SALE_ORDER_ACTS', SITE_ID, $fields, 'N', '', [], '');
    }

    /**
     * Получение данных заказа для email
     */
    private function getOrderEmailData(Order $order): array
    {
        $propertyCollection = $order->getPropertyCollection();

        // Получаем EVENT_ID
        $eventIdProperty = $propertyCollection->getItemByOrderPropertyCode('EVENT_ID');
        $eventId         = $eventIdProperty ? $eventIdProperty->getValue() : '';

        // Получаем название события по EVENT_ID
        $eventName = '';
        if ($eventId) {
            $eventName = $this->getEventNameById($eventId);
        }

        // Получаем FIO
        $fioProperty = $propertyCollection->getItemByOrderPropertyCode('FIO');
        $fio         = $fioProperty ? $fioProperty->getValue() : '';

        // Получаем EMAIL
        $emailProperty = $propertyCollection->getItemByOrderPropertyCode('EMAIL');
        $email         = $emailProperty ? $emailProperty->getValue() : '';

        return [
            'EVENT_NAME' => $eventName,
            'FIO'        => $fio,
            'EMAIL'      => $email
        ];
    }

    /**
     * Получение названия события по ID
     */
    private function getEventNameById(string $eventId): string
    {
        if (empty($eventId) || $eventId <= 0) {
            return '';
        }

        try {
            $eventEntity = new ORM\Query\Query('Custom\Core\Events\EventsTable');
            $query       = $eventEntity
                ->setSelect(['UF_NAME'])
                ->setFilter(['ID' => $eventId])
                ->exec();
            $event = $query->fetch();
            return $event['UF_NAME'] ?? '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Получение списка статусов брони
     */
    private function getReservationStatus(string $code = ''): array
    {
        $filter = [
            'CODE'           => 'RESERVATION_STATUS',
            'PERSON_TYPE_ID' => self::LEGAL_PERSON_TYPE_ID,
        ];

        if (!empty($code)) $filter['VARIANT_CODE'] = $code;

        $result = \Bitrix\Sale\Internals\OrderPropsTable::getList(
            [
                'select'  => [
                    'VARIANT_ID'   => 'REF_VARIANT.ID',
                    'VARIANT_CODE' => 'REF_VARIANT.VALUE',
                    'VARIANT_NAME' => 'REF_VARIANT.NAME'
                ],
                'filter'  => $filter,
                'runtime' => [
                    new \Bitrix\Main\Entity\ReferenceField(
                        'REF_VARIANT',
                        '\Bitrix\Sale\Internals\OrderPropsVariantTable',
                        ['this.ID' => 'ref.ORDER_PROPS_ID'],
                        ['join_type' => 'LEFT']
                    )
                ]
            ]
        );

        if (!empty($code)) return $result->fetch() ?? [];
        else return $result->fetchAll() ?? [];
    }
}