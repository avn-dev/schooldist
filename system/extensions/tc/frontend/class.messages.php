<?php

use Smarty\Smarty;

/**
 * Fehlerklasse für Frontendformulare
 *
 * @author Mark Friedrich
 */


class Ext_TC_Frontend_Messages {

	const ERROR_LOGIN_FAILED	= 'Benutzername oder Passwort falsch.';
	const ERROR_INVALID_LINK	= 'Link ungültig.';
	const ERROR_WRONG_LINK		= 'Link fehlerhaft.';
	const ERROR_PASSWORD_MATCH	= 'Passwörter sind nicht identisch.';
	const ERROR_PASSWORD_CHANGE	= 'Passwort konnte nicht geändert werden.';
	const ERROR_EMAIL_SEND		= 'E-Mail konnte nicht versendet werden.';
	const ERROR_NO_BOOKING		= 'Keine Buchungsdaten gefunden.';
	const ERROR_GENERATE_HASH	= 'Hash konnte nicht generiert werden.';
	const ERROR_NO_CUSTOMER		= 'Keine Kudendaten gefunden.';
	const ERROR_DATABASE_CONFIG	= 'Datenbank falsch konfiguriert.';
	const ERROR_NO_MAIL	= 'Keine E-Mail-Adresse hinterlegt.';
	const ERROR_PASSWORDSECURE	= 'Passwort Sicherheitsstufe zu niedrig.';
	const ERROR_INVALID_ACTIVATION_KEY = 'Der Aktivierungslink ist ungültig!';
	const INFO_EMAIL_SEND		= 'E-Mail wurde verschickt.';
	const INFO_PASSWORD_CHANGE = 'Das Passwort wurde erfolgreich geändert.';

	/**
	 * @var array
	 */
	protected $_aMessages	= array();

	/**
	 * @var null|Smarty
	 */
	protected $_oSmarty		= null;

	/**
	 * @param Smarty $oSmarty
	 */
	public function __construct(Smarty &$oSmarty){

		$this->_oSmarty = $oSmarty;
		$this->_oSmarty->assign('oMessage', $this);

		$this->_oSmarty->registerObject(
		'oMessage',
		$this,
		array(
			'getMessages'
		));


	}

	/**
	 * Setzt eine Meldung
	 * @param string $sMessage
	 * @param string $sType
	 * @param array $aAdditional
	 */
	public function setMessage($sMessage, $sType = 'error', $aAdditional = array()){

		$aError = array();
		$aError['message'] = $sMessage;
		$aError['additional'] = $aAdditional;

		$this->_aMessages[$sType][] = $aError;
	}

	/**
	 * Liefert alle Meldungen zurück eines oder mehrerer Typen
	 * @param type $mType
	 * @return type
	 */
	public function getMessages($mType = 'error'){

		$aMessages = array();

		if(!is_array($mType)){
			$mType = array($mType);
		}

		foreach((array)$mType as $sType){
			if(isset($this->_aMessages[$sType])){
				foreach((array)$this->_aMessages[$sType] as $aMessage){
					$aMessages[] = $aMessage['message'];
				}
			}
		}

		return $aMessages;
	}

	/**
	 * Mehr als eine Fehlermeldung gleichzeitig setzen.
	 *
	 * @param array $aMessages
	 * @param string $sType
	 * @param array  $aAdditional
	 */
	public function setMessageAsArray($aMessages, $sType = 'error', $aAdditional = []) {

		foreach ($aMessages as $sMessage) {
			$aError = [
				'message' => $sMessage,
				'additional' => $aAdditional,
			];

			$this->_aMessages[$sType][] = $aError;
		}

	}

}