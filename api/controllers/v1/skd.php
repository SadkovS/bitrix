<?php declare(strict_types=1);

namespace Local\Api\Controllers\V1;

use Bitrix\Main\Loader;
use Artamonov\Rest\Foundation\Response;
use Custom\Core\Traits\PropertyEnumTrait;
use Local\Api\Controllers\V1\Enum\SkdErrorStatusCode;
use Local\Api\Controllers\V1\Enum\SkdSearchBy;
use Local\Api\Controllers\V1\Traits\SkdTrait;
use Custom\Core\Traits\TimeTrait;
use Local\Api\Controllers\V1\Traits\DemoSkdTrait;
use Custom\Core\Helper;
use Custom\Core\EventStatistic;
use Custom\Core\FileSemaphoreManager;
use Local\Api\Controllers\V1\Traits\LoggerSkdTrait;

Loader::includeModule('artamonov.rest');

class Skd
{
	use TimeTrait;
	use PropertyEnumTrait;
	use SkdTrait;
	use DemoSkdTrait;
    use LoggerSkdTrait;
	
	private int $userId;
	private string $token;
	
	private array $barcodeStatusList;
	private array $skdHistoryStatusList;
	private array $eventTypeList;
	private array $participationType;
	
	private array|false $skd;
	
	private \DateTime $currentDateTime;
	
	protected FileSemaphoreManager $semaphoreManager;
	
	/**
	 * @var resource|null
	 */
	protected $fp = null;
	
	/**
	 * контроллер должен иметь возможность получать доступ до начала мероприятия за n часов
	 * @var int
	 */
	private int $timeReserveInHoursUntil;
	
	/**
	 * контроллер должен иметь возможность получать доступ после окончания мероприятия еще n часов
	 * @var int
	 */
	private int $timeReserveInHoursAfter;
	
	/**
	 * время начала текущего дня мероприятия с учетом $timeReserveInHoursUntil
	 * @var int
	 */
	private ?int $currentDayStartTs = null;
	
	/**
	 * время окончания текущего дня мероприятия с учетом $timeReserveInHoursAfter
	 * @var int
	 */
	private ?int $currentDayEndTs = null;
	
	private bool $demoMode = false;
	
	
	function __construct()
	{
		$request = request()->get();
		
		$this->semaphoreManager = new FileSemaphoreManager();
		
		$this->userId = (int)$request['_user']['ID'];
		$this->token = $request['_user']['UF_REST_API_TOKEN'];
		
		$groups = \CUser::GetUserGroup($request['_user']['ID']) ?? [];
		
		$this->barcodeStatusList = $this->getPropertiesEnum('Barcodes', 'UF_STATUS','ENUM_XML_ID');
		$this->skdHistoryStatusList = $this->getPropertiesEnum('HistorySKD', 'UF_STATUS','ENUM_XML_ID');
		$this->eventTypeList = $this->getPropertiesEnum('Events', 'UF_TYPE');
		$this->participationType = $this->getParticipationType();
		
		$this->timeReserveInHoursUntil = CHECK_TICKET_TIME_RESERVE_IN_HOURS_UNTIL;
		$this->timeReserveInHoursAfter = CHECK_TICKET_TIME_RESERVE_IN_HOURS_AFTER;
		
		$this->currentDateTime = new \DateTime();
		//$this->currentDateTime = new \DateTime('03.02.2025 12:30:00'); // test
		
		$this->demoConstruct();
		
		if (!$this->demoMode) {
			$controllerGroupId = Helper::getGroupByCode('controllers')['ID'] ?? null;
			
			if (!in_array($controllerGroupId, $groups)) {
				$this->outputError('Нет доступа', 401, SkdErrorStatusCode::NoValidationRights); // У вашей группы нет необходимых привелегий
			}
			
			$this->skd = $this->getSkd($this->userId);
		}
	}
	
