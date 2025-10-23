<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/include/handlers/ProfileHandler.php');


use Bitrix\Main\ORM\Data\DataManager;

\Bitrix\Main\ORM\EventManager::getInstance()->addEventHandler(
    'Custom\Core\Users\UserProfilesTable',
    DataManager::EVENT_ON_BEFORE_ADD,
    [ProfileHandler::class, 'onBeforeAdd']
);

\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'UserProfilesOnBeforeAdd',
    [ProfileHandler::class, 'onBeforeAdd']
);

\Bitrix\Main\ORM\EventManager::getInstance()->addEventHandler(
    'Custom\Core\Users\UserProfilesTable',
    DataManager::EVENT_ON_AFTER_ADD,
    [ProfileHandler::class, 'onAfterAdd']
);

\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'UserProfilesOnAfterAdd',
    [ProfileHandler::class, 'onAfterAdd']
);