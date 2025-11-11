<?php

/**
 * Class Ext_TC_System_Font
 */
class Ext_TC_System_Font extends Ext_TC_Basic {

	/**
	 * Tabellenname
	 * @var string
	 */
	protected $_sTable		= 'tc_fonts';
	#protected $_sTableAlias	= 'kf';

	/**
	 * @var array
	 */
	protected $_aFileTypes = array(
		'font',
		'font_i',
		'font_b',
		'font_bi'
	);

	/**
	 * @var array
	 */
	protected $_aUploadFiles = array();

	/**
	 * @var string
	 */
	protected static $sCacheKey = 'Ext_TC_System_Font::getSelectOptions';
	
	/**
	 * Kann abgeleitet werden um eine andere Update Klasse zu holen
	 */
	protected function _getUpdateClass(){
		
		$oClass = new Ext_TC_Update('update');
		
		return $oClass;
	}
	
	/**
	 * Löschen inklusive Cache löschen und Schriftdateien speichern
	 */
	public function save($bLog = true) {

		ini_set('memory_limit', '1G');
		
		// Cache für Select löschen
		$sCacheKey = self::$sCacheKey;
		WDCache::delete($sCacheKey);

		foreach($this->_aFileTypes as $sFileTypeField) {

			$sFileType = str_replace(['font', '_'], '', $sFileTypeField);

			// Über Magic-Get geht nicht weil komisch abgeleitet
			$aFontData = $this->aData;

			$sFontPath = $this->getPath().$aFontData[$sFileTypeField];

			if(is_file($sFontPath)) {

				$oPdf = new Ext_TC_Pdf_Fpdi;

				// Den Namen der normal Schrift als Basis nehmen
				if(!empty($sFileType)) {
					$sNewFontPath = $this->getFontPath(false, $sFileType);
					rename($sFontPath, $sNewFontPath);
					$sFontPath = $sNewFontPath;
				}
				
				$sConvertedFont = TCPDF_FONTS::addTTFfont($sFontPath);

				if(empty($sConvertedFont)) {
					throw new RuntimeException('Converting of font "'.$sFontPath.'" not possible!');
				}
				
				$this->$sFileTypeField = $sConvertedFont.'.ttf';
				$sNewFontPath = $this->getFontPath(false, $sFileType);

				rename($sFontPath, $sNewFontPath);

			}

		}

		return parent::save($bLog);

//		$sDir			= $this->getPath();
//		$sDirTcpfFonts	= \Util::getDocumentRoot().'system/bundles/Pdf/Resources/fonts/';
//
//		$aPostFiles	= array();
//
//		$aFileTypes	= array(
//			'font',
//			'font_i',
//			'font_b',
//			'font_bi'
//		);
//		$sMainFontFile = $sDir.$this->font;
//
//		foreach($aFileTypes as $sFileType) {
//			$sFile = $sDir.$this->$sFileType;
//			if(is_file($sFile)) {
//				$aPostFiles[$sFileType] = new CURLFile($sFile);
//			} elseif(is_file($sMainFontFile)) {
//				$aPostFiles[$sFileType] = new CURLFile($sMainFontFile);
//			}
//		}
//
//		$bSuccess = true;
//
//		// Erst konvertieren beim zweiten Speichern
//		if(
//			!$bLog &&
//			!empty($aPostFiles)
//		) {
//
//			$sScriptSource	= '/fonts.php';
//			$sReturn		= '';
//			
//			//Updateklasse holen, je nach System!
//			$oUpdate = $this->_getUpdateClass();//new Ext_Thebing_Update('update');
//
//			if($oUpdate){
//				$sReturn = $oUpdate->getFileContents($sScriptSource, $aPostFiles);
//			}
//
//			$aReturn = unserialize($sReturn);
//
//			if(
//				empty($aReturn['error']) &&
//				!empty($aReturn['zip_content']) &&
//				!empty($aReturn['zip_name'])
//			) {
//				$sZipFile = $sDir.$aReturn['zip_name'];
//	
//				$rHandle = fopen($sZipFile,'wb');
//				if($rHandle)
//				{
//					$bContentAdded = fwrite($rHandle,$aReturn['zip_content']);
//					fclose($rHandle);
//	
//					if($bContentAdded)
//					{
//						$oZip = new ZipArchive();
//
//						$bZip = $oZip->open($sZipFile);
//
//						if($bZip === true)
//						{				
//							$bSuccessExtract = $oZip->extractTo($sDirTcpfFonts);
//							if($bSuccessExtract)
//							{
//								$oZip->close();
//								unlink($sZipFile);
//							} 
//							else
//							{
//								$bSuccess = false;
//							}
//						} 
//						else
//						{
//							$bSuccess = false;
//						}
//					}
//					else
//					{
//						$bSuccess = false;
//						unlink($sZipFile);
//					}
//				}
//				else
//				{
//					$bSuccess = false;
//				}
//			}
//			else
//			{
//				$bSuccess = false;
//			}
//		}
//
//		if(isset($aReturn['error']['file'])) {
//
//			foreach($aReturn['error']['file'] as $sFileErrorType => $aFontTypes) {
//
//				switch($sFileErrorType) {
//					case 'covert':
//						$sErrorMessage = 'Fehler beim konvertieren der Datei %s';
//						break;
//					case 'upload':
//						$sErrorMessage = 'Fehler beim hochladen der Datei %s';
//						break;
//					case 'file_infos':
//						$sErrorMessage = 'Fehlerhafte Dateiinfos bei der Datei %s';
//						break;
//					default:
//						$sErrorMessage = 'Fehler bei der Datei %s';
//						break;
//				}
//				
//				foreach($aFontTypes as $sFontType) {
//
//					$sFile = (string)$this->_aUploadFiles[$sFontType];
//					if(!empty($sFile)) {
//						//Das kann man machen, weil die Markierung des Uploadinputs sowieso nicht sichtbar ist
//						$aReturn['error'][$sFile] = $sErrorMessage;
//					}
//
//				}
//			}
//
//			unset($aReturn['error']['file']);
//		}
//
//		if($bSuccess) {
//			return true;
//		} else {
//			$aErrors = array();
//			$aErrors[] = 'Fehler beim konvertieren der Schriftarten';
//			#$aErrors	= array_merge($aErrors,(array)$aReturn['error']);
//			return $aErrors;
//		}
		
	}

