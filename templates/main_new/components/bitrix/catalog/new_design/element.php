<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

$this->setFrameMode(true);?>
    <main class="flex-auto">
        <section class="flex flex-col">
            <div class="lg:mb-10 mb-8 hidden md:flex flex-col items-center">
                <?
                $APPLICATION->IncludeComponent(
                    "bitrix:breadcrumb",
                    "main",
                    Array(
                        "PATH" => "",
                        "SITE_ID" => "s1",
                        "START_FROM" => "0"
                    )
                );
                ?>
            </div>
            <div class="container__small inline-flex flex-col gap-c_lg md:gap-10 lg:gap-c_2xl">

            <?php $componentElementParams = array(
                'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'],
                'IBLOCK_ID' => $arParams['IBLOCK_ID'],
                'PROPERTY_CODE' => (isset($arParams['DETAIL_PROPERTY_CODE']) ? $arParams['DETAIL_PROPERTY_CODE'] : []),
                'META_KEYWORDS' => $arParams['DETAIL_META_KEYWORDS'],
                'META_DESCRIPTION' => $arParams['DETAIL_META_DESCRIPTION'],
                'BROWSER_TITLE' => $arParams['DETAIL_BROWSER_TITLE'],
                'SET_CANONICAL_URL' => $arParams['DETAIL_SET_CANONICAL_URL'],
                'BASKET_URL' => $arParams['BASKET_URL'],
                'PRODUCT_URL' => $arParams['PRODUCT_URL'],
                "ORGANIZER_PAGE_URL" => $arParams["ORGANIZER_PAGE_URL"],
                'SHOW_SKU_DESCRIPTION' => $arParams['SHOW_SKU_DESCRIPTION'],
                'ACTION_VARIABLE' => $arParams['ACTION_VARIABLE'],
                'PRODUCT_ID_VARIABLE' => $arParams['PRODUCT_ID_VARIABLE'],
                'SECTION_ID_VARIABLE' => $arParams['SECTION_ID_VARIABLE'],
                'CHECK_SECTION_ID_VARIABLE' => (isset($arParams['DETAIL_CHECK_SECTION_ID_VARIABLE']) ? $arParams['DETAIL_CHECK_SECTION_ID_VARIABLE'] : ''),
                'PRODUCT_QUANTITY_VARIABLE' => $arParams['PRODUCT_QUANTITY_VARIABLE'],
                'PRODUCT_PROPS_VARIABLE' => $arParams['PRODUCT_PROPS_VARIABLE'],
                'CACHE_TYPE' => $arParams['CACHE_TYPE'],
                'CACHE_TIME' => $arParams['CACHE_TIME'],
                'CACHE_GROUPS' => $arParams['CACHE_GROUPS'],
                'SET_TITLE' => $arParams['SET_TITLE'],
                'SET_LAST_MODIFIED' => $arParams['SET_LAST_MODIFIED'],
                'MESSAGE_404' => $arParams['~MESSAGE_404'],
                'SET_STATUS_404' => $arParams['SET_STATUS_404'],
                'SHOW_404' => $arParams['SHOW_404'],
                'FILE_404' => $arParams['FILE_404'],
                'PRICE_CODE' => $arParams['~PRICE_CODE'],
                'USE_PRICE_COUNT' => $arParams['USE_PRICE_COUNT'],
                'SHOW_PRICE_COUNT' => $arParams['SHOW_PRICE_COUNT'],
                'PRICE_VAT_INCLUDE' => $arParams['PRICE_VAT_INCLUDE'],
                'PRICE_VAT_SHOW_VALUE' => $arParams['PRICE_VAT_SHOW_VALUE'],
                'USE_PRODUCT_QUANTITY' => $arParams['USE_PRODUCT_QUANTITY'],
                'PRODUCT_PROPERTIES' => (isset($arParams['PRODUCT_PROPERTIES']) ? $arParams['PRODUCT_PROPERTIES'] : []),
                'ADD_PROPERTIES_TO_BASKET' => (isset($arParams['ADD_PROPERTIES_TO_BASKET']) ? $arParams['ADD_PROPERTIES_TO_BASKET'] : ''),
                'PARTIAL_PRODUCT_PROPERTIES' => (isset($arParams['PARTIAL_PRODUCT_PROPERTIES']) ? $arParams['PARTIAL_PRODUCT_PROPERTIES'] : ''),
                'LINK_IBLOCK_TYPE' => $arParams['LINK_IBLOCK_TYPE'],
                'LINK_IBLOCK_ID' => $arParams['LINK_IBLOCK_ID'],
                'LINK_PROPERTY_SID' => $arParams['LINK_PROPERTY_SID'],
                'LINK_ELEMENTS_URL' => $arParams['LINK_ELEMENTS_URL'],

                'OFFERS_CART_PROPERTIES' => (isset($arParams['OFFERS_CART_PROPERTIES']) ? $arParams['OFFERS_CART_PROPERTIES'] : []),
                'OFFERS_FIELD_CODE' => $arParams['DETAIL_OFFERS_FIELD_CODE'],
                'OFFERS_PROPERTY_CODE' => (isset($arParams['DETAIL_OFFERS_PROPERTY_CODE']) ? $arParams['DETAIL_OFFERS_PROPERTY_CODE'] : []),
                'OFFERS_SORT_FIELD' => $arParams['OFFERS_SORT_FIELD'],
                'OFFERS_SORT_ORDER' => $arParams['OFFERS_SORT_ORDER'],
                'OFFERS_SORT_FIELD2' => $arParams['OFFERS_SORT_FIELD2'],
                'OFFERS_SORT_ORDER2' => $arParams['OFFERS_SORT_ORDER2'],

                'ELEMENT_ID' => $arResult['VARIABLES']['ELEMENT_ID'],
                'ELEMENT_CODE' => $arResult['VARIABLES']['ELEMENT_CODE'],
                'SECTION_ID' => $arResult['VARIABLES']['SECTION_ID'],
                'SECTION_CODE' => $arResult['VARIABLES']['SECTION_CODE'],
                'SECTION_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['section'],
                'DETAIL_URL' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['element'],
                'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
                'CURRENCY_ID' => $arParams['CURRENCY_ID'],
                'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
                'HIDE_NOT_AVAILABLE_OFFERS' => $arParams['HIDE_NOT_AVAILABLE_OFFERS'],
                'USE_ELEMENT_COUNTER' => $arParams['USE_ELEMENT_COUNTER'],
                "ADD_ELEMENT_CHAIN" => $arParams['ADD_ELEMENT_CHAIN'],
            );

            $elementId = $APPLICATION->IncludeComponent(
                'custom:catalog.element',
                'new_design',
                $componentElementParams,
                $component
            );?>

            </div>
        </section>
        <section class="pc:mt-15 mt-5 lg:mt-10">
          <div class="container__wide">
                <?$eventIds = \Custom\Core\Events::getOrganizatorEventsByProductId($arResult['VARIABLES']['ELEMENT_ID']);?>
                <?if($eventIds):?>
                    <?$GLOBALS[$arParams["FILTER_NAME"]]["ID"] = $eventIds;
                    ?>
                    <?$APPLICATION->IncludeComponent(
                        "bitrix:catalog.top",
                        "new_design",
                        array(
                            "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
                            "IBLOCK_ID" => $arParams["IBLOCK_ID"],
                            "FILTER_NAME" => $arParams["FILTER_NAME"],
                            "ELEMENT_SORT_FIELD" => $arParams["TOP_ELEMENT_SORT_FIELD"],
                            "ELEMENT_SORT_ORDER" => $arParams["TOP_ELEMENT_SORT_ORDER"],
                            "ELEMENT_SORT_FIELD2" => $arParams["TOP_ELEMENT_SORT_FIELD2"],
                            "ELEMENT_SORT_ORDER2" => $arParams["TOP_ELEMENT_SORT_ORDER2"],
                            "SECTION_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["section"],
                            "DETAIL_URL" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["element"],
                            "BASKET_URL" => $arParams["BASKET_URL"],
                            "ORGANIZER_PAGE_URL" => $arParams["ORGANIZER_PAGE_URL"],
                            "ACTION_VARIABLE" => $arParams["ACTION_VARIABLE"],
                            "PRODUCT_ID_VARIABLE" => $arParams["PRODUCT_ID_VARIABLE"],
                            "PRODUCT_QUANTITY_VARIABLE" => $arParams["PRODUCT_QUANTITY_VARIABLE"],
                            "PRODUCT_PROPS_VARIABLE" => $arParams["PRODUCT_PROPS_VARIABLE"],
                            "DISPLAY_COMPARE" => $arParams["USE_COMPARE"],
                            "ELEMENT_COUNT" => $arParams["TOP_ELEMENT_COUNT"],
                            "LINE_ELEMENT_COUNT" => $arParams["LINE_ELEMENT_COUNT"],
                            "PROPERTY_CODE" => ($arParams["TOP_PROPERTY_CODE"] ?? []),
                            "PROPERTY_CODE_MOBILE" => $arParams["TOP_PROPERTY_CODE_MOBILE"] ?? [],
                            "PRICE_CODE" => $arParams["~PRICE_CODE"],
                            "USE_PRICE_COUNT" => $arParams["USE_PRICE_COUNT"],
                            "SHOW_PRICE_COUNT" => $arParams["SHOW_PRICE_COUNT"],
                            "PRICE_VAT_INCLUDE" => $arParams["PRICE_VAT_INCLUDE"],
                            "PRICE_VAT_SHOW_VALUE" => $arParams["PRICE_VAT_SHOW_VALUE"],
                            "USE_PRODUCT_QUANTITY" => $arParams['USE_PRODUCT_QUANTITY'],
                            "ADD_PROPERTIES_TO_BASKET" => ($arParams["ADD_PROPERTIES_TO_BASKET"] ?? ''),
                            "PARTIAL_PRODUCT_PROPERTIES" => ($arParams["PARTIAL_PRODUCT_PROPERTIES"] ?? ''),
                            "PRODUCT_PROPERTIES" => ($arParams["PRODUCT_PROPERTIES"] ?? []),
                            "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                            "CACHE_TIME" => $arParams["CACHE_TIME"],
                            "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
                            "OFFERS_CART_PROPERTIES" => ($arParams["OFFERS_CART_PROPERTIES"] ?? []),
                            "OFFERS_FIELD_CODE" => $arParams["TOP_OFFERS_FIELD_CODE"] ?? [],
                            "OFFERS_PROPERTY_CODE" => ($arParams["TOP_OFFERS_PROPERTY_CODE"] ?? []),
                            "OFFERS_SORT_FIELD" => $arParams["OFFERS_SORT_FIELD"],
                            "OFFERS_SORT_ORDER" => $arParams["OFFERS_SORT_ORDER"],
                            "OFFERS_SORT_FIELD2" => $arParams["OFFERS_SORT_FIELD2"],
                            "OFFERS_SORT_ORDER2" => $arParams["OFFERS_SORT_ORDER2"],
                            "OFFERS_LIMIT" => ($arParams["TOP_OFFERS_LIMIT"] ?? 0),
                            'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
                            'CURRENCY_ID' => $arParams['CURRENCY_ID'],
                            'HIDE_NOT_AVAILABLE' => $arParams['HIDE_NOT_AVAILABLE'],
                            'VIEW_MODE' => ($arParams['TOP_VIEW_MODE'] ?? ''),
                            'ROTATE_TIMER' => ($arParams['TOP_ROTATE_TIMER'] ?? ''),
                            'TEMPLATE_THEME' => ($arParams['TEMPLATE_THEME'] ?? ''),

                            'LABEL_PROP' => $arParams['LABEL_PROP'] ?? '',
                            'LABEL_PROP_MOBILE' => $arParams['LABEL_PROP_MOBILE'] ?? '',
                            'LABEL_PROP_POSITION' => $arParams['LABEL_PROP_POSITION'] ?? '',
                            'ADD_PICT_PROP' => $arParams['ADD_PICT_PROP'] ?? '',
                            'PRODUCT_DISPLAY_MODE' => $arParams['PRODUCT_DISPLAY_MODE'],
                            'PRODUCT_BLOCKS_ORDER' => $arParams['TOP_PRODUCT_BLOCKS_ORDER'],
                            'PRODUCT_ROW_VARIANTS' => $arParams['TOP_PRODUCT_ROW_VARIANTS'],
                            'ENLARGE_PRODUCT' => $arParams['TOP_ENLARGE_PRODUCT'],
                            'ENLARGE_PROP' => $arParams['TOP_ENLARGE_PROP'] ?? '',
                            'SHOW_SLIDER' => $arParams['TOP_SHOW_SLIDER'],
                            'SLIDER_INTERVAL' => $arParams['TOP_SLIDER_INTERVAL'] ?? '',
                            'SLIDER_PROGRESS' => $arParams['TOP_SLIDER_PROGRESS'] ?? '',

                            'OFFER_ADD_PICT_PROP' => $arParams['OFFER_ADD_PICT_PROP'] ?? '',
                            'OFFER_TREE_PROPS' => ($arParams['OFFER_TREE_PROPS'] ?? []),
                            'PRODUCT_SUBSCRIPTION' => $arParams['PRODUCT_SUBSCRIPTION'],
                            'SHOW_DISCOUNT_PERCENT' => $arParams['SHOW_DISCOUNT_PERCENT'],
                            'DISCOUNT_PERCENT_POSITION' => $arParams['DISCOUNT_PERCENT_POSITION'],
                            'SHOW_OLD_PRICE' => $arParams['SHOW_OLD_PRICE'],
                            'MESS_BTN_BUY' => $arParams['~MESS_BTN_BUY'],
                            'MESS_BTN_ADD_TO_BASKET' => $arParams['~MESS_BTN_ADD_TO_BASKET'],
                            'MESS_BTN_SUBSCRIBE' => $arParams['~MESS_BTN_SUBSCRIBE'],
                            'MESS_BTN_DETAIL' => $arParams['~MESS_BTN_DETAIL'],
                            'MESS_NOT_AVAILABLE' => $arParams['~MESS_NOT_AVAILABLE'] ?? '',
                            'MESS_NOT_AVAILABLE_SERVICE' => $arParams['~MESS_NOT_AVAILABLE_SERVICE'] ?? '',
                            'ADD_TO_BASKET_ACTION' => $basketAction,
                            'SHOW_CLOSE_POPUP' => $arParams['COMMON_SHOW_CLOSE_POPUP'] ?? '',
                            'COMPARE_PATH' => $arResult['FOLDER'].$arResult['URL_TEMPLATES']['compare'],
                            'USE_COMPARE_LIST' => 'Y',

                            'COMPATIBLE_MODE' => ($arParams['COMPATIBLE_MODE'] ?? '')
                        ),
                        $component
                    );?>
                <?endif;?>
          </div>

        </section>

    </main>

<div class="-mb-10 mt-14 flex md:hidden flex-col items-center px-c_narrow">
	<?
	$APPLICATION->IncludeComponent(
		"bitrix:breadcrumb",
		"main",
		Array(
			"PATH" => "",
			"SITE_ID" => "s1",
			"START_FROM" => "0"
		)
	);
	?>
</div>
