<?php

namespace TsAccommodation\Gui2\Style\Requirement\Row;

use TsAccommodation\Service\CheckRequirement;

class Requirement extends \Ext_Gui2_View_Style_Abstract {

	/**
	 * @param mixed $mValue
	 * @param $oColumn
	 * @param array $aRowData
	 * @return string $sStyle
	 */
	public function getStyle($mValue, &$oColumn, &$aRowData) {

		$sStyle = '';

		$oAccommodation = \Ext_Thebing_Accommodation::getInstance($aRowData['accommodation_id']);
		$oRequirement = \TsAccommodation\Entity\Requirement::getInstance($aRowData['id']);

		$oCheckRequirement = new CheckRequirement($oAccommodation, $oRequirement);
		$oCheckRequirement->check();

		if($oCheckRequirement->hasMissingDocument() === true) {
			$sStyle .= 'background: '.\Ext_Thebing_Util::getColor('red').';';
		} elseif($oCheckRequirement->hasExpiredDocument() === true) {
			$sStyle .= 'background: '.\Ext_Thebing_Util::getColor('orange').';';
		} else {
			$sStyle .= '';
		}

		return $sStyle;

	}

}
