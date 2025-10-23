<?php

namespace Custom\Core\Events;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\DatetimeField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\Validator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity\TextField;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use \Bitrix\Main\FileTable;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

Loc::loadMessages(__FILE__);

class EventsTable extends DataManager {

    public static function getTableName()
    {
        return 'b_hldb_events';
    }

    public static function getMap()
    {
        return [
            new IntegerField(
                'ID',
                [
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => Loc::getMessage('EVENTS_ENTITY_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_XML_ID',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_XML_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_STATUS',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_STATUS_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_IMG',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_IMG_FIELD'),
                ]
            ),
            new TextField(
                'UF_NAME',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_NAME_FIELD'),
                ]
            ),
            new TextField(
                'UF_CODE',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_CODE_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_CATEGORY',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_CATEGORY_FIELD'),
                ]
            ),
            new DatetimeField(
                'UF_DATE_CREATE',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_DATE_CREATE_FIELD'),
                ]
            ),
            new DatetimeField(
                'UF_DATE_UPDATE',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_DATE_UPDATE_FIELD'),
                ]
            ),
            new TextField(
                'UF_CREATED_BY',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_CREATED_BY_FIELD'),
                ]
            ),
            new TextField(
                'UF_COMPANY_ID',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_COMPANY_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_MODIFIED_BY',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_MODIFIED_BY_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_AGE_LIMIT',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_AGE_LIMIT_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_TYPE',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_TYPE_FIELD'),
                ]
            ),
            new TextField(
                'UF_DESCRIPTION',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_DESCRIPTION_FIELD'),
                ]
            ),
            new TextField(
                'UF_UUID',
                [
                    'required' => true,
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_UUID_FIELD'),
                    'unique' => true,
                    'validation' => function() {
                        return [
                            new Validator\RegExp('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/')
                        ];
                    }
                ]
            ),
            new IntegerField(
                'UF_SIT_MAP',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_SIT_MAP_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_STEP',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_STEP_FIELD'),
                ]
            ),
            (new ArrayField(
                'UF_QUESTIONNAIRE_FIELDS',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_QUESTIONNAIRE_FIELDS_FIELD'),
                ]
            ))->configureSerializationJson(),
            new \Bitrix\Main\Entity\ReferenceField(
                'PICTURE',
                '\Bitrix\Main\FileTable',
                ['this.UF_IMG' => 'ref.ID'],
                ['join_type' => 'LEFT'],
            ),
            new \Bitrix\Main\Entity\ExpressionField(
                'IMG_SRC', 'CONCAT("/upload/",%s, "/", %s)', ['PICTURE.SUBDIR', 'PICTURE.FILE_NAME']
            ),
            (new ArrayField(
                'UF_PAY_SYSTEM',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_PAY_SYSTEM_FIELD'),
                    'type' => 'integer',
                    'multiple' => 'Y',
                ]
            ))->configureSerializationPhp(),
            new ReferenceField(
                'UF_LOCATION_REF',
                '\Custom\Core\Events\EventsDateAndLocationTable',
                ['this.ID' => 'ref.UF_EVENT_ID'],
                ['join_type' => 'LEFT']
            ),
            (new ArrayField(
                'UF_FILES',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_FILES_FIELD'),
                    'multiple' => 'Y',
                ]
            ))->configureSerializationPhp(),
            new TextField(
                'UF_QUESTIONNAIRE_DESCRIPTION',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_QUESTIONNAIRE_DESCRIPTION_FIELD'),
                    'validation' => function()
                    {
                        return[
                            new LengthValidator(null, 2000),
                        ];
                    },
                ]
            ),
            new IntegerField(
                'UF_QUESTIONNAIRE_ACTIVE',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_QUESTIONNAIRE_ACTIVE_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_QUESTIONNAIRE_FOREACH_TICKETS',
                [
                    'title' => Loc::getMessage('EVENTS_UF_QUESTIONNAIRE_FOREACH_TICKETS_FIELD'),
                ]
            ),
            new TextField(
                'UF_SERIES',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_SERIES_FIELD'),
                    'validation' => function()
                    {
                        return[
                            new Validator\RegExp('/[A-Z]{3}/')
                        ];
                    },
                ],

            ),
            new DatetimeField(
                'UF_DATE_OF_SIGNIFICANT_CHANGES',
                [
                    'title' => Loc::getMessage('EVENTS_ENTITY_UF_DATE_OF_SIGNIFICANT_CHANGES_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_RESERVATION_VALIDITY_PERIOD',
                [
                    'title'         => Loc::getMessage('EVENTS_ENTITY_UF_RESERVATION_VALIDITY_PERIOD_FIELD'),
                    'default_value' => 5,  // значение по умолчанию
                    'validation'    => function () {
                        return [
                            new Validator\Range(1, 21),
                        ];
                    },
                ]
            ),
	        new IntegerField(
		        'UF_TICKET_IMG',
		        [
			        'title' => Loc::getMessage('EVENTS_ENTITY_UF_TICKET_IMG_FIELD'),
		        ]
	        ),
	        new \Bitrix\Main\Entity\ReferenceField(
		        'TICKET_IMG',
		        '\Bitrix\Main\FileTable',
		        ['this.UF_TICKET_IMG' => 'ref.ID'],
		        ['join_type' => 'LEFT'],
	        ),
        ];
    }
}