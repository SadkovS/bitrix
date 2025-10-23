<?php

\Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    'Sale\Handlers\PaySystem\p10102022_p10102022paycode2022Handler' => '/local/php_interface/include/sale_payment/p10102022_p10102022paycode2022/handler.php',
]);

/**
 * REST API
 *
 * @install  13.05.2024 18:04:34
 * @package  artamonov.rest
 * @website  https://marketplace.1c-bitrix.ru/solutions/artamonov.rest
 */
if (Bitrix\Main\Loader::includeModule('artamonov.rest')) \Artamonov\Rest\Foundation\Core::getInstance()->run();

//Константы
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/const.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/const.php');

//События изменения статуса мероприятия
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/eventHandler.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/eventHandler.php');

//События пользователя
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/usersHandler.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/usersHandler.php');

//События добавления/отправки писем
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/eventSendHandler.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/eventSendHandler.php');

//События добавления/измененения локации события
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/eventLocationHandler.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/eventLocationHandler.php');

//События заказа
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/orderHandler.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/orderHandler.php');

//События SKU
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/offersHandler.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/offersHandler.php');
//События DISCOUNT
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/discountsHandler.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/discountsHandler.php');
//События Profile
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/profileHandler.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/profileHandler.php');
//События Авторизации
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/loginHandler.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/loginHandler.php');
//События добавления/измененения компаний
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/companyHandler.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/companyHandler.php');

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/actHandler.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/actHandler.php');

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/flHandler.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/flHandler.php');
//События возвратов билетов
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/refundHandler.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/refundHandler.php');
//События истории статусов
if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/eventStatusHistoryHandler.php'))
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/include/eventStatusHistoryHandler.php');

CModule::AddAutoloadClasses(
    null,
    [
        'Local\\Api\\Controllers\\V1\\Enum\\SkdSearchBy'          => '/local/api/controllers/v1/Enum/SkdSearchBy.php',
        'Local\\Api\\Controllers\\V1\\Enum\\SkdCheckTicketStatus' => '/local/api/controllers/v1/Enum/SkdCheckTicketStatus.php',
        'Local\\Api\\Controllers\\V1\\Enum\\SkdErrorStatusCode'   => '/local/api/controllers/v1/Enum/SkdErrorStatusCode.php',
        'Local\\Api\\Controllers\\V1\\Traits\\SkdTrait'           => '/local/api/controllers/v1/Traits/SkdTrait.php',
        'Local\\Api\\Controllers\\V1\\Traits\\DemoSkdTrait'       => '/local/api/controllers/v1/Traits/DemoSkdTrait.php',
        'Local\\Api\\Controllers\\V1\\Traits\\LoggerSkdTrait'     => '/local/api/controllers/v1/Traits/LoggerSkdTrait.php',

        'Custom\\Core\\Traits\\TimeTrait'             => '/local/modules/custom.core/lib/Traits/TimeTrait.php',
        'Custom\\Core\\Traits\\PropertyEnumTrait'     => '/local/modules/custom.core/lib/Traits/PropertyEnumTrait.php',
        'Custom\\Core\\Traits\\DateTimeFormatedTrait' => '/local/modules/custom.core/lib/Traits/DateTimeFormatedTrait.php',

        'Local\\Api\\Controllers\\V1\\TgGroup'       => '/local/api/controllers/v1/tgGroup.php',
    ]
);

(new \Custom\Core\Session())->checkRequest();

/*// init agent cleanupFileSemaphoresAgent
use Bitrix\Main\Type\DateTime;
if (!CAgent::GetList([], ['NAME' => '\\Custom\\Core\\Agents::cleanupFileSemaphoresAgent();'])->Fetch()) {
	$agentTimeOutSec = 10;
	$t = DateTime::createFromTimestamp(time() + $agentTimeOutSec);
	CAgent::AddAgent(
		'\\Custom\\Core\\Agents::cleanupFileSemaphoresAgent();',
		'custom.core',
		'N',                            // агент не критический
		3600,                          // интервал в секундах (1 час)
		'',                            // дата первого запуска (сейчас)
		'Y',                           // агент активен
		$t->toString(),                            // дата следующего запуска (оставить пустым)
		100                            // сортировка
	);
}*/


