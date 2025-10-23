<?php declare(strict_types=1);

namespace Local\Api\Controllers\V1\Traits;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\ORM;
use Local\Api\Controllers\V1\Enum\SkdErrorStatusCode;
use Local\Api\Controllers\V1\Enum\SkdSearchBy;
use Bitrix\Main\ORM\Query\Result;

Loader::includeModule('sale');

trait SkdTrait
{
	/**
	 * @param int $userId
	 *
	 * @return array
	 * @throws \DateMalformedStringException
	 */
	private function getSkd(int $userId): array {
		
		$skd = $this->getSkdItem($userId);
		
		if (is_array($skd) && !empty($skd)) {
			$eventId = (int)($skd['UF_EVENT_ID'] ?? 0);
			if (!$eventId) {
				$this->outputError("Нет доступа", 401,  SkdErrorStatusCode::NoValidationRights); // Идентификатор мероприятия для SKD не определен!
			}
			
			$eventLocationDates = $this->getEventLocationDates($eventId);
			$skd['EVENT_LOCATION_DATES'] = $eventLocationDates;
			
			foreach ($skd['UF_TICKETS_TYPE'] as $ticketSTypeId) {
				$skd['_ALLOWED_TICKETS_TYPE'][$ticketSTypeId] = false;
			}
			
			//$isAllowedNow = $this->isAllowedNow($eventLocationDates, $skd['UF_DATE']);
			$eventTypeId = $this->getEventTypeId($eventId);
			
			if (!isset($this->eventTypeList[$eventTypeId]['ENUM_XML_ID'])) {
				$this->outputError("Нет доступа", 401,  SkdErrorStatusCode::NoValidationRights); // Тип события не определен
			}
			
		} else {
			$this->outputError("Нет доступа", 401,  SkdErrorStatusCode::NoValidationRights); // Пользователь Skd не найден
		}
		
		$offers = $this->getTicketsOffer((int)$skd['UF_EVENT_ID']);
		
		if (is_array($skd['UF_TICKETS_TYPE']) && !empty($skd['UF_TICKETS_TYPE'])) {
			foreach($skd['UF_TICKETS_TYPE'] as $controllerTicketTypeId) {
				$breakCurrentTicketType = false;
				
				if (isset($offers[$controllerTicketTypeId])) {
					
					if ($offers[$controllerTicketTypeId]['SKU_DATES_ALL'] === 'all') {
						$skd['_ALLOWED_TICKETS_TYPE'][$controllerTicketTypeId] = true;
					} else {
						$skuDates = preg_split('@;@', $offers[$controllerTicketTypeId]['SKU_DATES'] ?? '', -1, PREG_SPLIT_NO_EMPTY);
						$skuDates = array_map(fn($date) => new \DateTime($date), $skuDates);
						
						foreach ($skuDates as $skuDate) {
							foreach ($eventLocationDates as $location) {
								if (is_array($location['UF_DATE_TIME']) &&
								    count($location['UF_DATE_TIME']) > 0 &&
								    (int)$location['UF_DURATION'] > 0) {
									
									foreach ($location['UF_DATE_TIME'] as $dateItem) {
										if ($skuDate->format('Y-m-d') === $dateItem->format('Y-m-d')) {
											$dateTimeStartTs     = $dateItem->getTimestamp();
											$dateTimeEndTs       = (new \DateTime($dateItem->format('Y-m-d H:i:s')))->modify(
												'+' . (int)$location['UF_DURATION'] . ' minutes'
											)->getTimestamp();
											
											if ($this->currentDateTime->getTimestamp() >= ($dateTimeStartTs - $this->hoursToSeconds($this->timeReserveInHoursUntil)) && $this->currentDateTime->getTimestamp() <= ($dateTimeEndTs + $this->hoursToSeconds($this->timeReserveInHoursAfter))) {
												$skd['_ALLOWED_TICKETS_TYPE'][$controllerTicketTypeId] = true;
												
												$breakCurrentTicketType = true;
												break;
											}
										}
									}
								}
								if ($breakCurrentTicketType) break;
							}
							
							if ($breakCurrentTicketType) break;
						}
					}
				}
			}
		}
		
		return $skd;
		
	}
	
	
	/**
	 * @param int $userId
	 *
	 * @return array|false
	 */
	public function getSkdItem(int $userId): array|false {
		
		$skdEntity                      = new ORM\Query\Query('Custom\Core\Skd\AccessSKDTable');
		$query                            = $skdEntity
			->setSelect(
				[
					'ID',
					'UF_USER_ID',
					'UF_EVENT_ID',
					'UF_DATE',
					'UF_TICKETS_TYPE',
					'UF_IS_ALLOW_EXIT',
					'UF_IS_CONFIRMATION_REQUIRED',
					'EVENT_NAME' => 'REF_EVENT.UF_NAME',
					'COMPANY_ID' => 'REF_EVENT.UF_COMPANY_ID',
					'EVENT_STATUS' => 'REF_EVENT.UF_STATUS',
					'TOKEN'      => 'REF_USER.UF_REST_API_TOKEN',
					'USER_FIO',
				]
			)
			->setFilter([
				            'UF_USER_ID' => $userId,
			            ])
			->setLimit(1)
			->exec();
		
		return $query->fetch();
	}
	
