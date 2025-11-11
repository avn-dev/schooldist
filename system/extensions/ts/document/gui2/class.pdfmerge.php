<?php

class Ext_TS_Document_Gui2_PdfMerge extends Ext_Gui2_Pdf_Abstract {

	public function getPdfPath($iSelectedId) {

		$oDocument = Ext_Thebing_Inquiry_Document::getInstance($iSelectedId);
		$oVersion = $oDocument->getLastVersion();

		return $oVersion->getPath(true);

	}

}
