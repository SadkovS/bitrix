<?php

namespace Custom\Core\Events;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Class EventCategoryTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_XML_ID text optional
 * <li> UF_SORT int optional
 * <li> UF_NAME text optional
 * </ul>
 *
 * @package Custom\Core\Events
 **/
class EventsCategoryTable extends DataManager {
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_event_category';
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
                    'title' => Loc::getMessage('EVENT_CATEGORY_ENTITY_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_XML_ID',
                [
                    'title' => Loc::getMessage('EVENT_CATEGORY_ENTITY_UF_XML_ID_FIELD'),
                    'required' => true,
                    'unique' => true,
                ]
            ),
            new IntegerField(
                'UF_SORT',
                [
                    'title' => Loc::getMessage('EVENT_CATEGORY_ENTITY_UF_SORT_FIELD'),
                    'default_value' => 500
                ]
            ),
            new TextField(
                'UF_NAME',
                [
                    'title' => Loc::getMessage('EVENT_CATEGORY_ENTITY_UF_NAME_FIELD'),
                    'required' => true,
                ]
            ),
            new TextField(
                'UF_IMG',
                [
                    'title' => Loc::getMessage('EVENT_CATEGORY_ENTITY_UF_IMG_FIELD'),
                    'required' => true,
                ]
            ),
            new TextField(
                'UF_ICON',
                [
                    'title' => Loc::getMessage('EVENT_CATEGORY_ENTITY_UF_ICON_FIELD'),
                    'required' => true,
                ]
            ),
            new TextField(
                'UF_CODE',
                [
                    'title' => Loc::getMessage('EVENT_CATEGORY_ENTITY_UF_CODE_FIELD'),
                    'required' => true,
                ]
            ),
        ];
    }
}