<?php

/**
 * Check wurde für ELC verändert, sodass Items ab 2016 neue Daten bekommen!
 *
 *
 *
 * Check schreibt die Version-Items (um), hier kommen spalten hin zu die für die Statistiken notwendig sind (Caching)
 */
class Ext_Thebing_System_Checks_StatisticCache extends GlobalChecks
{

	protected $_aErrors = array();


	public function getTitle()
	{
		$sTitle = 'Initialize Statistic Caching';
		return $sTitle;
	}

	public function getDescription()
	{
		$sDescription = 'Initialize Statistic Caching';
		return $sDescription;
	}

	public function executeCheck()
	{
		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		try{	
			Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents_versions_items');
			
			// Document Caching Table
			$sSql = "ALTER TABLE 
								`kolumbus_inquiries_documents_versions_items` 
							ADD 
								`index_from` DATE NOT NULL AFTER `version_id` ,
							ADD 
								`index_until` DATE NOT NULL AFTER `index_from` 
			";
			
			DB::executeQuery($sSql);
		}catch(Exception $exc){
			#return true;
		}
		
		
		// Alle Items holen die umgeschrieben werden sollen
		$sSql = "SELECT
						`kidvi`.*,
						`kid`.`inquiry_id`		`inquiry_id`,
						`ts_i_j`.`id`			`journey_id`,
						`kidv`.`date`			`version_date`,
						DATE(`kidv`.`created`)	`version_created`,
						`kidvi`.`index_from`,
						`kidvi`.`index_until`,
						`kidvi`.`description`
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
						`kidv`.`created` > '2016-01-01'

					ORDER BY
						`kid`.`inquiry_id` DESC

		";

		$aSql = array();

		$oDB = DB::getDefaultConnection();
		$oCollection = $oDB->getCollection($sSql, $aSql);

		$this->_aErrors['all'] = count($oCollection);

		foreach($oCollection as $sKey => $aRowData){

			$sFrom = $sUntul = '';
			$aData = array();

			switch($aRowData['type']){
				case 'course':
					// Kursdatum ermitteln

					$sSql = "SELECT
									`from`,
									`until`
								FROM
									`ts_inquiries_journeys_courses`
								WHERE
									`id` = :item_id
							";
					$aSql['item_id'] = (int)$aRowData['type_id'];
					$aData = DB::getQueryRow($sSql, $aSql);
					break;
				case 'accommodation':
				case 'extra_nights':
				case 'extra_weeks':
					// Unterkunftsdatum ermitteln

					$sSql = "SELECT
									`from`,
									`until`
								FROM
									`ts_inquiries_journeys_accommodations`
								WHERE
									`id` = :item_id
							";
					$aSql['item_id'] = (int)$aRowData['type_id'];
					$aData = DB::getQueryRow($sSql, $aSql);
					break;
				case 'additional_course':

					// Kursdatum Zusastzkosten ermitteln
					$sSql = "SELECT
									`from`,
									`until`
								FROM
									`ts_inquiries_journeys_courses`
								WHERE
									`id` = :item_id
							";
					$aSql['item_id'] = (int)$aRowData['parent_booking_id'];
					$aData = DB::getQueryRow($sSql, $aSql);

					// Wird hier NIX gefunden (ganz alte Kurse), dann suche den ERSTEN Kurs und weise dort alles zu
					if(empty($aData)){
						$sSql = "SELECT
									`from`,
									`until`
								FROM
									`ts_inquiries_journeys_courses`
								WHERE
									`journey_id` = :journey_id
								LIMIT 1
							";
						$aSql['journey_id'] = (int)$aRowData['journey_id'];

						$aData = DB::getQueryRow($sSql, $aSql);

					}
					break;
				case 'additional_accommodation':
					// Unterkunftsdatum Zusastzkosten ermitteln

					$sSql = "SELECT
									`from`,
									`until`
								FROM
									`ts_inquiries_journeys_accommodations`
								WHERE
									`id` = :item_id
							";
					$aSql['item_id'] = (int)$aRowData['parent_booking_id'];
					$aData = DB::getQueryRow($sSql, $aSql);

					// Wird hier NIX gefunden (ganz alte Kurse), dann suche den ERSTEN Kurs und weise dort alles zu
					if(empty($aData)){
						$sSql = "SELECT
									`from`,
									`until`
								FROM
									`ts_inquiries_journeys_accommodations`
								WHERE
									`journey_id` = :journey_id
								LIMIT 1
							";
						$aSql['journey_id'] = (int)$aRowData['journey_id'];

						$aData = DB::getQueryRow($sSql, $aSql);
					}
					break;
				case 'additional_general':
				case 'extraPosition':
				case 'storno':
				case 'paket':
					$aData = $this->_getMinMaxDates($aRowData['journey_id']);
					break;
				case 'insurance':
					$sSql = "SELECT
									`from`,
									`until`
								FROM
									`ts_inquiries_journeys_insurances`
								WHERE
									`id` = :item_id
							";
					$aSql['item_id'] = (int)$aRowData['type_id'];
					$aData = DB::getQueryRow($sSql, $aSql);
					break;
				case 'special':
					continue;
					// So wurden die positionen FRÜHER gespeichert beim special. Jetzt sind sie anders veknüpft.
					switch($aRowData['parent_type']){
						case 'item_id':
							// Kursdatum ermitteln
							$sSql = "SELECT
											`from`,
											`until`
										FROM
											`ts_inquiries_journeys_courses`
										WHERE
											`id` = :item_id
									";
							$aSql['item_id'] = (int)$aRowData['parent_id'];
							$aData = DB::getQueryRow($sSql, $aSql);

							break;
						case 'course':
							// Kursdatum ermitteln
							$sSql = "SELECT
											`from`,
											`until`
										FROM
											`ts_inquiries_journeys_courses`
										WHERE
											`id` = :item_id
									";
							$aSql['item_id'] = (int)$aRowData['parent_id'];
							$aData = DB::getQueryRow($sSql, $aSql);

							break;
						case 'accommodation':
							$sSql = "SELECT
											`from`,
											`until`
										FROM
											`ts_inquiries_journeys_accommodations`
										WHERE
											`id` = :item_id
									";
							$aSql['item_id'] = (int)$aRowData['parent_id'];
							$aData = DB::getQueryRow($sSql, $aSql);
							break;
						case 'transfer':
							break;
						case 'additional_course':
							break;
						case 'additional_accommodation':
							break;
					}
					break;
				case 'transfer':

					if($aRowData['type_id'] == 0){
						// An/Abreise -> Ein Paket
						$sSql = "SELECT
									`transfer_date`
								FROM
									`ts_inquiries_journeys_transfers`
								WHERE
									`journey_id` = :journey_id AND
									`transfer_type` = 1
							";
						$aSql['journey_id'] = (int)$aRowData['journey_id'];
						$aResult = DB::getQueryRow($sSql, $aSql);
						$aData['from'] = $aResult['transfer_date'];

						$sSql = "SELECT
									`transfer_date`
								FROM
									`ts_inquiries_journeys_transfers`
								WHERE
									`journey_id` = :journey_id AND
									`transfer_type` = 2
							";
						$aSql['journey_id'] = (int)$aRowData['journey_id'];
						$aResult = DB::getQueryRow($sSql, $aSql);
						$aData['until'] = $aResult['transfer_date'];
					}else{
						// "normaler" Transfer
						$sSql = "SELECT
									`transfer_date`
								FROM
									`ts_inquiries_journeys_transfers`
								WHERE
									`id` = :item_id
							";
						$aSql['item_id'] = (int)$aRowData['type_id'];
						$aData = DB::getQueryRow($sSql, $aSql);

						// Selbes Datum
						$aData['from'] = $aData['transfer_date'];
						$aData['until'] = $aData['transfer_date'];
					}
					break;
			}

			$bUseVersionDate = false;
			$bUseCreatedDate = false;

			// Gültichkeit der Daten prüfen
			if(
				!isset($aData['from']) ||
				!WDDate::isDate($aData['from'], WDDate::DB_DATE) ||
				$aData['from'] == '0000-00-00'
			){
				// Rechnungsdatum
				$aData['from'] = $aRowData['version_date'];
				$bUseVersionDate = true;

				$this->_aErrors['missing_data'][$aRowData['inquiry_id']][$aRowData['type']]['from'][] = $aRowData['id'];

				if(
					!WDDate::isDate($aData['from'], WDDate::DB_DATE) ||
					$aData['from'] == '0000-00-00'
				){
					$aData['from'] = $aRowData['version_created'];
					$bUseCreatedDate = true;
				}

			}

			if(
				!isset($aData['until']) ||
				!WDDate::isDate($aData['until'], WDDate::DB_DATE) ||
				$aData['until'] == '0000-00-00'
			){
				$aData['until'] = $aRowData['version_date'];
				$bUseVersionDate = true;

				$this->_aErrors['missing_data'][$aRowData['inquiry_id']][$aRowData['type']]['until'][] = $aRowData['id'];


				if(
					!WDDate::isDate($aData['until'], WDDate::DB_DATE) ||
					$aData['until'] == '0000-00-00'
				){
					$aData['until'] = $aRowData['version_created'];
					$bUseCreatedDate = true;
				}

			}

			if($bUseVersionDate){
				$this->_aErrors['version_date_used']++;
			}

			if($bUseCreatedDate){
				$this->_aErrors['version_created_used']++;
			}

			// Zeit speichern

			if(
				$aRowData['index_from'] != $aData['from'] &&
				$aRowData['index_until'] != $aData['until'] &&
				$bUseVersionDate === false &&
				$bUseCreatedDate === false
			) {
				$this->_aErrors['difference']++;
				#__pout($aRowData);
				#__pout($aData);

				$sSql = "UPDATE
								`kolumbus_inquiries_documents_versions_items`
							SET
								`index_from` = :from,
								`index_until` = :until
							WHERE
								`id` = :id
						";

				$aSql['from']	= $aData['from'];
				$aSql['until']	= $aData['until'];
				$aSql['id']		= (int)$aRowData['id'];

				DB::executePreparedQuery($sSql, $aSql);

			}

			if(0) {

				$sSql = "UPDATE
								`kolumbus_inquiries_documents_versions_items`
							SET
								`index_from` = :from,
								`index_until` = :until
							WHERE
								`id` = :id
						";

				$aSql['from']	= $aData['from'];
				$aSql['until']	= $aData['until'];
				$aSql['id']		= (int)$aRowData['id'];

				DB::executePreparedQuery($sSql, $aSql);

			}

			$this->_aErrors['updated']++;
		}
		#__pout($this->_aErrors);
//		$this->_reportError();

		return true;
	}

