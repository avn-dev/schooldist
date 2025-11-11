<?php

class Ext_Thebing_School_Cost_Accommodation extends Ext_Thebing_School_Cost {

	protected $_aErrorMessages = array();

	public function setErrorMessage($sMessage) {
		$this->_aErrorMessages[] = L10N::t($sMessage, 'Thebing » Marketing » Costs');
	}
	
	public function getErrorMessages() {
		return $this->_aErrorMessages;
	}

	/**
	 * 
	 * @param $iCostKategorieId
	 * @param $iAccommodationCategoryId
	 * @param $iRoomtypeId
	 * @param $iMealId
	 * @return unknown_type
	 */
	public function getCost($iCostKategorieId, $iAccommodationCategoryId, $iRoomtypeId, $iMealId, $iWeekId = 0){
				
		$aResult = $this->checkForCostData($iCostKategorieId, $iAccommodationCategoryId, $iRoomtypeId, $iMealId, $iWeekId);
		
		$sPriceField = "amount";
		
		if($aResult === false){
			return 0;
		}

		return (float)$aResult[$sPriceField];
		
	}
	
	public function getNightCost($aPeriodIds, $iRoomTypeId, $iMealId) {

		if(!is_array($aPeriodIds)) {
			$aPeriodIds = array($aPeriodIds);
		}
	
		foreach($aPeriodIds as $iPeriodId) {

			$aResult = $this->checkForNightCostData($iPeriodId, $iRoomTypeId, $iMealId);

			if($aResult !== false){
				break;
			}
			
		}
			
		$sPriceField = "amount";
			
		if($aResult === false){
			return 0;
		}

		return (float)$aResult[$sPriceField];

	}

	public function checkForCostData($iCostKategorieId, $iAccommodationCategoryId, $iRoomtypeId, $iMealId, $iWeekId = null) {

		$sWhere = '';
		if(
			$iWeekId !== null &&
			$iWeekId !== 0
		) {
			$sWhere = " AND `week_id` = :week_id ";
		}

		$sSql = "
					SELECT
						*
					FROM
						`kolumbus_accommodations_costs`
					WHERE
						`costcategory_id` = :costcategory_id AND
						`accommodation_category_id` = :accommodation_category_id AND
						`roomtype_id` = :roomtype_id AND 
						`meal_id` = :meal_id AND 
						`saison_id` = :saison_id AND 
						`currency_id` = :currency_id  AND
						`active` = 1
						$sWhere
					";
		$aSql = array();
		$aSql['costcategory_id'] = (int)$iCostKategorieId;
		$aSql['accommodation_category_id'] = (int)$iAccommodationCategoryId;
		$aSql['week_id'] = (int)$iWeekId;
		$aSql['roomtype_id'] = (int)$iRoomtypeId;
		$aSql['meal_id'] = (int)$iMealId;
		$aSql['saison_id'] = (int)$this->_iSaisonId;
		$aSql['currency_id'] = (int)$this->_iCurrencyId;
		
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		if(count($aResult) <= 0){
			return false;
		}
		return $aResult[0];

	}
	
	public function checkForNightCostData($iPeriodId, $iRoomTypeId, $iMealId) {

		$sSql = "
					SELECT
						*
					FROM
						`kolumbus_accommodations_costs_nights`
					WHERE
						`period_id` = :period_id AND
						`roomtype_id` = :roomtype_id AND
						`meal_id` = :meal_id AND
						`saison_id` = :saison_id AND
						`currency_id` = :currency_id AND
						`active` = 1";
		$aSql = array();
		$aSql['period_id'] = $iPeriodId;
		$aSql['roomtype_id'] = $iRoomTypeId;
		$aSql['meal_id'] = $iMealId;
		$aSql['saison_id'] = $this->_iSaisonId;
		$aSql['currency_id'] = $this->_iCurrencyId;

		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		if(count($aResult) <= 0){
			return false;
		}
		return $aResult[0];

	}

