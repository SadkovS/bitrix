<?php
namespace Custom\Core;

require_once($_SERVER["DOCUMENT_ROOT"] . '/local/crest/crest.php');

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\Validator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use Custom\Core\ExportExcel;
use \Bitrix\Sale\Internals\OrderTable;
use \Custom\Core\Contract as ContractCore;
use \Custom\Core\Helper as Helper;

Loc::loadMessages(__FILE__);

class Act
{
    const HL_NAME = "Acts";
    const ACT_STATUS_XMLID = "UF_STATUS";

    const ACT_ENTITY_TIPE_ID = 1032;
    const DOC_ENTITY_TIPE_ID = 1036;

    const ACT_VERIF = ["Y" => 240, "N" => 241];

    const DOCUMENT_TYPE = ["upd" => 250, "act" => 242];
    public static function makeFile($fields = [])
    {
        $date = new \Bitrix\Main\Type\DateTime();
        $date = $date->format('d.m.Y');

        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/upload/tmp/'.$fields["NAME"]."_".$date.".xlsx"))
        {
            unlink($_SERVER['DOCUMENT_ROOT'] . '/upload/tmp/'.$fields["NAME"]."_".$date.".xlsx");
        }

        $obExport = new ExportExcel();
        $obExport->setFilePath($_SERVER['DOCUMENT_ROOT'] . '/upload/tmp');
        $obExport->setFileName($fields["NAME"]."_".$date.".xlsx");
        $xls = $obExport->createFile();
        $xls->getProperties()->setTitle("Тестовый файл");
        $xls->getProperties()->setCreated($date);
        $xls->setActiveSheetIndex(0);

        $sheet = $xls->getActiveSheet();
        $sheet->setTitle('Отчет');

        $sheet->setCellValue("B2", "вознаграждение");
        $sheet->setCellValue("C2", 'ООО "Фантам"');
        $sheet->getStyle("C3")->getFont()->setBold(true);
        $sheet->setCellValue("B3", $fields["PERCENT"]."%");

        $sheet->setCellValue("G2", "Организатор");

        if($fields["COMPANY_TYPE"] == "person")
            $sheet->setCellValue("H2", $fields["FIO"]);
        else
            $sheet->setCellValue("H2", $fields["FULL_NAME"]);

        $sheet->setCellValue("G3", "ID: ".$fields["ID"]);

        $sheet->setCellValue("D5", "Отчет об оказании услуг  №".date("my")."/{$fields["ID"]}");
        $sheet->getStyle("D5")->getFont()->setBold(true);
        $sheet->getStyle("D5")->getFont()->setSize(16);

        $sheet->setCellValue("E7", "за период");
        $sheet->getStyle("E7")->getFont()->setBold(true);
        $sheet->getStyle("D5")->getFont()->setSize(16);

        $sheet->setCellValue("G7", "с {$fields["PERIOD_FROM"]}  по  {$fields["PERIOD_TO"]}");
        $sheet->getStyle("D5")->getFont()->setSize(16);

        $sheet->setCellValue("B9", 'Общество с ограниченной ответственностью ООО "Фантам", в лице генерального директора Давыдова Эдварда Артуровича,  действующего на основании Устава,');

        if($fields["COMPANY_TYPE"] == "legal")
            $sheet->setCellValue("B10", 'в дальнейшем именуемого "Лицензиар" и '.$fields["FULL_NAME"].', в лице '.$fields["PERSON_NAME"].', действующего на основании Устава');

        if($fields["COMPANY_TYPE"] == "ip")
            $sheet->setCellValue("B10", 'в дальнейшем именуемого "Лицензиар" и '.$fields["FULL_NAME"].', в лице '.$fields["FIO"].', действующего на основании статуса, подтвержденного государственной регистрацией');

        if($fields["COMPANY_TYPE"] == "person")
            $sheet->setCellValue("B10", 'в дальнейшем именуемого "Лицензиар" и '.$fields["FIO"].', в лице '.$fields["FIO"].', действующего на основании Федерального закона № 422-ФЗ "О проведении эксперимента по установлению специального налогового режима "Налог на профессиональный доход"');

        $sheet->setCellValue("B11", ' именуемый в дальнейшем "Лицензиат", подписали настоящий отчет об оказании услуг, о нижеследующем:');

        $sheet->setCellValue("B13", "Остаток д/с   на начало отчетного периода");
        $sheet->getStyle("B13")->getFont()->setBold(true);
        $sheet->setCellValue("E13", number_format($fields["BALANCE_FIRST"],2,"."," "));
        $sheet->setCellValue("F13", "рублей");

        $sheet->setCellValue("B16", "Остаток д/с на конец отчетного периода");
        $sheet->getStyle("B16")->getFont()->setBold(true);
        $sheet->setCellValue("E16", number_format($fields["BALANCE_LAST"],2,"."," "));
        $sheet->setCellValue("F16", "рублей");

        $sheet->setCellValue("B19", "Сумма ЭБ, приобретенных ФЛ (руб)");
        $sheet->getStyle("B19")->getFont()->setBold(true);
        $sheet->getStyle("B19")->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension("B")->setWidth(25);
        $sheet->setCellValue("B20", number_format($fields["SUM"],2,"."," "));

        $sheet->setCellValue("C19", "Сумма ЭБ приобретенных ЮЛ (руб)");
        $sheet->getStyle("C19")->getFont()->setBold(true);
        $sheet->getStyle("C19")->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension("C")->setWidth(25);
        $sheet->setCellValue("C20", 0);

        $sheet->setCellValue("D19", "Общая сумма реализованных ЭБ (руб)");
        $sheet->getStyle("D19")->getFont()->setBold(true);
        $sheet->getStyle("D19")->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension("D")->setWidth(25);
        $sheet->setCellValue("D20", number_format($fields["SUM"],2,"."," "));

        $sheet->setCellValue("E19", "Общая сумма вознаграждения Лицензиара за ЭБ (руб)");
        $sheet->getStyle("E19")->getFont()->setBold(true);
        $sheet->getStyle("E19")->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension("E")->setWidth(25);
        $sheet->setCellValue("E20",  number_format(($fields["SUM"]/100)*$fields["PERCENT"],2,"."," "));

        $sheet->setCellValue("F19", "Сумма ДС возвращенных Покупателю (руб)");
        $sheet->getStyle("F19")->getFont()->setBold(true);
        $sheet->getStyle("F19")->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension("F")->setWidth(25);
        $sheet->setCellValue("F20", number_format($fields["REFUND"],2,"."," "));

        $sheet->setCellValue("G19", "Сумма ДС  комиссия за возвраты ЭБ (руб)");
        $sheet->getStyle("G19")->getFont()->setBold(true);
        $sheet->getStyle("G19")->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension("G")->setWidth(25);
        $sheet->setCellValue("G20", 0);

        $sheet->setCellValue("H19", "ДС удержанные Лицензиаром для взаимозачета (руб)");
        $sheet->getStyle("H19")->getFont()->setBold(true);
        $sheet->getStyle("H19")->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension("H")->setWidth(25);
        $sheet->setCellValue("H20", 0);

        $sheet->setCellValue("I19", "Сумма ДС перечисленных Лицензиаром на счет Лицензиата на основании Требования (руб)");
        $sheet->getStyle("I19")->getFont()->setBold(true);
        $sheet->getStyle("I19")->getAlignment()->setWrapText(true);
        $sheet->getColumnDimension("I")->setWidth(25);
        $sheet->setCellValue("I20",  number_format($fields["FUNDS_WITHDRAW"],2,"."," "));

        $sheet->getStyle("B19:I21")->applyFromArray($obExport->getBorderStyle());
        $sheet->getStyle("B19:I21")->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B19:I21")->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        $sheet->setCellValue("B23", "1.Лицензиар выполнил все обязательства в полном объеме и в установленный срок.");
        $sheet->setCellValue("B24", "2. Лицензиат подтверждает, что обязательства выполненные за вышеуказанный период Лицензиаром приняты в полном объеме.");
        $sheet->setCellValue("B25", "3. Стороны не имеют претензий и  возражений.");

        $sheet->setCellValue("C28", "Лицензиар");
        $sheet->setCellValue("C29", 'ООО "Фантам"');
        $sheet->setCellValue("C30", "ИНН 9715428584");
        $sheet->setCellValue("C31", "121357, г. Москва, ул. Верейская, д. 29, стр. 33, помещение 1н/2");
        $sheet->setCellValue("C32", "________________");
        $sheet->setCellValue("D32", "(Давыдов Э.А.)");

        $sheet->setCellValue("G28", "Лицензиат");

        if($fields["COMPANY_TYPE"] == "person")
            $sheet->setCellValue("G29", $fields["FIO"]);
        else
            $sheet->setCellValue("G29", $fields["FULL_NAME"]);

        $sheet->setCellValue("G30", "ИНН ".$fields["INN"]);
        $sheet->setCellValue("G31", $fields["ADDRESS"]);
        $sheet->setCellValue("G32", "________________");

        if($fields["COMPANY_TYPE"] == "legal")
            $sheet->setCellValue("H32", "(".$fields["PERSON_NAME_SHORT"].")");
        else
            $sheet->setCellValue("H32", "(".$fields["FIO_SHORT"].")");

        $obExport->saveFile($xls);

        return $obExport->getFilePath()."/".$obExport->getFileName();
    }

