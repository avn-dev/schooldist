<?php

/**
 * The timeclock class
 */
class Ext_Office_Timeclock extends Ext_Office_Office {

	/**
	 * The element data
	 */
	protected $_aData = array(
		'id' => 0,
		'changed' => '',
		'created' => null,
		'active' => 1,
	);

	/**
	 * The constructor
	 * 
	 * @param string The name of table
	 * @param int The element ID
	 */
	public function __construct($sTable = null, $iElementID = null) {
		$this->_sTable = $sTable;
		$this->_preloadFields();
		parent::__construct($sTable, $iElementID);
	}

	/**
	 * Returns the list of employee changing wishes
	 *
	 * @return array : The list of changing wishes
	 */
	public function getChangeWishes() {

		$sSQL = "
			SELECT
				`ot`.`id`,
				UNIX_TIMESTAMP(`ot`.`start`) AS `start`,
				UNIX_TIMESTAMP(`ot`.`end`) AS `end`,
				`ot`.`action`,
				`ot`.`change`,
				`ot`.`comment`,
				`c`.`".$this->_aConfig['pro_field_lastname']."` AS `lastname`,
				`c`.`".$this->_aConfig['pro_field_firstname']."` AS `firstname`,
				`op`.`title` AS `project`,
				`opc`.`title`
			FROM
				`office_timeclock` AS `ot`
			INNER JOIN
				`office_project_employees` AS `ope`
			ON
				`ot`.`p2e_id` = `ope`.`id`
			INNER JOIN
				`customer_db_".$this->_aConfig['pro_database']."` AS `c`
			ON
				`ope`.`employee_id` = `c`.`id`
			INNER JOIN
				`office_projects` AS `op`
			ON
				`ope`.`project_id` = `op`.`id`
			INNER JOIN
				`office_project_positions` AS `opp`
			ON
				`ot`.`p2p_id` = `opp`.`id`
			LEFT OUTER JOIN
				`office_project_categories` AS `opc`
			ON
				`opp`.`category_id` = `opc`.`id`
			WHERE
				`ot`.`action` != '' AND
				`ot`.`action` != 'declined' AND
				`ot`.`action` != 'accepted' AND
				`ot`.`active` = 1
			ORDER BY
				`ot`.`created` DESC,
				`c`.`id`, `ot`.`action`
		";
		$aResult = DB::getQueryData($sSQL);
		return $aResult;

	}

