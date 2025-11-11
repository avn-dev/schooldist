<?php

namespace TsAccommodation\Entity\Provider;

class SchoolSetting extends \WDBasic {
	
	protected $_sTable = 'ts_accommodation_categories_settings';
	protected $_sTableAlias = 'ts_acs';
	
	protected $_aJoinTables = [
		'schools' => [
			'table' => 'ts_accommodation_categories_settings_schools',
			'foreign_key_field' => 'school_id',
			'primary_key_field' => 'setting_id'
		]
	];
	
	/**
	 * {@inheritdoc}
	 */
	public function __get($sField) {

		\Ext_Gui2_Index_Registry::set($this);

		switch($sField) {
			case 'weeks':
				return (array)json_decode($this->_aData[$sField]);
		}

		return parent::__get($sField);

	}

	/**
	 * {@inheritdoc}
	 */
	public function __set($sField, $mValue) {

		switch($sField) {

			case 'weeks':
				$this->_aData[$sField] = (string)json_encode($mValue);
				break;

			default:
				parent::__set($sField, $mValue);

		}

	}
	
}
