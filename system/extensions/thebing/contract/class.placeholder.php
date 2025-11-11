<?php

class Ext_Thebing_Contract_Placeholder extends Ext_Thebing_Placeholder {

	protected $_oVersion;
	protected $_oContract;
	protected $_oItem;
	protected $_sSection;

	protected $replaceSmarty = true;

	public function  __construct($iVersionId = null) {

		if($iVersionId instanceof Ext_Thebing_Contract_Version) {
			$this->_oVersion = $iVersionId;
		} else {
			if(is_null($iVersionId)) {
				return;
			}

			$this->_oVersion = Ext_Thebing_Contract_Version::getInstance((int)$iVersionId);
		}

		$this->_oContract = $this->_oVersion->getContract();
		$this->_oItem = $this->_oContract->getItemObject();

		$this->_iFlexId = $this->_oItem->id;

		switch($this->_oContract->item) {
			case 'accommodation':
				$this->_sSection = 'accommodation_providers';
				break;
			case 'teacher':
			default:
				$this->_sSection = 'teachers';
				break;
		}

		parent::__construct();

	}

	public function getRootEntity() {
		return $this->_oItem;
	}
		
	/**
	 * Get the list of available placeholders
	 *
	 * @return array
	 */
	public function getPlaceholders($sType = '')
	{

		// Auch die Platzhalter der Dyn. Felder von Lehrern u Unterkünften anzeigen
		$aSections = array('teachers', 'accommodation_providers');

		$aFlexFields = Ext_TC_Flexibility::getSectionFieldData($aSections, true);

		$aPlaceholderFlex = array();

		foreach((array)$aFlexFields as $oFlexField){
			$sPlaceholder = $oFlexField->placeholder;
			if(!empty($sPlaceholder)){
				$aPlaceholderFlex[$sPlaceholder] = $oFlexField->description;
			}
		}

		// »section_key« wird in Ableitungen benutzt
		$aPlaceholders = array(
			array(
				'section_key' => 'general',
				'section'		=> L10N::t('Generelle Platzhalter', 'Thebing » Placeholder'),
				'placeholders'	=> array(
					'today'							=> L10N::t('Heute', 'Thebing » Placeholder'),
					'school_name'					=> L10N::t('Schule', 'Thebing » Placeholder'),
					'school_address'				=> L10N::t('Adresse der Schule', 'Thebing » Placeholder'),
					'school_address_addon'			=> L10N::t('Adresszusatz der Schule', 'Thebing » Placeholder'),
					'school_zip'					=> L10N::t('PLZ der Schule', 'Thebing » Placeholder'),
					'school_city'					=> L10N::t('Stadt der Schule', 'Thebing » Placeholder'),
					'school_country'				=> L10N::t('Land der Schule', 'Thebing » Placeholder'),
					'school_url'					=> L10N::t('URL der Schule', 'Thebing » Placeholder'),
					'school_phone'					=> L10N::t('Telefon der Schule', 'Thebing » Placeholder'),
					'school_phone2'					=> L10N::t('Telefon 2 der Schule', 'Thebing » Placeholder'),
					'school_email'					=> L10N::t('E-Mail der Schule', 'Thebing » Placeholder'),
					'account_holder' => L10N::t('Kontoinhaber', 'Thebing » Placeholder'),
					'account_number' => L10N::t('Kontonummer', 'Thebing » Placeholder'),
					'school_bank_name' => L10N::t('Name der Bank der Schule', 'Thebing » Placeholder'),
					'school_bank_code' => L10N::t('Bankleitzahl der Bank der Schule', 'Thebing » Placeholder'),
					'school_bank_address' => L10N::t('Adresse der Bank der Schule', 'Thebing » Placeholder'),
					'school_iban' => L10N::t('IBAN der Schule', 'Thebing » Placeholder'),
					'school_bic' => L10N::t('BIC der Schule', 'Thebing » Placeholder'),
				)
			),
			array(
				'section_key' => 'contracts',
				'section'		=> L10N::t('Verträge', 'Thebing » Contracts'),
				'placeholders'	=> array(
					'item_salutation'				=> L10N::t('Anrede', 'Thebing » Contracts'),
					'item_lastname'					=> L10N::t('Nachname', 'Thebing » Contracts'),
					'item_firstname'				=> L10N::t('Vorname', 'Thebing » Contracts'),
					'item_name'						=> L10N::t('Vorname und Nachname', 'Thebing » Contracts'),
					'item_birthdate'				=> L10N::t('Geburtsdatum', 'Thebing » Contracts'),
					'item_address'					=> L10N::t('Addresse', 'Thebing » Contracts'),
					'item_address_addon'			=> L10N::t('Adresszusatz', 'Thebing » Contracts'),
					'item_zip'						=> L10N::t('ZIP', 'Thebing » Contracts'),
					'item_city'						=> L10N::t('Stadt', 'Thebing » Contracts'),
					'item_state'					=> L10N::t('Bundesland', 'Thebing » Contracts'),
					'item_country'					=> L10N::t('Land', 'Thebing » Contracts'),
					'item_email'					=> L10N::t('E-Mail', 'Thebing » Contracts'),
					'item_phone'					=> L10N::t('Telefon', 'Thebing » Contracts'),
					'item_mobile_phone'				=> L10N::t('Handy', 'Thebing » Contracts'),
					'item_social_security_number'	=> L10N::t('Sozialversicherungsnummer', 'Thebing » Contracts'),
					'item_bank'						=> L10N::t('Bank', 'Thebing » Contracts'),
					'item_bank_code'				=> L10N::t('Banknummer', 'Thebing » Contracts'),
					'item_bank_account'				=> L10N::t('Konto', 'Thebing » Contracts'),
					'item_iban'						=> L10N::t('IBAN', 'Thebing » Contracts'),
					'item_account_holder'			=> L10N::t('Kontoinhaber', 'Thebing » Contracts'),
					'item_contract_start'			=> L10N::t('Vertragsstart', 'Thebing » Contracts'),
					'item_contract_end'				=> L10N::t('Vertragsende', 'Thebing » Contracts'),
					'item_contract_date'			=> L10N::t('Vertragsdatum', 'Thebing » Contracts'),
					'item_contract_number'			=> L10N::t('Vertragsnummer', 'Thebing » Contracts'),
					#'item_units'					=> L10N::t('Einheiten', 'Thebing » Contracts'),
					'item_master_contract_number'	=> L10N::t('Hauptvertragsnummer', 'Thebing » Contracts'),
					'item_master_contract_start'	=> L10N::t('Startdatum des Hauptvertrages', 'Thebing » Contracts'),
					'item_master_contract_end'		=> L10N::t('Enddatum des Hauptvertrages', 'Thebing » Contracts'),
					#'accommodation_provider_payment_overview'	=> L10N::t('Unterkunfstverträge: Alle Schüler die in dem Zeitraum bezahlt wurden.', 'Thebing » Contracts')
				)
			),
			array(
				'section_key' => 'individual',
				'section'		=> L10N::t('Individuelle Felder - Lehrer/Unterkünfte', 'Thebing » Contracts'),
				'placeholders'	=> $aPlaceholderFlex
			)
		);

		// Vertragsplatzhalter entfernen (diese Klasse ist eigentlich eher für Lehrer/Provider-Objekte)
		if(
			is_array($sType) &&
			in_array('accommodation_resources_provider', $sType)
		) {
			$aPlaceholders[1]['section'] = L10N::t('Unterkunftsanbieter', 'Thebing » Placeholder');
			foreach(array_keys($aPlaceholders[1]['placeholders']) as $sPlaceholder) {
				if(
					strpos($sPlaceholder, 'contract') !== false ||
					$sPlaceholder === 'item_salary'
				) {
					unset($aPlaceholders[1]['placeholders'][$sPlaceholder]);
				}
			}
		}

		if( 
			$sType == 'document_teacher_contract_basic' ||
			$sType == 'document_teacher_contract_additional'
		){
			$aPlaceholders[0]['placeholders']['item_units'] = L10N::t('Einheiten', 'Thebing » Contracts');
			$aPlaceholders[1]['placeholders']['item_salary'] = L10N::t('Lohn', 'Thebing » Contracts');
		}elseif(
			$sType == 'document_accommodation_contract_basic' ||
			$sType == 'document_accommodation_contract_additional'
		){
			$aPlaceholders[0]['placeholders']['accommodation_provider_payment_overview'] = L10N::t('Unterkunfstverträge: Alle Schüler die in dem Zeitraum bezahlt wurden.', 'Thebing » Contracts');
		}
		
		
		return $aPlaceholders;
	}

