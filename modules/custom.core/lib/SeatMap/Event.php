<?php

namespace Custom\Core\SeatMap;
// namespace SeatMap;

class Event
{
    /** @var string UUID - id события */
    private string $id;

    /** @var string Дата-время, после которого ?? */
    private string $killAfter;

    /** @var string Название события */
    private string $name;

    /** @var int Id схемы */
    private int $schemaId;

    /** @var string Дата-время начала события */
    private string $start;

    /**
     * @param string $id UUID - id события
     * @param string $killAfter Дата-время ???
     * @param string $name Название события
     * @param int $schemaId id схемы
     * @param string $start Дата-время начала события
     */
    public function __construct(string $id, string $killAfter, string $name, int $schemaId, string $start)
    {
        $this->id = $id;
        $this->killAfter = $killAfter;
        $this->name = $name;
        $this->schemaId = $schemaId;
        $this->start = $start;
    }

    /**
     * @return string
     */
    public function getId(): string
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

    /**
     * @return int
     */
    public function getSchemaId(): int
    {
        return $this->schemaId;
    }
}