<?php
use Bitrix\Main\ORM\Data\DataManager;
use \Custom\Core\Contract as ContractCore;

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'FinanceListOnBeforeUpdate',
    ['Custom\Core\FinanceList', 'onUpdate']
);

Bitrix\Main\ORM\EventManager::getInstance()->addEventHandler(
    'Custom\Core\Users\FinanceListTable',
    DataManager::EVENT_ON_BEFORE_UPDATE,
    ['Custom\Core\FinanceList', 'onUpdate']
);


