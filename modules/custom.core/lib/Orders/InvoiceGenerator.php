<?php

namespace Custom\Core\Orders;
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use Mpdf\Mpdf;
use Bitrix\Main\ORM;

class InvoiceGenerator
{
    private $mpdf;
    private $orderId;
    private $orderData;
    private $orderProps;
    private $basketItems;
    private $eventData;
    private $companyParams;

    public function __construct($orderId = null)
    {
        $this->mpdf = new Mpdf(
            [
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 20,
                'default_font' => 'dejavusans'
            ]
        );

        if ($orderId) {
            $this->orderId = $orderId;
            $this->loadOrderData();
        }

        $this->loadCompanyParams();
    }

    private function formatDateWithMonth($dateString)
    {
        if (empty($dateString))
            return '';

        $months = [
            1 => 'января',
            2 => 'февраля',
            3 => 'марта',
            4 => 'апреля',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'августа',
            9 => 'сентября',
            10 => 'октября',
            11 => 'ноября',
            12 => 'декабря'
        ];

        $timestamp = strtotime($dateString);
        if ($timestamp === false)
            return '';

        $day = date('j', $timestamp);
        $month = (int) date('n', $timestamp);

        return $day . ' ' . $months[$month];
    }

    private function loadOrderData()
    {
        if (!$this->orderId)
            return;

        $this->loadOrderDataD7();
    }

    private function loadOrderDataD7()
    {
        $order = \Bitrix\Sale\Order::load($this->orderId);
        if (!$order) {
            throw new \Exception("Заказ #{$this->orderId} не найден");
        }

        $this->orderData = [
            'ID' => $order->getId(),
            'ACCOUNT_NUMBER' => $order->getField('ACCOUNT_NUMBER'),
            'DATE_INSERT' => $order->getDateInsert()->format('Y-m-d H:i:s'),
            'PRICE' => $order->getPrice(),
            'CURRENCY' => $order->getCurrency(),
            'USER_ID' => $order->getUserId(),
            'STATUS_ID' => $order->getField('STATUS_ID'),
            'DELIVERY_ID' => $order->getField('DELIVERY_ID'),
            'PRICE_DELIVERY' => $order->getField('PRICE_DELIVERY'),
        ];

        $this->loadOrderProperties($order);
        $this->loadEventData();
        $this->loadBasketItems($order);
    }

    private function loadOrderProperties($order)
    {
        $this->orderProps = [];
        $propertyCollection = $order->getPropertyCollection();
        foreach ($propertyCollection as $property) {
            $code = $property->getField('CODE');
            $value = $property->getValue();
            $this->orderProps[$code] = $value;
        }
    }

    private function loadBasketItems($order)
    {
        $this->basketItems = [];
        $basket = $order->getBasket();

        if (!$basket)
            return;

        foreach ($basket as $basketItem) {
            $item = [
                'ID' => $basketItem->getId(),
                'NAME' => $basketItem->getField('NAME'),
                'PRICE' => $basketItem->getPrice(),
                'QUANTITY' => $basketItem->getQuantity(),
                'CURRENCY' => $basketItem->getCurrency(),
                'PRODUCT_ID' => $basketItem->getProductId(),
                'PROPS' => [],
                'DATES' => [] // Добавляем поле для дат
            ];

            $propertyCollection = $basketItem->getPropertyCollection();
            if ($propertyCollection) {
                foreach ($propertyCollection as $property) {
                    $code = $property->getField('CODE');
                    $value = $property->getField('VALUE');
                    if ($code && $value) {
                        $item['PROPS'][$code] = [
                            'CODE' => $code,
                            'VALUE' => $value,
                            'NAME' => $property->getField('NAME')
                        ];
                    }
                }
            }

            $this->basketItems[] = $item;
        }

        // Заполняем даты для элементов корзины
        if (!empty($this->eventData) && isset($this->eventData['ID'])) {
            $this->populateBasketItemsDates();
        }
    }

    private function loadEventData()
    {
        if (empty($this->orderProps['EVENT_ID'])) {
            $this->eventData = $this->getDefaultEventData();
            return;
        }

        try {
            if (class_exists('\Custom\Core\Events')) {
                $this->eventData = $this->getEventData($this->orderProps['EVENT_ID']);
                // Получаем даты события в формате Y-m-d H:i:s
                \Custom\Core\Events::getEventData($this->orderProps['EVENT_ID'], $this->eventData, true);
            } else {
                $this->eventData = $this->getDefaultEventData();
            }
        } catch (\Exception $e) {
            $this->eventData = $this->getDefaultEventData();
        }
    }

    private function getDefaultEventData()
    {
        return [
            'NAME' => 'Мероприятие',
            'LOCATION' => ['EVENT_DATE' => [], 'ADDRESS' => ''],
            'UF_RESERVATION_VALIDITY_PERIOD' => 1
        ];
    }

