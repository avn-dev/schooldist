<?
/*
 * Importiert die Bestätigungsfelder für Transfer von der Inquiry tabelle in die Inquiry-transfer
 */
class Ext_Thebing_System_Checks_TransferConfirmed extends GlobalChecks {

	public function getTitle() {
		$sTitle = 'Transfer Confirmation Import';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Transfer Confirmation Import';
		return $sDescription;
	}

	/*
	 * Check importiert die PDF/Mainuploads in die neue Struktur
	 */
	public function executeCheck(){
		global $user_data, $_VARS;

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');
		
		Ext_Thebing_Util::backupTable('kolumbus_inquiries_transfers');

		$sSql = "SELECT
						`id`,
						UNIX_TIMESTAMP(`agencyTspInfoSent`) `agencyTspInfoSent`,
						UNIX_TIMESTAMP(`tspInfoSent`) `tspInfoSent`
					FROM
						`kolumbus_inquiries`
					WHERE
						`active` = 1 AND
						(
							`agencyTspInfoSent` > 0 ||
							`tspInfoSent` > 0
						)
				";

		$aInquiries = DB::getQueryData($sSql);

		foreach((array)$aInquiries as $aInquiryData){

			$iTimestamp = 0;

			if($aInquiryData['agencyTspInfoSent'] > $aInquiryData['tspInfoSent']){
				$iTimestamp = $aInquiryData['agencyTspInfoSent'];
			}else{
				$iTimestamp = $aInquiryData['tspInfoSent'];
			}


			$sSql = "UPDATE
							`kolumbus_inquiries_transfers`
						SET
							`changed` = `changed`,
							`customer_agency_confirmed` = :time
						WHERE
							`inquiry_id` = :inquiry_id AND
							`active` = 1 AND
							`customer_agency_confirmed` = 0
					";
			$aSql = array();
			$aSql['time'] = date('Y-m-d H:i:s', $iTimestamp);
			$aSql['inquiry_id'] = $aInquiryData['id'];

			DB::executePreparedQuery($sSql, $aSql);
		}

		return true;
		
	}

}
