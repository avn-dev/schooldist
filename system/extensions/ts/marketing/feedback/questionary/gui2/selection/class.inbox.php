<?php

class Ext_TS_Marketing_Feedback_Questionary_Gui2_Selection_Inbox extends Ext_TC_Marketing_Feedback_Questionary_Gui2_Selection_SubObjects {

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param WDBasic $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aSchools = $oWDBasic->objects;
		if(empty($aSchools)) {
			return array();
		}
		
		$oClient = Ext_Thebing_Client::getInstance();
		$aInboxes = $oClient->getInboxList('use_id');
		
		return $aInboxes;
	}
	
}
