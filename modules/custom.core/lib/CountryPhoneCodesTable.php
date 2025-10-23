<?php

namespace Custom\Core;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

class CountryPhoneCodesTable extends DataManager {
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_country_phone_codes';
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
                    'title' => Loc::getMessage('COUNTRY_PHONE_CODES_ENTITY_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_NAME',
                [
                    'title' => Loc::getMessage('COUNTRY_PHONE_CODES_ENTITY_UF_NAME_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_CODE',
                [
                    'title' => Loc::getMessage('COUNTRY_PHONE_CODES_ENTITY_UF_CODE_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_SORT',
                [
                    'title' => Loc::getMessage('COUNTRY_PHONE_CODES_ENTITY_UF_SORT_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_ACTIVE',
                [
                    'title' => Loc::getMessage('COUNTRY_PHONE_CODES_ENTITY_UF_ACTIVE_FIELD'),
                ]
            ),
        ];
    }
}