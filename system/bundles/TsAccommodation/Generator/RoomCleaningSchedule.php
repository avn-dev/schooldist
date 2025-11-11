<?php

namespace TsAccommodation\Generator;

use Core\DTO\DateRange;
use TcStatistic\Generator\Table\Excel;
use TcStatistic\Model\Table;
use TsStatistic\Generator\Statistic\AbstractGenerator;

class RoomCleaningSchedule extends AbstractGenerator {

	/**
	 * @var \Closure
	 */
	private $cTranslate;

	/**
	 * @var bool
	 */
	private $bResidentalMatching;

	/**
	 * @var array
	 */
	private $aDates = [];

	/**
	 * @var Table\Table
	 */
	private $oTable;

	/**
	 * @param DateRange $oDateRange
	 * @param \Closure $cTranslate
	 * @param bool $bResidentalMatching
	 */
	public function __construct(DateRange $oDateRange, \Closure $cTranslate, $bResidentalMatching = true) {
		$this->cTranslate = $cTranslate;
		$this->bResidentalMatching = $bResidentalMatching;
		$this->aFilters['from'] = $oDateRange->from;
		$this->aFilters['until'] = $oDateRange->until;
	}

	public function getTitle() {
		return ($this->cTranslate)('Putzplan');
	}

	private function prepareData() {

		$oMatching = new \Ext_Thebing_Matching();

		$aAccommodationProviders = $oMatching->getAllFamiliesWithBeds($this->aFilters['from'], $this->aFilters['until'], !$this->bResidentalMatching);

		// Status des Raums ermitteln und Datumsspalten
		foreach($aAccommodationProviders as &$axAccommodationProvider) {

			$axAccommodationProvider['has_change'] = false;

			foreach($axAccommodationProvider['rooms'] as &$axRoomBed) {

				// Betten zählen für rowspan
				if(!isset($axAccommodationProvider['room_beds'][$axRoomBed['id']])) {
					$axAccommodationProvider['room_beds'][$axRoomBed['id']] = 0;
				}
				$axAccommodationProvider['room_beds'][$axRoomBed['id']]++;

				foreach($axRoomBed['allocation'] as &$xaAllocation) {

					$axAccommodationProvider['has_change'] = true;

					$dFrom = new \DateTime($xaAllocation['from_date']);
					$dUntil = new \DateTime($xaAllocation['until_date']);

					$xaAllocation['from_date'] = $dFrom;
					$xaAllocation['until_date'] = $dUntil;

					// Neu
					if(
						$this->aFilters['from'] <= $dFrom &&
						$this->aFilters['until'] >= $dFrom &&
						empty($axRoomBed['status'])
					) {
						if(!in_array($dFrom, $this->aDates)) {
							$this->aDates[] = $dFrom;
						}
						$axRoomBed['status'] = 'N';
					}

					// Abreise und Wechsel
					if(
						$this->aFilters['from'] <= $dUntil &&
						$this->aFilters['until'] >= $dUntil
					) {
						if(!in_array($dUntil, $this->aDates)) {
							$this->aDates[] = $dUntil;
						}
						if(!empty($axRoomBed['status'])) {
							$axRoomBed['status'] = 'C';
						} else {
							$axRoomBed['status'] = 'D';
						}
					}

					// Komplett belegt
					if(
						$this->aFilters['from'] > $dFrom &&
						$this->aFilters['until'] < $dUntil
					) {
						$axRoomBed['status'] = 'A';
					}

				}

			}
		}

		return $aAccommodationProviders;

	}

	public function generateDataTable() {

		$aAccommodationProviders = $this->prepareData();

		$oTable = new Table\Table();

		list($oRow1, $oRow2) = $this->generateHeaderRow();
		$oTable[] = $oRow1;
		$oTable[] = $oRow2;

		foreach($aAccommodationProviders as $iKey => $aAccommodationProvider) {

			// Nur Provider mit Änderung
			if(!$aAccommodationProvider['has_change']) {
				continue;
			}

			// odd/even
			$sCellBackground = null;
			if($iKey % 2 !== 0) {
				$sCellBackground = '#F7F7F7';
			}

			$aRoomIdsFirst = [];

			foreach($aAccommodationProvider['rooms'] as $iKey2 => $aRoomBed) {

				$oRow = new Table\Row();
				$oTable[] = $oRow;

				if($iKey2 === 0) {
					$oCell = new Table\Cell($aAccommodationProvider['ext_33']);
					$oCell->setBackground($sCellBackground);
					$oCell->setRowspan(count($aAccommodationProvider['rooms']));
					$oRow[] = $oCell;
				}

				$oCell = new Table\Cell($aRoomBed['name']);
				$oCell->setBackground($sCellBackground);
				$oRow[] = $oCell;

				// Zeile für jeden Raum nur beim ersten Mal setzen, damit rowspan funktioniert
				if(!in_array($aRoomBed['id'], $aRoomIdsFirst)) {

					if(empty($aRoomBed['status'])) {
						$aRoomBed['status'] = 'V';
					}

					$oCell = new Table\Cell($aRoomBed['status']);
					$oCell->setBackground($sCellBackground);
					$oCell->setRowspan($aAccommodationProvider['room_beds'][$aRoomBed['id']]);
					$oRow[] = $oCell;
					$aRoomIdsFirst[] = $aRoomBed['id'];

				}

				foreach($this->aDates as $dDate) {

					$oCellDepature = new Table\Cell();
					$oCellDepature->setBackground($sCellBackground);
					$oRow[] = $oCellDepature;

					$oCellArrival = new Table\Cell();
					$oCellArrival->setBackground($sCellBackground);
					$oRow[] = $oCellArrival;

					foreach($aRoomBed['allocation'] as $aAllocation) {

						// Im alten Plutzplan war $dDate immer plus und minus 4h
						if(
							$aAllocation['from_date'] >= $dDate &&
							$aAllocation['from_date'] <= $dDate
						) {
							$oCellArrival->setValue($this->getCellLabel($aRoomBed, $aAllocation, $aAllocation['inquiry']['tsp_arrival']));
						}
						if (
							$aAllocation['until_date'] >= $dDate &&
							$aAllocation['until_date'] <= $dDate
						) {
							$oCellDepature->setValue($this->getCellLabel($aRoomBed, $aAllocation, $aAllocation['inquiry']['tsp_depature']));
						}

					}

				}

			}
		}

		$this->oTable = $oTable;

		return $oTable;

	}

