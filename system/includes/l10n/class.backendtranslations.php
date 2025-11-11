<?php

class L10N_BackendTranslations extends WDBasic {

	protected $_sTable = 'language_data';
	
	protected $_sTableAlias = 'ld';
	
	protected $_aJoinTables = [
		'external_services' => [
			'table' => 'language_data_external',
			'primary_key_field' => 'language_data_id',
			'foreign_key_field' => ['language', 'service'],
			'autoload' => false
		]
	];
	
	protected $_aFormat = array(

	);

	protected $_bAutoFormat = true;

	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		if($mValidate === true) {

			$mValidate = array();

			if(
				$this->file_id == 0 &&
				$this->use == 0
			) {
				$mValidate[] = 'GLOBAL_TRANSLATION_USE_ERROR';
			}

		}

		if(empty($mValidate))
		{
			return true;
		}

		return $mValidate;

	}

	public function delete() {

		$sSql = "
				DELETE FROM
					#table
				WHERE
					`id` = :id
				LIMIT 1
			";
		$aSql = array('table'=>$this->_sTable, 'id'=>$this->id);
		DB::executePreparedQuery($sSql, $aSql);

		return true;

	}

	public function getExternalServices() {
		return $this->external_services;
	}
	
	public function setExternalService($sLanguage, $sService) {
		
		$aServices = $this->external_services;
		$bFound = false;
		
		foreach($this->external_services as $iIndex => $aService) {
			if($aService['language'] === $sLanguage) {
				$aServices[$iIndex]['service'] = $sService;
				$bFound = true;
				break;
			}
		}
		
		if(!$bFound) {
			$aServices[] = [
				'language' => $sLanguage,
				'service' => $sService
			];
		}
		
		$this->external_services = $aServices;		
	}
	
	public function verifyExternalTranslation($sLanguage) {
		$aServices = $this->external_services;
		
		$iVerified = 0;
		
		foreach($this->external_services as $iIndex => $aService) {
			if($aService['language'] === $sLanguage) {
				$iVerified = ($aServices[$iIndex]['verified'] == 0) ? 1 : 0;
				$aServices[$iIndex]['verified'] = $iVerified;
				break;
			}
		}
		
		$this->external_services = $aServices;	
		
		return (bool) $iVerified;
	}
	
	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		$aSqlParts['select'] .= "
			, GROUP_CONCAT(DISTINCT CONCAT(`lde`.`language`, '{|}', `lde`.`verified`) SEPARATOR '{||}') `verification`
		";
		
		$aSqlParts['from'] .= " LEFT JOIN
			`language_data_external` `lde` ON
				`lde`.`language_data_id` = `ld`.`id`
		";
		
	}
	
}