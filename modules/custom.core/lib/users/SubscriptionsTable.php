<?php
namespace Custom\Core\Users;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Class Table
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_NAME text optional
 * <li> UF_EMAIL text optional
 * <li> UF_PHONE text optional
 * <li> UF_SUBSCRIBE int optional
 * </ul>
 *
 * @package Bitrix\
 **/

class SubscriptionsTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'subscriptions';
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
                    'title' => Loc::getMessage('_ENTITY_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_NAME',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_NAME_FIELD'),
                ]
            ),
            new TextField(
                'UF_EMAIL',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_EMAIL_FIELD'),
                ]
            ),
            new TextField(
                'UF_PHONE',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_PHONE_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_SUBSCRIBE',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_SUBSCRIBE_FIELD'),
                ]
            ),
        ];
    }
}