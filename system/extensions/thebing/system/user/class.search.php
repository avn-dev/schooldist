<?php

class Ext_Thebing_System_User_Search {
		
	public static function getBirthdays($iFrom = 0,$iTo = 0){

		if($iFrom == 0){
			$iFrom = time();
			//$iFrom = strtotime('- 1 Week',$iFrom);
			$iFrom = mktime(0,0,0,date('m',$iFrom),date('d',$iFrom),date('Y',$iFrom));
		}
		
		if($iTo == 0){
			$iTo = time();
			$iTo = strtotime('+ 2 Week',$iFrom);
			$iTo = mktime(23,59,59,date('m',$iTo),date('d',$iTo),date('Y',$iTo));
		}
			
		$sSql = "	
			SELECT
				`su`.`id`,
				`su`.`birthday`,
				`su`.`firstname`,
				`su`.`lastname`,

				getAge(`su`.`birthday`) `age`

			FROM
				`system_user` `su`
			WHERE
				(
					(
						DAYOFYEAR(`su`.`birthday`)+IF(DAYOFYEAR(:from)>DAYOFYEAR(`su`.`birthday`),1000,0)
					) BETWEEN 
						DAYOFYEAR(:from) AND 
						(
							DAYOFYEAR(:to)+IF(DAYOFYEAR(:from)>DAYOFYEAR(:to),1000,0)
						)
				) AND
				`su`.`birthday` > 0 AND
				`su`.`active` = 1 AND
				`su`.`status` = 1
		";

		$aSql = [];
		$aSql['from'] = date('Y-m-d', $iFrom);
		$aSql['to'] = date('Y-m-d', $iTo);
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		return $aResult;
	}
		
}