<?php

/**
 * Class Ext_Thebing_User_Gui2_Selection_SalesPerson
 */
class Ext_Thebing_User_Gui2_Selection_SalesPerson extends Ext_Gui2_View_Selection_Abstract {

	/**
	 * Setting id
	 *
	 * @var int
	 */
	private $iSettingId;

	/**
	 * Agentur oder Nationalität
	 *
	 * @var string
	 */
	private $sType;

	/**
	 * Enthält die ausgewählten Schulids
	 *
	 * @var array
	 */
	private $aChoosenSchoolIds = [];

	/**
	 * @param int $iSettingId
	 * @param string $sType
	 * @param array $aSchoolIds
	 */
	public function __construct($iSettingId, $sType, array $aSchoolIds) {
		$this->iSettingId = (int)$iSettingId;
		$this->sType = $sType;
		$this->aChoosenSchoolIds = $aSchoolIds;
	}

	/**
	 * @param array $aSelectedIds
	 * @param array $aSaveField
	 * @param Ext_Thebing_Salesperson_Setting $oWDBasic
	 * @return array
	 * @throws Exception
	 */
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic) {

		$aAgencies = Ext_TS_Inquiry_Abstract::getAgenciesForSelect();
		$aNationalities = \Ext_Thebing_Nationality::getNationalities(true, null, 0);
		$aSchools = Ext_Thebing_Client::getSchoolList(true);
		unset($aNationalities['']);

		// Eigene Nutzer wird nicht geladen, damit seine gewählten Optionen nicht raus geworfen werden.
		$aSettings = Ext_Thebing_Salesperson_Setting::getRepository()->getAllSettingsExceptTheGiven($oWDBasic, $this->aChoosenSchoolIds);

		$aSettings = $this->checkAllSettings($aSettings);
		$aWantedElements = $this->generateWantedElements($aSettings);

		if($this->sType === 'agency') {
			$aOptions = $this->deleteWantedElements($aWantedElements, $aAgencies);
		} elseif($this->sType === 'country') {
			$aOptions = $this->deleteWantedElements($aWantedElements, $aNationalities);
		} elseif($this->sType === 'school') {
			$aOptions = $this->deleteWantedElements($aWantedElements, $aSchools);
		} else {
			throw new RuntimeException($this->getErrorMessages()['type_error']);
		}

		return $aOptions;

	}

	/**
	 * Prüft alle Einstellungs-Objekte, ob sie leer sind, wenn ja, brauchen wir diese nicht mehr
	 *
	 * @param Ext_Thebing_Salesperson_Setting[] $aTmpSettings
	 * @return Ext_Thebing_Salesperson_Setting[]
	 */
	private function checkAllSettings(array $aTmpSettings) {
		$aSettings = [];
		foreach($aTmpSettings as $oSalesPersonSetting) {
			if(
				!empty($oSalesPersonSetting->nationalities) ||
				!empty($oSalesPersonSetting->agencies) ||
				!empty($oSalesPersonSetting->schools)
			) {
				$aSettings[] = $oSalesPersonSetting;
			}
		}
		return $aSettings;
	}

	/**
	 * Wenn erfolgreich geprüft, dann wird jedes Verfügbare Element gelöscht, das in dem vergebene Elemente Array steht.
	 *
	 * @param array $aWantedElements
	 * @param array $aAvailableElements
	 *
	 * @return array
	 */
	private function deleteWantedElements(array $aWantedElements, array $aAvailableElements) {
		foreach($aWantedElements as $mKey => $iSettingId) {
			if(isset($aAvailableElements[$mKey])) {
				unset($aAvailableElements[$mKey]);
			}
		}
		return $aAvailableElements;
	}

	/**
	 * Erstellt ein Array aus vergebene Elemente.
	 *
	 * @param Ext_Thebing_Salesperson_Setting[] $aSettings
	 *
	 * @return array
	 * @throws Exception
	 */
	private function generateWantedElements(array $aSettings) {

		$aWantedElements = [];
		foreach($aSettings as $oSetting) {
			if($this->sType === 'agency') {
				$aAgenciesIds = $oSetting->agencies;
				foreach($aAgenciesIds as $iAgencyId) {
					if((int)$oSetting->id !== (int)$this->iSettingId) {
						$aWantedElements[$iAgencyId] = $this->iSettingId;
					}

				}
			} elseif($this->sType === 'country') {
				$aCountriesIsos = $oSetting->nationalities;
				foreach($aCountriesIsos as $sCountryIso) {
					if((int)$oSetting->id !== (int)$this->iSettingId) {
						$aWantedElements[$sCountryIso] = $this->iSettingId;
					}
				}

			} elseif($this->sType === 'school') {
				$aSchoolIds = $oSetting->schools;
				foreach($aSchoolIds as $iSchoolId) {
					if((int)$oSetting->id !== (int)$this->iSettingId) {
						$aWantedElements[$iSchoolId] = $this->iSettingId;
					}
				}
			} else {
				throw new RuntimeException($this->getErrorMessages()['type_error']);
			}
		}

		return $aWantedElements;

	}

	/**
	 * Gibt die Fehlermeldungen zurück
	 *
	 * @return array
	 */
	private function getErrorMessages() {
		return [
			'type_error' => 'You need a valid type! Given type: '.$this->sType
		];
	}

}
