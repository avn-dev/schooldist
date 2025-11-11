<?php

namespace Cms\Gui2\Selection;

class LanguageSelection extends \Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$oSite = \Cms\Entity\Site::getInstance($oWDBasic->site_id);

		$aLanguages = $oSite->getLanguages(1);
		$aLanguageLabels  = \Factory::executeStatic('Util', 'getLanguages', 'frontend');
		
		$aReturn = array_intersect_key($aLanguageLabels, array_flip($aLanguages));
		
		$aReturn = \Util::addEmptyItem($aReturn);
		
		return $aReturn;
	}

}