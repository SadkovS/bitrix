<?php

namespace Custom\Core\Tickets;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\FloatField;

class TicketRefundRequestsTable extends DataManager {
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_ticket_refund_requests';
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
                'ID',
                [
                    'primary'      => true,
                    'autocomplete' => true,
                    'title'        => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_XML_ID',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_XML_ID_FIELD'),
                ]
            ),
            new DatetimeField(
                'UF_DATE_TIME',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_DATE_TIME_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_REVIEW_STATUS',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_REVIEW_STATUS_FIELD'),
                ]
            ),
            new TextField(
                'UF_FULL_NAME',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_FULL_NAME_FIELD'),
                ]
            ),
            new TextField(
                'UF_EMAIL',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_EMAIL_FIELD'),
                ]
            ),
            new TextField(
                'UF_PHONE',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_PHONE_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_REASON_FOR_RETURN',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_REASON_FOR_RETURN_FIELD'),
                ]
            ),
            (new ArrayField(
                'UF_BASKET_ITEM_ID',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_BASKET_ITEM_ID_FIELD'),
                ]
            ))->configureSerializationPhp(),
            new TextField(
                'UF_COMMENT',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_COMMENT_FIELD'),
                ]
            ),
            (new ArrayField(
                'UF_DOCUMENTS',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_DOCUMENTS_FIELD'),
                ]
            ))->configureSerializationPhp(),
            new IntegerField(
                'UF_COMPANY_ID',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_COMPANY_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_ORDER_ID',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_ORDER_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_REASON_FOR_REFUSAL',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_REASON_FOR_REFUSAL_FIELD'),
                ]
            ),
            new TextField(
                'UF_COMMENT_ABOUT_REJECTION',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_COMMENT_ABOUT_REJECTION_FIELD'),
                ]
            ),
            new FloatField(
                'UF_ACTUAL_REFUND_SUM',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_ACTUAL_REFUND_SUM_FIELD'),
                ]
            ),
            new DatetimeField(
                'UF_DATE_TIME_REFUND',
                [
                    'title' => Loc::getMessage('TICKET_REFUND_REQUESTS_ENTITY_UF_DATE_TIME_REFUND_FIELD'),
                ]
            ),
            (new OneToMany(
                'BASKET_ITEM_ID',
                'Custom\Core\Tickets\TicketRefundRequestsUfBasketItemIdTable',
                'REFUND_REQUEST_REF'
            ))->configureJoinType('LEFT'),
            (new OneToMany(
                'DOCUMENT_ID',
                'Custom\Core\Tickets\TicketRefundRequestsUfDocumentsTable',
                'REFUND_REQUEST_REF'
            ))->configureJoinType('LEFT'),
        ];
    }
}