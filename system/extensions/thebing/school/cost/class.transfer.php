<?php
class Ext_Thebing_School_Cost_Transfer extends Ext_Thebing_School_Cost {
	
	
	public function getCost($iProvider,$iAirport,$sTransferType='arrival',$sDayType = 'weekday'){
				
		$aResult = $this->checkForCostData($iProvider,$iAirport,$sTransferType,$sDayType);
		
		$sPriceField = "amount";
		
		if($aResult === false){
			return 0;
		}

		return (float)$aResult[$sPriceField];
		
	}
	
	public function checkForCostData($iProvider,$iAirport,$sTransferType='arrival',$sDayType = 'weekday'){
		
		$sSql = " SELECT * FROM 
						`kolumbus_costprice_transfer` 
					WHERE 
						`provider_id` = :provider_id AND 
						`airport_id` = :airport_id AND 
						`school_id` = :school_id AND 
						`saison_id` = :saison_id AND 
						`currency_id` = :currency_id AND 
						`day_type` = :day_type AND 
						`transfer_type` = :transfer_type AND 
						`active` = 1";
		$aSql = array();
		$aSql['provider_id'] = $iProvider;
		$aSql['airport_id'] = $iAirport;
		$aSql['school_id'] = $this->getId();// SchulID
		$aSql['saison_id'] = $this->_iSaisonId;
		$aSql['currency_id'] = $this->_iCurrencyId;
		$aSql['day_type'] = $sDayType;
		$aSql['transfer_type'] = $sTransferType;
		
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		if(count($aResult) <= 0){
			return false;
		}
		return $aResult[0];
	}
	
	public function saveCost($fValue,$iProvider,$iAirport,$sTransferType='arrival',$sDayType = 'weekday'){
		
		$aData = $this->checkForCostData($iProvider,$iAirport,$sTransferType,$sDayType);
		
		$aSql = array();
		$sSqlEnd = "";
		
		if($aData !== false){
			$sSql = " UPDATE `kolumbus_costprice_transfer` ";
			$sSqlEnd = " WHERE id = :id LIMIT 1 ";
			$aSql['id'] = $aData['id'];
		} else {
			$sSql = " INSERT INTO `kolumbus_costprice_transfer` ";
		}
		
		$sSqlSet = " SET
						`created` = NOW(),
						`provider_id` = :provider_id ,
						`airport_id` = :airport_id ,
						`school_id` = :school_id ,
						`saison_id` = :saison_id ,
						`currency_id` = :currency_id,
						`amount` = :amount,
						`day_type` =:day_type,
						`transfer_type` = :transfer_type,
						`active` = 1";
		$sSql = $sSql.$sSqlSet.$sSqlEnd;
		
		$aSql['provider_id'] = $iProvider;
		$aSql['airport_id'] = $iAirport;
		$aSql['school_id'] = $this->getId();// SchulID
		$aSql['saison_id'] = $this->_iSaisonId;
		$aSql['currency_id'] = $this->_iCurrencyId;
		$aSql['amount'] = $fValue;
		$aSql['day_type'] = $sDayType;
		$aSql['transfer_type'] = $sTransferType;

		DB::executePreparedQuery($sSql,$aSql);
		
	}	
	
}