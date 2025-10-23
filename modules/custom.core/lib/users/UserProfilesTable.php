<?php
namespace Custom\Core\Users;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Class UserProfilesTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_UUID text optional
 * <li> UF_XML_ID text optional
 * <li> UF_USER_ID text optional
 * <li> UF_COMPANY int optional
 * </ul>
 *
 * @package Bitrix\Hldb
 **/

class UserProfilesTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_user_profiles';
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
                    'title' => Loc::getMessage('USER_PROFILES_ENTITY_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_UUID',
                [
                    'title' => Loc::getMessage('USER_PROFILES_ENTITY_UF_UUID_FIELD'),
                ]
            ),
            new TextField(
                'UF_XML_ID',
                [
                    'title' => Loc::getMessage('USER_PROFILES_ENTITY_UF_XML_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_USER_ID',
                [
                    'title' => Loc::getMessage('USER_PROFILES_ENTITY_UF_USER_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_COMPANY_ID',
                [
                    'title' => Loc::getMessage('USER_PROFILES_ENTITY_UF_COMPANY_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_IS_OWNER',
                [
                    'title' => Loc::getMessage('USER_PROFILES_ENTITY_UF_IS_OWNER_FIELD'),
                ]
            ),
        ];
    }
}