    public static function checkActFields($fields)
    {
        $numericFields = [
            "SUM",
            "REFUND",
            "FUNDS_WITHDRAW",
            "PERCENT",
            "BALANCE_FIRST",
            "BALANCE_LAST",
        ];

        foreach ($fields as $key => $val)
        {
            if(in_array($key, $numericFields))
            {
                if(!is_numeric($val))
                {
                    $fields[$key] = 0;
                }
            }
        }

        return $fields;
    }

    public static function make($orgId, $actId = null, $date = null)
    {
        /*if(!$date)
        {
            $date =  date('d.m.Y');
        }*/

        $dateFrom = date('d.m.Y 00:00:00', strtotime("first day of previous month"));
        $dateTo = date('d.m.Y 23:59:59', strtotime("last day of previous month"));

        $orderSumm = self::getOrdersSum($orgId, $dateFrom, $dateTo);

        if($orderSumm && $orderSumm["FULL"])
        {
            $orgEntity = new ORM\Query\Query('\Custom\Core\Users\CompaniesTable');
            $query       = $orgEntity
                ->setSelect([
                    "ID",
                    "UF_COOPERATION_PERCENT",
                    "UF_NAME",
                    "UF_FULL_NAME",
                    "UF_FIO",
                    "UF_XML_ID",
                    "UF_SIGNATORE_FIO",
                    "UF_REGISTRATION_ADDRESS",
                    "UF_INN",
                    "COMPANY_TYPE" => "TYPE.XML_ID"
                ])
                ->setFilter(['ID' => $orgId])
                ->registerRuntimeField(
                    'TYPE',
                    array(
                        'data_type' => '\Custom\Core\FieldEnumTable',
                        'reference' => array('=this.UF_TYPE' => 'ref.ID'),
                        'join_type' => 'LEFT'
                    )
                )
                ->exec();
            if($objOrg = $query->fetch()) {
                $balanceHistoryFirst = \Custom\Core\Users\BalanceHistoryTable::getList([
                    "filter" => [
                        'UF_COMPANY_ID' => $orgId,
                        '<=UF_DATE' => new \Bitrix\Main\Type\DateTime($dateFrom),
                    ],
                    "select" => [
                        "UF_BALANCE",
                    ],
                    "order" => [
                        "UF_DATE" => "DESC",
                    ],
                    'limit' => 1,
                ])->fetchAll();

                $balanceHistoryLast = \Custom\Core\Users\BalanceHistoryTable::getList([
                    "filter" => [
                        'UF_COMPANY_ID' => $orgId,
                        '>=UF_DATE' => new \Bitrix\Main\Type\DateTime($dateFrom),
                        '<=UF_DATE' => new \Bitrix\Main\Type\DateTime($dateTo),
                    ],
                    "select" => [
                        "UF_BALANCE",
                    ],
                    "order" => [
                        "UF_DATE" => "DESC",
                    ],
                    'limit' => 1,
                ])->fetchAll();

                if($balanceHistoryFirst) {
                    $balanceFirst = $balanceHistoryFirst[0]["UF_BALANCE"];
                }
                else
                {
                    $balanceFirst = 0;
                }

                if($balanceHistoryLast) {
                    $balanceLast = $balanceHistoryLast[0]["UF_BALANCE"];
                }
                else
                {
                    $balanceLast = 0;
                }

                $fundsWithdraw = self::getFundsWithdrawn($orgId, $dateFrom, $dateTo);

                $actFields = [
                    "ID" => $objOrg["UF_XML_ID"],
                    "NAME" => $objOrg["UF_NAME"],
                    "FULL_NAME" => $objOrg["UF_FULL_NAME"],
                    "COMPANY_TYPE" => $objOrg["COMPANY_TYPE"],
                    "FIO" => $objOrg["UF_FIO"],
                    "FIO_SHORT" => ($objOrg["UF_FIO"])?Helper::getShortFio($objOrg["UF_FIO"]):"",
                    "INN" => $objOrg["UF_INN"],
                    "ADDRESS" => $objOrg["UF_REGISTRATION_ADDRESS"],
                    "PERSON_NAME" => $objOrg["UF_SIGNATORE_FIO"],
                    "PERSON_NAME_SHORT" => ($objOrg["UF_SIGNATORE_FIO"])?Helper::getShortFio($objOrg["UF_SIGNATORE_FIO"]):"",
                    "SUM" => $orderSumm["FULL"],
                    "REFUND" => $orderSumm["REFUND"],
                    "FUNDS_WITHDRAW" => $fundsWithdraw,
                    "PERCENT" => $objOrg["UF_COOPERATION_PERCENT"],
                    "BALANCE_FIRST" => $balanceFirst,
                    "BALANCE_LAST" => $balanceLast,
                    "PERIOD_FROM" => (new \Bitrix\Main\Type\DateTime($dateFrom))->format("d.m.Y"),
                    "PERIOD_TO" => (new \Bitrix\Main\Type\DateTime($dateTo))->format("d.m.Y"),
                ];

                $actFields = self::checkActFields($actFields);

                $link = self::makeFile($actFields);

                if($actId)
                {
                    \Custom\Core\Users\ActsTable::update($actId, ["UF_LINK" => $link]);
                }

                return $link;

            }
            else
            {
                throw new \Exception('Организация не найдена');
            }
        }
        else
        {
            \Custom\Core\Users\ActsTable::delete($actId);
        }
    }

