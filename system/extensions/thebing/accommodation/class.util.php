<?php

/**
 * @deprecated 
 */
class Ext_Thebing_Accommodation_Util {
	
	public $oSchool;
	
	protected $aAccommodationCategorieList;
	public $aAccommodationCategorie;
	protected $idAccommodationCategorie;
	
	protected $aRoomtypeList;
	protected $aRoomtype;
	protected $idRoomtype;
	
	protected $idMeal;
	protected $aMeal;

	protected static $_aCache = array();

	protected $sDisplayLanguage;
	
	public $iFrom;
	public $iTo;

	/**
	 * @var Ext_Thebing_Accommodation_Category
	 */
	protected $oAccommodationCategory;

	/**
	 * @param Ext_Thebing_School $oSchool
	 * @param string $sDisplayLanguage
	 * @deprecated
	 */
	public function __construct(Ext_Thebing_School $oSchool, $sDisplayLanguage = '') {

//		if($oSchool == "noData" && !isset($_SESSION['sid'])){
//			throw new \LogicException("Sorry no School available");
//		} elseif($oSchool == "noData" && isset($_SESSION['sid'])){
//			$oSchool = Ext_Thebing_School::getInstance($_SESSION['sid']);
//		} elseif (!is_object($oSchool) && (int)$oSchool > 0){
//			$oSchool = Ext_Thebing_School::getInstance((int)$oSchool);
//		} elseif (!is_object($oSchool)) {
//			$oSchool = Ext_Thebing_School::getInstance($_SESSION['sid']);
//		}

		if($sDisplayLanguage == '') {
			$this->sDisplayLanguage = $oSchool->getInterfaceLanguage();
		} else {
			$this->sDisplayLanguage = $sDisplayLanguage;
		}

		$this->oSchool = $oSchool;
		$this->_setAccommodationCategorieList();

	}

	/**
	 * 
	 * ACCOMMODATIONS
	 * 
	 */
	
	public function getRoomList($idAccommodation = 0){
		
		$sSql = "
			SELECT 
				*,'0' as `allocation`
			FROM
				`kolumbus_rooms` as `room`
			WHERE
				`room`.`accommodation_id` = :idAccommodation AND
				`room`.`active` = 1
		";
		$aSql = array();
		$aSql['idAccommodation']	= (int)$idAccommodation;
		$aBack = DB::getPreparedQueryData($sSql,$aSql);

		return $aBack;	
	}
	
    protected $_aGetAccommodationWeekListCache = array();

    public function getAccommodationWeekList($bForSelects = false, $bWithExtraWeeks = true) {

        $sCacheKey = 'key_'.(int)$bWithExtraWeeks;

		// Dieser Cache ist falsch implementiert und überschreibt so ggf. andere Daten!
		//if(!isset($aCache[$sCacheKey])){

			$accommodationCategorySetting = $this->oAccommodationCategory->getSetting($this->oSchool);
		
            $aWeekIds = $accommodationCategorySetting->weeks;
			
            $sTemp = " AND (";
            $sWhereAddon = "";
            $i = 1;

            foreach((array)$aWeekIds as $iId) {
                $sWhereAddon .= $sTemp." `id` = '".$iId."'";
                $sTemp = " OR ";
                if(count($aWeekIds) == $i) {
                    $sWhereAddon .= " ) ";
                }
                $i++;
            }

            if($bWithExtraWeeks === false) {
                $sWhereAddon.=" AND `kw`.`extra` = 0 ";
            }

            $sSql = "
				SELECT 
					`kw`.* 
				FROM 
					`kolumbus_weeks` `kw` INNER JOIN
					`ts_weeks_schools` `ts_ws` ON
						`ts_ws`.`week_id` = `kw`.`id` AND
						`ts_ws`.`school_id` = :idSchool
				WHERE
					`kw`.`active` = 1
					".$sWhereAddon."
				GROUP BY
					`kw`.`id`
				ORDER BY
					`kw`.`position` ASC,
					`kw`.`id` ASC
			";
            $aSql = ['idSchool' => (int)$this->oSchool->id];
            $aResult = DB::getPreparedQueryData($sSql, $aSql);

            $this->_aGetAccommodationWeekListCache[$sCacheKey] = $aResult;

        //}

		$aResult = $this->_aGetAccommodationWeekListCache[$sCacheKey];

		$aBack = [];
		$p = 0;
		foreach($aResult as $aWeek) {
			if($aWeek['position'] == 0) {
				$aWeek['position'] = $p;
				$p++;
			}
			if($bForSelects == false) {
				$aBack[$aWeek['start_week']] = $aWeek;
			} else {
				$aBack[$aWeek['id']] = $aWeek['title'];
			}
		}

		return $aBack;

	}

