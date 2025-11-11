<?php

class Ext_Thebing_Management_Statistic extends Ext_Thebing_Basic
{
	// DB table
	protected $_sTable				= 'kolumbus_statistic_statistics';

	// Default GUI description
	public static $_sDescription	= 'Thebing » Management » Statistiken';		

	// Selected agencies
	protected $_aAgencies			= array();

	// Selected agency countries
	protected $_aAgencyCountries	= array();

	// Selected agency groups
	protected $_aAgencyGroups		= array();

	// Selected categories
	protected $_aCategories			= array();

	// Selected columns
	protected $_aColumns			= array();

	// Selected countries
	protected $_aCountries			= array();

	// Selected nationalities
	protected $_aNationalities		= array();

	// Available currencies
	protected $_aCurrencies			= array();

	// Selected intervals
	protected $_aIntervals			= array();

	// Selected schools
	protected $_aSchools			= array();

	protected $_aFormat = [
		'title' => [
			'required' => true
		]
	];
	/* ==================================================================================================== */

	public function __construct($iDataID = 0, $sTable = null)
	{

		parent::__construct($iDataID, $sTable);

		$this->_aData['client_id']	= \Ext_Thebing_Client::getClientId();
	}


	public function __get($sName)
	{
		
		Ext_Gui2_Index_Registry::set($this);
		
		if($sName == 'agencies')
		{
			$sValue = $this->_aAgencies;
		}
		else if($sName == 'agency_categories')
		{
			$sValue = $this->_aCategories;
		}
		else if($sName == 'agency_countries')
		{
			$sValue = $this->_aAgencyCountries;
		}
		else if($sName == 'agency_groups')
		{
			$sValue = $this->_aAgencyGroups;
		}
		else if($sName == 'columns')
		{
			$sValue = $this->_aColumns;
		}
		else if($sName == 'countries')
		{
			$sValue = $this->_aCountries;
		}
		else if($sName == 'nationalities')
		{
			$sValue = $this->_aNationalities;
		}
		else if($sName == 'currencies')
		{
			$sValue = $this->_aCurrencies;
		}
		else if($sName == 'intervals')
		{
			$sValue = $this->_aIntervals;
		}
		else if($sName == 'schools')
		{
			$sValue = $this->_aSchools;
		}
		else if($sName == 'currency_id')
		{
			// Sicherstellen, dass immer eine vorhandene Währung verwendet wird (Standardstatistiken)
			$sValue = parent::__get($sName);
			$aCurrencies = Ext_Thebing_Management_Statistic::getCurrencies();
			if(!array_key_exists($sValue, $aCurrencies)) {
				reset($aCurrencies);
				$sValue = key($aCurrencies);
			}
		}
		else
		{
			$sValue = parent::__get($sName);
		}

					// Sicherstellen, dass die Währung auch vorhanden ist. Das kann bei Standardstatistiken anders sein
					if($aValue['db_column'] == 'currency_id') {
						
					}
		return $sValue;
	}


	public function __set($sName, $mValue)
	{
		if($sName == 'agencies')
		{
			$this->_aAgencies = $mValue;
		}
		else if($sName == 'agency_categories')
		{
			$this->_aCategories = $mValue;
		}
		else if($sName == 'agency_countries')
		{
			$this->_aAgencyCountries = $mValue;
		}
		else if($sName == 'agency_groups')
		{
			$this->_aAgencyGroups = $mValue;
		}
		else if($sName == 'columns')
		{
			$this->_setColumns($mValue);
		}
		else if($sName == 'countries')
		{
			$this->_aCountries = $mValue;
		}
		else if($sName == 'nationalities')
		{
			$this->_aNationalities = $mValue;
		}
		else if($sName == 'intervals')
		{
			$this->_setIntervals($mValue);
		}
		else if($sName == 'schools')
		{
			$this->_aSchools = $mValue;
		}
		else if($sName == 'client_id' || $sName == 'user_id')
		{
			throw new Exception('Client-ID and User-ID are not rewritable values.');
		}
		else
		{
			parent::__set($sName, $mValue);
		}

	}