    public static function getFundsWithdrawn($orgId, $dateFrom, $dateTo)
    {
        if(!\Bitrix\Main\Loader::includeModule("sale"))
            return false;

        $queryActs = new ORM\Query\Query('\Custom\Core\Users\FinanceListTable');

        $resActsOb   = $queryActs
            ->setOrder([
                "ID" => "DESC"
            ])
            ->setFilter([
                'UF_COMPANY_ID' => $orgId,
                'STATUS.XML_ID' => ["success"],
                [
                    'LOGIC' => 'AND',
                    '>=UF_DATE_SUCCESS' => new \Bitrix\Main\Type\DateTime($dateFrom),
                    '<=UF_DATE_SUCCESS' => new \Bitrix\Main\Type\DateTime($dateTo),
                ]
            ])
            ->setSelect([
                'UF_SUMM',
            ])
            ->registerRuntimeField(
                'STATUS',
                array(
                    'data_type' => '\Custom\Core\FieldEnumTable',
                    'reference' => array('=this.UF_STATUS' => 'ref.ID'),
                    'join_type' => 'LEFT'
                )
            )
            ->exec();

        $sum = 0;

        while($result = $resActsOb->fetch())
        {
            $sum += $result["UF_SUMM"];
        }

        return $sum;
    }

    public static function getOrdersFullSum($orgId, $dateFrom, $dateTo)
    {
        $filter = [
            'PROPS.CODE' => "ORGANIZER_ID",
            'PROPS.VALUE' => $orgId,
            'PAYED' => "Y",
            '!CANCELED' => "Y",
            '!STATUS_ID' => "CD",
            [
                'LOGIC' => 'AND',
                '>=DATE_PAYED' => $dateFrom,
                '<=DATE_PAYED' => $dateTo,
            ]
        ];

        $ordersEntity = new \Bitrix\Main\ORM\Query\Query('\Bitrix\Sale\Internals\OrderTable');
        $query       = $ordersEntity
            ->setSelect([
                "ORDER_PRICE",
            ])
            ->setFilter($filter)
            ->registerRuntimeField(
                "PROPS",
                [
                    'data_type' => 'Bitrix\Sale\Internals\OrderPropsValueTable',
                    'reference' => array('=this.ID' => 'ref.ORDER_ID'),
                    'join_type' => 'INNER'
                ]
            )
            ->registerRuntimeField(
                "BASKET_REFS",
                [
                    'data_type' => 'Bitrix\Sale\Internals\BasketTable',
                    'reference' => array('=this.ID' => 'ref.ORDER_ID'),
                    'join_type' => 'INNER'
                ]
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ExpressionField(
                    'ORDER_PRICE', 'SUM(%s)', ['BASKET_REFS.PRICE']
                )
            )
            ->setDistinct()
            ->exec();

        if($result = $query->fetch())
        {
            return $result["ORDER_PRICE"];
        }

        return 0;
    }

