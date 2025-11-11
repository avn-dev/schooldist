<?php

use Core\Handler\SessionHandler as Session;

class Ext_Thebing_Placementtests_Results_Gui2 extends Ext_Thebing_Document_Gui2
{
	use \Communication\Traits\Gui2\WithCommunication,
		\Tc\Traits\Gui2\Import {
			requestExecuteImport as traitRequestExecuteImport;
		}

	private ?\Ts\Service\Import\Result $importService;

	/**
	 * Zweiter Aufruf mit Einträgen, für die manuell eine Zuweisung erfolgt ist
	 * @param $_VARS
	 * @return array
	 */
	protected function requestUnmatchedImport($_VARS): array
	{
		$import = new \Ts\Service\Import\Result();
		$session = Session::getInstance();
		$unmatchedResultImportItems = $session->get('ts_placementtest_import_unmatched_items');
		foreach ($unmatchedResultImportItems['items'] as $rowId => $item) {
			if (!empty($_VARS['save']['autocomplete_inquiry_id_'.$rowId])) {
				$unmatchedResultImportItems['items'][$rowId][3] = $_VARS['save']['autocomplete_inquiry_id_'.$rowId];
			}
		}
		$import->setSettings((array)$unmatchedResultImportItems['settings']);
		$import->setItems($unmatchedResultImportItems['items'], false);
		$import->setFlexFieldData($unmatchedResultImportItems['flexData']);
		$report = $import->executeUnmatched();
		$errors = $import->getErrors();
		$errorMessages = collect();
		foreach ($errors as $item => $itemErrors) {
			$itemMessages = collect($itemErrors)
				->mapToGroups(function($error) use ($item) {
					$prefix = '';
					if (is_numeric($item)) {
						$prefix = sprintf($this->t('Zeile %d'), $item).': ';
					}
					if (
						!empty($error['pointer']) &&
						!empty($error['pointer']->getWorksheet())
					) {
						$prefix = $error['pointer']->getWorksheet().', '.sprintf($this->t('Zeile %d'), $error['pointer']->getRowIndex()).': ';
					}
					return [$prefix => $error['message']];
				})
				->map(fn ($rowCollection, $prefix) => $prefix.$rowCollection->implode(' '))
				->values();
			$errorMessages = $errorMessages->merge($itemMessages);
		}
		$errors = $errorMessages->implode('<br/>');

		$transfer = [
			'action' => 'showSuccessAndReloadTable',
			'data' => [
				'id' => 'RESULT_IMPORT_UNMATCHED_0'
			],
			'success_title' => $this->t('Import ausgeführt'),
			'message' => [sprintf($this->t('Es wurden %d Einträge erstellt, %d aktualisiert und %d wurden wegen Fehler übersprungen.'), $report['insert'], $report['update'], $report['error'])]
		];

		if (isset($aReport['terminated'])) {
			$transfer['action'] = 'showError';
			$transfer['error'] = [
				$this->t('Import abgebrochen'),
				$errors
			];
		} elseif (!empty($errors)) {
			$transfer['message'][] = $errors;
		}
		return $transfer;
	}

	protected function requestExecuteImport($_VARS): array
	{
		$session = Session::getInstance();
		// Alte Werte leeren
		$session->remove('ts_placementtest_import_unmatched_items');
		$transfer = $this->traitRequestExecuteImport($_VARS);
		$unmatchedResultImportItems = $session->get('ts_placementtest_import_unmatched_items');
		// Es gibt unmatched Items, transfer überschreiben
		if (
			$transfer['action'] === 'showSuccessAndReloadTable' &&
			!empty($unmatchedResultImportItems['items'])
		) {

			$transfer['action'] = 'openDialog';
			$dialog = $this->getUnmatchedDialog($unmatchedResultImportItems['items']);
			$this->aIconData['unmatched-import']['dialog_data'] = $dialog;
			$this->_oGui->save();
			$data = $dialog->generateAjaxData([], $this->_oGui->hash);
			$data['events'] = $dialog->getEvents();
			$transfer['load_table'] = true;
			$transfer['show_success'] = true;
			$data['title'] = $this->t('Zuordnen');
			$data['buttons'] = [];
			$data['buttons'][] = [
				'label' => $this->t('Import starten'),
				'task' => 'saveDialog',
				'action' => 'unmatched-import'
			];
			$data['success_message_dialog_id'] = $this->getImportDialogId().'0';
			$data['task'] = 'request';
			$data['action'] = 'unmatched-import';
			$transfer['data'] = $data;
		}
		return $transfer;
	}

