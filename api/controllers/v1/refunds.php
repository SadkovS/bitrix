<?php

namespace Local\Api\Controllers\V1;
use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\ORM;

Loader::includeModule('highloadblock');
Loader::includeModule('custom.core');
Loader::includeModule('sale');


class Refunds {

    private int $userId;
    private array $groups;
    public function __construct()
    {
        $request = request()->get();
        $this->userId = (int)$request['_user']['ID'];
        $this->groups = \CUser::GetUserGroup($request['_user']['ID']) ?? [];
    }

    public function setApprove(): void
    {
        try{

            if(!in_array(1, $this->groups) && !in_array(8, $this->groups))
                throw new \Exception('Access denied');
            $request = request()->get();
            $statuses = $this->getPropertiesEnum('TicketRefundRequests', 'UF_REVIEW_STATUS','XML_ID');
            $status = $request['approved'] ? $statuses['refunded']['ENUM_ID'] : $statuses['reject']['ENUM_ID'];

            $requestEntity   = HL\HighloadBlockTable::compileEntity('TicketRefundRequests');
            $hlbClassRequest = $requestEntity->getDataClass();

            $requestID = (int)$hlbClassRequest::getList(
                ['select'=>['ID'],'filter' => ['UF_XML_ID' => $request['id']],'limit' => 1,]
            )->fetch()['ID'];

            if($requestID < 1) throw new \Exception('Заявка не найдена');

            $resUpdate = $hlbClassRequest::update($requestID, ['UF_REVIEW_STATUS' => $status]);
            if (!$resUpdate->isSuccess()) throw new \Exception($resUpdate->getErrorMessages());

            if($request['approved']) {

                $refundRequest = $hlbClassRequest::getById($requestID)->fetch();

                if($refundRequest['ID'] < 1) throw new \Exception('Заявка не найдена');
            }

            response()->json(
                [
                    'status' => 'success',
                    'result' => true,
                ],200,[],['Content-Type' => 'application/json']);



        }catch(\Exception $e){
            response()->json(
                [
                    'status'  => 'error',
                    'message' => $e->getMessage(),
                    'group'   => $refundRequest,
                ],400,[],['Content-Type' => 'application/json']
            );
        }
    }

    private function getPropertiesEnum(string $hlName, string $fieldName, string $key = '')
    {
        $filter = [
            "HL.NAME"    => $hlName,
            "FIELD_NAME" => $fieldName,
        ];

        $query = \Bitrix\Main\UserFieldTable::getList(
            [
                "filter"  => $filter,
                "select"  => [
                    "ENUM_ID"     => "ENUM.ID",
                    "ENUM_XML_ID" => "ENUM.XML_ID",
                    "ENUM_NAME"   => "ENUM.VALUE",
                ],
                "runtime" => [
                    new \Bitrix\Main\Entity\ExpressionField(
                        'HL_ID',
                        'REPLACE(%s, "HLBLOCK_", "")',
                        ['ENTITY_ID']
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'HL',
                        '\Bitrix\Highloadblock\HighloadBlockTable',
                        ['this.HL_ID' => 'ref.ID'],
                        ['join_type' => 'LEFT'],
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'ENUM',
                        '\Custom\Core\FieldEnumTable',
                        ['this.ID' => 'ref.USER_FIELD_ID'],
                        ['join_type' => 'LEFT'],
                    ),
                ],
                'order'   => ['ENUM_ID' => 'ASC'],
                'cache'   => ['ttl' => 3600],
            ]
        );
        $res   = [];
        while ($item = $query->fetch()) {
            if (!empty($xmlID)) $res[$item['ENUM_ID']] = $item;
            else $res[$item['ENUM_'.$key]] = $item;
        }
        return $res;
    }

    private function changeBalance(){
        //BalanceHistory
    }
}