	public function __destruct()
	{
		if (!is_null($this->fp)) {
			$this->semaphoreManager->release($this->fp);
		}
	}
	
	/**
	 * @return Response
	 */
	public function checkToken(): Response {
		$this->outputSuccess();
	}
	
	/**
	 * @return Response
	 */
	public function getInfo(): Response
	{
		try {
			$this->demoGetInfo();
			
			$info = $this->_getInfo();
			$this->outputSuccess(
				['result' => $info]
			);
			
		} catch (\Exception $e) {
			$this->outputError($e->getMessage());
		}
	}
	
	private function _getInfo(): array
	{
		$eventStatistic = new EventStatistic();
		
		return [
			'event_name' => $this->skd['EVENT_NAME'] ?? '',
			'is_allow_exit' => (bool)($this->skd['UF_IS_ALLOW_EXIT'] ?? false),
			'is_confirmation_required' => (bool)($this->skd['UF_IS_CONFIRMATION_REQUIRED'] ?? false),
			//'skd' => $this->skd,
			'tickets' => [
				'sold_quantity' => $eventStatistic->getEventCountTicketSold((int)$this->skd['UF_EVENT_ID'], $this->skd['UF_TICKETS_TYPE']),
				'validate_quantity' => $eventStatistic->getEventCountTicketValidated((int)$this->skd['UF_EVENT_ID'], (int)$this->skd['ID'], $this->skd['UF_TICKETS_TYPE']),
			],
		];
	}
	
	/**
	 * @return Response
	 */
	public function search(): Response
	{
		try {
			
			$request = request()->get();
			
			if (isset($request['by'])) {
				$by = match ($request['by']) {
					SkdSearchBy::Fio->value => SkdSearchBy::Fio,
					SkdSearchBy::Code->value => SkdSearchBy::Code,
					default => $this->outputError('Строка поиска некорректна', messageCode: SkdErrorStatusCode::IncorrectParameters),
				};
			} else {
				$by = SkdSearchBy::Fio;
			}
			
			$q = htmlspecialchars(trim((string)($request['q'] ?? '')));
			
			if (mb_strlen($q) < 2) {
				$this->outputError('Строка поиска не может быть менее 2х символов', messageCode: SkdErrorStatusCode::IncorrectParameters);
			}
			
			$offset  = isset($request['offset']) && (int)$request['offset'] > 0 ? (int)$request['offset'] : 0;
			
			if (isset($request['limit'])) {
				$limit   = (int)$request['limit'] > 0 ? (int)$request['limit'] : 30;
				$limit   = $limit > 100 ? 100 : $limit;
			} else {
				$limit = 30;
			}
			
			$this->demoSearch($by, $q, $limit, $offset);
			
			$results = $this->searchBy((int)$this->skd['UF_EVENT_ID'], $this->skd['UF_TICKETS_TYPE'], $by, $q, $limit, $offset);
			
			$this->outputSuccess(
				[
					'total_count' => $results['total_count'],
					'result' => $results['items'] ?? [],
				]
			);
			
		} catch (\Exception $e) {
			$this->outputError($e->getMessage(), messageCode: SkdErrorStatusCode::Error);
		}
	}
	
	public function getTickets(): Response
	{
		try {
			$request = request()->get();
			
			$offset  = isset($request['offset']) && (int)$request['offset'] > 0 ? (int)$request['offset'] : 0;
			
			if (isset($request['limit'])) {
				$limit   = (int)$request['limit'] > 0 ? (int)$request['limit'] : 30;
				$limit   = $limit > 100 ? 100 : $limit;
			} else {
				$limit = 30;
			}
			
			$this->demoGetTickets($limit, $offset);
			
			$results = $this->getTicketList((int)$this->skd['UF_EVENT_ID'], $this->skd['UF_TICKETS_TYPE'], $limit, $offset);
			
			$this->outputSuccess(
				[
					'total_count' => $results['total_count'],
					'result' => $results['items'] ?? [],
				]);
			
		} catch (\Exception $e) {
			$this->outputError($e->getMessage(), messageCode: SkdErrorStatusCode::Error);
		}
	}
	
