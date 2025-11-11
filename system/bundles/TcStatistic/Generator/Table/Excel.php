<?php

namespace TcStatistic\Generator\Table;

use PhpOffice\PhpSpreadsheet\Shared;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style;
use PhpOffice\PhpSpreadsheet\Writer;
use TcStatistic\Model\Table;

class Excel extends AbstractTable {

	/**
	 * @var Spreadsheet
	 */
	private $oSpreadsheet;

	/**
	 * @var array
	 */
	private $aUsedCells = [];

	/**
	 * @var int
	 */
	private $iColCount = 0;

	/**
	 * @var int
	 */
	private $iRowContinuous = 0;

	/**
	 * @var string
	 */
	private $sTitle;

	/**
	 * @var string
	 */
	private $sFileName;

	/**
	 * @var string|Writer\BaseWriter
	 */
	private $sSpreadsheetWriter = Writer\Xlsx::class;

	/**
	 * @var bool
	 */
	private $bTablePerWorksheet = false;

	/**
	 * Rowspan und Colspan berechnen
	 *
	 * PHPExcel kann das nicht von selbst. Wenn A1 ein Rowspan hat und in der nächsten Zeile die
	 * Zelle in B2 stehen müsste, schreibt PHPExcel diese einfach in A2, daher muss der Versatz,
	 * der durch rowspan und colspan erzeugt wird, hier manuell berechnet werden. Da man
	 * rowspan und colspan in einer Zelle kombinieren kann, wird es noch komplizierter,
	 * denn dann muss das betroffene Rechteck ausgerechnet werden.
	 *
	 * @param int $iCol
	 * @param int $iRow
	 * @param Table\Cell $oCell
	 */
	private function mergeCells($iCol, $iRow, Table\Cell $oCell) {

		if(
			empty($oCell->getRowspan()) &&
			empty($oCell->getColspan())
		) {
			return;
		}

		$iMergeColFrom = PHP_INT_MAX;
		$iMergeRowFrom = PHP_INT_MAX;
		$iMergeColTo = 0;
		$iMergeRowTo = 0;

		if(!empty($oCell->getRowspan())) {
			// Zellen-Range ermitteln, der durch den Rowspan kommt
			$iMergeColFrom = $iCol;
			$iMergeRowFrom = $iRow;
			$iMergeColTo = $iCol;
			$iMergeRowTo = $iRow + $oCell->getRowspan() - 1;
		}

		if(!empty($oCell->getColspan())) {
			// Zellen-Range ermitteln, der durch den Colspan kommt (in Kombination mit Rowspan)
			$iMergeColFrom = min($iMergeColFrom, $iCol);
			$iMergeRowFrom = min($iMergeRowFrom, $iRow);
			$iMergeColTo = max($iMergeColTo, $iCol + $oCell->getColspan() - 1);
			$iMergeRowTo = max($iMergeRowTo, $iRow);
		}

		$sMergeColFrom = \Util::getColumnCodeForExcel($iMergeColFrom);
		$sMergeColTo = \Util::getColumnCodeForExcel($iMergeColTo);

		// Merge erst hier machen, da PHPExcel aufeinander aufbauende Merges in zwei Richtungen nicht kann
		// Ergo relevant für Zellen, die colspan und rowspan gleichzeitig haben
		// +1 für Excel, da dort die Zellen erst bei 1 beginnen
		$this->oSpreadsheet->getActiveSheet()->mergeCells($sMergeColFrom.($iMergeRowFrom + 1).':'.$sMergeColTo.($iMergeRowTo + 1));

		// Zellen ermitteln, die durch Rowspan betroffen sind
		for($iRowSpanOffset=$iRow; $iRowSpanOffset < ($iRow + $oCell->getRowspan()); $iRowSpanOffset++) {
			$this->aUsedCells[$iCol][$iRowSpanOffset] = 'r';
		}

		// Zellen ermitteln, die durch Colspan betroffen sind
		for($iColSpanOffset=$iCol; $iColSpanOffset < ($iCol + $oCell->getColspan()); $iColSpanOffset++) {
			$this->aUsedCells[$iColSpanOffset][$iRow] = 'c';
		}

		// Zellen ermitteln, die durch Rowspan + Colspan (das dort entstehende Rechteck) betroffen sind
		for($iRowSpanOffset=$iRow; $iRowSpanOffset < ($iRow + $oCell->getRowspan()); $iRowSpanOffset++) {
			for($iColSpanOffset=$iCol; $iColSpanOffset < ($iCol + $oCell->getColspan()); $iColSpanOffset++) {
				$this->aUsedCells[$iColSpanOffset][$iRowSpanOffset] = 'rc';
			}
		}
	}

