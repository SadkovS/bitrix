<?php
use \Bitrix\Main\ORM\Data\DataManager;
use \Custom\Core\Contract as ContractCore;
use \Bitrix\Main\ORM;
use \Custom\Core\Helper as Helper;

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'ActsOnAfterUpdate',
    function (\Bitrix\Main\Event $e) {
        \Bitrix\Main\Loader::includeModule("sale");

        $fields = $e->getParameter('fields');

        $enumId = ContractCore::getHLfileldEnumId(
            Custom\Core\Act::HL_NAME,
            Custom\Core\Act::ACT_STATUS_XMLID,
            "cancel"
        );

        if($fields["UF_STATUS"] == $enumId)
        {
            \Custom\Core\Act::makeFromB24($fields["UF_COMPANY"]);
        }
    }
);

Bitrix\Main\ORM\EventManager::getInstance()->addEventHandler(
    'Custom\Core\Users\ActsTable',
    DataManager::EVENT_ON_BEFORE_UPDATE,
    function (\Bitrix\Main\Event $e) {
        \Bitrix\Main\Loader::includeModule("sale");

        $id = $e->getParameter('id')["ID"];
        $fields = $e->getParameter('fields');

        $enumId = ContractCore::getHLfileldEnumId(
            Custom\Core\Act::HL_NAME,
            Custom\Core\Act::ACT_STATUS_XMLID,
            "verif"
        );

        if($fields["UF_STATUS"] == $enumId)
        {
            $actEntity = new ORM\Query\Query('\Custom\Core\Users\ActsTable');

            $actQuery = $actEntity
                ->setSelect([
                    "ID",
                    "UF_COMPANY",
                    "COMPANY_EMAIL" => "COMPANY.UF_EMAIL",
                    "COMPANY_FIO" => "COMPANY.UF_FIO",
                    'UF_STATUS'
                ])
                ->setFilter([
                    'ID' => $id,
                    '!UF_STATUS' => $enumId,
                ])
                ->registerRuntimeField(
                    "COMPANY",
                    [
                        'data_type' => '\Custom\Core\Users\CompaniesTable',
                        'reference' => array('=this.UF_COMPANY' => 'ref.ID'),
                        'join_type' => 'INNER'
                    ]
                )
                ->exec();
            if($act = $actQuery->fetch())
            {
                \CEvent::Send('ACT_ADD', "s1", [
                    'EMAIL' => $act["COMPANY_EMAIL"],
                    'FULL_NAME' => $act["COMPANY_FIO"],
                    'LK_LINK' => Helper::getSiteUrl()."/admin_panel/documents/acts/",
                    'SERVER_NAME' => Helper::getSiteUrl(),
                ]);
            }
        }
    }
);

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'ActsOnAfterUpdate',
    function (\Bitrix\Main\Event $e) {
        $fields = $e->getParameter('fields');

        $enumId = ContractCore::getHLfileldEnumId(
            Custom\Core\Act::HL_NAME,
            Custom\Core\Act::ACT_STATUS_XMLID,
            "created"
        );

        if($fields["UF_STATUS"] == $enumId) {

            \Bitrix\Main\Loader::includeModule("sale");

            $id = $e->getParameter('id')["ID"];
            $company = $e->getParameter('fields')["UF_COMPANY"];
            $date = $e->getParameter('fields')['UF_DATE']->toString();

            \Custom\Core\Act::make($company, $id, $date);
            \Custom\Core\Act::send($id);
        }
    }
);

