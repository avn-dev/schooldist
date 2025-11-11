<?php

class Ext_TS_AddressLabel extends Ext_TC_Address_Label {
	
	public static $aAddressLabels = array();
		
	public static function getFirstLabelIdByType($sType)
	{
		if(
			isset(self::$aAddressLabels[$sType])
		){
			return self::$aAddressLabels[$sType];
		}
		
		$aCacheAddressLabels = (array)WDCache::get('address_labels');
		
		if(
			isset($aCacheAddressLabels[$sType])
		) {
			return (int)$aCacheAddressLabels[$sType];
		}
		
		$oSelf = new self;
		
		$sSql = "
			SELECT
				`id`
			FROM
				#table
			WHERE
				`type` = :type
			LIMIT
				1
		";
		
		$aSql = array(
			'table' => $oSelf->_sTable,
			'type'	=> $sType
		);

		$iId = (int)DB::getQueryOne($sSql, $aSql);
		
		// Label müssen da sein!
		if($iId === null) {
			throw new Exception('No label of type "'.$sType.'" found!');
		}

		self::$aAddressLabels[$sType] = $iId;
		
		$aCacheAddressLabels[$sType] = $iId;
		
		WDCache::set('address_labels', 86400, $aCacheAddressLabels);
		
		return $iId;

	}
	
	public static function getContactAdressLabelId()
	{
		$iId = self::getFirstLabelIdByType('contact_address');
		
		return $iId;
	}
	
	public static function getBillingAdressLabelId()
	{
		$iId = self::getFirstLabelIdByType('billing_address');
		
		return $iId;
	}
}
