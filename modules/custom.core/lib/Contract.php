<?php
namespace Custom\Core;

require_once($_SERVER["DOCUMENT_ROOT"] . '/local/crest/crest.php');

use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\Validator;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM;
use \Custom\Core\Helper as Helper;

Loc::loadMessages(__FILE__);

class Contract
{
    const HLNAME_ORGANIZATION = "Company";
    const HLNAME_ACTS = "Acts";
    const CONTRACT_STATUS_XMLID = "UF_CONTRACT";
    const CONTRACT_FINISH_STATUS_ENUM = "signed";

    const CONTRACT_TYPE_CONFIRM_XML_ID = [
        "scet" => 160,
        "docs" => 161,
    ];

    const COMPANY_TYPE_XML_ID = [
        "ip" => 473,
        "legal" => 158,
        "person" => 263,
    ];

    const RQ_IDENT_DOC = "Паспорт";

    const DOCS_TMP_PATH = '/upload/tmp/';

    public static function checkFileSize($file = null)
    {
        $Questionnaires = new \Custom\Core\Questionnaires();
        $maxSize = $Questionnaires->fileSize;
        $maxSizeMB = $maxSize/1024/1024;

        if($file['size'] > $maxSize)
        {
            throw new Exception('Размер файла не должен быть больше '.$maxSizeMB.' МБ');
        }
    }

