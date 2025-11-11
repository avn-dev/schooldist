<?php

use Illuminate\Support\Str;

class Ext_TC_Util extends Util {
	
	public static function compressForJson($sString) {
		$sString = str_replace(array("\n", "\t", "\r"), '', $sString);
		return $sString;
	}

	static public function checkAccessServer(){
		$sAccessServer = self::getAccessServer();
		return Util::checkUrl($sAccessServer);
	}	

	static public function getAccessServer(){
		$sUrl = 'https://fidelo.com';
		return $sUrl;
	}

	static public function getUpdateServer(){
		return 'update.fidelo.com';
	}
	
	static public function checkUpdateServer(){
		$sAccessServer = self::getUpdateServer();
		$sAccessServer = $sAccessServer.'/license.php'; 
		return Util::checkUrl($sAccessServer);
	}
	
	public static function getSalutations(){
		$aSalutations = array();
		$aSalutations['']	= '';
		$aSalutations['mr'] = L10N::t('Herr');
		$aSalutations['ms'] = L10N::t('Frau');
		return $aSalutations;
	}
	
	static public function truncateTable($sTable){

		try {
			self::backupTable($sTable);
		} catch (Exception $e) {
			__pout($e);
		}

		try {
			$sSql = " TRUNCATE TABLE #table ";
			$aSql = array('table' => $sTable);

			DB::executePreparedQuery($sSql, $aSql);
		} catch (Exception $e) {
			return false;
		}
		
		return true;
		
	}

	public static function getSignatureDirectory($bDocumentRoot = false) {
		$sDir = self::getSecureDirectory($bDocumentRoot);
		$sDir .= 'signatures/';
		return $sDir;
	}
	
	static function convertDateToTimestamp($sDate){

		$oWDDate = new WDDate();
		$oWDDate->set($sDate, WDDate::DB_DATE);
		$iTime = $oWDDate->get(WDDate::TIMESTAMP);
		return $iTime;
	}

	public static function setMonday(&$oDate) {

		$iDay = $oDate->get(WDDate::WEEKDAY);
		if(
			$iDay >= 2 &&
			$iDay <= 3
		) {
			$iDiff = $iDay - 1;
			$oDate->sub($iDiff, WDDate::DAY);
		} elseif(
			$iDay >= 4 &&
			$iDay <= 7
		) {
			$iDiff = 7 - $iDay + 1;
			$oDate->add($iDiff, WDDate::DAY);
		}

	}

	/**
	 * Gibt ein Array mit Jahren zurück
	 * Parameter 1 Anzahl der Jahre die in die Zukunft gehen
	 * Parameter 2 Anzahl der Jahre die in die Vergangenheit gehen
	 * @param int $iFutureYears
	 * @param int $iLastYears
	 * @return array
	 */
	static public function getYears($iFutureYears = 0, $iLastYears = 0) {

		$aYears = array();

		$iTime = mktime(00, 00, 00, 01, 01, date('Y'));
		$iYear = date('Y', $iTime);
		$aYears[$iYear] = $iYear;

		$iFutureTime = $iTime;
		for($i = 0; $i < $iFutureYears; $i++){
			$iFutureTime = strtotime('+1 Year', $iFutureTime);
			$iYear = date('Y', $iFutureTime);
			$aYears[$iYear] = $iYear;
		}

		$iLastTime = $iTime;
		for($i = 0; $i < $iLastYears; $i++){
			$iLastTime = strtotime('-1 Year', $iLastTime);
			$iYear = date('Y', $iLastTime);
			$aYears[$iYear] = $iYear;
		}

		ksort($aYears);

		return $aYears;
	}

	static public function getMonths($sLang = ''){
		global $system_data;
		
		if(empty($sLang)){
			$sLang = Ext_TC_System::getInterfaceLanguage();
		}
		
		$oLocale = new Core\Service\LocaleService();
		$aMonths = $oLocale->getLocaleData($sLang, 'month');

		return $aMonths;

	}

	public static function getMonthDays($iMonth = 0) {
		$aDays = array();
		
		for($i = 1; $i <= 31; $i++) {	
			
			if($iMonth === 0) {
				$aDays[$i] = $i;
			} elseif(
				$iMonth >= 1 && $iMonth <= 12 &&
				\Core\Helper\DateTime::isDate($iMonth . '-' . $i, 'n-j') !== false
			) {	
				if($i < 10) {
					$iDay = '0' . $i;
				} else {
					$iDay = $i;
				}

				$aDays[$i] = $iDay;				
			}
		}
		
		return $aDays;
	}

	/**
	 * @return array
	 */
	public static function getHours() {

		$aReturn = array();

		for($i = 0; $i < 24; $i++) {

			if($i < 10){
				$i = '0'.$i;
			}

			$aReturn[] = $i.':00:00';

		}

		return $aReturn;

	}

	/**
	 * Gibt ein Json Array mit dem Inhalt der Tabelle zurück
	 * @param <string> $sTableName
	 * @return <json>
	 */
	static public function getTableInsertData($sTableName, $iChanged = 0){

		$sSql      = " SELECT * FROM `".$sTableName."` ";
		$aSql = array('time' => $iChanged);
		
		if($iChanged > 0){
			$sSql .= ' WHERE UNIX_TIMESTAMP(`changed`) > :time ';
		}
		
		DB::setResultType(MYSQL_ASSOC);
		$aResult = (array)DB::getPreparedQueryData($sSql, $aSql);

		return json_encode($aResult);
		
	}

	/**
	 * Schreibt Tabelleninhalt aufbasis von Json Data
	 * @param <string> $sTableName
	 * @param <json> $sData
	 */
	static public function insertTableData($sTableName, $mData, $bBackup = true){

		if($bBackup){
			try {
				self::backupTable($sTableName);
			} catch (Exception $e) {
				__pout($e);
			}
		}

		$aData = $mData;

		if(!is_array($mData)){
			$aData = json_decode($mData, true);
		}

		foreach((array)$aData as $aRow) {
			
			$iId = 0;
			
			if(
				isset ($aRow['id']) &&
				$aRow['id'] > 0
			){
				$sSql = " SELECT `id` FROM #table WHERE `id` = :id LIMIT 1";
				$aSql = array('id' => (int)$aRow['id'], 'table' => $sTableName);
				$iId = DB::getQueryOne($sSql, $aSql);
			}
			
			if($iId > 0){
				DB::updateData($sTableName, $aRow, '`id` = '.$iId);
			} else {
				DB::insertData($sTableName, $aRow); 
			}
					
		}
		
	}


	static public function isLive1System(){
		
		$sHost = self::getHost();
		
		if(
			$sHost == 'live1.thebing.com'
		) {
			return true;
		}
		
		return false;
		
	}
	
	static public function isLive2System(){
		
		$sHost = self::getHost();
		
		if(
			$sHost == 'www.thebing.com' ||
			$sHost == 'live0.thebing.com' ||
			$sHost == 'live0.fidelo.com' ||
			$sHost == 'thebing.com' ||
			$sHost == 'live2.thebing.com'
		) {
			return true;
		}
		
		return false;
		
	}
	
	static public function isTestAgencySystem(){
		
		$sHost = self::getHost();
		
		if(
			$sHost == 'test.agency.fidelo.com' ||
			$sHost == 'test.agency.thebing.com'
		) {
			return true;
		}
		
		return false;
		
	}
	
	static public function isTestSchoolSystem(){
		return self::isTestSystem();
	}
	
	static public function isTestSystem(){
		
		$sHost = self::getHost();
		
		if(
			$sHost == 'test.school.fidelo.com' ||
			$sHost == 'test.school.thebing.com'
		) {
			return true;
		}
		
		return false;
		
	}
	
	/**
	 * @TODO Wird diese Methode überhaupt wirklich benötigt oder reicht isDevSystem()?
	 *
	 * Ermittelt, ob die Software local läuft
	 * @return boolean 
	 */
	static public function isLocalSystem() {
	
		if(
			// TODO .localhost wird oftmals ohne hosts auf 127.0.0.1 geroutet
			mb_strpos($_SERVER['HTTP_HOST'], '.localhost') !== false ||
			// TODO .dev gehört Google und ist komplett HSTS
			mb_strpos($_SERVER['HTTP_HOST'], '.dev') !== false ||
			// TODO .app gehört Google und ist komplett HSTS
			mb_strpos($_SERVER['HTTP_HOST'], '.app') !== false
		) {
			return true;
		}

		return false;

	}
	
	static public function isDevAgencySystem() {
		
        $sHost = self::getHost();
		
		if(
			$sHost == 'dev.agency.fidelo.com' ||
			$sHost == 'dev.agency.thebing.com' ||
			$sHost == 'ta.dev.box' ||
			$sHost == 'agency.dev.box'
		) {
			return true;
		}
		
		return false;
	}
	
	static public function isDevSchoolSystem(){
		return self::isDevSystem();
	}
	
	static public function isDevSystem(){
		
        $sHost = self::getHost();

		// TODO .local ist für mDNS/Zeroconf reserviert, daher funktioniert bei Apple-Devices z.B. keine .local-Domain
		// TODO .box existiert seit Ende 2016 auch als echte Domain
		if(
			$sHost === 'dev.school.fidelo.com' ||
			$sHost === 'dev.school.thebing.com' ||
			strpos($_SERVER['HTTP_HOST'], '.dev.box') !== false ||
			strpos($_SERVER['HTTP_HOST'], 'agency.local') !== false ||
            strpos($_SERVER['HTTP_HOST'], '.box') !== false ||
			strpos($_SERVER['HTTP_HOST'], 'school.local') !== false
		) {
			return true;
		}
		
		return false;
		
	}

	static public function isDevLegacySchoolSystem(){
		
        $sHost = self::getHost();
       
		if(
			$sHost == 'dev.legacy.school.fidelo.com' ||
			$sHost == 'dev.legacy.school.thebing.com' ||
			$sHost == 'school.legacy.dev.box' ||
			$sHost == 'ts.legacy.dev.box'
		) {
			return true;
		}

		return false;
	}

	static public function isDevCoreSystem(){
		
        $sHost = self::getHost();
        
		if(
			$sHost == 'dev.core.fidelo.com' ||
			$sHost == 'dev.core.thebing.com'
		) {
			return true;
		}
		
		return false;
	}
	