	/**
	 * Wenn Spalte blockiert ist durch rowspan/colspan: Nächste Spalte (Versatz) ermitteln
	 *
	 * @param int $iCol
	 * @param int $iRow
	 * @return int
	 */
	private function getColOffset($iCol, $iRow) {
		for(; $iCol < $this->iColCount; $iCol++) {
			if(!isset($this->aUsedCells[$iCol][$iRow])) {
				// Zelle ist nicht blockiert
				break;
			}
		}

		return $iCol;
	}

	/**
	 * Excel generieren
	 */
	public function generate() {

		$this->oSpreadsheet = new Spreadsheet();
		$this->oSpreadsheet->setActiveSheetIndex(0);

		// Metadaten
		$sCreator = 'Fidelo '.ucfirst(\Ext_TC_Util::getSystem()).' '.\System::d('version');
		if(!empty($this->sTitle)) {
			$this->oSpreadsheet->getProperties()->setTitle($this->sTitle);
		}
		$this->oSpreadsheet->getProperties()->setCreator($sCreator);
		$this->oSpreadsheet->getProperties()->setLastModifiedBy($sCreator);

		// Maximale Anzahl der Spalten der Tabellen ermitteln (nur nötig wenn mehrere Tabellen in einem Worksheet sind)
		if(!$this->bTablePerWorksheet) {
			foreach($this->aTables as $oTable) {
				$this->iColCount = max($this->iColCount, $oTable->getMaxColCount());
			}
		}

		$aData = $this->aTables;

		// Caption ergänzen und mehrere Tabellen behandeln
		if(!$this->bTablePerWorksheet) {
			$aData = $this->manipulateData();
		}

		foreach($aData as $iSheet => $oTable) {

			if($this->bTablePerWorksheet) {
				$this->iColCount = $oTable->getMaxColCount();
				$this->oSpreadsheet->getActiveSheet()->setTitle($oTable->getCaption());
			}

			$this->generateTable($oTable);

			if(
				$this->bTablePerWorksheet &&
				isset($aData[$iSheet + 1])
			) {
				$this->aUsedCells = [];
				$this->iColCount = 0;
				$this->iRowContinuous = 0;
				$this->oSpreadsheet->createSheet($iSheet + 1);
				$this->oSpreadsheet->setActiveSheetIndex($iSheet + 1);
			}

		}

		$this->oSpreadsheet->setActiveSheetIndex(0);

		return $this->oSpreadsheet;
	}

