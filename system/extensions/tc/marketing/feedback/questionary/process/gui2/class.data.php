<?php

abstract class Ext_TC_Marketing_Feedback_Questionary_Process_Gui2_Data extends Ext_TC_Gui2_Data {

	/**
	 * @param Ext_TC_Gui2 $oGui
	 * @return Ext_Gui2_Dialog
	 */
	public static function getDialog(Ext_TC_Gui2 $oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Feedback von "{customer_name}"'));

		return $oDialog;
	}

	/**
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param $aSelectedIds
	 * @param bool $sAdditional
	 * @return array
	 */
	protected function getEditDialogHTML(&$oDialog, $aSelectedIds, $sAdditional = false) {

		$oDialog->save_button = false;

		$oDialog->aElements = array();
		if(!$this->oWDBasic) {
			$this->_getWDBasicObject($aSelectedIds);
		}

		$oTab = $oDialog->createTab(L10N::t('Ergebnisse', $oDialog->oGui->gui_description));

		/** @var $oQuestionaryProcess Ext_TC_Marketing_Feedback_Questionary_Process */
		$oQuestionaryProcess = $this->oWDBasic;
		$oQuestionary = $oQuestionaryProcess->getQuestionary();
		$oJourney = $oQuestionaryProcess->getJourney();

		/** @var Ext_TC_Marketing_Feedback_Questionary_Generator $oQuestionaryGenerator */
		$sQuestionaryGenerator = Ext_TC_Factory::getClassName('Ext_TC_Marketing_Feedback_Questionary_Generator');
		$oQuestionaryGenerator = new $sQuestionaryGenerator($oJourney->getInquiry(), $oQuestionary, System::getInterfaceLanguage());
		$oQuestionaryGenerator->setSubDependencyFilter($this->getDialogDependency());
		$aGeneratorResults = $oQuestionaryGenerator->generate();

		$aProcessResults = array();
		$aOriginalProcessResults = $oQuestionaryProcess->getResults();
		foreach($aOriginalProcessResults as $oOriginalProcessReults) {
			$aProcessResults[$oOriginalProcessReults->questionary_question_group_question_id][$oOriginalProcessReults->dependency_id] = $oOriginalProcessReults;
		}

		$oAccordion = new Ext_Gui2_Dialog_Accordion('accordion_questionary_results');
		
		foreach($aGeneratorResults as $iGeneratorResultKey => $aGeneratorResult) {

			if(
				(
					isset($aGeneratorResult['parentId']) &&
					$aGeneratorResult['parentId'] == 0
				) ||
				(
					isset($aGeneratorResult['heading']) &&
					// Headings ohne Childs nicht anzeigen
					empty($aGeneratorResult['heading']['showAlways']) &&
					$aGeneratorResult['heading']['parentId'] == 0
				)
			) {

				if(isset($aGeneratorResult['heading'])) {

					
					$oDivElement = $this->createElement($iGeneratorResultKey, $aGeneratorResults, $aProcessResults, $oDialog);
					$oAccordionElement = $oAccordion->createElement($aGeneratorResult['heading']['text']);
					$oAccordionElement->setContent($oDivElement->generateHTML());
					$oAccordion->addElement($oAccordionElement);
					

				} else {

					$oDivElement = $this->createElement($iGeneratorResultKey, $aGeneratorResults, $aProcessResults, $oDialog);
					$oTab->setElement($oDivElement);

				}

			}

		}

		$oTab->setElement($oAccordion);
		
		$oDialog->setElement($oTab);

		$this->generateNoticeTabContent($oDialog);

		$aData = parent::getEditDialogHTML($oDialog, $aSelectedIds, $sAdditional);

		return $aData;
	}

	/**
	 * @return array
	 */
	protected function getDialogDependency() {
		return null;
	}

