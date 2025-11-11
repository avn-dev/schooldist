<?php

namespace TsAccounting\Gui2\Format;

class TransactionDetails extends \Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sDetails = '';
		
		switch($aResultData['type']) {
			case 'proforma':
			case 'invoice':
				$oInvoice = \Ext_Thebing_Inquiry_Document::getInstance($aResultData['type_id']);
				$sDetails .= $this->oGui->t('Rechnungsnummer').': '.$oInvoice->document_number;
				break;
			case 'payment':
				$oPayment = \Ext_Thebing_Inquiry_Payment::getInstance($aResultData['type_id']);
				
				$sDetails .= $this->oGui->t('Zahlungsart').': '.$oPayment->getMethod()->getName().', '.$this->oGui->t('Bezahlt von').': '.\Ext_Thebing_Inquiry_Payment::getSenderOptions()[$oPayment->sender];
				if(!empty($oPayment->comment)) {
					$sDetails .= ', '.$this->oGui->t('Kommentar').': '.nl2br($oPayment->comment);
				}
				
				break;
		}
		
		return $sDetails;
	}

}
