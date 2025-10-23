<?php

namespace Local\Api\Controllers\V1;
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Highloadblock as HL;
use Bitrix\Main\ORM;
use Bitrix\Main\Application;
use Custom\Core\Products;

class Tickets {
    function __construct()
    {
        Loader::includeModule("iblock");
        Loader::includeModule("sale");
        Loader::includeModule("custom.core");

        \CBitrixComponent::includeComponentClass("custom:basket");
    }

    public function addProductInfo($items = [])
    {
        $products = [];

        if($items)
        {
            $productsXmlId = [];

            foreach ($items as $key => $item)
            {
                $productsXmlId[] = $item["paymentGroup"];
            }

            $res = \CIBlockElement::GetList(
                [],
                ["XML_ID" => $productsXmlId, "IBLOCK_ID" => 6],
                false,
                false,
                ["ID", "XML_ID", "PROPERTY_RESERVE_TIME", "PROPERTY_TYPE", "IBLOCK_ID"],
            );

            while($ob = $res->Fetch())
            {
                $products[$ob["XML_ID"]] = $ob;
                $products[$ob["XML_ID"]]["NAME"] = $ob["PROPERTY_TYPE_VALUE"];
            }
        }

        return $products;
    }

    public function getEventId($offerId = null)
    {
        $productId = \CCatalogSku::GetProductInfo($offerId)["ID"];

        $eventId = \Custom\Core\Events::getEventIdByProduct($productId);

        return $eventId;
    }



    public function getBarcodesForTicket(&$ticket)
    {
        $barcodes = \Custom\Core\Tickets\BarcodesTable::getList([
            "filter" => [
                "UF_OFFER_ID" => $ticket["id"],
                [
                    "LOGIC" => "OR",
                    ["STATUS.XML_ID" => false],
                    ["STATUS.XML_ID" => "new"],
                ]
            ],
            "select" => ["ID", "UF_SERIES"],
            "limit" => $ticket["quantity"],
            "runtime" => [
                new \Bitrix\Main\Entity\ReferenceField(
                    'STATUS',
                    '\Custom\Core\FieldEnumTable',
                    ['this.UF_STATUS' => 'ref.ID'],
                    ['join_type' => 'LEFT'],
                ),
            ]
        ])->fetchAll();

        self::setNumberTicketForBarcodes($barcodes);

        $ticket["barcodes"] = $barcodes;
    }

    public function addToBasket()
    {
        $request = request()->get();

        $userId = null;
        $items = null;

        if($request["request"])
            $userId = $request["request"]["user_id"];

        if($request["items"])
            $items = $request["items"];
        //file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/log.txt',print_r(array('data' => $request),true)."\n",FILE_APPEND);
        if($userId && $items)
        {
            $this->clearBasket($userId);

            $products = $this->addProductInfo($items);

            $basket = \Bitrix\Sale\Basket::loadItemsForFUser($userId, \Bitrix\Main\Context::getCurrent()->getSite());

            foreach ($items as $item) {

                for($i = 1; $i <= $item["quantity"]; $i++)
                {
                    $fields = [
                        "PRODUCT_ID" => $products[$item["paymentGroup"]]["ID"],
                        "NAME" => $products[$item["paymentGroup"]]["NAME"],
                        'QUANTITY' => 1,
                        'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                        "PRODUCT_PROVIDER_CLASS" => "\Bitrix\Catalog\Product\CatalogProvider"
                    ];

                    if(!empty($item["props"]))
                    {
                        $props = [];
                        foreach ($item["props"] as $key => $val) {
                            if(!$val)
                                continue;

                            $name = $key;
                            if(\BasketCustom::basketPropsShow[$key])
                                $name = \BasketCustom::basketPropsShow[$key];

                            $props[] = [
                                "NAME" => $name,
                                "CODE" => strtoupper($key),
                                "VALUE" => $val,
                                "SORT" => "100",
                            ];
                        }
                        $fields['PROPS'] = $props;
                    }

                    $result = \Bitrix\Catalog\Product\Basket::addProductToBasket(
                        $basket,
                        $fields,
                        [
                            'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                        ],
                        [
                            'USE_MERGE' => 'N',
                        ]
                    );

                    $basket->save();
                }
            }

			\BasketCustom::recalculate($basket);
        }
        else
        {
            response()->json(
                [
                    'status' => 'error',
                    'result' => 'Не передан id пользователя или массив товаров',
                ],400,[],['Content-Type' => 'application/json']
            );
        }
        response()->json(
            [
                'status' => 'success',
            ],200,[],['Content-Type' => 'application/json']
        );
    }

    public function clearBasket($userId = null)
    {
        if (!$userId)
            return;
        \CSaleBasket::DeleteAll($userId);
    }

    public function addTicketType()
    {
        $request = request()->get();
        try{
            $eventEntity = new ORM\Query\Query('Custom\Core\Events\EventsTable');
            $eventClass  = $eventEntity->getEntity()->getDataClass();
            $query       = $eventEntity
                ->setSelect(['ID','UF_UUID', 'UF_NAME','UF_CREATED_BY'])
                ->setFilter(['UF_UUID' => $request['id']])
                ->countTotal(true)
                ->exec();
            if ($query->getCount() < 1) throw new \Exception('Event not found');

            $arEvent = $query->fetch();
            $eventID = $arEvent['ID'];
            $createdBy = $arEvent['UF_CREATED_BY'];
            $productName = $arEvent['UF_NAME'];

            $productEntity = \Bitrix\Iblock\IblockTable::compileEntity('tickets');
            $productClass  = $productEntity->getDataClass();
            $query         = new ORM\Query\Query($productEntity);
            $resProduct    = $query
                ->setSelect(['ID'])
                ->setFilter(['EVENT_ID.VALUE' => $eventID])
                ->setLimit(1)
                ->countTotal(true)
                ->exec();
            if ($resProduct->getCount() < 1) throw new \Exception('Product not found');

            $mainProductID = (int)($resProduct->fetch())['ID'];
            $offerUUID = \Custom\Core\UUID::uuid8();
			
            $offer = [
                'NAME' => $productName.' ['.$request['name'].']',
                'XML_ID' => $offerUUID,
                'CML2_LINK' => $mainProductID,
                'MAX_QUANTITY'   => $request['max_quantity'],
                'RESERVE_TIME'   => $request['reserve_time'],
                'TYPE'           => $request['name'],
                'TOTAL_QUANTITY' => $request['total_quantity'],
                'PRICE'          => $request['price'],
	            'IS_CREATED_SEAT_MAP' => 1,
            ];

            $resOffer = Products::getInstance()->createProduct($offer, true);

            response()->json(
                [
                    'status' => 'success',
                    'id' => $offerUUID,
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