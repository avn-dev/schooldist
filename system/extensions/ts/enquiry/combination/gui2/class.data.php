<?php

/**
 * @see Ext_TS_Enquiry_Combination_Gui2_Dialog_SaveHandler
 *
 * @property Ext_TS_Inquiry_Journey $oWDBasic
 */
class Ext_TS_Enquiry_Combination_Gui2_Data extends Ext_Thebing_Document_Gui2 {

	public function _getWDBasicObject($selectedIds) {

		/** @var Ext_TS_Inquiry_Journey $journey */
		$journey = parent::_getWDBasicObject($selectedIds);

		// Hier passiert wieder irgendein Blödsinn mit explizit ID 0, ausgelöst durch _buildQueryParts
		if (
			$this->request->input('task') === 'loadTable' ||
			$this->request->input('task') === 'updateIcons'
		) {
			return $journey;
		}

		// Bei neuer Kombination: Prüfen, ob diese (erste) mit Dummy-Eintrag ersetzt werden muss
		if (!$journey->exist()) {
			$this->replaceJourney($journey);
		}

		return $journey;

	}

	/**
	 * Hier passiert etwas sehr »schickes«: Die Anfrage braucht zwangsläufig eine Journey, um überhaupt eine Schule zu haben.
	 * Dieser Dummy-Eintrag wird nicht angezeigt, soll aber dann für die erste Kombination gesetzt werden.
	 *
	 * @param Ext_TS_Inquiry_Journey $journey
	 */
	private function replaceJourney(Ext_TS_Inquiry_Journey &$journey) {

		// Nicht getInquiry() aufrufen, da das ansonsten wegen bidirektional ein leeres Journey-Objekt erzeugt, welches dann auch gespeichert werden möchte (inquiry_id wird durch GUI gesetzt)
		// $journeys = collect($journey->getInquiry()->getJourneys());
		$journeys = collect(Ext_TS_Inquiry_Journey::getRepository()->findBy(['inquiry_id' => $journey->inquiry_id]));
		$dummies = $journeys->where('type', Ext_TS_Inquiry_Journey::TYPE_DUMMY);

		// Min. der Dummy-Eintrag muss da sein, wenn es keine bereits gespeicherte Kombination (request) gibt
		if ($journeys->isEmpty()) {
			throw new \LogicException(sprintf('Enquiry %d has no journey/combination and therefore no school.', $journey->getInquiry()->id));
		}

		if (
			$dummies->isNotEmpty() &&
			$journeys->count() > 1
		) {
			throw new \LogicException(sprintf('Enquiry %d has dummy and more than one journey?.', $journey->getInquiry()->id));
		}

		if ($dummies->count() === 1) {
			/** @var Ext_TS_Inquiry_Journey $journey */
			$journey = parent::_getWDBasicObject([$dummies->first()->id]);
			$journey->type = $journey::TYPE_REQUEST;
		} else {
			$journey->type = $journey::TYPE_REQUEST;
			$journey->school_id = $journeys->first()->school_id;
			$journey->productline_id = $journeys->first()->productline_id;
		}

	}

	protected function manipulateSqlParts(array &$aSqlParts, string $sView) {

		$aSqlParts['select'] .= ",
			`kid`.`id` `document_id`,
			`kid`.`document_number`,
			`kidv`.`path` `pdf_path`,
			IF(`ts_dtd`.`child_document_id` IS NOT NULL, 1, 0) `is_converted`,
			GROUP_CONCAT(DISTINCT `ts_ijc`.`id`) `course_ids`,
			GROUP_CONCAT(DISTINCT `ts_ija`.`id`) `accommodation_ids`,
			GROUP_CONCAT(DISTINCT `ts_iji`.`id`) `insurance_ids`
		";

		$aSqlParts['from'] .= " LEFT JOIN
			`ts_inquiries_journeys_courses` `ts_ijc` ON
				`ts_ijc`.`journey_id` = `ts_ij`.`id` AND
				`ts_ijc`.`active` = 1 AND
				`ts_ijc`.`visible` = 1 LEFT JOIN
			`ts_inquiries_journeys_accommodations` `ts_ija` ON
				`ts_ija`.`journey_id` = `ts_ij`.`id` AND
				`ts_ija`.`active` = 1 AND
				`ts_ija`.`visible` = 1 LEFT JOIN
			`ts_inquiries_journeys_insurances` `ts_iji` ON
				`ts_iji`.`journey_id` = `ts_ij`.`id` AND
				`ts_iji`.`active` = 1 AND
				`ts_iji`.`visible` = 1 LEFT JOIN
			`kolumbus_inquiries_documents` `kid` ON
				`kid`.`entity` = '".Ext_TS_Inquiry_Journey::class."' AND
				`kid`.`entity_id` = `ts_ij`.`id` AND
				`kid`.`active` = 1 LEFT JOIN
			`kolumbus_inquiries_documents_versions` `kidv` ON
				`kidv`.`id` = `kid`.`latest_version` LEFT JOIN
			`ts_documents_to_documents` `ts_dtd` ON
				`ts_dtd`.`parent_document_id` = `kid`.`id` AND
				`ts_dtd`.`type` = 'offer'
		";

		$aSqlParts['groupby'] = " `ts_ij`.`id` ";

	}

	protected $_aJoinedObjectOptions = array(
		'min' => 1,
		'max' => 100,
	);

