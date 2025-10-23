<?php
namespace Custom\Core;

use Bitrix\Main\Application;

class Session
{
    private $sessionKey = "widget_sessid";
    private $cipher_algo = "AES-256-CBC";
    private $salt = "VoRoHtockaRU";
    private $ip;

    public function __construct()
    {
        $this->ip = $_SERVER["REMOTE_ADDR"];
    }

    public function checkRequest()
    {
        $objRequest = \Bitrix\Main\Context::getCurrent()->getRequest();
        $request = $objRequest->toArray();

        foreach ($request as $key => $val)
        {
            $decryptKey = $this->decrypt($key, $this->ip);

            if($decryptKey == $this->sessionKey)
            {
                $sessId = $this->decrypt($val, $this->ip);

                if(trim($sessId) != null)
                {
                    $this->setSession($sessId);
                }
            }
        }
    }

    public function makeRequest($sessId)
    {
        $key = $this->encrypt($this->sessionKey, $this->ip);
        $val = $this->encrypt($sessId, $this->ip);

        return [$key, $val];
    }

    public function setSession($sessId)
    {
        session_id($sessId);
        session_start();

        if($_REQUEST["sessid"] && $_REQUEST["sessid"] != $sessId)
        {
            $_REQUEST["sessid"] = $sessId;
        }
    }

    public function encrypt($data, $key)
    {
        return base64_encode(openssl_encrypt($data, $this->cipher_algo, $key, 0, $this->salt));
    }

    public function decrypt($data, $key)
    {
        return openssl_decrypt(base64_decode($data), $this->cipher_algo, $key, 0, $this->salt);
    }
}
