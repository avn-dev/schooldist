<?php

namespace Ts\Gui2\Format;

/**
 * @internal
 */
class Traveller extends \Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$sName = "";

		if(isset($aResultData['inquiry_id'])) {

			$oInquiry = \Ext_TS_Inquiry::getInstance($aResultData['inquiry_id']);

			$oTraveller = $oInquiry->getTraveller();

			$sName = $oTraveller->getName();

		}

		return $sName;
	}

}
