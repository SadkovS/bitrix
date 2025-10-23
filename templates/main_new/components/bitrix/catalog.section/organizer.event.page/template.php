<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$this->setFrameMode(true);

$SEO_Event_JSON = [
	"@context" => "https://schema.org",
	"@graph" => []
];

?>

<?if($arResult["ITEMS"]):?>
    <div class="inline-flex w-full flex-col gap-5 md:gap-c_af">
        <div class="inline-flex flex-col gap-2.5 md:grid md:grid-cols-2 md:gap-5 md:gap-c_lg lg:grid-cols-3 xl:gap-10">
            <?if($_GET["ajax"] == "Y"):?>
                <?$APPLICATION->RestartBuffer();?>
            <?endif;?>

            <?foreach ($arResult['ITEMS'] as $item):?>
                <?
                $uniqueId = $item['ID'].'_'.md5($this->randString().$component->getAction());
                $areaId = $this->GetEditAreaId($uniqueId);
                $this->AddEditAction($uniqueId, $item['EDIT_LINK'], $elementEdit);
                $this->AddDeleteAction($uniqueId, $item['DELETE_LINK'], $elementDelete, $elementDeleteParams);

	              \Custom\Core\Helper::addSeoEventItem($item, $SEO_Event_JSON);

                $APPLICATION->IncludeComponent(
                    'bitrix:catalog.item',
                    '',
                    array(
                        'RESULT' => array(
                            'ITEM' => $item,
                            'AREA_ID' => $areaId,
                            'TYPE' => $item["PRODUCT"]["TYPE"],
                            'BIG_LABEL' => 'N',
                            'BIG_DISCOUNT_PERCENT' => 'N',
                            'BIG_BUTTONS' => 'N',
                            'SCALABLE' => 'N'
                        ),
                        'PARAMS' => $arParams,
                    ),
                    $component,
                    array('HIDE_ICONS' => 'Y')
                );
                ?>
            <?endforeach;?>

            <?if($arResult["SHOW_MORE_URL"]):?>
                <div class="btn__more md:hidden" data-more-url="<?=$arResult["SHOW_MORE_URL"]?>">
                    <span>Показать ещё</span>
                    <i class="svg_icon-arrow-r rotate-90"></i>
                </div>
            <?endif;?>

            <?if($_GET["ajax"] == "Y"):?>
                <?die();?>
            <?endif;?>
        </div>
    </div>
<?else:?>
    <div class="inline-flex flex-col gap-2 px-c_narrow *:text-sm md:my-5 md:gap-c_md md:px-5 *:md:text-lg lg:px-0">
        <div class="dark:text-white">Организатор пока не завел мероприятия.</div>
        <a href="/" class="inline-flex items-center gap-c_xs text-warning">
            <span>Перейти на главную</span>
        </a>
    </div>
<?endif;?>

<?php $APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode($SEO_Event_JSON) . '</script>'); ?>
