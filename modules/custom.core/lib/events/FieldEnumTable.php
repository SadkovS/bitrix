<?php

namespace Custom\Core\Events;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class FieldEnumTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> USER_FIELD_ID int optional
 * <li> VALUE string(255) mandatory
 * <li> DEF bool ('N', 'Y') optional default 'N'
 * <li> SORT int optional default 500
 * <li> XML_ID string(255) mandatory
 * </ul>
 *
 * @package Bitrix\User
 **/
class FieldEnumTable extends DataManager {
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_user_field_enum';
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
                    'title' => Loc::getMessage('FIELD_ENUM_ENTITY_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'USER_FIELD_ID',
                [
                    'title' => Loc::getMessage('FIELD_ENUM_ENTITY_USER_FIELD_ID_FIELD'),
                ]
            ),
            new StringField(
                'VALUE',
                [
                    'required' => true,
                    'validation' => function()
                    {
                        return[
                            new LengthValidator(null, 255),
                        ];
                    },
                    'title' => Loc::getMessage('FIELD_ENUM_ENTITY_VALUE_FIELD'),
                ]
            ),
            new BooleanField(
                'DEF',
                [
                    'values' => array('N', 'Y'),
                    'default' => 'N',
                    'title' => Loc::getMessage('FIELD_ENUM_ENTITY_DEF_FIELD'),
                ]
            ),
            new IntegerField(
                'SORT',
                [
                    'default' => 500,
                    'title' => Loc::getMessage('FIELD_ENUM_ENTITY_SORT_FIELD'),
                ]
            ),
            new StringField(
                'XML_ID',
                [
                    'required' => true,
                    'validation' => function()
                    {
                        return[
                            new LengthValidator(null, 255),
                        ];
                    },
                    'title' => Loc::getMessage('FIELD_ENUM_ENTITY_XML_ID_FIELD'),
                ]
            ),
        ];
    }
}