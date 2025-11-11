<?php


class Ext_Thebing_System_Checks_InquiryCache extends GlobalChecks {

	protected $_aErrors = array();

	public function getTitle() {
		$sTitle = 'Initialize Inquiry Cache';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Initialize Inquiry Cache';
		return $sDescription;
	}

	public function executeCheck(){

		set_time_limit(3600);
		ini_set("memory_limit", '1024M');

		try{	
			
			$bExistsTable = Ext_Thebing_Util::checkTableExists('ts_inquiries_documents_index');
			
			if($bExistsTable){
				$sSql = "DROP TABLE `ts_inquiries_documents_index` ";
			}
			
			// Document Caching Table
			$sSql = "CREATE TABLE IF NOT EXISTS `ts_inquiries_documents_index` (
					  `id` int(11) NOT NULL auto_increment,
					  `inquiry_id` mediumint(9) NOT NULL,
					  `document_count` mediumint(9) NOT NULL,
					  `last_document_number` varchar(255) NOT NULL,
					  `last_document_date` date NOT NULL,
					  `last_creditnote_number` varchar(255) NOT NULL,
					  `last_gross_pdf` varchar(255) NOT NULL,
					  `last_gross_sent` timestamp NOT NULL default '0000-00-00 00:00:00',
					  `last_netto_pdf` varchar(255) NOT NULL,
					  `last_netto_sent` timestamp NOT NULL default '0000-00-00 00:00:00',
					  `last_loa_pdf` varchar(255) NOT NULL,
					  `last_loa_sent` timestamp NOT NULL default '0000-00-00 00:00:00',
					  `last_studentcard_pdf` varchar(255) NOT NULL,
					  `last_certificate_pdf` varchar(255) NOT NULL,
					  PRIMARY KEY  (`id`),
					  KEY `inquiry_id` (`inquiry_id`)
					) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;
			";
			DB::executeQuery($sSql);
			
			$sSql = "TRUNCATE TABLE `ts_inquiries_documents_index` ";
			DB::executeQuery($sSql);
			
			// Caching Spalten anlegen Inquiry
			$sSql = "ALTER TABLE `ts_inquiries` ADD `amount_prepay_due` DATE NOT NULL ,
						ADD `amount_finalpay_due` DATE NOT NULL ,
						ADD `amount_prepay` DECIMAL( 15, 5 ) NOT NULL 
					";
			DB::executeQuery($sSql);			
			
			// TransferDaten cachen
			$sSql = "ALTER TABLE `ts_inquiries` ADD `arrival_date` DATE NOT NULL ,
					ADD `departure_date` DATE NOT NULL ";
			DB::executeQuery($sSql);
			
			
		}catch(Exception $exc){
			#return true;
		}

		
		// amount_finalpay_due füllen	
		$sSql = "SELECT
						`ts_i`.`id` `id`,
						MIN(`kidv`.`amount_finalpay_due`) `amount_finalpay_due`
					FROM
						`ts_inquiries` `ts_i` INNER JOIN
						`kolumbus_inquiries_documents` `kid` INNER JOIN
						`kolumbus_inquiries_documents_versions` `kidv` ON
							`kid`.`id` = `kidv`.`document_id` AND
							`kidv`.`active` = 1 AND
							`kidv`.`id` = `kid_ni`.`latest_version`
					WHERE
						`kid`.`inquiry_id` = `ts_i`.`id` AND
						`kid`.`active` = 1 AND
						`kidv`.`amount_finalpay_due` > 0 AND
						`kid`.`type` IN (
											'brutto',
											'netto',
											'brutto_diff',
											'brutto_diff_special',
											'netto_diff',
											'credit_brutto',
											'credit_netto'
											)
					GROUP BY `ts_i`.`id`
				";
		$aData = DB::getQueryData($sSql);
		
		
		foreach($aData as $aResult){
			$sSql = "UPDATE
							`ts_inquiries`
						SET
							`amount_finalpay_due` = :date
						WHERE
							`id` = :id
			";
			$aSql = array();
			$aSql['id'] = (int)$aResult['id'];
			$aSql['date'] = $aResult['amount_finalpay_due'];
			
			DB::executePreparedQuery($sSql, $aSql);
		}
		