	/**
	 * {@inheritdoc}
	 */
	public function switchAjaxRequest($_VARS) {

		$aTransfer = array();
//		$aParentGuiId = (array)$_VARS['parent_gui_id'];

//		$iEnquiryId = reset($aParentGuiId);
//		$oEnquiry = Ext_TS_Enquiry::getInstance($iEnquiryId);
//		$oSchool = $oEnquiry->getSchool();

//		$oSchoolForFormat = Ext_Thebing_Client::getFirstSchool($this->_oGui->access);

		$aAccommodationDates = array();
		$aTransferDates = array();

		if ($_VARS['action'] === 'delete_offer_pdf') {

			$oJourney = $this->_getWDBasicObject($_VARS['id']);
			$oDocument = $oJourney->getDocument();

			if ($oDocument !== null) {
				$oDocument->delete();
			}

			Ext_Gui2_Index_Stack::add('ts_inquiry', $oJourney->getInquiry()->id, 0);

			$aTransfer['action'] = 'deleteCallback';
			$aTransfer['error'] = [];
			$aTransfer['parent_gui'] = $this->_oGui->getParentGuiData();

			echo json_encode($aTransfer);

		} elseif($_VARS['task'] === 'recalculateWeek') { // Endwoche berechnen

			$oSchool = $this->getSelectedObject()->getSchool();
			$oSchoolForFormat = Ext_Thebing_Client::getFirstSchool($this->_oGui->access);

			$sUntil = '';

			if (
				!empty($_VARS['from']) &&
				$_VARS['weeks'] > 0
			) {
				if ($_VARS['type'] === 'courses') {

					$sUntil = Ext_Thebing_Util::getUntilDateOfCourse($_VARS['from'], $_VARS['weeks'], $oSchool->id, false, $oSchoolForFormat->id);

					// Hier müssen auch die Unterkunftszeiten mit berechnet werden da diese auto. befüllt werden
					$aFrom = json_decode($_VARS['courses_from'], true);
					$aUntil = json_decode($_VARS['courses_until'], true);
					$aAccommodations = json_decode($_VARS['accommodations'], true);

					// Evtl. Until Feld befüllen weil dies im selben Request befüllt wird
					foreach ($aFrom as $iKey => $sFrom) {
						if (empty($aUntil[$iKey])) {
							$aUntil[$iKey] = $sUntil;
						}
					}

					if (!empty($aAccommodations)) {
						$accommodationIndex = \Illuminate\Support\Arr::first(array_keys($aAccommodations));
						if (str_contains($aAccommodations[$accommodationIndex]['field'], '[ts_ija][0]')) {
							$aFirstLastDates = $oSchoolForFormat->getFirstLastDate($aFrom, $aUntil);
							$category = \Ext_Thebing_Accommodation_Category::getInstance((int)$aAccommodations[$accommodationIndex]['value']);
							$aAccommodationDates[] = ['field' => $aAccommodations[$accommodationIndex]['field'], 'dates' => $oSchoolForFormat->getAccommodationDatesOfCourseDates($aFirstLastDates['first_i'], $aFirstLastDates['last_i'], $category)];
						}
					}

				} elseif ($_VARS['type'] === 'accommodations') {

					$category = \Ext_Thebing_Accommodation_Category::getInstance((int)$_VARS['type_id']);

					$sUntil = Ext_Thebing_Util::getUntilDate($_VARS['from'], $_VARS['weeks'], $oSchool->id, $category, false, $oSchoolForFormat->id);

					// Hier müssen auch die Transfer mit berechnet werden da diese auto. befüllt werden
					$aFrom = json_decode($_VARS['accommodation_from']);
					$aUntil = json_decode($_VARS['accommodation_until']);

					// Evtl. Until Feld befüllen weil dies im selben Request befüllt wird
					foreach ($aFrom as $iKey => $sFrom) {
						if (empty($aUntil[$iKey])) {
							$aUntil[$iKey] = $sUntil;
						}
					}

					$aTransferDates = $oSchoolForFormat->getFirstLastDate($aFrom, $aUntil);

				} elseif ($_VARS['type'] === 'insurances') {

					$sDate = Ext_Thebing_Format::ConvertDate($_VARS['from'], $oSchoolForFormat->id);

					if (is_numeric($sDate)) // #2473 - Es soll nichts passieren, wenn kein Startdatum gegeben ist
					{
						$oDate = new WDDate($sDate);

						$oDate->add((int)$_VARS['weeks'], WDDate::WEEK)->sub(1, WDDate::DAY);

						$iUntil = $oDate->get(WDDate::TIMESTAMP);

						$sUntil = Ext_Thebing_Format::LocalDate($iUntil, $oSchool->id);
					}


				} else {
					throw new Exception('Type "' . \Util::convertHtmlEntities($_VARS['type']) . '" unknown!');
				}
			}

			$sElementFrom = $_VARS['element_id'];
			$sElementUntil = str_replace('from', 'until', $sElementFrom);

			$aTransfer['action'] = 'recalculateWeekCallback';
			$aTransfer['data'] = array(
				'type' => $_VARS['type'],
				'type_id' => $_VARS['type_id'],
				'weeks' => $_VARS['weeks'],
				'from' => $_VARS['from'],
				'until' => $sUntil,
				'until_id' => $sElementUntil,
				'accommodation_data' => $aAccommodationDates,
				'transfer_data' => $aTransferDates,
				'element_id' => $_VARS['element_id']
			);

			echo json_encode($aTransfer);

		} else if ($_VARS['task'] === 'getAccommodationPeriod') {

			$iCategory = (int)$_VARS['category_id'];
			$aFrom = json_decode($_VARS['course_from'], true);
			$aUntil = json_decode($_VARS['course_until'], true);

			$aTransfer['error'] = array();
			if ($iCategory > 0) {
				$oSchool = $this->getSelectedObject()->getSchool();
				$oCategory = \Ext_Thebing_Accommodation_Category::getInstance($iCategory);

				if (str_contains($_VARS['field'], '[ts_ija][0]')) {
					// Nur bei der ersten Unterkunft ausführen
					$aFirstLastDates = $oSchool->getFirstLastDate($aFrom, $aUntil);
					$aAccommodationDates = $oSchool->getAccommodationDatesOfCourseDates($aFirstLastDates['first_i'], $aFirstLastDates['last_i'], $oCategory);
				}

				$aAccommodationDates['time_from'] = substr($oCategory->arrival_time, 0, 5);
				$aAccommodationDates['time_until'] = substr($oCategory->departure_time, 0, 5);

				$aTransfer['action'] = 'resultAccommodationData';
				$aTransfer['data']['field'] = $_VARS['field'];
				$aTransfer['data']['returnData'] = $aAccommodationDates;
			}

			echo json_encode($aTransfer);

		} else {
			parent::switchAjaxRequest($_VARS);
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public function getDialogHTML(&$sIconAction, &$oDialog, $aSelectedIds = array(), $sAdditional = false) {

		if ($sIconAction === 'document_edit') {

			/** @var Ext_TS_Inquiry_Journey $oJourney */
			$oJourney = $this->getWDBasicObject($aSelectedIds);
			$oInquiry = $oJourney->getInquiry();
			$oInquiry->setJourneyContext($oJourney);

			// Dieses Injecten vom Dokument lief auch bei den früheren Offers immer so
			$oDocument = $oJourney->getDocument();
			if (!$oDocument) {
				// Hier scheint nur die Doc-ID wichtig zu sein
				$oDocument = $oInquiry->newDocument('offer', false);
				$oDocument->entity = Ext_TS_Inquiry_Journey::class;
				$oDocument->entity_id = $oJourney->id;
			}

			$oDocumentHelper = new Ext_Thebing_Document();
			$oDocumentHelper->setInquiry($oInquiry);
			$this->_oGui->setOption('document_class', $oDocumentHelper);
			//$aSelectedIds = [$this->oWDBasic->getInquiry()->id]; // Überschreiben, damit Dialog sich selbst auf seiner ewiggwährenden Reise finden kann
			$oDialog = $oDocumentHelper->getEditDialog($this->_oGui, $oDocument->id, 'offer', $aSelectedIds);

		}
		
		$aData = parent::getDialogHTML($sIconAction, $oDialog, $aSelectedIds, $sAdditional);
		
		return $aData;
		
	}

	public static function getListWhere(Ext_Gui2 $oGui = null) {

		return ['ts_ij.type' => ['&', Ext_TS_Inquiry_Journey::TYPE_REQUEST]];

	}

	/**
	 * @param Ext_Thebing_Gui2 $oGui
	 * @return Ext_Gui2_Dialog
	 */
	public static function getDialog(Ext_Thebing_Gui2 $oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Kombination bearbeiten'), $oGui->t('Kombination erstellen'));
//		$oDialog->setDataObject(Ext_TS_Enquiry_Combination_Gui2_Dialog_Data::class);
		return $oDialog;

	}

	/**
	 * {@inheritdoc}
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $aAction = 'edit', $bPrepareOpenDialog = true) {

		// Das ist total wichtig, damit beim Speichern ggf. der Dummy-Journey ersetzt wird
		// Ohne diese Zeile erzeugt ansonsten Dialog-Data immer wieder neue Journeys, da die Methode redundant ist
		if (!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);
		}

		if($bSave) {

			$aTransfer = array(
				'data' => array(),
				'error' => array(),
				'action' => 'saveDialogCallback'
			);

			$bHasService = true; #$this->_checkForService($aSaveData);

			if(!$bHasService) {

				$aTransfer['error'][0] = $this->t('Fehler beim Speichern');
				$aTransfer['error'][1] = array(
					'type' => 'error',
					'message' => $this->t('Bitte wählen Sie mindestens eine Leistung aus.')
				);
				return $aTransfer;

			} else {

				$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $aAction, $bPrepareOpenDialog);

			}

		} else {

			$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $aAction, $bPrepareOpenDialog);

		}

		return $aTransfer;

	}

	/**
	 * Prüft, ob mindestens eine Leistung ausgewählt ist.
	 *
	 * @param mixed[] $aSaveData
	 * @return boolean
	 */
//	protected function _checkForService($aSaveData) {
//
//		$bHasService = false;
//		$aNeededFields = array(
//			'course_id' => 'ts_ijc',
//			'accommodation_id' => 'ts_ija',
//			'transfer_mode' => 'ts_ij',
//			'insurance_id' => 'ts_iji'
//		);
//
//		foreach($aNeededFields as $sField => $sAlias) {
//
//			if(!isset($aSaveData[$sField][$sAlias])) {
//				continue;
//			}
//
//			// ID und JoinedObjectKey entfernen
//			if(is_array($aSaveData[$sField][$sAlias])){
//				$mValue = reset($aSaveData[$sField][$sAlias]);
//				$sValue = reset($mValue);
//			} else {
//				$sValue = $aSaveData[$sField][$sAlias];
//			}
//
//			// Bei Transfer aus Mode != no prüfen
//			if($sField === 'transfer_mode') {
//				if($sValue !== 'no') {
//					$bHasService = true;
//					break;
//				}
//			}
//
//			// Alle anderen Felder sind IDs
//			elseif(!empty($sValue)) {
//				$bHasService = true;
//				break;
//			}
//
//		}
//
//		return $bHasService;
//
//	}

	public function modifiyEditDialogDataRow(&$aRow) {

		// Locations beim Transfer für Select zusammenbasteln
		// Setter: Ext_TS_Enquiry_Combination_Gui2_Dialog_SaveHandler (nur beim Speichern)
		if (
			$aRow['db_alias'] === 'ts_ijt' && (
				$aRow['db_column'] === 'start' ||
				$aRow['db_column'] === 'end'
			)
		) {
			foreach ($aRow['value'] as &$aValue) {
				/** @var Ext_TS_Inquiry_Journey_Transfer $oTransfer */
				if (!preg_match('/^[^_]+_[^_]+$/', $aValue['value'])) {
					// Beim Dialog-Reload stehen die Werte bereits in dem Format zur Verfügung
					$oTransfer = $this->oWDBasic->getJoinedObjectChild($aRow['joined_object_key'], $aValue['id']);
					$aValue['value'] = $oTransfer->buildLocationMergedString($aRow['db_column']);
				}
			}
		}

	}

	/**
	 * {@inheritdoc}
	 */
	protected function getEditDialogHTML(&$oDialog, $aSelectedIds, $sAdditional = false) {

		$oWDBasic = $this->oWDBasic;
		if(!$oWDBasic){
			$oWDBasic = $this->_getWDBasicObject($aSelectedIds);
		}
		/** @var Ext_TS_Inquiry_Journey $oWDBasic */

//		if(
//			($oWDBasic instanceof Ext_TS_Enquiry_Combination) &&
//			$oWDBasic->hasOffers()
//		) {
//			$oDialog->bReadOnly = true;
//		}

		$interfaceLanguage = Ext_Thebing_School::fetchInterfaceLanguage();

		$oEnquiry = $oWDBasic->getInquiry();
		/** @var Ext_TS_Inquiry $oEnquiry */

		$oSchool = $oEnquiry->getSchool();
		/** @var Ext_Thebing_School $oSchool */

		$oDialog->aElements = array();

		// Kurse
		$oCourseTab = $this->getCourseTab($oDialog, $oEnquiry);
		$oCourseTab->access = 'thebing_tuition_icon';
		$oDialog->setElement($oCourseTab);

		// Unterkünfte
		$oAccommodationTab = $this->getAccommodationTab($oDialog, $oEnquiry);
		$oAccommodationTab->access = 'thebing_accommodation_icon';
		$oDialog->setElement($oAccommodationTab);

		// Transfer
		$oTransferTab = $this->getTransferTab($oDialog, $oEnquiry);
		$oTransferTab->access = 'thebing_pickup_icon';
		$oDialog->setElement($oTransferTab);

		// Versicherungen
		$oInsuranceTab = $this->getInsuranceTab($oDialog);
		$oInsuranceTab->access = 'thebing_insurance_icon';
		$oDialog->setElement($oInsuranceTab);

		$aData = $oDialog->generateAjaxData($aSelectedIds, $this->_oGui->hash);

		$aData['course_data'] = \Ext_TS_Inquiry_Index_Gui2_Data::getCourseDialogData($oSchool);
		$aData['course_lessons_units'] = collect(\TsTuition\Enums\LessonsUnit::cases())
			->mapWithKeys(fn ($case) => [$case->value => $case->getLabelText($this->_oGui->getLanguageObject())])
			->toArray();

		$aData['course_languages'] = Ext_Thebing_Tuition_LevelGroup::getInstance()->getArrayList(true, 'name_'.$interfaceLanguage);

		return $aData;

	}

	/**
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param Ext_TS_Enquiry $oEnquiry
	 * @return Ext_Gui2_Dialog_Tab
	 */
	private function getCourseTab(Ext_Gui2_Dialog $oDialog, Ext_TS_Inquiry $oEnquiry) {

		$oSchool = $oEnquiry->getSchool();
		$aCourses = $oSchool->getCourseList(true, false, false, false, false);
		$aCourses = Ext_Thebing_Util::addEmptyItem($aCourses, $this->t('Kein Kurs'));
		$aLevels = Ext_Thebing_Util::addEmptyItem($oSchool->getCourseLevelList());

		$oSaveHandlerCourse = new Ext_TS_Enquiry_Combination_Gui2_Dialog_SaveHandler();

		$aContainerOptions = $this->_aJoinedObjectOptions;
		$aContainerOptions['save_handler'] = $oSaveHandlerCourse;
		$aContainerOptions['row_class'] = 'InquiryCourseContainer';

		$oTab = $oDialog->createTab($this->t('Kurs'));		

		$oJoinContainer = $oDialog->createJoinedObjectContainer('courses', $aContainerOptions);
		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Kurs'),
			'select',
			[
				'db_alias' => 'ts_ijc',
				'db_column' => 'course_id',
				'select_options' => $aCourses,
				'class' => 'courseSelect txt',
			]
		));

		$interfaceLanguage = Ext_Thebing_School::fetchInterfaceLanguage();

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Kurssprache'),
			'select',
			[
				'db_alias' => 'ts_ijc',
				'db_column' => 'courselanguage_id',
				'select_options' => Ext_Thebing_Tuition_LevelGroup::getInstance()->getArrayList(true, 'name_'.$interfaceLanguage),
				'class' => 'courseLanguageSelect txt',
				'row_class' => 'row_course_languages',
			]
		));

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Level'),
			'select',
			[
				'db_alias' => 'ts_ijc',
				'db_column' => 'level_id',
				'select_options' => $aLevels,
			]
		));

		$oJoinContainer->setElement($oJoinContainer->createMultiRow($this->t('Anzahl der Einheiten'), [
			'row_class' => 'row_units',
			'db_alias' => 'ts_ijc',
			'items' => [
				[
					'input' => 'input',
					'db_column' => 'units',
					'class' => 'units lessons-input',
					'format' => new Ext_Thebing_Gui2_Format_Float(),
					'info_icon_key' => 'inquiry_course_units'
				],
				[
					'input' => 'select',
					'db_column' => 'units_dummy',
					'class' => 'units lessons-select',
					'select_options' => [],
					'text_after' => ' <span class="lessons_unit"></span>',
					'skip_value_handling' => true,
					'style' => 'display: none',
				]
			]
		]));

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Programm'),
			'select',
			[
				'db_alias' => 'ts_ijc',
				'db_column' => 'program_id',
				'select_options' => [],
				'class' => '',
				'row_class' => 'row_program'
			]
		));

		$oPeriodContainer = new Ext_Gui2_Html_Div();
		$oPeriodContainer->class = 'course_period_container';

		$oRefreshButton = new Ext_Gui2_Html_Button();
		$oRefreshButton->title = $this->t('Enddatum neu berechnen');
		$oRefreshButton->onclick="return false;";
		$oRefreshButton->class = 'btn btn-default btn-sm recalculate_enddate inputDivAddonIcon';
		$oRefreshButton->setElement('<i class="fa '.Ext_Thebing_Util::getIcon('refresh').'"></i>');

		$oPeriodContainer->setElement($oJoinContainer->createRow(
			$this->t('Wochen'),
			'input',
			[
				'db_alias' => 'ts_ijc',
				'db_column' => 'weeks',
				'class' => '',
				'input_div_addon' => $oRefreshButton,
				'row_class' => 'row_weeks'
			]
		));

		$oPeriodContainer->setElement($oJoinContainer->createRow(
			$this->t('Von'),
			'calendar',
			[
				'db_alias' => 'ts_ijc',
				'db_column' => 'from',
				'format' => new Ext_Thebing_Gui2_Format_Date(),
				'joined_object_key' => 'course',
				'class'	=> 'from_field courseFrom txt',
			]
		));

		$oPeriodContainer->setElement($oJoinContainer->createRow(
			$this->t('Bis'),
			'calendar',
			[
				'db_alias' => 'ts_ijc',
				'db_column' => 'until',
				'format' => new Ext_Thebing_Gui2_Format_Date(),
				'class' => 'courseUntil',
				'row_class' => 'row_until'
			]
		));

		$oJoinContainer->setElement($oPeriodContainer);

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Kommentar'),
			'textarea',
			[
				'db_alias' => 'ts_ijc',
				'db_column' => 'comment',
				'class' => 'course_comment',
			]
		));

		$oJoinContainer->setElement($this->createTravellerSelect($oEnquiry, $oJoinContainer));

		$oTab->setElement($oJoinContainer);

		return $oTab;

	}

	/**
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param Ext_TS_Enquiry $oEnquiry
	 * @return Ext_Gui2_Dialog_Tab
	 */
	private function getAccommodationTab(Ext_Gui2_Dialog $oDialog, Ext_TS_Inquiry $oEnquiry) {

		$oSchool = $oEnquiry->getSchool();
		$aAccommodations = $oSchool->getAccommodationList();
		$aAccommodations = Ext_Thebing_Util::addEmptyItem($aAccommodations, $this->t('Keine Unterkunft'));

		$oSaveHandlerAccommodation = new Ext_TS_Enquiry_Combination_Gui2_Dialog_SaveHandler();
		$aContainerOptions = $this->_aJoinedObjectOptions;
		$aContainerOptions['save_handler'] = $oSaveHandlerAccommodation;
		$aContainerOptions['row_class'] = 'InquiryAccommodationContainer';

		$oTab = $oDialog->createTab($this->t('Unterkunft'));
		$oJoinContainer = $oDialog->createJoinedObjectContainer('accommodations', $aContainerOptions);

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Unterkunft'),
			'select',
			[
				'db_alias' => 'ts_ija',
				'db_column' => 'accommodation_id',
				'class' => 'accommodationCategory',
				'select_options' => $aAccommodations,
				'events' => [
					[
						'event'  => 'change',
						'function' => 'reloadAccommodationPeriod',
					],
				],
			]
		));

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Raumart'),
			'select',
			[
				'db_alias' => 'ts_ija',
				'db_column' => 'roomtype_id',
				'selection' => new Ext_TS_Enquiry_Combination_Gui2_Selection_Accommodation_Roomtype(),
				'dependency' => [
					[
						'db_alias' => 'ts_ija',
						'db_column' => 'accommodation_id',
					],
				],
			]
		));

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Verpflegung'),
			'select',
			[
				'db_alias' => 'ts_ija',
				'db_column' => 'meal_id',
				'selection' => new Ext_TS_Enquiry_Combination_Gui2_Selection_Accommodation_Meal(),
				'dependency' => [
					[
						'db_alias' => 'ts_ija',
						'db_column' => 'roomtype_id',
					],
				],
			]
		));

		$oRefreshButton = new Ext_Gui2_Html_Button();
		$oRefreshButton->title = $this->t('Enddatum neu berechnen');
		$oRefreshButton->onclick="return false;";
		$oRefreshButton->class = 'btn btn-default btn-sm recalculate_enddate inputDivAddonIcon';
		$oRefreshButton->setElement('<i class="fa '.Ext_Thebing_Util::getIcon('refresh').'"></i>');

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Wochen'),
			'input',
			[
				'db_alias' => 'ts_ija',
				'db_column' => 'weeks',
				'class' => 'accommodationWeeks',
				'input_div_addon' => $oRefreshButton,
			]
		));

		$oJoinContainer->setElement($oJoinContainer->createMultiRow(
			$this->t('Von'),
			[
				'db_alias' => 'ts_ija',
				'grid' => true,
				'items' => [
					[
						'db_column' => 'from',
						'input' => 'calendar',
						'format' => new Ext_Thebing_Gui2_Format_Date(),
						'class' => 'from_field accommodationFrom txt',
					],
					[
						'db_column' => 'from_time',
						'input' => 'input',
						'format' => new Ext_Thebing_Gui2_Format_Time(),
						'placeholder' => $this->t('Anreisezeit')
					],
				],
			]
		));

		$oJoinContainer->setElement($oJoinContainer->createMultiRow(
			$this->t('Bis'),
			[
				'db_alias' => 'ts_ija',
				'grid' => true,
				'items' => [
					[
						'db_column' => 'until',
						'input' => 'calendar',
						'format' => new Ext_Thebing_Gui2_Format_Date(),
						'class' => 'txt accommodationUntil',
					],
					[
						'db_column' => 'until_time',
						'input' => 'input',
						'format' => new Ext_Thebing_Gui2_Format_Time(),
						'placeholder' => $this->t('Abreisezeit')
					],
				],
			]
		));

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Kommentar'),
			'textarea',
			[
				'db_alias' => 'ts_ija',
				'db_column' => 'comment',
				'class' => 'accommodation_comment',
			]
		));

		$oJoinContainer->setElement($this->createTravellerSelect($oEnquiry, $oJoinContainer));

		$oTab->setElement($oJoinContainer);

		return $oTab;

	}

	/**
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param Ext_TS_Enquiry $oEnquiry
	 * @return Ext_Gui2_Dialog_Tab
	 */
	private function getTransferTab(Ext_Gui2_Dialog $oDialog, Ext_TS_Inquiry $oEnquiry) {

		$aTransfer = Ext_Thebing_Data::getTransferList();
		$oTab = $oDialog->createTab($this->t('Transfer'));

		$oTab->setElement($oDialog->createRow(
			$this->t('Transfer'),
			'select',
			[
				'db_column' => 'transfer_mode',
				'db_alias' => 'ts_ij',
				'select_options' => $aTransfer,
			]
		));

		$oTab->setElement($oDialog->createRow(
			$this->t('Kommentar'),
			'textarea',
			[
				'db_column' => 'transfer_comment',
				'db_alias' => 'ts_ij',
				'class' => 'transfer_comment',
			]
		));

		$oJoinContainer = $this->getTransferElement('arrival', $oDialog, $oEnquiry);
		$oTab->setElement($oJoinContainer);

		$oJoinContainer = $this->getTransferElement('departure', $oDialog, $oEnquiry);
		$oTab->setElement($oJoinContainer);

		$oJoinContainer = $this->getTransferElement('additional', $oDialog, $oEnquiry);
		$oTab->setElement($oJoinContainer);		

		return $oTab;

	}

	private function getTransferElement($sType, $oDialog, Ext_TS_Inquiry $oEnquiry) {

		$aOptions = [
			'arrival' => [
				'label' => 'Anreise',
				'location_start' => 'Anreiseort',
				'location_end' => 'Ankunftsort',
				'time' => 'Anreiseuhrzeit',
			],
			'departure' => [
				'label' => 'Abreise',
				'location_start' => 'Anreiseort',
				'location_end' => 'Ankunftsort',
				'time' => 'Abreiseuhrzeit',
			],
			'additional' => [
				'label' => 'Individueller Transfer',
				'location_start' => 'Anreiseort',
				'location_end' => 'Ankunftsort',
			],
		];

		$aData = $aOptions[$sType];
		$aTransferLocations = $oEnquiry->getTransferLocations($sType);

		$oSaveHandlerTransfer = new Ext_TS_Enquiry_Combination_Gui2_Dialog_SaveHandler();

		$iMin = $iMax = 1;
		if($sType === 'additional') {
			$iMin = 0;
			$iMax = 100;
		}

		$aContainerOptions = [
			'min' => $iMin,
			'max' => $iMax,
		];
		$aContainerOptions['save_handler'] = $oSaveHandlerTransfer;

		$oJoinContainer = $oDialog->createJoinedObjectContainer('transfers_'.$sType, $aContainerOptions);

		$oH3 = new Ext_Gui2_Html_H3();
		$oH3->setElement($this->t($aData['label']));
		$oJoinContainer->setElement($oH3);

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t($aData['location_start']),
			'select',
			[
				'db_column' => 'start',
				'db_alias' => 'ts_ijt',
				'select_options' => $aTransferLocations,
				'class' => 'additional_transfer_from',
			]
		));

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t($aData['location_end']),
			'select',
			[
				'db_column' => 'end',
				'db_alias' => 'ts_ijt',
				'selection' => new Ext_TS_Enquiry_Combination_Gui2_Selection_Transfer($aTransferLocations),
				'dependency' => [ // @TODO: Funktioniert nicht, da das kein saveDialog ist
					[
						'db_column' => 'start',
						'db_alias' => 'ts_ijt',
					],
				],
				'class' => 'additional_transfer_end',
			]
		));

		if($sType !== 'additional') {

			$oJoinContainer->setElement($oJoinContainer->createRow(
				$this->t('Fluglinie'),
				'input',
				[
					'db_column' => 'airline',
					'db_alias' => 'ts_ijt',
				]
			));

			$oJoinContainer->setElement($oJoinContainer->createRow(
				$this->t('Flugnummer'),
				'input',
				[
					'db_column' => 'flightnumber',
					'db_alias' => 'ts_ijt',
				]
			));

			$sClassDate = '';
			if($sType == 'arrival') {
				$sClassDate = 'transferDateArrival';
			} elseif($sType == 'departure') {
				$sClassDate = 'transferDateDeparture';
			}

			$oJoinContainer->setElement($oJoinContainer->createMultiRow(
				$this->t('Datum'),
				[
					'grid' => true,
					'items' => [
						[
							'db_column' => 'transfer_date',
							'db_alias' => 'ts_ijt',
							'input' => 'calendar',
							'format' => new Ext_Thebing_Gui2_Format_Date(),
							'style'	=> 'margin-right:25px;',
							'class' => 'txt '.$sClassDate,
						],
						[
							'db_column' => 'transfer_time',
							'db_alias' => 'ts_ijt',
							'format' => new Ext_Thebing_Gui2_Format_Time(),
							'input' => 'input',
							'placeholder' => $this->t($aData['time']),
						],
					],
					'db_alias' => 'ts_ijt',
				]
			));

		} else {

			$oJoinContainer->setElement($oJoinContainer->createRow(
				$this->t('Datum'),
				'calendar',
				[
					'db_column' => 'transfer_date',
					'db_alias' => 'ts_ijt',
					'format' => new Ext_Thebing_Gui2_Format_Date(),
				]
			));

		}

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Abholung'),
			'input',
			[
				'db_column' => 'pickup',
				'db_alias' => 'ts_ijt',
				'format' => new Ext_Thebing_Gui2_Format_Time(),
			]
		));

		$aCommentRowOptions = [
			'db_column' => 'comment',
			'db_alias' => 'ts_ijt',
		];

		if($sType !== 'additional') {
			$aCommentRowOptions['class'] = 'transfer_comment';
		}

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Kommentar'),
			'textarea',
			$aCommentRowOptions
		));

		$oJoinContainer->setElement($this->createTravellerSelect($oEnquiry, $oJoinContainer));

		return $oJoinContainer;

	}

	/**
	 * Liefert den Versicherungstab
	 *
	 * @param Ext_Gui2_Dialog $oDialog
	 * @return Ext_Gui2_Dialog_Tab
	 */
	private function getInsuranceTab(Ext_Gui2_Dialog $oDialog) {

		$oTab = $oDialog->createTab($this->t('Versicherung'));

		$oCombination = $this->oWDBasic;
		if(!($oCombination instanceof Ext_TS_Inquiry_Journey)) {
			return $oTab;
		}

		$oEnquiry = $oCombination->getInquiry();
		$oSchool = $oEnquiry->getSchool();

		$aContainerOptions = $this->_aJoinedObjectOptions;
		$aContainerOptions['save_handler'] = new Ext_TS_Enquiry_Combination_Gui2_Dialog_SaveHandler();

		$oJoinContainer = $oDialog->createJoinedObjectContainer('insurances', $aContainerOptions);

		$aSelectOptions = array();
		$aInsurances = Ext_Thebing_Insurances_Gui2_Insurance::getInsurancesListForInbox(true, $oSchool->id);

		// Versicherungen mit Preisstruktur "pro tag"

		$aInsurencsPeriods = array();
		foreach($aInsurances as $aData) {
			$aSelectOptions[$aData['id']] = $aData['title'];
			if($aData['payment'] == 3) {
				$aInsurencsPeriods[] = $aData['id'];
			}
		}

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Versicherung'),
			'select',
			[
				'db_alias' => 'ts_iji',
				'db_column' => 'insurance_id',
				'select_options' => $aSelectOptions,
				'class' => 'insuranceSelect',
			]
		));

		$oRefreshImg = new Ext_Gui2_Html_Button();
		$oRefreshImg->title = $this->t('Enddatum neu berechnen');
		$oRefreshImg->onclick="return false;";
		$oRefreshImg->class = 'btn btn-default btn-sm recalculate_enddate inputDivAddonIcon';
		$oRefreshImg->setElement('<i class="fa '.Ext_Thebing_Util::getIcon('refresh').'"></i>');

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Wochen'),
			'input',
			[
				'db_alias' => 'ts_iji',
				'db_column' => 'weeks',
				'dependency_visibility' => [
					'db_column' => 'insurance_id',
					'db_alias' => 'ts_eci',
					'on_values' => $aInsurencsPeriods
				],
				'input_div_addon' => $oRefreshImg,
			]
		));

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Startdatum'),
			'calendar',
			[
				'db_alias' => 'ts_iji',
				'db_column' => 'from',
				'format' => new Ext_Thebing_Gui2_Format_Date(),
				'class' => 'from_field txt',
			]
		));

		$oJoinContainer->setElement($oJoinContainer->createRow(
			$this->t('Enddatum'),
			'calendar',
			[
				'db_alias' => 'ts_iji',
				'db_column' => 'until',
				'format' => new Ext_Thebing_Gui2_Format_Date()
			]
		));

		$oJoinContainer->setElement($this->createTravellerSelect($oEnquiry, $oJoinContainer));

		$oTab->setElement($oJoinContainer);

		return $oTab;

	}

	private function createTravellerSelect(Ext_TS_Inquiry $oInquiry, Ext_Gui2_Dialog_JoinedObjectContainer $oJoinContainer) {

		// Das war vorher die Ext_TS_Enquiry::getTravellers(), darf aber für das Gruppenkonstrukt in Ext_TS_Inquiry nicht passieren
		$aContacts = $oInquiry->hasGroup() ? $oInquiry->getGroup()->getMembers() : [$oInquiry->getFirstTraveller()];
		$aContacts = collect($aContacts)->mapWithKeys(function (Ext_TS_Contact $oContact) {
			return [$oContact->id => $oContact->getName()];
		});

		// Wenn leer: Werte werden in \Ext_TS_Enquiry_Combination_Gui2_Dialog_SaveHandler::handle() gesetzt
		return $oJoinContainer->createRow($this->t('Schüler'), 'select', [
			'db_alias' => 'ts_ijc',
			'db_column' => 'travellers',
			'select_options' => $aContacts->toArray(),
			//'default_value' => $aContacts->keys()->toArray(), // Funktioniert nicht, da dann immer alles ausgewählt wird (es wird HTML mit selected generiert)
			'multiple' => 3,
			'jquery_multiple' => true,
			//'required' => true, // Funktioniert auch nicht, weil dann anderen Services, die leer eh gelöscht werden, Fehler im Dialog melden
		]);

	}

	/**
	 * {@inheritdoc}
	 */
	public function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional = false, $bSave = true) {

		// Dialog: Ext_TS_Enquiry_Gui2_Dialog_Convert
		if ($sAction === 'convert_offer_to_inquiry') {

			$oNumberrange = Ext_Thebing_Inquiry_Document_Numberrange::getInstance($aData['numberrange_id']);
			if (!$oNumberrange->exist()) {
				throw new RuntimeException('Numberrange doesn\'t exist!');
			}

			if (!$oNumberrange->acquireLock()) {
				return [
					'action' => 'saveDialogCallback',
					'error' => [Ext_Thebing_Document::getNumberLockedError()],
					'data' => $aData
				];
			}

			DB::begin(__METHOD__);

			$oInquiry = $this->getSelectedObject();
			$oInbox = Ext_Thebing_Client_Inbox::getInstance($aData['inbox_id']);
			$oTemplate = Ext_Thebing_Pdf_Template::getInstance($aData['template_id']);
			$bCreateProforma = $aData['document_type'] === 'proforma';

			$oConvert = new Ext_TS_Enquiry_Convert2($oInquiry);
			$oConvert->setInbox($oInbox);
			$oConvert->setJourney($oInquiry->getJourney(), $oNumberrange, $oTemplate, $bCreateProforma);
			$oConvert->setAttachedAdditional($this->request->input('attached_additional_document', []));
			$oConvert->convert();

			DB::commit(__METHOD__);

			$oNumberrange->removeLock();

			return [
				'action' => 'closeDialogAndReloadTable',
				'data' => [
					'id' => 'CONVERT_'.\Illuminate\Support\Arr::first($this->request->input('id')),
					'options' => [
						'close_after_save' => false
					]
				]
			];

		}

		return parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);

	}

	/**
	 * {@inheritdoc}
	 */
	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab = false, $sAdditional = false, $bSaveSuccess = true) {

		$aData2 = [];

		if($this->oWDBasic === null) {
			$this->_getWDBasicObject($aSelectedIds);
		}
		$oSchool = $this->oWDBasic->getSchool();

		// Angebot+Anfrage zu Buchung umwandeln
		if ($sIconAction === 'convert_offer_to_inquiry') {

			$oEnquiry = $this->oWDBasic->getInquiry();
			$oSchool = $oEnquiry->getSchool();

			$aEnquiries[] = $oEnquiry;

			// Templates holen die verwendet werden dürfen
			$aTemplates = Ext_TS_Enquiry_Gui2_Dialog_Convert::getConvertDialogTemplateOptions($this->oWDBasic);

			// Nummernkreise holen die zur Auswahl stehen
			$aNumberranges = $oSchool->getEnquiryDocumentNumberrangeOptions();

			$oDialog = (new \Ext_TS_Enquiry_Gui2_Dialog_Convert($aEnquiries, $this->oWDBasic))->create($this->_oGui);
			$this->aIconData[$sIconAction]['dialog_data'] = $oDialog;

			$aData2['default_inbox_id'] = key(Ext_Thebing_System::getInboxList('use_id', true));
			$aData2['templates'] = $aTemplates;
			$aData2['numberranges'] = $aNumberranges;
		}

		$aData = parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);

		$aData['course_data'] = \Ext_TS_Inquiry_Index_Gui2_Data::getCourseDialogData($oSchool);

		$aInsurancesTypeLinks = Ext_Thebing_Insurances_Gui2_Insurance::getInsurancesListForInbox(true);
		$aData['aInsurancesTypeLinks'] = $aInsurancesTypeLinks;

		$aData = array_merge($aData, $aData2);

		return $aData;

	}

	/**
	 * {@inheritdoc}
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {

		switch($sError) {
			case 'ENQUIRY_CURRENCY_NOT_SAVED':
				$sErrorMessage = $this->t('Bitte wählen Sie für die ausgewählte Anfrage ein Währung aus.');
				break;

			default:
				$sErrorMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
				break;

		}

		return $sErrorMessage;

	}

	public function getSelectedObject(int $iObjectId = null): \Ts\Interfaces\Entity\DocumentRelation {

		if ($iObjectId !== null) {
			return Ext_TS_Inquiry::getInstance($iObjectId);
		}

		$iJourneyId = (int)collect($this->request->input('id'))->first();
		if ($this->request->has('major_journey_id')) {
			// Spezialfall, den es früher schon gab: Positionsdialog (Gruppen) überschreibt mit anderer ID das Objekt
			// ID ist in dem Fall der Pos-Index der Position (oder auch EP1 usw.) (sic)
			$iJourneyId = (int)$this->request->input('major_journey_id');
		}

		$oJourney = $this->_getWDBasicObject([$iJourneyId]);

		$oInquiry = $oJourney->getInquiry();
		$oInquiry->setJourneyContext($oJourney);

		return $oInquiry;

	}

	/**
	 * @see getSelectedObject()
	 * @param array $aInquiryIds
	 * @return string
	 */
	protected function buildDocumentDialogId(array $aInquiryIds): string {

		// Früher wurde die Offer-ID überall durchgeschleift als »Inquiry« und dann an diversen Stellen zurecht gefaked.
		// Der Dialog hat nun die Journey-ID, aber $aSelectedIDs wird sonst überall zur Inquiry-ID umgewandelt.
		return 'DOCUMENT_'.$this->oWDBasic->id;

	}

	public function replacePlaceholders($sTemplate, &$oWDBasic) {

		// Überschreiben, um sich das bescheuerte Magic __get() zu ersparen
		if (
			$this->request->input('action') === 'document_edit' &&
			$oDocument = $this->oWDBasic->getDocument()
		) {
			return str_replace('{document_number}', $oDocument->document_number, $sTemplate);
		}

		return parent::replacePlaceholders($sTemplate, $oWDBasic);

	}

	/**
	 * @see Ext_TS_Enquiry_Combination_Gui2_Icon_Active
	 */
	protected function checkDeleteRow($iRowId) {

		$mCheck = parent::checkDeleteRow($iRowId);

		// Da die Kombination/Journey die einzige Schul-Zuweisung der Anfrage/Buchung hat, darf diese niemals gelöscht werden
		if (
			$mCheck === true &&
			count($this->getSelectedObject()->getJourneys()) === 1
		) {
			$mCheck = [$this->t('Die letzte Kombination kann nicht gelöscht werden.')];
		}

		return $mCheck;

	}

	public function prepareColumnListByRef(&$aColumnList) {

		parent::prepareColumnListByRef($aColumnList);

		if (System::d('debugmode') == 2) {
			$oColumn = new Ext_Gui2_Head();
			$oColumn->db_column = 'document_id';
			$oColumn->title = 'KID-ID';
			$oColumn->width = 50;
			$oColumn->sortable = false;
			array_splice($aColumnList, 1, 0, [$oColumn]);
		}

	}

	protected function isLockedGui() {

		$bLocked = parent::isLockedGui();

		if (!$bLocked) {
			if ($this->getSelectedObject()->isConverted()) {
				$bLocked = true;
			}
		}

		return $bLocked;

	}

}
