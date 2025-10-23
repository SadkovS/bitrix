<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/include/handlers/EventLocationHandler.php');
use Bitrix\Main\Loader;
use Bitrix\Main\ORM;
use Bitrix\Main\Diag\Debug;
use \Local\PhpInterface\Handlers\EventLocationHandler;
Loader::includeModule('custom.core');
Loader::includeModule('highloadblock');
Loader::includeModule('iblock');

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'EventDateAndLocationOnAfterUpdate',
    function (\Bitrix\Main\Event $e) {
        $arParams = $e->getParameter('fields');
        $eventID = (int)$arParams['UF_EVENT_ID'];
        //\Bitrix\Main\Diag\Debug::writeToFile($arParams, 'arParams', 'MAX_DATE.txt');
        $result = (new EventLocationHandler($eventID))
            ->setProductExpDate();
    }
);

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'EventDateAndLocationOnAfterAdd',
    function (\Bitrix\Main\Event $e) {
        $arParams = $e->getParameter('fields');
        $eventID = (int)$arParams['UF_EVENT_ID'];
        $result = (new EventLocationHandler($eventID))
            ->setProductExpDate();
    }
);