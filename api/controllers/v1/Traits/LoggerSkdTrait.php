<?php declare(strict_types=1);

namespace Local\Api\Controllers\V1\Traits;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\ORM;
use Bitrix\Main\ORM\Query\Result;
use Yandex\Metrika\Fields;

trait LoggerSkdTrait
{
    const tableFields = [
        "request" => [
            "by" => "UF_SEARCH_BY",
            "q" => "UF_SEARCH_Q",
            "code" => "UF_CODE",
            "allowed" => "UF_ALLOWED",
            "exit_mode" => "UF_EXIT_MODE",
        ],
        "ticket" => [
            "order_number" => "UF_ORDER_NUMBER",
            "name" => "UF_TICKET_NAME",
            "ticket_type" => "UF_TICKET_TYPE",
            "status" => "UF_TICKET_STATUS",
            "status_code" => "UF_TICKET_STATUS_CODE",
            "status_message" => "UF_TICKET_STATUS_MESSAGE",
            "place" => "UF_TICKET_PLACE",
            "row" => "UF_TICKET_ROW",
            "sector" => "UF_TICKET_SECTOR",
            "date" => "UF_TICKET_DATE",
        ],
        "event_info" => [
            "is_allow_exit" => "UF_EVENT_IS_ALLOW_EXIT",
            "is_confirmation_required" => "UF_EVENT_IS_CONFIRMATION_REQURED",
            "tickets" => [
                "sold_quantity" => "UF_EVENT_SOLD_QUANTITY",
                "validate_quantity" => "UF_EVENT_VALIDATE_QUANTITY",
            ]
        ],
    ];

    private function prepareResult($data, $fieldName): array
    {
        $result = [];

        foreach (self::tableFields[$fieldName] as $key => $value) {
            if ($data[$key] !== null) {
                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        $result[$v] = $data[$key][$k];
                    }
                } else {
                    $result[$value] = $data[$key];
                }
            }
        }

        return $result;
    }

    private function getMethod(): string
    {
        $arRequestUri = explode("/", $_SERVER["REQUEST_URI"]);
        $method = $arRequestUri[count($arRequestUri) - 1];
        $method = strtok($method, "?");

        return $method ?? "";
    }
    private function add2log($data): void
    {
        try {
            $addFields = [];

            $addFields = [
                "UF_API_METHOD" => $this->getMethod(),
                "UF_REQ_STATUS" => $data["status"],
                "UF_REQ_MESSAGE" => $data["message"],
                "UF_DATE_INSERT" => (new \Bitrix\Main\Type\DateTime()),
            ];

            if ($data["messageCode"] && is_object($data["messageCode"])) {
                $addFields["UF_REQ_MESSAGE_CODE"] = $data["messageCode"]->value;
            }

            if($this->skd)
            {
                $addFields["UF_CONTROLLER_ID"] = $this->skd['ID'];
                $addFields["UF_EVENT_ID"] = $this->skd['UF_EVENT_ID'];
            }

            $data["request"] = request()->get();

            if(!$data["ticket"] && $data["item"])
                $data["ticket"] = $data["item"];

            foreach (self::tableFields as $key => $value)
            {
                if($data[$key])
                {
                    $addFields = array_merge($addFields, $this->prepareResult($data[$key], $key));
                }
            }

            $this->write2table($addFields);
        }
        catch (\Exception $e) {
            $this->returnError($e->getMessage());
        }
    }

    private function write2table(array $data): void
    {
        try {
            $result = \Custom\Core\Skd\LoggerSkdTable::add($data);

            if (!$result->isSuccess()) {
                $this->returnError($result->getErrors());
            }
        }
        catch (\Exception $e) {
            $this->returnError($e->getMessage());
        }
    }

    private function returnError($message):void
    {
        response()->json(
            [
                'status'  => 'error',
                'message' => $message,
            ],
            200, [],['Content-Type' => 'application/json']
        );
    }
}