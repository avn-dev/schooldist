<?php

class Ext_TC_Rounding_Gui2_Data extends Ext_TC_Gui2_Data {

	/**
	 * ORDER BY für den Query setzen
	 * @return array 
	 */
	public static function getOrderby(){
		return array('name' => 'ASC');
	}
	
}