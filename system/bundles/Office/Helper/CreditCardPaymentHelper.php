<?php

namespace Office\Helper;

use Smarty\Smarty;

/**
 * @todo Aufräumen!
 */
class CreditCardPaymentHelper {

	/**
	 * Wertet den Fehler aus, versendet eine Mail an den Administrator und erweitert
	 * das Smarty-Objekt um folgendes:
	 * 
	 * <ul>
	 *	<li>invoice: Status der Rechnunf
	 *	 <ul><li>payable: Rechnung kann bezahlt werden</li></ul>
	 *	</li>
	 *	<li>error: Ein Fehler, der nicht mit einer Falschen Eingabe zu tun hat, <b>oder</b></li>
	 *	<li>card_error: Ein Fehler, der mit der Eingabe zu tun hat. Z.B. falsche CVC.</li>
	 * </ul>
	 * 
	 * @param Smarty $oSmarty Das zu füllende Smarty-Objekt
	 * @param \Paymill\Services\PaymillException $oException Das Exception-Objekt
	 * @param \Ext_Office_Document $oDocument Das Rechnungs-Objekt
	 */
	public function handleException(Smarty $oSmarty, \Exception $oException, \Ext_Office_Document $oDocument, $aFormdata) {

		$sText = $this->_getErrorReportContent($oException, $oDocument);
		// Sende die Mail
		\Util::handleErrorMessage($sText, 1, 0, 1);
		$this->assignInitialData($oSmarty, $oDocument);
		$this->assignError($oSmarty, $oException->getMessage());

	}

	/**
	 * Diese Methode ist für den ersten Aufruf einer Kreditkartenzahlung und
	 * fügt dem Smarty-Objekt folgende Variablen hinzu:
	 * <ul>
	 *	<li>action: Was im moment passiert
	 *	 <ul><li>payment: Zahlung anzeigen</li></ul>
	 *	</li>
	 *	<li>next: Was als nächstes passieren wird
	 *	 <ul><li>attempt: Versuch einer Zahlung</li></ul>
	 *	</li>
	 *	<li>invoice: Status der Rechnung
	 *	 <ul>
	 *	  <li>paid:	Rechnung bereits gezahlt</li>
	 *	  <li>draft: Rechnung nur als entwurf vorliegen</li>
	 *	  <li>payable: Rechnung kann bezahlt werden</li>
	 *	 </ul>
	 *	</li>
	 *	<li>cardCurrency: Die Währung nach ISO 4217</li>
	 *	<li>pdfFile: Der Link zur Rechnung</li>
	 *	<li>customerCompany: Name der Firma des Kunden</li>
	 *	<li>customerZip: PLZ des Kunden</li>
	 *	<li>customerCity: Stadt des Kunden</li>
	 *	<li>customerCountry: Land des Kunden</li>
	 *	<li>customerNumber: Kundennummer</li>
	 *	<li>editorFirstname: Vorname des Ansprechpartners</li>
	 *	<li>editorLastname: Nachname des Ansprechpartners</li>
	 *	<li>editorEmail: Email des Ansprechpartners</li>
	 *	<li>editorPhone: Telefon des Ansprechpartners</li>
	 *	<li>contactPersonFirstname: Vorname der Kontaktperson</li>
	 *	<li>contactPersonLastname: Nachname der Kontaktperson</li>
	 *	<li>documentHash: Hash der Rechnung</li>
	 *	<li>documentNumber: Rechnungsnummer</li>
	 *	<li>documentPrice: Gesamtpreis der Rechnung</li>
	 *	<li>documentAmountPaid: Bereits bezahlter Betrag</li>
	 *	<li>documentRemainingAmount: Ausstehender betrag</li>
	 *	<li>creditcardTypes: Kreditkartentypen (Array)</li>
	 * </ul>
	 * 
	 * @param Smarty $oSmarty Das zu füllende Smarty-Objekt
	 * @param \Ext_Office_Document $oDocument Das Rechnungs-Objekt
	 */
	public function assignInitialData(Smarty $oSmarty, \Ext_Office_Document $oDocument) {

		// Prüfe den Status der Rechnung
		$this->_checkState($oSmarty, $oDocument);

		// Erweitere das Smarty-Objekt
		$this->_assignDocumentPDFFile($oSmarty, $oDocument);
		$this->_assignDocumentHash($oSmarty, $oDocument);
		$this->_assignDocumentNumber($oSmarty, $oDocument);
		$this->_assignDocumentPrice($oSmarty, $oDocument);
		$this->_assignCustomerData($oSmarty, $oDocument);
		$this->_assignEditorData($oSmarty, $oDocument);
		$this->_assignContactPerson($oSmarty, $oDocument);
		$oSmarty->assign('action', 'payment');
		$oSmarty->assign('next', 'attempt');

		// Der Public Paymill-Key
		$oSmarty->assign('publicStripeKey', \Ext_Office_Config::get('paymill_public_key'));

		// Der Gesmtpreis der Rechnung
		$fDocumentPrice = $this->getDocumentTotal($oDocument);
		$fAmountPaid = $this->getDocumentPayed($oDocument);

		// Noch zu zahlender Betrag
		$fRemainingAmount = $fDocumentPrice - $fAmountPaid;
		
		if($fRemainingAmount > 0) {
		
			$paymentIntent = \Stripe\PaymentIntent::create([
				'amount' => $fRemainingAmount*100,
				'currency' => 'eur',
				'customer' => $this->_getClientId($oDocument),
				'description' => $this->_generateDescription($oDocument),
				'payment_method_options' => ['card' => ['request_three_d_secure' => 'challenge']]
			]);

			$oSmarty->assign('clientSecret', $paymentIntent->client_secret);
			
		}
	}

