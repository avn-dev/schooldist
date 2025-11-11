<?php


class Ext_Thebing_Marketing_Saison extends Ext_Thebing_Basic  {

	/**
	 * @var string
	 */
	protected $_sTable = 'kolumbus_periods';

	/**
	 * @var string
	 */
	protected $_sTableAlias = '';

	/**
	 * @var array
	 */
	protected $_aFormat = array(
		'valid_from' => array(
			'required'	=> true,
			'validate'	=> 'DATE'
		),
		'valid_until' => array(
			'required'	=> true,
			'validate'	=> 'DATE'
		),
	);

	public function getName($language = null) {

		if ($language === null) {
			$school = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
			$language = $school->getInterfaceLanguage();
		}

		return $this->{'title_'.$language};

	}

	/**
	 * @param int $iSchoolId
	 * @param int $iClientId wird nicht mehr verwendet
	 * @param string $sInterfaceLanguage
	 * @param bool $bPrepareForSelect
	 * @return array
	 */
	public function getSaisonList($iSchoolId,$iClientId,$sInterfaceLanguage, $bPrepareForSelect=true, $bAddEmptyItem=true) {

			$sTitleColumn = 'title_'.$sInterfaceLanguage;

			$sSql = "
				SELECT
					*
				FROM
					#table
				WHERE
					`active`			= 1 AND
					`idPartnerschool`	= :idSchool
				";

			$aSql = array(
				'table'		=> $this->_sTable,
				'idSchool'	=> (int)$iSchoolId,
			);

			$aResult = DB::getPreparedQueryData($sSql, $aSql);
			$aBack	 = array();

			if($bPrepareForSelect)
			{
				foreach((array)$aResult as $aSaison){
					$sTitle = $aSaison[$sTitleColumn];
					if( 0 >= strlen($sTitle) )
					{
						$sTitle = $aSaison['title_en'];
					}
					$aBack[$aSaison['id']] = $sTitle;
				}

				if($bAddEmptyItem === true) {
					$aBack = Ext_Thebing_Util::addEmptyItem($aBack);
				}
			}
			else
			{
				$aBack = $aResult;
			}


			return $aBack;
	}

