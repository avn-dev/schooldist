<?php

use TsTuition\Model\Report\Field;
use TsTuition\Model\Report\Fields;

class Ext_Thebing_Tuition_Report_Gui2 extends Ext_Thebing_Gui2_Data
{
	// Default GUI description
	static $_sDescription = 'Thebing » Tuition » Own overview';

	static $aCache = [];
	
	/* ==================================================================================================== */

	/**
	 * Get template path
	 * 
	 * @return string
	 */
	static public function getOrderby() {
		
		return['title' => 'ASC'];
	}

	static public function getDialog(Ext_Thebing_Gui2 $oGui){
		
		$oDialog = $oGui->createDialog($oGui->t('Übersicht "{title}"'),
										$oGui->t('Neue Übersicht'));

		return $oDialog;
	}

	public static function getTemplatePath() {
		
		$oBundleHelper = new \Core\Helper\Bundle();
		$sBundleDir = $oBundleHelper->getBundleDirectory('TsTuition');
		
		return $sBundleDir.'/Resources/views/report/';
	}

    protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

	    if(!$this->oWDBasic) {
	        $this->getWDBasicObject($aSelectedIds);
        }


        $aLayouts = Ext_Thebing_Pdf_Template::getAvailableTemplateTypes(true);

        $aLayouts = Ext_Thebing_Util::addEmptyItem($aLayouts);

        $oStartWith = new Ext_Thebing_Gui2_Selection_Tuition_Report($this->_oGui->gui_description);

        $oDialogData->aElements = [];

        $oTabData					= $oDialogData->createTab(L10N::t('Daten', $this->_oGui->gui_description));

        $oDiv						= $oDialogData->createRow('', 'hidden', array('db_column' => 'columns'));
        $oDialogData->setElement($oDiv);

        $oDiv						= $oDialogData->createRow(L10N::t('Name', $this->_oGui->gui_description), 'input', array('db_column' => 'title', 'required' => 1));
        $oTabData->setElement($oDiv);
		
        $oDiv = $oDialogData->createRow(L10N::t('Gruppierung', $this->_oGui->gui_description), 'select', array('db_column' => 'group_by', 'select_options' => Ext_Thebing_Tuition_Report_Gui2::getGroups(), 'required' => 1));
        $oTabData->setElement($oDiv);
		
        $oDiv = $oDialogData->createRow(L10N::t('Untergruppierung', $this->_oGui->gui_description), 'select', array('db_column' => 'sub_group', 'select_options' => [''=>'', 'days' => $this->t('Tage')]));
        $oTabData->setElement($oDiv);
		
        $oDiv						= $oDialogData->createRow(L10N::t('Ausgehend von', $this->_oGui->gui_description), 'select', array('db_column' => 'start_with', 'selection' => $oStartWith, 'dependency' => array(array('db_column' => 'group_by')), 'required' => 1));
        $oTabData->setElement($oDiv);
		$oDiv = $oDialogData->createRow(
			L10N::t('Eine Zeile pro Lektion', $this->_oGui->gui_description),
			'checkbox',
			[
				'db_column' => 'per_lesson',
				'dependency_visibility' => [
					'db_column' => 'start_with',
					'on_values' => [5]
				]
			]
		);
		$oTabData->setElement($oDiv);

        $oDiv						= $oDialogData->createRow(L10N::t('Aufteilung', $this->_oGui->gui_description), 'select', array('db_column' => 'break', 'select_options' => Ext_Thebing_Tuition_Report_Gui2::getBreaks(), 'required' => 1));
        $oTabData->setElement($oDiv);
        $oDiv						= $oDialogData->createRow(L10N::t('Layout', $this->_oGui->gui_description), 'select', array('db_column' => 'layout', 'select_options' => $aLayouts, 'required' => 1));
        $oTabData->setElement($oDiv);

        $aSchools	= Ext_Thebing_Client::getSchoolList(true);

        $oTabData->setElement(
            $oDialogData->createRow(
                L10N::t('Schulen',  $this->_oGui->gui_description),
                'select',
                array(
                    'db_alias'=>'ku',
                    'db_column'=>'schools',
                    'select_options'=>$aSchools,
                    'multiple'=> 5,
                    'jquery_multiple'=> 1,
                    'row_id' => 'uploads_schools',
                    'required' => true,
                    'events' => array(
                        array(
                            'event' 	=> 'change',
                            'function' 	=> 'reloadDialogTab',
                            'parameter'	=> 'aDialogData.id, 0'
                        )
                    )
                )
            )
        );

