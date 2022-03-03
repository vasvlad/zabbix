<?php declare(strict_types = 1);
/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

class CItemTypeIpmi extends CItemType {

	/**
	 * @inheritDoc
	 */
	const FIELD_NAMES = ['interfaceid', 'ipmi_sensor', 'delay'];

	/**
	 * @inheritDoc
	 */
	public static function getCreateValidationRules(array &$item): array {
		return [
			'interfaceid' =>	self::getCreateFieldRule('interfaceid'),
			'ipmi_sensor' =>	['type' => API_MULTIPLE, 'rules' => [
									['if' => ['field' => 'key_', 'in' => 'ipmi.get'], 'type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'ipmi_sensor')],
									['else' => true, 'type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'ipmi_sensor')]
			]],
			'delay' =>			['type' => API_ITEM_DELAY, 'flags' => API_REQUIRED, 'length' => DB::getFieldLength('items', 'delay')]
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function getUpdateValidationRules(array &$item, array $db_item): array {
		return [
			'interfaceid' =>	self::getUpdateFieldRule('interfaceid', $db_item),
			'ipmi_sensor' =>	['type' => API_MULTIPLE, 'rules' => [
									['if' => static function (array $data) use ($db_item): bool {
										return $db_item['type'] != ITEM_TYPE_IPMI && $data['key_'] !== 'ipmi.get';
									}, 'type' => API_STRING_UTF8, 'flags' => API_REQUIRED | API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'ipmi_sensor')],
									['if' => ['field' => 'key_', 'in' => 'ipmi.get'], 'type' => API_STRING_UTF8, 'length' => DB::getFieldLength('items', 'ipmi_sensor')],
									['else' => true, 'type' => API_STRING_UTF8, 'flags' => API_NOT_EMPTY, 'length' => DB::getFieldLength('items', 'ipmi_sensor')]
			]],
			'delay' =>			self::getUpdateFieldRule('delay', $db_item)
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function getUpdateValidationRulesInherited(array &$item, array $db_item): array {
		return [
			'interfaceid' =>	self::getUpdateFieldRuleInherited('interfaceid', $db_item),
			'ipmi_sensor' =>	['type' => API_UNEXPECTED, 'error_type' => API_ERR_INHERITED],
			'delay' =>			['type' => API_ITEM_DELAY, 'length' => DB::getFieldLength('items', 'delay')]
		];
	}

	/**
	 * @inheritDoc
	 */
	public static function getUpdateValidationRulesDiscovered(array &$item, array $db_item): array {
		return [
			'interfaceid' =>	['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'ipmi_sensor' =>	['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED],
			'delay' =>			['type' => API_UNEXPECTED, 'error_type' => API_ERR_DISCOVERED]
		];
	}
}
