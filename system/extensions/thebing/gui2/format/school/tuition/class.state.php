<?php


class Ext_Thebing_Gui2_Format_School_Tuition_State extends Ext_Gui2_View_Format_Abstract {

	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$aReturn = array();

		if(is_numeric($mValue)) {
			
			$iValue = (int)$mValue;

			foreach(TsTuition\Helper\State::BINARY_MAPPING as $iState => $sState) {
				if($iValue & $iState) {
					$aReturn[] = $sState;
				}
			}

		}
	
		return implode(" ", $aReturn);
	}
	
	public function getTitle(&$oColumn = null, &$aResultData = null) {
		global $_VARS;

		$aReturn = array();
		$aReturn['path'] = '/wdmvc/ts/controller/tuition/lastAllocation';
		$aReturn['values'] = array(
			'week' => $_VARS['filter']['week'],
			'inquiry_course_id'	=> $aResultData['inquiry_course_id'],
			'course_id' => $aResultData['course_id'],
			'column' => $oColumn->db_column,
		);
		$aReturn['tooltip'] = 'wdmvc';
		$aReturn['tooltip_width'] = 1000;
		
		return $aReturn;
	}

}
