<?php

class Ext_Thebing_System_Checks_Visumnumber extends GlobalChecks
{
	public function isNeeded(){
		global $user_data;

		return true;

	}
	
	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		// Alle Schulen holen
		$sSql = "SELECT
						`id`
					FROM
						`customer_db_2`
					WHERE
						`active` = 1
				";

		$aSchools = DB::getQueryData($sSql); 

		foreach((array)$aSchools as $aSchool){

			// Visa Typen
			$sSql = "SELECT
							`id`
						FROM
							`kolumbus_visum_status`
						WHERE
							`school_id` = :school_id AND
							`active` = 1
					";

			$aSql = array();
			$aSql['school_id'] = (int)$aSchool['id'];

			$aVisaTypes = DB::getPreparedQueryData($sSql, $aSql);

			// Dokumente holen
			$sSql = "SELECT
						`kid`.`id` `id`
					FROM
						`kolumbus_inquiries_documents` `kid` INNER JOIN
						`kolumbus_inquiries` `ki` ON
							`kid`.`inquiry_id` = `ki`.`id` INNER JOIN
						`kolumbus_inquiries_documents_versions` `kidv` ON
							`kidv`.`document_id` = `kid`.`id` INNER JOIN
						`kolumbus_pdf_templates` `kpt` ON
								`kpt`.`id` = `kidv`.`template_id`
					WHERE
						`kpt`.`type` = 'document_studentrecord_visum_pdf' AND
						`kid`.`active` = 1 AND
						`ki`.`active` = 1 AND
						`kid`.`document_number` != '' AND
						`kid`.`type` = 'additional_document' AND
						`ki`.`crs_partnerschool` = :school_id 
					GROUP BY
						`kid`.`document_number`
				";

			$aSql = array();
			$aSql['school_id'] = (int)$aSchool['id'];
			$aDocumentData = DB::getPreparedQueryData($sSql, $aSql);




			foreach((array)$aVisaTypes as $aVisa){

				foreach((array)$aDocumentData as $aDocument){

					$sSql = "INSERT INTO
									`kolumbus_inquiries_additional_documents_relation`
								SET
									`document_id`			= :document_id,
									`additional_type_id`	= :additional_type_id,
									`additional_type`		= :additional_type
							";

					$aSql = array();
					$aSql['document_id']			= (int)$aDocument['id'];
					$aSql['additional_type_id']		= (int)$aVisa['id'];
					$aSql['additional_type']		= 'visum';

					DB::executePreparedQuery($sSql, $aSql);

				}
			}


		}


		

		return true;
	}

	public function getTitle()
	{
		return 'Visum Number';
	}

	public function getDescription()
	{
		return 'Import visum number range into new database structure.';
	}
}