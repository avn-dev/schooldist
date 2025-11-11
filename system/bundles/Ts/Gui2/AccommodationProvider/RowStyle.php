<?php

namespace Ts\Gui2\AccommodationProvider;

class RowStyle extends \Ext_Gui2_View_Style_Abstract {

	/**
	 * @param null $mValue
	 * @param null $oColumn
	 * @param array $aRowData
	 * @return string
	 */
	public function getStyle($mValue, &$oColumn, &$aRowData) {

		$sStyle = '';

		if((int)$aRowData['requirement_missing'] === 1) {

			$sStyle .= 'background: '.\Ext_Thebing_Util::getColor('red').';';

		} else if((int)$aRowData['requirement_expired'] === 1) {

			$sStyle .= 'background: '.\Ext_Thebing_Util::getColor('orange').';';

		} else {
			$sStyle .= '';
		}

		return $sStyle;
	}

}
