 <?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
$counter = 0;
$this->setFrameMode(true);

 $SEO_Event_JSON = [
	 "@context" => "https://schema.org",
	 "@graph" => []
 ];
 ?>

<div class="inline-flex w-full flex-col gap-5 md:gap-c_af lg:gap-10">
    <div class="inline-flex items-baseline justify-between px-c_narrow lg:px-0">
        <div class="h1 dark:text-white">Другие события организатора</div>
        <a href="<?=$arResult["ORGANIZER_PAGE_URL"]?>" class="link__red-big">
            <span>Все</span>
            <i class="svg_icon-arrow-r"></i>
        </a>
    </div>
    <div class="inline-flex flex-col gap-2.5 md:grid md:grid-cols-2 md:gap-5 md:gap-c_lg lg:grid-cols-3 xl:gap-10">

        <?foreach ($arResult['ITEMS'] as $item):?>
            <?
            $uniqueId = $item['ID'].'_'.md5($this->randString().$component->getAction());
            $areaId = $this->GetEditAreaId($uniqueId);
            $this->AddEditAction($uniqueId, $item['EDIT_LINK'], $elementEdit);
            $this->AddDeleteAction($uniqueId, $item['DELETE_LINK'], $elementDelete, $elementDeleteParams);
            $counter++;
            if ($counter == 3) {
                $item['DOP_CLASS'] = "!hidden lg:!flex";
            }

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

        <?/*<div class="btn__more lg:hidden">
            <span>Показать ещё</span>
            <i class="svg_icon-arrow-r rotate-90"></i>
        </div>*/?>
    </div>
</div>

 <?php $APPLICATION->AddHeadString('<script type="application/ld+json">' . json_encode($SEO_Event_JSON) . '</script>'); ?>