	/**
	 * @param bool $bDocumentRoot
	 * @return string
	 */
	public static function getPath($bDocumentRoot = true)
	{
		$sDir = Ext_TC_Util::getSecureDirectory($bDocumentRoot).'fonts/';
		return $sDir;
	}

	/**
	 * @param bool $sExt
	 * @param string $sStyle
	 * @return string
	 */
	public function getFontPath($sExt=false,$sStyle='')	{

		$sMainDir		= $this->getPath();
		$sFontName		= $this->getFontName($sExt,$sStyle);
		$sFileName		= $sMainDir.$sFontName;

		return $sFileName;

	}

	/**
	 * @param bool $sExt
	 * @param string $sStyle
	 * @return bool|string
	 */
	public function getFontName($sExt=false,$sStyle='') {

		$sFont = $this->_aData['font'];
		if(empty($sFont)) {
			return false;
		}

		$sFontFileBase = mb_substr($sFont,0,-4);
		if($sExt===false) {
			$sExt = mb_substr($sFont,-4);
		}
		
		$sFileName = $sFontFileBase.$sStyle.$sExt;

		return $sFileName;
	}

	/**
	 * @param string $sName
	 * @return bool|mixed|string
	 */
	public function __get($sName) {

		if(
			in_array($sName, $this->_aFileTypes)
		) {
			$sStyle			= mb_substr($sName, 5);
			$sFontName		= $this->getFontName(false, $sStyle);
			$sFontFile		= $this->getFontPath(false, $sStyle);

			if(is_file($sFontFile)) {
				return $sFontName;
			} else {
				return null;
			}
		} else {
			return parent::__get($sName);
		}
	}

	/**
	 * @param string $sName
	 * @param mixed $mValue
	 */
	public function  __set($sName, $mValue) {

		if(in_array($sName, $this->_aFileTypes)) {
			$this->_aUploadFiles[$sName] = $mValue;
		}
		
		parent::__set($sName, $mValue);
	}

	/**
	 * @param bool $bThrowExceptions
	 * @return array|bool
	 */
	public function validate($bThrowExceptions = false) {

		$mReturn	= parent::validate($bThrowExceptions);
		$aErrors	= array();

		if($mReturn===true) {

			foreach($this->_aUploadFiles as $sKey => $sUploadedFile) {

				if(mb_strlen($sUploadedFile) > 0) {

					$bMatch = preg_match('/.*(.ttf)/i',$sUploadedFile);

					if(!$bMatch) {
						$sClientPath	= $this->getPath(false);
						$sFailedFile	= str_replace($sClientPath, '',$sUploadedFile);

						$aErrors[$sKey] = 'WRONG_FORMAT';
					}

				}

			}

			if(!empty($aErrors)) {
				return $aErrors;
			} else {
				return true;
			}

		} else {
			return $mReturn;
		}

	}

	/**
	 * {@inheritdoc}
	 */
	public function getArrayList($bForSelect = false, $sNameField = 'name', $bCheckValid = true, $bIgnorePosition = false) {

		$aList = parent::getArrayList($bForSelect, $sNameField, $bCheckValid, $bIgnorePosition);
		
		if($bForSelect === true) {
			$aDefaults = self::getDefaultFonts();
			$aList = $aDefaults + $aList;
			
			asort($aList);
			
		}
		
		return $aList;
		
	}
	
	/**
	 * Gibt ein Array mit den Standardschriften zurück
	 * @return string 
	 */
	public static function getDefaultFonts() {
		
		$aFonts = array();
		$aFonts['courier'] = "Courier";
		$aFonts['dejavusans'] = "DejaVuSans";
		$aFonts['dejavuserif'] = "DejaVuSerif";
		$aFonts['freemono'] = "FreeMono";
		$aFonts['freesans'] = "FreeSans";
		$aFonts['freeserif'] = "FreeSerif";
		$aFonts['helvetica'] = "Helvetica";
		$aFonts['times'] = "Times New Roman";

		return $aFonts;
		
	}
	
	/**
	 * Gibt ein Array mit allen Schriftarten zurück.
	 *
	 * @param bool $bForSelect
	 * @return Ext_TC_Accounting_Accountscode 
	 */
	
	public static function getSelectOptions($bForSelect = false)
	{
		$aList = WDCache::remember(self::$sCacheKey, 86400, function() {
			return (new Ext_TC_System_Font())->getArrayList(true);
		});

		if($bForSelect){
			$aList = Ext_TC_Util::addEmptyItem($aList);
		}
		
		return $aList;
	}	
	
}
