<?php

namespace TcExternalApps\Interfaces;

/**
 * @TODO So etwas existiert auch nochmal als \Ts\Handler\ExternalAppPerSchool pro Schule, kann allerdings viel mehr
 * @see \Ts\Handler\ExternalAppPerSchool
 */
abstract class SystemConfigApp extends \TcExternalApps\Interfaces\ExternalApp {
	
	public function getContent(): ?string {
		
		$oSmarty = new \SmartyWrapper();
		
		$oSmarty->assign('sAppKey', $this->oAppEntity->app_key);
		$oSmarty->assign('oApp', $this);
		
		$aFields = $this->getConfigKeys();
		foreach($aFields as &$aField) {
			if(
				empty($aField['type']) ||
				$aField['type'] !== 'headline'
			) {
				if($aField['type'] === 'multiple_select') {
					$value = json_decode(\System::d($aField['key']));
					if (
						!empty($aField['default']) &&
						empty($value)
					) {
						$value = $aField['default'];
					}
					$aField['value'] = $value;
				} else {
					$aField['value'] = \System::d($aField['key']);
				}
			}
		}
		
		$oSmarty->assign('aFields', $aFields);
		
		return $oSmarty->fetch('@TcExternalApps/system_config_app.tpl');
		
	}
	
	public function saveSettings(\Core\Handler\SessionHandler $oSession, \MVC_Request $oRequest) {
		
		$aConfigs = $oRequest->input('config', []);
		$aConfigKeys = array_filter($this->getConfigKeys(), function($aConfigKey) {
			if(
				empty($aConfigKey['type']) ||
				$aConfigKey['type'] !== 'headline'
			) {
				return true;
			}
			return false;
		});
		$aFieldKeys = array_column($aConfigKeys, 'key');

		// @TODO Das ergibt keinen Sinn, die Werte sind falsch herum. $aConfigs wird nie Werte haben, die es nicht in
		// der getConfigKeys() gibt, andersherum natürlich schon, was auch der Sinn ist laut FlashBag-Message.
		// -> Dann müsste man meiner Meinung nach auch noch oben $aConfigKeys nach 'required' Filtern, weil sonst alle
		// Felder die es gibt required sind, und dann kann man in der getConfigKeys() mit required = true z.B. arbeiten
		$aMissingFields = array_diff_key($aConfigs, array_flip($aFieldKeys));
		
		if(empty($aMissingFields)) {
			// @TODO Hier muss eigentlich (denke ich) nicht jeder Request Wert ($aConfigs) gespeichert werden, sondern
			// jeder Formularwert ($aConfigKeys). Da Werte ja auch gelöscht werden können, kommen die nicht in den Request
			// und werden somit nicht in der system_config gelöscht.
			foreach($aConfigs as $sKey => $mValue) {
				// Für type = multiselect
				if(is_array($mValue)) {
					$mValue = json_encode($mValue);
				}
				\System::s($sKey, $mValue);
			}
			
			$oSession->getFlashBag()->add('success', \L10N::t('Ihre Einstellungen wurden gespeichert'));
		} else {
			$oSession->getFlashBag()->add('error', \L10N::t('Bitte füllen Sie alle Werte aus'));
		}
		
	}
	
	abstract protected function getConfigKeys() : array;
	
}