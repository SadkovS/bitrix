<?php

namespace Local\PhpInterface\Handlers;

include_once $_SERVER['DOCUMENT_ROOT']."/local/modules/custom.core/lib/Traits/PropertyEnumTrait.php";

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\ORM;
use Custom\Core\Traits\PropertyEnumTrait;

trait BarcodeHandlerTrait
{
    use PropertyEnumTrait;

    private array $barcodeStatusList;

    private function handleBarcodeGeneration($ignoreSold = false)
    {
        $this->barcodeStatusList = $this->getPropertiesEnum('Barcodes', 'UF_STATUS','ENUM_XML_ID');

        $queryEntity      = new ORM\Query\Query('Custom\Core\Tickets\BarcodesTable');
        $barCodesEntity   = $queryEntity->getEntity();
        $existingBarcodes = $this->getExistingBarcodes($queryEntity, $ignoreSold = false);

        if ($existingBarcodes->getCount() < 1) {
            $this->generateNewBarcodes($barCodesEntity);
        } else {
            $this->updateExistingBarcodes($existingBarcodes, $barCodesEntity);
        }
    }

    private function getExistingBarcodes($queryEntity, $ignoreSold = false)
    {
        if (!$ignoreSold) {
            $status = [
                $this->barcodeStatusList['sold']['ENUM_ID'],
                $this->barcodeStatusList['returned']['ENUM_ID'],
                $this->barcodeStatusList['canceled']['ENUM_ID'],
                $this->barcodeStatusList['used']['ENUM_ID'],
                $this->barcodeStatusList['request_refund']['ENUM_ID'],
            ];
        }else{
            $status = [
                $this->barcodeStatusList['returned']['ENUM_ID'],
                $this->barcodeStatusList['canceled']['ENUM_ID'],
                $this->barcodeStatusList['used']['ENUM_ID'],
                $this->barcodeStatusList['request_refund']['ENUM_ID'],
            ];
        }

        $elementEntity = \Bitrix\Iblock\IblockTable::compileEntity('ticketsOffers');

        return $queryEntity
            ->setSelect(['*'])
            ->setFilter(
                [
                    'UF_EVENT_ID' => $this->eventID,
                    '!UF_STATUS'  => $status
                ]
            )
            ->registerRuntimeField(
                'OFFER',
                array(
                    'data_type' => $elementEntity,
                    'reference' => array('=this.UF_OFFER_ID' => 'ref.ID'),
                    'join_type' => 'INNER'
                )
            )
            ->setGroup('ID')
            ->countTotal(true)
            ->exec();
    }

    private function generateNewBarcodes($barCodesEntity)
    {
        $arTickets  = \Custom\Core\Products::getInstance()->getTicketsByEventId($this->eventID);
        $totalQty   = $this->calculateTotalQuantity($arTickets);
        $this->arBarcodes = \Custom\Core\Products::getInstance()->genBarcodes($totalQty);

        foreach ($arTickets as $ticket) {
            $this->createBarcodesForTicket($ticket, $this->arBarcodes, $barCodesEntity);
        }
    }

    private function createBarcodesForTicket(mixed $ticket, &$arBarCodes, $barCodesEntity): void
    {
        $qty = (int)$ticket['SKU_QUANTITY'];
        while ($qty > 0) {
            $barCode    = array_shift($arBarCodes);

            $objBarCode = $barCodesEntity->createObject();
            $objBarCode->set('UF_EVENT_ID', $this->eventID);
            $objBarCode->set('UF_BARCODE', $barCode);
            $objBarCode->set('UF_STATUS', 63);
            $objBarCode->set('UF_OFFER_ID', $ticket['SKU_ID']);
            $objBarCode->set('UF_SERIES', $this->series);
            $objBarCode->save();
            $qty--;
        }
    }

