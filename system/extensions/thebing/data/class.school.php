<?php

class Ext_Thebing_Data_School {

	
	public static function getSaisonList($bForSelects = true, $bPriceSaisons = true,$bTeacherSaisons = false,$bTransferSaisons = false,$bAccommodationSaisons = false,$bFixcostSaisons = false){
		global $user_data;
        $iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		$aReturn = self::getSaisons($user_data['client'], $iSessionSchoolId, $bForSelects, $bPriceSaisons, $bTeacherSaisons, $bTransferSaisons, $bAccommodationSaisons, $bFixcostSaisons);
		
		return $aReturn;
		
	}
	
	public static function getSaisonListByYear( $iYear = null, $bForSelects = true, $bPriceSaisons = true, $bTeacherSaisons = false, $bTransferSaisons = false, $bAccommodationSaisons = false, $bFixcostSaisons = false ){
	    $aSaisonList = array();
		if ( ($iYear !== null) || ((int)$iYear > 0) ) {
		    $aSaisonListOrg = self::getSaisonList(
		        false, 
		        $bPriceSaisons, 
		        $bTeacherSaisons,
		        $bTransferSaisons,
		        $bAccommodationSaisons,
		        $bFixcostSaisons
		    );
		    $aYear = array(
		        "start" => mktime(0,0,0,1,1,(int)$iYear),
		        "end"   => mktime(0,0,0,12,31,(int)$iYear)
		    );
		    $aSaisonList = array();
		    foreach ((array)$aSaisonListOrg as $iKey => $aSaison) {
    		    if (
    		        (
    		            (strtotime($aSaison["valid_from"]) >= $aYear["start"]) &&
    		            (strtotime($aSaison["valid_from"]) <= $aYear["end"]) 
    		        ) || (
    		            (strtotime($aSaison["valid_until"]) >= $aYear["start"]) &&
    		            (strtotime($aSaison["valid_until"]) <= $aYear["end"]) 
    		        )
    		    ) {
    		        if ($bForSelects) {
    		            $aSaisonList[$aSaison["id"]] = $aSaison["title_en"];
    		        } else {
    		            $aSaisonList[] = $aSaison;
    		        }
    		    }
		    }
		} else {
		    $aSaisonList = self::getSaisonList(
		        $bForSelects, 
		        $bPriceSaisons, 
		        $bTeacherSaisons,
		        $bTransferSaisons,
		        $bAccommodationSaisons,
		        $bFixcostSaisons
		    );
		}
		return $aSaisonList;
	}
	
	public static function getSaisons($iClientId, $iSchoolId, $bForSelects = true,$bPriceSaisons = true,$bTeacherSaisons = false,$bTransferSaisons = false,$bAccommodationSaisons = false,$bFixcostSaisons = false){

		$aSqlAddon = array();
		if($bPriceSaisons){
			$aSqlAddon[] = " saison_for_price = 1 ";
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
		
		
		$sSql = "SELECT 
					* 
				FROM 
					`kolumbus_periods`
				WHERE
					".$sSqlAddon."
					`active` = 1 AND
					`idClient` = :idClient AND
					`idPartnerschool` = :idSchool
				";
		$aSql = array('idClient'=>(int)$iClientId,'idSchool'=>(int)$iSchoolId);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		
		if($bForSelects == true){
			$aSelect = array();
			foreach($aResult as $aValue){
				$aSelect[$aValue['id']] = $aValue['title_en'];
			}
			return $aSelect;
		} else {
			return $aResult;
		}

	}

	public static function getPricestruktur() {
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		return $oSchool->price_structure_week;
	}

	public static function getLektionenPricestruktur() {
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		return $oSchool->price_structure_unit;
	}

}
