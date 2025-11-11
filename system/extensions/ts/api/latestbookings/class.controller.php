<?php

class Ext_TS_API_LatestBookings_Controller extends Ext_TS_API_Inquiry_AbstractController {

	use \Core\Traits\MVCControllerToken;

	/**
	 * ruft einen Datensatz ab
	 *
	 * @param string $sToken
	 * @param string $sLanguage
	 * @param string $sDate
	 * @param string|null $sSchool
	 */
	public function getStudents($sToken, $sLanguage, $sDate, $sSchool = null) {

		$bCheckToken = $this->checkToken('ts_api_latestbookings', $sToken);

		$bCheckLanguage = $this->checkLanguageInterface($sLanguage);

		if(!$bCheckToken) {
			$this->_setErrorCode('e0001', 500, $_SERVER['REMOTE_ADDR']);
		}
		if(!$bCheckLanguage) {
			$this->_setErrorCode('e0002');
		}

		$sDate = (string)$sDate;
		$iSchool = (string)$sSchool;

		$bCheck = WDDate::isDate($sDate, WDDate::DB_DATE);

		if(!$bCheck) {
			$this->_setErrorCode('gel0117');
		} else {

			$aStudents = array();
			$aInquiries = $this->searchInquiries($sDate, $iSchool);

			foreach($aInquiries as $iInquiryId) {

				$oInquiry = Ext_TS_Inquiry::getInstance($iInquiryId);

				if($oInquiry) {
					$aStudents[] = $this->getBookingData($oInquiry);
				}

				$iMemory = memory_get_usage(true);
				$iMemory = ($iMemory / 1024) / 1024;

				if($iMemory >= 200) {
					$this->_setErrorCode('e0003');
				}

				// Memory Problem
				WDBasic::clearAllInstances();

			}

			$this->set('bookings', $aStudents);

		}

	}

	/**
	 * @param Ext_TS_Inquiry $oInquiry
	 * @return array
	 */
	private function getBookingData(Ext_TS_Inquiry $oInquiry) {

		$aBookingData = reset(parent::_getTravellersData($oInquiry));

		$oGroup = $oInquiry->getGroup();
		if($oGroup) {
			$aBookingData['group'] = $oGroup->getName();
		}

		// ID überschreiben, da diese sonst die des Kontakts wäre
		$aBookingData['id'] = $oInquiry->id;

		$aBookingData['first_course_start'] = date('Y-m-d', $oInquiry->getFirstCourseStart(true));
		$aBookingData['last_course_end'] = date('Y-m-d', $oInquiry->getLastCourseEnd(true));
		$aBookingData['school_name'] = $oInquiry->getSchool()->getName();

		return $aBookingData;
	}

	/**
	 * Überprüft ob die Sprache gesetzt wurde
	 *
	 * @param string $sLanguage
	 * @return bool
	 * @throws Exception
	 */
	private function checkLanguageInterface($sLanguage) {

		if(
			!empty($sLang) &&
			strlen($sLang) == 2
		){

			$sLang = strtolower($sLang);
			System::setInterfaceLanguage($sLang);

		} else if(!empty($sLang)){

			$this->_setError('Invalid Language Parameter');
			return false;

		}

		return true;

	}

}