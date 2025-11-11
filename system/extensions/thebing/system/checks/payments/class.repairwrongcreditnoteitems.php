<?php

/**
 * Sucht falsche Zahlungsitems und versucht diese wieder korrekt zuzuordnen 
 */
class Ext_Thebing_System_Checks_Payments_RepairWrongCreditnoteItems extends GlobalChecks {
	
	public function getTitle() {
		$sTitle = 'Repair wrong payment items';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck(){
		global $session_data;

		DB::begin('RepairWrongCreditnoteItems');
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		while(ob_get_level() > 0) {
			ob_end_flush();
		}
		
		$mBackup = Ext_Thebing_Util::backupTable('kolumbus_inquiries_payments_items');
		
		$aReport = array();
		
		if($mBackup !== false) {

			$aReport[] = 'Backup erfolgreich!';
			
			$sSql = "
				SELECT 
					`p`.`id` `payment_id`, 
					`d`.`document_number`, 
					`d`.`id` `document_id`, 
					`d`.`inquiry_id` `inquiry_id`,
					`pi`.`id` `payment_item_id`,
					`dvi`.`id` `creditnote_item_id`
				FROM  
					`kolumbus_inquiries_payments` `p` JOIN 
					`kolumbus_inquiries_payments_items` `pi` ON 
						`p`.`id` = `pi`.`payment_id` JOIN 
					`kolumbus_inquiries_documents_versions_items` `dvi` ON 
						`pi`.`item_id` = `dvi`.`id` JOIN 
					`kolumbus_inquiries_documents_versions` `dv` ON 
						`dvi`.`version_id` = `dv`.`id` JOIN 
					`kolumbus_inquiries_documents` `d` ON 
						`dv`.`document_id` = `d`.`id`
				WHERE 
					`d`.`type` = 'creditnote' AND
					`p`.`active` = 1 AND
					`d`.`active` = 1 AND
					`p`.`amount_inquiry` > 0
				";
			$aCreditnoteItems = DB::getQueryRows($sSql);

			if(!empty($aCreditnoteItems)) {

				foreach($aCreditnoteItems as $aCreditnoteItem) {

					$aReport[] = $aCreditnoteItem;
					
					$sSql = "
						SELECT
							*
						FROM
							`kolumbus_logs`
						WHERE
							`area` = 'Ext_Thebing_Inquiry_Payment_Item' AND
							`class_data_id` = :id
						ORDER BY
							`id` DESC
						";
					$aSql = array(
						'id' => (int)$aCreditnoteItem['payment_item_id']
					);
					$aLogs = DB::getQueryRows($sSql, $aSql);

					if(!empty($aLogs)) {

						$iNewItemId = null;

						// Logeinträge durchgehen
						foreach($aLogs as $aLog) {

							$aVars = json_decode($aLog['vars'], true);
							$aData = json_decode($aLog['optional_infos'], true);
							
							$oItem = Ext_Thebing_Inquiry_Document_Version_Item::getInstance($aData['item_id']);
							$oVersion = $oItem->getVersion();
							$oDocument = $oVersion->getDocument();

							// Wenn Eintrag gefunden wurde und die Dokument-ID nicht mehr zur Creditnote gehört, dann ID übernehmen
							if(
								$oDocument->id != $aCreditnoteItem['document_id']
							) {

								// Prüfen, ob die Item-ID zu der aktuellen Version gehört
								$oLatestVersion = $oDocument->getLastVersion();

								$aReport[] = 'Rechnungsnummer: '.$oDocument->document_number;

								if($oLatestVersion->id == $oVersion->id) {
									$iNewItemId = $aData['item_id'];
								} else {
									
									$aReport[] = 'WARNING: Gefundene Position gehört nicht zu der aktuellsten Version: '.$aData['item_id'];

									// Entsprechende Position in neuer Version suchen
									$aItems = $oLatestVersion->getItems();

									if(!empty($aItems)) {
										foreach($aItems as $aItem) {
											if(
												$aItem['type'] == $oItem->type &&
												$aItem['type_id'] == $oItem->type_id
											) {
												$iNewItemId = $aItem['position_id'];
												$aReport[] = 'Position in aktueller Version gefunden: '.$aItem['position_id'];
												break;
											}
										}
									}

								}

								break;
								
							}
							
						}

						// Wenn die korrekte ID gefunden wurde
						if($iNewItemId !== null) {
							
							$aUpdate = array(
								'item_id' => (int)$iNewItemId
							);
							$sWhere = '`id` = '.(int)$aCreditnoteItem['payment_item_id'];
							DB::updateData('kolumbus_inquiries_payments_items', $aUpdate, $sWhere);

							$aReport[] = 'Neue Position gefunden: '.$iNewItemId;

							echo  '1';
							
						} else {

							$aReport[] = 'ERROR: Keine Position gefunden!';

							echo  '0';
							
						}

					}

					$session_data['queryhistory'] = array();
					
					flush();
					
				}

			}

		} else {
			$aReport[] = 'Backup fehlgeschlagen!';
		}

		$sMessageBody = print_r($aReport, 1);
		$sMessage = 'Payments mit Zuweisung zu Agenturgutschriften bei "'.$_SERVER['HTTP_HOST'].'"';

		$oMail			= new WDMail();
		$oMail->subject = $sMessage;
		$oMail->html	= $sMessageBody;
		$oMail->send(array('support@thebing.com', 'mk@thebing.com'));
		
		__pout($aReport);
		
		DB::commit('RepairWrongCreditnoteItems');
		
		return true;

	}

}