	/**
	 **********************************************************************************************
	 * CATEGORIES
	 **********************************************************************************************
	 */
	public function setAccommodationCategorie($idAccommodationCategorie) {
		$this->idAccommodationCategorie = $idAccommodationCategorie;
		$this->_setAccommodationCategorie();
		$this->_setRoomtypeList();
	}

	/**
	 * ALT in Schulobj. istneue Funktion 14.10.10!!
	 */
	public function getAccommodationCategorieList($bForSelect = false)
	{
		return $this->_getAccommodationCategorieList($bForSelect);
	}


	public function getCategoryName($bShort = false){
		return $this->_getCategoryName($bShort);
	}

	public function getCategoryId(){
		return $this->aAccommodationCategorie['id'];
	}	

	public function getAccommodationCategory() {
		return $this->oAccommodationCategory;
	}
	
	/**
	 **********************************************************************************************
	 * ROOMTYPES
	 **********************************************************************************************
	 */
	public function setRoomtype($aRoomtype) {

		$this->idRoomtype = $aRoomtype['id'];
		$this->_setRoomtype($aRoomtype);

		if(!isset(self::$_aCache['roomtype_by_id'][$this->idRoomtype])) {

			$sSql = "
				SELECT
					`kar`.*
				FROM
					`kolumbus_accommodations_roomtypes` as `kar` INNER JOIN
					`ts_accommodation_roomtypes_schools` `ts_ars` ON
						`ts_ars`.`accommodation_roomtype_id` = `kar`.`id` AND
						`ts_ars`.`school_id` = :school_id
				WHERE
					`kar`.`active` = 1 AND
					`kar`.`id` = :type_id
				LIMIT
					1
			";
			$aSql = [
				'school_id' => (int)$this->oSchool->id,
				'type_id' => (int)$this->idRoomtype,
			];

			self::$_aCache['roomtype_by_id'][$this->idRoomtype] = DB::getQueryRow($sSql, $aSql);

		}

		return self::$_aCache['roomtype_by_id'][$this->idRoomtype];

	}

	public function setRoomtypeById($iId){
		$this->idRoomtype = $iId;
		$aRoomtype = $this->_getRoomtypeById($iId);
		$this->_setRoomtype($aRoomtype);
	}
	public function getRoomtype(){
		return $this->aRoomtype;
	}	
	public function getRoomtypeId(){
		return $this->idRoomtype;
	}

	public function getRoomtypeList($bPrepareSelect=false)
	{
		if($bPrepareSelect) {
			$aRoomtypeList = array(''=>'');
			
			foreach((array)$this->aRoomtypeList as $aRoomtype) {
				$aRoomtypeList[$aRoomtype['id']] = $aRoomtype['roomname']; 
			}
			
			return $aRoomtypeList;
		} else {
			return $this->aRoomtypeList;
		}
	}

	public function getAllRoomtypeList($bForSelect = false,$bFullName = false)
	{
		return $this->_getAllRoomtypeList($bForSelect,$bFullName);
	}
	public function getRoomtypeName($bFullName = false){

		$sName = ($this->aRoomtype['short_'.$this->sDisplayLanguage] ?? '');

		if(empty($this->aRoomtype['short_'.$this->sDisplayLanguage])) {
			$sName = $this->aRoomtype['ext_1'] ?? '';
		}

		if($bFullName == true){
			if(empty($this->aRoomtype['name_'.$this->sDisplayLanguage])){
				return $this->aRoomtype['ext_4'] ?? '';
			}
			return $this->aRoomtype['name_'.$this->sDisplayLanguage] ?? '';
		}
		
		return $sName;
	}
	