	public function checkTicket(): Response
	{
		try {
			$request = request()->get();
			if  (!isset($request['code'])) {
				$this->outputError('Параметр «code» обязателен для заполнения', 200, SkdErrorStatusCode::IncorrectParameters, null, $this->_getInfo());
			}
			
			if  (isset($request['allowed']) && !is_bool($request['allowed'])) {
				$this->outputError('Параметр «allowed» должен быть boolean', 200, SkdErrorStatusCode::IncorrectParameters, null, $this->_getInfo());
			}
			
			if  (isset($request['exit_mode']) && !is_bool($request['exit_mode'])) {
				$this->outputError('Параметр «exit_mode» должен быть boolean', 200, SkdErrorStatusCode::IncorrectParameters, null, $this->_getInfo());
			}
			
			if ( !(mb_strlen((string)$request['code']) === 16 && is_numeric($request['code']) )) {
				$this->outputError('Билет не найден', 200, SkdErrorStatusCode::NotFound, null, $this->_getInfo());
			}
			
			$code = htmlspecialchars(trim((string)$request['code']));
			
			$this->demoCheckTicket($code);
			
			// проверяем может ли валидатор сейчас проверять билеты (даты скд доступа)
			// так же проверяется началось ли мероприятие с учетом (CHECK_TICKET_TIME_RESERVE_IN_HOURS_UNTIL)
			// так же проверяется не закончилось ли мероприятие с учетом (CHECK_TICKET_TIME_RESERVE_IN_HOURS_AFTER)
			$this->checkEventAndValidator();
			
			$allowedTicketSType = array_keys(array_filter($this->skd['_ALLOWED_TICKETS_TYPE'], fn($true) => !!$true));
			
			// Захват семафора
			$this->fp = $this->semaphoreManager->acquire($request['code']);
			
			$ticket = $this->getTicketByCode((int)$this->skd['UF_EVENT_ID'], $allowedTicketSType, $code);
			
			if ($ticket) {
				if ($this->isOnline((int)($ticket['TICKET_TYPE_PARTICIPATION'] ?? 0))) {
					$this->outputError('Билет не найден', 200, SkdErrorStatusCode::NotFound, null, $this->_getInfo()); // Данный билет нельзя обработать, так как тип участия - онлайн
				}
				
				if ($this->skd['UF_IS_CONFIRMATION_REQUIRED'] && !isset($request['allowed'])) {
					$this->outputError('Требуется подтверждение', 200, SkdErrorStatusCode::Error, $this->wrapSearchItem($ticket), $this->_getInfo()); // Параметр «allowed» не передан
				}
				
				$prevStatusId = (int)$ticket['SKD_LAST_HISTORY_STATUS'];
				
				switch ($prevStatusId) {
					case $this->skdHistoryStatusList['no_exit']['ENUM_ID']:// участнику был запрещен выход контроллером
						$this->handleNoExitStatus($request, $ticket, $prevStatusId, $code);
						break;
					
					case $this->skdHistoryStatusList['allowed']['ENUM_ID']:// участник пытается выйти
						$this->handleAllowedStatus($request, $ticket, $prevStatusId, $code);
						break;
					
					case $this->skdHistoryStatusList['exit']['ENUM_ID']:// участнику вышел и заходит повторно
						$this->handleExitStatus($request, $ticket, $prevStatusId, $code);
						break;
					
					case $this->skdHistoryStatusList['no_entry']['ENUM_ID']:// участнику был запрещен проход контроллером
					default:// null - информации по билету нет в истории; первый проход
						$this->handleNoEntryOrDefaultStatus($request, $ticket, $prevStatusId, $code);
						break;
				}
				
				// Освобождение семафора
				$this->semaphoreManager->release($this->fp);
				$this->fp = null;
				
				$resultWithNewStatus = $this->getTicketByCode((int)$this->skd['UF_EVENT_ID'], $allowedTicketSType, $code);
				
				$this->outputSuccess(
					[
						'item' => $this->wrapSearchItem($resultWithNewStatus),
						'event_info' => $this->_getInfo(),
					]
				);
			}
			
		} catch (\Exception $e) {
			$this->outputError($e->getMessage(), 200, SkdErrorStatusCode::Error, null, $this->_getInfo());
		}
	}
	
	
	/**
	 * @param $request
	 * @param $ticket
	 * @param $prevStatusId
	 * @param $code
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function handleNoExitStatus(array $request, array $ticket, int $prevStatusId, string $code): void
	{
		if (!$this->skd['UF_IS_ALLOW_EXIT']) {
			$this->outputError('Выход запрещен', 200, SkdErrorStatusCode::IncorrectParameters, $this->wrapSearchItem($ticket), $this->_getInfo());
		}
		
		if ($request['exit_mode']) {
			if ($this->skd['UF_IS_CONFIRMATION_REQUIRED']) {
				if ($request['allowed']) {
					$date = new \DateTime();
					$this->addSkdHistoryItem((int)$this->skd['ID'], (int)$this->skd['UF_EVENT_ID'], $this->skdHistoryStatusList['exit']['ENUM_ID'], $prevStatusId, $date, $code);
				} else {
					$this->outputError('Выход запрещен', 200, SkdErrorStatusCode::IncorrectParameters, $this->wrapSearchItem($ticket), $this->_getInfo());
				}
			} else {
				$this->outputError('Проход уже был', 200, SkdErrorStatusCode::AlreadyPassed, $this->wrapSearchItem($ticket), $this->_getInfo());
			}
		} else {
			$this->outputError('Проход уже был', 200, SkdErrorStatusCode::AlreadyPassed, $this->wrapSearchItem($ticket), $this->_getInfo());
		}
	}
	
	/**
	 * @param $request
	 * @param $ticket
	 * @param $prevStatusId
	 * @param $code
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function handleAllowedStatus(array $request, array $ticket, int $prevStatusId, string $code): void
	{
		if (!$this->skd['UF_IS_ALLOW_EXIT']) {
			if ($request['exit_mode']) {
				$this->outputError('Выход запрещен', 200, SkdErrorStatusCode::IncorrectParameters, $this->wrapSearchItem($ticket), $this->_getInfo());
			} else {
				if ($this->skd['UF_IS_CONFIRMATION_REQUIRED']) {
					if ($request['allowed']) {
						$this->outputError('Проход уже был', 200, SkdErrorStatusCode::AlreadyPassed, $this->wrapSearchItem($ticket), $this->_getInfo());
					} else {
						$this->outputError('Действие было выполнено ранее', 200, SkdErrorStatusCode::Error);
					}
				} else {
					$this->outputError('Проход уже был', 200, SkdErrorStatusCode::AlreadyPassed, $this->wrapSearchItem($ticket), $this->_getInfo());
				}
			}
		}
		
		if ($request['exit_mode']) {
			$date = new \DateTime();
			if ($this->skd['UF_IS_CONFIRMATION_REQUIRED']) {
				$status = $request['allowed'] ? 'exit' : 'no_exit';
				$this->addSkdHistoryItem(
					(int)$this->skd['ID'],
					(int)$this->skd['UF_EVENT_ID'],
					$this->skdHistoryStatusList[$status]['ENUM_ID'],
					$prevStatusId,
					$date,
					$code
				);
			} else {
				$this->addSkdHistoryItem(
					(int)$this->skd['ID'],
					(int)$this->skd['UF_EVENT_ID'],
					$this->skdHistoryStatusList['exit']['ENUM_ID'],
					$prevStatusId,
					$date,
					$code
				);
			}
		} else {
			$this->outputError('Проход уже был', 200, SkdErrorStatusCode::AlreadyPassed, $this->wrapSearchItem($ticket), $this->_getInfo());
		}
	}
	
	/**
	 * @param $request
	 * @param $ticket
	 * @param $prevStatusId
	 * @param $code
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function handleExitStatus(array $request, array $ticket, int $prevStatusId, string $code): void
	{
		if ($request['exit_mode']) {
			$this->outputError('Выход уже был', 200, SkdErrorStatusCode::AlreadyOut, $this->wrapSearchItem($ticket), $this->_getInfo());
		}
		
		$date = new \DateTime();
		
		if ($this->skd['UF_IS_CONFIRMATION_REQUIRED']) {
			$status = $request['allowed'] ? 'allowed' : 'no_entry';
			$this->addSkdHistoryItem(
				(int)$this->skd['ID'],
				(int)$this->skd['UF_EVENT_ID'],
				$this->skdHistoryStatusList[$status]['ENUM_ID'],
				$prevStatusId,
				$date,
				$code
			);
		} else {
			$this->addSkdHistoryItem(
				(int)$this->skd['ID'],
				(int)$this->skd['UF_EVENT_ID'],
				$this->skdHistoryStatusList['allowed']['ENUM_ID'],
				$prevStatusId,
				$date,
				$code
			);
		}
	}
	
	/**
	 * @param $request
	 * @param $ticket
	 * @param $prevStatusId
	 * @param $code
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function handleNoEntryOrDefaultStatus(array $request, array $ticket, int $prevStatusId, string $code): void
	{
		if ($request['exit_mode']) {
			$this->outputError('Проход еще не был выполнен', 200, SkdErrorStatusCode::IncorrectParameters, $this->wrapSearchItem($ticket), $this->_getInfo());
		}
		
		$historyStatus = (
			($this->skd['UF_IS_CONFIRMATION_REQUIRED'] && $request['allowed']) ||
			(!$this->skd['UF_IS_CONFIRMATION_REQUIRED'])
		)
			? $this->skdHistoryStatusList['allowed']['ENUM_ID']
			: $this->skdHistoryStatusList['no_entry']['ENUM_ID'];
		
		if (empty($result['SKD_LAST_HISTORY_STATUS'])) {
			$this->setBarcodeStatusUsed((int)$this->skd['UF_EVENT_ID'], $code);
		}
		
		$date = new \DateTime();
		
		$this->addSkdHistoryItem(
			(int)$this->skd['ID'],
			(int)$this->skd['UF_EVENT_ID'],
			$historyStatus,
			$prevStatusId,
			$date,
			$code
		);
	}
	
	/**
	 * @param array $rest
	 *
	 * @return Response
	 */
	private function outputSuccess(array $rest = []): Response {
		$statusSuccess = [
			'status'  => 'success',
		];

        $this->add2log(
            array_merge($statusSuccess, $rest)
        );

		response()->json(
			array_merge($statusSuccess, $rest), 200, [],['Content-Type' => 'application/json']
		);
	}
	
	/**
	 * @param string                  $message
	 * @param int                     $code
	 * @param SkdErrorStatusCode|null $messageCode
	 * @param array|null              $ticket
	 * @param array|null              $eventInfo
	 *
	 * @return Response
	 */
	private function outputError(string $message, int $code = 200, ?SkdErrorStatusCode $messageCode = null, ?array $ticket = null, ?array $eventInfo = null): Response {

        $this->add2log(
            [
                "status" => "error",
                "message" => $message,
                "messageCode" => $messageCode,
                "ticket" => $ticket,
                "event_info" => $eventInfo
            ]
        );

		response()->json(
			[
				'status'  => 'error',
				'message' => $message,
				'message_code' => $messageCode,
				'item' => $ticket,
				'event_info' => $eventInfo,
			],
			$code, [],['Content-Type' => 'application/json']
		);
	}
}