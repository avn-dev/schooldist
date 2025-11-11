<?php

class Ext_Thebing_Management_Statistic_Gui2 extends Ext_Thebing_Gui2_Data
{

	// The GUI description
	public static $sDescription = 'Thebing » Management » Statistiken';

	/* ==================================================================================================== */

	/**
	 * See parent
	 */
	public function getTranslations($sDescription)
	{
		$aData = parent::getTranslations($sDescription);

		$aData['statistics']['based_on'] = array(
			1 => L10N::t('Ausgehend vom aktuellen Jahr', $sDescription),
			2 => L10N::t('Ausgehend vom aktuellen Quartal', $sDescription),
			3 => L10N::t('Ausgehend vom aktuellen Monat', $sDescription),
			4 => L10N::t('Ausgehend von aktuellen Woche', $sDescription),
			5 => L10N::t('Ausgehend vom aktuellen Tag', $sDescription)
		);

		$aData['statistics']['intervals'] = array(
			1 => L10N::t('Jahr(e)', $sDescription),
			2 => L10N::t('Quartal(e)', $sDescription),
			3 => L10N::t('Monat(e)', $sDescription),
			4 => L10N::t('Woche(n)', $sDescription),
			5 => L10N::t('Tag(e)', $sDescription)
		);

		return $aData;
	}


	/**
	 * Sort items by title
	 * 
	 * @param array $aX
	 * @param array $aY
	 * @return int
	 */
	static public function sortByTitle($aX, $aY)
    {

		return (strcmp(strtolower($aX['title']), strtolower($aY['title'])));
	}

	/* ==================================================================================================== */