		// amount_prepay_due füllen
		$sSql = "SELECT
						`ts_i`.`id` `id`,
						MIN(`kidv`.`amount_prepay_due`) `amount_prepay_due`
					FROM
						`ts_inquiries` `ts_i` INNER JOIN
						`kolumbus_inquiries_documents` `kid` INNER JOIN
						`kolumbus_inquiries_documents_versions` `kidv` ON
							`kid`.`id` = `kidv`.`document_id` AND
							`kidv`.`active` = 1 AND
							`kidv`.`id` = `kid_ni`.`latest_version`
					WHERE
						`kid`.`inquiry_id` = `ts_i`.`id` AND
						`kid`.`active` = 1 AND
						`kidv`.`amount_prepay_due` > 0 AND
						`kid`.`type` IN (
											'brutto',
											'netto',
											'brutto_diff',
											'brutto_diff_special',
											'netto_diff',
											'credit_brutto',
											'credit_netto'
											)
					GROUP BY `ts_i`.`id`
				";
		$aData = DB::getQueryData($sSql);
		
		foreach($aData as $aResult){
			$sSql = "UPDATE
							`ts_inquiries`
						SET
							`amount_prepay_due` = :date
						WHERE
							`id` = :id
			";
			$aSql = array();
			$aSql['id'] = (int)$aResult['id'];
			$aSql['date'] = $aResult['amount_prepay_due'];
			
			DB::executePreparedQuery($sSql, $aSql);
		}
		
		//amount_prepay füllen
		$sSql = "SELECT
						`ts_i`.`id` `id`,
						SUM(`kidv`.`amount_prepay`) `amount_prepay`
					FROM
						`ts_inquiries` `ts_i` INNER JOIN
						`kolumbus_inquiries_documents` `kid` INNER JOIN
						`kolumbus_inquiries_documents_versions` `kidv` ON
							`kid`.`id` = `kidv`.`document_id` AND
							`kidv`.`active` = 1 AND
							`kidv`.`id` = `kid_ni`.`latest_version`
					WHERE
						`kid`.`inquiry_id` = `ts_i`.`id` AND
						`kid`.`active` = 1 AND
						`kidv`.`amount_prepay` > 0 AND
						`kid`.`type` IN (
											'brutto',
											'netto',
											'brutto_diff',
											'brutto_diff_special',
											'netto_diff',
											'credit_brutto',
											'credit_netto'
											)
					GROUP BY `ts_i`.`id`
				";
		$aData = DB::getQueryData($sSql);
		
		foreach($aData as $aResult){
			$sSql = "UPDATE
							`ts_inquiries`
						SET
							`amount_prepay` = :date
						WHERE
							`id` = :id
			";
			$aSql = array();
			$aSql['id'] = (int)$aResult['id'];
			$aSql['date'] = $aResult['amount_prepay'];
			
			DB::executePreparedQuery($sSql, $aSql);
		}
		
		// Netto PDF Daten
		
		$sSql = "SELECT
							`ts_i`.`id` `id`,
							`kidv`.`id` `v_id`,
							`kidv`.`sent` `sent`,
							`kidv`.`path` `path`
						FROM
							`ts_inquiries` `ts_i` INNER JOIN
							`kolumbus_inquiries_documents` `kid_ni` ON
								`kid_ni`.`inquiry_id` = `ts_i`.`id` AND 
								`kid_ni`.`active` = 1 AND
								`kid_ni`.`type` = 'netto' AND
								`kid_ni`.`created` = (
													SELECT
														MAX(`created`)
													FROM
														`kolumbus_inquiries_documents` 
													WHERE
														`active` = 1 AND
														`type` = 'netto' AND
														`inquiry_id` = `kid_ni`.`inquiry_id`

								)INNER JOIN
							`kolumbus_inquiries_documents_versions` `kidv` ON
								`kidv`.`document_id` = `kid_ni`.`id` AND
								`kidv`.`active` = 1 AND
								`kidv`.`id` = `kid_ni`.`latest_version`
						GROUP BY
							`ts_i`.`id`
		";
		