        foreach($this->oWDBasic->getSchoolIds() as $iSchoolId) {
            $oSubSchool = \Ext_Thebing_School::getInstance($iSchoolId);

            $aTempPDFs = $oSubSchool->getSchoolFiles(1);

            $aPDFs = array();

            foreach((array)$aTempPDFs as $iKey => $aValue) {
                $aPDFs[$iKey] = $aValue['object']->description;
            }

            $aPDFs = Ext_Thebing_Util::addEmptyItem($aPDFs);

            $oDiv = $oDialogData->createRow(sprintf("%s (%s)", L10N::t('Hintergrund-PDF', $this->_oGui->gui_description), $oSubSchool->getName()), 'select', array(
                'db_column' => 'background_pdf_'.$iSchoolId,
                'select_options' => $aPDFs,
                'required' => 1
            ));
            $oTabData->setElement($oDiv);
        }

        $oDiv = $oDialogData->createRow(L10N::t('Kopfbereich', $this->_oGui->gui_description), 'html', array('db_column' => 'header', 'advanced' => true));
        $oTabData->setElement($oDiv);
		
        #$oDiv = $oDialogData->createRow(L10N::t('Untergruppierung', $this->_oGui->gui_description), 'html', array('db_column' => 'subheading', 'advanced' => true));
        #$oTabData->setElement($oDiv);
		
        $oDiv = $oDialogData->createRow(L10N::t('Fußbereich', $this->_oGui->gui_description), 'html', array('db_column' => 'footer', 'advanced' => true));
        $oTabData->setElement($oDiv);
        $oDiv = $oDialogData->createRow(L10N::t('Mindestanzahl Zeilen in Tabelle', $this->_oGui->gui_description), 'input', array('db_column' => 'min_rows'));
        $oTabData->setElement($oDiv);
        $oDiv = $oDialogData->createRow(L10N::t('Rahmen', $this->_oGui->gui_description), 'input', array('db_column' => 'border_style'));
        $oTabData->setElement($oDiv);
        $oDiv						= $oDialogData->createSaveField('hidden', array('db_column' => 'ignore_errors'));
        $oTabData->setElement($oDiv);
        $oDialogData->setElement($oTabData);

        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

        $oTabData					= $oDialogData->createTab(L10N::t('Spalten', $this->_oGui->gui_description));
        $oTabData->aOptions['task'] = 'columns';
        $oDialogData->setElement($oTabData);

        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

        $oTabData					= $oDialogData->createTab(L10N::t('Platzhalter', $this->_oGui->gui_description));
        $oTabData->aOptions['task'] = 'placeholder';
        $oDialogData->setElement($oTabData);

        $oDialogData->save_as_new_button	= true;

