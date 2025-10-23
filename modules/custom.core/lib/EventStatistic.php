<?php

declare(strict_types=1);

namespace Custom\Core;

use Custom\Core\Traits\PropertyEnumTrait;
use Bitrix\Main\ORM;

class EventStatistic
{
	use PropertyEnumTrait;
	
	private array $barcodeStatusList;
	private array $skdHistoryStatusList;
	
	private array $filter = [];
	
	private array $runtime = [];
	
	public function __construct()
	{
		$this->barcodeStatusList    = $this->getPropertiesEnum('Barcodes', 'UF_STATUS', 'ENUM_XML_ID');
		$this->skdHistoryStatusList = $this->getPropertiesEnum('HistorySKD', 'UF_STATUS', 'ENUM_XML_ID');
	}
	
	/**
	 * Общее количество проданных билетов для мероприятий (для всех типов билетов)
	 *
	 * @param array $eventsId
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function getEventsCountTicketSold(array $eventsId): array
	{
		$this->setBaseFilter($eventsId);
		$this->setBaseRuntime();
		$this->buildQueryParamSold();
		
		$query = \Bitrix\Sale\Order::getList(
			[
				'select'  => [
					'EVENT_ID'    => 'PROPERTY_EVENT_ID.VALUE',
					'TICKET_TYPE' => 'BASKET_REFS.PRODUCT_ID',
					'QUANTITY',
				],
				'runtime' => $this->runtime,
				'filter'  => $this->filter,
				'group'   => ['EVENT_ID', 'TICKET_TYPE']
			]
		);
		
		$res = [];
		while ($item = $query->fetch()) {
			$res[$item['EVENT_ID']][$item['TICKET_TYPE']] = (int)$item['QUANTITY'];
		}
		
		return $res;
	}
	
	
	/**
	 * Общее количество проданных билетов для мероприятия (для указанных типов билетов или всех)
	 *
	 * @param int   $eventId
	 * @param array $allowedTicketsType - типы билетов
	 *
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getEventCountTicketSold(int $eventId, array $allowedTicketsType = []): int
	{
		$this->setBaseFilter($eventId, false, $allowedTicketsType);
		$this->setBaseRuntime();
		$this->buildQueryParamSold();
		
		$query = \Bitrix\Sale\Order::getList(
			[
				'select'  => [
					'EVENT_ID' => 'PROPERTY_EVENT_ID.VALUE',
					'QUANTITY',
				],
				'runtime' => $this->runtime,
				'filter'  => $this->filter,
				'group'   => ['EVENT_ID']
			]
		);
		
		if ($item = $query->fetch()) {
			return (int)$item['QUANTITY'];
		}
		
		return 0;
	}
	
	
	/**
	 * Общее количество проверенных билетов для мероприятий (проходы в статусе отмечен и проход запрещен)
	 *
	 * @param array $eventsId
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	/*public function getEventsCountTicketValidated(array $eventsId): array
	{
		$this->setBaseFilter($eventsId, true);
		$this->setBaseRuntime(true);
		$this->buildQueryParamValidated();

		$this->runtime[] = new \Bitrix\Main\Entity\ReferenceField(
			'HISTORY_SKD_REF',
			'Custom\Core\Skd\HistorySKDTable',
			['=this.BARCODE.ID' => 'ref.UF_BARCODE_ID'],
			['join_type' => 'left']);

		$this->filter['HISTORY_SKD_REF.UF_STATUS'] = [$this->skdHistoryStatusList['allowed']['ENUM_ID'] ?? 0, $this->skdHistoryStatusList['no_entry']['ENUM_ID'] ?? 0];

		$query = \Bitrix\Sale\Order::getList(
			[
				'select'  => [
					'EVENT_ID'    => 'PROPERTY_EVENT_ID.VALUE',
					'TICKET_TYPE' => 'BASKET_REFS.PRODUCT_ID',
					'HISTORY_SKD_ID' => 'HISTORY_SKD_REF.ID',
					'BARCODE_ID' => 'BARCODE.ID',
					'ACCESS_SKD_ID' => 'HISTORY_SKD_REF.UF_ACCESS_SKD_ID',
					'SKD_HISTORY_ALLOWED',
					'QUANTITY',
				],
				'runtime' => $this->runtime,
				'filter'  => $this->filter,
				'group'   => ['EVENT_ID', 'TICKET_TYPE', 'BARCODE_ID', 'ACCESS_SKD_ID']
			]
		);
		
		$res = [];
		while ($item = $query->fetch()) {
			if (!isset($res[$item['EVENT_ID']][$item['ACCESS_SKD_ID']][$item['TICKET_TYPE']])) {
				$res[$item['EVENT_ID']][$item['ACCESS_SKD_ID']][$item['TICKET_TYPE']] = (int)$item['QUANTITY'];
			} else {
				$res[$item['EVENT_ID']][$item['ACCESS_SKD_ID']][$item['TICKET_TYPE']] += (int)$item['QUANTITY'];
			}
		}
		
		return $res;
	}*/
	