	/* ==================================================================================================== */


	/**
	 * Gui2 wrapper
	 */
	public function getListQueryData($oGui = null, $bForSelect = false) {
		
		$aQueryData = array();

		$aQueryData['data'] = array(
			'iCliendID'	=> \Ext_Thebing_Client::getClientId(),
			'sTable'	=> $this->_sTable
		);
		
		$sFormat = $this->_formatSelect();

		$sSelect = "
			`kms`.*,
			UNIX_TIMESTAMP(`kms`.`changed`) AS `changed`,
			UNIX_TIMESTAMP(`kms`.`created`) AS `created`
		";

		if($bForSelect)
		{
			$sSelect = "`kms`.`id`, `kms`.`title`";
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aMasterUserIds = Ext_Thebing_Access::getMasterUserIds();

		$oMatrix = new Ext_Thebing_Access_Matrix_Statistics;

		$aItems = $oMatrix->getListByUserRight();

		$sAccessWhere = '';
		if(!in_array(\Access::getInstance()->id, $aMasterUserIds)) {
			if(empty($aItems)) {
				$aItems[0] = true;
			}

			$aAccessWhere = array();
			foreach((array)$aItems as $iKey => $sValue) {
				if($iKey > 0) {
					$aAccessWhere[] = (int)$iKey;
				}
			}

			if(!empty($aAccessWhere)) {
				$sAccessWhere .= " AND `kms`.`id` IN ( ".join(', ', $aAccessWhere)." ) ";
			}
		}

		if(!Ext_Thebing_Util::isDevSystem() && !Ext_Thebing_Util::isTestSystem()) {
			$sAccessWhere .= " AND (`kmp`.`id` IS NULL OR `kmp`.`system` = 0) ";
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aQueryData['sql'] = "
			SELECT
				" . $sSelect . "
			FROM
				`kolumbus_statistic_statistics` AS `kms`		LEFT OUTER JOIN
				`kolumbus_statistic_pages_statistics` AS `ksps`		ON
					`kms`.`id` = `ksps`.`statistic_id`			LEFT OUTER JOIN
				`kolumbus_statistic_pages` AS `kmp`					ON
					`ksps`.`page_id` = `kmp`.`id`
			WHERE
				`kms`.`client_id`	= :iCliendID AND
				`kms`.`active`	= 1
				" . $sAccessWhere . "
			GROUP BY
				`kms`.`id`
			ORDER BY
				`kms`.`title`
		";

		if($bForSelect)
		{
			$aList = DB::getQueryPairs($aQueryData['sql'], $aQueryData['data']);

			return $aList;
		}

		return $aQueryData;
		
	}


	/**
	 * Get statistics list by user access right
	 */
	public function getListByUserRight()
	{
		$oMatrix = new Ext_Thebing_Access_Matrix_Statistics;

		$aList = $oMatrix->getListByUserRight();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oClient = Ext_Thebing_Client::getInstance();

		$sSQL = "
			SELECT
				`id`,
				`type`
			FROM
				`kolumbus_statistic_statistics`
			WHERE
				`client_id` = :iClientID OR
				`client_id` = 0
		";
		$aSQL = array('iClientID' => $oClient->id);
		$aTypes = DB::getQueryPairs($sSQL, $aSQL);

		foreach((array)$aList as $iKey => $sValue)
		{
			$aList[$iKey] = array(
				'title'	=> $sValue,
				'type'	=> $aTypes[$iKey]
			);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $aList;

	}


	/**
	 * Get available currencies
	 */
	public static function getCurrencies()
	{
		$oThis = new self();

		$aCurrencies = $oThis->_aCurrencies;

		return $aCurrencies;
	}


	/**
	 * Get groups
	 */
	public static function getGroups($bWithEmpty = true)
	{
		$aGroups = array(
			1 => L10N::t('Agenturen', self::$_sDescription),
			2 => L10N::t('Agenturgruppen', self::$_sDescription),
			3 => L10N::t('Agenturkategorien', self::$_sDescription),
			4 => L10N::t('Agenturländer', self::$_sDescription),
		);

		if($bWithEmpty)
		{
			return Ext_Thebing_Util::addEmptyItem($aGroups);
		}

		return $aGroups;
	}


	/**
	 * Get intervals list
	 */
	public static function getIntervals($bWithEmpty = true)
	{
		$aIntervals = array(
			1 => L10N::t('Jahr', self::$_sDescription),
			2 => L10N::t('Quartal', self::$_sDescription),
			3 => L10N::t('Monat', self::$_sDescription),
			4 => L10N::t('Woche', self::$_sDescription),
			5 => L10N::t('Tag', self::$_sDescription)
		);

		if($bWithEmpty)
		{
			return Ext_Thebing_Util::addEmptyItem($aIntervals);
		}

		return $aIntervals;
	}


	/**
	 * Get list-types list
	 */
	public static function getListTypes($bWithEmpty = true)
	{
		$aListTypes = array(
			1 => L10N::t('Summe', self::$_sDescription),
			2 => L10N::t('Detailliste', self::$_sDescription)
		);

		if($bWithEmpty)
		{
			return Ext_Thebing_Util::addEmptyItem($aListTypes);
		}

		return $aListTypes;
	}


	/**
	 * Get periods
	 * 
	 * @return array
	 */
	public static function getPeriods($bWithEmpty = true)
	{
		$aPeriods = array(
			1 => L10N::t('Buchungsdatum', self::$_sDescription),
			//2 => L10N::t('Anreise', self::$_sDescription),
			3 => L10N::t('Leistungszeitraum', self::$_sDescription),
			//4 => L10N::t('Zahlungseingang', self::$_sDescription),
//			5 => L10N::t('Anfrage', self::$_sDescription)
		);

		if($bWithEmpty)
		{
			return Ext_Thebing_Util::addEmptyItem($aPeriods);
		}

		return $aPeriods;
	}


	/**
	 * Get start withs (Ausgehend von)
	 * 
	 * @param bool $bWithEmpty
	 * @return array
	 */
	public static function getStartWiths($bWithEmpty = true)
	{
		$aStartWiths = array(
			1 => L10N::t('Buchung', self::$_sDescription),
			2 => L10N::t('Agentur', self::$_sDescription),
			3 => L10N::t('Kurs', self::$_sDescription),
			4 => L10N::t('Lehrer', self::$_sDescription),
			5 => L10N::t('Unterkunftskategorie', self::$_sDescription),
			6 => L10N::t('Unterkunftsanbieter', self::$_sDescription),
//			7 => L10N::t('Anfrage', self::$_sDescription)
			
		);

		if($bWithEmpty)
		{
			return Ext_Thebing_Util::addEmptyItem($aStartWiths);
		}

		return $aStartWiths;
	}


	/**
	 * Get types
	 * 
	 * @return array
	 */
	public static function getTypes($bWithEmpty = true)
	{
		$aTypes = array(
			1 => L10N::t('relativ', self::$_sDescription),
			2 => L10N::t('absolut', self::$_sDescription)
		);

		if($bWithEmpty)
		{
			return Ext_Thebing_Util::addEmptyItem($aTypes);
		}

		return $aTypes;
	}

	/**
	 * Save entry
	 */
	public function save($bLog = true)
	{
		$aIntervals			= array_unique($this->_aIntervals);
		$aAgencies			= $this->_aAgencies;
		$aAgencyCategories	= $this->_aCategories;
		$aAgencyCountries	= $this->_aAgencyCountries;
		$aAgencyGroups		= $this->_aAgencyGroups;
		$aColumns			= $this->_aColumns;
		$aCountries			= $this->_aCountries;
		$aSchools			= $this->_aSchools;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Make correctures

		// Gibts nicht mehr, deswegen immer deaktiviert (#9671)
		$this->start_page = 0;

		if($this->list_type == 2 || $this->type != 1)
		{
			$aIntervals = array();

			if($this->list_type == 2)
			{
				$this->interval = 0;
			}

			//$this->start_page = 0;
		}

		if($this->list_type != 1) // Wenn NICHT Summenansicht
		{
			unset($aColumns['groups']);

			$this->type = 2;
		}
		else
		{
			$this->start_with = 0;
		}

		if((int)$this->agency != 1 || (int)$this->group_by == 0)
		{
			$aAgencies = $aAgencyCategories = $aAgencyCountries = $aAgencyGroups = array();

			if($this->agency != 1)
			{
				$this->group_by = 0;
			}
		}
		else
		{
			switch((int)$this->group_by)
			{
				case 1: $aAgencyCategories = $aAgencyCountries = $aAgencyGroups = array();	break;
				case 2: $aAgencies = $aAgencyCategories = $aAgencyCountries = array();		break;
				case 3: $aAgencies = $aAgencyCountries = $aAgencyGroups = array();			break;
				case 4: $aAgencies = $aAgencyCategories = $aAgencyGroups = array();			break;
			}
		}

		if($this->direct_customer != 1)
		{
			$aCountries = array();
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		parent::save($bLog);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Save multiselects

		$sSQL = "
			DELETE FROM
				`kolumbus_statistic_statistic_intervals`
			WHERE
				`statistic_id` = :iStatisticID
		";
		$aSQL = array('iStatisticID' => $this->id);
		DB::executePreparedQuery($sSQL, $aSQL);

		$iPosition = 0;
		foreach((array)$aIntervals as $iInterval)
		{
			$aInsert = array(
				'statistic_id'	=> $this->id,
				'interval'		=> $iInterval,
				'position'		=> $iPosition++
			);
			DB::insertData('kolumbus_statistic_statistic_intervals', $aInsert);
		}

		DB::updateJoinData(
			'kolumbus_statistic_statistic_links',
			array('type' => 'agency', 'statistic_id' => $this->id),
			$aAgencies,
			'link_id'
		);

		DB::updateJoinData(
			'kolumbus_statistic_statistic_links',
			array('type' => 'agency_category', 'statistic_id' => $this->id),
			$aAgencyCategories,
			'link_id'
		);

		DB::updateJoinData(
			'kolumbus_statistic_statistic_links',
			array('type' => 'agency_country', 'statistic_id' => $this->id),
			$aAgencyCountries,
			'link_id'
		);

		DB::updateJoinData(
			'kolumbus_statistic_statistic_links',
			array('type' => 'agency_group', 'statistic_id' => $this->id),
			$aAgencyGroups,
			'link_id'
		);

		DB::updateJoinData(
			'kolumbus_statistic_statistic_links',
			array('type' => 'country', 'statistic_id' => $this->id),
			$aCountries,
			'link_id'
		);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Save schools multiselect

		$oClient = Ext_Thebing_Client::getInstance();

		$aAllSchools = $oClient->getSchools(true);
		$aAllSchools = Ext_Thebing_Access_User::clearSchoolsListByAccessRight($aAllSchools);

		$aTempSchools = array_flip($aSchools);
		$aTempSchools = Ext_Thebing_Access_User::clearSchoolsListByAccessRight($aTempSchools);
		$aTempSchools = array_flip($aTempSchools);

		if(empty($aTempSchools))
		{
			$aTempSchools = array(0);
		}
		if(empty($aAllSchools))
		{
			$aAllSchools = array(0);
		}

		$sSQL = "
			DELETE FROM
				`kolumbus_statistic_statistic_links`
			WHERE
				`statistic_id` = :iStatisticID AND
				`type` = 'school' AND
				`link_id` IN(" . implode(',', array_keys($aAllSchools)) . ")
		";
		$aSQL = array('iStatisticID' => $this->id);
		DB::executePreparedQuery($sSQL, $aSQL);

		foreach((array)$aSchools as $iSchoolID)
		{
			if(in_array($iSchoolID, $aTempSchools))
			{
				$aInsert = array(
					'statistic_id'	=> $this->id,
					'type'			=> 'school',
					'link_id'		=> $iSchoolID
				);
				DB::insertData('kolumbus_statistic_statistic_links', $aInsert);
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Reset columns data

		$sSQL = "
			DELETE FROM
				`kolumbus_statistic_cols`
			WHERE
				`statistic_id` = :iStatisticID
		";
		$aSQL = array('iStatisticID' => (int)$this->id);
		DB::executePreparedQuery($sSQL, $aSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Save columns data

		$aAntiRedundance = array(
			'cols'		=> 'column',
			'groups'	=> 'group'
		);

		foreach((array)$aAntiRedundance as $sKey => $sType)
		{
			$iPosition = 1;

			foreach((array)$aColumns[$sKey] as $iColumnID)
			{
				$aInsert = array(
					'statistic_id'	=> (int)$this->id,
					'column_id'		=> (int)$iColumnID,
					'position'		=> (int)$iPosition,
					'type'			=> $sType
				);

				// Default value in the DB is -1
				if(isset($aColumns['settings'][$iColumnID]))
				{
					$aInsert['settings'] = (int)$aColumns['settings'][$iColumnID];
				}
				if(isset($aColumns['max_by'][$iColumnID]))
				{
					$aInsert['max_by'] = (int)$aColumns['max_by'][$iColumnID];
				}

				DB::insertData('kolumbus_statistic_cols', $aInsert);

				$iPosition++;
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$this->_aIntervals			= $aIntervals;
		$this->_aAgencies			= $aAgencies;
		$this->_aCategories			= $aAgencyCategories;
		$this->_aAgencyCountries	= $aAgencyCountries;
		$this->_aAgencyGroups		= $aAgencyGroups;
		$this->_aColumns			= $aColumns;
		$this->_aCountries			= $aCountries;
		$this->_aSchools			= $aSchools;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Create access right

		$oMatrix = new Ext_Thebing_Access_Matrix_Statistics;
		$oMatrix->createOwnerRight($this->id);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $this;
	}

	/* ==================================================================================================== */

	/**
	 * Load available currencies by client ID
	 */
	protected function _loadCurrencies()
	{
		$oClient = Ext_Thebing_Client::getInstance();

		$aCurrencies = $oClient->getSchoolsCurrencies();

		$this->_aCurrencies = $aCurrencies;
	}


	/**
	 * See parent
	 * 
	 * @param $iDataID
	 */
	protected function _loadData($iDataID)
	{
		parent::_loadData($iDataID);

		$this->_loadCurrencies();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Load multiselects

		if($iDataID > 0)
		{
			$sSQL = "
				SELECT
					`interval`
				FROM
					`kolumbus_statistic_statistic_intervals`
				WHERE
					`statistic_id` = :iStatisticID
				ORDER BY
					`position`
			";
			$aSQL = array('iStatisticID' => $this->id);
			$this->_aIntervals = DB::getQueryCol($sSQL, $aSQL);

			if(empty($this->_aIntervals))
			{
				$this->_aIntervals = array();
			}

			$this->_aAgencies = DB::getJoinData(
				'kolumbus_statistic_statistic_links',
				array('type' => 'agency', 'statistic_id' => $this->id),
				'link_id'
			);

			$this->_aCategories = DB::getJoinData(
				'kolumbus_statistic_statistic_links',
				array('type' => 'agency_category', 'statistic_id' => $this->id),
				'link_id'
			);

			$this->_aAgencyCountries = DB::getJoinData(
				'kolumbus_statistic_statistic_links',
				array('type' => 'agency_country', 'statistic_id' => $this->id),
				'link_id'
			);

			$this->_aAgencyGroups = DB::getJoinData(
				'kolumbus_statistic_statistic_links',
				array('type' => 'agency_group', 'statistic_id' => $this->id),
				'link_id'
			);

			$this->_aCountries = DB::getJoinData(
				'kolumbus_statistic_statistic_links',
				array('type' => 'country', 'statistic_id' => $this->id),
				'link_id'
			);

			$this->_aCountries = DB::getJoinData(
				'kolumbus_statistic_statistic_links',
				array('type' => 'nationality', 'statistic_id' => $this->id),
				'link_id'
			);

			$this->_aSchools = DB::getJoinData(
				'kolumbus_statistic_statistic_links',
				array('type' => 'school', 'statistic_id' => $this->id),
				'link_id'
			);

			$aTempSchools = array_flip($this->_aSchools);
			$aTempSchools = Ext_Thebing_Access_User::clearSchoolsListByAccessRight($aTempSchools);

			foreach((array)$this->_aSchools as $iKey => $iSchoolID)
			{
				if(!array_key_exists($iSchoolID, $aTempSchools))
				{
					unset($this->_aSchools[$iKey]);
				}
			}

			$this->_aSchools = array_values($this->_aSchools);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Load columns data

		if($iDataID > 0)
		{
			// Gelöschte Columns rausschmeißen, da ansonsten das tolle JS abschmiert
			$sSQL = "
				SELECT
					`ksc`.*
				FROM
					`kolumbus_statistic_cols` `ksc` INNER JOIN
					`kolumbus_statistic_cols_definitions` `kscd` ON
						`kscd`.`id` = `ksc`.`column_id` AND
						`kscd`.`active` = 1
				WHERE
					`statistic_id` = :iStatisticID
				ORDER BY
					`type`, `position`
			";
			$aSQL = array(
				'iStatisticID' => (int)$iDataID
			);
			$aColumns = (array)DB::getPreparedQueryData($sSQL, $aSQL);

			foreach($aColumns as $aColumn)
			{
				if($aColumn['type'] == 'column')
				{
					$this->_aColumns['cols'][] = $aColumn['column_id'];
				}
				else
				{
					$this->_aColumns['groups'][] = $aColumn['column_id'];
				}

				if($aColumn['settings'] != -1)
				{
					$this->_aColumns['settings'][$aColumn['column_id']] = $aColumn['settings'];
				}
				if($aColumn['max_by'] > 0)
				{
					$this->_aColumns['max_by'][$aColumn['column_id']] = $aColumn['max_by'];
				}
			}
		}
	}


	/**
	 * Set columns
	 * 
	 * @param array $aColumns
	 */
	protected function _setColumns($aColumns)
	{
		$this->_aColumns = array();

		foreach((array)$aColumns['cols'] as $iKey => $iColumnID)
		{
			if(!empty($iColumnID))
			{
				$this->_aColumns['cols'][] = $iColumnID;

				if(isset($aColumns['settings'][$iColumnID]))
				{
					$this->_aColumns['settings'][$iColumnID] = $aColumns['settings'][$iColumnID];
				}
				if(isset($aColumns['max_by'][$iColumnID]))
				{
					$this->_aColumns['max_by'][$iColumnID] = $aColumns['max_by'][$iColumnID];
				}
			}
		}

		foreach((array)$aColumns['groups'] as $iKey => $iColumnID)
		{
			if(!empty($iColumnID))
			{
				$this->_aColumns['groups'][] = $iColumnID;

				if(isset($aColumns['settings'][$iColumnID]))
				{
					$this->_aColumns['settings'][$iColumnID] = $aColumns['settings'][$iColumnID];
				}
				if(isset($aColumns['max_by'][$iColumnID]))
				{
					$this->_aColumns['max_by'][$iColumnID] = $aColumns['max_by'][$iColumnID];
				}
			}
		}
	}


	/**
	 * Set intervals
	 * 
	 * @param array $aIntervals
	 */
	protected function _setIntervals($aIntervals)
	{
		$this->_aIntervals = array();

		if(is_array($aIntervals) && isset($aIntervals['dir']))
		{
			foreach((array)$aIntervals['dir'] as $iID => $iDir)
			{
				$iValue = (int)$aIntervals['cnt'][$iID];

				if($iDir == 0)
				{
					$iValue *= -1;
				}

				$this->_aIntervals[] = $iValue;
			}
		}
		else if(is_array($aIntervals))
		{
			$this->_aIntervals = $aIntervals;
		}
	}
}

?>