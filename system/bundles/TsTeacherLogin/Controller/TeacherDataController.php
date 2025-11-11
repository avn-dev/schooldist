<?php

namespace TsTeacherLogin\Controller;

use TsTeacherLogin\Events\TeacherDataUpdated;

class TeacherDataController extends InterfaceController {

	public function getTeacherDataView() {

		$oTeacher = \Ext_Thebing_Teacher::getInstance($this->_oAccess->id);
		$this->set('oTeacher', $oTeacher);

		$sInterfaceLanguage = \System::getInterfaceLanguage();
		$sInterfaceLanguage = substr($sInterfaceLanguage, 0, 2);
		
		$aLanguages = \Ext_Thebing_Data::getLanguageSkills(true, $sInterfaceLanguage);
		$this->set('aLanguages', $aLanguages);

		$aNationalities = \Ext_Thebing_Nationality::getNationalities(true, $sInterfaceLanguage, 0);
		$this->set('aNationalities', $aNationalities);

		$aCountries = \Ext_Thebing_Data::getCountryList(true, true, $sInterfaceLanguage);
		$this->set('aCountries', $aCountries);

		$sTemplate = 'system/bundles/TsTeacherLogin/Resources/views/pages/teacher_data.tpl';
		$this->_oView->setTemplate($sTemplate);

	}

	public function saveTeacherData() {

		$iTeacherId = $this->_oAccess->id;
		$oTeacher = \Ext_Thebing_Teacher::getInstance($iTeacherId);

		$aTeacherData = [
			'nationality',
			'mother_tongue',
			'street',
			'additional_address',
			'zip',
			'city',
			'state',
			'country_id',
			'phone',
			'phone_business',
			'mobile_phone',
			'fax',
			'email',
			'skype',
		];

		foreach($aTeacherData as $sFieldKey) {

			if($this->_oRequest->exists($sFieldKey)) {
				$oTeacher->$sFieldKey = $this->_oRequest->get($sFieldKey);
			}

		}

		$mValidate = $oTeacher->validate();

		if($mValidate === true) {

			$oTeacher->save();

			$this->oSession->getFlashBag()->add('success', \L10N::t('Your data has been updated successfully!'));

			TeacherDataUpdated::dispatch($oTeacher);

		} else {

			$aErrors = [];

			foreach ($mValidate as $sKey => $aFieldErrors) {

				// Wir berücksichtigen nur den ersten Fehler
				$sError = reset($aFieldErrors);

				list($sAlias, $sKey) = explode('.', $sKey);

				$sErrorMessage = \Ext_Gui2_Data::convertErrorKeyToMessage($sError);
				$sErrorMessage = str_replace(' "%s"', '', $sErrorMessage);

				$aErrors[$sKey] = \L10N::t($sErrorMessage);

			}

			$this->oSession->getFlashBag()->add('error', \L10N::t('Your changes could not be saved!'));
			$this->oSession->getFlashBag()->set('errors', $aErrors);
		}

		$this->getTeacherDataView();

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

		$this->getTeacherDataView();
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

		$oCustomerDb = new \Ext_CustomerDB_DB(32);
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