	/**
	 **********************************************************************************************
	 * MEALS
	 **********************************************************************************************
	 */
	public function getMealList($bForSelect = false,$aMealIds = array(),$bFullName = false){

		if(
			$bFullName
		){
			$sColumn = 'name_'.$this->sDisplayLanguage;
		}else{
			$sColumn = 'short_'.$this->sDisplayLanguage;
		}
		
		$oMeal		= new Ext_Thebing_Accommodation_Meal();
		$oMeal->setSchoolId($this->oSchool->getId());
		$aMeals		= $oMeal->getArrayListSchool($bForSelect, $sColumn);

		if(
			!empty($aMealIds)
		){
			$aCountValues	= array_count_values($aMealIds);

			$aIntersect		= array_intersect_key($aMeals, $aCountValues);

			$aMeals			= $aIntersect;
		}
		
		return $aMeals;
	}

	public function getMeal(){
		return $this->aMeal;
	}

	public function getMealName($bFullName = false){
		
		$sName = $this->aMeal['name_'.$this->sDisplayLanguage];		
		$sName2 = $this->aMeal['short_'.$this->sDisplayLanguage];		
		
		if($bFullName == true){
			return $sName;
		}

		return $sName2;

	}

	public function getMealId(){
		return $this->idMeal;
	}

    /**
     * @param int $iMealId
     */
	public function setMealById($iMealId) {

		$sCacheKey = $iMealId.'_'.$this->oSchool->getId();

		if(!isset(self::$_aCache['meal_by_id'][$sCacheKey])) {

			$sSql = "
				SELECT
					`kam`.*
				FROM
					`kolumbus_accommodations_meals` as `kam` INNER JOIN
					`ts_accommodation_meals_schools` `ts_ams` ON
						`ts_ams`.`accommodation_meal_id` = `kam`.`id` AND
						`ts_ams`.`school_id` = :idSchool
				WHERE
					`kam`.`active` = 1 AND
					`kam`.`id` = :meal_id AND (
						`kam`.`valid_until` = '0000-00-00' OR
						`kam`.`valid_until` >= CURDATE()
					)
				GROUP BY
					`kam`.`id`
            ";
			$aSql = [
				'idSchool' => $this->oSchool->getId(),
				'meal_id' => (int)$iMealId
			];
			self::$_aCache['meal_by_id'][$sCacheKey] = (array)DB::getQueryRow($sSql, $aSql);

		}

		$this->aMeal = self::$_aCache['meal_by_id'][$sCacheKey];
		$this->idMeal = $iMealId;

	}
	
	/**
	 **********************************************************************************************
	 * CATEGORIES
	 **********************************************************************************************
	 */
	public function getCategoryType(){

		$sName = $this->aAccommodationCategorie['ext_6'];
		
		return $sName;
	}
	protected function _getCategoryName($bShort = false){

		if($bShort){
			$sName = $this->aAccommodationCategorie['short_'.$this->sDisplayLanguage];
		}else{
		$sName = $this->aAccommodationCategorie['name_'.$this->sDisplayLanguage];
		}
		
		
		return $sName;
	}
	
	protected function _getAccommodationCategorie() {

		if(!isset(self::$_aCache['accommodation_category'][$this->idAccommodationCategorie])) {

			$sSql = "
					SELECT
						*
					FROM
						`kolumbus_accommodations_categories`
					WHERE
						`active` = 1 AND
						`id` = :idAccommodationCategorie
					LIMIT 1
					";
			$aSql = array (
				'idAccommodationCategorie' => (int)$this->idAccommodationCategorie
			);

			self::$_aCache['accommodation_category'][$this->idAccommodationCategorie] = DB::getQueryRow($sSql, $aSql);

		}

		if (!empty(self::$_aCache['accommodation_category'][$this->idAccommodationCategorie])) {
			$this->oAccommodationCategory = Ext_Thebing_Accommodation_Category::getObjectFromArray(self::$_aCache['accommodation_category'][$this->idAccommodationCategorie]);
		}

		return self::$_aCache['accommodation_category'][$this->idAccommodationCategorie];
	}

	protected function _setAccommodationCategorie() {
		$this->aAccommodationCategorie = $this->_getAccommodationCategorie();
	}
	/**
	 * Set the Accommodation Categorie Array
	 */
	protected function _setAccommodationCategorieList() {
		$this->aAccommodationCategorieList = $this->_getAccommodationCategorieList();
	}

