<?php

class Ext_Thebing_Teacher_Placeholder extends Ext_Thebing_Placeholder {

	/**
	 * @var Ext_Thebing_Teacher
	 */
	protected $_oTeacher;

	public function  __construct($iTeacherId = null) {

		if(is_null($iTeacherId)){
			return;
		}

		if ($iTeacherId instanceof Ext_Thebing_Teacher) {
			$this->_oTeacher = $iTeacherId;
		} else {
			$this->_oTeacher = Ext_Thebing_Teacher::getInstance($iTeacherId);
		}

		$this->_iFlexId = $this->_oTeacher->id;

		$this->_sSection = 'teachers';


		parent::__construct();

	}

	
	/**
	 * Get the list of available placeholders
	 *
	 * @return array
	 */
	public function getPlaceholders($sType = '') {

		// Auch die Platzhalter der Dyn. Felder von Lehrern u Unterkünften anzeigen
		$aSections = array($this->_sSection);

		$aFlexFields = Ext_TC_Flexibility::getSectionFieldData($aSections, true);

		$aPlaceholderFlex = array();

		foreach((array)$aFlexFields as $oFlexField){
			$sPlaceholder = $oFlexField->placeholder;
			if(!empty($sPlaceholder)){
				$aPlaceholderFlex[$sPlaceholder] = $oFlexField->description;
			}
		}


		$aPlaceholders = array(
			array(
				'section'		=> $this->_t('Generelle Platzhalter'),
				'placeholders'	=> array(
					'today'								=> $this->_t('Heute'),
					'school_name'						=> $this->_t('Schule'),
					'school_address'					=> $this->_t('Adresse der Schule'),
					'school_address_addon'				=> $this->_t('Adresszusatz der Schule'),
					'school_zip'						=> $this->_t('PLZ der Schule'),
					'school_city'						=> $this->_t('Stadt der Schule'),
					'school_country'					=> $this->_t('Land der Schule'),
					'school_url'						=> $this->_t('URL der Schule'),
					'school_phone'						=> $this->_t('Telefon der Schule'),
					'school_phone2'						=> $this->_t('Telefon 2 der Schule'),
					'school_email'						=> $this->_t('E-Mail der Schule')
				)
			),
			array(
				'section'		=> $this->_t('Lehrer Details'),
				'placeholders'	=> array(
					'teacher_salutation'				=> $this->_t('Anrede'),
					'teacher_lastname'					=> $this->_t('Nachname'),
					'teacher_firstname'					=> $this->_t('Vorname'),
					'teacher_birthdate'					=> $this->_t('Geburtstag'),
					'teacher_nationality'				=> $this->_t('Nationalität'),
					'teacher_mothertongue'				=> $this->_t('Muttersprache'),
					'teacher_address'					=> $this->_t('Adresse'),
					'teacher_address_addon'				=> $this->_t('Adresse Zusatz'),
					'teacher_zip'						=> $this->_t('Postleitzahl'),
					'teacher_city'						=> $this->_t('Stadt'),
					'teacher_country'					=> $this->_t('Land'),
					'teacher_phone'						=> $this->_t('Telefon'),
					'teacher_phone_office'				=> $this->_t('Telefon Büro'),
					'teacher_mobile_phone'				=> $this->_t('Telefon Mobil'),
					'teacher_fax'						=> $this->_t('Fax'),
					'teacher_email'						=> $this->_t('Email'),
					'teacher_skype'						=> $this->_t('Skype'),
					'teacher_social_security_number'	=> $this->_t('Sozialversicherungsnummer'),
					'teacher_note'						=> $this->_t('Kommentar'),
					'teacher_reset_password_link' => $this->_t('Passwort-Zurücksetzen-Link'),
					'teacher_username' => $this->_t('Benutzername')
				)
			),
			array(
				'section'		=> $this->_t('Bank Details'),
				'placeholders'	=> array(
					'teacher_account_holder'			=> $this->_t('Kontoinhaber'),
					'teacher_account_number'			=> $this->_t('Kontonummer'),
					'teacher_bank_code'					=> $this->_t('Bankleitzahl')
				)
			),
			array(
				'section'		=> $this->_t('Verfügbarkeit'),
				'placeholders'	=> array(
					'teacher_availability'				=> $this->_t('Verfügbarkeit')
				)
			),
			array(
				'section'		=> $this->_t('Individuelle Felder - Lehrer/Unterkünfte', 'Thebing » Contracts'),
				'placeholders'	=> $aPlaceholderFlex
			)
		);

		return $aPlaceholders;
	}