		$aData = DB::getQueryData($sSql);
		
		foreach($aData as $aResult){
			$sSql = "INSERT INTO
							`ts_inquiries_documents_index`
						SET
							`inquiry_id` = :inquiry_id,
							`last_netto_pdf` = :path,
							`last_netto_sent` = :sent
			";
			$aSql = array();
			$aSql['inquiry_id']		= (int)$aResult['id'];
			$aSql['path']			= $aResult['path'];
			$aSql['sent']			= $aResult['sent'];
			
			DB::executePreparedQuery($sSql, $aSql);
		}
		// Brutto PDF Daten	
		$sSql = "SELECT
							`ts_i`.`id` `id`,
							`kidv`.`id` `v_id`,
							`kidv`.`sent` `sent`,
							`kidv`.`path` `path`
						FROM
							`ts_inquiries` `ts_i` INNER JOIN
							`kolumbus_inquiries_documents` `kid_ni` ON
								`kid_ni`.`inquiry_id` = `ts_i`.`id` AND 
								`kid_ni`.`active` = 1 AND
								`kid_ni`.`type` = 'brutto' AND
								`kid_ni`.`created` = (
													SELECT
														MAX(`created`)
													FROM
														`kolumbus_inquiries_documents` 
													WHERE
														`active` = 1 AND
														`type` = 'brutto' AND
														`inquiry_id` = `kid_ni`.`inquiry_id` 
								)INNER JOIN
							`kolumbus_inquiries_documents_versions` `kidv` ON
								`kidv`.`document_id` = `kid_ni`.`id` AND
								`kidv`.`active` = 1 AND
								`kidv`.`id` = `kid_ni`.`latest_version`
						GROUP BY
							`ts_i`.`id`
		";
		
		$aData = DB::getQueryData($sSql);
		
		foreach($aData as $aResult){
			
			$aSql = array();
			$aSql['inquiry_id']		= (int)$aResult['id'];
			$aSql['path']			= $aResult['path'];
			$aSql['sent']			= $aResult['sent'];
			
			$sSql = "SELECT
							*
						FROM
							`ts_inquiries_documents_index`
						WHERE
							`inquiry_id` = :inquiry_id";
			
			$aTempResult = DB::getPreparedQueryData($sSql, $aSql);
	
			if(empty($aTempResult)){
				$sSql = "INSERT INTO ";
				$sWhere = "";
			}else{
				$sSql = "UPDATE ";
				
				$sWhere = " WHERE
								`inquiry_id` = :inquiry_id";
			}
			
			$sSql .= "		`ts_inquiries_documents_index`
						SET
							`inquiry_id` = :inquiry_id,
							`last_gross_pdf` = :path,
							`last_gross_sent` = :sent
				" . $sWhere;

			DB::executePreparedQuery($sSql, $aSql);
		}
		
		// LOA PDF
		$sSql = "SELECT
							`ts_i`.`id` `id`,
							`kidv_ni`.`sent` `sent`,
							`kidv_ni`.`path` `path`
						FROM
							`ts_inquiries` `ts_i` INNER JOIN
							`kolumbus_inquiries_documents` `kid_ni` ON
								`kid_ni`.`inquiry_id` = `ts_i`.`id` AND
								`kid_ni`.`active` = 1 INNER JOIN
							`kolumbus_inquiries_documents_versions` `kidv_ni` ON
								`kid_ni`.`id` = `kidv_ni`.`document_id` AND
								`kidv_ni`.`active` = 1 AND
								`kidv_ni`.`id` = (
									SELECT
										MAX(`kidv_ni_i`.`id`)
									FROM
										`kolumbus_inquiries_documents` `kid_ni_i` JOIN
										`kolumbus_inquiries_documents_versions` `kidv_ni_i` ON
											`kid_ni_i`.`id` = `kidv_ni_i`.`document_id` AND
											`kidv_ni_i`.`active` = 1 JOIN
										`kolumbus_pdf_templates` `kpt_ni_i` ON
											`kpt_ni_i`.`id` = `kidv_ni_i`.`template_id`
									WHERE
										`kid_ni_i`.`inquiry_id` = `ts_i`.`id` AND
										`kid_ni_i`.`active` = 1 AND
										`kpt_ni_i`.`type` = 'document_loa'
								) JOIN
							`kolumbus_pdf_templates` `kpt_ni` ON
								`kpt_ni`.`id` = `kidv_ni`.`template_id`
						GROUP BY
							`ts_i`.`id`
		";
		
