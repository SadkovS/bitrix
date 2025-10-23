<?php

namespace Local\Api\Controllers\V1;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\ORM;
use Bitrix\Main\Application;
use \Custom\Core\Contract as ContractCore;
use \Custom\Core\FinanceList as FinanceList;

class Finance {
    function __construct()
    {

    }

    public function financeListSetFields()
    {
        $request = request()->get();

        $actId = null;
        $fields = null;

        if($request["id"])
            $actId = $request["id"];

        if($request["fields"])
            $fields = $request["fields"];

        if($actId && $fields)
        {
            FinanceList::updateFL($actId, $fields);
        }
        else
        {
            response()->json(
                [
                    'status' => 'error',
                    'result' => 'Не передан id или массив полей',
                ],400,[],['Content-Type' => 'application/json']
            );
        }
        response()->json(
            [
                'status' => 'success',
            ],200,[],['Content-Type' => 'application/json']
        );
    }

}