<?php

class Ext_Thebing_Gui2_Pdf_Cards extends Ext_Gui2_Pdf_Abstract {

	protected $_sType = '';
	
	public function __construct($_sType){
		$this->_sType = $_sType;
	}
	
	public function getPdfPath($iSelectedId){
		
		$oInquiry = Ext_TS_Inquiry::getInstance($iSelectedId);

		$oAdditionalDocuments = $oInquiry->getLastDocument('additional_document', [$this->_sType]);

//		// Neuestes StudentCard PDF returnen
//		$oAdditionalDocuments	= Ext_Thebing_Inquiry_Document_Search::searchAdditional($iSelectedId, $this->_sType, false);

		if(is_object($oAdditionalDocuments)){
			$oVersion = $oAdditionalDocuments->getLastVersion();
			
			$sPath = $oVersion->getPath(true);
			
			if(!empty($sPath)){
				return $sPath;
			}
		}
	}

}
