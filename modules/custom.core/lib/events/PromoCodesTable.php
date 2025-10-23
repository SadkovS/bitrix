<?php

namespace Custom\Core\Events;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\Entity\ReferenceField;
use Custom\Core\UUID;

Loc::loadMessages(__FILE__);

class PromoCodesTable extends DataManager{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_hldb_promo_codes';
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
					'title' => Loc::getMessage('PROMO_CODES_ENTITY_ID_FIELD'),
				]
			),
			new TextField(
				'UF_XML_ID',
				[
					'title' => Loc::getMessage('PROMO_CODES_ENTITY_UF_XML_ID_FIELD'),
					'required' => true,
					'unique' => true,
					'default_value' => UUID::uuid4()
				]
			),
			new TextField(
				'UF_CODE',
				[
					'title' => Loc::getMessage('PROMO_CODES_ENTITY_UF_CODE_FIELD'),
					'required' => true,
				]
			),
			new IntegerField(
				'UF_RULE_ID',
				[
					'title' => Loc::getMessage('PROMO_CODES_ENTITY_UF_RULE_ID_FIELD'),
					'required' => true,
				]
			),
			new IntegerField(
				'UF_IS_USE',
				[
					'title' => Loc::getMessage('PROMO_CODES_ENTITY_UF_IS_USE_FIELD'),
					'default_value' => 0
				]
			),
		];
	}
}