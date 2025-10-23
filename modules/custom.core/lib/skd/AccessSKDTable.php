<?php

namespace Custom\Core\Skd;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\ArrayField;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;

Loc::loadMessages(__FILE__);

class AccessSKDTable extends DataManager{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_hldb_access_skd';
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
					'title' => Loc::getMessage('ACCESS_SKD_ENTITY_ID_FIELD'),
				]
			),
			new IntegerField(
				'UF_USER_ID',
				[
					'title' => Loc::getMessage('ACCESS_SKD_ENTITY_UF_USER_ID_FIELD'),
					'unique'   => true,
				]
			),
			new IntegerField(
				'UF_EVENT_ID',
				[
					'title' => Loc::getMessage('ACCESS_SKD_ENTITY_UF_EVENT_ID_FIELD'),
				]
			),
			(new ArrayField(
				'UF_DATE',
				[
					'title' => Loc::getMessage('ACCESS_SKD_ENTITY_UF_DATE_FIELD'),
				]
			))->configureSerializationPhp(),
			(new ArrayField(
				'UF_TICKETS_TYPE',
				[
					'title' => Loc::getMessage('ACCESS_SKD_ENTITY_UF_TICKETS_TYPE_FIELD'),
				]
			))->configureSerializationPhp(),
			new IntegerField(
				'UF_IS_ALLOW_EXIT',
				[
					'title' => Loc::getMessage('ACCESS_SKD_ENTITY_UF_IS_ALLOW_EXIT_FIELD'),
					'default_value' => 0
				]
			),
			new IntegerField(
				'UF_IS_CONFIRMATION_REQUIRED',
				[
					'title' => Loc::getMessage('ACCESS_SKD_ENTITY_UF_IS_CONFIRMATION_REQUIRED_FIELD'),
					'default_value' => 0
				]
			),
			new ReferenceField(
				'REF_EVENT',
				'\Custom\Core\Events\EventsTable',
				['this.UF_EVENT_ID' => 'ref.ID'],
				['join_type' => 'LEFT']
			),
			new ReferenceField(
				'REF_USER',
				'\Bitrix\Main\UserTable',
				['this.UF_USER_ID' => 'ref.ID'],
				['join_type' => 'LEFT']
			),
			new \Bitrix\Main\Entity\ExpressionField(
				'USER_FIO', 'CONCAT(%s, " ", %s, " ", %s)', ['REF_USER.LAST_NAME', 'REF_USER.NAME', 'REF_USER.SECOND_NAME']
			),
			(new OneToMany(
				'DATES',
				'Custom\Core\Skd\AccessSKDUfDateTable',
				'REF_DATE'
			))->configureJoinType('LEFT'),
			
			/*new ReferenceField(
				'REF_DATE',
				'\Custom\Core\Skd\AccessSKDUfDateTable',
				['this.ID' => 'ref.ID'],
				['join_type' => 'LEFT']
			),*/
			(new OneToMany(
				'TICKETS_TYPE',
				'\Custom\Core\Skd\AccessSKDUfTicketsTypeTable',
				'REF_TICKETS_TYPE'
			))->configureJoinType('LEFT'),
			
		/*	new ReferenceField(
				'REF_TICKETS_TYPE',
				'\Custom\Core\Skd\AccessSKDUfTicketsTypeTable',
				['this.ID' => 'ref.ID'],
				['join_type' => 'LEFT']
			),*/
			(new OneToMany(
				'HISTORY_ITEMS',
				'Custom\Core\Skd\HistorySKDTable',
				'HISTORY_REF'
			))->configureJoinType('LEFT'),
		];
	}
}