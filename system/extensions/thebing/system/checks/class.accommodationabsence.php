<?php 

class Ext_Thebing_System_Checks_AccommodationAbsence extends GlobalChecks {

	public function isNeeded(){
		global $user_data;

		if($user_data['name'] == 'admin' || $user_data['name'] == 'wielath' || $user_data['name'] == 'koopmann') {
			return true;
		}

		return false;
	}

	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		Ext_Thebing_Util::backupTable('kolumbus_absence');
		Ext_Thebing_Util::backupTable('kolumbus_rooms');
		Ext_Thebing_Util::backupTable('kolumbus_room_dates');
		// unterkunftsblockierungen wurden immer auch als raumblockierung gespeichert
		//Ext_Thebing_Util::backupTable('kolumbus_accommodation_dates');

		try {

			$sSql = "SELECT
						`krd`.*,
						DATE(FROM_UNIXTIME(`day`)) `date`
					FROM
						`kolumbus_room_dates` `krd` JOIN
						`kolumbus_rooms` `kr` ON
							`krd`.`room_id` = `kr`.`id` AND
							`kr`.`active` = 1
					WHERE
						1
					ORDER BY
						`krd`.`room_id`,
						`krd`.`day`
						";
			$aDates = DB::getQueryRows($sSql);

			$aEntries = array();
			$aSchools = array();
			$aClients = array();
			$iIndex = 0;
			$iLastRoom = 0;
			$oLastDate = false;
			foreach((array)$aDates as $aDate) {

				if($iLastRoom != $aDate['room_id']) {
					$oLastDate = false;
				}

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

				$aSchools[$aDate['school_id']] = $aDate['school_id'];
				$aClients[$aDate['client_id']] = $aDate['client_id'];

				$iLastRoom = $aDate['room_id'];
				$oLastDate = $oDate;

			}

			// Standardkategorie anlegen
			foreach((array)$aClients as $iClient => $iValue) {

				$aTemp = (array)Ext_Thebing_Absence_Category::getList(false, $iClient);
				$aCategory = reset($aTemp);
				$aClients[$iClient] = (int)$aCategory['id'];

				if(empty($aClients[$iClient])) {
					$oCategory = new Ext_Thebing_Absence_Category();
					$oCategory->active = 1;
					$oCategory->color = '#2E97E0';
					$oCategory->name = 'Standard';
					$oCategory->client_id = (int)$iClient;
					$oCategory->save();
					$aClients[$iClient] = $oCategory->id;
				}

			}

			foreach((array)$aEntries as $aEntry) {

				$aInsert = array();
				$aInsert['created']		= date('Y-m-d H:i:s');
				$aInsert['active']		= 1;
				$aInsert['school_id']	= (int)$aEntry['school_id'];
				$aInsert['item']		= 'accommodation';
				$aInsert['item_id']		= (int)$aEntry['room_id'];
				$aInsert['from']		= $aEntry['from'];
				$aInsert['until']		= $aEntry['until'];
				$aInsert['category_id']	= (int)$aClients[$aEntry['client_id']];

				DB::insertData('kolumbus_absence', $aInsert);

			}

			$sSql = "DROP TABLE `kolumbus_room_dates`";
			DB::executeQuery($sSql);

		} catch (DB_QueryFailedException $e) {
			__pout($e);
		}

	
		

		return true;
		
	}	

}