    public static function getOrdersRefundSum($orgId, $dateFrom, $dateTo)
    {
        $filter = [
            'UF_COMPANY_ID' => $orgId,
            'REF_STATUS.XML_ID' => ["refunded"],
            [
                'LOGIC' => 'AND',
                '>=UF_DATE_TIME_REFUND' => $dateFrom,
                '<=UF_DATE_TIME_REFUND' => $dateTo,
            ]
        ];

        $refundEntity = new \Bitrix\Main\ORM\Query\Query('\Custom\Core\Tickets\TicketRefundRequestsTable');
        $query       = $refundEntity
            ->setSelect([
                "REFUNDED_PRICE",
            ])
            ->setFilter($filter)
            ->registerRuntimeField(
                "REF_STATUS",
                [
                    'data_type' => '\Custom\Core\FieldEnumTable',
                    'reference' => array('=this.UF_REVIEW_STATUS' => 'ref.ID'),
                    'join_type' => 'LEFT'
                ]
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ExpressionField(
                    'REFUNDED_PRICE', 'SUM(%s)', ['UF_ACTUAL_REFUND_SUM']
                )
            )
            ->setDistinct()
            ->exec();

        if($result = $query->fetch())
        {
            return $result["REFUNDED_PRICE"];
        }

        return 0;
    }

