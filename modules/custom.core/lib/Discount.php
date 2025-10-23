<?php

namespace Custom\Core;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\Validator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;

Loc::loadMessages(__FILE__);

class Discount
{
    const discountType = [
        50 => "PRC",
        51 => "RUB",
        46 => "PRC",
        47 => "RUB",
    ];

    const coupon = 52;
    const group = 53;
    const discount = 54;
    const typeApplyAll = 48;

    public static function getEventIdByProduct($offerId = null)
    {
        \Bitrix\Main\Loader::includeModule("catalog");

        $mainProductId = \CCatalogSku::GetProductInfo($offerId)["ID"];
        $eventId = \Custom\Core\Events::getEventIdByProduct($mainProductId);

        return $eventId;
    }

    public static function applyDiscountToBasket(&$basket = null, $bufferItems = null, $targetRule, $prefix = "", $promocodes = [])
    {
        $fullBufferSum = 0;

        foreach ($bufferItems as $bufferItem) {
            $fullBufferSum += $bufferItem->getField('PRICE');
        }

        foreach ($bufferItems as $bufferItem) {
            $basketItem = $basket->getItemById($bufferItem->getId());

            $oldPrice = $basketItem->getField('BASE_PRICE');
            if($basketItem->getField('DISCOUNT_VALUE'))
                $oldPrice = $basketItem->getField('PRICE');

            if(self::discountType[$targetRule[$prefix."UF_DISCOUNT_TYPE"]] == "PRC")
            {
                $newPrice = $oldPrice - round(($oldPrice/100 * $targetRule[$prefix."UF_DISCOUNT"]), 2);

                if($newPrice < 0)
                    $newPrice = 0;

                $discountPrice = $oldPrice - $newPrice;

                if($promocodes)
                    $basketItem->setFields([
                        'CUSTOM_PRICE' => "Y",
                        'PRICE' => $newPrice,
                        'DISCOUNT_PRICE' => $discountPrice,
                        'DISCOUNT_COUPON' => json_encode($promocodes),
                    ]);
                else
                    $basketItem->setFields([
                        'CUSTOM_PRICE' => "Y",
                        'PRICE' => $newPrice,
                        'DISCOUNT_PRICE' => $discountPrice,
                        'DISCOUNT_VALUE' => $targetRule["ID"],
                    ]);
            }
            else
            {
                $proportion = $targetRule[$prefix."UF_DISCOUNT"]/$fullBufferSum*100;

                $newPrice = $oldPrice - round(($oldPrice/100 * $proportion), 2);

                if($newPrice < 0)
                    $newPrice = 0;

                $discountPrice = $oldPrice - $newPrice;

                if($promocodes)
                    $basketItem->setFields([
                        'CUSTOM_PRICE' => "Y",
                        'PRICE' => $newPrice,
                        'DISCOUNT_PRICE' => $discountPrice,
                        'DISCOUNT_COUPON' => json_encode($promocodes),
                    ]);
                else
                    $basketItem->setFields([
                        'CUSTOM_PRICE' => "Y",
                        'PRICE' => $newPrice,
                        'DISCOUNT_PRICE' => $discountPrice,
                        'DISCOUNT_VALUE' => $targetRule["ID"],
                    ]);
            }
        }
    }

