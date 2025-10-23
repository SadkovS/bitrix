<?

namespace Local\Api\Controllers;
use Bitrix\Main\Diag\Debug;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\ORM;
use Bitrix\Highloadblock as HL;use Custom\Core\TelegramBot;

class Moderation {

    private $userGroups;
    private $request;
    private $userID;
    private $resposeCode;

    function __construct()
    {
        global $USER;
        $this->request    = request()->get();
        $this->userID     = $this->request['_user']['ID'];
        $user             = UserTable::getList(
            [
                'select' => ['ID', 'GROUPS'],
                'filter' => ['ID' => $this->userID],
            ]
        );
        $objUser          = $user->fetchObject();
        $this->userGroups = ($objUser->getGroups())->getGroupIdList();
        if (!is_array($this->userGroups) || (!in_array(1, $this->userGroups) && !in_array(8, $this->userGroups)))
            response()->json(['status' => 'error', 'result' => 'Access denied'], 403);
        $USER->Authorize($this->userID);
    }

    public function setApproveEvent()
    {
        try {
            Loader::includeModule('custom.core');
            if (!$this->request['approve'] && !$this->request['comment']) throw new \Exception('Empty comment');

            $hlblock   = HL\HighloadBlockTable::getById(HL_EVENTS_ID)->fetch();
            $entity    = HL\HighloadBlockTable::compileEntity($hlblock);
            $hlbClass  = $entity->getDataClass();
            $query = $hlbClass::getList(
                [
                    "select"      => ["ID"],
                    "filter"  => ['ID' => $this->request['id'], '!STATUS.UF_XML_ID' => 'cancelled'],
                    "runtime" => [
                        new \Bitrix\Main\Entity\ReferenceField(
                            'STATUS',
                            '\Custom\Core\Events\EventsStatusTable',
                            ['this.UF_STATUS' => 'ref.ID'],
                            ['join_type' => 'LEFT'],
                        ),
                    ],
                    "limit"       => 1,
                    'count_total' => true
                ]
            );
            if ($query->getCount() < 1) throw new \Exception('Event not found');
            $objEvent = $query->fetchObject();
            $status   = $this->request['approve'] ? 5 : 3;

            $objEvent->set('UF_STATUS', $status);
            $objEvent->set('UF_DATE_UPDATE', (new \DateTime())->format('d.m.Y H:i:s'));
            $resEvent = $objEvent->save();

            if (!$resEvent->isSuccess()) throw new \Exception(implode(', ', $resEvent->getErrors()));

            $entityHistory = \Custom\Core\Events\EventsStatusHistoryTable::getEntity();
            $objHistory    = $entityHistory->createObject();
            $objHistory->set('UF_EVENT_ID', $this->request['id']);
            $objHistory->set('UF_STATUS_ID', $status);
            $objHistory->set('UF_MODIFIED_BY', $this->userID);
            $objHistory->set('UF_DATE_UPDATE', (new \DateTime())->format('d.m.Y H:i:s'));
            $objHistory->set('UF_COMMENT', $this->request['comment']);
            $resHistory = $objHistory->save();

            if(!$resHistory->isSuccess()) throw new \Exception(implode(', ', $resHistory->getErrors()));
            response()->json(
                [
                    'status' => 'success',
                    'result' => '',
                ]
            );
        } catch (\Exception $e) {
            response()->json(
                [
                    'status' => 'error',
                    'result' => $e->getMessage(),
                ]
            );
        }

    }

    public function setEvent()
    {
        $request = request()->get();
        response()->json(
            [
                'status' => 'success',
                'result' => $request,
            ]
        );
    }
}
