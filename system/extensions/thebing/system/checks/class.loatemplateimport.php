<?
class Ext_Thebing_System_Checks_LoaTemplateImport extends GlobalChecks {

	public function executeCheck(){
		global $user_data, $_VARS;

		/*
		if(!hasRight('modules_admin')){
			$this->_aFormErrors[] = 'Only an full CMS Administrator hast the Right to start this Script!';
			return false;
		}
		*/

		// Alle Clients
		$sSql = "SELECT `id` FROM `kolumbus_clients` WHERE `active` = 1";
		$aClients = DB::getQueryData($sSql);

		foreach((array)$aClients as $aClient){
			$aSql = array();

			$oClient = new Ext_Thebing_Client($aClient['id']);
			// Alle Schulen für diesen Client
			$sSql = "SELECT id FROM customer_db_2 WHERE active = 1 AND idClient = " . (int)$aClient['id'];
			$aSchools = DB::getQueryData($sSql);

			foreach((array)$aSchools as $aSchool){

				$sSql = "SELECT
							`kid`.*, `cdb1`.`ext_27` `language`
						FROM
							`kolumbus_inquiries_documents` `kid` INNER JOIN
							`kolumbus_inquiries` `ki` ON
								`kid`.`inquiry_id` = `ki`.`id` AND
								`ki`.`crs_partnerschool` = ".(int)$aSchool['id']." INNER JOIN
							`customer_db_1` AS `cdb1` ON
								`cdb1`.`id` = `ki`.`idUser`
						WHERE
							`kid`.`type` = 'loa'
						";

				$aResult = DB::getQueryData($sSql);

				foreach((array)$aResult as $aDoc){

					if($aDoc['language'] == '') {
						$aDoc['language'] = 'en';
					}

					$sSql = "SELECT
								 `kpt`.`id`
								FROM
									 `kolumbus_pdf_templates` AS `kpt` INNER JOIN
									 `kolumbus_pdf_templates_languages` AS `kptl` ON
									 `kptl`.`template_id` = `kpt`.`id` INNER JOIN
									 `kolumbus_pdf_templates_schools` AS `kpts` ON
									 `kpts`.`template_id` = `kpt`.`id`
								WHERE
									`kpt`.`type` = 'document_loa' AND
									`kpt`.`client_id` = :client AND
									`kpt`.`active` = 1 AND
									`kpts`.`school_id` = :school_id AND
									`kptl`.`iso_language` = :iso_language
								GROUP BY
									`kpt`.`id`
								ORDER BY
									`kpt`.`changed` DESC
								LIMIT 1";
					$aSql['client'] = (int)$aClient['id'];
					$aSql['school_id'] = (int)$aSchool['id'];
					$aSql['iso_language'] = (int)$aDoc['language'];

					$aTemplates = DB::getPreparedQueryData($sSql, $aSql);

					$aTemplate = reset($aTemplates);
					$iTemplateId = (int)$aTemplate['id'];

					$sWhere = '`id` = '.(int)$aDoc['id'];
					DB::updateData('kolumbus_inquiries_documents', array('type' => 'additional_document', 'document_number' => ''), $sWhere);

					$sWhere = '`document_id` = '.(int)$aDoc['id'];
					DB::updateData('kolumbus_inquiries_documents_versions', array('template_id' => (int)$iTemplateId), $sWhere);

				}

			}

		}

		return true;
	}

}
