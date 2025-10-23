<?php

namespace Custom\Core\Skd;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\Entity\ReferenceField;

class AccessSKDUfDateTable extends DataManager {
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName()
    {
        return 'b_hldb_access_skd_uf_date';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap()
    {
        return [
	        new IntegerField( // добавлено в таблицу вручную для корректной работы
		        'GENERAL_ID',
		        [
			        'primary' => true,
			        'title' => Loc::getMessage('ACCESS_SKD_GENERAL_ID_ENTITY_ID_FIELD'),
		        ]
	        ),
            new IntegerField(
                'ID',
                [
                    'required' => true,
                    'title' => Loc::getMessage('ACCESS_SKD_UF_DATE_ENTITY_ID_FIELD'),
                ]
            ),
            new DateField(
                'VALUE',
                [
                    'title' => Loc::getMessage('ACCESS_SKD_UF_DATE_ENTITY_VALUE_FIELD'),
                    'required' => true,
                ]
            ),
	       new ReferenceField(
		        'REF_DATE',
		        '\Custom\Core\Skd\AccessSKDTable',
		        ['this.ID' => 'ref.ID'],
		        ['join_type' => 'LEFT']
	        )
        ];
    }
}