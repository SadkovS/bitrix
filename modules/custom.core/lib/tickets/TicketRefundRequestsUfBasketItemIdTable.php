<?php

namespace Custom\Core\Tickets;

use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\IntegerField;

class TicketRefundRequestsUfBasketItemIdTable extends DataManager {
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_ticket_refund_requests_uf_basket_item_id';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            new IntegerField(
                'GENERAL_ID',
                [
                    'primary'      => true,
                    'autocomplete' => true,
                    'title'        => Loc::getMessage('TICKET_REFUND_REQUESTS_UF_BASKET_ITEM_ID_ENTITY_GENERAL_ID_FIELD'),
                    'size'         => 8,
                ]
            ),
            new IntegerField(
                'ID',
                [
                    'required' => true,
                    'title'    => Loc::getMessage('TICKET_REFUND_REQUESTS_UF_BASKET_ITEM_ID_ENTITY_ID_FIELD'),
                ]
            ),
            new FloatField(
                'VALUE',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_UF_BASKET_ITEM_ID_ENTITY_VALUE_FIELD'),
                ]
            ),
            new ReferenceField(
                'REFUND_REQUEST_REF',
                '\Custom\Core\Tickets\TicketRefundRequestsTable',
                ['this.ID' => 'ref.ID'],
                ['join_type' => 'LEFT']
            )
        ];
    }
}