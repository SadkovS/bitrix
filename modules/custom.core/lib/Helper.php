<?php

namespace Custom\Core;

use Bitrix\Main\Entity\Validator;
use Bitrix\Sale\Order;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\GroupTable;
use Bitrix\Main\ORM;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option as Option;

Loc::loadMessages(__FILE__);
Loader::includeModule('sale');

class Helper {
    /**
     * @param $param1
     * @param $param2
     * @param $showTime
     * @param $dateTimeSep
     *
     * @return false|int|string|void|null
     * @throws \Exception
     */
    public static function getFormatDate($param1 = null, $param2 = null, bool $showTime = false, string $dateTimeSep = ' | ', bool $showYearSymbol = true)
    {
        // y — Год
        // m — Месяц
        // d — День
        // w — Название дня недели
        // ——————————————————————————————————————
        // ymdw () — Вывод: 2019-08-30 (Текущая дата)
        // ymdw ("") — Вывод: 2019-08-30 (Текущая дата)
        // ymdw ("2019-08-30") — Вывод: 30 Августа (Пятница), 2019 г. (Заданная дата)
        // ymdw ( ymdw () ) — Вывод: 30 Августа (Пятница), 2019 г. (Текущая дата)
        // ymdw ("tmsp") — Вывод: 1567155817 // Метка времени Unix текущей даты и времени (тикает) (Текущий)
        // ymdw ("Y") — Вывод: 2019 (Текущий)
        // ymdw ("m") — Вывод: Номер месяца (от 1 до 12) (Текущий)
        // ymdw ("mn") — Вывод: Название месяца (Текущий)
        // ymdw ("d") — Вывод: Дата месяца (от 1 до 31) (Текущий)
        // ymdw ("dn") — Вывод: Название дня недели (Понедельник) (Текущий)
        // ymdw ("2019-08-30", "tmsp") — Вывод: 1567155817 // Метка времени Unix текущей даты
        // ymdw ("2019-08-30", "Y") — Вывод: Год (число)
        // ymdw ("2019-08-30", "m") — Вывод: Номер месяца (от 1 до 12)
        // ymdw ("2019-08-30", "mn") —  Вывод: Название месяца (Января)
        // ymdw ("2019-08-30", "d") — Вывод: Дата месяца (от 1 до 31)
        // ymdw ("2019-08-30", "dn") — Вывод: Название дня недели (Понедельник)
        // ymdw ("1559682000", "") — Вывод: 5 Июня (Среда) 2019 г.
        // ymdw ("1559682000", "Y") — Вывод: 2019
        // ymdw ("1559682000", "m") — Вывод: Номер месяца (от 1 до 12)
        // ymdw ("1559682000", "mn") — Вывод: Название месяца
        // ymdw ("1559682000", "d") — Вывод: Дата месяца (от 1 до 31)
        // ymdw ("1559682000", "dn") — Вывод: Название дня недели (Понедельник)
        // ——————————————————————————————————————
        if ($param1 === '1970-01-01 03:00:00' || $param1 == '') return null;
        date_default_timezone_set("Europe/Moscow"); // Set default time zone / Volgograd / Samara /
        $date = new \DateTime($param1);
        $date->setTimezone(new \DateTimeZone('Europe/Moscow'));
        $param1       = $date->format('Y-m-d');
        $timeStr      = $date->format('H:i');
        $args         = func_get_args(); // Массив аргументов функции
        $year         = substr($param1, 0, 4); // Год
        $month        = substr($param1, 5, -3); // Номер месяца
        $month_name   = self::rus_date("F", mktime(0, 0, 0, (int)$month, 10)); // Название месяца
        $day          = substr($param1, 8); // Число месяца
        $get_week_day = self::rus_date("l", strtotime($param1)); // День недели
        $timestamp    = strtotime($param1); // Метка времени Unix
        // ——————————————————————————————————————
        // Текущая дата (2019-08-30)
        if (count($args) == 0) {
            return date("Y-m-d");
        } elseif ($param1 == "") {
            return date("Y-m-d");
        } elseif ($param1 != "") {
            // 5 Июня (Воскресенье), 1977 г.
            if (preg_match("#([0-9]{4,4})-([0-9]{2,2})-([0-9]{2,2})#", $param1)) {
                if ($param2 != "") {
                    if ($param2 == "tmsp") {
                        return strtotime(date($param1));
                    } elseif ($param2 == "Y") {
                        return substr($param1, 0, 4);
                    } elseif ($param2 == "m") {
                        return substr($param1, 5, -3);
                    } elseif ($param2 == "d") {
                        return substr($param1, 8);
                    } elseif ($param2 == "mn") {
                        return self::rus_date("F", mktime(0, 0, 0, (int)substr($param1, 5, -3), 10));
                    } elseif ($param2 == "dn") {
                        return self::rus_date("l", strtotime($param1));
                    } elseif ($param2 == "dmnY") {
                        return (int)$day . " " . $month_name . " " . $year . ($showYearSymbol ? " г." : "");
                    }
                } else {
                    if ($showTime) {
                        return (int)$day . " " . $month_name . " " . $year . ($showYearSymbol ? " г." : "") . $dateTimeSep . $timeStr;
                    } else {
                        //return (int)$day . " " . $month_name . " " . $year . " г.";
                        return (int)$day . " " . $month_name;
                    }
                }
            } elseif (preg_match("#^[0-9]{5,20}$#", $param1)) {
                if ($param2 != "") {
                    // Год (число)
                    if ($param2 == "Y") {
                        return date("Y", $param1);
                    } // Номер месяца (от 1 до 12)
                    elseif ($param2 == "m") {
                        return (int)date("m", $param1);
                    } // Дата месяца (от 1 до 31)
                    elseif ($param2 == "d") {
                        return (int)date("d", $param1);
                    } // Название месяца
                    elseif ($param2 == "mn") {
                        return self::rus_date("F", mktime(0, 0, 0, (int)date("m", $param1), 10));
                    }
                    // Название дня недели (Понедельник)
                    //						elseif ( $param2 == "dn" ) {
                    //                return rus_date ("l", mktime(0, 0, 0, (int) date("d", $param1), 10));
                    //            }
                } // Вывод: 30 Августа (Пятница), 2019 г.
                else {
                    return (int)date("d", $param1) . " " . self::rus_date("F", $param1) . date("Y", $param1);
                }
            } else {
                // Метка времени Unix текущей даты и времени
                if ($param1 == "tmsp") {
                    return time();
                    // return strtotime("now");
                } // Текущий Год
                elseif ($param1 == "Y") {
                    return date("Y");
                } // Текущий Месяц
                elseif ($param1 == "m") {
                    return (int)date("m");
                } // Название месяца
                elseif ($param1 == "mn") {
                    return self::rus_date("F", time());
                } // Текущий День
                elseif ($param1 == "d") {
                    return (int)date("d");
                }
                // Название дня недели (Понедельник)
                //				elseif ( $param1 == "dn" ) {
                //            return rus_date ( "l", time() );
                //        }
            }
        }
    }