    private function updateExistingBarcodes(mixed $existingBarcodes, ORM\Entity $barCodesEntity)
    {
        $arTickets     = \Custom\Core\Products::getInstance()->getTicketsByEventId($this->eventID);
        $totalQty      = $this->calculateTotalQuantity($arTickets);
        $barCodesCount = $existingBarcodes->getCount();

        while ($code = $existingBarcodes->fetch()) {
            $arCodes[]                                      = $code['UF_BARCODE'];
            $curBarCodes[$code['UF_OFFER_ID']][$code['ID']] = $code;
        }

        if ($totalQty > $barCodesCount) {
            $arNewBarCodes = \Custom\Core\Products::getInstance()->genBarcodes($totalQty - $barCodesCount, $arCodes);
            foreach ($arTickets as $ticket) {
                if (!is_array($curBarCodes[$ticket['SKU_ID']])) $curBarCodes[$ticket['SKU_ID']] = [];
                $cntCodes = count($curBarCodes[$ticket['SKU_ID']]);
                if (count($curBarCodes[$ticket['SKU_ID']]) < $ticket['SKU_QUANTITY']) {
                    $qty = (int)$ticket['SKU_QUANTITY'] - $cntCodes;
                    while ($qty > 0) {
                        $barCode    = array_shift($arNewBarCodes);
                        $objBarCode = $barCodesEntity->createObject();
                        $objBarCode->set('UF_EVENT_ID', $this->eventID);
                        $objBarCode->set('UF_BARCODE', $barCode);
                        $objBarCode->set('UF_STATUS', 63);
                        $objBarCode->set('UF_SERIES', $this->series);
                        $objBarCode->set('UF_OFFER_ID', $ticket['SKU_ID']);
                        $objBarCode->save();
                        $qty--;
                    }
                }
            }
        }

        if ($totalQty < $barCodesCount) {
            foreach ($arTickets as $ticket) {
                if (!is_array($curBarCodes[$ticket['SKU_ID']])) $curBarCodes[$ticket['SKU_ID']] = [];
                $cntCodes = count($curBarCodes[$ticket['SKU_ID']]);
                if ($cntCodes > $ticket['SKU_QUANTITY']) {
                    $qty        = $cntCodes - (int)$ticket['SKU_QUANTITY'];
                    $arDelCodes = [];
                    foreach ($curBarCodes[$ticket['SKU_ID']] as $code) {
                        if ($code['UF_STATUS'] == 63) $arDelCodes[] = $code;
                        if (count($arDelCodes) == $qty) break;
                    }
                    unset($code);
                    if (count($arDelCodes) < $qty) $this->bxApp->ThrowException('Ошибка удаления штрихкодов!');

                    foreach ($arDelCodes as $code) {
                        $barCodesEntity->getDataClass()::delete($code['ID']);
                    }
                    unset($code, $arDelCodes);
                }
            }
        }
    }

    private function calculateTotalQuantity(array $arTickets): int
    {
        $totalQty = 0;
        foreach ($arTickets as $ticket) {
            $totalQty += $ticket['SKU_QUANTITY'];
        }
        return $totalQty;
    }

    public function deleteTicketBarcode($ticketId, $barcodeStatus = [])
    {
        $queryEntity      = new ORM\Query\Query('Custom\Core\Tickets\BarcodesTable');
        $barCodesEntity   = $queryEntity->getEntity();

        $filter = ["UF_OFFER_ID" => $ticketId];

        if($barcodeStatus)
        {
            $barcodeStatusList = $this->getPropertiesEnum('Barcodes', 'UF_STATUS','ENUM_XML_ID');

            foreach ($barcodeStatus as $status)
            {
                $filter["UF_STATUS"][] = $barcodeStatusList[$status]['ENUM_ID'];
            }
        }

        $queryEntity
            ->setSelect(['ID'])
            ->setFilter($filter)
            ->setGroup('ID')
            ->countTotal(true)
            ->exec();

        while ($barcode = $queryEntity->fetch()) {
            $barCodesEntity->getDataClass()::delete($barcode['ID']);
        }
    }
} 