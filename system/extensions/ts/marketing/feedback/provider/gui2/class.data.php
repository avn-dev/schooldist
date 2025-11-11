<?php

class Ext_TS_Marketing_Feedback_Provider_Gui2_Data extends Ext_TS_Marketing_Feedback_Questionary_Process_Gui2_Data {

	public function switchAjaxRequest($_VARS) {
		if($_VARS['action'] == 'extended_export') {
			$this->createExtendedExport();
			die();
		} else {
			parent::switchAjaxRequest($_VARS);
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function _buildQueryParts(&$sSql, &$aSql, &$aSqlParts, &$iLimit) {

		parent::_buildQueryParts($sSql, $aSql, $aSqlParts, $iLimit);

		// Funktioniert nur so, da Tabellenstruktur zu komplex ist für foreign_key/join_table
		$aSql['dependency_id'] = (int)reset($this->getParentGuiIds());
		$aSql['dependency_on'] = $this->getDependencyTypes();

		$aSqlParts['where'] .= " AND
			`tc_fqpr`.`dependency_id` = :dependency_id AND
			`tc_f_q`.`dependency_on` IN (:dependency_on)
		";

	}

	/**
	 * @inheritdoc
	 */
	protected function getDialogDependency() {
		return array_fill_keys($this->getDependencyTypes(), (int)reset($this->getParentGuiIds()));
	}

	/**
	 * @inheritdoc
	 */
	public function generateNoticeTabContent(Ext_Gui2_Dialog $oDialog) {
		// Keinen Tab für Notizen anzeigen
	}

	/**
	 * @return array
	 */
	public static function getOrderby() {
		return ['answered' => 'desc'];
	}

	/**
	 * @return array
	 */
	private function getDependencyTypes() {
		switch($this->_oGui->set) {
			case 'accommodation':
				return ['accommodation_provider'];
			case 'teacher':
				return ['teacher', 'teacher_course'];
			default:
				throw new \InvalidArgumentException('Unknown set: '.$this->_oGui->set);
		}
	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @param string $sSet
	 * @return Ext_Gui2_Dialog
	 */
	public static function createProviderDialog(Ext_Gui2 $oGui, $sSet) {

		$oDialog = $oGui->createDialog($oGui->t('Feedback für "{name}"'), $oGui->t('Feedback für "{name}"'));

		$oFactory = new Ext_Gui2_Factory('ts_feedback_provider');
		$oGuiChild = $oFactory->createGui($sSet);
		$oGuiChild->setParent($oGui);

		$oDialog->setElement($oGuiChild);

		return $oDialog;

	}

	/**
	 * Erweiterter Export: Export mit allen Schülern im Zeitraum und allen Fragen,
	 * welche die selektierten Anbieter haben
	 */
	private function createExtendedExport() {

		$aDependency = $this->getDialogDependency();
		$aTableData = $this->getTableQueryData([], [], [], true);
		$aData = $aTableData['data'];
		$aQuestions = [];

		foreach($aData as &$axRow) {

			$oQuestionaryProcess = Ext_TC_Marketing_Feedback_Questionary_Process::getInstance($axRow['id']);
			$aProcessResults = $oQuestionaryProcess->getResults();
			$oQuestionary = $oQuestionaryProcess->getQuestionary();
			$oJourney = $oQuestionaryProcess->getJourney();

			$axRow['invited'] = Ext_Thebing_Format::LocalDate($axRow['invited']);
			$axRow['started'] = Ext_Thebing_Format::LocalDate($axRow['started']);
			$axRow['answered'] = Ext_Thebing_Format::LocalDate($axRow['answered']);

			// TODO Kann man vielleicht so optimieren, dass jeder Fragebogen nur einmal generiert werden muss ($oInquiry = null)
			/** @var Ext_TC_Marketing_Feedback_Questionary_Generator $oQuestionaryGenerator */
			$sQuestionaryGenerator = Ext_TC_Factory::getClassName('Ext_TC_Marketing_Feedback_Questionary_Generator');
			$oQuestionaryGenerator = new $sQuestionaryGenerator($oJourney->getInquiry(), $oQuestionary, System::getInterfaceLanguage());
			$oQuestionaryGenerator->setSubDependencyFilter($aDependency);
			$aGeneratorResults = $oQuestionaryGenerator->generate();

			// Alle Fragen durchlaufen
			foreach($aGeneratorResults as $aGeneratorResult) {
				if(isset($aGeneratorResult['heading'])) {
					continue;
				}

				$aQuestions[$aGeneratorResult['questionId']] = $aGeneratorResult['questionText'];

				// Antworten zu Fragen matchen
				foreach($aProcessResults as $oProcessResult) {
					if(
						!empty($aGeneratorResult['columns']) &&
						$oProcessResult->questionary_question_group_question_id == $aGeneratorResult['questionGroupQuestionId'] &&
						isset($aDependency[$aGeneratorResult['questionDependencyOn']]) &&
						$oProcessResult->dependency_id == $aDependency[$aGeneratorResult['questionDependencyOn']]
					) {
						if(count($aGeneratorResult['columns']) > 1) {
							// Hier sollte bereits durch die Filter nur noch ein Eintrag existieren
							throw new RuntimeException('More than one column!');
						}

						$axRow['question_'.$aGeneratorResult['questionId']] = $oProcessResult->getAnswer($aGeneratorResult);

						break;
					}
				}
			}

		}

		$aHeader = [
			'customer_number' => $this->t('Kundennummer'),
			'group_short' => $this->t('Gruppe'),
			'customer_name' => $this->t('Name'),
			'agency_short' => $this->t('Agentur'),
			'questionary_name' => $this->t('Fragebogen'),
			'invited' => $this->t('Eingeladen'),
			'started' => $this->t('Gestartet'),
			'answered' => $this->t('Beantwortet')
		];

		foreach($aQuestions as $iQuestionId => $sQuestion) {
			$aHeader['question_'.$iQuestionId] = $sQuestion;
		}

		$oExport = new Ext_Gui2_Export_CSV('Export', $this->getCharsetForExport(), $this->getSeparatorForExport());
		$oExport->sendHeader();

		$oExport->sendLine($aHeader);

		foreach($aData as $aRow) {
			$aLine = [];
			foreach(array_keys($aHeader) as $sKey) {
				$aLine[] = $aRow[$sKey];
			}
			$oExport->sendLine($aLine);
		}

		$oExport->end();

	}

}
