<?php
namespace Custom\Core\Users;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

class CompaniesTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_company';
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
                'UF_XML_ID',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_XML_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_TYPE',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_TYPE_FIELD'),
                ]
            ),
            new TextField(
                'UF_DESCRIPTION',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_DESCRIPTION_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_LOGO',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_LOGO_FIELD'),
                ]
            ),
            new TextField(
                'UF_FIO',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_FIO_FIELD'),
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
            new TextField(
                'UF_INN',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_INN_FIELD'),
                ]
            ),
            new TextField(
                'UF_PASSPORT_SERIES',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_PASSPORT_SERIES_FIELD'),
                ]
            ),
            new TextField(
                'UF_PASSPORT_ISSUED',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_PASSPORT_ISSUED_FIELD'),
                ]
            ),
            new DateField(
                'UF_PASSPORT_DATE',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_PASSPORT_DATE_FIELD'),
                ]
            ),
            new TextField(
                'UF_REGISTRATION_ADDRESS',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_REGISTRATION_ADDRESS_FIELD'),
                ]
            ),
            new TextField(
                'UF_OGRNIP',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_OGRNIP_FIELD'),
                ]
            ),
            new TextField(
                'UF_KPP',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_KPP_FIELD'),
                ]
            ),
            new TextField(
                'UF_OGRN',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_OGRN_FIELD'),
                ]
            ),
            new TextField(
                'UF_SIGNATORE_FIO',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_SIGNATORE_FIO_FIELD'),
                ]
            ),
            new TextField(
                'UF_SIGNATORE_POSITION',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_SIGNATORE_POSITION_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_TAX_SYSTEM',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_TAX_SYSTEM_FIELD'),
                ]
            ),
            new TextField(
                'UF_BANK_NAME',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_BANK_NAME_FIELD'),
                ]
            ),
            new TextField(
                'UF_BANK_BIK',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_BANK_BIK_FIELD'),
                ]
            ),
            new TextField(
                'UF_BANK_KORR',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_BANK_KORR_FIELD'),
                ]
            ),
            new TextField(
                'UF_BANK_RS',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_BANK_RS_FIELD'),
                ]
            ),
            new TextField(
                'UF_BANK_COMMENT',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_BANK_COMMENT_FIELD'),
                ]
            ),
            new TextField(
                'UF_CONTRACT',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_CONTRACT_FIELD'),
                ]
            ),
            new TextField(
                'UF_CONTRACT_NUMBER',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_CONTRACT_NUMBER_FIELD'),
                ]
            ),
            new TextField(
                'UF_CONTRACT_DATE',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_CONTRACT_DATE_FIELD'),
                ]
            ),
            new TextField(
                'UF_CONTRACT_TYPE_CONFIRM',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_CONTRACT_TYPE_CONFIRM_FIELD'),
                ]
            ),
            new TextField(
                'UF_COOPERATION_PERCENT',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_COOPERATION_PERCENT_FIELD'),
                ]
            ),
            new TextField(
                'UF_CONTRACT_PAY_LINK',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_CONTRACT_PAY_LINK_FIELD'),
                ]
            ),
            new TextField(
                'UF_COOPERATION_SERVICE_FEE',
                [
                    'title' => Loc::getMessage('COMPANY_ENTITY_UF_COOPERATION_SERVICE_FEE_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_ACTIVE',
                [
                    'title' => Loc::getMessage('COMPANY_ENTITY_UF_ACTIVE_FIELD'),
                ]
            ),
            new TextField(
                'UF_CONTRACT_SCAN_LINK',
                [
                    'title' => Loc::getMessage('COMPANY_ENTITY_UF_CONTRACT_SCAN_LINK_FIELD'),
                ]
            ),
            new TextField(
                'UF_FILE_PASPORT_1',
                [
                    'title' => Loc::getMessage('COMPANY_ENTITY_UF_FILE_PASPORT_1_FIELD'),
                ]
            ),
            new TextField(
                'UF_FILE_PASPORT_2',
                [
                    'title' => Loc::getMessage('COMPANY_ENTITY_UF_FILE_PASPORT_2_FIELD'),
                ]
            ),
            new TextField(
                'UF_SNILS',
                [
                    'title' => Loc::getMessage('COMPANY_ENTITY_UF_SNILS_FIELD'),
                ]
            ),
            new TextField(
                'UF_FILE_SNILS',
                [
                    'title' => Loc::getMessage('COMPANY_ENTITY_UF_FILE_SNILS_FIELD'),
                ]
            ),
            new TextField(
                'UF_FILE_SELF_EMPLOYED',
                [
                    'title' => Loc::getMessage('COMPANY_ENTITY_UF_FILE_SELF_EMPLOYED_FIELD'),
                ]
            ),
            new TextField(
                'UF_STEP',
                [
                    'title' => Loc::getMessage('COMPANY_ENTITY_UF_STEP_FIELD'),
                ]
            ),
            new TextField(
                'UF_FULL_NAME',
                [
                    'title' => Loc::getMessage('COMPANY_ENTITY_UF_FULL_NAME_FIELD'),
                ]
            ),
            new TextField(
                'UF_B24_DEAL_ID',
                [
                    'title' => Loc::getMessage('COMPANY_ENTITY_UF_B24_DEAL_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_BALANCE',
                [
                    'title' => Loc::getMessage('COMPANY_ENTITY_UF_BALANCE_FIELD'),
                ]
            ),
            new TextField(
                'UF_CONTRACT_PAY_FILE',
                [
                    'title' => Loc::getMessage('COMPANY_ENTITY_UF_CONTRACT_PAY_FILE_FIELD'),
                ]
            ),
            new TextField(
                'UF_CONTACT_EMAIL',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_CONTACT_EMAIL_FIELD'),
                ]
            ),
            new TextField(
                'UF_CONTACT_PHONE',
                [
                    'title' => Loc::getMessage('_ENTITY_UF_CONTACT_PHONE_FIELD'),
                ]
            ),
        ];
    }
}