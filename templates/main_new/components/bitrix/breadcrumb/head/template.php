<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * @global CMain $APPLICATION
 */

global $APPLICATION;

//delayed function must return a string
if(empty($arResult) || count($arResult) == 1)
	return "";

$strReturn = '';

$serverName = Custom\Core\Helper::getSiteUrl();

$result = [
    "@context" => "http://schema.org",
    "@type" => "BreadcrumbList",
    "itemListElement" => [],
];
foreach ($arResult as $key => $item)
{
    if(!$item["LINK"])
        $item["LINK"] = $APPLICATION->GetCurPage();

    $result["itemListElement"][] = [
        "@type" => "ListItem",
        "position" => $key+1,
        "item" => [
            "@id" => $serverName.$item["LINK"],
            "name" => $item["TITLE"],
        ]
    ];
}

$strReturn = json_encode($result);

return '<script type="application/ld+json">'.$strReturn.'</script>';
