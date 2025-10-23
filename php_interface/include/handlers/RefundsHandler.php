<?php

namespace Local\PhpInterface\Handlers;
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/include/handlers/BarcodeHandlerTrait.php');

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM;
use Custom\Core\TelegramBot;
use Custom\Core\Traits\PropertyEnumTrait;

Loader::includeModule('iblock');
Loader::includeModule('sale');
Loader::includeModule('catalog');

class RefundsHandler {
    use BarcodeHandlerTrait, PropertyEnumTrait {
        PropertyEnumTrait::getPropertiesEnum insteadof BarcodeHandlerTrait;
    }

    public $refundID;
    public $reviewStatus;
    protected $eventID;
    protected $series;
    protected $actualRefundSum;
    protected $arParams;
    protected $serverName;

    public function __construct($arParams = [])
    {
        $this->refundID        = $arParams['ID'];
        $this->reviewStatus    = $arParams['UF_REVIEW_STATUS'];
        $this->actualRefundSum = (float)$arParams['UF_ACTUAL_REFUND_SUM'];
        $this->arParams        = $arParams;
        $this->serverName      = 'https://' . \Bitrix\Main\Config\Option::get('main', 'server_name', '');
    }

    public function getStatusList(): array
    {
        return $this->getPropertiesEnum('TicketRefundRequests', 'UF_REVIEW_STATUS') ?? [];
    }

    public function getRejectReasonList()
    {
        return $this->getPropertiesEnum('TicketRefundRequests', 'UF_REASON_FOR_REFUSAL') ?? [];
    }

    public function sendMailNotification()
    {
        $statusList    = $this->getStatusList();
        $refundRequest = $this->getRefundRequest($this->refundID);
        $currentStatus = $statusList[$refundRequest['UF_REVIEW_STATUS']]['ENUM_XML_ID'];
        $newStatus     = $statusList[$this->reviewStatus]['ENUM_XML_ID'];

        // Базовые параметры для всех уведомлений
        $baseParams           = $this->getBaseNotificationParams($refundRequest);

        if ($newStatus == 'reject' && $currentStatus == 'pending') {
            $rejectReason = $this->getRejectReasonList()[$this->arParams['UF_REASON_FOR_REFUSAL']]['ENUM_NAME'];
            $additionalParams = [
                'REJECT_REASON'  => $rejectReason,
                'REJECT_COMMENT' => $this->arParams['UF_COMMENT_ABOUT_REJECTION']
            ];
            $this->sendNotificationEvent('REFUND_REJECT', $baseParams, $additionalParams);
        }

        if ($newStatus == 'partial' && $currentStatus == 'pending') {
            $requestedAmount = $this->getRequestedAmount($refundRequest['UF_BASKET_ITEM_ID'], $this->getOrderBasket($refundRequest['UF_ORDER_ID']));
            $this->sendNotificationEvent('PARTIAL_REFUND', $baseParams, ['REQUESTED_AMOUNT' => $requestedAmount]);
        }

        if ($newStatus == 'independently' && $currentStatus == 'pending') {
            $this->sendNotificationEvent('SELF_REFUND', $baseParams);
        }

        if ($newStatus == 'commission' && $currentStatus == 'pending') {
            $this->sendNotificationEvent('COMMISSION_REFUND', $baseParams);
        }

        if ($newStatus == 'fully' && $currentStatus == 'pending') {
            $eventType = ($refundRequest['EVENT_STATUS'] == 'cancelled')
                ? 'EVENT_CANCELED_FULLY_REFUND'
                : 'FULLY_REFUND';
            $this->sendNotificationEvent($eventType, $baseParams);
        }

        return $this;
    }

    public function sendNotificationBuyer(int $eventID, string $orderNum, int $refundSum, string $names)
    {
        $objEvent = $this->getEventObject($eventID);
        $productID = $this->getProductIdByEventId($eventID);

        $params = [
            'EMAIL'             => $this->arParams['UF_EMAIL'],
            'TICKET_TYPE_NAMES' => $names,
            'FULL_NAME'         => $this->arParams['UF_FULL_NAME'],
            'SERVER_NAME'       => $this->serverName,
            'EVENT_ID'          => $eventID,
            'PRODUCT_ID'        => $productID,
            'EVENT_NAME'        => $objEvent->getUfName(),
            'ORDER_NUM'         => $orderNum,
            'REFUND_SUM'        => $refundSum
        ];

        \CEvent::Send("BUYER_REFUND_TICKETS", SITE_ID, $params);
    }

