<?php

class Ext_TC_Exchangerate_Table_Gui2 extends Ext_Gui2 {
	
	/**
	 * ORDER BY für den Query setzen
	 * @return array 
	 */
	public static function getOrderby(){
		return array('tc_et.name' => 'ASC');
	}
	
}