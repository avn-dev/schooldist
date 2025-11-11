<?php

class Ext_Thebing_School_Cost_Teacher extends Ext_Thebing_School_Cost {

	/**
	 * @param int $iCostKategorieId
	 * @param int $iCourseId
	 * @param int $iHoliday
	 * @return float|int
	 */
	public function getCost($iCostKategorieId, $iCourseId, $iHoliday = 0){

		$aResult = $this->checkForCostData($iCostKategorieId, $iCourseId, $iHoliday);
		
		$sPriceField = "amount";
		if($iHoliday == 1) {
			$sPriceField = 'amount_holiday';
		}

		if($aResult === false){
			return 0;
		}

		return (float)$aResult[$sPriceField];
		
	}

	/**
	 * @param int $iCostKategorieId
	 * @param int $iCourseId
	 * @param int $iHoliday
	 * @return bool
	 */
	public function checkForCostData($iCostKategorieId, $iCourseId, $iHoliday = 0) {
		
		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_costprice_teacher`
			WHERE
				`costkategorie_id` = :costkategorie_id AND
				`course_id` = :course_id AND
				`school_id` = :school_id AND
				`saison_id` = :saison_id AND
				`currency_id` = :currency_id
		";
		$aResult = DB::getPreparedQueryData($sSql, array(
			'costkategorie_id' => $iCostKategorieId,
			'course_id' => $iCourseId,
			'school_id' => $this->getId(),
			'saison_id' => $this->_iSaisonId,
			'currency_id' => $this->_iCurrencyId
		));

		if(count($aResult) <= 0) {
			return false;
		}

		return $aResult[0];
	}

	/**
	 * @param float $fValue
	 * @param int $iCostKategorieId
	 * @param int $iCourseId
	 * @param int $iHoliday
	 */
	public function saveCost($fValue, $iCostKategorieId, $iCourseId, $iHoliday = 0) {
		
		$aData = $this->checkForCostData($iCostKategorieId, $iCourseId, $iHoliday);
		
		$aSql = array();
		$sSqlEnd = '';
		
		if($aData !== false) {
			$sSql = " UPDATE `kolumbus_costprice_teacher` ";
			$sSqlEnd = " WHERE id = :id LIMIT 1 ";
			$aSql['id'] = $aData['id'];
		} else {
			$sSql = " INSERT INTO `kolumbus_costprice_teacher` ";
		}
		
		$sSqlSet = "
			SET
				`created` = NOW(),
				`costkategorie_id` = :costkategorie_id ,
				`course_id` = :course_id ,
				`school_id` = :school_id ,
				`saison_id` = :saison_id ,
				`currency_id` = :currency_id,
				#amount_field = :amount
		";

		$sSql = $sSql.$sSqlSet.$sSqlEnd;
		
		$aSql['costkategorie_id'] = (int)$iCostKategorieId;
		$aSql['course_id'] = (int)$iCourseId;
		$aSql['school_id'] = (int)$this->getId();
		$aSql['saison_id'] = (int)$this->_iSaisonId;
		$aSql['currency_id'] = (int)$this->_iCurrencyId;
		$aSql['amount'] = $fValue;
		$aSql['amount_field'] = 'amount';

		if($iHoliday == 1) {
			$aSql['amount_field'] = 'amount_holiday';
		}
	
		DB::executePreparedQuery($sSql, $aSql);
		
	}	
	
}
