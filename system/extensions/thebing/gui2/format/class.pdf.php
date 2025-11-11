<?php

class Ext_Thebing_Gui2_Format_Pdf extends Ext_Gui2_View_Format_Abstract{

	protected $_sField = 'pdf_path';
	protected $_sPath = '';

	protected $sFileLabel = 'PDF';

	public function __construct($sField = 'pdf_path', $sPath = ''){
		$this->_sField = $sField;
		$this->_sPath = $sPath;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		switch($oColumn->db_column){
			case 'accommodation_upload_pdf':
				$sPdfPath = 'storage/accommodation/' . $aResultData['filename'];
				break;
			default:
				$sFile = $this->_sField;
				if(!empty($sFile)){
					$mValue = $aResultData[$sFile];
				}
				$sPdfPath = $this->_sPath . $mValue;
				break;
		}

		// TODO Das mit dem Pfad sollte mal komplett refaktorisiert werden…
		if(strpos($sPdfPath, 'storage/') === false) {
			$sPdfPath = 'storage/'.$sPdfPath;
		}
		
		$sPdfPath = ltrim($sPdfPath, '/');
		
		## Letztes PDF ermitteln
		$sCheckFile = Util::getDocumentRoot().$sPdfPath;

		$sPdfPath = '/'.$sPdfPath;

		if(empty($mValue)) {
			// Wenn kein Pfad vorhanden ist, macht es keinen Sinn, ein inaktives Logo anzuzeigen
			return '';
		} elseif(!is_file($sCheckFile)) {
			$sOnClick = '';
			$sIcon = \Ext_TC_Util::getFileTypeIcon(''); // blanko

			$sTitle = L10N::t($this->sFileLabel.' fehlt', Ext_Gui2::$sAllGuiListL10N);

			$sStyle = '';
		} else {
			$sPdfPath = str_replace('/storage/', '', $sPdfPath);
			$sOnClick = 'onclick="window.open(\'/storage/download/'.$sPdfPath.'\'); return false"';
			$sIcon = Ext_Thebing_Util::getFileTypeIcon($sPdfPath);

			$sTitle = L10N::t($this->sFileLabel.' öffnen', Ext_Gui2::$sAllGuiListL10N);

			$sStyle = 'cursor: pointer;';
		}
		##

		return '<img style="'.$sStyle.'" '.$sOnClick.' src="'.$sIcon.'" alt="'.$sTitle.'" title="'.$sTitle.'"/>';
	}

	public function align(&$oColumn = null){
		return 'center';
	}

}
