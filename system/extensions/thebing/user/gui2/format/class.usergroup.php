<?php

class Ext_Thebing_User_Gui2_Format_UserGroup extends Ext_Gui2_View_Format_Abstract {

	/**
	 * {@inheritdoc}
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$aRetVal = [];

		$oUser = Ext_Thebing_User::getInstance($aResultData['id']);
		$aGroups = $oUser->getUserGroups();

        // Zuweisungen zu inaktive Schulen entfernen
        foreach($aGroups as $iGroupKey => $aGroup) {
            $oSchool = Ext_Thebing_School::getInstance($aGroup['school_id']);
            if(!$oSchool->isActive()) {
                unset($aGroups[$iGroupKey]);
            }
        }

		$oAccess = new Ext_Thebing_Access();
		$aAccessGroups = $oAccess->getAccessGroups();

		if($this->hasOneGroup($aGroups)) {
			$aGroup = reset($aGroups);
			$aRetVal[] = $aAccessGroups[$aGroup['group_id']];
		} else {
			foreach($aGroups as $aGroup) {
				$oSchool = Ext_Thebing_School::getInstance($aGroup['school_id']);
                $aRetVal[] = $aAccessGroups[$aGroup['group_id']].' - '.substr($oSchool->getName(), 0, 50).'…';
			}
		}

		return implode('<br />', $aRetVal);
	}

	/**
	 * Prüft, ob der User nur einer Gruppe zugewiesen ist.
	 *
	 * @param mixed[] $aGroups
	 * @return bool
	 */
	private function hasOneGroup($aGroups) {

		$aSelectedGroups = [];

		foreach($aGroups as $aGroup) {
			$aSelectedGroups[$aGroup['group_id']] = true;
		}

		if(count($aSelectedGroups) === 1) {
			return true;
		}

		return false;
	}

}
