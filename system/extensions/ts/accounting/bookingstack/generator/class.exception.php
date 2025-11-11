<?php
class Ext_TS_Accounting_Bookingstack_Generator_Exception extends Exception {
    
    protected $_sKey;
    protected $_mOptional;
    
    /**
     * @var Ext_TS_Accounting_Bookingstack_Generator 
     */
    protected $_oGenerator;

	protected $_aWarningKeys = ['no_receipt_text_found'];

    public function __construct ($sKey, Ext_TS_Accounting_Bookingstack_Generator $oGenerator, $mOptional = null) {
        $this->_sKey        = $sKey;
        $this->_mOptional   = $mOptional;
        $this->_oGenerator  = $oGenerator;
        $sMessage           = $this->getMessageForKey();
        parent::__construct($sMessage, 500);
    }

	public function isWarning(): bool {
		return in_array($this->_sKey, $this->_aWarningKeys);
	}

    public function getMessageForKey(){

    	if($this->_oGenerator instanceof Ext_TS_Accounting_Bookingstack_Generator_Payment) {
    		$sMessage = $this->getPaymentErrorMessage($this->_sKey);
		} else {
			$sMessage = $this->getDocumentErrorMessage($this->_sKey);
		}

    	return $sMessage;
    }

    protected function getDocumentErrorMessage($sKey) {

		$sDocumentNumber = $this->_oGenerator->getEntityName();

		switch ($sKey) {
			case 'no_version_found':
				$sMessage = $this->t('Für das Dokumentes ({document}) wurde keine Version gefunden!');
				break;
			case 'no_version_item_found':
				$sMessage = $this->t('Für das Dokumentes ({document}) wurde keine Rechnungspositionen gefunden!');
				break;
			case 'no_company_found':
				$sMessage = $this->t('Für eine Position des Dokumentes ({document}) konnte keine passende Firma gefunden werden!');
				break;
			case 'no_school_found':
				$sMessage = $this->t('Für eine Position des Dokumentes ({document}) konnte keine passende Schule gefunden werden!');
				break;
			case 'no_receipt_text_found':
				$sMessage = $this->t('Für eine Position des Dokumentes ({document}) konnte kein passender Belegtext gefunden werden!');
				break;
			case 'no_income_account_found':
				$sMessage = $this->t('Für eine Position des Dokumentes ({document}) konnte kein passendes Einnahme Konto gefunden werden!');
				break;
			case 'no_expense_account_found':
				$sMessage = $this->t('Für eine Position des Dokumentes ({document}) konnte kein passendes Ausgabe Konto gefunden werden!');
				break;
			case 'no_tax_account_found':
				$sMessage = $this->t('Für eine Position des Dokumentes ({document}) konnte kein passendes Steuer Konto gefunden werden!');
				break;
			case 'unknown_position_booking_case':
				$sMessage = $this->t('Es ist ein unbekannter Buchungsfall für eine Position des Dokument ({document}) aufgetreten!');
				break;
			case 'unknown_claim_booking_case':
				$sMessage = $this->t('Es ist ein unbekannter Buchungsfall für die Forderung des Dokument ({document}) aufgetreten!');
				break;
			case 'unknown_passiv_account':
				$sMessage = $this->t('Für das Dokument ({document}) wurde kein passendes Passives Rechnungsabgrenzungskonto gefunden!');
				break;
			case 'unknown_active_account':
				$sMessage = $this->t('Für das Dokument ({document}) wurde kein passendes Aktives Rechnungsabgrenzungskonto gefunden!');
				break;
			case 'save_error':
				$sMessage = $this->t('Fehler beim Speichern der Buchungssätze des Dokumentes ({document})!');
				break;
			case 'numberrange_locked':
				$sMessage = $this->t('Der Nummernkreis ist zur Zeit gesperrt');
				break;
			default:
				$sMessage = $this->t('Fehler beim Generieren der Buchungssätze des Dokumentes ({document})!');
				break;
		}

		$sMessage = str_replace('{document}', $sDocumentNumber, $sMessage);

		return $sMessage;
	}

	protected function getPaymentErrorMessage($sKey) {

		$sPaymentName = $this->_oGenerator->getEntityName();

		switch($sKey) {
			case 'no_company_found':
				$sMessage = $this->t('Für die Zahlung ({payment}) konnte keine passende Firma gefunden werden!');
				break;
			case 'unknown_booking_case':
				$sMessage = $this->t('Es ist ein unbekannter Buchungsfall für die Zahlung ({payment}) aufgetreten!');
				break;
			case 'no_paymentmethod_account':
				$sMessage = $this->t('Für die Bezahlmethode der Zahlung ({payment}) wurde keine Kontenzuweisung gefunden!');
				break;
			case 'save_error':
				$sMessage = $this->t('Fehler beim Speichern der Buchungssätze der Zahlung ({payment})!');
				break;
			case 'numberrange_locked':
				$sMessage = $this->t('Der Nummernkreis ist zur Zeit gesperrt');
				break;
			default:
				$sMessage = $this->t('Fehler beim Generieren der Buchungssätze der Zahlung ({payment})!');
				break;
		}

		$sMessage = str_replace('{payment}', $sPaymentName, $sMessage);

		return $sMessage;
	}

    public function getKey(){
        return $this->_sKey;
    }
    
    public function getOptionalData(){
        return $this->_mOptional;
    }
    
    public function t($sMessage){
        return L10N::t($sMessage);
    }
}
