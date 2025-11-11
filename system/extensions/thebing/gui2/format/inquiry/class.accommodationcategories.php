<?php

class Ext_Thebing_Gui2_Format_Inquiry_AccommodationCategories extends Ext_Gui2_View_Format_Abstract
{

	protected $_sTitle;
	protected $_aObjects = array();

	public function __construct() {
		
		#$oSchool = Ext_Thebing_Client::getFirstSchool();
		#$sLanguage = $oSchool->getLanguage();
		
		$sLanguage = Ext_Thebing_School::fetchInterfaceLanguage();
		
		$sNameField	= 'name_'.$sLanguage;
		$sShortField = 'short_'.$sLanguage;
		
		$this->_sShortField = $sShortField;
		$this->_sNameField = $sNameField;
	
		$this->_aObjects[] = new Ext_Thebing_Accommodation_Category();
		$this->_aObjects[] = new Ext_Thebing_Accommodation_Roomtype();
		$this->_aObjects[] = new Ext_Thebing_Accommodation_Meal();
	}
		
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(empty($mValue)) {
			$this->_sTitle = '';
			return '';
		}
		
		$aLines = explode('{||}', $mValue);

		$this->_sTitle	= '';
		$sReturn		= '';
		
		foreach($aLines as $sLine)
		{
			if(
				strlen($sReturn) > 0
			){
				$sReturn		.= '<br />';
				$this->_sTitle	.= '<br />';
			}
			
			$aLine = explode('{|}', $sLine);
			
			foreach($aLine as $iKey => $iObjectId)
			{
				$oObject	= $this->_aObjects[$iKey];
				
				$aArrayList	= $oObject->getArrayListSchool();

				if(
					isset($aArrayList[$iObjectId]) &&
					isset($aArrayList[$iObjectId][$this->_sShortField]) &&
					isset($aArrayList[$iObjectId][$this->_sNameField])
				){	
					if(
						$iKey > 0
					){
						$sReturn		.= ' / ';
						$this->_sTitle  .= ' / ';
					}
					
					$sReturn		.= $aArrayList[$iObjectId][$this->_sShortField];
					$this->_sTitle  .= $aArrayList[$iObjectId][$this->_sNameField];
				}
				
			}
		
		}
		
		return $sReturn;

	}

	public function getTitle(&$oColumn = null, &$aResultData = null) {

		$aReturn = array();
		$aReturn['content'] = (string)$this->_sTitle;
		$aReturn['tooltip'] = true;

		return $aReturn;

	}
}