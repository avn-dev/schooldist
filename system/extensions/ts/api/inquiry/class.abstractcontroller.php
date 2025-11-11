<?php

abstract class Ext_TS_API_Inquiry_AbstractController extends MVC_Abstract_Controller {

	protected $_sAccessRight = '';

	/**
	 * Alle Buchungen nach Änderungsdatum suchen
	 * Auch Änderungen an den Unterkunftszuweisungen berücksichtigen
	 * @param string $sDate
	 * @param int $iSchool
	 * @return array
	 */
	protected function searchInquiries($sDate, $iSchool = null){

		$sSchoolStatement = '';

		if(!empty($iSchool)) {
			$sSchoolStatement = " `tij`.`school_id` = :school_id AND ";
		}

		$sSql = "
			SELECT
				`ti`.`id`
			FROM
				`ts_inquiries` `ti` LEFT JOIN
				`ts_inquiries_journeys` `tij` ON
					`ti`.`id` = `tij`.`inquiry_id` LEFT JOIN
				`ts_inquiries_journeys_accommodations` `tija` ON
					`tij`.`id` = `tija`.`journey_id` LEFT JOIN
				`kolumbus_accommodations_allocations` `kaa` ON
					`kaa`.`inquiry_accommodation_id` = `tija`.`id`
			WHERE
				".$sSchoolStatement."
				`ti`.`active` = 1 AND
				(
					`ti`.`changed` > :date OR
					`kaa`.`changed` > :date
				)
			GROUP BY
				`ti`.`id`
		";

		$aResult = (array)DB::getQueryCol($sSql, array(
			'date' => $sDate,
			'school_id' => (int)$iSchool
		));

		return $aResult;
	}

	/**
	 * Reisende Aufbereiten
	 * @param Ext_TS_Inquiry $oInquiry
	 * @return array
	 */
	protected function _getTravellersData(Ext_TS_Inquiry $oInquiry){

		$aTravellerData = array();
		$aTravellers	= $oInquiry->getTravellers();

		foreach($aTravellers as $oTraveller){
			$aTravellerData[] = $this->_getTravellerData($oTraveller);
		}

		return $aTravellerData;
	}

	/**
	 * Daten eines Reisenden(buchers)
	 * @param Ext_TS_Inquiry_Contact_Traveller $oTraveller
	 * @return array
	 */
	protected function _getTravellerData($oTraveller){

		$sNumber = '';
		if(method_exists($oTraveller, 'getCustomerNumber')){
			$sNumber = $oTraveller->getCustomerNumber();
		}

		$aTravellerData = array(
			'id' => (int)$oTraveller->id,
			'number' => $sNumber,
			'firstname' => $oTraveller->firstname,
			'lastname' => $oTraveller->lastname,
			'birthday' => $oTraveller->birthday,
			'gender_key' => (int)$oTraveller->gender,
			'gender' => $oTraveller->getFrontendGender(System::getInterfaceLanguage()),
			'nationality' => $oTraveller->nationality,
		);

		return $aTravellerData;
	}

}