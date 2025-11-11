<?php

/**
 * @property $id
 * @property $created 	
 * @property $changed 	
 * @property $user_id 	
 * @property $idClient 	
 * @property $idSchool 	
 * @property $companie_id 	
 * @property $active 	
 * @property $creator_id 	
 * @property $name 	
 * @property $street 	
 * @property $city 	
 * @property $plz 	
 * @property $country 	
 * @property $tel 	
 * @property $emergency_number 	
 * @property $handy 	
 * @property $email
 */

class Ext_Thebing_Pickup_Company_Driver extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'kolumbus_drivers';

	// Tabellenalias
	protected $_sTableAlias = 'kdr';
	
	public function save($bLog=true){

		$oSave = parent::save($bLog);
		WDCache::delete('transfer_provider');
		return $oSave;

	}

}
