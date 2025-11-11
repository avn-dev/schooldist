<?php 

class Ext_Thebing_System_Checks_Schoolholiday extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'School Holiday Import';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'School Holiday Import'; 
		return $sDescription;
	}



	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		Ext_Thebing_Util::backupTable('kolumbus_holidays_school');

	
		$sSql = "SELECT
					`khs`.*,
					DATE(FROM_UNIXTIME(`khs`.`day`)) `date`
				FROM
					`kolumbus_holidays_school` `khs`
				WHERE
					`khs`.`idSchool` > 0 AND
					`khs`.`day` > 0
				GROUP BY
					`khs`.`day`, `khs`.`idSchool`
				";
		$aDates = DB::getQueryRows($sSql);

		$aEntries = array();
		$aSchools = array();
		$aClients = array();
		$iIndex = 0;
		$iLastRoom = 0;
		$oLastDate = false;
		foreach((array)$aDates as $aDate) {


			$oDate = new WDDate($aDate['date'], WDDate::DB_DATE);

			if($oLastDate) {
				$iDiff = $oLastDate->getDiff(WDDate::DAY, $oDate);
				if($iDiff < -1) {
					$iIndex++;
				}
			} else {
				$iIndex++;
			}
			if(!isset($aEntries[$iIndex])) { 
				$aEntries[$iIndex] = $aDate;
				$aEntries[$iIndex]['from'] = $oDate->get(WDDate::DB_DATE);
			}
			$aEntries[$iIndex]['until'] = $oDate->get(WDDate::DB_DATE);

			$oSchool = new Ext_Thebing_School(NULL, $aData['idSchool'], true);
			$iClient = $oSchool->getClientId();

			$aSchools[$aDate['idSchool']] = (int)$aDate['idSchool'];
			$aClients[$iClient] = (int)$iClient;


			$oLastDate = $oDate;

		}


		foreach((array)$aEntries as $aEntry) {

			$aInsert = array();
			$aInsert['created']		= date('Y-m-d H:i:s');
			$aInsert['active']		= 1;
			$aInsert['school_id']	= (int)$aEntry['idSchool'];
			$aInsert['item']		= 'holiday';
			$aInsert['item_id']		= (int)$aEntry['idSchool'];
			$aInsert['from']		= $aEntry['from'];
			$aInsert['until']		= $aEntry['until'];
			$aInsert['category_id']	= -2;

			DB::insertData('kolumbus_absence', $aInsert);

		}


		return true;
		
	}	

}