	public function executeGuiCreatedHook(): void
	{
		$this->_oGui->name = 'ts_tuition_placementtest';
		$this->_oGui->set = ''; // Darf nicht null sein (Legacy-HTML war leerer String)

		$oInquiryAdditionalDocuments = new Ext_Thebing_Inquiry_Document_Additional();
		$oRowIconActive = new Ext_Thebing_Gui2_Icon_Placementtest_Result();
		$oInquiryAdditionalDocuments->icon_status_active = $oRowIconActive;
		$oInquiryAdditionalDocuments->access_document_edit = 'thebing_tuition_placement_test_documents';
		$oInquiryAdditionalDocuments->access_document_open = 'thebing_tuition_placement_test_display_documents';
//		$oInquiryAdditionalDocuments->column_group_corresponding_language = $oColumnGroupCustomer;

		$this->_oGui->addAdditionalDocumentsOptions($oInquiryAdditionalDocuments);

	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getResultExistsFilter(\Ext_Gui2 $oGui): array
	{
		return array(
			'result_exists' => $oGui->t('online Einstufungstest ausgefüllt'),
			'result_not_exists' => $oGui->t('noch nicht ausgefüllt'),
		);
	}

	/**
	 * @param $sError
	 * @param string $sField
	 * @param string $sLabel
	 * @param null $sAction
	 * @param null $sAdditional
	 * @return string
	 * @throws Exception
	 */
	protected function _getErrorMessage($sError, $sField='', $sLabel='', $sAction = null, $sAdditional = null): string
	{
		switch($sError){
			case 'WRONG_PLACEMENTTEST_RESULT_DATE':
				$sMessage = $this->t('Das Datum der Testergebnisse darf nicht vor dem Datum des Tests liegen.');
				return $sMessage;
				break;
			default:
				return parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}
	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getHasLevelOptions(\Ext_Gui2 $oGui): array
	{
		return array(
			'has_level'		=> $oGui->t('bewertet'),
			'no_level_yet'	=> $oGui->t('noch nicht bewertet')
		);
	}

	/**
	 * @inheritdoc
	 */
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false): array
	{
		$iSelectedID = (int)reset($aSelectedIds);
		$inquiry = \Ext_TS_Inquiry::getInstance($this->_oGui->decodeId($iSelectedID, 'inquiry_id'));

		$courseLanguageId = $this->_oGui->decodeId($iSelectedID, 'courselanguage_id');
		$inquiryCoursesArray = [];
		if (!empty($courseLanguageId)) {
			$inquiryCoursesArray[] = Ext_TS_Inquiry_Journey_Course::query()
				->where('courselanguage_id', $courseLanguageId)
				->where('journey_id', $inquiry->getJourney(false)?->id)
				->get();
		}

		$oPlacementTestResult = Ext_Thebing_Placementtests_Results::getResultByInquiryAndCourseLanguage($inquiry->id, $courseLanguageId);

		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds);

		if (!empty($courseLanguageId)) {
			$oDivAll = $oDialogData->create('div');
			$oH3 = $oDialogData->create('h4');
			$oH3->setElement($this->t('Kursinformationen'));
			$oDivAll->setElement($oH3);

			foreach ($inquiryCoursesArray as $inquiryCoursesCollection) {
				foreach ($inquiryCoursesCollection as $inquiryCourse) {
					$oDiv = $oDialogData->create('div');
					//$oDiv->class = 'GUIDialogRow';
					$sInnerText = $inquiryCourse->getInfo();
					$oDiv->setElement($sInnerText);
					$oDiv->style = 'height:22px;line-height:22px;';
					$oDivAll->setElement($oDiv);
				}
			}


			$sHtml = $oDivAll->generateHtml();
			$aData['tabs'][0]['html'] = $sHtml.$aData['tabs'][0]['html'];
		}

		if(
			$oPlacementTestResult !== null &&
			$oPlacementTestResult->exist()
		) {

			$container = $this->getResultTab($oPlacementTestResult);
			
			if($container) {
				$aData['tabs'][1] = array();
				$aData['tabs'][1]['options'] = array();
				$aData['tabs'][1]['title'] = $this->t('Testergebnisse');
				$aData['tabs'][1]['html'] = $container->generateHTML();
			}
		}

		return $aData;
	}

