<?php 

class Ext_Thebing_System_Checks_AdditionalCosts2 extends GlobalChecks {
	
	public function executeCheck(){
		global $user_data;
		
		try{
			//Ext_Thebing_Util::backupTable('kolumbus_costs_courses');
			//Ext_Thebing_Util::backupTable('kolumbus_costs_accommodations');
		}catch(Exception $e){
			
		}

		// KURSKOSTEN
		$sSql = "SELECT * FROM `kolumbus_course_fee` WHERE `currency_id` = 0";
		$aResultCourseFee = DB::getQueryData($sSql);
		
		foreach((array)$aResultCourseFee AS $aCourseFeeData){
			// Currency Liste
			$oSchool = Ext_Thebing_School::getInstance($aCourseFeeData['school_id']);

			$aCurrencyList = (array)json_decode($oSchool->currencies);
			
			$aSql = array();

			foreach((array)$aCurrencyList as $iCurrency){
				$sSql = "INSERT INTO 
							`kolumbus_course_fee` 
						SET 
							`client_id` = :client, 
							`school_id` = :school,
							`course_id` = :course,
							`saison_id` = :saison,
							`cost_id` = :cost,					
							`currency_id` = :currency,
							`amount` = :amount";
				$aSql = array();
				$aSql['client'] = (int)$aCourseFeeData['client_id'];
				$aSql['school'] = (int)$aCourseFeeData['school_id'];
				$aSql['course'] = (int)$aCourseFeeData['course_id'];
				$aSql['saison'] = (int)$aCourseFeeData['saison_id'];
				$aSql['cost'] = (int)$aCourseFeeData['cost_id'];
				$aSql['currency'] = (int)$iCurrency;
				$aSql['amount'] = $aCourseFeeData['amount'];
				
				DB::executePreparedQuery($sSql,$aSql);
			}
		}

		// UNTERKUNFTSKOSTEN
		$sSql = "SELECT * FROM `kolumbus_accommodation_fee` WHERE `currency_id` = 0";
		$aResultAccommodationFee = DB::getQueryData($sSql);
		
		foreach((array)$aResultAccommodationFee AS $aAccFeeData){
			// Currency Liste
			$oSchool = Ext_Thebing_School::getInstance($aCourseFeeData['school_id']);

			$aCurrencyList = (array)json_decode($oSchool->currencies);
			
			$aSql = array();

			foreach((array)$aCurrencyList as $iCurrency){
				$sSql = "INSERT INTO 
							`kolumbus_accommodation_fee` 
						SET 
							`client_id` = :client, 
							`school_id` = :school,
							`categorie_id` = :category,
							`saison_id` = :saison,
							`cost_id` = :cost,					
							`currency_id` = :currency,
							`amount` = :amount";
				$aSql = array();
				$aSql['client'] = (int)$aAccFeeData['client_id'];
				$aSql['school'] = (int)$aAccFeeData['school_id'];
				$aSql['category'] = (int)$aAccFeeData['categorie_id'];
				$aSql['saison'] = (int)$aAccFeeData['saison_id'];
				$aSql['cost'] = (int)$aAccFeeData['cost_id'];
				$aSql['currency'] = (int)$iCurrency;
				$aSql['amount'] = $aAccFeeData['amount'];
				
				DB::executePreparedQuery($sSql,$aSql);
			}
		}
		
		$aSql = array();
		$sSql = "DELETE FROM `kolumbus_course_fee` WHERE `currency_id` = 0";
		DB::executePreparedQuery($sSql,$aSql);
		
		$sSql = "DELETE FROM `kolumbus_accommodation_fee` WHERE `currency_id` = 0";
		DB::executePreparedQuery($sSql,$aSql);
		
		return true;
		
	}
	
}


?>