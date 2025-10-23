<?php
namespace Custom\Core;

require_once($_SERVER["DOCUMENT_ROOT"] . '/local/crest/crest.php');

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\Validator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Custom\Core\ExportExcel;
use \Bitrix\Sale\Internals\OrderTable;
use \Custom\Core\Contract as ContractCore;
use \Custom\Core\Helper as Helper;

Loc::loadMessages(__FILE__);

class FinanceList
{
    const HL_NAME = "FinanceList";
    const FL_STATUS_XMLID = "UF_STATUS";

    const FL_ENTITY_TIPE_ID = 1040;

    public static function updateFL($flId, $fields)
    {
        $successEnumId = ContractCore::getHLfileldEnumId(
            self::HL_NAME,
            self::FL_STATUS_XMLID,
            "success"
        );

        $cancelEnumId = ContractCore::getHLfileldEnumId(
            self::HL_NAME,
            self::FL_STATUS_XMLID,
            "cancel"
        );

        $flEntity = new ORM\Query\Query('\Custom\Core\Users\FinanceListTable');

        $flQuery = $flEntity
            ->setSelect([
                "ID", "UF_STATUS"
            ])
            ->setFilter([
                'UF_XML_ID' => $flId,
                '!UF_STATUS' => $successEnumId
            ])
            ->exec();

        if($fl = $flQuery->fetch())
        {
            if($fl["UF_STATUS"] == $successEnumId)
            {
                throw new \Exception('Заявка уже в статусе завершена');
            }

            if($fl["UF_STATUS"] == $cancelEnumId)
            {
                throw new \Exception('Заявка ранее отклонена');
            }

            if($fields["UF_STATUS"])
            {
                $fields["UF_STATUS"] = ContractCore::getHLfileldEnumId(
                    self::HL_NAME,
                    self::FL_STATUS_XMLID,
                    $fields["UF_STATUS"]
                );
            }

            if($fields["UF_DATE_SUCCESS"])
                $fields["UF_DATE_SUCCESS"] = new \Bitrix\Main\Type\DateTime($fields["UF_DATE_SUCCESS"]);

            $res = \Custom\Core\Users\FinanceListTable::update(
                $fl["ID"],
                $fields
            );
        }
        else
        {
            throw new \Exception('Запись не найдена');
        }
    }

    public static function addFL($id, $data)
    {
        $result = \CRest::call(
            'crm.item.add',
            [
                'entityTypeId' => self::FL_ENTITY_TIPE_ID,
                'fields' => $data
            ]
        );

        if($result["result"] && $result["result"]["item"]["id"])
        {
            \Custom\Core\Users\FinanceListTable::update(
                $id,
                [
                    "UF_XML_ID" => $result["result"]["item"]["id"],
                ]
            );
        }
    }

    public static function onUpdate(\Bitrix\Main\Event $e)
    {
        $id       = $e->getParameter('id')["ID"];
        $fields = $e->getParameter('fields');

        $verifEnumId = ContractCore::getHLfileldEnumId(
            self::HL_NAME,
            self::FL_STATUS_XMLID,
            "verif"
        );

        $successEnumId = ContractCore::getHLfileldEnumId(
            self::HL_NAME,
            self::FL_STATUS_XMLID,
            "success"
        );

        $cancelEnumId = ContractCore::getHLfileldEnumId(
            self::HL_NAME,
            self::FL_STATUS_XMLID,
            "cancel"
        );

        if($fields["UF_STATUS"])
        {
            $flEntity = new ORM\Query\Query('\Custom\Core\Users\FinanceListTable');

            $flQuery = $flEntity
                ->setSelect([
                    "*",
                    'USER_LAST_NAME' => 'USER.LAST_NAME',
                    'USER_NAME' => 'USER.NAME',
                    'USER_SECOND_NAME' => 'USER.SECOND_NAME',
                    'USER_EMAIL' => 'USER.EMAIL',
                ])
                ->setFilter([
                    'ID' => $id,
                ])
                ->registerRuntimeField(
                    'USER',
                    array(
                        'data_type' => '\Bitrix\Main\UserTable',
                        'reference' => array('=this.UF_USER_ID' => 'ref.ID'),
                        'join_type' => 'LEFT'
                    )
                )
                ->exec();

            if($fl = $flQuery->fetch())
            {
                if($fl["UF_STATUS"] == $successEnumId)
                {
                    throw new \Exception('Заявка уже в статусе завершена');
                }

                if($fl["UF_STATUS"] == $cancelEnumId)
                {
                    throw new \Exception('Заявка ранее отклонена');
                }

                $fl["FULL_NAME"] = trim(implode(" ", [$fl["USER_LAST_NAME"], $fl["USER_NAME"], $fl["USER_SECOND_NAME"]]));

                if($cancelEnumId == $fields["UF_STATUS"])
                {
                    $balance = \Custom\Core\BalanceHistory::getBalanceForCompany($fl["UF_COMPANY_ID"]);

                    $newBalance = $balance + $fl["UF_SUMM"];

                    $enumType = ContractCore::getHLfileldEnumId(
                        \Custom\Core\BalanceHistory::HL_NAME,
                        \Custom\Core\BalanceHistory::BH_TYPE,
                        "up"
                    );

                    $enumDescription = ContractCore::getHLfileldEnumId(
                        \Custom\Core\BalanceHistory::HL_NAME,
                        \Custom\Core\BalanceHistory::BH_DESCRIPTION,
                        "refund"
                    );

                    \Custom\Core\Users\BalanceHistoryTable::add(
                        [
                            "UF_COMPANY_ID" => $fl["UF_COMPANY_ID"],
                            "UF_VALUE" => $fl["UF_SUMM"],
                            "UF_BALANCE" => $newBalance,
                            "UF_DATE" => new \Bitrix\Main\Type\DateTime(),
                            "UF_TYPE" => $enumType,
                            "UF_DESCRIPTION" => $enumDescription,
                        ]
                    );

                    \CEvent::Send('FINANCE_CANCEL', "s1", [
                        'EMAIL' => $fl['USER_EMAIL'],
                        'FULL_NAME' => $fl['FULL_NAME'],
                        'DATE' => $fl['UF_DATE'],
                        'SUM' => $fl['UF_SUMM'],
                        'COMMENT' => $fields['UF_COMMENT'],
                        'SERVER_NAME' => Helper::getSiteUrl(),
                    ]);
                }
                elseif ($verifEnumId == $fields["UF_STATUS"] && $fl["UF_STATUS"] != $verifEnumId)
                {
                    \CEvent::Send('FINANCE_VERIF', "s1", [
                        'EMAIL' => $fl['USER_EMAIL'],
                        'FULL_NAME' => $fl['FULL_NAME'],
                        'DATE' => $fl['UF_DATE'],
                        'SUM' => $fl['UF_SUMM'],
                        'SERVER_NAME' => Helper::getSiteUrl(),
                    ]);
                }

            }
            else
            {
                throw new \Exception('Запись не найдена');
            }


        }



    }

}
