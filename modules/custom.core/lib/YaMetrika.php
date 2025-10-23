<?php
namespace Custom\Core;

use \Custom\Core\Helper as Helper;
use Bitrix\Main\Config\Option as Option;

class YaMetrika
{
    const url = 'https://mc.yandex.ru/collect/';
    const log_file_error = "/log/metrika_error.txt";
    const log_file_success = "/log/metrika_success.txt";

    private $counter;
    private $token;

    function __construct()
    {
        $this->counter = $this->getCounterNumber();
        $this->token = $this->getToken();
    }

    private function getToken()
    {
        return Option::get("custom.core", "YA_SM_TOKEN");
    }

    private function getCounterNumber()
    {
        $counters = \COption::GetOptionString('yandex.metrika', 'counters', '', SITE_ID);

        try {
            $counters = json_decode($counters, true);
        } catch (\Exception $e) {
            $counters = [];
        }

        if($counters)
        {
            return $counters[0]["number"];
        }

        return false;
    }

    public function send($data)
    {
        $url = self::url;

        if(!$data["tid"])
            $data["tid"] = $this->counter;

        if(!$data["ms"])
            $data["ms"] = $this->token;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        if($info['http_code'] != 200)
        {
            Helper::logToFile($data, "Запрос на сервер", self::log_file_error);
            Helper::logToFile($res, "Ответ от сервера", self::log_file_error);

            return false;
        }
        else
        {
            Helper::logToFile($data, "Запрос на сервер", self::log_file_success);
            Helper::logToFile($res, "Ответ от сервера", self::log_file_success);

            return true;
        }
    }

    public function getActions(){
        $res = \Custom\Core\Analitics\YaMetrikaTable::getList(
            [
                "filter" => [],
                "select" => ["*"],
            ]
        );

        while ($row = $res->Fetch()) {
            $action = unserialize($row["EC_ACTION"]);
            $actions[$row["ID"]]["UID"] = $row["UID"];
            $actions[$row["ID"]]["COUNTER_ID"] = $row["COUNTER_ID"];
            $actions[$row["ID"]]["MS_TOKEN"] = $row["MS_TOKEN"];
            $actions[$row["ID"]]["ACTION"] = $action;
        }

        return $actions;
    }

    public function delActions($id){
        \Custom\Core\Analitics\YaMetrikaTable::delete($id);
    }
    public function sendActionsToMetrika(){
        $actions = self::getActions();

        foreach ($actions as $id => $data)
        {
            if($data["ACTION"]["ecommerce"]["purchase"])
            {
                $action = $data["ACTION"]["ecommerce"]["purchase"];
                $request = [
                    "tid" => $data["COUNTER_ID"],
                    "ms" => $data["MS_TOKEN"],
                    "cid" => $data["UID"],
                    "t" => "event",
                    "pa" => "purchase",
                    "ti" => $action["actionField"]["id"],
                    "tr" => $action["actionField"]["revenue"],
                    "et" => $action["actionField"]["date_time"],
                ];

                foreach ($action["products"] as $key => $product) {
                    $productId = $key + 1;
                    $request["pr{$productId}id"] = $product["id"];
                    $request["pr{$productId}nm"] = $product["name"];
                    $request["pr{$productId}pr"] = $product["price"];
                }
            }

            if($request && self::send($request))
            {
                self::delActions($id);
            }
        }
    }
}
