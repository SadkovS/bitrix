<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/include/handlers/orderHandler.php');

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleOrderSaved',
    function (\Bitrix\Main\Event $e) {
        $objOrder = new \Local\PhpInterface\Handlers\OrderHandler();
        $objOrder->OnSaleOrderSaved($e);
    }
);