<?php

class Ext_Office_Absence {

	/**
	 * The office config array
	 * 
	 * @var array
	 */
	protected $_aConfig;

	/**
	 * The calculation start and end dates
	 * 
	 * @var array
	 */
	protected $_aTimes;

	/**
	 * The customer DB-Table name
	 * 
	 * @var string
	 */
	protected $_sTable;

	/**
	 * The overtimes count start year
	 * 
	 * @var int
	 */
	protected $_iStartYear;

	/**
	 * The ID of the employee
	 * 
	 * @var int
	 */
	protected $_iEmployeeID;

	/**
	 * The constructor
	 * 
	 * @param int $iEmployeeID
	 * @param string $sFrom d.m.Y
	 * @param string $sTill d.m.Y
	 */
	public function __construct($iEmployeeID = 0, $sFrom = null, $sTill = null) {

		$this->_aConfig = Ext_Office_Config::getInstance();
		$this->_sTable = 'customer_db_' . $this->_aConfig['pro_database'];
		$this->_iStartYear = $this->_aConfig['overtimes_start'];
		$this->_iEmployeeID = (int)$iEmployeeID;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Define and set the start date

		// Define start date of calculation
		$oFrom = new WDDate();
		$oFrom->set('00:00:00', WDDate::TIMES);

		if(WDDate::isDate($sFrom, WDDate::DATES)) {
			$oFrom->set($sFrom, WDDate::DATES);
		} else {
			$oFrom->set(date('01.01.Y'), WDDate::DATES);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Define and set the end date

		// Define end date of calculation
		$oTill = new WDDate();
		$oTill->set('23:59:59', WDDate::TIMES);

		if(WDDate::isDate($sTill, WDDate::DATES)) {
			$oTill->set($sTill, WDDate::DATES);
			if($oTill->get(WDDate::TIMESTAMP) > time()) {
				$oTill->set(date('d.m.Y'), WDDate::DATES);
			}
		} else {
			$oTill->set(date('d.m.Y'), WDDate::DATES);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Years overflow

		if($this->_iStartYear > $oFrom->get(WDDate::YEAR)) {
			$oFrom->set($this->_iStartYear, WDDate::YEAR);
		}
		if($this->_iStartYear > $oTill->get(WDDate::YEAR)) {
			$oTill->set($this->_iStartYear, WDDate::YEAR);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Change the years

		if($oFrom->get(WDDate::TIMESTAMP) > $oTill->get(WDDate::TIMESTAMP)) {
			$iFrom = $oFrom->get(WDDate::TIMESTAMP);
			$oFrom = new WDDate($oTill);
			$oTill = new WDDate($iFrom);
		}

		$this->_aTimes = array(
			'from' => $oFrom,
			'till' => $oTill
		);

	}

	/**
	 * Erstellt ein Array it Tagen im angegebenen Zeitraum
	 *
	 * array(
	 *     <jahr> => array(
	 *         <monat> => array(
	 *             <tag> => <nummer>
	 *         )
	 *     )
	 * )
	 *
	 * <nummer> ist die Nummer des Wochentags (1 = Montag, 7 = Sonntag)
	 * oder 8 wenn es ein Feiertag ist.
	 *
	 * @param int $iFrom
	 * @param int $iTill
	 */
	public function createDaysArray($iFrom, $iTill) {

		$oDate = new WDDate($iFrom);
		$aDays = array();
		$aCacheHolidays = array();

		while($oDate->get(WDDate::TIMESTAMP) < $iTill) {

			$iYear	= $oDate->get(WDDate::YEAR);
			$iMonth	= $oDate->get(WDDate::MONTH);
			$iDay	= $oDate->get(WDDate::DAY);

			// Get holidays per month
			if(!isset($aCacheHolidays[$iYear][$iMonth])) {
				$aHolidays = $this->getHolidays($iMonth, $iYear);
				$aCacheHolidays[$iYear][$iMonth] = $aHolidays;
			}

			$aDays[$iYear][$iMonth][$iDay] = $oDate->get(WDDate::WEEKDAY);

			// Check the one day in holidays in the moth
			if(isset($aCacheHolidays[$iYear][$iMonth][(int)$iDay])) {
				// Write the WEEKDAY 8 if is a holiday
				$aDays[$iYear][$iMonth][$iDay] = 8;
			}

			$oDate->add(1, WDDate::DAY);

		}

		return $aDays;

	}

	/**
	 * Get absences list data for every employee
	 * 
	 * @param int $iMonth
	 * @param int $iYear
	 */
	public function getAbsencesData($iYear, $iMonth=null, $sType='sick') {

		if($iMonth) {
			$sDateFrom = sprintf('%04d-%02d-01 00:00:00', $iYear, $iMonth);
		} else {
			$sDateFrom = sprintf('%04d-02-01 00:00:00', $iYear);
		}

		$oDate = new WDDate($sDateFrom, WDDate::DB_TIMESTAMP);
		$oDate->sub(1, WDDate::MONTH);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get employees

		$aEmployees = $this->getEmployees();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get free dates

		$oDateFrom = new WDDate($oDate);
		$oDateTill = new WDDate($oDateFrom);
		if($iMonth) {
			$oDateTill->add(1, WDDate::MONTH)->sub(1, WDDate::SECOND);
		} else {
			$oDateTill->add(1, WDDate::YEAR)->sub(1, WDDate::SECOND);
		}

		foreach((array)$aEmployees as $iKey => $aEmployee) {

			$aTempData = null;

			$sSQL = "
				SELECT
					*,
					UNIX_TIMESTAMP(`from`) AS `from_unix`,
					UNIX_TIMESTAMP(`till`) AS `till_unix`
				FROM
					`office_timeclock_free_dates`
				WHERE
					`active` = 1 AND
					`employee_id` = :iEmployeeID AND
					`type` != 'overtime_paid' AND
					UNIX_TIMESTAMP(`from`) < :iTill AND
					UNIX_TIMESTAMP(`till`) > :iFrom
				ORDER BY
					`from`
			";
			$aSQL = array(
				'iEmployeeID' => $aEmployee['id'],
				'iFrom' => $oDateFrom->get(WDDate::TIMESTAMP),
				'iTill' => $oDateTill->get(WDDate::TIMESTAMP)
			);
			$aDates = (array)DB::getPreparedQueryData($sSQL, $aSQL);

			// Each all free dates of 3 month
			foreach($aDates as $aFreeDate) {
				if($aFreeDate['type'] == $sType) {
					$aTempData[] = $aFreeDate;
				}
			}

			if(empty($aTempData)) {
				unset($aEmployees[$iKey]);
			} else {
				$aEmployees[$iKey]['data'] = $aTempData;	
			}

		}

		return (array)$aEmployees;

	}

	/**
	 * Get absences list data for every employee
	 * 
	 * @param int $iMonth
	 * @param int $iYear
	 */
	public function getAbsencesList($iMonth, $iYear) {

		$sDateFrom = sprintf('%04d-%02d-01 00:00:00', $iYear, $iMonth);
		$oDate = new WDDate($sDateFrom, WDDate::DB_TIMESTAMP);
		$oDate->sub(1, WDDate::MONTH);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create days array

		$oTempDate = new WDDate($oDate);
		$iTemp = 0;
		$aData = $aMonth = array();

		while($iTemp < 3) {

			$iMonth = $oDate->get(WDDate::MONTH);
			$iYear = $oDate->get(WDDate::YEAR);
			$sColor = '';
			$aLimit = $oDate->getMonthLimits();

			if(
				$oDate->get(WDDate::WEEKDAY) == 6 ||
				$oDate->get(WDDate::WEEKDAY) == 7
			) {
				$sColor = '#DDD';
			}

			if(empty($aMonth)) {
				$aHolidays = $this->getHolidays($iMonth, $iYear);
			}

			if(array_key_exists((int)$oDate->get(WDDate::DAY), $aHolidays)) {
				$sColor = '#66FFFF';
			}

			$aMonth[] = array('day' => $oDate->get(WDDate::DAY), 'color' => $sColor);

			$oDate->add(1, WDDate::DAY);

			if($iMonth != $oDate->get(WDDate::MONTH)) {
				$aData[] = array(
					'year' => $iYear,
					'month' => $iMonth,
					'from' => $aLimit['start'],
					'till' => $aLimit['end'],
					'days' => $aMonth
				);
				$aMonth = array();
				$iTemp++;
			}

		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get employees

		$aEmployees = $this->getEmployees();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get free dates

		$oDateFrom = new WDDate($oTempDate);
		$oDateTill = new WDDate($oDateFrom);
		$oDateTill->add(3, WDDate::MONTH)->sub(1, WDDate::SECOND);

		foreach((array)$aEmployees as $iKey => $aEmployee) {

			$aTempData = $aData;

			$sSQL = "
				SELECT
					*,
					UNIX_TIMESTAMP(`from`) AS `from_unix`,
					UNIX_TIMESTAMP(`till`) AS `till_unix`
				FROM
					`office_timeclock_free_dates`
				WHERE
					`active` = 1 AND
					`employee_id` = :iEmployeeID AND
					`type` != 'overtime_paid' AND
					UNIX_TIMESTAMP(`from`) < :iTill AND
					UNIX_TIMESTAMP(`till`) > :iFrom
			";
			$aSQL = array(
				'iEmployeeID' => $aEmployee['id'],
				'iFrom' => $oDateFrom->get(WDDate::TIMESTAMP),
				'iTill' => $oDateTill->get(WDDate::TIMESTAMP)
			);
			$aDates = DB::getPreparedQueryData($sSQL, $aSQL);

			// Each all free dates of 3 month
			foreach((array)$aDates as $aFreeDate) {

				switch($aFreeDate['type']) {
					case 'holiday':
						$sColor = '#33FF33';
						break;
					case 'sick':
						$sColor = '#FF9900';
						break;
					case 'overtime':
						$sColor = '#FFFF00';
						break;
				}

				// Each the copy of days array from top
				foreach((array)$aTempData as $iM => $aMonth) {

					$oDayDate = new WDDate($aFreeDate['from_unix']);
					$iTempM = $iM;

					if($aMonth['till'] < $oDayDate->get(WDDate::TIMESTAMP)) {
						continue;
					}

					$iLastMonth = $oDayDate->get(WDDate::MONTH);

					while($aFreeDate['quote'] > 0) {

						$iTempDay = $oDayDate->get(WDDate::DAY) - 1;

						if(!isset($aTempData[$iTempM]['days'][$iTempDay])) {
							break;
						}

						if(
							isset($aTempData[$iTempM]['days'][$iTempDay]) &&
							$aTempData[$iTempM]['days'][$iTempDay]['color'] != '#66FFFF' &&
							$aTempData[$iTempM]['days'][$iTempDay]['color'] != '#DDD'
						) {
							// Get percent of free time to day
							if($aFreeDate['quote'] >= 1) {
								$iQuote = 100;
							} else {
								$iQuote = $aFreeDate['quote'] * 100;
							}
							$aEntry = array('quote' => $iQuote, 'color' => $sColor);
							$aTempData[$iTempM]['days'][$iTempDay]['entries'][] = $aEntry;
							$aFreeDate['quote'] -= 1;
						}

						$oDayDate->add(1, WDDate::DAY);

						if($iLastMonth != $oDayDate->get(WDDate::MONTH)) {
							$iLastMonth = $oDayDate->get(WDDate::MONTH);
							$iTempM++;
						}

					}

				}

			}

			$aEmployees[$iKey]['data'] = $aTempData;

		}

		$aReturn = array(
			'head' => $aData,
			'data' => $aEmployees
		);

		return $aReturn;

	}

	/**
	 * Get available contracts between start and end times
	 */
	public function getContracts($aParams = array()) {

		$sSQL = "
			SELECT
				*,
				UNIX_TIMESTAMP(`from`) AS `from_unix`,
				UNIX_TIMESTAMP(`until`) AS `till_unix`
			FROM
				`office_employee_contract_data`
			WHERE
				`active` = 1 AND
				`employee_id` = :iEmployeeID AND
				(
					UNIX_TIMESTAMP(`until`) > :iFrom OR
					UNIX_TIMESTAMP(`until`) = 0
				) AND (
					(
						:iTill > 0 AND
						UNIX_TIMESTAMP(`from`) < :iTill
					) OR (
						:iTill = 0
					)
				)
			ORDER BY
				`from`
		";

		if(empty($aParams)) {
			$aSQL = array(
				'iEmployeeID' => $this->_iEmployeeID,
				'iFrom' => $this->_aTimes['from']->get(WDDate::TIMESTAMP),
				'iTill' => $this->_aTimes['till']->get(WDDate::TIMESTAMP)
			);
		} else {
			$aSQL = array(
				'iEmployeeID' => $aParams['employee_id'],
				'iFrom' => $aParams['from'],
				'iTill' => $aParams['till']
			);
		}

		$aContracts = DB::getPreparedQueryData($sSQL, $aSQL);
		return $aContracts;

	}

	/**
	 * Get the list of employees in selected absence groups
	 * or one employee if $iEmployeeID is defined
	 * 
	 * @param int $iEmployeeID
	 */
	public function getEmployees($iEmployeeID = 0) {

		$aAbsences = $this->_aConfig['absence_groups'];
		$aAbsences = array_keys((array)$aAbsences);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Prepare selected groups

		if(empty($aAbsences)) {

			$sGroups = "";

		} else {

			$sGroups = " AND ( ";
			$i = 1;

			foreach($aAbsences as $iGroup) {
				$sGroups .= " `cdb`.`groups` LIKE '%|" . $iGroup . "|%' ";
				if($i < count((array)$aAbsences)) {
					$i++;
					$sGroups .= " OR ";
				}
			}

			$sGroups .= " ) ";

		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get employees

		$sWhere = "";

		if((int)$iEmployeeID > 0) {
			$sWhere .= " AND `cdb`.`id` = " . (int)$iEmployeeID;
		}

		$sSQL = "
			SELECT
				`cdb`.`id`,
				CONCAT(`".$this->_aConfig['pro_field_firstname']."`, ' ', `".$this->_aConfig['pro_field_lastname']."`) AS `name`
			FROM
				`customer_db_".$this->_aConfig['pro_database']."` AS `cdb`
			WHERE
				`cdb`.`active` = 1
				".$sGroups."
				{WHERE}
			GROUP BY 
				`cdb`.`id`
			ORDER BY 
				`name`
		";
		$sSQL = str_replace('{WHERE}', $sWhere, $sSQL);
		$aEmployees = DB::getQueryData($sSQL);

		return $aEmployees;

	}

	/**
	 * Get the list of holidays by month and year
	 * 
	 * @param int $iMonth
	 * @param int $iYear
	 */
	public function getHolidays($iMonth, $iYear) {

		$sSQL = "
			SELECT
				DAY(`date`) AS `day`
			FROM
				`office_timeclock_holidays`
			WHERE
				`active` = 1 AND
				(
					(
						YEAR(`date`) = :iYear AND
						MONTH(`date`) = :iMonth
					)
						OR
					(
						`repeat` = 1 AND
						YEAR(`date`) < :iYear AND
						MONTH(`date`) = :iMonth
					)
				)
		";
		$aSQL = array(
			'iYear' => $iYear,
			'iMonth' => $iMonth
		);
		$aHolidays = DB::getPreparedQueryData($sSQL, $aSQL);

		$aReturn = array();
		foreach((array)$aHolidays as $aValue) {
			$aReturn[(int)$aValue['day']] = true;
		}

		return $aReturn;

	}

	/**
	 * Get the data of "have to work" / "worked" times in a month
	 * 
	 * @param int $iEmployeeID
	 * @param int $iMonth
	 * @param int $iYear
	 */
	public function getMonthGraph($iEmployeeID, $iMonth = null, $iYear = null) {

		if(
			empty($iMonth) ||
			empty($iYear)
		) {
			$iMonth	= date('m');
			$iYear	= date('Y');
		}

		$oDate = new WDDate();
		$oDate->set(1, WDDate::DAY);
		$oDate->set($iMonth, WDDate::MONTH);
		$oDate->set($iYear, WDDate::YEAR);
		$oDate->set('00:00:00', WDDate::TIMES);

		$oTill = new WDDate($oDate);
		$oTill->set('23:59:59', WDDate::TIMES)->set($oDate->get(WDDate::MONTH_DAYS), WDDate::DAY);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aData = array();

		while((int)$oDate->get(WDDate::MONTH) == (int)$iMonth) {

			$oTill = new WDDate($oDate);
			$oTill->add(1, WDDate::DAY)->sub(1, WDDate::SECOND);

			$aParams = array(
				'employee_id' => $iEmployeeID,
				'from_ts' => $oDate->get(WDDate::TIMESTAMP),
				'till_ts' => $oTill->get(WDDate::TIMESTAMP)
			);

			$a = $aTimes = $this->getWorktimesList($aParams, true);
			$aTimes = $aTimes['data'][0];

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Calculate times

			$iHaveTo = $iWorked = 0;

			if(
				$aTimes['worked'] > 0 && 
				$aTimes['have_to'] > 0
			) {
				$iWorked = round($aTimes['worked'] / 3600, 1);
				$iHaveTo = round($aTimes['have_to'] / 3600, 1);
			} elseif(
				$aTimes['worked'] > 0 && 
				$aTimes['have_to'] <= 0
			) {
				$iWorked = round($aTimes['worked'] / 3600, 1);
				$iHaveTo = 0;
			} else {
				$iWorked = 0;
				$iHaveTo = round($aTimes['have_to'] / 3600, 1);
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			$aData[$oDate->get(WDDate::DAY)] = array('have_to' => $iHaveTo, 'worked' => $iWorked);
			$oDate->add(1, WDDate::DAY);

		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aChart_1 = $aChart_2 = array();

		foreach((array)$aData as $iKey => $aValue) {
			$aChart_1[] = $aValue['have_to'];
			$aChart_2[] = $aValue['worked'];
			$aLabels[]	= $iKey;
		}

		// Init dataset
		$oDataSet = new WDChart_Data;
		$oDataSet->AddPoint($aChart_1, "Serie1");
		$oDataSet->AddPoint($aChart_2, "Serie2");
		$oDataSet->AddPoint($aLabels, "XLabel");
		$oDataSet->AddSerie("Serie1");
		$oDataSet->AddSerie("Serie2");
		$oDataSet->SetYAxisUnit(" h");
		$oDataSet->SetAbsciseLabelSerie('XLabel');

		// Init the chart
		$oChart = new WDChart(750, 250);
		$oChart->setFontProperties("arial.ttf", 8);
		$oChart->setGraphArea(50,30,720,200);
		$oChart->drawFilledRoundedRectangle(7,7,743,223,5,240,240,240);
		$oChart->drawRoundedRectangle(5,5,745,225,5,230,230,230);
		$oChart->drawGraphArea(255,255,255,TRUE);
		$oChart->drawScale($oDataSet->GetData(),$oDataSet->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,2,TRUE);
		$oChart->drawGrid(4,TRUE,230,230,230,50);
		$oChart->drawBarGraph($oDataSet->GetData(), $oDataSet->GetDataDescription(), TRUE);

		$sTempFile = '/storage/tmp/'.Util::generateRandomString(16).'.png';
		$oChart->Save(\Util::getDocumentRoot().$sTempFile);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aData = array(
			'employee_id' => $iEmployeeID,
			'month' => $iMonth,
			'year' => $iYear,
			'file' => $sTempFile
		);

		return $aData;
	}

	/**
	 * Return available overtimes in minutes
	 */
	public function getOvertimes() {

		$iTotal = 0;
		$iMinus = 0;

		$aContracts = $this->getContracts();

		$oEmployee = new Ext_Office_Employee($this->_iEmployeeID);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get worked times

		foreach((array)$aContracts as $aValue) {

			if($aValue['till_unix'] == 0) {
				$aValue['till_unix'] = time();
			}

			$iResult = $this->getWorkedSeconds($this->_iEmployeeID, $aValue['from_unix'], $aValue['till_unix']);
			$iTotal += $iResult;
			$aTimes = $oEmployee->getSollTimes($aValue['from_unix'], $aValue['till_unix']);
			$iMinus += $aTimes['H'] * 3600 + $aTimes['M'] * 60 + $aTimes['S'];

		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return ($iTotal - $iMinus);

	}


	/**
	 * Get the list of all types of work or free dates
	 * 
	 * @param array $aParams
	 * @param bool $bUseTimestamps
	 */
	public function getWorktimesList($aParams, $bUseTimestamps = false) {

		if($bUseTimestamps) {

			$oDateFrom = new WDDate($aParams['from_ts']);
			$oDateTill = new WDDate($aParams['till_ts']);
			$aEmployees = $this->getEmployees($aParams['employee_id']);

		} else {

			$sDateFrom = $aParams['year_from'].'-'.sprintf('%02d', $aParams['month_from']).'-01 00:00:00';
			$sDateTill = $aParams['year_till'].'-'.sprintf('%02d', $aParams['month_till']).'-01 23:59:59';

			$oDateFrom = new WDDate($sDateFrom, WDDate::DB_TIMESTAMP);
			$oDateTill = new WDDate($sDateTill, WDDate::DB_TIMESTAMP);

			// Set the last day of the month
			$oDateTill->set($oDateTill->get(WDDate::MONTH_DAYS), WDDate::DAY);

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Check errors

			if($oDateTill->get(WDDate::TIMESTAMP) > time()) {
				$oDateTill = new WDDate();
				$oDateTill->set('23:59:59', WDDate::TIMES)->sub(1, WDDate::DAY);
			}
			if($oDateFrom->get(WDDate::TIMESTAMP) > $oDateTill->get(WDDate::TIMESTAMP)) {
				return array('ERROR' => 'FILTER');
			}
			$aEmployees = $this->getEmployees();

		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aReturnDates = array(
			'month_from' => $oDateFrom->get(WDDate::MONTH),
			'year_from' => $oDateFrom->get(WDDate::YEAR),
			'month_till' => $oDateTill->get(WDDate::MONTH),
			'year_till' => $oDateTill->get(WDDate::YEAR),
			'from' => $oDateFrom->get(WDDate::DATES).' '.$oDateFrom->get(WDDate::TIMES),
			'till' => $oDateTill->get(WDDate::DATES).' '.$oDateTill->get(WDDate::TIMES)
		);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create days array

		$aDays = $this->createDaysArray($oDateFrom->get(WDDate::TIMESTAMP), $oDateTill->get(WDDate::TIMESTAMP));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get the times to have to work

		foreach((array)$aEmployees as $iKey => $aEmployee) {

			$aTempParams = array(
				'employee_id' => $aEmployee['id'],
				'from' => $oDateFrom->get(WDDate::TIMESTAMP),
				'till' => $oDateTill->get(WDDate::TIMESTAMP)
			);
			$aContracts = $this->getContracts($aTempParams);

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			$iWorked = $this->getWorkedSeconds($aTempParams['employee_id'], $aTempParams['from'], $aTempParams['till']);
			$aEmployees[$iKey]['worked'] = $iWorked;

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			$iSecondsToWork = 0;
			$fDayHours = 0.00;
			$aMainFreeDates = array(
				'sick' => 0,
				'holi' => 0,
				'over' => 0,
				'over_paid' => 0
			);

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			foreach((array)$aContracts as $aContract) {

				$oTempDate = new WDDate($aContract['from_unix']);
				if($oTempDate->get(WDDate::TIMESTAMP) < $oDateFrom->get(WDDate::TIMESTAMP)) {
					$oTempDate = new WDDate($oDateFrom);
				}

				/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

				$sDayHoursMonth = null;
				$fDayHours = 0.00;

				while(true) {

					if (
						(
							$aContract['till_unix'] > 0 &&
							$aContract['till_unix'] < $oTempDate->get(WDDate::TIMESTAMP)
						) || (
							$oTempDate->get(WDDate::TIMESTAMP) > $oDateTill->get(WDDate::TIMESTAMP)
						)
					) {
						break;
					}

					if(!isset($aDays[$oTempDate->get(WDDate::YEAR)][$oTempDate->get(WDDate::MONTH)][$oTempDate->get(WDDate::DAY)])) {
						$oTempDate->add(1, WDDate::DAY);
						continue;
					}

					// Wochentage von 1 - 7 (Montag - Sonntag), $this->createDaysArray() setzt das auf 8 wenn es ein Feiertag ist
					$iWeekday = $aDays[$oTempDate->get(WDDate::YEAR)][$oTempDate->get(WDDate::MONTH)][$oTempDate->get(WDDate::DAY)];
					if($iWeekday > 7) {
						$oTempDate->add(1, WDDate::DAY);
						continue;
					}

					if(!Ext_Office_Employee_Contract::isWorkday($aContract, $oTempDate)) {
						$oTempDate->add(1, WDDate::DAY);
						continue;
					}

					// die Arbeitsstunden pro Tag nur neu berechnen wenn sich der Monat geändert hat
					if($sDayHoursMonth !== $oTempDate->get(WDDate::YEAR).$oTempDate->get(WDDate::MONTH)) {
						$fDayHours = Ext_Office_Employee_Contract::calculateHoursPerDayInMonth(
							$aContract,
							$oTempDate->get(WDDate::YEAR),
							$oTempDate->get(WDDate::MONTH)
						);
						$sDayHoursMonth = $oTempDate->get(WDDate::YEAR).$oTempDate->get(WDDate::MONTH);
					}

					$iSecondsToWork += $fDayHours * 3600;

					$oTempUntil = new WDDate($oTempDate);
					$oTempUntil->add(1, WDDate::DAY)->sub(1, WDDate::SECOND);
					$aFreeDates = $this->getFreeDates(
						$aEmployee['id'],
						$oTempDate->get(WDDate::DB_DATETIME),
						$oTempUntil->get(WDDate::DB_DATETIME)
					);

					if(empty($aFreeDates)) {
						$oTempDate->add(1, WDDate::DAY);
						continue;
					}

					foreach((array)$aFreeDates as $aFreeDate) {

						$oFreeDateTill = new WDDate($aFreeDate['till'], WDDate::DB_TIMESTAMP);

						// Eintrag endet an diesem Tag
						if($oFreeDateTill->get(WDDate::DATES) == $oTempDate->get(WDDate::DATES)) {
							// Tag anteilig?
							if($oFreeDateTill->get(WDDate::TIMES) != '00:00:00') {
								$iQuote = $aFreeDate['quote'] - floor($aFreeDate['quote']); // Get the decimal number
							} else {
								$iQuote = 1;
							}
						} else {
							$iQuote = 1;
						}

						switch($aFreeDate['type']) {
							case 'sick':
								(int)$aMainFreeDates['sick'] += $iQuote * $fDayHours * 3600;
								break;
							case 'holiday':
								(int)$aMainFreeDates['holi'] += $iQuote * $fDayHours * 3600;
								break;
							case 'overtime':
								(int)$aMainFreeDates['over'] += $iQuote * $fDayHours * 3600;
								break;
							case 'overtime_paid':
								(int)$aMainFreeDates['over_paid'] += $iQuote * $fDayHours * 3600;
								break;
						}

					}

					$oTempDate->add(1, WDDate::DAY);

				}

			}

			$aEmployees[$iKey]['sick'] = (int)$aMainFreeDates['sick'];
			$aEmployees[$iKey]['holi'] = (int)$aMainFreeDates['holi'];
			$aEmployees[$iKey]['over'] = (int)$aMainFreeDates['over'];
			$aEmployees[$iKey]['over_paid'] = (int)$aMainFreeDates['over_paid'];

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Sub the time until the end of today

/*
			if($oDateTill->get(WDDate::DATES) == date('d.m.Y') && $iDayHours > 0) {
				$oEmployee = new Ext_Office_Employee($aEmployee['id']);
				$aDayWorks = $oEmployee->getWorks(date('m'), date('Y'));
				foreach((array)$aDayWorks as $iDWKey => $aDWValue) {
					if($aDWValue['date'] == date('d.m')) {
						$aTemp = explode(':', $aDWValue['work']);
						$iSecondsToWork -= ($aTemp[0] * 3600 + $aTemp[1] * 60 + $aTemp[2]);
						break;
					}
				}
			}
*/

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			$aEmployees[$iKey]['to_work'] = $iSecondsToWork;
			$iSum = $aEmployees[$iKey]['holi'] + $aEmployees[$iKey]['sick'] + $aEmployees[$iKey]['over'];
			$aEmployees[$iKey]['have_to'] = $aEmployees[$iKey]['to_work'] - $iSum;
			$aEmployees[$iKey]['absence'] = $aEmployees[$iKey]['worked'] - $aEmployees[$iKey]['have_to'] - $aEmployees[$iKey]['over_paid'];

		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Format output

		if(!$bUseTimestamps) {

			$oEmployee = new Ext_Office_Employee();

			foreach((array)$aEmployees as $iKey => $aEmployee) {

				foreach((array)$aEmployee as $sKey => $mValue) {

					if(is_numeric($mValue) && $sKey != 'id') {
						if($mValue < 0) {
							$mValue = $oEmployee->getFormatedTimes(-1 * $mValue);
							$aEmployees[$iKey][$sKey] = '-' . $mValue['T'];
						} else {
							$mValue = $oEmployee->getFormatedTimes($mValue);
							$aEmployees[$iKey][$sKey] = $mValue['T'];
						}
					}

				}

			}

		}

		$aReturn = array(
			'data' => $aEmployees,
			'dates' => $aReturnDates
		);

		return $aReturn;

	}

	/**
	 * Get all free dates of an employee between FROM and TILL times
	 * 
	 * @param int $iEmployeeID
	 * @param int $iFrom
	 * @param int $iTill
	 */
	public function getFreeDates($iEmployeeID, $iFrom, $iTill) {

		$sSQL = "
			SELECT
				*
			FROM
				`office_timeclock_free_dates`
			WHERE
				`active` = 1 AND
				`employee_id` = :iEmployeeID AND
				(
					`from` <= :iTill AND
					`till` >= :iFrom
				)
		";
		$aSQL = array(
			'iEmployeeID' => $iEmployeeID,
			'iFrom' => $iFrom,
			'iTill' => $iTill
		);
		$aFreeDates = DB::getPreparedQueryData($sSQL, $aSQL);

		return $aFreeDates;

	}

	/**
	 * Get worked times of an employee between FROM and TILL times
	 * 
	 * @param int $iEmployeeID
	 * @param int $iFrom
	 * @param int $iTill
	 */
	public function getWorkedSeconds($iEmployeeID, $iFrom, $iTill) {

		$sSQL = "
			SELECT
				SUM(
					IF
					(
						UNIX_TIMESTAMP(`ot`.`end`) <= :iTill,
						IF
						(
							UNIX_TIMESTAMP(`ot`.`end`) != 0,
							UNIX_TIMESTAMP(`ot`.`end`),
							UNIX_TIMESTAMP(NOW())
						),
						:iTill
					) -
					IF
					(
						UNIX_TIMESTAMP(`ot`.`start`) >= :iFrom,
						UNIX_TIMESTAMP(`ot`.`start`),
						:iFrom
					)
				) AS `total`
			FROM
				`office_timeclock` AS `ot` INNER JOIN
				`office_project_employees` AS `ope` ON
					`ot`.`p2e_id` = `ope`.`id`
			WHERE
				`ot`.`active`		= 1 AND
				`ot`.`cleared`		!= 9 AND
				`ot`.`action`		!= 'new' AND
				`ope`.`employee_id`	= :iEmployeeID AND
				(
					UNIX_TIMESTAMP(`ot`.`start`) < :iTill AND
					(
						UNIX_TIMESTAMP(`ot`.`end`) > :iFrom OR
						UNIX_TIMESTAMP(`ot`.`end`) = 0
					)
				)
		";
		$aSQL = array(
			'iEmployeeID'	=> $iEmployeeID,
			'iFrom'			=> $iFrom,
			'iTill'			=> $iTill
		);
		$aTotals = DB::getPreparedQueryData($sSQL, $aSQL);
		return (int)$aTotals[0]['total'];

	}

}
