<?
namespace Custom\Core\Skd;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\DatetimeField;

Loc::loadMessages(__FILE__);

class LoggerSkdTable extends DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_logger_skd';
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
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_API_METHOD',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_API_METHOD_FIELD'),
                ]
            ),
            new DatetimeField(
                'UF_DATE_INSERT',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_DATE_INSERT_FIELD'),
                ]
            ),
            new TextField(
                'UF_SEARCH_BY',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_SEARCH_BY_FIELD'),
                ]
            ),
            new TextField(
                'UF_SEARCH_Q',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_SEARCH_Q_FIELD'),
                ]
            ),
            new TextField(
                'UF_CODE',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_CODE_FIELD'),
                ]
            ),
            new TextField(
                'UF_ALLOWED',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_ALLOWED_FIELD'),
                ]
            ),
            new TextField(
                'UF_EXIT_MODE',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_EXIT_MODE_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_CONTROLLER_ID',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_CONTROLLER_ID_FIELD'),
                ]
            ),
            new TextField(
                'UF_ORDER_NUMBER',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_ORDER_NUMBER_FIELD'),
                ]
            ),
            new TextField(
                'UF_REQ_STATUS',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_REQ_STATUS_FIELD'),
                ]
            ),
            new TextField(
                'UF_REQ_MESSAGE',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_REQ_MESSAGE_FIELD'),
                ]
            ),
            new TextField(
                'UF_REQ_MESSAGE_CODE',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_REQ_MESSAGE_CODE_FIELD'),
                ]
            ),
            new TextField(
                'UF_TICKET_NAME',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_TICKET_NAME_FIELD'),
                ]
            ),
            new TextField(
                'UF_TICKET_TYPE',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_TICKET_TYPE_FIELD'),
                ]
            ),
            new TextField(
                'UF_TICKET_PLACE',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_TICKET_PLACE_FIELD'),
                ]
            ),
            new TextField(
                'UF_TICKET_ROW',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_TICKET_ROW_FIELD'),
                ]
            ),
            new TextField(
                'UF_TICKET_DATE',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_TICKET_DATE_FIELD'),
                ]
            ),
            new TextField(
                'UF_TICKET_STATUS',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_TICKET_STATUS_FIELD'),
                ]
            ),
            new TextField(
                'UF_TICKET_STATUS_CODE',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_TICKET_STATUS_CODE_FIELD'),
                ]
            ),
            new TextField(
                'UF_TICKET_STATUS_MESSAGE',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_TICKET_STATUS_MESSAGE_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_EVENT_ID',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_EVENT_ID_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_EVENT_IS_ALLOW_EXIT',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_EVENT_IS_ALLOW_EXIT_FIELD'),
                ]
            ),
            new IntegerField(
                'UF_EVENT_IS_CONFIRMATION_REQURED',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_EVENT_IS_CONFIRMATION_REQURED_FIELD'),
                ]
            ),
            new TextField(
                'UF_EVENT_SOLD_QUANTITY',
                [
                    'title' => Loc::getMessage('LOGGER_SKD_ENTITY_UF_EVENT_SOLD_QUANTITY_FIELD'),
                ]
            ),
        ];
    }
}