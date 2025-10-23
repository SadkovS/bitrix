<?php

use Bitrix\Main\Loader;
use Bitrix\Main\ORM;
use Custom\Core\RabbitMQPublisher;
use Custom\Core\SimpleLogger;

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'main',
    'OnBeforeEventAdd',
    function (&$event, &$lid, &$arFields, &$message_id, array &$files) {
        if ($event == 'SALE_ORDER_PAID' || $event == 'SALE_NEW_LEGAL_ORDER') {

            Loader::includeModule('custom.core');
            Loader::includeModule('highloadblock');
            Loader::includeModule('sale');

            $order        = Bitrix\Sale\Order::load($arFields['ORDER_REAL_ID']);
            $personTypeId = $order->getPersonTypeId();
            $service_fee  = (float)$order->getDeliveryPrice();
            $objBasket    = $order->getBasket();
            $basket       = $objBasket->toArray();
            $offersIDs    = array_unique(array_column($basket, 'PRODUCT_ID'));
            $price        = $objBasket->getPrice();
            $quantity     = array_sum($objBasket->getQuantityList());

            $eventId   = (int)$order
                ->getPropertyCollection()
                ->getItemByOrderPropertyCode('EVENT_ID')
                ->getValue();
            $productID = getProductIDbyEventID($eventId);
            $orderUUID = $order->getPropertyCollection()->getItemByOrderPropertyCode('UUID')->getValue();

            $userName = $order->getPropertyCollection()->getItemByOrderPropertyCode('FIO')->getValue();

            $serverName = 'https://' . Bitrix\Main\Config\Option::get('main', 'server_name', '');
            if ($eventId > 0) {
                $eventEntity = new ORM\Query\Query('Custom\Core\Events\EventsTable');
                $query       = $eventEntity
                    ->setSelect(
                        [
                            'UF_NAME',
                            'IMG_SRC',
                            'UF_TYPE',
                            'AGE_LIMIT_' => 'AGE_LIMIT',
                            'LOCATION_'  => 'UF_LOCATION_REF'
                        ]
                    )
                    ->setFilter(['ID' => $eventId])
                    ->registerRuntimeField(
                        new \Bitrix\Main\Entity\ReferenceField(
                            'AGE_LIMIT',
                            '\Custom\Core\FieldEnumTable',
                            ['this.UF_AGE_LIMIT' => 'ref.ID'],
                            ['join_type' => 'LEFT']
                        )
                    )
                    ->setGroup('ID')
                    ->countTotal(true)
                    ->exec();
                if ($query->getCount() > 0) {
                    $arLocations = $query->fetchAll();
                    $eventDates  = [];
                    foreach ($arLocations as $location) {
                        $eventDate = unserialize($location['LOCATION_UF_DATE_TIME']);
                        foreach ($eventDate as $date) {
                            $eventDates[] = $date;
                        }
                    }
                    $resEvent                       = $arLocations[0];

                    if (!isset($arFields['EMAIL']))
                        $arFields['EMAIL'] = $order->getPropertyCollection()->getItemByOrderPropertyCode('EMAIL')->getValue();
                    if (!isset($arFields['SALE_EMAIL'])) $arFields['SALE_EMAIL'] = 'no-reply@voroh.ru';

                    $arFields['EVENT_NAME']         = html_entity_decode($resEvent['UF_NAME'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    $arFields['EVENT_DATE']         = $eventDates;
                    $arFields['EVENT_AGE_LIMIT']    = $resEvent['AGE_LIMIT_XML_ID'];
                    $arFields['EVENT_IMG']          = $resEvent['IMG_SRC'];
                    $arFields['EVENT_ADDRESS']      = $resEvent['LOCATION_UF_ADDRESS'];
                    $arFields['EVENT_ROOM']         = $resEvent['LOCATION_UF_ROOM'];
                    $arFields['EVENT_LINK']         = $serverName . '/event/' . $productID . '/';
                    $arFields['EVENT_TYPE']         = $resEvent['UF_TYPE'];
                    $arFields['ORDER_PRODUCT_LIST'] = '';
                    $arFields['ONLINE_BLOCK']       = '';
                    $arFields['FIO']                = $userName;
                    $arFields['REFUND_LINK']        = $serverName . '/refund/' . $orderUUID . '/';
                    $arFields['SERVER_NAME']        = $serverName;
                    $attachedFiles                  = [];

                    $arFields['EVENT_DATE'] = formatDates($arFields['EVENT_DATE']);
                    unset($eventDate, $eventDates, $key);

                    foreach ($basket as $item) {
                        $arProp = [];
                        foreach ($item['PROPERTIES'] as $prop) {
                            if (($prop['NAME'] == 'Ряд' || $prop['NAME'] == 'Место') && !empty($prop['VALUE'])) {
                                $arProp[] = $prop['VALUE'] . ' ' . $prop['NAME'];
                            }

                            if ($prop['CODE'] === 'UUID') {
                                $uuid = $prop['VALUE'];
                            }
                        }
                        $ticketType                     = getTextBetweenBrackets($item['NAME']);
                        $propInfo                       = '<span class="ticket-row__desc" style="color:#677183;display:block;font-weight:500">' . implode(', ', $arProp) . '</span>';
                        $itemPrice                      = $item["PRICE"] > 0 ? $item["PRICE"] . '&nbsp;₽' : 'Бесплатно';
                        $arFields['ORDER_PRODUCT_LIST'] .= '<table class="row ticket-row" style="border-bottom:2px solid #c7cddb;border-collapse:collapse;border-spacing:0;display:table;padding:0;position:relative;text-align:left;vertical-align:top;width:100%">
                                                                <tbody>
                                                                    <tr style="padding:0;text-align:left;vertical-align:top">
                                                                        <th class="small-8 large-8 columns first" valign="middle" style="-moz-box-sizing:border-box;-moz-hyphens:auto;-webkit-box-sizing:border-box;-webkit-hyphens:auto;Margin:0 auto;border-collapse:collapse!important;box-sizing:border-box;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:150%;margin:0 auto;padding:0;padding-bottom:0;padding-left:0!important;padding-right:0!important;text-align:left;vertical-align:middle;width:415px;word-wrap:break-word">
                                                                            <table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%">
                                                                                <tbody>
                                                                                <tr style="padding:0;text-align:left;vertical-align:top">
                                                                                    <th style="-moz-box-sizing:border-box;-moz-hyphens:auto;-webkit-box-sizing:border-box;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;box-sizing:border-box;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:150%;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
                                                                                        <p class="ticket-row__col"
                                                                                           style="Margin:0;Margin-bottom:0;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:400;line-height:150%;margin:0;margin-bottom:0;padding:16px 20px;text-align:left">
                                                                                            <a class="fw500 text-link"
                                                                                               href="https://' . COption::GetOptionString("main", "server_name") . '/personal/ticket/' . $arFields['ORDER_ID'] . '/' . $uuid . '/"
                                                                                               style="Margin:default;font-family:Montserrat,sans-serif;font-weight:500;line-height:150%;margin:default;padding:0;text-align:left;text-decoration:underline;color:#C92341;"><strog>' . $ticketType . '</strog></a>' . $propInfo . '
                                                                                        </p>
                                                                                    </th>
                                                                                </tr>
                                                                                </tbody>
                                                                            </table>
                                                                        </th>
                                                                        <th class="small-4 large-4 columns last" valign="middle" style="-moz-box-sizing:border-box;-moz-hyphens:auto;-webkit-box-sizing:border-box;-webkit-hyphens:auto;Margin:0 auto;border-collapse:collapse!important;box-sizing:border-box;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:150%;margin:0 auto;padding:0;padding-bottom:0;padding-left:0!important;padding-right:0!important;text-align:left;vertical-align:middle;width:215px;word-wrap:break-word">
                                                                            <table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%">
                                                                                <tbody>
                                                                                    <tr style="padding:0;text-align:left;vertical-align:top">
                                                                                        <th style="-moz-box-sizing:border-box;-moz-hyphens:auto;-webkit-box-sizing:border-box;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;box-sizing:border-box;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:150%;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
                                                                                            <p class="text-right ticket-row__col" style="Margin:0;Margin-bottom:0;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:400;line-height:150%;margin:0;margin-bottom:0;padding:16px 20px;text-align:right">
                                                                                                <span class="price-itm" style="font-size:18px;font-weight:600">' . $itemPrice . ' </span>
                                                                                            </p>
                                                                                        </th>
                                                                                    </tr>
                                                                                </tbody>
                                                                            </table>
                                                                        </th>
                                                                    </tr>
                                                                </tbody>
                                                            </table>';

                    }
                    if ($service_fee > 0) {
                        $arFields['ORDER_PRODUCT_LIST'] .= '<table class="row ticket-row" style="border-bottom:2px solid #c7cddb;border-collapse:collapse;border-spacing:0;display:table;padding:0;position:relative;text-align:left;vertical-align:top;width:100%">
                                                                <tbody>
                                                                    <tr style="padding:0;text-align:left;vertical-align:top">
                                                                        <th class="small-8 large-8 columns first" valign="middle" style="-moz-box-sizing:border-box;-moz-hyphens:auto;-webkit-box-sizing:border-box;-webkit-hyphens:auto;Margin:0 auto;border-collapse:collapse!important;box-sizing:border-box;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:150%;margin:0 auto;padding:0;padding-bottom:0;padding-left:0!important;padding-right:0!important;text-align:left;vertical-align:middle;width:415px;word-wrap:break-word">
                                                                            <table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%">
                                                                                <tbody>
                                                                                <tr style="padding:0;text-align:left;vertical-align:top">
                                                                                    <th style="-moz-box-sizing:border-box;-moz-hyphens:auto;-webkit-box-sizing:border-box;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;box-sizing:border-box;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:150%;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
                                                                                        <p class="ticket-row__col"
                                                                                           style="Margin:0;Margin-bottom:0;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:400;line-height:150%;margin:0;margin-bottom:0;padding:16px 20px;text-align:left">
                                                                                            Сервисный сбор
                                                                                        </p>
                                                                                    </th>
                                                                                </tr>
                                                                                </tbody>
                                                                            </table>
                                                                        </th>
                                                                        <th class="small-4 large-4 columns last" valign="middle" style="-moz-box-sizing:border-box;-moz-hyphens:auto;-webkit-box-sizing:border-box;-webkit-hyphens:auto;Margin:0 auto;border-collapse:collapse!important;box-sizing:border-box;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:150%;margin:0 auto;padding:0;padding-bottom:0;padding-left:0!important;padding-right:0!important;text-align:left;vertical-align:middle;width:215px;word-wrap:break-word">
                                                                            <table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%">
                                                                                <tbody>
                                                                                    <tr style="padding:0;text-align:left;vertical-align:top">
                                                                                        <th style="-moz-box-sizing:border-box;-moz-hyphens:auto;-webkit-box-sizing:border-box;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;box-sizing:border-box;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:400;hyphens:auto;line-height:150%;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
                                                                                            <p class="text-right ticket-row__col" style="Margin:0;Margin-bottom:0;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:400;line-height:150%;margin:0;margin-bottom:0;padding:16px 20px;text-align:right">
                                                                                                <span class="price-itm" style="font-size:18px;font-weight:600">' . $service_fee . '&nbsp;₽</span>
                                                                                            </p>
                                                                                        </th>
                                                                                    </tr>
                                                                                </tbody>
                                                                            </table>
                                                                        </th>
                                                                    </tr>
                                                                </tbody>
                                                            </table>';
                    }
                    $arFields['BASKET_QTY']    = \Custom\Core\Helper::declination($quantity, ['билет', 'билета', 'билетов']);
                    $arFields['ORDER_SUM']     = $order->getPrice();
                    $arFields['ADDITION_INFO'] = '';
                    if ((int)$arFields['EVENT_AGE_LIMIT'] >= 18) {
                        $arFields['ADDITION_INFO'] = '<div style="font-family:Commissioner;font-size:20px;font-weight:400;line-height:30px;text-align:center;color:#1A2E54;">
                                                        ВАЖНО: мероприятие имеет возрастное органичение 18+, <br>
                                                        поэтому, пожалуйста, возьмите с собой документ, <br>
                                                        подтверждающий ваш возраст.
                                                    </div>';
                    }


                    if (count($attachedFiles) > 0) {
                        $files = array_merge($files, $attachedFiles);
                    }

                    $showOnlineBlock = showOnlineBlock($offersIDs);

                    if ($arFields['EVENT_TYPE'] != '7' && $showOnlineBlock) {

                        foreach ($arLocations as &$location) {
                            $location['LOCATION_UF_DATE_TIME'] = unserialize($location['LOCATION_UF_DATE_TIME']);
                            $location['LOCATION_UF_LINK']      = json_decode($location['LOCATION_UF_LINK'][0], true);
                        }
                        $productIDs = array_column($basket, 'PRODUCT_ID') ?? [];
                        $arDates    = makeArDatesFilter($productIDs);
                        $arFields['ONLINE_BLOCK'] .= '
                            <!--ССЫЛКА НА ТРАНСЛЯЦИЮ -->
                            <table class="spacer hide-for-large" style="border-collapse:collapse;border-spacing:0;display:none;font-size:0;line-height:0;max-height:0;mso-hide:all;overflow:hidden;padding:0;text-align:left;vertical-align:top;width:100%"><tbody style="mso-hide:all"><tr style="mso-hide:all;padding:0;text-align:left;vertical-align:top"><td height="20" style="-moz-box-sizing:border-box;-moz-hyphens:auto;-webkit-box-sizing:border-box;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;box-sizing:border-box;color:#021231;font-family:Montserrat,sans-serif;font-size:20px;font-weight:400;hyphens:auto;line-height:20px;margin:0;mso-hide:all;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">&nbsp;</td></tr></tbody></table><table class="spacer show-for-large" style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%"><tbody><tr style="padding:0;text-align:left;vertical-align:top"><td height="30" style="-moz-box-sizing:border-box;-moz-hyphens:auto;-webkit-box-sizing:border-box;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;box-sizing:border-box;color:#021231;font-family:Montserrat,sans-serif;font-size:30px;font-weight:400;hyphens:auto;line-height:30px;margin:0;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">&nbsp;</td></tr></tbody></table>';
                        $arFields['ONLINE_BLOCK'] .= formatDatesAndLinksHTML($arLocations, $arDates);
                    }
                }
            }

            $orderId = $order->getId();

            if ($event == 'SALE_ORDER_PAID') {
                SimpleLogger::log('Trying to send data to RabbitMQ. Order ID: ' . $orderId, 'I', 'rabbit', 'rabbit_sender');

                try {
                    RabbitMQPublisher::publish($arFields);

                    SimpleLogger::log('Successfully published to RabbitMQ. Order ID: ' . $orderId, 'I', 'rabbit', 'rabbit_sender');

                    // Отменяем системную отправку письмо ушло в Rabbit
                    return false;

                } catch (\Exception $e) {
                    SimpleLogger::log(
                        'Failed to publish to RabbitMQ. Order ID: ' . $orderId . '. Error: ' . $e->getMessage(),
                        'E', 'rabbit', 'rabbit_sender'
                    );

                    // 🔥 Фолбэк — пробуем отправить стандартным способом
                    try {
                        \CEvent::Send(
                            $arFields['EVENT_NAME'],
                            SITE_ID,
                            $arFields
                        );
                        SimpleLogger::log(
                            'Fallback: mail sent by Bitrix engine. Order ID: ' . $orderId,
                            'I', 'bitrix', 'order_core_sender'
                        );
                    } catch (\Exception $ex) {
                        SimpleLogger::log(
                            'Fallback mail also failed. Order ID: ' . $orderId . '. Error: ' . $ex->getMessage(),
                            'E', 'bitrix', 'order_core_sender'
                        );
                    }
                }
            }

        }

        return true;
    }

);

function getDateItemLayout($date)
{
    return '<table align="center" class="spacer float-center show-for-large" style="Margin:0 auto;border-collapse:collapse;border-spacing:0;float:none;margin:0 auto;padding:0;text-align:center;vertical-align:top;width:100%">
                                                                            <tbody>
                                                                            <tr style="padding:0;text-align:left;vertical-align:top">
                                                                                <td height="5" style="-moz-box-sizing:border-box;-moz-hyphens:auto;-webkit-box-sizing:border-box;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;box-sizing:border-box;color:#021231;font-family:Montserrat,sans-serif;font-size:5px;font-weight:400;hyphens:auto;line-height:5px;margin:0;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">&nbsp;</td>
                                                                            </tr>
                                                                            </tbody>
                                                                        </table>
                                                                        <p class="text-center fw500 float-center" align="center" style="Margin:0;Margin-bottom:0;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:500;line-height:150%;margin:0;margin-bottom:0;padding:0;text-align:center">
                                                                            <b>' . $date . '</b>
                                                                        </p>
                                                                        <table align="center" class="spacer float-center hide-for-large" style="Margin:0 auto;border-collapse:collapse;border-spacing:0;display:none;float:none;font-size:0;line-height:0;margin:0 auto;max-height:0;mso-hide:all;overflow:hidden;padding:0;text-align:center;vertical-align:top;width:100%">
                                                                            <tbody style="mso-hide:all">
                                                                            <tr style="mso-hide:all;padding:0;text-align:left;vertical-align:top">
                                                                                <td height="5" style="-moz-box-sizing:border-box;-moz-hyphens:auto;-webkit-box-sizing:border-box;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;box-sizing:border-box;color:#021231;font-family:Montserrat,sans-serif;font-size:5px;font-weight:400;hyphens:auto;line-height:5px;margin:0;mso-hide:all;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">&nbsp;</td>
                                                                            </tr>
                                                                            </tbody>
                                                                        </table>';
}

function formatDates($arDates = [])
{
    $result    = '';
    $startDate = null;
    $prevDate  = null;
    usort(
        $arDates, function ($a, $b) {
        return strtotime($a) - strtotime($b);
    }
    );

    foreach ($arDates as $date) {
        $currentDate = new DateTime($date);

        if ($startDate === null) {
            $startDate = $currentDate;
            $prevDate  = $currentDate;
        } else {
            $diff     = $currentDate->diff($prevDate);
            $sameTime = $currentDate->format('H:i:s') === $prevDate->format('H:i:s');

            if ($diff->days === 1 && $sameTime) {
                $prevDate = $currentDate;
            } else {
                if ($startDate === $prevDate) {
                    $result .= FormatDate("d F Y", $startDate->getTimestamp()) . ' в ' . FormatDate("H:i", $startDate->getTimestamp()) . '<br>';
                } else {
                    $result .= FormatDate("d F Y", $startDate->getTimestamp()) . " - " . FormatDate("d F Y", $prevDate->getTimestamp()) . ' в ' . FormatDate("H:i", $prevDate->getTimestamp()) . '<br>';
                }
                $startDate = $currentDate;
                $prevDate  = $currentDate;
            }
        }
    }

    // Добавляем последний диапазон или дату
    if ($startDate === $prevDate) {
        $result .= FormatDate("d F Y", $startDate->getTimestamp()) . ' в ' . FormatDate("H:i", $startDate->getTimestamp()) . '<br>';
    } else {
        $result .= FormatDate("d F Y", $startDate->getTimestamp()) . " - " . FormatDate("d F Y", $prevDate->getTimestamp()) . ' в ' . FormatDate("H:i", $prevDate->getTimestamp()) . '<br>';
    }

    return $result;
}

function getTextBetweenBrackets($str)
{
    if (preg_match('/\[(.*?)\]/', $str, $matches)) {
        return $matches[1];
    }
    return '';
}

function makeArDatesFilter(array $productIDs)
{
    Loader::includeModule('iblock');

    $elementEntity = \Bitrix\Iblock\IblockTable::compileEntity('ticketsOffers');
    $propField     = $elementEntity->getField('CML2_LINK');
    $propEntity    = $propField->getRefEntity();

    $dbRes = \Bitrix\Iblock\Elements\ElementTicketsTable::getList(
        [
            'select'  => [
                //'*',
                'SKU_ID'        => 'OFFER.ID',
                'SKU_DATES',
                'SKU_DATES_ALL' => 'OFFER.DATES_ALL.VALUE',
            ],
            'filter'  => ['SKU_ID' => $productIDs],
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
            ],
        ]
    );

    $res = [];
    while ($sku = $dbRes->fetch()) {
        $sku['SKU_DATES'] ? $sku['SKU_DATES'] = explode(';', $sku['SKU_DATES']) : $sku['SKU_DATES'] = [];

        foreach ($sku['SKU_DATES'] as &$date) {
            $date = (new DateTime($date))->format('Y-m-d');
        }

        if ($sku['SKU_DATES_ALL'] == 'all') {
            $res = [];
            break;
        }
        $res = array_merge($sku['SKU_DATES'], $res);
    }
    if (count($res) > 0) $res = array_unique($res);
    return $res;
}