	private function getEventLocationDates(int $eventId): array {
		
		$hlblLocation     = HL\HighloadBlockTable::getById(HL_EVENTS_LOCATION_ID)->fetch();
		$entityLocation   = HL\HighloadBlockTable::compileEntity($hlblLocation);
		$hlbClassLocation = $entityLocation->getDataClass();
		
		$obElement                        = $hlbClassLocation::getList(
			[
				'select' => [
					'UF_DATE_TIME',
					'UF_DURATION',
				],
				'filter' => ['UF_EVENT_ID' => $eventId],
			]
		);
		
		// получим даты проведения мероприятия и определим доступность для контроллера
		return $obElement->fetchAll();
	}
	
	
	/**
	 * @param int $eventId
	 *
	 * @return int
	 */
	private function getEventTypeId(int $eventId): int {
		
		$hlblevent     = HL\HighloadBlockTable::getById(HL_EVENTS_ID)->fetch();
		$entityEvent   = HL\HighloadBlockTable::compileEntity($hlblevent);
		$hlbClassEvent = $entityEvent->getDataClass();
		
		$obElement                        = $hlbClassEvent::getList(
			[
				'select' => [
					'UF_TYPE',
				],
				'filter' => ['ID' => $eventId],
				'limit' => 1,
			]
		);
		
		
		return (int)($obElement->fetch()['UF_TYPE'] ?? 0);
	}
	
	
	/**
	 * @return void
	 * @throws \Exception
	 */
	public function checkEventAndValidator(): void
	{
		$nowTs = $this->currentDateTime->getTimestamp();
		$foundEventTime = false;      // Флаг, что текущее время попадает в интервал события (с резервом)
		$foundValidatorAccess = false; // Флаг, что дата входит в UF_DATE и время в интервале
		
		foreach ($this->skd['EVENT_LOCATION_DATES'] as $location) {
			if (is_array($location['UF_DATE_TIME']) &&
			    count($location['UF_DATE_TIME']) > 0 &&
			    (int)$location['UF_DURATION'] > 0)
			{
				foreach ($location['UF_DATE_TIME'] as $dateItem) {
					$dateTimeStartTs = $dateItem->getTimestamp();
					$dateTimeEndTs = (new \DateTime($dateItem->format('Y-m-d H:i:s')))
						->modify('+' . (int)$location['UF_DURATION'] . ' minutes')
						->getTimestamp();
					
					$reserveDateTimeStartTs = $dateTimeStartTs - $this->hoursToSeconds($this->timeReserveInHoursUntil);
					$reserveDateTimeEndTs = $dateTimeEndTs + $this->hoursToSeconds($this->timeReserveInHoursAfter);
					
					$inEventInterval = ($nowTs >= $reserveDateTimeStartTs && $nowTs <= $reserveDateTimeEndTs);
					
					if ($inEventInterval) {
						$foundEventTime = true;
					}
					
					if ($inEventInterval && in_array($dateItem->format('Y-m-d'), $this->skd['UF_DATE'])) {
						$foundValidatorAccess = true;
						$this->currentDayStartTs = $reserveDateTimeStartTs;
						$this->currentDayEndTs = $reserveDateTimeEndTs;
						// Можно прервать оба цикла, так как доступ подтверждён
						break 2;
					}
				}
			}
		}
		
		if (!$foundEventTime) {
			// Текущее время вне интервала события — ошибка начала/окончания
			$this->outputError(
				"Мероприятие ещё не началось или уже завершилось",
				200,
				SkdErrorStatusCode::Error,
				null,
				$this->_getInfo()
			);
		}
		
		if (!$foundValidatorAccess) {
			// Нет доступа валидатора — ошибка доступа
			$this->outputError(
				"Нет доступа",
				400,
				SkdErrorStatusCode::NoValidationRights
			);
		}
	}
	