	public static function executeChecks($sDate) {

		$oTimeclock = new Ext_Office_Timeclock('office_timeclock');

		$aConfig = $oTimeclock->config;

		// Ungeschlossene Einträge automatisch schliessen
		if($aConfig['timeclock_auto_close']) {

			$aItems = $oTimeclock->getEmployeeDayUnclosed($sDate);

			foreach($aItems as $aItem) {

				$oStartDate = new WDDate($aItem['start'], WDDate::DB_TIMESTAMP);
				$oCompareDate = new WDDate($oStartDate);
				$oCompareDate->set($aConfig['timeclock_auto_close_time'], WDDate::TIMES);

				$iCompare = $oStartDate->compare($oCompareDate);

				$aData = array();
				$oEndDate = new WDDate;

				// Wenn der Start vor der Endzeit liegt
				if($iCompare < 0) {
					$aData['end'] = $oCompareDate->get(WDDate::DB_TIMESTAMP);
					$oEndDate = new WDDate($oCompareDate);
				} else {
					$aData['end'] = $oStartDate->get(WDDate::DB_TIMESTAMP);
					$oEndDate = new WDDate($oCompareDate);
				}

				DB::updateData('office_timeclock', $aData, '`id` = '.(int)$aItem['id']);

				$sText = "Du hast Dich bei folgendem Eintrag nicht ausgeloggt. Das System hat Dich automatisch ausgeloggt.\n";
				$sText .= "\nStartzeit: ".$oStartDate->get(WDDate::STRFTIME, '%x %X');
				$sText .= "\nEndzeit:   ".$oEndDate->get(WDDate::STRFTIME, '%x %X');
				$sText .= "\n\nViele Grüße\nPersonalabteilung";

				wdmail($aItem['email'], 'Zeiterfassung - Ausloggen vergessen', $sText);
				wdmail($aConfig['timeclock_admin_email'], 'Kopie: Zeiterfassung - Ausloggen vergessen', $sText."\n\n".print_r($aItem, 1));

			}

		}

		// Pausen und Gesamtzeit checken
		if(
			$aConfig['timeclock_check_max_worktime'] &&
			$aConfig['timeclock_check_break']
		) {

			$aEmployees = $oTimeclock->getEmployeeDayStats($sDate);

			foreach($aEmployees as $iEmployeeId => $aEmployee) {

				$oStartDate = new WDDate($aEmployee['start'], WDDate::DB_TIMESTAMP);
				$oEndDate = new WDDate($aEmployee['end'], WDDate::DB_TIMESTAMP);

				$oEmployee = new Ext_Office_Employee($iEmployeeId);

				// Mehr als 6 Stunden
				if(
					$aConfig['timeclock_check_break'] &&
					$aEmployee['duration'] > (6*60*60)
				) {

					$iMinBreak = 30*60;
					if($aEmployee['duration'] > (9*60*60)) {
						$iMinBreak = 45*60;
					}

					// Wenn zuwenig Pause gemacht wurde
					if($aEmployee['break'] < $iMinBreak) {

						$iMinBreak -= $aEmployee['break'];

						$aTimeclockEntries = $oEmployee->getTimeclockEntries($sDate);

						$aTimeclockEntry = reset($aTimeclockEntries);
						$aTimeclockEntry['start'];
						$oTimeclockEntryFirstStartDate = new WDDate($aTimeclockEntry['start'], WDDate::DB_TIMESTAMP);

						$oBreakTime = new WDDate($oTimeclockEntryFirstStartDate);
						$oBreakTime->add(4, WDDate::HOUR);

						foreach($aTimeclockEntries as $aTimeclockEntry) {

							$oTimeclockEntryEndDate = new WDDate($aTimeclockEntry['end'], WDDate::DB_TIMESTAMP);

							$iCompare = $oTimeclockEntryEndDate->compare($oBreakTime);

							if($iCompare >= 0) {
								break;
							}

						}

						// Eintrag splitten
						if($aTimeclockEntry['duration'] > $iMinBreak) {

							$oNewEnd = new WDDate($oBreakTime);
							$oNewEnd->sub($iMinBreak, WDDate::SECOND);

							$aNewData = DB::getRowData('office_timeclock', $aTimeclockEntry['id']);
							$aNewData['start'] = $oBreakTime->get(WDDate::DB_TIMESTAMP);
							unset($aNewData['id']);

							$aData = array('end' => $oNewEnd->get(WDDate::DB_TIMESTAMP));
							DB::updateData('office_timeclock', $aData, '`id` = '.(int)$aTimeclockEntry['id']);

							DB::insertData('office_timeclock', $aNewData);

						} else {

							$iToMuch = $iMinBreak;

							do {

								$aTimeclockEntry = array_pop($aTimeclockEntries);

								// Eintrag verkürzen
								if($aTimeclockEntry['duration'] > $iToMuch) {

									$oTimeclockEntryEndDate = new WDDate($aTimeclockEntry['end'], WDDate::DB_TIMESTAMP);
									$oTimeclockEntryEndDate->sub($iToMuch, WDDate::SECOND);

									$aData = array('end' => $oTimeclockEntryEndDate->get(WDDate::DB_TIMESTAMP));
									DB::updateData('office_timeclock', $aData, '`id` = '.(int)$aTimeclockEntry['id']);

									$iToMuch = 0;

								} else {

									$aData = array('active' => 0);
									DB::updateData('office_timeclock', $aData, '`id` = '.(int)$aTimeclockEntry['id']);

									$iToMuch -= $aTimeclockEntry['duration'];

								}

							} while ($iToMuch > 0);

						}

						$sText = "Du hast zu wenig Pausenzeit erfasst. Deine Zeiterfassung wurde automatisch korrigiert.\n";
						$sText .= "\nDatum:      ".$oStartDate->get(WDDate::STRFTIME, '%x');
						$sText .= "\n\nViele Grüße\nPersonalabteilung";

						wdmail($aEmployee['email'], 'Zeiterfassung - Zu wenig Pause', $sText);
						if($aConfig['timeclock_admin_email']) {
							wdmail($aConfig['timeclock_admin_email'], 'Kopie: Zeiterfassung - Zu wenig Pause', $sText."\n\n".print_r($aEmployee, 1));
						}

					}

				}

				// Mehr als 10 Stunden
				if(
					$aConfig['timeclock_check_max_worktime'] &&
					$aEmployee['duration'] > (10*60*60)
				) {

					$aTimeclockEntries = $oEmployee->getTimeclockEntries($sDate);

					$iToMuch = $aEmployee['duration'] - (10*60*60);

					do {

						$aTimeclockEntry = array_pop($aTimeclockEntries);

						// Eintrag verkürzen
						if($aTimeclockEntry['duration'] > $iToMuch) {

							$oTimeclockEntryEndDate = new WDDate($aTimeclockEntry['end'], WDDate::DB_TIMESTAMP);
							$oTimeclockEntryEndDate->sub($iToMuch, WDDate::SECOND);

							$aData = array('end' => $oTimeclockEntryEndDate->get(WDDate::DB_TIMESTAMP));
							DB::updateData('office_timeclock', $aData, '`id` = '.(int)$aTimeclockEntry['id']);

							$iToMuch = 0;

						} else {

							$aData = array('active' => 0);
							DB::updateData('office_timeclock', $aData, '`id` = '.(int)$aTimeclockEntry['id']);

							$iToMuch -= $aTimeclockEntry['duration'];

						}

					} while ($iToMuch > 0);

					$sText = "Du warst länger als zehn Stunden eingeloggt. Deine Zeiterfassung wurde automatisch korrigiert.\n";
					$sText .= "\nDatum:      ".$oStartDate->get(WDDate::STRFTIME, '%x');
					$sText .= "\n\nViele Grüße\nPersonalabteilung";

					wdmail($aEmployee['email'], 'Zeiterfassung - Zu lange eingeloggt', $sText);
					if($aConfig['timeclock_admin_email']) {
						wdmail($aConfig['timeclock_admin_email'], 'Kopie: Zeiterfassung - Zu lange eingeloggt', $sText."\n\n".print_r($aEmployee, 1));
					}

				}

			}

		}

	}

