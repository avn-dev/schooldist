<?php

/**
 * @property $id 	
 * @property $created 	
 * @property $changed 	
 * @property $user_id 	
 * @property $client_id 	
 * @property $active 	
 * @property $creator_id 	
 * @property $name 	
 * @property $number 	
 * @property $type 	
 * @property $usage 	
 */
class Ext_Thebing_Contract_Template extends Ext_Thebing_Basic {

	protected $_aAdditional = array();

	// Tabellenname
	protected $_sTable = 'kolumbus_contract_templates';

	// Tabellenalias
	protected $_sTableAlias = 'kcontt';

	public function  __set($sName, $sValue) {

		// Ein leeres Passwort darf nicht gespeichert werden
		if(
			$sName == 'schools'
		) {
			$this->_aAdditional[$sName] = $sValue;
		} else {
			parent::__set($sName, $sValue);
		}

	}

	public function  __get($sName) {

		if(
			$sName == 'schools'
		) {
			$sValue = (array)$this->_aAdditional[$sName];
		} elseif($sName == 'type_name') {
			$aTypes = self::getTypeArray();
			$sValue = $aTypes[$this->type];
		} else {
			$sValue = parent::__get($sName);
		}

		return $sValue;

	}

	protected function _loadData($iDataID) {

		parent::_loadData($iDataID);

		if($iDataID > 0) {

			$aKeys = array('template_id'=>(int)$this->id);
			$this->_aAdditional['schools'] = DB::getJoinData('kolumbus_contract_templates_schools', $aKeys, 'school_id');

		}

	}

	public function save($bLog = true) {
		global $user_data;

		$aAdditional = $this->_aAdditional;

		$this->client_id = (int)$user_data['client'];;

		parent::save();
	
		$this->_aAdditional = $aAdditional;

		$aKeys = array('template_id'=>(int)$this->id);
		DB::updateJoinData('kolumbus_contract_templates_schools', $aKeys, (array)$this->_aAdditional['schools'], 'school_id');

		return $this;
	}

	public static function getTypeArray() {

		$desc	= 'Thebing » Admin » Contract templates';
		$aTypes = array(1 => L10N::t('Rahmenvertrag', $desc), 2 => L10N::t('Zusatzvertrag', $desc));

		return $aTypes;
	}

	public static function getUsageArray() {

		$desc	= 'Thebing » Admin » Contract templates';
		$aUsages = array('teacher' => L10N::t('Lehrer', $desc), 'accommodation' => L10N::t('Familien', $desc));

		return $aUsages;
	}

}