<?PHP


class Ext_Thebing_Data{

	public static $aCacheLanguageSkills = array();

	public static function getBankList($bForSelect = false){
		global $user_data;
		$sSql = "SELECT 
					* 
				FROM 
					#table 
				WHERE 
					`idClient` = :client 
				AND 
					`idSchool` = :idSchool 
				AND 
					`active` = 1";
		$aSql = array(
						'table'=>'kolumbus_banks',
						'client'=>(int)$user_data['client'],
						'idSchool'=>(int)\Core\Handler\SessionHandler::getInstance()->get('sid')
		);

		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		if($bForSelect){
			$aReturn = array();
			foreach((array) $aResult as $aData){
				$aReturn[$aData['id']] = $aData['name'];
			}
			return $aReturn;
		}else{
			return $aResult;
		}
	}


	public static function getPaymentList($bForSelect = false, $iSchoolId = 0, $iClientId = 0){

		if(empty($iSchoolId)) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		} else {
			$oSchool = Ext_Thebing_School::getInstance($iSchoolId);
		}

		$aBack = $oSchool->getPaymentMethodList($bForSelect);

		return $aBack;

	}

	public static function getDistance($mLanguage = '') {
		
		$mLanguage = Ext_Thebing_Util::getLanguageObject($mLanguage, 'Thebing » Accommodation » Matching');

		$aReturn = array(
			0 => $mLanguage->translate('Keine Distanz'), 
			1 => $mLanguage->translate('Nah'), 
			2 => $mLanguage->translate('Mittel'), 
			3 => $mLanguage->translate('Weit')
		);

		return $aReturn;
	}
	
	public static function getFamilyAge($mLanguage = '') {

		$mLanguage = Ext_Thebing_Util::getLanguageObject($mLanguage);

		$aReturn = array(
			0 => $mLanguage->translate('Kein Familienalter'),
			1 => $mLanguage->translate('Jung'),
			2 => $mLanguage->translate('Mittel'),
			3 => $mLanguage->translate('Alt')
		);

		return $aReturn;
	}
	
	public static function getLanguageSkills($bForSelects = true, $sLanguage=false, $bAddEmptyItem=true) {

		if($sLanguage === false) {
			$sLanguage = Ext_TC_System::getInterfaceLanguage();
		}

		if(empty($sLanguage)) {
			$sLanguage = 'en';
		}

		$sCacheKey = 'Ext_Thebing_Data::getLanguageSkills_'.(int)$bForSelects.'_'.$sLanguage.'_'.(int)$bAddEmptyItem;
		
		if(empty(self::$aCacheLanguageSkills[$sCacheKey])){

			$aLangSkillList = (array)WDCache::get($sCacheKey);
			
			if(empty($aLangSkillList)) {

				$sNameField = 'name_'.$sLanguage;

				/**
				 * Prüfen, ob Sprache (≙Spalte) existiert in dieser Struktur.
				 * Ansonsten würde es eine Exception geben.
				 * DG
				 */
				$oDb = DB::getDefaultConnection();
				$bCheck = $oDb->checkField('data_languages', $sNameField);

				if(!$bCheck) {
					$sNameField = 'name_en';
				}		

				$sSql = "SELECT
							*
						FROM
							#table ORDER BY #name_field, id DESC ";

				$aSql = array(
					'table'=>'data_languages', 
					'name_field'=>$sNameField
				);

				$aLangSkillList = DB::getPreparedQueryData($sSql,$aSql);

				if($bForSelects == true){
					$aSelect = array();
					foreach($aLangSkillList as $aValue){
						$aSelect[$aValue['iso_639_1']] = $aValue[$sNameField];
					}

					// Leeren Eintrag aus der Datenbank entfernen
					unset($aSelect['aa']);

					if($bAddEmptyItem) {
						$aSelect = Ext_Thebing_Util::addEmptyItem($aSelect, Ext_Thebing_L10N::getEmptySelectLabel('languages'));
					}

					$aLangSkillList = $aSelect;
				}
				
				
				self::$aCacheLanguageSkills[$sCacheKey] = $aLangSkillList;
				
				WDCache::set($sCacheKey, 86400, $aLangSkillList);
				
			}else{
				self::$aCacheLanguageSkills[$sCacheKey] = $aLangSkillList;
			}


		}else{
			$aLangSkillList = self::$aCacheLanguageSkills[$sCacheKey];
		}

		return $aLangSkillList;
	}

	public static function getContacts($iParentId){
		
		$sSql = "
				SELECT
					*
				FROM
					`ts_companies_contacts`
				WHERE
					`company_id` = :company_id AND
					`active` = 1
				";
					
		$aSql = array('company_id'=>(int)$iParentId);
		

		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		$aBack = array();
		foreach((array)$aResult as $aKontakt){
			$aBack[$aKontakt['id']] = $aKontakt;
		}
		return $aBack;
	}

	/**
	 * Kann nur im Backend verwendet werden
	 *
	 * @deprecated
	 */
	public static function getCountryList($bForSelect = true, $mOptParam = false, $sLanguage=false) {
		global $_VARS, $system_data;

		if($sLanguage === false) {
			#$sLanguage = Ext_Thebing_School::fetchInterfaceLanguage();
			//musste geändert werden wegen t-3044
			$sLanguage = $system_data['systemlanguage'];
		}

		if(empty($sLanguage)) {
			$sLanguage = 'en';
		}

		$aCountries = Ext_Thebing_Country_Search::getLocalizedCountries($sLanguage);

		asort($aCountries);

		if($mOptParam === true) {
			$aOptParam = array('' => Ext_Thebing_L10N::getEmptySelectLabel('country'));
		} elseif($mOptParam === false) {
			$aOptParam = array();
		} else {
			$aOptParam = (array)$mOptParam;
		}

		$aCountries = (array)$aOptParam + (array)$aCountries;

		return $aCountries;

	}
	

	public static function getTransferList($mLanguage='', $bFrontend=false) {

		if(empty($mLanguage)) {
			$mLanguage = Ext_TC_System::getInterfaceLanguage();
		}

		if(!$mLanguage instanceof \Tc\Service\LanguageAbstract) {
			if($bFrontend === false) {
				$mLanguage = new \Tc\Service\Language\Backend($mLanguage);
				$mLanguage->setContext('Thebing » Transfer');
			} else {
				$mLanguage = new \Tc\Service\Language\Frontend($mLanguage);
			}
		}

		$aTransferList = array(
			Ext_TS_Inquiry_Journey::TRANSFER_MODE_NONE => $mLanguage->translate('nicht gewünscht'),
			Ext_TS_Inquiry_Journey::TRANSFER_MODE_ARRIVAL => $mLanguage->translate('Anreise'),
			Ext_TS_Inquiry_Journey::TRANSFER_MODE_DEPARTURE => $mLanguage->translate('Abreise'),
			Ext_TS_Inquiry_Journey::TRANSFER_MODE_BOTH => $mLanguage->translate('An- und Abreise')
		);

		return $aTransferList;
	}



	public static function getTeacherList($bForSelect = false){
		global $user_data;
		$sSql = "SELECT 
					*,UNIX_TIMESTAMP(`birthday`) `birthday`
				FROM 
					`ts_teachers`
				WHERE
					`active` = 1
				AND
					`idClient` = :idClient
				";
		$aSql = array(
					'idClient' => (int)$user_data['client']
					);
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		foreach($aResult as $aTeacher){
			if($bForSelect == false){
				$aBack[] = $aTeacher;
			} else {
				$aBack[$aTeacher['id']] = $aTeacher['lastname'].", ".$aTeacher['firstname'];
			}
		}
		
		return $aBack;
		
	}

	/**
	 * Korrespondenzsprachen aller Schulen
	 *
	 * @return string[]
	 */
	public static function getSystemLanguages(string $languageIso = null) {

		$oLocaleService = new Core\Service\LocaleService;
		$aLocales = $oLocaleService->getInstalledLocales($languageIso);
		if(!is_array($aLocales)) {
			$aLocales = [];
		}

		$oConfig = Ext_TS_Config::getInstance();
		$aFrontentLanguages = $oConfig->frontend_languages;
		if(!is_array($aFrontentLanguages)) {
			$aFrontentLanguages = [];
		}

		$aFrontentLanguagesKeys = array_flip($aFrontentLanguages);
		$aReturn = array_intersect_key($aLocales, $aFrontentLanguagesKeys);

		return $aReturn;
	}

	/**
	 * Frontendsprachen
	 *
	 * @param bool $bForSelect
	 * @param string $sLang
	 * @return string[]
	 */
	public static function getCorrespondenceLanguages($bForSelect = true, $sLang = '') {

		// Altes Verhalten von System::d('arrSchoolLanguages')
		if(Ext_Thebing_System::isAllSchools()) {
			$oConfig = Ext_TS_Config::getInstance();
			$aLanguages = (array)$oConfig->frontend_languages;
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$aLanguages = $oSchool->getLanguages();
		}

		$aLanguageLabels = self::getAllCorrespondenceLanguages($sLang);

		$aReturn = array_intersect_key($aLanguageLabels, array_flip($aLanguages));

//		//Wenn keine Sprache übergeben nimm die Loginsprache
//		if(empty($sLang)){
//			$sLang = Ext_TC_System::getInterfaceLanguage();
//		}
//
//		$aSchoolLanguages = System::d('arrSchoolLanguages');
//
//		/**
//		 * Prüfen, ob Sprache (≙Spalte) existiert in dieser Struktur.
//		 * Ansonsten würde es eine Exception geben.
//		 */
//		$oDb = DB::getDefaultConnection();
//		$bCheck = $oDb->checkField('data_languages', 'name_'.$sLang);
//
//		if(!$bCheck) {
//			$sLang = 'en';
//		}
//
//		$oLocaleService = new Core\Service\LocaleService;
//		$aLocales = $oLocaleService->getInstalledLocales($sLang);
//
//		$aReturn = array_intersect_key($aLocales, $aSchoolLanguages);

		asort($aReturn);

		return $aReturn;

	}

	/**
	 * Alle zur Auswahl verfügbaren Korrespondenzsprachen (um die 700!)
	 *
	 * @param null $sLanguage
	 * @return array
	 * @throws Exception
	 */
	public static function getAllCorrespondenceLanguages($sLanguage=null) {

		// Wenn keine Sprache übergeben nimm die Loginsprache
		if(empty($sLanguage)) {
			$sLanguage = Ext_TC_System::getInterfaceLanguage();
		}

		$oLocaleService = new Core\Service\LocaleService;
		$aLocales = $oLocaleService->getInstalledLocales($sLanguage);

		return $aLocales;

	}
}
