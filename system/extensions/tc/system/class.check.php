<?php

abstract class Ext_TC_System_Check extends GlobalChecks {

	/**
	 * Titel der Installation: Name der Schule oder Agentur
	 *
	 * @param bool $bShort
	 * @return string
	 */
	protected function getInstallationTitle($bShort=false) {

		if(Ext_TC_Util::getSystem() === 'school') {
			if($bShort) {
				return 'TS';
			}

			$oSchool = Ext_Thebing_Client::getFirstSchool();
			return $oSchool->getName();
		} elseif(Ext_TC_Util::getSystem() === 'agency') {
			if($bShort) {
				return 'TA';
			}

			$oConfig = \Factory::getInstance('Ext_TC_Config');
			return $oConfig->getValue('agency_name');
		} else {
			throw new RuntimeException('Unknown system');
		}
	}

	/**
	 * Liefert den Namen des Checks für den E-Mail-Betreff
	 *
	 * @return string
	 */
	protected function getClassTitle() {

		preg_match('/Ext_(.+)_System_Checks_(.+)/', get_class($this), $aMatches);

		if(
			!empty($aMatches[1]) &&
			!empty($aMatches[2])
		) {
			return str_replace('_', '\\', $aMatches[1].'\\'.$aMatches[2]);
		} else {
			return get_class($this);
		}

	}

	/**
	 * Report an info@thebing.com schicken
	 *
	 * @param string $sText
	 * @return bool
	 */
	protected function sendReport($sText) {

		$oMail = new WDMail();

		$oMail->subject = $this->getInstallationTitle(true);
		$oMail->subject .= ' Checks Report: ';
		$oMail->subject .= $this->getInstallationTitle();
		$oMail->subject .= ' ('.$this->getClassTitle().')';

		$oMail->text = $sText;

		return $oMail->send(['info@thebing.com', 'thebing_error@p32.de']);

	}

}