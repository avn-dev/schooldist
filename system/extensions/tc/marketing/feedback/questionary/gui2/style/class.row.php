<?php

class Ext_TC_Marketing_Feedback_Questionary_Gui2_Style_Row extends \Ext_Gui2_View_Style_Abstract {

	/**
	 * @param mixed $mValue
	 * @param $oColumn
	 * @param array $aRowData
	 * @return string
	 */
	public function getStyle($mValue, &$oColumn, &$aRowData) {

		$sStyle = '';

		if($this->checkPast($aRowData)) {
			$sStyle .= 'background: '.\Ext_TC_Util::getColor('red').';'; 
		}

		return $sStyle;

	}

	/**
	 * @param array $aRowData
	 * @return bool
	 */
	public function checkPast(array $aRowData) {

		if(
			$aRowData['follow_up'] !== null &&
			$aRowData['follow_up'] !== '0000-00-00'
		) {
			$dFollowUp = new \DateTime($aRowData['follow_up']);
			$dNow = new \DateTime();
			$dNow->setTime(0, 0, 0);

			if($dFollowUp < $dNow) {
				return true;
			}

		}

		return false;

	}

}