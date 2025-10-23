<?php

namespace Local\PhpInterface\Handlers;
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/include/handlers/BarcodeHandlerTrait.php');

use Bitrix\Main\Diag\Debug;
use Bitrix\Main\ORM;
use Bitrix\Main\UserTable as UTable;
use Custom\Core\Helper as Helper;


class EventHandler {
    use BarcodeHandlerTrait;

    private $event;
    private $user;
    private $eventID;
    private $series;
    private $elementNewStatus;
    private $elementCurrentStatus;
    private $taskId;
    private $bxApp;
    private $objEvent;
    private $arBarcodes;

    const B24_MODERATION_STATUS        = 1;
    const B24_CANCEL_MODERATION_STATUS = 5;


    public function __construct(\Bitrix\Main\Event $event)
    {
        global $USER;
        global $APPLICATION;
        $this->event = $event;
        $this->user  = $USER;
        $this->bxApp = $APPLICATION;
        $this->initializeEventData();
    }

    private function initializeEventData()
    {
        $entity      = \Custom\Core\Events\EventsTable::getEntity();
        $entityClass = $entity->getDataClass();

        $this->eventID          = (int)$this->event->getParameter('id')['ID'];
        $this->elementNewStatus = (int)$this->event->getParameter('fields')['UF_STATUS'];

        $entityElement              = $entityClass::getByPrimary($this->eventID);
        $this->objEvent             = $entityElement->fetchObject();
        $this->taskId               = (int)$this->objEvent->getUfXmlId();
        $this->elementCurrentStatus = (int)$this->objEvent->getUfStatus();
        $this->series               = $this->objEvent->getUfSeries();
    }

    private function createStatusHistory()
    {
        $entityHistory = \Custom\Core\Events\EventsStatusHistoryTable::getEntity();
        $objHistory    = $entityHistory->createObject();
        $objHistory->set('UF_EVENT_ID', $this->eventID);
        $objHistory->set('UF_STATUS_ID', $this->elementNewStatus);
        $objHistory->set('UF_MODIFIED_BY', $this->user->GetID());
        $objHistory->set('UF_DATE_UPDATE', (new \DateTime())->format('d.m.Y H:i:s'));
        return $objHistory->save();
    }

    private function updateB24Task($statusModeration)
    {
        if ($this->taskId > 0) {
            $fields = [
                'GROUP_ID' => B24_WORK_GROUP_ID,
                'TITLE'    => $this->objEvent->getUfName(),
                'STATUS'   => $statusModeration
            ];

            if ($statusModeration == self::B24_CANCEL_MODERATION_STATUS) {
                $fields['STAGE_ID'] = 95;
            }

            $res = \CRest::call(
                'tasks.task.update', [
                                       'taskId' => $this->taskId,
                                       'fields' => $fields
                                   ]
            );

            Debug::writeToFile($res, 'update', "_b24_event.txt");
            return $res;
        }
        return false;
    }

    private function createNewB24Task(int $userID)
    {
        $user   = $this->getObjectUser($userID);
        $fields = [
            'TITLE'                   => $this->objEvent->getUfName(),
            'RESPONSIBLE_ID'          => B24_RESPONSIBLE_ID,
            B24_EVENT_ID_FIELD_NAME   => $this->eventID,
            B24_CONTACT_ID_FIELD_NAME => $user['UF_B24_CONTACT_ID'],
            'GROUP_ID'                => B24_WORK_GROUP_ID,
        ];

        //Debug::writeToFile($fields,'fields','_B24_FIELDS.txt');
        $res = \CRest::call('tasks.task.add', ['fields' => $fields]);

        if ((int)$res['result']['task']['id'] > 0) {
            $_SESSION['update_task_id'] = (int)$res['result']['task']['id'];
        }

        return $res;
    }

