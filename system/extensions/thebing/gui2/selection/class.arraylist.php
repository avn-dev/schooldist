<?php


class Ext_Thebing_Gui2_Selection_ArrayList extends Ext_Gui2_View_Selection_Abstract
{
	protected $_sOptionClass;
	
	public function __construct($sOptionClass)
	{
		$this->_sOptionClass = $sOptionClass;
	}
	
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
	{
		$mSelectedOptions	= (array)$oWDBasic->{$aSaveField['db_column']};
		
		$aOptionsDialog		= (array)$aSaveField['select_options'];

		foreach($mSelectedOptions as $iSelectedOption)
		{
			$iSelectedOption = (int)$iSelectedOption;
			
			if(
				$iSelectedOption > 0 &&
				!array_key_exists($iSelectedOption, $aOptionsDialog)
			){
				$oOption = call_user_func(array($this->_sOptionClass, 'getInstance'), $iSelectedOption);

				$iSchoolId	= (int)$oOption->getSchoolId();

				if(
					$iSchoolId !== false
				)
				{
					$oSchool	= Ext_Thebing_School::getInstance($iSchoolId);
					$sLanguage	= $oSchool->getInterfaceLanguage();
					$sName		= $oOption->getName($sLanguage);
				}
				else
				{
					$sName		= $oOption->getName();
				}

				$aOptionsDialog[$iSelectedOption] = $sName;
			}
		}


		return $aOptionsDialog;
	}
}