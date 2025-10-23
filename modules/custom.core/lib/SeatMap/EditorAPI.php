<?php

namespace Custom\Core\SeatMap;

use Exception;

class EditorAPI
{
    /** @var string SeatMap editor API URL */
    private string $api;

    /** @var string Логин доступа к API */
    private string $login;

    /** @var string Пароль доступа к API */
    private string $password;

    /** @var string Логин доступа к API */
    private string $publicKey;

    /** @var string Пароль доступа к API */
    private string $privateKey;

    /** @var string|null OAuth 2.0 JWT токен, полученный после авторизации */
    private ?string $jwt = null;

    /** @var int|null Unixtime окончания действия полученного ранее токена авторизации */
    private int|null $jwtExpire = null;

    /** @var string|null Авторизационный токен организации */
    private string|null $organizationToken = null;

    /** @var string|null Авторизационный токен пользователя */
    private string|null $tenantToken = null;

    /**
     * @param string $editorApi editor API URL
     * @param string $login Имя пользователя для доступа к API
     * @param string $password Пароль доступа к API
     * @throws Exception
     */
    public function __construct(
        string $editorApi,
        string $login,
        string $password
    )
    {
        if (empty($editorApi)) {
            throw new Exception('Не задан editor API URL');
        }
        $this->api = $editorApi;

        if (empty($login) || empty($password)) {
            throw new Exception('Не заданы логин или пароль');
        }

        $this->login = $login;
        $this->password = $password;

        if (!$this->login()) {
            throw new Exception('Не удалось авторизоваться');
        }
    }