	/**
	 * <p>
	 * Gibt die Payment-Client-Id des Kunden zurück. Wenn keine vorhand ist,
	 * dann wird bei Paymill ein Kunde angelegt und dessen neue Client-Id
	 * in der Datenbank gespeichert und zurückgegeben.
	 * </p>
	 * @param array $aFormdata Die Formulardaten.
	 * @return string Die Client-Id.
	 */
	private function _getClientId(\Ext_Office_Document $oOfficeDocument) {

		// Id des Kunden
		$iCustomerId = (int) $oOfficeDocument->customer_id;
		// Kunden-Objekt eines bestimmten Kunden instanziieren
		$oCustomer = new \Ext_Office_Customer('office_customers', $iCustomerId);

		if (
			$oCustomer->paymill_client_id === null ||
			strpos($oCustomer->paymill_client_id, 'cus_') !== 0
		) {
			$this->_createClient($oCustomer);
		}

		return $oCustomer->paymill_client_id;
	}

	/**
	 * <p>
	 * Erstellt einen neuen Kunden auf Paymill und gibt dessen Client-Id zurück.
	 * Zusätzlich wird die neue Clinet-Id des Kunden in der Datenbank
	 * abgespeichert.
	 * </p>
	 * @param \Ext_Office_Customer $oCustomer Der Kunde.
	 * @return string Die neue Client-Id.
	 */
	private function _createClient(\Ext_Office_Customer $oCustomer) {

		$oStripeCustomer = \Stripe\Customer::create(array(
			'description' => $oCustomer->company
		));

		// Speichere die paymill_client_id zum Kunden
		$oCustomer->paymill_client_id = $oStripeCustomer->id;
		// Speicher den Kunden ab
		$oCustomer->save();

	}

	/**
	 * Generiere eine Beschreibung der Kreditkartentransaktion und gib diese zurück.
	 * Diese Beschreibung besteht aus dem Namen "Fidelo Software GmbH" und 
	 * der Kunden - sowie Rechnungsnummer.
	 * 
	 * @param string $sHash Der Hash der Rechnung.
	 * @return string Die Beschreibung der Transaktion.
	 */
	private function _generateDescription($oOfficeDocument) {

		// Id des Kunden
		$iCustomerId = (int) $oOfficeDocument->customer_id;
		// Kunden-Objekt eines bestimmten Kunden instanziieren
		$oCustomer = new \Ext_Office_Customer('office_customers', $iCustomerId);
		// Kundennummer
		$sCustomerNumber = $oCustomer->number;
		// Rechnungsnummer
		$sDocumentNumber = $oOfficeDocument->number;

		// Transaktionsbeschreibung
		$sDescription = 'Fidelo Software GmbH ' . $sCustomerNumber . '-' . $sDocumentNumber;

		return $sDescription;
	}
	
