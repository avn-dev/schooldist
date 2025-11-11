<?php

namespace TcStatistic\Generator\Table;

use \TcStatistic\Model\Table;

/**
 * @TOOD Cell font-style neu implementieren (momenetan nur bold, aber auf color jetzt verwendet)
 * @TODO Cell text-align implementieren (momentan nur Excel)
 */
class Html extends AbstractTable {

	/** @var string CSS-Klasse für Tabelle */
	public $sTableCSSClass = 'statistic_result';

	/**
	 * HTML-Tabelle generieren
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function generate() {

		$sHtml = '';
		foreach($this->aTables as $oTable) {
			$sHtml .= $this->generateTable($oTable);
		}

		return $sHtml;

	}

	/**
	 * HTML-Tabelle generieren
	 *
	 * @param \TcStatistic\Model\Table\Table $oTable
	 * @return string
	 * @throws \Exception
	 */
	public function generateTable(Table\Table $oTable) {

		$oHtmlTable = new \Ext_Gui2_Html_Table();
		$oHtmlTable->class = $this->sTableCSSClass;

		// Bei mehreren Tabellen wird davon ausgegangen, dass alle Tabellenspalten auch untereinander die selbe Breite haben sollen
		if(count($this->aTables) > 1) {
			$oHtmlTable->style = 'table-layout: fixed';
		}

		if($oTable->hasCaption()) {
			$oCaption = new \Ext_Gui2_Html_Table_Caption();
			$oCaption->setElement($oTable->getCaption());
			$oHtmlTable->setElement($oCaption);
		}

		// Sortieren nach Row-Set
		$aRows = ['head' => [], 'body' => [], 'foot' => []];
		foreach($oTable as $oRow) {
			if($oRow->getRowSet() === 'body') {
				$aRows['body'][] = $oRow;
			} elseif($oRow->getRowSet() === 'head') {
				$aRows['head'][] = $oRow;
			} elseif($oRow->getRowSet() === 'foot') {
				$aRows['foot'][] = $oRow;
			} else {
				throw new \UnexpectedValueException('Unknown row set');
			}
		}

		// Footer kommt nach HTML-Standard vor Body…
		$this->generateSet($oHtmlTable, 'head', $aRows['head']);
		$this->generateSet($oHtmlTable, 'foot', $aRows['foot']);
		$this->generateSet($oHtmlTable, 'body', $aRows['body']);

		$sHtml = $oHtmlTable->generateHTML();
		return $sHtml;

	}

	/**
	 * Set generieren
	 *
	 * @param \Ext_Gui2_Html_Table $oTable
	 * @param string $sSet
	 * @param Table\Row[]
	 */
	protected function generateSet(\Ext_Gui2_Html_Table $oTable, $sSet, array $aRows) {

		if(empty($aRows)) {
			return;
		}

		if($sSet === 'body') {
			$oSet = new \Ext_Gui2_Html_Table_TBody();
		} elseif($sSet === 'head') {
			$oSet = new \Ext_Gui2_Html_Table_THead();
		} elseif($sSet === 'foot') {
			$oSet = new \Ext_Gui2_Html_Table_TFoot();
		} else {
			throw new \UnexpectedValueException('Unknown row set');
		}

		foreach($aRows as $oRow) {
			$oTr = $this->generateRow($oRow);
			$oSet->setElement($oTr);
		}

		$oTable->setElement($oSet);

	}

	/**
	 * Row generieren
	 *
	 * @param Table\Row $oRow
	 * @return \Ext_Gui2_Html_Table_Tr
	 */
	protected function generateRow(Table\Row $oRow) {
		$oTr = new \Ext_Gui2_Html_Table_Tr();

		foreach($oRow as $iCell => $oCell) {
			/** @var Table\Cell $oCell */

			if($oCell->isHeading()) {
				$oTd = new \Ext_Gui2_Html_Table_Tr_Th();
			} else {
				$oTd = new \Ext_Gui2_Html_Table_Tr_Td();
			}

			$aClasses = [];
			$aStyles = [];

			$mValue = $this->formatCellValue($oCell, $aClasses, $aStyles);
			$oTd->setElement((string)$mValue);

			if($oCell->hasBackground()) {
				$aStyles['background-color'] = $oCell->getBackground();
			}

			if(!empty($oCell->getColspan())) {
				$oTd->colspan = $oCell->getColspan();
			}

			if(!empty($oCell->getRowspan())) {
				$oTd->rowspan = $oCell->getRowspan();
			}

			if(!empty($aClasses)) {
				$oTd->class = join(' ', $aClasses);
			}

			if(!empty($oCell->getComment())) {
				$oTd->title = $oCell->getComment();
			}

			// CSS-Styles setzen
			if(!empty($aStyles)) {
				foreach($aStyles as $sKey => &$mValue) {
					$mValue = $sKey.': '.$mValue;
				}
				$oTd->style = join(';', $aStyles);
			}

			$oTr->setElement($oTd);
		}

		return $oTr;
	}

	/**
	 * HTML-Tabelle rendern: HTML direkt ausgeben
	 */
	public function render() {
		echo $this->generate();
	}

	/**
	 * Wert der Zelle formatieren
	 *
	 * @param Table\Cell $oCell
	 * @param array $aClasses
	 * @param array $aStyles
	 * @return string
	 */
	protected function formatCellValue(Table\Cell $oCell, array &$aClasses, array &$aStyles) {

		if($oCell->getBorder() !== 0) {
			if($oCell->getBorder() & Table\Cell::BORDER_RIGHT) {
				$aClasses[] = 'border_right';
			}
			if($oCell->getBorder() & Table\Cell::BORDER_BOTTOM) {
				$aClasses[] = 'border_bottom';
			}
		}

		if($oCell->hasFontStyle()) {
			// TODO
			foreach($oCell->getFontStyle() as $sType => $mValue) {
				if($sType === 'bold') {
					$aStyles['font-weight'] = 'bold';
				}
			}
		}

		if($oCell->getNoWrap()) {
			$aStyles['white-space'] = 'nowrap';
		}

		return $oCell->getValue();

	}

}
