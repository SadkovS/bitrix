<?php
header('Content-Type: application/json');

$ip = $_SERVER['REMOTE_ADDR'];
$json = file_get_contents('http://www.geoplugin.net/json.gp?ip=' . $ip);
$data = json_decode($json);

if ($data && $data->geoplugin_countryName) {
	echo json_encode([
		'success' => true,
		'country' => $data->geoplugin_countryName
	]);
} else {
	echo json_encode([
		'success' => false,
		'error' => 'Не удалось определить страну'
	]);
}