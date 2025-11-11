<?php

namespace Tc\Entity;

/**
 * @method static SystemTypeMappingRepository getRepository()
 */
class SystemTypeMapping extends \Ext_TC_Basic
{
	protected $_sTable = 'tc_system_type_mapping';

	protected $_sTableAlias = 'tc_stm';

	protected $_aJoinTables = [
		'system_types' => [
			'table' => 'tc_system_type_mapping_to_system_types',
			'foreign_key_field' => 'type',
			'primary_key_field' => 'mapping_id',
			'autoload' => false
		],
		'users' => [
			'table' => 'tc_employees_to_categories',
			'foreign_key_field' => 'employee_id',
			'primary_key_field' => 'category_id',
			'autoload' => false
		],
	];

	public static function getSelectOptions(string $entityType): array
	{
		return self::query()
			->where('type', $entityType)
			->pluck('name', 'id')
			->toArray();
	}

}