<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/crest/crest.php');
AddEventHandler("main", "OnBeforeUserAdd", ["UserEvents", "OnBeforeUserHandler"]);
AddEventHandler("main", "OnBeforeUserUpdate", ["UserEvents", "OnBeforeUserHandler"]);
AddEventHandler("main", "OnBeforeUserUpdate", ["UserEvents", "onBeforeUserUpdateHandler"]);
AddEventHandler("main", "OnAfterUserUpdate", ["UserEvents", "OnAfterUserUpdateHandler"]);
AddEventHandler("main", "OnAfterUserAdd", ["UserEvents", "OnAfterUserAddHandler"]);

use Bitrix\Main\Loader;
use Bitrix\Main\ORM;
use Bitrix\Main\UserTable as UTable;
use Bitrix\Main\Diag\Debug;

class UserEvents {
    public static function OnBeforeUserHandler(&$arFields)
    {
        global $USER;
        Loader::includeModule('custom.core');
        if (!empty($arFields['EMAIL']) && !$USER->IsAdmin()) {
            $arFields['LOGIN'] = $arFields['EMAIL'];
        }
        //todo временно код страны Россия по умолчанию
        if ((int)$arFields['UF_COUNTRY_CODE'] < 1) $arFields['UF_COUNTRY_CODE'] = 1;
        //        if ((int)$arFields['UF_COUNTRY_CODE'] > 0 && !empty($arFields['PERSONAL_PHONE'])) {
        //            $codeEntity = new ORM\Query\Query('Custom\Core\CountryPhoneCodesTable');
        //            $query      = $codeEntity
        //                ->setSelect(['UF_CODE'])
        //                ->setFilter(['ID' => $arFields['UF_COUNTRY_CODE']])
        //                ->exec();
        //            if ($resCode = $query->fetch()) {
        //                $arFields['PERSONAL_PHONE'] = preg_replace('~\D+~', '', trim(($resCode['UF_CODE'] . $arFields['PERSONAL_PHONE'])));
        //            }
        //        }

        if (key_exists('organizer', $arFields)) {
            $arFields['GROUP_ID'][] = ORGANIZER_AND_STAFF_GROUP_ID;
            unset($arFields['organizer']);
        }
    }

    public static function onBeforeUserUpdateHandler($arFields)
    {
        global $USER;

        if (!empty($arFields['PASSWORD']) && !empty($arFields['CONFIRM_PASSWORD'])) {

            $securityPolicy  = \CUser::GetGroupPolicy([1]);
            $errorsCheckPass = $USER->CheckPasswordAgainstPolicy($arFields['PASSWORD'], $securityPolicy);

            if (count($errorsCheckPass) > 0) {
                global $APPLICATION;
                $APPLICATION->throwException(implode(' ', $errorsCheckPass));
                return false;
            }
        }

        $resUser  = UTable::getList(
            [
                "select" => [
                    'NAME',
                    'LAST_NAME',
                    'SECOND_NAME',
                    'LAST_LOGIN',
                    'ACTIVE',
                    'EMAIL',
                    'ID',
                ],
                'filter' => ['ID' => $arFields['ID']],
                'limit'  => 1
            ]
        );
        $arUser   = $resUser->fetch();
        $arGroups = CUser::GetUserGroup($arFields['ID']);
        if (
            $arUser["ACTIVE"] == 'N' &&
            $arFields["ACTIVE"] == 'Y' &&
            empty($arUser["LAST_LOGIN"]) &&
            in_array(9, $arGroups)
        )
            $_SESSION["SEND_WELCOME_MESSAGE"] = true;

    }

