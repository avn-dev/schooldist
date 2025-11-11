<?php

namespace TsMobile\Generator\Pages\Personal;

use TsMobile\Generator\AbstractPage;

class Student extends AbstractPage {
	
	public function render(array $aData = array()) {
		
		$oUser = $this->oApp->getUser();
		/* @var $oUser \Ext_TS_Inquiry_Contact_Abstract */
		
		$sTemplate = $this->generatePageHeading($this->oApp->t('Personal data'));
		
		$oNationalityFormat = new \Ext_Thebing_Gui2_Format_Nationality($this->_sInterfaceLanguage);		
		
		$sContent = 
				//$this->t('Name').': '. $oUser->getName(). '<br>'.
				$this->t('Student ID').': '.$oUser->getCustomerNumber().'<br>'.
				$this->t('Day of birth').': '. $this->formatDate($oUser->birthday). '<br>'.
				$this->t('E-Mail').': '. $oUser->getFirstEmailAddress()->email. '<br>'.
				$this->t('Nationality').': '. $oNationalityFormat->format($oUser->nationality);

		$sTemplate .= $this->generateBlock($oUser->getName(), $sContent);

		if(\Util::isDebugIP()) {
			$sTemplate .= '<h3>Debug</h3>';
			$sContent = '';
			$sContent .= 'Inquiry-ID: '.$this->oApp->getInquiry()->id.'<br>';
			$sContent .= 'Contact-ID: '.$this->oApp->getUser()->id;
			$sTemplate .= $this->generateBlock(null, $sContent);
		}

		$oCountryFormat = new \Ext_TC_Gui2_Format_Country($this->_sInterfaceLanguage);

		$oAddress = $oUser->getAddress('contact');
		$oAddressBilling = $oUser->getBooker()?->getAddress('billing');
		$iInquiry = $oUser->getInquiries(true);
		$oInquiry = \Ext_TS_Inquiry::getInstance($iInquiry);
		$oEmergency = $oInquiry->getEmergencyContact();

		if(
			!$oAddress->isEmpty() ||
			!$oAddressBilling->isEmpty() ||
			$oEmergency->id > 0
		) {
			$sTemplate .= '<h3>'.$this->t('Other').'</h3>';
		}

		if(!$oAddress->isEmpty()) {
			$sAddresses = $oAddress->address . '<br>';
			$sAddresses .= $oAddress->zip . ' ' . $oAddress->city . ', ' . $oCountryFormat->format($oAddress->country_iso) . '<br>';
			$sTemplate .= $this->generateBlock($this->t('Address'), $sAddresses);
		}

		if(!$oAddressBilling->isEmpty()) {
			$sBillingAddresses = $oAddressBilling->address . '<br>';
			$sBillingAddresses .= $oAddressBilling->zip . ' ' . $oAddressBilling->city . ', ' . $oCountryFormat->format($oAddressBilling->country_iso) . '<br>';
			$sTemplate .= $this->generateBlock($this->t('Billing address'), $sBillingAddresses);
		}
				
		if($oEmergency->id > 0) {
			$sEmergency = 
					$this->t('Name').': '. $oEmergency->getName(). '<br>'.
					$this->t('E-Mail').': '. $oEmergency->getFirstEmailAddress()->email. '<br>'.
					$this->t('Phone').': '. $oEmergency->getDetail('phone_private'). '</div>';
			$sTemplate .= $this->generateBlock($this->t('Emergency contact'), $sEmergency);
		}
		
		return $sTemplate;
	}
	
}