	/**
	 * Общее количество проверенных билетов для мероприятия (проходы в статусе отмечен и проход запрещен) (для указанных типов билетов или всех)
	 *
	 * @param int   $eventId
	 * @param array $allowedTicketsType - типы билетов
	 *
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getEventCountTicketValidated(int $eventId, ?int $skdAccessId = null, array $allowedTicketsType = []): int
	{
		$this->setBaseFilter($eventId, true, $allowedTicketsType);
		$this->setBaseRuntime(true);
		$this->buildQueryParamValidated($skdAccessId);
		
		$query = \Bitrix\Sale\Order::getList(
			[
				'select'  => [
					'EVENT_ID' => 'PROPERTY_EVENT_ID.VALUE',
					'QUANTITY',
				],
				'runtime' => $this->runtime,
				'filter'  => $this->filter,
				'group'   => ['EVENT_ID']
			]
		);
		
		if ($item = $query->fetch()) {
			return (int)$item['QUANTITY'];
		}
		
		return 0;
	}
	
	
	/**
	 * Общее количество проходов по мероприятиям (проходы в статусе отмечен)
	 *
	 * @param array $eventsId
	 * @param bool  $isOnlyUnique
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getEventsCountTicketNumberOfPasses(array $eventsId, bool $isOnlyUnique = true): array
	{
		$this->setBaseFilter($eventsId, true);
		$this->setBaseRuntime(true);
		$this->buildQueryParamNumberPasses($isOnlyUnique);
		
		$query = \Bitrix\Sale\Order::getList(
			[
				'select'  => [
					'EVENT_ID'    => 'PROPERTY_EVENT_ID.VALUE',
					'TICKET_TYPE' => 'BASKET_REFS.PRODUCT_ID',
					'QUANTITY',
				],
				'runtime' => $this->runtime,
				'filter'  => $this->filter,
				'group'   => ['EVENT_ID', 'TICKET_TYPE']
			]
		);
		
		$res = [];
		
		while ($item = $query->fetch()) {
			$res[$item['EVENT_ID']][$item['TICKET_TYPE']] = (int)$item['QUANTITY'];
		}
		
		return $res;
	}
	
	/**
	 *Общее количество проходов по мероприятию (проходы в статусе отмечен) (для указанных типов билетов или всех)
	 *
	 * @param int   $eventId
	 * @param array $allowedTicketsType
	 * @param bool  $isOnlyUnique
	 *
	 * @return int
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getEventCountTicketNumberOfPasses(
		int $eventId,
		array $allowedTicketsType = [],
		bool $isOnlyUnique = true
	): int {
		$this->setBaseFilter($eventId, true, $allowedTicketsType);
		$this->setBaseRuntime(true);
		$this->buildQueryParamNumberPasses($isOnlyUnique);
		
		$query = \Bitrix\Sale\Order::getList(
			[
				'select'  => [
					'EVENT_ID' => 'PROPERTY_EVENT_ID.VALUE',
					'TICKET_TYPE' => 'BASKET_REFS.PRODUCT_ID',
					'QUANTITY',
				],
				'runtime' => $this->runtime,
				'filter'  => $this->filter,
				'group'   => ['EVENT_ID']
			]
		);
		
		if ($item = $query->fetch()) {
			return (int)$item['QUANTITY'];
		}
		
		return 0;
	}
	
	/**
	 * Получить историю прохождеий для расчета за каждый день
	 *
	 * @param int $eventId
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getEventTicketNumberOfPasses(
		int $eventId
	): array {
		$this->setBaseFilter($eventId, true);
		$this->setBaseRuntime(true);
		
		$this->runtime[] = new \Bitrix\Main\Entity\ReferenceField(
				'HISTORY_SKD_REF',
				'Custom\Core\Skd\HistorySKDTable',
				['=this.BARCODE_ID' => 'ref.UF_BARCODE_ID'],
				['join_type' => 'inner']);
		
		$this->filter['HISTORY_SKD_REF.UF_STATUS'] = $this->skdHistoryStatusList['allowed']['ENUM_ID'] ?? -1;
		
		$query = \Bitrix\Sale\Order::getList(
			[
				'select'  => [
					'EVENT_ID' => 'PROPERTY_EVENT_ID.VALUE',
					'BARCODE_ID' => 'BARCODE.ID',
					'HISTORY_DATE' => 'HISTORY_SKD_REF.UF_DATE_TIME',
				],
				'runtime' => $this->runtime,
				'filter'  => $this->filter,
				'group'   => ['EVENT_ID']
			]
		);
	
		return $query->fetchAll();
	}
	
	
	/**
	 * @return void
	 * @throws \Bitrix\Main\SystemException
	 */
	private function buildQueryParamSold(): void {
		$this->runtime[] = new \Bitrix\Main\Entity\ExpressionField(
		'QUANTITY', 'SUM(%s)', ['BASKET_REFS.QUANTITY']
		);
	}
	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function buildQueryParamValidated(?int $skdAccessId = null): void
	{
		$subFilter = [
			'UF_BARCODE_ID'        =>  new \Bitrix\Main\DB\SqlExpression('%s'),
			'UF_STATUS' => [$this->skdHistoryStatusList['allowed']['ENUM_ID'] ?? 0, $this->skdHistoryStatusList['no_entry']['ENUM_ID'] ?? 0],
		];
		
		if (!is_null($skdAccessId)) {
			$subFilter['UF_ACCESS_SKD_ID'] = $skdAccessId;
		}
		
		$subSkdHistoryQuery = new ORM\Query\Query('Custom\Core\Skd\HistorySKDTable');
		$subSkdHistoryQuery->setSelect(['CNT'])->setFilter($subFilter)->registerRuntimeField(
			'CNT',
			[
				'data_type'  => 'integer',
				'expression' => ['COUNT(DISTINCT(%s))', 'ID'],
			]
		);
		$subSkdHistoryQuerySql = $subSkdHistoryQuery->getQuery();
	
		$this->runtime[] = new \Bitrix\Main\Entity\ExpressionField(
			'SKD_HISTORY_ALLOWED', '(' . $subSkdHistoryQuerySql . ')', 'BARCODE.ID'
		);
		$this->filter[">SKD_HISTORY_ALLOWED"] = 0;
		
		if ($skdAccessId) {
			$this->runtime[] = new \Bitrix\Main\Entity\ExpressionField(
				'QUANTITY', 'SUM(%s)', ['SKD_HISTORY_ALLOWED']
			);
		} else {
			$this->runtime[] = new \Bitrix\Main\Entity\ExpressionField(
				'QUANTITY', 'SUM(%s)', ['BASKET_REFS.QUANTITY']
			);
		}
	}
	