		$aData = DB::getQueryData($sSql);

		foreach($aData as $aResult){
			
			$aSql = array();
			$aSql['inquiry_id']		= (int)$aResult['id'];
			$aSql['path']			= $aResult['path'];
			$aSql['sent']			= $aResult['sent'];
			
			$sSql = "SELECT
							*
						FROM
							`ts_inquiries_documents_index`
						WHERE
							`inquiry_id` = :inquiry_id";
			
			$aTempResult = DB::getPreparedQueryData($sSql, $aSql);
	
			if(empty($aTempResult)){
				$sSql = "INSERT INTO ";
				$sWhere = "";
			}else{
				$sSql = "UPDATE ";
				
				$sWhere = " WHERE
								`inquiry_id` = :inquiry_id";
			}
			
			$sSql .= "		`ts_inquiries_documents_index`
						SET
							`inquiry_id` = :inquiry_id,
							`last_loa_pdf` = :path,
							`last_loa_sent` = :sent
				" . $sWhere;

			DB::executePreparedQuery($sSql, $aSql);
		}
		
		# Student Card PDF
		$sSql = "
						SELECT
							`ts_i`.`id` `id`,
							`sub_kidv`.`path` `path`
						FROM
							`ts_inquiries` `ts_i` INNER JOIN
							`kolumbus_inquiries_documents` `sub_kid` ON
								`sub_kid`.`inquiry_id` = `ts_i`.`id` AND
								`sub_kid`.`active` = 1 AND
								`sub_kid`.`type` = 'additional_document' INNER JOIN
							`kolumbus_inquiries_documents_versions` `sub_kidv` ON
								`sub_kidv`.`document_id` = `sub_kid`.`id` AND
								`sub_kidv`.`active` = 1  INNER JOIN
							`kolumbus_pdf_templates` `sub_kpt` ON
								`sub_kpt`.`id` = `sub_kidv`.`template_id` AND
								`sub_kpt`.`type` = 'document_student_cards'	
		";
		
		$aData = DB::getQueryData($sSql);
		
		foreach($aData as $aResult){
			
			$aSql = array();
			$aSql['inquiry_id']		= (int)$aResult['id'];
			$aSql['path']			= $aResult['path'];
			
			$sSql = "SELECT
							*
						FROM
							`ts_inquiries_documents_index`
						WHERE
							`inquiry_id` = :inquiry_id";
			
			$aTempResult = DB::getPreparedQueryData($sSql, $aSql);
	
			if(empty($aTempResult)){
				$sSql = "INSERT INTO ";
				$sWhere = "";
			}else{
				$sSql = "UPDATE ";
				
				$sWhere = " WHERE
								`inquiry_id` = :inquiry_id";
			}
			
			$sSql .= "		`ts_inquiries_documents_index`
						SET
							`inquiry_id` = :inquiry_id,
							`last_studentcard_pdf` = :path
				" . $sWhere;

			DB::executePreparedQuery($sSql, $aSql);
		}
		
		# Certificates
		$sSql = "
						SELECT
							`ts_i`.`id` `id`,
							`sub_kidv`.`path` `path`
						FROM
							`ts_inquiries` `ts_i` INNER JOIN
							`kolumbus_inquiries_documents` `sub_kid` ON
								`sub_kid`.`inquiry_id` = `ts_i`.`id` AND
								`sub_kid`.`active` = 1 AND 
								`sub_kid`.`type` = 'additional_document' INNER JOIN
							`kolumbus_inquiries_documents_versions` `sub_kidv` ON
								`sub_kidv`.`document_id` = `sub_kid`.`id` AND
								`sub_kidv`.`active` = 1 INNER JOIN
							`kolumbus_pdf_templates` `sub_kpt` ON
								`sub_kpt`.`id` = `sub_kidv`.`template_id` AND
								`sub_kpt`.`type` = 'document_certificates'