	/**
	 * Diese Methode ist für den Aufruf nach einem versucht einer 
	 * Kreditkartenzahlung gedacht. Sie analysiert somit das Ergebnis einer 
	 * Paymill-Transaktion. Folgende Variablen werden dem Smarty-Objekt
	 * hinzugefügt:
	 * <ul>
	 *	<li>action: Was im moment passiert
	 *	 <ul><li>success: Zahlung erfolgreich</li></ul>
	 *	</li>
	 * </ul>
	 * Wenn die Zahlung erfolgreich war, dann wird der gezahlze Betrag in die DB
	 * eingetragen.
	 * 
	 * @param Smarty $oSmarty Das zu füllende Smarty-Objekt
	 * @param \Paymill\Models\Response\Transaction|null $oResponse Das Repsonse-Objekt der Kreditkartentransaktion
	 * @param string $sHash Der Hash der Rechnung
	 */
	public function handleResultData(Smarty $oSmarty, \Stripe\PaymentIntent $paymentIntent, \Ext_Office_Document $oDocument) {

		$log = \Log::getLogger('office', 'payments');

		if($paymentIntent->status == 'succeeded') {
			
			$log->info('Payment confirmed', [$paymentIntent]);
			
			// Erweitere das Smarty-Objekt
			$oSmarty->assign('action', 'success');
			
			// Instanziiere ein ExtnsionDao-Office-Objekt
			$aOfficeConfig = \Ext_Office_Config::getAll();
			$oExtensionDaoOffice = new \classExtensionDao_Office($aOfficeConfig);

			// Der Überwiesene Betrag
			$iAmount = (int)$paymentIntent->amount_received;
			$fAmount = ($iAmount / 100);

			// Trage eine Zahlung ein und verschicke eine Email
			$oExtensionDaoOffice->savePayment($oDocument->id, $fAmount, 0, true, 'Stripe: '.$paymentIntent->id);

		} else {
			
			$log->info('Payment error', [$paymentIntent]);
			$this->assignError($oSmarty, 'Payment not successful. Please try again!');
			
		}

	}

	/**
	 * Diese Methode prüft, ob die Rechnung bereits bezahlt wurde oder ob
	 * die Rechnung noch im Entwurfsmodus ist. Dementsprechend werden dem
	 * Smarty-Objekt die Felder hinzugefügt:
	 * <ul>
	 *  <li>invoice: Status der Rechnunf
	 *	 <ul>
	 *	  <li>paid:	Rechnung bereits gezahlt</li>
	 *	  <li>draft: Rechnung nur als entwurf vorliegen</li>
	 *	 </ul>
	 *	</li>
	 * </ul>
	 * @param type $oSmarty Das zu füllende Smarty-Objekt
	 * @param type $oDocument	Das Rechnungs-Objekt
	 */
	private function _checkState($oSmarty, $oDocument){
		// Der Status der Rechnung
		$sState = $oDocument->state;
		// Wenn die Rechnung bereits bezahlt ist
		if ($sState === "paid") {
			$oSmarty->assign('invoice', 'paid');
		} elseif($sState === "draft"){
			// Wenn die Rechnung noch im Entwurf ist.
			$oSmarty->assign('invoice', 'draft');
		} else {
			// Wenn die Rechnung noch bezahlt werden muss
			$oSmarty->assign('invoice', 'payable');
			// Erweitere das Smarty-Objekt
			$oSmarty->assign('cardCurrency', 'EUR');
		}
	}

	/**
	 * Füllt das Smarty-Objekt mit folgende Variablen:
	 * <ul><li>pdfFile: Der Link zur Rechnung</li></ul>
	 * 
	 * @param Smarty $oSmarty Das zu füllende Smarty-Objekt
	 * @param \Ext_Office_Document $oDocument Das Rechnungs-Objekt
	 */
	private function _assignDocumentPDFFile(Smarty $oSmarty, \Ext_Office_Document $oDocument){
		// Der relative Link zum Verzeichnis der Rechnungen
		$sPDFWebFileDirectory = $oDocument->getWebFileDir();
		// Der Name der PDF
		$sPDFFilename = $oDocument->getPDFFilename();
		// Der relative Pfad zur Rechnungs-PDF auf dem Server
		$sPDFWebPath = $sPDFWebFileDirectory . $sPDFFilename;

		// Splitte den Link auf und speichere es Temporär in einer Array. Splitte wenn ein '/' kommt.
		$aTemp = explode('/', $sPDFWebFileDirectory);
		// Entfert das erste Element
		array_shift($aTemp);
		// Mache aus dem array ein String. Der Link ohne das 'media/' am Anfang
		$sDocumentPDFWebFileDirectoryPart = implode('/', $aTemp);
		// Setze ein Falg, damit die PDF im Secureordner auch von außen zugänglich ist.
		$_SESSION['access']['media']['secure'][$sDocumentPDFWebFileDirectoryPart][$sPDFFilename] = 1;

		// Erweitere das Smarty-Objekt
		$oSmarty->assign('pdfFile', '/' . $sPDFWebPath);
	}