	/**
	 * Add additional dialog fields by object references
	 *
	 * Achtung: Wird auch direkt in der Statistiken-Seite benutzt (ausklappbare Filter)
	 *
	 * @param Ext_Gui2 $oGui
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param Ext_Gui2_Dialog_Tab $oTabData
	 * @param array $aOptions
	 */
	public static function addAdditionalFieldsByRef(&$oGui, &$oDialog, &$oTabData, $aOptions=array())
	{
		$oClient = Ext_Thebing_Client::getInstance();

		$aAgencies = Ext_Thebing_Client::getFirstClient()->getAgencies(true);
		$aAgencyCategories	= $oClient->getAgenciesCategoriesList();
		$aAgencyCountries	= $oClient->getAgenciesCountriesList();
		$aNationalities			= Ext_Thebing_Nationality::getNationalities(true, \System::getInterfaceLanguage(), 0);

		$aAgencyGroups		= Ext_Thebing_Agency::getGroupList(true);
		unset($aAgencyGroups[0]);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aSchools = $oClient->getSchools(true);

		$aSchools = Ext_Thebing_Access_User::clearSchoolsListByAccessRight($aSchools);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDiv						= $oDialog->createRow(L10N::t('Schulen', $oGui->gui_description), 'select', array('db_column' => 'schools', 'multiple' => 10, 'style' => 'height:130px;', 'select_options' => $aSchools, 'jquery_multiple' => 1, 'searchable' => 1));
		$oTabData->setElement($oDiv);
		$oDiv						= $oDialog->createRow(L10N::t('Agenturkunden', $oGui->gui_description), 'checkbox', array('db_column' => 'agency'));
		$oTabData->setElement($oDiv);
		$oDiv						= $oDialog->createRow(L10N::t('Filtern nach', $oGui->gui_description), 'select', array('db_column' => 'group_by', 'select_options' => Ext_Thebing_Management_Statistic::getGroups()));
		$oTabData->setElement($oDiv);
		$oDiv						= $oDialog->createRow(L10N::t('Agenturen', $oGui->gui_description), 'select', array('db_column' => 'agencies', 'multiple' => 10, 'style' => 'height:130px;', 'select_options' => $aAgencies, 'jquery_multiple' => 1, 'searchable' => 1));
		$oTabData->setElement($oDiv);
		$oDiv						= $oDialog->createRow(L10N::t('Agenturgruppen', $oGui->gui_description), 'select', array('db_column' => 'agency_groups', 'multiple' => 10, 'style' => 'height:130px;', 'select_options' => $aAgencyGroups, 'jquery_multiple' => 1, 'searchable' => 1));
		$oTabData->setElement($oDiv);
		$oDiv						= $oDialog->createRow(L10N::t('Agenturkategorien', $oGui->gui_description), 'select', array('db_column' => 'agency_categories', 'multiple' => 10, 'style' => 'height:130px;', 'select_options' => $aAgencyCategories, 'jquery_multiple' => 1, 'searchable' => 1));
		$oTabData->setElement($oDiv);
		$oDiv						= $oDialog->createRow(L10N::t('Agenturländer', $oGui->gui_description), 'select', array('db_column' => 'agency_countries', 'multiple' => 10, 'style' => 'height:130px;', 'select_options' => $aAgencyCountries, 'jquery_multiple' => 1, 'searchable' => 1));
		$oTabData->setElement($oDiv);
		$oDiv						= $oDialog->createRow(L10N::t('Direktkunden', $oGui->gui_description), 'checkbox', array('db_column' => 'direct_customer'));
		$oTabData->setElement($oDiv);
		$oDiv						= $oDialog->createRow(L10N::t('Nationalitäten', $oGui->gui_description), 'select', array('db_column' => 'nationalities', 'multiple' => 10, 'style' => 'height:130px;', 'select_options' => $aNationalities, 'jquery_multiple' => 1, 'searchable' => 1));
		$oTabData->setElement($oDiv);

			$oTabData->setElement($oDialog->createRow(L10N::t('Kunden in Liste anzeigen (Detaildarstellung)', $oGui->gui_description), 'select', array(
				'db_column' => 'customer_invoice_filter',
				'select_options' => array(
					'all' => $oGui->t('Schüler ohne Rechnung / Proforma anzeigen'),
					'proforma' => $oGui->t('Schüler mit Proforma anzeigen'),
					'invoice' => $oGui->t('Schüler mit Rechnung anzeigen')
				),
				'row_class' => 'not_enquiry'
			)));

			$oTabData->setElement($oDialog->createRow(L10N::t('Buchungstyp', $oGui->gui_description), 'select', array(
				'db_column' => 'inquiry_group_filter',
				'select_options' => array(
					'all' => $oGui->t('Alle'),
					'individual' => $oGui->t('Nur Individualbuchungen'),
					'group' => $oGui->t('Nur Gruppenbuchungen')
				),
				'row_class' => 'not_enquiry'
			)));

			// Felder, die nur im ausklappbaren Filterbereich auftauchen
			if(empty($aOptions['hide_expandable_filter_only_fields'])) {

			// Nur im Tool
			if(empty($aOptions['static_statistic'])) {
				$aStudentStatus = Ext_Thebing_Marketing_Studentstatus::getList(true);
				$oTabData->setElement($oDialog->createRow($oGui->t('Status d. Schülers'), 'select', array(
					'db_column' => 'inquiry_student_status_filter',
					'select_options' => Util::addEmptyItem($aStudentStatus, '', 'xNullx'), // Darf kein nummerisches Array sein
					'skip_value_handling' => true
				)));

				// Kurskategorien sind pro Schule, aber hier ist alles global
				$aCourseCategories = [];
				foreach(array_keys($aSchools) as $iSchoolId) {
					$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
					$aSchoolCategories = $oSchool->getCourseCategoriesList('select');
					foreach($aSchoolCategories as $iId => $sName) {
						$aCourseCategories[$iId] = $oSchool->short.' – '.$sName;
					}
				}

				$oTabData->setElement($oDialog->createRow($oGui->t('Kurskategorie'), 'select', array(
					'db_column' => 'inquiry_course_category_id',
					'select_options' => Util::addEmptyItem($aCourseCategories, '', 'xNullx'), // Darf kein nummerisches Array sein
					'skip_value_handling' => true
				)));

				// Nur bei Leistungszeitraum
				if(
					$aOptions['statistic'] &&
					$aOptions['statistic']->period == 3
				) {
					$oTabData->setElement($oDialog->createMultiRow($oGui->t('Erstellungsdatum'), array(
						'grid' => true,
						'items' => array(
							array(
								'db_column' => 'created_from',
								'input' => 'calendar',
								'format' => new Ext_Thebing_Gui2_Format_Date(),
								//'text_before' => $oGui->t('Von'),
								'placeholder' => $oGui->t('Von'),
								//'text_after' => ''.$oGui->t('bis'),
								//'text_after_spaces' => true,
								//'calendar_row_style' => 'display: inline-block;',
								'id' => 'created_from',
								'id_calendar' => 'created_from_calendar',
								'hide_weekday' => true
							),
							array(
								'db_column' => 'created_until',
								'input' => 'calendar',
								//'text_before' => $oGui->t('bis'),
								'placeholder' => $oGui->t('bis'),
								//'calendar_row_style' => 'display: inline-block;',
								'id' => 'created_until',
								'id_calendar' => 'created_until_calendar',
								'hide_weekday' => true
							)
						)
					)));
				}
			}

			$oTabData->setElement($oDialog->createMultiRow($oGui->t('Allgemeines Startdatum'), array(
				'row_class' => 'not_enquiry',
				'grid' => true,
				'items' => array(
					array(
						'db_column' => 'service_from_start',
						'input' => 'calendar',
						'format' => new Ext_Thebing_Gui2_Format_Date(),
						//'text_before' => $oGui->t('Von'),
						'placeholder' => $oGui->t('Von'),
						//'text_after' => ''.$oGui->t('bis'),
						//'text_after_spaces' => true,
						//'calendar_row_style' => 'display: inline-block;',
						'id' => 'service_from_start',
						'id_calendar' => 'service_from_start_calendar',
						'hide_weekday' => true,
						'skip_value_handling' => true
					),
					array(
						'db_column' => 'service_from_end',
						'input' => 'calendar',
						'placeholder' => $oGui->t('bis'),
						//'text_before' => $oGui->t('bis'),
						//'calendar_row_style' => 'display: inline-block;',
						'id' => 'service_from_end',
						'id_calendar' => 'service_from_end_calendar',
						'hide_weekday' => true,
						'skip_value_handling' => true
					)
				)
			)));

			$oTabData->setElement($oDialog->createMultiRow($oGui->t('Startdatum Kurs'), array(
				'row_class' => 'not_enquiry',
				'grid' => true,
				'items' => array(
					array(
						'db_column' => 'course_from_start',
						'input' => 'calendar',
						'format' => new Ext_Thebing_Gui2_Format_Date(),
						'placeholder' => $oGui->t('Von'),
						'id' => 'course_from_start',
						'id_calendar' => 'course_from_start_calendar',
						'hide_weekday' => true,
						'skip_value_handling' => true
					),
					array(
						'db_column' => 'course_from_end',
						'input' => 'calendar',
						'placeholder' => $oGui->t('bis'),
						//'text_before' => $oGui->t('bis'),
						//'calendar_row_style' => 'display: inline-block;',
						'id' => 'course_from_end',
						'id_calendar' => 'course_from_end_calendar',
						'hide_weekday' => true,
						'skip_value_handling' => true
					)
				)
			)));
		}

		if(empty($aOptions['hide_currency_field'])) {
			$oDiv = $oDialog->createRow(L10N::t('Währung', $oGui->gui_description), 'select', array('db_column' => 'currency_id', 'required' => 1, 'select_options' => Ext_Thebing_Management_Statistic::getCurrencies()));
			$oTabData->setElement($oDiv);
		}
	}


