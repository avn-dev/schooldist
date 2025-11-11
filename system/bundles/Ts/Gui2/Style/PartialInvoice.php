<?php

namespace Ts\Gui2\Style;

class PartialInvoice extends \Ext_Gui2_View_Style_Abstract {

	/**
	 * Statische FÃ¤rbung (wird im Index gespeichert)
	 *
	 * @inheritdoc
	 */
	public function getStyle($mValue, &$oColumn, &$aRowData) {

		if($aRowData['converted'] !== null) {
			// Anteilig bezahlt (wird ggf. unten Ã¼berschrieben)
			return 'background-color: '.\Ext_Thebing_Util::getColor('good', 30).'; color: '.\Ext_Thebing_Util::getColor('good_font').';';
		}

		return '';
	}

}
