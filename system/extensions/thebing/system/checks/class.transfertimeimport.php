<?
class Ext_Thebing_System_Checks_TransferTimeImport extends Ext_Thebing_System_ThebingCheck {

	public function isNeeded(){
		global $user_data;

		if($user_data['name'] == 'alpha'){
			return true;
		}

		return false;
	}
	
	/*
	 * Check importiert die Transferdaten in die neue Struktur
	 */
	public function executeCheck(){
		global $user_data, $_VARS;


		$sSql = "SELECT
						`id`, `tsp_arrival`, `tsp_departure`
					FROM
						`kolumbus_inquiries`
					WHERE
						`active` = 1
					";
		$aInquiries = DB::getQueryData($sSql);

		foreach((array)$aInquiries as $aInquiry){

			$aTimeFrom	= explode(' ', $aInquiry['tsp_arrival']);
			$aTimeTo	= explode(' ', $aInquiry['tsp_departure']);

			$sTimeFrom	= trim($aTimeFrom[1]);
			$sTimeTo	= trim($aTimeTo[1]);

			$sSql = "UPDATE
							`kolumbus_inquiries`
						SET
							`tsp_arrival_time` = :arrival,
							`tsp_departure_time` = :departure
						WHERE
							`id`= :id";

			$aSql = array();
			$aSql['arrival'] = $sTimeFrom;
			$aSql['departure'] = $sTimeTo;
			$aSql['id'] = (int)$aInquiry['id'];

			DB::executePreparedQuery($sSql,$aSql);
			
		}

		$sSql = "UPDATE
					`kolumbus_inquiries`
				SET
					`tsp_arrival_time` = NULL
				WHERE
					`tsp_arrival_time` = '00:00:00'";
		DB::executeQuery($sSql);

		$sSql = "UPDATE
					`kolumbus_inquiries`
				SET
					`tsp_departure_time` = NULL
				WHERE
					`tsp_departure_time` = '00:00:00'";
		DB::executeQuery($sSql);

		return true;
	}

}
