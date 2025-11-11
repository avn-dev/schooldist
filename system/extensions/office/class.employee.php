<?php

/**
 * The employees class
 */
class Ext_Office_Employee extends Ext_Office_Office
{
	
	protected $_aExtendedData = array(
		'reporting_group' => 0
	);

	/**
	 * The entry data
	 */
	protected $_aData = array(
		'id'			=> 0,
		'created'		=> null,
		'changed'	=> null,
		'active'		=> 1,
		'email'			=> '',
		'nickname'		=> '',
		'password'		=> '',
		'sex'			=> '',
		'firstname' 	=> '',
		'lastname' 		=> '',
		'date_o_b' 		=> '',
		'nationality' 	=> '',

		'bank_name' 	=> '',
		'bank_holder' 	=> '',
		'bank_number' 	=> '',
		'bank_code' 	=> '',

		'web' 			=> '',
		'phone' 		=> '',
		'fax' 			=> '',
		'mobile' 		=> '',
		'company' 		=> '',
		'sektion' 		=> '',
		'position' 		=> '',
		'street' 		=> '',
		'zip' 			=> '',
		'city' 			=> '',
		'country' 		=> '',
		'notice' 		=> ''
	);

	//=========================================================================================//

	/**
	 * Changes a_Data Keys into "ext_XXX" to read from DB
	 */
	protected function changeFields()
	{
		foreach((array)$this->_aData as $maDataKey => $maDataValue)
		{
			if(array_key_exists('pro_field_'.$maDataKey, $this->_aConfig) == true)
			{
				$aData[$this->_aConfig['pro_field_'.$maDataKey]] = $maDataValue;
				unset($this->_aData[$maDataKey]);
				$this->_aData = array_merge($this->_aData ,$aData);
			}
		}
	}

	//=========================================================================================//

	/**
	 * Changes a_Data Keys into $value to save into DB
	 */
	protected function changeFieldsBack()
	{
		foreach((array)$this->_aData as $maDataKey => $maDataValue)
		{
			if(in_array($maDataKey, $this->_aConfig) === true)
			{
				$sNewKey = array_keys($this->_aConfig, $maDataKey);
				foreach((array)$sNewKey as $sKey => $mValue)
				{
					if(substr($mValue, 0 , 10) == 'pro_field_')
					{
						$mValue = str_replace('pro_field_', '' , $mValue);
						$aData[$mValue] = $maDataValue;
						unset($this->_aData[$maDataKey]);
						$this->_aData = array_merge($this->_aData, $aData);
					}
				}
			}
		}
	}

	//=========================================================================================//

	/**
	 * Loads the Data from DB
	 */
	protected function _loadData() {
		
		$this->changeFields();
		parent::_loadData();

		$aExtendedData = DB::getRowData('office_employees', $this->id);
		
		if(!empty($aExtendedData)) {
			foreach($this->_aExtendedData as $sKey=>$mValue) {
				$this->_aExtendedData[$sKey] = $aExtendedData[$sKey];
			}
		}

	}

	//=========================================================================================//