	/**
	 * Sucht alle Mitarbeiter die an einem Tag gearbeitet haben und einen
	 * Eintrag nicht geschlossen haben
	 *
	 * @param string (DB_DATE)
	 * @return array
	 */
	public function getEmployeeDayUnclosed($sDate) {

		$sSql = "
			SELECT
				`ot`.`id`,
				`c`.`id` `employee_id`,
				`ot`.`start`,
				`ot`.`end`,
				`c`.`".$this->_aConfig['pro_field_email']."` AS `email`,
				`c`.`".$this->_aConfig['pro_field_lastname']."` AS `lastname`,
				`c`.`".$this->_aConfig['pro_field_firstname']."` AS `firstname`
			FROM
				`office_timeclock` AS `ot` INNER JOIN
				`office_project_employees` AS `ope` ON
					`ot`.`p2e_id` = `ope`.`id` INNER JOIN
				`customer_db_".$this->_aConfig['pro_database']."` AS `c` ON
					`ope`.`employee_id` = `c`.`id`
			WHERE
				DATE(`ot`.`start`) = :day AND
				`ot`.`active` = 1 AND
				`ot`.`action` != 'new' AND
				`ot`.`action` != 'declined' AND
				`ot`.`end` = 0
			ORDER BY
				`c`.`id`
		";

		$aSql = array(
			'day' => $sDate
		);

		$aResult = DB::getQueryData($sSql, $aSql);
		return (array)$aResult;

	}

