<?php

class Ext_TC_Gui2_Filterset_Gui2 extends Ext_TC_Gui2 {

	/**
	 * Definiert die Standardsortierung der Filterset-Liste
	 * 
	 * @return array
	 */
    public static function getOrderby(){
		return array('tc_gf.name' => 'ASC');
	}
	
}