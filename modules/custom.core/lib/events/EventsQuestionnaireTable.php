<?php

namespace Custom\Core\Events;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

class EventsQuestionnaireTable extends DataManager {
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_events_questionnaire';
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
                    'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_ENTITY_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_EVENT_ID',
                [
                    'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_ENTITY_UF_EVENT_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_USER_ID',
                [
                    'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_ENTITY_UF_USER_ID_FIELD'),
                ]
            ),
            new DatetimeField(
                'UF_DATE',
                [
                    'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_ENTITY_UF_DATE_FIELD'),
                ]
            ),
            (new ArrayField(
                'UF_USER_DATA',
                [
                    'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_ENTITY_UF_USER_DATA_FIELD'),
                ]
            ))->configureSerializationJson(),
            new IntegerField(
                'UF_ORDER_ID',
                [
                    'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_ENTITY_UF_ORDER_ID_FIELD'),
                ]
            ),
           /* new IntegerField(
                'UF_TICKET',
                [
                    'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_ENTITY_UF_TICKET_FIELD'),
                ]
            ),*/
	        (new ArrayField(
		        'UF_TICKET',
		        [
			        'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_ENTITY_UF_TICKET_FIELD'),
		        ]
	        ))->configureSerializationPhp(),
            new TextField(
                'UF_FULL_NAME',
                [
                    'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_ENTITY_UF_FULL_NAME_FIELD'),
                ]
            ),
            new TextField(
                'UF_PHONE',
                [
                    'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_ENTITY_UF_PHONE_FIELD'),
                ]
            ),
            new TextField(
                'UF_EMAIL',
                [
                    'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_ENTITY_UF_EMAIL_FIELD'),
                ]
            ),
            new TextField(
                'UF_BUYER',
                [
                    'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_ENTITY_UF_BUYER_FIELD'),
                ]
            ),
        ];
    }
}