	protected function _getSchool(){	
		$oSchool = Ext_Thebing_Client::getFirstSchool();
	}
	
	protected function _getReplaceValue($sPlaceholder, array $aPlaceholder) {

		$sValue = '';
		
		$oSchool = $this->getSchool();

		switch($sPlaceholder) {
			
			case 'school_name':
				$sValue = $oSchool->ext_1;
				break;
			case 'school_address':
				$sValue = $oSchool->address;
				break;	
			case 'school_address_addon':	
				$sValue = $oSchool->address_addon;
				break;
			case 'school_zip':	
				$sValue = $oSchool->zip;
				break;
			case 'school_city':
				$sValue = $oSchool->city;
				break;
			case 'school_country':
				$sDisplayLanguage	= $oSchool->getLanguage();
				$aCountry			= Ext_Thebing_Data::getCountryList(true,false,$sDisplayLanguage);
				$sCountry			= $oSchool->country_id;
				
				if(
					isset($aCountry[$sCountry])
				){
					$sValue = $aCountry[$sCountry];
				}else{
					$sValue = '';
				}
				break;
			case 'school_url':	
				$sValue = $oSchool->url;
				break;
			case 'school_phone':
				$sValue = $oSchool->phone_1;
				break;
			case 'school_phone2':
				$sValue = $oSchool->phone_2;
				break;
			case 'school_email':	
				$sValue = $oSchool->email;
				break;
			
			// Teacher ======================================================================	
			case 'teacher_salutation': 
				if($this->_oTeacher->gender == 1){
					$sValue = Ext_Thebing_L10N::t('Herr', $sDisplayLanguage);
				} else {
					$sValue = Ext_Thebing_L10N::t('Frau', $sDisplayLanguage);
				}
				break;
			case 'teacher_lastname':
				$sValue = $this->_oTeacher->lastname;
				break;
			case 'teacher_firstname':
				$sValue = $this->_oTeacher->firstname;
				break;
			case 'teacher_birthdate':
				$oFormat = new Ext_Thebing_Gui2_Format_Birthday();
				$sValue = $oFormat->format($this->_oTeacher->birthday, $oDummy, $this->_oTeacher->aData);
				break;
			case 'teacher_nationality':
				$aNationality	= Ext_Thebing_Nationality::getNationalities(true, $oSchool->getLanguage(), 0);
				$sValue = $aNationality[$this->_oTeacher->nationality];
				break;
			case 'teacher_mothertongue':
				$aLangs	= Ext_Thebing_Data::getLanguageSkills(true, $oSchool->getLanguage());
				$sValue = $aLangs[$this->_oTeacher->getLanguage()];
				break;
			case 'teacher_address':
				$sValue = $this->_oTeacher->street;
				break;
			case 'teacher_address_addon':
				$sValue = $this->_oTeacher->additional_address;
				break;
			case 'teacher_zip':
				$sValue = $this->_oTeacher->zip;
				break;
			case 'teacher_city':
				$sValue = $this->_oTeacher->city;
				break;
			case 'teacher_country':
				$mCountry = $this->_oTeacher->country_id;
				$oFormat = new Ext_Thebing_Gui2_Format_Country();
				$sValue = $oFormat->format($mCountry, $oDummy, $this->_oTeacher->aData);
				break;
			case 'teacher_phone':
				$sValue = $this->_oTeacher->phone;
				break;
			case 'teacher_phone_office':
				$sValue = $this->_oTeacher->phone_business;
				break;
			case 'teacher_mobile_phone':
				$sValue = $this->_oTeacher->mobile_phone;
				break;
			case 'teacher_fax':
				$sValue = $this->_oTeacher->fax;
				break;
			case 'teacher_email':
				$sValue = $this->_oTeacher->email;
				break;
			case 'teacher_skype':
				$sValue = $this->_oTeacher->skype;
				break;
			case 'teacher_social_security_number':
				$sValue = $this->_oTeacher->socialsecuritynumber; 
				break;
			case 'teacher_note':
				$sValue = $this->_oTeacher->comment;
				break;
			case 'teacher_username':
				$sValue = $this->_oTeacher->username;
				break;
			case 'teacher_reset_password_link':

				$oCustomerDb = new \Ext_CustomerDB_DB(32);
				$sToken = $oCustomerDb->createActivationCode($this->_oTeacher->id);

				$oRouting = new \Core\Helper\Routing;
				$sForgotPasswordLink = $oRouting->generateUrl('TsTeacherLogin.teacher_reset_password_link', ['sToken'=>$sToken]);

				// Die Route kann bereits die Domain beinhalten
				if(strpos($sForgotPasswordLink, 'http') === false) {
					$sForgotPasswordLink = \System::d('domain').$sForgotPasswordLink;
				}

				$sValue = $sForgotPasswordLink;

				break;

			// Bank Details ==============================================================================
			case 'teacher_account_holder':
				$sValue = $this->_oTeacher->account_holder;
				break;
			case 'teacher_account_number':
				$sValue = $this->_oTeacher->account_number;
				break;
			case 'teacher_bank_code':
				$sValue = $this->_oTeacher->adress_of_bank;
				break;
			
			// Qualifications ============================================================================
			case 'teacher_course_categories':
				$aCatetories = $this->_oTeacher->getCategories(true); 
				$sValue = implode(', ', $aCatetories);
				break;
			case 'teacher_levels':
				$aLevel = $this->_oTeacher->getLevels(true);
				$sValue = implode(', ', $aLevel);
				break;
			
			// Availability ==============================================================================
			case 'teacher_availability':
				$sValue = $this->_getScheduleTable();
				break;

			case 'teacher_teaching_minutes':
			case 'teacher_teaching_hours':
			case 'teacher_teaching_days':

				$unit = match ($sPlaceholder) {
					'teacher_teaching_minutes' => \TsTuition\Enums\TimeUnit::MINUTES,
					'teacher_teaching_hours' => \TsTuition\Enums\TimeUnit::HOURS,
					'teacher_teaching_days' => \TsTuition\Enums\TimeUnit::DAYS,
				};

				$period = match ($aPlaceholder['modifier'] ?? '') {
					'lastyear' => [\Carbon\Carbon::now()->subYear()->startOfYear(), \Carbon\Carbon::now()->subYear()->endOfYear()],
					default => [\Carbon\Carbon::now()->startOfYear(), \Carbon\Carbon::now()]
				};

				$sValue = $this->_oTeacher->getTeachingTime($period[0], $period[1], $unit);
				$sValue = Ext_Thebing_Format::Number($sValue, null, $oSchool, false);

				break;
			default:
				$sValue = parent::_getReplaceValue($sPlaceholder, $aPlaceholder);
				break;			
		}

		return $sValue;

	}
	
	
	protected function _getScheduleTable(){
		$aAvailabilities = $this->_oTeacher->getSchedule();
		
		$oTimeFormat = new Ext_Thebing_Gui2_Format_Date_Time();
		
		$aTemp = array();
		$aTemp['school_id'] = $this->getSchool()->id;
		
		$sString = '<ul>';
		foreach($aAvailabilities as $oAvailability){
			$sWeekday = Ext_TC_Util::convertWeekdayToString((int)$oAvailability->idDay);
			
			$sString .= '<li>' . $this->_t($sWeekday) . ' ';
			$sString .= substr($oAvailability->timeFrom, 0, 5) . ' ' . $this->_t('Uhr') . ' ';
			$sString .= ' - ' . substr($oAvailability->timeTo, 0, 5) . ' ' . $this->_t('Uhr') . '</li>'; 
		}
		$sString .= '</ul>';
		
		return $sString;
	}



}
