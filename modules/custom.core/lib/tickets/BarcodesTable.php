<?php
namespace Custom\Core\Tickets;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\Entity\Validator;

class BarcodesTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_barcodes';
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
                    'primary' => true,
                    'autocomplete' => true,
                    'title' => Loc::getMessage('BARCODES_ENTITY_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_XML_ID',
                [
                    'title' => Loc::getMessage('BARCODES_ENTITY_UF_XML_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_BARCODE',
                [
                    'title' => Loc::getMessage('BARCODES_ENTITY_UF_BARCODE_FIELD'),
                ]
            ),
            new TextField(
                'UF_OFFER_ID',
                [
                    'title' => Loc::getMessage('BARCODES_ENTITY_UF_OFFER_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_STATUS',
                [
                    'title' => Loc::getMessage('BARCODES_ENTITY_UF_STATUS_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_EVENT_ID',
                [
                    'title' => Loc::getMessage('BARCODES_ENTITY_UF_EVENT_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_SERIES',
                [
                    'title' => Loc::getMessage('BARCODES_ENTITY_UF_SERIES_FIELD'),
                    'validation' => function()
                    {
                        return[
                            new Validator\RegExp('/[A-Z]{3}/')
                        ];
                    },
                ]
            ),
            new TextField(
                'UF_TICKET_NUM',
                [
                    'title' => Loc::getMessage('BARCODES_ENTITY_UF_TICKET_NUM_FIELD'),
                    /*'validation' => function()
                    {
                        return[
                            new Validator\RegExp('/[0-9]{6, 10}/')
                        ];
                    },*/
                ]
            ),
            new IntegerField(
                'UF_SEATMAP_ID',
                [
                    'title' => Loc::getMessage('BARCODES_ENTITY_UF_SEATMAP_ID_FIELD'),
                ]
            ),
        ];
    }
}