<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
use Bitrix\Main\Page\Asset;
CJSCore::Init(array("jquery", "fx"));
global $APPLICATION;
global $USER;
//if (!$USER->IsAuthorized() && $APPLICATION->GetCurPage() != "/login/" && $APPLICATION->GetCurPage() != "/documents/mobile_privacy_policy/") LocalRedirect("/login/?back_url=" . urlencode($APPLICATION->GetCurPageParam()), true);

$ddtToken = Bitrix\Main\Config\Option::get('custom.core', 'DDT_API_TOKEN');
$ddtSecretToken = Bitrix\Main\Config\Option::get('custom.core', 'DDT_SECRET_TOKEN');
$yaCaptchaClient = Bitrix\Main\Config\Option::get('custom.core', 'YA_CAPTCHA_CLIENT');
$yaToken = Bitrix\Main\Config\Option::get('custom.core', 'YA_API_TOKEN');
$yaGeoToken = Bitrix\Main\Config\Option::get('custom.core', 'YA_GEO_API_TOKEN');
?>

<!doctype html>
<html lang="ru" class="group/document">
<head>
    <meta charset="UTF-8">



    <meta name="format-detection" content="telephone=no">

    <?$viewport = $APPLICATION->ShowProperty('viewport');?>
		<?
		$scale = "";
		$currentUrl = $_SERVER['REQUEST_URI'];

		if (strpos($currentUrl, '/basket/') !== false) {
			$scale = ", user-scalable=no";
		}
		$APPLICATION->SetPageProperty('viewport', '<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=0.5, maximum-scale=5.0' . $scale . '">'); ?>

    <link rel="icon" href="/favicons/favicon.ico">
    <link rel="icon" href="/favicons/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" sizes="57x57" href="/favicons/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/favicons/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/favicons/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/favicons/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/favicons/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/favicons/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/favicons/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/favicons/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/favicons/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicons/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="manifest" href="/favicons/manifest.json">
    <meta name="msapplication-TileColor" content="#C92341">
    <meta name="msapplication-TileImage" content="/favicons/ms-icon-144x144.png">
    <meta http-equiv="Permissions-Policy" content="accelerometer=(), gyroscope=()">
    <title><? $APPLICATION->ShowTitle() ?></title>

    <?
    $APPLICATION->ShowHead();
    Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/assets/libs/toastify/toastify.js");
    Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/assets/js/custom.js");

    Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/assets/libs/toastify/toastify.css");
    Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/assets/css/custom.css");

    ?>
    <script>
        BX.message({
            DDT_API_TOKEN: '<?= CUtil::JSEscape($ddtToken) ?>',
            DDT_SECRET_TOKEN: '<?= CUtil::JSEscape($ddtSecretToken) ?>',
            YA_CAPTCHA_CLIENT: '<?= CUtil::JSEscape($yaCaptchaClient) ?>',
            YA_API_TOKEN: '<?= CUtil::JSEscape($yaToken) ?>',
            YA_GEO_API_TOKEN: '<?= CUtil::JSEscape($yaGeoToken) ?>',
        });
    </script>
    <link rel="stylesheet" crossorigin
        href="<?= \Custom\Core\Helper::asset_with_version(SITE_TEMPLATE_PATH . "/assets/css/main.css") ?>">
    <script type="module" crossorigin
        src="<?= \Custom\Core\Helper::asset_with_version(SITE_TEMPLATE_PATH . "/assets/js/main.js") ?>"></script>

	<?php

  $ogDescription = $APPLICATION->GetPageProperty('description');
	if (!$ogDescription) {
		$ogDescription = "Voroh — это современная платформа для тех, кто организует события и хочет делать это эффективно.";
	}
	/*if (!$ogImage) {
		$ogImage = "https://" . $_SERVER['HTTP_HOST'] . "/img/logo-dark.svg";
	}*/
	?>
	<meta property="og:title" content="<?php $APPLICATION->ShowTitle(); ?>" />
	<meta property="og:description" content="<?=$ogDescription?>" />
	<meta property="og:image" content="<?=$APPLICATION->ShowProperty(
        'og_image',
        "https://" . $_SERVER['HTTP_HOST'] . "/img/logo-dark.svg"
    )?>" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="https://<?=$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']?>" />
	<meta property="og:site_name" content="Voroh" />

	<script type="application/ld+json">
		{
			"@context": "http://schema.org",
			"@type": "Organization",
			"address": {
				"@type": "PostalAddress",
				"addressLocality": "Москва, Россия",
				"postalCode": "123242",
				"streetAddress": "Нововаганьковский переулок, 3с2"
			},
			"email": "info@voroh.ru",
			"name": "Voroh — ваш инструмент для умной организации мероприятий",
			"telephone": "+7 (499) 397-77-04",
			"logo": "https://<?=$_SERVER['HTTP_HOST']?>/img/logo-dark.svg",
			"url": "https://<?=$_SERVER['HTTP_HOST']?>"
		}
	</script>


    <?
    $APPLICATION->IncludeComponent(
        "bitrix:breadcrumb",
        "head",
        Array(
            "PATH" => "",
            "SITE_ID" => "s1",
            "START_FROM" => "0",
        )
    );
    ?>

