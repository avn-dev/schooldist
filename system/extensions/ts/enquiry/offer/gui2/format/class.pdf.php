<?php

// TODO 16002 Entfernen
class Ext_TS_Enquiry_Offer_Gui2_Format_Pdf extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$oOffer = Ext_TS_Enquiry_Offer::getInstance($aResultData['id']);
		$aDocument = $oOffer->getDocuments();
		$sOnClick = '';
		$sStyle = '';

		if(!empty($aDocument)) {
			$oDocument = reset($aDocument);
			$oVersion = $oDocument->getLastVersion();
			$sPdfPath = $oVersion->path;

			if(!empty($sPdfPath)) {
				$sIcon = \Ext_TC_Util::getFileTypeIcon($sPdfPath);
				$sOnClick = 'onclick="window.open(\'/storage/download' . $sPdfPath . '\'); return false"';
				$sStyle = 'cursor: pointer;';
			}

			$sImg = '<img '.$sOnClick.' style="'.$sStyle.'" src="';
			$sImg .= $sIcon;
			$sImg .= '" />';

			return $sImg;

		}

		return '';

	}
	
	public function align(&$oColumn = null) {
		return 'center';
	}
	
}
