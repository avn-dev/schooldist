<?php

/**
 * Class Ext_TS_Gui2_Format_AgeAtCourseBeginn
 *
 * Errechnet das Alter zum Zeitpunkt des Kursbeginns.
 */
class Ext_TS_Gui2_Format_AgeAtCourseBeginn extends Ext_Gui2_View_Format_Abstract {

	/**
	 * @param $mValue
	 * @param null $oColumn
	 * @param null $aResultData
	 * @return int
	 */
	public function format($mValue, &$oColumn = null, &$aResultData = null){

		$sAge = 0;

		$oInquiry = Ext_TS_Inquiry_Journey::getInstance($aResultData['journey_id'])->getInquiry();
		/** @var Ext_TS_Inquiry_Contact_Traveller $oTraveller */
		$oTraveller = reset($oInquiry->getJoinTableObjects('travellers'));

		if($oTraveller->getId() > 0) {

			try {
				$dBirthday = new \Core\Helper\DateTime($oTraveller->getBirthday());
				$dFirstCourseStart = new \Core\Helper\DateTime($oInquiry->getFirstCourseStart());
				$dAge = $dBirthday->diff($dFirstCourseStart);

				// y enthält die Jahre, diese Zahl ist das Alter der Buchung zum Kursbeginn.
				$sAge = $dAge->y;
			} catch (Exception $ex) {

			}
		}

		return $sAge;

	}

}