	/**
	 * get the Accommodation Categorie Data 
	 */
	protected function _getAccommodationCategorieList($bForSelect = false) {

		$aBack = [];

		if(!isset(self::$_aCache['_getAccommodationCategorieList'][$this->oSchool->id])) {

			$sSql = "
				SELECT
					`kac`.*
				FROM
					`kolumbus_accommodations_categories` `kac` JOIN
					`ts_accommodation_categories_settings` `ts_acs` ON
						`ts_acs`.`category_id` = `kac`.`id` JOIN
					`ts_accommodation_categories_settings_schools` `ts_acss` ON
						`ts_acs`.`id` = `ts_acss`.`setting_id`
				WHERE
					`kac`.`active` = 1 AND
					`ts_acss`.`school_id` = :idSchool
				GROUP BY
					`kac`.`id`
				ORDER BY
					`kac`.`position`
			";
			$aSql = [
				'idSchool' => (int)$this->oSchool->id,
			];

			self::$_aCache['_getAccommodationCategorieList'][$this->oSchool->id] = DB::getPreparedQueryData($sSql, $aSql);

		}

		$aResult = self::$_aCache['_getAccommodationCategorieList'][$this->oSchool->id];

		foreach ($aResult as $aAccommodationCategory) {

			$sName = $aAccommodationCategory['name_'.$this->sDisplayLanguage];
			if($sName == '') {
				$sName = $aAccommodationCategory['ext_1'];
			}

			$aAccommodationCategory['name'] = $sName;

			if($bForSelect == false) {
				$aBack[$aAccommodationCategory['id']] = $aAccommodationCategory;
			} else {
				$aBack[$aAccommodationCategory['id']] = $sName;
			}

		}

		return $aBack;

	}

	/**
	 **********************************************************************************************
	 * ROOMTYPES
	 **********************************************************************************************
	 */

	protected function _setRoomtype($aRoomtype){
		$this->aRoomtype = $aRoomtype;
	}

	/**
	 * Set the Accommodation Categorie Array
	 */
	protected function _setRoomtypeList(){
		$this->aRoomtypeList = $this->getRoomtypeListQueryData();
	}
	
	/**
	 * get the Accommodation Categorie Data
	 *
	 * @param string $sValidDate
	 * @return array
	 */
	public function getRoomtypeListQueryData($sValidDate = null) {

		if(!isset(self::$_aCache['roomtype_list'][$this->idAccommodationCategorie][$this->oSchool->id][$this->sDisplayLanguage][$sValidDate])) {

			// @TODO Dieser Query sieht ziemlich inperformant aus, aber mit der aktuellen Struktur nicht anders lösbar…
			$sSql = "
				SELECT
					`cdb4`.`id` `accommodation_id`,
					`kr`.`type_id` `type`,
					GROUP_CONCAT(DISTINCT `kam`.`id`) `meal`,
					`kar`.*
				FROM
					`customer_db_4` `cdb4` INNER JOIN
					`kolumbus_rooms` `kr` ON
						`kr`.`accommodation_id` = `cdb4`.`id` AND
						`kr`.`active` = 1 AND (
							`kr`.`valid_until` >= :valid_date OR
							`kr`.`valid_until` = '0000-00-00'
						) INNER JOIN
					`kolumbus_accommodations_roomtypes` `kar` ON
						`kar`.`id` = `kr`.`type_id` AND
						`kar`.`active` = 1 AND (
							`kar`.`valid_until` >= :valid_date OR
							`kar`.`valid_until` = '0000-00-00'
						)  INNER JOIN
					`ts_accommodation_roomtypes_schools` `ts_ars` ON
						`ts_ars`.`accommodation_roomtype_id` = `kar`.`id` AND
						`ts_ars`.`school_id` = :school_id INNER JOIN
					`ts_accommodation_providers_schools` `ts_aps` ON
						`ts_aps`.`accommodation_provider_id` = `cdb4`.`id` AND
						`ts_aps`.`school_id` = :school_id INNER JOIN
					`ts_accommodation_categories_to_accommodation_providers` `kac_t_cdb4` ON
						`kac_t_cdb4`.`accommodation_provider_id` = `cdb4`.`id` AND
						`kac_t_cdb4`.`accommodation_category_id` = :category_id LEFT JOIN
					`ts_accommodation_providers_to_accommodation_meals` `ts_aptap` ON
						`ts_aptap`.`accommodation_provider_id` = `cdb4`.`id` LEFT JOIN
					`kolumbus_accommodations_meals` `kam` ON
						`kam`.`id` = `ts_aptap`.`meal_id` AND
						`kam`.`active` = 1 AND (
							`kam`.`valid_until` >= :valid_date OR
							`kam`.`valid_until` = '0000-00-00'
						)
				WHERE
					`cdb4`.`active` = 1 AND (
						`cdb4`.`valid_until` >= :valid_date OR
						`cdb4`.`valid_until` = '0000-00-00'
					)
				GROUP BY
					`kar`.`id`
				ORDER BY
					`kar`.`id`,
					`kar`.`position`
			";

			if($sValidDate === null) {
				$sValidDate = (new DateTime())->format('Y-m-d');
			}

			$aSql = [
				'category_id' => (int)$this->idAccommodationCategorie,
				'school_id' => (int)$this->oSchool->id,
				'valid_date' => $sValidDate,
			];

			$aRoomlist = (array)DB::getPreparedQueryData($sSql, $aSql);

			foreach($aRoomlist as $iKey => $mValue){
				$aRoomlist[$iKey]['roomname'] = $mValue['name_'.$this->sDisplayLanguage];
				$aRoomlist[$iKey]['room'] = $mValue['short_'.$this->sDisplayLanguage];
			}

			self::$_aCache['roomtype_list'][$this->idAccommodationCategorie][$this->oSchool->id][$this->sDisplayLanguage][$sValidDate] = $aRoomlist;

		}

		return self::$_aCache['roomtype_list'][$this->idAccommodationCategorie][$this->oSchool->id][$this->sDisplayLanguage][$sValidDate];

	}

