<?php

namespace Office\Entity\Customer;

class Comment extends \WDBasic {
	
	protected $_sTable = 'office_customers_comments';
	protected $_sTableAlias = 'occ';

	protected $_aFormat = array(
		'text' => array('required' => true),
		'customer_group_id' => array('required' => true),
	);

	/**
	 * <p>
	 * Gibt alle Kundengruppen zurück, zu dem dieser Kommentar gehören
	 * könnte. Welchen Kundengruppen ein Kommentar angehören könnte, entscheidet
	 * sich dadurch, zu welchen Kundengruppen der Kunde des Kommentars gehört.
	 * </p>
	 * @param int $iCustomerId <p>
	 * Die Kunden-Id, die zum Kunden des Kommentars gehört.
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
	 * Diese Methode erstellt das Array aus Kundengruppen. Das Array ist so
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
	 * Löscht ein Kommentar. Nach dem Löschen werden die Positionen der anderen
	 * Kommentare wieder aufgerückt.
	 * </p>
	 */
	public function delete(){
		// Speichern
		parent::delete();
	}

	
}