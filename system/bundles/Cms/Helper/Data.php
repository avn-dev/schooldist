<?php

namespace Cms\Helper;

class Data {

	function getWebSiteLanguages($bolShort = 1, $bolOnlyActive = 1) {
		$strOnlyActive = "";
		if($bolOnlyActive) {
			$strOnlyActive = " l.active = 1 AND ";
		}
		if($bolShort) {
			$strSql = "
				SELECT
					l.code
				FROM
					cms_sites_languages l 
				WHERE
					".$strOnlyActive."
					1
				GROUP BY
					l.code
				ORDER BY
					l.position ASC
				";
		} else {
			$strSql = "
				SELECT
					*
				FROM
					cms_sites_languages l
				WHERE
					".$strOnlyActive."
					1
				GROUP BY
					l.code
				ORDER BY
					l.position ASC
				";
		}
		$arrLanguages = $this->getEntries($strSql);

		return $arrLanguages;

	}

	function getSites() {
		
		$strSql = "
			SELECT
				s.id, 
				s.name, 
				s.email,
				s.redirect_to_domain,
				s.force_https,
				d.domain
			FROM
				`cms_sites` `s` LEFT JOIN
				`cms_sites_domains` `d` ON
					`s`.`id` = `d`.`site_id` AND
					`d`.`master` = 1
			WHERE
				`s`.`active` = 1
			ORDER BY
				`s`.`position`
			";
		$arrSites = \DB::getQueryData($strSql);

		$arrTemp = $arrSites;
		$arrSites = array();
		foreach((array)$arrTemp as $arrSite) {
			$arrSites[$arrSite['id']] = $arrSite;
		}

		return $arrSites;
	}

	function getSiteFromDomain($strHttpHost) {
		$strSql = "
			SELECT
				s.id, 
				s.name, 
				s.email,
				s.redirect_to_domain,
				s.force_https,
				d.domain
			FROM
				`cms_sites` s,
				`cms_sites_domains` d
			WHERE
				s.id = d.site_id AND
				d.domain = :strDomain AND
				s.active = 1 AND
				d.active = 1
			";
		$arrTransfer = array('strDomain'=>$strHttpHost);
		$arrSites = \DB::getPreparedQueryData($strSql, $arrTransfer);
		if(is_array($arrSites)) {
			return $arrSites[0];
		}
	}

	function getLanguageData() {

		$strSql = "
				SELECT
					*
				FROM
					language_data 
				WHERE 
					1
				ORDER BY
					file_id, de
					";
		$arrData = $this->getEntries($strSql);
		
		return $arrData;
		
	}

	function getCssFiles() {

		$strSql = "
			SELECT
				*
			FROM
				`cms_styles_files`
			ORDER BY
				`name`
			";

		$arrFiles = \DB::getQueryRows($strSql);

		return $arrFiles;

	}

	function getWebSiteTranslations($strLanguage = false /*, $iOffset = 0, $iLimit = 50*/) {
		global $system_data;
		
		$strSql = "
					SELECT 
						* 
					FROM
						system_translations
					";
		if($strLanguage == false) {
			$strSql .= "
					ORDER BY
						`code` ASC";
		}
		/*
		$strSql .= "
			LIMIT ".$iOffset.", ".$iLimit."
		";
		*/
		$arrData = \DB::getQueryData($strSql);
		
		$arrTranslations = array();
		
		if($strLanguage) {
			$strLanguage = preg_replace("/[^a-z]/", "", $strLanguage);
			foreach($arrData as $arrItem) {
				// check translation
				$strItem = $arrItem[$strLanguage];
				// if translation not found, get original language
				if($strItem == "") {
					// add prefix
					$strItem = '['.strtoupper($strLanguage).'] '.$arrItem[$system_data['arrLanguages'][0]];
				}
				$arrTranslations[$arrItem['id']] = $strItem;
				$arrTranslations[$arrItem['code']] = $strItem;
			}
		} else {
			foreach($arrData as $arrItem) {
				$arrTranslations[$arrItem['id']] = $arrItem;
			}
		}

		return $arrTranslations;
		
	}

	function getSiteData($intSiteId) {
		global $session_data;
		
		if(isset($session_data['site_data']) && isset($session_data['site_data'][$intSiteId])) {

			return $session_data['site_data'][$intSiteId];

		} else {
			
			$strSql = "
					SELECT 
						*
					FROM
						cms_sites s,
						cms_sites_domains d
					WHERE
						s.id = :intSiteId AND
						s.id = d.site_id AND
						d.master = 1
					LIMIT 1 
					";
			$arrTransfer = array("intSiteId"=>$intSiteId);
			$arrData = \DB::getPreparedQueryData($strSql, $arrTransfer);
			$session_data['site_data'][$intSiteId] = $arrData[0];

			return $arrData[0];
		}
	}

	function getPageData($intPageId) {
		global $session_data;
		
		if(
			isset($session_data['page_data']) && 
			isset($session_data['page_data'][$intPageId])
		) {

			return $session_data['page_data'][$intPageId];

		} else {

			$strSql = "
					SELECT 
						*,
						`language` `original_language`
					FROM
						cms_pages
					WHERE
						id = :intPageId
					LIMIT 1 
					";
			$arrTransfer = array("intPageId"=>(int)$intPageId);
			$arrData = \DB::getPreparedQueryData($strSql, $arrTransfer);
			$session_data['page_data'][$intPageId] = $arrData[0];

			return $arrData[0];

		}
		
	}

	function getEntries($strSql) {

		$aResult = (array)\DB::getQueryRows($strSql);

		$aResult = array_map(function($aRow) {
			if(count($aRow) > 2) {
				return $aRow;
			}
			return reset($aRow);
		}, $aResult);

		return $aResult;

	}
	
	static public function setSessionSiteId(int $iSiteId) {
		\Core\Handler\SessionHandler::getInstance()->set('cms_site_id', $iSiteId);
	}
	
	static public function getSessionSiteId() {
		return \Core\Handler\SessionHandler::getInstance()->get('cms_site_id');
	}
	
}
