<?php


class Ext_TS_Frontend_Combination_Login_Student_Personal extends Ext_TS_Frontend_Combination_Login_Student_Abstract
{
	protected function _setData()
	{
		//Daten
		$oInquiry		= $this->_getInquiry();
		$oCustomer		= $oInquiry->getCustomer();

		$sLanguage		= $this->_getLanguage();
		$aLangs			= Ext_Thebing_Data::getLanguageSkills(true, $sLanguage);
		$aCountries		= Ext_Thebing_Data::getCountryList(true, false, $sLanguage);

		//Format
		$oFormatNationality		= new Ext_Thebing_Gui2_Format_Nationality();

		//Emergency Contact
		$oEmergencyContact		= $oInquiry->getEmergencyContact();

		//Contact Address
		$oAddressContact		= $oCustomer->getAddress('contact');

		//Billing Address
		$oAddressBilling		= $oCustomer->getAddress('billing');

		//Vars
		$sLastName			= $oCustomer->lastname;
		$sFirstname			= $oCustomer->firstname;
		$sGender			= $oCustomer->getGender();
		$sNationality		= $oFormatNationality->format($oCustomer->nationality);
		$sPhone				= $oCustomer->getDetail('phone_private');
		$sCellPhone			= $oCustomer->getDetail('phone_mobile');
		$sEmail				= $oCustomer->getEmail();
		$sEmergencyName		= $oEmergencyContact->getName();
		$sEmergencyPhone	= $oEmergencyContact->getDetail('phone_private');
		$sEmergencyMail		= $oEmergencyContact->getEmail();
		$sAddressStreet		= $oAddressContact->address;
		$sAddressZip		= $oAddressContact->zip;
		$sAddressCity		= $oAddressContact->city;
		$sAddressState		= $oAddressContact->state;
		$sBillingCompany	= $oAddressBilling->company;
		$sBillingStreet		= $oAddressBilling->address;
		$sBillingZip		= $oAddressBilling->zip;
		$sBillingCity		= $oAddressBilling->city;

		$sMotherTongue	= $oCustomer->language;
		if(isset($aLangs[$sMotherTongue]))
		{
			$sMotherTongue = $aLangs[$sMotherTongue];
		}
		else
		{
			$sMotherTongue = '';
		}

		$sAddressCountry	= $oAddressContact->country_iso;
		if(isset($aCountries[$sAddressCountry]))
		{
			$sAddressCountry = $aCountries[$sAddressCountry];
		}
		else
		{
			$sAddressCountry = '';
		}

		$sBillingCountry	= $oAddressBilling->country_iso;
		if(isset($aCountries[$sBillingCountry]))
		{
			$sBillingCountry = $aCountries[$sBillingCountry];
		}
		else
		{
			$sBillingCountry = '';
		}

		//Form
		$oForm = new Ext_TS_Frontend_Combination_Login_Student_Form($this);

		$oForm->addRow('input', 'Lastname', $sLastName, array('readonly' => true));
		$oForm->addRow('input', 'Firstname', $sFirstname, array('readonly' => true));
		$oForm->addRow('input', 'Gender', $sGender, array('readonly' => true));
		$oForm->addRow('input', 'Nationality', $sNationality, array('readonly' => true));
		$oForm->addRow('input', 'Mother tongue', $sMotherTongue, array('readonly' => true));
		$oForm->addRow('input', 'Phone', $sPhone);
		$oForm->addRow('input', 'Cellphone', $sCellPhone);
		$oForm->addRow('input', 'E-Mail', $sEmail);
		$this->_assign('sContactDetails', (string)$oForm);

		$oForm->reset(false);

		$oForm->addRow('input', 'Name', $sEmergencyName);
		$oForm->addRow('input', 'Phone', $sEmergencyPhone);
		$oForm->addRow('input', 'E-Mail', $sEmergencyMail);
		$this->_assign('sEmergencyDetails', (string)$oForm);

		$oForm->reset(false);

		$oForm->addRow('input', 'Address', $sAddressStreet);
		$oForm->addRow('input', 'ZIP', $sAddressZip);
		$oForm->addRow('input', 'City', $sAddressCity);
		$oForm->addRow('input', 'State', $sAddressState);
		$oForm->addRow('input', 'Country', $sAddressCountry);
		$this->_assign('sAddressDetails', (string)$oForm);

		$oForm->reset(false);

		$oForm->addRow('input', 'Company', $sBillingCompany);
		$oForm->addRow('input', 'Address', $sBillingStreet);
		$oForm->addRow('input', 'ZIP', $sBillingZip);
		$oForm->addRow('input', 'City', $sBillingCity);
		$oForm->addRow('input', 'Country', $sBillingCountry);
		$this->_assign('sBillingDetails', (string)$oForm);
		
		// Upload
		$oForm = new Ext_TS_Frontend_Combination_Login_Student_Form($this);
		$oForm->addRow('upload', 'Passport', '');
		
		#$sHtml = (string)$oForm;
		
		$sHtml = '<p><img class="student_login_pic" src="../icef_login/photo.jpg"/></p>';
		$this->_assign('sUploads', (string)$sHtml);
		
		$this->_setTask('showPersonalData');
	}

}