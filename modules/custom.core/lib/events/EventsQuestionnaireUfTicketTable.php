<?php

namespace Custom\Core\Events;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class EventsQuestionnaireUfTicketTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> VALUE string(255) optional
 * </ul>
 *
 * @package Bitrix\Hldb
 **/

class EventsQuestionnaireUfTicketTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_events_questionnaire_uf_ticket';
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
                    'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_UF_TICKET_ENTITY_GENERAL_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'ID',
                [
                    'required' => true,
                    'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_UF_TICKET_ENTITY_ID_FIELD'),
                ]
            ),
            new StringField(
                'VALUE',
                [
                    'validation' => function()
                    {
                        return[
                            new LengthValidator(null, 255),
                        ];
                    },
                    'title' => Loc::getMessage('EVENTS_QUESTIONNAIRE_UF_TICKET_ENTITY_VALUE_FIELD'),
                ]
            ),
        ];
    }
}