	protected function getResultTab(Ext_Thebing_Placementtests_Results $oPlacementTestResult): ?Ext_Gui2_Html_Div
	{
		$answersGiven = $oPlacementTestResult->getJoinedObjectChilds('details');

		if(
			!$oPlacementTestResult->answered &
			empty($answersGiven)
		) {
			return null;
		}
		
		// Haken für gegebene Antworten (wird unten gesetzt)
		$check = new Ext_Gui2_Html_I();
		$check->class = 'fa fa-check';
		$check->style = 'width: auto';

		// Optional-Icon (wird unten gesetzt)
		$spanForOptionalIcon = new Ext_Gui2_Html_Span();
		$optionalIcon = new Ext_Gui2_Html_I();
		$optionalIcon->class = 'fa fa-exclamation-circle';
		$optionalIcon->title = L10N::t('Optional');
		$spanForOptionalIcon->setElement($optionalIcon);
		$spanForOptionalIcon->setElement('&nbsp;');

		$container = new Ext_Gui2_Html_Div();
		$container->class = 'infoBox';
		$container->style = 'border: none; margin: 0px; overfow-x: auto; position: auto;';

		$headerDiv = new Ext_Gui2_Html_Div();
		$headerDiv->class = 'box-header';
		$headerDiv->style = 'padding: 0';
		$container->setElement($headerDiv);

		$bodyDiv = new Ext_Gui2_Html_Div();
		$bodyDiv->class = 'box-body resultBody';
		$container->setElement($bodyDiv);

		$onClickDiv = new Ext_Gui2_Html_Div();
		$onClickDiv->class = 'guiBarElement guiBarLink divSingleIcon';
		$onClickDiv->style = 'height: auto; float: right;';
		$onClickDiv->onclick = '$j(&quot;.resultBody&quot;).printThis();';
		$headerDiv->setElement($onClickDiv);

		$printIcon = new Ext_Gui2_Html_I();
		$printIcon->class = 'fa fa-print box-tools pull-right';

		$printIcon->setElement('&nbsp;');
		$onClickDiv->setElement($printIcon);

		$placementtest = $oPlacementTestResult->getPlacementtest();
		$categories = $placementtest->getCategories();

		// Struktur erzeugen, da durch Methoden im Normalfall active == 0 geprüft wird und somit Fragen, die damals
		// aktiv waren, aber heute nicht mehr aktiv sind, nicht angezeigt werden
		$structure = [];
		foreach ($categories as $category) {
			$questions = $category->getQuestions();

			$structure[$category->id] = [
				'category' => $category,
				'questions' => [],
				'answers' => [],
			];

			foreach($questions as $question) {
				$structure[$category->id]['questions'][$question->id] = $question;
			}
		}

		// Gelöschte Kategorien / Fragen an das Array dranhängen
		foreach ($answersGiven as $answerGiven) {

			$question = Ext_Thebing_Placementtests_Question::getInstance($answerGiven->question_id);
			$category = Ext_Thebing_Placementtests_Question_Category::getInstance($question->idCategory);

			if (!isset($structure[$category->id]['category'])) {
				$structure[$category->id]['category'] = $category;
			}

			if (!isset($structure[$category->id]['questions'][$question->id])) {
				$structure[$category->id]['questions'][$question->id] = $question;
			}

			$structure[$category->id]['answers'][$question->id] = $answerGiven;
		}


		foreach ($structure as $categoryData) {

			$questions = $categoryData['questions'];
			$category = $categoryData['category'];

			if (!empty($questions)) {
				$categoryHeading = new Ext_Gui2_Html_H3();
				$categoryHeading->setElement($category->category);
				$bodyDiv->setElement($categoryHeading);
			}

			foreach ($questions as $question) {

				$answersExisting = $question->getAnswers();

				if (
					!empty($answersExisting) ||
					$question->type == $question::TYPE_TEXTAREA
				) {

					$questionResultBox = new \Gui2\Element\Box($question->text);
					$questionResultBox->setHeadlineTitle(false);

					if ($question->optional == 1) {
						$questionResultBox->addToolsElement($spanForOptionalIcon);
					}

					$answerGiven = $categoryData['answers'][$question->id];

					// Wenn es keine Textarea ist und es eine Antwort zu der Frage gibt
					if ($answerGiven->answer_is_right !== null) {
						$spanForAnswerResultIcon = new Ext_Gui2_Html_Span();
						$icon = new Ext_Gui2_Html_I();

						$correctAnswer = (int)$answerGiven->answer_is_right;
						if ($correctAnswer === 1) {
							$icon->class = 'fas fa-check-circle';
							$icon->style = 'color: green';
							$icon->title = L10N::t('Korrekt');
						} elseif ($correctAnswer === 0) {
							$icon->class = 'fas fa-times-circle';
							$icon->style = 'color: red';
							$icon->title = L10N::t('Falsch');
						}

						$spanForAnswerResultIcon->setElement($icon);
						$questionResultBox->addToolsElement($spanForAnswerResultIcon);
					}

					$answerTable = new Ext_Gui2_Html_Table();
					$answerTable->class = 'table table-hover';

					$tableBody = new Ext_Gui2_Html_Table_TBody();

					// Tabelle für die Antworten und den Haken für die beantwortete Frage
					foreach ($answersExisting as $answer) {
						if (!empty($answer->text)) {
							$tableTr = new Ext_Gui2_Html_Table_tr();
							$tableTd = new Ext_Gui2_Html_Table_Tr_Td();

							if ($answer->right_answer == 1) {
								$color = '221, 255, 172';
							} elseif ($answer->right_answer == 0) {
								$color = '255, 204, 204';
							}

							$tableTd->style = 'background-color: rgb(' . $color . '); width: 300px';
							$tableTd->setElement($answer->text);
							$tableTr->setElement($tableTd);


							// Ermitteln, ob die Antwortmöglichkeit vom Schüler geantwortet wurde
							// (-> dann ein Haken anzeigen) (Bei Textfelder gibt es nie ein Haken, sonder immer direkt
							// die Antwort vom Schüler ausgeschrieben, siehe unten bei der "displayCheck" Abfrage)
							$displayCheck = false;
							if (
								$question->type == $question::TYPE_SELECT &&
								$answer->id == $answerGiven->value
							) {
								$displayCheck = true;
							} elseif (
								$question->type == $question::TYPE_CHECKBOX ||
								$question->type == $question::TYPE_MULTISELECT
							) {
								// JSON (array) bei Checkboxen und Multiselects
								$answerValues = $answerGiven->value;
								foreach ($answerValues as $answerValue) {
									if ($answer->id == $answerValue) {
										$displayCheck = true;
									}
								}
							}

							// Immer ein 2. Td, damit die Breite gleich bleibt, auch wenn keine Antwort gegeben wurde
							$tableTd = new Ext_Gui2_Html_Table_Tr_Td();
							if ($displayCheck) {
								$tableTd->setElement($check);
							}
							$tableTr->setElement($tableTd);

							$tableBody->setElement($tableTr);
						}
					}

					$answerTable->setElement($tableBody);

					$questionResultBox->setElement($answerTable);

					if (
						$question->type == $question::TYPE_TEXT ||
						$question->type == $question::TYPE_TEXTAREA
					) {
						$questionResultBox->setElement(sprintf('%s: "%s"', $this->t('Antwort des Schülers'), e($answerGiven->value)));
					}

					$saveIdentifier = 'save[notices][' . $question->id . ']';

					// Kommentarfeld
					$commentDiv = new Ext_Gui2_Html_Div();
					$commentDiv->class = 'GUIDialogRow  form-group form-group-sm';
					$commentLabel = new Ext_Gui2_Html_Label();
					$commentLabel->class = 'GUIDialogRowLabelDiv col-sm-4 control-label';
					$commentLabel->for = $saveIdentifier;
					$commentLabel->setElement(L10N::t('Kommentar'));
					$commentDiv->setElement($commentLabel);

					$divForTextArea = new Ext_Gui2_Html_Div();
					$divForTextArea->class = 'GUIDialogRowInputDiv col-sm-8';

					$commentTextArea = new Ext_Gui2_Html_Textarea();
					$commentTextArea->class = 'txt form-control autoheight input-sm w-full';
					$commentTextArea->name = $saveIdentifier;
					$commentTextArea->id = $saveIdentifier;

					$notice = Ext_Thebing_Placementtests_Notices::getNoticeByResultAndQuestion($oPlacementTestResult->id, $question->id);

					// Value
					if (!empty($notice->comment)) {
						$commentTextArea->setElement($notice->comment);
					}

					$divForTextArea->setElement($commentTextArea);

					$commentDiv->setElement($divForTextArea);

					$questionResultBox->setElement('<br>');
					$questionResultBox->setElement($commentDiv);

					$bodyDiv->setElement($questionResultBox);
				}
			}
		}

		$resultEvaluationTable = new Ext_Gui2_Html_Table();
		$resultEvaluationTable->class = 'table table-hover table-striped';

		$tableHead = new Ext_Gui2_Html_Table_THead();

		$tr = new Ext_Gui2_Html_Table_tr();

		$td = new Ext_Gui2_Html_Table_Tr_Th();
		$td->style = 'width: auto';
		$td->setElement(L10N::t('Kategorien'));
		$tr->setElement($td);

		$td = new Ext_Gui2_Html_Table_Tr_Th();
		$td->style = 'width: 100px';
		$td->setElement(L10N::t('Richtig'));
		$tr->setElement($td);

		// Für die "Hintergrundfarbe" und die Breite.
		$td = new Ext_Gui2_Html_Table_Tr_Th();
		$td->style = 'width: 70px';
		$tr->setElement($td);

		$tableHead->setElement($tr);

		$resultEvaluationTable->setElement($tableHead);

		$tableBody = new Ext_Gui2_Html_Table_TBody();
		$resultSummary = $oPlacementTestResult->result_summary;

		// Ergebnis-Zusammenfassung
		foreach ($structure as $categoryData) {
			if (!empty($categoryData['questions'])) {

				$category = $categoryData['category'];

				$tr = new Ext_Gui2_Html_Table_tr();

				$td = new Ext_Gui2_Html_Table_Tr_Td();
				$td->style = 'width: auto';
				$td->setElement($category->category);
				$tr->setElement($td);

				$td = new Ext_Gui2_Html_Table_Tr_Td();
				$td->style = 'width: 100px';

				$amountQuestionsAnsweredCorrectBasedOnCategory = $resultSummary['amount'][$category->id]['amountQuestionsAnsweredCorrectBasedOnCategory'];
				$amountQuestionsAnsweredBasedOnCategory = $resultSummary['amount'][$category->id]['amountQuestionsAnsweredBasedOnCategory'];
				$stringForResult = $amountQuestionsAnsweredCorrectBasedOnCategory.'/'.$amountQuestionsAnsweredBasedOnCategory;

				$td->setElement($stringForResult);
				$tr->setElement($td);

				$td = new Ext_Gui2_Html_Table_Tr_Td();
				$td->style = 'text-align: right; font-weight: bold; width: 60px';

				$format = new Ext_Thebing_Gui2_Format_Float(2);

				$td->setElement($format->format($resultSummary['percentage'][$category->id]) . ' %');
				$tr->setElement($td);

				$tableBody->setElement($tr);
			}
		}

		// Summe
		$tr = new Ext_Gui2_Html_Table_tr();

		$td = new Ext_Gui2_Html_Table_Tr_Th();
		$td->setElement(L10N::t('Summe'));
		$tr->setElement($td);

		$td = new Ext_Gui2_Html_Table_Tr_Th();

		$td->setElement($oPlacementTestResult->getFormattedTotalCorrectAnswers());
		$tr->setElement($td);

		$td = new Ext_Gui2_Html_Table_Tr_Th();
		$td->class = 'text-right';

		$percentRight = $resultSummary['percentage']['total'];

		$format = new Ext_Thebing_Gui2_Format_Float(2);

		$format->format($percentRight);

		$td->setElement($format->format($percentRight) . ' %');
		$tr->setElement($td);

		$tableBody->setElement($tr);

		$resultEvaluationTable->setElement($tableBody);
		$bodyDiv->setElement($resultEvaluationTable);

		return $container;
	}