	/**
	 * @return void
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private function buildQueryParamNumberPasses(bool $isOnlyUnique = true): void
	{
		$subSkdHistoryQuery = new ORM\Query\Query('Custom\Core\Skd\HistorySKDTable');
		
		if ($isOnlyUnique) {
			$subSkdHistoryQuery->setSelect(['ID'])->setOrder(['ID' => 'DESC'])->setLimit(1)->setFilter(
				[
					'UF_BARCODE_ID'        =>  new \Bitrix\Main\DB\SqlExpression('%s'),
					'UF_STATUS' => [$this->skdHistoryStatusList['allowed']['ENUM_ID'] ?? 0],
				]
			);
			$subSkdHistoryQuerySql = $subSkdHistoryQuery->getQuery();
			
			$this->runtime[] = new \Bitrix\Main\Entity\ExpressionField(
				'SKD_HISTORY_ALLOWED', '(' . $subSkdHistoryQuerySql . ')', 'BARCODE.ID'
			);
			$this->filter["!SKD_HISTORY_ALLOWED"] = false;
			
		} else {
			$subSkdHistoryQuery->setSelect(['CNT'])->setFilter(
				[
					'UF_BARCODE_ID'        =>  new \Bitrix\Main\DB\SqlExpression('%s'),
					'UF_STATUS' => [$this->skdHistoryStatusList['allowed']['ENUM_ID'] ?? 0],
				]
			)->registerRuntimeField(
				'CNT',
				[
					'data_type'  => 'integer',
					'expression' => ['COUNT(%s)', 'ID'],
				]
			);
			$subSkdHistoryQuerySql = $subSkdHistoryQuery->getQuery();
			
			$this->runtime[] = new \Bitrix\Main\Entity\ExpressionField(
				'SKD_HISTORY_ALLOWED', '(' . $subSkdHistoryQuerySql . ')', 'BARCODE.ID'
			);
			//$this->filter[">SKD_HISTORY_ALLOWED"] = 0;
		}
		
		$this->runtime[] = new \Bitrix\Main\Entity\ExpressionField(
			'QUANTITY', 'SUM(%s)', [$isOnlyUnique ? 'BASKET_REFS.QUANTITY' : 'SKD_HISTORY_ALLOWED']
		);
	}
	
	
	/**
	 * @param array|int  $eventId
	 * @param array|null $allowedTicketsType
	 *
	 * @return void
	 */
	private function setBaseFilter(array|int $eventId, bool $withBarcodes = false, ?array $allowedTicketsType = []): void {
		$this->filter = [
			"PAYED"                    => "Y",
			"PROPERTY_EVENT_ID.CODE"   => "EVENT_ID",
			"PROPERTY_EVENT_ID.VALUE" => $eventId,
			'BASKET_PROPS_IS_REFUNDED.VALUE' => false,
		];
		
		if (is_array($allowedTicketsType) && count($allowedTicketsType) > 0) {
			$this->filter['BASKET_REFS.PRODUCT_ID'] = $allowedTicketsType;
		}
		
		if ($withBarcodes) {
			$this->filter["BARCODE.UF_STATUS"] = [$this->barcodeStatusList['used']['ENUM_ID'] ?? 0];
		}
	}
	
