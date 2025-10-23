<?php
//События после авторизации
AddEventHandler("main", "OnAfterUserAuthorize", ["PrepareUserParams", "OnAfterUserLoginHandler"]);
AddEventHandler("main", "OnAfterUserLogin", ["PrepareUserParams", "OnAfterUserLoginHandler"]);
AddEventHandler("main", "OnAfterUserLoginByHash", ["PrepareUserParams", "OnAfterUserLoginHandler"]);
//События до авторизации
AddEventHandler("main", "OnBeforeUserAuthorize", ["PrepareUserParams", "OnBeforeUserLoginHandler"]);
AddEventHandler("main", "OnBeforeUserLogin", ["PrepareUserParams", "OnBeforeUserLoginHandler"]);
AddEventHandler("main", "OnBeforeUserLoginByHash", ["PrepareUserParams", "OnBeforeUserLoginHandler"]);

use Wejet\Core\UserProfile;
use \Bitrix\Main\Loader;
use Local\Api\Controllers\Core\Profiles;
use Bitrix\Main\ORM;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Cookie;

class PrepareUserParams {
    static function OnBeforeUserLoginHandler($fields)
    {
        global $APPLICATION;
        Loader::IncludeModule("wejet.core");
        //$objProfile   = new Profiles;
        $rsUser       = CUser::GetByLogin($fields['LOGIN']);
        $arUser       = $rsUser->Fetch();
        $arGroups     = CUser::GetUserGroup($arUser['ID']);
        $userProfiles = [];
        $query = new ORM\Query\Query('\Custom\Core\Users\UserProfilesTable');
        $resProfiles   = $query
            ->setFilter(['UF_USER_ID' => $arUser['ID']])
            ->setSelect(['*'])
            ->exec();
        while($profile = $resProfiles->fetch()){
            $userProfiles[$profile['UF_COMPANY_ID']] = $profile;
        }
        $userOb       = new CUser;

        if ($arUser) {

            $lastLoginCompany = (int)$arUser['UF_LAST_AUTH_COMPANY'];

            if (count($userProfiles) < 1 && !in_array(1, $arGroups)) {
                $APPLICATION->throwException("Ваша учетная запись настроена не корректно! Обратитесь к администратору вашей компании.");
                return false;
            }

            if ($lastLoginCompany < 1 || !key_exists($lastLoginCompany, $userProfiles))
                $curProfile = array_shift($userProfiles);
            else
                $curProfile = $userProfiles[$lastLoginCompany];
                //TODO Добавить проверку прав доступа

//            if ((int)$curProfile['UF_USER_RIGHTS'] < 1 && !in_array(1, $arGroups)) {
//                $APPLICATION->throwException("Для вашей учетной записи не указан шаблон доступных услуг! Обратитесь к администратору вашей компании.");
//                return false;
//            }

        }

    }

    static function OnAfterUserLoginHandler(&$fields)
    {
        $userId = isset($fields['USER_ID']) && $fields['USER_ID'] > 0 ? $fields['USER_ID'] : (isset($fields['user_fields']['ID']) && $fields['user_fields']['ID'] > 0 ? $fields['user_fields']['ID'] : null);

        if ($userId) {
            self::getUFUserParams($userId);
        }
    }

    static function getUFUserParams($UserID)
    {
        $userOb = new CUser;
        Loader::IncludeModule("iblock");
        Loader::IncludeModule("wejet.core");

        $rsUser           = CUser::GetByID($UserID);
        $arUser           = $rsUser->Fetch();
        $lastLoginCompany = (int)$arUser['UF_LAST_AUTH_COMPANY'];

        $userProfiles = [];
        $query = new ORM\Query\Query('\Custom\Core\Users\UserProfilesTable');
        $resProfiles   = $query
            ->setFilter(['UF_USER_ID' => $UserID])
            ->registerRuntimeField(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'UF_COMPANY_REF',
                        '\Custom\Core\Users\CompaniesTable',
                        ['this.UF_COMPANY_ID' => 'ref.ID'],
                        ['join_type' => 'LEFT']
                    )
            )
            ->setSelect([
                '*',
                'UF_COMPANY_NAME' => 'UF_COMPANY_REF.UF_NAME',
                'UF_COMPANY_B24_ID' => 'UF_COMPANY_REF.UF_XML_ID',
            ])
            ->exec();
        while($profile = $resProfiles->fetch()){
            $userProfiles[$profile['UF_COMPANY_ID']] = $profile;
        }

        if (count($userProfiles) > 0) {
            $value = $userProfiles;

            $_SESSION['SESS_AUTH']['UF_USER_PROFILES'] = $value;

            if ($lastLoginCompany < 1)
                $curProfile = $_SESSION['CURRENT_USER_PROFILE'] = array_shift($value);
            else
                $curProfile = $_SESSION['CURRENT_USER_PROFILE'] = $value[$lastLoginCompany];

            if ((int)$arUser['UF_LAST_AUTH_COMPANY'] != (int)$curProfile['UF_COMPANY_ID']) {
                $userOb->Update($UserID, ['UF_LAST_AUTH_COMPANY' => $curProfile['UF_COMPANY_ID']]);
                $_SESSION['SESS_AUTH']['UF_LAST_AUTH_COMPANY'] = $curProfile['UF_COMPANY_ID'];
            }

            $_SESSION['SESS_AUTH']['UF_USER_PROFILES'][$curProfile['UF_COMPANY_ID']]['CHECKED'] = true;

        }
    }
}