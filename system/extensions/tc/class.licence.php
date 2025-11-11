<?php

/**
 * Lizenzverwaltung WDBASIC
 */
class Ext_TC_Licence extends Ext_TC_Basic {
	
	protected $_sTable = 'tc_licences';
	
	protected $_sTableAlias = 'tl';

	protected $_aFormat = array(
		
		'sms_credits' => array(
			'validate'	=> 'INT_NOTNEGATIVE'
		),
		'sms_credits_used' => array(
			'validate'	=> 'INT_NOTNEGATIVE'
		),
		'school_limit_schools' => array(
			'validate'	=> 'INT_NOTNEGATIVE'
		),
		'school_limit_weeks' => array(
			'validate'	=> 'INT_NOTNEGATIVE'
		),
		'agency_limit_offices' => array(
			'validate'	=> 'INT_NOTNEGATIVE'
		),
		'external_server_url' => array(
			'validate'	=> 'REGEX',
			'validate_value' => 'https?:\/\/[^<>\/]+'
		)
	);

	/*protected $_aJoinTables = array(
		'join_modules' => array(
			'table'=>'tc_licences_to_modules',
			'foreign_key_field'=>'licence_id',
			'primary_key_field'=>'module_id',
			'delete_check'=>true,
			'autoload'=>true
		)
	);*/
	
	/**
	 * Setzt Default-Values in den Dialog
	 *
	 * @param type $iDataID 
	 */
	protected function  _loadData($iDataID) {

		parent::_loadData($iDataID);
		
		if($iDataID == 0) {
			$sLicence = self::getNewLicence();
			$this->licence = $sLicence;
		}
		
	}
	
	/**
	 * Get All Modules for the Timepoint
	 * @param type $iTimepoint
	 * @return Ext_TC_Licence_Module[] 
	 */
	public function getModules($iTimepoint = null){
		
		if($iTimepoint == null){
			$iTimepoint = time();
		}
		
		$sSql = "SELECT 
					`tc_lm`.`id` `id`
				FROM
					`tc_licences_to_modules` `tc_ltm` INNER JOIN
					`tc_licences_modules` `tc_lm` ON
						`tc_lm`.`id` = `tc_ltm`.`module_id`
				WHERE
					`tc_ltm`.`licence_id` = :licence_id AND
					IF(
						`tc_ltm`.`valid_from` != '0000-00-00' AND `tc_ltm`.`valid_until` != '0000-00-00' ,
						(
							:time BETWEEN `tc_ltm`.`valid_from` AND `tc_ltm`.`valid_until`
						),
						IF(
							`tc_ltm`.`valid_until` != '0000-00-00',
							:time <= `tc_ltm`.`valid_until`,
							:time >= `tc_ltm`.`valid_from`
						)
					)
					AND
					`tc_lm`.`active` = 1
					";
		$aSql = array();
		
		$oWDDate = new WDDate();
		$oWDDate->set($iTimepoint, WDDate::TIMESTAMP);
		
		$aSql['licence_id'] = (int)$this->id;
		$aSql['time'] = $oWDDate->get(WDDate::DB_DATE);
		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		$aBack = array();

		if(!empty($aResult)){
			foreach((array)$aResult as $aData){
				$aBack[] = Ext_TC_Licence_Module::getInstance($aData['id']);
			}
		}
		
		return $aBack;
	}
	
	/**
	 * Erzeugt einen neuen Lizenzschlüssel
	 * @return string
	 */
	public static function getNewLicence() {
		
		do {
			
			$sLicence1 = mb_substr(mb_strtoupper(md5('CORE')), 0, 4);
			$sLicence2 = Ext_TC_Util::generateRandomString(4);
			$sLicence3 = Ext_TC_Util::generateRandomString(4);
			$sLicence4 = Ext_TC_Util::generateRandomString(4);
			
			$sLicence = $sLicence1.'-'.$sLicence2.'-'.$sLicence3.'-'.$sLicence4;
			
		} while(self::checkLicenceExist($sLicence));
		
		return $sLicence;
		
	}
	
	/**
	 * Prüft, ob es eine Lizenz schon gibt
	 * @param string $sLicence
	 * @return bool
	 */
	public static function checkLicenceExist($sLicence) {
		
		$sSql = " SELECT COUNT(*) FROM `tc_licences` WHERE `licence` = :licence ";
		$aSql = array('licence'=>$sLicence);
		
		$sResult = DB::getQueryOne($sSql, $aSql);
		
		if($sResult == "0"){
			return false;
		}
		
		return true;
		
	}
	
	public static function getLicenceObject($sLicence){
		
		$sSql = " SELECT `id` FROM `tc_licences` WHERE `licence` = :licence ";
		$aSql = array('licence'=>$sLicence);

		$sResult = DB::getQueryOne($sSql, $aSql);
		
		if($sResult > 0){
			$oLicence = Ext_TC_Licence::getInstance($sResult);
			return $oLicence;
		}
		
		return false;		
	}
}
?>
