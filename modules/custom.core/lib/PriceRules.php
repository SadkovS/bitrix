<?php

namespace Custom\Core;

use Bitrix\Main\ORM;

class PriceRules {
	
	/**
	 * @param string      $promoCode
	 * @param int         $eventId
	 * @param string|null $priceRuleXmlId
	 *
	 * @return bool
	 */
    public static function isPromoCodeUnique(string $promoCode, int $eventId, ?string $priceRuleXmlId = null): bool
    {
        $priceRulesEntity = new ORM\Query\Query('Custom\Core\Events\PriceRulesTable');
		
		$filter = ['REF_PROMOCODE.UF_CODE' => mb_strtoupper($promoCode), 'EVENT_ID' => $eventId];
		
		if (!is_null($priceRuleXmlId)) {
			$filter['!UF_XML_ID'] = $priceRuleXmlId;
		}
		
        $query = $priceRulesEntity
	        ->setSelect([
		                    '*',
		                    'EVENT_ID' => 'REF_EVENTS_ID.VALUE',
		                    'REF_PROMOCODE_ID' => 'REF_PROMOCODE.ID',
	                    ])
	        ->setFilter($filter)
	        ->setLimit(1)
	        ->exec();
		
        return !!$query->fetch();
    }
	
	/**
	 * @param string      $priceRuleName
	 * @param int         $eventId
	 * @param string|null $priceRuleXmlId
	 *
	 * @return bool
	 */
	public static function isPriceRuleNameUnique(string $priceRuleName, int $eventId, ?string $priceRuleXmlId = null): bool
	{
		$priceRulesEntity = new ORM\Query\Query('Custom\Core\Events\PriceRulesTable');
		
		$filter = ['UF_NAME' => $priceRuleName, 'EVENT_ID' => $eventId];
		
		if (!is_null($priceRuleXmlId)) {
			$filter['!UF_XML_ID'] = $priceRuleXmlId;
		}
		
		$query = $priceRulesEntity
			->setSelect([
				            '*',
				            'EVENT_ID' => 'REF_EVENTS_ID.VALUE',
			            ])
			->setFilter($filter)
			->setLimit(1)
			->exec();
		
		return !!$query->fetch();
	}
}