	/**
	 * @param array $aRoom
	 * @param array $aAllocation
	 * @param string $sTransferDate
	 * @return string
	 */
	private function getCellLabel(array $aRoom, array $aAllocation, $sTransferDate) {

		$sTime = '--:--';
		if(\Core\Helper\DateTime::isDate($sTransferDate, 'Y-m-d H:i:s')) {
			$sTime = \Ext_Thebing_Format::LocalTime($sTransferDate);
		}

		$sLabel = $aAllocation['customer']['lastname'].', '.$aAllocation['customer']['firstname']."\n";
		$sLabel .= $sTime.' ('.$aRoom["roomtype"].')';

		if(!empty($aAllocation['inquiry']['acc_comment2'])) {
			$sLabel .= "\n".$aAllocation['inquiry']['acc_comment2'];
		}

		return $sLabel;

	}

	protected function generateHeaderRow() {

		$oRow = new Table\Row();
		$oTable[] = $oRow;

		$oRow2 = new Table\Row();
		$oTable[] = $oRow2;

		$oCell = new Table\Cell(($this->cTranslate)('Anbieter'), true);
		$oCell->setRowspan(2);
		$oRow[] = $oCell;

		$oCell = new Table\Cell(($this->cTranslate)('Raum / Bett'), true);
		$oCell->setRowspan(2);
		$oRow[] = $oCell;

		$oCell = new Table\Cell('ST', true);
		$oCell->setRowspan(2);
		$oRow[] = $oCell;

		sort($this->aDates);
		foreach($this->aDates as $dDate) {
			$oCell = new Table\Cell(\Ext_Thebing_Format::LocalDate($dDate), true);
			//$oCell->setAlignment(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
			$oCell->setColspan(2);
			$oRow[] = $oCell;

			$oCell = new Table\Cell(($this->cTranslate)('Abreise'), true);
			$oRow2[] = $oCell;

			$oCell = new Table\Cell(($this->cTranslate)('Anreise'), true);
			$oRow2[] = $oCell;
		}

		return [$oRow, $oRow2];

	}

	public function render() {

		$oExcel = new Excel($this->generateDataTable());

		$sTitle = vsprintf('%s %s - %s', [
			$this->getTitle(),
			\Ext_Thebing_Format::LocalDate($this->aFilters['from']),
			\Ext_Thebing_Format::LocalDate($this->aFilters['until']),
		]);

		$oExcel->setTitle($sTitle);
		$oExcel->setFileName($sTitle);

		$oExcel->generate();

		$oSheet = $oExcel->getSpreadsheetObject()->getActiveSheet();

		// Titel für Druck
		$oSheet->getHeaderFooter()->setOddHeader('&B'.$sTitle);

		$iLastCol = $this->oTable->getMaxColCount();
		$sLastCol = \Util::getColumnCodeForExcel($this->oTable->getMaxColCount() - 1);
		$iLastRow = count($this->oTable);

		$oSheet->getPageMargins()->setTop(0.5);
		$oSheet->getPageMargins()->setRight(0.5);
		$oSheet->getPageMargins()->setLeft(0.5);
		$oSheet->getPageMargins()->setBottom(0.5);

		$oSheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 2);

		// Notwendig für die Zeilenumbrüche in den Spalten
		// LibreOffice: https://github.com/PHPOffice/PHPExcel/issues/588 https://bugs.documentfoundation.org/show_bug.cgi?id=62268
		$oSheet->getStyle('D3:'.$sLastCol.$iLastRow)->getAlignment()->setWrapText(true); // Excel
		//$oSheet->getStyle('D3:'.$sLastCol.$iLastRow)->getFont()->setSize(9);

		//for($iRow = 3; $iRow < $iLastRow; $iRow++) {
		//	$oSheet->getRowDimension($iRow)->setRowHeight(-1);
		//}

		// Alle Body-Zellen vertikal zentrieren
		$oSheet->getStyle('A3:'.$sLastCol.$iLastRow)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

		// Feste Spaltenbreiten für Datumsspalten
		for($iCol = 3; $iCol < $iLastCol; $iCol++) {
			$sCol = \Util::getColumnCodeForExcel($iCol);
			$oSheet->getColumnDimension($sCol)->setAutoSize(false);
			$oSheet->getColumnDimension($sCol)->setWidth(20);
		}

		// Rahmen um alle Zellen
		$oSheet->getStyle('A1:'.$sLastCol.$iLastRow)->applyFromArray([
			'borders' => [
				'allBorders' => [
					'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
				]
			]
		]);

		$oExcel->render();

	}

}
