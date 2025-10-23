<?php

use Bitrix\Main\Config\Option;

//Каталог Билетов
define('IBLOCK_TICKETS_ID', 4);
//Торговые предложения билетов
define('IBLOCK_TICKET_OFFERS_ID', 6);
//ID property CML2_LINK
define('CML2_LINK_PROPERTY_ID', 26);
//События
define('HL_EVENTS_ID', 6);
//Статусы событий
define('HL_EVENTS_STATUS_ID', 4);
//Категории событий
define('HL_EVENTS_CATEGORY_ID', 5);
//Дата и место мероприятия
define('HL_EVENTS_LOCATION_ID', 7);
//ID HL промокодов
define('HL_PROMOCODES_ID', 12);
//ID HL штрих-кодов
define('HL_BARCODES_ID', 17);
//ID HL ценовых правил
define('HL_PRICE_RULES_ID', 13);
//ID HL вопросов
define('HL_QUESTIONNARES', 8);
//Директория категорий событий
define('CATEGORY_EVENTS_URL', "/compilation/#CATEGORY_CODE#/");

define('PRICE_RULE_TYPE_COUPON', 52);
define('PRICE_RULE_TYPE_GROUP', 53);
define('PRICE_RULE_TYPE_DISCOUNT', 54);

define("DISCOUNT_TYPE_PERCENT", 50);
define("DISCOUNT_TYPE_RUB", 51);

define("FIELD_DISCOUNT_TYPE_ID", 112);
define("FIELD_TYPE_OF_APPLY_ID", 110);

define("COMPANY_TYPE_SE", 55);// самозанятый
define("COMPANY_TYPE_LE", 56);// юридическое лицо
define("ORGANIZER_AND_STAFF_GROUP_ID", 9);

define("TICKET_PDF_SALT", 'funstore-ticket');
define("TICKET_TEMPLATE_PATH", '/local/templates/ticket');

define("EVENT_STATUS_PUBLISHED", 5);
define("EVENT_STATUS_COMPLETED", 6);

define("CHECK_TICKET_TIME_RESERVE_IN_HOURS_UNTIL", 7);
define("CHECK_TICKET_TIME_RESERVE_IN_HOURS_AFTER", 2);

define("TICKET_RESERVE_MIN_DEF", 15);

define("YAMAP_API_KEY", "3b74120a-980c-4015-9d38-3755bc78f355");

define("COMPILATION_SPECIAL", "/compilation/special/");
define("COMPILATION_SPECIAL_NAME", "Специально для вас");

define("COMPANY_ADMINISTRATORS_GROUP_CODE", "company_administrators");

define("UPLOAD_TMP_DIR", "/upload/tmp/");

//dev test srv
if (
    in_array(
        $_SERVER['SERVER_NAME'],
        [
            "funstore.220.dev-server.pro",
            "funstore.loc",
            "bitrix"
        ]
    )
) {
    define('B24_WORK_GROUP_ID', 15);
    define('B24_RESPONSIBLE_ID', 1);
    define('B24_STAGE_ID_NEW', 58);
    define('B24_STAGE_ID_REJECT', 61);
    define('B24_EVENT_ID_FIELD_NAME', 'UF_AUTO_336876438596');
    define('B24_CONTACT_ID_FIELD_NAME', 'UF_AUTO_589623254641');
    define("USE_SEATMAP", true);
} else {//prod srv
    define('B24_STAGE_ID_NEW', 81);
    define('B24_WORK_GROUP_ID', 15);
    define('B24_RESPONSIBLE_ID', 1);
    define('B24_STAGE_ID_REJECT', 90);
    define('B24_EVENT_ID_FIELD_NAME', 'UF_AUTO_336876438596');
    define('B24_CONTACT_ID_FIELD_NAME', 'UF_AUTO_589623254641');
    define("USE_SEATMAP", true);
}