	/**
	 * Get all columns
	 * 
	 * @return array
	 */
	public static function getColumns()
	{
		$sSQL = "
			SELECT
				`id`, `title`
			FROM
				`kolumbus_statistic_cols_definitions`
			WHERE
				`active` = 1
		";
		$aAllCols = DB::getQueryPairs($sSQL);

		$aAllCols = self::_translateAndSortArray($aAllCols);

		return $aAllCols;
	}


	/**
	 * Get column groups
	 * 
	 * @param bool $bIntern
	 * @return array
	 */
	public static function getColumnGroups($bIntern = true)
	{
		if($bIntern)
		{
			$sSelect = "`id`, `title`";
		}
		else
		{
			$sSelect = "*";
		}

		$sSQL = "
			SELECT
				" . $sSelect . "
			FROM
				`kolumbus_statistic_cols_groups`
			WHERE
				`active` = 1
		";

		if($bIntern)
		{
			$aColGroups = DB::getQueryPairs($sSQL);

			$aColGroups = self::_translateAndSortArray($aColGroups, true, Ext_Thebing_L10N::getEmptySelectLabel('all_categories'));
		}
		else
		{
			$aColGroups = DB::getQueryData($sSQL);

			$aTemp = self::_translateAndSortArray($aColGroups);

			$aColGroups = array();

			foreach((array)$aTemp as $aGroup)
			{
				$aColGroups[$aGroup['id']] = $aGroup;
			}
		}

		return $aColGroups;
	}


