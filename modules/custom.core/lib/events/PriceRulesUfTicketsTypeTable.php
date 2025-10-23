<?php

namespace Custom\Core\Events;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;

class PriceRulesUfTicketsTypeTable extends DataManager {
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_price_rules_uf_tickets_type';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            new IntegerField(
                'GENERAL_ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => Loc::getMessage('PRICE_RULES_UF_TICKETS_TYPE_ENTITY_GENERAL_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'ID',
                [
                    'required' => true,
                    'title' => Loc::getMessage('PRICE_RULES_UF_TICKETS_TYPE_ENTITY_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'VALUE',
                [
                    'title' => Loc::getMessage('PRICE_RULES_UF_TICKETS_TYPE_ENTITY_VALUE_FIELD'),
                ]
            ),
        ];
    }
}