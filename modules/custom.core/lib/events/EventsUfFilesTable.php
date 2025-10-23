<?php

namespace Custom\Core\Events;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;

class EventsUfFilesTable extends DataManager {
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_events_uf_files';
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
                    'required' => true,
                    'title' => Loc::getMessage('EVENTS_UF_FILES_ENTITY_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'VALUE',
                [
                    'title' => Loc::getMessage('EVENTS_UF_FILES_ENTITY_VALUE_FIELD'),
                    'primary' => true,
                    'required' => true,
                ]
            ),
        ];
    }
}