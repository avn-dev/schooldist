<?php

/**
 * Check schreibt die Alten Specials in die neue Struktur (Daten hierfür müssen zZ. "geraten" werden :))
 */
class Ext_Thebing_System_Checks_SpecialCache extends GlobalChecks
{

	protected $_aErrors = array();


	public function getTitle()
	{
		$sTitle = 'Initialize Special Caching';
		return $sTitle;
	}

	public function getDescription()
	{
		$sDescription = 'Initialize Special Caching';
		return $sDescription;
	}

	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		try{	
			Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents_versions_items');

		}catch(Exception $exc){
			#return true;
		}
		
		
		// Alle Items holen die umgeschrieben werden sollen
		$sSql = "SELECT
						`kidvi`.*,
						`kid`.`inquiry_id`		`inquiry_id`,
						`ts_i_j`.`id`			`journey_id`,
						`kidv`.`date`			`version_date`,
						DATE(`kidv`.`created`)	`version_created`
					FROM
						`kolumbus_inquiries_documents_versions_items` `kidvi` INNER JOIN
						`kolumbus_inquiries_documents_versions` `kidv` ON
							`kidv`.`id` = `kidvi`.`version_id` INNER JOIN
						`kolumbus_inquiries_documents` `kid` ON
							`kid`.`id` = `kidv`.`document_id` INNER JOIN
						`ts_inquiries` `ts_i` ON
							`ts_i`.`id` = `kid`.`inquiry_id` INNER JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`inquiry_id` = `ts_i`.`id`
					WHERE
						`kidvi`.`type` = 'special' AND
						`kidvi`.`parent_type` != 'item_id' 

					ORDER BY
						`kid`.`inquiry_id` DESC
		";
		
		$aSql = array();
		
		$oDB = DB::getDefaultConnection();	
		$oCollection = $oDB->getCollection($sSql, $aSql);

		$this->_aErrors['all'] = count($oCollection);
		
		$aSql = array();
		
		foreach($oCollection as $sKey => $aRowData){

			$id = 0;
			switch($aRowData['parent_type']){
				case 'course':
				case 'accommodation':
					$sSql = "SELECT
									`id`
								FROM
									`kolumbus_inquiries_documents_versions_items`
								WHERE
									`type_id` = :type_id AND
									`type` = :type AND
									`version_id` = :version_id
							";
					$aSql['type_id']	= (int)$aRowData['parent_id'];
					$aSql['type']		= $aRowData['parent_type'];
					$aSql['version_id']	= (int)$aRowData['version_id'];
					$id = DB::getQueryOne($sSql, $aSql);
					
					break;
				case 'additional_course':
					$sSql = "SELECT
									`ts_i_j_c`.`id`
								FROM
									`ts_inquiries_journeys_courses` `ts_i_j_c` INNER JOIN
									`ts_inquiries_journeys` `ts_i_j` ON
										`ts_i_j`.`id` = `ts_i_j_c`.`journey_id` AND
										`ts_i_j`.`inquiry_id` = :inquiry_id
								WHERE
									`ts_i_j_c`.`active` = 1 AND
									`ts_i_j_c`.`visible` = 1
							";
					$aSql['inquiry_id'] = (int)$aRowData['inquiry_id'];
					
					$aCourses = DB::getPreparedQueryData($sSql, $aSql);
					
					// Erste Position suchen die auf der Rechnung war und passt
					foreach($aCourses as $aCourse){
						$sSql = "SELECT
										`id`
									FROM
										`kolumbus_inquiries_documents_versions_items`
									WHERE
										`version_id` = :version_id AND
										`type` = 'additional_course' AND
										`parent_booking_id` = :type_id
									LIMIT 1";
						$aSql['version_id'] = (int)$aRowData['version_id'];
						$aSql['type_id'] = (int)$aCourse['id'];
						$id = DB::getQueryOne($sSql, $aSql);
						
						if($id > 0){
							break;
						}
					}
					
					break;
				case 'additional_accommodation':
					$sSql = "SELECT
									`ts_i_j_a`.`id`
								FROM
									`ts_inquiries_journeys_accommodations` `ts_i_j_a` INNER JOIN
									`ts_inquiries_journeys` `ts_i_j` ON
										`ts_i_j`.`id` = `ts_i_j_a`.`journey_id` AND
										`ts_i_j`.`inquiry_id` = :inquiry_id
								WHERE
									`ts_i_j_a`.`active` = 1 AND
									`ts_i_j_a`.`visible` = 1
							";
					$aSql['inquiry_id'] = (int)$aRowData['inquiry_id'];
					
					$aAccommodations = DB::getPreparedQueryData($sSql, $aSql);
					
					// Erste Position suchen die auf der Rechnung war und passt
					foreach($aAccommodations as $aAccommodation){
						$sSql = "SELECT
										`id`
									FROM
										`kolumbus_inquiries_documents_versions_items`
									WHERE
										`version_id` = :version_id AND
										`type` = 'additional_accommodation' AND
										`parent_booking_id` = :type_id
									LIMIT 1";
						$aSql['version_id'] = (int)$aRowData['version_id'];
						$aSql['type_id'] = (int)$aAccommodation['id'];
						$id = DB::getQueryOne($sSql, $aSql);
						
						if($id > 0){
							break;
						}
					}
					break;
				case 'transfer':
					// kommt nicht vor
					break;
			}
			
			$sSql = "UPDATE
							`kolumbus_inquiries_documents_versions_items`
						SET
							`parent_type` = 'item_id',
							`parent_id` = :parent_id
						WHERE
							`id` = :id
					";
			$aSql['parent_id'] = (int)$id;
			$aSql['id'] = (int)$aRowData['id'];
			DB::executePreparedQuery($sSql, $aSql);
		}
		
		
		#$this->_reportError();

		return true;
	}

	
	protected function _reportError()
	{
		$oMail = new WDMail();
		$oMail->subject = 'Inquiry Statistic Cache';

		$sText = '';
		$sText = $_SERVER['HTTP_HOST']."\n\n";
		$sText .= date('Y-m-d H:i:s')."\n\n";
		$sText .= print_r($this->_aErrors, 1)."\n\n";

		$oMail->text = $sText;

		$oMail->send(array('developer@thebing.com'));
	}
}