<?php

class Ext_TC_Gui2_Format_Multiselect extends Ext_Gui2_View_Format_Selection
{
	protected $_sDisplayWithHtml;
	protected $_sSepType;
	protected $_sSep;

	public function __construct($aSelectOptions, $sDisplayWithHtml='<br />', $sSepType='string', $sSep=',')
	{
		parent::__construct($aSelectOptions);
		$this->_sDisplayWithHtml	= $sDisplayWithHtml;
		$this->_sSepType			= $sSepType;
		$this->_sSep				= $sSep;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$sReturn = '';
		$aValues = array();

		if(!empty($mValue))
		{
			switch($this->_sSepType)
			{
				case 'array':
					$aValues = $mValue;
					break;
				case 'string':
					$aValues = explode($this->_sSep, $mValue);
				break;
				case 'json':
					if(!empty($mValue)) {
						$aValues = json_decode($mValue);
					}
				break;
				case 'serialize':
					$aValues = unserialize($mValue);
				break;
			}
		}

		if(!empty($aValues)) {
			
			if(!empty($this->aSelectOptions)) {

				$aValues = array_flip($aValues);

				$aElements = array();
				foreach((array)$this->aSelectOptions as $mKey=>$mValue) {

					if(isset($aValues[$mKey])) {
						$aElements[] = $mValue;
					}

				}

			} else {
				$aElements = $aValues;
			}

			$sReturn = implode($this->_sDisplayWithHtml, $aElements);

		}

		return $sReturn;

	}

}

