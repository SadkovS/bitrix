<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

$request = Context::getCurrent()->getRequest();
Loc::loadMessages(__FILE__);
Loader::includeModule('wejet.pikta');
$module_id = "custom.core";
$aTabs     = [
    [
        "DIV"     => "edit1",
        "TAB"     => "Bitrix 24",
        "TITLE"   => "Настройки Bitrix 24",
        "OPTIONS" => [
            //Loc::getMessage("OPTIONS_TAB_COMMON"),
            [
                "B24_Host",
                "B24 Host",
                "",
                ["text", 50]
            ],
        ]
    ],
    [
        "DIV"     => "maps",
        "TAB"     => "Яндекс Карты",
        "TITLE"   => "Яндекс Карты",
        "OPTIONS" => [
            [
                "YA_API_TOKEN",
                "АПИ Токен поиска по организациям",
                "fc7ed658-29a0-4326-afb9-8938be54d986",
                ["text", 50]
            ],
             [
                "YA_GEO_API_TOKEN",
                "АПИ Токен Геолокации",
                "584d10d9-7d6c-4696-974b-0ea4297127a9",
                ["text", 50]
            ],
        ]
    ],
    [
        "DIV"     => "dadata",
        "TAB"     => "Дадата",
        "TITLE"   => "Дадата",
        "OPTIONS" => [
            [
                "DDT_API_TOKEN",
                "Мастер АПИ Токен",
                "a1350a1a780eecf5a1b812ecbb5e75e51476970b",
                ["text", 50]
            ],
            [
                "DDT_SECRET_TOKEN",
                "Мастер secret Токен",
                "f29c38f55f74c479d596dd7d928c2faa6ef1fb22",
                ["text", 50]
            ],
        ]
    ],
    [
        "DIV"     => "edit2",
        "TAB"     => "SMS RU",
        "TITLE"   => "Настройки интеграции с sms.ru",
        "OPTIONS" => [
            [
                "SMS_RU_API_Host",
                "SMS_RU_API_Host",
                "",
                ["text", 50]
            ],
            [
                "SMS_RU_API_ID",
                "SMS_RU_API_ID",
                "",
                ["text", 50]
            ]
        ]
    ],
    [
        "DIV"     => "edit3",
        "TAB"     => "SeatMap",
        "TITLE"   => "Настройки интеграции с SeatMap",
        "OPTIONS" => [
            [
                "SEAT_MAP_EDITOR_API_Host",
                "SeatMap Editor API",
                "",
                ["text", 50]
            ],
            [
                "SEAT_MAP_BOOKING_API_Host",
                "SeatMap Booking API",
                "",
                ["text", 50]
            ],
            [
                "SEAT_MAP_LOGIN",
                "SeatMap Admin Login",
                "",
                ["text", 50]
            ],
            [
                "SEAT_MAP_PASSWORD",
                "SeatMap Admin Password",
                "",
                ["text", 50]
            ],
            [
                'SEAT_MAP_RELOAD_TIMEOUT',
                'интервал обновления схемы рассадки (сек)',
                '',
                ['text', 50],
            ],
            [
                'SEAT_MAP_MAX_QUANTITY',
                'максимальное кол-во билетов в заказе для каждой категории',
                '',
                ['text', 50],
            ],
        ],
    ],
    [
	    "DIV"     => "ya_captcha",
	    "TAB"     => "Yandex SmartCaptcha",
	    "TITLE"   => "Yandex SmartCaptcha",
	    "OPTIONS" => [
		    [
			    "YA_CAPTCHA_CLIENT",
			    "Ключ клиента",
			    "",
			    ["text", 50]
		    ],
		    [
			    "YA_CAPTCHA_SERVER",
			    "Ключ сервера",
			    "",
			    ["text", 50]
		    ],
	    ]
    ],
    [
        "DIV"     => "tg_bot",
        "TAB"     => "Telegram bot",
        "TITLE"   => "Telegram bot",
        "OPTIONS" => [
            [
                "TG_BOT_API_KEY",
                "BOT_API_KEY",
                "",
                ["text", 50]
            ],
            [
                "TG_BOT_NAME",
                "BOT_NAME",
                "",
                ["text", 50]
            ],
            [
                "TG_API_BASE_URL",
                "API_BASE_URL",
                "",
                ["text", 50]
            ],
            [
                "TG_REG_LINK",
                "REG_LINK",
                "",
                ["text", 50]
            ],
            [
                "TG_INSTRUCTION_LINK",
                "INSTRUCTION_LINK",
                "",
                ["text", 50]
            ],
        ]
    ],
    [
        "DIV"     => "rabbitmq",
        "TAB"     => "RabbitMQ",
        "TITLE"   => "RabbitMQ",
        "OPTIONS" => [
            [
                "RABBITMQ_SERVER",
                "Сервер",
                "",
                ["text", 50]
            ],
            [
                "RABBITMQ_PORT",
                "Порт",
                "",
                ["text", 50]
            ],
            [
                "RABBITMQ_LOGIN",
                "Логин",
                "",
                ["text", 50]
            ],
            [
                "RABBITMQ_PASS",
                "Пароль",
                "",
                ["text", 50]
            ],
            [
                "RABBITMQ_VHOST",
                "Vhost",
                "",
                ["text", 50]
            ],
            [
                "RABBITMQ_QOS",
                "Кол-во сообщений для обработки за проход",
                "",
                ["text", 50]
            ],
        ]
    ],
    [
        "DIV"     => "smtp",
        "TAB"     => "SMTP",
        "TITLE"   => "SMTP",
        "OPTIONS" => [
            [
                "SMTP_SERVER",
                "Сервер",
                "",
                ["text", 50]
            ],
            [
                "SMTP_PORT",
                "Порт",
                "",
                ["text", 50]
            ],
            [
                "SMTP_LOGIN",
                "Логин",
                "",
                ["text", 50]
            ],
            [
                "SMTP_PASS",
                "Пароль",
                "",
                ["text", 50]
            ],
            [
                "SMTP_EMAIL",
                "Отправитель",
                "",
                ["text", 50]
            ],
            [
                "SMTP_NAME",
                "Имя",
                "",
                ["text", 50]
            ],
            [
                "MAX_MAIL_RETRY_ATTEMPTS",
                "Кол-во попыток отправки",
                "",
                ["text", 50]
            ],
        ]
    ],
    [
        "DIV"     => "cloudpayments",
        "TAB"     => "Cloudpayments",
        "TITLE"   => "Cloudpayments",
        "OPTIONS" => [
            [
                "CLOUDPAYMENTS_PUBLIC_ID",
                "Public ID",
                "",
                ["text", 50]
            ],
            [
                "CLOUDPAYMENTS_API_SECRET",
                "Пароль для API",
                "",
                ["text", 50]
            ],
            [
                "CLOUDPAYMENTS_NOTIFICATION_TG_CHAT_ID",
                "ID чата для оповещений Telegram",
                "",
                ["text", 50]
            ],
            [
                "CLOUDPAYMENTS_REPORT_EMAILS",
                "Email для получения расхождений",
                "",
                ["text", 50]
            ],
        ]
    ],
    [
        "DIV"     => "ya_metrika",
        "TAB"     => "Yandex Metrika Tokens",
        "TITLE"   => "Yandex Metrika Tokens",
        "OPTIONS" => [
            [
                "YA_SM_TOKEN",
                "Ключ Measurement Protocol",
                "",
                ["text", 50]
            ],
        ]
    ],
];
if ($request->isPost() && check_bitrix_sessid()) {
    foreach ($aTabs as $aTab) {
        foreach ($aTab["OPTIONS"] as $arOption) {
            if (!is_array($arOption)) {
                continue;
            }
            if ($arOption["note"]) {
                continue;
            }
            if ($request["apply"]) {
                $optionValue = $request->getPost($arOption[0]);
                if ($arOption[0] == "switch_on") {
                    if ($optionValue == "") {
                        $optionValue = "N";
                    }
                }
                Option::set($module_id, $arOption[0], is_array($optionValue) ? implode(",", $optionValue) : $optionValue);
            } elseif ($request["default"]) {
                Option::set($module_id, $arOption[0], $arOption[2]);
            }
        }
    }

    if ($request["apply"])
    {
        \Custom\Core\Helper::makeTgBotEnvFile();
    }

    LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . $module_id . "&lang=" . LANG);
}
$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
);
$tabControl->Begin(); ?>
    <form action="<?= ($APPLICATION->GetCurPage()); ?>?mid=<?= $module_id; ?>&lang=<?= (LANG); ?>" method="post">
        <?
        foreach ($aTabs as $aTab) {
            if ($aTab["OPTIONS"]) {
                $tabControl->BeginNextTab();
                __AdmSettingsDrawList($module_id, $aTab["OPTIONS"]);
            }
        }
        $tabControl->Buttons();
        ?>
        <input type="submit" name="apply" value="<?= (Loc::GetMessage("FANSTORE_OPTIONS_INPUT_APPLY")); ?>"
               class="adm-btn-save"/>
        <input type="submit" name="default" value="<?= (Loc::GetMessage("FANSTORE_OPTIONS_INPUT_DEFAULT")); ?>"/>
        <?= (bitrix_sessid_post()); ?>
    </form>
<? $tabControl->End();