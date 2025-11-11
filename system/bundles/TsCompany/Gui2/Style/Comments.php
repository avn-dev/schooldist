<?php

namespace TsCompany\Gui2\Style;

use TsCompany\Entity\Company;

class Comments extends \Ext_Gui2_View_Style_Abstract {

	/**
	 * @param mixed $mValue
	 * @param null $oColumn
	 * @param array $aRowData
	 * @return string
	 */
	public function getStyle($mValue, &$oColumn, &$aRowData) {

		$sStyle = '';

		if(isset($aRowData['follow_up'])) {
			$bDue = $this->checkFollowup($aRowData['follow_up']);
		} else {
			$bDue = $this->checkFollowupForCompany((int)$aRowData['id']);
		}

		if($bDue) {
			$sStyle .= 'background: '.\Ext_TC_Util::getColor('red').';';
		}

		return $sStyle;

	}

	/**
	 * Prüft ob das Nachhake Datum fällig ist.
	 *
	 * @param string $sFollowup
	 * @return bool
	 */
	private function checkFollowup($sFollowup) {

		if(
			empty($sFollowup) ||
			$sFollowup === '0000-00-00'
		) {
			return false;
		}

		$oDate	= new \DateTime($sFollowup);
		$oDate2	= new \DateTime();
		$oDate2->setTime(0, 0, 0);

		if($oDate < $oDate2) {
			return true;
		}
		return false;
	}

	/**
	 * Prüft den Follow Up für die Agenturliste.
	 *
	 * @param int $iCompanyId
	 *
	 * @return bool
	 */
	private function checkFollowupForCompany($iCompanyId) {

		$oCompany = Company::getInstance($iCompanyId);
		$aComments = $oCompany->getJoinTableObjects('comments');

		$bDue = false;
		foreach($aComments as $oComment) {
			if(!empty($oComment->follow_up)) {
				$bDue = $this->checkFollowup($oComment->follow_up);
				if($bDue) {
					$bDue = true;
					break;
				}
			}
		}

		return $bDue;
	}
}
