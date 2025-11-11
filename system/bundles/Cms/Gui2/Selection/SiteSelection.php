<?php

namespace Cms\Gui2\Selection;

class SiteSelection extends \Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$oRepo = \Cms\Entity\Site::getRepository();
		$aSites = $oRepo->findAll();

		$aReturn = [
			'' => ''
		];
		foreach($aSites as $oSite) {
			$aReturn[$oSite->id] = $oSite->name;
		}
		
		return $aReturn;
	}

}