	/**
	 * @param int $eventId
	 *
	 * @return array
	 */
	private function getTicketsOffer(int $eventId): array {
		$elementEntity = \Bitrix\Iblock\IblockTable::compileEntity('ticketsOffers');
		$propField     = $elementEntity->getField('CML2_LINK');
		$propEntity    = $propField->getRefEntity();
		
		$dbRes = \Bitrix\Iblock\Elements\ElementTicketsTable::getList(
			[
				'select'  => [
					'SKU_ID'   => 'OFFER.ID',
					'SKU_DATES',
					'SKU_DATES_ALL'      => 'OFFER.DATES_ALL.VALUE',
				],
				'filter'  => ['EVENT_ID.VALUE' => $eventId],
				'runtime' => [
					new \Bitrix\Main\Entity\ReferenceField(
						'TICKETS',
						$propEntity,
						['this.ID' => 'ref.VALUE'],
						['join_type' => 'LEFT'],
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'OFFER',
						$elementEntity,
						['this.TICKETS.IBLOCK_ELEMENT_ID' => 'ref.ID'],
						['join_type' => 'LEFT'],
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'PROPS',
						'\Bitrix\Catalog\ProductTable',
						['this.OFFER.ID' => 'ref.ID'],
						['join_type' => 'LEFT'],
					),
					new \Bitrix\Main\Entity\ExpressionField(
						'SKU_DATES',
						"GROUP_CONCAT(%s SEPARATOR ';')",
						['OFFER.DATES.VALUE']
					),
				]
			]
		);
		
		$res = [];
		while($sku = $dbRes->fetch()) {
			$res[$sku['SKU_ID']]   = $sku;
		}
		
		return $res;
	}
	
	/**
	 * @param int         $eventId
	 * @param array       $allowedTicketsType
	 * @param SkdSearchBy $by
	 * @param string      $q
	 * @param int         $limit
	 * @param int         $offset
	 *
	 * @return array
	 */
	private function searchBy(int $eventId, array $allowedTicketsType, SkdSearchBy $by, string $q, int $limit = 30, int $offset = 0): array //
	{
		$filter = [
			"PROPERTY_EVENT_ID.VALUE" => $eventId, // 505
			'BASKET_REF.PRODUCT_ID' => $allowedTicketsType, //[992] // OFFER_ID
			'TICKET_TYPE_PARTICIPATION' => [$this->participationType['offline']['ID'] ?? 0, $this->participationType['offline_online']['ID'] ?? 0],
		];
		
		if ($q !== '') {
			if ($by === SkdSearchBy::Fio) {
				$filter["%FULL_NAME"] = $q; ;
			} else {
				$filter["TICKET_BARCODE"] = $q."%";
			}
		}
		
		$select = [
			'TICKET_TYPE_ID' => 'BASKET_REF.PRODUCT_ID',
		];
		
		$dbRes = $this->_searchTicketQuery($select, $filter, $limit, $offset);
		$res = ['total_count' => $dbRes->getCount(), 'items' => []];
		while ($element = $dbRes->fetch()) {
			$res['items'][] = $this->wrapSearchItem($element);
		}
		
		return $res;
	}
	
