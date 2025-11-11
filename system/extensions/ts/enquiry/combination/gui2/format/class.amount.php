<?php

class Ext_TS_Enquiry_Combination_Gui2_Format_Amount extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		if (!empty($aResultData['document_id'])) {
			$document = Ext_Thebing_Inquiry_Document::getInstance($aResultData['document_id']);
			return Ext_Thebing_Format::Number($document->getAmount(), $document->getCurrency(), (int)($aResultData['school_id'] ?? 0));
		}

		return '';

	}

}
