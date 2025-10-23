<?php
//ini_set('display_errors', '1');
//ini_set('display_startup_errors', '1');

/*if(strpos(__FILE__, "funstore.220.dev-server.pro") !== false)
    $_SERVER["DOCUMENT_ROOT"] = "/home/bitrix/ext_www/funstore.220.dev-server.pro"; // test
else
    $_SERVER["DOCUMENT_ROOT"] = "/home/bitrix/ext_www/store.dev.funtam.ru"; // prod*/

$_SERVER["DOCUMENT_ROOT"] = realpath(__DIR__ . '/../../');

$DOCUMENT_ROOT            = $_SERVER["DOCUMENT_ROOT"];

setlocale(LC_NUMERIC, 'C');

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_CRONTAB", true);
define('BX_WITH_ON_AFTER_EPILOG', true);
define('BX_NO_ACCELERATOR_RESET', true);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

@set_time_limit(0);
@ignore_user_abort(true);

use Bitrix\Main\Loader;
use Bitrix\Main\ORM;

Loader::includeModule('custom.core');

$query = new ORM\Query\Query('\Custom\Core\Users\CompaniesTable');
$resCompany   = $query
    ->setFilter([
        'CONTRACT.XML_ID' => 'signed',
    ])
    ->setSelect([
        'ID', 'ACT_ID' => 'ACT.ID'
    ])
    ->registerRuntimeField(
        'CONTRACT',
        array(
            'data_type' => '\Custom\Core\FieldEnumTable',
            'reference' => array('=this.UF_CONTRACT' => 'ref.ID'),
            'join_type' => 'LEFT'
        )
    )
    ->registerRuntimeField(
        '',
        new \Bitrix\Main\Entity\ReferenceField(
            'ACT',
            '\Custom\Core\Users\ActsTable',
            \Bitrix\Main\ORM\Query\Join::on('ref.UF_COMPANY', 'this.ID')
                ->where(
                    'ref.UF_DATE',
                    '>=',
                    new \Bitrix\Main\Type\DateTime(date('01.m.Y 00:00:00'))
                ),
        )
    )
    ->exec();

while($company = $resCompany->fetch()){
    if(!$company["ACT_ID"] && $company["ID"])
    {
        \Custom\Core\Act::makeFromB24($company["ID"]);
    }
}
