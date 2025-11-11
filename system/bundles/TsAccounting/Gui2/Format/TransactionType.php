<?php

namespace TsAccounting\Gui2\Format;

class TransactionType extends \Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$aAccountTypes = [
			'invoice' => \L10N::t('Rechnung'), 
			'proforma' => \L10N::t('Proforma'),
			'payment' => \L10N::t('Zahlung')
		];
		
		return $aAccountTypes[$mValue];
	}

}