	public function saveNightCost($fValue, $iPeriodId, $iRoomTypeId, $iMealId) {
		global $user_data;

		$aData = $this->checkForNightCostData($iPeriodId, $iRoomTypeId, $iMealId);

		$aSql = array();
		$sSqlEnd = "";
		
		if($aData !== false){
			$sSql = "
					UPDATE
						`kolumbus_accommodations_costs_nights`
					SET ";
			$sSqlEnd = " WHERE id = :id LIMIT 1 ";
			$aSql['id'] = $aData['id'];
		} else {
			$sSql = "
					INSERT INTO
						`kolumbus_accommodations_costs_nights`
					SET
						`created` = NOW(),";
		}
		
		$sSqlSet = "
						`user_id` = :user_id,
						`period_id` = :period_id ,
						`roomtype_id` = :roomtype_id ,
						`meal_id` = :meal_id ,
						`saison_id` = :saison_id ,
						`currency_id` = :currency_id,
						`amount` = :amount,
						`active` = 1";

		$sSql = $sSql.$sSqlSet.$sSqlEnd;

		$aSql['user_id'] = (int)$user_data['id'];
		$aSql['period_id'] = (int)$iPeriodId;
		$aSql['roomtype_id'] = (int)$iRoomTypeId;
		$aSql['meal_id'] = (int)$iMealId;
		$aSql['saison_id'] = (int)$this->_iSaisonId;
		$aSql['currency_id'] = (int)$this->_iCurrencyId;
		$aSql['amount'] = (float)$fValue;

		DB::executePreparedQuery($sSql,$aSql);

	}

	public function saveCost($fValue, $iCostcategoryId, $iAccommodationCategoryId, $iRoomTypeId, $iMealId, $iWeekId = 0){
		global $user_data;

		$aData = $this->checkForCostData($iCostcategoryId, $iAccommodationCategoryId, $iRoomTypeId, $iMealId, $iWeekId);

		$aSql = array();
		$sSqlEnd = "";

		if($aData !== false){
			$sSql = "
					UPDATE
						`kolumbus_accommodations_costs`
					SET ";
			$sSqlEnd = " WHERE id = :id LIMIT 1 ";
			$aSql['id'] = (int)$aData['id'];
		} else {
			$sSql = "
					INSERT INTO
						`kolumbus_accommodations_costs`
					SET
						`created` = NOW(),";
		}

		$sSqlSet = "
						`user_id` = :user_id,
						`costcategory_id` = :costcategory_id ,
						`accommodation_category_id` = :accommodation_category_id ,
						`roomtype_id` = :roomtype_id ,
						`meal_id` = :meal_id ,
						`saison_id` = :saison_id ,
						`currency_id` = :currency_id,
						`week_id` = :week_id,
						`amount` = :amount,
						`active` = 1";

		$sSql = $sSql.$sSqlSet.$sSqlEnd;
		
		$aSql['user_id'] = (int)$user_data['id'];
		$aSql['costcategory_id'] = (int)$iCostcategoryId;
		$aSql['accommodation_category_id'] = (int)$iAccommodationCategoryId;
		$aSql['roomtype_id'] = (int)$iRoomTypeId;
		$aSql['meal_id'] = (int)$iMealId;
		$aSql['saison_id'] = (int)$this->_iSaisonId;
		$aSql['currency_id'] = (int)$this->_iCurrencyId;
		$aSql['week_id'] = (int)$iWeekId;
		$aSql['amount'] = (float)$fValue;

		DB::executePreparedQuery($sSql,$aSql);
		
	}
	
	public function getNightPeriod($iCostCategoryId, $iAccommodationCategoryId, $sDate) {

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_accommodations_costs_nights_periods`
			WHERE
				`costcategory_id` = :costcategory_id AND
				`accommodation_category_id` = :accommodation_category_id AND
				:date BETWEEN `from` AND `until` AND
				`active` = 1
			";

		$aSql = array(
			'costcategory_id'=>(int)$iCostCategoryId,
			'accommodation_category_id'=>(int)$iAccommodationCategoryId,
			'date'=>(string)$sDate
		);

		$aPeriods = DB::getQueryRows($sSql, $aSql);

		return $aPeriods;

	}

	public function getNightPeriods($iCostCategoryId, $iAccommodationCategoryId, $sFrom, $sUntil) {

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_accommodations_costs_nights_periods`
			WHERE
				`costcategory_id` = :costcategory_id AND
				`accommodation_category_id` = :accommodation_category_id AND
				`from` >= :from AND
				`until` <= :until AND
				`active` = 1
			";
		$aSql = array(
			'costcategory_id'=>(int)$iCostCategoryId,
			'accommodation_category_id'=>(int)$iAccommodationCategoryId,
			'from'=>(string)$sFrom,
			'until'=>(string)$sUntil
		);

		$aPeriods = DB::getQueryRows($sSql, $aSql);

		return $aPeriods;

	}

