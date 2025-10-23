<?php

namespace Custom\Core\Events;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

class EventsStatusHistoryTable extends DataManager {
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_events_status_history';
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
                    'title' => Loc::getMessage('EVENTS_STATUS_HISTORY_ENTITY_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_EVENT_ID',
                [
                    'title' => Loc::getMessage('EVENTS_STATUS_HISTORY_ENTITY_UF_EVENT_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_STATUS_ID',
                [
                    'title' => Loc::getMessage('EVENTS_STATUS_HISTORY_ENTITY_UF_STATUS_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_MODIFIED_BY',
                [
                    'title' => Loc::getMessage('EVENTS_STATUS_HISTORY_ENTITY_UF_MODIFIED_BY_FIELD'),
                ]
            ),
            new DatetimeField(
                'UF_DATE_UPDATE',
                [
                    'title' => Loc::getMessage('EVENTS_STATUS_HISTORY_ENTITY_UF_DATE_UPDATE_FIELD'),
                ]
            ),
            new TextField(
                'UF_COMMENT',
                [
                    'title' => Loc::getMessage('EVENTS_STATUS_HISTORY_ENTITY_UF_COMMENT_FIELD'),
                ]
            ),
            new \Bitrix\Main\Entity\ReferenceField(
                'STATUS',
                '\Custom\Core\Events\EventsStatusTable',
                ['this.UF_STATUS_ID' => 'ref.ID'],
                ['join_type' => 'LEFT'],
            ),
        ];
    }

}