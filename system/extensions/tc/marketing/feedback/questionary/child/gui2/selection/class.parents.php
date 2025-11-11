<?php

class Ext_TC_Marketing_Feedback_Questionary_Child_Gui2_Selection_Parents extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param WDBasic $oWDBasic
	 * @return array
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$oQuestionary = Ext_TC_Marketing_Feedback_Questionary::getInstance($oWDBasic->questionnaire_id);

		$aSelectOptions = array();
		$aChilds = $oQuestionary->getChildsSelectOptions();

		// Beim erstellen einer neuen Question/Heading
		// sind alle SelectedOptions möglich
		if($oWDBasic->id == 0) {
			$aSelectOptions = $aChilds;
			$aChilds = array();
		}

		// Überprüft das Array und deren Inhalt
		// damit keine Endlosschleifen durch die Abhängigkeiten
		// entstehen können
		foreach($aChilds as $iChildId => $sChildName) {

			if(
				$iChildId == $oWDBasic->id ||
				$this->isChild($iChildId, $oWDBasic->id)
			) {
				continue;
			}
			$aSelectOptions[$iChildId] = $sChildName;

		}
		$aSelectOptions = Ext_TC_Util::addEmptyItem($aSelectOptions);

		return $aSelectOptions;
	}

	/**
	 * Prüft ob die angegebene ChildId ein Child von der
	 * angegebenen ParentId ist
	 *
	 * @param $iChildId
	 * @param $iParentId
	 * @return bool
	 */
	private function isChild($iChildId, $iParentId) {

		$oChild = Ext_TC_Marketing_Feedback_Questionary_Child::getInstance($iChildId);

		$aParents = $oChild->getParents();
		foreach($aParents as $oPartent) {
			if($oPartent->id == $iParentId) {
				return true;
			}
		}

		return false;
	}
	
}
