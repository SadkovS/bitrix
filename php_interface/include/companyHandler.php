<?php
//require_once($_SERVER["DOCUMENT_ROOT"] . '/local/crest/crest.php');
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/include/handlers/CompanyHandler.php');

use Bitrix\Main\ORM\Data\DataManager;


// Register event handlers
Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'CompanyOnAfterAdd',
    [CompanyHandler::class, 'onCompanyAdd']
);

\Bitrix\Main\ORM\EventManager::getInstance()->addEventHandler(
    'Custom\Core\Users\CompaniesTable',
    DataManager::EVENT_ON_AFTER_ADD,
    [CompanyHandler::class, 'onCompanyAdd']
);

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'CompanyOnBeforeUpdate',
    ['Custom\Core\Contract', 'onUpdate']
);

Bitrix\Main\ORM\EventManager::getInstance()->addEventHandler(
    'Custom\Core\Users\CompaniesTable',
    DataManager::EVENT_ON_BEFORE_UPDATE,
    ['Custom\Core\Contract', 'onUpdate']
);