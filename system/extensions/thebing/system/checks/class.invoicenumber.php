<?
/*
 * Ersetzt fehlende Rechnungsnummern
 */
class Ext_Thebing_System_Checks_Invoicenumber extends GlobalChecks {

	
	public function isNeeded(){
		global $user_data;

		if(
			$user_data['name'] == 'TEST' ||
			$user_data['name'] == 'Bing000' ||
			$user_data['name'] == 'alpha'
		){
			return true;
		}

		return false;
	}
	
	
	public function executeCheck(){
		global $user_data, $_VARS;

		Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents');

		// Fehlende Rechnungsnummern
		$sSql = "SELECT
						`kid`.`id` `document_id`,
						`kid`.`type` `document_type`,
						`kid`.`document_number` `document_number`,
						`ki`.`crs_partnerschool` `school_id`,
						`ki`.`id` `inquiry_id`,
						`ki`.`group_id` `group_id`
					FROM
						`kolumbus_inquiries_documents` `kid` INNER JOIN
						`kolumbus_inquiries` `ki` ON
							`ki`.`id` = `kid`.`inquiry_id`
					WHERE
						`kid`.`type` IN (
										'netto',
										'brutto',
										'brutto_diff',
										'netto_diff',
										'creditnote',
										'storno'
									)
					ORDER BY
						`kid`.`created` ASC";

		$aDocuments = DB::getQueryRows($sSql);

		$aGroup = array();

		$sTempNumber = '';
		$iCounter = 0;
		// Alle Documente durchgehen
		foreach((array)$aDocuments as $aDocument){

			if(
				$aDocument['group_id'] > 0 &&
				empty($aGroup[$aDocument['group_id']])
			){
				$aGroup[$aDocument['group_id']] = $aDocument['document_number'];
			}

			// Wenn Rechnungsnummer vorhanden dann zwischenspeichern
			if($aDocument['document_number'] != ''){
				$sTempNumber = $aDocument['document_number'];
				$iCounter = 0;
				continue;
			}else{
				// Document hat noch keine Rechnungsnummer

				if(
					$aDocument['group_id'] > 0 &&
					!empty($aGroup[$aDocument['group_id']])
				){
					// Gruppen Rechnungsnr
					$sFinalNumber = $aGroup[$aDocument['group_id']];
				}else{

					// NR generieren
					$iCounter++;
					$sNewAddon= '';
					if($iCounter < 10){
						$sNewAddon = '0' . $iCounter;
					}else{
						$sNewAddon = $iCounter;
					}

					$sFinalNumber =  $sTempNumber . '.' . $sNewAddon;

					// ggf. Nr nur Gruppe speichern
					if($aDocument['group_id'] > 0){
						$aGroup[$aDocument['group_id']] = $sFinalNumber;
					}
				}


				$sSql = "UPDATE
							`kolumbus_inquiries_documents`
						SET
							`document_number` = :document_number
						WHERE
							`id` = :document_id
					";
				$aSql = array();
				$aSql['document_number'] = $sFinalNumber;
				$aSql['document_id'] = (int)$aDocument['document_id'];

				DB::executePreparedQuery($sSql, $aSql);
			}

			
		}





		return true;
	}

}
