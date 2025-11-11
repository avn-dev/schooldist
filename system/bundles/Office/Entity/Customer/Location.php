<?php

namespace Office\Entity\Customer;

class Location extends \WDBasic implements \Office\Interfaces\LogoInterface {

	protected $_sTable = 'office_customers_locations';
	protected $_sTableAlias = 'ocl';

	protected $_aFormat = array(
		'name' => array('required' => true),
		'city' => array('required' => true),
		'country' => array('required' => true),
		'customer_group_id' => array('required' => true),
	);

	/* {@inheritdoc} */
	public function getLogoWebDir(){
		return 'storage/public/office/customers/logos/locations/';
	}

	/**
	 * <p>
	 * Gibt alle Kundengruppen zurück, zu dem dieser Standort gehören
	 * könnte. Welchen Kundengruppen ein Standort angehören könnte, entscheidet
	 * sich dadurch, zu welchen Kundengruppen der Kunde des Standortes gehört.
	 * </p>
	 * @param int $iCustomerId <p>
	 * Die Kunden-Id, die zum Kunden des Standortes gehört.
	 * </p>
	 * @return array <p>
	 * Alle Kundengruppen im Array.
	 * </p>
	 */
	public function getCustomerGroups($iCustomerId){
		// Instanziiert ein Kundenobjekt, um die Kundengruppen (IDs) zu bekommen
		$oCustomer = new \Ext_Office_Customer('office_customers', $iCustomerId);
		// Die Ids der Kundengruppe des Kunden
		$aCustomerGroupIds = $oCustomer->getGroupIds();
		// Alle Kundengruppen holen
		$aCustomerGroups = $this->_buildCustomerGroupArray($aCustomerGroupIds);

		return $aCustomerGroups;
	}

	/**
	 * <p>
	 * Diese Methode erstellt das Array der Kundengruppen. Das Array ist so
	 * formatiert, wie es im späteren Verlauf gebraucht wird (Um ein Select
	 * auszugeben).
	 * </p>
	 * @return array <p>
	 * Alle Kundengruppen.
	 * </p>
	 */
	private function _buildCustomerGroupArray(array $aCustomerGroupIds){
		// Das Rückgabearray
		$aCustomerGroups = array();
		// Instanziiere ein Repository vom Ext_Office_Customer_Group
		$oCustomerGroupRepository = \Ext_Office_Customer_Group::getRepository();
		// Die Foreach-Schleife baut ein Array aus den Ids und den Namen der Kundengruppen zusammen
		foreach($aCustomerGroupIds as $iCustomerGroupId){
			// Finde das Kundengruppenobjekt um an den Namen zu gelangen
			$oCustomerGroup = $oCustomerGroupRepository->find($iCustomerGroupId);
			// Erweitere das Rückgabearray wie erwartet
			$aCustomerGroups[] = array($iCustomerGroupId, $oCustomerGroup->name);
		}

		return $aCustomerGroups;
	}

	/**
	 * <p>
	 * Vor dem Speichern wird geprüft, ob sich etwas an der Adresse geändert hat.
	 * Wenn sich etwas geändert hat, dann wird vor dem Speichern die Latitude - 
	 * und Longitude auf <b>null</b> gesetzt.
	 * </p>
	 */
	public function save(){
		// Adresse hat sich geändert
		$bAddressChanged = $this->_addressChanged();
		// Wenn sich etwas an der Adresse geändert hat
		if ($bAddressChanged) {
			//Dann die Latitude und Longitude zurücksetzen
			$this->latitude = '';
			$this->longitude = '';
		}

		// Speichernexi
		parent::save();
	}

	/**
	 * Prüft, ob sich die Adresse geändert hat.
	 * 
	 * @return boolean <p>
	 * Wenn sich die Adresse geändert hat, gib <b>TRUE</b> zurück, sonst
	 * <b>FALSE</b>
	 * </p>
	 */
	private function _addressChanged(){
		// Ein altes Locations erstellen (alt, weil dies hier der Speichervorgang ist)
		$oOldLocation = $this->getRepository()->find($this->id);
		// Wenn ein neuer Standort angelegt wird
		if($oOldLocation === null){
			return true;
		}
		$sOldAddress = $this->_getAddress($this->_aOriginalData);
		$sNewAddress = $this->_getAddress($this->_aData);

		// Wenn sich die Adresse geändert hat, dann gib TRUE zurück, sonst FALSE
		$bChangeAddress = strcmp($sOldAddress, $sNewAddress) !== 0;
		if($bChangeAddress) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Gibt die Adresse als String zurück.
	 * 
	 * @param \Office\Entity\Customer\Location $oLocation <p>
	 * Das Standort-Objekt, dessen Adresse zurückgegeben werden soll.
	 * </p>
	 * @return string Die Adresse.
	 */
	private function _getAddress($aLocationData) {

		// Adresse zusammenstellen
		$sAddress = '';
		$sAddress .= $aLocationData['zip'];
		$sAddress .= $aLocationData['city'];
		$sAddress .= $aLocationData['country'];

		return $sAddress;
	}
}