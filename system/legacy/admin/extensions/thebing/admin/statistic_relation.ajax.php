<?php

require_once(Util::getDocumentRoot()."system/legacy/admin/includes/main.inc.php");

Ext_Thebing_Access::accesschecker('thebing_admin_statisticfields');

$sDescription	= Ext_Thebing_Management_Statistic_Relation::$sModul;

if(
	isset($_VARS['x_id']) &&
	isset($_VARS['y_id']) &&
	isset($_VARS['handle'])
) {

	$oStatisticRelation = new Ext_Thebing_Management_Statistic_Relation();
	$iXId				= (int)$_VARS['x_id'];
	$iYId				= (int)$_VARS['y_id'];
	$iType				= (int)$_VARS['type'];
	$sHandle			= $_VARS['handle'];
	$aHandles			= array('add', 'remove');

	if(
		in_array($sHandle, $aHandles) &&
		$iXId > 0 &&
		$iYId > 0
	) {

		$oStatisticRelation->x_id = (int)$_VARS['x_id'];
		$oStatisticRelation->y_id = (int)$_VARS['y_id'];
		$oStatisticRelation->type = (int)$_VARS['type'];

		if('add' == $sHandle) {
			
			try{
				$oStatisticRelation->save();
			}catch(Exception $e){
				//
			}

		} else{

			$oStatisticRelation->loadByData();
			$oStatisticRelation->delete();

		}
	}
}

?>