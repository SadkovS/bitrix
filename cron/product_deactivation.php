<?php
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
Loader::includeModule('highloadblock');
Loader::includeModule('iblock');

$productEntity = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
$productClass = $productEntity->getDataClass();
$query    = new ORM\Query\Query($productEntity);
$subFilter = [
    'LOGIC' => 'OR',
    ['<ACTIVE_TO' => date('d.m.Y H:i:s')],
    ['=ACTIVE_TO' => date('d.m.Y H:i:s')]
];
$resProduct = $query
    ->setSelect(['ID','ACTIVE_TO','EVENT_ID'])
    ->setFilter(['ACTIVE' => 'Y',$subFilter])
    ->exec();
while ($product = $resProduct->fetchObject()) {
    $product->set('ACTIVE', 'N');
    $product->save();
    \Custom\Core\Products::addToIndex($product->getId());
    $eventID = (int)$product->get('EVENT_ID')->getValue();
    if($eventID < 1) continue;

    $eventEntity = new ORM\Query\Query('Custom\Core\Events\EventsTable');
    $query = $eventEntity
        ->setSelect(['*'])
        ->setFilter(['ID' => $eventID])
        ->countTotal(true)
        ->exec();
    if($query->getCount() < 1) continue;
    $event = $query->fetchObject();
    $event->set('UF_STATUS', 6);
    $event->save();
}
