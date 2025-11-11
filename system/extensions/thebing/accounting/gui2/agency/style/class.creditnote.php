<?

class Ext_Thebing_Accounting_Gui2_Agency_Style_CreditNote extends Ext_Gui2_View_Style_Abstract {

	/**
	 * @param mixed $mValue
	 * @param $oColumn
	 * @param array $aRowData
	 * @return string
	 */
	public function getStyle($mValue, &$oColumn, &$aRowData) {

		$sColorPartPayed = Ext_Thebing_Util::getColor('soft_green', 30);
		$sColorFullPayed = Ext_Thebing_Util::getColor('lightgreen');
		$sColorNotCreated = Ext_Thebing_Util::getColor('changed');

		$sStyle = '';

		$fAmount = (float)$aRowData['creditnote_amount'];
		$fPayedAmount = (float)$aRowData['creditnote_payed'];

		if(empty($aRowData['document_number_cn'])) {
			$sStyle .= 'background-color: '.$sColorNotCreated.';';
		} elseif(Ext_TC_Util::compareFloat(round($fPayedAmount, 2), round($fAmount, 2), 2) >= 0) {
			$sStyle .= 'background-color: '.$sColorFullPayed.';';
		} elseif($fPayedAmount > 0) {
			$sStyle .= 'background-color: '.$sColorPartPayed.';';
		}

		return $sStyle;
	}

}
