<?php

namespace Form\Entity;

class Option extends \WDBasic {
	
	protected $_sTable = 'form_options';
	protected $_sTableAlias = 'f_o';

	protected $_aJoinedObjects = [
		'conditions' => [
			'class' => '\Form\Entity\Option\Condition',
			'key' => 'option_id',
			'type' => 'child',
			'orderby' => 'position',
			'on_delete' => 'cascade'
		]
	];

	protected $_aJoinTables = [
		'display_conditions' => [
			'table' => 'form_options_conditions',
	 		'primary_key_field' => 'option_id',
	 		'sort_column' => 'position',
			'autoload' => false,
			'readonly' => true
		],
		'display_conditions_fields' => [
			'table' => 'form_options_conditions',
	 		'primary_key_field' => 'field',
			'autoload' => false,
			'on_delete' => 'delete'
		]
	];

	public function __set($sName, $mValue) {

		if(strpos($sName, 'additional_') === 0) {
			
			$sKey = str_replace('additional_', '', $sName);
			
			$this->setAdditional($sKey, $mValue);
			
			return;
		}
		
		parent::__set($sName, $mValue);
	}

	public function __get($sName) {
		
		if(strpos($sName, 'additional_') === 0) {
			
			$sKey = str_replace('additional_', '', $sName);

			$mValue = $this->getAdditional($sKey);

			return $mValue;
		}
		
		return parent::__get($sName);
	}

	
	public function getAdditional(string $sKey) {

		$aAdditional = \Util::decodeSerializeOrJson($this->additional);

		if(isset($aAdditional[$sKey])) {
			return $aAdditional[$sKey];
		}
	}
	
	/**
	 * @param string $sKey
	 * @param mixed $mValue
	 */
	public function setAdditional(string $sKey, $mValue) {

		$aAdditional = \Util::decodeSerializeOrJson($this->additional);
		
		if(!is_array($aAdditional)) {
			$aAdditional = [];
		}
		
		if(empty($mValue)) {
			unset($aAdditional[$sKey]);
		} else {
			$aAdditional[$sKey] = $mValue;
		}

		$this->additional = \Util::encodeJson($aAdditional);

		$this->save();

	}
	
	public function save() {

		parent::save();

		$oInit = Init::getInstance($this->form_id);
		$oInit->updateStructure();

		return $this;
	}

}