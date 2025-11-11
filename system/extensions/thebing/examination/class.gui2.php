<?php

use \Core\Helper\DateTime;

class Ext_Thebing_Examination_Gui2 extends Ext_Thebing_Gui2_Data {

	public function getEditDialogData($aSelectedIds, $aSaveData = array(), $sAdditional = false) {
		global $_VARS;

		if($_VARS['task'] !== 'reloadDialogTab') {

			$iSelectedID = 0;
			if(is_array($aSelectedIds)) {
				$iSelectedID = (int)reset($aSelectedIds);
			}

			$iTemplateID = $this->_oGui->decodeId($iSelectedID, 'template_id');
			$sExaminationDate = $this->_oGui->decodeId($iSelectedID, 'examination_date');
			$iVersionID = $this->_oGui->decodeId($iSelectedID, 'version_id');
			$iInquiryCourseID = $this->_oGui->decodeId($iSelectedID, 'inquiry_course_id');
			$iProgramServiceId = $this->_oGui->decodeId($iSelectedID, 'program_service_id');
			$iCourseID = $this->_oGui->decodeId($iSelectedID, 'course_id');

			// Wird nur ausgeführt beim Editieren von Template-Einträgen
			if (!empty($iTemplateID)) {
				$this->oWDBasic = Ext_Thebing_Examination_Version::getInstance((int)$iVersionID);

				if ($this->oWDBasic->isNew()) {

					// Prüfungstag setzen für neue Einträge, die durch Terms generiert wurden
					if (
						$this->oWDBasic->examination_date == '0000-00-00' ||
						$this->oWDBasic->examination_date == ''
					) {
						$this->oWDBasic->examination_date = $sExaminationDate;
					}

					$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance($iInquiryCourseID);
					$sLastExaminationDate = Ext_Thebing_Examination::getLastExaminationDateForPeriod($sExaminationDate, $oInquiryCourse);

					// Wenn keine vorherige Prüfung gefunden: Kurs-Startdatum nehmen
					if ($sLastExaminationDate === null) {
						$sLastExaminationDate = $oInquiryCourse->from;
					}

					// Prüfungszeitraum befüllen: Letzte Prüfung – Tag der Prüfung (wie in Liste)
					$this->oWDBasic->from = $sLastExaminationDate;
					$this->oWDBasic->until = $sExaminationDate;

					$oCourse = Ext_Thebing_Tuition_Course::getInstance($iCourseID);
					$oLevelGroup = $oCourse->getLevelgroup();

					$iProgress = Ext_Thebing_Tuition_Progress::getProgress($iInquiryCourseID, 'period', 'id', $oInquiryCourse->until, $oLevelGroup->id);

					$this->oWDBasic->level_id = $iProgress;

					$this->oWDBasic->getJoinedObject('kex')->examination_template_id = $iTemplateID;
					$this->oWDBasic->getJoinedObject('kex')->inquiry_course_id = $iInquiryCourseID;
					$this->oWDBasic->getJoinedObject('kex')->course_id = $iCourseID;
					$this->oWDBasic->getJoinedObject('kex')->program_service_id = $iProgramServiceId;
					$this->oWDBasic->getJoinedObject('kex')->term_possible_date = $sExaminationDate;

				}

			}
		}
		
		$aData = parent::getEditDialogData($aSelectedIds, $aSaveData);

		return $aData;

	}

