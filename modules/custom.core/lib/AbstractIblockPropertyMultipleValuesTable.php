<?php

namespace Models;

use Bitrix\Main\NotImplementedException;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Entity\IntegerField;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\ORM\Data\DeleteResult;

/**
 * Class AbstractIblockPropertyMultipleValuesTable
 *
 * @package Models
 */
abstract class AbstractIblockPropertyMultipleValuesTable extends DataManager
{
    const IBLOCK_ID = null;

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return 'b_iblock_element_prop_m'.static::IBLOCK_ID;
    }

    /**
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMap(): array
    {
        return [
            'ID'                 => new IntegerField('ID', ['primary' => true]),
            'IBLOCK_ELEMENT_ID'  => new IntegerField('IBLOCK_ELEMENT_ID'),
            'IBLOCK_PROPERTY_ID' => new IntegerField('IBLOCK_PROPERTY_ID'),
            'VALUE'              => new StringField('VALUE'),
            'VALUE_ENUM'         => new StringField('VALUE_ENUM'),
        ];
    }

    /**
     * @param  array  $data
     *
     * @return AddResult
     * @throws NotImplementedException
     */
    public static function add(array $data): AddResult
    {
        throw new NotImplementedException();
    }

    /**
     * @param $primary
     *
     * @return DeleteResult
     * @throws NotImplementedException
     */
    public static function delete($primary): DeleteResult
    {
        throw new NotImplementedException();
    }
}
