<?php

namespace Local\Api\Controllers\V1;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\ORM;
use Bitrix\Main\Application;
use Custom\Core\Products;

class widget {
    function __construct()
    {
        Loader::includeModule("custom.core");
    }

    public function addAnaliticsView()
    {
        $request = request()->get();
        try{
            $widgetEntity = new ORM\Query\Query('Custom\Core\Tickets\WidgetsTable');
            $widgetClass  = $widgetEntity->getEntity()->getDataClass();
            $query       = $widgetEntity
                ->setSelect(['UF_VIEWS'])
                ->setFilter(['UF_UUID' => $request['id']])
                ->countTotal(true)
                ->exec();
            if ($query->getCount() < 1) throw new \Exception('Weget not found');

            $obWeget = $query->fetchObject();

            $count = $obWeget->get('UF_VIEWS');

            if(!$count)
                $count = 0;

            $obWeget->set('UF_VIEWS', $count + 1);

            $obWeget->save();

            response()->json(
                [
                    'status' => 'success',
                ],200,[],['Content-Type' => 'application/json']
            );
        }catch(\Exception $e){
            response()->json(
                [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ],400,[],['Content-Type' => 'application/json']
            );
        }
    }
}