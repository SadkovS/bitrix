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

$orderIds = [];

\Bitrix\Main\Loader::includeModule("sale");

$dbRes = \Bitrix\Sale\Order::getList([
    'select' => ['ID'],
    'filter' => [
        ">PRICE" => 0,
        "CANCELED" => "N",
        "PAYED" => "N",
        "=PROPERTY.CODE" => "RESERVE_TIME",
        "<PROPERTY.VALUE" => time(),
    ],
    'runtime'     => [
        new \Bitrix\Main\Entity\ReferenceField(
            'PROPERTY',
            'Bitrix\Sale\Internals\OrderPropsValueTable',
            ['=this.ID' => 'ref.ORDER_ID'],
            ['join_type' => 'inner']
        ),
    ],
    'order' => ['ID' => 'DESC']
]);
while ($order = $dbRes->fetch()){
    $orderIds[] = $order["ID"];
}

foreach ($orderIds as $orderId)
{
    $seat_ids = [];
    $data = [];

    $order = \Bitrix\Sale\Order::load($orderId);

    $order->setField('CANCELED', "Y");
    $order->setField("STATUS_ID", "CD");
    $result = $order->save();
}

$obBasket = \Bitrix\Sale\Basket::getList(
    [
        'select'  => [
            'FUSER_ID',
            'BARCODE_ID' => 'BASKET_PROPS_BARCODE_REF.VALUE'
        ],
        'filter' => [
            'ORDER_ID' => 'NULL',
            '<=DATE_INSERT' => date('d.m.Y H:i:s', time() - TICKET_RESERVE_MIN_DEF * 60),
            "BASKET_PROPS_BARCODE_REF.CODE" => "BARCODE",
        ],
        'runtime' => [
            new \Bitrix\Main\Entity\ReferenceField(
                'BASKET_PROPS_BARCODE_REF',
                '\Bitrix\Sale\Internals\BasketPropertyTable',
                ['=this.ID' => 'ref.BASKET_ID'],
                ['join_type' => 'left']
            ),
        ],
    ]
);

$baskets = [];
while($bItem = $obBasket->Fetch()){
    $baskets[$bItem["FUSER_ID"]][] = $bItem["BARCODE_ID"];
}

if($baskets)
{
    $enumIdNew = \Custom\Core\Contract::getHLfileldEnumId(
        "Barcodes",
        "UF_STATUS",
        "new"
    );

    foreach ($baskets as $fuserId => $barcodeIds)
    {
        if($barcodeIds)
        {
            foreach ($barcodeIds as $barcodeId)
            {
                \Custom\Core\Tickets\BarcodesTable::update(
                    $barcodeId,
                    [
                        "UF_STATUS" => $enumIdNew,
                        "UF_TICKET_NUM" => null,
                        "UF_SEATMAP_ID" => null,
                    ]
                );
            }
        }

        \CSaleBasket::DeleteAll($fuserId, false);
    }
}



