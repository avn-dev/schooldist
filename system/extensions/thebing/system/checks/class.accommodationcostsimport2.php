<?php


class Ext_Thebing_System_Checks_AccommodationCostsImport2 extends GlobalChecks {

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		Ext_Thebing_Util::backupTable('kolumbus_accommodations_costs_nights_periods');
		Ext_Thebing_Util::backupTable('kolumbus_accommodations_costs_nights');
		
		try {

		
			$sSql = "
				SELECT
					*,
					UNIX_TIMESTAMP(`valid_from`) `unix_valid_from`,
					UNIX_TIMESTAMP(`valid_until`) `unix_valid_until`
				FROM
					`kolumbus_periods`
				WHERE
					`active` = 1 AND
					`saison_for_accommodationcost` = 1
				";
			$aTemps = DB::getQueryRows($sSql);
			$aSaisons = array();
			foreach((array)$aTemps as $aTemp) {
				$aSaisons[$aTemp['id']] = $aTemp;
			}
			
			$sSql = "
				SELECT
					*,
					UNIX_TIMESTAMP(`from`) `unix_valid_from`,
					UNIX_TIMESTAMP(`until`) `unix_valid_until`
				FROM
					`kolumbus_accommodations_costs_nights_periods`
				WHERE
					`active` = 1
				";
			$aTemps = DB::getQueryRows($sSql);
			$aPeriods = array();
			foreach((array)$aTemps as $aTemp) {
				$aPeriods[$aTemp['id']] = $aTemp;
			}


			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_accommodations_costs_nights`
				WHERE
					`active` = 1
				";
			$aItems = DB::getQueryRows($sSql);
			
			$aNewPeriods = array();
			
			foreach((array)$aItems as $aItem) {

				$aSaison = $aSaisons[$aItem['saison_id']];
				$aPeriod = $aPeriods[$aItem['period_id']];
				
				// Wenn die Periode in der Saison liegt ist alles OK!
				// Wenn keine Saison -> muss auch nichts geschrieben werden
				if(
					(
						$aPeriod['unix_valid_from'] == $aSaison['unix_valid_from'] && 
						$aPeriod['unix_valid_until'] == $aSaison['unix_valid_until'] 	
					) ||
					empty($aSaison) ||
					empty($aPeriod)
				) {
					continue;
				}

				$sTempKey = $aSaison['valid_from'];
				$sTempKey .= '_'.$aSaison['valid_until'];
				$sTempKey .= '_'.$aPeriod['costcategory_id'];
				$sTempKey .= '_'.$aPeriod['accommodation_category_id'];
				
				if(!key_exists($sTempKey, $aNewPeriods)){
					
					// Wenn nicht 
					// Periode Koppie an die Saison angleichen und speichern
					$aPeriod['id']		= 0;
					$aPeriod['from']	= $aSaison['valid_from'];
					$aPeriod['until']	= $aSaison['valid_until'];
					unset($aPeriod['unix_valid_from']);
					unset($aPeriod['unix_valid_until']);
					$iPeriodId = DB::insertData('kolumbus_accommodations_costs_nights_periods', $aPeriod);
					$aNewPeriods[$sTempKey] = $iPeriodId;
				} else {
					$iPeriodId = $aNewPeriods[$sTempKey];
				}
				
				DB::updateData('kolumbus_accommodations_costs_nights', array('period_id' => (int)$iPeriodId), '`id` = '.$aItem['id']);
				
			}

		} catch(Exception $e) {
			__pout($e);
			Ext_Thebing_Util::reportError('Ext_Thebing_System_Checks_AccommodationCostsImport2 error', $e);
		}

		return true;

	}

	public function getTitle() {
		$sTitle = 'Imports accommodation costs in new structure';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}

}