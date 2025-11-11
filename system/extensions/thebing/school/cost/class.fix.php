<?php
class Ext_Thebing_School_Cost_Fix extends Ext_Thebing_School_Cost {
	
	
	public function getCost($iFixCostId){
			
		$aResult = $this->checkForCostData($iFixCostId);
		
		$sPriceField = 'amount';
		
		if($aResult === false){
			return 0;
		}
		
		return (float)$aResult[$sPriceField];
		
	}
	
	public function checkForCostData($iFixCostId){
		
		$sSql = " SELECT * FROM 
						`kolumbus_costprice_fixcost` 
					WHERE 
						`fixcost_id` = :fixcost_id AND 
						`school_id` = :school_id AND 
						`saison_id` = :saison_id AND 
						`currency_id` = :currency_id AND 
						`active` = 1";
		$aSql = array();
		$aSql['fixcost_id'] = $iFixCostId;
		$aSql['school_id'] = $this->getId();// SchulID
		$aSql['saison_id'] = $this->_iSaisonId;
		$aSql['currency_id'] = $this->_iCurrencyId;
		
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		if(count($aResult) <= 0){
			return false;
		}
		return $aResult[0];
	}
	
	public function saveCost($fValue,$iFixCostId){
		
		$aData = $this->checkForCostData($iProvider,$iAirport,$sTransferType,$sDayType);
		
		$aSql = array();
		$sSqlEnd = "";
		
		if($aData !== false){
			$sSql = " UPDATE `kolumbus_costprice_fixcost` ";
			$sSqlEnd = " WHERE id = :id LIMIT 1 ";
			$aSql['id'] = $aData['id'];
		} else {
			$sSql = " INSERT INTO `kolumbus_costprice_fixcost` ";
		}
		
		$sSqlSet = " SET
						`created` = NOW(),
						`fixcost_id` = :fixcost_id ,
						`school_id` = :school_id ,
						`saison_id` = :saison_id ,
						`currency_id` = :currency_id,
						`amount` = :amount,
						`active` = 1 ";
		$sSql = $sSql.$sSqlSet.$sSqlEnd;
		
		$aSql['fixcost_id'] = $iFixCostId;
		$aSql['school_id'] = $this->getId();// SchulID
		$aSql['saison_id'] = $this->_iSaisonId;
		$aSql['currency_id'] = $this->_iCurrencyId;
		$aSql['amount'] = $fValue;

		DB::executePreparedQuery($sSql,$aSql);
	}	
	
}