    private function updateProductStatus($isActive, $onModeration = false)
    {
        $productEntity = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
        $query         = new ORM\Query\Query($productEntity);
        $resProduct    = $query
            ->setSelect(['ID'])
            ->setFilter(['EVENT_ID.VALUE' => $this->eventID])
            ->countTotal(true)
            ->exec();

        if ($resProduct->getCount() > 0) {
            $objProduct = $resProduct->fetchObject();
            $objProduct->set('ACTIVE', $isActive);

            if($onModeration)
                $objProduct->set('MODERATION', Helper::getIBPropEmunIdFromXmlId("MODERATION", "Y"));
            else
                $objProduct->set('MODERATION', null);

            $objProduct->save();
            \Custom\Core\Products::addToIndex($objProduct->getId());
        }
    }

    public function process()
    {
        $result     = new \Bitrix\Main\Entity\EventResult();
        $serverName = \COption::GetOptionString('main', 'server_name', '');
        $arParams   = $this->event->getParameter('fields');
        $productID  = $this->getProductIDbyEventID($this->eventID);
        $eventLink  = 'https://' . $serverName . '/event/' . $productID . '/';

        $companyID = $this->objEvent->getUfCompanyId();
        $creatorID = $this->objEvent->getUfCreatedBy();

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


        if (isset($_SESSION['BARCODE_UPDATE'])) {
            $this->handleBarcodeGeneration();
            unset($_SESSION['BARCODE_UPDATE']);
        }

        if (!$this->shouldProcessStatusChange()) {
            return $result;
        }
        if($this->user->GetLogin() != 'b24_moderation')
            $this->createStatusHistory();

        if (
            $this->elementNewStatus == 5 && $this->elementNewStatus != $this->elementCurrentStatus
        ) {
            $this->handleBarcodeGeneration();
            $this->updateProductStatus('Y');
            //Отправка уведомления
            \CEvent::Send(
                'EVENT_HAS_BEEN_PUBLISHED', 's1', [
                'EMAIL'       => $email,
                'SERVER_NAME' => 'https://' . $serverName,
                'FULL_NAME'   => $full_name,
                'EVENT_NAME'  => $this->objEvent->getUfName(),
                'EVENT_LINK'  => $eventLink,
                'BCC'         => implode(',', $bcc)
            ]
            );
        }

        if ($this->elementNewStatus == 1) {
            $this->handleModeration();
            $this->updateProductStatus('Y', true);
        }

        if ($this->elementNewStatus == 7) $this->updateProductStatus('N');

        if ($this->elementNewStatus == 4 || $this->elementNewStatus == 3) {
            $this->updateProductStatus('N');

            if($this->elementCurrentStatus == 1)
                $this->handleModerationRemoval();
        }

        return $result;
    }

    private function shouldProcessStatusChange()
    {
        return $this->elementNewStatus > 0
            && $this->elementCurrentStatus > 0
            && $this->elementNewStatus != $this->elementCurrentStatus;
    }

    private function handleModeration(): void
    {
        $userID = $this->objEvent->getUfCreatedBy();

        if ($this->taskId < 1) {
            $resTaskUpdate = $this->createNewB24Task($userID);
        } else {
            $resTaskUpdate = $this->updateB24Task(self::B24_MODERATION_STATUS);
        }

        if (!isset($resTaskUpdate['result'])) Debug::writeToFile($resTaskUpdate, '', $fileName = "_b24_event.txt");
    }

    private function getObjectUser(int $id): array
    {
        $resUser = UTable::getList(
            [
                "select" => [
                    'ID',
                    'UF_B24_CONTACT_ID'
                ],
                'filter' => ['ID' => $id],
                'limit'  => 1
            ]
        );
        return $resUser->fetch() ?? [];
    }

    private function handleModerationRemoval()
    {
        $resTaskUpdate = $this->updateB24Task(self::B24_CANCEL_MODERATION_STATUS);
        if (!isset($resTaskUpdate['result'])) Debug::writeToFile($resTaskUpdate, 'ModerationRemoval', $fileName = "_b24_event.txt");
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
                    'FULL_NAME', 'CONCAT(%s," ",%s)', ['USER.LAST_NAME', 'USER.NAME']
                ),
            )
            ->exec();
        while ($profile = $resProfileOb->fetch()) {
            $res[$profile['USER_ID']] = $profile;
        }
        return $res;
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
}