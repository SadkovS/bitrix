<?php
namespace Custom\Core\Users;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Class FinanceListTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_USER_ID int optional
 * <li> UF_DATE datetime optional
 * <li> UF_SUMM text optional
 * <li> UF_STATUS int optional
 * <li> UF_DATE_SUCCESS datetime optional
 * <li> UF_COMMENT text optional
 * <li> UF_XML_ID text optional
 * </ul>
 *
 * @package Bitrix\Hldb
 **/

class FinanceListTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_finance_list';
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
                    'title' => Loc::getMessage('FINANCE_LIST_ENTITY_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_USER_ID',
                [
                    'title' => Loc::getMessage('FINANCE_LIST_ENTITY_UF_USER_ID_FIELD'),
                ]
            ),
            new DatetimeField(
                'UF_DATE',
                [
                    'title' => Loc::getMessage('FINANCE_LIST_ENTITY_UF_DATE_FIELD'),
                ]
            ),
            new TextField(
                'UF_SUMM',
                [
                    'title' => Loc::getMessage('FINANCE_LIST_ENTITY_UF_SUMM_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_STATUS',
                [
                    'title' => Loc::getMessage('FINANCE_LIST_ENTITY_UF_STATUS_FIELD'),
                ]
            ),
            new DatetimeField(
                'UF_DATE_SUCCESS',
                [
                    'title' => Loc::getMessage('FINANCE_LIST_ENTITY_UF_DATE_SUCCESS_FIELD'),
                ]
            ),
            new TextField(
                'UF_COMMENT',
                [
                    'title' => Loc::getMessage('FINANCE_LIST_ENTITY_UF_COMMENT_FIELD'),
                ]
            ),
            new TextField(
                'UF_XML_ID',
                [
                    'title' => Loc::getMessage('FINANCE_LIST_ENTITY_UF_XML_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_COMPANY_ID',
                [
                    'title' => Loc::getMessage('FINANCE_LIST_ENTITY_UF_COMPANY_ID_FIELD'),
                ]
            ),
        ];
    }
}