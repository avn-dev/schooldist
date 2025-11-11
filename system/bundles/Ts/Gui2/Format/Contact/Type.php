<?php

namespace Ts\Gui2\Format\Contact;

/**
 * @internal
 */
class Type extends \Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aTypes = [
			'is_accommodation' => $this->oGui->t('Unterkunft'),
			'is_enquiry' => $this->oGui->t('Anfrage'),
			'is_booking' => $this->oGui->t('Buchung')
		];
		
		$aReturn = [];
		foreach($aTypes as $sField=>$sLabel) {
			if(!empty($aResultData[$sField])) {
				$aReturn[] = $sLabel;
			}
		}
		
		return implode(', ', $aReturn);
	}

}