    /**
     * @return string
     */
    public static function rus_date()
    {
        $translate = [
            "am"        => "дп",
            "pm"        => "пп",
            "AM"        => "ДП",
            "PM"        => "ПП",
            "Monday"    => "Понедельник",
            "Mon"       => "Пн",
            "Tuesday"   => "Вторник",
            "Tue"       => "Вт",
            "Wednesday" => "Среда",
            "Wed"       => "Ср",
            "Thursday"  => "Четверг",
            "Thu"       => "Чт",
            "Friday"    => "Пятница",
            "Fri"       => "Пт",
            "Saturday"  => "Суббота",
            "Sat"       => "Сб",
            "Sunday"    => "Воскресенье",
            "Sun"       => "Вс",
            "January"   => "Января",
            "Jan"       => "Янв",
            "February"  => "Февраля",
            "Feb"       => "Фев",
            "March"     => "Марта",
            "Mar"       => "Мар",
            "April"     => "Апреля",
            "Apr"       => "Апр",
            "May"       => "Мая",
            "June"      => "Июня",
            "Jun"       => "Июн",
            "July"      => "Июля",
            "Jul"       => "Июл",
            "August"    => "Августа",
            "Aug"       => "Авг",
            "September" => "Сентября",
            "Sep"       => "Сен",
            "October"   => "Октября",
            "Oct"       => "Окт",
            "November"  => "Ноября",
            "Nov"       => "Ноя",
            "December"  => "Декабря",
            "Dec"       => "Дек",
            "st"        => "ое",
            "nd"        => "ое",
            "rd"        => "е",
            "th"        => "ое"
        ];

        if (func_num_args() > 1) {
            $timestamp = func_get_arg(1);

            return strtr(date(func_get_arg(0), $timestamp), $translate);
        } else {
            return strtr(date(func_get_arg(0)), $translate);
        }
    }

