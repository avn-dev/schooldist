<?php

class Ext_TS_Gui2_Format_Enquiry_Service_Data extends Ext_Thebing_Gui2_Format_Concat
{
	
	protected $_sField;
	
	public function __construct($sField, $sSelectedAllIds = 'all_ids', $bUseEmtyValues = false, $sExtraFormatClass = false) 
	{		
		$this->_sField = $sField;
		
		parent::__construct($sSelectedAllIds, $bUseEmtyValues, $sExtraFormatClass, true, '{||}');
	}
	
	public function getTitle(&$oColumn = null, &$aResultData = null) 
	{

		$mValue		= $aResultData[$this->_sField];
		$aReturn	= array();
		$aReturn['content'] = (string)$this->format($mValue, $oColumn, $aResultData);
		$aReturn['tooltip'] = true;

		return $aReturn;

	}
	
}
