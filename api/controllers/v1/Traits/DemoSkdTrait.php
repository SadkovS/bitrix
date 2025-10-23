<?php declare(strict_types=1);

namespace Local\Api\Controllers\V1\Traits;

use Artamonov\Rest\Foundation\Response;
use Local\Api\Controllers\V1\Enum\SkdErrorStatusCode;
use Local\Api\Controllers\V1\Enum\SkdSearchBy;


trait DemoSkdTrait
{
	private string $DEMO_TOKEN_SUCCESS = 'b4db52a5-f95a0e2b-94b44be0-33e7ad31';
	private string $DEMO_TOKEN_FAILED = '30ebf43c-2b264079-bee5cbba-05053d94';
	private array $demoBarcodes = [
		'0000000000000001' => [// Успешно прошел
			'ACCOUNT_NUMBER' => '0AA00A080',
			'FULL_NAME' => 'Участник 1',
			'TICKET_BARCODE' => '0000000000000001',
			'TICKET_TYPE' => 'Тестовый тип',
			'PLACE' => 11,
			'ROW' => 3,
			'SECTOR' => 4,
			'SKD_LAST_HISTORY_DATE' => '17.03.2025 в 12:30',
			'SKD_LAST_HISTORY_STATUS_CODE' => 'allowed', // Отмечен
		],
		'0000000000000002' => [// Успешно вышел
			'ACCOUNT_NUMBER' => '0AA00A081',
			'FULL_NAME' => 'Участник 11',
			'TICKET_BARCODE' => '0000000000000002',
			'TICKET_TYPE' => 'Тестовый тип',
			'PLACE' => 8,
			'ROW' => 2,
			'SECTOR' => 7,
			'SKD_LAST_HISTORY_DATE' => '17.03.2025 в 13:31',
			'SKD_LAST_HISTORY_STATUS_CODE' => 'exit', // Осуществлён выход
		],
		'0000000000000003' => [// Нет доступа к валидации данного типа билета
			'ACCOUNT_NUMBER' => '0AA00A082',
			'FULL_NAME' => 'Участник 3',
			'TICKET_BARCODE' => '0000000000000003',
			'TICKET_TYPE' => 'Тестовый тип 2',
			'PLACE' => 2,
			'ROW' => 8,
			'SECTOR' => 4,
			'SKD_LAST_HISTORY_DATE' => '17.03.2025 в 13:45',
			'SKD_LAST_HISTORY_STATUS_CODE' => 'not_use',
		],
		'0000000000000004' => [// Уже провалидирован
			'ACCOUNT_NUMBER' => '0AA00A083',
			'FULL_NAME' => 'Участник 333',
			'TICKET_BARCODE' => '0000000000000004',
			'TICKET_TYPE' => 'Тестовый тип',
			'PLACE' => 25,
			'ROW' => 3,
			'SECTOR' => 4,
			'SKD_LAST_HISTORY_DATE' => '17.03.2025 в 12:30',
			'SKD_LAST_HISTORY_STATUS_CODE' => 'allowed',
		],
		'0000000000000005' => [// Требует подтверждения входа
			'ACCOUNT_NUMBER' => '0AA00A085',
			'FULL_NAME' => 'Участник 332',
			'TICKET_BARCODE' => '0000000000000005',
			'TICKET_TYPE' => 'Тестовый тип',
			'PLACE' => 18,
			'ROW' => 1,
			'SECTOR' => 4,
			'SKD_LAST_HISTORY_DATE' => '17.03.2025 в 12:30',
			'SKD_LAST_HISTORY_STATUS_CODE' => 'not_use',
		],
		'0000000000000006' => [// Требует подтверждения выхода
			'ACCOUNT_NUMBER' => '0AA00A086',
			'FULL_NAME' => 'Участник 6',
			'TICKET_BARCODE' => '0000000000000006',
			'TICKET_TYPE' => 'Тестовый тип',
			'PLACE' => 18,
			'ROW' => 1,
			'SECTOR' => 4,
			'SKD_LAST_HISTORY_DATE' => '17.03.2025 в 12:30',
			'SKD_LAST_HISTORY_STATUS_CODE' => 'allowed',
		],
	];
	
	private array $getInfoObj = [
		'event_name' => 'Демо мероприятие',
		'is_allow_exit' => true,
		'is_confirmation_required' => true,
		'tickets' => [
			'sold_quantity' => 100,
			'validate_quantity' => 10,
		],
	];
	
