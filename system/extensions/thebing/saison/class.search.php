<?php

class Ext_Thebing_Saison_Search {

	// Caching
	public static $aCachBySchoolAndTimestamp = array();

	public static function bySchoolAndTimestamp($idSchool, $mDate = null, $iCreated = 0, $sDicountFor = 'course',$bDiscountCheck = true,$bPriceSaisons = true,$bTeacherSaisons = false,$bTransferSaisons = false,$bAccommodationSaisons = false,$bFixcostSaisons = false, $bInsuranceSaisons = false, $bActivitySaisons = false){

		$aArguments = func_get_args();
		$sKey = 'KEY_'.implode('-', $aArguments);
		$mReturn = false;

		if(isset(self::$aCachBySchoolAndTimestamp[$sKey])){
			return self::$aCachBySchoolAndTimestamp[$sKey];
		}

		$sDate = '';
		
		if(WDDate::isDate($mDate, WDDate::DB_DATE)){
			$sDate = $mDate;
		}elseif(is_numeric($mDate)){
			$oDate = new WDDate($mDate);
			$sDate = $oDate->get(WDDate::DB_DATE);
		}
		
		$sFor = strtolower($sDicountFor);

		if(
			$bDiscountCheck &&
			$iCreated == 0
		) {
			// Bei $iCreated = 0 wäre der Rabatt immer gültig
			throw new InvalidArgumentException('Enabled season discount check but no discount date given');
		}

//		if($sFor != 'course'){
//			$sFor = 'accommodation';
//		}

		// Da man nicht weiß, was hier als Parameter reinkommt: Weiterhin Typ accommodation bei unbekannten Typ (wie vorher)
		if(!in_array($sDicountFor, array('course', 'accommodation', 'transfer', 'insurance'))) {
			$sFor = 'accommodation';
		}
		
		if ($idSchool > 0) {

			$aSqlAddon = array();
			if($bPriceSaisons){
				$aSqlAddon[] = " saison_for_price = 1 ";
			}
			if($bInsuranceSaisons){
				$aSqlAddon[] = " saison_for_insurance = 1 ";
			}
			if($bTeacherSaisons){
				$aSqlAddon[] = " saison_for_teachercost = 1 ";
			}
			if($bTransferSaisons){
				$aSqlAddon[] = " saison_for_transfercost = 1 ";
			}
			if($bAccommodationSaisons){
				$aSqlAddon[] = " saison_for_accommodationcost = 1 ";
			}
			if($bFixcostSaisons){
				$aSqlAddon[] = " saison_for_fixcost = 1 ";
			}
			if($bActivitySaisons) {
				$aSqlAddon[] = " season_for_activity = 1 ";
			}

			$sSqlAddon = " ( ";
			$i = 1;
			foreach((array)$aSqlAddon as $sData){
				$sSqlAddon .= $sData." ";
				if($i < count($aSqlAddon)){
					$sSqlAddon.= " OR ";
				}
				$i++;
			}
			$sSqlAddon .= " ) AND ";
			if(count($aSqlAddon) <= 0){
				$sSqlAddon = '';
			}
			$sQuery = "	SELECT
							`id`,UNIX_TIMESTAMP(`discount_".$sFor."`) as `discount_".$sFor."`,`discount_assignment`
						FROM
							`kolumbus_periods`
						WHERE
							".$sSqlAddon."
							`idPartnerschool` = ".(int)$idSchool." AND
							'".$sDate."' BETWEEN `valid_from` AND `valid_until`
						AND
							`active` = 1
						ORDER BY
							( UNIX_TIMESTAMP(`valid_until`) - UNIX_TIMESTAMP(`valid_from`) ) ASC";

			$aResult = DB::getQueryData($sQuery);
			if($bDiscountCheck == true){

				$aResult = self::checkForDiscount($aResult, $iCreated, $sFor);

			}

			if($aResult[0]['id'] > 0){
				$mReturn = $aResult;
			}
		}

		self::$aCachBySchoolAndTimestamp[$sKey] = (array)$mReturn;

		return $mReturn;
		
	}
	
	// Checken obs ein Frühbucherrabatt gibt
	public static function checkForDiscount($aSaison, $iCreated, $sFor = 'course', $bDiscountCheck = true) {
		
		$iDiscount 				= $aSaison[0]['discount_'.$sFor];
		$iDiscountAssignment 	= $aSaison[0]['discount_assignment'];
		
		$aDiscount = $aSaison;
		if(
			$iCreated <= $iDiscount && 
			$iDiscountAssignment > 0 && 
			$iDiscount > 0 && 
			$bDiscountCheck == true
		) {

			$aDiscount = self::byId($iDiscountAssignment, $sFor);
			$aDiscount = self::checkForDiscount($aDiscount, $iCreated, $sFor, false);

		}
		
		return $aDiscount;
		
	}
	
	/**
	 * get Saison 
	 * @param $iId
	 * @return array
	 */
	public static function byId($iId,$sFor = 'course'){
		$sQuery = "	SELECT
							`id`, UNIX_TIMESTAMP(`discount_".$sFor."`) as `discount_".$sFor."`, `discount_assignment`
						FROM
							`kolumbus_periods`
						WHERE
							`id` = '".(int)$iId."'
						AND
							`active` = 1
						";
		$aResult = DB::getQueryData($sQuery);
		return $aResult;
	}
	
}