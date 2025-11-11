<?php

namespace Ts\Gui2\AccommodationProvider;

class PaymentGroupByFormat extends \Ext_Gui2_View_Format_Abstract {
	
	protected $sClass;
	protected $aArguments = array();
	
	public function __construct() {
		
		$aArguments = func_get_args();
		
		$sClass = reset($aArguments);
		
		if(class_exists($sClass)) {
			$this->sClass = $sClass;
			$aArguments = array_slice($aArguments, 1);
		}
		
		$this->aArguments = $aArguments;
		
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		$aGroupBy = explode('_', $aResultData['groupby'], 2);
		$sGroupKey = reset($aGroupBy);
		
		if($sGroupKey == 'provider') {
			return '';
		}

		if(!empty($this->sClass)) {
			$oFormat = \Factory::getObject($this->sClass, $this->aArguments);
			$mValue = $oFormat->format($mValue, $oColumn, $aResultData);
		}

		return $mValue;
	}
	
}