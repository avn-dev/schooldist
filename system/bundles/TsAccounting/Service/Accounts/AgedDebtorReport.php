<?php

namespace TsAccounting\Service\Accounts;

use TcStatistic\Generator\Table\Excel;
use TcStatistic\Model\Table;
use TcStatistic\Model\Statistic\Column;

class AgedDebtorReport {
	
	protected $translate;
	
	public ?\DateTime $filterDate = null;
	public string $filterSearch;
	public string $filterType;
	public int $reportInterval = 30;
	public string $reportInclude;
	public int $reportBackIntervals = 4;
	public int $reportForthIntervals = 4;
	public ?\Carbon\CarbonPeriod $reportPeriod = null;
	
	public function __construct($translate) {
		$this->translate = $translate;
	}
	
	public function getReportPeriod() {

		if($this->filterDate === null) {
			$this->filterDate = new \DateTime;
		}
		
		$back = $this->reportBackIntervals * $this->reportInterval;
		$forth = ($this->reportForthIntervals) * $this->reportInterval;

		$from = clone $this->filterDate;
		$from->modify('-'.$back.' days');
		
		$until = clone $this->filterDate;
		$until->modify('+'.$forth.' days');
		
		$this->reportPeriod = \Carbon\CarbonPeriod::create($from, $this->reportInterval.' days', $until);
		
		return $this->reportPeriod;
	}
	
	public function generate(array $data) {
		
		$accountTypes = \TsAccounting\Gui2\Format\AccountType::getOptions();
		
		$title = ($this->translate)('Aged debtor report');
		
		if($this->filterDate === null) {
			$this->filterDate = new \DateTime;
		}
		
		$table = new Table\Table();
		$table->setCaption($title);
		
		$this->addTableInfoRows($table);
		
		$back = $this->reportBackIntervals * $this->reportInterval;
		
		$date = clone $this->filterDate;
		$date->modify('-'.$back.' days');
		
		// Überschriften
		$row1 = new Table\Row();
		$table[] = $row1;
		
		$row2 = new Table\Row();
		$table[] = $row2;
		
		$emptyCell = new Table\Cell();
		$emptyCell->setBorder(Table\Cell::BORDER_BOTTOM);
		
		$cell = new Table\Cell(($this->translate)('Typ'), true);
		$row1[] = $cell;		
		$row2[] = $emptyCell;
		
		$cell = new Table\Cell(($this->translate)('Debitorennummer'), true);
		$row1[] = $cell;	
		$row2[] = $emptyCell;
		
		$cell = new Table\Cell(($this->translate)('Debitor'), true);
		$row1[] = $cell;	
		$row2[] = $emptyCell;
		
		$cell = new Table\Cell(($this->translate)('Rechnungen'), true);
		$row1[] = $cell;	
		$row2[] = new Table\Cell(clone $this->filterDate, false, 'date');
		
		$cell = new Table\Cell(($this->translate)('Zahlungen'), true);
		$row1[] = $cell;	
		$row2[] = new Table\Cell(clone $this->filterDate, false, 'date');
		
		$cell = new Table\Cell(($this->translate)('Saldo'), true);
		$row1[] = $cell;	
		$row2[] = new Table\Cell(clone $this->filterDate, false, 'date');
		
		$this->getReportPeriod();

		$cellIndex = new Table\Cell(($this->translate)('Vorher'), true);
		$cellIndex->setAlignment('right');
		$row1[] = $cellIndex;

		$dateCell = new Table\Cell();
		$dateCell->setBorder(Table\Cell::BORDER_BOTTOM);
		$row2[] = $dateCell;
		
		for($i=($this->reportBackIntervals*-1);$i<$this->reportForthIntervals;$i++) {

			$heading = ($i*$this->reportInterval).' '.($this->translate)('bis').' '.($i*$this->reportInterval+$this->reportInterval-1).' '.($this->translate)('Tage');
			
			$cellIndex = new Table\Cell($heading, true);
			$row1[] = $cellIndex;
			
			$dateCell = new Table\Cell(clone $date, false, 'date');
			$dateCell->setBorder(Table\Cell::BORDER_BOTTOM);
			$row2[] = $dateCell;
			
			$date->modify('+'.$this->reportInterval.' days');
			
		}
		
		$cellIndex = new Table\Cell(($this->translate)('Nachher'), true);
		$cellIndex->setAlignment('right');
		$row1[] = $cellIndex;

		$dateCell = new Table\Cell();
		$dateCell->setBorder(Table\Cell::BORDER_BOTTOM);
		$row2[] = $dateCell;
		
		$cell = new Table\Cell(('Saldo'), true);
		$row1[] = $cell;	
		$row2[] = $emptyCell;
		
		$lastPeriodIndex = count($this->reportPeriod);
		
		$periodSums = [];
		
		foreach($data['data'] as $item) {

			$currencyId = \Ext_Thebing_Currency::getByIso($item['currency_iso'])->id;
			
			$saldo = 0.0;
			
			$row = new Table\Row();
			
			$row[] = new Table\Cell($accountTypes[$item['account_type']]);
			$row[] = new Table\Cell($item['account_number']);
			$row[] = new Table\Cell($item['account_name']);
			$cell = new Table\Cell($item['amount_invoices'], false, 'number_float');
			$cell->setCurrency($currencyId);
			$row[] = $cell;
			$cell = new Table\Cell($item['amount_payments'], false, 'number_float');
			$cell->setCurrency($currencyId);
			$row[] = $cell;
			
			if($this->reportInclude == 'without_proforma') {
				$cell = new Table\Cell($item['accounting_balance'], false, 'number_float');
			} else {
				$cell = new Table\Cell($item['ledger_balance'], false, 'number_float');
			}
			$cell->setCurrency($currencyId);
			$row[] = $cell;
			
			$transactions = \TsAccounting\Entity\Transaction::query()
				->where('account_type', '=', $item['account_type'])
				->where('account_id', '=', $item['account_id'])
				->orderBy('due_date', 'ASC')
				->get();

			$periodValues = [
				0 => 0
			];

			foreach($transactions as $transaction) {

				// Proforma nicht berücksichtigen
				if(
					$this->reportInclude == 'without_proforma' &&
					$transaction->type == 'proforma'
				) {
					continue;
				}

				$dueDate = new \Carbon\Carbon($transaction->due_date);
				
				$hasMatch = false;
				foreach($this->reportPeriod as $periodIndex=>$date) {
					if($dueDate < $date) {
						$periodValues[$periodIndex] += $transaction->amount;
						$hasMatch = true;
						break;
					}
				}
				
				if(!$hasMatch) {
					$periodValues[$lastPeriodIndex] += $transaction->amount;
				}
				
				$saldo += $transaction->amount;
				
			}
			
			for($i=0;$i<=$lastPeriodIndex;$i++) {
				$cell = new Table\Cell($periodValues[$i]??'', false, 'number_float');
				$cell->setCurrency($currencyId);
				$row[] = $cell;
				
				$periodSums[$i] += $periodValues[$i];
				
			}			
			
			$cell = new Table\Cell($saldo, false, 'number_float');
			$cell->setCurrency($currencyId);
			$row[] = $cell;
			
			$table[] = $row;

		}

		$row = new Table\Row();
		$row[] = new Table\Cell();
		$row[] = new Table\Cell();
		$row[] = new Table\Cell();
		$row[] = new Table\Cell();
		$row[] = new Table\Cell();
		$row[] = new Table\Cell();
		
		for($i=0;$i<=$lastPeriodIndex;$i++) {
			$cell = new Table\Cell($periodSums[$i]??'', true, 'number_float');
			$cell->setCurrency($currencyId);
			$row[] = $cell;
		}
		
		$table[] = $row;
#__out(1,1);	
		$excel = new Excel($table);
		
		$excel->setFileName($title);
		$excel->setTitle($title);

		return $excel;
	}
	
