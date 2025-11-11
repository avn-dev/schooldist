<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Ext_TS_Marketing_Feedback_Questionary_Process_Gui2_ExtendedExport {

	/**
	 * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
	 */
	private $oSpreadsheet;

	/**
	 * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
	 */
	private $oSheet;

	/**
	 * @var int
	 */
	private $iRow;

	/**
	 * @var array Struktur pro Fragebogen, direkt aus dem Questionary-Generator
	 */
	private $aQuestionaryStructure = [];

	/**
	 * @var string[] Name des Schülers pro Feedback-Prozess
	 */
	private $aQuestionaryProcessLabels = [];

	/**
	 * @var array Process-Results gruppiert nach Frage-zu-Fragebogen (Multiselect in den Childs)
	 */
	private $aGroupedProcessData = [];

	/**
	 * @param array $aGuiData
	 * @return Spreadsheet
	 */
	public function generate(array $aGuiData) {

		$oFormatUsername = new Ext_Gui2_View_Format_Name();

		foreach($aGuiData as $aRow) {

			$oQuestionaryProcess = Ext_TC_Marketing_Feedback_Questionary_Process::getInstance($aRow['id']);
			if(!Core\Helper\DateTime::isDate($oQuestionaryProcess->getData('answered'), 'Y-m-d H:i:s')) {
				continue;
			}

			if(!isset($this->aQuestionaryStructure[$oQuestionaryProcess->questionary_id])) {
				$oQuestionary = $oQuestionaryProcess->getQuestionary();
				/** @var Ext_TC_Marketing_Feedback_Questionary_Generator $oQuestionaryGenerator */
				$sQuestionaryGenerator = Ext_TC_Factory::getClassName('Ext_TC_Marketing_Feedback_Questionary_Generator');
				$oQuestionaryGenerator = new $sQuestionaryGenerator(null, $oQuestionary, System::getInterfaceLanguage());
				$this->aQuestionaryStructure[$oQuestionaryProcess->questionary_id] = $oQuestionaryGenerator->generate();
			}

			$aProcessResults = $oQuestionaryProcess->getResults();
			foreach($aProcessResults as $oProcessResult) {
				$this->aGroupedProcessData[$oProcessResult->questionary_question_group_question_id][] = $oProcessResult;
			}

			$this->aQuestionaryProcessLabels[$oQuestionaryProcess->id] = $aRow['customer_number'] . ' ' . $oFormatUsername->formatByResult($aRow);
			if(System::d('debugmode') == 2) {
				$this->aQuestionaryProcessLabels[$oQuestionaryProcess->id] .= ' (' . $oQuestionaryProcess->id . ')';
			}

		}

		$this->generateExcel();

		return $this->oSpreadsheet;

	}

	private function generateExcel() {

		$this->oSpreadsheet = new Spreadsheet();

		$iCurrentQuestionaryId = key($this->aQuestionaryStructure);
		foreach($this->aQuestionaryStructure as $iQuestionaryId => $aChilds) {

			// Pro Fragebogen eigenes Sheet
			if($iCurrentQuestionaryId !== $iQuestionaryId) {
				$this->oSpreadsheet->createSheet();
				$this->oSpreadsheet->setActiveSheetIndex($this->oSpreadsheet->getSheetCount() - 1);
				$iCurrentQuestionaryId = $iQuestionaryId;
			}

			$oQuestionary = Ext_TC_Marketing_Feedback_Questionary::getInstance($iQuestionaryId);

			$this->oSheet = $this->oSpreadsheet->getActiveSheet();
			$this->oSheet->setTitle(Ext_TC_Util::escapeExcelSheetTitle($oQuestionary->name));

			$this->iRow = 1;
			foreach($aChilds as $aChild) {
				if(isset($aChild['heading'])) {
					$this->addHeadingRow($aChild);
				} else {
					$this->addQuestion($aChild);
				}
			}

			$this->oSheet->getColumnDimension('A')->setAutoSize(true);
			$this->oSheet->getColumnDimension('B')->setAutoSize(true);
			$this->oSheet->getStyle('B1:B' . $this->iRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

		}

		$this->oSpreadsheet->setActiveSheetIndex(0);

	}

	/**
	 * @param array $aChild
	 */
	private function addQuestion(array $aChild) {

		$this->addCaptionRow($aChild['questionText'], ['font' => ['bold' => true]]);

		// Vorhandene Abhängigkeiten der Frage sammeln für Sortierung
		$aDependencies = [];
		$aDependencyLabels = [];
		foreach((array)$this->aGroupedProcessData[$aChild['questionGroupQuestionId']] as $oProcessResult) {
			$aDependencyLabels[$oProcessResult->dependency_id] = \Ext_TS_Marketing_Feedback_Question::getDependencyLabel($aChild['questionDependencyOn'], $oProcessResult->dependency_id);
			$aDependencies[$oProcessResult->dependency_id][] = $oProcessResult;
		}

		uksort($aDependencies, function ($iDependencyId1, $iDependencyId2) use ($aDependencyLabels) {
			return strnatcmp($aDependencyLabels[$iDependencyId1], $aDependencyLabels[$iDependencyId2]);
		});

		foreach($aDependencies as $iDependencyId => $aProcessResults) {
			/** @var Ext_TC_Marketing_Feedback_Questionary_Process_Result[] $aProcessResults */
			$this->iRow++;

			// Überschrift Provider – nur bei Abhängigkeit
			if(!empty($iDependencyId)) {
				$sDependencyLabel = $aDependencyLabels[$iDependencyId];
				if(System::d('debugmode') == 2) {
					$sDependencyLabel .= ' (' . $iDependencyId . ')';
				}

				$this->addCaptionRow($sDependencyLabel, ['font' => ['italic' => true]]);
			}

			$aCustomerLabels = $this->aQuestionaryProcessLabels;
			usort($aProcessResults, function ($oProcessResult1, $oProcessResult2) use($aCustomerLabels) {
				return strnatcmp($aCustomerLabels[$oProcessResult1->questionary_process_id], $aCustomerLabels[$oProcessResult2->questionary_process_id]);
			});

			foreach($aProcessResults as $oProcessResult) {
				$sAnswer = html_entity_decode((string)$oProcessResult->getAnswer($aChild), ENT_QUOTES, 'UTF-8');
				$this->oSheet->setCellValue('A' . $this->iRow, $aCustomerLabels[$oProcessResult->questionary_process_id]);
				$this->oSheet->setCellValue('B' . $this->iRow, $sAnswer);
				$this->iRow++;
			}
		}

		$this->iRow++;

	}

	public function send() {

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="feedback_export.xlsx"');
		header('Cache-Control: max-age=0');

		$oWriter = new Xlsx($this->oSpreadsheet);
		$oWriter->save('php://output');

	}

	/**
	 * @param string $sValue
	 * @param array $aStyleArray
	 */
	private function addCaptionRow($sValue, array $aStyleArray) {

		// HTML in Fragen entfernen
		$sValue = strip_tags(html_entity_decode($sValue, ENT_QUOTES, 'UTF-8'));

		$this->oSheet->setCellValue('A' . $this->iRow, $sValue);
		$this->oSheet->getStyle('A' . $this->iRow)->applyFromArray($aStyleArray);
		$this->oSheet->mergeCells('A' . $this->iRow . ':B' . $this->iRow . '');
		$this->iRow++;

	}

	/**
	 * @param array $aChild
	 */
	private function addHeadingRow(array $aChild) {

		// Größen stammen aus Formatvorlagen von MS Excel
		$iSize = 13;
		if($aChild['heading']['type'] === 'h1') {
			$iSize = 18;
		} elseif($aChild['heading']['type'] === 'h2') {
			$iSize = 15;
		}

		$aStyleArray = [
			'font' => [
				'size' => $iSize,
				'bold' => true
			]
		];

		$this->addCaptionRow($aChild['heading']['text'], $aStyleArray);
		$this->iRow++;

	}

}
