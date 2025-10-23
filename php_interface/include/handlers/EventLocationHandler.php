<?php

namespace Local\PhpInterface\Handlers;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\ORM;
use Bitrix\Main\Loader;
use Custom\Core\Events\EventsDateAndLocationTable;
use Custom\Core\Traits\DateTimeFormatedTrait;

Loader::includeModule('iblock');


class EventLocationHandler {
    use DateTimeFormatedTrait;

    protected $eventID;
    protected $duration;
    protected $serverName;

    public function __construct($eventID = 0)
    {
        $this->eventID = $eventID;
        $this->serverName = 'https://' . Option::get('main', 'server_name', '');
    }

    private function getMaxLocationDateByEventID($eventId = 0)
    {
        $query = new ORM\Query\Query('\Custom\Core\Events\EventsDateAndLocationTable');

        $objDate = $query
            ->setFilter(['UF_EVENT_ID' => $eventId])
            ->setSelect(['LOCATION_DATE', 'UF_DURATION'])
            ->setOrder(['LOCATION_DATES_REF.VALUE' => 'DESC'])
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'LOCATION_DATES_REF',
                    'Custom\Core\Events\EventsDateAndLocationUfDateTimeTable',
                    ['=this.ID' => 'ref.ID'],
                    ['join_type' => 'inner']
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ExpressionField(
                    'LOCATION_DATE',
                    "MAX(%s)",
                    ['LOCATION_DATES_REF.VALUE']
                ),
            )
            ->setLimit(1)
            ->exec();
        $res     = $objDate->fetch();

        $maxDate        = $res['LOCATION_DATE'];
        $this->duration = (int)$res['UF_DURATION'];
        if (!is_object($maxDate)) return false;

        return $maxDate;
    }

    public function setProductExpDate()
    {
        $maxDate = $this->getMaxLocationDateByEventID($this->eventID);

        if (!is_object($maxDate)) return false;

        $expDate = $this->makeExpDate($maxDate);
        $productEntity = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
        $productClass  = $productEntity->getDataClass();
        $query         = new ORM\Query\Query($productEntity);
        $resProduct    = $query
            ->setSelect(['ID', 'ACTIVE_TO'])
            ->setFilter(['EVENT_ID.VALUE' => $this->eventID])
            ->countTotal(true)
            ->exec();
        if ($resProduct->getCount() > 0) {
            $objProduct = $resProduct->fetchObject();
            $objProduct->set('ACTIVE_TO', $expDate);
            $objProduct->save();
        }
    }

    public function setDuration($duration)
    {
        $this->duration = $duration;
        return $this;
    }

    private function makeExpDate($maxDate): string
    {
        $date = new \DateTime($maxDate);
        $date = $date->modify('+' . ((int)$this->duration) . ' minutes')->format('d.m.Y H:i:s');

        return $date;
    }
}