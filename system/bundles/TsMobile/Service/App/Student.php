<?php

namespace TsMobile\Service\App;

use TsMobile\Service\AbstractApp;

class Student extends AbstractApp {
	
	/**
	 * @var \Ext_TS_Inquiry_Contact_Abstract 
	 */
	protected $_oUser = null;

	/**
	 * @var string 
	 */
	protected $_sType = 'student';


	protected $iRequestInquiryId = null;

	/**
	 * @var \Ext_TS_Inquiry 
	 */
	protected $_oInquiry = null;

	/**
	 * @var int
	 */
	protected $iInquiryCount;
	
	/**
	 * Liefert die Schule für die Daten geholt werden sollen
	 * ACHTUNG - bei mehreren gebuchten Schulen wird die Schule zurückgeliefert, dessen
	 * Leistungsbeginn am ehesten ist
	 * 
	 * @return \Ext_Thebing_School
	 */
	public function getSchool() {
		
		if(!($this->_oSchool instanceof \Ext_Thebing_School)) {
			$oInquiry = $this->getInquiry();
			$this->_oSchool = $oInquiry->getSchool();			
		}
		
		return $this->_oSchool;
	}
	
	public function getBookingData() {
		$aBookingData = array();		
		
		$oInquiry = $this->getInquiry();	
		$oSchool = $this->getSchool();

		$aBookingData[$oSchool->id] = array(
			'school' => $oSchool,
			'courses' => $oInquiry->getCourses(true),
			'accommodations' => $oInquiry->getAccommodations(),				
			'transfers' => $oInquiry->getTransfers('', true),
			'insurances' => $oInquiry->getInsurances(true)
		);

		return $aBookingData;
	}
	
	/**
	 * Verifiziert den User anhand der E-Mail-Adresse
	 * 
	 * @param string $sEmail
	 * @return null|\Ext_TS_Inquiry_Contact_Traveller
	 */
	public function verifyUserByEmail($sEmail) {
		
		$sSql = "
			SELECT
				`tc_c`.`id`
			FROM
				`tc_contacts` `tc_c` INNER JOIN
				`ts_inquiries_to_contacts` `ts_itc` ON
					`ts_itc`.`contact_id` = `tc_c`.`id` INNER JOIN
				`ts_inquiries_contacts_logins` `ts_icl` ON
					`ts_icl`.`contact_id` = `tc_c`.`id` AND
					`ts_icl`.`active` = 1 INNER JOIN
				`tc_contacts_to_emailaddresses` `tc_cte` ON
					`tc_cte`.`contact_id` = `tc_c`.`id` INNER JOIN
				`tc_emailaddresses` `tc_e` ON
					`tc_e`.`id` = `tc_cte`.`emailaddress_id` AND
					`tc_e`.`active` = 1 AND
					`tc_e`.`email` = :email
			WHERE
				`tc_c`.`active` = 1
			LIMIT 
				1
		";
		
		$aSql = array('email' => $sEmail);
		
		$iId = (int) \DB::getQueryOne($sSql, $aSql);
		
		if($iId > 0) {
			$oUser = \Ext_TS_Inquiry_Contact_Traveller::getInstance($iId);
			return $oUser;
		}
	}
	
	/**
	 * Liefert die Zugangsdaten des Users anhand des Zugangscode
	 * 
	 * Rückgabewert:
	 * - username
	 * - password
	 * 
	 * @param string $sAccessCode
	 * @return array
	 */
	public function getUserDataByAccessCode($sAccessCode) {

		$oRepository = \Ext_TS_Inquiry_Contact_Login::getRepository();
		$oUser = $oRepository->findOneBy(array('access_code' => $sAccessCode)); /** @var $oUser \Ext_TS_Inquiry_Contact_Login */

		if(!$oUser) {
			return array();
		}

		// Wenn das Passwort nie verschickt wurde, muss das jetzt generiert werden, da das ansonsten alles nicht funktioniert
		if(empty($oUser->password)) {
			$oUser->generatePassword();
		}

		$aReturn = array(
			'username' => $oUser->nickname,
			'password' => $oUser->password
		);
		
		return $aReturn;
	}
	
