<?php

/**
 * https://redmine.thebing.com/redmine/issues/6948
 */
class Ext_TS_System_Checks_Tuition_Placementtest_RenameTables extends GlobalChecks {

    /**
     * @return string
     */
    public function getTitle() {
        return 'Update placementtest structure';
    }

    /**
     * @return string
     */
    public function getDescription() {
        return self::getTitle();
    }

    /**
     * Einstufungstest Tabellen umbennen und die Template Tabelle löschen.
     * Die Spalten einiger Tabellen wurden auch mit diesem Check geändert (idSchool heißt jetzt school_id und idClient gibt es nicht mehr!)
     *
     * @return boolean
     */
    public function executeCheck() {

        set_time_limit(3600);
        ini_set('memory_limit', '2048M');

        $oDb = DB::getDefaultConnection();

        // Backup Tabellen
        $aTableNames = array(
            array(
                'old_name' => 'kolumbus_placementtests_results',
                'new_name' => 'ts_placementtests_results'
            ),
            array(
                'old_name' => 'kolumbus_placementtests_results_inquirycourse',
                'new_name' => 'ts_placementtests_results_inquiries_journeys_courses'
            ),
            array(
                'old_name' => 'kolumbus_placementtests_results_teachers',
                'new_name' => 'ts_placementtests_results_teachers'
            ),
            array(
                'old_name' => 'kolumbus_pt_answers',
                'new_name' => 'ts_placementtests_questions_answers'
            ),
            array(
                'old_name' => 'kolumbus_pt_categories',
                'new_name' => 'ts_placementtests_categories'
            ),
            array(
                'old_name' => 'kolumbus_pt_questions',
                'new_name' => 'ts_placementtests_questions'
            ),
            array(
                'old_name' => 'kolumbus_pt_results_detail',
                'new_name' => 'ts_placementtests_results_details'
            ),
            array('old_name' => 'kolumbus_pt_templates')
        );

        foreach($aTableNames as $aTableName) {

	        $bTableExist = $oDb->checkTable($aTableName['old_name']);

	        if($bTableExist) {

		        if(!Util::backupTable($aTableName['old_name'])) {
	                return false;
	            }

	        }

        }

        // Umbennen der Tabellen

        $sSql = "
				RENAME TABLE
					#old_name
				TO
					#new_name
		";

        foreach($aTableNames as $aTableName) {

            $bTableNameExist = $oDb->checkTable($aTableName['old_name']);

            if(
                $bTableNameExist &&
                $aTableName['old_name'] !== 'kolumbus_pt_templates'
            ) {

                $aSql = array(
                    'old_name' => $aTableName['old_name'],
                    'new_name' => $aTableName['new_name']
                );

                DB::executePreparedQuery($sSql, $aSql);

            }
        }

        // Umbennen der Tabellenspalten

        $bTableColumnExist = $oDb->checkField('ts_placementtests_results', 'key', true);

        if(!$bTableColumnExist) {
            $sSql = "ALTER TABLE `ts_placementtests_results` CHANGE `inquiry_md5` `key` VARCHAR(32)";
            DB::executeQuery($sSql);
        }

        $bInquiryIdColumnExist = $oDb->checkField('ts_placementtests_results', 'inquiry_id', true);

        if(!$bInquiryIdColumnExist) {
            $sSql = "ALTER TABLE `ts_placementtests_results` ADD `inquiry_id` INT NOT NULL AFTER `key` , ADD INDEX ( `inquiry_id` ) ";
            DB::executeQuery($sSql);
        }

	    // Hinzufügen neuer Spalten

	    $aNewTableColumns = [
		    'started',
		    'invited',
		    'answered'
	    ];

	    foreach($aNewTableColumns as $sNewTableColumn) {
		    if(!$oDb->checkField('ts_placementtests_results', $sNewTableColumn, true)) {
			    $sSql = "ALTER TABLE `ts_placementtests_results` ADD `".DB::escapeQueryString($sNewTableColumn)."` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00'";
			    DB::executeQuery($sSql);
		    }
	    }

	    $sSql = "ALTER TABLE `ts_placementtests_results` CHANGE `placementtest_date` `placementtest_date` DATE NOT NULL";
	    DB::executeQuery($sSql);

	    // In den Placementtest Result Details neue Spalte einfügen, die aussagt das die Antwort richtig ist.

	    $bRightAnswer = $oDb->checkField('ts_placementtests_results_details', 'answer_is_right', true);

	    if(!$bRightAnswer) {
		    $sSql = "ALTER TABLE `ts_placementtests_results_details` ADD `answer_is_right` TINYINT(1) NULL AFTER `value`";
		    DB::executeQuery($sSql);
	    }

        foreach($aTableNames as $aTableName) {
            if(
                $aTableName['new_name'] === 'ts_placementtests_questions_answers' ||
                $aTableName['new_name'] === 'ts_placementtests_categories' ||
                $aTableName['new_name'] === 'ts_placementtests_questions'
            ) {

                $bTableColumnExist = $oDb->checkField($aTableName['new_name'], 'idSchool', true);

                if($bTableColumnExist) {
                    $sSql = "ALTER TABLE
								`".$aTableName['new_name']."`
							 CHANGE
								`idSchool` `school_id` int(11) NOT NULL
					";

                    DB::executeQuery($sSql);
                }

                $bTableColumnExist = $oDb->checkField($aTableName['new_name'], 'idClient', true);

                if($bTableColumnExist) {
                    $sSql = "ALTER TABLE
								`".$aTableName['new_name']."`
							 DROP
								`idClient`
					";

                    DB::executeQuery($sSql);
                }
            }
        }

        // Löschen der Tabelle

        $bTableExist = $oDb->checkTable('kolumbus_pt_templates');

        if($bTableExist) {
            $sSql = "DROP TABLE `kolumbus_pt_templates`";
            DB::executeQuery($sSql);
        }

	    // Tabelle für Einstufungsergebnis Kommentare

	    $bTableExist = $oDb->checkTable('ts_placementtests_results_details_notices');

	    if(!$bTableExist) {

		    $sSql = "
				CREATE TABLE IF NOT EXISTS
					`ts_placementtests_results_details_notices`
					(
						`id` int(10) unsigned NOT NULL,
			    		`created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
						`changed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
						`active` tinyint(4) NOT NULL,
						`creator_id` int(10) unsigned NOT NULL,
						`editor_id` int(10) unsigned NOT NULL,
						`result_id` int(10) unsigned NOT NULL,
						`question_id` int(10) unsigned NOT NULL,
						`comment` text CHARACTER SET utf8 NOT NULL
					) ENGINE=InnoDB DEFAULT CHARSET=latin1
			";

		    DB::executeQuery($sSql);

		    $sSql = "
		        ALTER TABLE `ts_placementtests_results_details_notices` ADD PRIMARY KEY (`id`);
		    ";
		    DB::executeQuery($sSql);

		    $sSql = "
		        ALTER TABLE `ts_placementtests_results_details_notices` MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT;
		    ";
		    DB::executeQuery($sSql);

	    }

	    // Für die automatische Korrektur Spalte in der Schule-Tabelle hinzufügen

	    $bExistAutoCorrection = $oDb->checkField('customer_db_2', 'placementtest_automatic_comparison', true);
	    if(!$bExistAutoCorrection) {
		    $sSql = "ALTER TABLE `customer_db_2` ADD `placementtest_automatic_comparison` TINYINT(1) NOT NULL DEFAULT '0' AFTER `net_email_warning`";
		    DB::executeQuery($sSql);
	    }

		$bExistCorrectionInPercent = $oDb->checkField('customer_db_2', 'placementtest_accuracy_in_percent', true);
	    if(!$bExistCorrectionInPercent) {
		    $sSql = "ALTER TABLE `customer_db_2` ADD `placementtest_accuracy_in_percent` TINYINT UNSIGNED NULL DEFAULT NULL AFTER `placementtest_automatic_comparison`";
		    DB::executeQuery($sSql);
	    }

		$this->setInquiryIdsForPlacementtestResults();

        return true;

    }