        return parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);
    }

    /**
	 * See parent
	 */
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true)
	{
		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		if($sIconAction == 'new' || $sIconAction == 'edit')
		{
			foreach((array)$aData['tabs'] as $iTab => $aTab) {
				if($aTab['options']['task'] == 'columns') {
					$aData['tabs'][$iTab]['html'] = $this->_writeColsTabHTML($aData, $aSelectedIds);
				} elseif($aTab['options']['task'] == 'placeholder') {
					$oTuition = new Ext_Thebing_Tuition_Placeholder();
					$aData['tabs'][$iTab]['html'] = $oTuition->displayPlaceholderTable();
				}
			}
		}

		return $aData;
	}

	/* ==================================================================================================== */

	/**
	 * Get breaks
	 *
	 * @param bool $bWithEmpty
	 * @return array
	 */
	public static function getBreaks($bWithEmpty = true)
	{
		$aGroups = array(
			1 => L10N::t('Pro Gruppierung ein Blatt', self::$_sDescription),
			2 => L10N::t('fortlaufend', self::$_sDescription)
		);

		if($bWithEmpty)
		{
			return Ext_Thebing_Util::addEmptyItem($aGroups);
		}

		return $aGroups;
	}


	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true) {

		$oReport = $this->_getWDBasicObject($aSelectedIds);

		// Sortierung der Tabelle speichern (Radio-Checkbox bei Auswahl der Spalten)
		$oReport->order_by_column = (int)$aData['order_by_column'];

		$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);

		foreach((array)$aTransfer['error'] as $iKey => $aError){
			if(
				is_array($aError) &&
				isset($aError['input']['dbcolumn']) &&
				$aError['input']['dbcolumn'] == 'total_width'
			){
				$aTransfer['error'][$iKey]['type'] = 'hint';
				array_shift($aTransfer['error']);
				break;
			}
		}

		return $aTransfer;
	}

	/**
	 * Get columns data
	 *
	 * @return array
	 */
	public static function getColumnsData($bWithLocationCheck = true) {
		global $user_data;

		$aData = array(
			1 => array(
				'group'		=> L10N::t('Kundendaten', self::$_sDescription),
				'color'		=> 'CCFFAA',
				'fields'	=> array(
					1	=> Field::getInstance('Name')->setSettings([
							'surname' => L10N::t('Nachname', self::$_sDescription),
							'full' => L10N::t('Vollständiger Name', self::$_sDescription),
						]
					),
					2	=> Field::getInstance('Vorname'),
					3	=> Field::getInstance('Kundennummer'),
					4	=> Field::getInstance('Alter'),
					61 	=> Field::getInstance('Geburtsdatum'),
					5	=> Field::getInstance('Muttersprache'),
					6	=> Field::getInstance('Nationalität')->setSettings([
     						'name' => L10N::t('Name', self::$_sDescription),
							'flag' => L10N::t('Flagge', self::$_sDescription),
						])->setPrepareField(function($value, $column) {

							// Wenn Flagge explizit ausgewählt wurde
							if ($column['setting'] === 'flag') {
								$flagPath = Util::getFlagIcon($value[2]);

								if ($flagPath !== false) {
									return '<img src="' . htmlspecialchars($flagPath) . '" alt="'.htmlspecialchars($value[1]).'" title="'.htmlspecialchars($value[1]).'" width="16" height="11">';
								}

								return $value[2];
							}

							// Wenn "Name"- oder nichts ausgewählt wurde (Default).
							// Name ausgeschrieben
							return $value[1];
						}),
					73	=> Field::getInstance('Adresse')->setSelectField("GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `tc_a`.`address` SEPARATOR '{_}')"),
					7	=> Field::getInstance('Schülerstatus'),
					8	=> Field::getInstance('Gebuchte Kurse'),
                    59	=> Field::getInstance('Gebuchte Kurse (Kurzform)'),
					//9	=> array('label' => L10N::t('Wochenanzahl pro gebuchten Kurs'), // Ersetzt durch 56 und 57
					11	=> Field::getInstance('Erstes Startdatum'),
					12	=> Field::getInstance('Letztes Enddatum'),
					14	=> Field::getInstance('Testscore'),
					15	=> Field::getInstance('Telefon'),
					16	=> Field::getInstance('E-Mail'),
					17	=> Field::getInstance('Agentur')->setSettings([
							'abbreviation' => L10N::t('Abkürzung', self::$_sDescription),
							'full' => L10N::t('Name', self::$_sDescription),
						]
						)->setSelectField([
							'abbreviation' => "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `ka`.`ext_2` SEPARATOR '{_}')",
							'full' => "GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `ka`.`ext_1` SEPARATOR '{_}')"
						]),
					18	=> Field::getInstance('Visum (Art)'),
					74	=> Field::getInstance('Visum Passnummer')->setSelectField("GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `ts_j_t_v_d`.`passport_number` SEPARATOR '{_}')"),
					//20 => array('label' => L10N::t('Wochenanzahl'), // Ersetzt durch 56 und 57
					21	=> Field::getInstance('Aktuelle Woche / Wochenanzahl der Buchung'),
					27	=> Field::getInstance('Status (Buchung)'),
					44	=> Field::getInstance('Verbleibende Stunden (Buchung)'),
					45	=> Field::getInstance('Zugewiesene Stunden (Buchung)'),
					46	=> Field::getInstance('Gebuchte Stunden (Buchung)'),
					#47	=> array('label' => L10N::t('Wochen gesamt'),
					48	=> Field::getInstance('Geschlecht'),
					50	=> Field::getInstance('Gruppe'),
					51	=> Field::getInstance('Gruppe (Kürzel)'),
					56	=> Field::getInstance('Kurswochen (absolut)'),
					57	=> Field::getInstance('Kurswochen (relativ)'),
					62 => Field::getInstance('Gesamtbetrag'),
					63 => Field::getInstance('Offener Betrag'),
					64 => Field::getInstance('Anwesenheit absolut'),
					65 => Field::getInstance('Anwesenheit absolut (erwartet)'),
					71 => Field::getInstance('Kommentar')->setSelectField("GROUP_CONCAT(DISTINCT `ki`.`id`, '{::}', `tc_c_d_comment`.`value` SEPARATOR '{_}')"),
					72 => Field::getInstance('Kursgebühren')->
						setPrepareField(function($value) {
							if(empty($value[1])) {
								return null;
							}
							return Ext_Thebing_Format::Number($value[1], $value[2]);
						})->
						setSelectField("
							GROUP_CONCAT(
								DISTINCT (
								SELECT 
									CONCAT_WS(
										'{::}', 
										`kic`.`id`,
										SUM(
											IF(
												`72_kidvi`.`amount_discount` > 0,
												(
													## Discount ausrechnen
													`72_kidvi`.`amount` -
													(
														`72_kidvi`.`amount` / 100 * `72_kidvi`.`amount_discount`
													)
												),
												`72_kidvi`.`amount`
											) *
											IF(
												`72_kidvi`.`tax` = 2,
												(
													(`72_kidvi`.`tax` / 100) + 1
												),
												1
											)
										),
										`ki`.`currency_id`
									)
								FROM 
									`kolumbus_inquiries_documents` `72_kid` JOIN
									`kolumbus_inquiries_documents_versions` `72_kidv` ON
										`72_kidv`.`id` = `72_kid`.`latest_version` LEFT JOIN
									`kolumbus_inquiries_documents_versions_items` `72_kidvi` ON
										`72_kidvi`.`version_id` = `72_kidv`.`id` AND
										`72_kidvi`.`active` = 1 AND
										`72_kidvi`.`onPdf` = 1 AND
										`72_kidvi`.`type` = 'course'
								WHERE
									`72_kid`.`entity_id` = `ki`.`id` AND
									`72_kid`.`entity` = '".Ext_TS_Inquiry::class."' AND
									`72_kid`.`type` IN ( :invoice_types ) AND
									`72_kid`.`active` = 1 AND
									`kic`.`id` = `72_kidvi`.`type_id`
								GROUP BY
									`kic`.`id`
								)
							 SEPARATOR '{_}')
							 ")->
						setQueryParameters(
							[
								'invoice_types' => \Ext_Thebing_Inquiry_Document_Search::getTypeData('invoice_without_proforma'),
							]
						),
					76	=> Field::getInstance('Schüler') // Travellers
				)
			),
			2 => array(
				'group'		=> L10N::t('Kursdaten der Zuordnung', self::$_sDescription),
				'color'		=> 'CCEEFF',
				'fields'	=> array(

					10	=> Field::getInstance('Aktuelle Woche / Wochenanzahl pro gebuchten Kurs'),
					13	=> Field::getInstance('Gebuchtes Level'),
					19	=> Field::getInstance('Kurs'),
                    58	=> Field::getInstance('Kurs (Kurzform)'),

					22	=> Field::getInstance('Startdatum'),
					23	=> Field::getInstance('Enddatum'),
					24	=> Field::getInstance('Aktuelle Score (der ausgewählten Woche)'),
					#2778 - Anwesenheit erstmal auskommentieren
					//25	=> array('label' => L10N::t('Anwesenheit'),
					26	=> Field::getInstance('Aktuelles Niveau vom Schüler'),
					52  => Field::getInstance('Status (Kurs)'),
					53	=> Field::getInstance('Verbleibende Stunden (Kurs)'),
					54	=> Field::getInstance('Zugewiesene Stunden (Kurs)'),
					55	=> Field::getInstance('Gebuchte Stunden (Kurs)'),
				)
			),
			3 => array(
				'group'		=> L10N::t('Ort', self::$_sDescription),
				'color'		=> 'FFCCAA',
				'fields'	=> array(
					28	=> Field::getInstance('Gebäude'),
					29	=> Field::getInstance('Etage'),
					30	=> Field::getInstance('Klassenzimmer')
				)
			),
			4 => array(
				'group'		=> L10N::t('Lehrer', self::$_sDescription),
				'color'		=> 'E0E0F8',
				'fields'	=> array(
					31	=> Field::getInstance('Vorname'),
					32	=> Field::getInstance('Name')->setSettings([
							'surname' => L10N::t('Nachname', self::$_sDescription),
							'full' => L10N::t('Vollständiger Name', self::$_sDescription),
						]
					),
					33	=> Field::getInstance('Name pro Block mit Ersatzlehrer')->setSettings([
							'firstname' => L10N::t('Vorname', self::$_sDescription),
							'surname' => L10N::t('Nachname', self::$_sDescription),
							'full' => L10N::t('Vollständiger Name', self::$_sDescription),
						]
					),
				)
			),
			5 => array(
				'group'		=> L10N::t('Sonstiges', self::$_sDescription),
				'color'		=> '66FFCC',
				'fields'	=> array(
					34	=> Field::getInstance('Wochentage (Mo/Di/Mi/...)')->setSettings([
							'1' => L10N::t('keine Unterteilung', self::$_sDescription),
							'2' => L10N::t('Eine Unterteilung', self::$_sDescription),
							'3' => L10N::t('Zwei Unterteilungen', self::$_sDescription),
							'4' => L10N::t('Drei Unterteilungen', self::$_sDescription),
							'5' => L10N::t('Vier Unterteilungen', self::$_sDescription),
						]
					),
					35	=> Field::getInstance('Uhrzeit (von hh:mm bis hh:mm)'),
					36	=> Field::getInstance('Klassenname'),
					37	=> Field::getInstance('Anzahl der Schüler'),
					38	=> Field::getInstance('Name der Schule'),
					39	=> Field::getInstance('Unterichtstage')->setSettings([
							'abbreviation' => L10N::t('Abgekürzt', self::$_sDescription),
							'full' => L10N::t('Ausgeschrieben', self::$_sDescription),
						]
					),
					40	=> Field::getInstance('Niveau der Klasse'),
					42	=> Field::getInstance('Zeilenzähler'),
					43	=> Field::getInstance('MAX-Zähler'),
					60 => Field::getInstance('Inhalt (Block)'),
					66 => Field::getInstance('Anzahl Tage')->setSelectField("COUNT(DISTINCT CONCAT(`ktbd`.`block_id`, '{::}', CONCAT(`ktbd`.`day`)))"),
					67 => Field::getInstance('Blocknummer')->
						setSelectField("`ktcl`.`id`")->
						setPrepareField(function($value) {
							$classId = reset($value);
							if(!isset(Ext_Thebing_Tuition_Report_Gui2::$aCache['67_class_block_counter'])) {
								Ext_Thebing_Tuition_Report_Gui2::$aCache['67_class_block_counter'] = [];
							}
							if(!isset(Ext_Thebing_Tuition_Report_Gui2::$aCache['67_class_block_counter'][$classId])) {
								Ext_Thebing_Tuition_Report_Gui2::$aCache['67_class_block_counter'][$classId] = 1;
							}
							return Ext_Thebing_Tuition_Report_Gui2::$aCache['67_class_block_counter'][$classId]++;
						}),
					68 => Fields\Attendance::getInstance('Vollständig anwesende Schüler')->setFieldId(68),
					69 => Fields\Attendance::getInstance('Abwesende Schüler')->setFieldId(69),
					70 => Fields\Attendance::getInstance('Teilweise anwesende Schüler')->setFieldId(70),
					75 => Field::getInstance('Datum des Tages'),
				)
			)
		);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Add flex fields

		$aSections = array(
			4, // Kundendaten » Kurs
			38, // Klassenplanung » Anwesenheit
			46 // Kundendaten » Kursbuchung (pro Kurs)
		);

		$sSQL = "
			SELECT
				`id`,
				`title`
			FROM
				`tc_flex_sections_fields`
			WHERE
				`active` = 1 AND
				`visible` = 1 AND
				`type` != 3 AND
				`section_id` IN(:sections)
		";
		$aSQL = array('iClientID' => (int)$user_data['client'], 'sections' => $aSections);
		$aFlexibles = DB::getPreparedQueryData($sSQL, $aSQL);

		foreach((array)$aFlexibles as $aFlex) {
			$aData[1]['fields'][$aFlex['id'] * -1] = Field::getFlexInstance($aFlex['title'])->setFlex(true);
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Unset the buildings and floors

		if($bWithLocationCheck) {
			$sSQL = "
				SELECT
					*
				FROM
					`kolumbus_school_buildings`
				WHERE
					`school_id` = :iSchoolID
				LIMIT
					1
			";
			$aSQL = array('iSchoolID' => (int)\Core\Handler\SessionHandler::getInstance()->get('sid'));
			$mCheck = DB::getQueryOne($sSQL, $aSQL);

			if(empty($mCheck)) {
				unset($aData[3]['fields'][28], $aData[3]['fields'][29]);
			}
		}

		return $aData;
	}


	/**
	 * Get groups
	 * 
	 * @param bool $bWithEmpty
	 * @return array
	 */
	public static function getGroups($bWithEmpty = true)
	{
		$aGroups = array(
			9 => L10N::t('Block', self::$_sDescription),
			1 => L10N::t('Klasse', self::$_sDescription),
			2 => L10N::t('Lehrer', self::$_sDescription),
			3 => L10N::t('Klassenzimmer', self::$_sDescription),
			4 => L10N::t('Kurs', self::$_sDescription),
			5 => L10N::t('Gebäude', self::$_sDescription),
			6 => L10N::t('Etage', self::$_sDescription),
			7 => L10N::t('Schüler', self::$_sDescription),
			8 => L10N::t('Level', self::$_sDescription),
			10 => L10N::t('Keine', self::$_sDescription)
		);

		if($bWithEmpty) {
			return Ext_Thebing_Util::addEmptyItem($aGroups);
		}

		return $aGroups;
	}

	/* ==================================================================================================== */

	/**
	 * See parent
	 */
	protected function deleteRow($iRowId) {
		
		$mError = parent::deleteRow($iRowId);

		if(
			is_array($mError) &&
			count($mError) == 1 && 
			isset($mError['total_width'])
		) {
			return $this->oWDBasic->delete();
		}

		return $mError;
	}

	/**
	 * Write the HTML code for the cols dialog tab
	 * 
	 * @param array $aSelectedIds
	 * @return string
	 */
	protected function _writeColsTabHTML(&$aData, $aSelectedIds)
	{
		if(!is_array($aSelectedIds))
		{
			$aSelectedIds = array();
		}

		$iReportID = (int)reset($aSelectedIds);

		$oReport = new Ext_Thebing_Tuition_Report($iReportID);

		$aData['cols_tab_saved_cols'] = $oReport->columns;

		$oFakeResult = new Ext_Thebing_Tuition_Report_Result(array());

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Get column items

		$aData['cols_tab_data'] = self::getColumnsData();

		$aTranslations = array(
			'remove'	=> L10N::t('entfernen', $this->_oGui->gui_description),
			'columns'	=> L10N::t('Spalten', $this->_oGui->gui_description),
			'search'	=> L10N::t('Suche', $this->_oGui->gui_description),
			'width'		=> L10N::t('Breite', $this->_oGui->gui_description)
		);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		$oSmarty = new SmartyWrapper();

		$oSmarty->assign('iReportID', $iReportID);
		$oSmarty->assign('aSavedCols', $oReport->columns);
		$oSmarty->assign('sIconPath', Ext_Thebing_Util::getIcon('delete'));
		$oSmarty->assign('aTranslations', $aTranslations);
		$oSmarty->assign('aColumns', self::getColumnsData());
		$oSmarty->assign('iOrderByColumn', $oReport->order_by_column);
		$oSmarty->assign('aPossibleOrderColumns', $oFakeResult->getOrderByFields());

		$sCode = $oSmarty->fetch(self::getTemplatePath() . 'tab_cols.tpl');

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		return $sCode;
	}


	/**
	 * See parent
	 */
	protected function _getErrorMessage($sError, $sField='', $sLabel='', $sAction = null, $sAdditional = null) {

		$sMessage = '';

		switch($sError) {
			case 'WRONG_WIDTH':
				$sMessage = 'Bitte prüfen Sie die Breitenangaben der Spalten.';
				$sMessage = $this->t($sMessage);
				break;
			case 'MAXIMUM_WIDTH':
	
				$sMessage = 'Die Summe der Spaltenbreiten passt nicht mehr in das von Ihnen gewählte Layout.';
				$sMessage = $this->t($sMessage);
				$sMessage .= '<br/><input id="ignoreErrors" type="checkbox" value="1" />'.$this->t('Fehler ignorieren und speichern');
				break;
			default:
				$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
				break;
		}

		return $sMessage;
	}
	
}