<?php

class Ext_Thebing_Gui2_Format_Communication_Attachments extends Ext_Gui2_View_Format_Abstract {

	protected $_bSeperator = '<br/>';
	protected $_bReturnArray = false;

	public function __construct($bSeperator='<br/>', $bReturnArray=false) {
		$this->_bSeperator = $bSeperator;
		$this->_bReturnArray = $bReturnArray;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$aReturnDocuments = array();
		foreach((array)$mValue as $sPath=>$sName) {

			$sDocuments = '';
			$sPath = substr($sPath, strpos($sPath, '/storage'));
			$sPath = str_replace('/storage/', '', $sPath);
			$sPath = '/storage/download/'.$sPath;

			$sIcon = Ext_Thebing_Util::getFileTypeIcon($sPath);

			$sDocuments .= '<a href="'.$sPath.'" onclick="window.open(this.href);return false;">';
			$sDocuments .= '<img src="'.$sIcon.'" alt="'.\Util::convertHtmlEntities($sName).'" style="" class="imgPreTextIcon" />';
			$sDocuments .= '</a>';
			$sDocuments .= ' ';
			$sDocuments .= '<a href="'.$sPath.'" onclick="window.open(this.href);return false;">';
			$sDocuments .= $sName;
			$sDocuments .= '</a>';
			$aReturnDocuments[] = $sDocuments;

		}

		if($this->_bReturnArray === true) {
			return $aReturnDocuments;
		}

		$sDocuments = implode($this->_bSeperator, $aReturnDocuments);
		
		return $sDocuments;

	}

}
