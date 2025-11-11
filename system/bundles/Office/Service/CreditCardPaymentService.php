<?php

namespace Office\Service;

use Office\Helper\CreditCardPaymentHelper;

/**
 * @todo Aufräumen
 */
class CreditCardPaymentService {

	private $aStripe = null;
	
	public function __construct() {
		
		$this->aStripe = [
			"secret_key" => \Ext_Office_Config::get('paymill_private_key'),
			"publishable_key" => \Ext_Office_Config::get('paymill_public_key')
		];
		
		\Stripe\Stripe::setApiKey($this->aStripe['secret_key']);

	}
	
	public function showInvoice(\Smarty $oSmarty, string $sHash) {

		// Ein Kreditkartenhelfer-Objekt instanziieren
		$oCreditCardPaymentHelper = new CreditCardPaymentHelper();

		// Das dem hash entsprechende Rechnungs-Objekt instanziieren
		$iDocumentId = \Ext_Office_Document::getIdFromHash($sHash);
		
		$oDocument = new \Ext_Office_Document($iDocumentId);
		if($oDocument->type !== 'account') {
			$oCreditCardPaymentHelper->assignError($oSmarty, 'This is not an invoice!');
			return;
		}

		// Der relative Link zum Verzeichnis der Rechnungen
		$sPDFWebFileDirectory = $oDocument->getWebFileDir();
		// Der Name der PDF
		$sPDFFilename = $oDocument->getFilePath();
		
		// Eventuelle Ausgaben vorher beenden
		while (ob_get_level()) {
			ob_end_clean();
		}
		
		header('Content-type: application/pdf');
		header('Content-Disposition: inline; filename="'.$oDocument->getPDFFilename().'"');
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . filesize($sPDFFilename));
		header('Accept-Ranges: bytes');

		readfile($sPDFFilename);

		die();
		
	}
	
	/**
	 * Füllt das Smarty-Objekt mit den Daten, die für die Kreditkartenzahlung
	 * erforderlich sind.
	 *  
	 * @param \Smarty $oSmarty Das zu füllende Smarty-Objekt.
	 * @param string $sHash Der Hash der zur einer Rechnung gehört.
	 */
	public function preparePayment(\Smarty $oSmarty, $sHash){

		// Ein Kreditkartenhelfer-Objekt instanziieren
		$oCreditCardPaymentHelper = new CreditCardPaymentHelper();

		// Das dem hash entsprechende Rechnungs-Objekt instanziieren
		$iDocumentId = \Ext_Office_Document::getIdFromHash($sHash);

		// Wenn kein Document zu diesem Hash gefunden wurde, dann eine Fehlerausgabe erstellen.
		if( is_null($iDocumentId) ){
			$oCreditCardPaymentHelper->assignError($oSmarty, 'No invoice found!');
		} else {
			// Neues Document-Objekt instanziieren
			$oDocument = new \Ext_Office_Document($iDocumentId);
			// Typ des Documents
			$sDocumentType = $oDocument->type;
			// Wenn das Document keine Rechnung ist
			if( $sDocumentType !== 'account' ){
				$oCreditCardPaymentHelper->assignError($oSmarty, 'This is not an invoice!');
			} else {
				
				// Hänge dem Smarty-Objekt die nötigen Daten an
				$oCreditCardPaymentHelper->assignInitialData($oSmarty, $oDocument);
			}
		}
	}

	/**
	 * Versucht eine Zahlung zu tätigen. Um das Ergebnis des Zahlungsversuchs
	 * zu bekommen, sollte nach dieser Methode die Methode 
	 * <i>prepareOutput(\Smarty $oSmarty, $sHash)</i> aufgerufen werden. Das 
	 * Ergebnis wird somit im Smarty-Objekt gespeichert.
	 * @param type $aFormdata
	 */
	public function confirmPayment(\Smarty $oSmarty, $aFormdata) {
		
		$paymentIntentId = $aFormdata['paymentIntentId'];
		$sHash = $aFormdata['hash'];
		
		$iDocumentId = \Ext_Office_Document::getIdFromHash($sHash);
		$oDocument = new \Ext_Office_Document($iDocumentId);
		
		$oCreditCardPaymentHelper = new CreditCardPaymentHelper();

		try {

			$stripe = new \Stripe\StripeClient($this->aStripe['secret_key']);
			$paymentIntent = $stripe->paymentIntents->retrieve($paymentIntentId);

			$oCreditCardPaymentHelper->handleResultData($oSmarty, $paymentIntent, $oDocument);
			
		} catch (\Exception $oException) {
			
			$oCreditCardPaymentHelper->handleException($oSmarty, $oException, $oDocument, $aFormdata);
			
		}

	}

}