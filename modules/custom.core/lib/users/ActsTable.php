<?php
namespace Custom\Core\Users;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Class ActsTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_LINK text optional
 * <li> UF_DATE datetime optional
 * <li> UF_STATUS int optional
 * <li> UF_COMPANY int optional
 * <li> UF_BALANCE text optional
 * </ul>
 *
 * @package Bitrix\Hldb
 **/

class ActsTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_acts';
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
                    'title' => Loc::getMessage('ACTS_ENTITY_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_LINK',
                [
                    'title' => Loc::getMessage('ACTS_ENTITY_UF_LINK_FIELD'),
                ]
            ),
            new DatetimeField(
                'UF_DATE',
                [
                    'title' => Loc::getMessage('ACTS_ENTITY_UF_DATE_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_STATUS',
                [
                    'title' => Loc::getMessage('ACTS_ENTITY_UF_STATUS_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_COMPANY',
                [
                    'title' => Loc::getMessage('ACTS_ENTITY_UF_COMPANY_FIELD'),
                ]
            ),
            new TextField(
                'UF_XML_ID',
                [
                    'title' => Loc::getMessage('ACTS_ENTITY_UF_XML_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_FILE_UPD',
                [
                    'title' => Loc::getMessage('ACTS_ENTITY_UF_FILE_UPD_FIELD'),
                ]
            ),
        ];
    }
}