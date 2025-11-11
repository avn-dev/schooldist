<?php

/**
 * Helper-Klasse, um den Platzhalter »provider_payment_overview« bereitzustellen
 */
class Ext_TS_Accounting_Provider_Grouping_Placeholder_PaymentOverviewTable
{
	protected $_oPlaceholder;
	protected $_sTableData = '';
	protected $_sTemplateLanguage;
	private $_sTableRowStyle = 'border: 0.01mm solid black;';

	public function __construct(Ext_Thebing_Placeholder $oPlaceholder, $sLanguage) {
		$this->_oPlaceholder = $oPlaceholder;
		
		$this->_sTemplateLanguage = $sLanguage;
	}

	/**
	 * Row hinzufügen
	 * @param array $aCells
	 * @param string $sHtmlTag
	 */
	protected function _addRow(array $aCells, $sHtmlTag = 'td') {

		$this->_sTableData .= '<tr>';

		$aCells = array_values($aCells);
		$iCells = count($aCells);
		foreach($aCells as $iKey=>$mCell) {
			
			$sStyleAddon = '';
			if($iCells == ($iKey+1)) {
				$sStyleAddon = 'text-align: right;';
			}
			
			if(is_array($mCell)) {
				$sCell = $mCell['content'];

				if(isset($mCell['width'])) {
					$sStyleAddon .= 'width: '.$mCell['width'].';';
				}
				if(isset($mCell['align'])) {
					$sStyleAddon .= 'text-align: '.$mCell['align'].';';
				}

			} else {
				$sCell = $mCell;
			}
			
			$this->_sTableData .= '<'.$sHtmlTag.' style="'.$this->_sTableRowStyle.''.$sStyleAddon.'">'.$sCell.'</'.$sHtmlTag.'>';
		}

		$this->_sTableData .= '</tr>';
	}

	/**
	 * Summenzeile hinzufügen
	 * @param string $sSum
	 */
	protected function _addSumRow($sSum, $iColspan=4) {
		$this->_sTableData .= '<tr>';
		$this->_sTableData .= '<td style="'.$this->_sTableRowStyle.'">'.Ext_TC_Placeholder_Abstract::translateFrontend('Summe', $this->_sTemplateLanguage).'</td>';
		$this->_sTableData .= '<td style="'.$this->_sTableRowStyle.'" colspan="'.$iColspan.'">&nbsp;</td>';
		$this->_sTableData .= '<td style="'.$this->_sTableRowStyle.'text-align: right;">'.$sSum.'</td>';
		$this->_sTableData .= '</tr>';
	}
	
	/**
	 * Daten aufbereiten
	 */
	protected function _prepare() {

		// Daten kommen aus Ext_TS_Accounting_Provider_Grouping_Abstract
		$aGroupingData = $this->_oPlaceholder->getAdditionalData('grouping_data');
		$aGroupingData = $aGroupingData['provider_payment_overview'];

		// Lehrerbezahlung
		if($this->_oPlaceholder instanceof Ext_TS_Accounting_Provider_Grouping_Teacher_Placeholder) {

			$this->_addRow(array(
				Ext_TC_Placeholder_Abstract::translateFrontend('Zeitraum', $this->_sTemplateLanguage),
				Ext_TC_Placeholder_Abstract::translateFrontend('Klasse', $this->_sTemplateLanguage),
				Ext_TC_Placeholder_Abstract::translateFrontend('Lektionen', $this->_sTemplateLanguage),
				Ext_TC_Placeholder_Abstract::translateFrontend('Stunden', $this->_sTemplateLanguage),
				Ext_TC_Placeholder_Abstract::translateFrontend('Pro Lektion/Monat', $this->_sTemplateLanguage),
				Ext_TC_Placeholder_Abstract::translateFrontend('Betrag', $this->_sTemplateLanguage),
			), 'th');

			foreach($aGroupingData['rows'] as $aPosition) {
				$this->_addRow(array(
					$aPosition['weeks_months'],
					$aPosition['classname'],
					$aPosition['lessons'],
					$aPosition['hours'],
					$aPosition['per_lesson_month'],
					$aPosition['amount'],
				));
			}

			$this->_addSumRow($aGroupingData['amount_sum']);

		}

		// Unterkunftsanbieter bezahlen
		elseif($this->_oPlaceholder instanceof Ext_TS_Accounting_Provider_Grouping_Accommodation_Placeholder) {

			$this->_addRow(array(
				array('width'=>'18%', 'content'=>Ext_TC_Placeholder_Abstract::translateFrontend('Zeitraum', $this->_sTemplateLanguage)),
				array('width'=>'19%', 'content'=>Ext_TC_Placeholder_Abstract::translateFrontend('Kdnr.', $this->_sTemplateLanguage)),
				array('width'=>'28%', 'content'=>Ext_TC_Placeholder_Abstract::translateFrontend('Kunde', $this->_sTemplateLanguage)),
				array('width'=>'23%', 'content'=>Ext_TC_Placeholder_Abstract::translateFrontend('Leistung', $this->_sTemplateLanguage)),
				//Ext_TC_Placeholder_Abstract::translateFrontend('Je Nacht/Monat', $this->_sTemplateLanguage),
				array('width'=>'12%', 'content'=>Ext_TC_Placeholder_Abstract::translateFrontend('Betrag', $this->_sTemplateLanguage)),
			), 'th');

			foreach($aGroupingData['rows'] as $aPosition) {
				$this->_addRow(array(
					$aPosition['timeframe'],
					$aPosition['customer_number'],
					$aPosition['customer_name'],
					$aPosition['service'],
					//$aPosition['per_night_month'],
					$aPosition['amount'],
				));
			}

			$this->_addSumRow($aGroupingData['amount_sum'], 3);

		}

		// Transferanbieter bezahlen
		elseif($this->_oPlaceholder instanceof Ext_TS_Accounting_Provider_Grouping_Transfer_Placeholder) {

			$this->_addRow(array(
				Ext_TC_Placeholder_Abstract::translateFrontend('Zeitraum', $this->_sTemplateLanguage),
				Ext_TC_Placeholder_Abstract::translateFrontend('Kdnr.', $this->_sTemplateLanguage),
				Ext_TC_Placeholder_Abstract::translateFrontend('Kunde', $this->_sTemplateLanguage),
				Ext_TC_Placeholder_Abstract::translateFrontend('Leistung', $this->_sTemplateLanguage),
				Ext_TC_Placeholder_Abstract::translateFrontend('Betrag', $this->_sTemplateLanguage),
			), 'th');

			foreach($aGroupingData['rows'] as $aPosition) {
				$this->_addRow(array(
					$aPosition['timeframe'],
					$aPosition['customer_number'],
					$aPosition['customer_name'],
					$aPosition['service'],
					$aPosition['amount'],
				));
			}

			$this->_addSumRow($aGroupingData['amount_sum'], 3);

		}

		else {
			throw new Exception('Unknown placeholder class "'.get_class($this->_oPlaceholder).'" for this placeholder!');
		}

	}

	public function render() {
		$this->_prepare();
		$sTable = '<table cellspacing="0" cellpadding="4">';
		$sTable .= $this->_sTableData;
		$sTable .= '</table>';

		return $sTable;
	}
}