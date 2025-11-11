<?php

class Ext_TC_L10N extends L10N {

	 static public function getInstance($sLang = null) {

		if($sLang === null){
			$sLang = System::getInterfaceLanguage();
		}
		
		$sInterface = System::wd()->getInterface();
		
 		if(!isset(self::$aInstance[$sLang][$sInterface]))  {
			self::$aInstance[$sLang][$sInterface] = new Ext_TC_L10N($sLang);
		}
		
		return self::$aInstance[$sLang][$sInterface];

	}
	
	static protected $_aTCache = array();

	/**
	 * @global array $session_data
	 * @param string $sTranslation
	 * @param string $sLang
	 * @param string $sDescription
	 * @return string
	 */
	static public function t($sTranslation, $sLang = '', $sDescription = 0) {
		global $session_data;
		
		$sCacheKey = md5($sTranslation.'_'.$sLang.'_'.$sDescription);
		$aCache = static::$_aTCache;
		
		if(!isset($aCache[$sCacheKey])){

			$sInterface = System::getInterface();

			if($sInterface === 'backend') {

				if(mb_strlen($sLang) > 2) {

					throw new \RuntimeException('$sLang not a 2-code-lang or empty!');

					$sHtml = "
						2ter Parameter (".$sLang.") muss eine Sprache sein! Oder '' (nichts) <br/><br/>
						Übersetzung:" . $sTranslation . " <br/><br/>
						Backtrace: <br/> 
						<pre>
						" . print_r(Util::getBacktrace(), 1) . '
						</pre>';
					__out($sHtml,1);
//					$oMail = new WDMail();
//					$oMail->subject = "Thebing Core L10N Fehler! Unbedingt Beheben!!!";
//					$oMail->html = $sHtml;
//
//					$oMail->send(['thebing@p32.de']);
					$sDescription = $sLang;
					$sLang = '';
				}
			
				// Prüfen ob die angefragte Sprache überhaupt als Backendsprache vorkommt!
				// wenn nicht dann muss es auf eine Backendsprache umgeswitchted werden
				// da wir uns hier an keinem gesetzten wert Orientieren könne ( da ggf. die Interface Lang auch nicht passt )
				// müssen wir auf die erste Sprache switchen die es gibt
				$aAllowedLanguages = System::getBackendLanguages(true);
				if(
					!empty($sLang) &&
					!key_exists($sLang, $aAllowedLanguages)
				) {
					reset($aAllowedLanguages);
					$sLang = key($aAllowedLanguages);
				}
			}

			$oL10N = self::getInstance($sLang);
			$sTranslate = $oL10N->translate($sTranslation, $sDescription, false);
			
			static::$_aTCache[$sCacheKey] = $sTranslate;
		}

		return static::$_aTCache[$sCacheKey];

	}

	/**
	 * Erzwungende Frontend-Übersetzung
	 *
	 * @param string $sTranslation
	 * @param string $sContext
	 * @param string $sLanguage
	 * @return string
	 */
	public static function tf($sTranslation, $sContext, $sLanguage) {

		// Leerer String muss nicht übersetzt werden
		if(empty($sTranslation)) {
			return $sTranslation;
		}

		if(empty($sContext)) {
			throw new InvalidArgumentException('Context for future usage missing!');
		}

		// Sprache muss übergeben werden
		if(empty($sLanguage)) {
			throw new InvalidArgumentException('Language missing!');
		}

		$sInterface = \System::getInterface();
		\System::setInterface('frontend');
		$oL10N = self::getInstance($sLanguage);
		\System::setInterface($sInterface);

		return $oL10N->translate($sTranslation);

	}
	
