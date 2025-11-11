<?php


class Ext_Thebing_Customer_CustomerNumber {

	/**
	 *
	 * @var Ext_TS_Inquiry_Contact_Abstract
	 */
	private $_oCustomer;
	/**
	 *
	 * @var Ext_TS_Inquiry
	 */
	private $_oInquiry;
	private $_oSchool;

	public function __construct($oInquiry){
		$this->_oInquiry = &$oInquiry;
		$this->_oCustomer = $this->_oInquiry->getCustomer();
		$this->_oSchool = $this->_oInquiry->getSchool();

	}

	/**
	 * Falls abweichender Contact gesetzt werden muss
	 * @param \Ext_TS_Inquiry_Contact_Abstract $customer
	 */
	public function setCustomer(\Ext_TS_Inquiry_Contact_Abstract $customer) {
		$this->_oCustomer = $customer;
	}
	
	public function setSchool(Ext_Thebing_School $oSchool) {
		$this->_oSchool = $oSchool;
	}

    public function getApplicationByType(){
		
		if($this->_oCustomer instanceof \Ext_TS_Inquiry_Contact_Booker) {
			return 'invoice_contact';
		}
		
        if($this->_oInquiry->hasAgency()){
            return 'customer_agency';
        }
		
        return 'customer';
    }

    /**
     * Prüft ob die Kundennummern je Anwendungsfall unterschiedlich sind
     * @return boolean 
     */
    public function checkForDifferentApplicationRanges(){
        $oNumberrangeA = Ext_TS_Numberrange_Contact::getByApplicationAndObject('customer', $this->_oSchool->id);
        $oNumberrangeB = Ext_TS_Numberrange_Contact::getByApplicationAndObject('customer_agency', $this->_oSchool->id);
        if($oNumberrangeA->id == $oNumberrangeB->id){
            return false;
        }
        return true;
    }
    
    /**
     * 
     * @param type $bForce
     * @return null|\Ext_TC_NumberRange
     */
    public function generateNumberRangeObject($bForce = false){

		if(
			$bForce ||
			$this->_oCustomer->getCustomerNumber() === null
		) {
            
            // Inbox setzten da die Nummer abhängig hiervon ist
			$oInbox = $this->_oInquiry->getInbox();
			if($oInbox) {
				Ext_TS_NumberRange::setInbox($oInbox);
			}

			// direkt oder agentur
            $sApplication = $this->getApplicationByType();

            // objekt suchen für die aktuelle schule und den anwendungsfall
            $oNumberrange = Ext_TS_Numberrange_Contact::getByApplicationAndObject($sApplication, $this->_oSchool->id);

			// TODO: Saver usw. müssen umgeschrieben werden, damit hier keine doppelten Nummern mehr möglich sind
			// Da hier aktuell keine Row-Locks die Transaktionen blockieren zu scheinen, funktioniert das soweit
			$oNumberrange->bAllowDuplicateNumbers = true;

			// Nummernkreis sperren
			//if(!$oNumberrange->acquireLock()) {
			//	// Es wird aktuell eine andere Nummer innerhalb des Nummernkreises generiert
			//	return null;
			//}

            return $oNumberrange;
            
        }

        return null;
    }

	/**
	 * Sucht und speichert eine Kundennummer, falls noch keine vorhanden ist, oder es explizit angegeben ist
	 * @param bool $bForce
	 * @param bool $bSave
	 * @return array
	 */
	public function saveCustomerNumber($bForce = false, $bSave = true){

		if (
			$this->_oInquiry->type == Ext_TS_Inquiry::TYPE_ENQUIRY &&
			!System::d('customernumber_enquiry')
		) {
			return [];
		}

		if ($this->_oCustomer->getCustomerNumber() === null || $bForce) {
			
			$oNumberrange = $this->generateNumberRangeObject($bForce);

			if(!$oNumberrange || !$oNumberrange->acquireLock()) {
				return array(Ext_TC_NumberRange::getNumberLockedError());
			}

			$sNumber = $oNumberrange->generateNumber();
			$this->_oCustomer->saveCustomerNumber($sNumber, $oNumberrange->id, $bForce, $bSave);

			// Nur entsperren, wenn dieser Request den Nummernkreis auch gesperrt hat
			if($oNumberrange) {
				$oNumberrange->removeLock();
			}
		}
        
		return array();
	}

}