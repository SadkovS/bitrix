<?php

namespace Local\Api\Controllers\V1;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\ORM;

Loader::includeModule('highloadblock');
Loader::includeModule('iblock');
Loader::includeModule('catalog');

class Events {
    private $url;

    public function __construct()
    {
        $this->url = 'https://' . \COption::GetOptionString("main", "server_name");
    }

    /**
     * @return void
     */
    public function getMyEvents()
    {
        try {
            global $USER;
            $request = request()->get();
            $offset  = (int)$request['offset'] > 0 ?: 0;
            $limit   = (int)$request['limit'] > 0 ?: 10;
            $limit   = (int)$request['limit'] > 100 ? 100 : (int)$request['limit'];

            $types      = $this->getEventTypes();
            $ages       = $this->getAgeLimits();
            $categories = $this->getCategories();

            $hlblock     = HL\HighloadBlockTable::getById(HL_EVENTS_ID)->fetch();
            $entity      = HL\HighloadBlockTable::compileEntity($hlblock);
            $entityClass = $entity->getDataClass();

            $productEntity = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
            $propField = $productEntity->getField('EVENT_ID');
            $propEntity = $propField->getRefEntity();

            $select      = [
                'ID',
                'IMG_SRC',
                'UF_NAME',
                'DATE_TIME',
                'UF_CATEGORY',
                'UF_TYPE',
                'UF_STATUS',
                'UF_UUID',
                'UF_AGE_LIMIT',
                'UF_CREATED_BY',
                "UF_ADDRESS"     => "UF_LOCATION_REF.UF_ADDRESS",
                "UF_ROOM"        => "UF_LOCATION_REF.UF_ROOM",
                "UF_COORDINATES" => "UF_LOCATION_REF.UF_COORDINATES",
                "PRODUCT_ID" => "REF_PRODUCT_ID.IBLOCK_ELEMENT_ID",
                "PRODUCT_CODE" => "REF_PRODUCT.CODE",
            ];
            $order       = ['ID' => 'DESC'];
            $runtime     = [
                new \Bitrix\Main\Entity\ReferenceField(
                    'PICTURE',
                    '\Bitrix\Main\FileTable',
                    ['this.UF_IMG' => 'ref.ID'],
                    ['join_type' => 'LEFT'],
                ),
                new \Bitrix\Main\Entity\ExpressionField(
                    'IMG_SRC', 'CONCAT("/upload/",%s, "/", %s)', ['PICTURE.SUBDIR', 'PICTURE.FILE_NAME']
                ),
                new  \Bitrix\Main\Entity\ReferenceField(
                    'UF_LOCATION_REF',
                    '\Custom\Core\Events\EventsDateAndLocationTable',
                    ['this.ID' => 'ref.UF_EVENT_ID'],
                    ['join_type' => 'LEFT']
                ),
                new  \Bitrix\Main\Entity\ExpressionField(
                    'DATE_TIME',
                    '%s',
                    'UF_LOCATION_REF.UF_DATE_TIME',
                    [
                        'save_data_modification'  => function () {
                            return [
                                function ($value) {
                                    return serialize($value);
                                }
                            ];
                        },
                        'fetch_data_modification' => function () {
                            return [
                                function ($value) {
                                    return unserialize($value);
                                }
                            ];
                        }
                    ]
                ),
                new \Bitrix\Main\Entity\ReferenceField(
                    'REF_PRODUCT_ID',
                    $propEntity,
                    ['this.ID' => 'ref.VALUE'],
                    ['join_type' => 'LEFT'],
                ),
                new \Bitrix\Main\Entity\ReferenceField(
                    'REF_PRODUCT',
                    $productEntity,
                    ['this.PRODUCT_ID' => 'ref.ID'],
                    ['join_type' => 'LEFT'],
                ),
            ];

            $resEvents = $entityClass::getList(
                [
                    'select'      => $select,
                    'filter'      => ['UF_STATUS' => 5, 'UF_CREATED_BY' => $request['_user']['ID']],
                    'order'       => $order,
                    'group'       => 'ID',
                    'offset'      => $offset,
                    'limit'       => $limit,
                    'runtime'     => $runtime,
                    'count_total' => true,
                    //        'cache'       => [
                    //            'ttl' => $arParams['CACHE_TIME']
                    //        ]
                ]
            );
            $rowsCount = $resEvents->getCount();
            while ($event = $resEvents->fetch()) {

                if (is_array($event['DATE_TIME']) && count($event['DATE_TIME']) > 0) {
                    foreach ($event['DATE_TIME'] as &$date) {
                        $date = [
                            'DATE' => (new \DateTime($date))->format('Y-m-d'),
                            'TIME' => (new \DateTime($date))->format('H:i')
                        ];
                    }
                    $event['DATE_TIME'] = $date;
                }
                $location             = explode(' | ', $event['UF_ADDRESS']);
                $event['UF_CATEGORY'] = $categories[$event['UF_CATEGORY']];
                $event['UF_TYPE']     = $types[$event['UF_TYPE']]['XML_ID'];

                $price = $this->getPrices($event['ID']);

                $result['items'][] = [
                    'id'           => $event['UF_UUID'],
                    "title"        => $event['UF_NAME'],
                    "type"         => $event['UF_TYPE'],
                    "category"     => $event['UF_CATEGORY']['UF_CODE'],
                    "categoryName" => $event['UF_CATEGORY']['UF_NAME'],
                    "basket_link"  => $this->url.'/basket/'.$event['PRODUCT_CODE'].'/',
                    "image"        => $this->url . $event['IMG_SRC'],
                    "dateStart"    => $event['DATE_TIME']['DATE'],
                    "timeStart"    => $event['DATE_TIME']['TIME'],
                    "age"          => $ages[$event['UF_AGE_LIMIT']]['UF_NAME'],
                    "address"      => $location[1] ?: null,
                    "place"        => $location[0] ?: null,
                    "room"         => $event['UF_ROOM'] ?: null,
                    "coordinates"  => $event['UF_COORDINATES'] ? explode(',', $event['UF_COORDINATES']) : null,
                    "prices"       => $price
                ];
                $result['count']   = $rowsCount;

            }
            response()->json(
                [
                    'status' => 'success',
                    'result' => $result,
                ],200,[],['Content-Type' => 'application/json']
            );

        } catch (\Exception $e) {
            response()->json(
                [
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ],400,[],['Content-Type' => 'application/json']
            );
        }
    }

