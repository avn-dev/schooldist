<?php

class Ext_Thebing_Data_Currency{
	
	public static function getCurrencyData($id) {
		
		$sSql = "SELECT 
					* 
				FROM 
					#table 
				WHERE
					`id` = :id
				LIMIT 1
				";
		$aSql = array('table'=>'kolumbus_currency','id'=>(int)$id);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		
		return $aResult[0];
	}
	
	public static function getCurrencyList($bForSelects = true) {

		$sSql = "SELECT 
					* 
				FROM 
					#table ";
		$aSql = array('table'=>'kolumbus_currency');
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		if($bForSelects == true) {
			$aSelect = array();
			foreach($aResult as $aValue) {
				$aSelect[$aValue['id']] = $aValue['iso4217'] . ' (' . $aValue['sign'] . ')';
			}
			return $aSelect;
		} else {
			return $aResult;
		}

	}
	
}