    public static function OnAfterUserUpdateHandler($arFields)
    {
        $resUser = UTable::getList(
            [
                "select"  => [
                    'NAME',
                    'LAST_NAME',
                    'SECOND_NAME',
                    'PHONE_NUMBER' => 'PHONE.PHONE_NUMBER',
                    'ACTIVE',
                    'EMAIL',
                    'ID',
                    'UF_B24_CONTACT_ID'
                ],
                'filter'  => ['ID' => $arFields['ID']],
                'runtime' =>
                    [
                        new \Bitrix\Main\Entity\ReferenceField(
                            'PHONE',
                            '\Bitrix\Main\UserPhoneAuthTable',
                            ['this.ID' => 'ref.USER_ID'],
                            ['join_type' => 'LEFT'],
                        ),
                    ],
                'limit'   => 1
            ]
        );
        $user    = $resUser->fetch();
        if ($user['ACTIVE'] == 'Y' && empty($user['UF_B24_CONTACT_ID'])) {
            //Создание контакта Б24
            $res = CRest::call(
                'crm.contact.add',
                ['fields' =>
                     [
                         'UF_CRM_1716289598715' => $arFields['ID'], //USER_ID
                         'NAME'                 => $user['NAME'],
                         'LAST_NAME'            => $user['LAST_NAME'],
                         'SECOND_NAME'          => $user['SECOND_NAME'],
                         'EMAIL'                => [["VALUE" => $user['EMAIL']]],
                         'PHONE'                => [['VALUE' => $user['PHONE_NUMBER'], 'VALUE_TYPE' => 'WORK']],
                         'ASSIGNED_BY_ID'       => 44,
                         'TYPE_ID'              => 'UC_E9WSDO',
                         'SOURCE_ID'            => 'UC_JDVHA5',
                     ]
                ]
            );
            if ((int)$res['result'] > 0) {
                $obUser = new \CUser;
                $obUser->Update($arFields['ID'], ['UF_B24_CONTACT_ID' => $res['result']]);
            } else {
                Debug::writeToFile($res, 'crm.contact.add', $fileName = "_b24_event.txt");
            }
        }

        if ($_SESSION['SEND_WELCOME_MESSAGE']) {
            $serverName     = 'https://' . \COption::GetOptionString('main', 'server_name', '');
            $adminPanelLink = $serverName . '/admin_panel/';


            \CEvent::Send(
                'NEW_ORGANIZER_REGISTRATION', 's1',
                [
                    'FULL_NAME'        => trim($user['NAME'].' '.$user['LAST_NAME']),
                    'ADMIN_PANEL_LINK' => $adminPanelLink,
                    'EMAIL'            => $user['EMAIL'],
                    'SERVER_NAME'      => $serverName
                ]
            );
            unset($_SESSION['SEND_WELCOME_MESSAGE']);
        }
    }

    public static function OnAfterUserAddHandler($arFields)
    {
        Loader::includeModule('custom.core');

        if ($arFields["ID"] > 0 && in_array(ORGANIZER_AND_STAFF_GROUP_ID, $arFields['GROUP_ID']) && (!isset($arFields['_NO_CREATE_COMPANY']))) {
            $userID = $arFields["ID"];

            $companyName = trim($arFields['LAST_NAME'] . ' ' . $arFields['NAME'] . ' ' . $arFields['SECOND_NAME']);

            $companyEntity = (new ORM\Query\Query('Custom\Core\Users\CompaniesTable'))->getEntity();
            $objCompany    = $companyEntity->createObject();
            $objCompany->set('UF_NAME', $companyName);
            $objCompany->set('UF_FIO', $companyName);
            $objCompany->set('UF_TYPE', COMPANY_TYPE_SE); // Самозанятый
            $resCompany = $objCompany->save();
            $companyID  = $resCompany->getId();

            if ($companyID < 1) return false;

            $uuid          = \Custom\Core\UUID::uuid3(\Custom\Core\UUID::NAMESPACE_X500, $userID . microtime());
            $query         = new ORM\Query\Query('Custom\Core\Users\UserProfilesTable');
            $profileEntity = $query->getEntity();

            $query = $query
                ->setSelect(['ID'])
                ->setFilter(['UF_COMPANY_ID' => $companyID,'!UF_IS_OWNER' => false])
                ->countTotal(true)
                ->exec();


            $objProfile = $profileEntity->createObject();
            $objProfile->set('UF_COMPANY_ID', $companyID);
            $objProfile->set('UF_USER_ID', $userID);
            $objProfile->set('UF_UUID', $uuid);
            if ($query->getCount() == 0) $objProfile->set('UF_IS_OWNER', 1);
            $resProfile = $objProfile->save();

            if ($resProfile->getId() < 1) return false;

            if (!empty($arFields['CONFIRM_CODE']) && $arFields['ACTIVE'] == 'N') {
                $arFields['USER_ID'] = $arFields['ID'];
                $event               = new \CEvent;
                $event->SendImmediate("NEW_USER_CONFIRM", SITE_ID, $arFields, 'Y', '', [], LANGUAGE_ID);
            }
        }
    }
}
