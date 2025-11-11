<?php

/**
 * Class Ext_Thebing_Salesperson_Setting
 * @property int $id
 * @property int $user_id
 * @method static Ext_Thebing_Salesperson_SettingRepository getRepository()
 */
class Ext_Thebing_Salesperson_Setting extends Ext_Thebing_Basic {

	/**
	 * Tabellenname
	 *
	 * @var string
	 */
	protected $_sTable = 'ts_system_user_sales_persons_settings';

	/**
	 * Tabellen Alias
	 *
	 * @var string
	 */
	protected $_sTableAlias = 'ts_susps';

	/**
	 * @var array
	 */
	protected $_aJoinTables = [
		'schools' => [
			'table' => 'ts_system_user_sales_persons_schools',
			'primary_key_field' => 'setting_id',
			'foreign_key_field' => 'school_id',
			'autoload' => false,
			'on_delete' => 'delete',
		],
		'nationalities' => [
			'table' => 'ts_system_user_sales_persons_nationalities',
			'primary_key_field' => 'setting_id',
			'foreign_key_field' => 'country_iso',
			'autoload' => false,
			'on_delete' => 'delete',
		],
		'agencies' => [
			'table' => 'ts_system_user_sales_persons_agencies',
			'primary_key_field' => 'setting_id',
			'foreign_key_field' => 'agency_id',
			'autoload' => false,
			'on_delete' => 'delete',
		]
	];

	protected $_sEditorIdColumn = null;

	/**
	 * Der Benutzer dem diese Einstellung gehört.
	 *
	 * @return int
	 */
	public function getUserId() {
		return (int)$this->user_id;
	}
	
	/**
	 * @return Ext_Thebing_User
	 */
	public function getUser() {
		return Ext_Thebing_User::getInstance($this->user_id);
	}
	
}