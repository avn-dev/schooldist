<?php

namespace TsComplaints\Gui2\Style\Customer\Row;

class Complaint extends \Ext_Gui2_View_Style_Abstract {

	/**
	 * @param mixed $mValue
	 * @param $oColumn
	 * @param array $aRowData
	 * @return string
	 */
	public function getStyle($mValue, &$oColumn, &$aRowData) {

		$sStyle = '';

		if(
			$this->checkFollowup($aRowData) ||
			(
				$aRowData['state'] !== 'resolved' &&
				$this->checkPast($aRowData)
			)
		) {
			$sStyle .= 'background: '.\Ext_Thebing_Util::getColor('red').';';
		} elseif($aRowData['state'] === 'resolved') {
			$sStyle .= 'background: '.\Ext_Thebing_Util::getColor('lightgreen').';';
		}

		return $sStyle;

	}

	/**
	 * @param array $aRowData
	 * @return bool
	 */
	private function checkFollowup(array $aRowData) {

		if(
			$aRowData['state'] === 'followup' &&
			$this->checkPast($aRowData)
		) {
			return true;
		}

		return false;
	}

	/**
	 * @param array $aRowData
	 * @return bool
	 */
	public function checkPast(array $aRowData) {

		$sDate	= $aRowData['followup'];
		$oDate	= new \DateTime($sDate);

		$oDate2	= new \DateTime();
		$oDate2->setTime(0, 0, 0);

		if($oDate < $oDate2) {
			return true;
		}

		return false;
	}

}