<?php
namespace Custom\Core\Tickets;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Entity\Validator;
/**
 * Class WidgetsTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_XML_ID text optional
 * <li> UF_NAME text optional
 * <li> UF_EVENT_ID int optional
 * <li> UF_TYPE int optional
 * <li> UF_COMPANY_ID int optional
 * </ul>
 *
 * @package Bitrix\Hldb
 **/

class WidgetsTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_widgets';
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
                    'title' => Loc::getMessage('WIDGETS_ENTITY_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_XML_ID',
                [
                    'title' => Loc::getMessage('WIDGETS_ENTITY_UF_XML_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_NAME',
                [
                    'title' => Loc::getMessage('WIDGETS_ENTITY_UF_NAME_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_EVENT_ID',
                [
                    'title' => Loc::getMessage('WIDGETS_ENTITY_UF_EVENT_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_TYPE',
                [
                    'title' => Loc::getMessage('WIDGETS_ENTITY_UF_TYPE_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_COMPANY_ID',
                [
                    'title' => Loc::getMessage('WIDGETS_ENTITY_UF_COMPANY_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_VIEWS',
                [
                    'title' => Loc::getMessage('WIDGETS_ENTITY_UF_VIEWS_FIELD'),
                ]
            ),
            new TextField(
                'UF_UUID',
                [
                    'required' => true,
                    'title' => Loc::getMessage('WIDGETS_ENTITY_UF_UUID_FIELD'),
                    'unique' => true,
                    'validation' => function() {
                        return [
                            new Validator\RegExp('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/')
                        ];
                    }
                ]
            ),
            new TextField(
                'UF_BG_COLOR',
                [
                    'default_value' => '#E9EBF1',
                    'title' => Loc::getMessage('WIDGETS_ENTITY_UF_BG_COLOR_FIELD'),
                    'validation' => function() {
                        return [
                            new Validator\RegExp('/^#([0-9a-fA-F]{3}([0-9a-fA-F]{3})?)$/')
                        ];
                    }
                ]
            ),
            new TextField(
                'UF_CARDS_COLOR',
                [
                    'default_value' => '#FFF',
                    'title' => Loc::getMessage('WIDGETS_ENTITY_UF_CARDS_COLOR_FIELD'),
                    'validation' => function() {
                        return [
                            new Validator\RegExp('/^#([0-9a-fA-F]{3}([0-9a-fA-F]{3})?)$/')
                        ];
                    }
                ]
            ),
            new TextField(
                'UF_TEXT_COLOR',
                [
                    'default_value' => '#021231',
                    'title' => Loc::getMessage('WIDGETS_ENTITY_UF_TEXT_COLOR_FIELD'),
                    'validation' => function() {
                        return [
                            new Validator\RegExp('/^#([0-9a-fA-F]{3}([0-9a-fA-F]{3})?)$/')
                        ];
                    }
                ]
            ),
            new TextField(
                'UF_TEXT_BUTTON_COLOR',
                [
                    'default_value' => '#FFF',
                    'title' => Loc::getMessage('WIDGETS_ENTITY_UF_TEXT_BUTTON_COLOR_FIELD'),
                    'validation' => function() {
                        return [
                            new Validator\RegExp('/^#([0-9a-fA-F]{3}([0-9a-fA-F]{3})?)$/')
                        ];
                    }
                ]
            ),
            new TextField(
                'UF_ACCENT_COLOR',
                [
                    'default_value' => '#C92341',
                    'title' => Loc::getMessage('WIDGETS_ENTITY_UF_ACCENT_COLOR_FIELD'),
                    'validation' => function() {
                        return [
                            new Validator\RegExp('/^#([0-9a-fA-F]{3}([0-9a-fA-F]{3})?)$/')
                        ];
                    }
                ]
            ),
            new TextField(
                'UF_METRIC_COUNTER',
                [
                    'title' => Loc::getMessage('WIDGETS_ENTITY_UF_METRIC_COUNTER_FIELD'),
                ]
            ),
            new TextField(
                'UF_MS_TOKEN',
                [
                    'title' => Loc::getMessage('WIDGETS_ENTITY_UF_MS_TOKEN_FIELD'),
                ]
            ),
        ];
    }
}