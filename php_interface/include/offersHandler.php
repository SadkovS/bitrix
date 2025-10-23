<?php

use Bitrix\Main\Loader;
use Bitrix\Main\ORM;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\UserTable as UTable;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Highloadblock as HL;

Loader::includeModule('custom.core');
Loader::includeModule('iblock');

AddEventHandler("iblock", "OnIBlockElementUpdate", ["IBlockEvents", "OnIBlockElementUpdate"]);

// объект инфоблока Торговых предложений
$iblock = \Bitrix\Iblock\Iblock::wakeUp(IBLOCK_TICKET_OFFERS_ID);

//Редактирование следующих атрибутов у ценовой категории
// диспетчер событий
\Bitrix\Main\ORM\EventManager::getInstance()->addEventHandler(
    $iblock->getEntityDataClass(),
    DataManager::EVENT_ON_UPDATE,
    function (\Bitrix\Main\Event $e) {
        $id = (int)$e->getParameter('id')['ID'];
        $arParams  = $e->getParameter('fields');
        $object    = $e->getParameter('object');
        $productID = (int)$object->get('CML2_LINK')->getValue();
        $elementEntity = \Bitrix\Iblock\IblockTable::compileEntity('ticketsOffers');
        $objectOffer = $elementEntity->wakeUpObject($id);
        $previewText = $objectOffer->fill('PREVIEW_TEXT');
        if(isset($arParams['PREVIEW_TEXT']) && $previewText == $arParams['PREVIEW_TEXT'])
            unset($arParams['PREVIEW_TEXT']);
        //IBlockEvents::resetEventStatus($productID, $arParams);
    }
);

class IBlockEvents {
    /**
     * @param $arFields
     * @param $fields
     *
     * @return void
     */
    public static function OnIBlockElementUpdate($arFields, $fields)
    {
        if ($arFields['IBLOCK_ID'] == IBLOCK_TICKET_OFFERS_ID) {
            $productID = (int)$arFields['PROPERTY_VALUES'][CML2_LINK_PROPERTY_ID][$arFields['ID'] . ':' . CML2_LINK_PROPERTY_ID]['VALUE'];
            $arParams  = [];
            if ($arFields['NAME'] != $fields['NAME']) $arParams['NAME'] = $arFields['NAME'];
            if ($arFields['PREVIEW_TEXT'] != $fields['PREVIEW_TEXT']) $arParams['PREVIEW_TEXT'] = $arFields['PREVIEW_TEXT'];
            //Debug::writeToFile($arParams,'update_2','OFFERS.txt');
            //IBlockEvents::resetEventStatus($productID, $arParams);
        }
    }


    /**
     * @param int   $productID
     * @param array $arParams
     *
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function resetEventStatus(int $productID = 0, array $arParams = []): void
    {
        if ($productID > 0) {
            $elementEntity    = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
            $productDataClass = $elementEntity->getDataClass();
            $propField        = $elementEntity->getField('EVENT_ID');
            $propEntity       = $propField->getRefEntity();

            $resEvent = $productDataClass::getList(
                [
                    'select'  => [
                        'ID',
                        'EVENT'  => 'EVENT_ID.VALUE',
                        'STATUS' => 'REF_EVENT.UF_STATUS'
                    ],
                    'filter'  => ['ID' => $productID],
                    'runtime' => [
                        new \Bitrix\Main\Entity\ReferenceField(
                            'REF_EVENT',
                            '\Custom\Core\Events\EventsTable',
                            ['this.REF_PROP.VALUE' => 'ref.ID'],
                            ['join_type' => 'LEFT'],
                        ),
                        new \Bitrix\Main\Entity\ReferenceField(
                            'REF_PROP',
                            $propEntity,
                            ['this.ID' => 'ref.IBLOCK_ELEMENT_ID'],
                            ['join_type' => 'LEFT'],
                        ),
                    ]
                ]
            );
            $arEvent  = $resEvent->fetch();
//todo Временно отключено

//            if (in_array($arEvent['STATUS'], [5, 2]) && (isset($arParams['NAME']) || isset($arParams['PREVIEW_TEXT']))) {
//                $hlblock     = HL\HighloadBlockTable::getById(HL_EVENTS_ID)->fetch();
//                $entity      = HL\HighloadBlockTable::compileEntity($hlblock);
//                $entityClass = $entity->getDataClass();
//                $objEvent    = $entityClass::getByPrimary((int)$arEvent['EVENT'])->fetchObject();
//
//                $objEvent->set('UF_STATUS', 4);
//                $objEvent->save();
//            }
        }
    }
}