	/**
	 * @inheritdoc
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true): array
	{
		$selectedId = (int)reset($aSelectedIds);

		$iInquiryId = $this->_oGui->decodeId($selectedId, 'inquiry_id');
		$courseLanguageId = $this->_oGui->decodeId($selectedId, 'courselanguage_id');

		/** @var Ext_Thebing_Placementtests_Results $oResult */
		$oResult = $this->_getWDBasicObject($aSelectedIds);

		$oResult->inquiry_id = (int)$iInquiryId;
		$oResult->courselanguage_id = $courseLanguageId;

		foreach($aSaveData['notices'] as $iQuestionId => $sNotice) {

			$notice = Ext_Thebing_Placementtests_Notices::getNoticeByResultAndQuestion($oResult->id, $iQuestionId);

			if (!empty($sNotice)) {
				if (empty($notice)) {
					$notice = new Ext_Thebing_Placementtests_Notices();
					$notice->result_id = $oResult->id;
					$notice->question_id = $iQuestionId;
				}
				$notice->comment = $sNotice;
				$notice->save();
			} elseif (!empty($notice)) {
				// Wenn es den Kommentareintrag schon gibt (Beim Kommentar-Löschen)
				// (eigentlich geht auch $notice->remove(), aber erstmal so gelassen..)
				$notice->comment = $sNotice;
				$notice->save();
			}
		}