	/**
	 * @param int   $eventId
	 * @param array $allowedTicketsType
	 * @param int   $limit
	 * @param int   $offset
	 *
	 * @return array
	 */
	private function getTicketList(int $eventId, array $allowedTicketsType, int $limit = 30, int $offset = 0): array
	{
		
		$filter = [
			"PROPERTY_EVENT_ID.VALUE" => $eventId, // 505
			'BASKET_REF.PRODUCT_ID' => $allowedTicketsType, //[992] // OFFER_ID
			'TICKET_TYPE_PARTICIPATION' => [$this->participationType['offline']['ID'] ?? 0, $this->participationType['offline_online']['ID'] ?? 0],
		];
		
		$select = [
			'TICKET_TYPE_ID' => 'BASKET_REF.PRODUCT_ID',
		];
		
		$dbRes = $this->_searchTicketQuery($select, $filter, $limit, $offset);
		$res = ['total_count' => $dbRes->getCount(), 'items' => []];
		while ($element = $dbRes->fetch()) {
			$res['items'][] = $this->wrapSearchItem($element);
		}
		
		return $res;
	}
	
	/**
	 * @param array $element
	 *
	 * @return array
	 */
	private function wrapSearchItem(array $element): array {
		return [
			'order_number' => $element['ACCOUNT_NUMBER'],
			'name' => $element['FULL_NAME'],
			'barcode' => $element['TICKET_BARCODE'],
			'ticket_type' => $element['TICKET_TYPE'],
			'place' => $element['PLACE'],
			'row' => $element['ROW'],
			'sector' => $element['SECTOR'],
			'date' => is_object($element['SKD_LAST_HISTORY_DATE']) ? preg_replace('/(\d{2}\.\d{2}\.\d{4})(\s)(\d{2}:\d{2})/', '$1 в $3', $element['SKD_LAST_HISTORY_DATE']->format('d.m.Y H:i') ): '',
			'status' => $this->_getStatusTicketText((int)$element['SKD_LAST_HISTORY_STATUS']),
			'status_code' => $this->_getStatusTicketCode((int)$element['SKD_LAST_HISTORY_STATUS']),
			'status_message' => $this->_getStatusTicketMessage((int)$element['SKD_LAST_HISTORY_STATUS']),
		];
	}
	
	/**
	 * @param int    $eventId
	 * @param array  $allowedTicketsType
	 * @param string $code
	 *
	 * @return array
	 */
	private function getTicketByCode(int $eventId, array $allowedTicketsType, string $code): array
	{
		$filter = [
			"PROPERTY_EVENT_ID.VALUE" => $eventId,
			"TICKET_BARCODE" => $code,
		];
		
		$select = [
			'TICKET_TYPE_ID' => 'BASKET_REF.PRODUCT_ID',
		];
		
		$dbRes = $this->_searchTicketQuery($select, $filter, 1);
		
		if ($element = $dbRes->fetch()) {
			
			if (!in_array((int)$element['TICKET_TYPE_ID'], $allowedTicketsType)) {
				$this->outputError('У вас нет прав на валидацию данного типа билета',200, SkdErrorStatusCode::NoValidationRightsType, $this->wrapSearchItem($element), $this->_getInfo());
			}
			
			return $element;
		} else {
			$this->outputError('Билет не найден', 200, SkdErrorStatusCode::NotFound, null, $this->_getInfo());
		}
	}
	
	/**
	 * @param int $statusId
	 *
	 * @return string
	 */
	private function _getStatusTicketText(int $statusId): string {
		return match ($statusId) {
			$this->skdHistoryStatusList['allowed']['ENUM_ID'] => 'Отмечен',
			$this->skdHistoryStatusList['no_entry']['ENUM_ID'] => 'Проход запрещен',
			$this->skdHistoryStatusList['no_exit']['ENUM_ID'] => 'Выход запрещен',
			$this->skdHistoryStatusList['exit']['ENUM_ID'] => 'Осуществлен выход',
			default => 'Проход разрешен',
		};
	}
	
