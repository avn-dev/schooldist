<?php

/**
 * Beschreibung der Klasse
 */
class Ext_TS_Inquiry_Contact_Traveller_Detail extends Ext_TS_Inquiry_Contact_Detail{

	/**
	 * Alias der Tabelle (Optional)
	 * @var <string> 
	 */
	protected $_sTableAlias = 'tc_c_d';
	
	/**
	 * Funktion liefert den Key im Error Array der Validate
	 * @return type 
	 */
	protected function _getErrorFieldKey(){
		
		$sFieldPrefix = $this->_sTableAlias . '.';

		$sFieldPrefix .= $this->type;
	
		return $sFieldPrefix;
	}

}