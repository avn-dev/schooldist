<?php

class Ext_TC_Exchangerate_Allocation {

	public static function getDates($sDocumentType) 
	{
		
		$aDates = array();		
		
		switch($sDocumentType) {
			
			case 'prepay':
			case 'proforma':
			case 'invoice':
				$aDates['inquiry_date'] = L10N::t('Buchungsdatum');
				$aDates['document_date'] = L10N::t('Dokumentendatum');
				$aDates['offer_date']	= L10N::t('Angebotsdatum');
				#$aDates['offer_date_valid']	= L10N::t('Angebotsdatum (Gültig)');
		        break;
			case 'offer':
				$aDates['capture'] = L10N::t('Erfassung');
		        $aDates['creation']	= L10N::t('Erstellung');
				break;
			case 'final_payment':   
				$aDates['inquiry_date'] = L10N::t('Buchungsdatum');
		        $aDates['deposit_date']	= L10N::t('Datum der Anzahlungsrechnung');
				$aDates['document_date'] = L10N::t('Dokumentendatum');
				$aDates['offer_date']	= L10N::t('Angebotsdatum');
				#$aDates['offer_date_valid']	= L10N::t('Angebotsdatum (Gültig)');
				break;
			
		} 
		 
		return $aDates;		
	}
	
	public static function getApplications(){
		
		$aApplications = array(
			'countrygroups' => array(
				'offer_backend' => L10N::t('Angebot Backend'),
				'provision' => L10N::t('Provision'),
				'deposit_invoice' => L10N::t('Anzahlungsrechnung'),
				'residual_invoice' => L10N::t('Restzahlungsrechnung'),
				'invoice' => L10N::t('Rechnung')
			),
			'frontend' => array(
				'offer_frontend' => L10N::t('Angebot Frontend'),
				'website' => L10N::t('Webseite')
			)
		);

		return $aApplications;
	}

}