	/**
	 * Füllt das Smarty-Objekt mit folgende Variablen:
	 * <ul>
	 *	<li>customerCompany: Name der Firma des Kunden</li>
	 *	<li>customerZip: PLZ des Kunden</li>
	 *	<li>customerCity: Stadt des Kunden</li>
	 *	<li>customerCountry: Land des Kunden</li>
	 *	<li>customerNumber: Kundennummer</li>
	 * </ul>
	 * 
	 * @param Smarty $oSmarty Das zu füllende Smarty-Objekt
	 * @param \Ext_Office_Document $oDocument Das Rechnungs-Objekt
	 */
	private function _assignCustomerData(Smarty $oSmarty, \Ext_Office_Document $oDocument){
		// Id des Customers
		$iCustomerId = (int) $oDocument->customer_id;
		// Instanziiere ein Kunden-Objekt
		$oCustomer = new \Ext_Office_Customer('office_customers', $iCustomerId);

		// Erweitere das Smarty-Objekt
		$oSmarty->assign('customerCompany', $oCustomer->company);
		$oSmarty->assign('customerAddress', $oDocument->address);
		$oSmarty->assign('customerNumber', $oCustomer->number);
		$oSmarty->assign('customerData', $oCustomer->getData());
	}

	/**
	 * Füllt das Smarty-Objekt mit folgende Variablen:
	 * <ul>
	 *	<li>editorFirstname: Vorname des Ansprechpartners</li>
	 *	<li>editorLastname: Nachname des Ansprechpartners</li>
	 *	<li>editorEmail: Email des Ansprechpartners</li>
	 *	<li>editorPhone: Telefon des Ansprechpartners</li>
	 * </ul>
	 * 
	 * @param Smarty $oSmarty Das zu füllende Smarty-Objekt
	 * @param \Ext_Office_Document $oDocument Das Rechnungs-Objekt
	 */
	private function _assignEditorData(Smarty $oSmarty, \Ext_Office_Document $oDocument){
		// Id des Ansprechpartners
		$iEditorId = (int) $oDocument->editor_id;
		// Instanziiere ein User-Objekt
		/* @var $oUser \WDBasic */
		$oUser = \User::getInstance($iEditorId);

		// Erweitere das Smarty-Objekt
		$oSmarty->assign('editorFirstname', $oUser->firstname);
		$oSmarty->assign('editorLastname', $oUser->lastname);
		$oSmarty->assign('editorEmail', $oUser->email);
		$oSmarty->assign('editorPhone', $oUser->phone);
	}

	/**
	 * Füllt das Smarty-Objekt mit folgende Variablen:
	 * <ul>
	*	<li>contactPersonFirstname: Vorname der Kontaktperson</li>
	 *	<li>contactPersonLastname: Nachname der Kontaktperson</li>
	 * </ul>
	 * 
	 * @param Smarty $oSmarty Das zu füllende Smarty-Objekt
	 * @param \Ext_Office_Document $oDocument Das Rechnungs-Objekt
	 */
	private function _assignContactPerson(Smarty $oSmarty, \Ext_Office_Document $oDocument){
		// Id der Kontaktperson
		$iContactPersonId = $oDocument->contact_person_id;
		// Inztanziiere ein neues ExtensionDao_Office-Objekt
		$oOfficeDao = new \classExtensionDao_Office();
		// Hole die Kontaktpersonen entsprechend der Id
		$aContactPerson = $oOfficeDao->getContact($iContactPersonId);

		// Erweitere das Smarty-Objekt
		$oSmarty->assign('contactPersonFirstname', $aContactPerson['firstname']);
		$oSmarty->assign('contactPersonLastname', $aContactPerson['lastname']);
		$oSmarty->assign('contactPersonEmail', $aContactPerson['email']);
	}

	/**
	 * Füllt das Smarty-Objekt mit folgende Variablen:
	 * <ul><li>documentHash: Hash der Rechnung</li></ul>
	 * 
	 * @param Smarty $oSmarty Das zu füllende Smarty-Objekt
	 * @param \Ext_Office_Document $oDocument Das Rechnungs-Objekt
	 */
	private function _assignDocumentHash(Smarty $oSmarty, \Ext_Office_Document $oDocument){
		// Hash der Rechnung
		$sHash = $oDocument->hash;
		// Erweitere das Smarty-Objekt
		$oSmarty->assign('documentHash', $sHash);
	}

