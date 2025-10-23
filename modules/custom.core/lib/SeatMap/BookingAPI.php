<?php

namespace Custom\Core\SeatMap;
//namespace SeatMap;

use Exception;

class BookingAPI
{
    /** @var string SeatMap booking API URL */
    private string $api;

    /** @var array Заголовки запроса к API */
    private array $header = [];

    /** @var array Заголовки запроса к API для JSON */
    private array $headerJson = [];

    /** @var string|null Токен, полученный после авторизации */
    private ?string $organizationToken = null;

    /**
     * @param string $api booking API URL
     * @param string $organizationToken Токен организации доступа к API
     * @throws Exception
     */
    public function __construct(
        string $api,
        string $organizationToken,
    )
    {
        if (empty($api)) {
            throw new Exception('Не задан booking API URL');
        }
        $this->api = $api;

        if (empty($organizationToken)) {
            throw new Exception('Не заданы API токен');
        }

        $this->organizationToken = $organizationToken;
        $this->header = [
            "X-API-Key: $this->organizationToken",
        ];
        $this->headerJson = [
            'Content-Type: application/json',
            "X-API-Key: $this->organizationToken",
        ];
    }

    /**
     * Создаёт организацию.
     *
     * @param array{
     *         appendDomainToLogin: bool,
     *         autologinEnabled: bool,
     *         domain: string,
     *         id: int,
     *         name: string,
     *         privateKey: string,
     *         publicKey: string,
     *         type: string,
     *     } $user Параметры пользователя
     * @return array{
     *         appendDomainToLogin: bool,
     *         autologinEnabled: bool,
     *         domain: string,
     *         id: int,
     *         name: string,
     *         privateKey: string,
     *         publicKey: string,
     *         type: string,
     *     }|null
     */
    public function createOrganization(array $user): ?array
    {
        if (!$user) {
            return null;
        }

        if (!isset($user['publicKey'])) {
            $user['publicKey'] = Helper::generateGUID();
        }

        if (!isset($user['privateKey'])) {
            $user['privateKey'] = Helper::generateGUID();
        }

        $result = Helper::curlRequest(
            $this->api . '/api/private/v2.0/organizations/',
            $this->headerJson,
            'POST',
            $user
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $result = json_decode($result['response'], true);

        if (!$result || !isset($result['id'])) {
            return null;
        }

        return [
            'id' => $result['id'],
            'name' => $result['name'],
            'publicKey' => $result['publicKey'],
            'privateKey' => $result['privateKey'],
        ];
    }

    /**
     * Создаёт цену
     *
     * @param string $eventId Id события
     * @param string $name Название
     * @return int|null
     */
    public function createPrice(string $eventId, string $name): ?int
    {
        $result = Helper::curlRequest(
            $this->api . "/api/private/v2.0/event/$eventId/prices/",
            $this->headerJson,
            'POST',
            [
                [
                    'name' => $name,
                    'eventId' => $eventId,
                ]
            ],
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $result = json_decode($result['response'], true);

        if (!$result) {
            return null;
        }

        return $result[0]['id'];
    }

    /**
     * Создаёт ценовую категорию в SeatMap
     * @param int $schemaId Id Схемы
     * @param string $name Название ценовой категории
     * @return ?int
     */
    public function createPricingZone(int $schemaId, string $name): ?int
    {
        $result = Helper::curlRequest(
            $this->api . "/api/private/v2.0/schemas/$schemaId/pricing_zones/",
            $this->headerJson,
            'POST',
            [
                [
                    'name' => $name,
                    'schemaId' => $schemaId,
                ]
            ],
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $result = json_decode($result['response'], true);

        if (!$result) {
            return null;
        }

        return $result[0]['id'];
    }

    /**
     * Создаёт площадку в SeatMap.
     *
     * @param Venue $venue
     * @return Venue|null
     */
    public function createVenue(Venue $venue): ?Venue
    {
        $result = Helper::curlRequest(
            $this->api . '/api/private/v2.0/venues/',
            $this->headerJson,
            'POST',
            $venue->toArray(),
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $result = json_decode($result['response'], true);

        if (!$result) {
            return null;
        }

        if ($result['id']) {
            return new Venue(
                $result['address'],
                $result['draft'],
                $result['id'],
                $result['lat'],
                $result['lng'],
                $result['name']
            );
        }

        return null;
    }

    /**
     * Удаляет событие.
     *
     * @param string $eventId Id события
     * @return bool
     */
    public function deleteEvent(string $eventId): bool
    {
        $result = Helper::curlRequest(
            $this->api . "/api/private/v2.0/event/$eventId",
            $this->header,
            'DELETE',
            []
        );

        if (!$result) {
            return false;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return false;
        }

        return $result['response'] === 'true';
    }

    /**
     * Удаляет организацию.
     *
     * @param int $id Id организации.
     * @return bool
     */
    public function deleteOrganization(int $id): bool
    {
        $result = Helper::curlRequest(
            $this->api . "/api/private/v2.0/organizations/$id",
            $this->header,
            'DELETE',
            []
        );

        if (!$result) {
            return false;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return false;
        }

        return $result['response'] === 'true';
    }

    /**
     * Удаляет цену
     *
     * @param string $eventId Id события
     * @param int $priceId Id цены
     * @return bool
     */
    public function deletePrice(string $eventId, int $priceId): bool
    {
        $result = Helper::curlRequest(
            $this->api . "/api/private/v2.0/event/$eventId/prices/$priceId",
            $this->header,
            'DELETE',
            []
        );

        if (!$result) {
            return false;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return false;
        }

        return $result['response'] === 'true';
    }

    /**
     * Удаляет ценовую категорию.
     *
     * @param int $schemaId Id схемы.
     * @param int $zoneId Id ценовой категории.
     * @return bool
     */
    public function deletePricingZone(int $schemaId, int $zoneId): bool
    {
        $result = Helper::curlRequest(
            $this->api . "/api/private/v2.0/schemas/$schemaId/pricing_zones/$zoneId",
            $this->header,
            'DELETE',
            []
        );

        if (!$result) {
            return false;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return false;
        }

        return $result['response'] === 'true';
    }

    /**
     * Удаляет схему
     *
     * @param int $schemaId Id схемы
     * @return bool
     */
    public function deleteSchema(int $schemaId): bool
    {
        $result = Helper::curlRequest(
            $this->api . "/api/private/v2.0/schemas/$schemaId",
            $this->header,
            'DELETE',
            []
        );

        if (!$result) {
            return false;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return false;
        }

        return $result['response'] === 'true';
    }

    /**
     * Удаляет площадку в SeatMap.
     *
     * @param Venue $venue
     * @return bool|null
     */
    public function deleteVenue(Venue $venue): ?bool
    {
        if (!$venue->getId()) {
            return null;
        }

        $result = Helper::curlRequest(
            $this->api . '/api/private/v2.0/venues/' . $venue->getId(),
            $this->header,
            'DELETE',
            [],
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        return $result['response'] === 'true';
    }

    /**
     * Возвращает событие по id
     * Если id не задан, возвращает массив всех событий
     *
     * @param ?string $id UUID - id события
     * @param int $page Индекс страницы
     * @param int $size Кол-во событий на странице
     * @return ?Event|array{Event}
     */
    public function getEvent(string $id = null, int $page = 0, int $size = 20): null|array|Event
    {
        $params = [];
        foreach (['page', 'size'] as $param) {
            if (isset($$param)) {
                $params[$param] =$$param;
            }
        }

        $result = Helper::curlRequest(
            $this->api . '/api/private/v2.0/events/' . $id ?: '',
            $this->header,
            'GET',
            $id ? [] : $params
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $result = json_decode($result['response'], true);

        if (!$result) {
            return null;
        }

        if ($id) {
            return new Event($result['id'], $result['killAfter'], $result['name'], $result['schemaId'], $result['start']);
        }

        $events = [];
        foreach ($result['content'] as $e) {
            $events[] = new Event($e['id'], $e['killAfter'], $e['name'], $e['schemaId'], $e['start']);
        }

        return $events;
    }

    /**
     * Возвращает список организаций
     *
     * @param int|null $id Id организации
     * @param int $page Индекс страницы
     * @param int $size Кол-во записей на странице
     * @return array
     */
    public function getOrganizations(?int $id = null, int $page = 0, int $size = 20): array
    {
        $params = [];
        foreach (['page', 'size'] as $param) {
            if (isset($$param)) {
                $params[$param] =$$param;
            }
        }

        $result = Helper::curlRequest(
            $this->api . '/api/private/v2.0/organizations/' . $id ?: '',
            $this->header,
            'GET',
            $id ? [] : $params
        );

        if (!$result) {
            return [];
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return [];
        }

        $result = json_decode($result['response'], true);

        $organizations = [];
        foreach ($result['content'] as $org) {
            $organizations[] = [
                'id' => $org['id'],
                'name' => $org['name'],
            ];
        }

        return $organizations;
    }

    /**
     * Возвращает список цен
     *
     * @param string $eventId Id события
     * @param int $priceId Id цены
     * @param int $page Индекс страницы
     * @param int $size Кол-во записей на странице
     * @return array|null
     */
    public function getPrices(string $eventId, int $priceId = 0, int $page = 0, int $size = 20): ?array
    {
        $params = [];
        foreach (['page', 'size'] as $param) {
            if (isset($$param)) {
                $params[$param] =$$param;
            }
        }

        $result = Helper::curlRequest(
            $this->api . "/api/private/v2.0/event/$eventId/prices/" . ($priceId > 0 ? $priceId : ''),
            $this->header,
            'GET',
            $params,
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $result = json_decode($result['response'], true);
        if (!$result) {
            return null;
        }
        if (!$result['content']) {
            return [];
        }

        return $result['content'];
    }

    /**
     * Возвращает ценовые категории для схемы
     *
     * @param int $schemaId Id схемы
     * @param int $zoneId Id ценовой категории
     * @param int $page Индекс страницы
     * @param int $size Кол-во записей на странице
     * @return array|null
     */
    public function getPricingZones(int $schemaId, int $zoneId = 0, int $page = 0, int $size = 20): ?array
    {
        $params = [];
        foreach (['page', 'size'] as $param) {
            if (isset($$param)) {
                $params[$param] =$$param;
            }
        }

        $result = Helper::curlRequest(
            $this->api . "/api/private/v2.0/schemas/$schemaId/pricing_zones/" . ($zoneId > 0 ? $zoneId : ''),
            $this->header,
            'GET',
            $params,
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $result = json_decode($result['response'], true);
        if (!$result) {
            return null;
        }

        $pricing = [];

        foreach ($result as $p) {
            $pricing[$p['id']] = $p['name'];
        }

        return $pricing;
    }

    /**
     * Возвращает схему по id схемы, либо все схемы для площадки, либо все схемы
     *
     * @param ?int $id id схемы
     * @param ?int $venueId Id площадки
     * @param int $page Индекс страницы
     * @param int $size Кол-во записей на странице
     * @return Schema|null|array{Schema}
     */
    public function getSchema(int $id = null, int $venueId = null, int $page = 0, int $size = 20): Schema|array|null
    {
        $params = [];
        if (!$id) {
            foreach (['venueId', 'page', 'size'] as $param) {
               if (isset($$param)) {
                   $params[$param] =$$param;
               }
            }
        }

        $result = Helper::curlRequest(
            $this->api . '/api/private/v2.0/schemas/' . $id ?: '',
            $this->header,
            'GET',
            $id ? [] : $params
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $result = json_decode($result['response'], true);
        if (!$result) {
            return null;
        }

        if ($id) {
            return new Schema($result);
        }

        $schemas = [];

        foreach ($result['content'] as $s) {
            $schemas[] = new Schema($s);
        }

        return $schemas;
    }

    /**
     * Возвращает площадку по id
     * Если id не задан, возвращает массив всех площадок
     *
     * @param ?int $id
     * @param int $page
     * @param int $size
     * @return null|Venue|array{Venue}
     */
    public function getVenue(int $id = null, int $page = 0, int $size = 20): Venue|array|null
    {
        $params = [];
        foreach (['page', 'size'] as $param) {
            if (isset($$param)) {
                $params[$param] =$$param;
            }
        }

        $result = Helper::curlRequest(
            $this->api . '/api/private/v2.0/venues/' . $id ?: '',
            $this->header,
            'GET',
            $id ? [] : $params
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $result = json_decode($result['response'], true);
        if (!$result) {
            return null;
        }

        if ($id) {
            return new Venue($result['address'], $result['draft'], $result['id'], $result['lat'], $result['lng'], $result['name']);
        }

        $venues = [];
        foreach ($result['content'] as $v) {
            $venues[] = new Venue($v['address'], $v['draft'], $v['id'], $v['lat'], $v['lng'], $v['name']);
        }

        return $venues;
    }

    /**
     * Меняет цену
     *
     * @param string $eventId Id события
     * @param int $priceId Id цены
     * @param string $name цена
     * @return array|null
     */
    public function updatePrice(string $eventId, int $priceId, string $name): ?array
    {
        $result = Helper::curlRequest(
            $this->api . "/api/private/v2.0/event/{$eventId}/prices/",
            $this->headerJson,
            'PUT',
            [
                [
                    'id' => $priceId,
                    'name' => $name,
                    'eventId' => $eventId,
                ]
            ],
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $result = json_decode($result['response'], true);
        if (!$result) {
            return null;
        }

        return $result[0];
    }

    /**
     * Сохраняет изменения ценовой категории.
     *
     * @param int $schemaId
     * @param int $zoneId
     * @param string $name
     * @return bool|null
     */
    public function updatePricingZone(int $schemaId, int $zoneId, string $name): ?bool
    {
        $result = Helper::curlRequest(
            $this->api . "/api/private/v2.0/schemas/$schemaId/pricing_zones/",
            $this->headerJson,
            'PUT',
            [
                'id' => $zoneId,
                'name' => $name,
                'schemaId' => $schemaId,
            ],
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $result = json_decode($result['response'], true);
        if (!$result) {
            return null;
        }

        return $result['name'] === $name;
    }

    /**
     * Сохраняет изменения площадки в SeatMap.
     *
     * @param Venue $venue
     * @return Venue|null
     */
    public function updateVenue(Venue $venue): ?Venue
    {
        if (!$venue->getId()) {
            return null;
        }

        $result = Helper::curlRequest(
            $this->api . '/api/private/v2.0/venues/',
            $this->headerJson,
            'PUT',
            $venue->toArray(),
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $result = json_decode($result['response'], true);
        if (!$result) {
            return null;
        }

        return new Venue($result['address'], $result['draft'], $result['id'], $result['lat'], $result['lng'], $result['name']);
    }
}