    private function getEventData(int $eventID)
    {
        $productEntity = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
        $propFieldEventID = $productEntity->getField('EVENT_ID');
        $propEventIDEntity = $propFieldEventID->getRefEntity();
        $propFieldClosed = $productEntity->getField('IS_CLOSED_EVENT');
        $propFieldClosedEntity = $propFieldClosed->getRefEntity();

        $eventEntity = new ORM\Query\Query('Custom\Core\Events\EventsTable');
        $query = $eventEntity
            ->setSelect(
                [
                    'ID',
                    'UF_UUID',
                    'UF_NAME',
                    'IMG_SRC',
                    'UF_COMPANY_ID',
                    'UF_PAY_SYSTEM',
                    'PRODUCT_ID' => 'PRODUCT.IBLOCK_ELEMENT_ID',
                    'IS_CLOSED' => 'EVENT_CLOSED.VALUE',
                    'UF_RESERVATION_VALIDITY_PERIOD',
                ]
            )
            ->setFilter(['ID' => $eventID])
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'PRODUCT',
                    $propEventIDEntity,
                    ['this.ID' => 'ref.VALUE'],
                    ['join_type' => 'LEFT']
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'EVENT_CLOSED',
                    $propFieldClosedEntity,
                    ['this.PRODUCT_ID' => 'ref.IBLOCK_ELEMENT_ID'],
                    ['join_type' => 'LEFT']
                )
            )
            ->exec();

        $event = $query->fetch();
        $event['LOCATION'] = $this->getEventLocations($eventID);
        return $event;
    }

    private function getEventLocations(int $eventID)
    {
        $eventLocationsEntity = new ORM\Query\Query('Custom\Core\Events\EventsDateAndLocationTable');
        $eventLocationsEntity->setSelect(['UF_DATE_TIME', 'UF_ADDRESS', 'UF_DURATION']);
        $eventLocationsEntity->setFilter(['UF_EVENT_ID' => $eventID]);
        $eventLocations = $eventLocationsEntity->exec();

        $locations = ['ADDRESS' => '', 'EVENT_DATE' => []];

        while ($location = $eventLocations->fetch()) {
            $eventDate = unserialize($location['UF_DATE_TIME']);
            if (is_array($eventDate)) {
                $locations['EVENT_DATE'] = array_merge($locations['EVENT_DATE'], $eventDate);
            }
            $locations['ADDRESS'] = $location['UF_ADDRESS'];
        }

        return $locations;
    }

    private function loadCompanyParams()
    {
        $defaults = [
            'COMPANY_NAME' => 'ООО «ФАНТАМ»',
            'INN' => '9715428584',
            'KPP' => '773101001',
            'ADDRESS' => 'Муниципальный округ Можайский, ул. Верейская, д. 29, стр. 33, пом. 1Н/22',
            'INDEX' => '121357',
            'CITY' => 'Москва',
            'RSCH_BANK' => 'АО "АЛЬФА-БАНК"',
            'BIK' => '044525593',
            'KSCH' => '30101810200000000593',
            'RSCH' => '40702810002320004323',
            'BUYER_COMPANY_NAME' => $this->orderProps['COMPANY'] ?? '',
            'BUYER_INN' => $this->orderProps['INN'] ?? '',
            'BUYER_KPP' => $this->orderProps['KPP'] ?? '',
            'BUYER_ADDRESS' => $this->orderProps['ADDRESS'] ?? '',
        ];

        $this->companyParams = [
            'COMPANY_NAME' => \Bitrix\Main\Config\Option::get('main', 'company_name', $defaults['COMPANY_NAME']),
            'INN' => \Bitrix\Main\Config\Option::get('main', 'company_inn', $defaults['INN']),
            'KPP' => \Bitrix\Main\Config\Option::get('main', 'company_kpp', $defaults['KPP']),
            'ADDRESS' => \Bitrix\Main\Config\Option::get('main', 'company_address', $defaults['ADDRESS']),
            'INDEX' => \Bitrix\Main\Config\Option::get('main', 'company_index', $defaults['INDEX']),
            'CITY' => \Bitrix\Main\Config\Option::get('main', 'company_city', $defaults['CITY']),
            'RSCH_BANK' => \Bitrix\Main\Config\Option::get('main', 'bank_name', $defaults['RSCH_BANK']),
            'BIK' => \Bitrix\Main\Config\Option::get('main', 'bank_bik', $defaults['BIK']),
            'KSCH' => \Bitrix\Main\Config\Option::get('main', 'bank_ksch', $defaults['KSCH']),
            'RSCH' => \Bitrix\Main\Config\Option::get('main', 'bank_rsch', $defaults['RSCH']),
            'BUYER_COMPANY_NAME' => $this->orderProps['COMPANY'] ?? '',
            'BUYER_INN' => $this->orderProps['INN'] ?? '',
            'BUYER_KPP' => $this->orderProps['KPP'] ?? '',
            'BUYER_ADDRESS' => $this->orderProps['ADDRESS'] ?? '',
        ];
    }

    public function generateInvoiceByOrderId($orderId = null)
    {
        if ($orderId) {
            $this->orderId = $orderId;
            $this->loadOrderData();
        }

        if (!$this->orderData) {
            throw new \Exception("Заказ #{$this->orderId} не найден в базе данных");
        }

        $data = $this->prepareInvoiceData();
        return $this->generateInvoice($data);
    }

    public function orderExists($orderId)
    {
        try {
            $order = \Bitrix\Sale\Order::load($orderId);
            return !empty($order);
        } catch (\Exception $e) {
            return false;
        }
    }

    private function prepareInvoiceData()
    {
        $serverName = \Bitrix\Main\Config\Option::get('main', 'server_name', 'voroh.ru');

        if (!$this->orderData) {
            throw new \Exception("Данные заказа не загружены");
        }

        $totalAmount = floatval($this->orderData['PRICE'] ?? 0);
        $dateInsert = $this->formatDateWithMonth($this->orderData['DATE_INSERT'] ?? date('Y-m-d'));
        $year = date('Y', strtotime($this->orderData['DATE_INSERT'] ?? 'now'));

        [$ticketItems, $totalTicketPrice, $serviceFee] = $this->processBasketItems();
        [$deliveryId, $deliveryName, $deliveryPrice] = $this->processDelivery();

        if ($deliveryPrice > 0) {
            $serviceFee = $deliveryPrice;
        }

        if (empty($deliveryName) && $serviceFee > 0) {
            $deliveryName = 'Доставка';
        }

        $organizerInfo = $this->formatOrganizerInfo();

        $customerName = $this->resolveCustomerName();
        $customerInn = $this->resolveCustomerInn();

        return [
            'invoice_number' => $this->orderData['ACCOUNT_NUMBER'] ?? '',
            'invoice_date' => $dateInsert,
            'invoice_year' => $year,
            'customer_name' => $customerName,
            'customer_inn' => $customerInn,
            'customer_address' => $this->companyParams['BUYER_ADDRESS'] ?: ($this->orderProps['COMPANY_ADR'] ?? ''),
            'customer_email' => $this->orderProps['EMAIL'] ?? '',
            'event_name' => $this->eventData['UF_NAME'] ?? 'Мероприятие',
            'event_date' => $this->formatBasketDates(),
            'event_location' => $this->eventData['LOCATION']['ADDRESS'] ?? '',
            'organizer_info' => $organizerInfo,
            'ticket_items' => $ticketItems,
            'ticket_quantity' => array_sum(array_column($ticketItems, 'quantity')),
            'ticket_price' => count($ticketItems) > 0 ? $ticketItems[0]['price'] : 0,
            'ticket_total' => $totalTicketPrice,
            'service_fee' => $serviceFee,
            'service_total' => $serviceFee,
            'delivery_id' => $deliveryId,
            'delivery_name' => $deliveryName,
            'total_amount' => $totalAmount,
            'total_words' => $this->convertPriceToWords($totalAmount, $this->orderData['CURRENCY'] ?? 'RUB'),
            'payment_deadline' => date('d.m.Y', strtotime($this->orderData['DATE_INSERT'] . ' +' . ($this->eventData['UF_RESERVATION_VALIDITY_PERIOD'] ?? 1) . ' days')),
            'offer_valid_until' => date('d.m.Y', strtotime($this->orderData['DATE_INSERT'] . ' +' . ($this->eventData['UF_RESERVATION_VALIDITY_PERIOD'] ?? 1) . ' days')),
            'event_link' => 'https://' . $serverName . '/event/' . self::getProductIDbyEventID($this->orderProps['EVENT_ID']) . '/',
            'company_data' => $this->companyParams
        ];
    }

    private function resolveCustomerName(): string
    {
        $candidates = [
            $this->orderProps['COMPANY'] ?? null,
            $this->orderProps['COMPANY_NAME'] ?? null,
            $this->orderProps['LEGAL_NAME'] ?? null,
            $this->orderProps['ORG_NAME'] ?? null,
            $this->companyParams['BUYER_COMPANY_NAME'] ?? null,
        ];

        foreach ($candidates as $value) {
            if (!is_string($value))
                continue;
            $name = trim($value);
            if ($name === '')
                continue;
            if ($this->isInnLike($name))
                continue;
            return $name;
        }

        return '';
    }

    private function resolveCustomerInn(): string
    {
        $innCandidates = [
            $this->orderProps['INN'] ?? null,
            $this->companyParams['BUYER_INN'] ?? null,
            $this->orderProps['COMPANY'] ?? null,
        ];

        foreach ($innCandidates as $value) {
            if (!is_string($value))
                continue;
            $digits = preg_replace('/[^0-9]/', '', $value);
            if ($digits === null)
                continue;
            $len = strlen($digits);
            if ($len === 10 || $len === 12)
                return $digits;
        }

        return '';
    }

    private function isInnLike(string $value): bool
    {
        $digits = preg_replace('/[^0-9]/', '', $value);
        if ($digits === null)
            return false;
        $len = strlen($digits);
        return $len === 10 || $len === 12;
    }

    private function processBasketItems()
    {
        $ticketItems = [];
        $totalTicketPrice = 0;
        $serviceFee = 0;

        if (empty($this->basketItems)) {
            return [$ticketItems, $totalTicketPrice, $serviceFee];
        }

        foreach ($this->basketItems as $item) {
            $itemNameLower = strtolower($item['NAME'] ?? '');

            if ($this->isServiceItem($itemNameLower)) {
                $serviceFee += floatval($item['PRICE'] ?? 0) * floatval($item['QUANTITY'] ?? 0);
                continue;
            }

            $ticketData = $this->parseTicketItem($item);
            $totalTicketPrice += $ticketData['total'];
            $ticketItems[] = $ticketData;
        }

        return [$ticketItems, $totalTicketPrice, $serviceFee];
    }

    private function isServiceItem($itemName)
    {
        $serviceKeywords = ['сервис', 'доставка', 'delivery'];
        foreach ($serviceKeywords as $keyword) {
            if (strpos($itemName, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function parseTicketItem($item)
    {
        $rawName = $item['NAME'] ?? '';
        $eventName = '';
        $ticketType = '';

        if (preg_match('/^(.+?)\s*\[(.+?)\]$/', $rawName, $matches)) {
            $eventName = trim($matches[1]);
            $ticketType = trim($matches[2]);
        } else {
            $eventName = $rawName;
        }

        $locationParts = [];
        if (!empty($item['PROPS']) && is_array($item['PROPS'])) {
            $locationMap = ['SECTOR' => 'Сектор', 'ROW' => 'Ряд', 'PLACE' => 'Место'];

            foreach ($locationMap as $code => $label) {
                if (!empty($item['PROPS'][$code]['VALUE'])) {
                    $locationParts[] = $label . ' ' . $item['PROPS'][$code]['VALUE'];
                }
            }
        }

        return [
            'event_name' => $eventName,
            'ticket_type' => $ticketType,
            'location' => implode(', ', $locationParts),
            'quantity' => $item['QUANTITY'] ?? 0,
            'price' => floatval($item['PRICE'] ?? 0),
            'total' => floatval($item['PRICE'] ?? 0) * floatval($item['QUANTITY'] ?? 0)
        ];
    }

    private function processDelivery()
    {
        $deliveryId = $this->orderData['DELIVERY_ID'] ?? '';
        $deliveryName = '';
        $deliveryPrice = floatval($this->orderData['PRICE_DELIVERY'] ?? 0);

        if (!empty($deliveryId)) {
            try {
                $delivery = \Bitrix\Sale\Delivery\Services\Manager::getObjectById($deliveryId);
                if ($delivery) {
                    $deliveryName = $delivery->getNameWithParent();
                }
            } catch (\Exception $e) {
                // Используем значение по умолчанию
            }
        }

        return [$deliveryId, $deliveryName, $deliveryPrice];
    }

    private function formatOrganizerInfo()
    {
        if (empty($this->orderProps['ORGANIZER_ID'])) {
            return '';
        }

        $organizer = self::getOrganizerInfo((int) $this->orderProps['ORGANIZER_ID']);
        if (!$organizer) {
            return '';
        }

        return sprintf(
            '%s, ИНН %s, %s, e‑mail: %s',
            $organizer['UF_FULL_NAME'] ?? '',
            $organizer['UF_INN'] ?? '',
            $organizer['UF_REGISTRATION_ADDRESS'] ?? '',
            $organizer['UF_EMAIL'] ?? ''
        );
    }

    private function convertPriceToWords($price, $currency = 'RUB')
    {
        if ($currency == "RUR" || $currency == "RUB" || empty($currency)) {
            return $this->numberToWords($price);
        } else {
            return number_format($price, 2, ',', ' ') . ' ' . $currency;
        }
    }

    public function generateInvoice($data = [])
    {
        $data = $this->fillDefaults($data);
        $html = $this->getHTMLTemplate($data);
        $this->mpdf->WriteHTML($html);
        return $this->mpdf->Output('', 'S');
    }

    public function saveInvoice($data, $filename)
    {
        // Получаем директорию из полного пути к файлу
        $directory = dirname($filename);

        // Проверяем существует ли директория
        if (!is_dir($directory)) {
            // Создаем директорию рекурсивно с правами 0755
            if (!mkdir($directory, 0755, true)) {
                throw new \Exception("Не удалось создать директорию: {$directory}");
            }
        }

        $pdf = is_int($data) ? $this->generateInvoiceByOrderId($data) : $this->generateInvoice($data);

        // Сохраняем файл
        if (file_put_contents($filename, $pdf) === false) {
            throw new \Exception("Не удалось сохранить файл: {$filename}");
        }
    }

    public function outputInvoice($data)
    {
        try {
            while (ob_get_level()) {
                ob_end_clean();
            }

            if (is_int($data)) {
                if ($data != $this->orderId) {
                    $this->orderId = $data;
                    $this->loadOrderData();
                }
                $invoiceData = $this->prepareInvoiceData();
                $html = $this->getHTMLTemplate($invoiceData);
            } else {
                $data = $this->fillDefaults($data);
                $html = $this->getHTMLTemplate($data);
            }

            $filename = "счет-оферта-{$this->orderData["ACCOUNT_NUMBER"]}.pdf";

            $this->mpdf->WriteHTML($html);
            $this->mpdf->Output($filename, 'D');
        } catch (\Exception $e) {
            header('Content-Type: text/plain; charset=utf-8');
            echo "Ошибка генерации PDF: " . $e->getMessage();
        }
    }

    private function fillDefaults($data)
    {
        $defaults = [
            'invoice_number' => '',
            'invoice_date' => date('d.m.Y'),
            'invoice_year' => date('Y'),
            'invoice_month' => $this->getRussianMonth(date('n')),
            'customer_name' => '',
            'customer_inn' => '',
            'customer_address' => '',
            'customer_email' => '',
            'event_name' => '',
            'event_date' => '',
            'event_location' => '',
            'organizer_info' => '',
            'ticket_items' => [],
            'ticket_name' => '',
            'ticket_quantity' => 0,
            'ticket_price' => 0.00,
            'ticket_total' => 0.00,
            'service_fee' => 0.00,
            'service_total' => 0.00,
            'delivery_id' => '',
            'delivery_name' => '',
            'total_amount' => 0.00,
            'total_words' => '',
            'payment_deadline' => date('d.m.Y', strtotime('+3 days')),
            'offer_valid_until' => date('d.m.Y', strtotime('+7 days')),
            'event_link' => '',
            'company_data' => $this->companyParams ?? []
        ];

        $data = array_merge($defaults, $data);

        if (empty($data['total_words']) && !empty($data['total_amount'])) {
            $data['total_words'] = $this->numberToWords($data['total_amount']);
        }

        return $data;
    }

    public function formatDates($arDates = [])
    {
        if (empty($arDates))
            return '';

        $result = '';
        $startDate = null;
        $prevDate = null;

        usort(
            $arDates,
            function ($a, $b) {
                return strtotime($a) - strtotime($b);
            }
        );

        foreach ($arDates as $date) {
            $currentDate = new \DateTime($date);

            if ($startDate === null) {
                $startDate = $currentDate;
                $prevDate = $currentDate;
            } else {
                $diff = $currentDate->diff($prevDate);
                $sameTime = $currentDate->format('H:i:s') === $prevDate->format('H:i:s');

                if ($diff->days === 1 && $sameTime) {
                    $prevDate = $currentDate;
                } else {
                    if ($startDate === $prevDate) {
                        $result .= FormatDate("d F Y", $startDate->getTimestamp()) . ' в ' . FormatDate("H:i", $startDate->getTimestamp()) . '<br>';
                    } else {
                        $result .= FormatDate("d F Y", $startDate->getTimestamp()) . " - " . FormatDate("d F Y", $prevDate->getTimestamp()) . ' в ' . FormatDate("H:i", $prevDate->getTimestamp()) . '<br>';
                    }
                    $startDate = $currentDate;
                    $prevDate = $currentDate;
                }
            }
        }

        // Добавляем последний диапазон или дату
        if ($startDate === $prevDate) {
            $result .= FormatDate("d F Y", $startDate->getTimestamp()) . ' в ' . FormatDate("H:i", $startDate->getTimestamp()) . '<br>';
        } else {
            $result .= FormatDate("d F Y", $startDate->getTimestamp()) . " - " . FormatDate("d F Y", $prevDate->getTimestamp()) . ' в ' . FormatDate("H:i", $prevDate->getTimestamp()) . '<br>';
        }

        return $result;
    }

    private function getHTMLTemplate($data)
    {
        $companyData = $data['company_data'] ?? $this->companyParams;

        return '<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Счет-оферта</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; line-height: 1.4; margin: 0; padding: 0; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #000; padding: 8px; text-align: left; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .no-border { border: none; }
    </style>
</head>
<body>
<div style="max-width: 800px; margin: 0 auto; padding: 20px;">

    <!-- Предупреждение об оплате -->
    <p style="margin: 15px 0; font-weight: bold;">
        Внимание! Оплата данного счета-оферты означает полное согласие с условиями, изложенными в нем оказания услуг по оформлению для Заказчика билетов на Мероприятие. Уведомление об оплате обязательно, в ином случае отсутствует возможность гарантировать наличие билетов на Мероприятие.
    </p>

    <p style="text-align: center;margin: 0;"><b>Платежные реквизиты</b></p>
    
    ' . $this->generateBankDetailsTable($companyData, $data) . '
    
    <!-- Шапка счета -->
    <div style="text-align:center; margin-bottom:15px;">
        <p style="margin:0 0 0;"><b>Счет‑оферта</b></p>
        <p style="margin:0;">от "' . $data['invoice_date'] . '" ' . $data['invoice_year'] . ' г. № ' . $data['invoice_number'] . '</p>
    </div>
    
    <!-- Стороны сделки -->
    <p style="margin:0 0 20px;">
        <strong>Исполнитель:</strong> ' . ($companyData['COMPANY_NAME'] ?? 'ООО «ФАНТАМ»') . ', ИНН ' . ($companyData['INN'] ?? '') . ', ' . ($companyData['INDEX'] ?? '') . ', ' . ($companyData['CITY'] ? 'г.' . $companyData['CITY'] : '') . ', ' . ($companyData['ADDRESS'] ?? '') . ', e‑mail: support@voroh.ru
    </p>
    <p style="margin:0 0 20px;"><strong>Заказчик:</strong> ' . $data['customer_name'] . ', ИНН ' . $data['customer_inn'] . ', ' . $data['customer_address'] . ', e‑mail ' . $data['customer_email'] . '</p>
    
    <!-- Данные мероприятия -->
    <p style="margin:0 0 0;">Мероприятие: ' . $data['event_name'] . '</p>
    <p style="margin:0 0 0;">Дата и время проведения Мероприятия: ' . $data['event_date'] . '</p>
    <p style="margin:0 0 0;">Место проведения Мероприятия: ' . $data['event_location'] . '</p>
    <p style="margin:0 0 20px;">Организатор Мероприятия: ' . $data['organizer_info'] . '</p>
    
    ' . $this->generateItemsTable($data) . '
    
    <p style="padding-left: 25px;margin: 0;">Итого к оплате: ' . $data['total_words'] . '.</p>
    
    ' . $this->generateTermsAndConditions($data) . '
    
    <hr style="margin: 35px 0;">
    
    <p style="margin-bottom:20px;">
        Настоящее предложение действительно до "' . $data['offer_valid_until'] . ' г." включительно.</p>
    
    <!-- Подпись -->
    <p style="margin-bottom:20px;padding-left: 20px;">
        Генеральный директор ООО «ФАНТАМ»</p>
    <p style="margin-bottom:20px;padding-left: 20px;">' .
            $this->getSignatureImage() . ' Давыдов Э.А. 
    </p>

</div>
</body>
</html>';
    }

    private function generateBankDetailsTable($companyData, $data)
    {
        return '<table style="border-collapse: collapse; width: 100%;margin-bottom: 30px;">
        <tr>
            <td colspan="2" rowspan="2" style="border:1px solid #000; padding:8px;">Банк получателя: ' . ($companyData['RSCH_BANK'] ?? 'АО "АЛЬФА-БАНК"') . '</td>
            <td style="border:1px solid #000; padding:8px;">БИК</td>
            <td style="border:1px solid #000; border-bottom: none; padding:8px;">' . ($companyData['BIK'] ?? '044525593') . '</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:8px;">Кор/сч. №</td>
            <td style="border:1px solid #000; border-top:none; padding:8px;">' . ($companyData['KSCH'] ?? '30101810200000000593') . '</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:8px;">ИНН ' . ($companyData['INN'] ?? '9715428584') . '</td>
            <td style="border:1px solid #000; padding:8px;">КПП ' . ($companyData['KPP'] ?? '773101001') . '</td>
            <td rowspan="2" style="border:1px solid #000; padding:8px;">Расч/сч. №</td>
            <td rowspan="2" style="border:1px solid #000; padding:8px;">' . ($companyData['RSCH'] ?? '40702810002320004323') . '</td>
        </tr>
        <tr>
            <td colspan="2" style="border:1px solid #000; padding:8px;">Получатель: ' . ($companyData['COMPANY_NAME'] ?? 'ООО «ФАНТАМ»') . '</td>
        </tr>
        <tr>
            <td colspan="4" style="border:1px solid #000; padding:8px;">Назначение платежа: Оплата счета-оферты № ' . $data['invoice_number'] . ' от ' . $data['invoice_date'] . ' ' . $data['invoice_year'] . ' г.</td>
        </tr>
    </table>';
    }

    private function generateItemsTable($data)
    {
        $html = '<table style="width:100%; border-collapse:collapse; margin-bottom:30px; font-size: 12px;">
        <tr>
            <td style="border:1px solid #000; padding:4px; font-weight:bold;">№ п/п</td>
            <td style="border:1px solid #000; padding:4px; font-weight:bold;">Наименование товара (услуг)</td>
            <td style="border:1px solid #000; padding:4px; font-weight:bold;">Ед. изм.</td>
            <td style="border:1px solid #000; padding:4px; font-weight:bold;">Кол‑во</td>
            <td style="border:1px solid #000; padding:4px; font-weight:bold;">Цена за ед., руб.</td>
            <td style="border:1px solid #000; padding:4px; font-weight:bold;">Стоимость, руб.</td>
        </tr>';

        $rowNum = 1;

        // Строки билетов
        if (!empty($data['ticket_items']) && is_array($data['ticket_items'])) {
            foreach ($data['ticket_items'] as $ticket) {
                $ticketName = 'Билет на мероприятие «' . ($ticket['event_name'] ?? $data['event_name']) . '»';

                if (!empty($ticket['ticket_type'])) {
                    $ticketName .= '<br/>' . htmlspecialchars($ticket['ticket_type']);
                }

                if (!empty($ticket['location'])) {
                    $ticketName .= '<br/>' . htmlspecialchars($ticket['location']);
                }

                $html .= '<tr>
                    <td style="border:1px solid #000; padding:4px; text-align:center;">' . $rowNum . '</td>
                    <td style="border:1px solid #000; padding:4px;">' . $ticketName . '</td>
                    <td style="border:1px solid #000; padding:4px; text-align:center;">шт.</td>
                    <td style="border:1px solid #000; padding:4px; text-align:center;">' . ($ticket['quantity'] ?? 0) . '</td>
                    <td style="border:1px solid #000; padding:4px; text-align:right;">' . number_format((float) ($ticket['price'] ?? 0), 2, ',', ' ') . ' руб.</td>
                    <td style="border:1px solid #000; padding:4px; text-align:right;">' . number_format((float) ($ticket['total'] ?? 0), 2, ',', ' ') . ' руб.</td>
                </tr>';
                $rowNum++;
            }
        }

        // Строка доставки
        if (!empty($data['delivery_id']) || (float) $data['service_fee'] > 0) {
            $deliveryName = $data['delivery_name'] ?? 'Сервисный сбор';
            $html .= '<tr>
                <td style="border:1px solid #000; padding:4px; text-align:center;">' . $rowNum . '</td>
                <td style="border:1px solid #000; padding:4px;">' . htmlspecialchars($deliveryName) . '</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;"></td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">1</td>
                <td style="border:1px solid #000; padding:4px; text-align:right;">' . number_format((float) $data['service_fee'], 2, ',', ' ') . ' руб.</td>
                <td style="border:1px solid #000; padding:4px; text-align:right;">' . number_format((float) $data['service_total'], 2, ',', ' ') . ' руб.</td>
            </tr>';
        }

        // Итоговые строки
        $html .= '<tr>
            <td colspan="5" style="border:1px solid #000; padding:4px; text-align:right;">Всего к оплате:</td>
            <td style="border:1px solid #000; padding:4px; text-align:right;">' . number_format((float) $data['total_amount'], 2, ',', ' ') . ' руб.</td>
        </tr>
        <tr>
            <td colspan="5" style="border:1px solid #000; padding:4px; text-align:right;">НДС:</td>
            <td style="border:1px solid #000; padding:4px; text-align:right;">-</td>
        </tr>
    </table>';

        return $html;
    }

    private function generateTermsAndConditions($data)
    {
        $serverName = \Bitrix\Main\Config\Option::get('main', 'server_name', 'voroh.ru');

        return '<!-- Дополнительные условия -->
    <p style="margin: 0;">
        Перед формированием настоящего счета-оферты Заказчик акцептует, ставит галочку, чем подтверждает, что согласен с условиями Пользовательского соглашения и правилами работы сайта
    и Договора на оказание услуг по организации мероприятия,
        <a href="https://' . $serverName . '/documents/user_agreement_and_terms_of_use.pdf">https://' . $serverName . '/documents/user_agreement_and_terms_of_use.pdf</a>
        <a href="https://' . $serverName . '/documents/org_buyer_contract_offer.pdf">https://' . $serverName . '/documents/org_buyer_contract_offer.pdf</a>
        размещенных на сайте Исполнителя.
    </p>

    <p style="margin: 0;">Настоящий счет-оферта является письменным предложением (офертой) Исполнителя заключить договор, который направляется Заказчику в соответствии со ст. ст. 432 - 444 Гражданского кодекса Российской Федерации. Договор заключается путем принятия оферты Заказчиком в установленном порядке (п. 3 ст. 438 Гражданского кодекса Российской Федерации), что считается соблюдением письменной формы договора (п. 3 ст. 434 Гражданского кодекса Российской Федерации). </p>

    <p style="text-align: center;margin: 5px 0;"><b>Условия оферты</b></p>

    <p style="margin: 0;">1. Исполнитель, действующий от своего имени, но за счет Организатора мероприятия, на основании Лицензионного договора, оказывает Заказчику и/или для лиц, указанных Заказчиком услуги по оформлению для Заказчика и/или, указанных Заказчиком лиц билетов на Мероприятие (далее по тексту – Услуги). Стоимость Услуги состоит из стоимости участия в Мероприятия (далее-
        <b>Билет</b>) и Сервисного сбора.</p>
    <p style="margin: 0;">2. Заказчик обязуется оплатить Услуги на условиях 100% предоплаты в срок до ' . $data['payment_deadline'] . '.</p>
    <p style="margin: 0;">3. Исполнитель направляет Заказчику соответствующее количество Билетов и акт оказанных услуг на электронную почту, в течении 3 (трех) рабочих дней после оплаты Услуг. Услуга считается принятой Заказчиком без замечаний, а акт оказанных услуг подписанным, если в течение 3 (трех) рабочих дней с момента получения акта не поступило никаких возражений в адрес Исполнителя. Стоимость Услуг НДС не облагается, так как Исполнитель применяет упрощённую систему налогообложения в соответствии с п. 2 ст. 346.11 НК РФ.</p>
    <p style="margin: 0;">4. Заказчик уведомлен, что Исполнитель не является Организатором и не может влиять на качество Мероприятия. За качество проведения Мероприятия, его отмену, перенос, прочие организационные действия ответственность несет Организатор, что так же указано в Пользовательском соглашении и правилах работы сайта
        <a href="https://' . $serverName . '/documents/user_agreement_and_terms_of_use.pdf">https://' . $serverName . '/documents/user_agreement_and_terms_of_use.pdf</a>
        .
    </p>
    <p style="margin: 0;">5. Заказчик самостоятельно знакомится с информацией о Мероприятии, размещенной на <a href="' . ($data['event_link'] ?: 'сайте организатора') . '">' . ($data['event_link'] ?: 'сайте организатора') . '</a>' . '</p>
    <p style="margin: 0;">6. В случае отмены Мероприятия Организатором, в соответствии с Договором на оказание услуг по организации мероприятия
        <a href="https://' . $serverName . '/documents/org_buyer_contract_offer.pdf">https://' . $serverName . '/documents/org_buyer_contract_offer.pdf</a>
        , Организатор направляет соответствующее уведомление на электронную почту Заказчика и производит возврат денежных средств за Билет на Мероприятие, за исключением Сервисного сбора, самостоятельно или путем привлечения Исполнителя. Перечисление денежных средств производится на расчетный счет Заказчика, с которого Заказчик производил оплату услуг по оформлению билетов на Мероприятие, если иное не согласовано Сторонами, в течение трех рабочих дней с даты направления уведомления об отмене мероприятия в адрес Заказчика.
    </p>
    <p style="margin: 0;">7. В случае отказа Заказчика от участия в Мероприятии, Заказчик должен, путем активации соответствующей функции на сайте
        <a href="https://' . $serverName . '/">https://' . $serverName . '/</a>
        , оформить запрос на возврат денежных средств. Указанный запрос рассматривается Организатором, после чего Заказчику направляется уведомление о порядке и сроках осуществления возврата. В случае получения указания от Организатора Исполнитель возвращает Заказчику денежные средства, оплаченные за оказанные Услуги, за исключением Сервисного сбора, в размере, указанном Организатором или Организатор самостоятельно производит возврат. В любом случае возврат осуществляется путем перечисления денежных средств на расчетный счет Заказчика, с которого Заказчик производил оплату услуг по оформлению билетов на Мероприятие, если иное не согласовано Сторонами в течение 5 (Пяти) рабочих дней с даты оформления запроса. Вопросы, касающиеся размера, сроков и порядка возврата стоимости Билетов на Мероприятие, разрешаются Заказчиком непосредственно с Организатором по средствам электронной почты.
    </p>
    <p style="margin: 0;">8. Уведомления, сообщения и документы направляются и считаются полученными Сторонами в случае направления их по адресам электронной почты, указанной в реквизитах счета-оферты.</p>
    <p style="margin: 0;">9. Для подготовки необходимых документов по Договору может использоваться факсимильное воспроизведение подписей Сторон. Настоящим Стороны подтверждают, что документы, претензии, подписанные с помощью факсимильного воспроизведения подписи, имеют юридическую силу и обязательны для рассмотрения и принятия Сторонами.</p>
    <p style="margin: 0;">10. Комплект документов о выполненных работах направляется заказчику по его обращению на электронную почту Исполнителя, указанную в реквизитах счета-оферты и/или ЭДО.</p>';
    }

    private function getSignatureImage()
    {
        $signaturePath = $_SERVER['DOCUMENT_ROOT'] . '/upload/img/signature/signature.png';

        if (file_exists($signaturePath)) {
            $signatureBase64 = base64_encode(file_get_contents($signaturePath));
            return '<img src="data:image/png;base64,' . $signatureBase64 . '" style="height: 55px; vertical-align: top; margin-right: 10px; margin-top: -20px;" alt="Подпись">';
        }

        return '__________________';
    }

    private function getRussianMonth($month)
    {
        $months = [
            1 => 'января',
            2 => 'февраля',
            3 => 'марта',
            4 => 'апреля',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'августа',
            9 => 'сентября',
            10 => 'октября',
            11 => 'ноября',
            12 => 'декабря'
        ];

        return $months[$month] ?? '';
    }

    public function numberToWords(float $amount): string
    {
	    $amount = round($amount, 2);
	    $rubles = floor($amount);
	    $kopecks = round(($amount - $rubles) * 100);
	    
	    $rubText = $this->morph($rubles, "рубль", "рубля", "рублей");
	    $kopText = $this->morph($kopecks, "копейка", "копейки", "копеек");
	    
	    $rubWords = $this->ucFirst(mb_strtolower($this->numToStr($rubles), 'UTF-8'));
	    
	    return number_format($rubles, 0, '', ' ')
	           . " $rubText "
	           . sprintf('%02d', $kopecks)
	           . " $kopText ("
	           . $rubWords . " $rubText "
	           . sprintf('%02d', $kopecks)
	           . " $kopText)";
    }
	
	private function ucFirst(string $text): string
	{
		$first = mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8');
		$rest = mb_substr($text, 1, null, 'UTF-8');
		return $first . $rest;
	}
	
	private function morph(int $n, string $f1, string $f2, string $f5): string
	{
		$n = abs($n) % 100;
		if ($n > 10 && $n < 20) return $f5;
		$n = $n % 10;
		if ($n > 1 && $n < 5) return $f2;
		if ($n == 1) return $f1;
		
		return $f5;
	}
	
    private function numToStr(int $num): string
    {
	    $nul = 'ноль';
	    $ten = [
		    ['', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
		    ['', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
	    ];
	    $a20 = ['десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать'];
	    $tens = [2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят','восемьдесят','девяносто'];
	    $hundred = ['','сто','двести','триста','четыреста','пятьсот','шестьсот','семьсот','восемьсот','девятьсот'];
	    $unit = [
		    ['копейка','копейки','копеек',1],
		    ['рубль','рубля','рублей',0],
		    ['тысяча','тысячи','тысяч',1],
		    ['миллион','миллиона','миллионов',0],
		    ['миллиард','миллиарда','миллиардов',0],
	    ];
	    
	    if ($num == 0) return $nul;
	    
	    $out = [];
	    $parts = explode('.', sprintf("%015.2f", floatval($num)));
	    $rub = $parts[0];
	    
	    if (intval($rub) > 0) {
		    $rub = str_split($rub,3);
		    foreach ($rub as $uk=>$v) {
			    if (!intval($v)) continue;
			    $uk = sizeof($rub)-$uk; // единицы/тысячи/миллионы/...
			    $gender = $unit[$uk][3];
			    list($i1,$i2,$i3) = array_map('intval',str_split($v,1));
			    $out[] = $hundred[$i1];
			    if ($i2 > 1) {
				    $out[] = $tens[$i2].' '.$ten[$gender][$i3];
			    } else {
				    $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3];
			    }
			    if ($uk > 1) $out[] = $this->morph($v,$unit[$uk][0],$unit[$uk][1],$unit[$uk][2]);
		    }
	    } else {
		    $out[] = $nul;
	    }
	    
	    return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
    }

    public static function getOrganizerInfo(int $orgId): array
    {
        $eventEntity = new ORM\Query\Query('Custom\Core\Users\CompaniesTable');
        $query = $eventEntity
            ->setSelect(
                [
                    'ID',
                    'UF_FULL_NAME',
                    'UF_INN',
                    'UF_EMAIL',
                    'UF_REGISTRATION_ADDRESS',
                    'COMPANY_TYPE' => 'COMPANY_TYPE_REF.XML_ID',
                    'CONTRACT_STATUS' => 'CONTRACT_REF.XML_ID'
                ]
            )
            ->registerRuntimeField(
                'CONTRACT_REF',
                [
                    'data_type' => '\Custom\Core\FieldEnumTable',
                    'reference' => ['=this.UF_CONTRACT' => 'ref.ID'],
                    'join_type' => 'LEFT'
                ]
            )
            ->registerRuntimeField(
                'COMPANY_TYPE_REF',
                [
                    'data_type' => '\Custom\Core\FieldEnumTable',
                    'reference' => ['=this.UF_TYPE' => 'ref.ID'],
                    'join_type' => 'LEFT'
                ]
            )
            ->setFilter(['ID' => $orgId])
            ->exec();
        $result = $query->fetch();
        return !is_array($result) ? [] : $result;
    }

    public static function getProductIDbyEventID(int $eventID): int
    {
        $productEntity = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
        $query = new ORM\Query\Query($productEntity);
        $resProduct = $query
            ->setSelect(['ID'])
            ->setFilter(['EVENT_ID.VALUE' => $eventID])
            ->exec();

        $resProduct = $resProduct->fetch();
        return (int) $resProduct['ID'];
    }

    /**
     * Заполняет даты для элементов корзины аналогично BasketCustom
     */
    private function populateBasketItemsDates(): void
    {
        if (empty($this->eventData['ID']) || !class_exists('\Custom\Core\Helper')) {
            return;
        }

        $arProducts = \Custom\Core\Helper::getTicketsOffers($this->eventData['ID']);

        foreach ($this->basketItems as &$item) {
            // Сначала устанавливаем все даты события
            $item['DATES'] = $this->eventData['UF_DATE_TIME'] ?? [];

            // Фильтруем по SKU_DATES если они есть
            foreach ($arProducts as $product) {
                if ($product['SKU_ID'] == $item['PRODUCT_ID'] && !empty($product['SKU_DATES'])) {
                    // Фильтруем уже установленные даты по SKU_DATES
                    $filteredDates = [];
                    foreach ($item['DATES'] as $eventDate) {
                        $eventDateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $eventDate);
                        if (!$eventDateTime)
                            continue;

                        foreach ($product['SKU_DATES'] as $skuDate) {
                            $skuDateTime = \DateTime::createFromFormat('d.m.y', $skuDate);
                            if (!$skuDateTime)
                                continue;

                            // Сравниваем только даты (без времени)
                            if ($eventDateTime->format('Y-m-d') === $skuDateTime->format('Y-m-d')) {
                                $filteredDates[] = $eventDate;
                                break;
                            }
                        }
                    }
                    $item['DATES'] = $filteredDates;
                    break;
                }
            }
        }
        unset($item);
    }

    /**
     * Форматирует даты из билетов в корзине для отображения в счете
     */
    private function formatBasketDates(): string
    {
        if (empty($this->basketItems)) {
            return $this->formatDates($this->eventData['LOCATION']['EVENT_DATE'] ?? []);
        }

        // Собираем все даты из билетов в корзине
        $allDates = [];
        foreach ($this->basketItems as $item) {
            if (!empty($item['DATES']) && is_array($item['DATES'])) {
                $allDates = array_merge($allDates, $item['DATES']);
            }
        }

        // Удаляем дубликаты и сортируем
        $allDates = array_unique($allDates);
        if (empty($allDates)) {
            // Если нет дат в билетах, используем даты события
            return $this->formatDates($this->eventData['LOCATION']['EVENT_DATE'] ?? []);
        }

        // Сортируем даты
        usort($allDates, function ($a, $b) {
            $dateA = \DateTime::createFromFormat('Y-m-d H:i:s', $a);
            $dateB = \DateTime::createFromFormat('Y-m-d H:i:s', $b);
            return $dateA <=> $dateB;
        });

        return $this->formatDatePresentation($allDates);
    }

    /**
     * Форматирует даты для презентации аналогично BasketCustom::formatDatePresentation
     */
    private function formatDatePresentation(array $targetDates): string
    {
        if (empty($targetDates)) {
            return '';
        }

        // Конвертируем строки дат в объекты DateTime
        $dateObjects = [];
        foreach ($targetDates as $dateStr) {
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
            if ($dateTime) {
                $dateObjects[] = $dateTime;
            }
        }

        if (empty($dateObjects)) {
            return '';
        }

        // Сортируем даты по возрастанию
        usort($dateObjects, function ($a, $b) {
            return $a <=> $b;
        });

        // Проверяем, последовательны ли даты и одинаково ли время
        if (count($dateObjects) > 1) {
            $firstTime = $dateObjects[0]->format('H:i');
            $allSameTime = true;
            $isSequential = true;

            // Проверяем одинаковое время
            foreach ($dateObjects as $date) {
                if ($date->format('H:i') !== $firstTime) {
                    $allSameTime = false;
                    break;
                }
            }

            // Проверяем последовательность дат
            if ($allSameTime && count($dateObjects) > 1) {
                for ($i = 1; $i < count($dateObjects); $i++) {
                    $prevDate = clone $dateObjects[$i - 1];
                    $prevDate->modify('+1 day');
                    if ($prevDate->format('Y-m-d') !== $dateObjects[$i]->format('Y-m-d')) {
                        $isSequential = false;
                        break;
                    }
                }
            } else {
                $isSequential = false;
            }

            // Если даты последовательны и время одинаково
            if ($isSequential && $allSameTime) {
                $firstDate = $dateObjects[0];
                $lastDate = end($dateObjects);

                $firstDay = $firstDate->format('j');
                $lastDay = $lastDate->format('j');
                $firstMonth = $this->getRussianMonth((int) $firstDate->format('n'));
                $lastMonth = $this->getRussianMonth((int) $lastDate->format('n'));
                $firstYear = $firstDate->format('Y');
                $lastYear = $lastDate->format('Y');
                $time = $firstDate->format('H:i');

                // Если разные годы
                if ($firstYear !== $lastYear) {
                    return "{$firstDay} {$firstMonth} {$firstYear} - {$lastDay} {$lastMonth} {$lastYear} {$time}";
                }
                // Если разные месяцы, но одинаковый год
                elseif ($firstMonth !== $lastMonth) {
                    return "{$firstDay} {$firstMonth} - {$lastDay} {$lastMonth} {$lastYear} {$time}";
                }
                // Если одинаковый месяц и год
                else {
                    return "{$firstDay} - {$lastDay} {$lastMonth} {$lastYear} {$time}";
                }
            }
        }

        // Если даты не последовательны или время разное, выводим отдельными строками
        $result = [];
        foreach ($dateObjects as $date) {
            $day = $date->format('j');
            $month = $this->getRussianMonth((int) $date->format('n'));
            $year = $date->format('Y');
            $time = $date->format('H:i');

            $result[] = "{$day} {$month} {$year} {$time}";
        }

        return implode('<br>', $result);
    }
}