</head>

<body class="inline-flex min-h-screen w-full flex-col bg-icons-1 dark:bg-primary loading">
    <div id="panel" style="z-index:160; position:absolute; width: 100%; left:0;top:0"><? $APPLICATION->ShowPanel(); ?>
    </div>

    <? if (!$GLOBALS["ho_head"]): ?>

        <? $APPLICATION->IncludeComponent(
            "custom:location.filter",
            "",
            array(
                "FILTER_NAME" => "eventCatalogFilter",
            ),
            $component
        ); ?>

        <header
            class="header flex mx-auto md:group-[.header-sticky]/document:h-auto md:group-[.menu-open]/document:h-auto md:group-[.header-sticky]/document:h-[60px] h-fit group-[.menu-open.header-sticky]/document:bg-t-1 dark:group-[.menu-open.header-sticky]/document:bg-primary group-[.header-sticky]/document:h-c_sxl group-[.menu-open]/document:h-c_sxl fixed w-full  top-0 z-[150] group-[.header-sticky]/document:bg-t-1 dark:group-[.header-sticky]/document:bg-primary   dark:bg-primary"
            data-lp>
            <div
                class="container__header relative inline-flex items-center  justify-between  gap-[1.1875rem] w-full lg:gap-c_lg">
                <div class="header__menu menu lg:hidden">
                    <button type="button" class="menu__icon icon-menu"><span></span></button>
                    <nav class="menu__body">
                        <?
                        /*$APPLICATION->IncludeComponent(
                            "custom:events.category.menu",
                            "",
                            array(
                                "DETAIL_PAGE" => "/compilation/#CODE#/",
                                "CACHE_TIME" => 180,
                                "FILTER_NAME" => "eventCatalogFilter"
                            ),
                            $component
                        );*/ ?>
                        <div class="menu__block lg:flex-auto" data-in=".js-desktop-search, 999">
                            <div class="inline-flex gap-c_narrow w-full lg:justify-between lg:gap-10">
                                <div class="inline-flex lg:gap-c_sm flex-auto xl:justify-between">
                                    <? $APPLICATION->IncludeComponent(
                                        "custom:search.header",
                                        "search_input",
                                        array(
                                        ),
                                        $component
                                    ); ?>

	                                <div class="js-desktop-filter hidden lg:block relative">
		                                <button class="btn__filter" data-popup="#filter">
			                                <i class="svg_icon-filter"></i>
		                                </button>
	                                </div>

                                </div>

	                            <div class="header-sep hidden laptop:flex"></div>
	                            <div class="header-sep hidden laptop:flex"></div>

	                            <div class="hidden lg:inline-flex">
		                            <a href="/landing/organizer/" class="btn__red">
			                            <span class="font-gothic text-xs lg:text-lg lg:text-sm">Разместить свое событие</span>
		                            </a>
	                            </div>

	                            <button class="btn__filter lg:hidden" data-popup="#filter">
		                            <i class="svg_icon-filter"></i>
	                            </button>

                            </div>
                        </div>
                        <div class="menu__block ">
                            <div class="inline-flex flex-col gap-2.5 xl:gap-c_md lg:flex-col">
                                <a href="/about/" class="menu__link">
                                    <span>О компании</span>
                                </a>
                                <a href="/landing/organizer/" class="menu__link" target="_blank">
                                    <span>Для организатора</span>
                                    <span class="svg_icon-arrow text-mini lg:text-xs"></span>
                                </a>
                                <a href="#" class="menu__link" data-popup="#support-popup">
                                    <span>Поддержка </span>
                                    <span class="svg_icon-support "></span>
                                </a>

	                            <label class="theme__switcher">
		                            <input type="checkbox" id="theme" class="hidden">
		                            <span class="theme__switcher-wrapper">
									                <i class="svg_icon-moon dark"></i>
									                <i class="svg_icon-sun light"></i>
										            </span>
	                            </label>

                            </div>
                        </div>

		                    <div class="menu__block ">
			                    <a href="/landing/organizer/" class="btn__red">
				                    <span class="font-gothic text-xs lg:text-lg lg:text-sm">Разместить свое событие</span>
			                    </a>
		                    </div>

                        <div class="menu__block ">
                            <div class="inline-flex justify-between gap-5  items-start w-full lg:flex-col">
                                <div class="inline-flex flex-col gap-2.5 dark:text-white lg:gap-5">
                                    <a href="mailto:info@voroh.ru" class="font-gothic text-sm lg:text-xl">
                                        info@voroh.ru
                                    </a>
                                    <?/*<a href="tel:74993977704" class="font-gothic text-sm lg:text-xl">
                                  +7 (499) 397-77-04
                              </a>*/ ?>
                                </div>
                                <div class="inline-flex gap-2.5">
                                    <?/*<a href="#" class="link__icon">
                                  <i class="svg_icon-vk"></i>
                              </a>
                              <a href="#" class="link__icon">
                                  <i class="svg_icon-telegram"></i>
                              </a>*/ ?>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>
                <a href="/">
                    <div class="header__logo hidden dark:block" style="background-image:url(/img/logo-white.svg)"></div>
                    <div class="header__logo block dark:hidden" style="background-image:url(/img/logo-dark.svg)"></div>
                </a>
                <div class="place flex-auto inline-flex justify-end pt-1 lg:relative lg:gap-10 ml-5 laptop:ml-2.5 xl:ml-5">

                    <div class="inline-flex js-desktop-search lg:flex-auto">


                        <? $APPLICATION->IncludeComponent(
                            "custom:search.header",
                            "",
                            array(
                            ),
                            $component
                        ); ?>

                    </div>


                    <? $APPLICATION->ShowViewContent('filter_template'); ?>
                </div>

                <?
                $APPLICATION->IncludeComponent(
                    "custom:smart.filter",
                    "",
                    array(
                        "FILTER_NAME" => "eventCatalogFilter",
                    ),
                    $component
                ); ?>

        </header>
        <div
            class="md:pt-30 wrapper inline-flex h-full w-full flex-auto flex-col justify-between pt-20 has-[.detail]:pb-20 md:pt-[5.375rem] lg:pt-[6.75rem] lg:has-[.detail]:pb-0">
        <? else: ?>
            <div
                class="wrapper inline-flex h-full min-h-screen w-full flex-auto flex-col justify-between has-[.detail]:pb-20 laptop:max-h-screen laptop:has-[.detail]:pb-0">
            <? endif; ?>