<?php

class Ext_TC_Exchangerate_Table_Overview_Gui2 extends Ext_Gui2 {
	
	/**
	 * ORDER BY für den Query setzen
	 * @return array 
	 */
	public static function getOrderby(){
		return array('tc_etr.currency_iso_from' => 'ASC');
	}

	/**
	 * dem Datenselect die Navigation hinzufügen
	 * @return array 
	 */
	public static function getSelectFilterNavigation() {
		$sDate = date('Y-m-d');
		return array('default_value' => $sDate);
	}

	/**
	 * Standartwert des Datumsselects
	 * @return string 
	 */
	public static function getSelectValue() {
		$sDate = date('Y-m-d');
		return $sDate;
	}
	
}