    public static function getOptimalPriceInBasket($basket = null)
    {
        $basketItems = $basket->getBasketItems();
        if(!$basketItems)
            return false;

        self::deleteAllRuleDiscount($basket);

        $fullBasketPrice = $basket->getBasePrice();

        $eventId = false;

        $groupBasketItems = [];

        foreach ($basketItems as $item)
        {
            $offerId = $item->getField('PRODUCT_ID');
            $groupBasketItems[$offerId][] = $item;

            if(!$eventId)
            {
                $eventId = self::getEventIdByProduct($offerId);
            }
        }

        $arRules = [];

        $priceRulesEntity = new ORM\Query\Query('Custom\Core\Events\PriceRulesTable');
        $query = $priceRulesEntity
            ->setSelect([
                '*',
            ])
            ->setFilter([
                'REF_EVENTS.ID' => $eventId,
                'UF_TYPE' => self::discount,
                'UF_IS_ACTIVITY' => true,
                //'<=UF_MIN_COUNT_TICKETS' => $quantity,
                //'<=UF_TYPE_APPLY_MIN' => $quantity,
                [
                    "LOGIC" => "OR",
                    '<=UF_DATE_START' => new \Bitrix\Main\Type\DateTime(),
                    '=UF_DATE_START' => false,
                ],
                [
                    "LOGIC" => "OR",
                    '>=UF_DATE_END' => new \Bitrix\Main\Type\DateTime(),
                    '=UF_DATE_END' => false,
                ]
            ])
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'PROMOCODES',
                    'Custom\Core\Events\PromoCodesTable',
                    ['=this.ID' => 'ref.UF_RULE_ID'],
                    ['join_type' => 'left']
                )
            )
            ->setGroup('ID')
            ->setOrder([
                'UF_MIN_COUNT_TICKETS' => 'DESC',
                'UF_DISCOUNT_TYPE' => 'ASC',
                'UF_DISCOUNT' => 'DESC',
            ])
            ->countTotal(true)
            ->exec();

        while ($priceRule = $query->fetch()) {
            if(!$priceRule["UF_MAX_NUMBER_OF_USES"] || $priceRule["UF_MAX_NUMBER_OF_USES"] > $priceRule["UF_NUMBER_OF_USES"])
            {
                if(!$priceRule["UF_MIN_COUNT_TICKETS"])
                    $priceRule["UF_MIN_COUNT_TICKETS"] = 0;

                $arRules[$priceRule["ID"]] = $priceRule;
            }
        }

        $arCurrentRules = [];

        $typesUsedInRules = [];

        foreach ($arRules as $key => $targetRule)
        {
            if($targetRule["UF_FOR_ALL_TYPES"])
            {
                $sumSelectForRule = 0;
                $selectForRule = 0;
                $sumDiscount = 0;

                foreach ($groupBasketItems as $key => $items)
                {
                    if(!in_array($key, $typesUsedInRules))
                    {
                        $sumSelectForRule += array_sum(array_map(function ($item) {

                            return $item->getField("PRICE");
                        }, $items));

                        $selectForRule += count($items);
                    }
                }

                if($selectForRule >= $targetRule["UF_MIN_COUNT_TICKETS"])
                {
                    $typesUsedInRules = array_merge($typesUsedInRules, $targetRule["UF_TICKETS_TYPE"]);

                    foreach ($basketItems as &$bufferItem) {
                        if($bufferItem->getField("CUSTOM_PRICE") == "Y")
                            continue;

                        $basePrice = $bufferItem->getField('BASE_PRICE');

                        if(self::discountType[$targetRule["UF_DISCOUNT_TYPE"]] == "PRC")
                            $price = round($basePrice - $basePrice/100 * $targetRule["UF_DISCOUNT"], 2);
                        else
                        {
                            if($targetRule["UF_FOR_EACH_TICKET"])
                            {
                                $price = round($basePrice - $targetRule["UF_DISCOUNT"], 2);
                            }
                            else
                            {
                                $selectForRule--;

                                if($sumSelectForRule) {
                                    $proportion = $targetRule["UF_DISCOUNT"] / $sumSelectForRule * 100;

                                    if ($selectForRule) {
                                        $price = round($basePrice - $basePrice / 100 * $proportion, 2);
                                        $sumDiscount += $basePrice - $price;
                                    } else {
                                        $price = round($basePrice - ($targetRule["UF_DISCOUNT"] - $sumDiscount), 2);
                                    }
                                }
                            }

                        }

                        if($price < 0)
                            $price = 0;

                        $discountPrice = $basePrice - $price;

                        $bufferItem->setFields([
                            'CUSTOM_PRICE' => "Y",
                            'PRICE' => $price,
                            'DISCOUNT_PRICE' => $discountPrice,
                        ]);

                        self::addRuleInTAble([
                            'UF_BASKET_ITEM_ID' => $bufferItem->getId(),
                            'UF_RULE_ID' => $targetRule["ID"],
                            'UF_RULE_TYPE' => 'RULE',
                            'UF_DISCOUNT_VALUE' => $discountPrice,
                        ]);
                    }
                }
            }
            else
            {
                $sumSelectForRule = 0;
                $selectForRule = 0;
                $sumDiscount = 0;

                foreach ($groupBasketItems as $key => $items)
                {
                    if(!in_array($key, $typesUsedInRules) && in_array($key, $targetRule["UF_TICKETS_TYPE"]))
                    {
                        $sumSelectForRule += array_sum(array_map(function ($item) {

                            return $item->getField("PRICE");
                        }, $items));

                        $selectForRule += count($items);
                    }
                }

                if($selectForRule >= $targetRule["UF_MIN_COUNT_TICKETS"])
                {
                    $typesUsedInRules = array_merge($typesUsedInRules, $targetRule["UF_TICKETS_TYPE"]);

                    foreach ($basketItems as $key => &$bufferItem) {
                        if($bufferItem->getField("CUSTOM_PRICE") == "Y" || !in_array($bufferItem->getField("PRODUCT_ID"), $targetRule["UF_TICKETS_TYPE"]))
                            continue;

                        $basePrice = $bufferItem->getField('BASE_PRICE');

                        if(self::discountType[$targetRule["UF_DISCOUNT_TYPE"]] == "PRC")
                            $price = round($basePrice - $basePrice/100 * $targetRule["UF_DISCOUNT"], 2);
                        else
                        {
                            if($targetRule["UF_FOR_EACH_TICKET"])
                            {
                                $price = round($basePrice - $targetRule["UF_DISCOUNT"], 2);
                            }
                            else
                            {
                                $selectForRule--;

                                $proportion = $targetRule["UF_DISCOUNT"] / $sumSelectForRule * 100;

                                if ($selectForRule) {
                                    $price = round($basePrice - $basePrice / 100 * $proportion, 2);
                                    $sumDiscount += $basePrice - $price;
                                } else {
                                    $price = round($basePrice - ($targetRule["UF_DISCOUNT"] - $sumDiscount), 2);
                                }
                            }
                        }

                        if($price < 0)
                            $price = 0;

                        $discountPrice = $basePrice - $price;

                        $bufferItem->setFields([
                            'CUSTOM_PRICE' => "Y",
                            'PRICE' => $price,
                            'DISCOUNT_PRICE' => $discountPrice,
                        ]);

                        self::addRuleInTAble([
                            'UF_BASKET_ITEM_ID' => $bufferItem->getId(),
                            'UF_RULE_ID' => $targetRule["ID"],
                            'UF_RULE_TYPE' => 'RULE',
                            'UF_DISCOUNT_VALUE' => $discountPrice,
                        ]);
                    }
                }


            }
        }

    }

    public static function getOptimalPriceInCatalog(&$offers = null, $eventId = null)
    {
        $selectQuantity = 0;

        foreach ($offers as &$offer) {
            $selectQuantity += $offer["SELECT_QUANTITY"];

            $offer["PRICE"] = $offer["ITEM_PRICES"][0]["PRICE"];
            $offer["SUMM"] = $offer["PRICE"] * $offer["SELECT_QUANTITY"];
            $offer["CUSTOM_PRICE"] = "N";
        }
        unset($offer);

        $arRules = [];

        $priceRulesEntity = new ORM\Query\Query('Custom\Core\Events\PriceRulesTable');
        $query = $priceRulesEntity
            ->setSelect([
                '*',
            ])
            ->setFilter([
                'REF_EVENTS.ID' => $eventId,
                'UF_TYPE' => self::discount,
                'UF_IS_ACTIVITY' => true,
                //'<=UF_MIN_COUNT_TICKETS' => $quantity,
                //'<=UF_TYPE_APPLY_MIN' => $quantity,
                [
                    "LOGIC" => "OR",
                    '<=UF_DATE_START' => new \Bitrix\Main\Type\DateTime(),
                    '=UF_DATE_START' => false,
                ],
                [
                    "LOGIC" => "OR",
                    '>=UF_DATE_END' => new \Bitrix\Main\Type\DateTime(),
                    '=UF_DATE_END' => false,
                ]
            ])
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'PROMOCODES',
                    'Custom\Core\Events\PromoCodesTable',
                    ['=this.ID' => 'ref.UF_RULE_ID'],
                    ['join_type' => 'left']
                )
            )
            ->setOrder([
                'UF_MIN_COUNT_TICKETS' => 'DESC',
                'UF_DISCOUNT_TYPE' => 'ASC',
                'UF_DISCOUNT' => 'DESC',
            ])
            ->setGroup('ID')
            ->countTotal(true)
            ->exec();

        while ($priceRule = $query->fetch()) {
            if(!$priceRule["UF_MAX_NUMBER_OF_USES"] || $priceRule["UF_MAX_NUMBER_OF_USES"] > $priceRule["UF_NUMBER_OF_USES"])
            {
                if(!$priceRule["UF_MIN_COUNT_TICKETS"])
                    $priceRule["UF_MIN_COUNT_TICKETS"] = 0;

                $arRules[$priceRule["ID"]] = $priceRule;
            }
        }


        $arCurrentRules = [];

        $typesUsedInRules = [];

        foreach ($arRules as $key => $targetRule)
        {
            if($targetRule["UF_FOR_ALL_TYPES"])
            {
                $sumSelectForRule = 0;
                $selectForRule = 0;
                $sumDiscount = 0;

                foreach ($offers as $key => $item)
                {
                    if(!in_array($item["ID"], $typesUsedInRules) && $item["SELECT_QUANTITY"])
                    {
                        $selectForRule += $item["SELECT_QUANTITY"];
                        $sumSelectForRule += $item["PRICE"] * $item["SELECT_QUANTITY"];
                    }
                }

                if($selectForRule >= $targetRule["UF_MIN_COUNT_TICKETS"])
                {
                    $typesUsedInRules = array_merge($typesUsedInRules, $targetRule["UF_TICKETS_TYPE"]);

                    foreach ($offers as &$bufferItem) {
                        if($bufferItem["CUSTOM_PRICE"] == "Y" || !$bufferItem["SELECT_QUANTITY"])
                            continue;

                        $defQuantity = 1;

                        if(
                            $targetRule["UF_FOR_ALL_TYPES"] && $selectQuantity >= $targetRule["UF_MIN_COUNT_TICKETS"]
                            || !$targetRule["UF_FOR_ALL_TYPES"] && in_array($bufferItem["ID"], $targetRule["UF_TICKETS_TYPE"]) && $selectForRule >= $targetRule["UF_MIN_COUNT_TICKETS"]
                        )
                        {
                            if($bufferItem["SELECT_QUANTITY"])
                            {
                                $defQuantity = $bufferItem["SELECT_QUANTITY"];
                            }

                            if(self::discountType[$targetRule["UF_DISCOUNT_TYPE"]] == "PRC")
                            {
                                $bufferItem["PRICE"] = $bufferItem["ITEM_PRICES"][0]["PRICE"] - $bufferItem["ITEM_PRICES"][0]["PRICE"]/100 * $targetRule["UF_DISCOUNT"];
                                $bufferItem["SUMM"] = $bufferItem["PRICE"] * $defQuantity;
                            }
                            elseif($sumSelectForRule)
                            {
                                if($targetRule["UF_FOR_EACH_TICKET"])
                                {
                                    $bufferItem["PRICE"] = round($bufferItem["ITEM_PRICES"][0]["PRICE"] - $targetRule["UF_DISCOUNT"], 2);

                                    $bufferItem["SUMM"] = $bufferItem["PRICE"] * $defQuantity;
                                }
                                else
                                {
                                    $selectForRule -= $defQuantity;

                                    $proportion = $targetRule["UF_DISCOUNT"]/$sumSelectForRule*100;

                                    $bufferItem["PRICE"] = round($bufferItem["ITEM_PRICES"][0]["PRICE"] - $bufferItem["ITEM_PRICES"][0]["PRICE"]/100 * $proportion, 2);

                                    if($selectForRule)
                                    {
                                        $bufferItem["SUMM"] = $bufferItem["PRICE"] * $defQuantity;

                                        $sumDiscount += $bufferItem["ITEM_PRICES"][0]["PRICE"]*$defQuantity - $bufferItem["SUMM"];
                                    }
                                    else
                                    {
                                        $bufferItem["SUMM"] = $bufferItem["ITEM_PRICES"][0]["PRICE"]*$defQuantity - ($targetRule["UF_DISCOUNT"] - $sumDiscount);
                                    }
                                }
                            }

                            $bufferItem["RULE"] = $targetRule;

                            $bufferItem["CUSTOM_PRICE"] = "Y";
                        }
                    }
                }
            }
            else
            {
                $selectForRule = 0;
                $sumSelectForRule = 0;
                foreach ($offers as $key => $item)
                {
                    if(
                        !$targetRule["UF_FOR_ALL_TYPES"]
                        && in_array($item["ID"], $targetRule["UF_TICKETS_TYPE"])
                        && !in_array($item["ID"], $typesUsedInRules)
                        && $item["SELECT_QUANTITY"]
                    )
                    {
                        $selectForRule += $item["SELECT_QUANTITY"];
                        $sumSelectForRule += $item["PRICE"] * $item["SELECT_QUANTITY"];
                    }
                }

                if($selectForRule >= $targetRule["UF_MIN_COUNT_TICKETS"])
                {
                    $typesUsedInRules = array_merge($typesUsedInRules, $targetRule["UF_TICKETS_TYPE"]);

                    foreach ($offers as &$bufferItem) {
                        if($bufferItem["CUSTOM_PRICE"] == "Y" || !$bufferItem["SELECT_QUANTITY"])
                            continue;

                        $defQuantity = 1;

                        if(
                            $targetRule["UF_FOR_ALL_TYPES"] && $selectQuantity >= $targetRule["UF_MIN_COUNT_TICKETS"]
                            || !$targetRule["UF_FOR_ALL_TYPES"] && in_array($bufferItem["ID"], $targetRule["UF_TICKETS_TYPE"]) /*&& $selectForRule >= $targetRule["UF_MIN_COUNT_TICKETS"]*/
                        )
                        {
                            if($bufferItem["SELECT_QUANTITY"])
                            {
                                $defQuantity = $bufferItem["SELECT_QUANTITY"];
                            }

                            if(self::discountType[$targetRule["UF_DISCOUNT_TYPE"]] == "PRC")
                            {
                                $bufferItem["PRICE"] = $bufferItem["ITEM_PRICES"][0]["PRICE"] - $bufferItem["ITEM_PRICES"][0]["PRICE"]/100 * $targetRule["UF_DISCOUNT"];
                                $bufferItem["SUMM"] = $bufferItem["PRICE"] * $defQuantity;
                            }
                            elseif($sumSelectForRule)
                            {
                                if($targetRule["UF_FOR_EACH_TICKET"])
                                {
                                    $bufferItem["PRICE"] = round($bufferItem["ITEM_PRICES"][0]["PRICE"] - $targetRule["UF_DISCOUNT"], 2);

                                    $bufferItem["SUMM"] = $bufferItem["PRICE"] * $defQuantity;
                                }
                                else
                                {
                                    $selectForRule -= $defQuantity;

                                    $proportion = $targetRule["UF_DISCOUNT"]/$sumSelectForRule*100;

                                    $bufferItem["PRICE"] = round($bufferItem["ITEM_PRICES"][0]["PRICE"] - $bufferItem["ITEM_PRICES"][0]["PRICE"]/100 * $proportion, 2);

                                    if($selectForRule)
                                    {
                                        $bufferItem["SUMM"] = $bufferItem["PRICE"] * $defQuantity;

                                        $sumDiscount += $bufferItem["ITEM_PRICES"][0]["PRICE"]*$defQuantity - $bufferItem["SUMM"];
                                    }
                                    else
                                    {
                                        $bufferItem["SUMM"] = $bufferItem["ITEM_PRICES"][0]["PRICE"]*$defQuantity - ($targetRule["UF_DISCOUNT"] - $sumDiscount);
                                    }
                                }
                            }

                            $bufferItem["RULE"] = $targetRule;

                            $bufferItem["CUSTOM_PRICE"] = "Y";
                        }
                    }
                }


            }
        }

        foreach ($offers as &$bufferItem)
        {
            if($bufferItem["PRICE"] < 0)
            {
                $bufferItem["PRICE"] = 0;
                $bufferItem["SUMM"] = 0;
            }
        }
    }

    public static function getProductDiscount($offerId = null, $quantity = 1, $eventId = null)
    {
        if(!$eventId)
        {
            $eventId = self::getEventIdByProduct($offerId);
        }

        $arRules = [];

        $priceRulesEntity = new ORM\Query\Query('Custom\Core\Events\PriceRulesTable');
        $query = $priceRulesEntity
            ->setSelect([
                '*',
            ])
            ->setFilter([
                'REF_EVENTS.ID' => $eventId,
                'UF_TYPE' => 54,
                '<=UF_MIN_COUNT_TICKETS' => $quantity,
                [
                    "LOGIC" => "OR",
                    [
                        '<=UF_DATE_START' => new \Bitrix\Main\Type\DateTime(),
                    ],
                    [
                        '=UF_DATE_START' => false,
                    ]
                ],
                [
                    "LOGIC" => "OR",
                    [
                        '>=UF_DATE_END' => new \Bitrix\Main\Type\DateTime(),
                    ],
                    [
                        '=UF_DATE_END' => false,
                    ]
                ]
            ])
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'PROMOCODES',
                    'Custom\Core\Events\PromoCodesTable',
                    ['=this.ID' => 'ref.UF_RULE_ID'],
                    ['join_type' => 'left']
                )
            )
            ->setGroup('ID')
            ->countTotal(true)
            ->exec();

        while ($priceRule = $query->fetch()) {
            $arRules[] = $priceRule;
        }

        $discount = [
            "PRC" => 0,
            "RUB" => 0,
        ];

        foreach ($arRules as $rule)
        {
            if($rule["UF_FOR_ALL_TYPES"] && $rule["UF_MIN_COUNT_TICKETS"] <= $quantity)
            {
                $discount[self::discountType[$rule["UF_DISCOUNT_TYPE"]]] += $rule["UF_DISCOUNT"];
            }
            else
            {
                if(is_array($rule["UF_TICKETS_TYPE"]) && in_array($offerId, $rule["UF_TICKETS_TYPE"]) && $rule["UF_MIN_COUNT_TICKETS"] <= $quantity)
                {
                    $discount[self::discountType[$rule["UF_DISCOUNT_TYPE"]]] += $rule["UF_DISCOUNT"];
                }
            }
        }


    }

    public static function getRulesForPromocode($promocode = null, $eventId = null)
    {
        $arRules = [];

        $priceRulesEntity = new ORM\Query\Query('Custom\Core\Events\PromoCodesTable');
        $query = $priceRulesEntity
            ->setSelect([
                '*',
                'RULE_' => 'RULE',
                'REF_EVENTS_' => 'REF_EVENTS'
            ])
            ->setFilter([
                '=UF_CODE' => $promocode,
                'RULE.UF_IS_ACTIVITY' => true,
                '=REF_EVENTS.VALUE' => $eventId,
                [
                    "LOGIC" => "OR",
                    [
                        '<=RULE.UF_DATE_START' => new \Bitrix\Main\Type\DateTime(),
                    ],
                    [
                        '=RULE.UF_DATE_START' => false,
                    ]
                ],
                [
                    "LOGIC" => "OR",
                    [
                        '>=RULE.UF_DATE_END' => new \Bitrix\Main\Type\DateTime(),
                    ],
                    [
                        '=RULE.UF_DATE_END' => false,
                    ]
                ]
            ])
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'RULE',
                    'Custom\Core\Events\PriceRulesTable',
                    ['=this.UF_RULE_ID' => 'ref.ID'],
                    ['join_type' => 'left']
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'REF_EVENTS',
                    '\Custom\Core\Events\PriceRulesUfEventIdTable',
                    ['this.RULE_ID' => 'ref.ID'],
                    ['join_type' => 'LEFT']
                )
            )
            ->setGroup('ID')
            ->countTotal(true)
            ->exec();

        while ($priceRule = $query->fetch()) {
            if($priceRule["RULE_UF_MAX_NUMBER_OF_USES"] && $priceRule["RULE_UF_MAX_NUMBER_OF_USES"] <= $priceRule["RULE_UF_NUMBER_OF_USES"])
                continue;

            $arRules[$priceRule["RULE_ID"]] = $priceRule;
        }

        return $arRules;
    }

    public static function getApplyRuleIds($basketItems)
    {
        $arRulesId = [];
        $arPromocodeId = [];

        $basketItemsIds = array_map(function ($item) {
            return $item->getField("ID");
        }, $basketItems);

        if($basketItemsIds)
        {
            $applyRulesEntity = new ORM\Query\Query('Custom\Core\Events\DiscountApplyTable');
            $query = $applyRulesEntity
                ->setSelect([
                    'ID',
                    'UF_RULE_TYPE',
                    'UF_RULE_ID',
                    'UF_BASKET_ITEM_ID',
                    'UF_PROMOCODE_ID',
                ])
                ->setFilter([
                    'UF_BASKET_ITEM_ID' => $basketItemsIds
                ])
                ->exec();

            while ($rule = $query->fetch()) {
                if($rule["UF_RULE_TYPE"] == "RULE")
                {
                    $arRulesId[$rule["UF_RULE_ID"]][] = $rule["UF_BASKET_ITEM_ID"];
                }
                if($rule["UF_RULE_TYPE"] == "PROMOCODE")
                {
                    $arPromocodeId[$rule["UF_PROMOCODE_ID"]][] = $rule["UF_BASKET_ITEM_ID"];
                }
            }
        }

        return [$arRulesId, $arPromocodeId];
    }

    public static function validatePromocodeDiscount($promocodes = null, &$basket = null)
    {
        $promocodes = array_unique($promocodes);
        $promocodes = array_map(function ($item) {
            return mb_strtoupper(trim($item));
        }, $promocodes);

        $last = $promocodes[array_key_last($promocodes)];

        $basketItems = $basket->getBasketItems();
        $fullBasketPrice = $basket->getBasePrice();

        $eventId = false;

        $groupBasketItems = [];

        $basketItemsIds = [];

        foreach ($basketItems as $item)
        {
            $offerId = $item->getField('PRODUCT_ID');
            $groupBasketItems[$offerId][] = $item;
            $basketItemsIds[] = $item->getField("ID");

            if(!$eventId)
            {
                $eventId = self::getEventIdByProduct($offerId);
            }
        }

        $lastRule = [];
        if($last)
        {
            $priceRulesEntity = new ORM\Query\Query('Custom\Core\Events\PromoCodesTable');
            $query = $priceRulesEntity
                ->setSelect([
                    '*',
                    'RULE_' => 'RULE',
                    'REF_EVENTS_' => 'REF_EVENTS'
                ])
                ->setFilter([
                    'RULE.UF_IS_ACTIVITY' => true,
                    '=REF_EVENTS.VALUE' => $eventId,
                    '=UF_CODE' => $last,
                    [
                        "LOGIC" => "OR",
                        [
                            '<=RULE.UF_DATE_START' => new \Bitrix\Main\Type\DateTime(),
                        ],
                        [
                            '=RULE.UF_DATE_START' => false,
                        ]
                    ],
                    [
                        "LOGIC" => "OR",
                        [
                            '>=RULE.UF_DATE_END' => new \Bitrix\Main\Type\DateTime(),
                        ],
                        [
                            '=RULE.UF_DATE_END' => false,
                        ]
                    ],
                ])
                ->registerRuntimeField(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'RULE',
                        'Custom\Core\Events\PriceRulesTable',
                        ['=this.UF_RULE_ID' => 'ref.ID'],
                        ['join_type' => 'left']
                    )
                )
                ->registerRuntimeField(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'REF_EVENTS',
                        '\Custom\Core\Events\PriceRulesUfEventIdTable',
                        ['this.RULE_ID' => 'ref.ID'],
                        ['join_type' => 'LEFT']
                    )
                )
                ->setOrder([
                    'RULE.UF_DISCOUNT_TYPE' => 'ASC'
                ])
                ->countTotal(true)
                ->exec();
            if ($query->getCount() == 0) {
                throw new \Exception("Использование данного промокода невозможно!");
            }
            else
            {
                $lastRule = $query->fetch();
            }
        }


        [$applyRuleIds, $applyPromocodeIds1] = self::getApplyRuleIds($basketItems);

        $arRules = [];
        $validate = false;
        $errorMessage = [];

        if($applyRuleIds)
        {
            $priceRulesEntity = new ORM\Query\Query('Custom\Core\Events\PriceRulesTable');
            $query = $priceRulesEntity
                ->setSelect([
                    '*',
                ])
                ->setFilter([
                    'ID' => array_keys($applyRuleIds),
                    'UF_IS_SUM' => 0,
                ])
                ->setGroup('ID')
                ->countTotal(true)
                ->exec();

            $bufIds = [];
            while ($priceRule = $query->fetch()) {
                $bufIds = array_merge($bufIds, $applyRuleIds[$priceRule["ID"]]);
            }
            foreach ($basketItemsIds as $key => $value) {
                if($bufIds && in_array($value, $bufIds))
                    unset($basketItemsIds[$key]);
            }
        }

        if($applyPromocodeIds1 && count($applyPromocodeIds1) <= count($promocodes))
        {
            $priceRulesEntity = new ORM\Query\Query('Custom\Core\Events\PromoCodesTable');
            $query = $priceRulesEntity
                ->setSelect([
                    '*',
                    'RULE_' => 'RULE',
                    'REF_EVENTS_' => 'REF_EVENTS'
                ])
                ->setFilter([
                    '=RULE.UF_IS_ACTIVITY' => true,
                    '=UF_CODE' => $promocodes,
                    '=REF_EVENTS.VALUE' => $eventId,
                ])
                ->registerRuntimeField(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'RULE',
                        'Custom\Core\Events\PriceRulesTable',
                        ['=this.UF_RULE_ID' => 'ref.ID'],
                        ['join_type' => 'left']
                    )
                )
                ->registerRuntimeField(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'REF_EVENTS',
                        '\Custom\Core\Events\PriceRulesUfEventIdTable',
                        ['this.RULE_ID' => 'ref.ID'],
                        ['join_type' => 'LEFT']
                    )
                )
                ->setOrder([
                    'RULE.UF_DISCOUNT_TYPE' => 'ASC'
                ])
                ->countTotal(true)
                ->exec();

            $bufIds = [];

            $discount = [
                "PRC" => [],
                "RUB" => [],
            ];

            $arRules = [];
            $arRulesSort = [];

            while ($priceRule = $query->fetch()) {
                $arRules[$priceRule["ID"]] = $priceRule;
            }

            foreach ($promocodes as $promocode)
            {
                foreach ($arRules as $r)
                {
                    if($promocode == $r["UF_CODE"])
                        $arRulesSort[] = $r;
                }
            }

            $bufferBasketItemsIds = $basketItemsIds;

            foreach ($arRulesSort as $rule)
            {
                if(!$rule["RULE_UF_IS_SUM"])
                {
                    foreach ($applyPromocodeIds1[$rule["ID"]] as $value) {
                        $key = array_search($value, $bufferBasketItemsIds);
                        if($key !== false)
                            unset($bufferBasketItemsIds[$key]);
                    }
                }
            }

            if($lastRule && count($promocodes) > 1 && !$lastRule["RULE_UF_IS_SUM"])
            {
                foreach ($bufferBasketItemsIds as $basketItemsIdKey => $basketItemsId)
                {
                    foreach ($applyPromocodeIds1 as $applyPromocodeTickets)
                    {
                        if(in_array($basketItemsId, $applyPromocodeTickets))
                            unset($bufferBasketItemsIds[$basketItemsIdKey]);
                    }
                }
            }

            if(!$bufferBasketItemsIds)
            {
                throw new \Exception("Использование данного промокода невозможно!");
            }
        }

        if(!$basketItemsIds)
        {
            throw new \Exception("Использование данного промокода невозможно!");
        }

        $priceRulesEntity = new ORM\Query\Query('Custom\Core\Events\PromoCodesTable');
        $query = $priceRulesEntity
            ->setSelect([
                '*',
                'RULE_' => 'RULE',
                'REF_EVENTS_' => 'REF_EVENTS'
            ])
            ->setFilter([
                'RULE.UF_IS_ACTIVITY' => true,
                '=REF_EVENTS.VALUE' => $eventId,
                '=UF_CODE' => $promocodes,
                [
                    "LOGIC" => "OR",
                    [
                        '<=RULE.UF_DATE_START' => new \Bitrix\Main\Type\DateTime(),
                    ],
                    [
                        '=RULE.UF_DATE_START' => false,
                    ]
                ],
                [
                    "LOGIC" => "OR",
                    [
                        '>=RULE.UF_DATE_END' => new \Bitrix\Main\Type\DateTime(),
                    ],
                    [
                        '=RULE.UF_DATE_END' => false,
                    ]
                ],
            ])
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'RULE',
                    'Custom\Core\Events\PriceRulesTable',
                    ['=this.UF_RULE_ID' => 'ref.ID'],
                    ['join_type' => 'left']
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'REF_EVENTS',
                    '\Custom\Core\Events\PriceRulesUfEventIdTable',
                    ['this.RULE_ID' => 'ref.ID'],
                    ['join_type' => 'LEFT']
                )
            )
            ->setOrder([
                'RULE.UF_DISCOUNT_TYPE' => 'ASC'
            ])
            ->countTotal(true)
            ->exec();

        $discount = [
            "PRC" => [],
            "RUB" => [],
        ];

        while ($priceRule = $query->fetch()) {
            $arRules[$priceRule["ID"]] = $priceRule;
        }

        foreach ($promocodes as $key => $promocode)
        {
            foreach ($arRules as $r)
            {
                if($promocode == $r["UF_CODE"])
                    $discount[self::discountType[$r["RULE_UF_DISCOUNT_TYPE"]]][] = $r["UF_CODE"];
            }
        }

        $promocodesSort = array_merge($discount["PRC"], $discount["RUB"]);

        if($arRules)
        {
            $arDiscountRules = [];

            foreach ($promocodes as $promocode)
            {
                foreach ($arRules as $r)
                {
                    if($promocode == $r["UF_CODE"])
                        $rule = $r;
                }

                if(!$rule)
                    continue;

                if($applyRuleIds && in_array($rule["RULE_ID"], array_keys($applyRuleIds)))
                {
                    $errorMessage[] = "Данная скидка «{$rule["RULE_UF_NAME"]}» уже применена!";
                    continue;
                }

                if($applyPromocodeIds && in_array($rule["RULE_ID"], array_keys($applyPromocodeIds)))
                {
                    $errorMessage[] = "Данная скидка «{$rule["RULE_UF_NAME"]}» уже применена!";
                    continue;
                }

                if($rule["RULE_UF_MAX_NUMBER_OF_USES"] && $rule["RULE_UF_MAX_NUMBER_OF_USES"] <= $rule["RULE_UF_NUMBER_OF_USES"])
                {
                    $errorMessage[] = "Использование промокода «{$promocode}» невозможно!";
                    continue;
                }

                if($rule["RULE_UF_TYPE"] == self::group && $rule["UF_IS_USE"])
                {
                    $errorMessage[] = "Использование промокода «{$promocode}» невозможно!";
                    continue;
                }

                if(!$rule["RULE_UF_FOR_ALL_TYPES"])
                {
                    $bufferBasketItemsIds = $basketItemsIds;

                    foreach ($bufferBasketItemsIds as $key => $id)
                    {
                        $basketItem = $basket->getItemById($id);
                        if(!in_array($basketItem->getField('PRODUCT_ID'), $rule["RULE_UF_TICKETS_TYPE"]))
                        {
                            unset($bufferBasketItemsIds[$key]);
                        }
                    }

                    if(!$rule["RULE_UF_IS_SUM"])
                    {
                        foreach ($bufferBasketItemsIds as $bufferBasketItemsIdKey => $bufferBasketItemsId)
                        {
                            foreach ($applyPromocodeIds as $applyPromocodeTickets)
                            {
                                if(in_array($bufferBasketItemsId, $applyPromocodeTickets))
                                    unset($bufferBasketItemsIds[$bufferBasketItemsIdKey]);
                            }
                        }
                    }

                    if($bufferBasketItemsIds && count($bufferBasketItemsIds) >= $rule["RULE_UF_TYPE_APPLY_MIN"] && $rule["RULE_UF_MIN_COUNT_TICKETS"] <= count($bufferBasketItemsIds))
                    {
                        if(!$rule["RULE_UF_TYPE_APPLY_ALL_ORDER"] && $rule["RULE_UF_TYPE_APPLY_MAX"] && $rule["RULE_UF_TYPE_APPLY_MAX"] < count($bufferBasketItemsIds))
                            $bufferBasketItemsIds = array_slice($bufferBasketItemsIds, 0, $rule["RULE_UF_TYPE_APPLY_MAX"]);

                        if(self::discountType[$rule["RULE_UF_DISCOUNT_TYPE"]] == "PRC")
                        {
                            $arDiscountRules["PRC"][] = [
                                "DISCOUNT" => $rule["RULE_UF_DISCOUNT"],
                                "ITEMS" => $bufferBasketItemsIds,
                                "PROMOCODE_ID" => $rule["ID"],
                                "RULE_ID" => $rule["RULE_ID"],
                                "FOR_EACH_TICKET" => $rule["RULE_UF_FOR_EACH_TICKET"],
                                "PROMOCODE" => $promocode
                            ];
                            foreach ($bufferBasketItemsIds as $id)
                            {
                                if(!$rule["RULE_UF_IS_SUM"])
                                {
                                    $key = array_search($id, $basketItemsIds);
                                    unset($basketItemsIds[$key]);
                                }

                                $applyPromocodeIds[$rule["RULE_ID"]][] = $id;
                            }
                        }
                        else
                        {
                            $arDiscountRules["RUB"][] = [
                                "DISCOUNT" => $rule["RULE_UF_DISCOUNT"],
                                "ITEMS" => $bufferBasketItemsIds,
                                "PROMOCODE_ID" => $rule["ID"],
                                "RULE_ID" => $rule["RULE_ID"],
                                "FOR_EACH_TICKET" => $rule["RULE_UF_FOR_EACH_TICKET"],
                                "PROMOCODE" => $promocode
                            ];

                            foreach ($bufferBasketItemsIds as $id)
                            {
                                if(!$rule["RULE_UF_IS_SUM"])
                                {
                                    $key = array_search($id, $basketItemsIds);
                                    unset($basketItemsIds[$key]);
                                }

                                $applyPromocodeIds[$rule["RULE_ID"]][] = $id;
                            }
                        }
                    }
                    else
                    {
                        $errorMessage[] = "Использование промокода «{$promocode}» невозможно!";
                    }

                }
                else
                {
                    $bufferBasketItemsIds = $basketItemsIds;

                    if(!$rule["RULE_UF_IS_SUM"])
                    {
                        foreach ($bufferBasketItemsIds as $bufferBasketItemsIdKey => $bufferBasketItemsId)
                        {
                            foreach ($applyPromocodeIds as $applyPromocodeTickets)
                            {
                                if(in_array($bufferBasketItemsId, $applyPromocodeTickets))
                                    unset($bufferBasketItemsIds[$bufferBasketItemsIdKey]);
                            }
                        }
                    }

                    if(!$bufferBasketItemsIds || $rule["RULE_UF_MIN_COUNT_TICKETS"] > count($bufferBasketItemsIds) || $rule["RULE_UF_TYPE_APPLY_MIN"] > count($bufferBasketItemsIds))
                    {
                        $errorMessage[] = "Использование промокода «{$promocode}» невозможно!";
                    }
                    else
                    {
                        if(!$rule["RULE_UF_TYPE_APPLY_ALL_ORDER"] && $rule["RULE_UF_TYPE_APPLY_MAX"] && $rule["RULE_UF_TYPE_APPLY_MAX"] < count($bufferBasketItemsIds))
                            $bufferBasketItemsIds = array_slice($bufferBasketItemsIds, 0, $rule["RULE_UF_TYPE_APPLY_MAX"]);

                        if(self::discountType[$rule["RULE_UF_DISCOUNT_TYPE"]] == "PRC")
                        {
                            $arDiscountRules["PRC"][] = [
                                "DISCOUNT" => $rule["RULE_UF_DISCOUNT"],
                                "ITEMS" => $bufferBasketItemsIds,
                                "PROMOCODE_ID" => $rule["ID"],
                                "RULE_ID" => $rule["RULE_ID"],
                                "FOR_EACH_TICKET" => $rule["RULE_UF_FOR_EACH_TICKET"],
                                "PROMOCODE" => $promocode
                            ];

                            foreach ($bufferBasketItemsIds as $id)
                            {
                                if(!$rule["RULE_UF_IS_SUM"])
                                {
                                    $key = array_search($id, $basketItemsIds);
                                    unset($basketItemsIds[$key]);
                                }

                                $applyPromocodeIds[$rule["RULE_ID"]][] = $id;
                            }
                        }
                        else
                        {
                            $arDiscountRules["RUB"][] = [
                                "DISCOUNT" => $rule["RULE_UF_DISCOUNT"],
                                "ITEMS" => $bufferBasketItemsIds,
                                "PROMOCODE_ID" => $rule["ID"],
                                "RULE_ID" => $rule["RULE_ID"],
                                "FOR_EACH_TICKET" => $rule["RULE_UF_FOR_EACH_TICKET"],
                                "PROMOCODE" => $promocode
                            ];

                            foreach ($bufferBasketItemsIds as $id)
                            {
                                if(!$rule["RULE_UF_IS_SUM"])
                                {
                                    $key = array_search($id, $basketItemsIds);
                                    unset($basketItemsIds[$key]);
                                }

                                $applyPromocodeIds[$rule["RULE_ID"]][] = $id;
                            }
                        }
                    }
                }
            }

            if($arDiscountRules["PRC"] || $arDiscountRules["RUB"])
            {
                self::getOptimalPriceInBasket($basket);

                $arNewPrices = [];

                foreach ($basketItems as $item)
                {
                    $offerId = $item->getField('ID');
                    $price = $item->getField('PRICE');
                    $arNewPrices[$offerId]["PRICE"] = $price;
                }

                foreach ($arDiscountRules["PRC"] as $key => $rule)
                {
                    foreach ($rule["ITEMS"] as $id)
                    {
                        if($arNewPrices[$id])
                        {
                            $oldPrice = $arNewPrices[$id]["PRICE"];
                            $price = round($oldPrice - ($oldPrice/100 * $rule["DISCOUNT"]), 2);
                            if($price < 0)
                                $price = 0;

                            $discountPrice = $oldPrice - $price;

                            $arNewPrices[$id]["PRICE"] = $price;
                            $arNewPrices[$id]["RULES"][] = [
                                "DISCOUNT_VALUE" => $discountPrice,
                                "PROMOCODE_ID" => $rule["PROMOCODE_ID"],
                                "RULE_ID" => $rule["RULE_ID"],
                                "PROMOCODE" => $rule["PROMOCODE"],
                                "DISCOUNT" => $rule["DISCOUNT"],
                                "DISCOUNT_TYPE" => "prc",
                            ];
                        }
                    }
                }

                foreach ($arDiscountRules["RUB"] as $key => $rule)
                {
                    $summ = array_sum(array_map(function ($id) use($arNewPrices){
                        return $arNewPrices[$id]["PRICE"];
                    }, $rule["ITEMS"]));

                    if($summ)
                    {
                        $selectForRule = count($rule["ITEMS"]);
                        $sumDiscount = 0;

                        $proportion = $rule["DISCOUNT"]/$summ*100;

                        foreach ($rule["ITEMS"] as $id)
                        {
                            if($arNewPrices[$id])
                            {
                                $oldPrice = $arNewPrices[$id]["PRICE"];

                                if($rule["FOR_EACH_TICKET"])
                                {
                                    $price = round($oldPrice - $rule["DISCOUNT"], 2);
                                }
                                else
                                {
                                    $selectForRule--;

                                    if($selectForRule)
                                    {
                                        $price = round($oldPrice - ($oldPrice/100 * $proportion), 2);
                                        $sumDiscount += $oldPrice - $price;
                                    }
                                    else
                                    {
                                        $price = round($oldPrice - ($rule["DISCOUNT"] - $sumDiscount), 2);
                                    }
                                }


                                if($price < 0)
                                    $price = 0;

                                $discountPrice = $oldPrice - $price;

                                $arNewPrices[$id]["PRICE"] = $price;
                                $arNewPrices[$id]["RULES"][] = [
                                    "DISCOUNT_VALUE" => $discountPrice,
                                    "PROMOCODE_ID" => $rule["PROMOCODE_ID"],
                                    "RULE_ID" => $rule["RULE_ID"],
                                    "PROMOCODE" => $rule["PROMOCODE"],
                                    "DISCOUNT" => $rule["DISCOUNT"],
                                    "DISCOUNT_TYPE" => "rub",
                                ];
                            }
                        }
                    }

                }

                foreach ($arNewPrices as $basketItemId => $arPrice)
                {
                    if($arPrice["RULES"])
                    {
                        $basketItem = $basket->getItemById($basketItemId);

                        $oldPrice = $basketItem->getField('PRICE');
                        $newPrice = round($arPrice["PRICE"], 2);

                        if($newPrice < 0)
                            $newPrice = 0;

                        $discountPrice = $oldPrice - $newPrice;

                        $basketItem->setFields([
                            'CUSTOM_PRICE' => "Y",
                            'PRICE' => $newPrice,
                            'DISCOUNT_PRICE' => $discountPrice,
                        ]);
                    }
                }

                foreach ($promocodes as $promocode)
                {
                    foreach ($arNewPrices as $basketItemId => $arPrice)
                    {
                        if($arPrice["RULES"])
                        {
                            foreach ($arPrice["RULES"] as $rule)
                            {
                                if($promocode == $rule["PROMOCODE"])
                                    self::addRuleInTAble([
                                        'UF_BASKET_ITEM_ID' => $basketItemId,
                                        'UF_RULE_ID' => $rule["RULE_ID"],
                                        'UF_RULE_TYPE' => 'PROMOCODE',
                                        'UF_DISCOUNT_VALUE' => $rule["DISCOUNT_VALUE"],
                                        'UF_PROMOCODE_ID' => $rule["PROMOCODE_ID"],
                                        'UF_PROMOCODE' => $rule["PROMOCODE"],
                                        'UF_DISCOUNT' => $rule["DISCOUNT"],
                                        'UF_DISCOUNT_TYPE' => $rule["DISCOUNT_TYPE"],
                                    ]);
                            }
                        }
                    }
                }

            }
        }
        else
        {
            $errorMessage[] = "Использование данного промокода невозможно!";
        }

        return ["error" => $errorMessage];
    }

    public static function setPromocodeDiscount($promocodes = null, &$basket = null)
    {
        $basketItems = $basket->getBasketItems();
        $fullBasketPrice = $basket->getBasePrice();

        $eventId = false;

        $groupBasketItems = [];

        foreach ($basketItems as $item)
        {
            $offerId = $item->getField('PRODUCT_ID');
            $groupBasketItems[$offerId][] = $item;

            if(!$eventId)
            {
                $eventId = self::getEventIdByProduct($offerId);
            }
        }

        $arRules = [];

        $promocodes = array_unique($promocodes);

        foreach ($promocodes as $promocode) {
            $newRules = self::getRulesForPromocode(trim($promocode), $eventId);

            if($newRules)
            {
                $arRules = array_merge($arRules, $newRules);
            }
        }

        $discount = [
            "PRC" => [],
            "RUB" => [],
        ];

        if($arRules)
        {
            foreach ($arRules as $rule)
            {
                $discount[self::discountType[$rule["RULE_UF_DISCOUNT_TYPE"]]][] = $rule;
            }

            $targetRules = array_merge($discount['PRC'], $discount['RUB']);

            self::getOptimalPriceInBasket($basket);

            $promocodesRule = [];
            foreach ($promocodes as $value) {
                foreach ($targetRules as $targetRule) {
                    if($value == $targetRule["UF_CODE"])
                        $promocodesRule[$targetRule["ID"]] = $value;
                }
            }

            foreach ($targetRules as $targetRule) {
                if($targetRule["RULE_UF_FOR_ALL_TYPES"])
                {
                    $bufferItems = $basketItems;
                    if(!$targetRule["RULE_UF_TYPE_APPLY_ALL_ORDER"] && $targetRule["RULE_UF_TYPE_APPLY_MAX"])
                        $bufferItems = array_slice($basketItems, 0, $targetRule["RULE_UF_TYPE_APPLY_MAX"]);

                    self::applyDiscountToBasket($basket, $bufferItems, $targetRule, "RULE_", $promocodes);
                }
                else
                {
                    foreach ($groupBasketItems as $key => $item)
                    {
                        if(in_array($key, $targetRule["RULE_UF_TICKETS_TYPE"]) && count($item) >= $targetRule["RULE_UF_MIN_COUNT_TICKETS"] && count($item) >= $targetRule["RULE_UF_TYPE_APPLY_MIN"])
                        {
                            $bufferItems = $item;
                            if(!$prc["RULE_UF_TYPE_APPLY_ALL_ORDER"] && $targetRule["RULE_UF_TYPE_APPLY_MAX"])
                                $bufferItems = array_slice($item, 0, $targetRule["RULE_UF_TYPE_APPLY_MAX"]);

                            self::applyDiscountToBasket($basket, $bufferItems, $targetRule, "RULE_", $promocodes);
                        }
                    }
                }
            }
        }
        else
        {
            foreach ($groupBasketItem as &$item)
            {
                $price = $item->getField('BASE_PRICE');
                $discountPrice = 0;

                $item->setFields([
                    'DISCOUNT_COUPON' => "",
                ]);
            }

            self::getOptimalPriceInBasket($basket);
        }
    }

    public static function deleteAllPromocodeDiscount(&$basketItems = null)
    {
        $applyRulesEntity = new ORM\Query\Query('Custom\Core\Events\DiscountApplyTable');
        $query = $applyRulesEntity
            ->setSelect([
                'ID',
            ])
            ->setFilter([
                'UF_FUSER_ID' => \Bitrix\Sale\Fuser::getId(),
                'UF_RULE_TYPE' => "PROMOCODE",
                'BASKET_REFS.ORDER_ID' => null
            ])
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'BASKET_REFS',
                    'Bitrix\Sale\Internals\BasketTable',
                    ['this.UF_BASKET_ITEM_ID' => 'ref.ID'],
                    ['join_type' => 'LEFT'],
                )
            )
            ->exec();

        while ($rule = $query->fetchObject()) {
            $rule->delete();
        }
    }

    public static function addRuleInTAble($params)
    {
        $applyRulesEntity = new ORM\Query\Query('Custom\Core\Events\DiscountApplyTable');
        $applyRulesEntityClass  = $applyRulesEntity->getEntity()->getDataClass();

        if(!$params["UF_FUSER_ID"])
            $params["UF_FUSER_ID"] = \Bitrix\Sale\Fuser::getId();

        $resEvent = $applyRulesEntityClass::add($params);
    }

    public static function deleteAllRuleDiscount(&$basket = null)
    {
        $basketItems = $basket->getBasketItems();
        $basketItemsIds = [];

        foreach ($basketItems as &$item)
        {
            $basketItemsIds[] = $item->getField('ID');

            $price = $item->getField('BASE_PRICE');
            $discountPrice = 0;

            $item->setFields([
                'CUSTOM_PRICE' => "N",
                'PRICE' => $price,
                'DISCOUNT_PRICE' => $discountPrice,
                'DISCOUNT_COUPON' => "",
                'DISCOUNT_VALUE' => ""
            ]);
        }

        if($basketItems)
        {
            $applyRulesEntity = new ORM\Query\Query('Custom\Core\Events\DiscountApplyTable');
            $query = $applyRulesEntity
                ->setSelect([
                    'ID',
                ])
                ->setFilter([
                    'UF_FUSER_ID' => \Bitrix\Sale\Fuser::getId(),
                    'BASKET_REFS.ORDER_ID' => null
                ])
                ->registerRuntimeField(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'BASKET_REFS',
                        'Bitrix\Sale\Internals\BasketTable',
                        ['this.UF_BASKET_ITEM_ID' => 'ref.ID'],
                        ['join_type' => 'LEFT'],
                    )
                )
                ->exec();

            while ($rule = $query->fetchObject()) {
                $rule->delete();
            }
        }
    }

    public static function getRuleInfo($applyRuleId = null)
    {
        if($applyRuleId)
        {
            $priceRulesEntity = new ORM\Query\Query('Custom\Core\Events\PriceRulesTable');
            $query = $priceRulesEntity
                ->setSelect([
                    '*',
                ])
                ->setFilter([
                    'ID' => $applyRuleId,
                ])
                ->setGroup('ID')
                ->countTotal(true)
                ->exec();

            if ($priceRule = $query->fetch()) {
                return $priceRule;
            }
        }

        return [];
    }

    public static function getPromocodeInfo($applyPromocodeId = null)
    {
        if($applyPromocodeId)
        {
            $priceRulesEntity = new ORM\Query\Query('Custom\Core\Events\PromoCodesTable');
            $query = $priceRulesEntity
                ->setSelect([
                    '*',
                ])
                ->setFilter([
                    'ID' => $applyPromocodeId,
                ])
                ->setGroup('ID')
                ->countTotal(true)
                ->exec();

            if ($priceRule = $query->fetch()) {
                return $priceRule;
            }
        }

        return [];
    }
}