	/**
	 * Tabelle generieren (was die Statistiken als Tabelle sehen)
	 *
	 * @param Table\Table $oTable
	 */
	private function generateTable(Table\Table $oTable) {

		foreach($oTable as $aRows) {

			// Rows fortlaufen lassen (für mehrere Tabellen nötig)
			$iRow = $this->iRowContinuous++;

			// Excel arbeitet nicht mit 0, sondern beginnt mit 1
			$iRowExcel = $iRow + 1;

			foreach($aRows as $iCol => $oCell) {
				/** @var Table\Cell $oCell */

				// Versatz durch mögliches rowspan berücksichtigen
				$iColOffset = $this->getColOffset($iCol, $iRow);
				$sColOffsetLetter = \Util::getColumnCodeForExcel($iColOffset);

				// Zellen mergen
				$this->mergeCells($iColOffset, $iRow, $oCell);

				// Style der Zelle setzen
				$aCellStyles = $this->getCellStyles($oCell);
				if(!empty($aCellStyles)) {
					$this->oSpreadsheet->getActiveSheet()->getStyle($sColOffsetLetter.$iRowExcel)->applyFromArray($aCellStyles);
				}

				// Wert ggf. formatieren
				$this->formatCellValue($oCell);

				// Einstellungen für das Nummernformat der Zelle setzen
				$aNumberFormatSettings = $this->getCellNumberFormat($oCell);
				if(!empty($aNumberFormatSettings)) {
					$this->oSpreadsheet->getActiveSheet()->getStyle($sColOffsetLetter.$iRowExcel)->getNumberFormat()->applyFromArray($aNumberFormatSettings);
				}

				// Wert setzen
				$this->oSpreadsheet->getActiveSheet()->setCellValue($sColOffsetLetter.$iRowExcel, $oCell->getValue());
				// Zelle als verwendet markiert, damit Offsets von nachfolgenden Spalten bei rowspan korrekt funktionieren
				$this->aUsedCells[$iColOffset][$iRow] = 'v';

				$this->oSpreadsheet->getActiveSheet()->getColumnDimension($sColOffsetLetter)->setAutoSize(true);

				if(!empty($oCell->getComment())) {
					$oComment = $this->oSpreadsheet->getActiveSheet()->getComment($sColOffsetLetter.$iRowExcel);
					$oComment->getText()->createTextRun($oCell->getComment());
					// TODO Je nach String-Länge unterschiedliche Größen
					$oComment->setWidth('192pt'); // Doppelt so breit wie normal
					$oComment->setHeight('111pt'); // Doppelt so hoch wie normal
				}

			}
		}

	}

	/**
	 * Styles für die Zelle ermitteln und für PHPExcel konvertieren
	 *
	 * @param Table\Cell $oCell
	 * @return array
	 */
	protected function getCellStyles(Table\Cell $oCell) {

		$aCellStyle = [];

		if($oCell->hasFontStyle()) {
			foreach($oCell->getFontStyle() as $sType => $mValue) {
				$aCellStyle['font'][$sType] = $mValue;
			}
		}

		if($oCell->isHeading()) {
			$aCellStyle['font']['bold'] = true;

			if(!$oCell->hasBackground()) {
				$oCell->setBackground('#EEEEEE');
			}
		}

		// TODO Funktioniert bei colspan nicht richtig, da PHPExcel mal wieder zu doof ist, das auf die ganze Zelle anzuwenden
//		if($oCell->getBorder() !== 0) {
//			if($oCell->getBorder() & Table\Cell::BORDER_RIGHT) {
//				$aCellStyle['borders']['right']['style'] = \PHPExcel_Style_Border::BORDER_THIN;
//			}
//			if($oCell->getBorder() & Table\Cell::BORDER_BOTTOM) {
//				$aCellStyle['borders']['bottom']['style'] = \PHPExcel_Style_Border::BORDER_THIN;
//			}
//		}

		if($oCell->hasBackground()) {
			$sColor = ltrim($oCell->getBackground(), '#');
			$aCellStyle['fill'] = [
				'fillType' => Style\Fill::FILL_SOLID,
				'color' => [
					'rgb' => $sColor
				]
			];
		}

		// Wenn Formatierung mit Zahl, null nicht formatieren, aber Zelle hat Ersatzwert:
		// Feld rechtsbündig machen, da alle normalen Nummernfelder auch rechtsbündig sind
		if(
			!$oCell->hasValue() &&
			strpos($oCell->getFormat(), 'number') !== false &&
			$oCell->getNullValueFormatting() === false &&
			$oCell->getNullValueReplace() !== null
		) {
			$aCellStyle['alignment'] = [
				'horizontal' => Style\Alignment::HORIZONTAL_RIGHT
			];
		}

		// text-align
		if(!empty($oCell->getAlignment())) {
			$aCellStyle['alignment'] = [
				'horizontal' => $oCell->getAlignment()
			];
		}

		if ($oCell->shouldKeepLineBreaks()) {
			$aCellStyle['alignment'] = [
				'wrapText' => true
			];
		}

		return $aCellStyle;

	}

