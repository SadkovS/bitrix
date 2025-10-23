<?php

namespace Custom\Core\Events;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Custom\Core\UUID;

Loc::loadMessages(__FILE__);

class PriceRulesTable extends DataManager{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_price_rules';
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
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => Loc::getMessage('PRICE_RULES_ENTITY_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_XML_ID',
                [
                    'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_XML_ID_FIELD'),
                    'required' => true,
                    'unique' => true,
                    'default_value' => UUID::uuid4()
                ]
            ),
            /*new IntegerField(
                'UF_TYPE_OF_APPLY',
                [
                    'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_TYPE_OF_APPLY_FIELD'),
                ]
            ),*/
	        new TextField(
		        'UF_NAME',
		        [
			        'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_NAME_FIELD'),
			        'required' => true,
		        ]
	        ),
            new IntegerField(
                'UF_DISCOUNT_TYPE',
                [
                    'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_DISCOUNT_TYPE_FIELD'),
                    'required' => true,
                ]
            ),
            new FloatField(
                'UF_DISCOUNT',
                [
                    'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_DISCOUNT_FIELD'),
                    'required' => true,
                ]
            ),
	        new DatetimeField(
		        'UF_DATE_START',
		        [
			        'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_DATE_START_FIELD'),
		        ]
	        ),
	        new DatetimeField(
		        'UF_DATE_END',
		        [
			        'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_DATE_END_FIELD'),
		        ]
	        ),
	        (new ArrayField(
		        'UF_EVENT_ID',
		        [
			        'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_EVENT_ID_FIELD'),
			        'required' => true,
			        'multiple' => 'Y',
		        ]
	        ))->configureSerializationPhp(),
            (new ArrayField(
                'UF_TICKETS_TYPE',
                [
                    'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_TICKETS_TYPE_FIELD'),
                ]
            ))->configureSerializationPhp(),
	        new IntegerField(
		        'UF_FOR_ALL_TYPES',
		        [
			        'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_FOR_ALL_TYPES_FIELD'),
			        'default_value' => 1
		        ]
	        ),
	        new IntegerField(
		        'UF_MIN_COUNT_TICKETS',
		        [
			        'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_MIN_COUNT_TICKETS_FIELD'),
			        'default_value' => 0
		        ]
	        ),
	        new IntegerField(
		        'UF_IS_SUM',
		        [
			        'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_IS_SUM_FIELD'),
			        'default_value' => 0
		        ]
	        ),
	        new IntegerField(
		        'UF_TYPE',
		        [
			        'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_TYPE_FIELD'),
		        ]
	        ),
	        new IntegerField(
				'UF_MAX_NUMBER_OF_USES',
				[
					'title' => Loc::getMessage('PROMO_CODES_ENTITY_UF_MAX_NUMBER_OF_USES_FIELD'),
					'default_value' => 0
				]
			),
	        new IntegerField(
				'UF_NUMBER_OF_USES',
				[
					'title' => Loc::getMessage('PROMO_CODES_ENTITY_UF_NUMBER_OF_USES_FIELD'),
					'default_value' => 0
				]
			),
	        new IntegerField(
		        'UF_IS_ACTIVITY',
		        [
			        'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_IS_ACTIVITY_FIELD'),
			        'default_value' => 1
		        ]
	        ),
	        
	        new IntegerField(
		        'UF_TYPE_APPLY_ALL_ORDER',
		        [
			        'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_TYPE_APPLY_ALL_ORDER_FIELD'),
		        ]
	        ),
	        new IntegerField(
		        'UF_TYPE_APPLY_MIN',
		        [
			        'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_TYPE_APPLY_MIN_FIELD'),
		        ]
	        ),
	        new IntegerField(
		        'UF_TYPE_APPLY_MAX',
		        [
			        'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_TYPE_APPLY_MAX_FIELD'),
		        ]
	        ),
            new IntegerField(
                'UF_FOR_EACH_TICKET',
                [
                    'title' => Loc::getMessage('PRICE_RULES_ENTITY_UF_FOR_EACH_TICKET_FIELD'),
                ]
            ),
	        new ReferenceField(
		        'REF_EVENTS_ID',
		        '\Custom\Core\Events\PriceRulesUfEventIdTable',
		        ['this.ID' => 'ref.ID'],
		        ['join_type' => 'LEFT']
	        ),
            new ReferenceField(
		        'REF_EVENTS',
		        '\Custom\Core\Events\EventsTable',
		        ['this.REF_EVENTS_ID.VALUE' => 'ref.ID'],
		        ['join_type' => 'LEFT']
	        ),
	        new ReferenceField(
		        'REF_PROMOCODE',
		        'Custom\Core\Events\PromoCodesTable',
		        ['this.ID' => 'ref.UF_RULE_ID'],
		        ['join_type' => 'LEFT']
	        ),
            new ReferenceField(
                'REF_TYPES_OF_TICKETS_ID',
                '\Custom\Core\Events\PriceRulesUfTicketsTypeTable',
                ['this.ID' => 'ref.ID'],
                ['join_type' => 'LEFT']
            ),
        ];
    }
}