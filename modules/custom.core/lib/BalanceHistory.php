<?php
namespace Custom\Core;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\Validator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Custom\Core\ExportExcel;
use \Bitrix\Sale\Internals\OrderTable;
use \Custom\Core\Contract as ContractCore;

Loc::loadMessages(__FILE__);

class BalanceHistory
{
    const HL_NAME = "BalanceHistory";
    const BH_TYPE = "UF_TYPE";
    const BH_DESCRIPTION = "UF_DESCRIPTION";


    public static function getBalanceForCompany($companyId = null)
    {
        if($companyId)
        {
            $balanceEntity   = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity(self::HL_NAME);
            $hlbClassBalance = $balanceEntity->getDataClass();

            $balance = $hlbClassBalance::getList([
                'filter' => [
                    'UF_COMPANY_ID' => $companyId,
                ],
                'limit' => 1,
                'order' => ['ID' => 'DESC'],
            ])->fetch();

            if($balance['ID'] < 1)
            {
                return 0;
            }
            else
            {
                return $balance['UF_BALANCE'];
            }
        }
    }

}
