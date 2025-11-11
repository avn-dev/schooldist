<?php

class Ext_TS_Gui2_Format_Enquiry_Service_Amount extends Ext_Thebing_Gui2_Format_Amount {

	/**
	 *
	 * Zu berechnende Klasse (Angebot/Kombination)
	 * 
	 * @var string
	 */
	protected $_sServiceObject;
	
	/**
	 *
	 * Methode zum injekten in die Anfrage
	 * 
	 * @var string 
	 */
	protected $_sSetterMethod;
	
	/**
	 *
	 * Überprüfen ob ein Dokument erstellt worden ist (Nur bei Angeboten)
	 * 
	 * @var bool 
	 */
	protected $bCheckIfAmountExists;

	/**
	 *
	 * @param string $sServiceObject
	 * @param string $sSetterMethod
	 * @param bool $bCheckIfAmountExists 
	 */
	public function __construct($sServiceObject, $sSetterMethod, $bCheckIfAmountExists=false) {
		parent::__construct();
		
		$this->_sServiceObject = $sServiceObject;
		
		$this->_sSetterMethod = $sSetterMethod;
		
		$this->bCheckIfAmountExists = $bCheckIfAmountExists;
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {

		$fPrice = $mValue;
		
		$oEnquiry		= Ext_TS_Enquiry::getInstance($aResultData['enquiry_id']);
		$aResultData['currency_id'] = $oEnquiry->getCurrency();
		$aResultData['school_id'] = $oEnquiry->getSchoolId();
		
		if($this->bCheckIfAmountExists) {

			//In Tab Angebote überprüfen ob Dokument schon erstellt wurde, dann den Preis aus dem PDF übernehmen
			if(
				array_key_exists('document_version_id', $aResultData) &&
				$aResultData['document_version_id'] > 0
			) {
				$oInquiryDocumentVersion	= Ext_Thebing_Inquiry_Document_Version::getInstance($aResultData['document_version_id']);
				$fPrice						= (float)$oInquiryDocumentVersion->getAmount();
			}
		}

		if($fPrice === null) {

			//$fPrice ist null im Tab Suche, da da die Spalte gar nicht existiert in der Datenbank
			$sSetterMethod = $this->_sSetterMethod;

			//Diese Formatklasse, wird im Suchtab& in Angebottab übergeben, darum wird hier das Objekt geswitcht
			$oService		= call_user_func(array($this->_sServiceObject, 'getInstance'), (int)$aResultData['id']);
			
			//ServiceObjekt in die Anfrage reinpacken, da die calculateAmount nicht ohne Anfrage/Buchung funktioniert
			$oEnquiry->$sSetterMethod($oService);
			
			$fServiceAmount = $oEnquiry->calculateAmount();

			$fPrice += $fServiceAmount;
		}
		
		$fPrice = parent::format($fPrice, $oColumn, $aResultData);

		return $fPrice;
	}
}