	/**
	 * Sendet dem Kunden den generierten Zugangscode
	 * 
	 * @param string $sEmail
	 * @param string $sAccessCode
	 * @return boolean
	 */
	public function sendAccessCode($sEmail, $sAccessCode) {

		$oInquiry = $this->getInquiry();
		$oSchool = $this->getSchool();

		$oTemplate = $oSchool->getTemplateForMobileAppForgottenPassword();
		if(!$oTemplate instanceof \Ext_Thebing_Email_Template) {
			return false;
		}

		$aMailData = \Ext_Thebing_Mail::createMailDataArray($oInquiry, $this->_oUser, $oSchool, $oTemplate, array());

		if($aMailData !== null) {

			//$oUserLogin = \Ext_TS_Inquiry_Contact_Login::getRepository()->findOneBy(array('contact_id' => $this->_oUser->id));

			$aMailData['to'] = $sEmail;
			//$mBack['content'] = str_replace('{user_name}', $oUserLogin->nickname, $mBack['content']);

			if(strpos($aMailData['content'], '{user_login_code}') !== false) {
				(new \Ext_Thebing_Inquiry_Placeholder())->addMonitoringEntry('user_login_code');
				$aMailData['content'] = str_replace('{user_login_code}', $sAccessCode, $aMailData['content']);
			}

			\Ext_Thebing_Mail::sendAutoMail($aMailData, 'student_login');

			return true;
		}
		
		return false;
	}
	
	/**
	 * Speichert den Zugangscode zu einem User
	 * 
	 * @param string $sAccessCode
	 * @return boolean
	 */
	public function saveAccessKey($sAccessCode) {

		/** @var \Ext_TS_Inquiry_Contact_Login $oUserLogin */
		$oRepository = \Ext_TS_Inquiry_Contact_Login::getRepository();
		$oUserLogin = $oRepository->findOneBy(array('contact_id' => $this->_oUser->id));
		
		if($oUserLogin) {
			$oUserLogin->access_code = $sAccessCode;
			$oUserLogin->save();
			return true;
		}
		
		return false;
	}
	
	/**
	 * Prüft ob das Passwort zu dem aktuellen Benutzer passt
	 * 
	 * @param string $sPassword
	 * @return boolean
	 */
	public function checkPassword($sPassword) {

		/** @var \Ext_TS_Inquiry_Contact_Login $oUserLogin */
		$oRepository = \Ext_TS_Inquiry_Contact_Login::getRepository();
		$oUserLogin = $oRepository->findOneBy(array('contact_id' => $this->_oUser->id));

		if(
			$oUserLogin && (
				!empty($oUserLogin->password) &&
				password_verify($oUserLogin->password, $sPassword)
			) || (
				!empty($oUserLogin->access_code) &&
				$oUserLogin->access_code === $sPassword
			)
		) {
			return true;
		}
		
		return false;
	}	
	
	/**
	 * Ändert das Passwort für den aktuellen Benutzer
	 * 
	 * @param string $sPassword
	 * @return boolean
	 */
	public function changePassword($sPassword) {

		/** @var \Ext_TS_Inquiry_Contact_Login $oUserLogin */
		$oRepository = \Ext_TS_Inquiry_Contact_Login::getRepository();
		$oUserLogin = $oRepository->findOneBy(array('contact_id' => $this->_oUser->id));
		
		if($oUserLogin && !$oUserLogin->credentials_locked) {
			$oUserLogin->password = password_hash($sPassword, PASSWORD_DEFAULT);
			$oUserLogin->access_code = '';
			$oUserLogin->save();
			return true;
		}
		
		return false;
	}
	
	/**
	 * Bindet den angemeldeten Benutzer an das Data-Model
	 * 
	 * @param \Ext_TS_Inquiry_Contact_Abstract $oUser
	 */
	public function setUser($oUser) {
		
		if(!$oUser instanceof \Ext_TS_Inquiry_Contact_Abstract) {
			throw new \RuntimeException('The parameter must be an instanceof "\Ext_TS_Inquiry_Contact_Abstract".');
		}
		
		$this->_oUser = $oUser;
	}	
	