    public static function getOrdersSum($orgId, $dateFrom, $dateTo, $excludeRefund = false)
    {
        if(!\Bitrix\Main\Loader::includeModule("sale"))
            return false;

        $datefromBX = new \Bitrix\Main\Type\DateTime($dateFrom);
        $datetoBX = new \Bitrix\Main\Type\DateTime($dateTo);

        $sum = [];

        $sum["FULL"] = self::getOrdersFullSum($orgId, $datefromBX, $datetoBX);
        $sum["REFUND"] = self::getOrdersRefundSum($orgId, $datefromBX, $datetoBX);

        if($excludeRefund)
            return $sum["FULL"] - $sum["REFUND"];
        else
            return $sum;
    }

    public static function send($actId)
    {
        $actEntity = new ORM\Query\Query('\Custom\Core\Users\ActsTable');

        $actQuery = $actEntity
            ->setSelect([
                "UF_LINK",
                "UF_COMPANY",
                "COMPANY_NAME" => "COMPANY.UF_NAME",
                "COMPANY_XML_ID" => "COMPANY.UF_XML_ID",
                "COMPANY_B24_DEAL_ID" => "COMPANY.UF_B24_DEAL_ID",
            ])
            ->setFilter([
                'ID' => $actId,
                '!UF_LINK' => false,
                'UF_XML_ID' => false,
            ])
            ->registerRuntimeField(
                "COMPANY",
                [
                    'data_type' => '\Custom\Core\Users\CompaniesTable',
                    'reference' => array('=this.UF_COMPANY' => 'ref.ID'),
                    'join_type' => 'INNER'
                ]
            )
            ->exec();

        if($act = $actQuery->fetch())
        {
            $path = $act["UF_LINK"];
            $base64File = base64_encode(file_get_contents($path));

            $fileName = explode("/", $path);
            $fileName = $fileName[count($fileName)-1];


            $resultAct = \CRest::call(
                'crm.item.add',
                [
                    'entityTypeId' => self::ACT_ENTITY_TIPE_ID,
                    'fields' => [
                        "title" => 'Согласование Отчета об оказании услуг <' . $act['COMPANY_NAME'] . '>',
                        'companyId' => $act['COMPANY_XML_ID'],
                        'parentId2' => $act['COMPANY_B24_DEAL_ID'],
                        'ufCrm6_1732689927334' => [
                            '0' => [$fileName, $base64File]
                        ]
                    ]
                ]
            );

            if($resultAct["result"] && $resultAct["result"]["item"]["id"])
            {
                $enumId = ContractCore::getHLfileldEnumId(
                    self::HL_NAME,
                    self::ACT_STATUS_XMLID,
                    "created"
                );

                \Custom\Core\Users\ActsTable::update(
                    $actId,
                    [
                        "UF_XML_ID" => $resultAct["result"]["item"]["id"],
                        "UF_STATUS" => $enumId
                    ]
                );
            }
        }
    }

