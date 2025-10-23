<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/crest/crest.php');
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

$request = Application::getInstance()->getContext()->getRequest()->toArray();


define('SMARTCAPTCHA_SERVER_KEY', Option::get('custom.core', 'YA_CAPTCHA_SERVER'));

function getClientIP() {
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	return $ip;
}

function check_captcha($token) {
	$ch = curl_init("https://smartcaptcha.yandexcloud.net/validate");
	$args = [
		"secret" => SMARTCAPTCHA_SERVER_KEY,
		"token" => $token,
		"ip" => getClientIP(),
	];
	curl_setopt($ch, CURLOPT_TIMEOUT, 1);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$server_output = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($httpcode !== 200) {
		throw new \Exception("Allow access due to an error: code=$httpcode; message=$server_output\n");
		return true;
	}

	$resp = json_decode($server_output);
	return $resp->status === "ok";
}

$token = $request['smart-token'];
if (!check_captcha($token)) {
	throw new \Exception('Ошибка при проверке Captcha');
}


try {
    if($request['phone'])
    {
        $request['phone'] = '7'.preg_replace("/[^0-9]/", '', $request['phone']);
    }

    $resContact = CRest::call(
        'crm.contact.add',
        ['fields' =>
             [
                 'NAME'        => $request['first_name'],
                 'LAST_NAME'   => $request['last_name'],
                 'SECOND_NAME' => $request['patronymic'],
                 'EMAIL'       => [["VALUE" => $request['email']]],
                 'PHONE'       => [['VALUE' => $request['phone'], 'VALUE_TYPE' => 'WORK']],
             ]
        ]
    );
    $contactId  = $resContact["result"];
    if (!$contactId) {
        \Bitrix\Main\Diag\Debug::writeToFile([$request, $resContact], '','_B24_support_error.txt');
        throw new \Exception('Контакт не создан');
    }
    $arRequest = $request;
    unset($arRequest['first_name'], $arRequest['last_name'], $arRequest['patronymic'], $arRequest['email'], $arRequest['phone']);
    $arRequest['contactId'] = $contactId;

    if (isset($_FILES['ufCrm10_1739953862728']['tmp_name']) && !empty($_FILES['ufCrm10_1739953862728']['tmp_name'])) {
        $arRequest["ufCrm10_1739953862728"] = [
            'fileData'=>[
                0=>$_FILES['ufCrm10_1739953862728']['name'],
                1=>base64_encode(file_get_contents($_FILES['ufCrm10_1739953862728']['tmp_name']))
            ]
        ];
    }

    $result                 = CRest::call(
        'crm.item.add',
        [
            'entityTypeId' => 1050,
            'fields'       => $arRequest,
        ]
    );
    if ((int)$result["result"]["item"]["id"] < 1) {
        \Bitrix\Main\Diag\Debug::writeToFile([$request, $result], '','_B24_support_error.txt');
        throw new \Exception('Обращение не создано');
    }

    echo json_encode(
        [
            "title"   => 'Сообщение успешно отправлено',
            "message" => 'Обращение зарегистрировано',
            "status"  => 'success'
        ]
    );

} catch (\Exception $e) {
    echo json_encode(
        [
            "title"   => 'Ошибка при отправке сообщения',
            "message" => $e->getMessage(),
            "status"  => 'error'
        ]
    );
}