	static public function getExaminationDialog($bEdit, \Ext_Gui2 $oGui) {

		$oSchool			= Ext_Thebing_School::getSchoolFromSession();

		$aTemplates	= Ext_Thebing_Examination::getTemplates(true);
		$aTemplates	= Ext_Thebing_Util::addEmptyItem($aTemplates, Ext_Thebing_L10N::getEmptySelectLabel('please_choose'));
		$sDisplayLanguage = $oSchool->getInterfaceLanguage();
		$aLevels	= $oSchool->getLevelList(true, $sDisplayLanguage, 'internal');
		$aTeachers	= $oSchool->getTeacherList(true);

		$oDialog					= $oGui->createDialog(L10N::t("Prüfung editieren", $oGui->gui_description), L10N::t('Neue Prüfung anlegen', $oGui->gui_description));
		$oDialog->width			= 900;
		$oDialog->height			= 650;

		$oDivRefresh = $oDialog->createGuiIcon('fa '.Ext_Thebing_Util::getIcon('refresh'), $oGui->t('Zeitraum aktualisieren'));
		$oDivRefresh->style = 'margin-top:3px;background-color:#FFFFFF !important;';
		$oDivRefresh->getElements()[0]->getElements()[0]->getElements()[0]->style = 'position: static'; // Kalender-CSS selektiert einfach alle imgs…

		$oTab = $oDialog->createTab($oGui->t('Examen'));

		// Hidden fiels start
		$oHidden = $oDialog->createSaveField('input', array(
			'db_column' => 'examination_term_id',
			'db_alias'	=> 'kex',
			'type'		=> 'hidden'
		));
		$oTab->setElement($oHidden);

		$oHidden = $oDialog->createSaveField('input', array(
			'db_column' => 'term_possible_date',
			'db_alias'	=> 'kex',
			'type'		=> 'hidden'
		));
		$oTab->setElement($oHidden);

		$oHidden = $oDialog->createSaveField('input', array(
			'db_column' => 'sections',
			'db_alias'	=> 'kexv',
			'type'		=> 'hidden'
		));
		$oTab->setElement($oHidden);
		// hidden fields end

		$oTab->setElement($oDialog->createRow($oGui->t('Tag des Examens'), 'calendar', array(
			'db_column'			=> 'examination_date',
			'db_alias'			=> 'kexv',
			'required'			=> 1,
			'format'			=> new Ext_Thebing_Gui2_Format_Date()
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Examen'), 'select', array(
			'db_column'			=> 'examination_template_id',
			'db_alias'			=> 'kex',
			'select_options'	=> $aTemplates,
			'required'			=> 1,
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Schüler'), 'autocomplete', array(
			'db_alias' => '',
			'db_column'=>'inquiry_id',
			'required'=>true,
			'autocomplete'=>new Ext_Thebing_Examination_Autocomplete(),
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Kurs'), 'select', array(
			'db_column'			=> 'inquiry_course_course',
			'db_alias'			=> 'kex',
			'required'			=> 1,
			'dependency'		=> array(array('db_alias'=>'', 'db_column' => 'inquiry_id')),
			'selection'			=> new Ext_Thebing_Gui2_Selection_Examination_Course(),
			/*'events' => array(
				array(
					'function'=>'changeFromUntil',
					'event'=>'change'
				)
			)*/
		)));

		$oDiv = $oDialog->create('div');
		$oDiv->class = 'GUIDialogRowCalendarDiv';
		$oDiv->style = 'display: flex; flex-direction: row; align-items: center; gap: 0.25rem;';

		//$oLabelDiv = $oDialog->create('div');
		//$oLabelDiv->setElement($oGui->t('Von'));
		//$oLabelDiv->class = "label";
		//$oDiv->setElement($oLabelDiv);
		$oSaveFieldFrom = $oDialog->createSaveField('calendar', array(
			'db_column' => 'from',
			'db_alias'	=> 'kexv',
			'style' => 'width: 100px;',
			'format'	=> new Ext_Thebing_Gui2_Format_Date(),
		));
		$oSaveFieldFrom->style = 'float:left;';
		$oDiv->setElement($oSaveFieldFrom);
		$oLabelDiv = $oDialog->create('div');
		$oLabelDiv->setElement($oGui->t('bis'));
		//$oLabelDiv->class = "label";
		$oDiv->setElement($oLabelDiv);
		$oSaveFieldFrom = $oDialog->createSaveField('calendar', array(
			'db_column' => 'until',
			'db_alias'	=> 'kexv',
			'style' => 'width: 100px;',
			'format'	=> new Ext_Thebing_Gui2_Format_Date(),
		));
		$oSaveFieldFrom->style = 'float:left;margin-right:3px;';
		$oDiv->setElement($oSaveFieldFrom);
		$oDiv->setElement($oDivRefresh);

		$oTab->setElement($oDialog->createRow($oGui->t('Zeitraum'), $oDiv));

		$oTab->setElement($oDialog->createRow($oGui->t('Level'), 'select', array(
			'db_column'			=> 'level_id',
			'db_alias'			=> 'kexv',
			'select_options'	=> $aLevels,
		)));

		$oDivRefreshScore = new Ext_Gui2_Html_Button();
		$oDivRefreshScore->title = $oGui->t('Durchschnittsscore berechnen');
		$oDivRefreshScore->id = 'calculate_score_btn';
		$oDivRefreshScore->class = 'btn btn-default btn-sm';
		$oDivRefreshScore->setElement(
			'<i class="fa '.Ext_Thebing_Util::getIcon("calculator").'"></i> '
			//.$oGui->t('Durchschnittsscore berechnen')
		);

		$oTab->setElement($oDialog->createRow($oGui->t('Score'), 'input', array(
			'db_column'			=> 'score',
			'db_alias'			=> 'kexv',
			'input_div_addon'	=> $oDivRefreshScore
		)));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Prüfungsbereiche'));
		$oTab->setElement($oH3);

		$oDivSections = $oDialog->create('div');
		$oDivSections->id = 'sections';
		$oTab->setElement($oDivSections);

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Sonstiges'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Bestanden'), 'checkbox', array(
			'db_column'			=> 'passed',
			'db_alias'			=> 'kexv',
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Gesamtnote'), 'input', array(
			'db_column'			=> 'grade',
			'db_alias'			=> 'kexv',
			'format'			=> new Ext_Thebing_Gui2_Format_Float(),
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Prüfer'), 'select', array(
			'db_column'			=> 'teachers',
			'db_alias'			=> 'kexv',
			'select_options'	=> $aTeachers,
			'multiple'			=> 5,
			'jquery_multiple'	=> 1,
			'style'				=> 'height: 105px;',
			'searchable'		=> 1,
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Prüfername'), 'input', array(
			'db_column'			=> 'examiner_name',
			'db_alias'			=> 'kexv',
		)));

		$oTab->setElement($oDialog->createRow($oGui->t('Kommentar'), 'textarea', array(
			'db_column'			=> 'comment',
			'db_alias'			=> 'kexv',
		)));

		$oDialog->setElement($oTab);

		if($bEdit === true) {
			$oTab = $oDialog->createTab($oGui->t('Historie'));
			$oTab->setElement(self::getHistoryGui($oGui));
			$oDialog->setElement($oTab);
		}

		return $oDialog;
	}

	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false)
	{
		if(is_array($aSelectedIds))	{
			$iSelectedID	= (int)reset($aSelectedIds);
		} else {
			$iSelectedID	= 0;
		}

		$iVersionID = 0;
		
		$sDialogId	= $this->_getDialogId($oDialogData, $aSelectedIds);
		$sHash		= $this->_oGui->hash;

		if($this->oWDBasic && $this->oWDBasic->getJoinedObject('kex'))
		{
			if($this->oWDBasic->id > 0)
			{
				$iVersionID		= $this->oWDBasic->id;
			}
			$iTemplateID		= $this->oWDBasic->getJoinedObject('kex')->examination_template_id;
			$iInquiryCourseID	= $this->oWDBasic->getJoinedObject('kex')->inquiry_course_id;
		}
		else
		{
			$iTemplateID		= $this->_oGui->decodeId($iSelectedID, 'template_id');
			$iVersionID			= $this->_oGui->decodeId($iSelectedID, 'version_id');
			$iInquiryCourseID	= $this->_oGui->decodeId($iSelectedID, 'inquiry_course_id');
		}

		$oVersion		= Ext_Thebing_Examination_Version::getInstance($iVersionID);
		
		foreach($oDialogData->aElements[0]->aElements as $iKey => $oElement)
		{			
				if( 'sections' == $oElement->id )
				{
					$oElement = $oDialogData->create('div');
					$oElement->id = 'sections';
					$aSections		= $oVersion->getSections($iTemplateID);

					foreach($aSections as $sCategoryName => $aCategoryEntitys)
					{
						$oDivCategory			= new Ext_Gui2_Html_Div();
						$oDivCategory->class	= 'GUIDialogRow';
						$oDivCategoryLabel		= new Ext_Gui2_Html_Div();
						$oDivCategoryLabel->class = 'GUIDialogRowLabelDiv';
						$oDivCategoryLabel->style = 'margin:4px 0;font-weight:bold;';
						$oDivCategoryLabel->setElement((string)$sCategoryName);
						$oDivCategory->setElement($oDivCategoryLabel);

						$oDivCleaner			= new Ext_Gui2_Html_Div();
						$oDivCleaner->class		= 'divCleaner';
						$oDivCategory->setElement($oDivCleaner);

						$oElement->setElement($oDivCategory);

						foreach($aCategoryEntitys as $aEntity)
						{
							$sClass = $aEntity['model_class'];
							$oModel = new $sClass();
							$oModel->section_id = $aEntity['id'];
							$sKey	= $oModel->getEntityKey();
							$sInput = $oModel->getInput();

							$mValue	= $aEntity[$sKey];

							$oInput = $oDialogData->create($sInput);
							$oInput = $oModel->addOptions($oInput);
							$oInput->class	= 'txt form-control';
							$oInput->name	= 'save[section]['.$aEntity['id'].']';
							$oInput->id		= 'save[' . $sHash . '][' . $sDialogId . '][sections_' . $aEntity['id'] . ']';
							$oInput = $oModel->addValue($oInput, $mValue);
							$oElement->setElement($oDialogData->createRow($aEntity['title'],$oInput));
						}
					}

					$oRow = $oDialogData->createRow($this->t('Kommentar zu den Fächern'), 'textarea', array(
						'db_column'			=> 'comment_sections',
						'db_alias'			=> 'kexv',
						'required'			=> 0,
					));
					$oElement->setElement($oRow);

					$oDialogData->aElements[0]->aElements[$iKey] = $oElement;
				}
		}

		$aTransfer = parent::getEditDialogHTML($oDialogData, $aSelectedIds);

		if(!empty($iInquiryCourseID))
		{
			$oDivAll = $oDialogData->create('div');
			$oH3 = $oDialogData->create('h4');
			$oH3->setElement($this->t('Kursinformationen'));
			$oDivAll->setElement($oH3);

			$oDiv = $oDialogData->create('div');
			//$oDiv->class = 'GUIDialogRow';
			$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance($iInquiryCourseID);
			$sInnerText = $oInquiryCourse->getInfo();
			$oDiv->setElement($sInnerText);
			$oDiv->style = 'height:22px;line-height:22px;';
			$oDivAll->setElement($oDiv);

			$oH3BeforeContent = $oDialogData->create('h4');
			$oH3BeforeContent->setElement($this->t('Prüfungsinformationen'));

			$sHtml = $oDivAll->generateHtml().$oH3BeforeContent->generateHtml();
			$aTransfer['tabs'][0]['html'] = $sHtml.$aTransfer['tabs'][0]['html'];
		}

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aTransfer['school_examination_score_passed'] = (float)$oSchool->examination_score_passed;

		return $aTransfer;
	}
	
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {
		global $_VARS;

		if($_VARS['task'] !== 'reloadDialogTab') {

			$iSelectedID = (int)reset($aSelectedIds);

			$iInquiryCourseID = $this->_oGui->decodeId($iSelectedID, 'inquiry_course_id');
			$iProgramServiceId = $this->_oGui->decodeId($iSelectedID, 'program_service_id');
			$iCourseID = $this->_oGui->decodeId($iSelectedID, 'course_id');
			$iTemplateID = $this->_oGui->decodeId($iSelectedID, 'template_id');
			$sExaminationDate = $this->_oGui->decodeId($iSelectedID, 'examination_date');
			$iTermID = $this->_oGui->decodeId($iSelectedID, 'examination_term_id');

			if (!empty($iTemplateID)) {
				$aSaveData['inquiry_course_id'] = array('kex' => $iInquiryCourseID);
				$aSaveData['program_service_id'] = array('kex' => $iProgramServiceId);
				$aSaveData['course_id'] = array('kex' => $iCourseID);
				$aSaveData['examination_term_id'] = array('kex' => $iTermID); // Wichtig für Matching der Table-Rows
				$aSaveData['examination_template_id'] = array('kex' => $iTemplateID);
				if (!empty($sExaminationDate)) {
					$aSaveData['term_possible_date'] = array('kex' => $sExaminationDate);
				}
			}
		}
	 
		$aSections = $aSaveData['section'];

		unset($aSaveData['section']);

		$aSaveData['sections'] = array(
			'kexv' => $aSections
		);

		#dd($aSaveData['examination_template_id']);

		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction);

		return $aTransfer;
	}

	public function manipulateTableDataResultsByRef(&$aResult) {
		global $_VARS;
        $iSessionSchoolId = \Core\Handler\SessionHandler::getInstance()->get('sid');
		$iSchoolId = (int)$iSessionSchoolId;

		if(isset($_VARS['filter']['search_time_from_1'])) {
			$sFrom	= $_VARS['filter']['search_time_from_1'];
			$dFilterFrom = Ext_Thebing_Format::ConvertDate($sFrom, $iSchoolId, 3);
		} else {
			$dFilterFrom = self::getFromDefaultDate();
		}

		if(isset($_VARS['filter']['search_time_until_1'])) {
			$sUntil	= $_VARS['filter']['search_time_until_1'];
			$dFilterUntil = Ext_Thebing_Format::ConvertDate($sUntil, $iSchoolId, 3);
		} else {
			$dFilterUntil = self::getUntilDefaultDate();
		}

		$aNewData = [];

		// Zuerst tatsächlich existierende Prüfungen durchlaufen
		foreach($aResult['data'] as $aData) {
			if(!empty($aData['examination_id'])) {
				// Wenn konkreter Eintrag existiert: Diesen nehmen

				$aData['examination_date_object'] = new DateTime($aData['examination_date']);
				$aData['course_week'] = $this->getCourseWeekForTableRow($aData);
				$aNewData[] = $aData;
			}
		}

		$aCompareData = $aNewData;

		// Prüfungen, die durch Vorlageneinstellungen stattfinden, durchlaufen
		foreach($aResult['data'] as $aData) {
			if(empty($aData['examination_id'])) {
				$oJourneyCourse = Ext_TS_Inquiry_Journey_Course::getInstance($aData['inquiry_course_id']);
				$dCourseFrom = new \Core\Helper\DateTime($oJourneyCourse->from);
				$dCourseUntil = new \Core\Helper\DateTime($oJourneyCourse->until);
				$oExaminationTemplate = Ext_Thebing_Examination_Templates::getInstance($aData['template_id']);
				$aTerms = $oExaminationTemplate->getTerms();

				foreach($aTerms as $oTerm) {
					$aDates = $oTerm->getExaminationDates($dCourseFrom, $dCourseUntil);

					foreach($aDates as $dDate) {

						$aData['examination_term_id'] = $oTerm->id;
						$aData['examination_date_object'] = $dDate;
						$aData['examination_date'] = $dDate->format('Y-m-d');
						$aData['course_week'] = $this->getCourseWeekForTableRow($aData);

						// Nur hinzufügen, wenn Datum in Zeitraum fällt und tatsächlicher Eintrag noch nicht existiert
						if(
							$dDate->isBetween($dFilterFrom, $dFilterUntil) &&
							self::checkIfTableRowExists($aData, $aCompareData) === false
						) {
							$aNewData[] = $aData;
						}
					}
				}
			}
		}

		// Nach Tag der Prüfung und Nachnamen sortieren, wenn keine Sortierung vorhanden
		if(!isset($_VARS['orderby'])) {
			usort($aNewData, function($aData1, $aData2) {
				if($aData1['examination_date_object'] == $aData2['examination_date_object']) {
					return strcmp($aData1['lastname'], $aData2['lastname']);
				} else {
					return $aData1['examination_date_object'] > $aData2['examination_date_object'];
				}
			});
		}

		$aResult['data'] = $aNewData;

	}

	/**
	 * Prüfen, ob für eine errechnete Prüfung schon ein konkreter Eintrag existiert
	 *
	 * Verglichen wird mit nur existenten Einträgen (examination_id ansonsten NULL),
	 * template_id, examination_term_id und examination_date_object (Kombination) und
	 * inquiry_course_id (Unterschiedung verschiedener Schüler einer Gruppe (#9772))
	 *
	 * @param array $aEntry
	 * @param array $aNewData
	 * @return bool/integer
	 */
	public static function checkIfTableRowExists(array $aEntry, array $aNewData) {

		foreach($aNewData as $iIndex=>$aData) {
			if(
				!empty($aData['examination_id']) &&
				!empty($aEntry['examination_term_id']) && // Bei manuellen Einträgen 0
				$aEntry['template_id'] == $aData['template_id'] &&
				$aEntry['examination_term_id'] == $aData['examination_term_id'] &&
				$aEntry['examination_date_object'] == $aData['examination_date_object'] &&
				$aEntry['inquiry_course_id'] == $aData['inquiry_course_id'] &&
				$aEntry['program_service_id'] == $aData['program_service_id']
			) {
				return $iIndex;
			}
		}

		return false;
	}

	/**
	 * Kurswoche aus Prüfungstag ermitteln (für entsprechende Spalte)
	 *
	 * @param array $aData
	 * @return string|null
	 */
	private function getCourseWeekForTableRow(array $aData) {
		$aEntries = explode(';', $aData['tuition_index_entries']);

		foreach($aEntries as $sEntry) {
			list($sWeek, $sCurrentWeek) = explode(',', $sEntry);
			$dFrom = new DateTime($sWeek);
			$dUntil = clone($dFrom);
			$dUntil->add(new DateInterval('P6DT23H59M59S'));

			if($aData['examination_date_object']->isBetween($dFrom, $dUntil)) {
				return $sCurrentWeek.'/'.$aData['weeks'];
			}
		}

		return null;
	}

	public static function getHistoryGui($oGui)
	{
		$oInnerGui = $oGui->createChildGui(md5('examination_version_list'), 'Ext_Thebing_Examination_Version_Gui2');
		$oInnerGui->query_id_column		= 'id';
		$oInnerGui->query_id_alias		= 'kexv';
		$oInnerGui->foreign_key			= 'examination_id';
		$oInnerGui->foreign_key_alias	= 'kexv';
		$oInnerGui->parent_primary_key	= 'examination_id';
		$oInnerGui->load_admin_header	= false;
		$oInnerGui->multiple_selection  = false;

		$oInnerGui->setWDBasic('Ext_Thebing_Examination_Version_List');
		$oInnerGui->setTableData('limit', 30);
		$oInnerGui->setTableData('orderby', array('kexv.created'=>'DESC'));

		# START - Leiste 2 #
		$oBar = $oInnerGui->createBar();
		$oBar->width = '100%';

		$oLabelGroup = $oBar->createLabelGroup(L10N::t('Details', $oInnerGui->gui_description));
		$oBar ->setElement($oLabelGroup);

		$oIcon = $oBar->createIcon(
									Ext_Thebing_Util::getIcon('pdf'),
									'request',
									L10N::t('Vertragsversion öffnen', $oInnerGui->gui_description)
								);
		$oIcon->label				= L10N::t('Vertragsversion öffnen', $oInnerGui->gui_description);
		$oIcon->action				= 'contract_open';
		$oIcon->dbl_click_element	= 1;
		$oBar->setElement($oIcon);

		$oInnerGui->setBar($oBar);
		# ENDE - Leiste 2 #

		# START - Leiste 3 #
			$oBar = $oInnerGui->createBar();
			$oBar->width = '100%';
			$oBar->position = 'top';

			$oPagination = $oBar->createPagination();
			$oBar ->setElement($oPagination);

			$oLoading = $oBar->createLoadingIndicator();
			$oBar->setElement($oLoading);

			$oInnerGui->setBar($oBar);
		# ENDE - Leiste 2 #


		$oColumn				= $oInnerGui->createColumn();
		$oColumn->db_column		= 'version_nr';
		$oColumn->db_alias		= 'kexv';
		$oColumn->title			= $oGui->t('Version');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('number');
		$oColumn->width_resize	= false;
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Int();
		$oInnerGui->setColumn($oColumn);
		
		$oColumn				= $oInnerGui->createColumn();
		$oColumn->db_column		= 'created';
		$oColumn->db_alias		= 'kexv';
		$oColumn->db_type		= 'timestamp';
		$oColumn->title			= $oGui->t('Datum');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('date_time');
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Date_Time();
		$oColumn->width_resize	= false;
		$oInnerGui->setColumn($oColumn);

		$oColumn				= $oInnerGui->createColumn();
		$oColumn->db_column		= 'comment';
		$oColumn->db_alias		= 'kexv';
		$oColumn->title			= $oGui->t('Kommentar');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('comment');
		$oColumn->width_resize	= true;
		$oInnerGui->setColumn($oColumn);

		$oColumn				= $oInnerGui->createColumn();
		$oColumn->db_column		= 'user_id';
		$oColumn->db_alias		= 'kexv';
		$oColumn->title			= $oGui->t('Bearbeiter');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('user_name');
		$oColumn->width_resize	= false;
		$oColumn->inplaceEditor	= 0;
		$oColumn->format		= new Ext_Gui2_View_Format_UserName($bGetById);
		$oInnerGui->setColumn($oColumn);
		

		return $oInnerGui;
	}

	public function switchAjaxRequest($_VARS)
	{
		$aTransfer = array();

		$aTransfer = $this->_switchAjaxRequest($_VARS);

		if(
			$_VARS['action'] == 'contract_open'
		)
		{
			$aSelectedIds	= $_VARS['id'];

			if(
				is_array($aSelectedIds) && 
				count($aSelectedIds) > 0
			){
				
				$oPDF = new Ext_Thebing_Pdf_Merge();
				
				foreach((array)$aSelectedIds as $iSelectedId) {

					$iVersionID		= $this->_oGui->decodeId($iSelectedId, 'version_id');
					$oVersion		= Ext_Thebing_Examination_Version::getInstance($iVersionID);
					$oExamination	= $oVersion->getExamination();
					$oDocument		= Ext_Thebing_Inquiry_Document::getInstance($oExamination->document_id);
					$oVersion		= $oDocument->getLastVersion();

					if(is_object($oVersion) && $oVersion instanceof Ext_Thebing_Inquiry_Document_Version) {
						$sPDFPath		= $oVersion->getPath(true);
						$oPDF->addPdf($sPDFPath);
					}

				}

				$oPDF->display();

			}

		}

		// Zeitraum neu berechnen (Button)
		elseif($_VARS['task'] === 'refreshFromAndUntil') {

			$aTransfer = [
				'action' => 'refreshFromAndUntilCallback',
				'data' => [
					'id' => $_VARS['id']
				]
			];

			$iJourneyCourseId = (int)$_VARS['journey_course_id'];
			$sExaminationDate = $_VARS['examination_date'];

			if(
				!empty($sExaminationDate) &&
				$iJourneyCourseId > 0
			) {
				$sExaminationDate = Ext_Thebing_Format::ConvertDate($_VARS['examination_date'], null, 1);
				if($sExaminationDate) {

					$oDateFormat = new Ext_Thebing_Gui2_Format_Date();
					$oJourneyCourse = Ext_TS_Inquiry_Journey_Course::getInstance($iJourneyCourseId);
					$sLastExaminationDate = Ext_Thebing_Examination::getLastExaminationDateForPeriod($sExaminationDate, $oJourneyCourse);

					// Wenn keine vorherige Prüfung gefunden: Kurs-Startdatum nehmen
					if($sLastExaminationDate === null) {
						$sLastExaminationDate = $oJourneyCourse->from;
					}

					$aTransfer['data']['from'] = $oDateFormat->formatByValue($sLastExaminationDate);
					$aTransfer['data']['until'] = $oDateFormat->formatByValue($sExaminationDate);
					$aTransfer['data']['from_day'] = Ext_Thebing_Util::getWeekDay(2, $sLastExaminationDate);
					$aTransfer['data']['until_day'] = Ext_Thebing_Util::getWeekDay(2, $sExaminationDate);
				}
			}
		}

		// Score kalkulieren (Button)
		elseif($_VARS['task'] === 'calculateScore') {

			$iSelectedId = $this->_getFirstSelectedId();
			$iInquiryCourseId = (int)$_VARS['inquiry_course_id'];
			$iCourseId = (int)$_VARS['course_id'];
			$iProgramServiceId = (int)$_VARS['program_service_id'];
			$dFrom = Ext_Thebing_Format::ConvertDate($_VARS['from'], null, 3);
			$dUntil = Ext_Thebing_Format::ConvertDate($_VARS['until'], null, 3);
			$sScore = '';

			if(
				$iInquiryCourseId > 0 &&
				$iCourseId > 0
			) {
				// @TODO Hier müsste eigentlich auch der Prüfungszeitraum beachtet werden
				$mAverage = Ext_Thebing_Tuition_Attendance::getAverageScoreForInquiryCourse($iInquiryCourseId, $iProgramServiceId);
				if($mAverage !== null) {
					$sScore = $mAverage;
				}
			}

			$aHookData = [
				'period_from' => $dFrom,
				'period_until' => $dUntil,
				'journey_course_id' => $iInquiryCourseId,
				'course_id' => $iCourseId,
				'program_service_id' => $iProgramServiceId,
				'score' => $sScore
			];

			System::wd()->executeHook('ts_tuition_examination_calculate_score', $aHookData);

			$aTransfer = [
				'action' => 'setCalculatedScore',
				'data' => [
					'id' => 'ID_'.$iSelectedId,
					'score' => $aHookData['score']
				]
			];
		}

		else if($_VARS['task'] == 'autocomplete') {

			$sIconKey = self::getIconKey($_VARS['action'], $_VARS['additional']);

			if($this->aIconData[$sIconKey]){
				$oDialogData = $this->aIconData[$sIconKey]['dialog_data'];
			}

			if($oDialogData) {

				foreach((array)$oDialogData->aSaveData as $aOption) {

					if(
						$aOption['db_column'] == $_VARS['db_column'] &&
						$aOption['db_alias'] == $_VARS['db_alias'] &&
						$aOption['element'] == 'autocomplete'
					) {

						$oOptions = $aOption['autocomplete'];

						if($oOptions instanceof Ext_Gui2_View_Autocomplete_Abstract) {
							$oOptions->printOptions($_VARS['search'], $_VARS['id'], $aOption);
						}

						break;
					}
				}

			}

			die();

		}

		echo json_encode($aTransfer);
	}

	public static function getFromDefaultDate() {

		$dDate = new DateTime();
		if($dDate->format('N') != 1) {
			$dDate->modify('last monday');
		}

		return $dDate;

	}

	public static function getUntilDefaultDate() {

		$dDate = self::getFromDefaultDate();
		$dDate->modify('friday this week');
		$dDate->add(new DateInterval('P1W'));

		return $dDate;

	}

	protected function saveInplaceEditor($aParameter) {

		// Spalte steht im Dokument, nicht in Examination oder Version
		if($aParameter['column'] === 'released_student_login') {

			$iExaminationId = $this->_oGui->decodeId($aParameter['row_id'], 'examination_id');
			$oExamination = Ext_Thebing_Examination::getInstance($iExaminationId);
			$oDocument = $oExamination->getDocument();

			if(
				$oExamination->exist() &&
				$oDocument->exist() &&
				$oDocument->type === 'examination'
			) {
				$oDocument->released_student_login = (int)$aParameter['value'];
				return $oDocument->save();
			}
		}

		return parent::saveInplaceEditor($aParameter);

	}

	protected function deleteRow($iRowId) {

		$iId = reset($this->_oGui->decodeId([$iRowId], 'examination_id'));
		if(empty($iId)) {
			return false;
		}

		$oExamination = Ext_Thebing_Examination::getInstance($iId);
		$oExamination->delete();

		return true;

	}
	static public function getExaminationInserted(Ext_Gui2 $oGui, $bUseNumericKeys = false){
		
		if (!$bUseNumericKeys) {
		$aExaminationInserted = array(
			1 => $oGui->t('Examen eingetragen'),
			2 => $oGui->t('Examen nicht eingetragen'),
		);
		} else {
			$aExaminationInserted = [
				1 => "`created` != 0 AND `created` IS NOT NULL",
				2 => '`created` IS NULL'
			];
		}
		
		return $aExaminationInserted;
	}

	public static function getDefaultFilterFrom() {
		$iLastMonth	= self::getFromDefaultDate();
		return \Ext_Thebing_Format::LocalDate($iLastMonth);
	}
	
	public static function getDefaultFilterUntil() {
		$iNextMonth	= self::getUntilDefaultDate();
		return \Ext_Thebing_Format::LocalDate($iNextMonth);
	}
	
	public static function getCourseSelectOptions() {
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		return $oSchool->getCourseList();
	}

	public static function getExaminationStatusSelectOptions(\Ext_Thebing_Gui2 $oGui) {
		return [
			'examen_insterted' => $oGui->t('Examen eingetragen'),
			'examen_not_insterted' => $oGui->t('Examen nicht eingetragen'),
		];
	}

}