    public function sendNotificationOrganizer()
    {
        $orderID  = $this->arParams['UF_ORDER_ID'];
        $arFields = $this->getOrderInfo($orderID);
        if (count($arFields) > 0) {
            $arFields['SERVER_NAME'] = $this->serverName;
            \CEvent::Send("REFUND_NOTICE", SITE_ID, $arFields);
        }
    }

    private function getOrderInfo(int $id): array
    {
        $dbRes = \Bitrix\Sale\Order::getList(
            [
                'select'  => [
                    'ID',
                    'ORGANIZER_ID'  => 'PROPERTY_ORGANIZER.VALUE',
                    'EVENT_ID'      => 'PROPERTY_EVENT_ID.VALUE',
                    'EVENT_NAME'    => 'EVENT.UF_NAME',
                    'OWNER_USER_ID' => 'PROFILES_REF.UF_USER_ID',
                    'EMAIL'         => 'USER_REF.EMAIL',
                    'ACCOUNT_NUMBER',
                    'FULL_NAME'
                ],
                'filter'  => [
                    'ID'                       => $id,
                    "PAYED"                    => "Y",
                    "PROPERTY_ORGANIZER.CODE"  => 'ORGANIZER_ID',
                    "PROPERTY_EVENT_ID.CODE"   => 'EVENT_ID',
                    "PROFILES_REF.UF_IS_OWNER" => true
                ],
                'runtime' => [
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PROPERTY_ORGANIZER',
                        'Bitrix\Sale\Internals\OrderPropsValueTable',
                        ['=this.ID' => 'ref.ORDER_ID'],
                        ['join_type' => 'inner']
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PROPERTY_EVENT_ID',
                        'Bitrix\Sale\Internals\OrderPropsValueTable',
                        ['=this.ID' => 'ref.ORDER_ID'],
                        ['join_type' => 'inner']
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'EVENT',
                        'Custom\Core\Events\EventsTable',
                        ['=this.EVENT_ID' => 'ref.ID'],
                        ['join_type' => 'inner']
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PROFILES_REF',
                        'Custom\Core\Users\UserProfilesTable',
                        ['=this.ORGANIZER_ID' => 'ref.UF_COMPANY_ID'],
                        ['join_type' => 'inner']
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'USER_REF',
                        'Bitrix\Main\UserTable',
                        ['=this.OWNER_USER_ID' => 'ref.ID'],
                        ['join_type' => 'inner']
                    ),
                    new \Bitrix\Main\Entity\ExpressionField(
                        'FULL_NAME', 'CONCAT(%s, " ", %s)', ['USER_REF.NAME', 'USER_REF.LAST_NAME']
                    ),
                ],
            ]
        );
        $res   = $dbRes->fetch();

