<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/crest/crest.php');
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/include/handlers/EventHandler.php');


use Bitrix\Main\Loader;
use Bitrix\Main\ORM;
use Bitrix\Main\Diag\Debug;
use Local\PhpInterface\Handlers\EventHandler;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\UserTable as UTable;

Loader::includeModule('custom.core');
Loader::includeModule('iblock');

$eventEntity = (new ORM\Query\Query('Custom\Core\Events\EventsTable'))->getEntity();

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'EventsOnBeforeAdd',
    function (\Bitrix\Main\Event $e) {
        global $USER;
        $arParams                   = $e->getParameter('fields');
        $arParams['UF_CREATED_BY']  = $USER->GetID();
        $arParams['UF_MODIFIED_BY'] = $USER->GetID();
        $arParams['UF_NAME']        = str_replace('\'', '"', $arParams['UF_NAME']);
        $e->setParameter('fields', $arParams);
    }
);

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'EventsOnAfterAdd',
    function (\Bitrix\Main\Event $e) {
        global $USER;
        $objImages     = new Custom\Core\Images;
        $entity        = \Custom\Core\Events\EventsTable::getEntity();
        $entityClass   = $entity->getDataClass();
        $eventID = (int)$e->getParameter('id');
        $entityElement = $entityClass::getByPrimary($eventID);
        $objElement    = $entityElement->fetchObject();

        $entityHistory = \Custom\Core\Events\EventsStatusHistoryTable::getEntity();
        $objHistory    = $entityHistory->createObject();
        $objHistory->set('UF_EVENT_ID', $e->getParameter('id'));
        $objHistory->set('UF_STATUS_ID', $e->getParameter('fields')['UF_STATUS']);
        $objHistory->set('UF_MODIFIED_BY', $USER->GetID());
        $objHistory->set('UF_DATE_UPDATE', (new DateTime())->format('d.m.Y H:i:s'));
        $objHistory->save();

        if ((int)$objElement->get('UF_IMG') > 0) {

            $objImages->processImageResizes($objElement->get('UF_IMG'));
        }

        if (is_array($objElement->get('UF_FILES')) && count($objElement->get('UF_FILES')) > 0) {
            foreach ($objElement->get('UF_FILES') as $fileID) {
                $objImages->processImageResizes((int)$fileID);
            }
        }
    }
);

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'EventsOnBeforeUpdate',
    function (\Bitrix\Main\Event $e) {
        $object       = $e->getParameter('object');
        $arParams     = $e->getParameter('fields');

        $actualStatus = $object->remindActual('UF_STATUS');
        //$actual['UF_IMG']                  = $object->remindActual('UF_IMG');
        $actual['UF_NAME']                 = $object->remindActual('UF_NAME');
        $actual['UF_DESCRIPTION']          = $object->remindActual('UF_DESCRIPTION');
        $actual['UF_QUESTIONNAIRE_FIELDS'] = $object->remindActual('UF_QUESTIONNAIRE_FIELDS');
        $actual['UF_SIT_MAP'] = $object->remindActual('UF_SIT_MAP');

        $result       = new \Bitrix\Main\Entity\EventResult();
        if (isset($arParams['BARCODE_UPDATE'])) {
            $_SESSION['BARCODE_UPDATE'] = $arParams['BARCODE_UPDATE'];
            $result->unsetField('BARCODE_UPDATE');
        }

        $changeStatus = false;
        //todo Временно отключено!
//        foreach ($arParams as $key => $value) {
//            if (
//                in_array($key, ['UF_NAME', 'UF_DESCRIPTION', 'UF_QUESTIONNAIRE_FIELDS', 'UF_IMG','UF_SIT_MAP']) &&
//                $actual[$key] != $value && in_array($actualStatus, [5, 2, 1])
//            ) {
//                $changeStatus = true;
//                break;
//            }
//        }


        //if ($changeStatus) $result->modifyFields(['UF_STATUS' => 4]);

        return $result;
    }
);

Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'EventsOnUpdate',
    function (\Bitrix\Main\Event $e) {
        $handler = new EventHandler($e);
        return $handler->process();
    }
);
\Bitrix\Main\ORM\EventManager::getInstance()->addEventHandler(
    'Custom\Core\Events\EventsTable',
    'OnUpdate',
    function (\Bitrix\Main\Event $e) {
        $handler = new EventHandler($e);
        return $handler->process();
    }
);
Bitrix\Main\EventManager::getInstance()->addEventHandler(
    '',
    'EventsOnAfterUpdate',
    function (\Bitrix\Main\Event $e) {
        $objImages     = new Custom\Core\Images;
        $entity        = \Custom\Core\Events\EventsTable::getEntity();
        $entityClass   = $entity->getDataClass();
        $eventID       = (int)$e->getParameter('id')['ID'];
        $entityElement = $entityClass::getByPrimary($eventID);
        $objElement    = $entityElement->fetchObject();

        if (isset($_SESSION['update_task_id'])) {
            $objElement->set('UF_XML_ID', $_SESSION['update_task_id']);
            $resAdd = $objElement->save();
            if (!$resAdd->isSuccess()) Debug::writeToFile($resAdd->getErrorMessages(), 'resAdd', $fileName = "_b24_event.txt");
            unset($_SESSION['update_task_id']);
        }

        if ((int)$objElement->get('UF_IMG') > 0) {

            $objImages->processImageResizes($objElement->get('UF_IMG'));
        }

        if (is_array($objElement->get('UF_FILES')) && count($objElement->get('UF_FILES')) > 0) {
            foreach ($objElement->get('UF_FILES') as $fileID) {
                $objImages->processImageResizes((int)$fileID);
            }
        }
    }
);