	/**
	 * Sucht alle Mitarbeiter die an einem Tag gearbeitet haben mit der Arbeits-
	 * und Pausenzeit und der Information ob eine Eintrag nicht geschlossen wurde
	 *
	 * @param string (DB_DATE)
	 * @return array
	 */
	public function getEmployeeDayStats($sDate) {

		$sSql = "
			SELECT
				`ot`.`id`,
				`c`.`id` `employee_id`,
				UNIX_TIMESTAMP(`ot`.`end`) - UNIX_TIMESTAMP(`ot`.`start`) `duration`,
				`ot`.`end`,
				`ot`.`start`,
				`c`.`".$this->_aConfig['pro_field_email']."` AS `email`,
				`c`.`".$this->_aConfig['pro_field_lastname']."` AS `lastname`,
				`c`.`".$this->_aConfig['pro_field_firstname']."` AS `firstname`
			FROM
				`office_timeclock` AS `ot` INNER JOIN
				`office_project_employees` AS `ope` ON
					`ot`.`p2e_id` = `ope`.`id` INNER JOIN
				`customer_db_".$this->_aConfig['pro_database']."` AS `c` ON
					`ope`.`employee_id` = `c`.`id`
			WHERE
				DATE(`ot`.`start`) = :day AND
				`ot`.`active` = 1 AND
				`ot`.`action` != 'new' AND
				`ot`.`action` != 'declined'
			ORDER BY
				`c`.`id`,
				`ot`.`start`
		";
		$aSql = array(
			'day' => $sDate
		);
		$aResult = DB::getQueryData($sSql, $aSql);

		$aEmployees = array();
		foreach($aResult as $aEntry) {

			if(!isset($aEmployees[$aEntry['employee_id']])) {

				$aEmployees[$aEntry['employee_id']] = array(
					'duration' => 0,
					'break' => 0,
					'start' => '',
					'end' => '',
					'email' => $aEntry['email'],
					'lastname' => $aEntry['lastname'],
					'firstname' => $aEntry['firstname'],
					'timeclock_id' => $aEntry['id']
				);

				$iBreak = 0;

			} else {

				$oLastEndDate = new WDDate($aEmployees[$aEntry['employee_id']]['end'], WDDate::DB_TIMESTAMP);
				$oStartDate = new WDDate($aEntry['start'], WDDate::DB_TIMESTAMP);

				$iBreak = $oLastEndDate->getDiff(WDDate::SECOND, $oStartDate);

			}

			$aEmployees[$aEntry['employee_id']]['break'] += $iBreak;
			$aEmployees[$aEntry['employee_id']]['duration'] += $aEntry['duration'];
			$aEmployees[$aEntry['employee_id']]['start'] = $aEntry['start'];
			$aEmployees[$aEntry['employee_id']]['end'] = $aEntry['end'];

		}

		return $aEmployees;

	}

	/**
	 * Counts how many differently days are in the month
	 */
	public function countMonthDays($iMonth, $iYear) {

		$oDate = new WDDate();
		$oDate->set('01.'.$iMonth.'.'.$iYear, WDDate::DATES);

		$aDays = array();
		for($i = 1; $i <= $oDate->get(WDDate::MONTH_DAYS); $i++) {
			if(!isset($aDays[$oDate->get(WDDate::WEEKDAY)])) {
				$aDays[$oDate->get(WDDate::WEEKDAY)] = 0;
			}
			$aDays[$oDate->get(WDDate::WEEKDAY)]++;
			$oDate->add(1, WDDate::DAY);
		}

		return $aDays;

	}

	/**
	 * Returns the list of holidays by year
	 * 
	 * @param int : The year
	 * @return array : The list of holidays
	 */
	public function getHolidaysList($iYear) {

		// Convert the timestamps
		$sTimestamps = $this->_convertTimestamps('into_unix_ts');

		$sSQL = "
			SELECT
				*
				" . $sTimestamps . "
			FROM
				`office_timeclock_holidays`
			WHERE
				YEAR(`date`) = :iYear OR
				(
					`repeat` = 1 AND
					YEAR(`date`) <= :iSecondYear
				)
			ORDER BY
				DATE_FORMAT(`date`, '%c%d')+1
		";
		$aHolidays = DB::getPreparedQueryData($sSQL, array('iYear' => $iYear, 'iSecondYear' => $iYear));
		return $aHolidays;

	}