	/**
	 * Erstellt ein Div mit allen Kind-Elementen, sofern Kind-Elemente
	 * vorhanden sind. Sollte es kein Kind-Element geben, wird das übergebene
	 * Child Objekt im Div erstellt.
	 *
	 * @param int $iCurrentGeneratorResult
	 * @param array $aGeneratorResults
	 * @param Ext_TC_Marketing_Feedback_Questionary_Process_Result[] $aProcessResults
	 * @param Ext_Gui2_Dialog $oDialog
	 * @return Ext_Gui2_Html_Div
	 */
	private function createElement($iCurrentGeneratorResult, array $aGeneratorResults, array $aProcessResults, Ext_Gui2_Dialog $oDialog) {

		$oDiv = $oDialog->create('div');

		$aQuestionaryChilds = array();

		// Alle Elemente chronologisch durchlaufen und anhand parentId ermitteln, welche Elemente dieser Ebene gehören
		for($iCnt = ($iCurrentGeneratorResult + 1); $iCnt < count($aGeneratorResults); $iCnt++) {

			if(
				(
					isset($aGeneratorResults[$iCnt]['parentId']) &&
					$aGeneratorResults[$iCnt]['parentId'] > 0
				) ||
				(
					isset($aGeneratorResults[$iCnt]['heading']) &&
					$aGeneratorResults[$iCnt]['heading']['parentId'] > 0
				)
			) {
				$aQuestionaryChilds[] = $aGeneratorResults[$iCnt];
			} else {
				break;
			}

		}

		if(empty($aQuestionaryChilds)) {
			$aQuestionaryChilds[] = $aGeneratorResults[$iCurrentGeneratorResult];
		}

		foreach($aQuestionaryChilds as $iQuestionaryChildKey => $aQuestionaryChild) {

			if(isset($aQuestionaryChild['heading'])) {

				$oHeading = $oDialog->create($aQuestionaryChild['heading']['type']);
				$oHeading->setElement($aQuestionaryChild['heading']['text']);
				$oDiv->setElement($oHeading);

			} else {

				$oDiv->setElement($oDialog->create('h4')->setElement($aQuestionaryChild['questionText']));

				/** @var Ext_TC_Marketing_Feedback_Questionary_Process_Result[] $aProcessResultEntries */
				$aProcessResultEntries = $aProcessResults[$aQuestionaryChild['questionGroupQuestionId']];

				if($aProcessResultEntries) {
					if(
						// Immer anzeigen, weil man z.B. bei nur einen Teacher gar nicht weiß, wer bewertet wurde
						//count($aQuestionaryChild['columns']) > 1 &&
						!empty($aQuestionaryChild['columns'][0]['title'])
					) {
						$sTableHtml = $this->getTable($aQuestionaryChild, $aProcessResultEntries);
						$oDiv->setElement($sTableHtml);
					} else {
						$sAnswer = $this->getAnswer($aQuestionaryChild, reset($aProcessResultEntries));
						$oDiv->setElement($sAnswer);
					}
				}

			}

		}

		return $oDiv;
	}

	/**
	 * Gibt einen String als HTML-Tabelle zurück
	 *
	 * @param array $aQuestionaryChild
	 * @param Ext_TC_Marketing_Feedback_Questionary_Process_Result[] $aProcessResultEntries
	 * @return string
	 */
	private function getTable(array $aQuestionaryChild, array $aProcessResultEntries) {

		$sDebug = 'data-question-id="'.$aQuestionaryChild['questionId'].'" data-question-group-question-id="'.$aQuestionaryChild['questionGroupQuestionId'].'"';
		$sTableHtml = '<table class="table" '.$sDebug.'><thead>';

		foreach($aQuestionaryChild['columns'] as $aColumn) {
			$sTableHtml .= '<th>' . $aColumn['title'] . '</th>';
		}

		$sTableHtml .= '</thead><tbody><tr>';

		foreach($aQuestionaryChild['columns'] as $aColumn) {
			$sAnswer = $sDebug = '';
			if(isset($aProcessResultEntries[$aColumn['dependencyId']])) {
				/** @var Ext_TC_Marketing_Feedback_Questionary_Process_Result $oProcessResultEntry */
				$oProcessResultEntry = $aProcessResultEntries[$aColumn['dependencyId']];
				$sDebug = ' data-dependency-id="'.$oProcessResultEntry->id.'"';
				$sAnswer = $this->getAnswer($aQuestionaryChild, $oProcessResultEntry);
			}
			$sTableHtml .= '<td'.$sDebug.'>' . $sAnswer . '</td>';
		}

		$sTableHtml .= '</tr></tbody></table>';

		return $sTableHtml;
	}