		";
		
		$aData = DB::getQueryData($sSql);
			
		foreach($aData as $aResult){
			
			$aSql = array();
			$aSql['inquiry_id']		= (int)$aResult['id'];
			$aSql['path']			= $aResult['path'];
			
			$sSql = "SELECT
							*
						FROM
							`ts_inquiries_documents_index`
						WHERE
							`inquiry_id` = :inquiry_id";
			
			$aTempResult = DB::getPreparedQueryData($sSql, $aSql);
	
			if(empty($aTempResult)){
				$sSql = "INSERT INTO ";
				$sWhere = "";
			}else{
				$sSql = "UPDATE ";
				
				$sWhere = " WHERE
								`inquiry_id` = :inquiry_id";
			}
			
			$sSql .= "		`ts_inquiries_documents_index`
						SET
							`inquiry_id` = :inquiry_id,
							`last_certificate_pdf` = :path
				" . $sWhere;

			DB::executePreparedQuery($sSql, $aSql);
		}
		
		## Document Number
		$sSql = "SELECT
						`ts_i`.`id` `id`,
						`kid`.`document_number` `number`
					FROM
						`ts_inquiries` `ts_i` INNER JOIN
						`kolumbus_inquiries_documents` `kid` ON
							`ts_i`.`id` = `kid`.`inquiry_id` AND
							`kid`.`created` = (
								SELECT
									MAX(`created`)
								FROM
									`kolumbus_inquiries_documents` 
								WHERE
									`inquiry_id` = `kid`.`inquiry_id` AND
									`active` = 1 AND
									`type` IN (	'".implode("', '", (array)Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice'))."')
							)
					WHERE
						`kid`.`inquiry_id` = `ts_i`.`id` AND
						`kid`.`active` = 1 AND
						`kid`.`type` IN (	'".implode("', '", (array)Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice'))."')
				";
		
		$aData = DB::getQueryData($sSql);
		
		foreach($aData as $aResult){
			
			$aSql = array();
			$aSql['inquiry_id']		= (int)$aResult['id'];
			$aSql['number']			= $aResult['number'];
			
			$sSql = "SELECT
							*
						FROM
							`ts_inquiries_documents_index`
						WHERE
							`inquiry_id` = :inquiry_id";
			
			$aTempResult = DB::getPreparedQueryData($sSql, $aSql);
	
			if(empty($aTempResult)){
				$sSql = "INSERT INTO ";
				$sWhere = "";
			}else{
				$sSql = "UPDATE ";
				
				$sWhere = " WHERE
								`inquiry_id` = :inquiry_id";
			}
			
			$sSql .= "		`ts_inquiries_documents_index`
						SET
							`inquiry_id` = :inquiry_id,
							`last_document_number` = :number
				" . $sWhere;

			DB::executePreparedQuery($sSql, $aSql);
		}
		
		// Creditnote Number
		$sSql = "SELECT
						`ts_i`.`id` `id`,
						`kid`.`document_number` `number`
					FROM
						`ts_inquiries` `ts_i` INNER JOIN
						`kolumbus_inquiries_documents` `kid` ON
							`ts_i`.`id` = `kid`.`inquiry_id`  AND
							`kid`.`created` = (
								SELECT
									MAX(`created`)
								FROM
									`kolumbus_inquiries_documents` 
								WHERE
									`inquiry_id` = `kid`.`inquiry_id` AND
									`active` = 1 AND
									`type` IN (	'creditnote')
							)
					WHERE
						`kid`.`inquiry_id` = `ts_i`.`id` AND
						`kid`.`active` = 1 AND
						`kid`.`type` IN ('creditnote')
				";
		
		$aData = DB::getQueryData($sSql);
		
		foreach($aData as $aResult){
			
			$aSql = array();
			$aSql['inquiry_id']		= (int)$aResult['id'];
			$aSql['number']			= $aResult['number'];
			
			$sSql = "SELECT
							*
						FROM
							`ts_inquiries_documents_index`
						WHERE
							`inquiry_id` = :inquiry_id";
			
			$aTempResult = DB::getPreparedQueryData($sSql, $aSql);
	
			if(empty($aTempResult)){
				$sSql = "INSERT INTO ";
				$sWhere = "";
			}else{
				$sSql = "UPDATE ";
				
				$sWhere = " WHERE
								`inquiry_id` = :inquiry_id";
			}
			
			$sSql .= "		`ts_inquiries_documents_index`
						SET
							`inquiry_id` = :inquiry_id,
							`last_creditnote_number` = :number
				" . $sWhere;

			DB::executePreparedQuery($sSql, $aSql);
		}
		
		## Document Date
		$sSql = "SELECT
						`ts_i`.`id` `id`,
						`kidv`.`date` `date`
					FROM
						`ts_inquiries` `ts_i` INNER JOIN 
						`kolumbus_inquiries_documents` `kid` ON
							`kid`.`inquiry_id` = `ts_i`.`id` AND
							`kid`.`active` = 1 AND
							`kid`.`created` = (
												SELECT
													MAX(`created`)
												FROM
													`kolumbus_inquiries_documents` 
												WHERE
													`inquiry_id` = `kid`.`inquiry_id` AND
													`active` = 1 AND
													`type` IN (	'".implode("', '", (array)Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice'))."')
							
							) AND
							`kid`.`type` IN (	'".implode("', '", (array)Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice'))."') INNER JOIN
						`kolumbus_inquiries_documents_versions` `kidv` ON
							`kidv`.`document_id` = `kid`.`id` AND
							`kidv`.`active` = 1 AND
							`kidv`.`id` = `kid_ni`.`latest_version`
					";
		
		$aData = DB::getQueryData($sSql);
		
		foreach($aData as $aResult){
			
			$aSql = array();
			$aSql['inquiry_id']		= (int)$aResult['id'];
			$aSql['date']			= $aResult['date'];
			
			$sSql = "SELECT
							*
						FROM
							`ts_inquiries_documents_index`
						WHERE
							`inquiry_id` = :inquiry_id";
			
			$aTempResult = DB::getPreparedQueryData($sSql, $aSql);
	
			if(empty($aTempResult)){
				$sSql = "INSERT INTO ";
				$sWhere = "";
			}else{
				$sSql = "UPDATE ";
				
				$sWhere = " WHERE
								`inquiry_id` = :inquiry_id";
			}
			
			$sSql .= "		`ts_inquiries_documents_index`
						SET
							`inquiry_id` = :inquiry_id,
							`last_document_date` = :date
				" . $sWhere;

			DB::executePreparedQuery($sSql, $aSql);
		}
		
		
		// Vorhandene Dokumente cachen
		$sSql = "SELECT
							`ts_i`.`id` `id`,
							(
								SELECT
									COUNT(*)
								FROM
									`kolumbus_inquiries_documents` `kid`
								WHERE
									`kid`.`inquiry_id` = `ts_i`.`id` AND
									`kid`.`inquiry_id` > 0
							) `counter`
						FROM
							`ts_inquiries` `ts_i`
				";
		
		$aData = DB::getQueryData($sSql);
		
		foreach($aData as $aResult){
			
			$aSql = array();
			$aSql['inquiry_id']		= (int)$aResult['id'];
			$aSql['counter']			= $aResult['counter'];
			
			$sSql = "SELECT
							*
						FROM
							`ts_inquiries_documents_index`
						WHERE
							`inquiry_id` = :inquiry_id";
			
			$aTempResult = DB::getPreparedQueryData($sSql, $aSql);
	
			if(empty($aTempResult)){
				$sSql = "INSERT INTO ";
				$sWhere = "";
			}else{
				$sSql = "UPDATE ";
				
				$sWhere = " WHERE
								`inquiry_id` = :inquiry_id";
			}
			
			$sSql .= "		`ts_inquiries_documents_index`
						SET
							`inquiry_id` = :inquiry_id,
							`document_count` = :counter
				" . $sWhere;

			DB::executePreparedQuery($sSql, $aSql);
		}
		
		// TransferDaten holen (Anreise)
		$sSql = "SELECT
						`ts_i_j_t`.`transfer_date` `transfer_date`,
						`ts_i`.`id` `id`
					FROM
						`ts_inquiries_journeys_transfers` `ts_i_j_t` INNER JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`id` = `ts_i_j_t`.`journey_id` AND
							`ts_i_j`.`active` = 1 INNER JOIN
						`ts_inquiries` `ts_i` ON
							`ts_i`.`id` = `ts_i_j`.`inquiry_id` AND
							`ts_i`.`active` = 1 AND
							`ts_i`.`tsp_transfer` IN ('arrival', 'arr_dep')
					WHERE
						`ts_i_j_t`.`active` = 1 AND
						`ts_i_j_t`.`transfer_type` = 1
				"; 
		
		$aData = DB::getQueryData($sSql);
		
		// Löschen
		$sSql = "UPDATE
							`ts_inquiries`
						SET
							`arrival_date` = '0000-00-00'
			";
		DB::executeQuery($sSql);
		
		foreach($aData as $aResult){
			$sSql = "UPDATE
							`ts_inquiries`
						SET
							`arrival_date` = :date
						WHERE
							`id` = :id
			";
			$aSql = array();
			$aSql['id'] = (int)$aResult['id'];
			$aSql['date'] = (string)$aResult['transfer_date'];

			DB::executePreparedQuery($sSql, $aSql);
		}
		
		// TransferDaten holen (Abreise)
		$sSql = "SELECT
						`ts_i_j_t`.`transfer_date` `transfer_date`,
						`ts_i`.`id` `id`
					FROM
						`ts_inquiries_journeys_transfers` `ts_i_j_t` INNER JOIN
						`ts_inquiries_journeys` `ts_i_j` ON
							`ts_i_j`.`id` = `ts_i_j_t`.`journey_id` AND
							`ts_i_j`.`active` = 1 INNER JOIN
						`ts_inquiries` `ts_i` ON
							`ts_i`.`id` = `ts_i_j`.`inquiry_id` AND
							`ts_i`.`active` = 1 AND
							`ts_i`.`tsp_transfer` IN ('departure', 'arr_dep')
					WHERE
						`ts_i_j_t`.`active` = 1 AND
						`ts_i_j_t`.`transfer_type` = 2
				"; 
		
		$aData = DB::getQueryData($sSql);
	
		// Löschen
		$sSql = "UPDATE
							`ts_inquiries`
						SET
							`departure_date` = '0000-00-00'
			";  
		DB::executeQuery($sSql);
		
		foreach($aData as $aResult){
			$sSql = "UPDATE
							`ts_inquiries`
						SET
							`departure_date` = :date
						WHERE
							`id` = :id
			";
			$aSql = array();
			$aSql['id'] = (int)$aResult['id'];
			$aSql['date'] = (string)$aResult['transfer_date'];

			DB::executePreparedQuery($sSql, $aSql);
		}
		
		
		
		
		return true;
	}

	
	public static function report($aError, $aInfo){
		
		$oMail = new WDMail();
		$oMail->subject = 'Inquiry Structure';
		
		$sText = '';
		$sText = $_SERVER['HTTP_HOST']."\n\n";
		$sText .= date('Y-m-d H:i:s')."\n\n";
		$sText .= print_r($aInfo, 1)."\n\n";
		
		if(!empty($aError)){
			$sText .= '------------ERROR------------';
			$sText .= "\n\n";
			$sText .= print_r($aError, 1);
		}
		
		$oMail->text = $sText;

		$oMail->send(array('m.durmaz@thebing.com'));
				
	}
	

	
	
}



