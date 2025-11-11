<?php

namespace Ts\Gui2\Selection\Numberrange;

use Illuminate\Support\Arr;

class Currencies extends \Ext_Gui2_View_Selection_Abstract {
	
	/**
	 * 
	 * @param type $aSelectedIds
	 * @param type $aSaveField
	 * @param \WDBasic $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
	
		$currencies = [];
		if($this->oJoinedObject instanceof \Ext_TS_NumberRange_Allocation_Set) {
			/** @var \Ext_Thebing_School $school */
			foreach($oWDBasic->getJoinTableObjects('schools') as $school) {

				$schoolCurrencies = Arr::pluck($school->getCurrencies(), 'name', 'id');
				
				if(empty($currencies)) {
					$currencies = $schoolCurrencies;
				}
				
				$currencies = array_intersect_assoc($schoolCurrencies, $currencies);
			}
		}

		return $currencies;		
	}		
	
}