	/**
	 * Liefert alle Menüpunkte der Schüler-App
	 * 
	 * @return array
	 */
	public function getPages() {
		$aPages = parent::getPages();
		
		$aPages['top']['items']['personal'] = array(
			'title' => $this->t('Personal data'),
			'class' => '\\TsMobile\\Generator\\Pages\\Personal\\Student',
			'type' => 'html'
		);
		$aPages['top']['items']['booking'] = array(
			'title' => $this->t('Booking data'),
			'class' => '\\TsMobile\\Generator\\Pages\\Booking',
			'type' => 'html'
		);
		$aPages['top']['items']['accommodation'] = array(
			'title' => $this->t('Accommodation data'),
			'class' => '\\TsMobile\\Generator\\Pages\\Accommodation',
			'type' => 'nested_list_view'
		);
		$aPages['top']['items']['attendance'] = array(
			'title' => $this->t('Attendance'),
			'class' => '\\TsMobile\\Generator\\Pages\\Attendance',
			'type' => 'select_list'
		);
		$aPages['top']['items']['documents'] = array(
			'title' => $this->t('Documents'),
			'class' => '\\TsMobile\\Generator\\Pages\\Documents',
			'type' => 'document_list'
		);

		// collapsible_list neu in 1.1.0
		if(
			$this->getVersion() === null ||
			version_compare($this->getVersion(), '1.1.0', '>=')
		) {
			$aPages['top']['items']['faq'] = array(
				'title' => $this->t('Student handbook'),
				'class' => '\\TsMobile\\Generator\\Pages\\Faq',
				'type' => 'collapsible_list'
			);
		}

		$aHookData = [
			'pages' => &$aPages,
			'app' => $this
		];

		\System::wd()->executeHook('ts_mobile_app_student_pages', $aHookData);

		return $aPages;
	}

	/**
	 * Buchung des Schülers ermitteln (ID vom Request oder erstbeste)
	 *
	 * @return \Ext_TS_Inquiry
	 */
	public function getInquiry() {

		if(!$this->_oInquiry) {

			// Wenn Request Inquiry ID enthält: Prüfen, ob die Inquiry zum Kontakt gehört
			if($this->iRequestInquiryId) {
				$aInquiries = $this->getUser()->getInquiries(false, true);
				$this->iInquiryCount = count($aInquiries);
				foreach($aInquiries as $oInquiry) {
					if($oInquiry->id == $this->iRequestInquiryId) {
						$this->_oInquiry = $oInquiry;
						return $this->_oInquiry;
					}
				}
			}

			$this->_oInquiry = $this->_oUser->getClosestInquiry();
		}
		
		return $this->_oInquiry;
	}

	/**
	 * Anzahl der Buchungen dieses Schülers
	 *
	 * @return int
	 */
	public function getInquiryCount() {
		if($this->iInquiryCount === null) {
			$this->iInquiryCount = count($this->getUser()->getInquiries(false, true));
		}

		return $this->iInquiryCount;
	}

	/**
	 * @param int $iInquiryId
	 */
	public function setRequestInquiryId($iInquiryId) {
		$this->iRequestInquiryId = $iInquiryId;
	}

	/**
	 * Seiten filtern, welche aktiviert/deaktiviert sind
	 *
	 * @param array $aPages
	 * @return array
	 */
	public function filterPages(array $aPages) {

		/*$oSchool = $this->getSchool();
		$oAppConfig = $oSchool->getAppSettingsConfig();

		$aEnabledPages = array_map(function($aSetting) {
			return $aSetting['additional'];
		}, $oAppConfig->getValue('enabled_page'));*/

		if(empty($aEnabledPages)) {
			// Wenn leer, wurde nichts konfiguriert, also alles anzeigen
			return $aPages;
		}

		foreach($aPages as $sLayer => &$aLayerData) {
			foreach($aLayerData['items'] as $sPage => $aPageData) {

				if(
					$sLayer !== 'bottom' && // Punkte der unteren Navigation werden immer angezeigt
					$sPage !== 'welcome' && // Willkommensseite wird immer angezeigt
					!in_array($sPage, $aEnabledPages)
				) {
					unset($aLayerData['items'][$sPage]);
				}

			}
		}

		return $aPages;
	}
	
}