	/**
	 * Füllt das Smarty-Objekt mit folgende Variablen:
	 * <ul><li>documentNumber: Rechnungsnummer</li></ul>
	 * 
	 * @param Smarty $oSmarty Das zu füllende Smarty-Objekt
	 * @param \Ext_Office_Document $oDocument Das Rechnungs-Objekt
	 */
	private function _assignDocumentNumber(Smarty $oSmarty, \Ext_Office_Document $oDocument){
		// Die Rechnungsnummer
		$sNumber = $oDocument->number;
		// Erweitere das Smarty-Objekt
		$oSmarty->assign('documentNumber', $sNumber);
	}

	/**
	 * Füllt das Smarty-Objekt mit folgende Variablen:
	 * <ul>
	 *  <li>invoice: Status der Rechnunf
	 *	 <ul><li>paid:	Rechnung bereits gezahlt</li></ul>
	 *	</li>
	 *	<li>documentPrice: Gesamtpreis der Rechnung</li>
	 *	<li>documentAmountPaid: Bereits bezahlter Betrag</li>
	 *	<li>documentRemainingAmount: Ausstehender betrag</li>
	 * </ul>
	 * 
	 * @param Smarty $oSmarty Das zu füllende Smarty-Objekt
	 * @param \Ext_Office_Document $oDocument Das Rechnungs-Objekt
	 */
	private function _assignDocumentPrice(Smarty $oSmarty, \Ext_Office_Document $oDocument) {

		// Der Gesmtpreis der Rechnung
		$fDocumentPrice = $this->getDocumentTotal($oDocument);
		
		$fAmountPaid = $this->getDocumentPayed($oDocument);

		// Noch zu zahlender Betrag
		$fRemainingAmount = $fDocumentPrice - $fAmountPaid;

		// Erweitere das Smarty-Objekt
		$oSmarty->assign('documentPrice', $fDocumentPrice);
		$oSmarty->assign('documentAmountPaid', $fAmountPaid);
		$oSmarty->assign('documentRemainingAmount', $fRemainingAmount);

		// Wenn nichts mehr zum bezahlen da ist
		if(bccomp($fRemainingAmount, 0, 2) <= 0){
			// Smarty-Objekt erweitern
			$oSmarty->assign('invoice', 'paid');
		}
	}
	
	public function getDocumentOutstandingAmount(\Ext_Office_Document $oDocument) {
		
		// Der Gesmtpreis der Rechnung
		$fDocumentPrice = $this->getDocumentTotal($oDocument);
		
		$fAmountPaid = $this->getDocumentPayed($oDocument);

		// Noch zu zahlender Betrag
		$fRemainingAmount = $fDocumentPrice - $fAmountPaid;

		return $fRemainingAmount;
	}
	
	protected function getDocumentTotal($oDocument) {
		
		$fDocumentPrice = (float)$oDocument->price;
		
		return $fDocumentPrice;
	}

	protected function getDocumentPayed($oDocument) {
				
		$oExtensionDaoOffice = new \classExtensionDao_Office();
		
		// Hole alle geleisteten Zahlungen zu einer Rechnung
		$aPayments = $oExtensionDaoOffice->getPayments($oDocument->id);

		// Bereits gezahlter Betrag
		$fAmountPaid = 0.00;
		foreach ($aPayments['payments'] as $aPayment) {
			$fAmountPaid += $aPayment['amount'];
		}
		
		return $fAmountPaid;
	}

	/**
	 * Gibt den Inahlt für den Errorreport zurück.
	 * 
	 * @return string
	 */
	private function _getErrorReportContent(\Exception $oException, \Ext_Office_Document $oDocument) {

		// Die Nachricht der Exception
		$sExceptionMessage = $oException->getMessage();

		// Stelle den Text zusammen
		$sText = "Während einer Kreditkartentransaktion ist folgendes Problem aufgetreten:\n";
		$sText .= "   Fehlerbeschreibung: " . $sExceptionMessage . "\n\n";
		$sText .= "Details zum Verursacher:\n";
		$sText .= "   Rechnungsnummer: " . $oDocument->number . "\n";

		return $sText;
	}

	public function assignError(Smarty $oSmarty, $sError){
		$oSmarty->assign('cardError', $sError);
		$oSmarty->assign('invoice', 'payable');
		$oSmarty->assign('action', 'payment');
	}

}