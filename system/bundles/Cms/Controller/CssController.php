<?php

namespace Cms\Controller;

class CssController extends \MVC_Abstract_Controller {

	protected $_sInterface = 'frontend';
	protected $_sAccessRight = null;

	public function outputFile($sFile) {

		$sCssFile = $sFile.'.css';

		ob_start();

		$sScriptInclude = \System::d('include_script');
		
		// Wenn Individuelles Script Include
		if (
			!empty($sScriptInclude) && 
			file_exists(\Util::getDocumentRoot().$sScriptInclude)
		) {
			include_once(\Util::getDocumentRoot().$sScriptInclude);
		}

		$sWhere = "";
		$aSql = array(
			'sFile'		=> $sCssFile
		);
		
		$sMedia = $this->_oRequest->get('media');

		if(!empty($sMedia)) {
			$sWhere = " `media` IN(:sMedia, 'both') AND ";
			$aSql['sMedia'] = $sMedia;
		}

		// einfügen der Benutzerdefinierten Stylesheets aus der Datenbank
		$strSql = "
			SELECT
				*,
				UNIX_TIMESTAMP(`changed`) `changed`
			FROM
				`cms_styles`
			WHERE
				`file` IN(:sFile, '') AND
				" . $sWhere . "
				`active` = 1
			ORDER BY
				`position`,
				`name`
		";
		$style_aufgabe = (array)\DB::getPreparedQueryData($strSql, $aSql);

		$iMaxChanged = 0;

		$aParts = array(
			'both'		=> '',
			'print'		=> "@media print {\n",
			'screen'	=> "@media screen {\n"
		);

		foreach($style_aufgabe as $style_array)
		{
			switch($style_array['media'])
			{
				case 'print':
				case 'screen':
					$sKey = $style_array['media'];
					break;
				default:
					$sKey = 'both';
			}

			if($style_array['type'] === 'individuell') {
				$aParts[$sKey] .= "\n." . $style_array['name'] . ' {';
			} else { 
				$aParts[$sKey] .= "\n" . $style_array['name'] . ' {';
			}
			$aParts[$sKey] .= "\n\t" . $style_array['style'] . "\n}\n";

			$iMaxChanged = max($iMaxChanged, $style_array['changed']);
		}

		$aParts['print'] .= "}\n";
		$aParts['screen'] .= "}\n";

		if(!empty($aParts['both'])) {
			echo $aParts['both'];
		}
		if(!empty($aParts['print'])) {
			echo $aParts['print'];
		}
		if(!empty($aParts['screen'])) {
			echo $aParts['screen'];
		}

		if($iMaxChanged == 0) {
			$iMaxChanged = time();
		}

		if (
			\System::d('debugmode') ||
			(
				$this->_oAccess instanceof \Access_Backend &&
				$this->_oAccess->cms === true
			)
		) {
			include(__DIR__.'/../Resources/public/css/page.css');
		}

		$sTempContent = ob_get_contents();
		ob_end_clean();

		$strContent = \Cms\Service\ReplaceVars::execute($sTempContent);
		$strContent = \Util::compressCssOutput($strContent);

		/**
		 * Änderungszeitpunkt anpassen, wenn Variablen verwendet werden
		 */
		if($strContent != $sTempContent) {
			$iMaxChanged = time();
		}

		/**
		 * GZIP compression
		 */
		ob_start("ob_gzhandler");

		/**
		 * Header for caching
		 */
		$iLifetime = 60*60*1;
		$sExpGmt = gmdate("D, d M Y H:i:s", time() + $iLifetime) ." GMT";
		$sModGmt = gmdate("D, d M Y H:i:s", $iMaxChanged) ." GMT";
		$sEtag = md5($sModGmt);

		header("Content-type: text/css");
		header("Expires: " . $sExpGmt);
		header("Last-Modified: " . $sModGmt);
		header("Cache-Control: private, must-revalidate, max-age=" . $iLifetime);
		header("Pragma: private");
		header("ETag: ".$sEtag);

		/**
		 * 304
		 */
		$bIdMatch = false;
		if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
			if($_SERVER['HTTP_IF_NONE_MATCH'] == $sEtag) {
				$bIdMatch = true;
			}
		}

		$bModifiedSince = false;
		if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
			$iModtimeCache = @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
			if($iMaxChanged <= $iModtimeCache) {
				$bModifiedSince = true;
			}
		}

		if(
			$bIdMatch ||
			$bModifiedSince
		) {
			header("HTTP/1.1 304 Not Modified");
			header('Connection: close');
			exit;
		} else {
			header('Connection: close');
			echo $strContent;
			flush();
			exit;
		}
	}

}