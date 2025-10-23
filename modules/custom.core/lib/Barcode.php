<?php
namespace Custom\Core;
require_once($_SERVER["DOCUMENT_ROOT"] . '/local/php_interface/include/handlers/BarcodeHandlerTrait.php');

use Local\PhpInterface\Handlers\BarcodeHandlerTrait;
class Barcode {
    use BarcodeHandlerTrait;

    public function __construct(){}

    public function clearBasketThisTicket($ticketId)
    {
        \Bitrix\Main\Loader::includeModule("sale");

        $obBasket = \Bitrix\Sale\Internals\BasketTable::getList(
            [
                'select'  => [
                    'ID',
                ],
                'filter' => [
                    'ORDER_ID' => 'NULL',
                    'PRODUCT_ID' => $ticketId
                ],
            ]
        );

        while($bItem = $obBasket->Fetch()){
            \Bitrix\Sale\Internals\BasketTable::delete($bItem['ID']);
        }
    }
}
