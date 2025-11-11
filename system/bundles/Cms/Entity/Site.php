<?php

namespace Cms\Entity;

class Site extends \WDBasic {

	protected $_sTable = 'cms_sites';

	protected $_aJoinTables = [
		'domains' => [
			'table'=>'cms_sites_domains',
			'foreign_key_field'=>'domain',
			'primary_key_field'=>'site_id',
			'autoload'=>false,
			'check_active'=>true,
			'readonly' => true,
		],
		'main_domains' => [
			'table'=>'cms_sites_domains',
			'foreign_key_field'=>'domain',
			'primary_key_field'=>'site_id',
			'static_key_fields' => ['master' => 1],
			'autoload'=>false,
			'check_active'=>true,
			'readonly' => true,
		]
	];

	static protected $aLanguagesCache = [];

	public function getLanguages($iReturnMode=0, $bOnlyActive=1) {
		
		$sCacheKey = 'Site::getLanguages:'.$this->id.':'.$iReturnMode.':'.$bOnlyActive;
		
		if(!array_key_exists($sCacheKey, self::$aLanguagesCache)) {

			$sWhere = "";

			if($bOnlyActive) {
				$sWhere .= " AND
						active = 1";
			}

			$sSql = "
					SELECT
						*
					FROM
						cms_sites_languages 
					WHERE
						site_id = :site_id
						".$sWhere."
					ORDER BY
						position ASC
					";
			$aSql = array('site_id'=>(int)$this->id);
			$aLanguages = \DB::getPreparedQueryData($sSql, $aSql);

			// copy entries of site 1 if array is empty
			if(empty($aLanguages)) {

				if($this->id > 1) {

					$oSite = self::getInstance(1);
					$aLanguages = $oSite->getLanguages();

					foreach((array)$aLanguages as $aLanguage) {
						$sSql = "INSERT INTO 
									cms_sites_languages
								SET
									`changed` = NOW(), 	 	 	
									`created` = NOW(),		 	 	 	 	 	 	
									`active` = :active, 	 	 	 	 	 	
									`site_id` = :site_id,
									`name` = :name,
									`code` = :code,
									`charset` = :charset,
									`locale` = :locale,
									`position` = :position
									";
						$aSql = array();
						$aSql['active'] = $aLanguage['active'];	 	 	 	 	 	 	
						$aSql['site_id'] = $this->id;
						$aSql['name'] = $aLanguage['name'];
						$aSql['code'] = $aLanguage['code'];
						$aSql['charset'] = $aLanguage['charset'];
						$aSql['locale'] = $aLanguage['locale'];
						$aSql['position'] = $aLanguage['position'];
						\DB::executePreparedQuery($sSql, $aSql);
					}

				} 

			}

			if($iReturnMode == 1) {

				foreach((array)$aLanguages as $iKey=>$aLanguage) { 
					$aLanguages[$iKey] = $aLanguage['code'];
				}

			}

			self::$aLanguagesCache[$sCacheKey] = $aLanguages;

		}
		
		return self::$aLanguagesCache[$sCacheKey];
	}
	
	public function getLanguage($sCode) {
		
		$sSql = "
				SELECT
					*
				FROM
					cms_sites_languages 
				WHERE
					site_id = :site_id AND
					code = :code AND
					active = 1
				ORDER BY
					position ASC
				";
		$aSql = array('site_id'=>$this->id, 'code'=>$sCode);
		$aLanguages = \DB::getPreparedQueryData($sSql, $aSql);
		
		return $aLanguages[0];
		
	}
	
	public function getPageStructure($sLanguage, $sPattern='', $iLevel=0) {
		
		$aStructure = [];
		
		$sSql = "
			SELECT 
				*
			FROM 
				cms_pages 
			WHERE 
				site_id = :site_id AND 
				(
					(
						path LIKE :path_pattern AND 
						(
							file = 'index' OR 
							file = ''
						) AND 
						level = :level2
					) OR (
						path LIKE :path_pattern AND 
						file != 'index' AND 
						file != '' AND 
						level = :level
					)
				) AND 
				(
					language = :language OR 
					language = ''
				) AND 
				element != 'template' AND 
				active != 2 
			GROUP BY 
				CONCAT(path,file) 
			ORDER BY 
				position, id
		";
		$aSql = [
			'site_id' => $this->id,
			'path_pattern' => $sPattern.'%',
			'level' => $iLevel,
			'level2' => $iLevel+1,
			'language' => $sLanguage
		];
		
		$aItems = \DB::getQueryRows($sSql, $aSql);

		if(!empty($aItems)) {
		
			foreach($aItems as $aItem) {

				$oPage = Page::getObjectFromArray($aItem);

				$aAdd = [
					'page' => $oPage
				];
				if(
					$aItem['file'] === 'index' ||
					$aItem['file'] === ''
				) {
					$aAdd['childs'] = $this->getPageStructure($sLanguage, $aItem['path'], $iLevel+1);
				}

				$aStructure[] = $aAdd;

			}

		}

		return $aStructure;
	}
	
	public function getMainDomain() {
		
		$aMainDomains = $this->main_domains;
		
		if(!empty($aMainDomains)) {
			return reset($aMainDomains);
		}
		
	}
	
	public function getFullDomain() {
		
		$sFullDomain = '';
		
		if($this->force_https) {
			$sFullDomain .= 'https://';
		} else {
			$sFullDomain .= 'http://';
		}
		$sFullDomain .= $this->getMainDomain();
		
		return $sFullDomain;
	}
	
}