	/**
	 * This funktion set Addsleshes if the File is an .js.php File
	 * @param $sTranslation
	 * @param $bUseFileId
	 * @param $bAddslashes
	 * @return unknown_type
	 */
	public function translate($sTranslation, $bUseFileId = false, $bAddslashes = false) {

		// Leerer String muss nicht übersetzt werden
		if(empty($sTranslation)) {
			return $sTranslation;
		}

		$arrStr = explode("/", $_SERVER['SCRIPT_NAME'] );
		$arrStr = array_reverse($arrStr );
		
		$sFileName = $arrStr[0];

		if(
			mb_strpos($sFileName,'.js.php') > 0 || 
			$bAddslashes == true
		) {
			$bAddslashes = true;
		} else {
			$bAddslashes = false;
		}

		$mTrans = parent::translate($sTranslation, $bUseFileId, $bAddslashes);

		return $mTrans;

	}

	/**
	 * Gibt das entsprechende Label für den ersten / leeren Eintrag in Selects zurück
	 * @param string $sKey
	 * @return string
	 */
	public static function getEmptySelectLabel($sKey) {

		$sFileKey = 'Select fields';

		switch($sKey) {
			case 'all_categories':
				$sLabel = L10N::t('Alle Kategorien', $sFileKey);
				break;
			case 'nationalities':
				$sLabel = L10N::t('Keine Nationalität', $sFileKey);
				break;
			case 'genders':
				$sLabel = L10N::t('Kein Geschlecht', $sFileKey);
				break;
			case 'all':
				$sLabel = L10N::t('Alle', $sFileKey);
				break;
			case 'please_choose':
				$sLabel = L10N::t('Bitte wählen', $sFileKey);
				break;
			case 'levels':
				$sLabel = L10N::t('Kein Niveau', $sFileKey);
				break;
			case 'teacher_cost_category':
				$sLabel = L10N::t('Keine Kostenkategorie', $sFileKey);
				break;
			case 'course_category':
				$sLabel = L10N::t('Keine Kurskategorie', $sFileKey);
				break;
			case 'agency':
				$sLabel = L10N::t('Keine Agentur', $sFileKey);
				break;
			case 'room':
				$sLabel = L10N::t('Kein Raum', $sFileKey);
				break;
			case 'teacher':
				$sLabel = L10N::t('Kein Lehrer', $sFileKey);
				break;
			case 'country':
				$sLabel = L10N::t('Kein Land', $sFileKey);
				break;
			case 'languages':
				$sLabel = L10N::t('Keine Sprache', $sFileKey);
				break;
			case 'attendance':
				$sLabel = L10N::t('Anwesenheit', $sFileKey);
				break;
			case 'visum':
				$sLabel = L10N::t('Visa', $sFileKey);
				break;
			case 'advertency':
				$sLabel = L10N::t('Aufmerksam', $sFileKey);
				break;
			case 'correspondence_language':
				$sLabel = L10N::t('Korrespondenzsprache', $sFileKey);
				break;
			default:
				break;
		}

		return (string)$sLabel;

	}
	
	/**
	 * Überschreibt Übersetzungen mit individuellen Einträgen
	 * @see Hook
	 * @param array $aMixed
	 */
	public static function getIndividualTranslations($aMixed) {
		
		$oL10N = $aMixed['l10n'];
		$aTranslations =& $aMixed['translations'];
		$iFileId = $aMixed['file_id'];

		// Backend
		if($oL10N->_sDatabaseTable === 'language_data') {

			$aSql = array();
			$aSql['language'] = $oL10N->sLanguage;
			$aSql['default_language'] = $aMixed['default_language'];
			$aSql['code_field'] = $aMixed['code_field'];

			$sSelectAddon = self::getSelectAddon($oL10N->sLanguage, $aMixed['default_language'], $aMixed['code_field']);

			$sSql = "
				SELECT 
					`file_id`, ".$sSelectAddon." 
				FROM 
					#table `t`
				WHERE
					`file_id` = :file_id AND
					`active` = 1
				";

			$aSql['file_id'] = (int)$iFileId;
			$aSql['table'] = 'tc_language_data';

			$aData = (array)DB::getQueryRows($sSql, $aSql);
			
			foreach($aData as $aItem) {
				(int)$iNewKey = crc32($aItem[$aMixed['code_field']]);
				$aTranslations[$iNewKey] = $aItem[$oL10N->sLanguage];
			}

		}

	}

}
