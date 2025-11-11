<?php

class Ext_Thebing_Saison {

	/**
	 * @var Ext_Thebing_School 
	 */
	public $oSchool;

	protected $aSaisonList; 
	protected $aSaison;
	public static $aSeasonById = array();
	protected static $_aCache = array();

	protected $_bPriceSaison = true;
	protected $_bTeacherSaisons = false;
	protected $_bTransferSaisons = false;
	protected $_bAccommodationSaisons = false;
	protected $_bActivitySaisons = false;
	protected $_bFixcostSaisons = false;

	/**
	 * @param string|int|Ext_Thebing_School $oSchool
	 * @param bool $bPriceSaisons
	 * @param bool $bTeacherSaisons
	 * @param bool $bTransferSaisons
	 * @param bool $bAccommodationSaisons
	 * @param bool $bFixcostSaisons
	 * @throws \LogicException
	 */
	public function __construct($oSchool = "noData", $bPriceSaisons = true, $bTeacherSaisons = false, $bTransferSaisons = false, $bAccommodationSaisons = false, $bFixcostSaisons = false) {

		if(!is_object($oSchool)) {
			if((int)$oSchool > 0) {
				$oSchool = Ext_Thebing_School::getInstance((int)$oSchool);
			} else {
				// TODO Der Schrott sollte raus, da das vor allem bei All Schools Überraschungen bereit hält!
				$oSchool = Ext_Thebing_School::getSchoolFromSession();
			}
		}

		if(
			!($oSchool instanceof Ext_Thebing_School) ||
			$oSchool->id < 1
		) {
			throw new \LogicException('No school available (season)');
		}

		$this->oSchool = $oSchool;
		$this->_bPriceSaison = (bool)$bPriceSaisons;
		$this->_bTeacherSaisons = (bool)$bTeacherSaisons;
		$this->_bTransferSaisons = (bool)$bTransferSaisons;
		$this->_bAccommodationSaisons = (bool)$bAccommodationSaisons;
		$this->_bFixcostSaisons = (bool)$bFixcostSaisons;

		$this->_setSaisonList(
			$this->_bPriceSaison,
			$this->_bTeacherSaisons,
			$this->_bTransferSaisons,
			$this->_bAccommodationSaisons,
			$this->_bFixcostSaisons
		);

	}

	public function search($iSaisonTime, $sDiscountFor = 'accommodation', $iDiscountTime = 0) {

		$bDiscountCheck = false;

		if($iDiscountTime > 0){
			$bDiscountCheck = true;
		}

		$oSaisonSearch 	= new Ext_Thebing_Saison_Search();

		$aSaisonData 	= $oSaisonSearch->bySchoolAndTimestamp(
																$this->oSchool->id,
																$iSaisonTime,
																$iDiscountTime,
																$sDiscountFor,
																$bDiscountCheck,
																$this->_bPriceSaison,
																$this->_bTeacherSaisons,
																$this->_bTransferSaisons,
																$this->_bAccommodationSaisons,
																$this->_bFixcostSaisons,
																$this->_bActivitySaisons
															);

		$iSaisonId 		= $aSaisonData[0]['id'];

		return $iSaisonId;

	}

	public function getSaisonId(){
		return (int)$this->aSaison['id'];
	}

	public function __get($sField){
		return $this->aSaison[$sField];
	}

	/**
	 * @param int $id
	 */
	public function setSaisonById($id){
		$this->aSaison = $this->getSaisonById($id);			
	}
	
	public function checkHoliday($iDay = time){
		//TODO
		return false;
	}

	/**
	 * @param int $idSaison
	 * @return mixed[]
	 */
	public function getSaisonById($idSaison){

		$idSaison = (int)$idSaison;

		if($idSaison <= 0) {
			return [];
		}

		// Caching
		if(!isset(self::$aSeasonById[$idSaison])) {

			$sSql = "
				SELECT
					*,
					valid_from AS valid_from_mysql,
					valid_until AS valid_until_mysql,
					UNIX_TIMESTAMP(valid_from) AS valid_from,
					UNIX_TIMESTAMP(valid_until) AS valid_until
				FROM
					#table
				WHERE
					`active` = 1
				AND
					`id` = :idSaison
				LIMIT
					1
			";
			$aSql = [
				'table' => 'kolumbus_periods',
				'idSaison' => (int)$idSaison
			];
			$aResult = DB::getPreparedQueryData($sSql,$aSql);
			self::$aSeasonById[$idSaison] = $aResult[0];

		}

		return self::$aSeasonById[$idSaison];

	}

