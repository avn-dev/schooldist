<?php

class Ext_Thebing_Gui2_Format_Inquiry_AccommodationInfo2 extends Ext_Gui2_View_Format_Abstract {

	private $aColumnIndex = [
		'accommodation_fullnamelist',
		'accommodation_fulllstreetlist',
		'accommodation_fulllziplist',
		'accommodation_fulllcitytlist',
		'accommodation_fulllphonelist',
		'accommodation_fulllphone2list',
		'accommodation_fulllmobilelist',
		'accommodation_fulllmaillist',
		'accommodation_fulladdressaddonlist'
	];

	/**
	 * @inheritdoc
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$mValue = $aResultData['accommodation_info'];

		if(empty($mValue)) {
			return '';
		}

		$aReturn = [];
		$aProviders = explode('{||}', $mValue);

		foreach($aProviders as $sProvider) {

			$aData = explode('{|}', $sProvider);
			$aReturn[] = $aData[array_search($oColumn->db_column, $this->aColumnIndex)];

		}

		return join('<br>', $aReturn);

	}

}