	/**
	 * Neue Spalte Inquiry-ID befüllen (von den Results)
	 */
	private function setInquiryIdsForPlacementtestResults() {

		DB::begin(__METHOD__);

		$aResult = (array)DB::getQueryRows("
			SELECT
				`ts_ptr`.`id`,
				(
					SELECT
						`ts_ij`.`inquiry_id`
					FROM
						`ts_placementtests_results_inquiries_journeys_courses` `ts_ptri` INNER JOIN
						`ts_inquiries_journeys_courses` `ts_ijc` ON
							`ts_ijc`.`id` = `ts_ptri`.`inquiry_course_id` INNER JOIN
						`ts_inquiries_journeys` `ts_ij` ON
							`ts_ij`.`id` = `ts_ijc`.`journey_id`
					WHERE
						`ts_ptri`.`placementtest_result_id` = `ts_ptr`.`id`
					LIMIT
						1
				) `inquiry_id`
			FROM
				`ts_placementtests_results` `ts_ptr`
			WHERE
				`inquiry_id` = 0
		");

		foreach($aResult as $aRow) {

			if($aRow['inquiry_id'] === null) {
				$this->logInfo('Couldn\'t find inquiry_id for placement test result '.$aRow['id']);
				continue;
			}

			DB::executePreparedQuery("
				UPDATE
					`ts_placementtests_results`
				SET
					`changed` = `changed`,
					`inquiry_id` = :inquiry_id
				WHERE
					`id` = :id
			", $aRow);

		}

		DB::commit(__METHOD__);

	}

}