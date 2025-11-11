<?php

/**
 * Class Style
 */
class Ext_TC_Validity_Gui2_Style extends \Ext_Gui2_View_Style_Abstract {

	/**
	 * @param null $mValue
	 * @param null $oColumn
	 * @param array $aRowData
	 * @return string
	 */
	public function getStyle($mValue, &$oColumn, &$aRowData) {

		$sStyle = '';

		$dNow = new DateTime('now');
		$dValidFrom = new DateTime($aRowData['valid_from']);

		if(
			$aRowData['valid_until'] === '0000-00-00' ||
			$aRowData['valid_until'] === ''
		) {

			if($dValidFrom <= $dNow) {
				$sStyle .= 'background: '.\Ext_TC_Util::getColor('marked').'; ';
			}

		} else {

			$dValidUntil = new DateTime($aRowData['valid_until']);

			if(
				$dValidFrom <= $dNow &&
				$dNow <= $dValidUntil
			) {
				$sStyle .= 'background: '.\Ext_TC_Util::getColor('marked').'; ';
			}

		}

		return $sStyle;

	}

}