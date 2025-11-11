<?
class Ext_Thebing_System_Checks_VisumTemplateImport extends GlobalChecks {

	/*
	public function isNeeded(){
		global $user_data;

		if($user_data['name'] == 'admin' || $user_data['name'] == 'wielath'){
			return true;
		}

		return false;
	}
	*/
	
	public function executeCheck(){
		global $user_data, $_VARS;

		/*
		if(!hasRight('modules_admin')){
			$this->_aFormErrors[] = 'Only an full CMS Administrator hast the Right to start this Script!';
			return false;
		}
		 */

		// Backup der Tabellen
		try{
			//Ext_Thebing_Util::backupTable('kolumbus_inquiries_courses');
			//Ext_Thebing_Util::backupTable('kolumbus_inquiries_accommodations');
		} catch(Exception $e){
			__pout($e);
			//return false;
		}


		// Alle Clients
		$sSql = "SELECT `id` FROM `kolumbus_clients` WHERE `active` = 1";
		$aClients = DB::getQueryData($sSql);

		foreach((array)$aClients as $aClient){
			$aSql = array();

			// Alle Schulen für diesen Client
			$sSql = "SELECT id FROM customer_db_2 WHERE active = 1 AND idClient = " . (int)$aClient['id'];
			$aSchools = DB::getQueryData($sSql);

			foreach((array)$aSchools as $aSchool){

				## START Visum
					$sSql = "SELECT
								`kid`.*, `cdb1`.`ext_27` `language`
							FROM
								`kolumbus_inquiries_documents` `kid` INNER JOIN
								`kolumbus_inquiries` `ki` ON
									`kid`.`inquiry_id` = `ki`.`id` AND
									`ki`.`crs_partnerschool` = " . $aSchool['id'] . " INNER JOIN
								`customer_db_1` AS `cdb1` ON
									`cdb1`.`id` = `ki`.`idUser`
							WHERE
								`kid`.`type` = 'customer_visum'
							";

					$aResult = DB::getQueryData($sSql);

					foreach((array)$aResult as $aDoc){
						// Pro Doc Template bestimmen
						$sSql = "SELECT
										`kpt`.*
									FROM
										`kolumbus_pdf_templates` AS `kpt` INNER JOIN
										`kolumbus_pdf_templates_languages` AS `kptl` ON
											`kptl`.`template_id` = `kpt`.`id` INNER JOIN
											`kolumbus_pdf_templates_schools` AS `kpts` ON
											`kpts`.`template_id` = `kpt`.`id`
									WHERE
										`kpt`.`type` = 'document_studentrecord_visum_pdf' AND
										`kpt`.`client_id` = :client AND
										`kpt`.`active` = 1 AND
										`kpts`.`school_id` = :school_id AND
										`kptl`.`iso_language` = :lang
									GROUP BY
										`kpt`.`id`
									ORDER BY
										`kpt`.`changed` DESC
									LIMIT 1";
						$aSql['client'] = (int)$aClient['id'];
						$aSql['school_id'] = (int)$aSchool['id'];
						$aSql['lang'] = (int)$aDoc['language'];
						$aTemplate = DB::getPreparedQueryData($sSql,$aSql);


						// Typ umbenennen
						$sSql = "UPDATE
										`kolumbus_inquiries_documents`
									SET
										`type` = 'additional_document'
									WHERE
										`id` = :id";
						$aSql['id'] = (int) $aDoc['id'];

						DB::executePreparedQuery($sSql,$aSql);

						// Template umgenennen
						$sSql = "UPDATE
										`kolumbus_inquiries_documents_versions`
									SET
										`template_id` = :temp_id
									WHERE
										`document_id` = :id";
						$aSql['temp_id'] = (int)$aTemplate[0]['id'];
						$aSql['id'] = (int)$aDoc['id'];

						DB::executePreparedQuery($sSql,$aSql);

					}
				## ENDE VISUM
/*
				## START STudent PDF
					$sSql = "SELECT
								`kid`.*, `cdb1`.`ext_27` `language`
							FROM
								`kolumbus_inquiries_documents` `kid` INNER JOIN
								`kolumbus_inquiries` `ki` ON
									`kid`.`inquiry_id` = `ki`.`id` AND
									`ki`.`crs_partnerschool` = " . $aSchool['id'] . " INNER JOIN
								`customer_db_1` AS `cdb1` ON
									`cdb1`.`id` = `ki`.`idUser`
							WHERE
								`kid`.`type` = 'customer_visum'
							";

					$aResult = DB::getQueryData($sSql);

					foreach((array)$aResult as $aDoc){
						// Pro Doc Template bestimmen
						$sSql = "SELECT
										`kpt`.*
									FROM
										`kolumbus_pdf_templates` AS `kpt` INNER JOIN
										`kolumbus_pdf_templates_languages` AS `kptl` ON
											`kptl`.`template_id` = `kpt`.`id` INNER JOIN
											`kolumbus_pdf_templates_schools` AS `kpts` ON
											`kpts`.`template_id` = `kpt`.`id`
									WHERE
										`kpt`.`type` = 'document_studentrecord_additional_pdf' AND
										`kpt`.`client_id` = :client AND
										`kpt`.`active` = 1 AND
										`kpts`.`school_id` = :school_id AND
										`kptl`.`iso_language` = :lang
									ORDER BY
										`kpt`.`changed` DESC
									GROUP BY
										`kpt`.`id`
									LIMIT 1";
						$aSql['client'] = (int)$aClient['id'];
						$aSql['school_id'] = (int)$aSchool['id'];
						$aSql['lang'] = (int)$aDoc['language'];
						$aTemplate = DB::getPreparedQueryData($sSql,$aSql);


						// Typ umbenennen
						$sSql = "UPDATE
										`kolumbus_inquiries_documents`
									SET
										`type` = 'additional_document'
									WHERE
										`id` = :id";
						$aSql['id'] = (int) $aDoc['id'];

						DB::executePreparedQuery($sSql,$aSql);

						// Template umgenennen
						$sSql = "UPDATE
										`kolumbus_inquiries_documents_versions`
									SET
										`template_id` = :temp_id
									WHERE
										`document_id` = :id";
						$aSql['temp_id'] = (int)$aTemplate[0]['id'];
						$aSql['id'] = (int)$aDoc['id'];

						DB::executePreparedQuery($sSql,$aSql);

					}
				## ENDE
*/

			}


		}

		## START Inport des sendestatus
			// Ein Array mit allen Dateien anlegen, die jemals verschickt wurden
			$sSql = "
					SELECT
						*
					FROM
						`kolumbus_maillog`
					WHERE
						`active` = 1 AND
						`attachments` != 'N;' AND
						`attachments` != ''
			";
			$aLogs = DB::getQueryData($sSql);

			$aAttachments = array();
			foreach((array)$aLogs as $aLog) {
				$aTemp = unserialize($aLog['attachments']);

				if(
					is_array($aTemp) &&
					!empty($aTemp)
				) {
					foreach((array)$aTemp as $sFile) {
						// /media/secure abschneiden
						$sFile = str_replace('/media/secure', '', $sFile);
						$sFile = str_replace('//', '/', $sFile);
						$aAttachments[$sFile] = $aLog['created'];
					}
				}
			}

			// Die document_versions durchlaufen und schauen ob der Pfad in dem Array steht
			$sSql = "
					SELECT
						*
					FROM
						`kolumbus_inquiries_documents_versions`
					WHERE
						`active` = 1 AND
						`path` != ''
			";
			$aVersions = DB::getQueryData($sSql);

			foreach((array)$aVersions as $aVersion) {

				// Dokument wurde verschickt
				if(
					isset($aAttachments[$aVersion['path']]) &&
					$aVersion['sent'] == '0000-00-00 00:00:00'
				) {
					$sSql = "
							UPDATE
								`kolumbus_inquiries_documents_versions`
							SET
								`changed` = `changed`,
								`sent` = :sent
							WHERE
								`id` = :id
							LIMIT 1
							";
					$aSql = array(
								'id'=>(int)$aVersion['id'],
								'sent'=>$aAttachments[$aVersion['path']]
							);
					DB::executePreparedQuery($sSql, $aSql);

				}

			}

		## ENDE


		return true;
	}

}
