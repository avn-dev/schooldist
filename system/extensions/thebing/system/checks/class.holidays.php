<?
/*
 * Importiert die Feiertage in die Neue Struktur
 */
class Ext_Thebing_System_Checks_Holidays extends Ext_Thebing_System_ThebingCheck {

	public function getTitle() {
		$sTitle = 'Holiday data import';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Holiday data import';
		return $sDescription;
	}

	/*
	 * Check importiert die PDF/Mainuploads in die neue Struktur
	 */
	public function executeCheck(){
		global $user_data, $_VARS;

		Ext_Thebing_Util::backupTable('kolumbus_holidays');

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$sSql = "SELECT
						FROM_UNIXTIME(`kh`.`day`) `day`,
						`kh`.*
					FROM
						`kolumbus_holidays` `kh`
					WHERE
						`kh`.`idSchool` > 0 AND
						`kh`.`day` > 0
					GROUP BY
						`kh`.`day`, `kh`.`idSchool`
				";

		$aHolidays = DB::getQueryData($sSql);

		foreach((array)$aHolidays as $aData){
			$oSchool = new Ext_Thebing_School(NULL, $aData['idSchool'], true);
			$iClient = $oSchool->getClientId();

			$oDate = new WDDate( );
			$oDate->set($aData['day'], TIMESTAMP);
			$sDate = $oDate->get(DB_DATE);
/*
			$oDateFormat = new Ext_Thebing_Gui2_Format_Date();

			$aTemp = array();
			$aTemp['school_id'] = (int)$aData['idSchool'];
*/
			$oHoliday = new Ext_Thebing_Holiday_Holiday(0);
			$oHoliday->active = 1;
			$oHoliday->client_id = (int)$iClient;
			$oHoliday->status = 1;
			$oHoliday->name = $sDate;
			$oHoliday->comment = '';
			$oHoliday->date = $sDate;
			$oHoliday->annual = 1;
			$oHoliday->join_school = array($aData['idSchool']);
			$oHoliday->save();
		}


		// Username löschen
		$sSql = "UPDATE
						`kolumbus_holidays_public`
					SET
						`user_id` = 0
				";
		DB::executeQuery($sSql);

		return true;
	}

}
