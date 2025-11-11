<?php

class Ext_TS_System_Checks_Enquiry_CombinationAmount extends GlobalChecks {

	public function getTitle() {
		return 'Update amount of enquiry combinations';
	}

	public function getDescription() {
		return 'Precalculates amount and saves it to database.';
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '1G');
		
		$oLog = Log::getLogger();
		
		DB::addField('ts_enquiries_combinations', 'amount', 'DECIMAL( 16, 5 ) NULL');
		DB::addField('ts_enquiries_combinations', 'currency_id', 'INT NULL');

		$sKey = 'db_table_description_ts_enquiries_combinations';	
		WDCache::delete($sKey);

		$sKey = 'wdbasic_table_description_ts_enquiries_combinations';
		WDCache::delete($sKey);

		$sSql = "
			SELECT
				`id`
			FROM 
				`ts_enquiries_combinations`
			WHERE
				`amount` IS NULL
			ORDER BY
				`id` DESC
				";
		$aCombinations = DB::getDefaultConnection()->getCollection($sSql);
		
		$iCounter = 0;
		foreach($aCombinations as $aCombination) {
			
			$oCombination = Ext_TS_Enquiry_Combination::getInstance($aCombination['id']);
			
			if($oCombination->active == 0) {
				$oLog->addInfo('Ext_TS_System_Checks_Enquiry_CombinationAmount - Inactive combination', array($oCombination->aData));
				continue;
			}
			
			$oEnquiry = $oCombination->getEnquiry();

			if($oEnquiry->active == 0) {
				$oLog->addInfo('Ext_TS_System_Checks_Enquiry_CombinationAmount - Inactive enquiry', array($oEnquiry->aData));
				continue;
			}

			$oEnquiry->setCombination($oCombination);

			try {
				$fCombinationAmount = $oEnquiry->calculateAmount();
				$sCurrencyIso = $oEnquiry->getCurrency();

				$oCombination->amount = $fCombinationAmount;
				$oCombination->currency_id = $sCurrencyIso;
				$oCombination->save();
			} catch(Exception $e) {
				// Exception tritt bei unvollständigen Anfragen auf und deutet auf korrupte Daten hin.
				$oLog->addError('Ext_TS_System_Checks_Enquiry_CombinationAmount - Exception', array($e->getMessage(), $e->getTraceAsString()));
			}

			if($iCounter % 100 == 0) {
				WDBasic::clearAllInstances();
			}

			$iCounter++;
		}

		return true;
	}

}