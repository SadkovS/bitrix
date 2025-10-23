<?php

namespace Custom\Core;

use Bitrix\Main\Loader;
use Bitrix\Main\ORM;
Loader::includeModule('sale');
class Sale {
    protected static $instance = null;

    /**
     * @return Sale
     */
    public static function getInstance(): Sale
    {
        if (!static::$instance) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    /**
     * @param $name
     *
     * @return array|false
     */
    public function getTradingRuleByName($name)
    {
        try{
            $promoCodesEntity = new ORM\Query\Query('Bitrix\Sale\Internals\DiscountTable');
            $query = $promoCodesEntity
                ->setSelect(['*'])
                ->setFilter(['USE_COUPONS' => 'N','NAME' => $name])
                ->countTotal(true)
                ->setLimit(1)
                ->exec();
            if($query->getCount() < 1) throw new \Exception('Promo code not found');
            return $query->fetch();
        }catch(\Exception $e){
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function addBxTradingRule(array $params = [])
    {
        try{
            $promoCodesEntity = new ORM\Query\Query('Bitrix\Sale\Internals\DiscountTable');
           Bitrix\Main\ORM\Entity\Manager::getInstance()->createObject($promoCodesEntity, $params);
//            $promoCodesEntity = new ORM\Query\Query('Bitrix\Sale\Internals\DiscountTable');
//            $query = $promoCodesEntity
//                ->setSelect(['*'])
//                ->setFilter(['USE_COUPONS' => 'N','NAME' => $name])
//                ->countTotal(true)
//                ->setLimit(1)
//                ->exec();
//            if($query->getCount() < 1) throw new \Exception('Promo code not found');
//            return $query->fetch();

        }catch(\Exception $e){
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}