	/**
	 * Returns the list of available years from the DB
	 * 
	 * @return array : The list of years
	 */
	public function getHolidayYears() {

		$sSQL = "
			SELECT
				YEAR(`oth`.`date`) AS `year`,
				(SELECT MIN(YEAR(`date`)) FROM `office_timeclock_holidays`) AS `min`,
				IF(
					(
						SELECT COUNT(*)
						FROM `office_timeclock_holidays`
						WHERE `repeat` = 1 AND YEAR(`oth`.`date`) = YEAR(`date`)
					) > 0,
					'1',
					'0'
				) AS `count`
			FROM
				`office_timeclock_holidays` AS `oth`
			GROUP BY
				YEAR(`oth`.`date`)
			ORDER BY
				`oth`.`date`
		";
		$aHolidayYears = DB::getQueryData($sSQL);

		// Prepare years
		$aYears = array();
		$iCountYear = null;
		if(isset($aHolidayYears[0])) {
			$bFlag = false;
			foreach((array)$aHolidayYears as $iKey => $aValue) {
				if($aHolidayYears[$iKey]['count'] == 1 && !isset($iCountYear)) {
					$bFlag = true;
					$iCountYear = $aValue['year'];
				}
				if(!$bFlag) {
					$aYears[$aValue['year']] = $aValue['year'];
				} elseif($bFlag) {
					for($i = $iCountYear; $i <= date('Y')+1; $i++) {
						$aYears[$i] = $i;
					}
					break;
				}
			}
		} else {
			$aYears[date('Y')+1] = date('Y')+1;
			$aYears[date('Y')] = date('Y');
		}

		return $aYears;

	}


	/**
	 * Creates / fills fields for new entry in DB
	 */
	protected function _preloadFields() {

		switch($this->_sTable) {
			case 'office_timeclock_holidays':
				// Set the default element data
				$this->_aData['title'] = '';
				$this->_aData['date'] = null;
				$this->_aData['repeat'] = 0;
				$this->_aData['notice'] = '';
				// Set required fields
				$this->_aRequiredFields[] = array('field' => 'title', 'type' => 'TEXT');
				$this->_aRequiredFields[] = array('field' => 'date', 'type' => 'TIMESTAMP');
				break;
			
			case 'office_timeclock_free_dates':
				// Set the default element data
				$this->_aData['employee_id'] = 0;
				$this->_aData['type'] = '';
				$this->_aData['from'] = null;
				$this->_aData['till'] = null;
				$this->_aData['notice'] = '';
				// Set required fields
				$this->_aRequiredFields[] = array('field' => 'employee_id', 'type' => 'ID');
				$this->_aRequiredFields[] = array('field' => 'from', 'type' => 'TIMESTAMP');
				$this->_aRequiredFields[] = array('field' => 'till', 'type' => 'TIMESTAMP');
				break;

			case 'office_timeclock':
				// Set the default element data
				$this->_aData['start'] = null;
				$this->_aData['end'] = null;
				$this->_aData['salary'] = 0;
				$this->_aData['cleared'] = 0;
				$this->_aData['p2p_id'] = 0;
				$this->_aData['p2e_id'] = 0;
				$this->_aData['action'] = '';
				$this->_aData['change'] = '';
				// Set required fields
				$this->_aRequiredFields[] = array('field' => 'start', 'type' => 'TIMESTAMP');
				$this->_aRequiredFields[] = array('field' => 'p2p_id', 'type' => 'ID');
				$this->_aRequiredFields[] = array('field' => 'p2e_id', 'type' => 'ID');
				break;

		}

	}

	public static function getLastTimeclockEntry($iEmployeeID) {

		$sSQL = "
			SELECT
				`ot`.`id`
			FROM
				`office_timeclock` AS `ot`
			INNER JOIN
				`office_project_employees` AS `ope`
			ON
				`ope`.`id` = `ot`.`p2e_id`
			WHERE
				UNIX_TIMESTAMP(`end`) = 0 AND
				`ope`.`employee_id` = :iEmployeeID
		";
		$iLastTimeclockID = DB::getQueryOne($sSQL, array('iEmployeeID' => (int)$iEmployeeID));
		return $iLastTimeclockID;

	}
	
