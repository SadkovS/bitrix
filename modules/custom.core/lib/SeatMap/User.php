<?php

namespace Custom\Core\SeatMap;
//namespace SeatMap;

class User
{
    /** @var string Имя */
    private string $firstName;

    /** @var string Фамилия */
    private string $lastName;

    /** @var string e-mail */
    private string $email;

    /** @var string Название организации */
    private string $organizationName;

    /** @var string Тип организации */
    private string $organizationType;

    /** @var array{'ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN', } Роли пользователя */
    private array $roles;

    /** @var int Id организации */
    private int $organizationId;

    /** @var string Public Key */
    private string $publicKey;

    /** @var string|null Private Key */
    private ?string $privateKey = null;

    /** @var string|null Id сессии */
    private ?string $sessionId = null;

    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $organizationName
     * @param string $organizationType
     * @param array $roles
     * @param int $organizationId
     * @param string $publicKey
     * @param string|null $privateKey
     * @param string|null $sessionId
     */
    public function __construct(
        string $firstName,
        string $lastName,
        string $email,
        string $organizationName,
        string $organizationType,
        array $roles,
        int $organizationId,
        string $publicKey,
        ?string $privateKey = null,
        ?string $sessionId = null)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->organizationName = $organizationName;
        $this->organizationType = $organizationType;
        $this->roles = $roles;
        $this->organizationId = $organizationId;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->sessionId = $sessionId;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }
}
