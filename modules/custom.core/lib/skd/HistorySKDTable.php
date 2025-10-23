<?php

namespace Custom\Core\Skd;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;

Loc::loadMessages(__FILE__);

class HistorySKDTable extends DataManager{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_hldb_history_skd';
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
					'title' => Loc::getMessage('HISTORY_SKD_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'UF_ACCESS_SKD_ID',
				[
					'title' => Loc::getMessage('HISTORY_SKD_ENTITY_UF_ACCESS_SKD_ID_FIELD'),
				]
			),
			new DatetimeField(
				'UF_CREATED_DATE',
				[
					'title' => Loc::getMessage('HISTORY_SKD_ENTITY_UF_CREATED_DATE_FIELD'),
				]
			),
			(new DatetimeField(
				'UF_DATE_TIME',
				[
					'title' => Loc::getMessage('ACCESS_SKD_ENTITY_UF_DATE_TIME_FIELD'),
				]
			)),
			new IntegerField(
				'UF_BARCODE_ID',
				[
					'title' => Loc::getMessage('HISTORY_SKD_ENTITY_UF_BARCODE_ID_FIELD'),
				]
			),
			new IntegerField(
				'UF_STATUS',
				[
					'title' => Loc::getMessage('HISTORY_SKD_ENTITY_UF_STATUS_FIELD'),
				]
			),
			new ReferenceField(
				'HISTORY_REF',
				'\Custom\Core\Skd\AccessSKDTable',
				['this.UF_ACCESS_SKD_ID' => 'ref.ID'],
				['join_type' => 'LEFT']
			)
		];
	}
}