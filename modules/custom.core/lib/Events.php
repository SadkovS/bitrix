<?php

namespace Custom\Core;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\Validator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ORM;
use Bitrix\Main\Config\Option;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\SystemException;

Loc::loadMessages(__FILE__);

class Events {
    const statusLockChange = ["completed", "cancelled"];

    public static function getIntervalDates(&$item, $event_id)
    {
        $datesEntity = new ORM\Query\Query('\Custom\Core\Events\EventsDateAndLocationTable');
        $query       = $datesEntity
            ->setSelect(["*"])
            ->setFilter(['UF_EVENT_ID' => $event_id])
            ->exec();


        $arDates = [];
        while ($obDate = $query->fetch()) {
            $datesArray = unserialize($obDate["UF_DATE_TIME"]);

            foreach ($datesArray as $date) {
                $objDateTime = new \DateTime($date);

                $dateStart = $objDateTime->format('d.m.Y');
                $timeStart = $objDateTime->format('H:i');

                if ($obDate['UF_DURATION']) {
                    $dateEnd = $objDateTime->modify("+{$obDate['UF_DURATION']} minutes");
                    $timeEnd = $dateEnd->format('H:i');
                }

                $arDates[$dateStart] = $timeStart . " - " . $timeEnd;
            }
        }

        if ($arDates) {
            $date_keys = array_keys($arDates);

            usort(
                $date_keys, function ($date1, $date2) {
                $date_1 = new \DateTime($date1);
                $date_2 = new \DateTime($date2);
                if ($date_1 < $date_2) return -1;
                else if ($date_1 > $date_2) return 1;
                return 0;
            }
            );

            $rearranged_data = [];
            foreach ($date_keys as $each_key) {
                $rearranged_data[$each_key] = $arDates[$each_key];
            }

            $arDates = $rearranged_data;

            $resultDates = [];
            $i           = 0;
            foreach ($arDates as $date => $time) {
                if (!$resultDates) {
                    $resultDates[$i][] = ["time" => $time, "date" => $date];
                } else {
                    if ($resultDates[$i][count($resultDates[$i]) - 1]["time"] == $time) {
                        $resultDates[$i][] = ["time" => $time, "date" => $date];
                    } else {
                        $i++;
                        $resultDates[$i][] = ["time" => $time, "date" => $date];
                    }
                }
            }

            $resultGroup = [];
            foreach ($resultDates as $group) {
                if (count($group) > 1) {
                    $dateStart = \Custom\Core\Helper::rus_date('j F Y', (new \DateTime($group[0]["date"]))->getTimestamp());
                    $dateEnd   = \Custom\Core\Helper::rus_date('j F Y', (new \DateTime($group[count($group) - 1]["date"]))->getTimestamp());

                    $resultGroup[] = [
                        "date"            => $dateStart . " - " . $dateEnd,
                        "time"            => $group[0]["time"],
                        "timestamp_start" => (new \DateTime($group[0]["date"]))->getTimestamp(),
                        "date_start"      => $dateStart,
                        "timestamp_end"   => (new \DateTime($group[count($group) - 1]["date"]))->getTimestamp(),
                        "date_end"        => $dateEnd,
                    ];
                } else {
                    $dateStart = \Custom\Core\Helper::rus_date('j F Y', (new \DateTime($group[0]["date"]))->getTimestamp());

                    $resultGroup[] = [
                        "date"            => $dateStart,
                        "time"            => $group[0]["time"],
                        "timestamp_start" => (new \DateTime($group[0]["date"]))->getTimestamp(),
                        "date_start"      => $dateStart,
                    ];
                }
            }

            $item["DATES_GROUP"] = $resultGroup;

            $start = $date_keys[0];
            $end   = $date_keys[count($date_keys) - 1];

            if ($start != $end) {
                $arStart = explode(".", $start);
                $arEnd   = explode(".", $end);

                $maskStart = 'j F Y';
                if ($arStart[2] == $arEnd[2]) $maskStart = 'j F';
                if ($arStart[2] == $arEnd[2] && $arStart[1] == $arEnd[1]) $maskStart = 'j';

                $item["DATES_GROUP_STR"] = \Custom\Core\Helper::rus_date($maskStart, (new \DateTime($start))->getTimestamp()) . " - " . \Custom\Core\Helper::rus_date('j F Y', (new \DateTime($end))->getTimestamp());
            } else {
                //$item["DATES_GROUP_STR"] = \Custom\Core\Helper::rus_date('j F Y', (new \DateTime($start))->getTimestamp());
            }
        }
    }

