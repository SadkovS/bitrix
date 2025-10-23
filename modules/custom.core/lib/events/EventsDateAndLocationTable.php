<?php

namespace Custom\Core\Events;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
/**
 * Class EventsDateAndLocationTable
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_EVENT_ID int optional
 * <li> UF_DATE_TIME text optional
 * <li> UF_ADDRESS text optional
 * <li> UF_ROOM text optional
 * <li> UF_COORDINATES text optional
 * <li> UF_LINK text optional
 * </ul>
 *
 * @package Bitrix\Hldb
 **/
class EventsDateAndLocationTable extends DataManager {
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_events_date_and_location';
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
                    'primary'      => true,
                    'autocomplete' => true,
                    'title'        => Loc::getMessage('EVENTS_DATE_AND_LOCATION_ENTITY_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_EVENT_ID',
                [
                    'title' => Loc::getMessage('EVENTS_DATE_AND_LOCATION_ENTITY_UF_EVENT_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_DURATION',
                [
                    'title' => Loc::getMessage('EVENTS_DATE_AND_LOCATION_ENTITY_UF_DURATION_FIELD'),
                ]
            ),
            new TextField(
                'UF_DATE_TIME',
                [
                    'title' => Loc::getMessage('EVENTS_DATE_AND_LOCATION_ENTITY_UF_DATE_TIME_FIELD'),
                ]
            ),
            new TextField(
                'UF_ADDRESS',
                [
                    'title' => Loc::getMessage('EVENTS_DATE_AND_LOCATION_ENTITY_UF_ADDRESS_FIELD'),
                ]
            ),
            new TextField(
                'UF_ROOM',
                [
                    'title' => Loc::getMessage('EVENTS_DATE_AND_LOCATION_ENTITY_UF_ROOM_FIELD'),
                ]
            ),
            new TextField(
                'UF_COORDINATES',
                [
                    'title' => Loc::getMessage('EVENTS_DATE_AND_LOCATION_ENTITY_UF_COORDINATES_FIELD'),
                ]
            ),
            (new ArrayField(
                'UF_LINK',
                [
                    'title' => Loc::getMessage('EVENTS_DATE_AND_LOCATION_ENTITY_UF_LINK_FIELD'),
                ]
            ))->configureSerializationPhp(),
            (new ArrayField(
                'UF_DATE_TIME',
                [
                    'title' => Loc::getMessage('EVENTS_DATE_AND_LOCATION_ENTITY_UF_DATE_TIME'),
                ]
            ))->configureSerializationPhp(),
            (new OneToMany(
                'DATE_TIME',
                'Custom\Core\Events\EventsDateAndLocationUfDateTimeTable',
                'DATE_TIME_REF'
            ))->configureJoinType('LEFT'),
        ];
    }
}