	public function getFactors()
	{
		$iEmployeeID = $this->_aData['id'];

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get contracts

		$sSQL = "
			SELECT
				`id`,
				CONCAT(DATE_FORMAT(`from`, '%d.%m.%Y'), ' - ', IF(`until` = 0, '...', DATE_FORMAT(`until`, '%d.%m.%Y'))) AS `date`
			FROM `office_employee_contract_data`
			WHERE
				`active` = 1 AND
				`employee_id` = :iEmployeeID
			ORDER BY `from` DESC
		";
		$aContracts = DB::getQueryPairs($sSQL, array('iEmployeeID' => $iEmployeeID));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get categories

		$sSQL = "
			SELECT `id`, `title`
			FROM `office_project_categories`
			WHERE
				`active` = 1 AND
				`time_flag` = 1
			ORDER BY `title`
		";
		$aCategories = DB::getQueryPairs($sSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get factors

		$sSQL = "
			SELECT *
			FROM `office_employee_factors`
			WHERE `employee_id` = :iEmployeeID
		";
		$aFactors = DB::getPreparedQueryData($sSQL, array('iEmployeeID' => $iEmployeeID));

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Prepare return array

		$aReturn = array();

		foreach((array)$aContracts as $iConID => $sDate)
		{
			foreach((array)$aCategories as $iCatID => $sCategory)
			{
				$aLine = array(
					'contract_id'	=> $iConID,
					'date'			=> $sDate,
					'category_id'	=> $iCatID,
					'title'			=> $sCategory,
					'factor'		=> 0
				);

				foreach((array)$aFactors as $iKey => $aFactor)
				{
					if($aFactor['contract_id'] == $iConID && $aFactor['category_id'] == $iCatID)
					{
						$aLine['factor'] = $aFactor['factor'];
					}
				}

				$aReturn[$iConID][] = $aLine;
			}
		}
		$aReturn = array_values($aReturn);

		return $aReturn;
	}

	//=========================================================================================//

	public function saveFactors($aFactors)
	{
		$iEmployeeID = $this->_aData['id'];

		$sSQL = "DELETE FROM `office_employee_factors` WHERE `employee_id` = " . $iEmployeeID;
		DB::executeQuery($sSQL);

		foreach((array)$aFactors as $sKey => $mValue)
		{
			$aTemp = explode('_', $sKey);

			$aInsert = array(
				'employee_id'	=> $iEmployeeID,
				'contract_id'	=> (int)$aTemp[0],
				'category_id'	=> (int)$aTemp[1],
				'factor'		=> (int)$mValue
			);
			DB::insertData('office_employee_factors', $aInsert);
		}

		return true;
	}

	//=========================================================================================//

	/**
	 * Creates the FileList from an existing Employee
	 */
	public function getFileList()
	{
		
		$arrFiles = array();
		
		if(!$this->_aData['id']) {
			return false;
		}
		
		if(is_dir(\Util::getDocumentRoot()."storage/office/employees/".$this->_aData['id']))
		{
			
			$sDir = opendir(\Util::getDocumentRoot()."storage/office/employees/".$this->_aData['id']);
			while($sFile = readdir($sDir))
			{
				if($sFile != "." && $sFile != "..")
				{
					// fill array with files
					$arrFiles[] = $sFile;
				}
			}
			closedir($sDir); 
		}
		return $arrFiles;
	}

	//=========================================================================================//

	/**
	 * 
	 */
	public function __set($sName, $mValue)
	{
		if(isset($this->_aExtendedData[$sName])) {
			$this->_aExtendedData[$sName] = $mValue;
		} else {
			$this->changeFieldsBack();
			parent::__set($sName, $mValue);
		}
	}

	public function __get($sName) {

		if(isset($this->_aExtendedData[$sName])) {
			$mValue = $this->_aExtendedData[$sName];
		} else {
			$mValue = parent::__get($sName);
		}

		return $mValue;

	}
	
	//=========================================================================================//

	/**
	 * The constructor
	 * 
	 * @param int : The element ID
	 */
	public function __construct($iElementID = null)
	{
		global $oZendDB;

		$this->_oZendDB = $oZendDB;

		// Load configuration data
		$this->_aConfig = Ext_Office_Config::getInstance();

		// Set the table name
		$this->_sTable = 'customer_db_'.$this->_aConfig['pro_database'];

		// Get the table definition
		$this->_aTableDB = DB::describeTable($this->_sTable);

		if(is_numeric($iElementID) && $iElementID > 0)
		{
			// Set the ID and load the element data from DB
			$this->_aData['id'] = (int)$iElementID;

			$this->_loadData();
		}
		else
		{
			// The new element
			$this->_aData['id'] = 0;
		}
	}

	//=========================================================================================//

	/**
	 * 
	 */
	public function save() {

		$this->changeFields();

		if($this->_aData['nickname'] == '')
		{
			$this->_aData['nickname'] = \Util::generateRandomString(32);
		}
		if($this->_aData['email'] == '')
		{
			$this->_aData['email'] = \Util::generateRandomString(32);
		}
		if($this->_aData['access_code'] == '')
		{
			$this->_aData['access_code'] = md5(\Util::generateRandomString(32));
		}

		$sSQL = "
			SELECT
				`email`, `nickname`
			FROM
				`customer_db_".$this->_aConfig['pro_database']."`
			WHERE
				(
					`email` = :sEmail
						OR
					`nickname` = :sNick
				)
					AND
				`id` != :iEmployeeID
			LIMIT
				1
		";
		$aSQL = array(
			'sEmail'		=> $this->_aData['email'],
			'sNick'			=> $this->_aData['nickname'],
			'iEmployeeID'	=> $this->_aData['id']
		);
		$aCheck = DB::getQueryRow($sSQL, $aSQL);

		if(!empty($aCheck))
		{
			$aErrors = array();

			if($aCheck['email'] == $this->_aData['email'])
			{
				$this->_aData['email'] = \Util::generateRandomString(32);

				$aErrors['email'] = $this->_aData['email'];
			}
			if($aCheck['nickname'] == $this->_aData['nickname'])
			{
				$this->_aData['nickname'] = \Util::generateRandomString(32);

				$aErrors['nickname'] = $this->_aData['email'];
			}
			if(!empty($aErrors))
			{
				return $aErrors;
			}
		}

		parent::save();

		// Erweiterte Daten per REPLACE
		$aData = array(
			array(
				'id' => $this->id,
				'reporting_group' => $this->_aExtendedData['reporting_group']
			)
		);
		DB::insertMany('office_employees', $aData, true);

	}

	//=========================================================================================//

	public function sendAccessData()
	{
		if($this->_aData[$this->_aConfig['pro_field_sex']] == 0)
		{
			$sMail = "Sehr geehrter Herr {NAME},\n\n";
		}
		else
		{
			$sMail = "Sehr geehrte Frau {NAME},\n\n";
		}
		$sMail .= "Ihre neuen Zugangsdaten:\n\n";
		$sMail .= "Nickname: {NICKNAME}\n";
		$sMail .= "Passwort: {PASSWORT}\n\n";
		$sMail .= "Mit freundlichen Grüßen\n";

		\System::wd()->executeHook('edit_send_acces_data_email_text', $sMail);

		$sPassword = \Util::generateRandomString(6);
		$aSearch = array('{NICKNAME}', '{PASSWORT}', '{NAME}', '{VORNAME}');
		$aReplace = array(
			$this->nickname,
			$sPassword,
			$this->_aData[$this->_aConfig['pro_field_lastname']],
			$this->_aData[$this->_aConfig['pro_field_firstname']]
		);

		wdmail($this->email, 'Neue Zugangsdaten', str_replace($aSearch, $aReplace, $sMail));

		$sSQL = "
			SELECT 
				`db_encode_pw`
			FROM 
				`customer_db_config`
			WHERE 
				`id` = :iDB_ID
		";
		$iEncode = DB::getQueryOne($sSQL, array('iDB_ID' => $this->_aConfig['pro_database']));

		if($iEncode == 1)
		{
			$this->_aData['password'] = md5($sPassword);
		}
		else
		{
			$this->_aData['password'] = $sPassword;
		}

		parent::save();

	}

	//=========================================================================================//

	 public function refreshHolidaysData($iYear) {

		// get Holiday Data from office_timeclock_free_dates & office_employee_contract_data & customer_db_X
		$sSql = "
			SELECT
				`otfd`.*,
				UNIX_TIMESTAMP(`otfd`.`from`) AS `from`,
				UNIX_TIMESTAMP(`otfd`.`till`) AS `till`,
				`cdb`.`".$this->_aConfig['pro_field_lastname']."` AS `lastname`,
				`cdb`.`".$this->_aConfig['pro_field_firstname']."` AS `firstname`,
				`oecd`.`holiday` AS `holiday`,
				`oecd`.`hours_per_week`,
				`oecd`.`hours_per_month`,
				`oecd`.`hours_type`,
				`oecd`.`days_per_week`
			FROM
				`office_timeclock_free_dates` AS `otfd`
			INNER JOIN
				`customer_db_".$this->_aConfig['pro_database']."` AS `cdb`
			ON
				(`otfd`.`employee_id` = `cdb`.`id`)
			INNER JOIN
				`office_employee_contract_data` AS `oecd`
			ON
				(`cdb`.`id` = `oecd`.`employee_id`)
			WHERE
				`otfd`.`employee_id` = '".(int)$this->_aData['id']."'
			AND
				`otfd`.`active` = 1
			AND
				`oecd`.`active` = 1
			AND
				(
					`oecd`.`until` = 0 OR
					(
						UNIX_TIMESTAMP(`oecd`.`until`) > UNIX_TIMESTAMP(`otfd`.`from`) AND
						UNIX_TIMESTAMP(`oecd`.`from`) < UNIX_TIMESTAMP(`otfd`.`till`)
					)
				)
			AND
				YEAR(`otfd`.`from`) = '".$iYear."'
			GROUP BY
				`otfd`.`id`
			ORDER BY
				`otfd`.`from`
		";
		$aHolidaysData = DB::getQueryData($sSql);

		$iAllHoliDays = 0;
		$iAllSickDays = 0;
		$iAllOvertimes = 0;

		if(empty($aHolidaysData)) {

			$sSql = "
				SELECT
					`cdb`.`".$this->_aConfig['pro_field_lastname']."` AS `lastname`,
					`cdb`.`".$this->_aConfig['pro_field_firstname']."` AS `firstname`,
					`oecd`.`holiday` AS `holiday`
				FROM
					`customer_db_".$this->_aConfig['pro_database']."` AS `cdb`
				INNER JOIN
					`office_employee_contract_data` AS `oecd`
				ON
					(`cdb`.`id` = `oecd`.`employee_id`)
				WHERE
					`cdb`.`id` = '".(int)$this->_aData['id']."'
				AND
					`oecd`.`active` = 1
				AND
					(
						`oecd`.`until` = 0 OR
						(
							UNIX_TIMESTAMP(`oecd`.`until`) > " . time() . " AND
							UNIX_TIMESTAMP(`oecd`.`from`) < " . time() . "
						)
					)
			";
			$aEmployeeName = DB::getQueryRow($sSql);

			$aHolidaysData[0]['no_entrys'] = 1;
			$aHolidaysData[0]['lastname'] = $aEmployeeName['lastname'];
			$aHolidaysData[0]['firstname'] = $aEmployeeName['firstname'];
			$aHolidaysData[0]['holiday'] = $aEmployeeName['holiday'];

		}

		foreach((array)$aHolidaysData as $sKey => $mValue) {

			// get Difference between days
			/*
			if(date('m.d', $mValue['till']) == date('m.d', $mValue['from'])) {
				$aHolidaysData[$sKey]['difference'] = 0;
			} else {
				// show difference as days without leading zeros
				$aHolidaysData[$sKey]['difference'] = round(($mValue['till'] - $mValue['from']) / 86400);
			}
			*/
			// Das wird im Frontend als "Tage" verwendet und auch sonst anscheinend nirgendwo. Die Berechnung
			// "von Starttag bis Endtag" gibt falsche Werte wenn der Eintrag über Feiertage oder Wochenenden geht.
			$aHolidaysData[$sKey]['difference'] = floor($mValue['quote']);

			// 24-hour format of an hour without leading zeros
			$aHolidaysData[$sKey]['hours'] = date('G', $mValue['till']);

			// calculate all holidays togehter
			if($mValue['type'] == 'holiday') {
				$iAllHoliDays = ($iAllHoliDays + $aHolidaysData[$sKey]['quote']);
			}

			// calculate all sickdays togehter
			if($mValue['type'] == 'sick') {
				$iAllSickDays = ($iAllSickDays + $aHolidaysData[$sKey]['quote']);
			}

			// calculate all overtimes together
			if(
				$mValue['type'] == 'overtime' ||
				$mValue['type'] == 'overtime_paid'
			) {

				$oOvertimeFrom = new WDDate($mValue['from']);
				$oOvertimeTill = new WDDate($mValue['till']);
				$sHoursPerDayMonth = null;
				$fHoursPerDay = 0.00;
				$fQuote = $mValue['quote']; // quote = Überstunden in Tagen

				do {

					$sOvertimeFromMonth = $oOvertimeFrom->get(WDDate::YEAR).$oOvertimeFrom->get(WDDate::MONTH);
					$sOvertimeTillMonth = $oOvertimeTill->get(WDDate::YEAR).$oOvertimeTill->get(WDDate::MONTH);

					// wenn sich der Monat geändert hat die Soll-Stunden pro Werktag neu berechnen
					if($sHoursPerDayMonth !== $sOvertimeFromMonth) {
						$fHoursPerDay = Ext_Office_Employee_Contract::calculateHoursPerDayInMonth(
							$mValue,
							$oOvertimeFrom->get(WDDate::YEAR),
							$oOvertimeFrom->get(WDDate::MONTH)
						);
						$sHoursPerDayMonth = $sOvertimeFromMonth;
					}

					// wenn wir uns im letzten Monat befinden oder nur noch ein Tag übrig ist einfach den Rest addieren
					if(
						$fQuote <= 1 ||
						$sOvertimeFromMonth === $sOvertimeTillMonth
					) {
						$iAllOvertimes += (
							$fQuote *
							3600 *
							$fHoursPerDay
						);
						break;
					}

					// wenn der aktuelle Tag ein Werktag ist die Stunden addieren
					if(Ext_Office_Employee_Contract::isWorkday($mValue, $oOvertimeFrom)) {
						$fQuote--;
						$iAllOvertimes += (
							3600 *
							$fHoursPerDay
						);
					}

					$oOvertimeFrom->add(1, WDDate::DAY);

				} while($oOvertimeFrom->get(WDDate::DATES) !== $oOvertimeTill->get(WDDate::DATES));

			}

			// format the timestamp for use in javascript
			if($mValue['from'] != 0) {
				$aHolidaysData[$sKey]['from'] = strftime('%d.%m.%Y', $mValue['from']);
				$aHolidaysData[$sKey]['from'] = explode('.', $aHolidaysData[$sKey]['from']);
			} else {
				$aHolidaysData[$sKey]['from'] = '';
			}

			if($mValue['till'] != 0) {
				if(date('m.d', $mValue['till']) == date('m.d', $mValue['from'])) {
					$aHolidaysData[$sKey]['till'] = strftime('%d.%m.%Y', $mValue['till']);
				} else {
					if(date('H', $mValue['till']) != date('H', $mValue['from'])) {
						$aHolidaysData[$sKey]['till'] = strftime('%d.%m.%Y', $mValue['till']);
					} else {
						$aHolidaysData[$sKey]['till'] = strftime('%d.%m.%Y', $mValue['till']);
					}
				}
				$aHolidaysData[$sKey]['till'] = explode('.', $aHolidaysData[$sKey]['till']);
			} else {
				$aHolidaysData[$sKey]['till'] = '';
			}

		}

		$aHolidaysData[0]['togetherholidays'] = $iAllHoliDays;
		$aHolidaysData[0]['togethersickdays'] = $iAllSickDays;

		$aTimes = $this->getFormatedTimes($iAllOvertimes);
		$aHolidaysData[0]['togetherovertimes']	= $aTimes['T'];
		$oAbsence = new Ext_Office_Absence($this->_aData['id'], '01.01.' . $iYear, '31.12.' . $iYear);
		$iOverTimes = $oAbsence->getOvertimes();

		if($iOverTimes <= 0) {
			$aHolidaysData[0]['allovertimes'] = $aHolidaysData[0]['overtimesopen'] = 0;
		} else {
			$aTimes = $this->getFormatedTimes($iOverTimes);
			$aHolidaysData[0]['allovertimes'] = $aTimes['T'];
			$aTimes = $this->getFormatedTimes($iOverTimes - $iAllOvertimes);
			$aHolidaysData[0]['overtimesopen'] = $aTimes['T'];
		}

		return $aHolidaysData;

	 }

	//=========================================================================================//

	 public function getFreeDates($sFrom, $sUntil, $sType='holiday') {

		 $oFrom = new WDDate($sFrom, WDDate::DB_DATE);
		 $oUntil = new WDDate($sUntil, WDDate::DB_DATE);

		$sSql = "
			SELECT
				SUM(`quote`) `quote`
			FROM
				`office_timeclock_free_dates`
			WHERE
				`active` = 1					AND
				`employee_id` = :employee_id	AND
				`type` = :type		AND
				`from` < :until	AND
				`till` > :from
		";
		$aSql = array(
			'employee_id'	=> $this->id,
			'from'			=> $oFrom->get(WDDate::DB_DATE),
			'until'			=> $oUntil->get(WDDate::DB_DATE),
			'type'			=> $sType
		);
		$aDates = DB::getQueryRow($sSql, $aSql);

		return $aDates['quote'];
	}

	/**
	 * 
	 */
	public function getHolidaysData() {

		// look if contract exists
		$sSql = "
			SELECT
				*
			FROM
				`office_employee_contract_data`
			WHERE
				`employee_id` = '".(int)$this->_aData['id']."'
			AND
				`active` = 1
			AND
				(
					`until` = 0 OR
					(
						UNIX_TIMESTAMP(`until`) > " . time() . " AND
						UNIX_TIMESTAMP(`from`) < " . time() . "
					)
				)
		";
		$mContractExists = DB::getQueryRow($sSql);

		if($mContractExists == false)
		{
			$aHolidaysData[0]['no_contract'] = 1;
			return $aHolidaysData;
		}

		// get contract from - date
		$sSql = "
			SELECT
				UNIX_TIMESTAMP(`from`) AS `from`
			FROM
				`office_employee_contract_data`
			WHERE
				`active` = 1
			AND
				(
					`until` = 0 OR
					(
						UNIX_TIMESTAMP(`until`) > " . time() . " AND
						UNIX_TIMESTAMP(`from`) < " . time() . "
					)
				)
			AND
				`employee_id` = '".(int)$this->_aData['id']."'
		";
		$iValidityContractStartDate = $iValidityContractDate = DB::getQueryOne($sSql);
		$iValidityContractDate = date('Y', $iValidityContractDate);

		if($iValidityContractDate < date('Y')) {
			$iValidityContractDate = date('Y');
		}

		$aHolidaysData = $this->refreshHolidaysData($iValidityContractDate);

		// Years for SelectBox in Javascript
		if($iValidityContractStartDate != 0)
		{
			if(date('Y', $iValidityContractStartDate) < date('Y'))
			{
				for($i=date('Y', $iValidityContractStartDate); $i<date('Y'); $i++){
					$aYearsTemp[] = $i;
				}
				$aYearsTemp[] = date('Y');
				$aYearsTemp[] = date('Y')+1;
			}else if(date('Y', $iValidityContractStartDate) == date('Y'))
			{
				$aYearsTemp[] = date('Y');
				$aYearsTemp[] = date('Y')+1;
			} else if(date('Y', $iValidityContractStartDate) > date('Y'))
			{
				$aYearsTemp[] = date('Y', $iValidityContractStartDate);
				$aYearsTemp[] = date('Y', $iValidityContractStartDate)+1;
			}
		} 
		
		if(in_array(date('Y'),$aYearsTemp) == true)
		{
			$aHolidaysData[0]['selected_year'] = date('Y');
		} else {
			$aHolidaysData[0]['selected_year'] = $aYearsTemp[0];
		}

		// sort entrys
		array_multisort($aYearsTemp , SORT_ASC, SORT_NUMERIC);
		// prepare array years for javascript gui usage
		foreach((array)$aYearsTemp as $iKey => $iValue)
		{
			$aYears[] = array(
				'0' =>	$iValue,
				'1' =>	$iValue
			);
		}

		// set all holidays/sickdays into array
		$aHolidaysData[0]['years']				= $aYears;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $aHolidaysData;
	}

	//=========================================================================================//

	/**
	 * Get one contract
	 */
	public function getContractData($iContractID)
	{
		$sSQL = "
			SELECT *
			FROM `office_employee_contract_data`
			WHERE
				`employee_id` = " . (int)$this->_aData['id'] . " AND
				`id` = " . (int)$iContractID . "
			LIMIT 1
		";
		$aContract = DB::getQueryRow($sSQL);

		switch($aContract['hours_type']) {
			case 'month':
				$aContract['hours_value'] = $aContract['hours_per_month'];
				break;
			case 'week':
			default:
				$aContract['hours_value'] = $aContract['hours_per_week'];
				break;
		}

		return $aContract;
	}

	//=========================================================================================//

	/**
	 * Get contracts
	 */
	public function getContractListData($sFrom=null, $sUntil=null, $sOrderBy='`from` DESC') {

		$sWhere = "";
		$aSql = array();

		if(
			$sFrom !== null &&
			$sUntil !== null
		) {
			$sWhere .= " AND (`from` <= :until OR `from` = 0)";
			$sWhere .= " AND (`until` >= :from OR `until` = 0)";
			$aSql['from'] = $sFrom;
			$aSql['until'] = $sUntil;
		}

		$sSql = "
			SELECT
				*,
				UNIX_TIMESTAMP(`from`) AS `from`,
				UNIX_TIMESTAMP(`until`) AS `until`
			FROM
				`office_employee_contract_data`
			WHERE
				`active` = 1 AND
				`employee_id` = " . (int)$this->_aData['id'] . "
				".$sWhere."
			ORDER BY
				".$sOrderBy."
		";
		$aContracts = DB::getQueryData($sSql, $aSql);

		foreach((array)$aContracts as $iKey => $mValue)
		{
			if($aContracts[$iKey]['from'] <= time() && ($aContracts[$iKey]['until'] == 0 || $aContracts[$iKey]['until'] > time()))
			{
				$aContracts[$iKey]['validity'] = 1;
			}
			else
			{
				$aContracts[$iKey]['validity'] = 0;
			}

			if($aContracts[$iKey]['until'] == 0)
			{
				$aContracts[$iKey]['until'] = '-';
			} 
			else 
			{
				$aContracts[$iKey]['until'] = strftime('%x', $mValue['until']);
			}

			if($aContracts[$iKey]['from'] == 0)
			{
				$aContracts[$iKey]['from'] = '-';
			}
			else 
			{
				$aContracts[$iKey]['from'] = strftime('%x', $mValue['from']);
			}

			if(
				!isset($aContracts[$iKey]['hours_type']) ||
				!in_array($aContracts[$iKey]['hours_type'], array('week', 'month'))
			) {
				$aContracts[$iKey]['hours_type'] = 'week';
			}
			switch($aContracts[$iKey]['hours_type']) {
				case 'month':
					$aContracts[$iKey]['hours_value'] = $aContracts[$iKey]['hours_per_month'];
					break;
				case 'week':
				default:
					$aContracts[$iKey]['hours_value'] = $aContracts[$iKey]['hours_per_week'];
					break;
			}

		}

		return $aContracts;
	}

	//=========================================================================================//

	/**
	 * 
	 */
	public function saveEditContractData($aContract, $iContractID = 0)
	{
		$aErrors = array();

		if(!WDDate::isDate($aContract['from'], WDDate::DATES)) {
			$aErrors['ERROR'] = 'from_date';
		} elseif(
			!is_numeric($aContract['hours_value']) ||
			$aContract['hours_value'] <= 0
		) {
			$aErrors['ERROR'] = 'hours_value';
		} elseif(
			!is_numeric($aContract['days_per_week']) ||
			$aContract['days_per_week'] <= 0
		) {
			$aErrors['ERROR'] = 'days_per_week';
		} elseif(!in_array($aContract['hours_type'], array('week', 'month'))) {
			$aErrors['ERROR'] = 'hours_type';
		}

		if(!empty($aErrors)) {
			return $aErrors;
		}

		$aDate = explode('.', $aContract['from']);
		$aContract['from'] = $aDate['2'].'-'.$aDate['1'].'-'.$aDate['0'];

		$aDate = explode('.', $aContract['until']);
		$aContract['until'] = $aDate['2'].'-'.$aDate['1'].'-'.$aDate['0'];

		$aContract['from'] = str_replace('.', '-', $aContract['from']);
		$aContract['until'] = str_replace('.', '-', $aContract['until']);

		$aContract['from'] .= ' 00:00:00';
		$aContract['until'] .=  ' 23:59:59';

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */// Check the overlapping of contract dates

		$oFrom = new WDDate($aContract['from'], WDDate::DB_TIMESTAMP);
		if(!WDDate::isDate($aContract['until'], WDDate::DB_TIMESTAMP))
		{
			$oTill = new WDDate('0000-00-00 00:00:00', WDDate::DB_TIMESTAMP);
			$iTill = 0;
		}
		else
		{
			$oTill = new WDDate($aContract['until'], WDDate::DB_TIMESTAMP);
			$iTill = $oTill->get(WDDate::TIMESTAMP);
		}

		$oAbsence = new Ext_Office_Absence();

		$aParams = array(
			'employee_id'	=> $aContract['employee_id'],
			'from'			=> $oFrom->get(WDDate::TIMESTAMP),
			'till'			=> $iTill
		);

		$aContracts = $oAbsence->getContracts($aParams);

		foreach((array)$aContracts as $iKey => $aTemp)
		{
			if($aTemp['from_unix'] < $aParams['from'] && $aTemp['till_unix'] >= $aParams['from'])
			{
				$aErrors['ERROR'] = 'overlapping';
			}
			else if($aTemp['from_unix'] == $aParams['from'])
			{
				if($aTemp['id'] != $iContractID)
				{
					$aErrors['ERROR'] = 'overlapping';
				}
				else
				{
					if(isset($aContracts[$iKey + 1]) && $aContracts[$iKey + 1]['from_unix'] >= $iTill)
					{
						$aErrors['ERROR'] = 'overlapping';
					}
				}
			}
			else // $aParams['from'] ist > $aTemp['from_unix']
			{
				if($aTemp['id'] != $iContractID && $aTemp['till_unix'] == 0)
				{
					$aErrors['ERROR'] = 'overlapping';
				}
			}
		}

		if(!empty($aErrors))
		{
			return $aErrors;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		switch($aContract['hours_type']) {
			case 'month':
				$aContract['hours_per_month'] = $aContract['hours_value'];
				$aContract['hours_per_week'] = 0;
				break;
			case 'week':
			default:
				$aContract['hours_per_month'] = 0;
				$aContract['hours_per_week'] = $aContract['hours_value'];
				break;
		}

		$aSql = array(
			'employee_id'				=> $aContract['employee_id'],
			'from' 						=> $aContract['from'],
			'until'						=> $aContract['until'],
			'social_security_number' 	=> $aContract['social_security_number'],
			'religion' 					=> $aContract['religion'],
			'tax_class' 				=> $aContract['tax_class'],
			'tax_number' 				=> $aContract['tax_number'],
			'factor' 					=> $aContract['factor'],
			'health_insurance' 			=> $aContract['health_insurance'],
			'gross_salary' 				=> $aContract['gross_salary'],
			'salary' 					=> $aContract['salary'],
			'hours_type'				=> $aContract['hours_type'],
			'hours_per_week' 			=> $aContract['hours_per_week'],
			'hours_per_month' 			=> $aContract['hours_per_month'],
			'days_per_week' 			=> $aContract['days_per_week'],
			'holiday' 					=> $aContract['holiday'],
			'active'					=> 1
		);

		if($iContractID > 0)
		{
			$aSqlinactive = array('active' => 0);

			// update old entry / set it inactive
			DB::updateData('office_employee_contract_data',$aSqlinactive, "`id` = '".(int)$iContractID."'");
		}

		// new entry = fill created
		$aSql['created'] = date('YmdHis');

		// insert new entry
		DB::insertData('office_employee_contract_data',$aSql);

		// get id from insert
		$sLastID = DB::fetchInsertID();

		return $sLastID;
	}

	//=========================================================================================//


	public function saveEditHolidayData($aHoliday, $sHolidayID = '') {

		$oFrom	= new WDDate($aHoliday['from'], WDDate::DB_DATE);
		$oFrom->set('00:00:00', WDDate::TIMES);

		$aSql = array(
			'employee_id' => (int)$aHoliday['employee_id']	
		);
		$sSql= "
			SELECT
				`hours_type`,
				`hours_per_week`,
				`hours_per_month`,
				`days_per_week`
			FROM
				`office_employee_contract_data`
			WHERE
				`employee_id` = :employee_id AND
				`active` = 1 AND
				(
					`until` = 0 OR
					(
						UNIX_TIMESTAMP(`until`) > " . time() . " AND
						UNIX_TIMESTAMP(`from`) < " . time() . "
					)
				)
			LIMIT 1
		";
		$aHoursPerDay = DB::getQueryRow($sSql, $aSql);

		// die Angaben müssen zwingend Zahlen sein sonst kommt nichts vernünftiges dabei raus
		$aHoliday['till_days'] = (float)$aHoliday['till_days'];
		$aHoliday['till_hours'] = (float)$aHoliday['till_hours'];
		if(
			$aHoliday['till_days'] <= 0 &&
			$aHoliday['till_hours'] <= 0
		) {
			return false;
		}

		$aEntries = array();
		$oTill = new WDDate($oFrom);
		$fDays = $aHoliday['till_days'];
		$aHoliday['till_days'] = 0;
		$sHoursPerDayMonth = null;
		$fHoursPerDay = 0.00;

		do {

			if($fDays >= 1) {
				if(!$this->isFreeDate($oTill)) {
					$aEntries[$oTill->get(WDDate::YEAR)][$oTill->get(WDDate::MONTH)][] = $oTill->get(WDDate::DAY);
					$fDays--;
					$aHoliday['till_days']++;
				}
				$oTill->add(1, WDDate::DAY);
			}

			// die Arbeitsstunden pro Tag nur neu berechnen wenn sich der Monat geändert hat
			if($sHoursPerDayMonth !== $oTill->get(WDDATE::YEAR).$oTill->get(WDDATE::MONTH)) {
				$fHoursPerDay = Ext_Office_Employee_Contract::calculateHoursPerDayInMonth(
					$aHoursPerDay,
					$oTill->get(WDDATE::YEAR),
					$oTill->get(WDDATE::MONTH)
				);
				$sHoursPerDayMonth = $oTill->get(WDDATE::YEAR).$oTill->get(WDDATE::MONTH);
			}

			// wenn keine ganzen Tage mehr übrig sind schauen ob die Stundenanzahl
			// noch einen weiteren ganzen Tag ergibt
			if($fDays < 1) {

				$aHoliday['till_hours'] += (
					$fDays *
					$fHoursPerDay
				);
				$fDays = 0.00;

				// es wird explizit immer nur EIN weiter Tag aufgerechnet da sich die Stundenanzahl
				// pro Tag ($fHoursPerDay) ändern kann wenn ein neuer Monat beginnt
				if($aHoliday['till_hours'] >= $fHoursPerDay) {
					$aHoliday['till_hours'] -= $fHoursPerDay;
					$fDays++;
				}

			}

		} while($fDays >= 1);

		if($sHolidayID > 0) {
			$oHoliday = new WDBasic($sHolidayID, 'office_timeclock_free_dates');
			$oHoliday->active = 0;
			$oHoliday->save();
		}

		$oHoliday = null;
		$aHolidayInfo = array();

		$oDateFrom = new WDDate();
		$oDateFrom->set('00:00:00', WDDate::TIMES);
		$oDateTill = null;

		// Urlaubseinträge werden immer nur innerhalb eines Monats erstellt
		foreach((array)$aEntries as $iYear => $aMonths) {

			foreach((array)$aMonths as $iMonth => $aDays) {

				$oDateFrom->set($iYear, WDDate::YEAR);
				$oDateFrom->set($iMonth, WDDate::MONTH);
				$oDateFrom->set($aDays[0], WDDate::DAY);

				$oDateTill = new WDDate($oDateFrom);

				$iFirstDay = reset($aDays);
				$iLastDay = end($aDays);
				$iDays = $iLastDay - $iFirstDay;

				$oDateTill->add($iDays, WDDate::DAY);

				$oHoliday = new WDBasic(0, 'office_timeclock_free_dates');
				$oHoliday->created = date('YmdHis');
				$oHoliday->active = 1;
				$oHoliday->employee_id = $aHoliday['employee_id'];
				$oHoliday->type = $aHoliday['type'];
				$oHoliday->from = $oDateFrom->get(WDDate::DB_TIMESTAMP);
				$oHoliday->till = $oDateTill->get(WDDate::DB_TIMESTAMP);
				$oHoliday->notice = $aHoliday['notice'];
				$oHoliday->quote = count((array)$aDays);
				$oHoliday->save();

				if($oHoliday->type == 'holiday') {
					$aHolidayInfo[$oHoliday->id] = $this->getHolidayDescription($oHoliday);
				}

			}

		}

		// alles was kein ganzer Urlaubstag ist wird jetzt als Stunden an den letzten Urlaubstag angehängt
		// (da in der Schleife am Anfang überschüssige Stunden in Tage umgerechnet werden ist der noch vorhandene
		// Rest auf jeden fall geringer als die eigentlichen Soll-Arbeitsstunden des letztes Urlaubstages)
		if($aHoliday['till_hours'] > 0) {

			if(!$oHoliday) {
				$oHoliday = new WDBasic(0, 'office_timeclock_free_dates');
				$oHoliday->created = date('YmdHis');
				$oHoliday->active = 1;
				$oHoliday->employee_id = $aHoliday['employee_id'];
				$oHoliday->type = $aHoliday['type'];
				$oHoliday->from = $oFrom->get(WDDate::DB_TIMESTAMP);
				$oHoliday->notice = $aHoliday['notice'];
				$oDateTill = $oTill;
			}

			$iSeconds = floor($aHoliday['till_hours'] * 60 * 60);
			$fQuote = ($aHoliday['till_hours'] / $fHoursPerDay);
			$oDateTill->add($iSeconds, WDDate::SECOND);
			$oHoliday->till = $oDateTill->get(WDDate::DB_TIMESTAMP);
			$oHoliday->quote += $fQuote;
			$oHoliday->save();

			if($oHoliday->type == 'holiday') {
				$aHolidayInfo[$oHoliday->id] = $this->getHolidayDescription($oHoliday);
			}

		}

		// Info E-Mail an Mitarbeiter schicken
		if(!empty($aHolidayInfo) && checkEmailMx($this->email)) {
			$sText = "Folgender Eintrag wurde erfasst und wird hiermit bestätigt:\n\n";
			foreach((array)$aHolidayInfo as $sDescription) {
				$sText .= $sDescription."\n";
			}
			$sText .= "\nBitte bewahre diese Meldung gut auf.\n\nViele Grüße\nPersonalabteilung\n\n";
			wdmail($this->email, 'Abwesenheit - Änderung', $sText);
		}

		return true;

	}


	protected function getHolidayDescription($oHoliday) {
		$oFrom = new WDDate($oHoliday->from, WDDate::DB_DATETIME);
		$oUntil = new WDDate($oHoliday->till, WDDate::DB_DATETIME);
		$sDescription = $oFrom->get(WDDate::STRFTIME, '%x').' - '.$oUntil->get(WDDate::STRFTIME, '%x').' (Tage: '.$oHoliday->quote.')';
		return $sDescription;
	}

	public function isFreeDate($oDate)
	{
		if($oDate->get(WDDate::WEEKDAY) == 6 || $oDate->get(WDDate::WEEKDAY) == 7)
		{
			return true;
		}

		$sSQL = "
			SELECT *
			FROM
				`office_timeclock_holidays`
			WHERE
				`active` = 1 AND
				(
					(
						DATE(`date`) = :sDate
					) OR
					(
						DAY(`date`) = :iDay AND
						MONTH(`date`) = :iMonth AND
						YEAR(`date`) <= :iYear AND
						`repeat` = 1
					)
				)
		";
		$aSQL = array(
			'sDate'		=> $oDate->get(WDDate::DB_DATE),
			'iDay'		=> $oDate->get(WDDate::DAY),
			'iMonth'	=> $oDate->get(WDDate::MONTH),
			'iYear'		=> $oDate->get(WDDate::YEAR)
		);
		$aDate = DB::getQueryRow($sSQL, $aSQL);

		if(empty($aDate))
		{
			return false;
		}

		return $aDate;
	}

	//=========================================================================================//

	/**
	 * 
	 */
	public function deleteHoliday($sID)
	{
		if(is_int($sID))
		{

			$oHoliday = new WDBasic($sID, 'office_timeclock_free_dates');
			$bSuccess = $oHoliday->delete();

			if(
				$bSuccess &&
				$oHoliday->type == 'holiday'
			) {
				$aHolidayInfo[$oHoliday->id] = $this->getHolidayDescription($oHoliday);
			}

			// Info E-Mail an Mitarbeiter schicken
			if(!empty($aHolidayInfo) && checkEmailMx($this->email)) {
				$sText = "Folgender Eintrag wurde entfernt:\n\n";
				foreach((array)$aHolidayInfo as $sDescription) {
					$sText .= $sDescription."\n";
				}
				$sText .= "\nBitte bewahre diese Meldung gut auf.\n\nViele Grüße\nPersonalabteilung\n\n";
				wdmail($this->email, 'Abwesenheit - Änderung', $sText);
			}

		}

	}

	//=========================================================================================//

	/**
	 * 
	 */
	public function deleteContract($sID)
	{
		if(is_int($sID))
		{
			$aSql = array(
				'active'	=> 0
			);

			// "delete" contract - ...change it to inactive
			DB::updateData('office_employee_contract_data',$aSql, "`id` = '".$sID."'");
		}
	}

	//=========================================================================================//

	/**
	 * Returns the list of all employees by the first
	 * sign of lastname AND/OR search string
	 * 
	 * @param string : The first sign of lastname
	 * @param string : The search string
	 * @param int : The limit (DEFAULT 100)
	 * @return array : The list of employees
	 */
	public function getEmployeesList($sSign=null, $sSearch=null, $iLimit = 100, $iGroup = null) {

		$sWhere = " WHERE `active` = 1 ";
		if($sSign != '' && !is_null($sSign)) {
			$sWhere .= " AND `".$this->_aConfig['pro_field_lastname']."` LIKE '".$sSign."%' ";
		} else if(!is_null($sSign)) {
			$sWhere .= " AND TRIM(`".$this->_aConfig['pro_field_lastname']."`) = '' ";
		}

		if(empty($iLimit)) {
			$iLimit = 100;
		} 

		// Search string
		if(mb_strpos($sSearch, 'zip::') === 0) {
			// Spezielle Suche nach PLZ für Umkreissuche Consulimus (Ticket #853)
			$sWhere .= " AND (
				`".$this->_aConfig['pro_field_zip']."` LIKE '".mb_substr($sSearch, 5)."%'
			) ";
		} elseif(trim($sSearch) != '') {
			$sWhere .= " AND (
				`".$this->_aConfig['pro_field_lastname']."` LIKE '%".$sSearch."%'
					OR
				`".$this->_aConfig['pro_field_firstname']."` LIKE '%".$sSearch."%'
					OR
				`id` LIKE '%".$sSearch."%'
					OR
				`".$this->_aConfig['pro_field_company']."` LIKE '%".$sSearch."%'
					OR
				`".$this->_aConfig['pro_field_zip']."` LIKE '%".$sSearch."%'
					OR
				`".$this->_aConfig['pro_field_city']."` LIKE '%".$sSearch."%'
			) ";
		}

		if(!is_null($iGroup) && !empty($iGroup)) {
			$sWhere .= " AND `groups` LIKE '%|".$iGroup."|%' ";
		}

		$sSQL = "
			SELECT
				`id`,
				`email`,
				`".$this->_aConfig['pro_field_sex']."` AS `sex`,
				CONCAT(`".$this->_aConfig['pro_field_firstname']."`, ' ', `".$this->_aConfig['pro_field_lastname']."`) AS `name`,
				UNIX_TIMESTAMP(`".$this->_aConfig['pro_field_date_o_b']."`) AS `date_o_b`,
				`".$this->_aConfig['pro_field_phone']."` AS `phone`,
				`".$this->_aConfig['pro_field_mobile']."` AS `mobile`,
				`".$this->_aConfig['pro_field_sektion']."` AS `sektion`
			FROM
				`customer_db_".$this->_aConfig['pro_database']."`
			{WHERE}
			ORDER BY
				`".$this->_aConfig['pro_field_firstname']."`
			LIMIT
				".$iLimit."
		";
		$aEmployees = (array)DB::getQueryData(str_replace('{WHERE}', $sWhere, $sSQL));

		if(count($aEmployees) < $iLimit && !is_null($sSign))
		{
			return $this->getEmployeesList(null, $sSearch, $iLimit, $iGroup);
		}

		return $aEmployees;
	}