	protected function _getMinMaxDates($iJourneyId){
		
		$aSql = array();
		$aSql['journey_id'] = (int)$iJourneyId;
		
		$sSql = "
				SELECT 
					`x`.`date`
				FROM
					(
						SELECT
							MIN(`from`) AS `date`
						FROM
							`ts_inquiries_journeys_courses`
						WHERE
							`active`		= 1 AND
							`visible`		= 1 AND
							`journey_id`	= :journey_id
					UNION ALL
						SELECT
							MIN(`from`) AS `date`
						FROM
							`ts_inquiries_journeys_accommodations`
						WHERE
							`active`		= 1 AND
							`visible`		= 1 AND
							`journey_id`	= :journey_id
					UNION ALL
						SELECT
							MIN(`transfer_date`) AS `date`
						FROM
							`ts_inquiries_journeys_transfers`
						WHERE
							`active`		= 1 AND
							`booked`		= 1 AND
							`transfer_type`	> 0 AND
							`journey_id`	= :journey_id
					) `x`
						HAVING
							`x`.`date` > 0
						ORDER BY
							`x`.`date` 
					
				";
		
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$sFrom = $aResult[0]['date'];
		
		$sSql = "SELECT 
					`x`.`date`
				FROM
					(
						SELECT
							MAX(`until`) AS `date`
						FROM
							`ts_inquiries_journeys_courses`
						WHERE
							`active`		= 1 AND
							`visible`		= 1 AND
							`journey_id`	= :journey_id
					UNION ALL
						SELECT
							MAX(`until`) AS `date`
						FROM
							`ts_inquiries_journeys_accommodations`
						WHERE
							`active`		= 1 AND
							`visible`		= 1 AND
							`journey_id`	= :journey_id
					UNION ALL
						SELECT
							MAX(`transfer_date`) AS `date`
						FROM
							`ts_inquiries_journeys_transfers`
						WHERE
							`active`		= 1 AND
							`booked`		= 1 AND
							`transfer_type`	> 0 AND
							`journey_id`	= :journey_id
					) `x`
						HAVING
							`x`.`date` > 0
						ORDER BY
							`x`.`date` 
				";

		
		
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		$sUntil = $aResult[0]['date'];

		$aBack = array();
		$aBack['from'] = $sFrom;
		$aBack['until'] = $sUntil;

		return $aBack;
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