	public static function getSalary($iEmployeeID, $iStart, $iEnd, $iProjectID = 0, $iPositionID = 0) {

		$sSQL = "
			SELECT
				`salary`
			FROM
				`office_employee_contract_data`
			WHERE
				`employee_id` = :iEmployeeID AND
				`active` = 1 AND
				(
					`until` = 0 OR
					(
						UNIX_TIMESTAMP(`until`) > ".(int)$iStart." AND
						UNIX_TIMESTAMP(`from`) < ".(int)$iEnd."
					)
				)
			LIMIT
				1
		";
		$iSalary = DB::getQueryOne($sSQL, array('iEmployeeID' => $iEmployeeID));

		if(!is_numeric($iSalary)) {
			$sSQL = "
				SELECT 
					`opc`.`price`
				FROM
					`office_project_categories` AS `opc`
				INNER JOIN
					`office_project_positions` AS `opp`
				ON
					`opc`.`id` = `opp`.`category_id`
				WHERE
					`opp`.`id` = :iPositionID AND
					`opp`.`project_id` = :iProjectID
				LIMIT
					1
			";
			$aSQL = array(
				'iProjectID' => $iProjectID,
				'iPositionID' => $iPositionID
			);
			$iSalary = DB::getQueryOne($sSQL, $aSQL);
		}

		return $iSalary;

	}

	public static function getProjects($iEmployeeID, $sWhere = '') {

		$sSQL = "
			SELECT
				`ope`.`project_id`,
				`op`.`title`
			FROM
				`office_project_employees` AS `ope`
			INNER JOIN
				`office_projects` AS `op`
			ON
				`ope`.`project_id` = `op`.`id`
			WHERE
				`ope`.`employee_id` = :iEmployeeID
				{WHERE}
			ORDER BY
				`op`.`title`
		";
		$aProjects = DB::getQueryPairs(str_replace('{WHERE}', $sWhere, $sSQL), array('iEmployeeID' => $iEmployeeID));
		return $aProjects;

	}

	public static function getTimes($iEmployeeID, $iFrom, $iTill, $iProjectID = 0) {

		$sWhere = '';
		$aTimes = array();
		$aSQL = array('iEmployeeID' => $iEmployeeID);

		$sWhere .= " AND UNIX_TIMESTAMP(`ot`.`start`) >= ".(int)$iFrom;
		$sWhere .= " AND ((UNIX_TIMESTAMP(`ot`.`end`) > 0 AND UNIX_TIMESTAMP(`ot`.`end`) <= ".(int)$iTill.")";
		$sWhere .= " OR (UNIX_TIMESTAMP(`ot`.`end`) = 0 AND UNIX_TIMESTAMP(`ot`.`start`) < ".(int)$iTill."))";

		if(is_numeric($iProjectID) && $iProjectID != 0) {
			$sWhere .= " AND `ope`.`project_id` = ".(int)$iProjectID;
		}

		$sSQL = "
			SELECT
				`ot`.`id`,
				UNIX_TIMESTAMP(`ot`.`start`) AS `start`,
				UNIX_TIMESTAMP(`ot`.`end`) AS `end`,
				`ot`.`action`,
				`ot`.`p2p_id` AS `position_id`,
				`ot`.`cleared`,
				`ot`.`comment`,
				`opc`.`title`,
				`opc`.`id` AS `category_id`,
				`opa`.`alias`,
				`opa`.`id` AS `alias_id`,
				`op`.`title` AS `project`,
				`opp`.`project_id`
			FROM
				`office_timeclock`			AS `ot`
					INNER JOIN
				`office_project_employees`	AS `ope`
					ON
				`ot`.`p2e_id` = `ope`.`id`
					INNER JOIN
				`office_project_positions`	AS `opp`
					ON
				`ot`.`p2p_id` = `opp`.`id`
					INNER JOIN
				`office_projects`			AS `op`
					ON
				`ope`.`project_id` = `op`.`id`
					LEFT OUTER JOIN
				`office_project_categories`	AS `opc`
					ON
				`opp`.`category_id` = `opc`.`id`
					LEFT OUTER JOIN
				`office_project_aliases`	AS `opa`
					ON
				`opp`.`alias_id` = `opa`.`id`
			WHERE
				`ope`.`employee_id` = :iEmployeeID
					AND
				`ot`.`active` = 1
					{WHERE}
			ORDER BY
				`ot`.`start` DESC
		";
		$aTimes = DB::getPreparedQueryData(str_replace('{WHERE}', $sWhere, $sSQL), $aSQL);
		return $aTimes;

	}

}
