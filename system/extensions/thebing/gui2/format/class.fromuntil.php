<?php

class Ext_Thebing_Gui2_Format_FromUntil extends Ext_Gui2_View_Format_Abstract
{
	protected $_sColumnFrom;
	protected $_sColumnUntil;

	public function __construct($sColumnFrom,$sColumnUntil)
	{
		$this->_sColumnFrom		= $sColumnFrom;
		$this->_sColumnUntil	= $sColumnUntil;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		$sColumnFrom	= $this->_sColumnFrom;
		$sColumnUntil	= $this->_sColumnUntil;

		if(!empty($sColumnFrom) && !empty($sColumnUntil))
		{
			$mValueFrom		= (int)$aResultData[$sColumnFrom];
			$mValueUntil	= (int)$aResultData[$sColumnUntil];
			
	
			return $mValueFrom.'('.$mValueUntil.')';
		}

		return null;
	}
}
