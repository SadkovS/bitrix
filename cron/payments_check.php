<?php

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_CRONTAB', true);
define('SITE_ID', 's1');

$_SERVER["DOCUMENT_ROOT"] = realpath(__DIR__ . '/../../');

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Custom\Core\Services\OrdersPaymentCheckService;
use Custom\Core\SimpleLogger;

try {
    if (!Loader::includeModule('custom.core')) {
        throw new LoaderException('Модуль custom.core не найден');
    }

    (new OrdersPaymentCheckService())->run();
} catch (Throwable $e) {
    SimpleLogger::log(
        'Ошибка при выполнении payments_check: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString(),
        'E',
        'payments_check',
        'error'
    );
    echo "Ошибка: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
