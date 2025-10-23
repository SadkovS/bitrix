<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/**
 * @global CMain $APPLICATION
 */

global $APPLICATION;

//delayed function must return a string
if(empty($arResult))
	return "";

$strReturn = '';

$strReturn .= '<div class="container__wide inline-flex flex-col gap-c_lg">
                    <ul class="breadcrumbs text-sm lg:text-base">';

$itemSize = count($arResult);
for($index = 0; $index < $itemSize; $index++)
{
	$title = htmlspecialcharsex($arResult[$index]["TITLE"]);
	$arrow = ($index > 0? '<i class="fa fa-angle-right"></i>' : '');

	if($arResult[$index]["LINK"] <> "" && $index != $itemSize-1)
	{
        $strReturn .= '<li>
                            <a href="'.$arResult[$index]["LINK"].'">'.$title.'</a>
                        </li>';
	}
	else
	{
        $strReturn .= '<li>
                            <span>'.$title.'</span>
                        </li>';
	}
}

$strReturn .= '</ul></div>';

return $strReturn;
