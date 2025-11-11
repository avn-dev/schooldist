<?php

class Ext_TS_Marketing_Feedback_Questionary extends Ext_TC_Marketing_Feedback_Questionary {
	
	/**
	 * 
	 * @return array 
	 */
	public static function getSubObjectLabel(bool $bPlural=true) {
		$sLabel = L10N::t('Inbox');
		return $sLabel;		
	}

	/**
	 * Gibt ein Array für Selectfilter zurück
	 *
	 * @return array
	 */
	public static function getSelectOptions() {

		$aList = array();
		$iSchoolId = Ext_Thebing_School::getSchoolFromSession()->getId();

		if($iSchoolId > 0) {
			$aQuestionaries = self::getRepository()->findAll();
			foreach($aQuestionaries as $oQuestionary) {
				$aSchools = $oQuestionary->objects;
				if(in_array($iSchoolId, $aSchools)) {
					$aList[$oQuestionary->getId()] = $oQuestionary->getName();
				}
			}
		} else {
			$oSelf = new self;
			$aList = $oSelf->getArrayList(true, 'name');
		}

		asort($aList);

		return $aList;
	}

	/**
	 * Überprüft ob die Subobjects die Journey beinhaltet
	 *
	 * @param $iJourneyId
	 * @return bool
	 */
	public function checkSubObjectsByJourneyId($iJourneyId) {

		$oInquiry = Ext_TS_Inquiry_Journey::getInstance($iJourneyId)->getInquiry();
		$bInArray = in_array($oInquiry->getInbox()->id, $this->subobjects);

		return $bInArray;
	}
	
}
