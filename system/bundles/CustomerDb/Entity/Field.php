<?php

namespace CustomerDb\Entity;

class Field extends \WDBasic {
	
	protected $_sTable = 'customer_db_definition';
	protected $_sTableAlias = 'c_d';
	
	public function getOptions() {
		
		$aOptions = \DB::getQueryPairs("SELECT value, display FROM customer_db_values WHERE definition_id = '".$this->id."' AND active = 1 ORDER BY value");
		
		return $aOptions;
	}
	
	public function getFieldName() {
		
		if($this->field_nr > 0) {
			return 'ext_'.$this->field_nr;
		} else {
			return $this->name;
		}
		
	}
	
}
