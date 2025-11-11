<?php

namespace TsAccommodationLogin\Controller;

use TsAccommodationLogin\Events\AccommodationDataUpdated;
use TsAccommodationLogin\Handler\ExternalApp;

class AccommodationDataController extends InterfaceController {

	protected function getFields(\Ext_Thebing_Accommodation $accommodation) {

		$aCountries = \Ext_Thebing_Data::getCountryList(true, false, substr(\System::getInterfaceLanguage(), 0, 2));

		$frontendLanguages = \Ext_Thebing_Data::getSystemLanguages();

		if (\System::d(ExternalApp::KEY_ONE_LANGUAGE)) {
			// Nur die Schulsprache
			$iso = \Ext_Thebing_School::getSchoolFromSession()->language;
			$frontendLanguages = [$iso => $frontendLanguages[$iso]];
		}
		$i = 0;
		$aFields = array();
		$aFields[$i]['label'] 		= \L10N::t("General");
		$aFields[$i]['type'] 		= "h2";

		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_106;
		$aFields[$i]['field'] 		= 'ext_106';
		$aFields[$i]['main'] 		= 'ext_103';
		$aFields[$i]['label'] 		= \L10N::t("Ansprechpartner (Vorname)");
		$aFields[$i]['type'] 		= 'text';	
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_107;
		$aFields[$i]['field'] 		= 'ext_107';
		$aFields[$i]['main'] 		= 'ext_104';
		$aFields[$i]['label'] 		= \L10N::t("Ansprechpartner (Nachname)");
		$aFields[$i]['type'] 		= 'text';	
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_108;
		$aFields[$i]['field'] 		= 'ext_108';
		$aFields[$i]['main'] 		= 'ext_105';
		$aFields[$i]['label'] 		= \L10N::t("Ansprechpartner (Salutation)");
		$aFields[$i]['type'] 		= 'select';
		$aFields[$i]['data_array']	= array('',\L10N::t('Herr'),\L10N::t('Frau'));

		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_110;
		$aFields[$i]['field'] 		= 'ext_110';
		$aFields[$i]['main'] 		= 'ext_109';
		$aFields[$i]['label'] 		= \L10N::t("Google Maps");
		$aFields[$i]['type'] 		= 'text';	

		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_80;
		$aFields[$i]['field'] 		= 'ext_80';
		$aFields[$i]['main'] 		= 'ext_63';
		$aFields[$i]['label'] 		= \L10N::t("Address");
		$aFields[$i]['type'] 		= 'text';	
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_81;
		$aFields[$i]['field'] 		= 'ext_81';
		$aFields[$i]['main'] 		= 'ext_64';
		$aFields[$i]['label'] 		= \L10N::t("ZIP");
		$aFields[$i]['type'] 		= 'text';	
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_82;
		$aFields[$i]['field'] 		= 'ext_82';
		$aFields[$i]['main'] 		= 'ext_65';
		$aFields[$i]['label'] 		= \L10N::t("City");
		$aFields[$i]['type'] 		= 'text';	
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_100;
		$aFields[$i]['field'] 		= 'ext_100';
		$aFields[$i]['main'] 		= 'ext_99';
		$aFields[$i]['label'] 		= \L10N::t("State");
		$aFields[$i]['type'] 		= 'text';	
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_83;
		$aFields[$i]['field'] 		= 'ext_83';
		$aFields[$i]['main'] 		= 'ext_66';
		$aFields[$i]['label'] 		= \L10N::t("Country");
		$aFields[$i]['type'] 		= 'select';
		$aFields[$i]['data_array']	= $aCountries;
		$aFields[$i]['style']		= "width:300px;"; 
		$i++;
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_84;
		$aFields[$i]['field'] 		= 'ext_84';
		$aFields[$i]['main'] 		= 'ext_67';
		$aFields[$i]['label'] 		= \L10N::t("Phone");
		$aFields[$i]['type'] 		= 'text';	
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_85;
		$aFields[$i]['field'] 		= 'ext_85';
		$aFields[$i]['main'] 		= 'ext_76';
		$aFields[$i]['label'] 		= \L10N::t("Phone 2");
		$aFields[$i]['type'] 		= 'text';	
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_103;
		$aFields[$i]['field'] 		= 'ext_103';
		$aFields[$i]['main'] 		= 'ext_101';
		$aFields[$i]['label'] 		= \L10N::t("Fax");
		$aFields[$i]['type'] 		= 'text';	
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_86;
		$aFields[$i]['field'] 		= 'ext_86';
		$aFields[$i]['main'] 		= 'ext_77';
		$aFields[$i]['label'] 		= \L10N::t("Mobile");
		$aFields[$i]['type'] 		= 'text';	
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_87;
		$aFields[$i]['field'] 		= 'ext_87';
		$aFields[$i]['main'] 		= 'email';
		$aFields[$i]['label'] 		= \L10N::t("E-mail");
		$aFields[$i]['type'] 		= 'text';	
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_88;
		$aFields[$i]['field'] 		= 'ext_88';
		$aFields[$i]['main'] 		= 'ext_78';
		$aFields[$i]['label'] 		= \L10N::t("Skype");
		$aFields[$i]['type'] 		= 'text';

		foreach($frontendLanguages as $languageKey=>$languageLabel) {
			if (!\System::d(ExternalApp::KEY_HIDE_FAMILY_DESCRIPTION)) {
				$i++;
				$aFields[$i]['value'] 		= $accommodation->{'family_description_'.$languageKey};
				$aFields[$i]['field'] 		= 'portal_family_description_'.$languageKey;
				$aFields[$i]['main'] 		= 'family_description_'.$languageKey;
				$aFields[$i]['label'] 		= \L10N::t("Family description").' ('.$languageLabel.')';
				$aFields[$i]['style']		= "width:300px; height: 100px;";
				$aFields[$i]['type'] 		= 'html';
			}

			if (!\System::d(ExternalApp::KEY_HIDE_WAY_DESCRIPTION)) {
				$i++;
				$aFields[$i]['value'] 		= $accommodation->{'way_description_'.$languageKey};
				$aFields[$i]['field'] 		= 'portal_way_description_'.$languageKey;
				$aFields[$i]['main'] 		= 'way_description_'.$languageKey;
				$aFields[$i]['label'] 		= \L10N::t("Best way to get to the school").' ('.$languageLabel.')';
				$aFields[$i]['style']		= "width:300px; height: 100px;";
				$aFields[$i]['type'] 		= 'html';
			}
		}
		
		$i++;
		$aFields[$i]['label'] 		= \L10N::t("Bank account");
		$aFields[$i]['type'] 		= "h2";
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_92;
		$aFields[$i]['field'] 		= 'ext_92';
		$aFields[$i]['main'] 		= 'ext_68';
		$aFields[$i]['label'] 		= \L10N::t("Account holder");
		$aFields[$i]['type'] 		= 'text';
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_93;
		$aFields[$i]['field'] 		= 'ext_93';
		$aFields[$i]['main'] 		= 'ext_69';
		$aFields[$i]['label'] 		= \L10N::t("Name of bank");
		$aFields[$i]['type'] 		= 'text';
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_94;
		$aFields[$i]['field'] 		= 'ext_94';
		$aFields[$i]['main'] 		= 'ext_70';
		$aFields[$i]['label'] 		= \L10N::t("Account number");
		$aFields[$i]['type'] 		= 'text';
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_95;
		$aFields[$i]['field'] 		= 'ext_95';
		$aFields[$i]['main'] 		= 'ext_71';
		$aFields[$i]['label'] 		= \L10N::t("Banking code");
		$aFields[$i]['type'] 		= 'text';
		
		$i++;
		$aFields[$i]['value'] 		= $accommodation->ext_96;
		$aFields[$i]['field'] 		= 'ext_96';
		$aFields[$i]['main'] 		= 'ext_72';
		$aFields[$i]['label'] 		= \L10N::t("Address of bank");
		$aFields[$i]['type'] 		= 'text';

		return $aFields;
	}