	static public function isCoreSystem(){
		
        $sHost = self::getHost();
        
		if(
			$sHost == 'core.thebing.com' ||
			$sHost == 'core.fidelo.com'
		) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * Methode prüft einen Timestamp, ob er ein Datum in UTC darstellt
	 * @deprecated
	 */
	static public function checkUTCDate($iTimestamp) {
		
		// Ist UTC Timestamp
		$iHour = gmdate("H", $iTimestamp);

		if($iHour == 0) {
			return true;
		}

		// Ist lokaler Timestamp?
		$iHour = date("H", $iTimestamp);
		
		if($iHour == 0) {
			return false;
		}
		
	}

	/**
	 * Wandelt einen Timestamp im UTC um, wenn er ein lokales Datum darstellt
	 * @deprecated
	 */
	static public function convertUTCDate($iTimestamp) {
		$bUTC = self::checkUTCDate($iTimestamp);
		if(!$bUTC) {
			$iTimestamp = self::convertToGMT($iTimestamp);
		}
		return $iTimestamp;
	}

	/**
	 * @deprecated
	 */
	static public function convertToGMT($iTime) {

		$aTemp['year'] = date("Y",$iTime);
		$aTemp['month'] = date("m",$iTime);
		$aTemp['day'] = date("d",$iTime);		
		$iTime = gmmktime(0, 0, 0, $aTemp['month'], $aTemp['day'], $aTemp['year']);
		
		return $iTime;
	
	}

	static public function formatGMT($iTime) {

		$sTime = gmdate('Y-m-d H:i:s', $iTime);
		return $sTime;

	}

	static function getTimeRows($sType='assoc', $iStep=15, $iStart=28800, $iEnd=79200, $bIncludeEnd=true) {
		$aTimeRows = array();
		$iTime = $iStart;

		if($bIncludeEnd) {
			$iEnd++;
		}

		while($iTime < $iEnd) {
			if($sType == 'assoc') {
				$aTimeRows[$iTime] = gmdate("H:i", $iTime);
			} elseif($sType == 'format') {
				$sTime = gmdate("H:i", $iTime);
				$aTimeRows[$sTime] = $sTime;
			} else {
				$aTimeRows[] = array($iTime, gmdate("H:i", $iTime));
			}
			$iTime += $iStep*60;
		}

		return $aTimeRows;

	}

	public static function convertTimeToSeconds($sTime) {

		$aTime = explode(":", $sTime);
		$iSeconds = ($aTime[0]*3600)+($aTime[1]*60)+$aTime[2];

		return $iSeconds;

	}
	
	public static function convertSecondsToTime($iSeconds) {

		$sTime = sprintf("%02d:%02d:%02d", floor($iSeconds/3600), floor(($iSeconds%3600)/60), ($iSeconds%60));

		return $sTime;

	}

	public static function getCoursePreferences() {
		
		$aPreferences = array();
		$aPreferences[0] = L10N::t('-');
		$aPreferences[1] = L10N::t('a.m.');
		$aPreferences[2] = L10N::t('p.m.');

		return $aPreferences;

	}

	public static function getStudentLevels() {

		$aLevels = array();
		$aLevels['normal'] = L10N::t('normal');
		$aLevels['internal'] = L10N::t('internal');
		return $aLevels;

	}

	/**
	 * Get the SQL query part for age
	 * 
	 * @return string
	 */
	public static function getAgeQueryPart()
	{
		// TODO : Diese Methode zur Altersberechnung systemweit verwenden
		$sSQL = "
			(
				(
					YEAR(NOW()) - YEAR(CONCAT(cdb1.ext_5, '-', cdb1.ext_4, '-', cdb1.ext_3))
				) -
				(
					RIGHT(CURDATE(), 5) < RIGHT(CONCAT(cdb1.ext_5, '-', cdb1.ext_4, '-', cdb1.ext_3), 5)
				)
			)
		";
		return $sSQL;
	}

	public static function getDateFormatDescription(Ext_Gui2_Dialog $oDialog, $sId=null, array $aAdditional = null, $bIncludeNames=false) {

		$sDescription = '<ul>';
		$sDescription .= '<li>'.L10N::t('Tag des Monats mit führender Null: %d').'</li>';
		$sDescription .= '<li>'.L10N::t('Monat als Zahl mit führender Null: %m').'</li>';
		$sDescription .= '<li>'.L10N::t('Jahr als 2-stellige Zahl: %y').'</li>';
		$sDescription .= '<li>'.L10N::t('Jahr als 4-stellige Zahl: %Y').'</li>';

		if($bIncludeNames === true) {
			$sDescription .= '<li>'.L10N::t('Ordinalzeichen des Tags: %O').'</li>';
			$sDescription .= '<li>'.L10N::t('Abgekürzter Name des Monats: %b').'</li>';
			$sDescription .= '<li>'.L10N::t('Ausgeschriebener Name des Monats: %B').'</li>';
		}

		if($aAdditional !== null) {
			foreach($aAdditional as $sPlaceholder) {
				$sDescription .= '<li>'.L10N::t($sPlaceholder).'</li>';
			}
		}

		$sDescription .= '</ul>';

		$oNotification = $oDialog->createNotification(
			L10N::t('Verfügbare Platzhalter'),
			$sDescription,
			'info',
			[
				'row_id' => $sId,
				'dismissible' => false
			]
		);

		return $oNotification;
	}

	/**
	 * @deprecated Nicht mehr verwenden, sondern getLocaleDays
	 * @param string $sFormat
	 * @param int $iDay
	 * @param int $iStartDay
	 * @return array|string
	 */
	public static function getDays($sFormat="%A", $iDay=null, $iStartDay=1) {

		$aDays = array();
		$aDays[1] = strftime($sFormat, strtotime('last Monday'));
		$aDays[2] = strftime($sFormat, strtotime('last Tuesday'));
		$aDays[3] = strftime($sFormat, strtotime('last Wednesday'));
		$aDays[4] = strftime($sFormat, strtotime('last Thursday'));
		$aDays[5] = strftime($sFormat, strtotime('last Friday'));
		$aDays[6] = strftime($sFormat, strtotime('last Saturday'));
		$aDays[7] = strftime($sFormat, strtotime('last Sunday'));

		// Array anders sortieren wenn Starttag angegeben wurde
		for($i=1; $i < $iStartDay; $i++) {
			$sDay = $aDays[$i];
			unset($aDays[$i]);
			$aDays[$i] = $sDay;
		}

		if($iDay) {
			return $aDays[$iDay];				
		} else {
			return $aDays;
		}

	}

	/**
	 * @param $sLanguage
	 * @param string $sType
	 * @return array
	 */
	public static function getLocaleDays($sLanguage=null, $sType = 'short') {

		if($sLanguage === null) {
			$sLanguage = Ext_TC_System::getInterfaceLanguage();
		}
		
		$oLocaleService = new \Core\Service\LocaleService;

		$aDays = [];

		$aDays[1] = $oLocaleService->getDay($sLanguage, 'mon', $sType);
		$aDays[2] = $oLocaleService->getDay($sLanguage, 'tue', $sType);
		$aDays[3] = $oLocaleService->getDay($sLanguage, 'wed', $sType);
		$aDays[4] = $oLocaleService->getDay($sLanguage, 'thu', $sType);
		$aDays[5] = $oLocaleService->getDay($sLanguage, 'fri', $sType);
		$aDays[6] = $oLocaleService->getDay($sLanguage, 'sat', $sType);
		$aDays[7] = $oLocaleService->getDay($sLanguage, 'sun', $sType);

		return $aDays;
	}
	
	/**
	 * Checks if directory exists and creates it if not
	 * 
	 * @param string $sDir Absolute directory path with document root
	 * @return bool
	 */
	public static function checkDir($sDir) {
		global $system_data;
		
		$sOriginalDir = $sDir;

		Util::checkDir($sDir);

		if(is_dir($sOriginalDir)) {
			return true;
		} else {
			Ext_TC_Util::reportError('Ext_TC_Util::checkDir error', $sOriginalDir."\n\n".$sDir);
			return false;
		}

	}

	public static function convertArrayForSelect(array $aArray, $sFieldValue='name', $sFieldKey = 'id') {
		
		$aBack = array();
		
		foreach($aArray as $mArr) {
			if(is_array($mArr)) {
				$aBack[$mArr[$sFieldKey]] = $mArr[$sFieldValue];
			} elseif(is_object($mArr)) {
				$mKey			= self::callMagicGetOrMethod($mArr, $sFieldKey);
				$mValue			= self::callMagicGetOrMethod($mArr, $sFieldValue);
				$aBack[$mKey]	= $mValue;
			}
		}
		
		return (array)$aBack;
	}

	/**
	 * All a getter or a Method of an Object
	 * @param object $oObject
	 * @param string $mCall
	 * @return mixed 
	 */
	public static function callMagicGetOrMethod($oObject, $mCall){
		$mValue = '';
		
		if(mb_strpos($mCall, '()')){
			$mCall	= str_replace('()', '', $mCall);
			$mValue = $oObject->$mCall();
		} else {
			$mValue = $oObject->$mCall;
		}
		
		return $mValue;
	}


	public static function convertArrayForSelectOptions(array $aArray, $sFieldValue='name', $sFieldKey = 'id'){

		$aBack = array();

		foreach($aArray as $mArr){
			if(is_array($mArr)) {
				$aBack[] = array(
					'value'	=> $mArr[$sFieldKey],
					'text'	=> $mArr[$sFieldValue]
				);
			} elseif(is_object($mArr)) {
				$mKey			= self::callMagicGetOrMethod($mArr, $sFieldKey);
				$mValue			= self::callMagicGetOrMethod($mArr, $sFieldValue);
				$aBack[] = array(
					'value'	=> $mKey,
					'text'	=> $mValue
				);
			}
		}

		return (array)$aBack;

	}

	/**
	 * convert an normal Select Option Array (gui) into Js Option Array for the updateSelectOption Method
	 * @param array $aArray
	 * @return array
	 */
	public static function convertOptionArrayToJsOptionsArray(array $aArray){

		$aBack = array();

		foreach($aArray as $mKey => $mValue){
			$aBack[] = array(
				'value'=>$mKey,
				'text'=>$mValue
			);
		}

		return $aBack;

	}

	public static function getGenders($bEmptyItem = true, $sEmptyItem = '', $mLanguage = null, $bLowerCase = false) {

		if(empty($mLanguage)) {
			$mLanguage = Ext_TC_System::getInterfaceLanguage();
		}
		
		if(!$mLanguage instanceof Tc\Service\LanguageAbstract) {
			$mLanguage = new \Tc\Service\Language\Backend($mLanguage);
		}

		$convert = function ($sGender) use ($bLowerCase) {
			if ($bLowerCase) {
				return strtolower($sGender);
			}
			return $sGender;
		};

		if ($mLanguage instanceof \Tc\Service\Language\Frontend) {
			$aGenders = array(
				1 => $mLanguage->translate($convert('Male')),
				2 => $mLanguage->translate($convert('Female')),
				3 => $mLanguage->translate($convert('Diverse'))
			);
		} else {
			$aGenders = array(
				1 => $mLanguage->translate($convert('Männlich')),
				2 => $mLanguage->translate($convert('Weiblich')),
				3 => $mLanguage->translate($convert('Divers'))
			);
		}

		if($bEmptyItem) {
			$aGenders = self::addEmptyItem($aGenders, $sEmptyItem);
		}

		return $aGenders;
	}
	
	public static function getPercentage($fA, $fB) {

		if(
			$fB == 0
		) {
			$fResult = 0;
		} else {
			$fResult = (($fA/$fB))*100;
		}

		return $fResult;

	}

	public static function getAverage($fA, $fB) {

		if(
			$fB == 0
		) {
			$fResult = 0;
		} else {
			$fResult = ($fA/$fB);
		}

		return $fResult;

	}
	
	public static function stripString($sString, $iChars, $sSuffix='...') {
	
		$iLen = mb_strlen($sString);
		$iLenSuffix = mb_strlen($sSuffix);
		
		if($iLen > $iChars) {
			$sString = mb_substr($sString, 0, $iChars).$sSuffix;
		}
		
		return $sString;
		
	}
	
	public static function getLanguageFieldContent($aData, $sPrefix, $sLanguage) {
		
		if(!empty($aData[$sPrefix.$sLanguage])) {
			return $aData[$sPrefix.$sLanguage];
		}
		
		foreach((array)$aData as $sKey=>$sData) {
			if(
				mb_strpos($sKey, $sPrefix) !== false &&
				!empty($sData)
			) {
				return $sData;
			}
		}
		
	}

	/**
	 * @deprecated
	 *
	 * @param int $iValue
	 * @param int $iFrom
	 * @param int $iTo
	 * @return bool
	 */
	public static function between($iValue,$iFrom,$iTo){
		
		if($iValue >= $iFrom && $iValue <= $iTo){
			return true;
		}
		return false;
		
	}
	
	/**
	 * Die Funktion prüft ob sich 2 Zeiträume überschneiden
	 * @param type $sStart1
	 * @param type $sEnd1
	 * @param type $sStart2
	 * @param type $sEnd2
	 * @return boolean 
	 */
	public static function overlap($sStart1, $sEnd1, $sStart2, $sEnd2){
	
		$mStatus = false;
		
		if(
			WDDate::isDate($sStart1, 	WDDate::DB_DATE) &&
			WDDate::isDate($sEnd1, 		WDDate::DB_DATE) &&
			WDDate::isDate($sStart2, 	WDDate::DB_DATE) &&
			WDDate::isDate($sEnd2, 		WDDate::DB_DATE)
		){
			$oDateFrom		= new WDDate($sStart1, 	WDDate::DB_DATE);
			$oDateUntil		= new WDDate($sEnd1, 	WDDate::DB_DATE);
		
			$iCompFirst = $oDateFrom->compare(new WDDate($sEnd2, WDDate::DB_DATE));
			$iCompLast 	= $oDateUntil->compare(new WDDate($sStart2, WDDate::DB_DATE));
			
			if(
				$iCompFirst != $iCompLast
			){
				$mStatus = true;
			}
		
		}
		
		return $mStatus;
	}

	public static function getWeekdaySelectOptions($sInterfaceLanguage) {

		$oLocale = new WDLocale($sInterfaceLanguage, 'date');

		return [
			'mo' => $oLocale->getValue('A', '1'),
			'di' => $oLocale->getValue('A', '2'),
			'mi' => $oLocale->getValue('A', '3'),
			'do' => $oLocale->getValue('A', '4'),
			'fr' => $oLocale->getValue('A', '5'),
			'sa' => $oLocale->getValue('A', '6'),
			'so' => $oLocale->getValue('A', '7'),
		];

	}

	public static function convertWeekdayToInt($sWeekDay = 'mo'){
		switch($sWeekDay){
			case 'mo':
				$iWeekDay = 1;
				break;
			case 'di':
				$iWeekDay = 2;
				break;
			case 'mi':
				$iWeekDay = 3;
				break;
			case 'do':
				$iWeekDay = 4;
				break;
			case 'fr':
				$iWeekDay = 5;
				break;
			case 'sa':
				$iWeekDay = 6;
				break;
			case 'so':
				$iWeekDay = 7;
				break;
		}
		return $iWeekDay;
	}
	
	public static function convertWeekdayToString($iWeekday = 1){
		switch($iWeekday) {
			case 1:
				$sWeekDay = 'mo';
				break;
			case 2:
				$sWeekDay = 'di';
				break;
			case 3:
				$sWeekDay = 'mi';
				break;
			case 4:
				$sWeekDay = 'do';
				break;
			case 5:
				$sWeekDay = 'fr';
				break;
			case 6:
				$sWeekDay = 'sa';
				break;
			case 7:
				$sWeekDay = 'so';
				break;
		}
		return $sWeekDay;
	}

	/**
	 * Liefert anhand des Wochentages den Englischen vollausgeschrieben Tag
	 *
	 * @param int $iWeekday
	 * @return string
	 */
	public static function convertWeekdayToEngWeekday($iWeekday = 1){
		switch($iWeekday) {
			case 1:
				$sWeekDay = 'Monday';
				break;
			case 2:
				$sWeekDay = 'Tuesday';
				break;
			case 3:
				$sWeekDay = 'Wednesday';
				break;
			case 4:
				$sWeekDay = 'Thursday';
				break;
			case 5:
				$sWeekDay = 'Friday';
				break;
			case 6:
				$sWeekDay = 'Saturday';
				break;
			case 7:
				$sWeekDay = 'Sunday';
				break;
		}
		return $sWeekDay;
	}

	
	public static function getOnlyDateTimestamp($iTimestamp){
	    return mktime(0,0,0,date('m',$iTimestamp),date('d',$iTimestamp),date('Y',$iTimestamp));
	}

	public static function sendErrorMessage($sMessage, $sSubject = '') {

		$sErrorEmailAddress = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getErrorEmailAddress');

		$bSuccess = false;

		if($sErrorEmailAddress !== '') {			
			$oMail = new WDMail();
			$oMail->subject = 'Thebing Software Error';
			
			if(!empty($sSubject)) {
				$oMail->subject .= ' - '.$sSubject;
			}
			
			if(!is_scalar($sMessage)) {
				$sMessage = print_r($sMessage, 1);
			}
			
			$oMail->text = $sMessage;

			$bSuccess = $oMail->send($sErrorEmailAddress);
		}	
		
		return $bSuccess;
	}

	/**
	 * @param string $sSubject
	 * @param string $sMessage
	 * @deprecated
	 * @return bool
	 */
	public static function reportMessage($sSubject, $sMessage="") {
		return self::reportError($sSubject, $sMessage);
	}

	/**
	 * Thebing Debug methode
	 * Gibt die variable aus sobald per Parameter debugcode = $sCode gesetzt wird.
	 * @param object $mVariable
	 * @param object $sCode [optional]
	 * @return 
	 */
	public static function debug($mVariable, $sCode = 1, $bStopScript = 0){
		global $_VARS;

		if(!is_array($_VARS['debugcode'])){
			if($_VARS['debugcode'] == $sCode){
				$aDebug	= debug_backtrace();
				__pout($mVariable, $bStopScript, $aDebug);
			}
		} else {
			foreach($_VARS['debugcode'] as $sDebug){
				if($sDebug == $sCode){
					$aDebug	= debug_backtrace();
					__pout($mVariable, $bStopScript, $aDebug);
				}
			}
		}

	}
	
    /**
     * super spezielle Methode zur Neutralisierung von Sommer- und Winterzeit
     * @param	INTEGER		$iForm
     * @return	INTEGER
     * @author	CW
     * @deprecated
     */
	public static function magicMatchingTime ( $iFrom ) {
		
		if(WDDate::isDate($iFrom, WDDate::TIMESTAMP)){
			$oDate = new WDDate($iFrom);
			
			$iHour = $oDate->get(WDDate::HOUR);
			
			if($iHour > 12){
				$oDate->add((24 - $iHour), WDDate::HOUR);
			}elseif(
				$iHour < 12 &&
				$iHour > 0
			){
				$oDate->add($iHour, WDDate::HOUR);			
			}
			
			$iFrom  = $oDate->get(WDDate::TIMESTAMP);
			
		}
		
		
		/*	ALTE variante habe ich durch WDDate ersetzt s.o. wegen Winterzeitbugs		
	    if ( (int)date('h',$iFrom) > 12 ) {
	        $iFrom = $iFrom + ( 3600 * ( 24 - (int)date('h',$iFrom) ) );
		} else if ( ((int)date('h',$iFrom) < 12) && ((int)date('h',$iFrom) > 0)) {
	        $iFrom = $iFrom - ( 3600 * (int)date('h',$iFrom) );
		}		
		*/

		return $iFrom;
	}
	
	function convertUtf8ToCp($strInput) {
		$strInput = html_entity_decode($strInput, ENT_QUOTES, 'UTF-8');
		$strInput = iconv('utf-8', 'cp1252', $strInput);
		return $strInput;
	}
	
	function checkVarsInput($_VARS, $arrFields) {
		foreach($arrFields as $arrField) {
			if(!Ext_TC_Util::checkFieldByType($_VARS[$arrField[0]],$arrField[1])) {
				$arrErrors[$arrField[0]] = "color:red;";
			}
			$arrField[0]." - ".$arrField[1].": ".$_VARS[$arrField[0]]."<br>";
		}
		return $arrErrors;
	}
	
	function checkFieldByType($sInput, $sType) {
		if (!is_array($sInput)) {
			$sInput = trim($sInput);
		}
		$tYears100 = number_format(date("YmdHis") - 1000000000000, 0, "", "");
		if ($sType == "age" && (!strtotimestamp($sInput,1) || strtotimestamp($sInput,1) > date("YmdHis",strtotime("-18 years")) || strtotimestamp($sInput,1) < $tYears100)) {
			return $sInput;
		}
		if ($sType == "email" && \Util::checkEmailMx($sInput)) {
			return $sInput;
		}
		if ($sType == "name" && preg_match("/^[üöäßa-z0-9\-\., ]{3,}$/i",$sInput)) {
			return $sInput;
		}
		if ($sType == "text" && preg_match("/[a-z0-9]+/i",$sInput)) {
			return $sInput;
		}
		if ($sType == "number" && preg_match("/^[0-9]+$/i",$sInput)) {
			return $sInput;
		}
		if ($sType == "date" && preg_match("/^(((0?[1-9]|[12][0-9])\.(0?[1-9]|1[0-2])\.)|(30\.((0?[13-9])|(1[0-2]))\.)|(31\.(0?[13578]|1[02])\.))(\d{2}|(19|20)\d{2})$/i",$sInput)) {
			return $sInput;
		}
		if ($sType == "zip" && preg_match("/^[0-9]{1,5}$/i",$sInput)) {
			return $sInput;
		}
		if ($sType == "phone" && preg_match("/^([+0-9\/\.-\s]*)$/i",$sInput)) {
			return $sInput;
		}
		if ($sType == "price" && preg_match("/^([+0-9\.,]*)$/i",$sInput)) {
			return $sInput;
		}
		if ($sType == "upload" && is_file($sInput['tmp_name'])) {
			return $sInput;
		}
		if ($sType == "check" && $sInput == "1") {
			return $sInput;
		}
		return false;
	}

	/**
	 * Funktion liefert global die Colorcodes für das System zurück
	 *
	 * @param string $sUse
	 * @param int $iFactor
	 * @return mixed[]|string
	 */
	public static function getColor($sUse = false, $iFactor = 100) {

		$aColors = [];
		$aColors['green'] = '#80ff80'; // #66E275
		$aColors['payed'] = $aColors['green'];
		$aColors['lightgreen'] = '#CCFFAA';
		$aColors['marked'] = '#DDFFAC'; // #66E275
		$aColors['red'] = '#ff8080'; // #FF7A73
		$aColors['red_font'] = '#FF0000';
		$aColors['orange'] = '#FFD373';
		$aColors['yellow'] = '#ffff99'; // #FF7A73
		$aColors['highlight'] = '#22bbff'; // blau
		$aColors['selected'] = '#a7cdf0'; // #FFD373;
		$aColors['inactive'] = '#999999';
		$aColors['changed'] = '#FFDDEE';
		$aColors['storno'] = '#CCEEFF';
		$aColors['substitute_full'] = '#FF0000';
		$aColors['substitute_part'] = '#FFB900';
		$aColors['substitute_teacher'] = '#2E97E0';

		$aColors['soft_orange'] = '#E0642E';
		$aColors['soft_yellow'] = '#E0D62E';
		$aColors['soft_blue'] = '#2E97E0';
		$aColors['soft_purple'] = '#B02EE0';
		$aColors['soft_green'] = '#BCE02E';

		$aColors['soft_turquoise'] = '#4DD0E1';

		$aColors['good'] = '#c6efce';
		$aColors['good_font'] = '#006100';
		$aColors['neutral'] = '#ffeb9c';
		$aColors['neutral_font'] = '#9c6500';
		$aColors['bad'] = '#ffc7ce';
		$aColors['bad_font'] = '#9c0006';

		$aColors['accent1'] = '#4f81bd';
		$aColors['accent2'] = '#c0504d';
		$aColors['accent3'] = '#9bbb59';
		$aColors['accent4'] = '#8064a2';
		$aColors['accent5'] = '#4bacc6';
		$aColors['accent6'] = '#f79646';

		$aColors['new'] = '#FFCCAA'; // rot
		$aColors['edit'] = '#FFFF99'; // gelb
		$aColors['delete'] = '#FFFF99'; // gelb
		$aColors['old'] = '#CCFFAA'; // grün

		$aColors['inactive_font'] = '#666666';

		if(!$sUse) {
			return $aColors;
		}

		if(!isset($aColors[$sUse])) {
			return false;
		}

		return \Core\Helper\Color::applyColorFactor($aColors[$sUse], $iFactor);

	}

	public static function getIcon($sUse=false) {

		$aIcons = Ext_Gui2_Util::getIcon();

		$aIcons['excel'] = 'fa-file-excel';
		$aIcons['arrow_right'] = 'fa-arrow-right';

		$aIcons['complaint'] = 'fa-exclamation-triangle';
		
		$aIcons['table_go']		= 'fa-table';
		$aIcons['table_row_insert']		= '/admin/extensions/tc/icons/table_row_insert.png';
	
		$aIcons['car']		= 'fa-car';
		
		if(!$sUse) {
			return $aIcons;
		} else {
			return $aIcons[$sUse];
		}

	}

	/*
	 * Definiert die spaltenbreiten der GUI Tabellen
	 * @todo Ist redundant zu Ext_Gui2_Util. Diese Methode muss entfernt und 
	 * alle Aufrufe angepasst werden.
	 */
	public static function getTableColumnWidth($sKey){

		switch ($sKey){
			// Datum
			case 'date':
				return 90;
				break;
			// Generelle Namen / Titel von Items (kurze Bezeichnungen)
			case 'short_name':
				return 80;
				break;
			// Generelle Namen / Titel von Items
			case 'agency_name':
			case 'group_name':
			case 'name':
				return 150;
				break;
			case 'char';
				return 50;
				break;
			// Zweistelliger ISO-Kürzel
			case 'iso';
				return 50;
				break;
			case 'id';
				return 50;
				break;
			case 'number';
			case 'document_number';
			case 'customer_number';
				return 120;
				break;
			case 'comment':
				return 200;
				break;
			// TODO: Entfernen
			case 'familyinfo':
				return 150;
				break; 
			case 'long_description':
				return 300;
				break;
			case 'extra_long_description':
				return 500;
				break;
			case 'icon':
				return 50;
				break;
			case 'niveau':
				return 100;
				break;
			case 'gender';
				return 80;
				break;
			case 'age';
				return 50;
				break;
			case 'nationality':
				return 100;
				break;
			case 'language':
				return 100;
				break;
			// Beträge
			case 'amount':
				return 120;
				break;
			// Datum kurz
			case 'date_short':
				return 70;
				break;
			// Datums periode
			case 'date_period':
				return 160;
				break;
			// TODO: Entfernen
			case 'week_title':
				return 250;
				break;
			// Datum mit Benutzer
			case 'date_user':
				return 250;
				break;
			// Uhrzeit
			case 'time':
				return 60;
				break;
			// Datum - Uhrzeit
			case 'date_time':
				return 120;
				break;
			// Anzahl von Items
			case 'count':
				return 100;
				break;
			// Ja oder Nein / 0 oder 1
			case 'yes_no':
				return 50;
				break;
			case 'transfer':
				return 120;
				break;
			//Kunden- oder Benutzername
			case 'user_name':
			case 'person_name':
			case 'customer_name':
				return 180;
				break;
			// Kunden Mail
			case 'email':
				return 200;
				break;
			// Telefon, Mobil, Fax
			case 'phone':
				return 160;
				break;
			case 'group_short':
			case 'agency_short':
				return 60;
				break;
			case 'zip':
				return 60;
				break;
			// TODO: Entfernen
			case 'group_course_options':
			case 'group_accommodation_options':
			case 'group_transfer_option':
				return 130;
				break;
			// TODO: Entfernen
			case 'courseweek_from_until':
				return 80;
				break;
			// TODO: Entfernen
			case 'nights':
				return 50;
				break;
			// Default
			default:
				return 120;
		}

	}

	public static function checkForFileExtensions($sPath, $sFileName, $bOnlyFilename = false){

		$sExists = '';
		$sPathAbsolute = \Util::getDocumentRoot().$sPath;

		if
		(
			is_file($aExists['.gif']	=	$sPathAbsolute.$sFileName.'.gif') ||
			is_file($aExists['.png']	=	$sPathAbsolute.$sFileName.'.png') ||
			is_file($aExists['.jpg']	=	$sPathAbsolute.$sFileName.'.jpg') ||
			is_file($aExists['.jpeg']	=	$sPathAbsolute.$sFileName.'.jpeg')||
			is_file($aExists['.pdf']	=	$sPathAbsolute.$sFileName.'.pdf') ||
			is_file($aExists['.docx']	=	$sPathAbsolute.$sFileName.'.docx') ||
			is_file($aExists['.doc']	=	$sPathAbsolute.$sFileName.'.doc') ||
			is_file($aExists['.txt']	=	$sPathAbsolute.$sFileName.'.txt') ||
			is_file($aExists['.msg']	=	$sPathAbsolute.$sFileName.'.msg')
		) {
			foreach((array)$aExists as $sKey => $aValue) {
				if($bOnlyFilename){
					$sExists = $sFileName.$sKey;
				} else {
					$sExists = $sPath.$sFileName.$sKey;
				}
				
			}
		}

		return $sExists;

	}

	
	/*
	 * Liefert den Wochentag zu einem Datum
     * umgestellt auf DateTime!! bitte bei Fehlern darauf achten!
     * $iTyp = 0 Date DD.MM.YYYY
	 * $iTyp = 1 datetime
	 * $iTyp = 2 date
	 * $iTyp = 3 timestamp
	 */
	public static function getWeekDay($iTyp, $sValue, $bAsString = true, $sLang = null){

		if($sValue == '') { 
			return -1;
		}
		
		if(empty($sLang)) {
			$sLang = System::getInterfaceLanguage();
		}
		
		if(empty($sLang)) {
			$sLang = 'en';
		}

		// Sollte mit allen Typen die im Kommentar stehen klappen; 

		switch($iTyp){
			case 0:
				$oDate = DateTime::createFromFormat("d.m.Y", $sValue);
				break;
			case 1:
				$oDate = DateTime::createFromFormat("Y-m-d h:i:s", $sValue);
				break;
			case 2:
				$oDate = DateTime::createFromFormat("Y-m-d", $sValue);
				break;
			case 3:
				$oDate = self::getDateTimeByUnixTimestamp($sValue);
				break;
			default: throw new Exception("Invalide Date Format");
		}

		if(!$oDate){
			return -1;
		}

		$iWeekday = $oDate->format('w');
		if(!$bAsString){
            // vorher war datetime da und dort war das anderst daher hier SO auf 7 stellen
            if($iWeekday == 0){
                $iWeekday = 7;
            }
			return $iWeekday;
		}

		$oLocaleService = new \Core\Service\LocaleService;

		$sDay = '';
		switch($iWeekday){
			case 1: $sDay = $oLocaleService->getDay($sLang, 'mon', 'short'); break;
			case 2: $sDay = $oLocaleService->getDay($sLang, 'tue', 'short'); break;
			case 3: $sDay = $oLocaleService->getDay($sLang, 'wed', 'short'); break;
			case 4: $sDay = $oLocaleService->getDay($sLang, 'thu', 'short'); break;
			case 5: $sDay = $oLocaleService->getDay($sLang, 'fri', 'short'); break;
			case 6: $sDay = $oLocaleService->getDay($sLang, 'sat', 'short'); break;
            // vorher war SO case 7 wegen wddate
			case 0: $sDay = $oLocaleService->getDay($sLang, 'sun', 'short'); break;
		}

		return $sDay;
	}

	/**
	 * @param $aData
	 * @param $sClassName
	 * @param bool $bCheckActive
	 * @return array
	 * @throws Exception
	 * @deprecated
	 */
	public static function convertDataIntoObject($aData, $sClassName, $bCheckActive = true){
		
		$aBack = [];

		if(!is_array($aData)){
			return $aBack;
		}

		if(!class_exists($sClassName)){
			throw new Exception('Class not exists');
		}

		if(!method_exists($sClassName, 'getInstance')){
			throw new Exception('static getInstance() not found in ' . $sClassName);
		}

		foreach((array)$aData as $mValue) {

			if(
				is_array($mValue) &&
				isset($mValue['id']) &&
				is_numeric($mValue['id'])
			){
				$oObject = call_user_func(array($sClassName, 'getInstance'), (int)$mValue['id']);
			}elseif(
				is_numeric($mValue) &&
				$mValue > 0
			){
				$oObject = call_user_func(array($sClassName, 'getInstance'), (int)$mValue);
			}else{
				continue;
			}

			if(
				$bCheckActive
			){
				if((int)$oObject->active !== 0) {
					$aBack[] = $oObject;
				} else {
					continue;
				}
			}else{
				$aBack[] = $oObject;
			}
		}

		return $aBack;
	}

	/**
	 * @TODO Die Methode kann entfernt werden, da diese nur mit/für der/die Kommunikation arbeitet
	 *
	 * Entfernt Document Root aus einem Pfad oder einem Array mit Pfaden
	 * @param <type> $mEntries
	 * @return <type>
	 */
	public static function stripDocumentRoot($mEntries) {

		if(!is_array($mEntries)) {
			$mEntries = array($mEntries);
		}
		
		$aReturn = array();
		foreach($mEntries as $sKey=>$sEntry) {
			$sKey = str_replace(Ext_TC_Util::getDocumentRoot(false), '', $sKey);
			$aReturn[$sKey] = $sEntry;
		}

		return $aReturn;

	}

	/**
	 * Get available number formats
	 * 
	 * @return array
	 */
	public static function getNumberFormats()
	{
		$aNumberFormats = array('1.000,00', '1,000.00', '1 000.00', '1 000,00', "1'000.00");

		return $aNumberFormats;
	}

	public static function getNumberFormatData($iNumberFormat){

		$iNumberFormat = (int)$iNumberFormat;
		
		$aFormat = array();
		$aFormat['dec'] = 2;
		$aFormat['increment'] = 1;

		switch ($iNumberFormat){
			case 0:
				$aFormat['t'] = '.';
				$aFormat['e'] = ',';
				break;
			case 1:
				$aFormat['t'] = ',';
				$aFormat['e'] = '.';
				break;
			case 2:
				$aFormat['t'] = ' ';
				$aFormat['e'] = '.';
				break;
			case 3:
				$aFormat['t'] = ' ';
				$aFormat['e'] = ',';
				break;
			case 4:
				$aFormat['t'] = "'";
				$aFormat['e'] = '.';
				break;
		}
		
		return $aFormat;
	}

	/**
	 * Gibt ein Array zurück für die OptionValue Keys
	 *
	 * Bei den nummerischen Keys ist die Reihenfolge (0 = nein) wichtig,
	 * da die setDialogSaveFieldValues() der gui2.js den Wert 0 ignoriert
	 *
	 * @param bool $bNumericKey
	 * @param bool $bEmptyEntry
	 * @return array
	 */
	public static function getYesNoArray($bNumericKey = true, $bEmptyEntry = false) {

		$aReturn = array();

		if($bNumericKey === true) {
			$aReturn[0] = L10N::t('Nein');
			$aReturn[1] = L10N::t('Ja');

			// [0] ist eigentlich immer EmptyItem, geht hier aber nicht weil "Nein" auch [0] ist.
			if($bEmptyEntry === true) {
				$aReturn = Ext_TC_Util::addEmptyItem($aReturn, '', '');
			}

		} else {
			$aReturn['no'] = L10N::t('Nein');
			$aReturn['yes'] = L10N::t('Ja');

			if($bEmptyEntry === true) {
				$aReturn = Ext_TC_Util::addEmptyItem($aReturn);
			}
		}

		return $aReturn;
	}

	/**
	 * @return array
	 */
	public static function getPersonTitles($mLanguage = null) {

		if(empty($mLanguage)) {
			$mLanguage = Ext_TC_System::getInterfaceLanguage();
		}

		if(!$mLanguage instanceof Tc\Service\LanguageAbstract) {
			$mLanguage = new \Tc\Service\Language\Backend($mLanguage);
		}

		// TODO für Frontend die englischen Titel nehmen
		$aReturn = array(
			0 => '',
			1 => $mLanguage->translate('Herr'),
			2 => $mLanguage->translate('Frau'),
			3 => $mLanguage->translate('Divers')
		);

		return $aReturn;
	}

	/**
	 *
	 * Prüft ein Date String ob der Tag ein Wochenende ist (Sa, So)
	 */
	public static function isWeekend($sDBDate){

		$bCheckDate = WDDate::isDate($sDBDate, WDDate::DB_DATE);

		if($bCheckDate){
			$oDate = new WDDate();
			$oDate->set($sDBDate, WDDate::DB_DATE);
			$iDay = $oDate->get(WDDate::WEEKDAY);
			if(
				$iDay == 6 ||
				$iDay == 7
			){
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	/**
	 * Übersetzt die Validierungsoptionen der  WDValidate für Thebing
	 */
	public static function getValidationOptions(){
		$aOptions = WDValidate::getValidationDescriptions();

		$aUnsetValidations = array(
								'IN_ARRAY',
								'CTYPT_DIGIT',
								'FLOAT',
								'FLOAT_POSITIVE',
								'FLOAT_NOTNEGATIVE',
								'DATE',
								'DATE_TIME',
								'TEXT'

		);

		$aBack = array();
		
		foreach((array)$aOptions as $sKey => $sDescription){

			// Validierungsfunktionen die wir bei Thebingnicht benötigen unsetten
			if(!in_array($sKey, $aUnsetValidations)){
				$aBack[$sKey] = L10N::t($sDescription);
			}	
		}

		return $aBack;
	}

	/**
	 * Gibt ein Array mit allen Zeitzonen zurück
	 * @return array
	 */
	public static function getTimeZones() {
		$aItems = DateTimeZone::listIdentifiers();
		$aTimeZones = array();
		foreach((array)$aItems as $sItem) {
			$aTimeZones[$sItem] = $sItem;
		}
		return $aTimeZones;
	}

	public static function setTimezone($sTimezone) {
		
		$mReturnMySQL = false;
		$mReturnPHP = false;
		
		try{

			// MySQL
			
			// Hier stand mal "SET SESSION time_zone = :time_zone", das hat aber leider nicht 
			// funktioniert, obwohl @@session.time_zone richtig gesetzt war ergab NOW() nicht die
			// richtige Uhrzeit, darum habe ich das in SET time_zone umgeändert (#4591)
			$sSql = "SET time_zone = :time_zone;";
			$aSql = array();
			$aSql['time_zone'] = (string)$sTimezone;
			$mReturnMySQL = DB::executePreparedQuery($sSql, $aSql);

			// PHP
			$mReturnPHP = date_default_timezone_set((string)$sTimezone);

		} catch(Exception $e){

		}

		if(
			$mReturnMySQL === false ||
			$mReturnPHP === false
		) {
			return false;
		} else {
			return true;
		}
		
	}
	
	/**
	 * Allgemeine Fileextensions für Uploads
	 * 
	 * @param string $sType Typ der Dateien
	 * @see Ext_Thebing_Upload_File::getFileExtensions()
	 */
	public static function getFileExtensions($sType) {

		switch($sType) {
			case 'file':
				$aAllowed = array('jpg', 'jpeg', 'png', 'pdf', 'txt', 'doc', 'docx', 'xls', 'xlsx');
				break;
			case 'pdf':
				$aAllowed = array('pdf');
				break;
			case 'image':
			default:
				$aAllowed = array('jpg', 'jpeg', 'png', 'gif');
				break;
		}

		return $aAllowed;

	}
	
	/**
	 * Liefert den Dateityp einer Datei
	 * 
	 * @param string $sFile
	 * @return string
	 */
	public static function getFileExtension($sFile) {
		$aPathInfo = pathinfo($sFile);
		return $aPathInfo['extension'];
	}
	
	/**
	 * Gibt den Pfad für sichere Dateien zurück
	 * @param type $bDocumentRoot
	 * @return string
	 */
	public static function getSecureDirectory($bDocumentRoot=false) {
		
		$sDirectory = '/storage/tc/';
		
		if($bDocumentRoot === true) {
			$sDirectory = \Util::getDocumentRoot(false).$sDirectory;
			$sDirectory = str_replace('//', '/', $sDirectory);
		}
		
		return $sDirectory;
		
	}

	/**
	 * Generiert aus einem übergebenen Pfad einen Link auf
	 * @param string $sPath
	 * @return string 
	 */
	public static function generateSecureLink($sPath) {

		$sPath = str_replace(self::getDocumentRoot(), '', $sPath);
		$sPath = str_replace('/media/secure/', '', $sPath);
		$sPath = str_replace('media/secure/', '', $sPath);

		$sLink = '/storage/download'.$sPath;
		
		return $sLink;
		
	}
	
	/**
	 * @TODO Auf FA umstellen (müssen eben auch alle Stellen angepasst werden)
	 *
	 * Gibt ein Iconpfad zu einer Datei zurück
	 * @param string $sFile
	 * @return string
	 */
	public static function getFileTypeIcon($sFile) {

		$sExt = mb_substr($sFile, mb_strrpos($sFile, '.') + 1);

		if(mb_strlen($sExt) > 3) {
			rtrim($sExt, 'x');
		}

		$sIcon = '/admin/extensions/tc/images/filetypes/'.$sExt.'.png';
		$sPath = Util::getDocumentRoot().'system/legacy/'.$sIcon;

		if(!is_file($sPath)) {
			$sIcon = '/admin/extensions/tc/images/filetypes/blanko.png';
		}

		return $sIcon;

	}
	
	/**
	 * Gibt eine lesbare Dateigröße aus
	 * @param int $iSize
	 * @return type 
	 */
	public static function getFilesize($iSize) {
		$units = array(' B', ' KB', ' MB', ' GB', ' TB');
		for($i = 0; $iSize > 1024; $i++) {
			$iSize /= 1024;
		}
		return Ext_TC_Number::format($iSize).$units[$i];
	}
	
	/*
	 * Create Unique Arrays using an md5 hash 
	 * ein array_unique() für mehrdimensionale arrays!
	 */
	public static function arrayUnique($array, $preserveKeys = false){
		// Unique Array for return  
		$arrayRewrite = array();
		// Array with the md5 hashes  
		$arrayHashes = array();
		foreach($array as $key=>$item){
			// Serialize the current element and create a md5 hash  
			$hash = md5(serialize($item));
			// If the md5 didn't come up yet, add the element to  
			// to arrayRewrite, otherwise drop it  
			if(!isset($arrayHashes[$hash])){
				// Save the current element hash  
				$arrayHashes[$hash] = $hash;
				// Add element to the unique Array  
				if($preserveKeys){
					$arrayRewrite[$key] = $item;
				}else{
					$arrayRewrite[] = $item;
				}
			}
		}
		return $arrayRewrite;
	}

	/**
	 * Dummy, muss in der Schul/Agentursoftware überschrieben werden!
	 */
	public static function getTranslationLanguages(string $languageIso = null) {
		$aLanguages = array(array('iso' => 'de', 'name' => 'Deutsch'), array('iso' => 'en', 'name' => 'Englisch'));
		return $aLanguages;
	}
	
	public static function getHeadingTypes() {
		$aHeadings = array(
			'h1' => 'H1',
			'h2' => 'H2',
			'h3' => 'H3',
		);
		
		return $aHeadings;
	}
	
	/**
	 * Password security status
	 * @return int 
	 */
	public static function getValidPassStatus(){
		
		$aStatus = array();
		
		// Low Security
		$aStatus['low']['length']		= 6;
		
		// Mid Security
		$aStatus['medium']['upper']		= 2;
		$aStatus['medium']['lower']		= 2;
		$aStatus['medium']['length']	= 6;
		
		// High Security
		$aStatus['high']['upper']		= 2;
		$aStatus['high']['lower']		= 2;
		$aStatus['high']['special']		= 2;
		$aStatus['high']['number']		= 2;
		$aStatus['high']['length']		= 8;
		
		return $aStatus;
	}
	
	/**
	 * Prüft ein Neues Passwort auf die Gültichkeit einer Sicherheitsstufe
	 * @param string $candidate
	 * @param string $sSecurityStatus
	 * @return boolean 
	 */
	public static function validPass($candidate, $sSecurityStatus = 'medium'){
		$bCheck = true;

		$r1='/[A-Z]/';						//Uppercase
		$r2='/[a-z]/';						//lowercase
		$r3='/[!@#$%^&*()-_=+{};:,<.>]/';	// whatever you mean by "special char"
		$r4='/[0-9]/';						//numbers
		$r5='/\s/';							//restricted chars

		$iCount1 = preg_match_all($r1, $candidate, $o);
		$iCount2 = preg_match_all($r2, $candidate, $o);
		$iCount3 = preg_match_all($r3, $candidate, $o);
		$iCount4 = preg_match_all($r4, $candidate, $o);
		$iCount5 = preg_match_all($r5, $candidate, $o);
		
		$aStatus = self::getValidPassStatus();
		
		switch($sSecurityStatus){
			case 'low':
				if(
					mb_strlen($candidate) < $aStatus['low']['length'] ||
					$iCount5 > 0
				) {
				   $bCheck = false;
				}
				break;
			case 'medium':
				if(
					$iCount1 < $aStatus['medium']['upper'] ||	// Großbuchstaben
					$iCount2 < $aStatus['medium']['lower'] ||	// Kleinbuchstaben
					$iCount5 > 0 ||								// Verbotene Zeichen
					mb_strlen($candidate) < $aStatus['medium']['length']
				) {
				   $bCheck = false;
				}
				break;
			case 'high':
				if(
					$iCount1 < $aStatus['high']['upper'] ||	// Großbuchstaben
					$iCount2 < $aStatus['high']['lower'] ||	// Kleinbuchstaben
					$iCount3 < $aStatus['high']['special'] ||	// Sonderzeichen
					$iCount4 < $aStatus['high']['number'] ||	// Ziffern
					$iCount5 > 0 ||								// Verbotene Zeichen
					mb_strlen($candidate) < $aStatus['high']['length']
				) {
				   $bCheck = false;
				}
				break;
			
		}
		
		return $bCheck;
	}

	public static function getPHPVersion() {

		$sVersion = phpversion();

		preg_match("/[0-9]+\.[0-9]+\.[0-9]+/", $sVersion, $aMatch);

		return $aMatch[0];

	}

	/**
	 * Achtung, bcscale() rundet nicht, sondern schneidet ab
	 *
	 * Gibt 0 zurück, wenn beide Operatoren gleich sind, 1, wenn $fFloat1 
	 * größer ist als $fFloat2, und andernfalls -1. 
	 *
	 * @deprecated
	 * @param float $fFloat1
	 * @param float $fFloat2
	 * @param int $iDec
	 * @return int
	 */
	public static function compareFloat($fFloat1, $fFloat2, $iDec = 6){

		$iReturn = bccomp($fFloat1, $fFloat2, $iDec);

		return $iReturn;

	}

	/**
	 * Verschiebt den Inhalt von einem Verzeichnis in ein anderes und integriert dabei die Dateien
	 * @static
	 * @param string $sOrigin
	 * @param string $sDestination
	 * @return bool
	 */
	public static function recursiveMove($sOrigin, $sDestination) {
		global $system_data;

		if(!is_dir($sOrigin)) {
			throw new Exception('Directory "'.$sOrigin.'" does not exist!');
		}

		if(!is_dir($sDestination)) {

			$bMkDir = mkdir($sDestination, $system_data['chmod_mode_dir'], true);
			@chmod($sDestination, $system_data['chmod_mode_dir']);

			if(!$bMkDir) {
				throw new Exception('Creation of directory '.$sDestination.' failed!');
			}

		}

		$oDirIterator = new DirectoryIterator($sOrigin);
		foreach($oDirIterator as $oDir) {
			/* @var DirectoryIterator $oDir */

			if($oDir->isDot()) {
				continue;
			} if($oDir->isDir()) {

				self::recursiveMove($oDir->getPathname(), $sDestination.'/'.$oDir->getFilename());

			} elseif($oDir->isFile()) {

				$sOriginFile = $sOrigin.'/'.$oDir->getFilename();
				$sDestinationFile = $sDestination.'/'.$oDir->getFilename();

				$bRename = rename($sOriginFile, $sDestinationFile);

				if(!$bRename) {
					throw new Exception('Rename of file '.$sOriginFile.' to '.$sDestinationFile.' failed!');
				}
			}

		}

		$bReturn = rmdir($sOrigin);

		return $bReturn;

	}
	
	/**
	 * Insert a Dummy entry an return the ID
	 * @param type $sTable
	 * @param int $aData
	 * @return type 
	 */
	public static function insertDummy($sTable, $aData = array()){
		
		if(empty($aData)){
			$aData['id'] = 1;
		}
		
		$sSql = " INSERT INTO 
						#table 
					SET 
				";
		$aSql = array('table' => $sTable);
		foreach($aData as $sField => $mValue){
			$sSql .= "
				#field_".$sField." = :value_".$sField.",";
			$aSql["field_".$sField] = $sField;
			$aSql["value_".$sField] = $mValue;
		}
		
		$sSql = rtrim($sSql, ',');
		
		DB::executePreparedQuery($sSql, $aSql);
		$iID = DB::fetchInsertID();
		return $iID;
	}
	
	/**
	 * ksort für multidemsionale Arrays
	 * @param type $aArray 
	 */
	public static function ksortDeep(&$aArray){		
		ksort($aArray);
		foreach($aArray as &$aItem) {
			if (is_array($aItem) && !empty($aItem)) {
				self::ksortDeep($aItem);
			}
		}			
	}
	
	/*
	 * escaped Daten für den CSV Export
	 */
	public static function prepareDataForCSV($sValue, $sCharset = 'CP1252'){
		 
		if(is_numeric($sValue)) {
			$sValue = strip_tags($sValue);
		} else {
			$sValue = html_entity_decode((string)$sValue, ENT_QUOTES, 'UTF-8');
			$sValue = iconv('UTF-8', $sCharset, $sValue);
			$sValue = str_replace('<br />', '; ', $sValue);
			$sValue = strip_tags($sValue);
		}

		
		return $sValue;
	}
	
		/**
	 * Die Funktion vergleicht 2 zeiträume miteinander und liefert den/die Zeiträume der 1. Periode zurück , die NICHT
	 * in der 2. Periode enthalten sind. (Einseitige Symmetrische Differenz)
	 * @param type $aPeriodFirst
	 * @param type $aPeriodSecond
	 * @return array 
	 */
	public static function getDatePeriodOverlapDiff($aPeriodFirst, $aPeriodSecond){
		$aCheckDates = array();
		
		if(
			WDDate::isDate($aPeriodFirst['from'], WDDate::DB_DATE) &&
			WDDate::isDate($aPeriodFirst['until'], WDDate::DB_DATE) &&
			WDDate::isDate($aPeriodSecond['from'], WDDate::DB_DATE) &&
			WDDate::isDate($aPeriodSecond['until'], WDDate::DB_DATE) &&
            $aPeriodFirst['from'] != "0000-00-00" &&
            $aPeriodFirst['until'] != "0000-00-00" &&
            $aPeriodSecond['from'] != "0000-00-00" &&
            $aPeriodSecond['until'] != "0000-00-00"
		){
			
			$oDateFrom = new WDDate($aPeriodFirst['from'], WDDate::DB_DATE);
			$oDateUntil = new WDDate($aPeriodFirst['until'], WDDate::DB_DATE);	
			
			$sFrom = $oDateFrom->get(WDDate::DB_DATE);
			$sUntil = $oDateUntil->get(WDDate::DB_DATE);
			
			$oDateFilterFrom = new WDDate($aPeriodSecond['from'], WDDate::DB_DATE);
			$oDateFilterUntil = new WDDate($aPeriodSecond['until'], WDDate::DB_DATE);	

			$sFilterFrom = $oDateFilterFrom->get(WDDate::DB_DATE);
			$sFilterUntil = $oDateFilterUntil->get(WDDate::DB_DATE);

			// Über-kreuz Differenz
			$iDiff1 = $oDateFrom->getDiff(WDDate::DAY, $oDateFilterFrom);
			$iDiff2 = $oDateUntil->getDiff(WDDate::DAY, $oDateFilterUntil);

			// Anfang Differenz
			$iDiff3 = $oDateFrom->getDiff(WDDate::DAY, $oDateFilterUntil);

			// End Differenz
			$iDiff4 = $oDateUntil->getDiff(WDDate::DAY, $oDateFilterFrom);

			if(
				$iDiff1 >= 0 &&
				$iDiff2 <= 0
			){
				// Alte Periode wurde von beiden Seiten Verlängert -> Nix muss geprüft werden
				$sCase = 'vorne und hinten verlängert';
			}elseif(
				$iDiff1 < 0 &&
				$iDiff2 > 0
			){
				// ursprungs-Periode wurde von beiden Seiten Verkleinert -> es müssen 2 zeiträume geprüft werden
				$oDateFilterFrom->sub(1, WDDate::DAY);
				$aCheckDates[0]['from'] = $sFrom;
				$aCheckDates[0]['until'] = $oDateFilterFrom->get(WDDate::DB_DATE);

				$oDateFilterUntil->add(1, WDDate::DAY);
				$aCheckDates[1]['from'] = $oDateFilterUntil->get(WDDate::DB_DATE);
				$aCheckDates[1]['until'] = $sUntil;
				$sCase = 'vorne und hinten verkürzt';
			}elseif(
				( # liegt links daneben
					$iDiff1 > 0 &&
					$iDiff2 > 0 &&
					$iDiff3 >= 0
				) || ( # liegt rechts daneben
					$iDiff1 < 0 &&
					$iDiff2 < 0 &&
					$iDiff4 <= 0
				)
			){
				// neue Periode liegt komplett außerhalb de alten Periode
				$aCheckDates[0]['from'] = $sFrom;
				$aCheckDates[0]['until'] = $sUntil;	
				$sCase = 'liegt komplett außerhalb der alten Periode';			
			}elseif(
				$iDiff1 >= 0 &&
				$iDiff2 > 0 &&
				$iDiff3 < 0	
			){
				# Periode ragt von links in ursprungs-Periode rein
				$oDateFilterUntil->add(1, WDDate::DAY);
				$aCheckDates[0]['from'] = $oDateFilterUntil->get(WDDate::DB_DATE);
				$aCheckDates[0]['until'] = $sUntil;	
				$sCase = 'liegt teilweise von "links" in der alten Periode';					
			}elseif(
				$iDiff1 < 0 &&
				$iDiff2 <= 0 &&
				$iDiff4 > 0
			){
				# Periode ragt von rechts in ursprungs-Periode rein
				$oDateFilterFrom->sub(1, WDDate::DAY);
				$aCheckDates[0]['from'] = $sFrom;
				$aCheckDates[0]['until'] = $oDateFilterFrom->get(WDDate::DB_DATE);	
				$sCase = 'liegt teilweise von "rechts" in der alten Periode';
			}			
		}

		#Debug
		#__pout($sCase);
		
		return $aCheckDates;
	}
	
	/**
	 * Nummernformat in der Schulsoftware abhängig von der Schule, deshalb ne Factory dazu bereit stellen
	 * 
	 * @global array $system_data
	 * @return array 
	 */
	public static function getNumberFormat() {
		global $system_data;

		// Währungsformatierung
		$aTemp = self::getNumberFormatData((int)$system_data['number_format']);
		
		return $aTemp;
	}

	/**
	 * Ermittelt aus einer Auswahl von Jahren das frühste Datum und das letzte Datum
	 *
	 * Als Beispiel:
	 * 	$aYears = array('2011', '2012', '2013')
	 * 	Return: array('
	 *
	 * @static
	 * @param array $aYears
	 * @return array
	 */
	public static function getStartAndEndDateOfYears(array $aYears)
	{
		sort($aYears);

		$iFirstYear = (int)reset($aYears);
		$iLastYear = (int)end($aYears);

		$aReturn = array(
			'start' => $iFirstYear.'-01-01',
			'end' => $iLastYear.'-12-31'
		);

		return $aReturn;
	}
	
	public static function arrayMergeRecrusivePreserveKeys() {

        $arrays = func_get_args();
        $base = array_shift($arrays);

        foreach ($arrays as $array) {
            reset($base); //important
			foreach($array as $key=>$value) {
                if (is_array($value) && @is_array($base[$key])) {
                    $base[$key] = self::arrayMergeRecrusivePreserveKeys($base[$key], $value);
                } else {
                    $base[$key] = $value;
                }
            }
        }

        return $base;
    }

	/**
	 * Dafür braucht man keine extra Methode, da die Operatoren nativ mit DateTime funktionieren!
	 * @deprecated
	 *
	 * @param DateTime $oDate1
	 * @param DateTime $oDate2
	 * @return int
	 */
	public static function compareDate(DateTime $oDate1, DateTime $oDate2){
		if($oDate1 < $oDate2){
			return -1;
		} else if($oDate1 > $oDate2){
			return 1;
		} else {
			return 0;
		}
	}
	
	public static function buildProgressBar($iUsed, $iTotal) {
		
		if($iUsed > $iTotal) {
			throw new Exception('Used-data must be smaller then total-data!');
		}
		
		$iPercent = self::getPercentage($iUsed, $iTotal);
		$iPercent = round($iPercent);
		
		$sBackground = self::getColor('good');
		$sFontColor = self::getColor('good_font');
		
		$sReturn = '
			<table class="progressbar">
				<tr>
					<td style="width: '.$iPercent.'%; background: '.$sBackground.';"></td>
					<td style="width: '.(100 - $iPercent).'%;" class="extant"></td>
				</tr>
			</table>';
		
		return $sReturn;
	}

	/**
	 * Timezone determinieren und setzen
	 * @return bool
	 */
	public static function getAndSetTimezone()
	{
		$oConfig = \Factory::getInstance('Ext_TC_Config');
		$sTimezone = $oConfig->getValue('standardtimezone');

		$bReturn = false;
		if(!empty($sTimezone)) {
			$bReturn = Ext_TC_Util::setTimezone($sTimezone);
		}

		return $bReturn;
	}
	
	/**
	 * @deprecated
	 *
	 * DateTime Objekt generieren anhand des Unix-Timestamps
	 * 
	 * @param int $iUnixTimestamp
	 * @return DateTime 
	 */
	public static function getDateTimeByUnixTimestamp($iUnixTimestamp)
	{
		$oDate = new DateTime();
		$oDate->setTimestamp($iUnixTimestamp);
		
		return $oDate;
	}
	
	/**
	 * @deprecated
	 *
	 * DateTime Objekt generieren anhand Datum/Unix-Timestamp
	 * 
	 * @param mixed $mDate
	 * @return DateTime 
	 */
	public static function getDateTimeObject($mDate)
	{
		if(is_numeric($mDate))
		{
			$oDateTime = self::getDateTimeByUnixTimestamp($mDate);
		}
		else
		{
			$oDateTime = new DateTime($mDate);
		}
		
		return $oDateTime;
	}

	public static function bindDays($aDays, $language)
	{
		$aShortDays		= self::getLocaleDays($language);

		$bCanBind		= false;
		$iLastDayKey	= 0;

		if(count($aDays) > 1){

			$bCanBind		= true;

			foreach($aDays as $iDayKey){

				if(
					$bCanBind &&
					$iLastDayKey > 0 &&
					$iDayKey - $iLastDayKey != 1
				){
					$bCanBind = false;
				}

				$iLastDayKey = $iDayKey;
			}
		}

		if(
			$bCanBind
		){
			$iFirstDay	= reset($aDays);
			$iLastDay	= end($aDays);

			$sFirstDay	= $aShortDays[$iFirstDay];
			$sLastDay	= $aShortDays[$iLastDay];

			$sReturn = $sFirstDay . ' - ' . $sLastDay;
		}else{
			$aDayNames	= array();

			foreach($aDays as $iDay)
			{
				$aDayNames[] = $aShortDays[$iDay];
			}

			$sReturn	= implode(', ', $aDayNames);
		}  


		return $sReturn;
	}

	public static function getSystem() {

		$sSystem = 'school';
		if(class_exists('Ext_TA_Util')) {
			$sSystem = 'agency';
		}

		return $sSystem;
	}
	
	public static function getClientName() {
		$oConfig = \Factory::getInstance('Ext_TC_Config');
		$sClientName = $oConfig->getValue('client_name');
		
		return $sClientName;
	}
	
	public static function saveClientName($sClientName) {
		$oConfig = \Factory::getInstance('Ext_TC_Config');
		$oConfig->set('client_name', $sClientName);
		$oConfig->save();
	}
	
	public static function setFactoryAllocations() {
		
		$aAllocations = array(
			'User' => 'Ext_TC_User'
		);
		
		Ext_TC_Factory::setAllocations($aAllocations);
	}

	/**
	 * Splittet im Dreisatz einen Betrag anhand der Datumsangaben
	 * Dies ist das PHP-Äquivalent zur Thebing-MySQL-Funktion getSubAmountByDates()
	 *
	 * @see Ext_Thebing_Db_StoredFunctions::getSubAmountByDates()
	 * @param float $fValue
	 * @param DateTime $dTimeFrom
	 * @param DateTime $dTimeUntil
	 * @param DateTime $dEntryFrom
	 * @param DateTime $dEntryUntil
	 * @return float
	 */
	public static function getSplittedAmountByDates($fValue, DateTime $dTimeFrom, DateTime $dTimeUntil, DateTime $dEntryFrom, DateTime $dEntryUntil) {

		// Wenn sich der Zeitraum gar nicht überschneidet, gibt es hier auch keinen Betrag
		$bOverlap = \Core\Helper\DateTime::checkDateRangeOverlap($dTimeFrom, $dTimeUntil, $dEntryFrom, $dEntryUntil);
		if(!$bOverlap) {
			return 0;
		}

		// Anzahl der Tage (Nächte!) beider überschneidender Perioden
		$iMultiplyDays = \Core\Helper\DateTime::getDaysInPeriodIntersection($dTimeFrom, $dTimeUntil, $dEntryFrom, $dEntryUntil);

		// Anzahl der Tage der Leistung
		$iEntryDays = $dEntryUntil->diff($dEntryFrom)->days;

		// Hier muss immer ein Tag addiert werden, da man bei 1 Tag Differenz bspw. durch 2 teilen muss (2 Tage)
		// Diese Logik dürfte daher kommen, dass Tage und nicht Nächte betrachtet werden
		$iMultiplyDays++;
		$iEntryDays++;

		// Bei nur einem Tag darf nichts gesplittet werden
		if($iEntryDays <= 1) {
			return $fValue;
		}

		// Dreisatz rechnen
		$fDividedValue = $fValue / $iEntryDays;
		$fReturn = $fDividedValue * $iMultiplyDays;

		return $fReturn;
	}

	/**
	 * Liefert ein Array mit DateTime-Objekten von from und until
	 * @param WDBasic[] $aObjects
	 * @return array
	 */
	public static function getDateTimeTuples(array $aObjects) {
		$aDates = array();

		foreach($aObjects as $oObject) {
			$aDates[$oObject->id] = array(
				new DateTime($oObject->from),
				new DateTime($oObject->until)
			);
		}

		return $aDates;
	}

	public static function getDepurationSelectOptionsMonth() {
		$aDepurationTimes = [
			1 => '1 '.L10N::t('Monat'),
			3 => '3 '.L10N::t('Monate'),
			6 => '6 '.L10N::t('Monate'),
			9 => '9 '.L10N::t('Monate'),
			12 => '12 '.L10N::t('Monate'),
			24 => '24 '.L10N::t('Monate'),
			0 => L10N::t('Nie')
		];
		
		return $aDepurationTimes;
	}


	/**
	 * @deprecated
	 * @see \Core\Helper\DateTime::getWeekPeriods()
	 *
	 * @param DateTime $dFrom
	 * @param DateTime $dUntil
	 * @return integer
	 */
	static public function countWeeks(DateTime $dFrom, DateTime $dUntil) {

		$oDiff = $dFrom->diff($dUntil);
		$iDayDiff = $oDiff->format('%R%a');
		$iWeeks = ceil($iDayDiff/7);
		
		return $iWeeks;
	}
	
	/**
	 * @deprecated
	 * @see \Core\Helper\DateTime::getMonthPeriods()
	 *
	 * Zählt die Anzahl der Monate zwischen zwei Zeitpunkten
	 * Rundet auf!
	 * 
	 * @param DateTime $dFrom
	 * @param DateTime $dUntil
	 * @return integer
	 */
	static public function countMonth(DateTime $dFrom, DateTime $dUntil) {

		$oDiff = $dFrom->diff($dUntil);
		$iMonthDiff = $oDiff->m + 12*$oDiff->y;

		// Startdatum um Anzahl der Monate erhöhen und prüfen, ob es Resttage gibt
		$dCheck = clone $dFrom;
		$dCheck->modify($iMonthDiff.' month');

		$oDiffCheck = $dCheck->diff($dUntil);
		$iRemainingDaysDiff = $oDiffCheck->format('%R%a');

		$iMonth = intval($iMonthDiff);
		if($iRemainingDaysDiff > 0) {
			$iMonth++;
		}

		return $iMonth;
	}

	/**
	 * @return string
	 */
	public static function getInstallationHost() {

		if(!empty($_SERVER['HTTP_HOST'])) {
			
			if(System::d('admin_https') == 1) {
				$sReturn = 'https';
			} else {
				$sReturn = 'http';
			}

			$sReturn .= '://'.$_SERVER['HTTP_HOST'];

		} else {
			$sReturn = System::d('domain');
		}

		return $sReturn;
	}

	public static function getLanguageObject($mLanguage='', $sDefaultBackendPath=null) {

		if(empty($mLanguage)) {
			$mLanguage = System::getInterfaceLanguage();
		}

		if(!$mLanguage instanceof Tc\Service\LanguageAbstract) {
			$mLanguage = new \Tc\Service\Language\Backend($mLanguage);
			if($sDefaultBackendPath !== null) {
				$mLanguage->setPath($sDefaultBackendPath);
			}
		}

		return $mLanguage;
	}
	
	/**
	 * Hallo, array_column()
	 *
	 * @deprecated
	 *
	 * @param array $aInput
	 * @return array
	 */
	public static function makeIdArrayIndex(array $aInput) {
		
		$aOutput = [];
		foreach($aInput as $aItem) {
			$aOutput[$aItem['id']] = $aItem;
		}
		
		return $aOutput;
	}

	/**
	 * Titel für Excel-Sheet vorbereiten
	 *
	 * @see \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::checkSheetCodeName()
	 * @param string $sTitle
	 * @param string $sReplace
	 * @return string
	 */
	public static function escapeExcelSheetTitle($sTitle, $sReplace = '') {

		$sTitle = str_replace(['*', ':', '/', '\\', '?', '[', ']'], $sReplace, $sTitle);

		if(Str::startsWith($sTitle, '\'')) {
			$sTitle = ltrim($sTitle, '\'');
		}
		if(Str::endsWith($sTitle, '\'')) {
			$sTitle = rtrim($sTitle, '\'');
		}

		$sTitle = Str::limit($sTitle, \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEET_TITLE_MAXIMUM_LENGTH);

		return $sTitle;
	}
	
	public static function formatLogo($sLogo, $sDirectory) {
		
		$oDesign = new Admin\Helper\Design;

		$sImage = $oDesign->formatLogo($sLogo, $sDirectory);

		return $sImage;
	}

	/**
	 * Eine Länge von 1024 ist meistens zu wenig.
	 */
	public static function setMySqlGroupConcatMaxLength() {

		DB::executeQuery(" SET SESSION group_concat_max_len = 4294967295 ");

	}

	/**
	 * Die Woche startet bei Carbon mit Sonntag = 0; 7 gibt es nicht
	 *
	 * @param int $weekDay
	 * @return int
	 */
	public static function convertWeekdayToCarbonWeekday(int $weekDay): int {

		if ($weekDay === 7) {
			$weekDay = Carbon\Carbon::SUNDAY;
		}

		return $weekDay;

	}

	public static function parsePipedString(string $string): \Illuminate\Support\Collection {

		return Str::of($string)
			->explode('|')
			->mapWithKeys(function (string $option) {
				[$option, $value] = Str::of($option)->explode(':', 2);
				return [$option => $value];
			});

	}

	/**
	 * Perioden auf Basis der Einheit generieren, z.B. je eine Periode pro Monat bei $unit = 'month'
	 *
	 * @param \Carbon\Carbon $from
	 * @param \Carbon\Carbon $until
	 * @param string $unit Alle Units, die Carbon behrrscht, z.B. year, month, week, quarter usw.
	 * @param bool $complete $from+$until runden oder nicht
	 * @return \Carbon\CarbonPeriod[]
	 */
	public static function generateDatePeriods(Carbon\Carbon $from, Carbon\Carbon $until, string $unit, bool $complete = false): array {

		$dates = [];
		$from2 = $from->clone()->{'startOf'.$unit}();
		$until2 = $until->clone()->{'endOf'.$unit}();
		$period = $from2->toPeriod($until2, 1, $unit);

		foreach ($period as $date) {
			$start = $date->clone()->{'startOf'.$unit}();
			$end = $date->clone()->{'endOf'.$unit}();
			if (!$complete && $date->{'isSame'.$unit}($from2)) {
				$start = $from->clone()->startOfDay();
			}
			if (!$complete && $date->{'isSame'.$unit}($until2)) {
				$end = $until->clone()->endOfDay();
			}
			$dates[] = $start->toPeriod($end);
		}

		return $dates;
	}

}
