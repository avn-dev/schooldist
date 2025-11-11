<?php

namespace Ts\Gui2\AccommodationProvider\Payment;

class AllocatedCategoryFormat extends \Ext_Gui2_View_Format_Abstract {
	
	private $aCategories;
	
	public function __construct() {
		
		$this->aCategories = \Ext_Thebing_Data_Accommodation::getAccommodationCategories();
		
	}
	
	/**
	 * @param string $mValue
	 * @param Ext_Gui2_Head $oColumn
	 * @param array $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$iCategoryId = $aResultData['default_category_id'];
		$aAccommodationCategoryIds = (array)explode(',', $aResultData['accommodation_category_ids']);
				
		if(
			in_array($aResultData['booked_category'], $aAccommodationCategoryIds)
		) {
			$iCategoryId = $aResultData['booked_category'];
		}
		
		return $this->aCategories[$iCategoryId];
	}
	
}
