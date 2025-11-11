<?php

namespace Cms\Gui2\Format;

class SnippetFormat extends \Ext_Gui2_View_Format_Abstract {

	// Wandelt den wert wieder in den ursprungswert um
	public function convert($mValue, &$oColumn = null, &$aResultData = null) {

		// Unterstich weil der Wert als PHP-Variable verwendet wird
		$mValue = \Util::getCleanFilename($mValue, '_', false);
		
		return $mValue;
	}

}