	/**
	 * @param int $statusId
	 *
	 * @return string
	 */
	private function _getStatusTicketMessage(int $statusId): string {
		return match ($statusId) {
			$this->skdHistoryStatusList['allowed']['ENUM_ID'] => 'Проход уже был',
			$this->skdHistoryStatusList['exit']['ENUM_ID'] => 'Выход уже был',
			default => '',
		};
	}
	
	private function _getStatusTicketCode(int $statusId): string {
		
		foreach ($this->skdHistoryStatusList as $item) {
			if ((int)$item['ENUM_ID'] === $statusId) {
				return $item['ENUM_XML_ID'];
			}
		}
		
		return 'not_use';
	}
	
	/**
	 * @param int    $skdId
	 * @param int    $eventId
	 * @param string $barcode
	 *
	 * @return int
	 * @throws \Exception
	 */
	private function addSkdHistoryItem(int $skdId, int $eventId, int $statusId, ?int $prevStatusId,  \DateTime $date, string $barcode): int {
		
		if ($statusId === $prevStatusId) {
			$this->outputError('Действие было выполнено ранее', messageCode: SkdErrorStatusCode::Error);
		}
		
		$barcodeQuery = $this->_getBarcodeQuery($eventId, $barcode);
		
		if ($barcode = $barcodeQuery->fetch()) {
			
			$entityHistory = \Custom\Core\Skd\HistorySKDTable::getEntity();
			$objHistory    = $entityHistory->createObject();
			$objHistory->set('UF_ACCESS_SKD_ID', $skdId);
			$objHistory->set('UF_BARCODE_ID', $barcode['ID']);
			$objHistory->set('UF_STATUS', $statusId);
			$objHistory->set('UF_DATE_TIME', $date->format('d.m.Y H:i:s'));
			$objHistory->set('UF_CREATED_DATE', (new \DateTime())->format('d.m.Y H:i:s'));
			$resHistory = $objHistory->save();
			
			if(!$resHistory->isSuccess()) {
				$this->outputError(implode(', ', $resHistory->getErrors()), messageCode: SkdErrorStatusCode::Error);
			}
			
			return $resHistory->getId();
		} else {
			$this->outputError('Билет не найден', 200, SkdErrorStatusCode::NotFound, null, $this->_getInfo()); // Штрихкод не найден или недоступен
		}
	}
	
	/**
	 * @param int    $eventId
	 * @param string $barcode
	 *
	 * @return bool
	 */
	private function setBarcodeStatusUsed(int $eventId, string $barcode): bool {
		$barcodeQuery = $this->_getBarcodeQuery($eventId, $barcode);
		
		if ($obBarcode = $barcodeQuery->fetchObject()) {
			$obBarcode->set('UF_STATUS', $this->barcodeStatusList['used']['ENUM_ID'] ?? 0);
			$resUpdate = $obBarcode->save();
			
			if(!$resUpdate->isSuccess()) {
				$this->outputError(implode(', ', $resUpdate->getErrors()), messageCode: SkdErrorStatusCode::Error);
			}
			return true;
		} else {
			$this->outputError('Билет не найден', 200, SkdErrorStatusCode::NotFound, $this->_getInfo()); //Штрихкод не найден или недоступен
		}
	}
	
	
	/**
	 * @param int    $eventId
	 * @param string $barcode
	 *
	 * @return Result
	 */
	private function _getBarcodeQuery(int $eventId, string $barcode): Result {
		$barcodesEntity = new ORM\Query\Query('Custom\Core\Tickets\BarcodesTable');
		return $barcodesEntity->setSelect(
			[
				'*',
			]
		)->setFilter(
			[
				'UF_EVENT_ID' => $eventId,
				'UF_BARCODE' => $barcode,
				'UF_STATUS' => [$this->barcodeStatusList['sold']['ENUM_ID'] ?? 0, $this->barcodeStatusList['used']['ENUM_ID'] ?? 0, $this->barcodeStatusList['request_refund']['ENUM_ID'] ?? 0]
			]
		)->setLimit(1)->exec();
	}
	