	/**
	 * Nummernformat für die Zelle ermitteln und für PHPExcel konvertieren
	 *
	 * @param Table\Cell $oCell
	 * @return array
	 */
	protected function getCellNumberFormat(Table\Cell $oCell) {

		$aNumberFormatSettings = [];

		// Währung setzen
		if($oCell->hasCurrency()) {
			/** @var \Ext_TC_Currency|\Ext_Thebing_Currency $oCurrency */
			$oCurrency = \Factory::getInstance('Ext_TC_Currency', $oCell->getCurrency());

			if($oCurrency->hasLeftBoundSign()) {
				$aNumberFormatSettings['formatCode'] = $oCurrency->getSign().' #,##0.00;[Red]'.$oCurrency->getSign().' -#,##0.00';
			} else {
				$aNumberFormatSettings['formatCode'] = '#,##0.00_ '.$oCurrency->getSign().';[Red]-#,##0.00_ '.$oCurrency->getSign();
			}
		} elseif(
			$oCell->getFormat() === 'number_percent' ||
			$oCell->getFormat() === 'number_percent_color'
		) {
			$sColor = '';
			if($oCell->getFormat() === 'number_percent_color') {
				$sColor = '[Red]';
			}

			$aNumberFormatSettings['formatCode'] = " #,##0.00 %;{$sColor} -#,##0.00 %";

		} elseif(
			$oCell->getFormat() === 'date' &&
			$oCell->hasValue()
		) {
			// Format sollte von Excel automatisch an lokale Gegebenheiten angepasst werden
			$aNumberFormatSettings['formatCode'] = Style\NumberFormat::FORMAT_DATE_DDMMYYYY;
		} elseif(
			$oCell->getFormat() === 'time' &&
			$oCell->hasValue()
		) {
			$aNumberFormatSettings['formatCode'] = Style\NumberFormat::FORMAT_DATE_TIME3;
		}

		return $aNumberFormatSettings;

	}

	/**
	 * Zellenwert umformatieren
	 *
	 * @param Table\Cell $oCell
	 */
	protected function formatCellValue(Table\Cell $oCell) {

		// Wenn Formatklasse für eine Zahl angegeben wurde
		if(strpos($oCell->getFormat(), 'number') !== false) {

			if(!$oCell->hasValue()) {

				if(
					(
						$oCell->getFormat() === 'number_float' ||
						$oCell->getFormat() === 'number_percent' ||
						$oCell->getFormat() === 'number_percent_color' ||
						$oCell->getFormat() === 'number_amount'
					) &&
					$oCell->getNullValueFormatting() === false
				) {
					// Nichts tun, da es hier sein kann, dass es keinen Ausgangswert gibt und das hier nicht auf 0 gestellt werden darf
					if($oCell->getNullValueReplace() !== null) {
						// TODO Das läuft so nicht analog zur HTML-Klasse
						// Keine Ahnung was Excel bei einer Formel macht, wenn hier keine Zahl drin steht und das Feld nicht leer ist
						$oCell->setValue($oCell->getNullValueReplace());
					}
				} else {
					// Werte, welche eine Number-Formatklasse haben, aber leer sind, auf 0 setzen
					$oCell->setValue(0);
				}

			} else {
				if(is_numeric($oCell->getValue())) {
					// Fließkomma-Ungenauigkeit interessiert PHPExcel nicht, daher Zahl immer runden
					$oCell->setValue(round($oCell->getValue(), 6));
				}
			}

			if(
				$oCell->hasValue() && (
					$oCell->getFormat() === 'number_percent' ||
					$oCell->getFormat() === 'number_percent_color'
				)
			) {
				// http://stackoverflow.com/a/19243659
				// http://php.net/manual/en/class.numberformatter.php
				if(is_numeric($oCell->getValue())) {
					$oCell->setValue($oCell->getValue() / 100);
				}
			}

		} elseif(
			$oCell->hasValue() &&
			$oCell->getFormat() === 'date' ||
			$oCell->getFormat() === 'time'
		) {
			if(
				!$oCell->getValue() instanceof \DateTime &&
				!$oCell->bExcelConverted
			) {
				throw new \UnexpectedValueException('Given '.$oCell->getFormat().' cell has not a DateTime object!');
			}

			// DateTime zu irgendwas Komischen (Excel) umkonvertieren
			$oCell->setValue(Shared\Date::PHPToExcel($oCell->getValue()));
			$oCell->bExcelConverted = true;
		}

	}

