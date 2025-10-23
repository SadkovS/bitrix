<?php

namespace Local\Api\Controllers\V1;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\ORM;
use Bitrix\Main\Application;
use \Custom\Core\Contract as ContractCore;
use \Custom\Core\Act as Act;

class Contract {
    function __construct()
    {

    }

    public function getXmlidById()
    {

    }

    public function setContractFinishStatus()
    {
        $request = request()->get();

        if($request["id"])
            $orgId = $request["id"];

        $enumId = ContractCore::getHLfileldEnumId(
            ContractCore::HLNAME_ORGANIZATION,
            ContractCore::CONTRACT_STATUS_XMLID,
            ContractCore::CONTRACT_FINISH_STATUS_ENUM
        );

        if($orgId && $enumId)
        {
            ContractCore::updateOrganization($orgId, [ContractCore::CONTRACT_STATUS_XMLID => $enumId]);
        }
        else
        {
            response()->json(
                [
                    'status' => 'error',
                    'result' => 'Не передан id организации или массив полей',
                ],400,[],['Content-Type' => 'application/json']
            );
        }
        response()->json(
            [
                'status' => 'success',
            ],200,[],['Content-Type' => 'application/json']
        );
    }

    public function organizationSetFields()
    {
        $request = request()->get();

        $orgId = null;
        $fields = null;

        if($request["id"])
            $orgId = $request["id"];

        if($request["fields"])
            $fields = $request["fields"];


        if($orgId && $fields)
        {
            ContractCore::updateOrganization($orgId, $fields);
        }
        else
        {
            response()->json(
                [
                    'status' => 'error',
                    'result' => 'Не передан id организации или массив полей',
                ],400,[],['Content-Type' => 'application/json']
            );
        }
        response()->json(
            [
                'status' => 'success',
            ],200,[],['Content-Type' => 'application/json']
        );
    }

    public function makeAct()
    {
        $request = request()->get();

        $companyId = null;

        if($request["id"])
            $companyId = $request["id"];

        if($companyId)
        {
            Act::makeFromB24($companyId);
        }
        else
        {
            response()->json(
                [
                    'status' => 'error',
                    'result' => 'Не передан id акта или массив полей',
                ],400,[],['Content-Type' => 'application/json']
            );
        }
        response()->json(
            [
                'status' => 'success',
            ],200,[],['Content-Type' => 'application/json']
        );
    }

    public function actSetFields()
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
            Act::updateAct($actId, $fields);
        }
        else
        {
            response()->json(
                [
                    'status' => 'error',
                    'result' => 'Не передан id акта или массив полей',
                ],400,[],['Content-Type' => 'application/json']
            );
        }
        response()->json(
            [
                'status' => 'success',
            ],200,[],['Content-Type' => 'application/json']
        );
    }
    public function addAdditionalAgreements()
    {
        $request = request()->get();

        $orgId = null;
        $fields = null;

        if($request["id"])
            $orgId = $request["id"];

        if($request["fields"])
            $fields = $request["fields"];


        if($orgId && $fields)
        {
            ContractCore::addAdditionalAgreements($orgId, $fields);
        }
        else
        {
            response()->json(
                [
                    'status' => 'error',
                    'result' => 'Не передан id организации или массив полей',
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