    /**
     * Авторизует и возвращает sessionId для открытия редактора схем.
     *
     * @param array $user Параметры пользователя для авторизации.
     * @return array|null
     */
    public function autologin(array $user): ?array
    {
        if (!$this->login()) {
            return null;
        }

        $result = Helper::curlRequest(
            $this->api . '/api/auth/autologin',
            [
                'Content-Type: application/json',
                "Authorization: Bearer $this->jwt",
            ],
            'POST',
            [
                'login' =>  $user['email'],
                'token' => $user['privateKey'],
            ]
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $result = json_decode($result['response'], true);

        if (!$result || !isset($result['sessionId'])) {
            return null;
        }

        return [
            'token' => $result['token'],
            'refreshToken' => $result['refreshToken'],
            'sessionId' => $result['sessionId'],
        ];
    }

    /**
     * Клонирует схему
     *
     * @param int $venueId Id площадки
     * @param int $schemaId Id исходной схемы
     * @param string $name Название новой схемы
     * @return Schema|null
     */
    public function copySchema(
        int $venueId,
        int $schemaId,
        string $name
    ): ?Schema
    {
        if (!$this->login()) {
            return null;
        }

        $result = Helper::curlRequest(
            $this->api . "/api/venues/$venueId/schemas/$schemaId",
            [
                'Content-Type: application/json',
                "Authorization: Bearer $this->jwt",
            ],
            'POST',
            [
                'name' => $name,
            ]
        );

        if (!$result) {
            return null;
        }

        $result = json_decode($result['response'], true);
        if (!$result || !isset($result['id'])) {
            return null;
        }

        return new Schema($result);
    }

    /**
     * Создаёт организацию.
     *
     * @param string $orgName Название организации
     * @param string $email e-mail (логин)
     * @param string $password пароль
     * @return array|null
     */
    public function createOrganization(string $orgName, string $email, string $password): ?array
    {
        if (!isset($orgName, $email, $password)) {
            return null;
        }

        $publicKey = Helper::generateGUID();
        list(, $domain) = explode('@', $email);

        $result = Helper::curlRequest(
            $this->api . '/api/admin/organizations/',
            [
                'Content-Type: application/json',
                "Authorization: Bearer $this->jwt",
            ],
            'POST',
            [
                'id' => 0,
                'name' => $orgName,
                'publicKey' => $publicKey,
                'type' => 'DEFAULT',
                'domain' => $domain,
                'autologinEnabled' => true,
                'appendDomainToLogin' => true,
                'user' => [
                    'firstName' => 'Admin',
                    'lastName' => $orgName,
                    'email' => $email,
                    'password' => $password,
                ]
            ]
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
     * Создаёт пользователя
     *
     * @param string $email e-mail (логин)
     * @param string $firstName Имя
     * @param string $lastName Фамилия
     * @param string $organizationName Название организации
     * @param string $password Пароль
     * @return mixed|null
     */
    public function createUser(
        string $email,
        string $firstName,
        string $lastName,
        string $organizationName,
        string $password
    )
    {
        if (!$this->login()) {
            return null;
        }

        $result = Helper::curlRequest(
            $this->api . '/api/organization/',
            [
                'Content-Type: application/json',
                "Authorization: Bearer $this->jwt",
            ],
            'POST',
            [
                'name' => $organizationName,
                'type' => 'DEFAULT',
                'autologinEnabled' => true,
                'user' => [
                    'email' =>  $email,
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'password' => $password,
                ]
            ]
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

        return $result;
    }

    /**
     * Создаёт новую схему
     *
     * @param int $venueId Id площадки
     * @param string $name Название схемы
     * @param int $orgId Id организации
     * @param bool $forceEventCreation Создавать ли Event при создании схемы
     * @param ?string $description Описание
     * @return Schema|null
     */
    public function createSchema(
        int $venueId,
        string $name,
        int $orgId,
        bool $forceEventCreation = false,
        ?string $description = null
    ): ?Schema
    {
        if (!$this->login()) {
            return null;
        }

        $result = Helper::curlRequest(
            $this->api . "/api/venues/$venueId/schemas/?forceEventCreation="
            . ($forceEventCreation ? 'true' : 'false') . "&orgId=$orgId",
            [
                'Content-Type: application/json',
                "Authorization: Bearer $this->jwt",
            ],
            'POST',
            [
                'name' => $name,
                'template' => false,
                'draft' => !$forceEventCreation,
                'venue' => [
                    'id' => $venueId,
                ],
                'description' => $description,
            ]
        );

        if (!$result) {
            return null;
        }

        $result = json_decode($result['response'], true);
        if (!$result || !isset($result['id'])) {
            return null;
        }

        return new Schema($result);
    }

    /**
     * Возвращает список с привязкой цен к местам.
     *
     * @param string $eventId
     * @return array
     */
    public function getAssignment(string $eventId): array
    {
        if (!$this->login()) {
            return [];
        }

        $result = Helper::curlRequest(
            $this->api . "/api/events/{$eventId}/assignment/",
            [
                "Authorization: Bearer $this->jwt",
            ],
            'GET',
            []
        );

        if (!$result) {
            return [];
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return [];
        }

        return json_decode($result['response'], true);
    }

    /**
     * Возвращает общие данные схемы
     *
     * @param int $venueId Id площадки
     * @param int|null $schemaId Id схемы
     * @return Schema
     */
    public function getSchema(int $venueId, int $schemaId = null): Schema
    {
        if (!$this->login()) {
            return [];
        }

        $result = Helper::curlRequest(
            $this->api . "/api/venues/{$venueId}/schemas/" . ($schemaId ?: ''),
            [
                "Authorization: Bearer $this->jwt",
            ],
            'GET',
            []
        );

        if (!$result) {
            return [];
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return [];
        }

        return new Schema(json_decode($result['response'], true));
    }

    /**
     * Возвращает подробные данные схемы. Места, секторы, ряды, фигуры.
     *
     * @param int|null $schemaId Id схемы
     * @return array
     */
    public function getSchemaData(int $schemaId = null): array
    {
        if (!$this->login()) {
            return [];
        }

        $result = Helper::curlRequest(
            $this->api . "/api/seatmap/{$schemaId}/",
            [
                "Authorization: Bearer $this->jwt",
            ],
            'GET',
            []
        );

        if (!$result) {
            return [];
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return [];
        }

        return json_decode($result['response'], true);
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
        if (!$this->login()) {
            return [];
        }

        $params = [];
        foreach (['page', 'size'] as $param) {
            if (isset($$param)) {
                $params[$param] =$$param;
            }
        }

        $result = Helper::curlRequest(
            $this->api . '/api/admin/organizations/'. $id ?: '',
            [
                "Authorization: Bearer $this->jwt",
            ],
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
     * Возвращает токен организации
     *
     * @return string|null
     */
    public function getOrganizationToken(): ?string
    {
        return $this->organizationToken;
    }

    /**
     * Возвращает список всех зарегистрированных пользователей
     *
     * @return User|array{User}|null
     */
    public function getRegistrations(): User|array|null
    {
        if (!$this->login()) {
            return null;
        }

        $result = Helper::curlRequest(
            $this->api . '/api/admin/registrations/',
            [
                'Content-Type: application/json',
                "Authorization: Bearer $this->jwt",
            ],
            'GET',
            []
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $users = [];
        $result = json_decode($result['response'], true);

        if (!$result) {
            return null;
        }

        foreach ($result as $user) {
            $users[] = [
                'firstName' => $user['firstName'],
                'lastName' => $user['lastName'],
                'email' => $user['email'],
                'organizationName' => $user['organizationName'],
                'registrationDate' => $user['registrationDate'],
            ];
        }

        return $users;
    }

    /**
     * Возвращает токен пользователя
     *
     * @return string|null
     */
    public function getTenantToken(): ?string
    {
        return $this->tenantToken;
    }

    /**
     * Возвращает список пользователей
     *
     * @return User|array{User}|null
     */
    public function getUser(): User|array|null
    {
        if (!$this->login()) {
            return null;
        }

        $result = Helper::curlRequest(
            $this->api . '/api/organization/users/',
            [
                'Content-Type: application/json',
                "Authorization: Bearer $this->jwt",
            ],
            'GET',
            []
        );

        if (!$result) {
            return null;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return null;
        }

        $users = [];
        $result = json_decode($result['response'], true);

        if (!$result) {
            return null;
        }

        foreach ($result as $user) {
            $users[] = new User(
                $user['firstName'],
                $user['lastName'],
                $user['email'],
                $user['organizationName'],
                $user['organizationType'] ?? '',
                $user['roles'],
                $user['organizationId'],
                $user['publicKey']
            );
        }

        return $users;
    }

    /**
     * Выполняет авторизацию (получение токенов) в SeatMap.
     *
     * @return bool
     */
    private function login(): bool
    {
        if ($this->jwt && $this->jwtExpire && $this->jwtExpire > time()) {
            return true;
        }

        $result = Helper::curlRequest(
            $this->api . '/api/auth/login',
            [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->login . ':' . $this->password),
            ],
            'POST',
            [
                'username' => $this->login,
                'password' => $this->password,
            ],
        );

        if (!$result) {
            return false;
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return false;
        }

        $result = json_decode($result['response'], true);

        if (!isset($result['success']) || !$result['success'] || !isset($result['token'])) {
            return false;
        }

        $this->organizationToken = $result['user']['organizationToken'];
        $this->tenantToken = $result['user']['tenantToken'];
        $this->jwt = $result['token'];
        $this->jwtExpire = time() + 600; // 5 минут

        return true;
    }

    /**
     * Переводит схему в "опубликовано".
     *
     * @param int|Schema $schema Схема или id схемы
     * @param int|null $venueId Id площадки
     * @return Schema|null
     */
    public function publishSchema(int|Schema $schema, ?int $venueId = null): ?Schema
    {
        if (!$this->login()) {
            return null;
        }

        if (is_int($schema)) {
            if (!$venueId) {
                return null;
            }
            $schema = $this->getSchema($venueId, $schema);
        }

        if (!$venueId) {
            $venueId = $schema->getVenueId();
        }
        if (!$venueId) {
            return null;
        }

        $arSchema = $schema->toArray();
        $arSchema['draft'] = false;
        $arSchema['template'] = false;

        $result = Helper::curlRequest(
            $this->api . "/api/venues/$venueId/schemas/?forceEventCreation=false",
            [
                'Content-Type: application/json',
                "Authorization: Bearer $this->jwt",
            ],
            'PUT',
            $arSchema,
        );

        if (!$result) {
            return null;
        }

        $result = json_decode($result['response'], true);
        if (!$result || !isset($result['id'])) {
            return null;
        }

        return new Schema($result);
    }

    /**
     * Назначает цены на места.
     *
     * @param string $eventId Id события
     * @param array $assignment Список мест и ga-секторов с привязанными ценами
     * @return array
     */
    public function putAssignment(string $eventId, array $assignment): array
    {
        if (!$this->login()) {
            return [];
        }

        $result = Helper::curlRequest(
            $this->api . "/api/events/{$eventId}/assignment/",
            [
                'Content-Type: application/json',
                "Authorization: Bearer $this->jwt",
            ],
            'PUT',
            $assignment
        );

        if (!$result) {
            return [];
        }

        if ($result['status'] !== 'success' || $result['code'] !== 200) {
            return [];
        }

        return json_decode($result['response'], true);
    }
}
