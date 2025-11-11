<?php

namespace Ts\Controller;

/**
 * Class SalespersonController
 * @package Ts\Controller
 */
class SalespersonController extends \MVC_Abstract_Controller {

	/**
	 * @var bool
	 */
	private $bSave;

	/**
	 * @var bool
	 */
	private $bDelete;

	/**
	 * Hauptmethode zur Interaktion mit der Oberfläche. GET-Request
	 * @return void
	 */
	public function getSetupAction() {
		$this->postSetupAction();
	}

	/**
	 * Hauptmethode zur Interaktion mit der Oberfläche. POST-Request
	 * Anmerkung: return void, da ein echo verwendet wird.
	 * @return void
	 */
	public function postSetupAction() {

		$oSmarty = $this->getSmarty();
		$iId = (int)$this->_oRequest->input('id');

		$oSmarty->assign('iId', $iId);

		$sTask = $this->_oRequest->input('task');
		$iSettingId = (int)$this->_oRequest->input('remove_setting');

		/** @var \Ext_Thebing_User $oUser */
		$oUser = \Ext_Thebing_User::getInstance($iId);

		if($sTask === 'save') {

			$this->saveInformations($oUser);

			$oSmarty->assign('bSaved', true);

		}

		if($sTask === 'remove-setting') {
			$this->removeOneInformations($oUser, $iSettingId);
		}

		if($sTask === 'add_settings') {
			$this->addANewSalesPersonSetting($oUser);
		}

		// Daten aus dem Formular.
		$aChoosenSchools = $this->_oRequest->input('schools');
		$aChoosenNationalities = $this->_oRequest->input('nationalities');
		$aChoosenAgencies = $this->_oRequest->input('agencies');

		$aSalesPersonSettings = (array)$oUser->getJoinedObjectChilds('salespersonssettings');

		if(
			$sTask !== 'save' &&
			empty($aSalesPersonSettings)
		) {
			$aSalesPersonSettings[] = $this->addANewSalesPersonSetting($oUser);
		}

		$aAllAgencies = \Ext_TS_Inquiry_Abstract::getAgenciesForSelect();
		$aAllNationalities = \Ext_Thebing_Nationality::getNationalities(true, null, 0);
		$aAllSchools = \Ext_Thebing_Client::getSchoolList(true);

		$aGetBackSettings = [];
		foreach($aSalesPersonSettings as $oSalesPersonSetting) {

			$aTmpAgencies = $aAllAgencies;
			$aTmpNationalities = $aAllNationalities;
			$aTmpSchools = $aAllSchools;

			// Zuordnung von Agenturen zur Einstellung
			$aSalesPersonAgencies = [];
			foreach($oSalesPersonSetting->agencies as $iAgencyId) {
				if(isset($aTmpAgencies[$iAgencyId])) {
					$aSalesPersonAgencies[$iAgencyId] = $aTmpAgencies[$iAgencyId];
				}
			}

			// Zuordnung von Nationalitäten zur Einstellung
			$aSalesPersonNationalities = [];
			foreach($oSalesPersonSetting->nationalities as $sCountryIso) {
				if(isset($aTmpNationalities[$sCountryIso])) {
					$aSalesPersonNationalities[$sCountryIso] = $aTmpNationalities[$sCountryIso];
				}
			}

			// Zuordnung von Schulen zur Einstellung
			$aSalesPersonSchools = [];
			foreach($oSalesPersonSetting->schools as $iSchoolId) {
				if(isset($aTmpSchools[$iSchoolId])) {
					$aSalesPersonSchools[$iSchoolId] = $aTmpSchools[$iSchoolId];
				}
			}

			// Die ausgewählten Daten aus dem Formular hinzugefügen, wenn diese noch nicht ausgewählt wurde.
			if(isset($aChoosenAgencies[$oSalesPersonSetting->getId()])) {
				$aAgenciesIds = $aChoosenAgencies[$oSalesPersonSetting->getId()];
				$aAgenciesIds = explode(',', $aAgenciesIds);
				foreach($aAgenciesIds as $iAgenciesId) {
					if (
						!empty($iAgenciesId) &&
						!isset($aSalesPersonAgencies[$iAgenciesId])
					) {
						$aSalesPersonAgencies[$iAgenciesId] = $aTmpAgencies[$iAgenciesId];
					}
				}
			}

			if(isset($aChoosenNationalities[$oSalesPersonSetting->getId()])) {
				$aNationalities = $aChoosenNationalities[$oSalesPersonSetting->getId()];
				$aNationalities = explode(',', $aNationalities);
				foreach($aNationalities as $sNationality) {
					if (
						!empty($sNationality) &&
						!isset($aSalesPersonNationalities[$sNationality])
					) {
						$aSalesPersonNationalities[$sNationality] = $aTmpNationalities[$sNationality];
					}
				}
			}

			if(isset($aChoosenSchools[$oSalesPersonSetting->getId()])) {
				$aSchoolsIds = $aChoosenSchools[$oSalesPersonSetting->getId()];
				foreach($aSchoolsIds as $iSchoolsId) {
					if (!isset($aSalesPersonNationalities[$iSchoolsId])) {
						$aSalesPersonSchools[$iSchoolsId] = $aTmpSchools[$iSchoolsId];
					}
				}
			}

			if (1 ||
				$sTask === 'add_settings' ||
				!empty($aSalesPersonSchools) ||
				!empty($aSalesPersonNationalities) ||
				!empty($aSalesPersonAgencies)
			) {
				$aGetBackSettings[$oSalesPersonSetting->getId()] = [
					'nationalities' => $aSalesPersonNationalities,
					'agencies' => $aSalesPersonAgencies,
					'schools' => $aSalesPersonSchools,
				];
			} else {
				if ($sTask !== 'add_settings') {
					// Ansonsten kann die Einstellung gelöscht werden.
					$this->removeOneInformations($oUser, $oSalesPersonSetting->getId());
				}
			}
		}

		$oSmarty->assign('aSchools', $aAllSchools);
		$oSmarty->assign('aSalesPersonSettings', $aGetBackSettings);

		$oSmarty->assign('sUser', $oUser->getName());

		$sTemplatePath = $this->getTemplatePath().'setup.tpl';
		$sContent = $oSmarty->fetch($sTemplatePath);

		echo $sContent;
		
		die();

	}

