<?php

namespace Custom\Core;

use Bitrix\Main\Loader;
use Bitrix\Main\ORM;

class Products {

    protected static $instance = null;
    const IBLOCK_PRODUCTS_API_CODE = 'tickets';
    const IBLOCK_OFFERS_API_CODE   = 'ticketsOffers';

    /**
     * @return Products
     */
    public static function getInstance(): Products
    {
        if (!static::$instance) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    /**
     * @param array $arrFields
     * @param       $isOffer
     *
     * @return array
     */
    public function createProduct(array $arrFields, $isOffer = false)
    {
        try {

            global $USER;
            Loader::includeModule('iblock');
            Loader::includeModule('catalog');

            /*
             * Создание элемента в инфоблоке
             *
             * Компиляция сущности инфоблока
            */
            if ($isOffer) {
                $elementEntity = \Bitrix\Iblock\IblockTable::compileEntity(self::IBLOCK_OFFERS_API_CODE);
            } else {
                $elementEntity = \Bitrix\Iblock\IblockTable::compileEntity(self::IBLOCK_PRODUCTS_API_CODE);
            }

            //Создание объекта инфоблока
            $objElement = $elementEntity->createObject();

            //Заполнение полей
            foreach ($arrFields as $key => $value) {

                if ($elementEntity->hasField($key)) {

                    if ($key == "DATES" && $isOffer) {
                        if ($value) {
                            $dates = array_filter($value, 'trim');

                            if ($dates) {
                                sort($dates);

                                foreach ($dates as $date) {
                                    $objElement->addToDates($date);
                                }
                            }
                        }
                    } else
                        $objElement->set($key, $value);
                    if ($key == 'SHOW_STOCK' && $isOffer)
                        $objElement->set($key, (int)$value != 1 ? 0 : 1);

                    if ($key == 'NAME' && $isOffer) {
                        $offerName = \Custom\Core\Helper::trimIBElementName($value, $arrFields['TYPE']);
                        $objElement->set($key, $offerName);
                    } elseif ($key == 'NAME' && !$isOffer) {
                        $postfix = strtoupper(substr(sha1(microtime(true)), 0, 8));
                        $objElement->set('CODE', \Cutil::translit($value . '-' . $postfix, "ru", ['replace_space' => '-', 'replace_other' => '-']));
                    } elseif ($key == 'ID') continue;
                }
            }

            if ($isOffer && !isset($arrFields['SHOW_STOCK'])) $objElement->set('SHOW_STOCK', 0);
            //Сохранение
            $resElement = $objElement->save();


            if (!$resElement->isSuccess()) {
                throw new \Exception(implode(', ', $resElement->getErrors()));
            }

            /*
             * Преобразование элемента инфоблока в товар
             * Создание объекта каталога
            */
            $objCatalog = \Bitrix\Catalog\ProductTable::createObject();
            /*
             * Заполнение полей
             */
            $objCatalog->setId($resElement->getId());
            if (!$isOffer) $objCatalog->setType(3); //Товар с торговыми предложениями
            if ($isOffer) $objCatalog->setType(4); //Торговое предложение
            $objCatalog->setVatIncluded('N');
            $objCatalog->setQuantity($arrFields['QUANTITY']);
            $objCatalog->setAvailable('Y');

            $resCatalog = $objCatalog->save();

            if (!$resCatalog->isSuccess()) {
                throw new \Exception(implode(', ', $resCatalog->getErrors()));
            }

            // Установка цены
            if ($isOffer && isset($arrFields['PRICE'])) {
                $objPrice = \Bitrix\Catalog\PriceTable::createObject();

                $objPrice->setProductId($resElement->getId());
                $objPrice->setPrice((int)$arrFields['PRICE']);
                $objPrice->setPriceScale((int)$arrFields['PRICE']);
                $objPrice->setCatalogGroupId(1); // Розничная цена
                $objPrice->setCurrency('RUB');

                $resPrice = $objPrice->save();

                if (!$resPrice->isSuccess()) {
                    throw new \Exception(implode(', ', $resPrice->getErrors()));
                }
            }

            $id = $resElement->getId();
            static::addToIndex($id);

            return ['status' => 'success', 'id' => $resElement->getId(), 'code' => $objElement->get('CODE')];

        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => str_replace(',', ';', $e->getMessage())];
        }
    }