	//=========================================================================================//

	/**
	 * 
	 */
	public function handleEmployeeGroups($iEmployeeID, $sGroups = false)
	{
		if($sGroups == false)
		{
			$sSql = "
				SELECT
					`groups`
				FROM
					`customer_db_".$this->_aConfig['pro_database']."`
				WHERE
					`id` = '".(int)$iEmployeeID."'
				LIMIT 1
			";

			$sGroups = DB::getQueryOne($sSql);

			$aGroups = explode('|', $sGroups);

			return $aGroups;
		}
		else
		{
			$aSql = array(
				'groups' 	=> $sGroups,
			);

			DB::updateData('customer_db_'.$this->_aConfig['pro_database'], $aSql, '`id` = '.(int)$iEmployeeID);

			return true;
		}
	}

	//=========================================================================================//

	/**
	 * Returns the selected edit group as array
	 */
	public function get_grouptoedit($GroupID)
	{
		$aSql = array(
			'groupid'	=> $GroupID
		);
		
		$sSql = "
			SELECT
				*
			FROM
				`customer_groups`
			WHERE
				`id` = :groupid
		";
		$aGroup = DB::getQueryRow($sSql, $aSql);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Group for the list of absences

		$aAbsences = $this->_aConfig['absence_groups'];

		$aGroup['absence'] = $aAbsences[$aGroup['id']];

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $aGroup;
	}

