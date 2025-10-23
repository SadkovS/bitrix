<?php
namespace Custom\Core\Users;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Class BalanceHistoryTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_DATE datetime optional
 * <li> UF_BALANCE text optional
 * <li> UF_TYPE int optional
 * <li> UF_DESCRIPTION int optional
 * <li> UF_VALUE text optional
 * </ul>
 *
 * @package Bitrix\Hldb
 **/

class BalanceHistoryTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_balance_history';
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
                    'title' => Loc::getMessage('BALANCE_HISTORY_ENTITY_ID_FIELD'),
                ]
            ),
            new DatetimeField(
                'UF_DATE',
                [
                    'title' => Loc::getMessage('BALANCE_HISTORY_ENTITY_UF_DATE_FIELD'),
                ]
            ),
            new TextField(
                'UF_BALANCE',
                [
                    'title' => Loc::getMessage('BALANCE_HISTORY_ENTITY_UF_BALANCE_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_TYPE',
                [
                    'title' => Loc::getMessage('BALANCE_HISTORY_ENTITY_UF_TYPE_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_DESCRIPTION',
                [
                    'title' => Loc::getMessage('BALANCE_HISTORY_ENTITY_UF_DESCRIPTION_FIELD'),
                ]
            ),
            new TextField(
                'UF_VALUE',
                [
                    'title' => Loc::getMessage('BALANCE_HISTORY_ENTITY_UF_VALUE_FIELD'),
                ]
            ),
            new TextField(
                'UF_COMPANY_ID',
                [
                    'title' => Loc::getMessage('BALANCE_HISTORY_ENTITY_UF_COMPANY_ID_FIELD'),
                ]
            ),
        ];
    }
}