	public function profile() {

		$_VARS = $this->_oRequest->getAll();

		$bLoggedIn = true;

		$accommodation = \Ext_Thebing_Accommodation::getInstance($this->_oAccess->id);
		
		$aFields = $this->getFields($accommodation);
		
		foreach((array)$aFields as $iField=>$aField) {
			if(isset($aField['field'])) {
				$mValue = $accommodation->{$aField['field']};
				if(empty($mValue)) {
					$aFields[$iField]['value'] = $accommodation->{$aField['main']};
				} else {
					$aFields[$iField]['value'] = $accommodation->{$aField['field']};
				}
			}
		}
	
		$_VARS['task'] = 'add';
		
		$this->set('aFields', $aFields);
		
		$this->set('bSuccess', $bSuccess);
		$this->set('bShowPayments', $accommodation->ext_97); 
		$this->set('bError', $bError);


		$this->set('bLoggedIn', $bLoggedIn);

		$this->set('sView', $sView);
		$this->set('sTask', $_VARS['task']);

	}

	public function rooms() {
		
		$aSaisons = Ext_Thebing_Data_school::getSaisons($accommodation->idClient, $accommodation->ext_2);

		
		
		$aRooms = $accommodation->getRoomList();
		
		if($_VARS['task'] == 'calendar') {
			// Blockierung auslesen
			// Ausgebaut da die Blockierungspflege neu gemacht wurde
			// TODO hier ebenfalls an die neue struktur anpasse
		}

		$this->set('aSaisons', $aSaisons);
		$this->set('oCalendar', $oCalendar);
		$this->set('sCalendar', $sCalendar);
		$this->set('aRooms', $aRooms);

	}
	