    public static function getEventIdByProduct($productId = null)
    {
        $eventId = null;

        if ($productId) {
            $res = \Bitrix\Iblock\Elements\ElementTicketsTable::getList(
                [
                    'select' => ['ID', 'EVENT_ID_' => 'EVENT_ID'],
                    'filter' => ['ID' => $productId],
                ]
            );

            if ($arItem = $res->fetch()) {
                $eventId = (int)$arItem["EVENT_ID_VALUE"];
            }
        }

        return $eventId;
    }

    public static function getOrganizatorEventsByProductId($id = null)
    {
        $productEntity     = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
        $propFieldEventID  = $productEntity->getField('EVENT_ID');
        $propEventIDEntity = $propFieldEventID->getRefEntity();

        $eventEntity = new ORM\Query\Query('\Bitrix\Iblock\ElementTable');
        $query       = $eventEntity
            ->setSelect(
                [
                    'EVENT_ID'      => 'EVENT_PROP.VALUE',
                    'COMPANY_ID'    => 'EVENT_ORG.UF_COMPANY_ID',
                    'EVENT_OVER_ID' => 'EVENT.ID',
                    'PRODUCT_ID'    => 'PRODUCT.IBLOCK_ELEMENT_ID',
                ]
            )
            ->setFilter(['ID' => $id])
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'EVENT_PROP',
                    $propEventIDEntity,
                    ['this.ID' => 'ref.IBLOCK_ELEMENT_ID'],
                    ['join_type' => 'LEFT'],
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'EVENT_ORG',
                    'Custom\Core\Events\EventsTable',
                    ['this.EVENT_ID' => 'ref.ID'],
                    ['join_type' => 'LEFT'],
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'EVENT',
                    'Custom\Core\Events\EventsTable',
                    ['this.COMPANY_ID' => 'ref.UF_COMPANY_ID'],
                    ['join_type' => 'LEFT'],
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'PRODUCT',
                    $propEventIDEntity,
                    ['this.EVENT_OVER_ID' => 'ref.VALUE'],
                    ['join_type' => 'LEFT'],
                )
            )
            ->exec();

        $eventId = [];
        while ($event = $query->fetch()) {
            $eventId[] = $event["PRODUCT_ID"];
        }

        return $eventId;
    }

    public static function getEventData($event_id = null, &$item = null, $is_detail = null)
    {
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheId = 'event_' . $event_id;
        $cacheDir = '/events/';
        $cacheTime = 60;
        $cacheTags = ['event_' . $event_id];

        if ($cache->startDataCache($cacheTime, $cacheId, $cacheDir, $cacheTags) || $is_detail) {
            $productEntity = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
            $propFieldEventID = $productEntity->getField('EVENT_ID');
            $propEventIDEntity = $propFieldEventID->getRefEntity();
            $propFieldClosed = $productEntity->getField('IS_CLOSED_EVENT');
            $propFieldClosedEntity = $propFieldClosed->getRefEntity();

            $eventEntity = new ORM\Query\Query('Custom\Core\Events\EventsTable');
            $query = $eventEntity
                ->setSelect(
                    [
                        '*',
                        'ID',
                        'UF_UUID',
                        'UF_NAME',
                        'UF_DESCRIPTION',
                        'IMG_SRC',
                        'UF_QUESTIONNAIRE_FIELDS',
                        'UF_CREATED_BY',
                        'UF_COMPANY_ID',
                        'AGE'          => 'AGE_LIMIT.XML_ID',
                        'EVENT_TYPE'   => 'TYPE.XML_ID',
                        'CATEGORY_'    => 'CATEGORY',
                        'LOCATION_'    => 'LOCATION',
                        'ICON_'        => 'ICON',
                        'PRODUCT_ID'   => 'PRODUCT.IBLOCK_ELEMENT_ID',
                        'IS_CLOSED'    => 'EVENT_CLOSED.VALUE',
                        'COMPANY_NAME' => 'COMPANY.UF_NAME',
                        'COMPANY_ADDRESS' => 'COMPANY.UF_REGISTRATION_ADDRESS',
                        'COMPANY_LOGO_ID' => 'COMPANY.UF_LOGO',
                        'UF_RESERVATION_VALIDITY_PERIOD',
                        'COMPANY_LOGO_SRC'
                    ]
                )
                ->setFilter(['ID' => $event_id])
                ->registerRuntimeField(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'CATEGORY',
                        'Custom\Core\Events\EventsCategoryTable',
                        ['=this.UF_CATEGORY' => 'ref.ID'],
                        ['join_type' => 'left']
                    )
                )
                ->registerRuntimeField(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'ICON',
                        'Custom\Core\FieldEnumTable',
                        ['=this.CATEGORY_UF_ICON' => 'ref.ID'],
                        ['join_type' => 'left']
                    )
                )
                ->registerRuntimeField(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'LOCATION',
                        'Custom\Core\Events\EventsDateAndLocationTable',
                        ['=this.ID' => 'ref.UF_EVENT_ID'],
                        ['join_type' => 'left']
                    )
                )
                ->registerRuntimeField(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'AGE_LIMIT',
                        'Custom\Core\FieldEnumTable',
                        ['=this.UF_AGE_LIMIT' => 'ref.ID'],
                        ['join_type' => 'left']
                    )
                )
                ->registerRuntimeField(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'TYPE',
                        'Custom\Core\FieldEnumTable',
                        ['=this.UF_TYPE' => 'ref.ID'],
                        ['join_type' => 'left']
                    )
                )
                ->registerRuntimeField(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PRODUCT',
                        $propEventIDEntity,
                        ['this.ID' => 'ref.VALUE'],
                        ['join_type' => 'LEFT'],
                    )
                )
                ->registerRuntimeField(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'EVENT_CLOSED',
                        $propFieldClosedEntity,
                        ['this.PRODUCT_ID' => 'ref.IBLOCK_ELEMENT_ID'],
                        ['join_type' => 'LEFT'],
                    )
                )
                ->registerRuntimeField(
                    new \Bitrix\Main\Entity\ReferenceField(
                        'COMPANY',
                        '\Custom\Core\Users\CompaniesTable',
                        ['this.UF_COMPANY_ID' => 'ref.ID'],
                        ['join_type' => 'LEFT'],
                    )
                )
                ->registerRuntimeField(
                    'COMPANY_LOGO_PICTURE',
                    [
                        'data_type' => '\Bitrix\Main\FileTable',
                        'reference' => ['=this.COMPANY_LOGO_ID' => 'ref.ID'],
                        'join_type' => 'LEFT'
                    ]
                )
                ->registerRuntimeField(
                    'COMPANY_LOGO_SRC',
                    new \Bitrix\Main\Entity\ExpressionField(
                        'COMPANY_LOGO_SRC',
                        'CONCAT("/upload/",%s, "/", %s)',
                        ['COMPANY_LOGO_PICTURE.SUBDIR', 'COMPANY_LOGO_PICTURE.FILE_NAME']
                    )
                )
                ->exec();
            $event = $query->fetch();

            self::getIntervalDates($event, $event_id);

            $cache->endDataCache($event);
        }
        else
        {
            $event = $cache->getVars();
        }

        $item['DATES_GROUP'] = $event['DATES_GROUP'];
        $item['DATES_GROUP_STR'] = $event['DATES_GROUP_STR'];
        $item['UF_RESERVATION_VALIDITY_PERIOD'] = $event['UF_RESERVATION_VALIDITY_PERIOD'];
        $item['EVENT_ID'] = $event['ID'];
        $item['EVENT_XML_ID'] = $event['UF_XML_ID'];
        $item['CREATED_BY'] = $event['UF_CREATED_BY'];
        $item['COMPANY_ID'] = $event['UF_COMPANY_ID'];
        $item['COMPANY_NAME'] = $event['COMPANY_NAME'];
        $item['COMPANY_LOGO_SRC'] = $event['COMPANY_LOGO_SRC'];
        $item['DETAIL_PICTURE'] = $event['IMG_SRC'];
        $item['DETAIL_PICTURE_ID'] = $event['UF_IMG'];
        $item['NAME'] = $event['UF_NAME'];
        $item['DETAIL_TEXT'] = $event['UF_DESCRIPTION'];
        $item['IS_CLOSED'] = $event['IS_CLOSED'] > 0;
        $item['QUESTIONNAIRE_ACTIVE'] = $event['UF_QUESTIONNAIRE_ACTIVE'];
        $item['QUESTIONNAIRE_DESCRIPTION'] = $event['UF_QUESTIONNAIRE_DESCRIPTION'];
        $item['QUESTIONNAIRE_FOREACH_TICKETS'] = $event['UF_QUESTIONNAIRE_FOREACH_TICKETS'];

        if ($event['UF_FILES']) {
            $item['EVENT_FILES'] = \Bitrix\Main\FileTable::getList(
                [
                    'select' => [
                        'ID',
                        'ORIGINAL_NAME',
                        new \Bitrix\Main\Entity\ExpressionField(
                            'PATH',
                            'CONCAT("/upload/", %s, "/", %s)',
                            ['SUBDIR', 'FILE_NAME']
                        ),
                    ],
                    'filter' => ['=ID' => $event['UF_FILES']],
                ]
            )->fetchAll();
        }

        $item['CATEGORY_ID']   = $event['CATEGORY_ID'];
        $item['CATEGORY_NAME'] = $event['CATEGORY_UF_NAME'];
        $item['CATEGORY_SORT'] = $event['CATEGORY_UF_SORT'];
        $item['CATEGORY_ICON'] = $event['ICON_XML_ID'];

        if ($event['CATEGORY_UF_CODE'])
            $item['CATEGORY_URL'] = str_replace("#CATEGORY_CODE#", $event['CATEGORY_UF_CODE'], CATEGORY_EVENTS_URL);
        $item['UF_DATE_TIME'] = unserialize($event['LOCATION_UF_DATE_TIME']);
        $objDateTime = (new \DateTime($item['UF_DATE_TIME'][0]));

        $item['DATE_TIME'] =
            [
                'DATE'    => $objDateTime->format('d.m.Y'),
                'DATE_RU' => \Custom\Core\Helper::rus_date('j F Y, D', $objDateTime->getTimestamp()),
                'TIME'    => $objDateTime->format('H:i'),
            ];

        if (date("d.m.Y") != $objDateTime->format('d.m.Y')) {
            $item['DATE_TIME']['DATE_RU_SHORT'] = $item['DATE_TIME']["DATE_RU"];
        } else {
            $item['DATE_TIME']['DATE_RU_SHORT'] = "Сегодня";
        }

        if ($event['LOCATION_UF_DURATION']) {
            $dateEnd                          = $objDateTime->modify("+{$event['LOCATION_UF_DURATION']} minutes");
            $item['DATE_TIME']['DATE_END']    = $dateEnd->format('d.m.Y');
            $item['DATE_TIME']['DATE_END_RU'] = \Custom\Core\Helper::rus_date('j F, D', $dateEnd->getTimestamp());
            $item['DATE_TIME']['TIME_END']    = $dateEnd->format('H:i');
        }

        $item['LOCATION_COORDINATES'] = $event['LOCATION_UF_COORDINATES'];
        $item['LOCATION_ROOM']        = $event['LOCATION_UF_ROOM'];

        $item['LOCATION_ADDRESS_FULL'] = $event['LOCATION_UF_ADDRESS'];
        if (strpos($event['LOCATION_UF_ADDRESS'], "|") !== false) {
            $arAddress = explode("|", $event['LOCATION_UF_ADDRESS']);
            if ($arAddress) {
                $item['LOCATION_NAME']    = $arAddress[0];
                $item['LOCATION_ADDRESS'] = $arAddress[1];
            }
        }

        $item['AGE']        = $event['AGE'];
        $item['EVENT_TYPE'] = $event['EVENT_TYPE'];

        if (is_array($event['UF_QUESTIONNAIRE_FIELDS']) && empty($event['UF_QUESTIONNAIRE_FIELDS'][0]))
            $item['UF_QUESTIONNAIRE_FIELDS'] = $event['UF_QUESTIONNAIRE_FIELDS'];
        else
            $item['UF_QUESTIONNAIRE_FIELDS'] = json_decode($event['UF_QUESTIONNAIRE_FIELDS'][0], true);

        $hours            = floor($event['LOCATION_UF_DURATION'] / 60);
        $minutes          = $event['LOCATION_UF_DURATION'] % 60;
        $item['DURATION'] =
            \Custom\Core\Helper::declination($hours, ['час', 'часа', 'часов']) . ' ' .
            \Custom\Core\Helper::declination($minutes, ['минута', 'минуты', 'минут']);

        foreach ($item['OFFERS'] as $key => $offer) {
            foreach ($offer['ITEM_PRICES'] as $price) {
                if (!is_numeric($item['MIN_PRICE']) || $price["PRICE"] < $item['MIN_PRICE']) {
                    $item['MIN_PRICE'] = $price["PRICE"];
                }
            }
        }

        self::getIntervalDates($item, $event_id);

        if ($is_detail) {
            $item['SIT_MAP'] = $event['UF_SIT_MAP'];

            try {
                $item['SeatMap'] = self::getSeatMapData($event_id);
            } catch (ObjectPropertyException|ArgumentException|SystemException $e) {
                $item['SeatMap']['error'] = $e->getMessage();
            }

        }
    }

    /**
     * Извлекает из базы данные SeatMap для данного мероприятия.
     *
     * @param int $eventId Id события
     *
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    public static function getSeatMapData(int $eventId): array
    {
        if (!$eventId) {
            return [];
        }

        $entitySchema   = HL\HighloadBlockTable::compileEntity('SeatMapSchemas');
        $hlbClassSchema = $entitySchema->getDataClass();
        $hlSchema       = $hlbClassSchema::getList(
            [
                'select' => [
                    'UF_SCHEMA_ID',
                    'UF_EVENT_ID',
                    'UF_ORG_ID',
                    'UF_VENUE_ID',
                ],
                'filter' => [
                    'UF_F_EVENT_ID' => $eventId,
                ],
                'limit'  => 1,
            ]
        );
        $resSchema      = $hlSchema->fetch();

        if (!$resSchema) {
            return [];
        }

        $barcodeIds = \Bitrix\Sale\BasketPropertiesCollection::getList(
            [
                'select'  => ['VALUE'],
                'filter'  => [
                    '=BASKET.FUSER_ID' => \Bitrix\Sale\Fuser::getId(),
                    '=BASKET.ORDER_ID' => null,
                    '=CODE'            => "BARCODE",
                ],
                'runtime' => [
                    new \Bitrix\Main\Entity\ReferenceField(
                        'BASKET',
                        '\Bitrix\Sale\Internals\BasketTable',
                        ['this.BASKET_ID' => 'ref.ID'],
                        ['join_type' => 'LEFT'],
                    ),
                ]
            ]
        )->fetchAll();;

        $barcodes = \Custom\Core\Tickets\BarcodesTable::getList(
            [
                'select'  => [
                    'ID',
                    'UF_SEATMAP_ID',
                ],
                'filter'  => [
                    'UF_EVENT_ID' => $eventId,
                    [
                        "LOGIC" => "AND",
                        [
                            "LOGIC" => "OR",
                            ["STATUS.XML_ID" => "sold"],
                            ["STATUS.XML_ID" => "used"],
                            ["STATUS.XML_ID" => "booked"],
                            ["STATUS.XML_ID" => "request_refund"],
                        ],
                    ],
                ],
                "runtime" => [
                    new \Bitrix\Main\Entity\ReferenceField(
                        'STATUS',
                        '\Custom\Core\FieldEnumTable',
                        ['this.UF_STATUS' => 'ref.ID'],
                        ['join_type' => 'LEFT'],
                    ),
                ],
            ]
        )->fetchAll();

        $disabledIds = [];
        foreach ($barcodes as $seat) {
            if ($id = (int)$seat['UF_SEATMAP_ID']) {
                if (in_array($seat['ID'], array_column($barcodeIds, 'VALUE'))) {
                    continue;
                }
                $disabledIds[] = $id;
            }
        }

        $entityOrg   = HL\HighloadBlockTable::compileEntity('SeatMapOrganizations');
        $hlbClassOrg = $entityOrg->getDataClass();
        $hlOrg       = $hlbClassOrg::getList(
            [
                'select' => [
                    'UF_PUBLIC_KEY',
                ],
                'filter' => ['UF_ORG_ID' => $resSchema['UF_ORG_ID']],
                'limit'  => 1
            ]
        );
        $resOrg      = $hlOrg->fetch();

        $entityPrice   = HL\HighloadBlockTable::compileEntity('SeatMapPricingZones');
        $hlbClassPrice = $entityPrice->getDataClass();

        $hlPrice   = $hlbClassPrice::getList(
            [
                'select' => [
                    'ID',
                    'UF_UUID',
                    'UF_OFFER_ID',
                    'UF_SEATMAP_ID',
                    'UF_SEATMAP_SECTOR_ID',
                ],
                'filter' => [
                    'UF_EVENT_ID' => $eventId,
                ],
            ]
        );
        $pricesIds = [];
        while ($resPrice = $hlPrice->fetch()) {
            $pricesIds[(int)$resPrice['UF_SEATMAP_SECTOR_ID']][(int)$resPrice['UF_SEATMAP_ID']] = (int)$resPrice['UF_OFFER_ID'];
        }

        $tickets        = \Custom\Core\Helper::getTicketsOffers($eventId);
        $pricesQuantity = [];
        $pricesComments = [];
        $barcodes = \Custom\Core\Tickets\BarcodesTable::getList([
            'select' => [
                'UF_OFFER_ID',
                'STATUS_ID' => 'STATUS.XML_ID',
                'COUNT',
            ],
            'filter' => [
                'UF_EVENT_ID' => $eventId,
            ],
            'group' => [
                'UF_OFFER_ID',
                'STATUS.XML_ID',
            ],
            "runtime" => [
                new \Bitrix\Main\Entity\ReferenceField(
                    'STATUS',
                    '\Custom\Core\FieldEnumTable',
                    ['this.UF_STATUS' => 'ref.ID'],
                    ['join_type' => 'LEFT'],
                ),
                new \Bitrix\Main\Entity\ExpressionField(
                    'COUNT',
                    'COUNT(*)'
                )
            ],
        ])->fetchAll();
        foreach ($barcodes as $offer) {
            if ($offer['STATUS_ID'] === 'new') {
                $pricesQuantity[$offer['UF_OFFER_ID']] = (int) $offer['COUNT'];
            }
        }

        $tickets = \Custom\Core\Helper::getTicketsOffers($eventId);
        foreach ($tickets as $ticket) {
            $now = time();
            if (
                isset($ticket['SKU_ACTIVE_FROM'])
                && $ticket['SKU_ACTIVE_FROM'] instanceof \Bitrix\Main\Type\DateTime
                && $now < $ticket['SKU_ACTIVE_FROM']->getTimestamp()
                || isset($ticket['SKU_ACTIVE_TO'])
                && $ticket['SKU_ACTIVE_TO'] instanceof \Bitrix\Main\Type\DateTime
                && $now > $ticket['SKU_ACTIVE_TO']->getTimestamp()
            ) {
                $pricesQuantity[$ticket['SKU_ID']] = 0;
            } else {
                if (isset($pricesQuantity[$ticket['SKU_ID']])) {
                    $pricesQuantity[$ticket['SKU_ID']] = min((int)$ticket['SKU_MAX_QUANTITY'], $pricesQuantity[$ticket['SKU_ID']]);
                } else {
                    $pricesQuantity[$ticket['SKU_ID']] = 0;
                }
            }
            if ($ticket['SKU_ACTIVE'] !== 'Y') {
                $pricesQuantity[$ticket['SKU_ID']] = 0;
            }
            if (!empty($ticket['SKU_PREVIEW_TEXT'])) {
                $pricesComments[$ticket['SKU_ID']] = $ticket['SKU_PREVIEW_TEXT'];
            }
        }

        return [
            'schemaId'       => $resSchema['UF_SCHEMA_ID'],
            'eventId'        => $resSchema['UF_EVENT_ID'],
            'venueId'        => $resSchema['UF_VENUE_ID'],
            'publicKey'      => $resOrg ? $resOrg['UF_PUBLIC_KEY'] : null,
            'bookingUrl'     => Option::get('custom.core', 'SEAT_MAP_BOOKING_API_Host') . '/api/public/v1.0/',
            'reloadTimeout'  => (int)Option::get('custom.core', 'SEAT_MAP_RELOAD_TIMEOUT') ?: 5,
            'disabled'       => $disabledIds,
            'pricesQuantity' => $pricesQuantity,
            'pricesIds'      => $pricesIds,
            'pricesComments' => $pricesComments,
        ];
    }

    public static function isClosedEvent(int $eventId): bool
    {
        $productEntity = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
        $productClass  = $productEntity->getDataClass();
        $query         = new ORM\Query\Query($productEntity);
        $resProduct    = $query
            ->setSelect(['ID'])
            ->setFilter(
                [
                    'EVENT_ID.VALUE'         => $eventId,
                    '!IS_CLOSED_EVENT.VALUE' => null
                ]
            )
            ->exec();
        if ($product = $resProduct->fetch()) {
            return true;
        }

        return false;
    }

    public static function getTicketStatus($ticketId)
    {
        $offerEntity       = \Bitrix\Iblock\IblockTable::compileEntity('ticketsOffers');
        $productEntity     = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
        $productClass  = $productEntity->getDataClass();

        $offerPropField  = $offerEntity->getField('CML2_LINK');
        $offerPropEntity = $offerPropField->getRefEntity();

        $filter = [
            "ID" => $ticketId,
            "OFFER.ACTIVE" => "Y",
            ">PRODUCT.QUANTITY" => 0,
            [
                'LOGIC' => 'OR',
                ['OFFER.ACTIVE_TO' => false],
                ['>=OFFER.ACTIVE_TO' => date('d.m.Y H:i:s')]
            ],
        ];

        $dbRes = $productClass::getList(
            [
                'select'      => [
                    'ID',
                    'OFFER_SUM_PRICE',
                ],
                'filter'      => $filter,
                'runtime'     => [
                    new \Bitrix\Main\Entity\ReferenceField(
                        'CML2_LINK',
                        $offerPropEntity,
                        ['=this.ID' => 'ref.VALUE'],
                        ['join_type' => 'inner']
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'OFFER',
                        $offerEntity,
                        ['=this.CML2_LINK.IBLOCK_ELEMENT_ID' => 'ref.ID'],
                        ['join_type' => 'inner']
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PRODUCT',
                        '\Bitrix\Catalog\ProductTable',
                        ['=this.OFFER.ID' => 'ref.ID'],
                        ['join_type' => 'inner']
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PRICE',
                        '\Bitrix\Catalog\PriceTable',
                        ['=this.OFFER.ID' => 'ref.PRODUCT_ID'],
                        ['join_type' => 'inner']
                    ),
                    new \Bitrix\Main\Entity\ExpressionField(
                        'OFFER_SUM_PRICE', 'SUM(%s)', ['PRICE.PRICE']
                    ),
                ],
            ]
        );

        if($obEvents = $dbRes->fetch())
        {
            if($obEvents["OFFER_SUM_PRICE"] > 0)
            {
                $result = "Скоро в продаже";
            }
            else
            {
                $result = "Скоро регистрация";
            }
        }
        else
        {
            $result = "Распродано";
        }

        return $result;
    }

    public static function getSortProductsByEvents($eventIds)
    {
        $productEntity     = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
        $propFieldEventID  = $productEntity->getField('EVENT_ID');
        $propEventIDEntity = $propFieldEventID->getRefEntity();

        $eventEntity = new ORM\Query\Query('\Bitrix\Iblock\ElementTable');
        $query       = $eventEntity
            ->setOrder(
                [
                    'LOCATION_DATE_TIME.VALUE' => "ASC",
                    'LOCATION.UF_DURATION' => "ASC",
                    'NAME' => "ASC",
                ]
            )
            ->setSelect(
                [
                    'ID',
                    //'DATE_TIME_' => 'LOCATION_DATE_TIME.VALUE'
                ]
            )
            ->setFilter(
                [
                    'EVENT_PROP.VALUE' => $eventIds,
                    //'>=LOCATION_DATE_TIME.VALUE' => date('d.m.Y 00:00:00'),
                ]
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'EVENT_PROP',
                    $propEventIDEntity,
                    ['this.ID' => 'ref.IBLOCK_ELEMENT_ID'],
                    ['join_type' => 'LEFT'],
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'LOCATION',
                    'Custom\Core\Events\EventsDateAndLocationTable',
                    ['=this.EVENT_PROP.VALUE' => 'ref.UF_EVENT_ID'],
                    ['join_type' => 'left']
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ReferenceField(
                    'LOCATION_DATE_TIME',
                    'Custom\Core\Events\EventsDateAndLocationUfDateTimeTable',
                    ['=this.LOCATION.ID' => 'ref.ID'],
                    ['join_type' => 'left']
                )
            )
            ->exec();

        $eventId = [];
        while ($event = $query->fetch()) {
            $eventId[] = $event["ID"];
        }

        return array_unique($eventId);
    }

    public static function getEventStatus($eventId)
    {
        $ob = \Custom\Core\Events\EventsTable::getList(
            [
                'select'  => [
                    'EVENT_ID' => 'ID',
                    'STATUS_' => 'STATUS',
                ],
                'filter'  => [
                    "ID" => $eventId,
                ],
                'runtime' => [
                    new \Bitrix\Main\Entity\ReferenceField(
                        'STATUS',
                        '\Custom\Core\Events\EventsStatusTable',
                        ['this.UF_STATUS' => 'ref.ID'],
                        ['join_type' => 'LEFT'],
                    ),
                ]
            ]
        )->fetch();

        return $ob;
    }

    public static function blockChanges($eventId)
    {
        $arEventStatus = self::getEventStatus($eventId);

        return in_array($arEventStatus["STATUS_UF_XML_ID"], self::statusLockChange);
    }
}
