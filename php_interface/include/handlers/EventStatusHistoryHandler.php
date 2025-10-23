<?php

namespace Local\PhpInterface\Handlers;

use Bitrix\Main\Loader;
use Bitrix\Main\Diag\Debug;
use Custom\Core\Traits\PropertyEnumTrait;
use Bitrix\Main\ORM;

Loader::includeModule('custom.core');
Loader::includeModule('sale');

class EventStatusHistoryHandler {

    use PropertyEnumTrait;

    private $event;
    private $eventID;
    private $serverName;
    private $arParams;

    public function __construct(\Bitrix\Main\Event $event)
    {
        $this->event      = $event;
        $this->serverName = \COption::GetOptionString('main', 'server_name', '');
        $this->arParams = $event->getParameter('fields');
        $this->eventID    = (int)$this->arParams['UF_EVENT_ID'];
    }

    private function makeNotificationData(): array
    {
        $arEvent   = $this->getEventByID($this->eventID);
        $productID = $this->getProductIDbyEventID($this->eventID);
        $eventLink = 'https://' . $this->serverName . '/admin_panel/events/?popup-event-id='.$this->eventID;
        $creatorID = (int)$arEvent['UF_CREATED_BY'];
        $companyID = (int)$arEvent['UF_COMPANY_ID'];

        $profiles  = $this->getEmployees($companyID);
        $email     = $profiles[$creatorID]['EMAIL'];
        $full_name = $profiles[$creatorID]['FULL_NAME'];
        $bcc       = [];

        foreach ($profiles as $profile) {
            if ($profile['UF_IS_OWNER']) {
                $email     = $profile['EMAIL'];
                $full_name = $profile['FULL_NAME'];
            }
            $bcc[] = $profile['EMAIL'];
        }
        $keyDouble = array_search($email, $bcc);
        if ($keyDouble !== false) unset($bcc[$keyDouble]);

        return [
            'FULL_NAME'         => $full_name,
            'EMAIL'             => $email,
            'SERVER_NAME' => 'https://' . $this->serverName,
            'BCC'               => implode(',', $bcc),
            'EVENT_LINK'        => $eventLink,
            'MODERATOR_COMMENT' => $this->arParams['UF_COMMENT'],
            'EVENT_NAME'        => $arEvent['UF_NAME'],
        ];

    }

    private static function getStatusXmlID(int $id): string
    {
        $query     = new ORM\Query\Query('\Custom\Core\Events\EventsStatusTable');
        $resStatus = $query
            ->setSelect(['UF_XML_ID'])
            ->setOrder(['UF_SORT' => 'ASC'])
            ->setFilter(['ID' => $id])
            ->setCacheTtl(3600)
            ->exec();

        return $resStatus->fetch()['UF_XML_ID'];
    }

    private function sendRejectNotificationEmail()
    {
        $arData = $this->makeNotificationData();
        \CEvent::Send('EVENT_MODERATION_REJECTED', 's1', $arData);
    }

    private function sendConfirmNotificationEmail()
    {
        $arData = $this->makeNotificationData();
        \CEvent::Send('EVENT_MODERATION_CONFIRMED', 's1', $arData);
    }

    private function getEventByID(int $id): array
    {
        $eventEntity = new ORM\Query\Query('Custom\Core\Events\EventsTable');
        $query       = $eventEntity
            ->setSelect(['*'])
            ->setFilter(['ID' => $id])
            ->countTotal(true)
            ->exec();

        return $query->fetch() ?? [];
    }