        if (!is_array($res)) return [];
        else return $res;
    }

    /**
     * @param string|null $refundDateTime
     *
     * @return bool
     */
    public function isNeedSetDateRefund(?string $refundDateTime): bool
    {
        $statusList         = $this->getStatusList();
        $newStatus          = $statusList[$this->reviewStatus]['ENUM_XML_ID'];

        if ((($newStatus == 'refunded') || ($newStatus == 'independently')) && !$refundDateTime)  {
            return true;
        }

        return false;
    }

    public function refundProcess()
    {
        $statusList         = $this->getStatusList();
        $refundRequest      = $this->getRefundRequest($this->refundID);
        $currentStatus      = $statusList[$refundRequest['UF_REVIEW_STATUS']]['ENUM_XML_ID'];
        $newStatus          = $statusList[$this->reviewStatus]['ENUM_XML_ID'];
        $orderID            = $refundRequest['UF_ORDER_ID'];
        $basket             = $this->getOrderBasket($orderID);
        $needBarcodesUpdate = false;

        if (
            (
                ($newStatus == 'refunded') ||
                ($newStatus == 'independently')
            )
        ) {

            foreach ($refundRequest['UF_BASKET_ITEM_ID'] as $ticketID) {
                if (key_exists($ticketID, $basket) && $basket[$ticketID]['IS_REFUNDED'] != 'Y') {

                    $productID = $basket[$ticketID]['PRODUCT_ID'];
                    $barcodeID = $basket[$ticketID]['BARCODE'];

                    $this->returnProductQty($productID);
                    $this->setRefundedBarcode($barcodeID);
                    $this->setPropertyRefunded($ticketID);
                    $needBarcodesUpdate = true;
                }
            }

            if ($needBarcodesUpdate) {
                $this->handleBarcodeGeneration(true);
            }

        }
        return $this;
    }

    public function beforeUpdateProcess()
    {
        $statusList    = $this->getStatusList();
        $refundRequest = $this->getRefundRequest($this->refundID);
        $currentStatus = $statusList[$refundRequest['UF_REVIEW_STATUS']]['ENUM_XML_ID'];
        $newStatus     = $statusList[$this->reviewStatus]['ENUM_XML_ID'];
        $orderID       = $refundRequest['UF_ORDER_ID'];
        $basket        = $this->getOrderBasket($orderID);

        if ($newStatus == 'reject' && $currentStatus != 'reject') {

            $this->setFinalStatusOrder($orderID);

            foreach ($basket as $item) {
                if (in_array($item['ID'], $refundRequest['UF_BASKET_ITEM_ID'])) {
                    $this->setSoldOutBarcode($item['BARCODE']);
                }
            }
        }

        if (in_array($newStatus, ['fully', 'partial', 'commission']) && $currentStatus == 'pending') {
            $requestedAmount = $this->getRequestedAmount($refundRequest['UF_BASKET_ITEM_ID'], $basket);

            if (!$this->arParams['UF_ACTUAL_REFUND_SUM']) $this->arParams['UF_ACTUAL_REFUND_SUM'] = $requestedAmount;
            $this->arParams['UF_ACTUAL_REFUND_SUM'] < $requestedAmount ? $coefficient = $this->arParams['UF_ACTUAL_REFUND_SUM'] / $requestedAmount : $coefficient = 1;
            foreach ($refundRequest['UF_BASKET_ITEM_ID'] as $ticketID) {
                if (key_exists($ticketID, $basket) && $basket[$ticketID]['IS_REFUNDED'] != 'Y') {
                    $this->setPropertyRefundedSum($ticketID, $basket[$ticketID]['PRICE'] * $coefficient);
                }
            }

            $this->setRefundOrderStatus($orderID);
        }

        if ($newStatus == 'independently' && $currentStatus == 'pending') {
            $requestedAmount = $this->getRequestedAmount($refundRequest['UF_BASKET_ITEM_ID'], $basket);

            if (!$this->arParams['UF_ACTUAL_REFUND_SUM']) $this->arParams['UF_ACTUAL_REFUND_SUM'] = $requestedAmount;
            $this->arParams['UF_ACTUAL_REFUND_SUM'] < $requestedAmount ? $coefficient = $this->arParams['UF_ACTUAL_REFUND_SUM'] / $requestedAmount : $coefficient = 1;
            foreach ($refundRequest['UF_BASKET_ITEM_ID'] as $ticketID) {
                if (key_exists($ticketID, $basket) && $basket[$ticketID]['IS_REFUNDED'] != 'Y') {
                    $this->setPropertyRefundedSum($ticketID, $basket[$ticketID]['PRICE'] * $coefficient);
                }
            }

            $this->setRefundOrderStatus($orderID);
        }
        return $this;
    }

    public function afterAddProcess()
    {
        $orderID        = (int)$this->arParams['UF_ORDER_ID'];
        $basketItemsIDs = unserialize($this->arParams['UF_BASKET_ITEM_ID']);
        $order = $this->loadOrder($orderID);

        if (!$order) return;

        $objBasket = $order->getBasket();
        $basket    = $objBasket->toArray();

        $names = array_column($basket, 'NAME') ?? [];
        $names = implode(',', $names);
        $names = $this->processTicketTypeNames($names);

        $eventID     = $order->getPropertyCollection()->getItemByOrderPropertyCode('EVENT_ID')->getValue();
        $eventStatus = $this->getCurrentEventStatus($eventID);
        $refundSum   = 0;

        $this->setOrderStatus($orderID, 'RR');

        foreach ($basket as $item) {
            if (in_array($item['ID'], $basketItemsIDs)) {
                $refundSum += $item['PRICE'];
                foreach ($item['PROPERTIES'] as $prop) {
                    if ($prop['CODE'] == 'BARCODE' && !empty($prop['VALUE'])) {
                        $this->setRequestRefundBarcode($prop['VALUE']);
                    }
                }
            }
        }

        if ($eventStatus != 7) {
            $this->sendNotificationBuyer($eventID, $order->getField('ACCOUNT_NUMBER'), $refundSum, $names);
            $this->sendNotificationOrganizer();
        }
    }

    public function setRequestRefundBarcode(int $barcode_id): bool
    {
        $statusID = $this->getPropertiesEnum('Barcodes', 'UF_STATUS', 'ID', 'request_refund');
        return $this->updateBarcodeStatus($barcode_id, $statusID);
    }

    private function setSoldOutBarcode(int $barcode_id): bool
    {
        return $this->updateBarcodeStatus($barcode_id, 64);
    }

    public function setRefundedBarcode(int $barcode_id): bool
    {
        return $this->updateBarcodeStatus($barcode_id, 66);
    }

    /**
     * Общий метод для обновления статуса штрихкода
     */
    private function updateBarcodeStatus(int $barcode_id, int $statusId): bool
    {
        $query         = new ORM\Query\Query('Custom\Core\Tickets\BarcodesTable');
        $entityBarcode = $query->getEntity();
        $objBarcode    = $entityBarcode->wakeUpObject($barcode_id);
        $objBarcode->set('UF_STATUS', $statusId);
        $res = $objBarcode->save();
        return $res->isSuccess();
    }

    public function returnProductQty($productID): bool
    {
        $objCatalog = \Bitrix\Catalog\ProductTable::wakeUpObject($productID);
        $objCatalog->fillQuantity();
        $qty = (int)$objCatalog->getQuantity() + 1;
        $objCatalog->setQuantity($qty);
        $res = $objCatalog->save();
        return $res->isSuccess();
    }

    public function setEventID(int $id)
    {
        $this->eventID = $id;
        return $this;
    }

    public function setTicketSeries()
    {
        if (!$this->series) {
            $objEvent     = $this->getEventObject($this->eventID);
            $this->series = $objEvent->getUfSeries();
        }
        return $this;
    }

    public function getCurrentEventStatus($eventID)
    {
        $objEvent = $this->getEventObject($eventID);
        return $objEvent->getUfStatus();
    }

    public function getEventID()
    {
        return $this->eventID;
    }

    public function setPropertyRefunded($productID): void
    {
        $this->addBasketProperty($productID, 'IS_REFUNDED', 'IS_REFUNDED', 'Y');
    }

    public function setPropertyRefundedSum(int $productID, int $refundPrice): void
    {
        $this->addBasketProperty($productID, 'REFUNDED_PRICE', 'REFUNDED_PRICE', $refundPrice);
    }

    /**
     * Общий метод для добавления свойства в корзину
     */
    private function addBasketProperty(int $basketId, string $name, string $code, $value): void
    {
        \Bitrix\Sale\Internals\BasketPropertyTable::add(
            [
                'BASKET_ID' => $basketId,
                'NAME'      => $name,
                'CODE'      => $code,
                'VALUE'     => $value,
                'SORT'      => 100,
                'XML_ID'    => $code
            ]
        );
    }

    /**
     * Общий метод для получения объекта события
     */
    private function getEventObject(int $eventID): object
    {
        $entity        = \Custom\Core\Events\EventsTable::getEntity();
        $entityClass   = $entity->getDataClass();
        $entityElement = $entityClass::getByPrimary($eventID);
        return $entityElement->fetchObject();
    }

    /**
     * Общий метод для загрузки и работы с заказом
     */
    private function loadOrder(int $orderID): ?\Bitrix\Sale\Order
    {
        return \Bitrix\Sale\Order::load($orderID);
    }

    /**
     * Общий метод для установки статуса заказа
     */
    private function setOrderStatus(int $orderID, string $statusId): void
    {
        $order = $this->loadOrder($orderID);
        if ($order) {
            $order->setField('STATUS_ID', $statusId);
            $order->save();
        }
    }

    private function getRequestedAmount(array $basketItemsIDs, array $orderBasket): float
    {
        $sum = 0;
        foreach ($basketItemsIDs as $ticketID) {
            $sum += $orderBasket[$ticketID]['PRICE'];
        }
        return $sum;
    }

    public function getRefundRequest(int $id)
    {
        $productEntity = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
        $propField     = $productEntity->getField('EVENT_ID');
        $propEntity    = $propField->getRefEntity();

        $query      = new ORM\Query\Query('Custom\Core\Tickets\TicketRefundRequestsTable');
        $resRefunds = $query
            ->setSelect(
                [
                    '*',
                    'ORDER_NUM'    => 'ORDER_REF.ACCOUNT_NUMBER',
                    'EVENT_ID'     => 'PROPERTY_EVENT_ID.VALUE',
                    'EVENT_STATUS' => 'EVENT_REF.UF_STATUS',
                    'EVENT_NAME'   => 'EVENT_REF.UF_NAME',
                    'PRODUCT_ID'   => 'REF_PRODUCT_ID.IBLOCK_ELEMENT_ID',
                    'TICKET_TYPE_NAMES'
                ]
            )
            ->setFilter(['ID' => $id, "PROPERTY_EVENT_ID.CODE" => "EVENT_ID"])
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'ORDER_REF',
                    '\Bitrix\Sale\Order',
                    ['this.UF_ORDER_ID' => 'ref.ID'],
                    ['join_type' => 'left']
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'PROPERTY_EVENT_ID',
                    'Bitrix\Sale\Internals\OrderPropsValueTable',
                    ['=this.UF_ORDER_ID' => 'ref.ORDER_ID'],
                    ['join_type' => 'inner']
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'EVENT_REF',
                    'Custom\Core\Events\EventsTable',
                    ['this.EVENT_ID' => 'ref.ID'],
                    ['join_type' => 'left']
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'BASKET_REF',
                    '\Bitrix\Sale\Internals\BasketTable',
                    ['=this.BASKET_ITEM_ID.VALUE' => 'ref.ID'],
                    ['join_type' => 'inner']
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'REF_PRODUCT_ID',
                    $propEntity,
                    ['this.EVENT_ID' => 'ref.VALUE'],
                    ['join_type' => 'LEFT'],
                )
            )
            ->registerRuntimeField(new \Bitrix\Main\Entity\ExpressionField('TICKET_TYPE_NAMES', "GROUP_CONCAT(DISTINCT %s)", ['BASKET_REF.NAME']))
            ->exec();

        $res                 = $resRefunds->fetch();
        $res['EVENT_STATUS'] = $this->getStatusXmlID($res['EVENT_STATUS']);
        return $res;
    }

    public function getOrderBasket(int $orderID): array
    {
        $result = [];
        $order  = $this->loadOrder($orderID);
        if (!is_object($order)) return $result;
        $eventID = $order->getPropertyCollection()->getItemByOrderPropertyCode('EVENT_ID')->getValue();
        $this->setEventID($eventID)->setTicketSeries();
        $basket      = $order->getBasket();
        $basketItems = $basket->getBasketItems();

        foreach ($basketItems as $item) {
            $result[$item->getId()]                   = $item->getFields()->getValues();
            $basketPropertyCollection                 = $item->getPropertyCollection();
            $basketPropertyCollection                 = $basketPropertyCollection->getPropertyValues();
            $result[$item->getId()]['EVENT_ID']       = $eventID;
            $result[$item->getId()]['UUID']           = $basketPropertyCollection['UUID']['VALUE'];
            $result[$item->getId()]['BARCODE']        = $basketPropertyCollection['BARCODE']['VALUE'];
            $result[$item->getId()]['IS_REFUNDED']    = $basketPropertyCollection['IS_REFUNDED']['VALUE'];
            $result[$item->getId()]['REFUNDED_PRICE'] = $basketPropertyCollection['REFUNDED_PRICE']['VALUE'];
        }

        return $result;
    }

    private static function getStatusXmlID(int $id): string
    {

        $query     = new ORM\Query\Query('\Custom\Core\Events\EventsStatusTable');
        $resStatus = $query
            ->setSelect(['UF_XML_ID'])
            ->setOrder(['UF_SORT' => 'ASC'])
            ->setFilter(['ID' => $id])
            ->setCacheTtl(3600)
            ->exec();

        return $resStatus->fetch()['UF_XML_ID'];
    }

    private function setRefundOrderStatus(int $orderID): void
    {
        $order = $this->loadOrder($orderID);
        if (!$order) return;
        
        $currentStatus = $order->getField('STATUS_ID');
        if ($currentStatus == 'CD') return;

        $objBasket = $order->getBasket();
        $basket    = $objBasket->toArray();
        $returned  = 0;
        foreach ($basket as $item) {
            foreach ($item['PROPERTIES'] as $prop) {
                if ($prop['CODE'] == 'IS_REFUNDED') {
                    $returned++;
                }
            }
        }

        $statusId = ($returned == count($basket)) ? 'RF' : 'PR';
        $order->setField('STATUS_ID', $statusId);
        $order->save();
    }

    private function setFinalStatusOrder(int $orderID): void
    {
        $this->setOrderStatus($orderID, 'F');
    }

    public function processTicketTypeNames(string $ticketTypeNames): string
    {
        // Разбиваем строку по запятым
        $items = explode(',', $ticketTypeNames);

        // Извлекаем только текст в квадратных скобках
        $extractedItems = [];
        foreach ($items as $item) {
            $extracted = $this->extractTextFromBrackets(trim($item));
            if (!empty($extracted)) {
                $extractedItems[] = $extracted;
            }
        }

        // Убираем дубликаты
        $uniqueItems = array_unique($extractedItems);

        // Оборачиваем каждый элемент в HTML
        $wrappedItems = [];
        foreach ($uniqueItems as $item) {
            $wrappedItems[] = $this->wrapInParagraph($item);
        }

        return implode('', $wrappedItems);
    }

    /**
     * Извлекает текст из квадратных скобок
     */
    private function extractTextFromBrackets(string $text): string
    {
        if (preg_match('/\[([^\]]+)\]/', $text, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    /**
     * Оборачивает текст в HTML параграф с заданными стилями
     */
    private function wrapInParagraph(string $text): string
    {
        return '<p class="text-center fw500 float-center" align="center" style="Margin:0;Margin-bottom:0;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:500;line-height:150%;margin:0;margin-bottom:0;padding:0;text-align:center">' .
            $text .
            '</p>';
    }

    private function getProductIdByEventId($eventId)
    {
        $productEntity = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
        $productClass  = $productEntity->getDataClass();
        $query         = $productClass::getList(
            [
                'select' => ['ID'],
                'filter' => [
                    'EVENT_ID.VALUE' => $eventId
                ],
            ]
        );

        return $query->fetch()['ID'];

    }

    /**
     * Формирует базовые параметры для уведомлений
     */
    private function getBaseNotificationParams(array $refundRequest): array
    {
        return [
            'EMAIL'             => $refundRequest['UF_EMAIL'],
            'EVENT_ID'          => $refundRequest['EVENT_ID'],
            'EVENT_NAME'        => $refundRequest['EVENT_NAME'],
            'PRODUCT_ID'        => $refundRequest['PRODUCT_ID'],
            'FULL_NAME'         => $refundRequest['UF_FULL_NAME'],
            'REFUND_SUM'        => $this->actualRefundSum . ' ₽',
            'ORDER_NUM'         => $refundRequest['ORDER_NUM'],
            'SERVER_NAME'       => $this->serverName,
            'TICKET_TYPE_NAMES' => $this->processTicketTypeNames($refundRequest['TICKET_TYPE_NAMES'])
        ];
    }

    /**
     * Отправляет уведомление с базовыми и дополнительными параметрами
     */
    private function sendNotificationEvent(string $eventType, array $baseParams, array $additionalParams = []): void
    {
        $params = array_merge($baseParams, $additionalParams);
        \CEvent::Send($eventType, SITE_ID, $params);
    }
}