		$aTransfer =  parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction);
		// Wird gebraucht um Flex Werte zu speichern
		$aTransfer['save_id'] = $oResult->getId();
		return $aTransfer;
	}

	/**
	 * @param array $_VARS
	 *
	 * @throws Exception
	 */
	public function switchAjaxRequest($_VARS): void
	{
		if($_VARS['task'] == 'getReviewDetails') {

			$aSelectedIDs = (array)$_VARS['id'];
			$iSelectedID = (int)reset($aSelectedIDs);

			/** @var Ext_Thebing_Placementtests_Results $oResult */
			$oPlacementTestResult = $this->_getWDBasicObject($aSelectedIDs);

			if(
				$oPlacementTestResult !== null &&
				$oPlacementTestResult->getId() !== 0
			) {

				$aData = $oPlacementTestResult->getReviewData();
				$aTransfer = parent::_switchAjaxRequest($_VARS);
				$aTransfer['data']['id'] = 'ID_'.$iSelectedID;
				$aTransfer['action'] = 'getReviewDetails';
				$aTransfer['reviews'] = $aData;
				$aTransfer['notices'] = $oPlacementTestResult->getChildAsArray();
				echo json_encode($aTransfer);
				$this->_oGui->save();
				die();

			}
		} else if (
			$_VARS['task'] === 'request' &&
			$_VARS['action'] === 'communication'
		) {
			$ids = $this->_oGui->decodeId($_VARS['id'], 'inquiry_id');
			// Wird bei Massenkommunikation zu viel und wird aktuell nicht verwendet
			//$additional = ['gui2_encoded' => $this->_oGui->decodeId($_VARS['id'])];
			$additional = [];

			$notifiables = \Ext_TS_Inquiry::query()->findOrFail($ids);

			$access = ($_VARS['additional'])
				? $this->readCommunicationAccessFromIconData($_VARS['additional'])
				: null;

			$this->openCommunication($notifiables, application: $_VARS['additional'] ?? null, access: $access, additional: $additional);
		}

		parent::switchAjaxRequest($_VARS);

	}

	/**
	 * @param $sL10NDescription
	 *
	 * @return array
	 */
	public function getTranslations($sL10NDescription): array
	{
		$aData = parent::getTranslations($sL10NDescription);
		$aData['right'] = L10N::t('Richtig', $sL10NDescription);
		$aData['optional'] = L10N::t('Optional', $sL10NDescription);
		$aData['summary'] = L10N::t('Summe', $sL10NDescription);
		$aData['results'] = L10N::t('Ergebnisse', $sL10NDescription);
		$aData['user_answer'] = L10N::t('Antwort des Schülers', $sL10NDescription);
		$aData['commentary'] = L10N::t('Kommentar', $sL10NDescription);
		$aData['print'] = L10N::t('Drucken', $sL10NDescription);

		return $aData;
	}

	public static function getOrderby(): array
	{
		return ['first_course_start' => 'ASC', 'customerNumber' => 'ASC'];
	}

	public static function getDialog(\Ext_Gui2 $oGui): Ext_Gui2_Dialog
	{
		$oSchool				= Ext_Thebing_School::getSchoolFromSession();
		$sDisplayLanguage		= $oSchool->getInterfaceLanguage();
		$aLevels				= $oSchool->getLevelList(true, $sDisplayLanguage, 'internal');
		$aTeachers				= $oSchool->getTeacherList(true);

		$oDialog					= $oGui->createDialog(L10N::t("Einstufungstest editieren", $oGui->gui_description), L10N::t('Einstufungstest anlegen', $oGui->gui_description));
		$oDialog->width				= 900;
		$oDialog->height			= 650;

		$oTab	= $oDialog->createTab($oGui->t('Level'));
		$oTab->aOptions['section'] = 'placementtests_results';
		$oH3	= $oDialog->create('h4');
		$oH3->setElement($oGui->t('Level'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Datum des Tests'), 'calendar', array(
			'db_column' => 'placementtest_date',
			'db_alias'	=> 'ts_ptr',
			'format'	=> new Ext_Thebing_Gui2_Format_Date()
		)));
		$oTab->setElement($oDialog->createRow($oGui->t('Datum der Bewertung'), 'calendar', array(
			'db_column' => 'placementtest_result_date',
			'db_alias'	=> 'ts_ptr',
			'format'	=> new Ext_Thebing_Gui2_Format_Date(),
			'row_id'	=> 'result_date'
		)));
		$oTab->setElement($oDialog->createRow($oGui->t('Internes Level'), 'select', array(
			'db_column' => 'level_id',
			'db_alias'	=> 'ts_ptr',
			'select_options' => Ext_Thebing_Util::addEmptyItem($aLevels),
			'required'	=> 1
		)));
		$oTab->setElement($oDialog->createRow($oGui->t('Note/Punkte'), 'input', array(
			'db_column' => 'mark',
			'db_alias'	=> 'ts_ptr',
		)));
		$oTab->setElement($oDialog->createRow($oGui->t('Score'), 'input', array(
			'db_column' => 'score',
			'db_alias'	=> 'ts_ptr',
		)));
		$oTab->setElement($oDialog->createRow($oGui->t('Bemerkung'), 'textarea', array(
			'db_column' => 'comment',
			'db_alias'	=> 'ts_ptr',
		)));
		$oTab->setElement($oDialog->createRow($oGui->t('Lehrer'), 'select', array(
			'db_column'			=> 'teachers',
			'db_alias'			=> 'ts_ptr',
			'select_options'	=> $aTeachers,
			'multiple'			=> 5,
			'jquery_multiple'	=> 1,
			'style'				=> 'height: 105px;',
			'searchable'		=> 1,
		)));
		$oTab->setElement($oDialog->createRow($oGui->t('Prüfer'), 'input', array(
			'db_column' => 'examiner_name',
			'db_alias'	=> 'ptr',
		)));
		$oDialog->setElement($oTab);

		return $oDialog;
	}

	public static function getSelectFilterEntriesCourseCategories(): array
	{
		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		return $oSchool->getCourseCategoriesList('select');
	}

	public static function getInboxColumnandInboxFilterValues(): array
	{
		$oClient = Ext_Thebing_System::getClient();
		$aInboxes = $oClient->getInboxList(true, true);

		return $aInboxes;
	}

	public static function getDefaultFilterFrom(): int|string
	{
		$oDate = new WDDate();
		$oDate->add(1,  WDDate::MONTH);
		$oDate->sub(3, WDDate::MONTH);
		$iLastMonth = (int)$oDate->get(WDDate::TIMESTAMP);

		return Ext_Thebing_Format::LocalDate($iLastMonth);
	}

	public static function getDefaultFilterUntil(): int|string
	{
		$oDate = new WDDate();
		$oDate->add(1,  WDDate::MONTH);
		$iNextMonth = (int)$oDate->get(WDDate::TIMESTAMP);

		return Ext_Thebing_Format::LocalDate($iNextMonth);
	}

	public static function getInquiryTypeOptions(\Ext_Gui2 $oGui): array
	{
		return [
			'enquiry' => $oGui->t('Anfragen'),
			'inquiry' => $oGui->t('Buchungen')
		];
	}

	public static function getInquiryTypeOptionsQueries(): array
	{
		return [
			'enquiry' => " `ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_REQUEST."' ",
			'inquiry' => " `ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' ",
		];
	}

	protected function getImportService(): \Ts\Service\Import\AbstractImport
	{
		return new \Ts\Service\Import\Result();
	}

	protected function getImportDialogId(): string
	{
		return 'RESULT_IMPORT_';
	}

	protected function addSettingFields(\Ext_Gui2_Dialog $oDialog): void
	{
		$oRow = $oDialog->createRow($this->t('E-Mail für Kunden übernehmen, wenn keine E-Mail vorhanden ist'), 'checkbox', ['db_column' => 'settings', 'db_alias' => 'add_email']);
		$oDialog->setElement($oRow);
		$oRow = $oDialog->createRow($this->t('Fehler überspringen'), 'checkbox', ['db_column' => 'settings', 'db_alias' => 'skip_errors']);
		$oDialog->setElement($oRow);
	}

	public function getUnmatchedDialog(array $unmatchedResultImportItems): Ext_Gui2_Dialog
	{
		$dialog = $this->_oGui->createDialog();
		$dialog->sDialogIDTag = 'RESULT_IMPORT_UNMATCHED_';
		$dialog->save_button = false;
		$dialog->width = 900;
		$dialog->height = 650;
		foreach ($unmatchedResultImportItems as $rowId => $item) {
			$dialog->setElement($dialog->createRow($this->t('Zeile')." ".$rowId." (".$item[0]." / ".$item[1].")", 'autocomplete', [
				'db_column' => 'autocomplete_inquiry_id_'.$rowId,
				'autocomplete' => new Ext_TS_Enquiry_Gui2_View_Autocomplete_Inquiry(\Ext_TS_Inquiry::TYPE_BOOKING_STRING),
				'autocomplete_exclude_with_storno' => true,
				'skip_value_handling' => true
			]));
		}
		return $dialog;
	}
}