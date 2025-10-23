<?php

require_once($_SERVER["DOCUMENT_ROOT"] . '/local/crest/crest.php');

use Bitrix\Main\ORM;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\UserTable;
use Bitrix\Main\UserGroupTable;
use Bitrix\Main\ORM\Query\Query;
use Custom\Core\Helper;

class CompanyHandler {
    private const COMPANY_UF_FIELD = 'UF_CRM_1733239527983';

    public static function onCompanyAdd(\Bitrix\Main\Event $e)
    {
        $id       = (int)$e->getParameter('id');
        $arParams = $e->getParameter('fields');

        $companyB24ID = self::createB24Company($id, $arParams);

        if ($companyB24ID > 0) {
            self::updateCompanyXmlId($id, $companyB24ID);
        }
		
		self::attachUsersCompanyAdministrationGroup($id);
    }

    private static function createB24Company($id, $arParams)
    {
        $res = CRest::call(
            'crm.company.add',
            ['FIELDS' => [
                'TITLE'                => $arParams['UF_NAME'],
                self::COMPANY_UF_FIELD => $id,
                //'UF_CRM_1733228023' => '', //руководитель
            ]]
        );

        return (int)$res['result'];
    }

    private static function updateCompanyXmlId($id, $companyB24ID)
    {
        $companyEntity = (new ORM\Query\Query('Custom\Core\Users\CompaniesTable'))->getEntity();
        $objCompany    = $companyEntity->wakeUpObject($id);
        $objCompany->set('UF_XML_ID', $companyB24ID);
        $objCompany->save();
    }
	
	private static function attachUsersCompanyAdministrationGroup($companyId): void {
		global $USER;
		
		try {
			$groupId = Helper::getGroupIdByCode(COMPANY_ADMINISTRATORS_GROUP_CODE);
			
			$query = new Query(UserTable::getEntity());
			$query->registerRuntimeField('', new \Bitrix\Main\ORM\Fields\Relations\Reference(
				'USER_GROUP',
				UserGroupTable::class,
				\Bitrix\Main\ORM\Query\Join::on('this.ID', 'ref.USER_ID')
			));
			$query->setFilter(['USER_GROUP.GROUP_ID' => $groupId, 'ACTIVE' => 'Y']);
			$query->setSelect(['ID']);
			
			$result = $query->exec();
			
			
			while ($user = $result->fetch()) {
				
				$userId = (int)$user['ID'];
				if ((int)$USER->GetID() === $userId) {
					continue;
				}
				
				Helper::createProfile($userId, $companyId);
			}
		} catch (\Exception $e) {
			Debug::writeToFile($e->getMessage(), '', $fileName = "_company_log.txt");
		}
	}
}