	/**
	 * Fügt ein neue Setting zum User hinzu
	 *
	 * @param \Ext_Thebing_User $oUser
	 * @return \Ext_Thebing_Salesperson_Setting
	 */
	private function addANewSalesPersonSetting(\Ext_Thebing_User $oUser) {

		/** @var \Ext_Thebing_Salesperson_Setting $oSetting */
		$oSetting = $oUser->getJoinedObjectChild('salespersonssettings');
		$oSetting->save();

		return $oSetting;
	}

	/**
	 * @param \Ext_Thebing_User $oUser
	 * @param \MVC_Request $oRequest
	 * @param array $aSchools
	 * @return void
	 */
	private function saveInformations(\Ext_Thebing_User $oUser) {

		$aSettings = $oUser->getJoinedObjectChilds('salespersonssettings');
		$aSettingsData = $this->generateSalesPersonDetails($aSettings);

		$this->addSalesPerson($oUser, $aSettingsData);

	}

	/**
	 * Löscht eine Einstellung eines Benutzers.
	 *
	 * @param \Ext_Thebing_User $oUser
	 * @param int $iSettingId
	 * @return void
	 */
	private function removeOneInformations(\Ext_Thebing_User $oUser, $iSettingId) {

		/** @var \Ext_Thebing_Salesperson_Setting[] $aSettings */
		$aSettings = $oUser->getJoinedObjectChilds('salespersonssettings');

		if(!empty($aSettings)) {
			foreach($aSettings as $oSetting) {
				if ((int)$oSetting->getId() === $iSettingId) {
					$oSetting->delete();
					break;
				}
			}
		}

	}

