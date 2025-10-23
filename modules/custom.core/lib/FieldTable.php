<?php

namespace Custom\Core;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;



class FieldTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_user_field';
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
                    'title' => Loc::getMessage('FIELD_ENTITY_ID_FIELD'),
                ]
            ),
            new StringField(
                'ENTITY_ID',
                [
                    'validation' => function()
                    {
                        return[
                            new LengthValidator(null, 50),
                        ];
                    },
                    'title' => Loc::getMessage('FIELD_ENTITY_ENTITY_ID_FIELD'),
                ]
            ),
            new StringField(
                'FIELD_NAME',
                [
                    'validation' => function()
                    {
                        return[
                            new LengthValidator(null, 50),
                        ];
                    },
                    'title' => Loc::getMessage('FIELD_ENTITY_FIELD_NAME_FIELD'),
                ]
            ),
            new StringField(
                'USER_TYPE_ID',
                [
                    'validation' => function()
                    {
                        return[
                            new LengthValidator(null, 50),
                        ];
                    },
                    'title' => Loc::getMessage('FIELD_ENTITY_USER_TYPE_ID_FIELD'),
                ]
            ),
            new StringField(
                'XML_ID',
                [
                    'validation' => function()
                    {
                        return[
                            new LengthValidator(null, 255),
                        ];
                    },
                    'title' => Loc::getMessage('FIELD_ENTITY_XML_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'SORT',
                [
                    'title' => Loc::getMessage('FIELD_ENTITY_SORT_FIELD'),
                ]
            ),
            new BooleanField(
                'MULTIPLE',
                [
                    'values' => ['N', 'Y'],
                    'default' => 'N',
                    'title' => Loc::getMessage('FIELD_ENTITY_MULTIPLE_FIELD'),
                ]
            ),
            new BooleanField(
                'MANDATORY',
                [
                    'values' => ['N', 'Y'],
                    'default' => 'N',
                    'title' => Loc::getMessage('FIELD_ENTITY_MANDATORY_FIELD'),
                ]
            ),
            new BooleanField(
                'SHOW_FILTER',
                [
                    'values' => ['N', 'Y'],
                    'default' => 'N',
                    'title' => Loc::getMessage('FIELD_ENTITY_SHOW_FILTER_FIELD'),
                ]
            ),
            new BooleanField(
                'SHOW_IN_LIST',
                [
                    'values' => ['N', 'Y'],
                    'default' => 'Y',
                    'title' => Loc::getMessage('FIELD_ENTITY_SHOW_IN_LIST_FIELD'),
                ]
            ),
            new BooleanField(
                'EDIT_IN_LIST',
                [
                    'values' => ['N', 'Y'],
                    'default' => 'Y',
                    'title' => Loc::getMessage('FIELD_ENTITY_EDIT_IN_LIST_FIELD'),
                ]
            ),
            new BooleanField(
                'IS_SEARCHABLE',
                [
                    'values' => ['N', 'Y'],
                    'default' => 'N',
                    'title' => Loc::getMessage('FIELD_ENTITY_IS_SEARCHABLE_FIELD'),
                ]
            ),
            new TextField(
                'SETTINGS',
                [
                    'title' => Loc::getMessage('FIELD_ENTITY_SETTINGS_FIELD'),
                ]
            ),
        ];
    }
}