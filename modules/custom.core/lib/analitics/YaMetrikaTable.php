<?php
namespace Custom\Core\Analitics;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;

/**
 * Class ActionsTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UID string(50) mandatory
 * <li> EC_ACTION text mandatory
 * <li> SID string(50) mandatory
 * </ul>
 *
 * @package Bitrix\Metrika
 **/

class YaMetrikaTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'yandex_metrika_actions';
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
                    'title' => Loc::getMessage('ACTIONS_ENTITY_ID_FIELD'),
                ]
            ),
            new StringField(
                'UID',
                [
                    'required' => true,
                    'validation' => function()
                    {
                        return[
                            new LengthValidator(null, 50),
                        ];
                    },
                    'title' => Loc::getMessage('ACTIONS_ENTITY_UID_FIELD'),
                ]
            ),
            new TextField(
                'EC_ACTION',
                [
                    'required' => true,
                    'title' => Loc::getMessage('ACTIONS_ENTITY_EC_ACTION_FIELD'),
                ]
            ),
            new StringField(
                'SID',
                [
                    'required' => true,
                    'validation' => function()
                    {
                        return[
                            new LengthValidator(null, 50),
                        ];
                    },
                    'title' => Loc::getMessage('ACTIONS_ENTITY_SID_FIELD'),
                ]
            ),
            new StringField(
                'COUNTER_ID',
                [
                    'required' => true,
                    'validation' => function()
                    {
                        return[
                            new LengthValidator(null, 50),
                        ];
                    },
                    'title' => Loc::getMessage('ACTIONS_ENTITY_COUNTER_ID_FIELD'),
                ]
            ),
            new StringField(
                'MS_TOKEN',
                [
                    'required' => true,
                    'validation' => function()
                    {
                        return[
                            new LengthValidator(null, 50),
                        ];
                    },
                    'title' => Loc::getMessage('ACTIONS_ENTITY_MS_TOKEN_FIELD'),
                ]
            ),
        ];
    }
}