	/**
	 * @return Response|false
	 */
	protected function demoConstruct(): Response|false {
		
		if (in_array($this->token, [$this->DEMO_TOKEN_SUCCESS, $this->DEMO_TOKEN_FAILED])) {
			$this->demoMode = true;
			
			if ($this->token === $this->DEMO_TOKEN_FAILED) {
				$this->outputError('Был использован demo токен для проверки авторизации с ошибкой', 401, SkdErrorStatusCode::NoValidationRights);
			} else {
				
				// назначаем id по коду для SKD_LAST_HISTORY_STATUS
				foreach ($this->demoBarcodes as $key => $barcode) {
					$this->demoBarcodes[$key]['SKD_LAST_HISTORY_STATUS'] = $this->skdHistoryStatusList[$barcode['SKD_LAST_HISTORY_STATUS_CODE']]['ENUM_ID'];
				}
			}
		}
		
		return false;
	}
	
	/**
	 * @return Response|false
	 */
	protected function demoGetInfo(): Response|false {
		
		if (!$this->demoMode) return false;
		
		$this->outputSuccess(
			[
				'result' => $this->getInfoObj
			]
		);
	}
	
	/**
	 * @param SkdSearchBy $by
	 * @param string      $q
	 * @param int         $limit
	 * @param int         $offset
	 *
	 * @return Response|false
	 */
	protected function demoSearch(SkdSearchBy $by, string $q, int $limit = 30, int $offset = 0): Response|false {
		
		if (!$this->demoMode) return false;
		
		$searched = [];
		
		if ($by === SkdSearchBy::Fio) {
			foreach ($this->demoBarcodes as $item) {
				if (strpos($item['FULL_NAME'], $q) !== false) {
					$searched[] = $item;
				}
			}
		} else {
			foreach ($this->demoBarcodes as $item) {
				if ($item['TICKET_BARCODE'] === $q) {
					$searched[] = $item;
				}
			}
		}
		
		$items = [];
		foreach ($searched as $item) {
			$items[] = $this->wrapSearchItem($item);
		}
		
		$items = array_slice($items, $offset, $limit);
		
		$this->outputSuccess(
			[
				'total_count' => count($searched),
				'result' => $items,
			]
		);
	}
	
	/**
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return Response|false
	 */
	protected function demoGetTickets(int $limit = 30, int $offset = 0): Response|false {
		
		if (!$this->demoMode) return false;
		
		$items = [];
		foreach ($this->demoBarcodes as $item) {
			$items[] = $this->wrapSearchItem($item);
		}
		
		$items = array_slice($items, $offset, $limit);
		
		$this->outputSuccess(
			[
				'total_count' => count($this->demoBarcodes),
				'result' => $items,
			]
		);
	}
	
	/**
	 * @param string $code
	 *
	 * @return Response|false
	 */
	protected function demoCheckTicket(string $code): Response|false {
		if (!$this->demoMode) return false;
		
		switch ($code) {
			case '0000000000000001': // Успешно прошел
			case '0000000000000002': { // Успешно вышел
				$this->outputSuccess(['item' => $this->wrapSearchItem($this->demoBarcodes[$code]), 'event_info' => $this->getInfoObj]);
			}
			case '0000000000000003': { // Нет доступа к валидации данного типа билета
				$this->outputError('У вас нет прав на валидацию данного типа билета',200, SkdErrorStatusCode::NoValidationRightsType, $this->wrapSearchItem($this->demoBarcodes[$code]), $this->getInfoObj);
			}
			case '0000000000000004': { // Уже провалидирован
				$this->outputError('Проход уже был', 200, SkdErrorStatusCode::AlreadyPassed, $this->wrapSearchItem($this->demoBarcodes[$code]), $this->getInfoObj);
			}
			case '0000000000000005': // Требует подтверждения входа
			case '0000000000000006': { // Требует подтверждения выхода
				$this->outputError('Требуется подтверждение', 200, SkdErrorStatusCode::Error, $this->wrapSearchItem($this->demoBarcodes[$code]), $this->getInfoObj);
			}
			default: {
				$this->outputError('Билет не найден', 200, SkdErrorStatusCode::NotFound, null, $this->getInfoObj);
			}
		}
	}
}