	/**
	 * Titel für Metadaten
	 *
	 * @param $sTitle
	 */
	public function setTitle($sTitle) {
		$this->sTitle = $sTitle;
	}

	/**
	 * Dateinamen setzen für Export (Download)
	 *
	 * @param string $sFileName
	 */
	public function setFileName($sFileName) {
		// Außerdem trim() benutzen, da hier auch mal Zeilenumbrüche reinkommen könnten…
		$this->sFileName = trim(\Util::getCleanFilename($sFileName));
	}

	/**
	 * Wenn mehrere Tabellen oder Tabellenüberschrift: Daten manipulieren, da eigentlich alles eine Tabelle ist
	 *
	 * @return Table\Table[]
	 */
	private function manipulateData() {

		if(
			count($this->aTables) === 1 &&
			!$this->aTables[0]->hasCaption()
		) {
			return $this->aTables;
		}

		$aData = [];
		foreach($this->aTables as $oOriginalTable) {
			$oTable = clone $oOriginalTable;
			$aData[] = $oTable;

			if($oTable->hasCaption()) {
				$oRow = new Table\Row();
				$oCell = new Table\Cell($oTable->getCaption());
				$oCell->setFontStyle('bold');
				$oCell->setColspan($this->iColCount);
				$oRow[] = $oCell;

				$aTableRows = $oTable->getArrayCopy();
				array_unshift($aTableRows, $oRow);
				$oTable->exchangeArray($aTableRows);
			}

			for($i = 0; $i < 2; $i++) {
				$oRow = new Table\Row();
				$oCell = new Table\Cell();
				$oCell->setColspan($this->iColCount);
				$oRow[] = $oCell;
				$oTable[] = $oRow;
			}

		}

		return $aData;

	}

	/**
	 * @return Spreadsheet
	 */
	public function getSpreadsheetObject() {
		return $this->oSpreadsheet;
	}

	/**
	 * @param Spreadsheet $oExcel
	 */
	public function setSpreadsheetObject(Spreadsheet $oExcel) {
		$this->oSpreadsheet = $oExcel;
	}

	/**
	 * @param string $sWriter
	 */
	public function setSpreadsheetWriter($sWriter) {
		$this->sSpreadsheetWriter = $sWriter;
	}

	/**
	 * Jede Tabelle in ein eigenes Excel-Worksheet generieren
	 */
	public function setTablePerWorkSheet() {
		$this->bTablePerWorksheet = true;
	}

	/**
	 * @return string
	 */
	private function getFileExtension() {
		switch($this->sSpreadsheetWriter) {
			case Writer\Xls::class:
				return 'xls';
			case Writer\Xlsx::class:
				return 'xlsx';
			default:
				throw new \RuntimeException('Unknown writer: '.$this->sSpreadsheetWriter);
		}
	}

	/**
	 * Excel direkt im Browser ausgeben (Zum Download anbieten)
	 */
	public function render() {

		if(empty($this->sFileName)) {
			throw new \InvalidArgumentException('No filename set!');
		}

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="'.$this->sFileName.'.'.$this->getFileExtension().'"');
		header('Cache-Control: max-age=0');

		/** @var Writer\BaseWriter $oWriter */
		$oWriter = new $this->sSpreadsheetWriter($this->oSpreadsheet);
		$oWriter->save('php://output');

	}

	/**
	 * Excel als Datei speichern
	 */
	public function save($path) {

		if(empty($this->sFileName)) {
			throw new \InvalidArgumentException('No filename set!');
		}

		$filePath = $path.$this->sFileName.'.'.$this->getFileExtension();
		
		/** @var Writer\BaseWriter $oWriter */
		$oWriter = new $this->sSpreadsheetWriter($this->oSpreadsheet);
		$oWriter->save($filePath);
	
		return $filePath;
	}

}