	protected function _getReplaceValue($sPlaceholder, array $aPlaceholder) {

		$sValue = null;
		
		$oSchool = $this->getSchool();

		switch($sPlaceholder) {
			case 'item_salutation':
				$sValue = Ext_TS_Contact::getSalutationForFrontend($this->_oItem->gender, $this->getLanguageObject());
				break;
			case 'item_lastname':
			case 'teacher_lastname':
			case 'teacher_surname':
				$sValue = $this->_oItem->lastname;
				break;
			case 'item_firstname':
			case 'teacher_firstname':
				$sValue = $this->_oItem->firstname;
				break;
			case 'item_name':
			case 'teacher_name':
				$sValue = $this->_oItem->name;
				break;
			case 'item_birthdate':
			case 'teacher_birthdate':
				$oFormat = new Ext_Thebing_Gui2_Format_Birthday();
				$sValue = $oFormat->format($this->_oItem->birthday, $oDummy, $this->_oContract->aData);
				break;
			case 'item_address':
			case 'teacher_address':
				$sValue = $this->_oItem->street;
				break;
			case 'item_address_addon':
			case 'teacher_address_addon':
				$sValue = $this->_oItem->additional_address;
				break;
			case 'item_zip':
			case 'teacher_zip':
				$sValue = $this->_oItem->zip;
				break;
			case 'item_city':
			case 'teacher_city':
				$sValue = $this->_oItem->city;
				break;
			case 'item_email':
				$sValue = $this->_oItem->email;
				break;
			case 'item_phone':
				$sValue = $this->_oItem->phone;
				break;
			case 'item_mobile_phone':
				$sValue = $this->_oItem->mobile_phone;
				break;
			case 'item_state':
				$sValue = $this->_oItem->state;
				break;
			case 'item_country':
			case 'teacher_country':
				$mCountry = $this->_oItem->country_id;
				if(is_numeric($mCountry)) {
				$oFormat = new Ext_Thebing_Gui2_Format_Country($this->sTemplateLanguage);
					$sValue = $oFormat->format($mCountry, $oDummy, $this->_oContract->aData);
				} else {
					$sValue = $mCountry;
				}
				break;
			case 'item_social_security_number':
			case 'teacher_social_security_number':
				$sValue = $this->_oItem->socialsecuritynumber;
				break;
			case 'item_bank':
			case 'teacher_bank':
				$sValue = $this->_oItem->name_of_bank;
				break;
			case 'item_bank_code':
			case 'teacher_bank_code':
				$sValue = $this->_oItem->adress_of_bank;
				break;
			case 'item_bank_account':
			case 'teacher_bank_account':
				$sValue = $this->_oItem->account_number;
				break;
			case 'item_iban':
				$sValue = $this->_oItem->bank_account_iban;
				break;
			case 'item_account_holder':
			case 'teacher_account_holder':
				$sValue = $this->_oItem->account_holder;
				break;
			case 'item_contract_start':
			case 'teacher_contract_start':
				$oFormat = new Ext_Thebing_Gui2_Format_Date();
				$sValue = $oFormat->format($this->_oVersion->valid_from, $oDummy, $this->_oContract->aData);
				break;
			case 'item_contract_end':
			case 'teacher_contract_end':
				$oFormat = new Ext_Thebing_Gui2_Format_Date();
				$sValue = $oFormat->format($this->_oVersion->valid_until, $oDummy, $this->_oContract->aData);
				break;
			case 'item_contract_date':
			case 'teacher_contract_date':
				$oFormat = new Ext_Thebing_Gui2_Format_Date();
				$sValue = $oFormat->format($this->_oContract->date, $oDummy, $this->_oContract->aData);
				break;
			case 'item_contract_number':
			case 'teacher_contract_number':
				$sValue = $this->_oContract->number;
				break;
			case 'item_salary':
			case 'teacher_salary':
				if($this->_oContract->item == 'accommodation') {
					$oFormat = new Ext_Thebing_Gui2_Format_Accommodation_Salary();
				} else {
					$oFormat = new Ext_Thebing_Gui2_Format_Teacher_Salary();
				}
				$aSalary = $this->_oItem->getSalary($this->_oVersion->valid_from, $this->_oContract->school_id);
				$oDummy = null;
				$sValue = $oFormat->format($aSalary['salary'], $oDummy, $aSalary);
				break;
			case 'item_units':
			case 'teacher_units':
			case 'teacher_lessons':
				$oFormat = new Ext_Thebing_Gui2_Format_Teacher_Lessons();
				$aSalary = $this->_oItem->getSalary($this->_oVersion->valid_from, $this->_oContract->school_id);
				$sValue = $oFormat->formatByResult($aSalary);
				break;
			case 'item_master_contract_number':
			case 'teacher_master_contract_number':
				$oBasicContract = $this->_oContract->getBasicContract();
				$sValue = $oBasicContract->number;
				break;
			case 'item_master_contract_start':
			case 'item_master_contract_end':
				$oBasicContract = $this->_oContract->getBasicContract();
				$basicContractVersion = $oBasicContract->getLatestVersion();

				$oFormat = new Ext_Thebing_Gui2_Format_Date();

				$date = $basicContractVersion->valid_until;
				if($sPlaceholder === 'item_master_contract_start') {
					$date = $basicContractVersion->valid_from;
				}

				$sValue = $oFormat->format($date, $oDummy, $oBasicContract->aData);

				break;
			case 'item_category':
			case 'teacher_category':
				$oFormat = new Ext_Thebing_Gui2_Format_Teacher_Costcategory();
				$aSalary = $this->_oItem->getSalary($this->_oVersion->valid_from, $this->_oContract->school_id);
				$sValue = $oFormat->formatByValue($aSalary['costcategory_id']);
				break;
			case 'item_category_details':
			case 'teacher_category_details':
				$sValue = '';
				break;
			case 'accommodation_provider_payment_overview':

				$sValue = $this->_getPayedStudentsOverview();

				break;
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
			
			default:
				$sValue = parent::_getReplaceValue($sPlaceholder, $aPlaceholder);
				break;
		}

		return $sValue;
	}

