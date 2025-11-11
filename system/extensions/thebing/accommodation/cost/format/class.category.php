<?php

class Ext_Thebing_Accommodation_Cost_Format_Category extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return string
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if($aResultData['costcategory_id'] == -1) {
			$mValue = $this->oGui->t('Festgehalt');
		}

		return $mValue;
	}

}