<?php

class Ext_Thebing_Management_PageBlock_Result
{
	/**
	 * Werte formatieren
	 * @var bool
	 */
	public $bFormat = true;

	// Cache array
	protected static $_aCache		= array();

	// The columns data
	protected $_aColumns			= array();

	// The formated results
	protected $_aData				= array();

	// The formated labels
	protected $_aLabels				= array();
	protected $_bBuildLabels		= true;

	// The list type
	protected $_iListType			= null;

	// The output language
	protected $_sLang				= null;

	// The period
	protected $_iPeriod				= null;

	// The unformated results
	protected $_aResults			= array();

	/* ==================================================================================================== */

	/**
	 * The constructor
	 * 
	 * @param object $oStatistic
	 * @param array $aFilterDates
	 * @param array $aColumnsData
	 */
	public function __construct($oStatistic, array $aFilterDates, $aColumnsData = array())
	{
		$this->_aColumns	= $oStatistic->columns + array('data' => $aColumnsData);

		$this->_iListType	= $oStatistic->list_type;

		$this->_iPeriod		= $oStatistic->period;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		foreach($aFilterDates as $iKey => $aDates)
		{
			$sLocalFrom	= Ext_Thebing_Format::LocalDate($aDates['from']->get(WDDate::TIMESTAMP));
			$sLocalTill	= Ext_Thebing_Format::LocalDate($aDates['till']->get(WDDate::TIMESTAMP));

			if($sLocalFrom == $sLocalTill) {
				$sTitle = $sLocalFrom;
			} else {
				$sTitle = $sLocalFrom . ' - ' . $sLocalTill;
			}
			
			$this->_aLabels[$iKey + 1] = array(
				'title'	=> $sTitle,
				'from' => $aDates['from']->get(WDDate::DB_DATE), // Benötigt für Agentur-CRM
				'until' => $aDates['till']->get(WDDate::DB_DATE), // Benötigt für Agentur-CRM
				'count'	=> 0,
				'data'	=> array()
			);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$this->_sLang = System::getInterfaceLanguage();

		self::$_aCache['columns'] = Ext_Thebing_Management_Statistic_Gui2::getColumns();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		unset($aColumnsData, $oStatistic, $aFilterDates);
	}

	public function setLabels($aLabels) {
		$this->_bBuildLabels = false;
		$this->_aLabels = $aLabels;
	}
	
	/* ==================================================================================================== */

	/**
	 * Add unformated results from DB
	 * 
	 * @param array $aResults
	 */
	public function addResult(array $aResults)
	{
		foreach($aResults as $aResult)
		{
			$iColumnID = (int)$aResult['column_id'];

			if($this->_iListType == 1) // Summe
			{
				$iPeriod	= (int)$aResult['period'];
				$iGroupKey	= (int)$aResult['query_group_key'];
				$mGroupID	= (string)$aResult['query_group_id'];
				$mSubGroup 	= (string)$aResult['query_sub_group_id'];

				$this->_aResults[$iPeriod][$iGroupKey][$mGroupID][$mSubGroup][$iColumnID][] = $aResult;
			}
			else // Details
			{
				$sRowID = (int)$aResult['unique_row_key'];

				if(empty($sRowID))
				{
					continue; // No results for current line found
				}

				$this->_aResults[$sRowID][$iColumnID][] = $aResult;
			}
		}
	}

	public function setData($aData) {
		$this->_aData = $aData;
	}

	public function setResults($aData) {
		$this->_aResults = $aData;
	}

	/**
	 * WRAPPER OF $this->_checkColumnInt();
	 * 
	 * @param int $iColumnID
	 * @return bool
	 */
	public function checkColumnInt($iColumnID)
	{
		return $this->_checkColumnInt($iColumnID);
	}


	/**
	 * Format results
	 * 
	 * @param bool $bExport
	 */
	public function format($bExport = false)
	{
		if($this->_iListType == 1) { // Summe
			$this->_mergeS();

			if($this->bFormat) {
				$this->_formatS($bExport);
			}
		} else { // Details
			$this->_mergeD();

			if($this->bFormat) {
				$this->_formatD($bExport);
			}
		}
	}


	/**
	 * Get formated results
	 * 
	 * @return array
	 */
	public function getData()
	{
		return $this->_aData;
	}


	/**
	 * Get the labels
	 * 
	 * @return array
	 */
	public function getLabels()
	{
		return $this->_aLabels;
	}

	/* ==================================================================================================== */

	/**
	 * Get the label names
	 * 
	 * @param int $iColumnID
	 * @param array $aParams
	 * @return array
	 */
	protected function _getLabelNames($iColumnID, $aParams = array()) {

		$sSelect = "";
		$sFrom = "";
		$sWhere = "";
		$sUnionSelect = "";

		switch($iColumnID)
		{
			case 14: // Geschlecht
			{
				$aGenders = Ext_Thebing_Util::getGenders(false, '', $this->_sLang);

				return $aGenders[$aParams['key']];
			}
			case 15: // Land
			case 17: // Nationalität
			case 158: // Agenturland
			case 174: // Nationalität in %
			{
				$sSelect = "`cn_iso_2`, `cn_short_" . $this->_sLang . "`";

				$sFrom = "data_countries";

				$sWhere = "`cn_iso_2`";

				break;
			}
			case 16: // Muttersprache
			{
				$sSelect = "`iso_639_1`, `name_" . $this->_sLang . "`";

				$sFrom = "data_languages";

				$sWhere = "`iso_639_1`";

				break;
			}
			case 18: // Status des Schülers
			{
				$sSelect = "`id`, `text`";

				$sFrom = "kolumbus_student_status";

				$sWhere = "`id`";

				break;
			}
			case 19: // Wie sind Sie auf uns aufmerksam geworden
			{
				$sSelect = "`id`, `tc_r_i18n`.`name` ";

				$sFrom = " `tc_referrers` `tc_r` LEFT JOIN
					`tc_referrers_i18n` `tc_r_i18n` ON
						`tc_r_i18n`.`referrer_id` = `tc_r`.`id` AND
						`tc_r_i18n`.`language_iso` = '{$this->_sLang}'
				";

				$sWhere = "`id`";

				break;
			}
			case 20: // Währung
			{
				$sSelect = "`id`, CONCAT(`sign`, ' (', `iso4217`, ')')";

				$sFrom = "kolumbus_currency";

				$sWhere = "`id`";

				break;
			}
			case 21: // Agenturen
			{
				$sSelect = "`id`, `ext_1`";

				$sFrom = "ts_companies";

				$sWhere = "`id`";

				break;
			}
			case 23: // Agenturkategorien
			{
				$sSelect = "`id`, `name`";

				$sFrom = "kolumbus_agency_categories";

				$sWhere = "`id`";

				break;
			}
			case 25: // Agenturgruppen
			{
				$sSelect = "`id`, `name`";

				$sFrom = "kolumbus_agency_groups";

				$sWhere = "`id`";

				break;
			}
			case 33: // Anfrage (Y/N)
			{
				$aYesNo = Ext_Thebing_Util::getYesNoArray();

				return $aYesNo[(int)((bool)$aParams['key'])];
			}
			case 37: // Umsätze je Kurskategorie
			case 48: // ø Kurspreis je Kurskategorie (Auflistung)
			case 69: // Kurswochen je Kurskategorie
			case 79: // ø Kursdauer je Kurskategorie in Wochen
			case 86: // ø Alter Kunde je Kurskategorie
			case 98: // Margen je Kurskategorie
			case 115: // Kosten je Kurskategorie
			case 139: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurskategorie)
			case 145: // Verdienst je Kurskategorie
			case 167: // Anzahl der Schüler je Kurskategorie
			case 205: // Kurswochen je Kurskategorie (Erwachsene)
			case 206: // Kurswochen je Kurskategorie (Minderjährige)
			{
				$sSelect = "`id`, `name_{$this->_sLang}`";

				$sFrom = "ts_tuition_coursecategories";

				$sWhere = "`id`";

				break;
			}
			case 38: // Umsätze je Kurs
			case 49: // ø Kurspreis je Kurs (Auflistung)
			case 68: // Kurswochen je Kurs
			case 78: // ø Kursdauer je Kurs in Wochen
			case 84: // ø Alter Kunde je Kurs
			case 97: // Margen je Kurs
			case 114: // Kosten je Kurs
			case 138: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurs)
			case 146: // Verdienst je Kurs
			case 166: // Anzahl der Schüler je Kurs
			{
				$sSelect = "`id`, `name_" . $this->_sLang . "`";

				$sFrom = "kolumbus_tuition_courses";

				$sWhere = "`id`";

				break;
			}
			case 39: // Umsätze je Unterkunftskategorie
			case 51: // ø Unterkunftspreis je Unterkunftskategorie
			case 71: // Unterkunftswochen je Unterkunftskategorie
			case 81: // ø Unterkunftsdauer je Unterkunftskategorie in Wochen
			case 87: // ø Alter Kunde je Unterkunftskategorie
			case 102: // Margen je Unterkunftskategorie
			case 118: // Kosten je Unterkunftskategorie
			{
				$sSelect = "`id`, `name_" . $this->_sLang . "`";

				$sFrom = "kolumbus_accommodations_categories";

				$sWhere = "`id`";

				break;
			}
			case 41: // Umsätze je generelle Kosten
			case 42: // Umsätze je kursbezogene Kosten
			case 56: // Umsätze je unterkunftsbezogene Kosten
			{
				$sSelect = "`id`, `name_" . $this->_sLang . "`";

				$sFrom = "kolumbus_costs";

				$sWhere = "`id`";

				break;
			}
			case 60: // Zahlungsmethode
			{
				$sSelect = "`id`, `name`";

				$sFrom = "kolumbus_payment_method";

				$sWhere = "`id`";

				break;
			}
			case 67: // Summe je angelegtem Steuersatz
			{
				$sSelect = "`id`, `name`";

				$sFrom = "tc_vat_rates";

				$sWhere = "`id`";

				break;
			}
			case 72: // Unterkunftswochen je Unterkunft
			case 80: // ø Unterkunftsdauer je Unterkunft in Wochen
			case 101: // Margen je Unterkunftsanbieter
			case 125: // Name des Anbieters
			case 154: // Umsatz je Unterkunftsanbieter
			case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
			{
				$sSelect = "`id`, `ext_33`";

				$sFrom = "customer_db_4";

				$sWhere = "`id`";

				break;
			}
			case 74: // Anreise je Flughafen
			case 75: // Abreise je Flughafen
			case 76: // Anreise je Flughafen im Stundenrhythmus
			case 77: // Abreise je Flughafen im Stundenrhythmus
			case 107: // Margen Transfer gesamt je Flughafen
			case 108: // Margen Transfer - Abreise je Flughafen
			case 109: // Margen Transfer - Anreise je Flughafen
			case 122: // Kosten Transfer gesamt je Flughafen
			case 123: // Kosten Transfer - Abreise je Flughafen
			case 124: // Kosten Transfer - Anreise je Flughafen
			{
				$sSelect = "`id`, `airport`";

				$sFrom = "kolumbus_airports";

				$sWhere = "`id`";

				break;
			}
			case 88: // Versicherung
			case 91: // Versicherungssumme je Versicherung
			{
				$sSelect = "`id`, `name_" . $this->_sLang . "`";

				$sFrom = "kolumbus_insurances";

				$sWhere = "`id`";

				break;
			}
			case 94: // Geleistete Stunden je Niveau
			{
				$sSelect = "`id`, `name_" . $this->_sLang . "`";

				$sFrom = "ts_tuition_levels";

				$sWhere = "`id`";

				break;
			}
			case 96: // Margen je Klasse (entsprechend Klassenplanung)
			{
				$sSelect = "`id`, `name`";

				$sFrom = "kolumbus_tuition_classes";

				$sWhere = "`id`";

				break;
			}
			case 104: // Margen je Transferanbieter (bei An- und Abreise: Preis/2)
			{
				$sSelect = "`id`, `name`";

				$sFrom = "kolumbus_companies";

				$sWhere = "`id`";

				break;
			}
			case 137: // Schulen
			{
				$sSelect = "`id`, `ext_1`";
				$sFrom = "customer_db_2";
				$sWhere = "`id`";

				break;
			}
			case 161: // Schulen / Inboxen

				$sSelect = "CONCAT(`cdb2`.`id`, '_', `kinb`.`id`) `id`, CONCAT(`cdb2`.`ext_1`, ' / ', `kinb`.`name`)";
				$sFrom = "`customer_db_2` `cdb2`, `kolumbus_inboxlist` `kinb`"; // CROSS JOIN
				$sWhere = "`id`";

				break;
			case 191: // Schüler pro internem Niveau (exkl. Storno)
				$sSelect = "`id`, `name_short`";
				$sFrom = "ts_tuition_levels";
				$sWhere = "`id`";

				break;
			case 193: // Gruppen
				$sSelect = "`id`, `name`";
				$sFrom = "kolumbus_groups";
				$sWhere = "`id`";

				break;
			case 203: // Vertriebsmitarbeiter
			case 207: // Vertriebsmitarbeiter (Anfragen)
				$sSelect = "`id`, CONCAT(`lastname`, ', ', `firstname`)";
				$sFrom = "system_user";
				$sWhere = "`id`";

				break;
			case 204: // Agenturen / Inboxen

				$sSelect = "CONCAT(`ka`.`id`, '_', `kinb`.`id`) `id`, CONCAT(`ka`.`ext_1`, ' / ', `kinb`.`name`)";
				$sFrom = "`kolumbus_agencies` `ka`, `kolumbus_inboxlist` `kinb`"; // CROSS JOIN
				$sWhere = "`id`";

				$sTranslation = strtoupper(L10N::t('Direktbucher', Ext_Thebing_Management_Statistic::$_sDescription));

				// Durch die Verwendung von IFNULL werden Direktbuchungen auch angezeigt, aber im CROSS JOIN gibt es keinen Gegenpart für ID 0
				$sUnionSelect = "
					UNION (
						SELECT
							CONCAT('0_', `kinb`.`id`), CONCAT('{$sTranslation}', ' / ', `kinb`.`name`),
							{$iColumnID} AS `XXX`
						FROM
							`kolumbus_inboxlist` `kinb`
					)
				";

				break;
			case 208: // Inbox

				$sSelect = "`kinb`.`short` `id`, `kinb`.`name`";
				$sFrom = "`kolumbus_inboxlist` `kinb`"; // CROSS JOIN
				$sWhere = "`id`";

				break;
		}