    /**
     * @param $number
     * @param $after
     *
     * @return string
     */
    public static function declination($number, $after)
    {
        $cases = [2, 0, 1, 1, 1, 2];
        return $number . ' ' . $after[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
    }

    /**
     * @param string $url
     * @param array  $headers
     * @param string $method
     * @param array  $params
     *
     * @return array
     */
    public static function curlRequest(string $url = '', array $headers = [], string $method = 'GET', array $params = [])
    {
        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt(
                    $ch, CURLOPT_POSTFIELDS, json_encode(
                           $params, JSON_UNESCAPED_UNICODE
                       )
                );
            }
            if (in_array($method, ['PUT', 'PATCH'])) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt(
                    $ch, CURLOPT_POSTFIELDS, json_encode(
                           $params, JSON_UNESCAPED_UNICODE
                       )
                );
            }

            if ($method == 'DELETE') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }

            if ($method == 'GET') {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                if (count($params) > 0) {
                    $queryStr = http_build_query($params);
                    $url      .= '?' . $queryStr;
                }
            }
            curl_setopt($ch, CURLOPT_URL, $url);
            $response = curl_exec($ch);
            if ($response === false) throw new \Exception(curl_error($ch));
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200)
                return ['status' => 'success', 'code' => $httpCode, 'response' => $response];
            if ($httpCode === 404)
                return ['status' => 'success', 'code' => $httpCode, 'response' => $response];
            else
                throw new \Exception($response);
        } catch (\Exception $e) {
            return ['status' => 'error', 'code' => $httpCode, 'message' => $e->getMessage()];
        }
    }

    public static function getTicketsOffers(int $eventId)
    {
        $elementEntity = \Bitrix\Iblock\IblockTable::compileEntity('ticketsOffers');
        $propField     = $elementEntity->getField('CML2_LINK');
        $propEntity    = $propField->getRefEntity();

        $elements = \Bitrix\Iblock\Elements\ElementTicketsTable::getList(
            [
                'select'  => [
                    'ID',
                    'NAME',
                    'SKU_ID'                  => 'OFFER.ID',
                    'SKU_NAME'                => 'OFFER.NAME',
                    'SKU_MAX_QUANTITY'        => 'OFFER.MAX_QUANTITY.VALUE',
                    'SKU_RESERVE_TIME'        => 'OFFER.RESERVE_TIME.VALUE',
                    'SKU_TYPE'                => 'OFFER.TYPE.VALUE',
                    'SKU_TOTAL_QUANTITY'      => 'OFFER.TOTAL_QUANTITY.VALUE',
                    'SKU_PREVIEW_TEXT'        => 'OFFER.PREVIEW_TEXT',
                    'SKU_ACTIVE'              => 'OFFER.ACTIVE',
                    'SKU_ACTIVE_FROM'         => 'OFFER.ACTIVE_FROM',
                    'SKU_ACTIVE_TO'           => 'OFFER.ACTIVE_TO',
                    'SKU_IS_CREATED_SEAT_MAP' => 'OFFER.IS_CREATED_SEAT_MAP.VALUE',
                    'SKU_DATES',
                    'SKU_DATES_ALL'           => 'OFFER.DATES_ALL.VALUE',
                    'SKU_QUANTITY'            => 'PROPS.QUANTITY',
                    'SKU_PRICE'               => 'PRICE.PRICE',
                    'SKU_TYPE_PARTICIPATION' => 'OFFER.TYPE_PARTICIPATION.VALUE',
                    'SKU_SHOW_STOCK'         => 'OFFER.SHOW_STOCK.VALUE',
                ],
                'filter'  => ['EVENT_ID.VALUE' => $eventId],
                'runtime' => [
                    new \Bitrix\Main\Entity\ReferenceField(
                        'TICKETS',
                        $propEntity,
                        ['this.ID' => 'ref.VALUE'],
                        ['join_type' => 'LEFT']
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
                    new \Bitrix\Main\Entity\ExpressionField(
                        'SKU_DATES',
                        "GROUP_CONCAT(%s SEPARATOR ';')",
                        ['OFFER.DATES.VALUE']
                    ),
                ]
            ]
        )->fetchAll();

        $arEventDates = self::getAllDatesFromEvent($eventId);
        $arSalesTickets = self::ticketSalesCheck(array_column($elements, 'SKU_ID'));
        foreach ($elements as &$element) {
            $element['SKU_SALES_EXISTS'] = 0;
            if ($element["SKU_DATES"]) {
                $element["SKU_DATES"] = explode(";", $element["SKU_DATES"]);

                $element["SKU_DATES"] = array_filter($element["SKU_DATES"], 'trim');

                if ($arEventDates && $element["SKU_DATES"]) {
                    foreach ($element["SKU_DATES"] as $key => $value) {
                        if (!in_array($value, $arEventDates))
                            unset($element["SKU_DATES"][$key]);
                    }
                }
            }
            if (in_array($element["SKU_ID"], $arSalesTickets)) $element['SKU_SALES_EXISTS'] = 1;

            \Custom\Core\Helper::modifyFormatDateOffers($element);

            $element['SKU_PREVIEW_TEXT']        = str_replace("<br/>", "\n", $element['SKU_PREVIEW_TEXT']);
            $element['SKU_IS_CREATED_SEAT_MAP'] = !!$element['SKU_IS_CREATED_SEAT_MAP'];
        }

        return $elements;
    }

    private static function ticketSalesCheck(int|array $productID = 0)
    {
        $products = [];
        $orders   = Order::getList(
            [
                'select'  => [
                    'PRODUCT'
                ],
                'filter'  => [
                    "PROPERTY_EVENT_ID.CODE" => "EVENT_ID",
                    'BASKET_REFS.PRODUCT_ID' => $productID
                ],
                'runtime' => [
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PROPERTY_EVENT_ID',
                        'Bitrix\Sale\Internals\OrderPropsValueTable',
                        ['=this.ID' => 'ref.ORDER_ID'],
                        ['join_type' => 'inner']
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'BASKET_REFS',
                        'Bitrix\Sale\Internals\BasketTable',
                        ['this.ID' => 'ref.ORDER_ID'],
                        ['join_type' => 'left']
                    ),
                    new \Bitrix\Main\Entity\ExpressionField(
                        'PRODUCT', 'GROUP_CONCAT(DISTINCT %s)', ['BASKET_REFS.PRODUCT_ID']
                    ),
                ],
            ]
        );
        while ($product = $orders->fetch()) {
            $products = array_merge($products, explode(',', $product['PRODUCT']));
        }

        return $products;
    }

    public static function includeComponent($componentName, $componentTemplate = '', $arParams = [], $parentComponent = null, $arFunctionParams = [])
    {
        global $APPLICATION;
        return $APPLICATION->IncludeComponent($componentName, $componentTemplate, $arParams, $parentComponent, $arFunctionParams);
    }

    public static function modifyFormatDateOffers(&$offer = null, $toSiteFormat = false)
    {
        if (!$offer)
            return false;

        $actives = ["ACTIVE_FROM", "ACTIVE_TO"];

        if ($toSiteFormat) {
            foreach ($actives as $active) {
                $year = $offer["{$active}_YEAR"];
                if ($year) {
                    $time = $offer["${active}_TIME"];
                    if (!$time)
                        $time = "00:00";

                    $yearTime = implode(" ", [$year, $time]);

                    $offer[$active] = \DateTime::createFromFormat('d.m.y H:i', $yearTime)->format('d.m.Y H:i:s');
                } else {
                    $offer[$active] = false;
                }
            }

            if ($offer["DATES"]) {
                foreach ($offer["DATES"] as &$value) {
                    if ($value) {
                        $value = \DateTime::createFromFormat('d.m.y', $value)->format('d.m.Y');
                    }
                }
            }
        } else {
            foreach ($actives as $active) {
                $yearTime = $offer["SKU_{$active}"];

                if ($yearTime) {
                    $offer["SKU_${active}_YEAR"] = \DateTime::createFromFormat('d.m.Y H:i:s', $yearTime->toString())->format('d.m.y');
                    $offer["SKU_${active}_TIME"] = \DateTime::createFromFormat('d.m.Y H:i:s', $yearTime->toString())->format('H:i');
                } else {
                    $offer["SKU_${active}_YEAR"] = false;
                    $offer["SKU_${active}_TIME"] = false;
                }
            }

            if ($offer["SKU_DATES"]) {
                foreach ($offer["SKU_DATES"] as &$value) {
                    $value = \DateTime::createFromFormat('d.m.Y', $value)->format('d.m.y');
                }
            }
        }
    }

    public static function getAllDatesFromEvent($eventId = null, $offerId = null)
    {
        if (!$eventId) {
            if ($offerId) {
                $offerEntity = \Bitrix\Iblock\IblockTable::compileEntity("ticketsOffers");
                $offerObj    = $offerEntity->wakeUpObject($offerId);

                $elId          = $offerObj->fillCml2Link()->getValue();
                $elementEntity = \Bitrix\Iblock\IblockTable::compileEntity("tickets");
                $objElement    = $elementEntity->wakeUpObject($elId);

                $eventId = $objElement->fill("EVENT_ID")->getValue();
            }

            if (!$eventId)
                return [];
        }

        $hlblLocation     = \Bitrix\Highloadblock\HighloadBlockTable::getById(HL_EVENTS_LOCATION_ID)->fetch();
        $entityLocation   = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblLocation);
        $hlbClassLocation = $entityLocation->getDataClass();

        $obElement = $hlbClassLocation::getList(
            [
                'select' => [
                    'ID',
                    'UF_EVENT_ID',
                    'UF_DATE_TIME',
                ],
                'filter' => ['UF_EVENT_ID' => $eventId],
            ]
        );

        $arDates = [];

        while ($location = $obElement->fetch()) {
            if (is_array($location['UF_DATE_TIME']) && count($location['UF_DATE_TIME']) > 0) {
                foreach ($location['UF_DATE_TIME'] as $key => &$dateItem) {
                    $currentDate = ($dateItem)->format('d.m.Y');
                    $arDates[]   = $currentDate;
                }
                unset($dateItem, $key);
            } else {
                $currentDate = (new \DateTime())->format('d.m.Y');
                $arDates[]   = $currentDate;
            }
            unset($location['UF_DATE_TIME']);
        }

        if ($arDates)
            sort($arDates);

        return $arDates;
    }

    public static function removeUnnecessaryDatesInTicket($eventId = null)
    {
        if (!$eventId)
            return false;

        $elementEntity = \Bitrix\Iblock\IblockTable::compileEntity('ticketsOffers');
        $propField     = $elementEntity->getField('CML2_LINK');
        $propEntity    = $propField->getRefEntity();

        $elements = \Bitrix\Iblock\Elements\ElementTicketsTable::getList(
            [
                'select'  => [
                    'SKU_ID'        => 'OFFER.ID',
                    'SKU_DATES',
                    'SKU_DATES_ALL' => 'OFFER.DATES_ALL.VALUE',
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
                    new \Bitrix\Main\Entity\ExpressionField(
                        'SKU_DATES',
                        "GROUP_CONCAT(%s SEPARATOR ';')",
                        ['OFFER.DATES.VALUE']
                    ),
                ]
            ]
        )->fetchAll();

        $arEventDates = \Custom\Core\Helper::getAllDatesFromEvent($eventId);

        foreach ($elements as &$element) {
            if ($element["SKU_DATES"]) {
                $element["SKU_DATES"] = explode(";", $element["SKU_DATES"]);

                $element["SKU_DATES"] = array_filter($element["SKU_DATES"], 'trim');

                if ($arEventDates && $element["SKU_DATES"]) {
                    foreach ($element["SKU_DATES"] as $key => $value) {
                        if (!in_array($value, $arEventDates))
                            unset($element["SKU_DATES"][$key]);
                    }
                }

                $elementEntity = \Bitrix\Iblock\IblockTable::compileEntity("ticketsOffers");
                $objElement    = $elementEntity->wakeUpObject($element["SKU_ID"]);

                $objElement->removeAllDates();

                if ($element["SKU_DATES"]) {
                    sort($element["SKU_DATES"]);

                    foreach ($element["SKU_DATES"] as $date) {
                        $objElement->addToDates($date);
                    }
                }

                $resElement = $objElement->save();

                if (!$resElement->isSuccess()) {
                    throw new \Exception(implode(', ', $resElement->getErrors()));
                }
            }
        }
    }

    public static function getDiscountTypes()
    {
        $query         = new ORM\Query\Query('\Custom\Core\FieldEnumTable');
        $discountTypes = [];
        $resType       = $query
            ->setSelect(['ID', 'UF_NAME' => 'VALUE'])
            ->setOrder(['SORT' => 'ASC'])
            ->setFilter(['USER_FIELD_ID' => FIELD_DISCOUNT_TYPE_ID])
            ->setCacheTtl(3600)
            ->exec();
        while ($type = $resType->fetch()) {
            $discountTypes[$type['ID']] = $type;
        }
        unset($query, $resType, $type);

        return $discountTypes;
    }

    public static function getRandomInt(int $len = 16)
    {
        return mt_rand(pow(10, $len - 1) - 1, pow(10, $len) - 1);
    }

    public static function setOrderTicketsBarcodeStatus($orderId, $status)
    {
        $userFieldEnum = \Bitrix\Main\UserFieldTable::getList(
            [
                "filter"  => [
                    "HL.NAME"    => "Barcodes",
                    "FIELD_NAME" => "UF_STATUS",
                ],
                "select"  => [
                    "ENUM_ID"     => "ENUM.ID",
                    "ENUM_XML_ID" => "ENUM.XML_ID",
                ],
                "runtime" => [
                    new \Bitrix\Main\Entity\ExpressionField(
                        'HL_ID',
                        'REPLACE(%s, "HLBLOCK_", "")',
                        ['ENTITY_ID']
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'HL',
                        '\Bitrix\Highloadblock\HighloadBlockTable',
                        ['this.HL_ID' => 'ref.ID'],
                        ['join_type' => 'LEFT'],
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'ENUM',
                        '\Custom\Core\FieldEnumTable',
                        ['this.ID' => 'ref.USER_FIELD_ID'],
                        ['join_type' => 'LEFT'],
                    ),
                ],
            ]
        )->fetchAll();

        $arStatus = [];
        foreach ($userFieldEnum as $item) {
            $arStatus[$item["ENUM_XML_ID"]] = $item["ENUM_ID"];
        }

        $barcodeIds = \Bitrix\Sale\BasketPropertiesCollection::getList(
            [
                'select'  => ['VALUE'],
                'filter'  => [
                    '=BASKET.ORDER_ID' => $orderId,
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

        foreach ($barcodeIds as $id) {
            \Custom\Core\Tickets\BarcodesTable::update($id["VALUE"], ["UF_STATUS" => $arStatus[$status]]);
        }
    }


    public static function genPass($passLen = 10)
    {
        $illegals = [
            'qazxswedcvfrtgbnhyujmkiolp',
            '1234567890',
            'QAZXSWEDCVFRTGBNHYUJMKIOLP',
            '!@#$%^*()_-+=\|/.,:;[]{}'
        ];

        $chars    = implode('', $illegals);
        $max      = $passLen;
        $size     = strlen($chars) - 1;
        $password = null;
        while ($max--) {
            $password .= $chars[rand(0, $size)];
        }

        foreach ($illegals as $sequence) {
            if (false === strpbrk($password, $sequence)) {
                return self::genPass($passLen);
            }
        }

        return $password;
    }

    public static function getGroupByCode(string $code): array|false
    {
        $rsGroups = \CGroup::GetList($by = "c_sort", $order = "asc", ["STRING_ID" => $code]);
        return $rsGroups->Fetch();
    }

    public static function getShareLinks($page, $text)
    {
        $text = urlencode($text);
        $text = str_replace("+", "%20", $text);

        $fullURL     = (\CMain::IsHTTPS() ? "https://" : "http://") . SITE_SERVER_NAME . $page;
        $fullURLCode = (\CMain::IsHTTPS() ? "https://" : "http://") . SITE_SERVER_NAME . urlencode($page);

        return [
            "THIS" => $fullURL,
            "VK"   => "https://vk.com/share.php?title={$text}&url={$fullURL}",
            "TG"   => "https://t.me/share/url?text={$text}&url={$fullURL}",
        ];
    }

    public static function getPropertiesEnum(string $hlName, string $fieldName, string $xmlID = '')
    {
        $filter = [
            "HL.NAME"    => $hlName,
            "FIELD_NAME" => $fieldName,
        ];

        if (!empty($xmlID)) $filter["ENUM.XML_ID"] = $xmlID;

        $query = \Bitrix\Main\UserFieldTable::getList(
            [
                "filter"  => $filter,
                "select"  => [
                    "ENUM_ID"     => "ENUM.ID",
                    "ENUM_XML_ID" => "ENUM.XML_ID",
                    "ENUM_NAME"   => "ENUM.VALUE",
                ],
                "runtime" => [
                    new \Bitrix\Main\Entity\ExpressionField(
                        'HL_ID',
                        'REPLACE(%s, "HLBLOCK_", "")',
                        ['ENTITY_ID']
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'HL',
                        '\Bitrix\Highloadblock\HighloadBlockTable',
                        ['this.HL_ID' => 'ref.ID'],
                        ['join_type' => 'LEFT'],
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'ENUM',
                        '\Custom\Core\FieldEnumTable',
                        ['this.ID' => 'ref.USER_FIELD_ID'],
                        ['join_type' => 'LEFT'],
                    ),
                ],
                'order'   => ['ENUM_ID' => 'ASC'],
                'cache'   => ['ttl' => 3600],
            ]
        );
        $res   = [];
        while ($item = $query->fetch()) {
            if (!empty($xmlID)) $res = $item['ENUM_ID'];
            else $res[$item['ENUM_ID']] = $item;
        }
        return $res;
    }

    public static function priceFormat($price = null, $replaceZeroCent = true)
    {
        if ($price === null)
            return false;

        $price = number_format($price, 2, '.', '&nbsp;');

        if ($replaceZeroCent)
            $price = str_replace(".00", "", $price);

        return $price;
    }

    public static function getDefFilterUrl()
    {
        $url = $GLOBALS["APPLICATION"]->GetCurPage();
        $clear = substr($url, 0, strpos($url, "filter/"));

        if ($clear)
            return $clear;
        else
            return $url;
    }

    public static function asset_with_version(string $url): string
    {
        $documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
        $filePath     = $documentRoot . $url;

        if (!file_exists($filePath)) {
            return $url;
        }

        $version   = filemtime($filePath);
        $separator = strpos($url, '?') === false ? '?' : '&';

        return $url . $separator . 'v=' . $version;
    }

    /**
     * @param int $id
     * @param int $companyID
     *
     * @return bool
     * @throws Exception
     */
    public static function createProfile(int $id, int $companyID): bool
    {
        $uuid          = \Custom\Core\UUID::uuid3(\Custom\Core\UUID::NAMESPACE_X500, $id . microtime());
        $profileEntity = (new ORM\Query\Query('Custom\Core\Users\UserProfilesTable'))->getEntity();
        $objProfile    = $profileEntity->createObject();
        $objProfile->set('UF_COMPANY_ID', $companyID);
        $objProfile->set('UF_USER_ID', $id);
        $objProfile->set('UF_UUID', $uuid);

        $objProfile->set('UF_XML_ID', $uuid);
        $resProfile = $objProfile->save();

        if (!$resProfile->isSuccess()) throw new \Exception('Ошибка при создании профиля');

        return true;
    }

    /**
     * @param string $groupCode
     *
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public static function getGroupIdByCode(string $groupCode): int
    {
        $group = GroupTable::getList(
            [
                'filter' => ['=STRING_ID' => $groupCode],
                'select' => ['ID']
            ]
        )->fetch();

        if (!$group) {
            throw new \Exception("Группа с символьным идентификатором '{$groupCode}' не найдена.");
        }

        return (int)$group['ID'];
    }

    public static function getShortFio(string $fio)
    {
        $result = [];

        $fioArray = explode(" ", $fio);

        if ($fioArray[0])
            $result[] = $fioArray[0];
        if ($fioArray[1])
            $result[] = mb_substr($fioArray[1], 0, 1) . ".";
        if ($fioArray[2])
            $result[] = mb_substr($fioArray[2], 0, 1) . ".";

        if ($result)
            return implode(" ", $result);

        return "";
    }

    public static function saveFileFromBase64(string $fileBase64, string $fileName, string $fileaAditionalName = null)
    {
        $fileId = 0;

        $filePath = $_SERVER["DOCUMENT_ROOT"] . UPLOAD_TMP_DIR;
        if ($fileaAditionalName)
            $filePath .= $fileaAditionalName . "_";
        $filePath .= $fileName;

        file_put_contents($filePath, base64_decode($fileBase64));

        $fileArray = \CFile::MakeFileArray($filePath);

        $fileId = \CFile::SaveFile($fileArray, "tmp");

        unlink($filePath);

        return $fileId;
    }

    public static function prepareStringSqlResultToArray($str = "")
    {
        $result = [];

        $str = explode(";", $str);

        foreach ($str as $val) {
            $val             = explode("-", $val);
            $result[$val[0]] = $val[1];
        }

        return $result;
    }

    public static function trimIBElementName($productName = "", $offerName = "", $prefix = "")
    {
        $maxLen = 240;
        $result = "";

        if (!$offerName) {
            if ($prefix) {
                $strLen = mb_strlen($prefix . " " . $productName);
                if ($strLen > $maxLen) {
                    $prefixLen   = mb_strlen($prefix . " ");
                    $productName = mb_substr($productName, 0, $maxLen - $prefixLen - 1);
                }
                $result = $prefix . " " . $productName;
            } else {
                $strLen = mb_strlen($productName);
                if ($strLen > $maxLen) {
                    $productName = mb_substr($productName, $maxLen);
                }
                $result = $productName;
            }
        } else {
            $strLen = mb_strlen($productName . ' [' . $offerName . ']');
            if ($strLen > $maxLen) {

                $offerNameLen = mb_strlen(' [' . $offerName . ']');
                $productName = mb_substr($productName, 0, $maxLen - $offerNameLen - 1);
            }
            $result = $productName . ' [' . $offerName . ']';
        }

        return $result;
    }

    public static function getSiteUrl()
    {
        $serverName = (!empty(SITE_SERVER_NAME)) ? SITE_SERVER_NAME : $_SERVER["SERVER_NAME"];
        return (\CMain::IsHTTPS() ? "https://" : "http://") . $serverName;
    }

    public static function getIBPropEmunIdFromXmlId($prop, $xmlId)
    {
        $propObj = \Bitrix\Iblock\PropertyTable::getList(
            [
                'select'  => [
                    'ENUM_ID' => 'ENUM.ID'
                ],
                'filter'  => [
                    '=CODE' => $prop,
                    'ENUM.XML_ID' => $xmlId
                ],
                'runtime' => [
                    new \Bitrix\Main\Entity\ReferenceField(
                        'ENUM',
                        '\Bitrix\Iblock\PropertyEnumerationTable',
                        ['this.ID' => 'ref.PROPERTY_ID'],
                        ['join_type' => 'INNER'],
                    ),
                ]
            ]
        )->fetch();

        return (!empty($propObj["ENUM_ID"]))? $propObj["ENUM_ID"] : null;
    }

    public static function logToFile($data, $name, $file)
    {
        \Bitrix\Main\Diag\Debug::writeToFile("=============================================","",$file);
        \Bitrix\Main\Diag\Debug::writeToFile(date("d.m.Y H:i:s"),"",$file);
        \Bitrix\Main\Diag\Debug::writeToFile($data,$name,$file);
        \Bitrix\Main\Diag\Debug::writeToFile("=============================================","",$file);
    }

	public static function addSeoEventItem($item, &$SEO_Event_JSON)
	{
		$start_date = (new \DateTime($item["DATE_TIME"]["DATE"]))->format('Y-m-d');
		$end_date = (new \DateTime($item["DATE_TIME"]["DATE_END"]))->format('Y-m-d');
		$start_time = $item["DATE_TIME"]["TIME"];
		$end_time = $item["DATE_TIME"]["TIME_END"];

		$host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

		$SEO_Event_JSON["@graph"][] = [
			"@type" => "Event",
			"eventStatus" => "https://schema.org/EventScheduled",
			"name" => $item["NAME"],
			"startDate" => $start_date . "T" . $start_time . ":00+03:00",
			"endDate" => $end_date . "T" . $end_time . ":00+03:00",
			"eventAttendanceMode" => "https://schema.org/OfflineEventAttendanceMode",
			"url" => $host . $item["DETAIL_PAGE_URL"],
			"image" => [
				"@id" => $host . $item["DETAIL_PICTURE"]
			],
			"location" => [
				"@type" => "Place",
				"name" => $item['LOCATION_NAME'],
				"address" => $item['LOCATION_ADDRESS'],
			],
			"offers" => [
				"@type" => "Offer",
				"url" => $host . $item["DETAIL_PAGE_URL"],
				"price" => $item["MIN_PRICE"],
				"priceCurrency" => 'RUB',
				"availability" => "https://schema.org/InStock",
				"validFrom" => $start_date . "T" . $start_time . ":00+03:00"
			],
			"performer" => [
				"@type" => "Organization",
				"name" => $item["COMPANY_NAME"],
			],
			"organizer" => [
				"@type" => "Organization",
				"name" => $item["COMPANY_NAME"],
				"address" => $item["COMPANY_ADDRESS"] ?? ""
			]
		];
	}

    public static function makeTgBotEnvFile()
    {
        $data = [];

        $file = $_SERVER["DOCUMENT_ROOT"] . "/local/telegram_bot/.env";

        $needFields = [
            "BOT_API_KEY",
            "BOT_NAME",
            "API_BASE_URL",
            "REG_LINK",
            "INSTRUCTION_LINK",
        ];

        foreach ($needFields as $field)
        {
            $value = Option::get("custom.core", "TG_".$field);

            if($value)
            {
                $data[$field] = $value;
            }
        }

        if($data)
        {
            $fh = fopen($file, 'w+');
            foreach ($data as $key => $val) {
                fwrite($fh, $key . "=" . $val . PHP_EOL);
            }
            fclose($fh);
        }
    }
}
