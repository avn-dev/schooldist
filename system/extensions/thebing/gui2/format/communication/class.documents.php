<?php

class Ext_Thebing_Gui2_Format_Communication_Documents extends Ext_Gui2_View_Format_Abstract {

	protected $_bSeperator = '<br/>';
	protected $_bReturnArray = false;

	public function __construct($bSeperator='<br/>', $bReturnArray=false) {
		$this->_bSeperator = $bSeperator;
		$this->_bReturnArray = $bReturnArray;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$mValue = (array)$mValue;

		if(
			!isset($mValue['inquiry']) &&
			!isset($mValue['contract'])
		) {
			$aTemp = $mValue;
			$mValue = array('inquiry' => $aTemp);
		}

		$aReturnDocuments = array();
		foreach((array)$mValue as $sType=>$aDocuments) {

			switch((string)$sType) {
				case 'contract':
					$sObject = 'Ext_Thebing_Contract_Version';
					break;
				case 'inquiry':
				default:
					$sObject = 'Ext_Thebing_Inquiry_Document_Version';
					break;
			}

			foreach((array)$aDocuments as $iVersionId) {

				$sDocuments = '';

				$oVersion = new $sObject($iVersionId);
				$sPath = $oVersion->getPath();

				$sIcon = Ext_Thebing_Util::getFileTypeIcon($sPath);

				$sDocuments .= '<a href="/storage/download'.$sPath.'" target="_blank">';
				$sDocuments .= '<img src="'.$sIcon.'" alt="'.\Util::convertHtmlEntities($oVersion->getLabel()).'" style="" class="imgPreTextIcon" />';
				$sDocuments .= '</a>';
				$sDocuments .= ' ';
				$sDocuments .= '<a href="/storage/download'.$sPath.'" target="_blank">';
				$sDocuments .= $oVersion->getLabel();
				$sDocuments .= '</a>';
				$aReturnDocuments[] = $sDocuments;

			}

		}

		if($this->_bReturnArray === true) {
			return $aReturnDocuments;
		}

		$sDocuments = implode($this->_bSeperator, $aReturnDocuments);
		
		return $sDocuments;

	}

}

