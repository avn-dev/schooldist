<?php

namespace TsCompany\Gui2\Selection;

class SubAgencies extends \Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {
		
		$agencies = \Ext_Thebing_Client::getFirstClient()->getAgencies(true);
		
		// Aktuelle Agentur entfernen weil die Agentur nicht von sich selber Subagency sein kann
		unset($agencies[$oWDBasic->id]);
		
		return \Util::addEmptyItem($agencies, $this->_oGui->t('Keine Unteragentur'));
	}

}

