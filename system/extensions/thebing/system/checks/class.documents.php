<?php

class Ext_Thebing_System_Checks_Documents extends GlobalChecks {

	public function executeCheck(){
		global $user_data, $system_data;

		$oDb = DB::getDefaultConnection();

		if(!hasRight('modules_admin') && $user_data['name'] != 'patrick'){
			$this->_aFormErrors[] = 'Only an full CMS Administrator hast the Right to start this Script!';
			return false;
		}

		set_time_limit(14400);
		@ini_set("memory_limit", '2048M');

		// Backup der Tabellen
		try{
			Ext_Thebing_Util::backupTable('kolumbus_inquiries');
			Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents');
			Ext_Thebing_Util::backupTable('kolumbus_inquiries_documents_positions');
		} catch(Exception $e){
			__pout($e);
			//return false;
		}


		$aSql = array();

		try{

			//$sSql = "DROP TABLE `kolumbus_inquiries_documents_versions_items`";
			//DB::executePreparedQuery($sSql,$aSql);

			$sSql = "DROP TABLE `kolumbus_inquiries_documents_versions_items`  ";
			DB::executeQuery($sSql);

			$sSql = "CREATE  TABLE  `kolumbus_inquiries_documents_versions_items` (  `id` int( 11  )  NOT  NULL  auto_increment ,
				 `invoice_id` int( 11  )  NOT  NULL ,
				 `changed` timestamp NOT  NULL  default CURRENT_TIMESTAMP  on  update  CURRENT_TIMESTAMP ,
				 `created` timestamp NOT  NULL default  '0000-00-00 00:00:00',
				 `type` varchar( 255  )  NOT  NULL ,
				 `description` text NOT  NULL ,
				 `old_description` text NOT  NULL ,
				 `amount` float( 10, 2  )  NOT  NULL default  '0.00',
				 `amount_net` float( 10, 2  )  NOT  NULL default  '0.00',
				 `amount_provision` float( 10, 2  )  NOT  NULL default  '0.00',
				 `initalcost` tinyint( 4  )  NOT  NULL default  '0',
				 `calculate` tinyint( 4  )  NOT  NULL default  '1',
				 `onPdf` tinyint( 4  )  NOT  NULL default  '1',
				 `active` tinyint( 4  )  NOT  NULL default  '1',
				 `position` int( 11  )  NOT  NULL default  '0',
				 `count` int( 11  )  NOT  NULL default  '1',
				 `type_id` int( 11  )  NOT  NULL ,
				 `nights` int( 11  )  NOT  NULL ,
				 `old` tinyint( 1  )  NOT  NULL ,
				 `amount_discount` float( 10, 2  )  NOT  NULL ,
				 `description_discount` text NOT  NULL ,
				 `tax_category` int( 11  )  NOT  NULL ,
				 PRIMARY  KEY (  `id`  ) ,
				 KEY  `invoice_id` (  `invoice_id`  ) ,
				 KEY  `type_id` (  `type_id`  ) ,
				 KEY  `type` (  `type`  ) ,
				 KEY  `initalcost` (  `initalcost`  ) ,
				 KEY  `calculate` (  `calculate`  ) ,
				 KEY  `onPdf` (  `onPdf`  ) ,
				 KEY  `active` (  `active`  ) ,
				 KEY  `position` (  `position`  ) ,
				 KEY  `tax_category` (  `tax_category`  )  ) ENGINE  =  MyISAM  DEFAULT CHARSET  = utf8";
			DB::executeQuery($sSql);

			$sSql = "INSERT INTO `thebing`.`kolumbus_inquiries_documents_versions_items` SELECT * FROM `thebing`.`kolumbus_inquiries_documents_positions`";
			DB::executeQuery($sSql);

			try {

				// Tabelle umbenennen
				$sSql = "RENAME TABLE
							`kolumbus_inquiries_documents_positions`
						TO
							`kolumbus_inquiries_documents_versions_items`
				";

				DB::executePreparedQuery($sSql,$aSql);

			} catch(Exception $e) {

				__pout($e);

			}
				//$sSql = "ALTER TABLE `kolumbus_inquiries_documents_versions_items` DROP `version_id` ";
				//DB::executePreparedQuery($sSql,$aSql);

			try {

				// Feld umbenennen
				$sSql = "ALTER TABLE
							`kolumbus_inquiries_documents_versions_items`
						ADD
							`version_id`
						INT( 11 )
						NOT NULL
				";

				DB::executePreparedQuery($sSql,$aSql);

			} catch(Exception $e) {

				__pout($e);

			}

			try {

				// Feld umbenennen
				$sSql = "ALTER TABLE
							`kolumbus_inquiries_documents_versions_items`
						ADD
							`invoice_id`
						INT( 11 )
						NOT NULL
				";

				DB::executePreparedQuery($sSql,$aSql);

			} catch(Exception $e) {

				__pout($e);

			}
				//$sSql = "ALTER TABLE `kolumbus_inquiries_documents` DROP `document_number` ";
				//DB::executePreparedQuery($sSql,$aSql);

			try {

				// Feld umbenennen
				$sSql = "ALTER TABLE
							`kolumbus_inquiries_documents`
						CHANGE
							`invoiceNumber` `document_number`
						VARCHAR( 255 )
						NOT NULL
				";

				DB::executePreparedQuery($sSql,$aSql);

			} catch(Exception $e) {

				__pout($e);

			}


			// neue VersionsTabelle
			$sSql = "CREATE TABLE IF NOT EXISTS `kolumbus_inquiries_documents_versions` (
					  `id` int(11) NOT NULL auto_increment,
					  `changed` timestamp NOT NULL default CURRENT_TIMESTAMP,
					  `created` timestamp NOT NULL ,
					  `active` tinyint(1) NOT NULL default '1',
					  `document_id` int(11) NOT NULL,
					  `version` int(11) NOT NULL default '1',
					  `template_id` int(11) NOT NULL,
					  `date` timestamp NOT NULL,
					  `txt_address` text NOT NULL,
					  `txt_subject` text NOT NULL,
					  `txt_intro` text NOT NULL,
					  `txt_outro` text NOT NULL,
					  `txt_enclosures` text NOT NULL,
					  `txt_pdf` text NOT NULL,
					  `txt_signature` varchar(255) NOT NULL,
					  `signature` varchar(255) NOT NULL,
					  `path` varchar(255) NOT NULL,
					  `comment` LONGTEXT NOT NULL,
					  `user_id` INT NOT NULL,
					  PRIMARY KEY  (`id`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8;
					";

			DB::executePreparedQuery($sSql,$aSql);

			// Indexe setzen
			try {
				$sSql = "ALTER TABLE `kolumbus_inquiries_documents_versions` ADD INDEX `document_id` ( `document_id` ) ";
				DB::executePreparedQuery($sSql,$aSql);
				$sSql = "ALTER TABLE `kolumbus_inquiries_documents_versions` ADD INDEX `document_id` ( `version` ) ";
				DB::executePreparedQuery($sSql,$aSql);
				$sSql = "ALTER TABLE `kolumbus_inquiries_documents_versions` ADD INDEX `document_id` ( `template_id` ) ";
				DB::executePreparedQuery($sSql,$aSql);
			} catch(Exception $e) {
				__pout($e);
			}

		} catch(Exception $e){
			__pout($e);
			//return false;
		}



		## Start Daten umschreiben


			/*
			$sSql = "
						SELECT
							`kid`.* , UNIX_TIMESTAMP(`kid`.`created`) `created`
						FROM
							`kolumbus_inquiries` `ki` INNER JOIN
							`kolumbus_inquiries_documents` `kid`
							ON
								`kid`.`inquiry_id` = `ki`.`id`
						WHERE
							`ki`.`office` >= 47 ||
							`ki`.`office` IN (9, 11, 16, 17, 19, 20, 22, 24, 27, 28, 30, 33, 36, 37, 40, 41, 42, 43, 44, 45, 46, 47, 1, 21)
						";

			*/
			$sSql = "SELECT * FROM `kolumbus_inquiries_documents`";
			$aDocuments = DB::getPreparedQueryData($sSql,$aSql);

			foreach((array)$aDocuments as $aDocument){
				// Von jedem Document die dazugehöreigen Positionen holen
				$sSql = "SELECT
								*
							FROM
								`kolumbus_inquiries_documents_versions_items`
							WHERE
								`invoice_id` = :invoice_id AND
								`active` = 1 AND
								`old` = 0
				";
				$aSql['invoice_id'] = $aDocument['id'];
				$aPositions = DB::getPreparedQueryData($sSql,$aSql);

				$aData = array();
				$aData['document_id']	= $aDocument['id'];
				$aData['created']		= $aDocument['created'];
				$aData['version']		= 1;
				$aData['template_id']	= $aDocument['template_id'];
				$aData['txt_address']	= $aDocument['txt_address'];
				$aData['txt_subject']	= $aDocument['txt_subject'];
				$aData['txt_intro']		= $aDocument['txt_intro'];
				$aData['txt_outro']		= $aDocument['txt_outro'];
				$aData['txt_enclosures']= $aDocument['txt_enclosures'];
				$aData['txt_pdf']		= $aDocument['txt_pdf'];
				$aData['txt_signature'] = $aDocument['txt_signature'];
				$aData['signature']		= $aDocument['signature'];
				$aData['path']			= $aDocument['path'];

				$oDb->insert('kolumbus_inquiries_documents_versions', $aData);
				$iVersionId	= $oDb->getInsertId();

				foreach((array)$aPositions as $aPosition){
					// Positions ID auf Version Tabelle umschreiben
					$sWhere = '`id` = '.(int)$aPosition['id'];
					DB::updateData('kolumbus_inquiries_documents_versions_items', array('version_id'=>$iVersionId), $sWhere);
				}

			}

			/*
			// alte unnötige Spalten löschen
			$sSql = "ALTER TABLE `kolumbus_inquiries_documents` DROP `template_id` ";
			DB::executePreparedQuery($sSql,$aSql);
			$sSql = "ALTER TABLE `kolumbus_inquiries_documents` DROP `date` ";
			DB::executePreparedQuery($sSql,$aSql);
			$sSql = "ALTER TABLE `kolumbus_inquiries_documents` DROP `txt_address` ";
			DB::executePreparedQuery($sSql,$aSql);
			$sSql = "ALTER TABLE `kolumbus_inquiries_documents` DROP `txt_subject` ";
			DB::executePreparedQuery($sSql,$aSql);
			$sSql = "ALTER TABLE `kolumbus_inquiries_documents` DROP `txt_intro` ";
			DB::executePreparedQuery($sSql,$aSql);
			$sSql = "ALTER TABLE `kolumbus_inquiries_documents` DROP `txt_outro` ";
			DB::executePreparedQuery($sSql,$aSql);
			$sSql = "ALTER TABLE `kolumbus_inquiries_documents` DROP `txt_enclosures` ";
			DB::executePreparedQuery($sSql,$aSql);
			$sSql = "ALTER TABLE `kolumbus_inquiries_documents` DROP `txt_pdf` ";
			DB::executePreparedQuery($sSql,$aSql);
			$sSql = "ALTER TABLE `kolumbus_inquiries_documents` DROP `txt_signature` ";
			DB::executePreparedQuery($sSql,$aSql);
			$sSql = "ALTER TABLE `kolumbus_inquiries_documents` DROP `signature` ";
			DB::executePreparedQuery($sSql,$aSql);
			$sSql = "ALTER TABLE `kolumbus_inquiries_documents` DROP `path` ";
			DB::executePreparedQuery($sSql,$aSql);
*/

		return true;

	}

}


?>