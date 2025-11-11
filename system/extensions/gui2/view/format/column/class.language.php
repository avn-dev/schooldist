<?php

class Ext_Gui2_View_Format_Column_Language extends Ext_Gui2_View_Format_Abstract { 

	protected $_aLanguageData = array();
	protected $_sColumn = 'name';


	public function  __construct($aLanguageData, $sColumn = 'name') {
        if(is_string($aLanguageData)){
            $aLanguageData = array('iso' => $aLanguageData);
        }
		$this->_aLanguageData = $aLanguageData;
		$this->_sColumn = $sColumn;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){
		
		$oWDBasic = $this->oGui->getWDBasic($aResultData['id']);
		$sJoinTable = $oColumn->select_column;
		$mValue = $oWDBasic->$sJoinTable;
		
		foreach((array)$mValue as $aJoinData){
			if($aJoinData['language_iso'] == $this->_aLanguageData['iso']){
				return $aJoinData[$this->_sColumn];
			}
		}

		return ''; 

	}

}
