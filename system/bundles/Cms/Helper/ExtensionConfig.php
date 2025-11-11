<?php

namespace Cms\Helper;

// Konfigurationsklasse zum Modulconfig-handling
class ExtensionConfig {

	// Constructor
	function __construct($page_id, $element_id, $content_id, $sLanguage) {
		
		$objWebDynamicsDAO = new Data();

		$page_id_sec 	=	\DB::escapeQueryString($page_id);
		$element_id_sec =	\DB::escapeQueryString($element_id);
		$content_id_sec =	\DB::escapeQueryString($content_id);
		$sLanguage 		= \DB::escapeQueryString($sLanguage);

		if(
			$page_id != -1 && 
			$element_id != -1
		) {
			
		 	$aPage = $objWebDynamicsDAO->getPageData($page_id);
		 	if(!$aPage['localization']) {
		 		$sLanguage = '';
		 	}
			
			$sSql = "SELECT param FROM cms_extensions_config WHERE page_id = ".(int)$page_id_sec."";

			if(empty($content_id_sec)) {
				$sSql .= " AND element_id = ".(int)$element_id_sec." ";
			} else {
				$sSql .= " AND content_id = ".(int)$content_id_sec." ";	
			}

			$sSql .= " AND (language = '".$sLanguage."' OR language = '') ORDER BY content_id DESC, language DESC LIMIT 1";
			$my_conf = \DB::getQueryRow($sSql);
			
			if(empty($my_conf)) {
				
				$sSql = "SELECT param FROM cms_extensions_config WHERE page_id = ".(int)$page_id_sec."";

				$sSql .= " AND element_id = ".(int)$element_id_sec." ";

				$sSql .= " AND (language = '".$sLanguage."' OR language = '') ORDER BY content_id DESC, language DESC LIMIT 1";
				$my_conf = \DB::getQueryRow($sSql);
			}
			
			if($my_conf) {
				$mTmp = \Util::decodeSerializeOrJson($my_conf['param']);
				if(is_array($mTmp)) {
					foreach($mTmp as $strKey=>$mixValue) {
						$this->$strKey = $mixValue;
					}
				} elseif(
					is_object($mTmp) ||
					!empty(get_object_vars($mTmp))
				) {
					foreach((array)get_object_vars($mTmp) as $strKey=>$mixValue) {
						$this->$strKey = $mixValue;
					}
				}
			}

		}

	}

	function save_config($page_id, $element_id, $content_id = 0, $sLanguage='') {
		
		$objWebDynamicsDAO = new Data();

		$page_id_sec 	=	\DB::escapeQueryString($page_id);
		$element_id_sec =	\DB::escapeQueryString($element_id);
		$content_id_sec	=	\DB::escapeQueryString($content_id);
		$sLanguage 		= \DB::escapeQueryString($sLanguage);
		$param_sec 		=	\DB::escapeQueryString(json_encode(get_object_vars($this)));

	 	$aPage = $objWebDynamicsDAO->getPageData($page_id);
	 	if(!$aPage['localization']) {
	 		$sLanguage = '';
	 	}

		$sSql = "SELECT param FROM cms_extensions_config WHERE page_id = '$page_id_sec' AND element_id = '$element_id_sec' AND content_id = '$content_id_sec' AND language = '".$sLanguage."' LIMIT 1";
		$my_conf = \DB::getQueryRow($sSql);

		if($my_conf) {
			$sSql = "UPDATE cms_extensions_config SET param='$param_sec' WHERE page_id = '$page_id_sec' AND element_id = '$element_id_sec' AND content_id = '$content_id_sec' AND language = '".$sLanguage."'";
		} else {
			$sSql = "INSERT INTO cms_extensions_config (param, page_id, element_id, content_id, language) VALUES ('$param_sec', '$page_id_sec', '$element_id_sec', '$content_id_sec', '".$sLanguage."')";
   		}

		$result_conf = \DB::executeQuery($sSql);

	}

}
// ENDE Konfigurationsklasse zum Modulconfig-handling
