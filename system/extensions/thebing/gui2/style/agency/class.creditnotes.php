<?php

class Ext_Thebing_Gui2_Style_Agency_Creditnotes extends Ext_Gui2_View_Style_Abstract {

	/**
	 * @var bool
	 */
	private bool $bRowStyle;

	/**
	 * @param bool $bRowStyle
	 */
	public function __construct($bRowStyle = true) {
		$this->bRowStyle = (bool)$bRowStyle;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getStyle($mValue, &$oColumn, &$aRowData){

		if($this->bRowStyle) {

			if($aRowData['amount'] < 0) {
				return 'background: '.Ext_Thebing_Util::getColor('red').';';
			} elseif( $aRowData['storno_id'] > 0 ) {
				return 'background: '.Ext_Thebing_Util::getColor('storno').';';
			}

		} else {

			$oCN = Ext_Thebing_Agency_Manual_Creditnote::getInstance($aRowData['id']);
			$fPaid = $oCN->getAllocatedAccountingAmount();
			if(
				$fPaid > 0 &&
				$fPaid < $aRowData['amount']
			) {
				return 'background: '.Ext_Thebing_Util::getColor('soft_green', 30).';';
			} elseif($fPaid > 0) {
				return 'background: '.Ext_Thebing_Util::getColor('lightgreen').';';
			}

		}

		return parent::getStyle($mValue, $oColumn, $aRowData);

	}

}