	protected function _getPayedStudentsOverview() {

		$aStudents = $this->_oVersion->getPayedStudents();

		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		$sHtml = "<table>";

		$fTotal = 0;

		if(
			is_array($aStudents) &&
			count($aStudents) > 0
		){
			foreach($aStudents as $aStudent) {

			$oStart = new WDDate($aStudent['timepoint'], WDDate::DB_DATE);
			$oEnd = new WDDate($aStudent['timepoint'], WDDate::DB_DATE);
			$oEnd = $oEnd->add($aStudent['nights'], WDDate::DAY);

			$sHtml .= "<tr>";
			$sHtml .= "<td>".$aStudent['lastname'].", ".$aStudent['firstname']."</td>";
			$sHtml .= "<td>".Ext_Thebing_Format::LocalDate($oStart->get(WDDate::TIMESTAMP), null, true)." - ".Ext_Thebing_Format::LocalDate($oEnd->get(WDDate::TIMESTAMP), null, true)."</td>";
			$sHtml .= '<td style="text-align:right;">'.Ext_Thebing_Format::Number($aStudent['amount_school'], $oSchool->getCurrency()).'</td>';
			$sHtml .= "</tr>";

			$fTotal += $aStudent['amount_school'];

}
		}


		$sHtml .= "<tr>";
		$sHtml .= '<td colspan="3" style="border-bottom: 1px solid black;"></td>';
		$sHtml .= "</tr>";
		$sHtml .= '<tr>';
		$sHtml .= "<td>".L10N::t('Total', 'Thebing » Contracts')."</td>";
		$sHtml .= "<td></td>";
		$sHtml .= '<td style="text-align:right;">'.Ext_Thebing_Format::Number($fTotal, $oSchool->getCurrency()).'</td>';
		$sHtml .= "</tr>";

		$sHtml .= "</table>";

		return $sHtml;

	}

	/**
	 * @inheritdoc
	 */
	public function getSchool() {

		if($this->_oItem instanceof Ext_Thebing_Accommodation) {
			return Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		}

		return $this->_oItem->getSchool();

	}

}
