<?php

/**
 * SMS Gateway Basis
 */
class Ext_TC_Communication_SMS_Gateway extends Ext_TC_Communication_SMS_Abstract_Gateway {
	
	public function send() {

		$sLicence = System::d('license');
		$oUpdate = new Ext_TC_Update('core', $sLicence);
		
		$aPost = array(
			'recipient' => $this->_sRecipient,
			'message' => $this->_sMessage,
			'sender' => $this->_sSender,
			//'licence' => $sLicence
		);

		// Das Fragezeichen muss sein, da mit & Parameter angehangen werden.
		$sReturn = $oUpdate->getFileContents('/fidelo/api/sms?', $aPost);

		return $sReturn;
	}
	
	/**
	 * Prüft den String für smskaufen-Richtlinien
	 */
	public static function checkSender($sSender)
	{
		$sSender = preg_quote($sSender);
		$iCheck = preg_match("#^([a-z0-9]{1,11}|[0-9]{1,16})$#i", $sSender);
		
		if(!$iCheck) {
			return false;
		}
		
		return true;
	}

	static public function convertErrorKeyToMessage($sErrorKey) {

		switch ($sErrorKey) {
			case 'NO_CREDITS_LEFT':
				$sMessage = 'Das System verfügt über keine weiteren SMS-Credits.';
				break;
			case 'NOT_SENT_SMS':
				$sMessage = 'Die SMS konnte nicht verschickt werden.';
				break;
			case 'NO_SENDER_SMS':
				$sMessage = 'Es wurde kein Absender eingestellt.';
				break;
			case 'WRONG_SENDER_FORMAT':
				$sMessage = 'Der Absender hat ein falsches Format.';
				break;
			case 'SERVER_ERROR_SMS':
				$sMessage = 'Der Server konnte die SMS nicht verschicken.';
				break;
			case 'INVALID_LICENCE':
				$sMessage = 'Ungültige Lizenz.';
				break;
			case 'MISSING_DATA':
				$sMessage = 'Fehlende Daten.';
				break;
			default:
				throw new RuntimeException('Invalid error key "'.$sErrorKey.'"!');
				break;
		}

		return $sMessage;
	}

}