	public function pictures() {
		
		if(isset($_VARS['task']) && $_VARS['task'] == 'save') {

			$bSuccess = $accommodation->savePicture($_VARS['picture']);
			// TODO #9834 - sehe ich das falsch oder ist $oSchool hier gar nicht gesetzt? | kann nochwas warten
			wdmail($oSchool->email, L10N::t('family_send_profile_change_subject'), L10N::t('family_send_profile_change_body'));

		}

		if(isset($_VARS['task']) && $_VARS['task'] == 'delete'){
			$bSuccess = $accommodation->deletePicture($_VARS['picture']);
		}

		$aList = $accommodation->getPictures(false);
		$this->set('aImages', $aList);
		
		$aList = $accommodation->getPictures(true);
		$this->set('aImagesNew', $aList);
		
	}
	
	public function saveData(\MVC_Request $request) {

		$accommodation = \Ext_Thebing_Accommodation::getInstance($this->_oAccess->id);
		
		$aFields = $this->getFields($accommodation);

		$aChangedFields = json_decode($accommodation->ext_98);

		// set request values to object
		foreach((array)$aFields as $iField=>$aField) {
			if(isset($aField['field'])) {

				if(
					(
						$accommodation->{$aField['main']} != $request->input($aField['field']) &&
						$accommodation->{$aField['field']} == ''
					) ||
					(
						$accommodation->{$aField['field']} != $request->input($aField['field']) &&
						$accommodation->{$aField['field']} != ''
					)
				) {
					$aChangedFields[] = $aField['field'];
					$accommodation->{$aField['field']} = $request->input($aField['field']);
				}

			}

		}

		$aChangedFields = array_unique($aChangedFields);

		$accommodation->ext_98 = json_encode($aChangedFields);

		$mValidate = $accommodation->validate();

		if($mValidate === true) {

			$accommodation->save();

			$this->oSession->getFlashBag()->add('success', \L10N::t('Your changes have been saved successfully!'));

			AccommodationDataUpdated::dispatch($accommodation);

		} else {

			$this->oSession->getFlashBag()->add('error', \L10N::t('An error occured! Please try again!'));
			$this->set('aErrors', $mValidate);

		}		
		
		$this->redirect('TsAccommodationLogin.accommodation_data');
		
	}

	public function savePassword() {

		$iTeacherId = $this->_oAccess->id;
		$oTeacher = \Ext_Thebing_Teacher::getInstance($iTeacherId);

		$aErrors = $this->handleSavePassword($oTeacher);

		if(empty($aErrors)) {

			$this->set('success', true);
			$this->oSession->getFlashBag()->add('password_success', \L10N::t('Your changes have been saved successfully.'));

		} else {

			$this->set('success', false);
			$this->set('aPasswordErrors', $aErrors);

			$this->oSession->getFlashBag()->add('password_error', \L10N::t('Your changes could not be saved!'));

		}

		$this->profile();

		$sTemplate = 'system/bundles/TsAccommodationLogin/Resources/views/accommodation-data/profile.tpl';
		$this->_oView->setTemplate($sTemplate);
	}

	/**
	 * @param \Ext_Thebing_Teacher $oTeacher
	 * @return array
	 */
	public function handleSavePassword(\Ext_Thebing_Teacher $oTeacher) {

		$aErrors = [];

		$sPassword = $this->_oRequest->get('password_new');
		$sPasswordCheck = $this->_oRequest->get('password_check');
		$sPasswordOld = $this->_oRequest->get('password_old');

		if(empty($sPassword)) {
			$aErrors['password_new'] = \L10N::t('Please enter the desired password!');
		}

		if(empty($sPasswordCheck)) {
			$aErrors['password_check'] = \L10N::t('Please confirm your new password!');
		}

		if(empty($sPasswordOld)) {
			$aErrors['password_old'] = \L10N::t('Please enter your current password!');
		}

		if(!empty($aErrors)) {
			return $aErrors;
		}

		$oCustomerDb = new \Ext_CustomerDB_DB(4);
		$bPasswordVerify = $oCustomerDb->verifyPassword($oTeacher->id, $sPasswordOld);

		if($bPasswordVerify === false) {
			$aErrors['password_old'] = \L10N::t('The current password is not correct!');
			return $aErrors;
		}

		if($sPassword !== $sPasswordCheck) {
			$aErrors['password_new'] = true;
			$aErrors['password_check'] = \L10N::t('The entered passwords do not match!');
			return $aErrors;
		}

		$aUserData = [
			$oTeacher->username,
			$oTeacher->firstname,
			$oTeacher->lastname,
			$oTeacher->email
		];

		$oZxcvbn = new \ZxcvbnPhp\Zxcvbn();
		$aStrength = $oZxcvbn->passwordStrength($sPassword, $aUserData);

		if($aStrength['score'] < \System::getMinPasswordStrength()) {
			$aErrors['password_new'] = \L10N::t('The password is not secure enough!');
			return $aErrors;
		}

		$oCustomerDb->updateCustomerField($oTeacher->id, 'password', $sPassword);

		return $aErrors;
	}

}
