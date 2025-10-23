<?php

namespace Custom\Core\Traits;

trait PropertyEnumTrait
{
	protected function getPropertiesEnum(string $hlName, string $fieldName, string $resKey = '', string $xmlID = ''): array|int
	{
		$filter = [
			"HL.NAME"    => $hlName,
			"FIELD_NAME" => $fieldName,
		];
		
		if ( ! empty($xmlID)) {
			$filter["ENUM.XML_ID"] = $xmlID;
		}
		
		$query = \Bitrix\Main\UserFieldTable::getList(
			[
				"filter"  => $filter,
				"select"  => [
					"ENUM_ID"     => "ENUM.ID",
					"ENUM_XML_ID" => "ENUM.XML_ID",
					"ENUM_NAME"   => "ENUM.VALUE",
				],
				"runtime" => [
					new \Bitrix\Main\Entity\ExpressionField(
						'HL_ID', 'REPLACE(%s, "HLBLOCK_", "")', ['ENTITY_ID']
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'HL',
						'\Bitrix\Highloadblock\HighloadBlockTable',
						['this.HL_ID' => 'ref.ID'],
						['join_type' => 'LEFT'],
					),
					new \Bitrix\Main\Entity\ReferenceField(
						'ENUM', '\Custom\Core\FieldEnumTable', ['this.ID' => 'ref.USER_FIELD_ID'], ['join_type' => 'LEFT'],
					),
				],
				'order'   => ['ENUM_ID' => 'ASC'],
				'cache'   => ['ttl' => 3600],
			]
		);
		$res   = ! empty($xmlID) ? 0 : [];
		while ($item = $query->fetch()) {
			if ( ! empty($xmlID)) {
				$res = (int)$item['ENUM_ID'];
			} else {
				$res[$item[$resKey ?: 'ENUM_ID']] = [
					"ENUM_ID"     => (int)$item["ENUM_ID"],
					"ENUM_XML_ID" => $item["ENUM_XML_ID"],
					"ENUM_NAME"   => $item["ENUM_NAME"],
				];
			}
		}
		
		return $res;
	}
}