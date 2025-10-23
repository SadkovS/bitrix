<?php

namespace Custom\Core\Events;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;

class EventsDateAndLocationUfDateTimeTable extends DataManager {
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_events_date_and_location_uf_date_time';
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
                    'title' => Loc::getMessage('EVENTS_DATE_AND_LOCATION_UF_DATE_TIME_ENTITY_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'ID',
                [
                    'required' => true,
                    'title' => Loc::getMessage('EVENTS_DATE_AND_LOCATION_UF_DATE_TIME_ENTITY_ID_FIELD'),
                ]
            ),
            new DatetimeField(
                'VALUE',
                [
                    'title' => Loc::getMessage('EVENTS_DATE_AND_LOCATION_UF_DATE_TIME_ENTITY_VALUE_FIELD'),
                ]
            ),
            new ReferenceField(
                'DATE_TIME_REF',
                '\Custom\Core\Events\EventsDateAndLocationTable',
                ['this.ID' => 'ref.ID'],
                ['join_type' => 'LEFT']
            )
        ];
    }
}