		if($iColumnID == 155 && isset($aParams['sub_key']))
		{
			$sSelect = "`id`, `name`";

			$sFrom = "kolumbus_rooms";

			$sWhere = "`id`";
		}

		if(!empty($this->_aColumns['data'][$iColumnID])) {
			$sWhere .= " IN(" . implode(',', $this->_aColumns['data'][$iColumnID]) . ")";
		} else {
			$sWhere .= " IN(0)";

			if(isset($aParams['key'])) {
				$sWhere = 1;
			}
		}

		$sSQL = "
			SELECT
				*
			FROM
				(
					(
						SELECT
							" . $sSelect . ",
							$iColumnID AS `XXX`
						FROM
							" . $sFrom . "
					) {$sUnionSelect}
				) `x`
			WHERE
				{$sWhere}
			
		";
		$sCacheKey = md5($sSQL);

		if(!isset(self::$_aCache['labels'][$sCacheKey])) {
			self::$_aCache['labels'][$sCacheKey] = DB::getQueryPairs($sSQL);
		}

		if(isset($aParams['key'])) {
			return self::$_aCache['labels'][$sCacheKey][$aParams['key']];
		}

		return self::$_aCache['labels'][$sCacheKey];
	}


	/**
	 * Set the column labels
	 */
	protected function _setLabels() {

		if($this->_bBuildLabels !== true) {
			return;
		}
		
		if($this->_iListType == 1) // Summe
		{
			$aBlock = array();

			$iCount = 0;

			foreach((array)$this->_aColumns['cols'] as $iColumnID)
			{
				$aBlock[$iColumnID] = array(
					'title'	=> self::$_aCache['columns'][$iColumnID],
					'count'	=> 1
				);

				if(
					isset($this->_aColumns['data'][$iColumnID]) &&
					!empty($this->_aColumns['data'][$iColumnID]) &&
					!isset($this->_aColumns['data'][$iColumnID][0])
				) {
					$aNames = $this->_getLabelNames($iColumnID);

					$aBlock[$iColumnID]['data'] = array();
					$aBlock[$iColumnID]['count'] = count($this->_aColumns['data'][$iColumnID]);

					$iCount += count($this->_aColumns['data'][$iColumnID]);

					foreach($this->_aColumns['data'][$iColumnID] as $mKey => $mItem)
					{
						$aBlock[$iColumnID]['data'][$mKey] = $aNames[$mKey];
					}
				} else {
					$iCount++;
				}

			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			if(!empty($this->_aColumns['groups']))
			{
				$iColumnID = reset($this->_aColumns['groups']);

				if(
					isset($this->_aColumns['data'][$iColumnID]) &&
					!empty($this->_aColumns['data'][$iColumnID])
				) {
					$aNames = $this->_getLabelNames($iColumnID);

					$this->_aLabels[1]['data'][$iColumnID] = array(
						'title'	=> self::$_aCache['columns'][$iColumnID],
						'count'	=> ($iCount * count($this->_aColumns['data'][$iColumnID])),
						'data'	=> array()
					);

					// Gruppierungsspalten einfügen
					foreach($this->_aColumns['data'][$iColumnID] as $mKey => $mItem) {
						$this->_aLabels[1]['data'][$iColumnID]['data'][$mKey] = array(
							'title'	=> $aNames[$mKey],
							'count'	=> $iCount
						);

						// Spaltendaten der Gruppe einfügen
						$this->_aLabels[1]['data'][$iColumnID]['data'][$mKey]['data'] = $aBlock;
					}

					// Summenspalte ergänzen
					if(count($this->_aColumns['data'][$iColumnID]) > 1) {
						$this->_aLabels[1]['data'][$iColumnID]['data']['-'] = array(
							'title'	=> L10N::t('Summe', Ext_Thebing_Management_Statistic::$_sDescription),
							'count'	=> $iCount,
							'data'	=> $aBlock
						);

						
					}
				}

				$this->_aLabels[1]['count'] = $iCount * count($this->_aLabels[1]['data']);

				if(isset($this->_aLabels[1]['data'][$iColumnID]['data']['-']))
				{
					$this->_aLabels[1]['data'][$iColumnID]['count'] += $iCount;
				}
			}
			else
			{
				$this->_aLabels[1]['count'] = $iCount;
				$this->_aLabels[1]['data'] = $aBlock;
			}

			if(count($this->_aLabels) > 1)
			{
				$this->_aLabels['-'] = array(
					'title'	=> L10N::t('Summe', Ext_Thebing_Management_Statistic::$_sDescription),
					'count'	=> 0,
					'data'	=> array()
				);
			}
		}
		else // Details
		{
			$aBlock = array();

			foreach((array)$this->_aColumns['cols'] as $iColumnID)
			{
				$aBlock[$iColumnID] = array(
					'title'	=> self::$_aCache['columns'][$iColumnID]
				);
			}

			$this->_aLabels[1]['data'] = $aBlock;
		}

	}

	/* ==================================================================================================== */ // SUMMEN

	/**
	 * Add new columns by founds
	 * 
	 * @param int $iColumnID
	 * @param mixed $mKey
	 */
	protected function _addColumns($iColumnID, $mKey)
	{
		$this->_aColumns['data'][$iColumnID][$mKey] = $mKey;

		if(!is_numeric($mKey))
		{
			$this->_aColumns['data'][$iColumnID][$mKey] = "'" . $mKey . "'";
		}
	}


	/**
	 * Create HTML table for output
	 * 
	 * @param string $sTitle
	 * @param mixed $mValue
	 * @return string 
	 */
	protected function _createHtmlTable($sTitle, $mValue)
	{
		$sTable = '';

		$sTable .= '<table style="background-color:#E7E7E7; border:0; border-collapse:collapse; border-spacing:0; width:100%; border-radius:6px;">';
			$sTable .= '<tr>';
				$sTable .= '<td style="white-space:nowrap; padding:2px 4px; border:0; text-align:left;">';
					$sTable .= $sTitle . '&nbsp;»&nbsp;';
				$sTable .= '</td>';
				$sTable .= '<td style="white-space:nowrap; padding:2px 4px; border:0; text-align:right;">';
					$sTable .= $mValue;
				$sTable .= '</td>';
			$sTable .= '</tr>';
		$sTable .= '</table>';

		return $sTable;
	}


	/**
	 * Format the data (Summen)
	 * 
	 * @param bool $bExport
	 */
	protected function _formatS($bExport = false) {
		
		foreach($this->_aData as $iPeriod => $aGroups)
		{
			if(!is_array($aGroups))
			{
				continue;
			}

			foreach($aGroups as $iGroupKey => $aGroupIDs)
			{
				if(!is_array($aGroupIDs))
				{
					continue;
				}

				foreach($aGroupIDs as $mGroupID => $aSubGroupIDs)
				{
					if(!is_array($aSubGroupIDs))
					{
						continue;
					}

					foreach($aSubGroupIDs as $mSubGroupID => $aColumns)
					{
						if(!is_array($aColumns))
						{
							continue;
						}

						foreach($aColumns as $iColumnID => $mData)
						{
							switch($iColumnID)
							{
								case 40: // Umsatz je Unterkunft (Kategorie / Raum / Verpflegung)
								{
									$aLines = array();

									foreach($mData as $mKey => $mValue)
									{
										$aKeys = explode('_', $mKey);

										$oCat	= Ext_Thebing_Accommodation_Category::getInstance($aKeys[0]);
										$oRoom	= Ext_Thebing_Accommodation_Roomtype::getInstance($aKeys[1]);
										$oMeal	= Ext_Thebing_Accommodation_Meal::getInstance($aKeys[2]);

										$sField = 'short_' . $this->_sLang;

										$sName = $oCat->$sField . '/' . $oRoom->$sField . '/' . $oMeal->$sField;

										if($bExport)
										{
											$sLine = $sName . ' » ' . Ext_Thebing_Format::Number($mValue);
										}
										else
										{
											$sLine = $this->_createHtmlTable($sName, Ext_Thebing_Format::Number($mValue));
										}

										$aLines[] = $sLine;
									}

									if($bExport)
									{
										$this->_aData[$iPeriod][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID] =
											implode("\n", $aLines);
									}
									else
									{
										$this->_aData[$iPeriod][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID] =
											implode('<div style="height:5px;"></div>', $aLines);
									}

									break;
								}
								case 76: // Anreise je Flughafen im Stundenrhytmus
								case 77: // Abreise je Flughafen im Stundenrhytmus
								{
									foreach($mData as $iKey => $aSubData)
									{
										ksort($aSubData);

										$aLines = array();

										foreach($aSubData as $iHour => $mValue)
										{
											$sLine = '';

											if($bExport)
											{
												$sLine .= str_pad($iHour, 2, '0', STR_PAD_LEFT) . ':00-';
												$sLine .= str_pad($iHour, 2, '0', STR_PAD_LEFT) . ':59=';
												$sLine .= $mValue;
											}
											else
											{
												$sTitle = '';

												$sTitle .= str_pad($iHour, 2, '0', STR_PAD_LEFT) . ':00-';
												$sTitle .= str_pad($iHour, 2, '0', STR_PAD_LEFT) . ':59';

												$sLine = $this->_createHtmlTable($sTitle, $mValue);
											}

											$aLines[] = $sLine;
										}

										if($bExport)
										{
											$this->_aData[$iPeriod][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID][$iKey] =
												implode("\n", $aLines);
										}
										else
										{
											$this->_aData[$iPeriod][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID][$iKey] =
												implode('<div style="height:5px;"></div>', $aLines);
										}
									}

									break;
								}
								case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
								{
									foreach($mData as $iKey => $aSubData)
									{
										$aLines = array();

										foreach($aSubData as $iSubKey => $mValue)
										{
											$oRoom = Ext_Thebing_Accommodation_Room::getInstance($iSubKey);

											if($bExport)
											{
												$sLine = $oRoom->name . ' » ' . Ext_Thebing_Format::Number($mValue);
											}
											else
											{
												$sLine = $this->_createHtmlTable($oRoom->name, Ext_Thebing_Format::Number($mValue));
											}

											$aLines[] = $sLine;
										}

										if($bExport)
										{
											$this->_aData[$iPeriod][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID][$iKey] =
												implode("\n", $aLines);
										}
										else
										{
											$this->_aData[$iPeriod][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID][$iKey] =
												implode('<div style="height:5px;"></div>', $aLines);
										}
									}

									break;
								}
								default:
								{

									if(!is_array($mData))
									{
										if(is_numeric($mData)) {

											if(
												$mData < 0.005 &&
												$mData > -0.005 &&
												!$this->showColumnZeroValue($iColumnID)
											) {
												$this->_aData[$iPeriod][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID] = '';
											}
											else if($this->_checkColumnInt($iColumnID))
											{
												$this->_aData[$iPeriod][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID] = round($mData);
											}
											else
											{
												$this->_aData[$iPeriod][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID] =
													Ext_Thebing_Format::Number($mData);
											}
										}
									}
									else
									{
										foreach($mData as $mKey => $mValue)
										{
											if(is_numeric($mValue))
											{
												if($mValue < 0.005 && $mValue > -0.005)
												{
													$this->_aData[$iPeriod][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID][$mKey] = '';
												}
												else if($this->_checkColumnInt($iColumnID))
												{
													$this->_aData[$iPeriod][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID][$mKey] = round($mValue);
												}
												else
												{
													$this->_aData[$iPeriod][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID][$mKey] =
														Ext_Thebing_Format::Number($mValue);
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$this->_setLabels();
	}


	/**
	 * Check the INT state of a column result
	 * 
	 * @param int $iColumnID
	 * @return bool
	 */
	protected function _checkColumnInt($iColumnID)
	{
		$aColumns = array(
			5, // Alter
			6, // Schüler gesamt
			7, // Erwachsene schüler
			8, // Minderjährige schüler
			9, // Weibliche Schüler
			10, // Männliche Schüler
			11, // ø Alter gesamt
			12, // ø Alter männliche Schüler
			13, // ø Alter weibliche Schüler
			15, // Land
			16, // Muttersprache
			17, // Nationalität
			18, // Status des Schülers
			19, // Wie sind Sie auf uns aufmerksam geworden
			21, // Agenturen
			23, // Agenturkategorien
			25, // Agenturgruppen
			27, // Stornierungen gesamt
			28, // Stornierungen Minderjähriger
			29, // Stornierungen Erwachsener
			30, // Stornierungen männlich
			31, // Stornierungen weiblich
			34, // Anfragen (Anzahl)
			35, // Umwandlung (Anzahl)
			73, // Anzahl Transfers (Anreise, Abreise, An- und Abreise)
			74, // Anreise je Flughafen
			75, // Abreise je Flughafen
			84, // ø Alter Kunde je Kurs
			86, // ø Alter Kunde je Kurskategorie
			87, // ø Alter Kunde je Unterkunftskategorie
			89, // Versicherungen (Anzahl)
			126, // Aufgenommene Schüler gesamt
			128, // Anzahl der Bewertungen
			129, // Niedrigste Bewertung (Note)
			130, // Höchste Bewertung (Note)
			131, // Häufigste Bewertung (Note, bei mehreren CVS)
			137, // Schulen
			147, // Anzahl der Bewertungen
			148, // Niedrigste Bewertungen
			149, // Höchste Bewertungen
			150, // Häufigste Bewertungen
			156, // Anzahl der Schüler (nur kursbezogen, mit Rechnung)
			157, // Anzahl der Schüler (nur unterkunftsbezogen)
			159, // Anzahl der Schüler (Erwachsene) nur kursbezogen exkl. Stornierungen
			160, // Anzahl der Schüler (Minderjährige) nur kursbezogen exkl. Stornierungen
			161, // Schulen / Inboxen
			166, // Anzahl der Schüler je Kurs
			167, // Anzahl der Schüler je Kurskategorie
			168, // Anzahl der Schüler (nur kursbezogen)
			191, // Schüler pro internem Niveau (exkl. Storno)
			192, // Anzahl der Schüler ohne internes Level (exkl. Storno)
			193, // Gruppen
			194, // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status der Buchung, exkl. Storno)
			195, // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status des Kurses, exkl. Storno)
			196, // Anzahl der Angebote
			197, // Anzahl der Anfragen ohne Angebot
			198, // Anzahl fälliger nachzuhakender Anfragen
			199, // Anzahl umgewandelter Anfragen in %
			200, // Durchschnittliche Dauer bis zur Umwandlung (Tage)
			201, // Anzahl der Online-Anmeldungen (Buchungen)
			202, // Anzahl der Online-Anmeldungen (Anfragen)
			203, // Vertriebsmitarbeiter
			204, // Agenturen / Inbox
		);

		$bInt = false;
		if(
			!is_numeric($iColumnID) ||
			in_array($iColumnID, $aColumns)
		) {
			$bInt = true;
		}
		
		return $bInt;
	}


	/**
	 * Check the sum compatibility of a column
	 * 
	 * @param int $iColumnID
	 * @return bool
	 */
	protected function _checkColumnSum($iColumnID)
	{
		if($this->_iListType == 1) // Summe
		{
			if($this->_iPeriod == 3) // Leistungszeitraum
			{
				$aColumns = array(
					6, // Schüler gesamt
					7, // Erwachsene schüler
					8, // Minderjährige schüler
					9, // Weibliche Schüler
					10, // Männliche Schüler
					15, // Land
					16, // Muttersprache
					17, // Nationalität
					36, // Umsätze (inkl. Storno) gesamt
					37, // Umsätze je Kurskategorie
					38, // Umsätze je Kurs
					39, // Umsätze je Unterkunftskategorie
					41, // Umsätze je generelle Kosten
					42, // Umsätze je kursbezogene Kosten
					43, // Umsätze Agenturkunden (netto, inkl. Storno)
					44, // Umsätze Direktkunden (inkl. Storno)
					54, // Zahlungseingänge (Summe)
					56, // Umsätze je unterkunftsbezogene Kosten
					57, // Zahlungsausgänge (Summe)
					63, // Provision gesamt
					66, // Stornierungsumsätze
					67, // Summe je angelegtem Steuersatz
					68, // Kurswochen je Kurs
					69, // Kurswochen je Kurskategorie
					70, // Kurswochen gesamt
					71, // Unterkunftswochen je Unterkunftskategorie
					72, // Unterkunftswochen je Unterkunft
					90, // Versicherungsumsatz
					91, // Versicherungssumme je Versicherung
					93, // Geleistete Stunden gesamt
					94, // Geleistete Stunden je Niveau
					113, // Kosten Lehrer
					114, // Kosten je Kurs
					115, // Kosten je Kurskategorie
					116, // Unterkunftskosten
					118, // Kosten je Unterkunftskategorie
					119, // Kosten Transfer gesamt
					120, // Kosten Transfer - Abreise
					121, // Kosten Transfer - Anreise
					122, // Kosten Transfer gesamt je Flughafen
					123, // Kosten Transfer - Abreise je Flughafen
					124, // Kosten Transfer - Anreise je Flughafen
					144, // Verdienst gesamt
					145, // Verdienst je Kurskategorie
					146, // Verdienst je Kurs
					154, // Umsatz je Unterkunftsanbieter
					156, // Anzahl der Schüler (nur kursbezogen, mit Rechnung)
					164, // Lektionen (Gruppenreisende)
					165, // Lektionen (Einzelreisende),
					166, // Anzahl der Schüler je Kurs
					167, // Anzahl der Schüler je Kurskategorie
					168, // Anzahl der Schüler (nur kursbezogen)
					170, // Kursumsatz (brutto)
					171, // Kursumsatz (netto)
					172, // Umsätze gesamt (brutto, inkl. Storno)
					173, // Umsätze gesamt (netto, inkl. Storno)
					174, // Nationalität in %
					176, // Umsatz - gesamt (netto, inkl. Storno und Steuern)
					178, // Umsatz - Unterkunft (netto, exkl. Steuern)
					180, // Umsatz - Transfer (netto, exkl. Steuern)
					182, // Umsatz - manuelle Positionen (netto, exkl. Steuern)
					184, // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
					186, // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
					188, // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
					190, // Totale Steuern (netto)
					192, // Anzahl der Schüler ohne internes Level (exkl. Storno)
					194, // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status der Buchung, exkl. Storno)
					195, // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status des Kurses, exkl. Storno)
					205, // Kurswochen je Kurskategorie (Erwachsene)
					206, // Kurswochen je Kurskategorie (Minderjährige)
				);
			}
			else
			{
				$aColumns = array(
					6, // Schüler gesamt
					7, // Erwachsene schüler
					8, // Minderjährige schüler
					9, // Weibliche Schüler
					10, // Männliche Schüler
					15, // Land
					16, // Muttersprache
					17, // Nationalität
					18, // Status des Schülers
					19, // Wie sind Sie auf uns aufmerksam geworden
					21, // Agenturen
					23, // Agenturkategorien
					25, // Agenturgruppen
					27, // Stornierungen gesamt
					28, // Stornierungen Minderjähriger
					29, // Stornierungen Erwachsener
					30, // Stornierungen männlich
					31, // Stornierungen weiblich
					34, // Anfragen (Anzahl)
					35, // Umwandlung (Anzahl)
					36, // Umsätze (inkl. Storno) gesamt
					37, // Umsätze je Kurskategorie
					38, // Umsätze je Kurs
					39, // Umsätze je Unterkunftskategorie
					41, // Umsätze je generelle Kosten
					42, // Umsätze je kursbezogene Kosten
					43, // Umsätze Agenturkunden (netto, inkl. Storno)
					44, // Umsätze Direktkunden (inkl. Storno)
					54, // Zahlungseingänge (Summe)
					56, // Umsätze je unterkunftsbezogene Kosten
					57, // Zahlungsausgänge (Summe)
					63, // Provision gesamt
					66, // Stornierungsumsätze
					67, // Summe je angelegtem Steuersatz
					68, // Kurswochen je Kurs
					69, // Kurswochen je Kurskategorie
					70, // Kurswochen gesamt
					71, // Unterkunftswochen je Unterkunftskategorie
					72, // Unterkunftswochen je Unterkunft
					73, // Anzahl Transfers (Anreise, Abreise, An- und Abreise)
					74, // Anreise je Flughafen
					75, // Abreise je Flughafen
					89, // Versicherungen (Anzahl)
					90, // Versicherungsumsatz
					91, // Versicherungssumme je Versicherung
					93, // Geleistete Stunden gesamt
					94, // Geleistete Stunden je Niveau
					113, // Kosten Lehrer
					114, // Kosten je Kurs
					115, // Kosten je Kurskategorie
					116, // Unterkunftskosten
					118, // Kosten je Unterkunftskategorie
					119, // Kosten Transfer gesamt
					120, // Kosten Transfer - Abreise
					121, // Kosten Transfer - Anreise
					122, // Kosten Transfer gesamt je Flughafen
					123, // Kosten Transfer - Abreise je Flughafen
					124, // Kosten Transfer - Anreise je Flughafen
					137, // Schulen
					144, // Verdienst gesamt
					145, // Verdienst je Kurskategorie
					146, // Verdienst je Kurs
					147, // Anzahl der Bewertungen
					154, // Umsatz je Unterkunftsanbieter
					156, // Anzahl der Schüler (nur kursbezogen, mit Rechnung)
					157, // Anzahl der Schüler (nur unterkunftsbezogen)
					159, // Anzahl der Schüler (Erwachsene) nur kursbezogen exkl. Stornierungen
					160, // Anzahl der Schüler (Minderjährige) nur kursbezogen exkl. Stornierungen
					161, // Schulen / Inboxen
					168, // Anzahl der Schüler (nur kursbezogen)
					170, // Kursumsatz (brutto)
					171, // Kursumsatz (netto)
					172, // Umsätze gesamt (brutto, inkl. Storno)
					173, // Umsätze gesamt (netto, inkl. Storno)
					174, // Nationalität in %
					176, // Umsatz - gesamt (netto, inkl. Storno und Steuern)
					178, // Umsatz - Unterkunft (netto, exkl. Steuern)
					180, // Umsatz - Transfer (netto, exkl. Steuern)
					182, // Umsatz - manuelle Positionen (netto, exkl. Steuern)
					184, // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
					186, // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
					188, // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
					190, // Totale Steuern (netto)
					191, // Schüler pro internem Niveau (exkl. Storno)
					192, // Anzahl der Schüler ohne internes Level (exkl. Storno)
					193, // Gruppen
					194, // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status der Buchung, exkl. Storno)
					195, // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status des Kurses, exkl. Storno)
					196, // Anzahl der Angebote
					197, // Anzahl der Anfragen ohne Angebot
					198, // Anzahl fälliger nachzuhakender Anfragen
					201, // Anzahl der Online-Anmeldungen (Buchungen)
					202, // Anzahl der Online-Anmeldungen (Anfragen)
					203, // Vertriesmitarbeiter
					204, // Agenturen / Inboxen
					205, // Kurswochen je Kurskategorie (Erwachsene)
					206, // Kurswochen je Kurskategorie (Minderjährige)
				);
			}
		}
		else // Details
		{
			$aColumns = array(
				36, // Umsätze (inkl. Storno) gesamt
				54, // Zahlungseingänge (Summe)
				57, // Zahlungsausgänge (Summe)
				63, // Provision gesamt
				70, // Kurswochen gesamt
				90, // Versicherungsumsatz
				93, // Geleistete Stunden gesamt
				126, // Aufgenommene Schüler gesamt
				128, // Anzahl der Bewertungen
				140, // Kursumsatz
				141, // Unterkunftumsatz
				142, // Stornierungsumsatz
				144, // Verdienst gesamt
				147, // Anzahl der Bewertungen
				170, // Kursumsatz (brutto)
				171, // Kursumsatz (netto)
				172, // Umsätze gesamt (brutto, inkl. Storno)
			 	173, // Umsätze gesamt (netto, inkl. Storno)
				176, // Umsatz - gesamt (netto, inkl. Storno und Steuern)
				178, // Umsatz - Unterkunft (netto, exkl. Steuern)
				180, // Umsatz - Transfer (netto, exkl. Steuern)
				182, // Umsatz - manuelle Positionen (netto, exkl. Steuern)
				184, // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
				186, // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
				188, // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
				190, // Totale Steuern (netto)
			);
		}

		return in_array($iColumnID, $aColumns);
	}

	/**
	 * Spalten, die auch eine 0 anzeigen dürfen (wird normalerweise durch leeren String ersetzt)
	 *
	 * Anmerkung: Wenn der Query NULL zurückliefert, geht der Wert der Zelle gar nicht durch
	 * die Klasse. Daher wird der Wert in mergeS(), wo der Wert normalerweise schon ersetzt wird,
	 * (bei Summen-Statistiken) einfach nur direkt mit der Methode geprüft und nicht auf noch
	 * einmal auf den Wert. Ansonsten würde ja round() aus null auch 0 machen…
	 *
	 * @TODO Muss bei Ergänzung bei den jeweiligen Fällen in den Methoden noch ergänzt werden
	 *
	 * @param $iColumnId
	 * @return bool
	 */
	protected function showColumnZeroValue($iColumnId) {
		$aColumns = [
			200
		];

		return in_array($iColumnId, $aColumns);
	}

	/**
	 * Merge results (Summen)
	 */
	protected function _mergeS()
	{
		foreach($this->_aResults as $iPeriod => $aGroups)
		{
			if(!is_array($aGroups))
			{
				continue;
			}

			foreach($aGroups as $iGroupKey => $aGroupIDs)
			{
				if(!is_array($aGroupIDs))
				{
					continue;
				}

				foreach($aGroupIDs as $mGroupID => $aSubGroupIDs)
				{
					if(!is_array($aSubGroupIDs))
					{
						continue;
					}

					foreach($aSubGroupIDs as $mSubGroupID => $aColumns) {

						if(!is_array($aColumns))
						{
							continue;
						}

						foreach($aColumns as $iColumnID => $aResults)
						{
							$mData = array();

							switch($iColumnID)
							{
								case 6: // Schüler gesamt
								case 7: // Erwachsene schüler
								case 8: // Minderjährige schüler
								case 9: // Weibliche Schüler
								case 10: // Männliche Schüler
								case 11: // ø Alter gesamt
								case 12: // ø Alter männliche Schüler
								case 13: // ø Alter weibliche Schüler
								case 27: // Stornierungen gesamt
								case 28: // Stornierungen Minderjähriger
								case 29: // Stornierungen Erwachsener
								case 30: // Stornierungen männlich
								case 31: // Stornierungen weiblich
								case 34: // Anfragen (Anzahl)
								case 35: // Umwandlung (Anzahl)
								case 36: // Umsätze (inkl. Storno) gesamt
								case 43: // Umsätze Agenturkunden (netto, inkl. Storno)
								case 44: // Umsätze Direktkunden (inkl. Storno)
								case 45: // ø Reisepreis (alles, inkl. Storno)
								case 46: // ø Kurspreis je Kurs
								case 47: // ø Kurspreis je Kunde
								case 50: // ø Unterkunftspreis
								case 52: // ø Nettoreisepreis (exkl. Storno) - Agenturbuchungen
								case 53: // ø Bruttoreisepreis (exkl. Storno) - Direktbuchungen
								case 54: // Zahlungseingänge (Summe)
								case 63: // Provision gesamt
								case 64: // ø Provision absolut pro Kunde bei Agenturbuchungen
								case 65: // ø Provisionssatz je Kunde bei Agenturbuchungen
								case 66: // Stornierungsumsätze
								case 70: // Kurswochen gesamt
								case 73: // Anzahl Transfers (Anreise, Abreise, An- und Abreise)
								case 82: // ø Anzahl Schüler pro Lektion
								case 83: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (gesamt)
								case 89: // Versicherungen (Anzahl)
								case 90: // Versicherungsumsatz
								case 93: // Geleistete Stunden gesamt
								case 113: // Kosten Lehrer
								case 116: // Unterkunftskosten
								case 119: // Kosten Transfer gesamt
								case 120: // Kosten Transfer - Abreise
								case 121: // Kosten Transfer - Anreise
								case 147: // Anzahl der Bewertungen
								case 150: // Häufigste Bewertungen
								case 151: // ø Bewertung gesamt
								case 156: // Anzahl der Schüler (nur kursbezogen, mit Rechnung)
								case 157: // Anzahl der Schüler (nur unterkunftsbezogen)
								case 159: // Anzahl der Schüler (Erwachsene) nur kursbezogen exkl. Stornierungen
								case 160: // Anzahl der Schüler (Minderjährige) nur kursbezogen exkl. Stornierungen
								case 162: // Anzahl der Schüler (Einzelreisende)
								case 163: // Anzahl der Schüler (Gruppenreisende)
								case 164: // Lektionen (Gruppenreisende)
								case 165: // Lektionen (Einzelreisende)
								case 168: // Anzahl der Schüler (nur kursbezogen)
								case 171: // Kursumsatz (netto)
								case 172: // Umsätze gesamt (brutto, inkl. Storno)
								case 173: // Umsätze gesamt (netto, inkl. Storno)
								case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
								case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
								case 180: // Umsatz - Transfer (netto, exkl. Steuern)
								case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
								case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
								case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
								case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
								case 190: // Totale Steuern (netto)
								case 192: // Anzahl der Schüler ohne internes Level (exkl. Storno)
								case 194: // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status der Buchung, exkl. Storno)
								case 195: // Anzahl der neuen Schüler (nur kursbezogen, basierend auf Status des Kurses, exkl. Storno)
								case 196: // Anzahl der Angebote
								case 197: // Anzahl der Anfragen ohne Angebot
								case 198: // Anzahl fälliger nachzuhakender Anfragen
								case 199: // Anzahl umgewandelter Anfragen in %
								case 200: // Durchschnittliche Dauer bis zur Umwandlung (Tage)
								case 201: // Anzahl der Online-Anmeldungen (Buchungen)
								case 202: // Anzahl der Online-Anmeldungen (Anfragen)
								{
									// Spalte so ausgeben
									if(
										abs($aResults[0]['result']) >= 0.005 ||
										$this->showColumnZeroValue($iColumnID)
									) {
										$mData = round($aResults[0]['result'], 2);
									} else {
										$mData = 0;
									}

									break;
								}
								case 15: // Land
								case 16: // Muttersprache
								case 17: // Nationalität
								case 18: // Status des Schülers
								case 19: // Wie sind Sie auf uns aufmerksam geworden
								case 21: // Agenturen
								case 23: // Agenturkategorien
								case 25: // Agenturgruppen
								case 37: // Umsätze je Kurskategorie
								case 38: // Umsätze je Kurs
								case 39: // Umsätze je Unterkunftskategorie
								case 40: // Umsatz je Unterkunft (Kategorie / Raum / Verpflegung)
								case 41: // Umsätze je generelle Kosten
								case 42: // Umsätze je kursbezogene Kosten
								case 48: // ø Kurspreis je Kurskategorie (Auflistung)
								case 49: // ø Kurspreis je Kurs (Auflistung)
								case 51: // ø Unterkunftspreis je Unterkunftskategorie
								case 56: // Umsätze je unterkunftsbezogene Kosten
								case 67: // Summe je angelegtem Steuersatz
								case 68: // Kurswochen je Kurs
								case 69: // Kurswochen je Kurskategorie
								case 71: // Unterkunftswochen je Unterkunftskategorie
								case 72: // Unterkunftswochen je Unterkunft
								case 74: // Anreise je Flughafen
								case 75: // Abreise je Flughafen
								case 78: // ø Kursdauer je Kurs in Wochen
								case 79: // ø Kursdauer je Kurskategorie in Wochen
								case 80: // ø Unterkunftsdauer je Unterkunft in Wochen
								case 81: // ø Unterkunftsdauer je Unterkunftskategorie in Wochen
								case 84: // ø Alter Kunde je Kurs
								case 86: // ø Alter Kunde je Kurskategorie
								case 87: // ø Alter Kunde je Unterkunftskategorie
								case 91: // Versicherungssumme je Versicherung
								case 94: // Geleistete Stunden je Niveau
								case 114: // Kosten je Kurs
								case 115: // Kosten je Kurskategorie
								case 118: // Kosten je Unterkunftskategorie
								case 122: // Kosten Transfer gesamt je Flughafen
								case 123: // Kosten Transfer - Abreise je Flughafen
								case 124: // Kosten Transfer - Anreise je Flughafen
								case 137: // Schulen
								case 138: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurs)
								case 139: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurskategorie)
								case 154: // Umsatz je Unterkunftsanbieter
								case 161: // Schulen / Inboxen
								case 166: // Anzahl der Schüler je Kurs
								case 167: // Anzahl der Schüler je Kurskategorie
								case 174: // Nationalität in %
								case 191: // Schüler pro internem Niveau (exkl. Storno)
								case 193: // Gruppen
								case 203: // Vertriebsmitarbeiter
								case 204: // Agenturen / Inboxen
								case 205: // Kurswochen je Kurskategorie (Erwachsene)
								case 206: // Kurswochen je Kurskategorie (Minderjährige)
								case 208: // Inbox
								{
									// Ergebnis der Spalte in jeweilige Untergruppierung schreiben
									foreach($aResults as $aResult)
									{
										if($aResult['result'] >= 0.005 || $aResult['result'] <= -0.005)
										{
											$mData[$aResult['key']] = round($aResult['result'], 2);
										}
										else
										{
											$mData[$aResult['key']] = 0;
										}

										switch($iColumnID)
										{
											case 94: // Geleistete Stunden je Niveau
											case 118: // Kosten je Unterkunftskategorie
											case 122: // Kosten Transfer gesamt je Flughafen
											case 123: // Kosten Transfer - Abreise je Flughafen
											case 124: // Kosten Transfer - Anreise je Flughafen
											case 138: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurs)
											case 139: // Auslastung in % bei Klassen in Bezug auf Maximalgröße (je Kurskategorie)
												$this->_addColumns($iColumnID, $aResult['key']);
												break;
										}
									}

									break;
								}
								case 57: // Zahlungsausgänge (Summe)
								{
									$mData = 0;

									foreach($aResults as $aResult)
									{
										$mData +=
											$aResult['transfer_pay'] +
											$aResult['teacher_pay'] +
											$aResult['acc_pay'] +
											$aResult['individual_pay'];
									}

									if($mData < 0.005 && $mData > -0.005)
									{
										$mData = '';
									}
									else
									{
										$mData = round($mData, 2);
									}

									break;
								}
								case 76: // Anreise je Flughafen im Stundenrhytmus
								case 77: // Abreise je Flughafen im Stundenrhytmus
								{
									foreach($aResults as $aResult)
									{
										$aSubResults = explode(',', $aResult['result']);

										foreach($aSubResults as $sSubResult)
										{
											$aTemp = explode('_', $sSubResult);

											if($sSubResult)
											{
												$mData[$aResult['key']][$aTemp[1]] = $aTemp[0];
											}
										}
									}

									break;
								}
								case 95: // Margen Kurse (gesamt)
								{
									$aIn = $aOut = array();

									foreach($aResults as $aResult)
									{
										$aIn[$aResult['kipi_id']]	= $aResult['price'];
										$aOut[$aResult['ktp_id']]	= $aResult['result'];
									}

									$iIns = array_sum($aIn);

									if($iIns != 0)
									{
										$mData = (1 - array_sum($aOut) / $iIns) * 100;
									}
									else
									{
										$mData = '';
									}

									break;
								}
								case 96: // Margen je Klasse (entsprechend Klassenplanung)
								{
									$aTemp = array();

									foreach($aResults as $aResult)
									{
										$aTemp[$aResult['key']][$aResult['ijc_id']]['l'] = $aResult['lessons'];
										$aTemp[$aResult['key']][$aResult['ijc_id']]['h'] = $aResult['total'];

										$aTemp[$aResult['key']][$aResult['ijc_id']]['p'][$aResult['ktp_id']] = $aResult['result'];
										$aTemp[$aResult['key']][$aResult['ijc_id']]['i'][$aResult['kipi_id']] = $aResult['price'];
									}

									foreach($aTemp as $iClassID => $aClass)
									{
										$iIn = $iOut = 0;

										if(is_array($aClass))
										{
											foreach($aClass as $aCourse)
											{
												if($aCourse['h'] > 0)
												{
													$iIn += ($aCourse['l'] / $aCourse['h']) * array_sum($aCourse['i']);
												}

												$iOut += array_sum($aCourse['p']);
											}
										}

										if($iIn != 0)
										{
											$mData[$iClassID] = (1 - $iOut / $iIn) * 100;
										}
										else
										{
											$mData[$iClassID] = '';
										}

										$this->_addColumns($iColumnID, $iClassID);
									}

									break;
								}
								case 97: // Margen je Kurs
								case 98: // Margen je Kurskategorie
								{
									$aIn = $aOut = array();

									foreach($aResults as $aResult)
									{
										$aIn[$aResult['key']][$aResult['kipi_id']]	= $aResult['price'];
										$aOut[$aResult['key']][$aResult['ktp_id']]	= $aResult['result'];
									}

									foreach($aIn as $mKey => $aIns)
									{
										$iIns = array_sum($aIns);

										if($iIns != 0)
										{
											$mData[$mKey] = (1 - array_sum($aOut[$mKey]) / $iIns) * 100;
										}
										else
										{
											$mData[$mKey] = '';
										}

										$this->_addColumns($iColumnID, $mKey);
									}

									break;
								}
								case 100: // Margen Unterkunftsbezogen (gesamt)
								{
									$aIn = $aOut = array();

									foreach($aResults as $aResult)
									{
										$aIn[$aResult['kipi_id']]	= $aResult['price'];
										$aOut[$aResult['kap_id']]	= $aResult['result'];
									}

									$iIns = array_sum($aIn);

									if($iIns != 0)
									{
										$mData = (1 - array_sum($aOut) / $iIns) * 100;
									}
									else
									{
										$mData = '';
									}

									break;
								}
								case 101: // Margen je Unterkunftsanbieter
								case 102: // Margen je Unterkunftskategorie
								{
									$aIn = $aOut = array();

									foreach($aResults as $aResult)
									{
										$aIn[$aResult['key']][$aResult['kipi_id']]	= $aResult['price'];
										$aOut[$aResult['key']][$aResult['kap_id']]	= $aResult['result'];
									}

									foreach($aIn as $mKey => $aIns)
									{
										$iIns = array_sum($aIns);

										if($iIns != 0)
										{
											$mData[$mKey] = (1 - array_sum($aOut[$mKey]) / $iIns) * 100;
										}
										else
										{
											$mData[$mKey] = '';
										}

										$this->_addColumns($iColumnID, $mKey);
									}

									break;
								}
								case 103: // Margen transferbezogen (gesamt)
								case 105: // Margen je Transferabreise (bei An- und Abreise: Preis/2)
								case 106: // Margen je Transferanreise (bei An- und Abreise: Preis/2)
								{
									$aIn = $aOut = array();

									foreach($aResults as $aResult)
									{
										$aIn[$aResult['query_price_group']]		= $aResult['price'];
										$aOut[$aResult['query_result_group']]	= $aResult['result'];
									}

									$iIns = array_sum($aIn);

									if($iIns != 0)
									{
										$mData = (1 - array_sum($aOut) / $iIns) * 100;
									}
									else
									{
										$mData = '';
									}

									break;
								}
								case 104: // Margen je Transferanbieter (bei An- und Abreise: Preis/2)
								case 107: // Margen Transfer gesamt je Flughafen
								case 108: // Margen Transfer - Abreise je Flughafen
								case 109: // Margen Transfer - Anreise je Flughafen
								{
									$aIn = $aOut = array();

									foreach($aResults as $aResult)
									{
										$aIn[$aResult['key']][$aResult['query_price_group']]	= $aResult['price'];
										$aOut[$aResult['key']][$aResult['query_result_group']]	= $aResult['result'];
									}

									foreach($aIn as $mKey => $aIns)
									{
										$iIns = array_sum($aIns);

										if($iIns != 0)
										{
											$mData[$mKey] = (1 - array_sum($aOut[$mKey]) / $iIns) * 100;
										}
										else
										{
											$mData[$mKey] = '';
										}

										$this->_addColumns($iColumnID, $mKey);
									}

									break;
								}
								case 144: // Verdienst gesamt
								case 145: // Verdienst je Kurskategorie
								case 146: // Verdienst je Kurs
								{
									$aIn = $aOut = array();

									foreach($aResults as $aResult)
									{
										if($iColumnID == 144)
										{
											$aTemp = array_merge(
												explode(',', $aResult['transfer_pay']),
												explode(',', $aResult['teacher_pay']),
												explode(',', $aResult['acc_pay']),
												explode(',', $aResult['individual_pay'])
											);
										}
										else
										{
											$aTemp = explode(',', $aResult['teacher_pay']);
										}

										foreach($aTemp as $sTempOut)
										{
											$aTempOut = explode('_', $sTempOut);

											$aOut[$aResult['key']][$aTempOut[0]] = $aTempOut[1];
										}

										$aTemp = explode(',', $aResult['result']);

										foreach($aTemp as $sTempIn)
										{
											$aTempIn = explode('_', $sTempIn);

											$aIn[$aResult['key']][$aTempIn[0]] = $aTempIn[1];
										}
									}

									if($iColumnID == 144)
									{
										$mData = 0;

										foreach($aIn as $iKey => $aIns)
										{
											$mData += array_sum($aIns);
											$mData -= array_sum($aOut[$iKey]);
										}

										$mData = round($mData, 2);
									}
									else
									{
										foreach($aIn as $iKey => $aIns)
										{
											$mData[$iKey] += array_sum($aIns);
											$mData[$iKey] -= array_sum($aOut[$iKey]);

											$mData[$iKey] = round($mData[$iKey], 2);
										}
									}

									break;
								}
								case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
								{
									foreach($aResults as $aResult)
									{
										if($aResult['result'] >= 0.005 || $aResult['result'] <= -0.005)
										{
											$mData[$aResult['key']][$aResult['sub_key']] = round($aResult['result'], 2);
										}
										else
										{
											$mData[$aResult['key']][$aResult['sub_key']] = '';
										}
									}

									break;
								}
							}

							$this->_aData[$iPeriod][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID] = $mData;

							/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Write sum data

							if($this->_checkColumnSum($iColumnID))
							{
								if(!is_array($mData))
								{
									$this->_aData['-'][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID] += $mData;	// Bottom
									$this->_aData[$iPeriod][$iGroupKey]['-'][''][$iColumnID] += $mData;		// Right

									$this->_aData['-'][$iGroupKey]['-'][''][$iColumnID] += $mData;			// Bottom right
								}
								else
								{
									foreach($mData as $mKey => $mValue)
									{
										$this->_aData['-'][$iGroupKey][$mGroupID][$mSubGroupID][$iColumnID][$mKey] += $mValue;	// Bottom
										$this->_aData[$iPeriod][$iGroupKey]['-'][''][$iColumnID][$mKey] += $mValue;		// Right

										$this->_aData['-'][$iGroupKey]['-'][''][$iColumnID][$mKey] += $mValue;			// Bottom right
									}
								}
							}
						}
					}
				}
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$this->_aResults = array(); // Clear results
	}

	/* ==================================================================================================== */ // DETAILS

	/**
	 * Format the data (Details)
	 * 
	 * @param bool $bExport
	 */
	protected function _formatD($bExport = false)
	{
		foreach($this->_aData as $iLineID => $aColumns)
		{
			$bHasData = false; // Has the line data in min. one of the columns?

			if(is_array($aColumns))
			{
				foreach($aColumns as $iColumnID => $mData)
				{
					if(!$bHasData && !empty($mData))
					{
						$bHasData = true; // The line has data
					}

					switch($iColumnID)
					{
						case 1: // Kundennummer
						case 2: // Rechnungsnummer
						case 3: // Vorname
						case 4: // Nachname
						case 5: // Alter
						case 14: // Geschlecht
						case 15: // Land
						case 16: // Muttersprache
						case 17: // Nationalität
						case 18: // Status des Schülers
						case 19: // Wie sind Sie auf uns aufmerksam geworden
						case 20: // Währung
						case 21: // Agenturen
						case 23: // Agenturkategorien
						case 25: // Agenturgruppen
						case 33: // Anfrage (Y/N)
						case 60: // Zahlungsmethode
						case 62: // Zahlungskommentar
						case 88: // Versicherung
						case 92: // Name, Vorname (Lehrer)
						case 125: // Name des Anbieters
						case 137: // Schule
						case 158: // Agenturland
						case 161: // Schule / Inboxen
						case 193: // Gruppen
						case 203: // Vertriebsmitarbeiter
						case 208: // Inbox
						{
							$this->_aData[$iLineID][$iColumnID] = implode(', ', $mData);

							if(!$bExport)
							{
								$this->_aData[$iLineID][$iColumnID] .= '&nbsp;';
							}

							break;
						}
						case 36: // Umsätze (inkl. Storno) gesamt
						case 54: // Zahlungseingänge (Summe)
						case 57: // Zahlungsausgänge (Summe)
						case 63: // Provision gesamt
						case 70: // Kurswochen gesamt
						case 90: // Versicherungsumsatz
						case 93: // Geleistete Stunden gesamt
						case 126: // Aufgenommene Schüler gesamt
						case 128: // Anzahl der Bewertungen
						case 129: // Niedrigste Bewertung (Note)
						case 130: // Höchste Bewertung (Note)
						case 131: // Häufigste Bewertung (Note, bei mehreren CVS)
						case 132: // ø Bewertung gesamt
						case 140: // Kursumsatz
						case 141: // Unterkunftumsatz
						case 142: // Stornierungsumsatz
						case 144: // Verdienst gesamt
						case 147: // Anzahl der Bewertungen
						case 148: // Niedrigste Bewertungen
						case 149: // Höchste Bewertungen
						case 150: // Häufigste Bewertungen
						case 151: // ø Bewertung gesamt
						case 152: // Bewertungen Details (Lehrer)
						case 170: // Kursumsatz (brutto)
						case 171: // Kursumsatz (netto)
						case 172: // Umsätze gesamt (brutto, inkl. Storno)
						case 173: // Umsätze gesamt (netto, inkl. Storno)
						case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
						case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
						case 180: // Umsatz - Transfer (netto, exkl. Steuern)
						case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
						case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
						case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
						case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
						case 190: // Totale Steuern (netto)
						{
							if(!empty($this->_aData[$iLineID][$iColumnID]))
							{
								if($this->_checkColumnInt($iColumnID))
								{
									$this->_aData[$iLineID][$iColumnID] = round($mData);
								}
								else
								{
									$this->_aData[$iLineID][$iColumnID] = Ext_Thebing_Format::Number($mData);
								}
							}
							else
							{
								if(!$bExport)
								{
									$this->_aData[$iLineID][$iColumnID] = '&nbsp;';
								}
								else
								{
									$this->_aData[$iLineID][$iColumnID] = '';
								}
							}

							if(!$bExport)
							{
								$this->_aData[$iLineID][$iColumnID] = '<div style="float:right;">' . $this->_aData[$iLineID][$iColumnID] . '</div>';
							}

							break;
						}
						case 41: // Umsätze je generelle Kosten
						case 42: // Umsätze je kursbezogene Kosten
						case 55: // Zahlungseingänge (tatsächlig, einzeln)
						case 56: // Umsätze je unterkunftsbezogene Kosten
						case 58: // Zahlungsausgänge (tatsächlig, einzeln)
						case 61: // Zahlung je Rechnungsposition
						case 67: // Summe je angelegtem Steuersatz
						case 68: // Kurswochen je Kurs
						case 69: // Kurswochen je Kurskategorie
						case 71: // Unterkunftswochen je Unterkunftskategorie
						case 72: // Unterkunftswochen je Unterkunft
						case 94: // Geleistete Stunden je Niveau
						case 145: // Verdienst je Kurskategorie
						case 146: // Verdienst je Kurs
						case 154: // Umsatz je Unterkunftsanbieter
						case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
						{
							$aLines = array();

							foreach($this->_aData[$iLineID][$iColumnID] as $aData)
							{
								if($bExport)
								{
									$sLine = $aData['name'] . ' » ' . Ext_Thebing_Format::Number($aData['value']);
								}
								else
								{
									$sLine = $this->_createHtmlTable($aData['name'], Ext_Thebing_Format::Number($aData['value']));
								}

								$aLines[] = $sLine;
							}

							if($bExport)
							{
								$this->_aData[$iLineID][$iColumnID] = implode("\n", $aLines);
							}
							else
							{
								$this->_aData[$iLineID][$iColumnID] = implode('<div style="height:5px;"></div>', $aLines);
							}

							break;
						}
					}
				}
			}

			if(!$bHasData)
			{
				unset($this->_aData[$iLineID]); // The line has no data
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$this->_setLabels();
	}


	/**
	 * Merge results (Details)
	 */
	protected function _mergeD()
	{
		foreach($this->_aResults as $iLineID => $aColumns)
		{
			if(is_array($aColumns))
			{
				foreach($aColumns as $iColumnID => $aResults)
				{
					$mData = array();

					switch($iColumnID)
					{
						case 1: // Kundennummer
						case 2: // Rechnungsnummer
						case 3: // Vorname
						case 4: // Nachname
						case 5: // Alter
						case 14: // Geschlecht
						case 15: // Land
						case 16: // Muttersprache
						case 17: // Nationalität
						case 18: // Status des Schülers
						case 19: // Wie sind Sie auf uns aufmerksam geworden
						case 20: // Währung
						case 21: // Agenturen
						case 23: // Agenturkategorien
						case 25: // Agenturgruppen
						case 33: // Anfrage (Y/N)
						case 60: // Zahlungsmethode
						case 62: // Zahlungskommentar
						case 92: // Name, Vorname (Lehrer)
						case 158: // Agenturland
						{
							foreach($aResults as $aResult)
							{
								if(!empty($aResult['result']))
								{
									$aLines = explode('{_}', $aResult['result']);

									foreach($aLines as $sLine)
									{
										$aTemp = explode('{::}', $sLine);

										switch($iColumnID)
										{
											case 14: // Geschlecht
											case 15: // Land
											case 16: // Muttersprache
											case 17: // Nationalität
											case 18: // Status des Schülers
											case 19: // Wie sind Sie auf uns aufmerksam geworden
											case 20: // Währung
											case 21: // Agenturen
											case 23: // Agenturkategorien
											case 25: // Agenturgruppen
											case 33: // Anfrage (Y/N)
											case 60: // Zahlungsmethode
											case 88: // Versicherung
											case 158: // Agenturland
											{
												$aTemp[1] = $this->_getLabelNames($iColumnID, array('key' => $aTemp[1]));

												break;
											}
										}

										$mData[$aTemp[0]] = $aTemp[1];
									}
								}
							}

							break;
						}
						case 36: // Umsätze (inkl. Storno) gesamt
						case 54: // Zahlungseingänge (Summe)
						case 63: // Provision gesamt
						case 70: // Kurswochen gesamt
						case 90: // Versicherungsumsatz
						case 126: // Aufgenommene Schüler gesamt
						case 128: // Anzahl der Bewertungen
						case 129: // Niedrigste Bewertung (Note)
						case 130: // Höchste Bewertung (Note)
						case 131: // Häufigste Bewertung (Note, bei mehreren CVS)
						case 132: // ø Bewertung gesamt
						case 135: // Bewertungen Details (Unterkunft)
						case 140: // Kursumsatz
						case 141: // Unterkunftumsatz
						case 142: // Stornierungsumsatz
						case 147: // Anzahl der Bewertungen
						case 148: // Niedrigste Bewertungen
						case 149: // Höchste Bewertungen
						case 150: // Häufigste Bewertungen
						case 151: // ø Bewertung gesamt
						case 152: // Bewertungen Details (Lehrer)
						case 170: // Kursumsatz (brutto)
						case 171: // Kursumsatz (netto)
						case 172: // Umsätze gesamt (brutto, inkl. Storno)
						case 173: // Umsätze gesamt (netto, inkl. Storno)
						case 176: // Umsatz - gesamt (netto, inkl. Storno und Steuern)
						case 178: // Umsatz - Unterkunft (netto, exkl. Steuern)
						case 180: // Umsatz - Transfer (netto, exkl. Steuern)
						case 182: // Umsatz - manuelle Positionen (netto, exkl. Steuern)
						case 184: // Umsatz - zusätzliche Kursgebühren (netto, exkl. Steuern)
						case 186: // Umsatz - zusätzliche Unterkunftsgebühren (netto, exkl. Steuern)
						case 188: // Umsatz - zusätzliche generelle Gebühren (netto, exkl. Steuern)
						case 190: // Totale Steuern (netto)
						{
							if($aResults[0]['result'] >= 0.005 || $aResults[0]['result'] <= -0.005)
							{
								$mData = round($aResults[0]['result'], 2);
							}
							else
							{
								$mData = 0;
							}

							break;
						}
						case 41: // Umsätze je generelle Kosten
						case 42: // Umsätze je kursbezogene Kosten
						case 56: // Umsätze je unterkunftsbezogene Kosten
						case 67: // Summe je angelegtem Steuersatz
						case 68: // Kurswochen je Kurs
						case 69: // Kurswochen je Kurskategorie
						case 71: // Unterkunftswochen je Unterkunftskategorie
						case 72: // Unterkunftswochen je Unterkunft
						case 94: // Geleistete Stunden je Niveau
						case 154: // Umsatz je Unterkunftsanbieter
						{
							foreach($aResults as $aResult)
							{
								if($aResult['result'] >= 0.005 || $aResult['result'] <= -0.005)
								{
									if($aResult['key'])
									{
										$mData[$aResult['key']] = array(
											'name'	=> $this->_getLabelNames($iColumnID, array('key' => $aResult['key'])),
											'value'	=> round($aResult['result'], 2)
										);
									}
								}
							}

							break;
						}
						case 55: // Zahlungseingänge (tatsächlig, einzeln)
						case 61: // Zahlung je Rechnungsposition
						{
							foreach($aResults as $aResult)
							{
								if($aResult['result'] >= 0.005 || $aResult['result'] <= -0.005)
								{
									$mData[$aResult['unique']] = array(
										'name'	=> $aResult['name'],
										'value'	=> round($aResult['result'], 2)
									);
								}
							}

							break;
						}
						case 57: // Zahlungsausgänge (Summe)
						{
							$mData = 0;

							foreach($aResults as $aResult)
							{
								$mData +=
									$aResult['transfer_pay'] +
									$aResult['teacher_pay'] +
									$aResult['acc_pay'] +
									$aResult['individual_pay'];
							}

							if($mData < 0.005 && $mData > -0.005)
							{
								$mData = '';
							}
							else
							{
								$mData = round($mData, 2);
							}

							break;
						}
						case 58: // Zahlungsausgänge (tatsächlig, einzeln)
						{
							$aLines = array();

							foreach($aResults as $aResult)
							{
								if($aResult['transfer_pay'] >= 0.005 || $aResult['transfer_pay'] <= -0.005)
								{
									$aLines['kTRp']['query_kTRp_group'] = array(
										'name'	=> $aResult['query_kTRp_date'],
										'value'	=> round($aResult['transfer_pay'], 2)
									);
								}
								if($aResult['teacher_pay'] >= 0.005 || $aResult['teacher_pay'] <= -0.005)
								{
									$aLines['kTEp']['query_kTEp_group'] = array(
										'name'	=> $aResult['query_kTEp_date'],
										'value'	=> round($aResult['teacher_pay'], 2)
									);
								}
								if($aResult['acc_pay'] >= 0.005 || $aResult['acc_pay'] <= -0.005)
								{
									$aLines['kACp']['query_kACp_group'] = array(
										'name'	=> $aResult['query_kACp_date'],
										'value'	=> round($aResult['acc_pay'], 2)
									);
								}
								if($aResult['individual_pay'] >= 0.005 || $aResult['individual_pay'] <= -0.005)
								{
									$aLines['kINp']['query_kINp_group'] = array(
										'name'	=> $aResult['query_kINp_date'],
										'value'	=> round($aResult['individual_pay'], 2)
									);
								}
							}

							foreach($aLines as $aGroups)
							{
								foreach($aGroups as $aLine)
								{
									$mData[] = $aLine;
								}
							}

							break;
						}
						case 88: // Versicherung
						case 125: // Name des Anbieters
						case 137: // Schule
						case 193: // Gruppen
						case 203: // Vertriebsmitarbeiter
						case 208: // Inbox
						{
							foreach($aResults as $aResult)
							{
								if(!empty($aResult['result']))
								{
									$aLines = explode(',', $aResult['result']);

									foreach($aLines as $mLine)
									{
										switch($iColumnID)
										{
											case 88: // Versicherung
											case 125: // Name des Anbieters
											case 137: // Schule
											case 193: // Gruppen
											case 203: // Vertriebsmitarbeiter
											case 208: // Inbox
											{
												$mData[$mLine] = $this->_getLabelNames($iColumnID, array('key' => $mLine));

												break;
											}
										}
									}
								}
							}

							break;
						}
						case 93: // Geleistete Stunden gesamt
						{
							$aLines = array();

							foreach($aResults as $aResult)
							{
								if($aResult['result'] >= 0.005 || $aResult['result'] <= -0.005)
								{
									$aLines[$aResult['key']] = $aResult['result'];
								}
							}

							$iSum = array_sum($aLines);

							if($iSum)
							{
								$mData = round($iSum, 2);
							}
							else
							{
								$mData = '';
							}

							break;
						}
						case 144: // Verdienst gesamt
						case 145: // Verdienst je Kurskategorie
						case 146: // Verdienst je Kurs
						{
							$aIn = $aOut = array();

							foreach($aResults as $aResult)
							{
								if($iColumnID == 144)
								{
									$aTemp = array_merge(
										explode(',', $aResult['transfer_pay']),
										explode(',', $aResult['teacher_pay']),
										explode(',', $aResult['acc_pay']),
										explode(',', $aResult['individual_pay'])
									);
								}
								else
								{
									$aTemp = explode(',', $aResult['teacher_pay']);
								}

								foreach($aTemp as $sTempOut)
								{
									$aTempOut = explode('_', $sTempOut);

									$aOut[$aResult['key']][$aTempOut[0]] = $aTempOut[1];
								}

								$aTemp = explode(',', $aResult['result']);

								foreach($aTemp as $sTempIn)
								{
									$aTempIn = explode('_', $sTempIn);

									$aIn[$aResult['key']][$aTempIn[0]] = $aTempIn[1];
								}
							}

							if($iColumnID == 144)
							{
								$mData = 0;

								foreach($aIn as $iKey => $aIns)
								{
									$mData += array_sum($aIns);
									$mData -= array_sum($aOut[$iKey]);
								}

								$mData = round($mData, 2);

							}
							else
							{
								foreach($aIn as $iKey => $aIns)
								{
									$mData[$iKey]['name'] = $this->_getLabelNames($iColumnID, array('key' => $iKey));

									$mData[$iKey]['value'] += array_sum((array)$aIns);
									$mData[$iKey]['value'] -= array_sum((array)$aOut[$iKey]);

									$mData[$iKey]['value'] = round($mData[$iKey]['value'], 2);
								}
							}

							break;
						}
						case 155: // Umsatz je Unterkunftsanbieter pro Zimmer
						{
							foreach($aResults as $aResult)
							{
								if($aResult['result'] >= 0.005 || $aResult['result'] <= -0.005)
								{
									if($aResult['key'] && $aResult['sub_key'])
									{
										$sAcc = $this->_getLabelNames($iColumnID, array('key' => $aResult['key']));

										$sRoom = $this->_getLabelNames($iColumnID, array('key' => $aResult['sub_key'], 'sub_key' => 1));

										$sName = $sAcc . ' (' . $sRoom . ')';

										$mData[$aResult['key'] . '_' . $aResult['sub_key']] = array(
											'name'	=> $sName,
											'value'	=> round($aResult['result'], 2)
										);
									}
								}
							}

							break;
						}
					}

					$this->_aData[$iLineID][$iColumnID] = $mData;

					if($this->_checkColumnSum($iColumnID))
					{
						if(!is_array($mData))
						{
							$this->_aData['-'][$iColumnID] += $mData;
						}
					}
				}
			}

			if(count($this->_aData[$iLineID]) != count($this->_aColumns['cols']))
			{
				$aTemp = $this->_aData[$iLineID];

				$this->_aData[$iLineID] = array();

				foreach($this->_aColumns['cols'] as $iColumnID)
				{
					if(isset($aTemp[$iColumnID]))
					{
						$this->_aData[$iLineID][$iColumnID] = $aTemp[$iColumnID];
					}
					else
					{
						$this->_aData[$iLineID][$iColumnID] = array();
					}
				}
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Sum line

		krsort($this->_aData);

		if(count((array)$this->_aData['-']) != count((array)$this->_aColumns['cols'])) {
			
			$aTemp = $this->_aData['-'];

			$this->_aData['-'] = array();

			foreach($this->_aColumns['cols'] as $iColumnID)
			{
				if(isset($aTemp[$iColumnID]))
				{
					$this->_aData['-'][$iColumnID] = $aTemp[$iColumnID];
				}
				else
				{
					$this->_aData['-'][$iColumnID] = array();
				}
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$this->_aResults = array(); // Clear results
	}

	/**
	 * Extrahiert aus $this->_aData Untergruppen-IDs.
	 * Die Struktur der class.pageblock.php und dieser Datei lassen es nicht zu,
	 * 	dies sauberer zu übergeben, ohne gleich alles neu zu schreiben.
	 */
	protected function _getSubGroupItemIds() {
		$aIds = array();
		foreach($this->_aData as $aColumnData) {
			foreach($aColumnData as $aGroup) {
				foreach($aGroup as $aSubGroup) {
					foreach($aSubGroup as $iSubGroupId => $aColumn) {
						if(
							!is_array($aColumn) ||
							!is_int($iSubGroupId) ||
							in_array($iSubGroupId, $aIds))
						{
							continue;
						}

						$aIds[] = $iSubGroupId;
					}
				}
			}
		}
		return $aIds;
	}
}
