<?php

class Ext_Thebing_Inquiry_Transfer_Placeholder extends Ext_Thebing_Placeholder{

	private static $aInstance = null;
	protected $_sApplication = null;
	protected $_oObject = null;
	protected $_oTemplate = null;
	// ProviderInformationen mitschicken in der Platzhaltertabelle
	protected $_bShowProviderInformation = false;
	// Markierte Transfere
	protected $_aTransfers = array();


	public function __construct($sApplication = null, $oObject = null, $aSelectedIds = null){

		if(is_null($sApplication)){
			return;
		}

		$this->_sApplication = $sApplication;
		$this->_oObject = $oObject;
		$this->_oTemplate = $oTemplate;


		// Aktuelle Object ID
		$iObjectId		= $oObject->id;

		// Aktueller Objecttyp
		if($oObject instanceof Ext_TS_Inquiry_Contact_Abstract) {
			$sObjectType = 'customer';
		} elseif($oObject instanceof Ext_TS_Inquiry) {
			$sObjectType = 'inquiry';
		} elseif($oObject instanceof Ext_Thebing_Agency) {
			$sObjectType = 'agency';
		} elseif($oObject instanceof Ext_Thebing_Agency){
			$sObjectType = 'agency';
		} elseif($oObject instanceof Ext_Thebing_Accommodation) {
			$sObjectType = 'accommodation';
		} elseif($oObject instanceof Ext_Thebing_Pickup_Company) {
			$sObjectType = 'provider';
		}

		$this->_aTransfers = array();
		foreach((array)$aSelectedIds as $iTransferId) {
			$this->_aTransfers[] = Ext_TS_Inquiry_Journey_Transfer::getInstance($iTransferId);
		}


		$sPlaceholder = '';

		switch($sApplication){
			case 'transfer_customer_agency_information':
					// Nur wenn Transfer dem gewählten Kunden bzw. Agentur zugeordnet werden können
					foreach((array)$this->_aTransfers as $iKey => $oTransfer){
						$oInquiry = $oTransfer->getInquiry();
						if($oInquiry->agency_id > 0){
							// Agentur
							$sType = 'agency';
							$iType_id = $oInquiry->agency_id;
						}else{
							// Direktkunde
							$sType = 'inquiry';
							$iType_id = $oInquiry->id;
						}

						if(
							$sType != $sObjectType ||
							$iType_id != $iObjectId
						){
							unset($this->_aTransfers[$iKey]);
						}
					}

					break;
			case 'transfer_customer_accommodation_information':
					// Nur wenn Zielpunkt des Transfers die aktuelle Familie ist an die geschickt wird
					foreach((array)$this->_aTransfers as $iKey => $oTransfer){
						if(
							$oTransfer->end_type != $sObjectType ||
							$oTransfer->end != $iObjectId
						){
							unset($this->_aTransfers[$iKey]);
						}
					}
					break;
			case 'transfer_provider_confirm':
					// Nur die Transfers bestätigen die auch aktuellen Provider sind
					foreach((array)$this->_aTransfers as $iKey => $oTransfer){
						if(
							$oTransfer->provider_id != $iObjectId ||
							$oTransfer->provider_type != $sObjectType
						){
							unset($this->_aTransfers[$iKey]);
						}
					}
					$this->_bShowProviderInformation = true;
					break;
			case 'transfer_provider_request':
					// Da jeder Transferanbieter hier JEDEN Transfer übernehmen kann bekommt
					// jeder auch alles zugeschickt :P
					break;
			default:
					break;
		}
	}

	public function replace($sString) {
		if($this->_oObject instanceof Ext_TS_Inquiry_Journey_Transfer){
			$oPlaceolder = new Ext_Thebing_Inquiry_Placeholder($this->_oObject->inquiry_id);
			$sString = $oPlaceolder->replace($sString);
		} else if($this->_oObject instanceof Ext_TS_Inquiry_Contact_Abstract){
			// Holt die letzte Buchung (nicht 100% korrekt aber meißt)
			$aInquiries = $this->_oObject->getInquiries(true);
			if(count($aInquiries) > 0){
				$oInquiry = reset($aInquiries);
				$oPlaceolder = new Ext_Thebing_Inquiry_Placeholder($oInquiry->id);
				$sString = $oPlaceolder->replace($sString);
			}
		} else if(!empty($this->_aTransfers)){
			// Inquiry Platzhalter verfügbar machen
			$oTransfer = reset($this->_aTransfers);
			$oPlaceolder = new Ext_Thebing_Inquiry_Placeholder($oTransfer->inquiry_id);
			$sString = $oPlaceolder->replace($sString);
		}

		return $sString;
	}

	/**
	 * Get the list of available placeholders
	 *
	 * @return array
	 */
	public function getPlaceholders($sType = ''){
		$aPlaceholders = array(
			array(
				'section'		=> L10N::t('Transfer', 'Thebing » Pickup » Confirmation'),
				'placeholders'	=> array(
					'transfer_provider_title'	=> L10N::t('Transferkommunikation: Provider Titel', 'Thebing » Pickup » Confirmation'),
					'transfer_provider_firstname'	=> L10N::t('Transferkommunikation: Provider Vorname', 'Thebing » Pickup » Confirmation'),
					'transfer_provider_lastname'	=> L10N::t('Transferkommunikation: Provider Nachname', 'Thebing » Pickup » Confirmation')
				)
			)
		);

		return $aPlaceholders;
	}

	protected function _getReplaceValue($sPlaceholder, array $aPlaceholder) {

		$sValue = '';

		switch($sPlaceholder) {
			case 'transfer_provider_title':
				// Anrede für Familien bzw. Provider
				$oFormat = new Ext_Thebing_Gui2_Format_PersonTitle();
				$aTemp = array();
				if($this->_oObject instanceof Ext_Thebing_Accommodation) {
					$sValue = $oFormat->format($this->_oObject->ext_105, $aTemp, $aTemp);
				} elseif($this->_oObject instanceof Ext_Thebing_Pickup_Company) {
					$sValue = $oFormat->format($this->_oObject->title, $aTemp, $aTemp);
				}
				break;
			case 'transfer_provider_firstname':
				if($this->_oObject instanceof Ext_Thebing_Accommodation) {
					$sValue = $this->_oObject->ext_103;
				} elseif($this->_oObject instanceof Ext_Thebing_Pickup_Company) {
					$sValue = $this->_oObject->firstname;
				}
				break;
			case 'transfer_provider_lastname':
				if($this->_oObject instanceof Ext_Thebing_Accommodation) {
					$sValue =$this->_oObject->ext_104;
				} elseif($this->_oObject instanceof Ext_Thebing_Pickup_Company) {
					$sValue = $this->_oObject->lastname;
				}
				break;
			default:
				$sValue = parent::_getReplaceValue($sPlaceholder, $aPlaceholder);
				break;
		}

		return $sValue;
	}

}