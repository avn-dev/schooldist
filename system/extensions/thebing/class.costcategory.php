<?php

/*
 * Zum verwalten der Lehrer/Unterkunftskostkategorien
 */
class Ext_Thebing_Costcategory {

	
	public static function getCostCategories($oObject, $sFrom = '', $sUntil = ''){

		$aBack = array();
		
		if($oObject instanceof Ext_Thebing_Accommodation){
			$aCostCategories = $oObject->getJoinedObjectChilds('salary');
			$sPaymentObject = 'Ext_Thebing_Accommodation_Salary';
		}elseif($oObject instanceof Ext_Thebing_Teacher){
			$aCostCategories = $oObject->getJoinedObjectChilds('salary');
			$sPaymentObject = 'Ext_Thebing_Accommodation_Salary';
		}else{
			return $aBack;
		}
		
		$bFilter = false;
		if(
			WDDate::isDate($sFrom, WDDate::DB_DATE) &&
			WDDate::isDate($sUntil, WDDate::DB_DATE)
		){
			$oDateFrom		= new WDDate($sFrom, WDDate::DB_DATE);
			$oDateUntil		= new WDDate($sUntil, WDDate::DB_DATE);
			$bFilter = true;
		}

		foreach((array)$aCostCategories as $oCostCategories){
			
			if($bFilter){

				$iCompFirst = $oDateFrom->compare(new WDDate($oCostCategories->valid_until, WDDate::DB_DATE));
				$iCompLast = $oDateUntil->compare(new WDDate($oCostCategories->valid_from, WDDate::DB_DATE));


				if(
					$oCostCategories->valid_until == '0000-00-00' &&
					$iCompLast < 0
				){
					continue;
				}elseif(
					$iCompFirst == $iCompLast &&
					$iCompFirst != 0
				){
					continue;
				}
				
				// Es kann immer nur EINE Kategorie zu einem Zeitpunkt gültig sein
				return $oCostCategories;
			}
			
			$aBack[] = $oCostCategories;
		}
		
		return $aBack;
		
		
	}
	
	
}