<?php

namespace Ts\Gui2\Format;

class TotalCorrectAnswers extends \Ext_Gui2_View_Format_Abstract {

	public function format($value, &$column = null, &$resultData = null) {
		return \Ext_Thebing_Placementtests_Results::getInstance($resultData['placementtest_result_id'])->getFormattedTotalCorrectAnswers();
	}
}

