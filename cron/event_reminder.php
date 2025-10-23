<?php
$_SERVER["DOCUMENT_ROOT"] = realpath(__DIR__ . '/../../');
$DOCUMENT_ROOT            = $_SERVER["DOCUMENT_ROOT"];

setlocale(LC_NUMERIC, 'C');
if (!$_SERVER["DOCUMENT_ROOT"]) $_SERVER["DOCUMENT_ROOT"] = realpath(__DIR__ . '/../../');
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
use Bitrix\Highloadblock as HL;

Loader::includeModule('sale');

$objDate    = new DateTime();
$date_start = $objDate->modify('+24 hour')->format('Y-m-d H:i') . ':00';

$dbRes = \Bitrix\Sale\Order::getList(
    [
        'select'  => [
            'EVENT_ID'   => 'PROPERTY_EVENT_ID.VALUE',
            'FULL_NAME'  => 'PROPERTY_BUYER.VALUE',
            'EMAIL'      => 'PROPERTY_BUYER_EMAIL.VALUE',
            'ADDRESS'    => 'EVENT.UF_LOCATION_REF.UF_ADDRESS',
            "EVENT_NAME" => "EVENT.UF_NAME",
            'EVENT_DATE',
        ],
        'filter'  => [
            "PAYED"                     => "Y",
            'STATUS_ID'                 => ['P', 'F'],
            "PROPERTY_EVENT_ID.CODE"    => "EVENT_ID",
            "PROPERTY_BUYER_EMAIL.CODE" => "EMAIL",
            "PROPERTY_BUYER.CODE"       => 'FIO',
            "EVENT_DATE"                => $date_start,
        ],
        'runtime' => [
            new \Bitrix\Main\Entity\ReferenceField(
                'PROPERTY_BUYER',
                'Bitrix\Sale\Internals\OrderPropsValueTable',
                ['=this.ID' => 'ref.ORDER_ID'],
                ['join_type' => 'inner']
            ),
            new \Bitrix\Main\Entity\ReferenceField(
                'PROPERTY_BUYER_EMAIL',
                'Bitrix\Sale\Internals\OrderPropsValueTable',
                ['=this.ID' => 'ref.ORDER_ID'],
                ['join_type' => 'inner']
            ),
            new \Bitrix\Main\Entity\ReferenceField(
                'PROPERTY_EVENT_ID',
                'Bitrix\Sale\Internals\OrderPropsValueTable',
                ['=this.ID' => 'ref.ORDER_ID'],
                ['join_type' => 'inner']
            ),
            new \Bitrix\Main\Entity\ReferenceField(
                'EVENT',
                'Custom\Core\Events\EventsTable',
                ['=this.EVENT_ID' => 'ref.ID'],
                ['join_type' => 'inner']
            ),
            new \Bitrix\Main\Entity\ExpressionField(
                'EVENT_DATE', 'MIN(%s)', ['EVENT.UF_LOCATION_REF.DATE_TIME.VALUE']
            ),
        ],
        'group'   => ['EVENT_ID', 'EMAIL']
    ]
);
while ($order = $dbRes->fetch()) {
    $order['SERVER_NAME'] = 'https://' . Bitrix\Main\Config\Option::get('main', 'server_name', '');
    $order['EVENT_DATE'] = new DateTime($order['EVENT_DATE']);
    $order['EVENT_DATE'] = FormatDate("d F Y", $order['EVENT_DATE']->getTimestamp()) . ' в ' . FormatDate("H:i", $order['EVENT_DATE']->getTimestamp());

    CEvent::Send('EVENT_REMINDER', SITE_ID, $order);
}