	/**
	 * Holt die Antworten der Fragen anhand des Question Type
	 *
	 * @param array $aQuestionaryChild
	 * @param Ext_TC_Marketing_Feedback_Questionary_Process_Result $oProcessResultEntry
	 * @return string
	 */
	private function getAnswer(array $aQuestionaryChild, Ext_TC_Marketing_Feedback_Questionary_Process_Result $oProcessResultEntry) {

		$sAnwser = '';

		switch($aQuestionaryChild['questionType']) {
			case 'yes_no':
				$oFormat = new Ext_TC_Gui2_Format_YesNo();
				$sAnwser = $oFormat->format($oProcessResultEntry->answer);
				break;
			case 'rating':
				$oRating = Ext_TC_Marketing_Feedback_Rating::getInstance($aQuestionaryChild['questionRatingId']);
				$oRatingChild = $oRating->getChildByRating($oProcessResultEntry->answer);

				// Da ein Rating-Feld kein Pflichtfeld sein muss, kann das hier auch null sein #9188
				if($oRatingChild !== null) {
					$sAnwser = $oRatingChild->getName();
				}

				break;
			case 'textfield':
				$sAnwser = $oProcessResultEntry->answer;
				break;
			case 'stars':
				for($iCnt = 1; $iCnt <= $oProcessResultEntry->answer; $iCnt++) {
					$sAnwser .= '&#9733;';
				}
				break;
		}

		return $sAnwser;
	}

	/**
	 * Baut den Inhalt für das Tab Notizen in den Dialog
	 *
	 * @param Ext_Gui2_Dialog $oDialog
	 * @throws Exception
	 */
	public function generateNoticeTabContent(Ext_Gui2_Dialog $oDialog) {

		$oTab = $oDialog->createTab(L10N::t('Notizen', $oDialog->oGui->gui_description));

		$oTab->setElement($oDialog->createRow(L10N::t('Feedback wurde gelesen', $oDialog->oGui->gui_description), 'checkbox', array(
			'db_alias' => 'tc_fqp',
			'db_column' => 'read_feedback'
		)));

		$aUsers = Factory::executeStatic('Ext_TC_Marketing_Feedback_Questionary_Process_Gui2_Data', 'getUserSelectOptions', [false]);

		$oTab->setElement($oDialog->createRow(L10N::t('Zuordnen an', $oDialog->oGui->gui_description), 'select', [
			'db_alias' => 'tc_fqp',
			'db_column' => 'assigned_to',
			'select_options' => $aUsers,
		]));

		$oTab->setElement($oDialog->create('h4')->setElement(L10N::t('Nachhaken', $oDialog->oGui->gui_description)));

		$oDateFormat = Factory::getObject('Ext_TC_Gui2_Format_Date');

		$oTab->setElement($oDialog->createRow(L10N::t('Nachhaken'), 'calendar', array(
			'db_alias' => 'tc_fqp',
			'db_column' => 'follow_up',
			'format' => new $oDateFormat
		)));

		$oJoinContainer = $oDialog->createJoinedObjectContainer('notice', array('min' => 1, 'max' => 20));

		$oJoinContainer->setElement($oJoinContainer->createRow(L10N::t('Notizen', $oDialog->oGui->gui_description), 'html', array(
			'db_alias' => 'tc_fqpn',
			'db_column' => 'commentary'
		)));

		$oTab->setElement($oJoinContainer);

		$oDialog->setElement($oTab);

		$oDialog->save_button = true;

	}

	/**
	 * Gibt die Optionen für den Nachhake-Filter
	 *
	 * @param Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getFilterOptions(Ext_Gui2 $oGui) {

		$aReturn = [
			'yes' => $oGui->t('Ja'),
			'no' => $oGui->t('Nein'),
			'due_followups' => $oGui->t('Fällig')
		];

		return $aReturn;

	}

	/**
	 * @param bool $bForFilter
	 * @return mixed[]
	 */
	public static function getUserSelectOptions($bForFilter = true) {
		return [];
	}

}