    public static function addAdditionalAgreements($orgId, $fields)
    {
        $orgEntity = new ORM\Query\Query('\Custom\Core\Users\CompaniesTable');
        $query       = $orgEntity
            ->setSelect(["ID"])
            ->setFilter(['UF_XML_ID' => $orgId])
            ->exec();
        if($objOrg  = $query->fetchObject())
        {
            $fields["UF_COMPANY_ID"] = $objOrg["ID"];

            if($fields["UF_DATE_SIGNED"])
                $fields["UF_DATE_SIGNED"] = new \Bitrix\Main\Type\DateTime($fields["UF_DATE_SIGNED"]);

            if($fields["UF_DATE_LOAD"])
                $fields["UF_DATE_LOAD"] = new \Bitrix\Main\Type\DateTime($fields["UF_DATE_LOAD"]);

            $fields["UF_LINK"] = Helper::saveFileFromBase64(
                $fields["UF_LINK"]["1"],
                $fields["UF_LINK"]["0"],
                $objOrg["ID"]
            );

            $result = \Custom\Core\Users\AdditionalAgreementsTable::add($fields);

            if(!$result->isSuccess())
            {
                throw new \Exception(implode(', ', $result->getErrors()));
            }
        }
        else
        {
            throw new \Exception('Организация не найдена');
        }
    }
    public static function onUpdate(\Bitrix\Main\Event $e)
    {
        $id       = $e->getParameter('id')["ID"];
        $fields = $e->getParameter('fields');

        $enumVerifId =  self::getHLfileldEnumId(
            self::HLNAME_ORGANIZATION,
            "UF_CONTRACT",
            "verif"
        );

        $enumSuccessId =  self::getHLfileldEnumId(
            self::HLNAME_ORGANIZATION,
            "UF_CONTRACT",
            "signed"
        );

        $query = new ORM\Query\Query('\Custom\Core\Users\CompaniesTable');
        $resCompany   = $query
            ->setFilter([
                'ID' => $id
            ])
            ->setSelect([
                '*',
                'CONTRACT_STATUS' => 'CONTRACT.XML_ID',
                'CONTRACT_TYPE_CONFIRM_XML_ID' => 'CONTRACT_TYPE_CONFIRM.XML_ID',
                'OWNER_USER_ID' => 'PROFILE.UF_USER_ID',
                'FULL_NAME'
            ])
            ->setLimit(1)
            ->registerRuntimeField(
                'CONTRACT',
                array(
                    'data_type' => '\Custom\Core\FieldEnumTable',
                    'reference' => array('=this.UF_CONTRACT' => 'ref.ID'),
                    'join_type' => 'LEFT'
                )
            )
            ->registerRuntimeField(
                'CONTRACT_TYPE_CONFIRM',
                array(
                    'data_type' => '\Custom\Core\FieldEnumTable',
                    'reference' => array('=this.UF_CONTRACT_TYPE_CONFIRM' => 'ref.ID'),
                    'join_type' => 'LEFT'
                )
            )
            ->registerRuntimeField(
                '',
                new \Bitrix\Main\Entity\ReferenceField(
                    'PROFILE',
                    '\Custom\Core\Users\UserProfilesTable',
                    \Bitrix\Main\ORM\Query\Join::on('ref.UF_COMPANY_ID', 'this.ID')
                        ->where(
                            'ref.UF_IS_OWNER',
                            '=',
                            1
                        ),
                )
            )
            ->registerRuntimeField(
                'USER',
                array(
                    'data_type' => '\Bitrix\Main\UserTable',
                    'reference' => array('=this.OWNER_USER_ID' => 'ref.ID'),
                    'join_type' => 'LEFT'
                )
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ExpressionField(
                    'FULL_NAME', 'CONCAT(%s," ",%s)', ['USER.LAST_NAME', 'USER.NAME']
                ),
            )
            ->exec();
        if($company = $resCompany->fetch())
        {
            if(
                $company["CONTRACT_TYPE_CONFIRM_XML_ID"] == "scet"
                && $fields["UF_CONTRACT_PAY_FILE"]
                && !$company["UF_CONTRACT_PAY_FILE"]
                && $company["UF_CONTRACT"] != $enumSuccessId
            )
            {
                \CEvent::Send('CONTRACT_SCET', "s1", [
                    'EMAIL' => $company["UF_EMAIL"],
                    'FULL_NAME' => $company["FULL_NAME"],
                    'CONCTARC_LINK' => Helper::getSiteUrl()."/documents/org_license_agreement.pdf",
                    'PAY_LINK' => Helper::getSiteUrl().\CFile::GetPath($fields["UF_CONTRACT_PAY_FILE"]),
                    'SERVER_NAME' => Helper::getSiteUrl(),
                ]);
            }
            if($company["CONTRACT_TYPE_CONFIRM_XML_ID"] == "docs" && $fields["UF_CONTRACT"] == $enumVerifId && $company["UF_CONTRACT"] != $enumVerifId)
            {
                \CEvent::Send(
                    'CONTRACT_DOCS',
                    "s1",
                    [
                        'EMAIL' => $company["UF_EMAIL"],
                        'FULL_NAME' => $company["FULL_NAME"],
                        'CONCTARC_LINK' => Helper::getSiteUrl()."/documents/org_license_agreement.pdf",
                        'SERVER_NAME' => Helper::getSiteUrl(),
                    ],
                );
            }
            if($fields["UF_CONTRACT"] == $enumSuccessId && $company["UF_CONTRACT"] != $enumSuccessId)
            {
                \CEvent::Send('CONTRACT_SUCCESS', "s1", [
                    'EMAIL' => $company["UF_EMAIL"],
                    'FULL_NAME' => $company["FULL_NAME"],
                    'CONCTARC_LINK' => Helper::getSiteUrl()."/documents/org_license_agreement.pdf",
                    'LK_LINK' => Helper::getSiteUrl()."/admin_panel/organization/contract/",
                    'SERVER_NAME' => Helper::getSiteUrl(),
                ]);
            }
        }
        else
        {
            throw new \Exception('Организация не найдена');
        }
    }

    public static function updateOrganization($orgId, $fields)
    {
        $query = new ORM\Query\Query('\Custom\Core\Users\CompaniesTable');
        $resCompany   = $query
            ->setFilter([
                'UF_XML_ID' => $orgId
            ])
            ->setSelect([
                'ID',
            ])
            ->setLimit(1)
            ->exec();
        if($company = $resCompany->fetch())
        {
            if($fields["UF_CONTRACT_DATE"])
                $fields["UF_CONTRACT_DATE"] = new \Bitrix\Main\Type\DateTime($fields["UF_CONTRACT_DATE"]);

            if($fields["UF_CONTRACT_PAY_FILE"])
            {
                $fields["UF_CONTRACT_PAY_FILE"] = Helper::saveFileFromBase64(
                    $fields["UF_CONTRACT_PAY_FILE"]["1"],
                    $fields["UF_CONTRACT_PAY_FILE"]["0"],
                    $company["ID"]
                );
            }

            \Custom\Core\Users\CompaniesTable::update($company["ID"], $fields);

        }
        else
        {
            throw new \Exception('Организация не найдена');
        }
    }

