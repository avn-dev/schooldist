<?php

/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Ext_Thebing_Marketing_Gui2_Admin extends Ext_Thebing_Gui2_Data {
   
    private $_sTable = 'kolumbus_material_orders_items';

    public function  switchAjaxRequest($_VARS) {

		if(
			$_VARS['action'] == 'active' ||
			$_VARS['action'] == 'inactive'
		) {
			$this->changeOrderableStatus($_VARS['id'][0], $_VARS['action']);
			$_VARS['task'] = 'loadTable';
			$_VARS['loadBars'] = 0;
		}

		parent::switchAjaxRequest($_VARS);
	}

	/**
	 *  enable or disable selected orderable status 
	 */
	public function changeOrderableStatus($iId, $sAction){

		//$sAction == "active" ? $iVal = 1 : $iVal  = 0;
		
		if($sAction == 'active') 
			$iVal = 1;
		else 
			$iVal = 0;

		$sSql = "UPDATE
					#table
				SET
					`orderable` = :value
				WHERE
					`id` = :id AND
					`active` = 1";

		$aSql = array(
				'table'	=> $this->_sTable,
				'id'	=> (int)$iId,
				'value'	=> (int)$iVal,
			);

		DB::executePreparedQuery($sSql, $aSql);

	}


	   
   }


?>