	/**
	 *
	 * @param int $iFromSaison
	 * @param int $iToSaison
	 * @return int
	 */
	public function copyPrices($iFromSaison, $iToSaison=false){
		if(false === $iToSaison){
			$iToSaison = $this->id;
		}

		try{
			// Preise holen ================================================================
			$sSql = "SELECT 
							* 
						FROM 
							`kolumbus_prices_new` 
						WHERE 
							`idSaison` = :idSaison AND 
							`typeParent` NOT LIKE 'cost_%'
					";
			
			$aSql = array();
			$aSql['idSaison'] = (int)$iFromSaison;

			
			$aCopyFromData = DB::getPreparedQueryData($sSql, $aSql);

			// Alte Preise löschen
			$sSql = "DELETE 
						FROM 
							`kolumbus_prices_new` 
						WHERE 
							`idSaison` = :idSaison AND 
							`typeParent` NOT LIKE 'cost_%'
						";
			$aSql = array();
			$aSql['idSaison'] = (int)$iToSaison;
			
			DB::executePreparedQuery($sSql, $aSql);
			
			// Neu Preise schreiben
			foreach((array) $aCopyFromData as $aPrice){
				$sSql = "
				INSERT INTO
					`kolumbus_prices_new`
				SET
					`idSchool` = :idSchool,
					`idSaison` = :idSaison,
					`idCurrency` = :idCurrency,
					`idWeek` = :idWeek,
					`idParent` = :idParent,
					`typeParent` = :typeParent,
					`value` = :value
				";

				$aSql = array(
					'idSchool' => (int) $aPrice['idSchool'],
					'idSaison' => (int) $iToSaison,
					'idCurrency' => (int) $aPrice['idCurrency'],
					'idWeek' => (int) $aPrice['idWeek'],
					'idParent' => (int) $aPrice['idParent'],
					'typeParent' => $aPrice['typeParent'],
					'value' => $aPrice['value']
				);

				DB::executePreparedQuery($sSql, $aSql);
			}
			
			// Kurszusatzpreise =========================================================		
			$sSql = "SELECT
							*
						FROM
							`kolumbus_course_fee`
						WHERE
							`saison_id` = :season_id
					";
			$aSql = array();
			$aSql['season_id'] = (int)$iFromSaison;	
			$aAdditionalCoursePrice = DB::getPreparedQueryData($sSql, $aSql);	
			
			// Alte Preise löschen
			$sSql = "DELETE
						FROM
							`kolumbus_course_fee`
						WHERE
							`saison_id` = :season_id
					";
			$aSql = array();
			$aSql['season_id'] = (int)$iToSaison;
			DB::executePreparedQuery($sSql, $aSql);
			
			// Neue Preise einfügen
			foreach((array)$aAdditionalCoursePrice as $aPrice){
				$sSql = "
						INSERT INTO
							`kolumbus_course_fee`
						SET
							`school_id`		= :school_id 	,
							`course_id`		= :course_id,
							`saison_id`		= :saison_id 	,
							`cost_id`		= :cost_id,
							`currency_id`	= :currency_id,
							`amount`		= :amount
				";

				$aSql = array();
				$aSql['school_id']			= (int)$aPrice['school_id'];
				$aSql['course_id']			= (int)$aPrice['course_id'];
				$aSql['saison_id']			= (int)$iToSaison;
				$aSql['cost_id']			= (int)$aPrice['cost_id'];
				$aSql['currency_id']		= (int)$aPrice['currency_id'];
				$aSql['amount']				= $aPrice['amount'];

				DB::executePreparedQuery($sSql, $aSql);
			}
			
			
			// Unterkunftszusatzpreise =================================================
			$sSql = "SELECT
							*
						FROM
							`kolumbus_accommodation_fee`
						WHERE
							`saison_id` = :season_id
					";
			$aSql = array();
			$aSql['season_id'] = (int)$iFromSaison;
			
			$aAdditionalAccommodationPrice = DB::getPreparedQueryData($sSql, $aSql);	
			
			// Alte Preise löschen
			$sSql = "DELETE
						FROM
							`kolumbus_accommodation_fee`
						WHERE
							`saison_id` = :season_id
					";
			$aSql = array();
			$aSql['season_id'] = (int)$iToSaison;
			DB::executePreparedQuery($sSql, $aSql);
			
			// Neue Preise einfügen
			foreach((array)$aAdditionalAccommodationPrice as $aPrice){
				$sSql = "
						INSERT INTO
							`kolumbus_accommodation_fee`
						SET
							`school_id`		= :school_id 	,
							`categorie_id`	= :categorie_id,
							`saison_id`		= :saison_id 	,
							`cost_id`		= :cost_id,
							`currency_id`	= :currency_id,
							`amount`		= :amount
				";

				$aSql = array();
				$aSql['school_id']			= (int)$aPrice['school_id'];
				$aSql['categorie_id']		= (int)$aPrice['categorie_id'];
				$aSql['saison_id']			= (int)$iToSaison;
				$aSql['cost_id']			= (int)$aPrice['cost_id'];
				$aSql['currency_id']		= (int)$aPrice['currency_id'];
				$aSql['amount']				= $aPrice['amount'];

				DB::executePreparedQuery($sSql, $aSql);
			}
			
			// =========================================================================
			
			// Versicherungspreise müssen auch mit kopiert werden
			$sSql = "SELECT
							*
						FROM
							`kolumbus_insurance_prices` 
						WHERE
							`active` = 1 AND
							`period_id` = :period_id
					";
			$aSql = array();
			$aSql['period_id'] = (int)$iFromSaison;
			
			$aInsurancePrices = DB::getPreparedQueryData($sSql, $aSql);
			
			// Preise löschen der herein zu kopierenden Season
			$sSql = "UPDATE
							`kolumbus_insurance_prices`
						SET
							`active` = 0 
						WHERE
							`period_id` = :period_id
					";
			$aSql = array();
			$aSql['period_id'] = (int)$iToSaison;
			
			DB::executePreparedQuery($sSql, $aSql);
				
			// Preise einfügen
			foreach($aInsurancePrices as $aData){
				
				$sSql = "INSERT INTO
								`kolumbus_insurance_prices` 
							SET
								`active`		= 1,
								`creator_id`	= :creator_id,
								`school_id`		= :school_id,
								`insurance_id`	= :insurance_id,
								`week_id`		= :week_id 	,
								`period_id`		= :period_id,
								`currency_id`	= :currency_id,
								`price`			= :price,
								`packet`		= :packet,
								`user_id`		= :user_id
						";
				
				$aSql = array();
				$aSql['creator_id']		= $aData['creator_id'];
				$aSql['school_id']		= $aData['school_id'];
				$aSql['insurance_id']	= $aData['insurance_id'];
				$aSql['week_id']		= $aData['week_id'];
				$aSql['period_id']		= $iToSaison;
				$aSql['currency_id']	= $aData['currency_id'];
				$aSql['price']			= $aData['price'];
				$aSql['packet']			= $aData['packet'];
				$aSql['user_id']		= $aData['user_id'];
				
				DB::executePreparedQuery($sSql, $aSql);
			}
			

			return 1;
		} catch(Exception $e){
			return 0;
		}
	}

