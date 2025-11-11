<?php


class Ext_Thebing_System_Checks_BookingStructure extends GlobalChecks {

	
	protected $_aErrors = array();

	public function getTitle() {
		$sTitle = 'Change Booking Structure'; 
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Convert bookings to new booking structure';
		return $sDescription;
	}

	public function executeCheck(){ 

		set_time_limit(3600 * 4);
		ini_set("memory_limit", '1024M');

		$aError = array();
		$aInfo = array();
		
		// Tabelle MUSS vorhanden sein!!!!!
		$bExistsOldTable = Ext_Thebing_Util::checkTableExists('ts_inquiries');
		
		if(!$bExistsOldTable){
			$aError['no_table_found'] = 'Keine Tabelle gefunden (Booling Check)';
			self::report($aError, $aInfo);
			return true;
		}

		$bExistsOldCourseTable				= Util::checkTableExists('kolumbus_inquiries_courses');
		$bExistsOldAccommodationTable		= Util::checkTableExists('kolumbus_inquiries_accommodations');
		$bExistsOldTransferTable			= Util::checkTableExists('kolumbus_inquiries_transfers');

		$bExistsNewCourseTable				= Util::checkTableExists('ts_inquiries_journeys_courses');
		$bExistsNewAccommodationTable		= Util::checkTableExists('ts_inquiries_journeys_accommodations');
		$bExistsNewTransferTable			= Util::checkTableExists('ts_inquiries_journeys_transfers');

		$bExistsCourseBackupTable			= Util::checkTableExists('__kolumbus_inquiries_courses_backup');
		$bExistsAccommodationBackupTable	= Util::checkTableExists('__kolumbus_inquiries_accommodations_backup');
		$bExistsTransferBackupTable			= Util::checkTableExists('__kolumbus_inquiries_transfers_backup');

		$aNewCourseEntries					= array();
		$aNewAccommodationEntries			= array();
		$aNewTransferEntries				= array();
		
		if(
			!$bExistsOldCourseTable &&
			$bExistsNewCourseTable &&
			$bExistsCourseBackupTable
		){
			//Falls der Check ein zweites mal ausgeführt wird, nach dem Merge erstellte Datensätze merken und später wieder einfügen...
			$sSql = "
				SELECT
					*
				FROM
					`ts_inquiries_journeys_courses`
				WHERE
					`id` NOT IN(
						SELECT
							`id`
						FROM
							`__kolumbus_inquiries_courses_backup`
					)
			";
			
			$aNewCourseEntries = (array)DB::getQueryRows($sSql);

			//Falls der Check ein zweites mal ausgeführt wird, nach dem Merge veränderte Datensätze merken und später wieder einfügen...
			$sSql = "
				SELECT
					`ts_i_j_c`.*
				FROM
					`ts_inquiries_journeys_courses` `ts_i_j_c` INNER JOIN
					`__kolumbus_inquiries_courses_backup` `course_backup` ON
						`course_backup`.`id` = `ts_i_j_c`.`id`
				WHERE
					`ts_i_j_c`.`course_id` != `course_backup`.`course_id` OR
					`ts_i_j_c`.`level_id` != `course_backup`.`level_id` OR
					`ts_i_j_c`.`visible` != `course_backup`.`visible` OR
					`ts_i_j_c`.`active` != `course_backup`.`active` OR
					`ts_i_j_c`.`weeks` != `course_backup`.`weeks` OR
					`ts_i_j_c`.`units` != `course_backup`.`units` OR
					`ts_i_j_c`.`from` != `course_backup`.`from` OR
					`ts_i_j_c`.`until` != `course_backup`.`until` OR
					`ts_i_j_c`.`comment` != `course_backup`.`comment`
			";
			
			$aChangedCourseEntries = (array)DB::getQueryRows($sSql);
			
			$aNewCourseEntries = array_merge($aNewCourseEntries, $aChangedCourseEntries);
			
			//Falls der Merge ein zweites mal ausgeführt wird, die neue Tabelle nochmal absichern, damit wir nicht die Datensätze
			//die nach dem Merge angelegt wurden, auf jeden Fall nicht verlieren...
			Ext_Thebing_Util::backupTable('ts_inquiries_journeys_courses');
			
			$sSql = "DROP TABLE `ts_inquiries_journeys_courses`";
			DB::executeQuery($sSql);

			$sSql = "RENAME TABLE `__kolumbus_inquiries_courses_backup` to `kolumbus_inquiries_courses`";
			DB::executeQuery($sSql);
		}elseif(
			$bExistsOldCourseTable &&
			!$bExistsNewCourseTable &&
			!$bExistsCourseBackupTable
		){
			//alles super
		}else{
			$aError['course_table'] = 'Kurstabellen fehlerhaft';
			self::report($aError, $aInfo);
			return true;
		}
		
		if(
			!$bExistsOldAccommodationTable &&
			$bExistsNewAccommodationTable &&
			$bExistsAccommodationBackupTable		
		){
			//Falls der Check ein zweites mal ausgeführt wird, nach dem Merge erstellte Datensätze merken und später wieder einfügen...
			$sSql = "
				SELECT
					*
				FROM
					`ts_inquiries_journeys_accommodations`
				WHERE
					`id` NOT IN(
						SELECT
							`id`
						FROM
							`__kolumbus_inquiries_accommodations_backup`
					)
			";
			
			$aNewAccommodationEntries = (array)DB::getQueryRows($sSql);

			//Falls der Check ein zweites mal ausgeführt wird, nach dem Merge veränderte Datensätze merken und später wieder einfügen...
			$sSql = "
				SELECT
					`ts_i_j_a`.*
				FROM
					`ts_inquiries_journeys_accommodations` `ts_i_j_a` INNER JOIN
					`__kolumbus_inquiries_accommodations_backup` `accommodation_backup` ON
						`accommodation_backup`.`id` = `ts_i_j_a`.`id`
				WHERE
					`ts_i_j_a`.`accommodation_id` != `accommodation_backup`.`accommodation_id` OR
					`ts_i_j_a`.`roomtype_id` != `accommodation_backup`.`roomtype_id` OR
					`ts_i_j_a`.`meal_id` != `accommodation_backup`.`meal_id` OR
					`ts_i_j_a`.`weeks` != `accommodation_backup`.`weeks` OR
					`ts_i_j_a`.`from` != `accommodation_backup`.`from` OR
					`ts_i_j_a`.`until` != `accommodation_backup`.`until` OR
					`ts_i_j_a`.`comment` != `accommodation_backup`.`comment` OR
					`ts_i_j_a`.`visible` != `accommodation_backup`.`visible` OR
					`ts_i_j_a`.`active` != `accommodation_backup`.`active` OR
					`ts_i_j_a`.`from_time` != `accommodation_backup`.`from_time` OR
					`ts_i_j_a`.`until_time` != `accommodation_backup`.`until_time`
			";
			
			$aChangedAccomodationEntries = (array)DB::getQueryRows($sSql);
			
			$aNewAccommodationEntries = array_merge($aNewAccommodationEntries, $aChangedAccomodationEntries);
			
			//Falls der Merge ein zweites mal ausgeführt wird, die neue Tabelle nochmal absichern, damit wir nicht die Datensätze
			//die nach dem Merge angelegt wurden, auf jeden Fall nicht verlieren...
			Ext_Thebing_Util::backupTable('ts_inquiries_journeys_accommodations');
			
			$sSql = "DROP TABLE `ts_inquiries_journeys_accommodations`";
			DB::executeQuery($sSql);

			$sSql = "RENAME TABLE `__kolumbus_inquiries_accommodations_backup` to `kolumbus_inquiries_accommodations`";
			DB::executeQuery($sSql);
		}elseif(
			$bExistsOldAccommodationTable &&
			!$bExistsNewAccommodationTable &&
			!$bExistsAccommodationBackupTable
		){
			//alles super
		}else{
			$aError['accommodation_table'] = 'Unterkunfttabellen fehlerhaft';
			self::report($aError, $aInfo);
			return true;
		}

		if(
			!$bExistsOldTransferTable &&
			$bExistsNewTransferTable &&
			$bExistsTransferBackupTable
		){
			//Falls der Check ein zweites mal ausgeführt wird, nach dem Merge erstellte Datensätze merken und später wieder einfügen...
			$sSql = "
				SELECT
					*
				FROM
					`ts_inquiries_journeys_transfers`
				WHERE
					`id` NOT IN(
						SELECT
							`id`
						FROM
							`__kolumbus_inquiries_transfers_backup`
					)
			";
			
			$aNewTransferEntries = (array)DB::getQueryRows($sSql);
			
			//Falls der Check ein zweites mal ausgeführt wird, nach dem Merge veränderte Datensätze merken und später wieder einfügen...
			$sSql = "
				SELECT
					`ts_i_j_t`.*
				FROM
					`ts_inquiries_journeys_transfers` `ts_i_j_t` INNER JOIN
					`__kolumbus_inquiries_transfers_backup` `transfer_backup` ON
						`transfer_backup`.`id` = `ts_i_j_t`.`id`
				WHERE
					`ts_i_j_t`.`active` != `transfer_backup`.`active` OR
					`ts_i_j_t`.`transfer_type` != `transfer_backup`.`transfer_type` OR
					`ts_i_j_t`.`start` != `transfer_backup`.`start` OR
					`ts_i_j_t`.`end` != `transfer_backup`.`end` OR
					`ts_i_j_t`.`start_type` != `transfer_backup`.`start_type` OR
					`ts_i_j_t`.`end_type` != `transfer_backup`.`end_type` OR
					`ts_i_j_t`.`transfer_date` != `transfer_backup`.`transfer_date` OR
					`ts_i_j_t`.`transfer_time` != `transfer_backup`.`transfer_time` OR
					`ts_i_j_t`.`comment` != `transfer_backup`.`comment` OR
					`ts_i_j_t`.`start_additional` != `transfer_backup`.`start_additional` OR
					`ts_i_j_t`.`end_additional` != `transfer_backup`.`end_additional` OR
					`ts_i_j_t`.`airline` != `transfer_backup`.`airline` OR
					`ts_i_j_t`.`flightnumber` != `transfer_backup`.`flightnumber` OR
					`ts_i_j_t`.`pickup` != `transfer_backup`.`pickup` OR
					`ts_i_j_t`.`accommodation_confirmed` != `transfer_backup`.`accommodation_confirmed` OR
					`ts_i_j_t`.`provider_updated` != `transfer_backup`.`provider_updated` OR
					`ts_i_j_t`.`provider_confirmed` != `transfer_backup`.`provider_confirmed` OR
					`ts_i_j_t`.`provider_id` != `transfer_backup`.`provider_id` OR
					`ts_i_j_t`.`provider_type` != `transfer_backup`.`provider_type` OR
					`ts_i_j_t`.`driver_id` != `transfer_backup`.`driver_id` OR
					`ts_i_j_t`.`customer_agency_confirmed` != `transfer_backup`.`customer_agency_confirmed` OR
					`ts_i_j_t`.`updated` != `transfer_backup`.`updated`
			";
			
			$aChangedTransferEntries = (array)DB::getQueryRows($sSql);
			
			$aNewTransferEntries = array_merge($aNewTransferEntries, $aChangedTransferEntries);
			
			//Falls der Merge ein zweites mal ausgeführt wird, die neue Tabelle nochmal absichern, damit wir nicht die Datensätze
			//die nach dem Merge angelegt wurden, auf jeden Fall nicht verlieren...
			Ext_Thebing_Util::backupTable('ts_inquiries_journeys_transfers');
			
			$sSql = "DROP TABLE `ts_inquiries_journeys_transfers`";
			DB::executeQuery($sSql);

			$sSql = "RENAME TABLE `__kolumbus_inquiries_transfers_backup` to `kolumbus_inquiries_transfers`";
			DB::executeQuery($sSql);

			$bSuccessBackupTransfer			= true;
		}elseif(
			$bExistsOldTransferTable &&
			!$bExistsNewTransferTable &&
			!$bExistsTransferBackupTable
		){
			//alles super
		}else{
			$aError['accommodation_table'] = 'Unterkunfttabellen fehlerhaft';
			self::report($aError, $aInfo);
			return true;
		}

		// Tabellen Backups erstellen
		$sBackupCourse			= 'ts_inquiries_journeys_courses';
		$sBackupAccommodation	= 'ts_inquiries_journeys_accommodations';
		$sBackupTransfer		= 'ts_inquiries_journeys_transfers';

		$bSuccessBackupCourse			= Ext_Thebing_Util::backupTable('kolumbus_inquiries_courses', false, $sBackupCourse);
		$bSuccessBackupAccommodation	= Ext_Thebing_Util::backupTable('kolumbus_inquiries_accommodations', false, $sBackupAccommodation);
		$bSuccessBackupTransfer			= Ext_Thebing_Util::backupTable('kolumbus_inquiries_transfers', false, $sBackupTransfer);

		if(
			!$bSuccessBackupCourse ||
			!$bSuccessBackupAccommodation ||
			!$bSuccessBackupTransfer
		){
			$aError['no_table_found'] = 'Backup fehlgeschlagen (Booling Check)';
			self::report($aError, $aInfo);
			return true;
		}
		
		// Spalten Umbenennen
		$sSql = "ALTER TABLE `" . $sBackupCourse . "` CHANGE `inquiry_id` `journey_id` MEDIUMINT( 9 ) NOT NULL ";
		DB::executeQuery($sSql);
		$sSql = "ALTER TABLE `" . $bSuccessBackupAccommodation . "` CHANGE `inquiry_id` `journey_id` MEDIUMINT( 9 ) NOT NULL ";
		DB::executeQuery($sSql);
		$sSql = "ALTER TABLE `" . $bSuccessBackupTransfer . "` CHANGE `inquiry_id` `journey_id` MEDIUMINT( 9 ) NOT NULL ";
		DB::executeQuery($sSql);
		
		
		//Connection definieren
		$oDB = DB::getDefaultConnection();
		
		// bugfix #2310
		// es müssen alle kurse/unterkünfte/transfere gelöscht werden die nicht zu den übriggebliebenen
		// buchungen gehören ( alle buchungen die nicht dem client gehören )
		$sSql = "
			SELECT
				`ts_i`.`id` `inquiry_id`,
				`ts_i_j`.`id` `journey_id`
			FROM
				`ts_inquiries` `ts_i` INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`inquiry_id` = `ts_i`.`id` AND
					`ts_i_j`.`id` > 0
			WHERE
				`ts_i`.`id` > 0
		";
		
		$aSql = array();

		#$aResult = DB::getPreparedQueryData($sSql, $aSql);
		
		$aResult = $oDB->getCollection($sSql, $aSql);
		
		$aInquiriesIds = array();
		
		foreach($aResult as $aRowData){
			$aInquiriesIds[] = (int)$aRowData['inquiry_id'];
		}
		
		$aInquiriesIds = array_unique($aInquiriesIds);
		
		$sSql = " DELETE FROM #table WHERE  `journey_id` NOT IN  (:inquiry_ids) ";
		$aSql = array('inquiry_ids' => $aInquiriesIds);
		
		$aSql['table'] = $sBackupCourse;
		DB::executePreparedQuery($sSql, $aSql);

		$aSql['table'] = $bSuccessBackupAccommodation;
		DB::executePreparedQuery($sSql, $aSql);

		$aSql['table'] = $bSuccessBackupTransfer;
		DB::executePreparedQuery($sSql, $aSql);


		################### InquiryCourse ###################
		$sSql = "
			CREATE TABLE IF NOT EXISTS `ts_inquiries_journeys_courses_to_travellers` (
			  `journey_course_id` mediumint(9) NOT NULL,
			  `contact_id` mediumint(9) NOT NULL,
			  UNIQUE KEY `journey_course_contact` (`journey_course_id`,`contact_id`),
			  KEY `contact_id` (`contact_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";

		DB::executeQuery($sSql);

		//Falls Daten vorhanden, rauslöschen
		$sSql = "TRUNCATE `ts_inquiries_journeys_courses_to_travellers`";
		DB::executeQuery($sSql);

		$sSql = "
			SELECT
				`ts_i_to_c`.`contact_id`,
				`kic`.`id` `inquiry_course_id`,
				`ts_i_j`.`id` `journey_id`
			FROM
				`kolumbus_inquiries_courses` `kic` INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `kic`.`inquiry_id` INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`inquiry_id` = `ts_i`.`id` INNER JOIN
				`ts_inquiries_to_contacts` `ts_i_to_c` ON
					`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
					`ts_i_to_c`.`type` = 'booker'
		";

		#$aResult	= (array)DB::getQueryRows($sSql);
		
		$aResult	= $oDB->getCollection($sSql, array());

		foreach($aResult as $aRowData)
		{
			//Die Spalte inquiry_id wurde in journey_id umbenannt, jetzt auch den Inhalt austauschen
			$aUpdate = array(
				'journey_id' => $aRowData['journey_id']
			);
			
			$sWhere = ' id = ' . $aRowData['inquiry_course_id'];
			
			DB::updateData($sBackupCourse, $aUpdate, $sWhere);
			
			//Contacts Zwischentabelle befüllen
			$aInsertData = array(
				'journey_course_id' => (int)$aRowData['inquiry_course_id'],
				'contact_id'		=> (int)$aRowData['contact_id']
			);

			$rRes = DB::insertData('ts_inquiries_journeys_courses_to_travellers', $aInsertData);
		}
		################### InquiryCourse Ende ###################

		################### InquiryAccommodation ###################
		$sSql = "
			CREATE TABLE IF NOT EXISTS `ts_inquiries_journeys_accommodations_to_travellers` (
			  `journey_accommodation_id` mediumint(9) NOT NULL,
			  `contact_id` mediumint(9) NOT NULL,
			  UNIQUE KEY `journey_accommodation_contact` (`journey_accommodation_id`,`contact_id`),
			  KEY `contact_id` (`contact_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";

		DB::executeQuery($sSql);

		//Falls Daten vorhanden, rauslöschen
		$sSql = "TRUNCATE `ts_inquiries_journeys_accommodations_to_travellers`";
		DB::executeQuery($sSql);

		$sSql = "
			SELECT
				`ts_i_to_c`.`contact_id`,
				`kia`.`id` `inquiry_accommodation_id`,
				`ts_i_j`.`id` `journey_id`
			FROM
				`kolumbus_inquiries_accommodations` `kia` INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `kia`.`inquiry_id` INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`inquiry_id` = `ts_i`.`id` INNER JOIN
				`ts_inquiries_to_contacts` `ts_i_to_c` ON
					`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
					`ts_i_to_c`.`type` = 'booker'
		";

		#$aResult = (array)DB::getQueryRows($sSql);

		$aResult	= $oDB->getCollection($sSql, array());
		
		foreach($aResult as $aRowData)
		{
			//Die Spalte inquiry_id wurde in journey_id umbenannt, jetzt auch den Inhalt austauschen
			$aUpdate = array(
				'journey_id' => $aRowData['journey_id']
			);
			
			$sWhere = ' id = ' . $aRowData['inquiry_accommodation_id'];
			
			DB::updateData($sBackupAccommodation, $aUpdate, $sWhere);
			
			//Contacts Zwischentabelle befüllen
			$aInsertData = array(
				'journey_accommodation_id'	=> (int)$aRowData['inquiry_accommodation_id'],
				'contact_id'				=> (int)$aRowData['contact_id']
			);

			$rRes = DB::insertData('ts_inquiries_journeys_accommodations_to_travellers', $aInsertData);
		}
		################### InquiryAccommodation Ende ###################

		################### InquiryTransfer ###################
		$sSql = "
			CREATE TABLE IF NOT EXISTS `ts_inquiries_journeys_transfers_to_travellers` (
			  `journey_transfer_id` mediumint(9) NOT NULL,
			  `contact_id` mediumint(9) NOT NULL,
			  UNIQUE KEY `journey_transfer_contact` (`journey_transfer_id`,`contact_id`),
			  KEY `contact_id` (`contact_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		";

		DB::executeQuery($sSql);

		//Falls Daten vorhanden, rauslöschen
		$sSql = "TRUNCATE `ts_inquiries_journeys_transfers_to_travellers`";
		DB::executeQuery($sSql);

		$sSql = "
			SELECT
				`ts_i_to_c`.`contact_id`,
				`kit`.`id` `inquiry_transfer_id`,
				`ts_i_j`.`id` `journey_id`
			FROM
				`kolumbus_inquiries_transfers` `kit` INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `kit`.`inquiry_id` INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`inquiry_id` = `ts_i`.`id` INNER JOIN
				`ts_inquiries_to_contacts` `ts_i_to_c` ON
					`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
					`ts_i_to_c`.`type` = 'booker'
		";

		#$aResult = (array)DB::getQueryRows($sSql);

		$aResult	= $oDB->getCollection($sSql, array());
		
		foreach($aResult as $aRowData)
		{
			//Die Spalte inquiry_id wurde in journey_id umbenannt, jetzt auch den Inhalt austauschen
			$aUpdate = array(
				'journey_id' => $aRowData['journey_id']
			);
			
			$sWhere = ' id = ' . $aRowData['inquiry_transfer_id'];
			
			DB::updateData($sBackupTransfer, $aUpdate, $sWhere);
			
			//Contacts Zwischentabelle befüllen
			$aInsertData = array(
				'journey_transfer_id'	=> (int)$aRowData['inquiry_transfer_id'],
				'contact_id'			=> (int)$aRowData['contact_id']
			);

			$rRes = DB::insertData('ts_inquiries_journeys_transfers_to_travellers', $aInsertData);
		}
		################### InquiryTransfer Ende ###################		
		
		
		/*
		foreach($aResult as $aRowData){
			
			$sSql = "UPDATE
							#table
						SET
							`journey_id` = :journey_id
						WHERE
							`journey_id` = :inquiry_id
						";
			
			$aSql = array(
						'journey_id' => (int)$aRowData['journey_id'],
						'inquiry_id' => (int)$aRowData['inquiry_id']
			);

			
			$aSql['table'] = $sBackupCourse;
			DB::executePreparedQuery($sSql, $aSql);

			$aSql['table'] = $bSuccessBackupAccommodation;
			DB::executePreparedQuery($sSql, $aSql);

			$aSql['table'] = $bSuccessBackupTransfer;
			DB::executePreparedQuery($sSql, $aSql);

			
		}*/

		$sSql = "RENAME TABLE `kolumbus_inquiries_courses` TO `__kolumbus_inquiries_courses_backup`";
		DB::executeQuery($sSql);

		$sSql = "RENAME TABLE `kolumbus_inquiries_accommodations` TO `__kolumbus_inquiries_accommodations_backup`";
		DB::executeQuery($sSql);

		$sSql = "RENAME TABLE `kolumbus_inquiries_transfers` TO `__kolumbus_inquiries_transfers_backup`";
		DB::executeQuery($sSql);
		
		//Falls der Merge ein zweites mal ausgeführt wurde, die Datensätze die nach dem Merge angelegt wurden
		//wieder in die umgewandelten Tabellen einfügen
		foreach($aNewCourseEntries as $aData)
		{
			//Datensatz zuerst löschen, falls es ein aktualisierter Eintrag ist, sonst würde man versuchen den Datensatz zum 2.mal einzufügen
			$sSql = "
				DELETE FROM
					#table
				WHERE
					`id` = :data_id
			";
			
			$aSql = array(
				'table'		=> $sBackupCourse,
				'data_id'	=> $aData['id']
			);
			
			DB::executePreparedQuery($sSql, $aSql);
			
			//Datensatz einfügen
			DB::insertData($sBackupCourse, $aData);
		}
		
		foreach($aNewAccommodationEntries as $aData)
		{
			//Datensatz zuerst löschen, falls es ein aktualisierter Eintrag ist, sonst würde man versuchen den Datensatz zum 2.mal einzufügen
			$sSql = "
				DELETE FROM
					#table
				WHERE
					`id` = :data_id
			";
			
			$aSql = array(
				'table'		=> $sBackupAccommodation,
				'data_id'	=> $aData['id']
			);
			
			DB::executePreparedQuery($sSql, $aSql);
			
			//Datensatz einfügen
			DB::insertData($sBackupAccommodation, $aData);
		}
		
		foreach($aNewTransferEntries as $aData)
		{
			//Datensatz zuerst löschen, falls es ein aktualisierter Eintrag ist, sonst würde man versuchen den Datensatz zum 2.mal einzufügen
			$sSql = "
				DELETE FROM
					#table
				WHERE
					`id` = :data_id
			";
			
			$aSql = array(
				'table'		=> $sBackupTransfer,
				'data_id'	=> $aData['id']
			);
			
			DB::executePreparedQuery($sSql, $aSql);
			
			//Datensatz einfügen
			DB::insertData($sBackupTransfer, $aData);
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