    public static function getHLfileldEnumId($hlName, $fieldName, $enum)
    {
        $userFieldEnum = \Bitrix\Main\UserFieldTable::getList(
            [
                "filter" => [
                    "HL.NAME" => $hlName,
                    "ENUM_XML_ID" => $enum,
                    "FIELD_NAME" => $fieldName,
                ],
                "select" => [
                    "FIELD_NAME",
                    "ENUM_" => "ENUM",
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
            ]
        )->fetch();
        if($userFieldEnum)
        {
            return $userFieldEnum["ENUM_ID"];
        }

        return false;
    }

	public static function getHLfilelds($hlName)
    {
        $userFieldEnum = \Bitrix\Main\UserFieldTable::getList(
            [
                "filter" => [
                    "HL.NAME" => $hlName,
                    "!ENUM_VALUE" => false
                ],
                "select" => [
                    "FIELD_NAME",
                    "ENUM_" => "ENUM",
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
            ]
        )->fetchAll();

        $arFields = [];
        foreach ($userFieldEnum as $item)
        {
            $arFields[$item["FIELD_NAME"]][$item["ENUM_ID"]] = $item;

        }

        return $arFields;
	}

    public static function getB24UserField(string $fieldName)
    {
        $result = [];

        $resUserField = \CRest::call(
            'crm.deal.userfield.list',
            ['FILTER' => [
                'FIELD_NAME' => $fieldName,
            ]]
        );

        if($resUserField["result"] && $resUserField["result"][0]["LIST"])
        {
            foreach ($resUserField["result"][0]["LIST"] as $item)
            {
                $result[$resUserField["result"][0]["FIELD_NAME"]][$item["XML_ID"]] = $item["ID"];
            }
        }

        return $result;
    }

    public static function getB24UserFieldValueID($fieldName, $xml_id)
    {
        $result = self::getB24UserField($fieldName);

        if($result && $result[$fieldName])
            return $result[$fieldName][$xml_id];

        return false;
    }

    public static function sendContractB24($id)
    {
        $query = new ORM\Query\Query('\Custom\Core\Users\CompaniesTable');
        $resCompany   = $query
            ->setFilter([
                'ID' => $id,
                [
                    "LOGIC" => "OR",
                    '=CONTRACT_STATUS' => "no",
                    '=UF_CONTRACT' => false,
                ]
            ])
            ->setSelect([
                '*',
                'CONTRACT_STATUS' => 'CONTRACT.XML_ID',
                'COMPANY_TYPE' => 'COMPANY.XML_ID',
                'CONTRACT_TYPE_CONFIRM_XML_ID' => 'CONTRACT_TYPE_CONFIRM.XML_ID',
                'TAX_SYSTEM_MANE' => 'TAX_SYSTEM.VALUE',
            ])
            ->setLimit(1)
            ->registerRuntimeField(
                'CONTRACT',
                array(
                    'data_type' => '\Custom\Core\FieldEnumTable',
                    'reference' => array('=this.UF_CONTRACT' => 'ref.ID'),
                    'join_type' => 'LEFT'
                )
            )
            ->registerRuntimeField(
                'CONTRACT_TYPE_CONFIRM',
                array(
                    'data_type' => '\Custom\Core\FieldEnumTable',
                    'reference' => array('=this.UF_CONTRACT_TYPE_CONFIRM' => 'ref.ID'),
                    'join_type' => 'LEFT'
                )
            )
            ->registerRuntimeField(
                'COMPANY',
                array(
                    'data_type' => '\Custom\Core\FieldEnumTable',
                    'reference' => array('=this.UF_TYPE' => 'ref.ID'),
                    'join_type' => 'LEFT'
                )
            )
            ->registerRuntimeField(
                'TAX_SYSTEM',
                array(
                    'data_type' => '\Custom\Core\FieldEnumTable',
                    'reference' => array('=this.UF_TAX_SYSTEM' => 'ref.ID'),
                    'join_type' => 'LEFT'
                )
            )
            ->exec();
        if($company = $resCompany->fetch())
        {
            $companyId = $company["UF_XML_ID"];

            if($company["COMPANY_TYPE"] == "ip")
            {
                $fio = explode(" ", $company["UF_FIO"]);

                $resContact = \CRest::call(
                    'crm.contact.add',
                    ['FIELDS' => [
                        'LAST_NAME' => $fio[0],
                        'NAME' => $fio[1],
                        'SECOND_NAME' => $fio[2],
                        'EMAIL' => [
                            [
                                "VALUE" => $company["UF_EMAIL"],
                                "VALUE_TYPE" => "WORK"
                            ]
                        ],
                        'PHONE' => [
                            [
                                "VALUE" => $company["UF_PHONE"],
                                "VALUE_TYPE" => "WORK"
                            ]
                        ],
                        'COMPANY_ID' => $companyId
                    ]]
                );

                if($resContact["result"])
                    $contactId = $resContact["result"];

                $requsiteFields = [
                    'ACTIVE' => 'Y',
                    "ENTITY_TYPE_ID" => 4,
                    "ENTITY_ID" => $companyId,
                    "PRESET_ID" => 2,
                    "NAME" => $company["UF_NAME"],
                    "RQ_COMPANY_NAME" => $company["UF_NAME"],
                    "RQ_COMPANY_FULL_NAME" => $company["UF_NAME"],
                    "RQ_LAST_NAME" => $fio[0],
                    "RQ_FIRST_NAME" => $fio[1],
                    "RQ_SECOND_NAME" => $fio[2],
                    "RQ_INN" => $company["UF_INN"],
                    "RQ_OGRNIP" => $company["UF_OGRNIP"],
                ];

                $resultRequisite = \CRest::call(
                    'crm.requisite.add',
                    [
                        'FIELDS' => $requsiteFields
                    ]
                );

                if($resultRequisite["result"])
                    $requsiteId = $resultRequisite["result"];

                $addressFields = [
                    "TYPE_ID" => 4,
                    "ENTITY_TYPE_ID" => 8,
                    "ENTITY_ID" => $requsiteId,
                    "ADDRESS_1" => $company["UF_REGISTRATION_ADDRESS"],
                ];

                $resultAddress = \CRest::call(
                    'crm.address.add',
                    [
                        'FIELDS' => $addressFields
                    ]
                );

                $bankRequsiteFields = [
                    "ACTIVE" => "Y",
                    "ENTITY_TYPE_ID" => 8,
                    "ENTITY_ID" => $requsiteId,
                    "NAME" => $company["UF_BANK_NAME"],
                    "RQ_BANK_NAME" => $company["UF_BANK_NAME"],
                    "RQ_BIK" => $company["UF_BANK_BIK"],
                    "RQ_ACC_NUM" => $company["UF_BANK_RS"],
                    "RQ_COR_ACC_NUM" => $company["UF_BANK_KORR"],
                    "COMMENTS" => $company["UF_BANK_COMMENT"],
                ];

                $resultBankRequisite = \CRest::call(
                    'crm.requisite.bankdetail.add',
                    [
                        'FIELDS' => $bankRequsiteFields
                    ]
                );

                $dealFIelds = [
                    "NAME" => "Договор ".$companyId,
                    "CATEGORY_ID" => 30,
                    "COMPANY_ID" => $companyId,
                    "CONTACT_ID" => $contactId,
                    "UF_CRM_1733202527739" => $company["TAX_SYSTEM_MANE"],
                    "UF_CRM_66683177DBD6E" => $company["UF_BANK_COMMENT"],
                    "UF_CRM_6668317701398" => $company["UF_PASSPORT_SERIES"],
                    "UF_CRM_666831772D36D" => $company["UF_PASSPORT_ISSUED"],
                    "UF_CRM_66683177653DD" => $company["UF_PASSPORT_DATE"]->toString(),
                ];

                $fileP1 = json_decode($company["UF_FILE_PASPORT_1"], true);
                $fileP2 = json_decode($company["UF_FILE_PASPORT_2"], true);
                //$fileSn = json_decode($company["UF_FILE_SNILS"], true);

                $dealFIelds["UF_CRM_66683177ED000"] = [
                    'fileData'=>[
                        0=>$fileP1['name'],
                        1=>base64_encode(file_get_contents($fileP1['path']))
                    ]
                ];
                $dealFIelds["UF_CRM_666831787AD7B"] = [
                    'fileData'=>[
                        0=>$fileP2['name'],
                        1=>base64_encode(file_get_contents($fileP2['path']))
                    ]
                ];
                /*$dealFIelds["UF_CRM_1733206313945"] = [
                    'fileData'=>[
                        0=>$fileSn['name'],
                        1=>base64_encode(file_get_contents($fileSn['path']))
                    ]
                ];*/

                $dealFIelds["UF_CRM_1713353297586"] = self::CONTRACT_TYPE_CONFIRM_XML_ID[$company["CONTRACT_TYPE_CONFIRM_XML_ID"]];

                $dealFIelds["UF_CRM_1713353215708"] = self::getB24UserFieldValueID("UF_CRM_1713353215708", $company["COMPANY_TYPE"]);

                $resultDeal = \CRest::call(
                    'crm.deal.add',
                    [
                        'FIELDS' => $dealFIelds
                    ]
                );
            }

            if($company["COMPANY_TYPE"] == "legal")
            {
                $fio = explode(" ", $company["UF_FIO"]);

                $resContact = \CRest::call(
                    'crm.contact.add',
                    ['FIELDS' => [
                        'LAST_NAME' => $fio[0],
                        'NAME' => $fio[1],
                        'SECOND_NAME' => $fio[2],
                        'EMAIL' => [
                            [
                                "VALUE" => $company["UF_EMAIL"],
                                "VALUE_TYPE" => "WORK"
                            ]
                        ],
                        'PHONE' => [
                            [
                                "VALUE" => $company["UF_PHONE"],
                                "VALUE_TYPE" => "WORK"
                            ]
                        ],
                        'COMPANY_ID' => $companyId
                    ]]
                );

                if($resContact["result"])
                    $contactId = $resContact["result"];

                $requsiteFields = [
                    'ACTIVE' => 'Y',
                    "ENTITY_TYPE_ID" => 4,
                    "ENTITY_ID" => $companyId,
                    "PRESET_ID" => 1,
                    "NAME" => $company["UF_NAME"],
                    "RQ_COMPANY_NAME" => $company["UF_NAME"],
                    "RQ_COMPANY_FULL_NAME" => $company["UF_FULL_NAME"],
                    "RQ_LAST_NAME" => $fio[0],
                    "RQ_FIRST_NAME" => $fio[1],
                    "RQ_SECOND_NAME" => $fio[2],
                    "RQ_INN" => $company["UF_INN"],
                    "RQ_KPP" => $company["UF_KPP"],
                    "RQ_OGRN" => $company["UF_OGRN"],
                ];

                $resultRequisite = \CRest::call(
                    'crm.requisite.add',
                    [
                        'FIELDS' => $requsiteFields
                    ]
                );
                
                if($resultRequisite["result"])
                    $requsiteId = $resultRequisite["result"];

                $addressFields = [
                    "TYPE_ID" => 4,
                    "ENTITY_TYPE_ID" => 8,
                    "ENTITY_ID" => $requsiteId,
                    "ADDRESS_1" => $company["UF_REGISTRATION_ADDRESS"],
                ];

                $resultAddress = \CRest::call(
                    'crm.address.add',
                    [
                        'FIELDS' => $addressFields
                    ]
                );

                $bankRequsiteFields = [
                    "ACTIVE" => "Y",
                    "ENTITY_TYPE_ID" => 8,
                    "ENTITY_ID" => $requsiteId,
                    "NAME" => $company["UF_BANK_NAME"],
                    "RQ_BANK_NAME" => $company["UF_BANK_NAME"],
                    "RQ_BIK" => $company["UF_BANK_BIK"],
                    "RQ_ACC_NUM" => $company["UF_BANK_RS"],
                    "RQ_COR_ACC_NUM" => $company["UF_BANK_KORR"],
                    "COMMENTS" => $company["UF_BANK_COMMENT"],
                ];

                $resultBankRequisite = \CRest::call(
                    'crm.requisite.bankdetail.add',
                    [
                        'FIELDS' => $bankRequsiteFields
                    ]
                );

                $dealFIelds = [
                    "NAME" => "Договор ".$companyId,
                    "CATEGORY_ID" => 30,
                    "COMPANY_ID" => $companyId,
                    "CONTACT_ID" => $contactId,
                    "UF_CRM_1733202527739" => $company["TAX_SYSTEM_MANE"],
                    "UF_CRM_66683177DBD6E" => $company["UF_BANK_COMMENT"],
                    "UF_CRM_6668317BA8836" => $company["UF_SIGNATORE_FIO"],
                    "UF_CRM_6668317C79CA6" => $company["UF_SIGNATORE_POSITION"],
                ];

                $dealFIelds["UF_CRM_1713353297586"] = self::CONTRACT_TYPE_CONFIRM_XML_ID[$company["CONTRACT_TYPE_CONFIRM_XML_ID"]];

                $dealFIelds["UF_CRM_1713353215708"] = self::getB24UserFieldValueID("UF_CRM_1713353215708", $company["COMPANY_TYPE"]);

                $resultDeal = \CRest::call(
                    'crm.deal.add',
                    [
                        'FIELDS' => $dealFIelds
                    ]
                );
            }

            if($company["COMPANY_TYPE"] == "person")
            {
                $fio = explode(" ", $company["UF_FIO"]);

                $resContact = \CRest::call(
                    'crm.contact.add',
                    ['FIELDS' => [
                        'LAST_NAME' => $fio[0],
                        'NAME' => $fio[1],
                        'SECOND_NAME' => $fio[2],
                        'EMAIL' => [
                            [
                                "VALUE" => $company["UF_EMAIL"],
                                "VALUE_TYPE" => "WORK"
                            ]
                        ],
                        'PHONE' => [
                            [
                                "VALUE" => $company["UF_PHONE"],
                                "VALUE_TYPE" => "WORK"
                            ]
                        ],
                        'COMPANY_ID' => $companyId
                    ]]
                );

                if($resContact["result"])
                    $contactId = $resContact["result"];

                $passportSN = explode(" ", $company["UF_PASSPORT_SERIES"]);

                $requsiteFields = [
                    'ACTIVE' => 'Y',
                    "ENTITY_TYPE_ID" => 4,
                    "ENTITY_ID" => $companyId,
                    "PRESET_ID" => 3,
                    "NAME" => $company["UF_NAME"],
                    "RQ_LAST_NAME" => $fio[0],
                    "RQ_FIRST_NAME" => $fio[1],
                    "RQ_SECOND_NAME" => $fio[2],
                    "RQ_INN" => $company["UF_INN"],
                    "RQ_IDENT_DOC" => self::RQ_IDENT_DOC,
                    "RQ_IDENT_DOC_SER" => $passportSN[0],
                    "RQ_IDENT_DOC_NUM" => $passportSN[1],
                    "RQ_IDENT_DOC_ISSUED_BY" => $company["UF_PASSPORT_ISSUED"],
                    "RQ_IDENT_DOC_DATE" => $company["UF_PASSPORT_DATE"]->toString(),
                ];

                $resultRequisite = \CRest::call(
                    'crm.requisite.add',
                    [
                        'FIELDS' => $requsiteFields
                    ]
                );

                if($resultRequisite["result"])
                    $requsiteId = $resultRequisite["result"];

                $addressFields = [
                    "TYPE_ID" => 4,
                    "ENTITY_TYPE_ID" => 8,
                    "ENTITY_ID" => $requsiteId,
                    "ADDRESS_1" => $company["UF_REGISTRATION_ADDRESS"],
                ];

                $resultAddress = \CRest::call(
                    'crm.address.add',
                    [
                        'FIELDS' => $addressFields
                    ]
                );

                $bankRequsiteFields = [
                    "ACTIVE" => "Y",
                    "ENTITY_TYPE_ID" => 8,
                    "ENTITY_ID" => $requsiteId,
                    "NAME" => $company["UF_BANK_NAME"],
                    "RQ_BANK_NAME" => $company["UF_BANK_NAME"],
                    "RQ_BIK" => $company["UF_BANK_BIK"],
                    "RQ_ACC_NUM" => $company["UF_BANK_RS"],
                    "RQ_COR_ACC_NUM" => $company["UF_BANK_KORR"],
                    "COMMENTS" => $company["UF_BANK_COMMENT"],
                ];

                $resultBankRequisite = \CRest::call(
                    'crm.requisite.bankdetail.add',
                    [
                        'FIELDS' => $bankRequsiteFields
                    ]
                );

                $dealFIelds = [
                    "NAME" => "Договор ".$companyId,
                    "CATEGORY_ID" => 30,
                    "COMPANY_ID" => $companyId,
                    "CONTACT_ID" => $contactId,
                    "UF_CRM_1733202527739" => $company["TAX_SYSTEM_MANE"],
                    "UF_CRM_66683177DBD6E" => $company["UF_BANK_COMMENT"],
                    "UF_CRM_6668317701398" => $company["UF_PASSPORT_SERIES"],
                    "UF_CRM_666831772D36D" => $company["UF_PASSPORT_ISSUED"],
                    "UF_CRM_66683177653DD" => $company["UF_PASSPORT_DATE"]->toString(),
                    "UF_CRM_66683178D10C1" => $company["UF_INN"],
                    "UF_CRM_1732779114243" => $company["UF_SNILS"],
                ];

                $fileP1 = json_decode($company["UF_FILE_PASPORT_1"], true);
                $fileP2 = json_decode($company["UF_FILE_PASPORT_2"], true);
                $fileSn = json_decode($company["UF_FILE_SNILS"], true);
                $fileEm = json_decode($company["UF_FILE_SELF_EMPLOYED"], true);

                $dealFIelds["UF_CRM_66683177ED000"] = [
                    'fileData'=>[
                        0=>$fileP1['name'],
                        1=>base64_encode(file_get_contents($fileP1['path']))
                    ]
                ];
                $dealFIelds["UF_CRM_666831787AD7B"] = [
                    'fileData'=>[
                        0=>$fileP2['name'],
                        1=>base64_encode(file_get_contents($fileP2['path']))
                    ]
                ];
                $dealFIelds["UF_CRM_1733206313945"] = [
                    'fileData'=>[
                        0=>$fileSn['name'],
                        1=>base64_encode(file_get_contents($fileSn['path']))
                    ]
                ];
                $dealFIelds["UF_CRM_1733206340898"] = [
                    'fileData'=>[
                        0=>$fileEm['name'],
                        1=>base64_encode(file_get_contents($fileEm['path']))
                    ]
                ];

                $dealFIelds["UF_CRM_1713353297586"] = self::CONTRACT_TYPE_CONFIRM_XML_ID[$company["CONTRACT_TYPE_CONFIRM_XML_ID"]];

                $dealFIelds["UF_CRM_1713353215708"] = self::getB24UserFieldValueID("UF_CRM_1713353215708", $company["COMPANY_TYPE"]);

                $resultDeal = \CRest::call(
                    'crm.deal.add',
                    [
                        'FIELDS' => $dealFIelds
                    ]
                );
            }

            if($resultDeal["result"])
            {
                $enumId =  self::getHLfileldEnumId(
                    self::HLNAME_ORGANIZATION,
                    "UF_CONTRACT",
                    "verif"
                );

                $orgEntity = new ORM\Query\Query('\Custom\Core\Users\CompaniesTable');
                $query       = $orgEntity
                    ->setSelect(["ID", "UF_CONTRACT"])
                    ->setFilter(['ID' => $id])
                    ->exec();
                $objOrg    = $query->fetchObject();

                $objOrg->set("UF_CONTRACT", $enumId);
                $objOrg->set("UF_B24_DEAL_ID", $resultDeal["result"]);

                $objOrg->save();

                /*if($company["CONTRACT_TYPE_CONFIRM_XML_ID"] == "scet")
                {
                    sleep(10);

                    $resultDocs = \CRest::call(
                        'crm.documentgenerator.document.list',
                        [
                            'filter' => [
                                "entityTypeId" => 2,
                                'entityId' => $resultDeal["result"],
                            ]
                        ]
                    );

                    if ($resultDocs["result"]["documents"] && count($resultDocs["result"]["documents"]) > 0) {
                        $filePath = $_SERVER["DOCUMENT_ROOT"] . self::DOCS_TMP_PATH . $resultDocs["result"]["documents"][0]["title"].".docx";

                        $ch = curl_init($resultDocs["result"]["documents"][0]["downloadUrlMachine"]);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HEADER, false);
                        curl_exec($ch);
                        $resFile = curl_exec($ch);

                        file_put_contents($filePath, $resFile);

                        $fileId = \CFile::SaveFile(\CFile::MakeFileArray($filePath), "tmp");

                        $objOrg->set("UF_CONTRACT_PAY_FILE", $fileId);
                        $objOrg->save();

                        unlink($filePath);
                    }
                }*/
            }

        }
        else
        {
            throw new \Exception('Договора нет или он уже на проверке');
        }
    }

}