function getProductIDbyEventID(int $eventID): int
{
    $productEntity = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
    $productClass  = $productEntity->getDataClass();
    $query         = new ORM\Query\Query($productEntity);
    $resProduct    = $query
        ->setSelect(['ID'])
        ->setFilter(['EVENT_ID.VALUE' => $eventID])
        ->exec();

    $resProduct = $resProduct->fetch();
    return (int)$resProduct['ID'];
}

function formatDatesAndLinksHTML($arrLocations, $arDates = [])
{
    $html      = '';
    $allRanges = [];

    foreach ($arrLocations as $location) {
        $dates    = $location['LOCATION_UF_DATE_TIME'];
        $linkName = $location['LOCATION_UF_LINK'][0] ?? 'Ссылка на трансляцию';
        $link     = $location['LOCATION_UF_LINK'][1] ?? '';

        // Сортируем даты
        usort(
            $dates, function ($a, $b) {
            return strtotime($a) - strtotime($b);
        }
        );

        $ranges       = [];
        $currentRange = [];

        foreach ($dates as $date) {
            $timestamp   = strtotime($date);
            $currentDate = date('Y-m-d', $timestamp); // для сравнения
            $displayDate = date('d.m.Y', $timestamp); // для вывода
            $time        = date('H:i', $timestamp);

            if (empty($currentRange)) {
                $currentRange = [
                    'start'        => $currentDate,
                    'end'          => $currentDate,
                    'displayStart' => $displayDate,
                    'displayEnd'   => $displayDate,
                    'time'         => $time,
                    'link'         => $link,
                    'linkName'     => $linkName,
                ];
            } else {
                $prevTimestamp = strtotime($currentRange['end']);
                $diff = (strtotime($currentDate) - $prevTimestamp) / (60 * 60 * 24);

                if ($diff == 1 && $time === $currentRange['time']) {
                    $currentRange['end'] = $currentDate;
                    $currentRange['displayEnd'] = $displayDate;
                } else {
                    $ranges[] = $currentRange;
                    $currentRange = [
                        'start'        => $currentDate,
                        'end'          => $currentDate,
                        'displayStart' => $displayDate,
                        'displayEnd'   => $displayDate,
                        'time'         => $time,
                        'link'         => $link,
                        'linkName'     => $linkName,
                    ];
                }
            }
        }
        $ranges[]  = $currentRange;
        $allRanges = array_merge($allRanges, $ranges);
    }

    // Сортируем все диапазоны по дате начала
    usort(
        $allRanges, function ($a, $b) {
        return strtotime($a['start']) - strtotime($b['start']);
    }
    );

    // Если задан массив дат, обрезаем диапазоны
    if (!empty($arDates)) {
        $allRanges = array_filter(
            $allRanges, function ($range) use ($arDates) {
            $rangeDates = [];
            $current    = strtotime($range['start']);
            $end        = strtotime($range['end']);

            while ($current <= $end) {
                $rangeDates[] = date('Y-m-d', $current);
                $current      = strtotime('+1 day', $current);
            }

            // Проверяем, есть ли хотя бы одна дата из $arDates в диапазоне
            return count(array_intersect($rangeDates, $arDates)) > 0;
        }
        );

        // Разбиваем диапазоны на отдельные даты из $arDates
        $newRanges = [];
        foreach ($allRanges as $range) {
            $rangeDates = [];
            $current    = strtotime($range['start']);
            $end        = strtotime($range['end']);

            while ($current <= $end) {
                $rangeDates[] = date('Y-m-d', $current);
                $current      = strtotime('+1 day', $current);
            }

            $intersectDates = array_intersect($rangeDates, $arDates);
            sort($intersectDates);

            // Формируем новые диапазоны только из последовательных дат
            $tempRange = null;
            foreach ($intersectDates as $date) {
                $displayDate = date('d.m.Y', strtotime($date));
                if ($tempRange === null) {
                    $tempRange = [
                        'start'        => $date,
                        'end'          => $date,
                        'displayStart' => $displayDate,
                        'displayEnd'   => $displayDate,
                        'time'         => $range['time'],
                        'link'         => $range['link'],
                        'linkName'     => $range['linkName']
                    ];
                } else {
                    $prevDate    = $tempRange['end'];
                    $currentDate = $date;
                    $diff        = (strtotime($currentDate) - strtotime($prevDate)) / (60 * 60 * 24);

                    if ($diff == 1) {
                        $tempRange['end'] = $date;
                        $tempRange['displayEnd'] = $displayDate;
                    } else {
                        $newRanges[] = $tempRange;
                        $tempRange   = [
                            'start'        => $date,
                            'end'          => $date,
                            'displayStart' => $displayDate,
                            'displayEnd'   => $displayDate,
                            'time'         => $range['time'],
                            'link'         => $range['link'],
                            'linkName'     => $range['linkName']
                        ];
                    }
                }
            }
            if ($tempRange !== null) {
                $newRanges[] = $tempRange;
            }
        }
        $allRanges = $newRanges;
    }

    foreach ($allRanges as $range) {
        if ($range['displayStart'] === $range['displayEnd']) {
            $result = sprintf(
                "Онлайн-трансляция %s, %s:",
                FormatDate("d F Y", strtotime($range['displayStart'])),
                $range['time'],
            );
        } else {
            $result = sprintf(
                "Онлайн-трансляция %s–%s %s, %s:",
                FormatDate('d', strtotime($range['displayStart'])),
                FormatDate('d.m.Y', strtotime($range['displayEnd'])),
                FormatDate('F Y', strtotime($range['displayEnd'])),
                $range['time'],
            );
        }

        $html .= '<center style="width:100%">
            <p class="text-center fw500 float-center" align="center" style="Margin:0;Margin-bottom:0;color:#021231;font-family:Montserrat,sans-serif;font-size:16px;font-weight:500;line-height:150%;margin:0;margin-bottom:0;padding:0;text-align:center">
                ' . $result . '<br>
                <a href="' . $range['link'] . '" class="link fw500" style="Margin:default;color:#ff2020;font-family:Montserrat,sans-serif;font-weight:500;line-height:150%;margin:default;padding:0;text-align:left;text-decoration:underline">' . $range['linkName'] . '</a>
            </p>
        </center>
        <table class="spacer hide-for-large" style="border-collapse:collapse;border-spacing:0;display:none;font-size:0;line-height:0;max-height:0;mso-hide:all;overflow:hidden;padding:0;text-align:left;text-align:left;vertical-align:top;width:100%"><tbody style="mso-hide:all"><tr style="mso-hide:all;padding:0;text-align:left;vertical-align:top"><td height="20" style="-moz-box-sizing:border-box;-moz-hyphens:auto;-webkit-box-sizing:border-box;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;box-sizing:border-box;color:#021231;font-family:Montserrat,sans-serif;font-size:20px;font-weight:400;hyphens:auto;line-height:20px;margin:0;mso-hide:all;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">&nbsp;</td></tr></tbody></table><table class="spacer show-for-large" style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%"><tbody><tr style="padding:0;text-align:left;vertical-align:top"><td height="30" style="-moz-box-sizing:border-box;-moz-hyphens:auto;-webkit-box-sizing:border-box;-webkit-hyphens:auto;Margin:0;border-collapse:collapse!important;box-sizing:border-box;color:#021231;font-family:Montserrat,sans-serif;font-size:30px;font-weight:400;hyphens:auto;line-height:30px;margin:0;mso-line-height-rule:exactly;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">&nbsp;</td></tr></tbody></table>';
    }

    return $html;
}

function showOnlineBlock(array $offerIDs): bool
{
    $elementEntity = \Bitrix\Iblock\IblockTable::compileEntity('ticketsOffers');
    $propField     = $elementEntity->getField('CML2_LINK');
    $propEntity    = $propField->getRefEntity();
    $dbRes = \Bitrix\Iblock\Elements\ElementTicketsTable::getList(
        [
            'select'  => [
                'TYPE_PARTICIPATION' => 'TYPE_PARTICIPATION_REF.XML_ID',
                'SKU_ID' => 'OFFER.ID',
            ],
            'filter'  => ['SKU_ID' => $offerIDs, '!TYPE_PARTICIPATION' => 'offline'],
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
                    'TYPE_PARTICIPATION_REF',
                    '\Bitrix\Iblock\PropertyEnumerationTable',
                    ['this.OFFER.TYPE_PARTICIPATION.VALUE' => 'ref.ID'],
                    ['join_type' => 'LEFT'],
                ),

            ],
            'limit'   => 1,
            'count_total' => true,
        ]
    );
    return $dbRes->GetCount() > 0;
}