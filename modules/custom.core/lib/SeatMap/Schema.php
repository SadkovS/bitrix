<?php

namespace Custom\Core\SeatMap;

class Schema
{
    /** @var bool Архивная схема */
    private bool $archived;

    /** @var string|null Описание */
    private ?string $description;

    /** @var bool Черновик */
    private bool $draft;

    /** @var string|null Внешний id */
    private ?string $externalId;

    /** @var int|null ? */
    private ?int $gaCapacity;

    /** @var int id схемы */
    private int $id;

    /** @var string Название схемы */
    private string $name;

    /** @var string|null UUID схемы */
    private ?string $preview;

    /** @var int|null Кол-во мест */
    private ?int $seatsCapacity;

    /** @var bool шаблон */
    private bool $template;

    /** @var string|null Id события */
    private ?string $eventId;

    /** @var Venue|null Площадка */
    private ?Venue $venue = null;

    /**
     * @param array $settings Параметры схемы
     */
    public function __construct(array $settings)
    {
        if (isset($settings['venue'])) {
            if ($settings['venue'] instanceof Venue) {
                $this->venue = $settings['venue'];
            } elseif (is_array($settings['venue'])) {
                $this->venue = new Venue(
                    $settings['venue']['address'],
                    false,
                    $settings['venue']['id'],
                    $settings['venue']['lat'],
                    $settings['venue']['lng'],
                    $settings['venue']['name']
                );
            }
        }

        foreach ($settings as $key => $value) {
            if ($key === 'venue') {
                continue;
            }
            $this->{$key} = $value;
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getEventId(): ?string
    {
        return $this->eventId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Venue|null
     */
    public function getVenue(): ?Venue
    {
        return $this->venue;
    }

    /**
     * @return int|null
     */
    public function getVenueId(): ?int
    {
        return $this->venue ? $this->venue->getId() : ($this->venueId ?? null);
    }

    public function toArray(): array
    {
        $result = [];

        foreach (get_object_vars($this) as $key => $value) {
            if ($key === 'venue' && is_object($value)) {
                $result['venue'] = $this->venue->toArray();
                continue;
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