    private function getEmployees($companyID)
    {
        $queryProfile = new ORM\Query\Query('\Custom\Core\Users\UserProfilesTable');
        $res          = [];
        $resProfileOb = $queryProfile
            ->setFilter(
                [
                    'UF_COMPANY_ID' => $companyID,
                    '!UF_USER_ID'   => false,
                    '!USER.ID'      => false
                ]
            )
            ->setSelect(
                [
                    'USER_ID' => 'USER.ID',
                    'EMAIL'   => 'USER.EMAIL',
                    'FULL_NAME',
                    'UF_IS_OWNER'
                ]
            )
            ->registerRuntimeField(
                'USER',
                [
                    'data_type' => '\Bitrix\Main\UserTable',
                    'reference' => ['=this.UF_USER_ID' => 'ref.ID'],
                    'join_type' => 'LEFT'
                ]
            )
            ->registerRuntimeField(
                new \Bitrix\Main\Entity\ExpressionField(
                    'FULL_NAME', 'CONCAT(%s," ",%s," ",%s)', ['USER.LAST_NAME', 'USER.NAME', 'USER.SECOND_NAME']
                ),
            )
            ->exec();
        while ($profile = $resProfileOb->fetch()) {
            $res[$profile['USER_ID']] = $profile;
        }
        return $res;
    }

    public function process()
    {
        $arParams    = $this->event->getParameter('fields');
        $statusID    = (int)$arParams['UF_STATUS_ID'];
        $statusXmlID = $this->getStatusXmlID($statusID);

        if ($statusXmlID == 'rejected') {
            $this->sendRejectNotificationEmail();
        }
        if ($statusXmlID == 'confirmed') {
            $this->sendConfirmNotificationEmail();
        }
        if ($statusXmlID == 'cancelled') {
            $this->sendNoticeOfCancellationEmail();
        }
    }
    private function getProductIDbyEventID(int $eventID): int
    {
        $productEntity = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
        $productClass  = $productEntity->getDataClass();
        $query         = new ORM\Query\Query($productEntity);
        $resProduct    = $query
            ->setSelect(['ID'])
            ->setFilter(['EVENT_ID.VALUE' => $eventID])
            ->exec();

        $resProduct = $resProduct->fetch();
        return (int)$resProduct['ID'];
    }
    private function sendNoticeOfCancellationEmail()
    {
        $dbRes = \Bitrix\Sale\Order::getList(
            [
                'select'  => [
                    'EVENT_ID'   => 'PROPERTY_EVENT_ID.VALUE',
                    'FULL_NAME'  => 'PROPERTY_BUYER.VALUE',
                    'EMAIL'      => 'PROPERTY_BUYER_EMAIL.VALUE',
                    'EVENT_NAME' => 'EVENT.UF_NAME',
                ],
                'filter'  => [
                    "PAYED"                     => "Y",
                    'STATUS_ID'                 => ['P', 'F'],
                    "PROPERTY_EVENT_ID.CODE"    => "EVENT_ID",
                    "PROPERTY_EVENT_ID.VALUE"   => $this->eventID,
                    "PROPERTY_BUYER_EMAIL.CODE" => "EMAIL",
                    "PROPERTY_BUYER.CODE"       => 'FIO',
                ],
                'runtime' => [
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PROPERTY_BUYER',
                        'Bitrix\Sale\Internals\OrderPropsValueTable',
                        ['=this.ID' => 'ref.ORDER_ID'],
                        ['join_type' => 'inner']
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PROPERTY_BUYER_EMAIL',
                        'Bitrix\Sale\Internals\OrderPropsValueTable',
                        ['=this.ID' => 'ref.ORDER_ID'],
                        ['join_type' => 'inner']
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'PROPERTY_EVENT_ID',
                        'Bitrix\Sale\Internals\OrderPropsValueTable',
                        ['=this.ID' => 'ref.ORDER_ID'],
                        ['join_type' => 'inner']
                    ),
                    new \Bitrix\Main\Entity\ReferenceField(
                        'EVENT',
                        'Custom\Core\Events\EventsTable',
                        ['=this.EVENT_ID' => 'ref.ID'],
                        ['join_type' => 'inner']
                    ),
                ],
                'group'   => ['EVENT_ID', 'EMAIL']
            ]
        );

        while ($order = $dbRes->fetch()) {
            $order['EVENT_NAME'] = trim($order['EVENT_NAME']);
            \CEvent::Send('EVENT_CANCELLED', SITE_ID, $order);
        }
    }
}