<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/include/handlers/RefundsHandler.php');
use Bitrix\Main\Loader;
use Bitrix\Main\Entity;
use Bitrix\Main\ORM;
use Bitrix\Main\Diag\Debug;
use Local\PhpInterface\Handlers\EventLocationHandler;
use Bitrix\Main\ORM\Data\DataManager;
use Local\PhpInterface\Handlers\RefundsHandler;

Loader::includeModule('custom.core');
Loader::includeModule('highloadblock');
Loader::includeModule('iblock');
Loader::includeModule('sale');

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'TicketRefundRequestsOnBeforeUpdate',
    function(\Bitrix\Main\Event $e) {
        $id = (int)$e->getParameter('id')['ID'];
        $arParams = $e->getParameter('fields');
        $arParams['ID'] = $id;

        $objRefund = new RefundsHandler($arParams);
        if ($objRefund->isNeedSetDateRefund($arParams['UF_DATE_TIME_REFUND'] ?? null)) {
            $result = new Entity\EventResult();
            $result->modifyFields(
                [
                    'UF_DATE_TIME_REFUND' => new \Bitrix\Main\Type\DateTime()
                ]
            );

            return $result;
        }
    }
);

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'TicketRefundRequestsOnAfterUpdate',
    function (\Bitrix\Main\Event $e) {
        $id = (int)$e->getParameter('id')['ID'];
        $arParams = $e->getParameter('fields');
        $arParams['ID'] = $id;
        $objRefund = new RefundsHandler($arParams);
        $objRefund->refundProcess();
    }
);

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'TicketRefundRequestsOnUpdate',
    function (\Bitrix\Main\Event $e) {
        $id = (int)$e->getParameter('id')['ID'];
        $arParams = $e->getParameter('fields');
        $arParams['ID'] = $id;
        $objRefund = new RefundsHandler($arParams);
        $objRefund->refundProcess();
        $objRefund->beforeUpdateProcess();
        $objRefund->sendMailNotification();
    }
);

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'TicketRefundRequestsOnAfterAdd',
    function (\Bitrix\Main\Event $e) {
        $arParams = $e->getParameter('fields');
        $objRefund = new RefundsHandler($arParams);
        $objRefund->afterAddProcess();
    }
);