    /**
     * @param       $id
     * @param array $arrFields
     * @param       $isOffer
     *
     * @return array
     */
    public function updateProduct($id, array $arrFields, $isOffer = false)
    {
        try {
            global $USER;
            Loader::includeModule('iblock');
            Loader::includeModule('catalog');

            if ($isOffer) {
                $elementEntity = \Bitrix\Iblock\IblockTable::compileEntity(self::IBLOCK_OFFERS_API_CODE);
            } else {
                $elementEntity = \Bitrix\Iblock\IblockTable::compileEntity(self::IBLOCK_PRODUCTS_API_CODE);
            }

            //Получение объекта инфоблока
            $objElement = $elementEntity->wakeUpObject($id);
            $oldName    = $objElement->fillName();
            //Заполнение полей
            foreach ($arrFields as $key => $value) {

                if ($elementEntity->hasField($key)) {

                    if ($key == "DATES" && $isOffer && $value) {
                        $objElement->removeAllDates();

                        $dates = array_filter($value, 'trim');

                        if ($dates) {
                            sort($dates);

                            foreach ($dates as $date) {
                                $objElement->addToDates($date);
                            }
                        }
                    } elseif ($key == 'SHOW_STOCK' && $isOffer)
                        $objElement->set($key, (int)$value != 1 ? 0 : 1);
                    else
                        $objElement->set($key, $value);

                    if ($key == 'NAME' && $isOffer) {
                        $offerName = \Custom\Core\Helper::trimIBElementName($value, $arrFields['TYPE']);
                        $objElement->set($key, $offerName);
                    } elseif ($key == 'NAME' && !$isOffer && $value != $oldName) {
                        $postfix = strtoupper(substr(sha1(microtime(true)), 0, 8));
                        $objElement->set('CODE', \Cutil::translit($value . '-' . $postfix, "ru", ['replace_space' => '-', 'replace_other' => '-']));
                    }
                }
            }

            if ($isOffer && !isset($arrFields['SHOW_STOCK'])) $objElement->set('SHOW_STOCK', 0);
            //Сохранение
            $resElement = $objElement->save();

            if (!$resElement->isSuccess()) {
                throw new \Exception(implode(', ', $resElement->getErrors()));
            }

            if (isset($arrFields['QUANTITY']) && $isOffer) {

                $objCatalog = \Bitrix\Catalog\ProductTable::wakeUpObject($id);
                $objCatalog->setQuantity($arrFields['QUANTITY']);
                $objCatalog->setAvailable('Y');

                $resCatalog = $objCatalog->save();

                if (!$resCatalog->isSuccess()) {
                    throw new \Exception(implode(', ', $resCatalog->getErrors()));
                }
            }


            // Обновление цены
            if ($isOffer && isset($arrFields['PRICE'])) {

                $priceEntity = \Bitrix\Catalog\PriceTable::getEntity();
                $query       = new ORM\Query\Query($priceEntity);
                $resQuery    = $query
                    ->setSelect(['ID', 'PRICE'])
                    ->setFilter(['PRODUCT_ID' => $id])
                    ->exec();
                if ($objPrice = $resQuery->fetchObject()) {

                    $objPrice->setPrice((int)$arrFields['PRICE']);
                    $objPrice->setPriceScale((int)$arrFields['PRICE']);
                    $resPrice = $objPrice->save();
                    if (!$resPrice->isSuccess()) {
                        throw new \Exception(implode(', ', $resPrice->getErrors()));
                    }
                }
            }

            static::addToIndex($id);

            return ['status' => 'success', 'id' => $id];

        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => str_replace(',', ';', $e->getMessage())];
        }
    }

    public static function addToIndex($id)
    {
        if (Loader::includeModule("iblock")) {
            \CIBlockElement::UpdateSearch($id, true);
        }
    }

    public function genBarcodes(int $quantity, array $barcodes = []): array
    {
        $newBarcodes = [];

        while ($quantity > 0) {
            do $barcode = Helper::getRandomInt();
            while (in_array($barcode, $newBarcodes) || in_array($barcode, $barcodes));
            $newBarcodes[] = $barcode;
            $quantity--;
        }

        return $newBarcodes;
    }

    public function getTicketsByEventId(int $id): array
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');
        $productEntity = \Bitrix\Iblock\IblockTable::compileEntity(self::IBLOCK_PRODUCTS_API_CODE);
        $query         = new ORM\Query\Query($productEntity);
        $elementEntity = \Bitrix\Iblock\IblockTable::compileEntity(self::IBLOCK_OFFERS_API_CODE);
        $propField     = $elementEntity->getField('CML2_LINK');
        $propEntity    = $propField->getRefEntity();

        $resProduct = $query
            ->setSelect(
                [
                    'EVENT'        => 'EVENT_ID.VALUE',
                    'PRODUCT_ID'   => 'ID',
                    'SKU_ID'       => 'OFFER.ID',
                    'SKU_TYPE'     => 'OFFER.TYPE.VALUE',
                    'SKU_QUANTITY' => 'PROPS.QUANTITY',
                ]
            )
            ->setFilter(['EVENT_ID.VALUE' => $id])
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'TICKETS',
                    $propEntity,
                    ['this.ID' => 'ref.VALUE'],
                    ['join_type' => 'LEFT'],
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'OFFER',
                    $elementEntity,
                    ['this.TICKETS.IBLOCK_ELEMENT_ID' => 'ref.ID'],
                    ['join_type' => 'LEFT'],
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'PROPS',
                    '\Bitrix\Catalog\ProductTable',
                    ['this.OFFER.ID' => 'ref.ID'],
                    ['join_type' => 'LEFT'],
                )
            )
            ->setGroup(['SKU_ID'])
            ->countTotal(true)
            ->exec();
        $arItems    = [];
        while ($offer = $resProduct->fetch()) {
            $arItems[] = $offer;
        }

        $arReservItems = $this->getTicketsReservByEventId($id);

        if($arReservItems)
        {
            foreach ($arItems as &$item)
            {
                if($arReservItems[$item["SKU_ID"]])
                {
                    if($item["SKU_QUANTITY"])
                    {
                        $item["SKU_QUANTITY"] += $arReservItems[$item["SKU_ID"]]["SKU_QUANTITY"];
                    }
                    else
                    {
                        $item["SKU_QUANTITY"] = $arReservItems[$item["SKU_ID"]]["SKU_QUANTITY"];
                    }
                }
            }
            unset($item);
        }

        return $arItems;
    }

    public function getTicketsReservByEventId(int $id): array
    {
        $filter = [
            'PROPS.CODE' => "EVENT_ID",
            'PROPS.VALUE' => $id,
            '!PAYED' => "Y",
            '!CANCELED' => "Y",
            'STATUS_ID' => "N",
            "!=BASKET_PROPS.VALUE" => "Y",
        ];

        $ordersEntity = new ORM\Query\Query('\Bitrix\Sale\Internals\OrderTable');
        $query       = $ordersEntity
            ->setSelect([
                "SKU_ID" => "BASKET_REFS.PRODUCT_ID",
                'SKU_QUANTITY'
            ])
            ->setFilter($filter)
            ->registerRuntimeField(
                "PROPS",
                [
                    'data_type' => 'Bitrix\Sale\Internals\OrderPropsValueTable',
                    'reference' => array('=this.ID' => 'ref.ORDER_ID'),
                    'join_type' => 'INNER'
                ]
            )
            ->registerRuntimeField(
                "BASKET_REFS",
                [
                    'data_type' => 'Bitrix\Sale\Internals\BasketTable',
                    'reference' => array('=this.ID' => 'ref.ORDER_ID'),
                    'join_type' => 'INNER'
                ]
            )
            ->registerRuntimeField(
                '',
                (new \Bitrix\Main\Entity\ReferenceField(
                    'BASKET_PROPS',
                    'Bitrix\Sale\Internals\BasketPropertyTable',
                    \Bitrix\Main\ORM\Query\Join::on('ref.BASKET_ID', 'this.BASKET_REFS.ID')
                        ->where("ref.CODE", "=", "IS_REFUNDED")
                ))->configureJoinType(
                    \Bitrix\Main\ORM\Query\Join::TYPE_LEFT,
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ExpressionField(
                    'SKU_QUANTITY', 'SUM(%s)', ['BASKET_REFS.QUANTITY']
                )
            )
            ->exec();

        while($item = $query->fetch()){
            $arItems[$item["SKU_ID"]] = $item;
        }

        return $arItems ?? [];
    }

    public function genTicketSeries(): string
    {
        $permitted_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle($permitted_chars), 0, 3);
    }
}