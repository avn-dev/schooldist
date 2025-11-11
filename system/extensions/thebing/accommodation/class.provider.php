<?php

class Ext_Thebing_Accommodation_Provider {
	
	private $_iProviderId = 0;
	private $_aProvider = array();
	private $_aRoomlist = array();	
	
	public function __construct($iId,$iFrom = 0,$iTo = 0){
		$this->_iProviderId = $iId;
		$aProvider = Ext_Thebing_Accommodation_Util::getAccommodationProvidersFromId($iId);
		$this->_aProvider = $aProvider[0];
		$this->iFrom = $iFrom;
		$this->iTo = $iTo;
		$this->setRoomListOfProvider();	
	}
	
	public function __get($sField){
		return $this->_aProvider[$sField];
	}
	
	public function getAddressForCheck(){
		
		$sAddress = $this->_aProvider['ext_68']."\n";
		$sAddress .= $this->_aProvider['ext_64'].", ".$this->_aProvider['ext_65'].", ".$this->_aProvider['ext_66'];
		return $sAddress;
	}
	
	private function setRoomListOfProvider(){

		$sSql = "SELECT 
					*,'0' as `allocation`
				FROM
					`kolumbus_rooms` as `room`
				WHERE
					`room`.`accommodation_id` = :idAccommodation AND
					`room`.`active` = 1
			";
		$aSql = array();
		
		$aSql['idAccommodation'] = $this->_iProviderId;
		$aBack = DB::getPreparedQueryData($sSql,$aSql);
		
		$this->_aRoomlist =  $aBack ;
		$this->setRoomsWithAllocations();
	}
	
	private function setRoomsWithAllocations(){
	
		foreach($this->_aRoomlist as &$aRoom){
			$aAllocation = Ext_Thebing_Matching::getRoomAllocation($aRoom['id'],$this->iFrom,$this->iTo);
			$aRoom['provider'] = $this->_aProvider['ext_33'];
			$aRoom['allocation'] = $aAllocation;
		}
	}
	
	public function getRoomList(){
		return $this->_aRoomlist;
	}
	
	
}