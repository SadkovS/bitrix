<?php

namespace Local\Api\Controllers\V1;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\ORM;
use Bitrix\Main\Application;
use \Custom\Core\TgGroup as TgGroupCLass;

class TgGroup
{
    function __construct()
    {
        Loader::includeModule("custom.core");
    }

    public function addGroupInfo()
    {
        $request = request()->get();

        TgGroupCLass::add($request);

        response()->json(
            [
                'status' => 'success',
            ],
            200,
            [],
            ['Content-Type' => 'application/json']
        );
    }
    public function delGroupInfo()
    {
        $request = request()->get();

        TgGroupCLass::del($request);

        response()->json(
            [
                'status' => 'success',
            ],
            200,
            [],
            ['Content-Type' => 'application/json']
        );
    }

    public function getGroupInfo()
    {
        $request = request()->get();

        $items = TgGroupCLass::get($request);

        response()->json(
            [
                'status' => 'success',
                'items' => $items,
            ],200,[],['Content-Type' => 'application/json']
        );
    }

    public function addWidget()
    {
        $request = request()->get();

        $uuid = TgGroupCLass::addWidget($request);

        response()->json(
            [
                'status' => 'success',
                'uuid' => $uuid,
            ],
            200,
            [],
            ['Content-Type' => 'application/json']
        );
    }
}