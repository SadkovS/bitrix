<?php
namespace Custom\Core;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\Validator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use \Custom\Core\Contract as ContractCore;


Loc::loadMessages(__FILE__);

class TgGroup
{
    const HL_NAME = "TgGroup";

    public static function prepareRequest($fields, $revert = false): array
    {
        $tableFields = [
            "title" => "UF_TITLE",
            "id" => "UF_ID",
            "type" => "UF_TYPE",
            "addedAt" => "UF_ADDED_AT",
            "addedBy" => "UF_ADDED_BY",
            "userTgId" => "UF_USER_TG_ID",
        ];

        if($revert)
            $tableFields = array_flip($tableFields);

        $result = [];

        foreach ($fields as $key => $field)
        {
            if($tableFields[$key])
                $result[$tableFields[$key]] = $field;
        }

        return $result;
    }

    public static function getList($filter, $select = ["*"])
    {
        return \Custom\Core\Tickets\TgGroupTable::getList(
            [
                "filter" => $filter,
                "select" => $select,
            ]
        )->fetchAll();
    }

    public static function get($fields)
    {
        $data = self::prepareRequest($fields);
        $items = self::getList($data);

        foreach ($items as &$item)
        {
            $item = self::prepareRequest($item, true);
        }

        return $items;
    }

    public static function add($fields)
    {
        $data = self::prepareRequest($fields);
        $result = \Custom\Core\Tickets\TgGroupTable::add($data);

        if(!$result->isSuccess())
        {
            throw new \Exception(implode(', ', $result->getErrors()));
        }
    }

    public static function del($fields)
    {
        $data = self::prepareRequest($fields);
        $items = self::getList($data, ["ID"]);

        foreach ($items as $item)
        {
            $result = \Custom\Core\Tickets\TgGroupTable::delete($item["ID"]);

            if(!$result->isSuccess())
            {
                throw new \Exception(implode(', ', $result->getErrors()));
            }
        }
    }

    public static function addWidget($fields)
    {
        $productEntity     = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
        $propFieldEventID  = $productEntity->getField('EVENT_ID');
        $propEventIDEntity = $propFieldEventID->getRefEntity();

        $eventEntity = new ORM\Query\Query('\Bitrix\Iblock\ElementTable');
        $query       = $eventEntity
            ->setSelect(
                [
                    'EVENT_ID'   => 'EVENT_PROP.VALUE',
                    'COMPANY_ID' => 'EVENT.UF_COMPANY_ID',
                ]
            )
            ->setFilter([
                'ID' => $fields["ticket_id"]
            ])
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'EVENT_PROP',
                    $propEventIDEntity,
                    ['this.ID' => 'ref.IBLOCK_ELEMENT_ID'],
                    ['join_type' => 'LEFT'],
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'EVENT',
                    'Custom\Core\Events\EventsTable',
                    ['this.EVENT_ID' => 'ref.ID'],
                    ['join_type' => 'LEFT'],
                )
            )
            ->exec();

        if ($event = $query->fetch()) {
            $uuid = \Custom\Core\UUID::uuid8();

            $typeTgId = ContractCore::getHLfileldEnumId(
                "Widgets",
                "UF_TYPE",
                "telegram"
            );

            $arFields = [
                'UF_NAME'              => $fields["name"] ?? "Виджет",
                'UF_EVENT_ID'          => $event["EVENT_ID"],
                'UF_TYPE'              => $typeTgId,
                'UF_COMPANY_ID'        => $event["COMPANY_ID"],
                'UF_UUID'              => $uuid,
                'UF_BG_COLOR'          => '#E9EBF1',
                'UF_CARDS_COLOR'       => '#FFF',
                'UF_TEXT_COLOR'        => '#021231',
                'UF_TEXT_BUTTON_COLOR' => '#FFF',
                'UF_ACCENT_COLOR'      => '#C92341',
            ];

            $widgetEntity    = new ORM\Query\Query('Custom\Core\Tickets\WidgetsTable');
            $widgetDataClass = $widgetEntity->getEntity()->getDataClass();
            $result          = $widgetDataClass::add($arFields);
            if (!$result->isSuccess()) throw new Exception(str_replace("<br>", "\n", implode(', ', $result->getErrors())));
            else{
                return $uuid;
            }
        }

    }

}