	public static function getSaisonDataById($idSaison) {
		$sSql = "SELECT 
					*,
					UNIX_TIMESTAMP(valid_from) as valid_from,
					UNIX_TIMESTAMP(valid_until) as valid_until
				FROM 
					#table 
				WHERE
					`active` = 1
				AND
					`id` = :idSaison
				LIMIT 1";
		$aSql = array('table'=>'kolumbus_periods','idSaison'=>(int)$idSaison);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);	
		return $aResult[0];
	}

	/**
	 * @param bool $bForSelect
	 * @return array
	 */
	public function getSaisonList($bForSelect = false) {
		
		if($bForSelect == false) {
			return $this->aSaisonList;
		}
		
		if(
			is_object($this->oSchool) &&
			$this->oSchool instanceof Ext_Thebing_School
		) {
			$sLanguage = $this->oSchool->getInterfaceLanguage();
		} else {
			$sLanguage = 'en';
		}
		
		$sTitleColumn = 'title_'.$sLanguage;

		$aBack = array();
		foreach((array)$this->aSaisonList as $aData) {
			$aBack[$aData['id']] = $aData[$sTitleColumn]." (".Ext_Thebing_Format::LocalDate($aData['valid_from'])." - ".Ext_Thebing_Format::LocalDate($aData['valid_until']).")";
		}
		
		return $aBack;
	}
	
	public function getInformation($sLanguage = '') {
		// Veraltet
	}
	
	public function getHolidays($idSaison = 0) {

		if($idSaison == 0) {
			$idSaison = $this->aSaison['id'];
		}

		$sSql = "
				SELECT
					*
				FROM
					`kolumbus_holidays`
				WHERE
					`idSchool` 	= :idSchool AND
					`idSaison`	= :idSaison
				";
		$aSql = array();
		$aSql['idSchool'] = (int)\Core\Handler\SessionHandler::getInstance()->get('sid');
		$aSql['idSaison'] = (int)$idSaison;
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		
		return $aResult;

	}

	public function checkHolidays($sDay, $idSaison = 0){
		global $user_data;

		if(is_numeric($sDay)) {
			$oDate = new WDDate($sDay);
			$sDay = $oDate->get(WDDate::DB_DATE);
		}

		if(!isset(self::$_aCache['check_holidays'][$user_data['client']][$sDay])) {

			$sSql = "
					SELECT
						*
					FROM
						`kolumbus_holidays_public`
					WHERE
						`active`	= 1 AND
						(
							(
								`annual` = 1 AND
								DAY(`date`) = DAY(:date) AND
								MONTH(`date`) = MONTH(:date)
							) OR
							(
								`annual` = 0 AND
								`date` = :date
							)
						)
					";
			$aSql = array();
			$aSql['date'] = $sDay;
			$aResult = DB::getQueryRow($sSql,$aSql);

			if(!empty($aResult)) {
				self::$_aCache['check_holidays'][$user_data['client']][$sDay] = true;
			} else {
				self::$_aCache['check_holidays'][$user_data['client']][$sDay] = false;
			}

		}

		return self::$_aCache['check_holidays'][$user_data['client']][$sDay];

	}

	protected function _setSaisonList($bPriceSaisons = true,$bTeacherSaisons = false,$bTransferSaisons = false,$bAccommodationSaisons = false,$bFixcostSaisons = false){
		$this->aSaisonList = $this->_getSaisonList($bPriceSaisons,$bTeacherSaisons,$bTransferSaisons,$bAccommodationSaisons,$bFixcostSaisons);

		$this->_setCurrentSaison($bPriceSaisons,$bTeacherSaisons,$bTransferSaisons,$bAccommodationSaisons,$bFixcostSaisons);
	}
	
	protected function _getSaisonList($bPriceSaisons = true, $bTeacherSaisons = false, $bTransferSaisons = false, $bAccommodationSaisons = false, $bFixcostSaisons = false) {

		if(is_object($this->oSchool)){

			$aArguments = func_get_args();
			$sKey = 'KEY_'.$this->oSchool->id.'_'.implode('-', $aArguments);

			// Caching
			if(!isset(self::$_aCache['saison_list'][$sKey])) {

				$aSqlAddon = array();
				if($bPriceSaisons){
					$aSqlAddon[] = " saison_for_price = 1 ";
				}
				if($bTeacherSaisons){
					$aSqlAddon[] = " saison_for_teachercost = 1 ";
				}
				if($bTransferSaisons){
					$aSqlAddon[] = " saison_for_transfercost = 1 ";
				}
				if($bAccommodationSaisons){
					$aSqlAddon[] = " saison_for_accommodationcost = 1 ";
				}
				if($bFixcostSaisons){
					$aSqlAddon[] = " saison_for_fixcost = 1 ";
				}

				$sSqlAddon = " ( ";
				$i = 1;
				foreach((array)$aSqlAddon as $sData){
					$sSqlAddon .= $sData." ";
					if($i < count($aSqlAddon)){
						$sSqlAddon.= " OR ";
					}
					$i++;
				}
				$sSqlAddon .= " ) AND ";
				if(count($aSqlAddon) <= 0){
					$sSqlAddon = '';
				}
				$sSql = "SELECT
							*,
							UNIX_TIMESTAMP(valid_from) as valid_from,
							UNIX_TIMESTAMP(valid_until) as valid_until
						FROM
							#table
						WHERE
							".$sSqlAddon."
							`active` = 1	AND
							`idPartnerschool` = :idSchool
						";

				$aSql = array('table'=>'kolumbus_periods', 'idSchool'=>(int)$this->oSchool->id);

				$aResult = DB::getPreparedQueryData($sSql, $aSql);

				foreach($aResult as $aSaison){
					$aBack[$aSaison['id']] = $aSaison;
				}

				self::$_aCache['saison_list'][$sKey] = (array)$aBack;

			}

			return self::$_aCache['saison_list'][$sKey];

		}

		return false;

	}

	protected function _setCurrentSaison($bPriceSaisons = true,$bTeacherSaisons = false,$bTransferSaisons = false,$bAccommodationSaisons = false,$bFixcostSaisons = false) {
		global $_VARS;

		if (empty($_VARS['idPeriod'])) {
			$this->aSaison = $this->_getCurrentSaison($bPriceSaisons,$bTeacherSaisons,$bTransferSaisons,$bAccommodationSaisons,$bFixcostSaisons);
		} else {
			$this->aSaison = $this->getSaisonById($_VARS['idPeriod']);
		}

		$_VARS['idPeriod'] = $this->aSaison['id'];

	}
	
	protected function _getCurrentSaison($bPriceSaisons = true, $bTeacherSaisons = false, $bTransferSaisons = false, $bAccommodationSaisons = false, $bFixcostSaisons = false) {

		$aArguments = func_get_args();
		$sKey = 'KEY_'.$this->oSchool->id.'_'.implode('-', $aArguments);

		if(!isset(self::$_aCache['current_saison'][$sKey])) {

			$aSqlAddon = array();
			if($bPriceSaisons){
				$aSqlAddon[] = " saison_for_price = 1 ";
			}
			if($bTeacherSaisons){
				$aSqlAddon[] = " saison_for_teachercost = 1 ";
			}
			if($bTransferSaisons){
				$aSqlAddon[] = " saison_for_transfercost = 1 ";
			}
			if($bAccommodationSaisons){
				$aSqlAddon[] = " saison_for_accommodationcost = 1 ";
			}
			if($bFixcostSaisons){
				$aSqlAddon[] = " saison_for_fixcost = 1 ";
			}

			$sSqlAddon = " ( ";
			$i = 1;
			foreach((array)$aSqlAddon as $sData){
				$sSqlAddon .= $sData." ";
				if($i < count($aSqlAddon)){
					$sSqlAddon.= " OR ";
				}
				$i++;
			}
			$sSqlAddon .= " ) AND ";
			if(count($aSqlAddon) <= 0){
				$sSqlAddon = '';
			}
			$sSql = "
					SELECT
						*,
						UNIX_TIMESTAMP(valid_from) as valid_from,
						UNIX_TIMESTAMP(valid_until) as valid_until
					FROM
						#table
					WHERE
						".$sSqlAddon."
						`active` = 1 AND
						`idPartnerschool` = :idSchool AND
						NOW() BETWEEN `valid_from` AND `valid_until`
					ORDER BY
						(UNIX_TIMESTAMP(`valid_until`) - UNIX_TIMESTAMP(`valid_from`)) ASC
					LIMIT
						1";
			$aSql = array(
				'table'=>'kolumbus_periods',
				'idSchool'=>(int)$this->oSchool->id
			);
			$aReturn = DB::getQueryRow($sSql, $aSql);

			self::$_aCache['current_saison'][$sKey] = (array)$aReturn;

		}

		return self::$_aCache['current_saison'][$sKey];

	}

	protected static function _error($sString){
		die("[Saison] :: ".$sString);
	}

}
