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

\Bitrix\Main\Loader::includeModule('custom.core');

(new \Custom\Core\YaMetrika())->sendActionsToMetrika();