<?php

class Ext_Gui2_View_Format_Function extends Ext_Gui2_View_Format_Abstract {

	protected $_sFunction;

	public function __construct($sFunction)
	{
		$this->_sFunction = $sFunction;
	}

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if(!empty($mValue) && !is_bool($mValue))// boolean prüfung nötig da wir sonst bei bool filter spalten im index probleme haben
		{
			$mValue = call_user_func_array($this->_sFunction, array($mValue));
		}

		return $mValue;

	}

}
