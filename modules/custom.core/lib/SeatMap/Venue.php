<?php

namespace Custom\Core\SeatMap;
//namespace SeatMap;

class Venue
{
    /** @var string Адрес площадки */
    private string $address;

    /** @var bool Признак черновика */
    private bool $draft;

    /** @var int Id */
    private int $id;

    /** @var float Широта */
    private float $lat;

    /** @var float Долгота */
    private float $lng;

    /** @var string Название площадки */
    private string $name;

    /**
     * @param string $address Адрес
     * @param bool $draft Признак черновика
     * @param int $id Id
     * @param float $lat Широта
     * @param float $lng Долгота
     * @param string $name Название
     */
    public function __construct(string $address, bool $draft, int $id, float $lat, float $lng, string $name)
    {
        $this->address = $address;
        $this->draft = $draft;
        $this->id = $id;
        $this->lat = $lat;
        $this->lng = $lng;
        $this->name = $name;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function toArray(): array
    {
        if ($this->id) {
            return [
                'address' => $this->address,
                'draft' => $this->draft,
                'id' => $this->id,
                'lat' => $this->lat,
                'lng' => $this->lng,
                'name' => $this->name,
            ];
        }

        return [
            'address' => $this->address,
            'draft' => $this->draft,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'name' => $this->name,
        ];
    }
}
