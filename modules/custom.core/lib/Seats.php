<?php

namespace Custom\Core;

use Bitrix\Main\Entity\DataManager;
use Bitrix\Sale;
use Bitrix\Main\ORM;

class Seats
{
	const STATUS_CANCEL = "cancel";
	const STATUS_BOOK = "book";
	const KEY_SEAT_ID = "seat_id";
	const PROP_RESERV_TIME_ID = 25;
	
    public static function setSeatStatus($schema, $data)
	{
		$scheme = \Custom\Core\Helper::curlRequest(
            SIT_MAP_API_HOST . "/api/schema/{$schema}/book-seats",
            ['Content-Type: application/json; charset=UTF-8'],
            'POST',
            $data
        );
		//\Bitrix\Main\Diag\Debug::writeToFile([$schema, $data], 'pay', $fileName = "/log/log.txt");
		return ($scheme["status"] == "success")?true:false;
	}
	
	public static function orderEventUUID($event_id)
	{
		$eventEntity = new \Bitrix\Main\ORM\Query\Query('Custom\Core\Events\EventsTable');
	    $query = $eventEntity
	    ->setSelect(
	        [
	            'ID',
	            'UF_UUID',
	        ]
	    )
	    ->setFilter(['ID' => $event_id, 'UF_SIT_MAP' => true])
	    
	    ->exec();
	    $event = $query->fetch();
		
		return $event["UF_UUID"]?$event["UF_UUID"]:false;
	}
	
	public static function orderData($order = null)
	{
		$propertyCollection = $order->getPropertyCollection();
		$property = $propertyCollection->getItemByOrderPropertyCode("EVENT_ID");
		$event_id = $property->getValue();
		
		$schema_id = self::orderEventUUID($event_id);
		
		$basket = $order->getBasket();
			
		$basketItems = $basket->getBasketItems();
			
		foreach ($basketItems as $item)
		{
			$basketPropertyCollection = $item->getPropertyCollection();
			foreach ($basketPropertyCollection as $basketProperty){
				if($basketProperty->getField('CODE') == self::KEY_SEAT_ID) 
			    	$seat_ids[] = $basketProperty->getField('VALUE');
			}
		}
		
		return [$schema_id, $seat_ids];
	}
	
	public static function onOrderAdd($orderId = null)
	{
		if(!$orderId)
			return;
		
		$order = \Bitrix\Sale\Order::load($orderId);
		
		if($order->getPrice() > 0)
			return;
		
		[$schema_id, $seat_ids] = self::orderData($order);
		
		if($seat_ids)
		{
			foreach ($seat_ids as $value)
			{
				$data["objects"][$value] = [
					"action" => self::STATUS_BOOK
				];
			}
		}
		
		if($schema_id && $data)
		{
			self::setSeatStatus($schema_id, $data);
		}
	}
	
	public static function onOrderPay($orderId = null)
	{
		if(!$orderId)
			return;
		
		$order = \Bitrix\Sale\Order::load($orderId);
			
		[$schema_id, $seat_ids] = self::orderData($order);
		
		if($seat_ids)
		{
			foreach ($seat_ids as $value)
			{
				$data["objects"][$value] = [
					"action" => self::STATUS_BOOK
				];
			}
		}
		
		if($schema_id && $data)
		{
			self::setSeatStatus($schema_id, $data);
		}
	}
	
	public static function orderCancel($is_agent = false)
	{
		$orderIds = [];
		
		\Bitrix\Main\Loader::includeModule("sale");
		
		$dbRes = \Bitrix\Sale\Order::getList([
		    'select' => ['ID'],
		  	'filter' => [
		  	  ">PRICE" => 0,
		  	  "CANCELED" => "N",
		  	  "PAYED" => "N",
		      "=PROPERTY.ORDER_PROPS_ID" => self::PROP_RESERV_TIME_ID,
		      "<PROPERTY.VALUE" => time(),     
		  ],
		  'order' => ['ID' => 'DESC']
		]);
		while ($order = $dbRes->fetch()){
		    $orderIds[] = $order["ID"];
		}
		
		foreach ($orderIds as $orderId)
		{
			$seat_ids = [];
			$data = [];
			
			$order = \Bitrix\Sale\Order::load($orderId);
			
			/*[$schema_id, $seat_ids] = self::orderData($order);
			
			if($seat_ids)
			{
				foreach ($seat_ids as $value)
				{
					$data["objects"][$value] = [
						"action" => self::STATUS_CANCEL
					];
				}
			}
			
			if($schema_id && $data)
			{
				self::setSeatStatus($schema_id, $data);
			}*/

			$order->setField('CANCELED', "Y"); 
			$order->setField("STATUS_ID", "CD");
            $result = $order->save();
		}
		
		if($is_agent)
		{
			return "\Custom\Core\Seats::orderCancel(true);";
		}
	}
}
