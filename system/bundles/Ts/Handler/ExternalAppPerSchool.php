<?php

namespace Ts\Handler;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;

abstract class ExternalAppPerSchool extends \TcExternalApps\Interfaces\ExternalApp {

	const VALUE_ENCRYPTED = 'ENCRYPTED';

	public function getContent(): ?string {

		$aSchools = $this->getSchools();

		$oSmarty = new \SmartyWrapper();

		$oSmarty->assign('sAppKey', $this->oAppEntity->app_key);
		$oSmarty->assign('oApp', $this);
		$oSmarty->assign('aSchools', $aSchools);

		$aSchoolAttibutes = static::getSettings();

		foreach ($aSchoolAttibutes as $sKey => $aAttribute) {
			foreach ($aSchools as $oSchool) {
				$mValue = $oSchool->getMeta($sKey);
				if (Arr::get($aAttribute, 'encrypted', false) && !empty($mValue)) {
					$mValue = self::VALUE_ENCRYPTED;
				}
				if ($aAttribute['type'] === 'select_multiple') {
					$mValue = (array)$oSchool->getMeta($sKey);
				}

				$aSchoolAttibutes[$sKey]['value'][$oSchool->id] = $mValue;
			}
		}

		$oSmarty->assign('aAttributes', $aSchoolAttibutes);

		return $oSmarty->fetch('@Ts/external_app_per_school.tpl');

	}

	public function saveSettings(\Core\Handler\SessionHandler $oSession, \MVC_Request $oRequest) {

		$aConfig = $oRequest->input('config', []);
		$aUploads = $oRequest->files->get('config')??[];
		$aSchools = $this->getSchools();

		$aSchoolAttibutes = static::getSettings();

		foreach($aSchools as $oSchool) {

			$aSchoolConfig = $aConfig[$oSchool->getId()];
			$aSchoolUploads = $aUploads[$oSchool->getId()];

			foreach($aSchoolAttibutes as $sAttribute => $aAttribute) {

				if ($aAttribute['type'] === 'upload') {
					$uploadedFile = $aSchoolUploads[$sAttribute];
					$sValue = null;
					if($uploadedFile) {
						$sValue = 'visa_check_'.\Util::getCleanFilename($uploadedFile->getClientOriginalName());
						$uploadedFile->move($oSchool->getSchoolFileDir(), $sValue);
					}
				} else {
					$sValue = $aSchoolConfig[$sAttribute];
				}

				if(isset($sValue) && (!is_string($sValue) || trim($sValue) !== '')) {
					if ($aAttribute['type'] === 'select_multiple') {
						$oSchool->setMeta($sAttribute, (array)$sValue);
					} else {
						if (Arr::get($aAttribute, 'encrypted', false)) {
							if ($sValue === self::VALUE_ENCRYPTED) {
								continue;
							}
							$sValue = Crypt::encrypt($sValue);
						}

						$oSchool->setMeta($sAttribute, $sValue);
					}
				} else {
					$oSchool->unsetMeta($sAttribute);
				}

				$oSchool->save();
			}
		}

		$oSession->getFlashBag()->add('success', \L10N::t('Ihre Einstellungen wurden gespeichert'));

	}

	/**
	 * @return \Ext_Thebing_School[]
	 */
	protected function getSchools(): array {
		return \Ext_Thebing_School::getRepository()->findAll();
	}

	abstract public function getSettings(): array;

}