	/**
	 * Generiert ein Array für die Sales Person
	 *
	 * @param \Ext_Thebing_Salesperson_Setting[] $aSalesPersonSettings
	 * @return array
	 */
	private function generateSalesPersonDetails(array $aSalesPersonSettings) {

		$aAgencies = $this->_oRequest->input('agencies');
		$aCountries = $this->_oRequest->input('nationalities');
		$aSchools = $this->_oRequest->input('schools');

		$aReturn = [];

		foreach($aSalesPersonSettings as $oSalesPersonSetting) {

			$aAgenciesIds = [];
			$aCountriesIsos = [];
			$aSchoolIds = [];
			
			if(
				isset($aAgencies[$oSalesPersonSetting->getId()]) &&
				!empty($aAgencies[$oSalesPersonSetting->getId()])
			) {
				$aTmpAgenciesIds = explode(',', $aAgencies[$oSalesPersonSetting->getId()]);
				foreach($aTmpAgenciesIds as $iAgencyId) {
					$aAgenciesIds[] = $iAgencyId;
				}
			}
			if(
				isset($aCountries[$oSalesPersonSetting->getId()]) &&
				!empty($aCountries[$oSalesPersonSetting->getId()])
			) {
				$aTmpCountriesIso = explode(',', $aCountries[$oSalesPersonSetting->getId()]);
				foreach($aTmpCountriesIso as $sCountryIso) {
					$aCountriesIsos[] = $sCountryIso;
				}
			}
			if(
				isset($aSchools[$oSalesPersonSetting->getId()]) &&
				!empty($aSchools[$oSalesPersonSetting->getId()])
			) {
				foreach($aSchools[$oSalesPersonSetting->getId()] as $iSchoolId) {
					$aSchoolIds[] = $iSchoolId;
				}
			}
			
			$aReturn[$oSalesPersonSetting->id] = [
				'agencies' => $aAgenciesIds,
				'nationalities' => $aCountriesIsos,
				'schools' => $aSchoolIds,
			];
			
		}

		return $aReturn;
	}

	/**
	 * Speichert die Sales Person Datensätze
	 *
	 * @param \Ext_Thebing_User $oUser
	 * @param array $aSalesPersonDetails
	 *
	 * @return void
	 */
	public function addSalesPerson(\Ext_Thebing_User $oUser, array $aSalesPersonDetails) {

		$aSalesPersonSettings = $oUser->getJoinedObjectChilds('salespersonssettings');

		foreach($aSalesPersonSettings as $oSalesPersonSetting) {

			$aTmpNationalities = [];
			$aTmpAgencies = [];
			$aTmpSchools = [];

			foreach($aSalesPersonDetails[$oSalesPersonSetting->id]['nationalities'] as $aInformations) {
				$aTmpNationalities[] = $aInformations;
			}
			foreach($aSalesPersonDetails[$oSalesPersonSetting->id]['agencies'] as $aInformations) {
				$aTmpAgencies[] = $aInformations;
			}
			foreach($aSalesPersonDetails[$oSalesPersonSetting->id]['schools'] as $aInformations) {
				$aTmpSchools[] = $aInformations;
			}

			$oSalesPersonSetting->nationalities = $aTmpNationalities;
			$oSalesPersonSetting->agencies = $aTmpAgencies;
			$oSalesPersonSetting->schools = $aTmpSchools;

			// Leere Objekte werden nicht benötigt, daher wieder löschen
			if(
				empty($oSalesPersonSetting->nationality) &&
				empty($oSalesPersonSetting->agencies) &&
				empty($oSalesPersonSetting->schools)
			) {
				$oSalesPersonSetting->delete();
			} else {
				$oSalesPersonSetting->save();
			}

		}

		$this->bSave = true;
	}