	/**
	 * Get columns colors
	 * 
	 * @return array
	 */
	public static function getColumnColorsByID()
	{
		$sSQL = "
			SELECT
				`kscd`.`id`,
				`kscg`.`color_dark`,
				`kscg`.`color_light`
			FROM
				`kolumbus_statistic_cols_definitions` AS `kscd` INNER JOIN
				`kolumbus_statistic_cols_groups` AS `kscg` ON
					`kscd`.`group_id` = `kscg`.`id`
			WHERE
				`kscd`.`active` = 1
		";
		$aResults = DB::getQueryData($sSQL);

		$aColors = array();

		foreach((array)$aResults as $aResult)
		{
			$aColors[$aResult['id']] = $aResult;
		}

		return $aColors;
	}


	/**
	 * Get periods access
	 * 
	 * @return array
	 */
	public static function getPeriodsAccess()
	{
		$aPeriodsAccess = array(
			1 => array(1 => true, 2 => true),
			2 => array(1 => true),
			3 => array(1 => true, 2 => true, 3 => true, 4 => true, 5 => true, 6 => true),
			4 => array(1 => true, 2 => true),
			5 => array(7 => true)
		);

		return $aPeriodsAccess;
	}


	/**
	 * Get matrix data
	 */
	protected function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional=false)
	{
		$oMatrix = new Ext_Thebing_Access_Matrix_Statistics;

		$aMatrix = $oMatrix->aMatrix;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oDialog = $this->_oGui->createDialog($this->_oGui->t('Zugriffsrechte'), $this->_oGui->t('Zugriffsrechte'));

		$aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);
		$aData['bSaveButton'] = 1;
		$aData['aMatrixData'] = $aMatrix;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$aData['aMatrixCellColors'] = array(
			'red'	=> Ext_Thebing_Util::getColor('red'),
			'green'	=> Ext_Thebing_Util::getColor('green')
		);

		$aData['html'] = $oMatrix->generateHTML($this->_oGui->gui_description);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $aData;
	}


	/**
	 * See parent
	 */
	protected function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false) {

		$aData = parent::getEditDialogData($aSelectedIds, $aSaveData, $sAdditional);

		return $aData;
	}


	/**
	 * See parent
	 */
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true)
	{
		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		if($sIconAction == 'new' || $sIconAction == 'edit')
		{
			$aData['tabs'][1]['html'] = $this->_writeColsTabHTML($aData, $aSelectedIds);
		}

		return $aData;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true) {

		if($sAction == 'openAccessDialog') {

			$oMatrix = new Ext_Thebing_Access_Matrix_Statistics();
			$oMatrix->saveAccessData($aData['access']);

			$aTransfer = [
				'task' => 'openDialog',
				'action' => 'showSuccess',
				'data' => [
					'action' => ''
				]
			];

		} else {
			$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional);
		}

		return $aTransfer;
	}

	/* ==================================================================================================== */

	/**
	 * Write the HTML code for the cols dialog tab
	 * 
	 * @param array &$aData
	 * @param array $aSelectedIds
	 * @return string
	 */
	protected function _writeColsTabHTML(&$aData, $aSelectedIds)
	{
		if(!is_array($aSelectedIds))
		{
			$aSelectedIds = array();
		}

		$iStatisticID = (int)reset($aSelectedIds);

		$oStatistic = new Ext_Thebing_Management_Statistic($iStatisticID);

		$aData['column_count'] = $oStatistic->column_count;

		$aData['cols_tab_saved_cols'] = $oStatistic->columns;

		$aData['cols_tab_periods_access'] = self::getPeriodsAccess();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get column groups

		$aColGroups = self::getColumnGroups();

		$sSQL = "
			SELECT
				`id`, `right`
			FROM
				`kolumbus_statistic_cols_groups`
			WHERE
				`active` = 1
		";
		$aColGroupRights = DB::getQueryPairs($sSQL);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get column group items

		$aGroupItems = array();

		foreach((array)$aColGroups as $iGroupID => $sGroup) {
			if($iGroupID == 0) {
				continue;
			}

			if(!Ext_Thebing_Access::hasRight($aColGroupRights[$iGroupID])) {
				unset($aColGroups[$iGroupID]);
				continue;
			}

			$sSQL = "
				SELECT
					`kscd`.*,
					IF(`kscd`.`group_by` = 1, `kscg`.`color_dark`, `kscg`.`color_light`) AS `color`,
					`kscg`.`color_dark`,
					`kscg`.`color_light`
				FROM
					`kolumbus_statistic_cols_definitions` AS `kscd` INNER JOIN
					`kolumbus_statistic_cols_groups` AS `kscg` ON
						`kscd`.`group_id` = `kscg`.`id`
				WHERE
					`kscd`.`active` = 1 AND
					`kscd`.`group_id` = :iGroupID
			";
			$aSQL = array('iGroupID' => $iGroupID);
			$aGroupItems[$iGroupID] = DB::getPreparedQueryData($sSQL, $aSQL);

			$aGroupItems[$iGroupID] = self::_translateAndSortArray($aGroupItems[$iGroupID]);

			$aData['cols_tab_data'] = $aGroupItems;
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get relations access data

		$sSQL = "
			SELECT
				`x_id`,
				GROUP_CONCAT(`y_id` SEPARATOR '_')
			FROM
				`kolumbus_statistic_cols_definitions_access`
			WHERE
				`type` = 2
			GROUP BY
				`x_id`
		";
		$aTemp = DB::getQueryPairs($sSQL);

		$aRelations = array();

		foreach((array)$aTemp as $iX => $sY)
		{
			$aYs = explode('_', $sY);

			foreach((array)$aYs as $iY)
			{
				$aRelations[$iX][$iY] = true;
			}
		}

		$aData['cols_tab_relations_details'] = $aRelations;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$sSQL = "
			SELECT
				`x_id` AS `x`,
				`y_id` AS `y`
			FROM
				`kolumbus_statistic_cols_definitions_access`
			WHERE
				`type` = 1
		";
		$aTemp = DB::getQueryData($sSQL);

		$aRelations = array();

		foreach((array)$aTemp as $i => $aSub)
		{
			$aRelations[$aSub['x']][$aSub['y']] = true;
			$aRelations[$aSub['y']][$aSub['x']] = true;
		}

		$aData['cols_tab_relations_sums'] = $aRelations;

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get all columns

		$aAllCols = self::getColumns();

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Define translations

		$aTranslations = array(
			'remove'	=> L10N::t('entfernen', $this->_oGui->gui_description),
			'all'		=> L10N::t('alle', $this->_oGui->gui_description),
			'grouping'	=> L10N::t('Gruppierung', $this->_oGui->gui_description),
			'columns'	=> L10N::t('Spalten', $this->_oGui->gui_description),
			'search'	=> L10N::t('Suche', $this->_oGui->gui_description),
			'max'		=> L10N::t('max.', $this->_oGui->gui_description),
			'max_value'	=> array(
				1 => L10N::t('Gesamtumsatz', $this->_oGui->gui_description),
				2 => L10N::t('Nettoumsatz', $this->_oGui->gui_description),
				3 => L10N::t('Anzahl Buchungen', $this->_oGui->gui_description),
				4 => L10N::t('Anzahl Kurswochen', $this->_oGui->gui_description),
				5 => L10N::t('Anzahl Anfragen', $this->_oGui->gui_description)
			)
		);

		$aData['cols_tab_messages'] = array(
			'invalid_item'	=> L10N::t('Die Gruppierung ist nur mit gruppierungsfähigen Feldern (dunkelfarbige) möglich', $this->_oGui->gui_description),
			'double_item'	=> L10N::t('Es darf nur ein Eintrag pro Spalte-Box gewählt werden', $this->_oGui->gui_description),
			'reset_columns_start'	=>
				L10N::t('Die folgenden Spalten sind von Ihnen gewünschten Format nicht verfügbar:', $this->_oGui->gui_description),
			'reset_columns_end'		=>
				L10N::t('Um das automatische Löschen dieser Spalten aus der Statistik zu bestätigen, drücken Sie bitte ok. Durch Abbrechen werden die Änderungen nicht gespeichert.', $this->_oGui->gui_description)
		);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oSmarty = new SmartyWrapper();

		$oSmarty->assign('iStatisticID', $iStatisticID);
		$oSmarty->assign('aSavedCols', $oStatistic->columns);
		$oSmarty->assign('iColumnCount', $oStatistic->column_count);
		$oSmarty->assign('aColGroups', $aColGroups);
		$oSmarty->assign('aGroupItems', $aGroupItems);
		$oSmarty->assign('sIconPath', Ext_Thebing_Util::getIcon('delete'));
		$oSmarty->assign('aTranslations', $aTranslations);

		$sCode = $oSmarty->fetch(Ext_Thebing_Management_PageBlock::getTemplatePath() . 'tab_cols.tpl');

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $sCode;
	}


	/**
	 * Translate and sort columns
	 * 
	 * @param array $aArray
	 * @param bool $bWithEmpty
	 * @return array
	 */
	static protected function _translateAndSortArray($aArray, $bWithEmpty = false, $sLabel = '', $mEmptyValue = 0) {

		$bArray = false;

		//Sicherstellen das es ein Array ist damit sort/usort keine warnings werfen wenn die statistik noch ganz leer ist
		$aArray = (array)$aArray;

		foreach((array)$aArray as $iKey => $mValue) {
			if(is_array($mValue)){
				$bArray = true;
				$aArray[$iKey]['title'] = L10N::t($mValue['title'], self::$sDescription);
			} else {
				$aArray[$iKey] = L10N::t($mValue, self::$sDescription);
			}
		}

		if(!$bArray) {
			asort($aArray);
		} else {
			uasort($aArray, array('Ext_Thebing_Management_Statistic_Gui2', 'sortByTitle'));
		}

		if(!$bArray && $bWithEmpty) {
			$aArray = Ext_Thebing_Util::addEmptyItem($aArray, $sLabel, $mEmptyValue);
		}

		return $aArray;
	}

	public static function getInterfaceTranslations()
    {
		return array(
			'do_not_use' => L10N::t('Bitte bestätigen Sie, dass Sie sich bewusst sind, dass das alte Reporting nicht länger korrekte Werte liefert.', Ext_Thebing_Management_Statistic::$_sDescription),
			'choose_statistic' => L10N::t('Statistik wählen', Ext_Thebing_Management_Statistic::$_sDescription),
			'date' => L10N::t('Datum', Ext_Thebing_Management_Statistic::$_sDescription),
			'refresh' => L10N::t('Aktualisieren', Ext_Thebing_Management_Statistic::$_sDescription),
			'from' => L10N::t('Von', Ext_Thebing_Management_Statistic::$_sDescription),
			'until' => L10N::t('bis', Ext_Thebing_Management_Statistic::$_sDescription),
			'info' => L10N::t('Hinweise', Ext_Thebing_Management_Statistic::$_sDescription),
			'infos' => array(
				L10N::t('Einmalige Kurs- oder Unterkunftsgebühren beziehen sich nur auf den ersten Eintrag.', Ext_Thebing_Management_Statistic::$_sDescription),
				L10N::t('Kostenbeträge werden komplett für den ersten Tag der jeweiligen Periode genommen.', Ext_Thebing_Management_Statistic::$_sDescription),
				L10N::t('Es werden keine Summen von "Anzahl"-Spalten unter "Leistungszeitraum" gebildet.', Ext_Thebing_Management_Statistic::$_sDescription),
				L10N::t('Bei der Berechnung auf Basis des Leistungszeitraume kann es zu Rundungsfehlern im Centbereich kommen.', Ext_Thebing_Management_Statistic::$_sDescription),
				L10N::t('Bei der Berechnung auf Basis des Leistungszeitraums werden die Kurs - und Unterkunftsumsätze jeweils tagesgenau anhand der Kurs- bzw. Unterkunftsdaten ausgerechnet und verteilt. Dies bedeutet, dass falls ein Kunde einen 4-wöchigen Sprachkurs von montags bis freitags bucht, rechnet das System in den ersten 3 Wochen jeweils mit 7 Tagen und in der letzten Woche mit 5 Tagen. Eine Berechnung auf Basis der tatsächlichen Klassenplanung findet nicht statt.', Ext_Thebing_Management_Statistic::$_sDescription)
			)
		);
	}

	public static function getDialog(Ext_Thebing_Gui2 $oGui)
    {
		$oDialog = $oGui->createDialog($oGui->t('Statistik "{title}"'), $oGui->t('Neue Statistik'));

		$oTabData = $oDialog->createTab($oGui->t('Daten'));

		$oDiv = $oDialog->createRow('', 'hidden', array('db_column' => 'intervals'));
		$oDialog->setElement($oDiv);
		$oDiv= $oDialog->createRow('', 'hidden', array('db_column' => 'columns'));
		$oDialog->setElement($oDiv);

		$oDiv = $oDialog->createRow(
				$oGui->t('Titel'), 'input', array('db_column' => 'title', 'required' => 1));
		$oTabData->setElement($oDiv);
		$oDiv = $oDialog->createRow(
				$oGui->t('Art'), 'select', array('db_column' => 'list_type', 'required' => 1, 'select_options' => Ext_Thebing_Management_Statistic::getListTypes()));
		$oTabData->setElement($oDiv);
		$oDiv = $oDialog->createRow(
				$oGui->t('Statistik-Typ'), 'select', array('db_column' => 'type', 'required' => 1, 'select_options' => Ext_Thebing_Management_Statistic::getTypes()));
		$oTabData->setElement($oDiv);
		$oDiv = $oDialog->createRow(
				$oGui->t('Zeitrahmen'), 'select', array('db_column' => 'interval', 'required' => 1, 'select_options' => Ext_Thebing_Management_Statistic::getIntervals()));
		$oTabData->setElement($oDiv);
		$oDiv = $oDialog->createRow(
				$oGui->t('Ausgehend von'), 'select', array('db_column' => 'start_with', 'required' => 1, 'select_options' => Ext_Thebing_Management_Statistic::getStartWiths()));
		$oTabData->setElement($oDiv);
		$oDiv = $oDialog->createRow(
				$oGui->t('Basierend auf'), 'select', array('db_column' => 'period', 'required' => 1, 'select_options' => Ext_Thebing_Management_Statistic::getPeriods()));
		$oTabData->setElement($oDiv);

		/*
		$oDiv						= $oDialog->createRow(L10N::t('Startseite', $oGui->gui_description), 'checkbox', array('db_column' => 'start_page'));
		$oTabData->setElement($oDiv);
		*/

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Filter'));
		$oTabData->setElement($oH3);

		Ext_Thebing_Management_Statistic_Gui2::addAdditionalFieldsByRef($oGui, $oDialog, $oTabData, array('hide_expandable_filter_only_fields' => true));

		$oDialog->setElement($oTabData);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oTabData = $oDialog->createTab($oGui->t('Spalten'));

		$oDialog->setElement($oTabData);

		return $oDialog;
	}

}