	/**
	 * @param int $iOriginSeason
	 */
	public function copyCommission($iOriginSeason) {

		DB::begin(__CLASS__.':'.__METHOD__);

		// Provisionen holen
		$sSql = " SELECT * FROM `ts_commission_categories_values_old` WHERE `season_id` = {$iOriginSeason}";
		$aCommissions = DB::getQueryRows($sSql);

		// Alte Provisionen löschen
		$sSql = " DELETE FROM `ts_commission_categories_values_old` WHERE `season_id` = {$this->id}";
		DB::executeQuery($sSql);

		foreach($aCommissions as $aCommission) {
			$sSql = "
				INSERT INTO
					`ts_commission_categories_values_old`
				SET
					`group_id` = :group_id,
					`school_id` = :school_id,
					`season_id` = :season_id,
					`type_id` = :type_id,
					`type` = :type,
					`category_id` = :category_id,
					`provision` = :provision,
					`additional_id` = :additional_id
			";

			$aCommission['season_id'] = $this->id;
			DB::executePreparedQuery($sSql, $aCommission);
		}

		DB::commit(__CLASS__.':'.__METHOD__);

	}

	/**
	 * @param int $iFromSaison
	 * @param int $iToSaison
	 * @return int
	 */
	public function copyCosts($iFromSaison, $iToSaison=false)
	{
		if( false === $iToSaison )
		{
			$iToSaison = $this->id;
		}

		try
		{
			/******************************* Costs Accommodation *************************************/

			$sSql = " SELECT * FROM `kolumbus_accommodations_costs` WHERE `saison_id` = :idSaison AND active = 1 ";
			$aSql = array('idSaison'=>$iFromSaison);
			$aCopyFromData = DB::getPreparedQueryData($sSql,$aSql);

			// Alte Kosten Löschen
			$sSql = " UPDATE `kolumbus_accommodations_costs` SET `active` = 0 WHERE `saison_id` = :idSaison ";
			$aSql = array('idSaison'=>$iToSaison);
			DB::executePreparedQuery($sSql,$aSql);

			foreach((array)$aCopyFromData as $aPrice)
			{

				$sSql = "
					INSERT INTO
						`kolumbus_accommodations_costs`
					SET
						`costcategory_id` = :costcategory_id,
						`accommodation_category_id` = :accommodation_id,
						`roomtype_id` = :roomtype_id,
						`meal_id` = :meal_id,
						`week_id` = :week_id,
						`saison_id` = :saison_id,
						`currency_id` = :currency_id,
						`amount` = :amount
				";
				$aSql = array(
					'costcategory_id'=>$aPrice['costcategory_id'],
					'accommodation_id'=>$aPrice['accommodation_category_id'],
					'roomtype_id'=>$aPrice['roomtype_id'],
					'meal_id'=>$aPrice['meal_id'],
					'week_id'=>$aPrice['week_id'],
					'saison_id'=>$iToSaison,
					'currency_id'=>$aPrice['currency_id'],
					'amount'=>$aPrice['amount']
				);

				DB::executePreparedQuery($sSql,$aSql);

			}


			/******************************* Costs Fixcost *************************************/

			$sSql = " SELECT * FROM `kolumbus_costprice_fixcost` WHERE `saison_id` = :idSaison AND active = 1 ";
			$aSql = array('idSaison'=>$iFromSaison);
			$aCopyFromData = DB::getPreparedQueryData($sSql,$aSql);

			// Alte Kosten Löschen
			$sSql = " UPDATE `kolumbus_costprice_fixcost` SET `active` = 0 WHERE `saison_id` = :idSaison ";
			$aSql = array('idSaison'=>$iToSaison);
			DB::executePreparedQuery($sSql,$aSql);

			foreach((array)$aCopyFromData as $aPrice)
			{

				$sSql = "
					INSERT INTO
						`kolumbus_costprice_fixcost`
					SET
						`fixcost_id` = :fixcost_id,
						`school_id` = :school_id,
						`saison_id` = :saison_id,
						`currency_id` = :currency_id,
						`amount` = :amount
				";
				$aSql = array(
					'fixcost_id'=>$aPrice['fixcost_id'],
					'school_id'=>$aPrice['school_id'],
					'saison_id'=>$iToSaison,
					'currency_id'=>$aPrice['currency_id'],
					'amount'=>$aPrice['amount']
				);
				DB::executePreparedQuery($sSql,$aSql);

			}

			/******************************* Costs Teachers *************************************/

			$sSql = "
				SELECT
					*
				FROM
					`kolumbus_costprice_teacher`
				WHERE
					`saison_id` = :saison_id
			";
			$aCopyFromData = (array)DB::getPreparedQueryData($sSql, array(
				'saison_id' => $iFromSaison
			));

			// Alte Preise/Kosten Löschen
			$sSql = "
				DELETE FROM
					`kolumbus_costprice_teacher`
				WHERE
					`saison_id` = :saison_id
			";
			DB::executePreparedQuery($sSql, array(
				'saison_id' => $iToSaison
			));

			foreach($aCopyFromData as $aPrice) {

				$sSql = "
					INSERT INTO
						`kolumbus_costprice_teacher`
					SET
						`course_id` = :course_id,
						`costkategorie_id` = :costkategorie_id,
						`school_id` = :school_id,
						`saison_id` = :saison_id,
						`currency_id` = :currency_id,
						`amount` = :amount,
						`amount_holiday` = :amount_holiday
				";
				DB::executePreparedQuery($sSql, array(
					'course_id' => $aPrice['course_id'],
					'costkategorie_id' => $aPrice['costkategorie_id'],
					'school_id' => $aPrice['school_id'],
					'currency_id' => $aPrice['currency_id'],
					'amount' => $aPrice['amount'],
					'amount_holiday' => $aPrice['amount_holiday'],
					'saison_id' => $iToSaison,
				));

			}

		} catch(Exception $e) {

			__pout($e->getMessage());
			return 0;

		}

		return 1;

	}

