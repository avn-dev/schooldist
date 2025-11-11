<?php

namespace Office\Controller;

/**
 * @TODO Wird die Klasse nach der SWIPE-Umstellung noch verwendet?
 *
 * Über diesen Controller kann bei einem Fehler im JavaScript eine Email 
 * an den Admin versendet werden.
 */
class CreditCardController extends \MVC_Abstract_Controller {

	//use \Core\Traits\MVCControllerToken;

	/**
	 * Zugriffsbeschränkung per Token
	 */
	//protected $_bUseToken = true;

	/**
	 * Login für diesen Controller ausschalten
	 */
	protected $_sAccessRight = false;

	/**
	 * Sendet eine Email an den Kunden und 
	 */
	public function postEmailAction(){
		// Hole den Inhalt der Mail
		$sText = $this->_getErrorReportContent();
		// Sende die Mail
		\Util::handleErrorMessage($sText, 1, 0, 1);
	}

	/**
	 * Gibt den Inahlt für den Errorreport zurück.
	 * 
	 * @return string
	 */
	private function _getErrorReportContent(){
		// Hole die POST-Daten
		$aPostData = $this->_oRequest->getJSONDecodedPostData();
		// Die Rechnungsdaten
		$aPaymentData = $aPostData['payment_data'];

		// Stelle den Text zusammen
		$sText = "Während einer Kreditkartenzahlung ist folgendes clientseitiges Problem aufgetreten:\n";
		$sText .= "   " . implode('\n   ', $aPostData['errors']) . "\n\n";
		$sText .= "Details zum Verursacher:\n";
		$sText .= "   Kundennummer: " . $aPaymentData['customer_number'] ."\n";
		$sText .= "   Rechnungsnummer: " . $aPaymentData['document_number'] ."\n\n";

		return $sText;
	}

	/**
	 * Diese Methode prüft, ob der Zugang erlaubt ist.
	 * 
	 * @return boolean Wenn der Zugang erlaubt ist <b>TRUE</b>, sonst <b>FALSE</b>.
	 */
	protected function checkToken() {

		// Wenn ein Token benutzt werden soll
//		if ($this->_bUseToken) {

			// Starte eine Session, wenn keine vorhanden ist, um an den access_token zu kommen
			$oSession = \Core\Handler\SessionHandler::getInstance();

			// Hole die POST-Daten
			$aPostData = $this->_oRequest->getJSONDecodedPostData();
			// Der access_token, der übermittelt wird
			$sAccessToken = $aPostData['access_token'];
			// Der access_token, der in der Session gespeichert ist
			$sSessionAccessToken = $oSession->get('office_controller_creditCard_access_token');
			// Wenn diese Tokens übereinstimmen, dann ist der Zugang erlaubt.
			$bValid = false;
			if ($sAccessToken === $sSessionAccessToken) {
				$bValid = true;
			}
			return $bValid;
//		} else {
//			return true;
//		}
	}
}