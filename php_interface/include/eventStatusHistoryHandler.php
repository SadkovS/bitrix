<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/include/handlers/EventStatusHistoryHandler.php');
use Bitrix\Main\Loader;
use Bitrix\Main\ORM;
use Bitrix\Main\Diag\Debug;
use \Local\PhpInterface\Handlers\EventLocationHandler;
use Bitrix\Main\ORM\Data\DataManager;
use Local\PhpInterface\Handlers\EventStatusHistoryHandler;

Loader::includeModule('custom.core');
Loader::includeModule('highloadblock');
Loader::includeModule('iblock');

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'EventsStatusHistoryOnAfterAdd',
    function (\Bitrix\Main\Event $e) {
        $objHistory = new EventStatusHistoryHandler($e);
        $objHistory->process();
    }
);

\Bitrix\Main\ORM\EventManager::getInstance()->addEventHandler(
    'Custom\Core\Events\EventsStatusHistoryTable',
    'OnAfterAdd',
    function (\Bitrix\Main\Event $e) {
        $objHistory = new EventStatusHistoryHandler($e);
        $objHistory->process();
    }
);