	private function setBaseRuntime(bool $withBarcodes = false): void {
		$this->runtime = [
			new \Bitrix\Main\Entity\ReferenceField(
				'PROPERTY_EVENT_ID',
				'Bitrix\Sale\Internals\OrderPropsValueTable',
				['this.ID' => 'ref.ORDER_ID'],
				['join_type' => 'inner']
			),
			new \Bitrix\Main\Entity\ReferenceField(
				'BASKET_REFS',
				'Bitrix\Sale\Internals\BasketTable',
				['this.ID' => 'ref.ORDER_ID'],
				['join_type' => 'left']
			),
			(new \Bitrix\Main\Entity\ReferenceField(
				'BASKET_PROPS_IS_REFUNDED',
				'Bitrix\Sale\Internals\BasketPropertyTable',
				\Bitrix\Main\ORM\Query\Join::on('ref.BASKET_ID', 'this.BASKET_REFS.ID')
				                           ->where("ref.CODE", "=", "IS_REFUNDED")
			))->configureJoinType(
				\Bitrix\Main\ORM\Query\Join::TYPE_LEFT,
			),
		];
		
		if ($withBarcodes) {
			$this->runtime[] = new \Bitrix\Main\Entity\ReferenceField(
				'BASKET_PROPS_BARCODE_REF',
				'\Bitrix\Sale\Internals\BasketPropertyTable',
				['=this.BASKET_REFS.ID' => 'ref.BASKET_ID'],
				['join_type' => 'left']
			);
			$this->runtime[] = new \Bitrix\Main\Entity\ReferenceField(
				'BARCODE',
				'Custom\Core\Tickets\BarcodesTable',
				['=this.BASKET_PROPS_BARCODE_REF.VALUE' => 'ref.ID'],
				['join_type' => 'left']
			);
		}
	}
}