<?
/*
 * Ermittelt fehlende Rechnungen
 */
class Ext_Thebing_System_Checks_Missinginvoicepdf extends GlobalChecks {

	
	public function isNeeded(){ 
		global $user_data;

		return false; 

	}
	
	
	public function executeCheck(){
		global $user_data, $_VARS;

		$sSql =	"SELECT
						`kidv`.`path`,
						`kidv`.`version`,
						`kid`.`document_number`,
						`ki`.`crs_partnerschool`
					FROM
						`kolumbus_inquiries_documents_versions` `kidv` INNER JOIN
						`kolumbus_inquiries_documents` `kid` ON
							`kid`.`id` = `kidv`.`document_id` INNER JOIN
						`kolumbus_inquiries` `ki` ON
							`ki`.`id` = `kid`.`inquiry_id`
					WHERE
						`ki`.`active` = 1 AND
						`kid`.`type` IN ('netto', 'brutto', 'netto_diff', 'brutto_diff', 'creditnote') AND
						`kidv`.`active` = 1 AND
						`kid`.`active` = 1 AND
						`kid`.`document_number` != '' AND
						`kidv`.`version` = `kid`.`latest_version`
					ORDER BY
						`kid`.`created` DESC
					";

		$aVersions = DB::getQueryRows($sSql);

		$aMissingData = array();

		foreach((array)$aVersions as $aVersion){

			$oSchool = new Ext_Thebing_School(null, $aVersion['crs_partnerschool'], true);
			$sDir = $oSchool->getSchoolFileDir(true, true);

			if($aVersion['path'] == ''){
				$aInfo = array();
				$aInfo['document_number'] = $aVersion['document_number'];
				$aInfo['version'] = $aVersion['version'];
				$aInfo['reason'] = 'Pfad leer';

				$aMissingData[] = $aInfo;
			}elseif(!is_file(\Util::getDocumentRoot()."media/secure" . $aVersion['path'])){
				$aInfo = array();
				$aInfo['document_number'] = $aVersion['document_number'];
				$aInfo['version'] = $aVersion['version'];
				$aInfo['path'] = \Util::getDocumentRoot()."media/secure" . $aVersion['path'];
				$aInfo['reason'] = 'Datei nicht gefunden';

				$aMissingData[] = $aInfo;
			}


		}

		$oMail = new WDMail();
		$oMail->subject = "missing PDFs";
		$oMail->html = print_r($aMissingData,1);
		$oMail->send(array('developer@thebing.com'));  

		return true;
	}

}
