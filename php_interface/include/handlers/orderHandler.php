<?php

namespace Local\PhpInterface\Handlers;
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/include/handlers/BarcodeHandlerTrait.php');
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/crest/crest.php');

use Bitrix\Main\Loader;
use Bitrix\Main\ORM;
use Bitrix\Main\Diag\Debug;
use Bitrix\Highloadblock as HL;
use Custom\Core\Contract as ContractCore;

Loader::includeModule('sale');
Loader::includeModule('custom.core');
\CBitrixComponent::includeComponentClass("custom:basket");

class OrderHandler {
    use BarcodeHandlerTrait;

    protected $eventID;
    protected $series;
    protected const LEGAL_PERSON_TYPE_ID = 2;

    public function __construct() {}

    public function setTicketSeries()
    {
        if (!$this->series) {
            $entity        = \Custom\Core\Events\EventsTable::getEntity();
            $entityClass   = $entity->getDataClass();
            $entityElement = $entityClass::getByPrimary($this->eventID);
            if ($objEvent = $entityElement->fetchObject()) {
                $this->series = $objEvent->getUfSeries();
            }
        }
        return $this;
    }

    public function getBasketItems($order)
    {
        $basket = $order->getBasket();
        $basketItems = $basket->getBasketItems();

        return $basketItems;
    }

    public function OnSaleOrderPayed($order)
    {
        \Custom\Core\Helper::setOrderTicketsBarcodeStatus($order->getId(), "sold");

        $orderPrice = $order->getPrice() - $order->getDeliveryPrice();

        if ($orderPrice > 0) {
            $propertyCollection = $order->getPropertyCollection();

            $property = $propertyCollection->getItemByOrderPropertyCode("COOPERATION_PERCENT");
            $percent = $property->getValue();

            $property = $propertyCollection->getItemByOrderPropertyCode("ORGANIZER_ID");
            $companyId = $property->getValue();

            if ($percent)
                $orderPrice = $orderPrice - $orderPrice * ($percent / 100);
            else
                $orderPrice = $orderPrice;

            $balanceEntity = HL\HighloadBlockTable::compileEntity('BalanceHistory');
            $hlbClassBalance = $balanceEntity->getDataClass();

            $enumType = ContractCore::getHLfileldEnumId(
                \Custom\Core\BalanceHistory::HL_NAME,
                \Custom\Core\BalanceHistory::BH_TYPE,
                "up"
            );

            $enumDescription = ContractCore::getHLfileldEnumId(
                \Custom\Core\BalanceHistory::HL_NAME,
                \Custom\Core\BalanceHistory::BH_DESCRIPTION,
                "tickets"
            );

            $balance = $hlbClassBalance::getList(
                [
                    'filter' => [
                        'UF_COMPANY_ID' => $companyId,
                    ],
                    'limit'  => 1,
                    'order'  => ['ID' => 'DESC'],
                ]
            )->fetch();

            if ($balance['ID'] < 1) {
                $newBalance = $orderPrice;
            } else {
                $newBalance = $balance['UF_BALANCE'] + $orderPrice;
            }

            $resAdd = $hlbClassBalance::add(
                [
                    'UF_COMPANY_ID' => $companyId,
                    'UF_TYPE'       => $enumType,
                    'UF_VALUE'      => $orderPrice,
                    'UF_BALANCE'    => $newBalance,
                    'UF_DESCRIPTION' => $enumDescription
                ]
            );
        }
    }

    public function OnSaleOrderSaved(\Bitrix\Main\Event $e)
    {
        static $recursion = false;
        if ($recursion) {
            return;
        }

        $order     = $e->getParameter("ENTITY");
        $oldValues = $e->getParameter("VALUES");
        $personTypeId = $order->getField('PERSON_TYPE_ID');

        if ($e->getParameter("IS_NEW")
            || ($order->getField('CANCELED') != 'Y' && $oldValues['CANCELED'] == 'Y')) {
            $basketItems = $this->getBasketItems($order);

            \BasketCustom::changeOfferQuantity($basketItems);
        } elseif ($order->getField('CANCELED') == 'Y' && $oldValues['CANCELED'] != 'Y') {
            $basketItems = $this->getBasketItems($order);

            \BasketCustom::changeOfferQuantity($basketItems, true);

            \Custom\Core\Helper::setOrderTicketsBarcodeStatus($order->getId(), "canceled");

            $propertyCollection = $order->getPropertyCollection();

            $eventPropOb = $propertyCollection->getItemByOrderPropertyCode('EVENT_ID');

            $eventID = false;

            if ($eventPropOb)
                $eventID = $eventPropOb->getValue();

            if ($eventID) {
                $this->eventID = $eventID;
                $this->setTicketSeries();
                if ($this->series) {
                    $this->handleBarcodeGeneration();
                }
            }

            if ($personTypeId == self::LEGAL_PERSON_TYPE_ID) {
                $dealID = $order->getPropertyCollection()->getItemByOrderPropertyCode('B24_DEAL_ID')->getValue();

                $result = \CRest::call(
                    'crm.legal.payments.cancel',
                    [
                        'id'    => $dealID,
                        'stage' => 'expired',
                    ]
                );
                if ((int)$result["result"]["item"]["id"] < 1) {
                    \Bitrix\Main\Diag\Debug::writeToFile([['id' => $dealID, 'stage' => 'expired',], $result], 'crm.legal.payments.cancel', '_B24_support_error.txt');
                } else {
                    $reservationProp = $order->getPropertyCollection()
                        ->getItemByOrderPropertyCode('RESERVATION_STATUS');
                    if ($reservationProp) {
                        $reservationProp->setValue('ONP');
                    }

                    $recursion = true;
                    $order->save();
                    $recursion = false;
                }
            }
        }

        if ($order->getField('PAYED') == 'Y' && $oldValues['PAYED'] == 'N') {
            $this->OnSaleOrderPayed($order);
        }
        if ($e->getParameter("IS_NEW") && $personTypeId == self::LEGAL_PERSON_TYPE_ID) {
            $serverName   = 'https://' . \Bitrix\Main\Config\Option::get('main', 'server_name', '');
            $relativePath = $serverName . "/upload/invoice/order/счет-оферта-{$order->getField("ACCOUNT_NUMBER")}.pdf";
            // Формируем данные для отправки письма
            $propertyCollection = $order->getPropertyCollection();
            $emailProperty      = $propertyCollection->getItemByOrderPropertyCode('EMAIL');
            $email              = $emailProperty ? $emailProperty->getValue() : '';
            $arFields = [
                'ORDER_REAL_ID' => $order->getId(),
                'ORDER_ID'      => $order->getField('ACCOUNT_NUMBER'),
                'INVOICE_LINK'  => $relativePath,
                'EMAIL' => $email
            ];

            // Отправляем событие SALE_ORDER_PAID для формирования билетов
            \CEvent::Send(
                'SALE_NEW_LEGAL_ORDER',
                SITE_ID,
                $arFields
            );
        }

    }
}