	//=========================================================================================//

	/**
	 * Returns list of available groups
	 * 
	 * @return array : Available groups
	 */
	public function getGroups($bAssociativeArray=false)
	{
		$sSql = "
			SELECT
				`id`,
				`name`,
				`description`,
				`db_nr`,
				`active`,
				(
					SELECT COUNT(*)
					FROM `customer_db_".$this->_aConfig['pro_database']."`
					WHERE `groups` LIKE CONCAT('%|', `cg`.`id`, '|%')
				) AS `count`
			FROM
				`customer_groups` AS `cg`
			WHERE
				`db_nr` = :iDB_NR
					AND
				`active` = 1
			ORDER BY `name`
		";
		
		$aSql = array('iDB_NR' => (int)$this->_aConfig['pro_database']);
		
		if($bAssociativeArray === true) {
			$aGroups = DB::getQueryPairs($sSql, $aSql);
		} else {
			$aGroups = DB::getPreparedQueryData($sSql, $aSql);
		}

		return $aGroups;
	}

	//=========================================================================================//

	/**
	 * Saves a new group
	 * 
	 * @param string : The name of the group
	 * @param string : The description of the group
	 */
	public function saveGroup($sGroup, $sDescription, $iID = 0, $iAbsence = 0)
	{
		$aInsert = array(
			'name'			=> $sGroup,
			'description'	=> $sDescription,
			'db_nr'			=> $this->_aConfig['pro_database'],
			'active'		=> 1
		);

		if($iID == 0)
		{
			DB::insertData('customer_groups', $aInsert);

			$iID = DB::fetchInsertID();
		}
		else
		{
			DB::updateData('customer_groups', $aInsert, '`id` = '.(int)$iID);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Group for the list of absences

		$aAbsences = $this->_aConfig['absence_groups'];

		if($iAbsence == 0)
		{
			unset($aAbsences[$iID]);
		}
		else
		{
			$aAbsences[$iID] = 1;
		}

		$sSQL = "REPLACE INTO `office_config` SET `value` = :sAbsences, `key` = 'absence_groups'";
		$aSQL = array('sAbsences' => serialize($aAbsences));
		DB::executePreparedQuery($sSQL, $aSQL);
	}

	//=========================================================================================//

	/**
	 * Deletes a group by ID
	 * 
	 * @param int : The group ID
	 * @return bool : TRUE || FALSE
	 */
	public function deleteGroup($iGroupID)
	{
		if($iGroupID == $this->_aConfig['pro_master_group'])
		{
			return false;
		}

		$sSQL = "
			UPDATE
				`customer_db_".$this->_aConfig['pro_database']."`
			SET
				`groups` = IF(`groups` = CONCAT('|', :iGroupID, '|'), '', REPLACE(`groups`, CONCAT('|', :iGroupID), ''))
		";
		DB::executePreparedQuery($sSQL, array('iGroupID' => $iGroupID));

		$sSQL = "DELETE FROM `customer_groups` WHERE `id` = " . $iGroupID;

		return true;
	}

	// ========================================================================================= //

	/**
	 * Returns the first sign of employees names
	 * 
	 * @return array : The first sign of employees names
	 */
	public function getNameSigns()
	{
		$sSql = "
			SELECT
				DISTINCT LEFT(`".$this->_aConfig['pro_field_lastname']."`, 1),
				LEFT(`".$this->_aConfig['pro_field_lastname']."`, 1) AS `key`
			FROM
				`customer_db_".$this->_aConfig['pro_database']."`
			ORDER BY
				`".$this->_aConfig['pro_field_lastname']."`
		";
		$aSigns = DB::getQueryPairs($sSql);

		return $aSigns;
	}

	// ========================================================================================= //

	/**
	 * Returns the stats of work, holidays, sick etc. times by employee ID
	 * 
	 * @param int : The number of selected month
	 * @param int : The number of selected year
	 * @return array : The array with times by employee ID
	 */
	public function getWorkTimes($iMonth, $iYear)
	{

		$iMonth = (int)$iMonth;
		$iYear = (int)$iYear;
		$oMonthStart = new WDDate(mktime(0, 0, 0, $iMonth, 1, $iYear));
		$oMonthEnd = new WDDate(mktime(23, 59, 59, $iMonth+1, 0, $iYear));

		// ================================================== // Get hours/days

		$sSQL = "
			SELECT
				`hours_type`,
				`hours_per_month`,
				`hours_per_week`,
				`days_per_week`
			FROM
				`office_employee_contract_data`
			WHERE
				`active` = 1
					AND
				`employee_id` = :iEmployeeID
					AND
				(
					( UNIX_TIMESTAMP(`from`) BETWEEN :iFrom AND :iTill )
						OR
					( UNIX_TIMESTAMP(`until`) BETWEEN :iFrom AND :iTill )
						OR
					( UNIX_TIMESTAMP(`from`) <= :iFrom AND UNIX_TIMESTAMP(`until`) >= :iTill )
						OR
					( UNIX_TIMESTAMP(`from`) <= :iFrom AND UNIX_TIMESTAMP(`until`) = 0 )
				)
			LIMIT 1
		";
		$aSQL = array(
			'iEmployeeID' => $this->_aData['id'],
			'iFrom' => $oMonthStart->get(WDDate::TIMESTAMP),
			'iTill' => $oMonthEnd->get(WDDate::TIMESTAMP)
		);
		$aEmployeeContract = DB::getQueryRow($sSQL, $aSQL);

		$aEmployeeContract['day_h'] = Ext_Office_Employee_Contract::calculateHoursPerDayInMonth(
			$aEmployeeContract,
			$iYear,
			$iMonth
		);

		// ================================================== // Work times

		$sSQL = "
			SELECT
				SUM(
						(
							IF(
								UNIX_TIMESTAMP(`ot`.`end`) != 0,
								UNIX_TIMESTAMP(`ot`.`end`),
								UNIX_TIMESTAMP(NOW())
							) - UNIX_TIMESTAMP(`ot`.`start`)
						)
				) AS `total`
			FROM
				`office_timeclock`			AS `ot`
					INNER JOIN
				`office_project_employees`	AS `ope`
					ON
				`ot`.`p2e_id` = `ope`.`id`
			WHERE
				`ot`.`active` = 1
					AND
				`ot`.`cleared` != 9
					AND
				`ot`.`action` != 'new'
					AND
				`ope`.`employee_id` = :iEmployeeID
					AND
				MONTH(`ot`.`start`) = :iMonth
					AND
				YEAR(`ot`.`start`) = :iYear
		";
		$aSQL = array(
			'iEmployeeID'	=> $this->_aData['id'],
			'iMonth'		=> $iMonth,
			'iYear'			=> $iYear
		);
		$aTimes['iH_Work'] = DB::getQueryOne($sSQL, $aSQL);

		if(!is_numeric($aTimes['iH_Work']))
		{
			$aTimes['iH_Work'] = 0;
		}

		// ================================================== // Holiday times

		$sSQL = "
			SELECT
				SUM(`quote`) AS `total`
			FROM
				`office_timeclock_free_dates`
			WHERE
				(`type` = 'holiday' OR `type` = 'overtime')
					AND
				`employee_id` = :iEmployeeID
					AND
				MONTH(`from`) = :iMonth
					AND
				YEAR(`from`) = :iYear
					AND
				`active` = 1
		";
		$aTimes['iH_Holly'] = DB::getQueryOne($sSQL, $aSQL);

		if(!is_numeric($aTimes['iH_Holly']))
		{
			$aTimes['iH_Holly'] = 0;
		}

		// ================================================== // Sick times

		$sSQL = "
			SELECT
				SUM(`quote`) AS `total`
			FROM
				`office_timeclock_free_dates`
			WHERE
				`type` = 'sick'
					AND
				`employee_id` = :iEmployeeID
					AND
				MONTH(`from`) = :iMonth
					AND
				YEAR(`from`) = :iYear
					AND
				`active` = 1
		";
		$aTimes['iH_Sick'] = DB::getQueryOne($sSQL, $aSQL);

		if(!is_numeric($aTimes['iH_Sick']))
		{
			$aTimes['iH_Sick'] = 0;
		}

		// ================================================== // Format / count times

		$aTemp				= $this->getFormatedTimes($aTimes['iH_Work']);
		$aTimes['iH_Work']	= $aTemp['H'];
		$aTimes['iM_Work']	= $aTemp['M'];
		$aTimes['iS_Work']	= $aTemp['S'];

		$aTemp				= $this->getFormatedTimes($aTimes['iH_Holly'] * 3600 * $aEmployeeContract['day_h']);
		$aTimes['iH_Holly']	= $aTemp['H'];
		$aTimes['iM_Holly']	= $aTemp['M'];
		$aTimes['iS_Holly']	= $aTemp['S'];

		$aTemp				= $this->getFormatedTimes($aTimes['iH_Sick'] * 3600 * $aEmployeeContract['day_h']);
		$aTimes['iH_Sick']	= $aTemp['H'];
		$aTimes['iM_Sick']	= $aTemp['M'];
		$aTimes['iS_Sick']	= $aTemp['S'];

		$aTimes['iH_Total']	 = $aTimes['iH_Work'] + $aTimes['iH_Holly'] + $aTimes['iH_Sick'];
		$aTimes['iM_Total']	 = $aTimes['iM_Work'] + $aTimes['iM_Holly'] + $aTimes['iM_Sick'];
		$aTimes['iS_Total']	 = $aTimes['iS_Work'] + $aTimes['iS_Holly'] + $aTimes['iS_Sick'];

		if($aTimes['iS_Total'] >= 60)
		{
			$iOverflowS 		 = ($aTimes['iS_Total'] - ($aTimes['iS_Total'] % 60)) / 60;
			$aTimes['iM_Total']	+= $iOverflowS;
			$aTimes['iS_Total'] -= 60 * $iOverflowS;
		}
		if($aTimes['iM_Total'] >= 60)
		{
			$iOverflowM 		 = ($aTimes['iM_Total'] - ($aTimes['iM_Total'] % 60)) / 60;
			$aTimes['iH_Total']	+= $iOverflowM;
			$aTimes['iM_Total'] -= 60 * $iOverflowM;
		}

		// ================================================== // Get holidays dates

		$sSQL = "
			SELECT
				`date`
			FROM
				`office_timeclock_holidays`
			WHERE
				`active` = 1
					AND
				MONTH(`date`) = :iMonth
					AND
				(
					YEAR(`date`) = :iYearNow
							OR
					(
						`repeat` = 1
							AND
						YEAR(`date`) < :iYear
					)
				)
		";
		$aSQL = array(
			'iMonth'	=> $iMonth,
			'iYearNow'	=> $iYear,
			'iYear'		=> $iYear
		);
		$aHolidays = DB::getQueryCol($sSQL, $aSQL);

		// ================================================== // Count hours to work in month

		$oDate = new WDDate();
		$oDate->set(1, WDDate::DAY);
		$oDate->set($iMonth, WDDate::MONTH);
		$oDate->set($iYear, WDDate::YEAR);

		$aDays = array();
		while($oDate->get(WDDate::MONTH) == $iMonth) {

			if(Ext_Office_Employee_Contract::isWorkday($aEmployeeContract, $oDate)) {
				$aDays[$oDate->get(WDDate::DAY).'.'.$oDate->get(WDDate::MONTH)] = 1;
			}

			$oDate->add(1, WDDate::DAY);

		}

		foreach((array)$aHolidays as $iKey => $sValue)
		{
			$oDate->set($sValue, WDDate::DB_TIMESTAMP);
			$sKey = $oDate->get(WDDate::DAY) . '.' . $oDate->get(WDDate::MONTH);

			if(array_key_exists($sKey, $aDays))
			{
				unset($aDays[$sKey]);
			}
		}
		$aTemp				= $this->getFormatedTimes(3600 * count($aDays) * $aEmployeeContract['day_h']);
		$aTimes['iH_Todo']	= $aTemp['H'];
		$aTimes['iM_Todo']	= $aTemp['M'];
		$aTimes['iS_Todo']	= $aTemp['S'];

		// ================================================== // Count minus times

		$aTimes['iH_Minus']	 = 3600 * ($aTimes['iH_Total'] - $aTimes['iH_Todo']);
		$aTimes['iH_Minus']	+= 60 * ($aTimes['iM_Total'] - $aTimes['iM_Todo']);
		$aTimes['iH_Minus']	+= ($aTimes['iS_Total'] - $aTimes['iS_Todo']);
		$iTmp				 = $aTimes['iH_Minus'];
		$iOverflowH			 = $aTimes['iH_Minus'] % 3600;
		$aTimes['iH_Minus']	-= $iOverflowH;
		$aTimes['iH_Minus']	 = round($aTimes['iH_Minus'] / 3600);
		$iOverflowM			 = $iOverflowH % 60;
		$iOverflowH			-= $iOverflowM;
		$aTimes['iM_Minus']	 = $iOverflowH / 60;
		$aTimes['iS_Minus']	 = $iOverflowM;

		if($iTmp < 0)
		{
			if($aTimes['iH_Minus'] == 0)
			{
				$aTimes['iH_Minus'] = '-0';
			}
			$aTimes['iM_Minus'] *= -1;
			$aTimes['iS_Minus'] *= -1;
		}

		// ================================================== // Format times for output

		foreach((array)$aTimes as $sKey => $iValue)
		{
			$aTimes[$sKey] = str_pad($iValue, 2, '0', STR_PAD_LEFT);
		}

		return $aTimes;
	}

	// ========================================================================================= //

	public function getSollTimes($iFrom, $iTill) {

		// ================================================== // Get hours/days

		if(
			$iFrom <= 0 ||
			$iTill <= 0
		) {
			return $this->getFormatedTimes(0);
		}

		$sSQL = "
			SELECT *,
				UNIX_TIMESTAMP(`from`) AS `from`,
				UNIX_TIMESTAMP(`until`) AS `until`
			FROM
				`office_employee_contract_data`
			WHERE
				`employee_id` = :iEmployeeID AND
				(
					(
						UNIX_TIMESTAMP(`from`) BETWEEN :iFrom1 AND :iTill1
					) OR (
						UNIX_TIMESTAMP(`until`) BETWEEN :iFrom2 AND :iTill2
					) OR (
						UNIX_TIMESTAMP(`from`) <= :iFrom3 AND
						UNIX_TIMESTAMP(`until`) >= :iTill3
					) OR (
						UNIX_TIMESTAMP(`from`) <= :iFrom4 AND
						UNIX_TIMESTAMP(`until`) = 0
					)
				)
			ORDER BY
				`created` DESC
		";
		$aSQL = array(
			'iEmployeeID' => $this->_aData['id'],
			'iFrom1' => $iFrom,
			'iTill1' => $iTill,
			'iFrom2' => $iFrom,
			'iTill2' => $iTill,
			'iFrom3' => $iFrom,
			'iTill3' => $iTill,
			'iFrom4' => $iFrom
		);
		$aContracts = DB::getPreparedQueryData($sSQL, $aSQL);

		// Filter entries
		$aTmp = array();
		foreach((array)$aContracts as $iKey => $aValue) {
			if(!isset($aTmp[$aValue['from']])) {
				$aTmp[$aValue['from']] = $aValue;
			} else {
				if($aTmp[$aValue['from']]['until'] == $aValue['until']) {
					unset($aContracts[$iKey]);
					continue;
				} else {
					$aTmp[$aValue['from']] = $aValue;
				}
			}
		}

		// ================================================== // Get free dates

		$sSQL = "
			SELECT
				*,
				UNIX_TIMESTAMP(`from`) AS `from`,
				UNIX_TIMESTAMP(`till`) AS `till`
			FROM
				`office_timeclock_free_dates`
			WHERE
				(
					`type` = 'sick' OR
					`type` = 'holiday' OR
					`type` = 'overtime'
				) AND (
					`employee_id` = :iEmployeeID
				) AND (
					UNIX_TIMESTAMP(`from`) BETWEEN :iFrom1 AND :iTill1
				) AND (
					`active` = 1
				)
		";
		$aSQL = array(
			'iEmployeeID' => $this->_aData['id'],
			'iFrom1' => $iFrom,
			'iTill1' => $iTill
		);
		$aFreeDates = DB::getPreparedQueryData($sSQL, $aSQL);

		// Die freien Tage nach Jahr/Monat/Tag vorsortieren, damit später schneller ermittelt werden
		// kann ob der Mitarbeiter an einem bestimmten Tag frei hat
		//
		// $aFreeDatesCache = vorsortiert nach [Jahr][Monat][Tag], als Wert die ID
		// $aFreeDatesQuotes = den "quote"-Wert des Eintrags, als Index die ID, diese Werte werden in der Schleife
		//                     dann immer runter gerechnet bis der Wert bei 0 ist
		$aFreeDatesCache = array();
		$aFreeDatesQuotes = array();
		foreach($aFreeDates as $iId => $aFreeDate) {
			$aFreeDatesQuotes[$iId] = (float)$aFreeDate['quote'];
			$oFreeDateFrom = new WDDate($aFreeDate['from']);
			$oFreeDateTill = new WDDate($aFreeDate['till']);
			$oFreeDateTill->add(1,  WDDate::DAY);
			while($oFreeDateFrom->get(WDDate::DATES) != $oFreeDateTill->get(WDDate::DATES)) {
				$aFreeDatesCache[$oFreeDateFrom->get(WDDate::YEAR)]
				                [$oFreeDateFrom->get(WDDate::MONTH)]
				                [$oFreeDateFrom->get(WDDate::DAY)] = $iId;
				$oFreeDateFrom->add(1, WDDate::DAY);
			}
		}

		// ================================================== // Get holidays dates

		$iYear = date('Y', $iFrom);
		do {

			$sSQL = "
				SELECT
					UNIX_TIMESTAMP(`date`)
				FROM
					`office_timeclock_holidays`
				WHERE
					(
						`active` = 1
					) AND (
						(
							YEAR(`date`) = :iYear1
						) OR (
							`repeat` = 1 AND
							YEAR(`date`) < :iYear2
						)
					)
			";
			$aSQL = array(
				'iYear1' => $iYear,
				'iYear2' => $iYear
			);
			$aHolidays = DB::getQueryCol($sSQL, $aSQL);
			$iYear++;

		} while($iYear <= date('Y', $iTill));

		// ================================================== // Calculate the hours to work

		$iTotal = 0;
		$oDateF = new WDDate($iFrom);
		$oDateT = new WDDate($iTill);
		$oDateT->add(1, WDDate::DAY);
		$aHoursPerDayMonth = array();

		while($oDateF->get(WDDate::DATES) != $oDateT->get(WDDate::DATES)) {

			$sMonthIndex = $oDateF->get(WDDate::YEAR).$oDateF->get(WDDate::MONTH);

			// wenn es sich um einen Feiertag handelt kann der Tag ignoriert werden
			foreach((array)$aHolidays as $iKey => $iValue) {
				if(date('d.m', $oDateF->get(WDDate::TIMESTAMP)) == date('d.m', $iValue)) {
					$oDateF->add(1, WDDate::DAY);
					continue 2;
				}
			}

			foreach((array)$aContracts as $iKey => $aValue) {

				// Vertrag überspringen wenn der aktuelle Tag nicht im Vertragszeitraum liegt
				// -- Vertrag beginnt später --
				if($oDateF->get(WDDate::TIMESTAMP) < $aValue['from']) {
					continue;
				}
				// -- Vertrag endet früher --
				if(
					$oDateF->get(WDDate::TIMESTAMP) > $aValue['until'] &&
					$aValue['until'] > 0
				) {
					// den Eintrag entfernen damit er nicht immer wieder durchlaufen wird
					// (kann etwas Performance bringen wenn es hier extrem viele Einträge gibt)
					unset($aContracts[$iKey]);
					unset($aHoursPerDayMonth[$iKey]);
					continue;
				}

				// wenn am aktuellen Tag laut Vertrag nicht gearbeitet werden muss wird der Tag übersprungen
				if(!Ext_Office_Employee_Contract::isWorkday($aValue, $oDateF)) {
					break;
				}

				// die Soll-Stunden pro Tag für den aktuellen Monat berechnen
				if(!isset($aHoursPerDayMonth[$iKey][$sMonthIndex])) {
					$aHoursPerDayMonth[$iKey][$sMonthIndex] = Ext_Office_Employee_Contract::calculateHoursPerDayInMonth(
						$aValue,
						$oDateF->get(WDDate::YEAR),
						$oDateF->get(WDDate::MONTH)
					);
				}

				$fHoursPerDay = $aHoursPerDayMonth[$iKey][$sMonthIndex];

				// den aktuellen Tag überspringen wenn der Mitarbeiter an diesem
				// Tag frei hat (Krank, Urlaub, Überstunden, ...)
				if(isset(
					$aFreeDatesCache[$oDateF->get(WDDate::YEAR)]
					                [$oDateF->get(WDDate::MONTH)]
					                [$oDateF->get(WDDate::DAY)]
				)) {
					$iId = $aFreeDatesCache[$oDateF->get(WDDate::YEAR)]
					                       [$oDateF->get(WDDate::MONTH)]
					                       [$oDateF->get(WDDate::DAY)];
					if($aFreeDatesQuotes[$iId] > 0) {
						if($aFreeDatesQuotes[$iId] >= 1) {
							// der Tag hat keine Soll-Stunden
							$fHoursPerDay = 0.00;
						} else {
							// der Tag hat anteilige Soll-Stunden
							$fHoursPerDay = ($fHoursPerDay * $aFreeDatesQuotes[$iId]);
						}
						$aFreeDatesQuotes[$iId]--;
					}
				}

				// die Soll-Arbeitszeit für den aktuellen Tag (in Sekunden) addieren
				$iTotal += ($fHoursPerDay * 3600);

				// nach dem ersten Vertrag mit passendem Vertragszeitraum für den aktuellen Tag keinen
				// weiteren Vertrag suchen
				break;

			}

			$oDateF->add(1, WDDate::DAY);

		}

		$aSollTimes = $this->getFormatedTimes($iTotal);

		return $aSollTimes;

	}

	// ========================================================================================= //

	public function getTimeclockEntries($sDate) {
		
		$sSql = "
			SELECT
				`ot`.*,
				UNIX_TIMESTAMP(`ot`.`end`) - UNIX_TIMESTAMP(`ot`.`start`) `duration`
			FROM
				`office_timeclock` AS `ot` INNER JOIN
				`office_project_employees`	AS `ope` ON
					`ot`.`p2e_id` = `ope`.`id`
			WHERE
				`ot`.`active` = 1 AND
				`ot`.`action` != 'new' AND
				`ot`.`action` != 'declined' AND
				`ope`.`employee_id` = :employee_id AND
				DATE(`ot`.`start`) = :day
			ORDER BY
				`ot`.`start`
		";
		$aSql = array(
			'employee_id'	=> $this->_aData['id'],
			'day'			=> $sDate
		);
		$aWorks = DB::getPreparedQueryData($sSql, $aSql);

		return $aWorks;

	}

	// ========================================================================================= //

	// ARBEITSSTUNDEN
	/**
	 * Returns the monthly works overview by employee ID
	 * 
	 * @param int : The number of selected month
	 * @param int : The number of selected year
	 * @return array : The array with works by employee ID
	 */
	public function getWorks($iMonth, $iYear)
	{
		$iMonth = str_pad($iMonth, 2, '0', STR_PAD_LEFT);

		$sSQL = "
			SELECT
				UNIX_TIMESTAMP(`ot`.`start`) AS `start`,
				IF(UNIX_TIMESTAMP(`ot`.`end`) != 0, UNIX_TIMESTAMP(`ot`.`end`), UNIX_TIMESTAMP(NOW())) AS `end`
			FROM
				`office_timeclock`			AS `ot`
					INNER JOIN
				`office_project_employees`	AS `ope`
					ON
				`ot`.`p2e_id` = `ope`.`id`
			WHERE
				`ot`.`active` = 1
					AND
				`ot`.`cleared` != 9
					AND
				`ot`.`action` != 'new'
					AND
				`ope`.`employee_id` = :iEmployeeID
					AND
				MONTH(`ot`.`start`) = :iMonth
					AND
				YEAR(`ot`.`start`) = :iYear
			ORDER BY
				`ot`.`start`
		";
		$aSQL = array(
			'iEmployeeID'	=> $this->_aData['id'],
			'iMonth'		=> $iMonth,
			'iYear'			=> $iYear
		);
		$aWorks = DB::getPreparedQueryData($sSQL, $aSQL);

		$oDate = new WDDate();
		$oDate->set('01.'.$iMonth.'.'.$iYear, WDDate::DATES);

		$aDays = array();
		for($i = 1; $i <= $oDate->get(WDDate::MONTH_DAYS); $i++)
		{
			if(!isset($aDays[$oDate->get(WDDate::DAY).'.'.$iMonth]))
			{
				$aDays[$oDate->get(WDDate::DAY).'.'.$iMonth] = array('work' => 0, 'break' => 0);
			}
			$oDate->add(1, WDDate::DAY);
		}

		$sLastDate = '01.'.$iMonth;
		$iTmp = 0;
		foreach((array)$aWorks as $iKey => $aValue)
		{
			$aDays[date('d.m', $aValue['start'])]['work'] += $aValue['end'] - $aValue['start'];

			if(isset($aWorks[$iKey+1]))
			{
				if(date('d.m', $aWorks[$iKey+1]['start']) != $sLastDate)
				{
					$aDays[date('d.m', $aValue['start'])]['break'] = $iTmp;
					$iTmp = 0;
					$sLastDate = date('d.m', $aWorks[$iKey+1]['start']);
				}
				else if(date('d.m', $aWorks[$iKey+1]['start']) == $sLastDate)
				{
					$iTmp += $aWorks[$iKey+1]['start'] - $aValue['end'];
				}
			}
			else
			{
				$aDays[date('d.m', $aValue['start'])]['break'] = $iTmp;
			}
		}

		// Format the output
		$aResult = array();
		foreach((array)$aDays as $sKey => $aValue)
		{
			$aTimesW	= $this->getFormatedTimes($aValue['work']);
			$aTimesB	= $this->getFormatedTimes($aValue['break']);
			$aTimesT	= $this->getFormatedTimes($aValue['work'] + $aValue['break']);

			$aResult[] = array(
				'date'		=> $sKey,
				'work'		=> $aTimesW['H'].':'.$aTimesW['M'].':'.$aTimesW['S'],
				'break'		=> $aTimesB['H'].':'.$aTimesB['M'].':'.$aTimesB['S'],
				'total'		=> $aTimesT['H'].':'.$aTimesT['M'].':'.$aTimesT['S']
			);
		}

		return $aResult;
	}

	// ========================================================================================= //

	// LOGINDETAILS
	public function getWorksDetails($iMonth, $iYear)
	{
		$sSQL = "
			SELECT
				`op`.`title` AS `project`,
				UNIX_TIMESTAMP(`ot`.`start`) AS `start`,
				IF(UNIX_TIMESTAMP(`ot`.`end`) != 0, UNIX_TIMESTAMP(`ot`.`end`), UNIX_TIMESTAMP(NOW())) AS `end`,
				`c`.".$this->_aConfig['field_company']." AS `company`,
				`opc`.`title`,
				`opa`.`alias`
			FROM
				`office_timeclock`			AS `ot`
					INNER JOIN
				`office_project_employees`	AS `ope`
					ON
				`ot`.`p2e_id` = `ope`.`id`
					INNER JOIN
				`office_projects`			AS `op`
					ON
				`ope`.`project_id` = `op`.`id`
					INNER JOIN
				`customer_db_".$this->_aConfig['database']."` AS `c`
					ON
				`op`.`customer_id` = `c`.`id`
					INNER JOIN
				`office_project_positions`	AS `opp`
					ON
				`ot`.`p2p_id` = `opp`.`id`
					LEFT OUTER JOIN
				`office_project_categories`	AS `opc`
					ON
				`opp`.`category_id` = `opc`.`id`
					LEFT OUTER JOIN
				`office_project_aliases`	AS `opa`
					ON
				`opp`.`alias_id` = `opa`.`id`
			WHERE
				`ot`.`active` = 1
					AND
				`ot`.`cleared` != 9
					AND
				`ot`.`action` != 'new'
					AND
				`ope`.`employee_id` = :iEmployeeID
					AND
				MONTH(`ot`.`start`) = :iMonth
					AND
				YEAR(`ot`.`start`) = :iYear
			ORDER BY
				`ot`.`start`
		";
		$aSQL = array(
			'iEmployeeID'	=> $this->_aData['id'],
			'iMonth'		=> $iMonth,
			'iYear'			=> $iYear
		);
		$aResult = DB::getPreparedQueryData($sSQL, $aSQL);

		foreach((array)$aResult as $iKey => $aValue)
		{
			$aResult[$iKey]['start'] = strftime('%x %X', $aValue['start']);
			$aResult[$iKey]['end'] = strftime('%x %X', $aValue['end']);
			$aTmp = $this->getFormatedTimes($aValue['end'] - $aValue['start']);
			$aResult[$iKey]['time'] = $aTmp['H'].':'.$aTmp['M'].':'.$aTmp['S'];

			$sCheckDay1 = strftime('%x', $aValue['start']);
			$sCheckDay2 = strftime('%x', $aValue['end']);
			
			// login and logout on different days 
			if($sCheckDay1 != $sCheckDay2) {
				$aResult[$iKey]['different_days'] = 1;
			} else {
				$aResult[$iKey]['different_days'] = 0;
			}
			
			if($aValue['alias'] != '')
			{
				$aResult[$iKey]['title'] = $aValue['alias'] . ' - ' . $aValue['title'];
			}
		}

		return $aResult;
	}

	// ========================================================================================= //

	/**
	 * Calculates and formats the H:i:s by seconds
	 * 
	 * @param int : The number of seconds
	 * @return array : Formated times
	 */
	public function getFormatedTimes($iSeconds) {

		$aTimes = Util::getFormatedTimes($iSeconds);

		return $aTimes;

	}

	// ========================================================================================= //

	public function saveResidualHoliday($iYear, $fDays) {

		$sSql = "
			REPLACE
				`office_timeclock_free_dates_residual`
			SET
				`employee_id` = :employee_id,
				`year` = :year,
				`days` = :days
			";
		$aSql = array(
			'employee_id'=>(int)$this->id,
			'year'=>$iYear,
			'days'=>(float)$fDays
		);
		DB::executePreparedQuery($sSql, $aSql);

	}

	// ========================================================================================= //

	public function getResidualHoliday($iYear) {

		$sSql = "
			SELECT
				`days`
			FROM
				`office_timeclock_free_dates_residual`
			WHERE
				`employee_id` = :employee_id AND
				`year` = :year
			";
		$aSql = array(
			'employee_id'=>(int)$this->id,
			'year'=>$iYear
		);
		$fDays = DB::getQueryOne($sSql, $aSql);

		return $fDays;

	}

}