	/**
	 * Gibt die Optionen per 'echo' aus.
	 *
	 * @param string $sType
	 */
	private function getOptions($sType) {

		$iSettingId = $this->_oRequest->input('setting_id');
		$iUserId = $this->_oRequest->input('user_id');
		$sQuery = $this->_oRequest->input('query');
		$sSchools = $this->_oRequest->input('schools');

		$aSchoolIds = explode(',', $sSchools);

		$sPrefix = mb_substr($sQuery, 0, 3);
		
		$sCacheKey = 'ts_salesperson_controller_'.$sType.'_'.$iSettingId.'_'.$iUserId.'_'.$sSchools.'_'.$sPrefix;
		#$aReturn = \WDCache::get($sCacheKey);
		$aReturn = null;

		if($aReturn === null) {

			$oSelection = new \Ext_Thebing_User_Gui2_Selection_SalesPerson($iSettingId, $sType, $aSchoolIds);

			$oSetting = \Ext_Thebing_Salesperson_Setting::getInstance($iSettingId);

			$aOptions = $oSelection->getOptions([$iUserId], [], $oSetting);

			$aReturn = [];
			foreach($aOptions as $iOptionId => $sOption) {
				if(mb_stripos($sOption, $sPrefix) === 0) {
					$aReturn[] = [
						'value' => $iOptionId,
						'text' => $sOption
					];
				}
			}

			\WDCache::set($sCacheKey, 10*60, $aReturn);

		}

		header('Content-Type: application/json');
		echo json_encode($aReturn);
		die();
	}

	/**
	 * Wird für den Request von TypeAhead benötigt
	 */
	public function getAgenciesAction() {
		$this->getOptions('agency');
	}

	/**
	 * Wird für den Request von TypeAhead benötigt
	 */
	public function getNationalitiesAction() {
		$this->getOptions('country');
	}

	/**
	 * Erstellt ein Smarty-Objekt hinzu und gibt den TemplateDir an.
	 *
	 * @return \SmartyWrapper
	 */
	private function getSmarty() {
		$oSmarty = new \SmartyWrapper();
		$oSmarty->setTemplateDir(\Util::getDocumentRoot(true));
		return $oSmarty;
	}

	/**
	 * Gibt den Pfad zum Template-Ordner aus
	 *
	 * @return string
	 */
	private function getTemplatePath() {
		return \Util::getDocumentRoot().'system/bundles/Ts/Resources/views/salesperson/';
	}

	/**
	 * Gibt die Übersicht zurück
	 * Return "void" da die Methode keinen Rückgabe-Wert hat, sondern ein echo in Verbindung mit die(); verwendet.
	 * @param string $sType
	 * @return void
	 */
	public function getOverview($sType) {
		
		$oSmarty = $this->getSmarty();
		
		$aSchools = \Ext_Thebing_Client::getSchoolList(true);
		
		$oClient = \Ext_Thebing_Client::getInstance();
		$aUsers = $oClient->getUsers(true);
		
		$oSettingRepo = \Ext_Thebing_Salesperson_Setting::getRepository();
		$aSettings = $oSettingRepo->findAll();
		
		$aData = [];
		foreach($aSettings as $oSetting) {
			
			$iUserId = $oSetting->user_id;
			$aSchoolIds = $oSetting->schools;
			$aTypeKeys = $oSetting->$sType;
			if(!empty($aTypeKeys)) {
				foreach($aTypeKeys as $iTypeId) {
					foreach($aSchoolIds as $iSchoolId) {
						$aData[$iTypeId][$iSchoolId] = $iUserId;
					}
				}
			
			}
			
		}

		if($sType === 'nationalities') {
			$aItems = \Ext_Thebing_Nationality::getNationalities(true, null, 0);
		} else {
			$aItems = \Ext_TS_Inquiry_Abstract::getAgenciesForSelect();
		}
		
		$oSmarty->assign('aSchools', $aSchools);
		$oSmarty->assign('aItems', $aItems);
		$oSmarty->assign('aData', $aData);
		$oSmarty->assign('aUsers', $aUsers);
		
		$sTemplatePath = $this->getTemplatePath().'overview.tpl';
		$sContent = $oSmarty->fetch($sTemplatePath);

		echo $sContent;

		die();
	}
	
}