	/**
	 * Aktualisiert einen Kosten-Zeitraum
	 * @global array $user_data
	 * @param int $iPeriodId
	 * @param int $iCostCategoryId
	 * @param int $iAccommodationCategoryId
	 * @param string $sFrom
	 * @param string $sUntil
	 * @param Ext_Thebing_Saison $oSeason
	 * @return boolean
	 */
	public function updatePeriod($iPeriodId, $iCostCategoryId, $iAccommodationCategoryId, $sFrom, $sUntil, Ext_Thebing_Saison $oSeason) {
		global $user_data;
		
		$sFromSeason		= $oSeason->valid_from_mysql;
		$sUntilSeason		= $oSeason->valid_until_mysql;

		$oDateFromSeason	= new DateTime($sFromSeason);
		$oDateUntilSeason	= new DateTime($sUntilSeason);
		
		// Datum hat falsches Format? Automatisch korrigieren!
		if(WDDate::isDate($sFrom, WDDate::DB_DATE)) {
			$oDateFromPeriod	= new DateTime($sFrom);
		} else {
			$oDateFromPeriod	= $oDateFromSeason;
			$this->setErrorMessage('Ein Startdatum wurde automatisch korrigiert.');
		}
		if(WDDate::isDate($sUntil, WDDate::DB_DATE)) {
			$oDateUntilPeriod	= new DateTime($sUntil);
		} else {
			$oDateUntilPeriod	= $oDateUntilSeason;
			$this->setErrorMessage('Ein Enddatum wurde automatisch korrigiert.');
		}

		// Passt der Zeitabschnitt in die Saison?
		if($oDateFromSeason > $oDateFromPeriod) {
			$oDateFromPeriod = $oDateFromSeason;
			$this->setErrorMessage('Ein Startdatum lag außerhalb der Saison und wurde automatisch korrigiert.');
		}
		if($oDateUntilSeason < $oDateUntilPeriod) {
			$oDateUntilPeriod = $oDateUntilSeason;
			$this->setErrorMessage('Ein Enddatum lag außerhalb der Saison und wurde automatisch korrigiert.');
		}

		// Liegt das Startdatum vor dem Enddatum?
		if($oDateFromPeriod > $oDateUntilPeriod) {
			$this->setErrorMessage('Ein Enddatum liegt vor einem Startdatum, bitte korrigieren!');
		}

		$aSql = array(
			'costcategory_id'=>(int)$iCostCategoryId,
			'accommodation_category_id'=>(int)$iAccommodationCategoryId,
			'from'=>(string)$oDateFromPeriod->format('Y-m-d'),
			'until'=>(string)$oDateUntilPeriod->format('Y-m-d'),
			'user_id'=>(int)$user_data['id']
		);

		$sSql .= "
				`user_id` = :user_id,
				`costcategory_id` = :costcategory_id,
				`accommodation_category_id` = :accommodation_category_id,
				`from` = :from,
				`until` = :until,
				`active` = 1";

		if($iPeriodId > 0) {
			$sSql = "
				UPDATE
					`kolumbus_accommodations_costs_nights_periods`
				SET
					".$sSql."
				WHERE
					`id` = :id
					";
			$aSql['id'] = (int)$iPeriodId;

			DB::executePreparedQuery($sSql, $aSql);

		} else {
			$sSql = "
				INSERT INTO
					`kolumbus_accommodations_costs_nights_periods`
				SET
					`created` = NOW(),
					".$sSql."
			";
			DB::executePreparedQuery($sSql, $aSql);
			$iPeriodId = DB::fetchInsertID();
		}

		return $iPeriodId;

	}

}