	/**
	 * @param array $select
	 * @param array $filter
	 *
	 * @return Result
	 */
	private function _searchTicketQuery(array $select, array $filter, int $limit = 30, int $offset = 0): \Bitrix\Main\DB\Result {
		
		$selectBase = [
			'ACCOUNT_NUMBER',
			'TICKET_BARCODE'   => 'BARCODE.UF_BARCODE',
			'TICKET_STATUS'    => 'BARCODE.UF_STATUS',
			//'OFFER_ID'          => 'BASKET_REF.PRODUCT_ID',
			'TICKET_TYPE'       => 'TICKET_TYPE_REF.VALUE',
			'TICKET_TYPE_PARTICIPATION'       => 'TICKET_TYPE_PARTICIPATION_REF.VALUE',
			'PLACE' => 'BASKET_PROPS_PLACE_REF.VALUE',
			'ROW' => 'BASKET_PROPS_ROW_REF.VALUE',
			'SECTOR' => 'BASKET_PROPS_SECTOR_REF.VALUE',
			
			'FULL_NAME' => 'USER_FIO_REF.UF_FULL_NAME',
			'SKD_LAST_HISTORY_STATUS',
			'SKD_LAST_HISTORY_DATE',
		];
		
		$filterBase = [
			"PAYED"                    => "Y", //оплаченные
			//'STATUS_ID'                => ['P', 'F'],
			"PROPERTY_EVENT_ID.CODE"   => "EVENT_ID",
			"BASKET_PROPS_PLACE_REF.CODE" => 'PLACE',
			"BASKET_PROPS_ROW_REF.CODE" => 'ROW',
			"BASKET_PROPS_SECTOR_REF.CODE" => 'SECTOR',
			"TICKET_STATUS" => [$this->barcodeStatusList['sold']['ENUM_ID'] ?? 0, $this->barcodeStatusList['used']['ENUM_ID'] ?? 0, $this->barcodeStatusList['request_refund']['ENUM_ID'] ?? 0],
		];
		
		$select = array_merge($selectBase, $select);
		$filter = array_merge($filterBase, $filter);
		
		$subQueryFilter = [
			'UF_BARCODE_ID'  => new \Bitrix\Main\DB\SqlExpression('%s'),
		];
		
		if ($this->currentDayStartTs && $this->currentDayEndTs) {
			$subQueryFilter[">=UF_DATE_TIME"] = date('d.m.Y H:i:s', $this->currentDayStartTs);
			$subQueryFilter["<=UF_DATE_TIME"] = date('d.m.Y H:i:s', $this->currentDayEndTs);
		}
		
		$subQueryHistoryStatus = new ORM\Query\Query('Custom\Core\Skd\HistorySKDTable');
		$subQueryHistoryStatus
			->setSelect(['UF_STATUS'])
			->setLimit(1)
			->setOrder(['ID' => 'DESC'])
			->setFilter($subQueryFilter);
		$subQueryHistoryStatusSql = $subQueryHistoryStatus->getQuery();
		
		$subQueryHistoryDate = new ORM\Query\Query('Custom\Core\Skd\HistorySKDTable');
		$subQueryHistoryDate
			->setSelect(['UF_CREATED_DATE'])
			->setLimit(1)
			->setOrder(['ID' => 'DESC'])
			->setFilter($subQueryFilter);
		$subQueryHistoryDateSql = $subQueryHistoryDate->getQuery();
		
		$offerEntity         = \Bitrix\Iblock\IblockTable::compileEntity('ticketsOffers');
		$propFieldType       = $offerEntity->getField('TYPE');
		$propFieldTypeEntity = $propFieldType->getRefEntity();
		
		$propFieldTypeParticipation       = $offerEntity->getField('TYPE_PARTICIPATION');
		$propFieldTypeParticipationEntity = $propFieldTypeParticipation->getRefEntity();
		
		return \Bitrix\Sale\Order::getList(
			[
				'select'      => $select,
				'filter'      => $filter,
				'runtime'     => [
					new \Bitrix\Main\Entity\ReferenceField(
						'PROPERTY_EVENT_ID',
						'Bitrix\Sale\Internals\OrderPropsValueTable',
						['=this.ID' => 'ref.ORDER_ID'],
						['join_type' => 'inner']
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'BASKET_REF',
						'\Bitrix\Sale\Internals\BasketTable',
						['=this.ID' => 'ref.ORDER_ID'],
						['join_type' => 'inner']
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'BASKET_PROPS_BARCODE_REF',
						'\Bitrix\Sale\Internals\BasketPropertyTable',
						['=this.BASKET_REF.ID' => 'ref.BASKET_ID'],
						['join_type' => 'inner']
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'BASKET_PROPS_ROW_REF',
						'\Bitrix\Sale\Internals\BasketPropertyTable',
						['=this.BASKET_REF.ID' => 'ref.BASKET_ID'],
						['join_type' => 'inner']
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'BASKET_PROPS_PLACE_REF',
						'\Bitrix\Sale\Internals\BasketPropertyTable',
						['=this.BASKET_REF.ID' => 'ref.BASKET_ID'],
						['join_type' => 'inner']
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'BASKET_PROPS_SECTOR_REF',
						'\Bitrix\Sale\Internals\BasketPropertyTable',
						['=this.BASKET_REF.ID' => 'ref.BASKET_ID'],
						['join_type' => 'inner']
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'BARCODE',
						'Custom\Core\Tickets\BarcodesTable',
						['=this.BASKET_PROPS_BARCODE_REF.VALUE' => 'ref.ID'],
						['join_type' => 'inner']
					),
					
					new \Bitrix\Main\Entity\ReferenceField(
						'TICKET_TYPE_REF',
						$propFieldTypeEntity,
						['this.BASKET_REF.PRODUCT_ID' => 'ref.IBLOCK_ELEMENT_ID'],
						['join_type' => 'LEFT'],
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'TICKET_TYPE_PARTICIPATION_REF',
						$propFieldTypeParticipationEntity,
						['this.BASKET_REF.PRODUCT_ID' => 'ref.IBLOCK_ELEMENT_ID'],
						['join_type' => 'LEFT'],
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'QUESTIONNAIRE_TICKET_REF',
						'Custom\Core\Events\EventsQuestionnaireUfTicketTable',
						['=this.BASKET_REF.ID' => 'ref.VALUE'],
						['join_type' => 'LEFT']
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'USER_FIO_REF',
						'Custom\Core\Events\EventsQuestionnaireTable',
						['=this.QUESTIONNAIRE_TICKET_REF.ID' => 'ref.ID'],
						['join_type' => 'LEFT']
					),
					new \Bitrix\Main\Entity\ExpressionField(
						'SKD_LAST_HISTORY_STATUS', '(' . $subQueryHistoryStatusSql . ')', ['BARCODE.ID',]
					),
					new \Bitrix\Main\Entity\ExpressionField(
						'SKD_LAST_HISTORY_DATE', '(' . $subQueryHistoryDateSql . ')', ['BARCODE.ID',]
					)
				],
				'limit'       => $limit,
				'offset'      => $offset,
				'count_total' => true,
			]
		);
	}
	
	/**
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	protected function getParticipationType(): array {
		$query                                     = new ORM\Query\Query('\Bitrix\Iblock\PropertyEnumerationTable');
		$res = [];
		
		$resType                     = $query
			->setSelect(['ID', 'VALUE', 'XML_ID'])
			->setOrder(['SORT' => 'ASC'])
			->setFilter(['PROPERTY.CODE' => 'TYPE_PARTICIPATION'])
			->registerRuntimeField(
				new \Bitrix\Main\Entity\ReferenceField(
					'PROPERTY',
					'\Bitrix\Iblock\PropertyTable',
					['this.PROPERTY_ID' => 'ref.ID'],
					['join_type' => 'LEFT'],
				)
			)
			->setCacheTtl(3600)
			->exec();
		while ($type = $resType->fetch()) {
			$res[$type['XML_ID']] = $type;
		}
		unset($query, $resType, $type);
		
		return $res;
	}
	
	/**
	 * @param int $participationTypeId
	 *
	 * @return bool
	 */
	protected function isOnline(int $participationTypeId): bool {
		foreach ($this->participationType as $item) {
			if ((int)$item['ID'] === $participationTypeId && $item['XML_ID'] === 'online') {
				return true;
			}
		}
		
		return false;
	}
}