<?php
namespace Custom\Core\Events;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;

/**
 * Class ApplyTable
 * 
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> UF_BASKET_ITEM_ID text optional
 * <li> UF_RULE_ID text optional
 * <li> UF_RULE_TYPE text optional
 * <li> UF_DISCOUNT_VALUE text optional
 * </ul>
 *
 * @package Bitrix\Apply
 **/

class DiscountApplyTable extends DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'discount_apply';
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
					'title' => Loc::getMessage('APPLY_ENTITY_ID_FIELD'),
				]
			),
			new TextField(
				'UF_BASKET_ITEM_ID',
				[
					'title' => Loc::getMessage('APPLY_ENTITY_UF_BASKET_ITEM_ID_FIELD'),
				]
			),
			new TextField(
				'UF_RULE_ID',
				[
					'title' => Loc::getMessage('APPLY_ENTITY_UF_RULE_ID_FIELD'),
				]
			),
			new TextField(
				'UF_RULE_TYPE',
				[
					'title' => Loc::getMessage('APPLY_ENTITY_UF_RULE_TYPE_FIELD'),
				]
			),
			new TextField(
				'UF_DISCOUNT_VALUE',
				[
					'title' => Loc::getMessage('APPLY_ENTITY_UF_DISCOUNT_VALUE_FIELD'),
				]
			),
			new TextField(
				'UF_FUSER_ID',
				[
					'title' => Loc::getMessage('APPLY_ENTITY_UF_FUSER_ID_FIELD'),
				]
			),
			new TextField(
				'UF_PROMOCODE_ID',
				[
					'title' => Loc::getMessage('APPLY_ENTITY_UF_PROMOCODE_ID_FIELD'),
				]
			),
            new TextField(
                'UF_PROMOCODE',
                [
                    'title' => Loc::getMessage('APPLY_ENTITY_UF_PROMOCODE_FIELD'),
                ]
            ),
            new TextField(
                'UF_DISCOUNT_TYPE',
                [
                    'title' => Loc::getMessage('APPLY_ENTITY_UF_DISCOUNT_TYPE_FIELD'),
                ]
            ),
            new TextField(
                'UF_DISCOUNT',
                [
                    'title' => Loc::getMessage('APPLY_ENTITY_UF_DISCOUNT_FIELD'),
                ]
            ),
            new TextField(
                'UF_DISCOUNT',
                [
                    'title' => Loc::getMessage('APPLY_ENTITY_UF_DISCOUNT_FIELD'),
                ]
            ),
		];
	}
}