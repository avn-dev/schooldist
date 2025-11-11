<?
/*
 * Importiert die alten Transfers auf die neue speicherstruktur nach kolumbus_inquiries_transfers
 */
class Ext_Thebing_System_Checks_TransferDataImport extends Ext_Thebing_System_ThebingCheck {

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
						*
					FROM
						`kolumbus_inquiries`
					WHERE
						`active` = 1 AND
						(
							`tsp_arrival` != '0000-00-00 00:00:00' OR
							`tsp_airport` != 0 OR
							`tsp_arrival_time` != '00:00:00' OR
							`tsp_flightnumber` != '' OR
							`tsp_airline` != '' OR
							`tsp_departure` != '0000-00-00 00:00:00' OR
							`tsp_airport2` != 0 OR
							`tsp_departure_time` != '00:00:00' OR
							`tsp_flightnumber2` != '' OR
							`tsp_airline2` != ''
						)
				";

		$aInquiries = DB::getQueryData($sSql);

		foreach((array)$aInquiries as $aInquiry) {

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

			$iTransferId = 0;
	
			$oTransferArrival->save();
			$oTransferDeparture->save();

			$iTransferId = $oTransferArrival->id;

			// Rechnungspositionen anpassen ////////////////////////////////////////
			if($iTransferId > 0 && $aInquiry['tsp_transfer'] != 'arr_dep'){
				$oInquiry = Ext_TS_Inquiry::getInstance($aInquiry['id']);

				// Rechnungsdoc
				$aDocuments = Ext_Thebing_Inquiry_Document_Search::search($oInquiry->id, 'invoice_without_proforma', true, true);

				foreach((array)$aDocuments as $oDocument) {
					// Letzte Version
					$oLastVersion	= $oDocument->getLastVersion();

					if($oLastVersion){
						// Alle Items
						$aItems = $oLastVersion->getItemObjects();

						foreach((array)$aItems as $oItem){
							if($oItem->type == 'transfer'){
								// Typ beibehalten, ID anpassen
								$oItem->type_id = $iTransferId;
								$oItem->save();
							}
						}
					}
				}
			}

			/////////////////////////////////////////////////////////////////////////


		}
		return true;
	}

}