	/**
	 * @param Table\Table $oTable
	 */
	private function addTableInfoRows(Table\Table $oTable) {

		#$aColumns = $this->getColumns();

		$iTitleColspan = 2;
		$iValueColspan = 2;#count($aColumns) - $iTitleColspan;

		// Leerzeile
		$oRow = new Table\Row();
		$oCell = new Table\Cell('');
		$oCell->setColspan(4);
		$oRow[] = $oCell;
		$oTable[] = $oRow;

		$oRow = new Table\Row();
		$oTable[] = $oRow;

		$oCell = new Table\Cell(($this->translate)('Datum'), true);
		$oCell->setColspan($iTitleColspan);
		$oRow[] = $oCell;

		$oCell = new Table\Cell($this->filterDate, false, 'date');
		$oCell->setColspan($iValueColspan);
		$oRow[] = $oCell;

		$oRow = new Table\Row();
		$oTable[] = $oRow;

		$oCell = new Table\Cell(($this->translate)('Benutzer'), true);
		$oCell->setColspan($iTitleColspan);
		$oRow[] = $oCell;

		$oCell = new Table\Cell(\Access_Backend::getInstance()->completename, false);
		$oCell->setColspan($iValueColspan);
		$oRow[] = $oCell;

		// Leerzeile
		$oRow = new Table\Row();
		$oCell = new Table\Cell('');
		$oCell->setColspan(4);
		$oRow[] = $oCell;
		$oTable[] = $oRow;

	}

}