	protected function _getAllRoomtypeList($bForSelect = false,$bFullName = false){

		$sSql = "
			SELECT 
				`kar`.*
			FROM 
				`kolumbus_accommodations_roomtypes` as `kar` INNER JOIN
				`ts_accommodation_roomtypes_schools` `ts_ars` ON
					`ts_ars`.`accommodation_roomtype_id` = `kar`.`id` AND
					`ts_ars`.`school_id` = :school_id
			WHERE
				`kar`.`active` = 1
			GROUP BY
				`kar`.`id`
			ORDER BY
				`kar`.`position`
		";
		$aSql = [
			'school_id' => (int)$this->oSchool->id,
		];

		$aRoomlist = DB :: getPreparedQueryData($sSql, $aSql);

		if($bForSelect == true) {
			foreach($aRoomlist as $aRoom) {
				$sName = $aRoom['name_'.$this->sDisplayLanguage];					
				$sName2 = $aRoom['short_'.$this->sDisplayLanguage];
				if($bFullName == false) {
					$aBack[$aRoom['id']] = $sName2;
				} else {
					$aBack[$aRoom['id']] = $sName;
				}
			}
		} else {
			$aBack = $aRoomlist;
		}

		return $aBack;

	}

	public static function getRoomtypeByIdWithooutSchoolId($iId) {

		$sSql = "
			SELECT 
				`kar`.*
			FROM 
				`kolumbus_accommodations_roomtypes` `kar` INNER JOIN
				`ts_accommodation_roomtypes_schools` `ts_ars` ON
					`ts_ars`.`accommodation_roomtype_id` = `kar`.`id` INNER JOIN
				`customer_db_2` as `cdb2` ON
					`cdb2`.`id` = `ts_ars`.`school_id` AND
					`cdb2`.`active` = 1
			WHERE
				`kar`.`active` = 1 AND
				`kar`.`id` = :roomtype_id
			GROUP BY
				`kar`.`id`
			LIMIT
				1
		";
		$aSql = [
			'roomtype_id' => (int)$iId,
		];

		$aRoomlist = DB::getPreparedQueryData($sSql, $aSql);
		return $aRoomlist[0];

	}

	/**
	 **********************************************************************************************
	 * MEALS
	 **********************************************************************************************
	 */

	protected function _setMeal() {
		$this->idMeal = $this->aRoomtype['meal_id'];
	}

