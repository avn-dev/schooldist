<?php


class Ext_Thebing_Gui2_Format_Dropdown extends Ext_Thebing_Gui2_Format_Select {

	protected $_aDropdownOptions;

	public function __construct($aSelectOptions=null, array $aDropdownOptions) {
		parent::__construct($aSelectOptions);
		$this->_aDropdownOptions = $aDropdownOptions;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$aSelectOptions = (array)$this->aSelectOptions;

		if($this->sFlexType != 'list'){
			return $aSelectOptions[$mValue];
		}
		
		$sName = false;

		if($oColumn)
		{
			$sName = $oColumn->db_column;
			
			if(
				is_array($aResultData) &&
				isset($aResultData['id'])
			)
			{
				$sName .= '[' . $aResultData['id'] . ']';
			}
		}
		
		$oSelect = new Ext_Gui2_Html_Select();
		
		if($sName)
		{
			$oSelect->name = $sName;
		}
		
		foreach($this->_aDropdownOptions as $sOptionKey => $mOptionValue)
		{
			$oSelect->$sOptionKey = $mOptionValue;
		}

		foreach($aSelectOptions as $mOptionKey => $sOptionLabel)
		{
			$oOption = new Ext_Gui2_Html_Option();
			$oOption->value = $mOptionKey;
			$oOption->setElement($sOptionLabel);
			if($mValue==$mOptionKey)
			{
				$oOption->selected = 'selected';
			}
			$oSelect->setElement($oOption);
		}


		return $oSelect->generateHTML();
	}

}

?>
