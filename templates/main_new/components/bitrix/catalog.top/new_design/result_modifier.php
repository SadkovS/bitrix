<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var CBitrixComponentTemplate $this
 * @var CatalogElementComponent  $component
 */

use Bitrix\Main\Loader;
use Bitrix\Main\ORM;
use Bitrix\Highloadblock as HL;
use Custom\Core\Helper;

Loader::includeModule('custom.core');
Loader::includeModule('highloadblock');

foreach ($arResult['ITEMS'] as &$item)
{
	if ((int)$item['PROPERTIES']['EVENT_ID']['VALUE'] > 0) {
	    \Custom\Core\Events::getEventData($item['PROPERTIES']['EVENT_ID']['VALUE'], $item);
	}

    if(!$arResult["ORGANIZER_PAGE_URL"])
        $arResult["ORGANIZER_PAGE_URL"] = str_replace("#ORGANIZER_ID#", $item['COMPANY_ID'], $arParams["ORGANIZER_PAGE_URL"]);
}unset($item);

