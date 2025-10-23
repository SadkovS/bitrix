<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/**
 * @var CBitrixComponentTemplate $this
 * @var CatalogElementComponent  $component
 */

use Bitrix\Main\Loader;
use Bitrix\Main\ORM;
use Bitrix\Highloadblock as HL;

Loader::includeModule('custom.core');
Loader::includeModule('highloadblock');


$component = $this->getComponent();
$arParams  = $component->applyTemplateModifications();

foreach ($arResult['ITEMS'] as &$item)
{
	if ((int)$item['PROPERTIES']['EVENT_ID']['VALUE'] > 0) {
	    \Custom\Core\Events::getEventData($item['PROPERTIES']['EVENT_ID']['VALUE'], $item);
	}
}

if(
    $arResult["NAV_RESULT"]
    && $arResult["NAV_RESULT"]->NavPageCount > 1
    && ($arResult["NAV_RESULT"]->NavPageNomer+1 <= $arResult["NAV_RESULT"]->nEndPage))
{
    if(($pos = mb_strpos($arResult["NAV_PARAM"]["BASE_LINK"], "?")) !== false)
    {
        $arResult["NAV_PARAM"]["sUrlPath"] = mb_substr($arResult["NAV_PARAM"]["BASE_LINK"], 0, $pos);
    }
    else
    {
        $arResult["NAV_PARAM"]["sUrlPath"] = $arResult["NAV_PARAM"]["BASE_LINK"];
    }

    $nextPage = $arResult["NAV_RESULT"]->NavPageNomer + 1;
    $arResult["SHOW_MORE_URL"] = $arResult["NAV_PARAM"]["sUrlPath"]."?PAGEN_".$arResult["NAV_RESULT"]->NavNum."=".$nextPage."&ajax=Y";
}

$arResult["DEF_URL"] = \Custom\Core\Helper::getDefFilterUrl();