	protected function _getAccommodationList(){

		$sSql = "
			SELECT 
				`cdb4`.*
			FROM
				`customer_db_4` `cdb4` INNER JOIN
				`ts_accommodation_providers_schools` `ts_aps` ON
					`ts_aps`.`accommodation_provider_id` = `cdb4`.`id` AND
					`ts_aps`.`school_id` = :school_id
			WHERE
				`cdb4`.`active` = 1
			GROUP BY
				`cdb4`.`id`
		";
		$aSql = array();
		$aSql['idSchool'] = (int)\Core\Handler\SessionHandler::getInstance()->get('sid');
		$aResult = DB::getPreparedQueryData($sSql,$aSql);

		$aBack = array();
		$i = 0;
		foreach($aResult as $aAccom){
			$aBack[$i] = $aAccom;
		}
		return $aBack;

	}


	
	public static function getRoom($iRoomId){

		$sSql = "
			SELECT 
				*,'0' as `allocation`
			FROM
				`kolumbus_rooms` as `room`
			WHERE
				`room`.`id` = :id AND
				`room`.`active` = 1
		";
		$aSql = [
			'id' => (int)$iRoomId,
		];
		$aBack = DB::getPreparedQueryData($sSql,$aSql);

		return $aBack[0];

	}

	public static function getAccommodationProviderFromInquiryId($idInquiry, $bGetAll = false) {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$sLanguage = $oSchool->getInterfaceLanguage();

		$aAllocations = Ext_Thebing_Allocation::getAllocationByInquiryId($idInquiry, 0, $bGetAll);

		if(!$bGetAll) {
			$aAllocations = array($aAllocations);
		}

		$aAccommodations = array();
		foreach((array)$aAllocations as $aAllocation) {

			$sSql = "
					SELECT
						`room`.*,
						`type`.`name_".$sLanguage."` as type
					FROM
						`kolumbus_rooms` as `room`,
						`kolumbus_accommodations_roomtypes` as `type`
					WHERE
						`room`.id = :id AND
						`type`.`id`	= `room`.`type_id`
					ORDER BY
						`room`.`name` ASC";

			$aSql = array(
				'id'=>(int)($aAllocation['room_id'] ?? 0)
			);
			$aRoom = DB::getQueryRow($sSql,$aSql);

			$sSql = "
				SELECT
					*
				FROM
					`customer_db_4`
				WHERE
					`id` = :id
			";
			$aSql = [
				'id' => (int)($aRoom['accommodation_id'] ?? 0)
			];

			$aAccommodation = DB::getQueryRow($sSql, $aSql);
			$aAccommodation['room_name'] = ($aRoom['name'] ?? null);

			/*
			 * Das wurde vorher im Query mit abgefragt, aber eine Unterkunft kann jetzt mehrere Kategorien haben
			 * und somit ist das nicht mehr eindeutig. Außerdem wurde das anscheinend auch nirgendwo verwendet.
			 */
			$aAccommodation['category'] = '';

			$aAccommodations[] = $aAccommodation;

		}

		if($bGetAll) {
			return $aAccommodations;
		}

		return $aAccommodation;

	}

	public static function getAccommodationProvidersFromId($iId){
		

		$sSql = "SELECT
					`accom`.*,
					`accom`.`ext_33` `provider`
				FROM
					`customer_db_4` `accom`
				WHERE
					`accom`.`id` = :id";

		$aSql = array();
		$aSql['id']	= (int)$iId;
		
		$aAccommodations = DB::getPreparedQueryData($sSql,$aSql);

		return $aAccommodations;
		
	}

	protected function _getRoomtypeById($iId) {

		if(!isset(self::$_aCache['roomtype_by_id'][$iId])) {

			$sSql = "
				SELECT
					`kar`.*
				FROM
					`kolumbus_accommodations_roomtypes` as `kar` INNER JOIN
					`ts_accommodation_roomtypes_schools` `ts_ars` ON
						`ts_ars`.`accommodation_roomtype_id` = `kar`.`id` AND
						`ts_ars`.`school_id` = :school_id
				WHERE
					`kar`.`active` = 1 AND
					`kar`.`id` = :type_id
				GROUP BY
					`kar`.`id`
				LIMIT
					1
			";
			$aSql = [
				'school_id' => (int)$this->oSchool->id,
				'type_id' => (int)$iId,
			];
			self::$_aCache['roomtype_by_id'][$iId] = DB::getQueryRow($sSql, $aSql);

		}

		return self::$_aCache['roomtype_by_id'][$iId];

	}

}
