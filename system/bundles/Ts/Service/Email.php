<?php

namespace Ts\Service;

/**
 * @todo Redundant mit Ext_Thebing_Communication und Ext_Thebing_Mail. Muss vereinheitlicht werden.
 */
class Email extends \Tc\Service\Email {

	/**
	 * @var \Ext_Thebing_School
	 */
	protected $oSchool;

	public function setSchool(\Ext_Thebing_School $oSchool) {
		$this->oSchool = $oSchool;
	}

	// Signatur bei eingesteller Standard-Identität im Template
	private function getUserSignature(\Ext_Thebing_User $oUser = null) {

		$sSignature = '';

		if($oUser !== null) {

			if($this->oTemplate->html == 1) {

				$sSignatureKey = 'signature_email_html_'.$this->sLanguage.'_'.$this->oSchool->id;

			} else {

				$sSignatureKey = 'signature_email_text_'.$this->sLanguage.'_'.$this->oSchool->id;
			}

			// Signatur an den Inhalt anhängen
			$sSignature = \Ext_Thebing_Communication::getMailContentSignature($oUser->$sSignatureKey);
		}

		return $sSignature;
	}

	public function getDefaultIdentityUser() {
		$oUser = $this->oTemplate->getDefaultIdentityUser();
		if($oUser === null) {
			$access = \Access_Backend::getInstance();
			if($access instanceof \Access_Backend) {
				$oUser = $access->getUser();
			}
		}

		return $oUser;
	}

	public function setLayoutAndSignature($oUser, &$sContent) {
		$sSignature = $this->getUserSignature($oUser);
		\Ext_Thebing_Communication::setLayoutAndSignature($this->oTemplate, $this->sLanguage, $sContent, $sSignature);
	}
	
}