    public function getEvent()
    {
        try {
            $request = request()->get();

            $types      = $this->getEventTypes();
            $ages       = $this->getAgeLimits();
            $categories = $this->getCategories();

            $hlblock   = HL\HighloadBlockTable::getById(HL_EVENTS_ID)->fetch();
            $entity    = HL\HighloadBlockTable::compileEntity($hlblock);
            $hlbClass  = $entity->getDataClass();

            $productEntity = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
            $propField = $productEntity->getField('EVENT_ID');
            $propEntity = $propField->getRefEntity();

            $obElement = $hlbClass::getList(
                [
                    'select'  => [
                        'ID',
                        'UF_STATUS',
                        'UF_IMG',
                        'UF_NAME',
                        'UF_CATEGORY',
                        'UF_AGE_LIMIT',
                        'UF_TYPE',
                        'UF_DESCRIPTION',
                        'UF_QUESTIONNAIRE_FIELDS',
                        'UF_PAY_SYSTEM',
                        'UF_STEP',
                        'UF_FILES',
                        'UF_UUID',
                        'IMG_SRC',
                        'UF_SIT_MAP',
                        'DATE_TIME',
                        "UF_ADDRESS"     => "UF_LOCATION_REF.UF_ADDRESS",
                        "UF_ROOM"        => "UF_LOCATION_REF.UF_ROOM",
                        "UF_COORDINATES" => "UF_LOCATION_REF.UF_COORDINATES",
                        "UF_DURATION" => "UF_LOCATION_REF.UF_DURATION",
                        "PRODUCT_ID" => "REF_PRODUCT_ID.IBLOCK_ELEMENT_ID",
                        "PRODUCT_CODE" => "REF_PRODUCT.CODE",
                    ],
                    'filter'  => ['UF_UUID' => $request['id']],
                    'runtime' => [
                        new \Bitrix\Main\Entity\ReferenceField(
                            'PICTURE',
                            '\Bitrix\Main\FileTable',
                            ['this.UF_IMG' => 'ref.ID'],
                            ['join_type' => 'LEFT'],
                        ),
                        new \Bitrix\Main\Entity\ExpressionField(
                            'IMG_SRC', 'CONCAT("/upload/",%s, "/", %s)', ['PICTURE.SUBDIR', 'PICTURE.FILE_NAME']
                        ),
                        new  \Bitrix\Main\Entity\ReferenceField(
                            'UF_LOCATION_REF',
                            '\Custom\Core\Events\EventsDateAndLocationTable',
                            ['this.ID' => 'ref.UF_EVENT_ID'],
                            ['join_type' => 'LEFT']
                        ),
                        new  \Bitrix\Main\Entity\ExpressionField(
                            'DATE_TIME',
                            '%s',
                            'UF_LOCATION_REF.UF_DATE_TIME',
                            [
                                'save_data_modification'  => function () {
                                    return [
                                        function ($value) {
                                            return serialize($value);
                                        }
                                    ];
                                },
                                'fetch_data_modification' => function () {
                                    return [
                                        function ($value) {
                                            return unserialize($value);
                                        }
                                    ];
                                }
                            ]
                        ),
                        new \Bitrix\Main\Entity\ReferenceField(
                            'REF_PRODUCT_ID',
                            $propEntity,
                            ['this.ID' => 'ref.VALUE'],
                            ['join_type' => 'LEFT'],
                        ),
                        new \Bitrix\Main\Entity\ReferenceField(
                            'REF_PRODUCT',
                            $productEntity,
                            ['this.PRODUCT_ID' => 'ref.ID'],
                            ['join_type' => 'LEFT'],
                        ),
                    ]
                ]
            );
            $event     = $obElement->fetch();

            $location = explode(' | ', $event['UF_ADDRESS']);
            if (is_array($event['DATE_TIME']) && count($event['DATE_TIME']) > 0) {
                foreach ($event['DATE_TIME'] as &$date) {
                    $date = [
                        'DATE' => (new \DateTime($date))->format('Y-m-d'),
                        'TIME' => (new \DateTime($date))->format('H:i')
                    ];
                }
                $event['DATE_TIME'] = $date;
            }
            $result   = [
                "id"           => $event['UF_UUID'],
                "title"        => $event['UF_NAME'],
                "image"        => $this->url . $event['IMG_SRC'],
                "description"  => $event['UF_DESCRIPTION'],
                "type"         => $types[$event['UF_TYPE']]['XML_ID'],
                "age"          => $ages[$event['UF_AGE_LIMIT']]['UF_NAME'],
                "link"         => $event['UF_LINK'],
                "category"     => $categories[$event['UF_CATEGORY']]['UF_CODE'],
                "categoryName" => $categories[$event['UF_CATEGORY']]['UF_NAME'],
                "basket_link"  => $this->url.'/basket/'.$event['PRODUCT_CODE'].'/',
                "address"      => $location[1] ?: null,
                "place"        => $location[0] ?: null,
                "room"         => $event['UF_ROOM'] ?: null,
                "coordinates"  => $event['UF_COORDINATES'] ? explode(',', $event['UF_COORDINATES']) : null,
                "dateStart"    => $event['DATE_TIME']['DATE'],
                "timeStart"    => $event['DATE_TIME']['TIME'],
                "duration"     => $event['UF_DURATION'],
                "prices"       => $this->getPrices($event['ID']),
            ];

            response()->json(
                [
                    'status' => 'success',
                    'result' => $result,
                ],200,[],['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            response()->json(
                [
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ],400,[],['Content-Type' => 'application/json']
            );
        }
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getEventTypes()
    {
        $query   = new ORM\Query\Query('\Custom\Core\FieldEnumTable');
        $types   = [];
        $resType = $query
            ->setSelect(['ID', 'UF_NAME' => 'VALUE', 'XML_ID'])
            ->setOrder(['SORT' => 'ASC'])
            ->setFilter(['USER_FIELD_ID' => 48])
            ->setCacheTtl(3600)
            ->exec();
        while ($type = $resType->fetch()) {
            $types[$type['ID']] = $type;
        }
        unset($query, $resType, $type);

        return $types;
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */

    public function getAgeLimits()
    {
        $query   = new ORM\Query\Query('\Custom\Core\FieldEnumTable');
        $ages    = [];
        $resAges = $query
            ->setSelect(['ID', 'UF_NAME' => 'VALUE',])
            ->setOrder(['SORT' => 'ASC'])
            ->setFilter(['USER_FIELD_ID' => 47])
            ->setCacheTtl(3600)
            ->exec();
        while ($age = $resAges->fetch()) {
            $ages[$age['ID']] = $age;
        }
        unset($query, $resType, $type);

        return $ages;
    }

    /**
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getCategories()
    {
        $query       = new ORM\Query\Query('\Custom\Core\Events\EventsCategoryTable');
        $categories  = [];
        $resCategory = $query
            ->setSelect(['ID', 'UF_NAME', 'UF_CODE'])
            ->setOrder(['UF_SORT' => 'ASC'])
            ->setCacheTtl(3600)
            ->exec();
        while ($category = $resCategory->fetch()) {
            $categories[$category['ID']] = $category;
        }
        unset($query, $resCategory, $category);
        return $categories;
    }

    /**
     * @param $eventId
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     *
     */
    public function getPrices($eventId){
        $elementEntity = \Bitrix\Iblock\IblockTable::compileEntity('ticketsOffers');
        $propField     = $elementEntity->getField('CML2_LINK');
        $propEntity    = $propField->getRefEntity();
        $elements = \Bitrix\Iblock\Elements\ElementTicketsTable::getList(
            [
                'select'  => [
                    'ID',
                    'NAME',
                    'SKU_ID'             => 'OFFER.ID',
                    'SKU_NAME'           => 'OFFER.NAME',
                    'SKU_MAX_QUANTITY'   => 'OFFER.MAX_QUANTITY.VALUE',
                    'SKU_RESERVE_TIME'   => 'OFFER.RESERVE_TIME.VALUE',
                    'SKU_TYPE'           => 'OFFER.TYPE.VALUE',
                    'SKU_TOTAL_QUANTITY' => 'OFFER.TOTAL_QUANTITY.VALUE',
                    'SKU_QUANTITY'       => 'PROPS.QUANTITY',
                    'SKU_PRICE'          => 'PRICE.PRICE'
                ],
                'filter'  => ['EVENT_ID.VALUE' => $eventId],
                'runtime' => [
                    new \Bitrix\Main\Entity\ReferenceField(
                        'TICKETS',
                        $propEntity,
                        ['this.ID' => 'ref.VALUE'],
                        ['join_type' => 'LEFT'],
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'OFFER',
                        $elementEntity,
                        ['this.TICKETS.IBLOCK_ELEMENT_ID' => 'ref.ID'],
                        ['join_type' => 'LEFT'],
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PROPS',
                        '\Bitrix\Catalog\ProductTable',
                        ['this.OFFER.ID' => 'ref.ID'],
                        ['join_type' => 'LEFT'],
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PRICE',
                        '\Bitrix\Catalog\PriceTable',
                        ['this.OFFER.ID' => 'ref.PRODUCT_ID'],
                        ['join_type' => 'LEFT'],
                    ),
                ]
            ]
        );
        $price = [];
        while ($element = $elements->fetch()) {
            $price[] = [
                "name"     => $element['SKU_TYPE'],
                "currency" => "RUB",
                "price"    => (int)$element['SKU_PRICE'],
                "quantity"   => (int)$element['SKU_QUANTITY']
            ];
        }
        return $price;
    }

    /**
     * Возвращает список id недоступных мест для события
     *
     * @param int|null $eventId Id мероприятия
     * @return void
     */
    public function  getTakenSeats(int $eventId = null): void
    {
        try {
            if (!$eventId) {
                $request = request()->get();
                $eventId = \Custom\Core\Events::getEventIdByProduct((int) $request['id']);
            }

            if (!$eventId) {
                throw new \Exception('Событие не найдено.');
            }

            $eventData = \Custom\Core\Events::getSeatMapData($eventId);

            response()->json(
                [
                    'status' => 'success',
                    'result' => [
                        'disabled' => ($eventData['disabled'] ?? []),
                        'pricesQuantity' => $eventData['pricesQuantity'],
                    ],
                ], 200, [], ['Content-Type' => 'application/json']
            );
        } catch (\Exception $e) {
            response()->json(
                [
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                ], 400, [], ['Content-Type' => 'application/json']
            );
        }
    }
}
