<?
/*
 * Importiert die alten Transfers auf die neue speicherstruktur nach kolumbus_inquiries_transfers
 */
class Ext_Thebing_System_Checks_TransferDataImport2 extends Ext_Thebing_System_ThebingCheck {

	public function isNeeded(){
		global $user_data;

		return true;
	}

	/*
	 * Check importiert die Transferdaten in die neue Struktur
	 */
	public function executeCheck(){
		global $user_data, $_VARS;

		Ext_Thebing_Util::backupTable('kolumbus_inquiries_transfers');

		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$sSql = "SELECT
						`ki`.*
					FROM
						`kolumbus_inquiries` `ki` LEFT JOIN
						`kolumbus_inquiries_transfers` `kit` ON
							`kit`.`inquiry_id` = `ki`.`id`
					WHERE
						`ki`.`active` = 1 AND
						`kit`.`id` IS NULL AND
						(
							`tsp_arrival` != '0000-00-00 00:00:00' OR
							`tsp_departure` != '0000-00-00 00:00:00' OR
							`tsp_airport` != 0 OR
							`tsp_arrival_time` IS NOT NULL OR
							`tsp_flightnumber` != '' OR
							`tsp_airline` != '' OR
							`tsp_airport2` != 0 OR
							`tsp_departure_time` IS NOT NULL OR
							`tsp_flightnumber2` != '' OR
							`tsp_airline2` != ''
						)

				";

		$aInquiries = DB::getQueryData($sSql);

		foreach((array)$aInquiries as $aInquiry) {

			if(
				$aInquiry['id'] > 0 &&
				(
					$aInquiry['tsp_airport'] > 0 ||
					$aInquiry['tsp_arrival'] != '0000-00-00 00:00:00' ||
					$aInquiry['tsp_arrival_time'] != '' ||
					$aInquiry['tsp_flightnumber'] != '' ||
					$aInquiry['tsp_airline'] != ''
				)
			){
				$oTransferArrival = new Ext_TS_Inquiry_Journey_Transfer(0);
				$oTransferArrival->active				= 1;
				$oTransferArrival->transfer_type		= 1; // Anreise
				$oTransferArrival->inquiry_id			= (int)$aInquiry['id'];
				$oTransferArrival->start				= (int)$aInquiry['tsp_airport'];
				$oTransferArrival->start_type			= 'location';
				$oTransferArrival->end					= 0;
				$oTransferArrival->end_type				= 'accommodation';
				$oTransferArrival->transfer_date		= $aInquiry['tsp_arrival'];
				$oTransferArrival->transfer_time		= $aInquiry['tsp_arrival_time'];
				$oTransferArrival->comment				= $aInquiry['comment_transfer_arr'];
				$oTransferArrival->start_additional		= 0;
				$oTransferArrival->end_additional		= 0;
				$oTransferArrival->flightnumber			= $aInquiry['tsp_flightnumber'];
				$oTransferArrival->airline				= $aInquiry['tsp_airline'];
				$oTransferArrival->pickup				= '';

				$bValidate = $oTransferArrival->validate();

				if($bValidate !== true){
					$oTransferArrival->transfer_time = NULL;
				}

				$oTransferArrival->save();
			}

			if(
				$aInquiry['id'] > 0 &&
				(
					$aInquiry['tsp_airport2'] > 0 ||
					$aInquiry['tsp_departure'] != '0000-00-00 00:00:00' ||
					$aInquiry['tsp_departure_time'] != '' ||
					$aInquiry['tsp_flightnumber2'] != '' ||
					$aInquiry['tsp_airline2'] != ''
				)
			){
				$oTransferDeparture = new Ext_TS_Inquiry_Journey_Transfer(0);
				$oTransferDeparture->active				= 1;
				$oTransferDeparture->transfer_type		= 2; // Abreise
				$oTransferDeparture->inquiry_id			= (int)$aInquiry['id'];
				$oTransferDeparture->start				= (int)$aInquiry['tsp_airport2'];
				$oTransferDeparture->start_type			= 'location';
				$oTransferDeparture->end				= 0;
				$oTransferDeparture->end_type			= 'accommodation';
				$oTransferDeparture->transfer_date		= $aInquiry['tsp_departure'];
				$oTransferDeparture->transfer_time		= $aInquiry['tsp_departure_time'];
				$oTransferDeparture->comment			= $aInquiry['comment_transfer_dep'];
				$oTransferDeparture->start_additional	= 0;
				$oTransferDeparture->end_additional		= 0;
				$oTransferDeparture->flightnumber		= $aInquiry['tsp_flightnumber2'];
				$oTransferDeparture->airline			= $aInquiry['tsp_airline2'];
				$oTransferDeparture->pickup				= '';

				$bValidate = $oTransferDeparture->validate();

				if($bValidate !== true){
					$oTransferDeparture->transfer_time = NULL;
				}

				$oTransferDeparture->save();
			}
			/////////////////////////////////////////////////////////////////////////

		}
		return true;
	}

}