	/**
	 * siehe parent
	 * @param bool $bThrowExceptions
	 * @return array|bool
	 */
	public function validate($bThrowExceptions = false){
		
		$mValidate = parent::validate($bThrowExceptions);
		
		if($mValidate === true)
		{
			$mValidate = array();
		}
		
		// Das Enddatum einer Periode muss immer größer als das Startdatum sein
		if(
			WDDate::isDate($this->valid_from, WDDate::DB_DATE) &&
			WDDate::isDate($this->valid_until, WDDate::DB_DATE)	
		)
		{
			$oDateFrom = new WDDate($this->valid_from, WDDate::DB_DATE);
			$oDateUntil = new WDDate($this->valid_until, WDDate::DB_DATE);

			$iComp = $oDateUntil->compare($oDateFrom);

			if($iComp < 0){
				if(!is_array($mValidate)){
					$mValidate = array();
				}

				$mValidate['valid_until'][] = 'INVALID_PERIOD_END';

			}	
		}
		
		if(empty($mValidate))
		{
			$mValidate = true;
		}
		
		return $mValidate;
	}

	/**
	 * Gibt Kurse zurück, die in dieser Periode gültig sind
	 *
	 * $oFilterDeactivatedEntries-Closure funktioniert auch so
	 *
	 * @param Ext_Thebing_School $oSchool
	 * @return Ext_Thebing_Tuition_Course[]
	 */
	public function getCoursesBySchool(Ext_Thebing_School $oSchool) {

		$sSql = "
			SELECT
				*
			FROM
				`kolumbus_tuition_courses`
			WHERE
				`active` = 1 AND
				`school_id` = :school_id AND (
					`valid_until` >= :valid_from OR
					`valid_until` = '0000-00-00'
				)
			ORDER BY
				`position`
		";

		$aSql = [
			'school_id' => $oSchool->getId(),
			'valid_from' => $this->valid_from
		];

		$aCourses = array_map(function($aRow) {
			return Ext_Thebing_Tuition_Course::getObjectFromArray($aRow);
		}, (array)DB::getQueryRows($sSql, $aSql));

		return $aCourses;
	}

}
