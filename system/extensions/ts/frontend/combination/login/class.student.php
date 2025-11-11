<?php


/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of class
 *
 * @author Mehmet Durmaz
 */

class Ext_TS_Frontend_Combination_Login_Student extends Ext_TS_Frontend_Combination_Login_Abstract {

	// Objekt mit Fehlern die aufgetreten sind
	protected $_oMessage = null;

	protected $_oCustomer	= null;
	protected $_aBookings	= array();
	protected $_oBooking	= null;

	// landing Page
	protected function _default() {
		$this->_showIndexData();
	}

	// Schuldaten
	protected function _showIndexData() {

		$this->_initUserData();
		
		$oData = new Ext_TS_Frontend_Combination_Login_Student_Default($this);
	}
	
	// Schuldaten
	protected function _showSchoolData() {
		$this->_initUserData();
		
		$oData = new Ext_TS_Frontend_Combination_Login_Student_School($this);
	}

	protected function _showGeneralData() {
		$this->_showPersonalData();
	}

	//Kundendaten
	protected function _showPersonalData() {
		$this->_initUserData();

		$oData = new Ext_TS_Frontend_Combination_Login_Student_Personal($this);
	}

	//Buchungsdaten
	protected function _showBookingData() {
		$this->_initUserData();

		$oData = new Ext_TS_Frontend_Combination_Login_Student_Booking($this);
	}

	//Dokumente
	protected function _showDocuments() {
		$this->_initUserData();

		$oData = new Ext_TS_Frontend_Combination_Login_Student_Document($this);
	}

	//Kommunikation
	protected function _showMails() {
		$this->_initUserData();

		$oData = new Ext_TS_Frontend_Combination_Login_Student_Mail($this);
		
	}
	
	// Kursdaten
	protected function _showCourseData() {
		$this->_initUserData();
		
		$oData = new Ext_TS_Frontend_Combination_Login_Student_Course($this);
	}
	
	// Unterkünfte Daten
	protected function _showAccommodationData() {
		$this->_initUserData();
		
		$oData = new Ext_TS_Frontend_Combination_Login_Student_Accommodation($this);
	}
	
	// Transfer Daten
	protected function _showTransferData() {
		$this->_initUserData();
		
		$oData = new Ext_TS_Frontend_Combination_Login_Student_Transfer($this);
	}
	
	// Insurance Daten
	protected function _showInsuranceData() {
		$this->_initUserData();
		
		$oData = new Ext_TS_Frontend_Combination_Login_Student_Insurance($this);
	}
	
	protected function _getCustomerDbId() {
		return '77'; 
	}
	
	/**
	 * Standard Variablen setzen
	 */
	public function initDefaultVars() {
		// Base URL
		$this->_assign('sBaseURL', $this->_getBaseUrl());
	}
	
	/**
	 * Adresse unter der das Snippet errechbar ist
	 * @return type 
	 */
	protected function _getBaseUrl() {
		return '?';
	}

	protected function _initUserData() {
		//oder doch booker?
		$this->_oCustomer = Ext_TS_Inquiry_Contact_Traveller::getInstance($this->_aUserData['data']['contact_id']);

		$aBookings = (array)$this->_oCustomer->getInquiries();
		if(empty($aBookings)) {
			$this->_aUserData = array();
			$this->_showLogin();
			$this->_setError(Ext_TS_Frontend_Messages::ERROR_NO_BOOKING);
		}

		// Flag für eingeloggt sei
		$this->_assign('iLoggedIn', 1);

		$this->_aBookings = $aBookings;

		$aFilterBookings = array();

		$iCounter = 1;
		foreach($aBookings as $iBookingId)
		{
			$aFilterBookings[$iBookingId] = $this->t('Booking').$iCounter;

			$iCounter++;
		}

		$this->_assign('aBookings', $aFilterBookings);
		$this->_assign('sFirstname', $this->_oCustomer->firstname);


		if(
			isset($this->_aVars['student_booking']) &&
			array_key_exists($this->_aVars['student_booking'], $aFilterBookings)
		) {
			$iBooking = (int)$this->_aVars['student_booking'];

			setcookie("booking", $iBooking);
		} else {
			if(
				isset($_COOKIE['booking'])
			) {
				$iBooking = $_COOKIE['booking'];
			} else {
				$iBooking = (int)key($aFilterBookings);

				setcookie("booking", $iBooking);
			}
		}

		$this->_oBooking = Ext_TS_Inquiry::getInstance($iBooking);

        \Core\Handler\SessionHandler::getInstance()->set('sid', $this->_oBooking->getSchool()->id);

		if(
			$this->_isDev()
		) {
			global $user_data;
			$user_data['name'] = $this->_oCustomer->nickname;
		}

		$this->_assign('iCurrentBooking', $this->_oBooking->id);
	}

	public function getCustomer() {
		return $this->_oCustomer;
	}

	public function getBookings() {
		return $this->_aBookings;
	}
	
	public function getBooking() {
		return $this->_oBooking;
	}

	protected function _getNavItems() {
		return array(
			'showIndexData',
			'showSchoolData',
			'showGeneralData' => array(
				'showPersonalData',
				'showBookingData',
				'showDocuments',
				'showMails'
			),
			'showCourseData',
			'showAccommodationData',
			'showTransferData',
			'showInsuranceData'
		);
	}
	
	protected function getLoginEmail($iLoginId) {
		
		$oTraveller = Ext_TS_Inquiry_Contact_Traveller::getInstance($iLoginId);
		
		$oEmail = $oTraveller->getFirstEmailAddress();
		
		if($oEmail instanceof Ext_TC_Email_Address) {
			return $oEmail->email;
		}
	}
	
}
