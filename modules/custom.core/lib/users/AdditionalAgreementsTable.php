<?php
namespace Custom\Core\Users;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Class AdditionalAgreementsTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_NAME text optional
 * <li> UF_LINK text optional
 * <li> UF_DATE_SIGNED datetime optional
 * <li> UF_DATE_LOAD datetime optional
 * <li> UF_COMPANY_ID int optional
 * </ul>
 *
 * @package Bitrix\Hldb
 **/

class AdditionalAgreementsTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_additional_agreements';
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
                    'title' => Loc::getMessage('ADDITIONAL_AGREEMENTS_ENTITY_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_NAME',
                [
                    'title' => Loc::getMessage('ADDITIONAL_AGREEMENTS_ENTITY_UF_NAME_FIELD'),
                ]
            ),
            new TextField(
                'UF_LINK',
                [
                    'title' => Loc::getMessage('ADDITIONAL_AGREEMENTS_ENTITY_UF_LINK_FIELD'),
                ]
            ),
            new DatetimeField(
                'UF_DATE_SIGNED',
                [
                    'title' => Loc::getMessage('ADDITIONAL_AGREEMENTS_ENTITY_UF_DATE_SIGNED_FIELD'),
                ]
            ),
            new DatetimeField(
                'UF_DATE_LOAD',
                [
                    'title' => Loc::getMessage('ADDITIONAL_AGREEMENTS_ENTITY_UF_DATE_LOAD_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_COMPANY_ID',
                [
                    'title' => Loc::getMessage('ADDITIONAL_AGREEMENTS_ENTITY_UF_COMPANY_ID_FIELD'),
                ]
            ),
        ];
    }
}