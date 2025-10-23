<?php

namespace Custom\Core\Events;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
class PriceRulesUfEventIdTable extends DataManager {
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_price_rules_uf_event_id';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
	        new IntegerField( // добавлено в таблицу вручную для корректной работы
		        'GENERAL_ID',
		        [
			        'primary' => true,
			        'title' => Loc::getMessage('PRICE_RULES_GENERAL_ID_ENTITY_ID_FIELD'),
		        ]
	        ),
            new IntegerField(
                'ID',
                [
                    'required' => true,
                    'title' => Loc::getMessage('PRICE_RULES_UF_EVENT_ID_ENTITY_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'VALUE',
                [
                    'title' => Loc::getMessage('PRICE_RULES_UF_EVENT_ID_ENTITY_VALUE_FIELD'),
                    'required' => true,
                ]
            ),
        ];
    }
}