    public static function updateAct($actId, $fields)
    {
        $actEntity = new ORM\Query\Query('\Custom\Core\Users\ActsTable');

        $actQuery = $actEntity
            ->setSelect([
                "ID",
                "UF_COMPANY",
            ])
            ->setFilter([
                'UF_XML_ID' => $actId,
            ])
            ->exec();

        if($act = $actQuery->fetch())
        {
            $statusXmlId = false;
            if($fields["UF_STATUS"])
            {
                $statusXmlId = $fields["UF_STATUS"];

                if($statusXmlId == "cancel")
                {
                    self::makeFromB24($act["UF_COMPANY"]);
                }

                $fields["UF_STATUS"] = ContractCore::getHLfileldEnumId(
                    self::HL_NAME,
                    self::ACT_STATUS_XMLID,
                    $fields["UF_STATUS"]
                );
            }

            if($fields["UF_FILE_UPD"])
            {
                $fields["UF_FILE_UPD"] = Helper::saveFileFromBase64(
                    $fields["UF_FILE_UPD"]["1"],
                    $fields["UF_FILE_UPD"]["0"],
                    $act["ID"]
                );
            }

            $resUpdate = \Custom\Core\Users\ActsTable::update(
                $act["ID"],
                $fields
            );
        }
        else
        {
            throw new \Exception('Акт не найден');
        }
    }

    public static function sendActMessage($data)
    {
        $resultAct = \CRest::call(
            'crm.item.update',
            [
                'id' => $data["id"],
                'entityTypeId' => self::ACT_ENTITY_TIPE_ID,
                'fields' => $data["fields"]
            ]
        );
    }

    public static function sendDocs($data)
    {
        $resultAct = \CRest::call(
            'crm.item.add',
            [
                'entityTypeId' => self::DOC_ENTITY_TIPE_ID,
                'fields' => $data["fields"]
            ]
        );
    }

    public static function makeFromB24($companyId)
    {
        $result = \Custom\Core\Users\ActsTable::add([
            "UF_COMPANY" => $companyId,
            "UF_DATE" => new \Bitrix\Main\Type\DateTime()
        ]);
        $id = $result->getId();

        self::make($companyId, $id);
        self::send($id);
    }
}
