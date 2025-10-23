<?php
namespace Custom\Core\Tickets;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\DatetimeField;

/**
 * Class GroupTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_TITLE text optional
 * <li> UF_ID text optional
 * <li> UF_TYPE text optional
 * <li> UF_ADDED_AT datetime optional
 * <li> UF_ADDED_BY text optional
 * <li> UF_USER_TG_ID text optional
 * </ul>
 *
 * @package Bitrix\Group
 **/

class TgGroupTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'tg_group';
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
                    'title' => Loc::getMessage('GROUP_ENTITY_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_TITLE',
                [
                    'title' => Loc::getMessage('GROUP_ENTITY_UF_TITLE_FIELD'),
                ]
            ),
            new TextField(
                'UF_ID',
                [
                    'title' => Loc::getMessage('GROUP_ENTITY_UF_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_TYPE',
                [
                    'title' => Loc::getMessage('GROUP_ENTITY_UF_TYPE_FIELD'),
                ]
            ),
            new DatetimeField(
                'UF_ADDED_AT',
                [
                    'title' => Loc::getMessage('GROUP_ENTITY_UF_ADDED_AT_FIELD'),
                ]
            ),
            new TextField(
                'UF_ADDED_BY',
                [
                    'title' => Loc::getMessage('GROUP_ENTITY_UF_ADDED_BY_FIELD'),
                ]
            ),
            new TextField(
                'UF_USER_TG_ID',
                [
                    'title' => Loc::getMessage('GROUP_ENTITY_UF_USER_TG_ID_FIELD'),
                ]
            ),
        ];
    }
}