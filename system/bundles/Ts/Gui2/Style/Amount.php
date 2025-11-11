<?php

namespace Ts\Gui2\Style;

class Amount extends \Ext_Gui2_View_Style_Abstract {

	/**
	 * Statische Färbung (wird im Index gespeichert)
	 *
	 * @inheritdoc
	 */
	public function getStyle($mValue, &$oColumn, &$aRowData) {

		if(
			$aRowData['amount_total_original'] > 0 &&
			$aRowData['amount_open_original'] <= 0
		) {
			// Komplett bezahlt
			return 'background-color: '.\Ext_Thebing_Util::getColor('good').'; color: '.\Ext_Thebing_Util::getColor('good_font').';';
		} elseif(
			$aRowData['amount_open_original'] > 0
		) {
			// Anteilig bezahlt (wird ggf. unten Überschrieben)
			return 'background-color: '.\Ext_Thebing_Util::getColor('bad', 30).'; color: '.\Ext_Thebing_Util::getColor('bad_font').';';
		}

		return '';
	}

}
