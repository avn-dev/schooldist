<?php

class Ext_Thebing_Util extends Ext_TC_Util {

	/**
	 * Einstellung in den Tools, hier können Kurse/Programme, die bereits gebucht wurden, nachträglich bearbeitet werden
	 *
	 * @return bool
	 */
	public static function canOverwriteCourseSettings(): bool {
		return ((int)\System::d('overwrite_course_settings', 0) === 1);
	}

	/**
	 * @TODO Entfernen (analog zu getUntilDateOfCourse)
	 * GUI-Methode
	 * FÜR UNTERKÜNFTE
	 * @deprecated
	 *
	 * Errechnet das Enddatum zu einem Startdateum
	 * @param type $iFrom
	 * @param type $iWeeks
	 * @param type $iSchool
	 * @param type $bAsTimestamp
	 * @param type $iSchoolForFormat
	 * @return type
	 */
	static public function getUntilDate($sFrom, $iWeeks, $iSchool, \Ext_Thebing_Accommodation_Category $category, $bAsTimestamp = false, $iSchoolForFormat = 0){

		if($iSchool <= 0) {
			$iSchool = \Core\Handler\SessionHandler::getInstance()->get('sid');;
		}

		if($iSchoolForFormat <= 0){
			$iSchoolForFormat = $iSchool;
		}

		// Wenn keine Wochen angegeben gehe von 1 Woche aus
		if($iWeeks == 0){ 
			$iWeeks = 1;
		}

		$oSchool = Ext_Thebing_School::getInstance($iSchool);
		
		$iFrom = Ext_Thebing_Format::ConvertDate($sFrom, $iSchoolForFormat, 1);
		
        if(is_numeric($iFrom)){
            $oFrom = Ext_TC_Util::getDateTimeByUnixTimestamp($iFrom);
        } else {
            $oFrom = new DateTime($iFrom);
        }

		$oFrom = self::getAccommodationEndDate($oFrom, $iWeeks, $category->getAccommodationInclusiveNights($oSchool));

//		$oFrom->modify('+ '.$iWeeks.' Weeks');
//
//		$iLastWeekNights = $oSchool->getNightsOfLastAccommodationWeek();
//
//		$iDiff = $iLastWeekNights - 7;
//
//		if($iDiff != 0) {
//            if($iDiff > 0){
//                $oFrom->modify('+ '.$iDiff.' Days');
//            } else {
//                $oFrom->modify('- '.($iDiff * -1).' Days');
//            }
//		}

		$iTo = $oFrom->getTimestamp();

		if($bAsTimestamp == false){
			$sTo = Ext_Thebing_Format::LocalDate($iTo, $iSchoolForFormat);
		} else {
			$sTo = $iTo;
		}
		 
		return $sTo;

	}

	/**
	 * @TODO Entfernen (analog zu getUntilDate)
	 * GUI-Methode
	 *
	 * @deprecated (neu getCourseEndDate()?)
	 * @param $iFrom
	 * @param $iWeeks
	 * @param $iSchool
	 * @param bool $bReturnTimestamp
	 * @param int $iSchoolForFormat
	 * @return int|string
	 * @throws Exception
	 */
	static public function getUntilDateOfCourse($iFrom, $iWeeks, $iSchool, $bReturnTimestamp=false, $iSchoolForFormat = 0) {

		if($iSchool <= 0) {
			$iSchool = \Core\Handler\SessionHandler::getInstance()->get('sid');;
		}

		if($iSchoolForFormat <= 0){
			$iSchoolForFormat = $iSchool;
		}

		$sFrom = Ext_Thebing_Format::ConvertDate($iFrom, $iSchoolForFormat, 1);

		if(\Core\Helper\DateTime::isDate($sFrom, 'Y-m-d')) {

			$dFrom = new DateTime($sFrom);
//			$dFrom->add(new DateInterval('P'.(int)$iWeeks.'W'));
//
			$oSchool = Ext_Thebing_School::getInstance($iSchool);
//			$aCourseDays = Ext_Thebing_Util::getCourseWeekDays($oSchool->course_startday);
//			$iEndDay = end($aCourseDays);
//			//$iEndDay = $oSchool->getCourseEndDay();
//
//			if($dFrom->format('N') != $iEndDay) {
//				$dFrom->modify('last '.self::convertWeekdayToEngWeekday($iEndDay));
//			}
//
//			$sReturn = $dFrom->getTimestamp();

			$sReturn = \Ext_Thebing_Util::getCourseEndDate($dFrom, (int)$iWeeks, (int)$oSchool->course_startday)->getTimestamp();

			if(!$bReturnTimestamp) {
				$sReturn = Ext_Thebing_Format::LocalDate($sReturn, $iSchoolForFormat);
			}

		} else {
			$sReturn = '';
		}

		return $sReturn;
	
	}

	/**
	 * @deprecated
	 *
	 * $bRealTuitionWeek: Die Methode wird mal zur Ermittlung der tatsächlichen Wochen benutzt,
	 * mal aber auch zur Ermittlung der Kurswoche. Da früher alles Montag-Sonntag war,
	 * war das nie ein Problem. Seit dem man den Starttag allerdings einstellen kann,
	 * gibt es da Probleme…
	 *
	 * @param DateTime|int|string $mTime
	 * @param int $iStartDay
	 * @param bool $bRealTuitionWeek
	 * @return array
	 */
	public static function getWeekTimestamps($mTime=0, $iStartDay=1, $bRealTuitionWeek=false) {
		$aReturn = array();

		if(!$mTime) {
			$mTime = new DateTime();
		}

		if($mTime instanceof DateTime) {
			$dDate = $mTime;
		} elseif(is_numeric($mTime)) {
			#$dDate = Core\Helper\DateTime::createFromLocalTimestamp($mTime);

			$dDate = new DateTime('@'.$mTime);
			$dDate->setTimezone(new DateTimeZone(date_default_timezone_get()));

		} elseif(mb_substr_count($mTime, '-') === 2) {
			$dDate = DateTime::createFromFormat('Y-m-d', $mTime);
		} else {
			throw new InvalidArgumentException('No valid time format "'.$mTime.'"');
		}

		// Zeit immer auf 00:00:00 setzen, damit korrekt gerechnet wird
		$dDate->setTime(0, 0, 0);

		// Wenn Datum nicht Montag: Auf Montag springen
		// @TODO Vermutlich ist das nicht ganz richtig, aber in die Methode werden wild verschiedenste Werte reingegeben
		if($dDate->format('N') != 1) {
			$dDate->modify('last monday');
		}

		// Wenn tatsächliche Kurswoche und Starttag nicht Montag: Tag verschieben
		if($bRealTuitionWeek && $iStartDay != 1) {
			$dDate = self::getCorrectCourseStartDay($dDate, $iStartDay);
		}

		$aReturn['start'] = $dDate->getTimestamp();

		$dDate->modify('+7 days -1 second');
		
		// Sicher ist sicher, ich trau dem ganzen Zeitumstellungskram nicht
		if($dDate->format('H:i:s') !== '23:59:59') {
			throw new \RuntimeException('Invalid week enddate! ('.$dDate->format('c').')');
		}
		
		$aReturn['end'] = $dDate->getTimestamp();

		return $aReturn;
	}

	public static function getWeekOptions($sFormat=null, $iMinusYears=1, $iStartDay=1) {
		$aWeeks = array();

		$dStart = new DateTime((date('Y') - $iMinusYears).'-01-01');
		$dEnd = new DateTime((date('Y') + 1).'-12-31 23:59:59');
		$oPeriod = new DatePeriod($dStart, new DateInterval('P1W'), $dEnd);

		foreach($oPeriod as $dWeek) {

			$dWeek->modify('monday this week');
			$sWeek = $dWeek->format('W');

			// Kompatiblität: WDDate-Typ
			if(
				$sFormat === null ||
				$sFormat === 'TIMESTAMP'
			) {
				$mKey = $dWeek->getTimestamp();
			} else {
				$mKey = $dWeek->format('Y-m-d');
			}

			$aWeek = Ext_Thebing_Util::getWeekTimestamps($dWeek->getTimestamp(), $iStartDay, true);

			$aWeeks[$mKey] = L10N::t('Woche')." ".$sWeek.", ".Ext_Thebing_Format::LocalDate($aWeek['start'])." - ".Ext_Thebing_Format::LocalDate($aWeek['end'])."";
		}

		return $aWeeks;
	}

	/**
	 * Enddatum von Kurs errechnen (auf Basis des Starttags der Schule)
	 *
	 * @param DateTimeInterface $dDate
	 * @param int $iWeeks
	 * @param int $iStartDay
	 * @return \Carbon\Carbon
	 */
	public static function getCourseEndDate(\DateTimeInterface $dDate, int $iWeeks, int $iStartDay): Carbon\Carbon {

		if (!self::checkCourseStartDay($iStartDay)) {
			throw new InvalidArgumentException('Wrong start day ('.$iStartDay.')');
		}

		$dDate = Carbon\Carbon::instance($dDate);
		$dDate->addWeeks($iWeeks);

		$aCourseDays = Ext_Thebing_Util::getCourseWeekDays($iStartDay);
		$iEndDay = end($aCourseDays);

		if((int)$dDate->format('N') !== $iEndDay) {
			$dDate->modify('last '.self::convertWeekdayToEngWeekday($iEndDay));
		}

		return $dDate;

	}

	/**
	 * Wochentag (1-7) zum entsprechenden Datum konvertieren, basierend auf dem Starttag der Kurswoche
	 *
	 * Bei den Starttagen Fr-So liegen diese Tage beispielsweise nicht in der selben Kalenderwoche,
	 * sondern in der Kalenderwoche zuvor. $dWeek sollte entsprechend ein Montag sein, damit diese
	 * Methode auch korrekt funktioniert (das System identifiziert eine Woche immer über den Montag).
	 *
	 * Es gibt für diese Methode eine entsprechende MySQL-Funktion:
	 * @see Ext_Thebing_Db_StoredFunctions::getRealDateFromTuitionWeek()
	 *
	 * @param DateTime $dWeek Montag
	 * @param int $iWeekday Ausgewählter Wochentag: 1=Montag, […]
	 * @param int $iStartDay $oSchool->course_startday
	 * @return DateTime|\Core\Helper\DateTime|\Carbon\Carbon
	 */
	public static function getRealDateFromTuitionWeek(DateTime $dWeek, int $iWeekday, int $iStartDay) {

		$dWeek = clone $dWeek;

		if (!self::checkCourseStartDay($iWeekday) || !self::checkCourseStartDay($iStartDay)) {
			throw new InvalidArgumentException('Weekday ('. $iWeekday.'/' . $iStartDay.') is not in range');
		}

		$dWeek = self::getCorrectCourseStartDay($dWeek, $iStartDay);

		if($iWeekday > $iStartDay) {
			// Normal Tage addieren, solange Wochentag größer ist als Kurs-Starttag (normale Addition)
			$iAddDays = $iWeekday - $iStartDay;
			$dWeek->add(new DateInterval('P'.$iAddDays.'D'));
		} elseif($iWeekday < $iStartDay) {
			// Überlauf: Hier muss nun die Differenz addiert werden, damit die nächste Woche erreicht wird (Brainfuck?)
			$iAddDays = 7 - ($iStartDay - $iWeekday);
			$dWeek->add(new DateInterval('P'.$iAddDays.'D'));
		}

		return $dWeek;

	}

	/**
	 * Von einem Montag-Datum (≙ Woche) auf den korrekten Tag der Kurswoche springen
	 *
	 * Der Aufruf dieser Methode ist äquivalent zu getRealDateFromTuitionWeek() und $iWeekday == $iStartDay.
	 *
	 * Es gibt für diese Methode eine entsprechende MySQL-Funktion:
	 * @see Ext_Thebing_Db_StoredFunctions::getCorrectCourseStartDay()
	 *
	 * @param DateTime $dDate
	 * @param int $iStartDay
	 * @return DateTime
	 */
	public static function getCorrectCourseStartDay(DateTime $dDate, int $iStartDay) {

		$dDate = clone $dDate;

		if (!self::checkCourseStartDay($iStartDay)) {
			throw new InvalidArgumentException('Wrong start day ('.$iStartDay.')');
		}

		if($dDate->format('N') != 1) {
			throw new InvalidArgumentException('Invalid use of getCorrectCourseStartDay(): $dDate is not monday');
		}

		$iWeekdayDiff = self::getCourseStartDiffFromMonday($iStartDay);
		$oWeekDayDiff = new DateInterval('P'.abs($iWeekdayDiff).'D');
		if($iWeekdayDiff > 0) {
			$dDate->add($oWeekDayDiff);
		} else {
			$dDate->sub($oWeekDayDiff);
		}

		return $dDate;

	}

	/**
	 * Von einem beliebigen Kursstart-Datum die Woche (Montag) ermitteln
	 *
	 * Dies ist NICHT vergleichbar mit der Ermittlung vom Montag der Woche. Es wird erwartet, dass $dDate bereits
	 * ein Kurs-Starttag nach dem eingestellten Kursstartag der Schule ist. Ist $dDate Fr-So, ergibt das den Montag
	 * der Woche danach (siehe andere Methoden).
	 *
	 * Inversions-Methode von getCorrectCourseStartDay()
	 *
	 * @see getCorrectCourseStartDay()
	 * @param DateTime $dDate
	 * @return DateTime|Carbon\Carbon
	 */
	public static function getWeekFromCourseStartDate(DateTime|Carbon\Carbon $dDate, $noAddition = false) {

		$dDate = clone $dDate;

		$iDay = $dDate->format('N');

		$iWeekdayDiff = self::getCourseStartDiffFromMonday($iDay, $noAddition);

		$oWeekDayDiff = new DateInterval('P'.abs($iWeekdayDiff).'D');
		if(
			$iWeekdayDiff > 0 ||
			$noAddition
		) {
			$dDate->sub($oWeekDayDiff);
		} else {
			$dDate->add($oWeekDayDiff);
		}

		if($dDate->format('N') != 1) {
			throw new RuntimeException('Result date is not monday');
		}

		return $dDate;

	}

	/**
	 * Springt von einem beliebigen Tag auf den nächsten Starttag eines Kurses (in der Regel Montag)
	 *
	 * Inversions-Methode von getPreviousCourseStartDay()
	 *
	 * @see getPreviousCourseStartDay()
	 * @param DateTime $dDate
	 * @param int $iStartDay $oSchool->course_startday
	 * @return DateTime|\Carbon\Carbon
	 */
	public static function getNextCourseStartDay(\DateTime $dDate, int $iStartDay) {

		if (!self::checkCourseStartDay($iStartDay)) {
			throw new InvalidArgumentException('Wrong start day ('.$iStartDay.')');
		}

		$dDate = \Carbon\Carbon::instance($dDate);

		if($dDate->format('N') != $iStartDay) {
			if($dDate->format('N') < $iStartDay) {
				$dDate->add(new DateInterval('P'.($iStartDay - (int)$dDate->format('N')).'D'));
			} else {
				$dDate->add(new DateInterval('P'.(7 - (int)$dDate->format('N') + $iStartDay).'D'));
			}
		}

		return $dDate;
	}

	/**
	 * @TODO Methode umbenennen in getWeek[…], da die Methode immer die Woche (Montag) ermittelt, basierend auf Starttag
	 *
	 * Springt von einem Tag in der Woche auf den Kursstarttag der Schule (in der Regel Montag)
	 *
	 * Mit $iStartDay=1 entspricht dies hier: if($date->format('N') != 1) $date->modify('last monday')
	 *
	 * Beispiele
	 * Kursstartag Montag, Kurs startet dienstags: Springt auf den Montag davor
	 * Kursstarttag Mittwoch, Kurs startet dienstags: Springt auf den Mittwoch davor
	 *
	 * Inversions-Methode von getNextCourseStartDay()
	 *
	 * @see getNextCourseStartDay()
	 * @internal
	 * @param DateTime|Carbon\Carbon $dDate
	 * @param int $iStartDay $oSchool->course_startday
	 * @return DateTime|Carbon\Carbon
	 */
	public static function getPreviousCourseStartDay(DateTime|Carbon\Carbon $dDate, $iStartDay) {

		if (!self::checkCourseStartDay($iStartDay)) {
			throw new InvalidArgumentException('Wrong start day ('.$iStartDay.')');
		}

		$dDate = clone $dDate;

		if($dDate->format('N') != $iStartDay) {
			if($dDate->format('N') > $iStartDay) {
				$dDate->sub(new DateInterval('P'.((int)$dDate->format('N') - $iStartDay).'D'));
			} else {
				$dDate->sub(new DateInterval('P'.(7 - $iStartDay + (int)$dDate->format('N')).'D'));
			}
		}

		return $dDate;

	}

	/**
	 * Liefert den Differenzwert an Wochentagen, um von einem Montag auf den richtigen Starttag zu kommen
	 *
	 * Wenn Starttag der Kurswoche beispielsweise Samstag ist, muss hier -2 zurückkommen, damit man
	 * Montag - 2 rechnen kann, um auf den Samstag zu kommen. Bei einem anderen Starttag als Montag ist
	 * die Woche nämlich immer die, wo die meisten Tage reinfallen. Bei Starttag Fr-So befinden sich diese
	 * Tage also in der Woche zuvor.
	 *
	 *
	 * Kalkulation dieser Methode:
	 *
	 * Montag (1) - 1 = 0
	 * Dienstag (2) - 1 = 1
	 * Mittwoch (3) - 1 = 2
	 * Donnerstag (4) - 1 = 3
	 * Freitag (5) - 8 = -3
	 * Samstag (6) - 8 = -2
	 * Sonntag (7) - 8 = -1
	 *
	 * @param int $iStartDay $oSchool->course_startday
	 * @return int
	 */
	private static function getCourseStartDiffFromMonday(int $iStartDay, bool $noAddition = false) {

		if(
			$iStartDay <= 4 ||
			$noAddition
		) {
			return $iStartDay - 1;
		} else {
			return $iStartDay - 8;
		}

	}

	/**
	 * Tage der Kurswoche (als Integer) ermitteln, ausgehend vom Kurs-Starttag (Schuleinstellung)
	 *
	 * Aktuell ist die Definition der Kurswoche: Kurs-Starttag + 4 Tage (5 insgesamt)
	 *
	 * @see shiftWeekDays();
	 * @param $iStartDay
	 * @return int[]
	 */
	public static function getCourseWeekDays(int $iStartDay) {
		$aDays = range(1, 5);
		self::shiftWeekDays($aDays, $iStartDay);
		return $aDays;
	}

	/**
	 * Tage der Blockwoche (als Integer) ermitteln, ausgehend vom Kurs-Starttag (Schuleinstellung)
	 *
	 * @see shiftWeekDays();
	 */
	public static function getBlockWeekDays(int $iStartDay) {
		$aDays = range(1, 7);
		self::shiftWeekDays($aDays, $iStartDay);
		return $aDays;
	}

	/**
	 * Array von Tagen anhand des übergebenen Starttags verschieben
	 *
	 * Beispiel:
	 * $aDays = range(1, 7);
	 * Bei Montag (1) wäre das hier nun ein Array mit [1, 2, 3, 4, 5, 6, 7],
	 * bei Starttag Mittwoch (3) allerdings [3, 4, 5, 6, 7, 1 ,2]
	 */
	private static function shiftWeekDays(&$aDays, int $iStartDay) {

		foreach($aDays as &$iDay) {
			$iTmp = $iDay + ($iStartDay - 1);
			if($iTmp <= 7) {
				$iDay = $iTmp;
			} else {
				$iDay = $iTmp - 7;
			}
		}

	}

	private static function checkCourseStartDay(int $startDay) {
		return $startDay >= 1 && $startDay <= 7;
	}

	/**
	 * Errechnet das Enddatum einer Unterkunft
	 *
	 * @param DateTime $dDate
	 * @param int $iWeeks
	 * @param int $iInclusiveNights $category->getAccommodationInclusiveNights($school)
	 * @return \Carbon\Carbon
	 */
	public static function getAccommodationEndDate(\DateTime $dDate, int $iWeeks, int $iInclusiveNights) {

		$dDate = Carbon\Carbon::instance($dDate);
		$dDate->addWeeks($iWeeks - 1);
		$dDate->addDays($iInclusiveNights);

		return $dDate;

	}

	static public function getWeekTitle($iTime) {
		$aWeek = Ext_Thebing_Util::getWeekTimestamps($iTime);
		$sTitle = Ext_Thebing_L10N::t('Woche')." ".date("W", $aWeek['start']).", ".Ext_Thebing_Format::LocalDate($aWeek['start'])." – ".Ext_Thebing_Format::LocalDate($aWeek['end'])."";
		return $sTitle;
	}

	static public function getAccommodationWeekTitle($iTimeOfFirstDay, $bKW = true) {

		$iStart = $iTimeOfFirstDay;
		$iEnd = strtotime('+ 1 Week', $iTimeOfFirstDay);
		//$iEnd = strtotime('- 1 Second', $iEnd);

		$sTitle = Ext_Thebing_L10N::t('Woche')." ";
		if($bKW){
			$sTitle .= date("W", $iStart).", ";
		}
		$sTitle .= Ext_Thebing_Format::LocalDate($iStart)." - ".Ext_Thebing_Format::LocalDate($iEnd)."";

		return $sTitle;
	}
		
	public static function getLanguageName($sLanguageIso = "de", $sDisplayLang = ''){

		if($sDisplayLang == ""){
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
			$sDisplayLang = $oSchool->getInterfaceLanguage();
		}

		$aLangs = Ext_Thebing_Data::getLanguageSkills(true, $sDisplayLang);
		return $aLangs[$sLanguageIso];
	}


	public static function getCurrentInboxData($iInbox = 0){
		global $_VARS;

		$aInbox = array();
		$aInbox['short'] = '';

		if($iInbox <= 0){
			$iInbox = (int)$_VARS['inbox_id'];
		}

		if($iInbox > 0){
			$oClient = Ext_Thebing_Client::getFirstClient();
			$aInboxList = $oClient->getInboxList();

			$aInbox = $aInboxList[$iInbox];
		}

		return $aInbox;
	}

	public static function mailLog($sType, $sTable, $sUserID, $sMail, $sSubject, $sText, $idClient = "", $sIdCmsUser = "",$iPdfInvoice = 0,$iPdfInvoiceNet = 0,$iPdfLoa = 0, $aAttachments = array()) {
		$sQuery = "INSERT INTO `kolumbus_maillog` (
				      `changed`,
				      `created`,
				      `active`,
				      `type`,
				      `idTable`,
				      `idUser`,
				      `email`,
				      `subject`,
				      `content`,
					  `idSchool`,
					  `idCmsUser`,
					  `pdf_invoice`,
					  `pdf_invoicenet`,
					  `pdf_loa`,
					  `attachments`
				   ) VALUES (
				   	  NOW(),
				   	  NOW(),
				   	  '1',
				   	  '".db_addslashes($sType)."',
				   	  '".db_addslashes($sTable)."',
				   	  '".db_addslashes($sUserID)."',
				   	  '".db_addslashes($sMail)."',
				   	  '".db_addslashes($sSubject)."',
				   	  '".db_addslashes($sText)."',
					  '".db_addslashes(\Core\Handler\SessionHandler::getInstance()->get('sid'))."',
					  '".db_addslashes($sIdCmsUser)."',
					  '".db_addslashes($iPdfInvoice)."',
					  '".db_addslashes($iPdfInvoiceNet)."',
					  '".db_addslashes($iPdfLoa)."',
					  '".db_addslashes(serialize($aAttachments))."'
				   );";
		DB::executeQuery($sQuery);
	}

	public static function getIcon($sUse=false) {

		$aIcons = parent::getIcon();
		

		$aIcons['refresh']	= 'fa-refresh';
		$aIcons['refresh2']	= '/media/arrow_refresh_small.png';
		$aIcons['calculator']			= 'fa-calculator';
		$aIcons['storno']	= 'fa-minus-square-o';

		$aIcons['communication'] = '/admin/extensions/thebing/images/email.png';
		$aIcons['payment'] = 'fa-money';

		$aIcons['pdf_inactive'] = '/media/page_grey_acrobat.png';

		$aIcons['feedback'] = 'fa-star';

		$aIcons['page_detail'] = '/admin/extensions/thebing/images/page_white_magnify.png';

		$aIcons['details'] = '/admin/extensions/thebing/images/zoom.png';

		$aIcons['tick'] ='/media/tick.png';

		$aIcons['accept'] ='fa-check-circle';

		$aIcons['provider_confirm'] ='fa-car';
		$aIcons['multi_provider_confirm'] ='/admin/extensions/thebing/images/car_add.png';

		$aIcons['convert_item'] ='fa-exchange';

		$aIcons['arr_right'] = '/admin/extensions/thebing/images/arrow_right.png';
		$aIcons['arr_left'] = '/admin/extensions/thebing/images/arrow_left.png';
		$aIcons['arr_down'] = '/admin/extensions/thebing/images/arrow_down.png';

		$aIcons['page_magnifier'] = '/admin/extensions/thebing/images/page_white_magnify.png';
		$aIcons['magnifier'] = '/admin/extensions/thebing/images/magnifier.png';

		$aIcons['access'] ='fa-key';

		$aIcons['dialog_detail'] = 'fa-file-text';
		$aIcons['history_detail'] = 'fa-history';

		$aIcons['clock'] = '/admin/extensions/thebing/images/clock.png';

		$aIcons['calendar'] = 'fa-calendar';

		$aIcons['group'] = 'fa-users';
		
		$aIcons['shear'] = 'fa-scissors';

		$aIcons['exclamation'] = '/admin/extensions/gui2/exclamation.png';
		$aIcons['indicator'] = '/admin/media/indicator.gif';

		$aIcons['list'] = 'fa-list';
		
		if(!$sUse) {
			return $aIcons;
		} else {
			return $aIcons[$sUse];
		}

	}

	/**
	 * @return array
	 */
	public static function getPaymentTranslations() {

		$sL10NDescription = Ext_Thebing_Inquiry_Payment::TRANSLATION_PATH;

		$aData = array();
		$aData['payment_checkbox'] = L10N::t('Positionen einzeln bezahlen?', $sL10NDescription);
		$aData['payment_delete'] = L10N::t('Zahlungseingang wirklich löschen?', $sL10NDescription);
		$aData['payment_delete_all'] = L10N::t('Wirklich alle Zahlungseingänge löschen?', $sL10NDescription);
		$aData['creditnote_payments_delete'] = L10N::t("Warnung: Die Bezahlung ist über eine Agenturzahlung zu dem Schüler ({customer_numbers}) und der Provisionsgutschrift ({creditnote_numbers}) verknüpft. Durch das Löschen der Provisionszahlung wird die komplette Zahlung mit einem Gesamtbetrag von {total_amount} gelöscht.", $sL10NDescription);
		$aData['payment_creditnotes_delete'] = L10N::t("Warnung: Die Bezahlung ist mit der Provisionsgutschrift ({creditnote_numbers}) verknüpft. Durch das Löschen der Agenturzahlung wird die komplette Provisionsauszahlung mit einem Gesamtbetrag von {total_amount} gelöscht.", $sL10NDescription);
		
		return $aData;
	}

	/**
	 * Liefert alle Clienten
	 */
	public static function getClients(){
		$sSql = "SELECT
						*
					FROM
						`kolumbus_clients`
					WHERE
						`active` = 1
				";
		$aClients = DB::getQueryData($sSql);

		$aBack = array();
		foreach((array)$aClients as $aData){
			$aBack[] = new Ext_Thebing_Client($aData['id']);
		} 

		return $aBack;
	}

	/**
	 * Sprachfelder bei Systemen ohne Thebing Update eintragen
	 */
	public static function updateLanguageFields() {

		$aFields = (new \Core\Helper\Bundle())->readBundleFile('Ts', 'db')['language_fields'];
		$aLanguages = (array)Ext_TS_Config::getInstance()->frontend_languages;

		foreach ($aLanguages as $sLang) {

			foreach ($aFields as $aField) {

				[$sTable, $sPrefix, $sFieldType] = $aField;

				$sField = $sPrefix;
				$sField .= !empty($sField) ? '_' : ''; // Sonderfall system_translations
				$sField .= $sLang;

				// Felder, die unter diesem Präfix bereits existieren
				$aExistingFields = collect(DB::describeTable($sTable, true))->filter(function ($aColumn, $sColumn) use ($sPrefix) {
					return !empty($sPrefix) && strpos($sColumn, $sPrefix) !== false;
				});

				$bSuccess = false;
				if ($aColumn = $aExistingFields->get($sField)) {
					// Feld updaten
					$sCurrentType = sprintf('%s %sNULL', $aColumn['Type'], $aColumn['Null'] === 'NO' ? 'NOT ' : '');
					if (strtolower($sFieldType) !== strtolower($sCurrentType)) {
						// Anmerkung: Da hier CHARACTER SET / COLLATE weggelassen werden, werden immer die Einstellungen der Tabelle verwendet
						$sSql = "ALTER TABLE `{$sTable}` CHANGE `{$sField}` `{$sField}` {$sFieldType}";
						DB::executeQuery($sSql);
						$bSuccess = true;
					}
				} else {
					// Feld anlegen
					// Die letzte Spalte gegebenenfalls als AFTER setzen, ansonsten ohne AFTER
					$sAfter = $aExistingFields->keys()->last();
					$bSuccess = DB::addField($sTable, $sField, $sFieldType, $sAfter);
				}

				// Wenn Feld angelegt wurde
				if ($bSuccess === true) {
					// Feld-Cache der WDBasic für die Tabelle leeren
					WDCache::delete('wdbasic_table_description_'.$sTable);
					WDCache::delete('db_table_description_'.$sTable);
				}

			}
			
		}

	}

	/**
	 * @param string $sLanguage
	 * @return array
	 */
	public static function getMatchingYesNoArray($sLanguage = null) {
		
		if($sLanguage == '') {
			$sLanguage = Ext_Thebing_School::fetchInterfaceLanguage();
		}

		$aMatchingYesNo = array(
			2 => Ext_TC_L10N::t('Ja', $sLanguage),
			1 => Ext_TC_L10N::t('Nein', $sLanguage)
		);

		$aMatchingYesNo = Ext_Thebing_Util::addEmptyItem($aMatchingYesNo, Ext_TC_L10N::t('keine Auswahl', $sLanguage));

		return $aMatchingYesNo;
	}

	/**
	 * Optionen für Zusatzkosten Einstellungen bei Gruppen/Guides
	 */
	public static function getGroupAdditionalcostOptions(){

		$aGroupOptions = array();
		$aGroupOptions[1] = L10N::t('pro Gruppenmitglied inkl. Leader', 'Thebing » Marketing » Additionalcosts');
		$aGroupOptions[2] = L10N::t('pro Gruppenmitglied exkl. Leader', 'Thebing » Marketing » Additionalcosts');
		$aGroupOptions[3] = L10N::t('einmalig für die Gruppe', 'Thebing » Marketing » Additionalcosts');
		$aGroupOptions[4] = L10N::t('nie berechnen', 'Thebing » Marketing » Additionalcosts');

		return $aGroupOptions;
	}

	/**
	 * Optionen für Stornomöglichkeiten
	 */
	public static function getStornoTypeOptions($bDynStorno = false, $sLang = ''){
		
		if(empty($sLang)){
			$oSchool = Ext_Thebing_Client::getFirstSchool();
			$sLang = $oSchool->fetchInterfaceLanguage();
		}
		
		$aFeeTypes = array(
					1 => Ext_Thebing_L10N::t('Prozent', $sLang, 'Thebing » Marketing » Stornofee'),
					2 => Ext_Thebing_L10N::t('Währung', $sLang, 'Thebing » Marketing » Stornofee'),
		);

//		if($bDynStorno){
//			$aFeeTypes[3] = Ext_Thebing_L10N::t('Währung/Woche', $sLang, 'Thebing » Marketing » Stornofee');
//		}

		return $aFeeTypes;
	}

	/**
	 * @deprecated
	 *
	 * @param array $aPeriods
	 * @return array
	 */
	public static function getPeriodDates($aPeriods) {

		$aDates				= array();

		$oDate				= new WDDate();

		foreach((array)$aPeriods as $aPeriod) {

			$bMatch = false;

			if(WDDate::isDate($aPeriod['from'], WDDate::TIMESTAMP)){
				$oDate->set($aPeriod['from'], WDDate::TIMESTAMP);
			}elseif(WDDate::isDate($aPeriod['from'], WDDate::DB_DATE)){
				$oDate->set($aPeriod['from'], WDDate::DB_DATE);
			}else{
				continue;
			}

			$aPeriod['from'] = $oDate->get(WDDate::TIMESTAMP);

			if(WDDate::isDate($aPeriod['until'], WDDate::TIMESTAMP)){
				$oDate->set($aPeriod['until'], WDDate::TIMESTAMP);
			}elseif(WDDate::isDate($aPeriod['until'], WDDate::DB_DATE)){
				$oDate->set($aPeriod['until'], WDDate::DB_DATE);
			}else{
				continue;
			}

			$aPeriod['until'] = $oDate->get(WDDate::TIMESTAMP);

			foreach($aDates as $iKey => $aPeriodDates) {

				if(
					$aPeriodDates['from'] < $aPeriod['until'] &&
					$aPeriod['from'] < $aPeriodDates['until']
				) {
					$aDates[$iKey]['from']	= min($aPeriodDates['from'],$aPeriod['from']);
					$aDates[$iKey]['until'] = max($aPeriodDates['until'],$aPeriod['until']);
					$bMatch = true;
				}
			}

			if(!$bMatch) {
				$aDates[$aPeriod['from']] = $aPeriod;
			}
		}

		return $aDates;
	}

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

	public static function checkFileExtension($sFile,$sType){

		$aAllowed	= (array)self::getFileExtensions($sType);

		$aPathParts = (array)pathinfo($sFile);

		if(
			!in_array(strtolower($aPathParts['extension']), $aAllowed)
		) {
			return false;
		}else{
			return true;
		}
	}

	public static function getErrorMessageFileExtension($sType,$sDescriptionPart){

		switch($sType){

			case 'pdf':

				$sMessage = 'Nur PDF-Dateien sind erlaubt.';
				$sMessage = L10N::t($sMessage, $sDescriptionPart);
				break;
			
			case 'image':

				$sMessage = 'Nur Bilddateien mit den Endungen %s sind erlaubt.';
				$sMessage = L10N::t($sMessage, $sDescriptionPart);
				
				$aAllowedFileExteions = self::getFileExtensions('image');
				$sAllowedFileExteions = implode(", ", $aAllowedFileExteions);
				
				$sMessage = str_replace('%s', $sAllowedFileExteions, $sMessage);
				break;

			case 'file':
				
				$sMessage = 'Nur Dateien mit den Endungen %s sind erlaubt.';
				$sMessage = L10N::t($sMessage, $sDescriptionPart);

				$aAllowedFileExteions = self::getFileExtensions('file');
				$sAllowedFileExteions = implode(", ", $aAllowedFileExteions);
				
				$sMessage = str_replace('%s', $sAllowedFileExteions, $sMessage);
				break;
		}

		return $sMessage;
	}
	
	public static function setFactoryAllocations() {

		$aAllocations = array(

			'User' => 'Ext_Thebing_User',
			'Welcome' => 'Ext_Thebing_Welcome',
			'Util' => 'Ext_Thebing_Util',

			'Ext_TC_Config' => 'Ext_TS_Config',
			'Ext_Gui2' => 'Ext_Thebing_Gui2',
			'Ext_TC_System_Navigation'				=> 'Ext_TS_System_Navigation',
			'Ext_TC_Object'							=> 'Ext_Thebing_Client',
			'Ext_TC_SubObject'						=> 'Ext_Thebing_School',
			'Ext_TC_Util'							=> 'Ext_Thebing_Util',
			'Ext_TC_Update' => 'Ext_Thebing_Update',
			'Ext_TC_L10N'							=> 'Ext_Thebing_L10N',
			'Ext_TC_Log'							=> 'Ext_Thebing_Log',
			'Ext_TC_Number'							=> 'Ext_TS_Number',
			'Ext_TC_Currency'						=> 'Ext_Thebing_Currency',
			'Ext_TC_Gui2_Format_Int'				=> 'Ext_Thebing_Gui2_Format_Int',
			'Ext_TC_Gui2_Format_Date'				=> 'Ext_Thebing_Gui2_Format_Date',
			'Ext_Gui2_View_Format_Date'				=> 'Ext_Thebing_Gui2_Format_Date',
			'Ext_Gui2_View_Format_Int'				=> 'Ext_Thebing_Gui2_Format_Int',
			'Ext_Gui2_View_Format_Float' => 'Ext_Thebing_Gui2_Format_Float',
			'Ext_TC_Gui2_Format_Date_Time'			=> 'Ext_Thebing_Gui2_Format_Date_Time',
			'Ext_TC_Gui2_Format_Date_DateTime'      => 'Ext_Thebing_Gui2_Format_Date_DateTime',
			'Ext_Gui2_View_Format_Date_DateTime' => 'Ext_Thebing_Gui2_Format_Date_DateTime',
			'Ext_Gui2_View_Format_Date_Time'		=> 'Ext_Thebing_Gui2_Format_Date_Time',

			'Ext_TC_Frontend_Template_Gui2_Data'	=> 'Ext_TS_Frontend_Template_Gui2_Data',
			'Ext_TC_Frontend_Combination'			=> 'Ext_TS_Frontend_Combination',
			'Ext_TC_Frontend_Combination_Gui2_Data' => 'Ext_TS_Frontend_Combination_Gui2_Data',

			'Ext_TC_NumberRange_Gui2_Data'			=> 'Ext_TS_NumberRange_Gui2_Data',
			'Ext_TC_NumberRange_Allocation'			=> 'Ext_TS_NumberRange_Allocation',
			'Ext_TC_NumberRange_Allocation_Set'		=> 'Ext_TS_NumberRange_Allocation_Set',
			'Ext_TC_User'							=> 'Ext_Thebing_User',
			'Ext_TC_User_Group'						=> 'Ext_Thebing_Admin_Usergroup',
			'Ext_TC_Db_StoredFunctions'				=> 'Ext_Thebing_Db_StoredFunctions',
			'Ext_TC_Communication_EmailAccount'		=> 'Ext_Thebing_Mail',
			'Ext_TC_Communication'					=> 'Ext_Thebing_Communication',
			'Ext_TC_Communication_Message' => 'Ext_Thebing_Communication_Message',
			'Ext_TC_Communication_AutomaticTemplate'=> 'Ext_Thebing_Email_TemplateCronjob',
			'Ext_TC_Communication_AutomaticTemplate_Gui2_Data' => 'Ext_Thebing_Email_TemplateCronjob_Gui2_Data',
			'Ext_TC_Communication_Gui2_Data' => 'Ext_Thebing_Communication_Gui2_Data',
			'Ext_TC_Communication_Gui2' => 'Ext_Thebing_Gui2_Communication',
			'Ext_TC_Communication_Message_Notice_Gui2_Selection_Correspondant' => '\Ts\Gui2\Selection\Communication\NoticeCorrespondant',
			'Ext_TC_Communication_Message_Notice_Gui2_Data' => 'Ext_Thebing_Communication_Message_Note_Gui2_Data',

			'Ext_TC_WDMVC_Token'					=> 'Ext_TS_WDMVC_Token',
			'Ext_TC_Gui2_Selection_Flexibility_FieldType' => 'Ext_Thebing_Gui2_Selection_Flexibility_FieldType',
			'Ext_TC_Gui2_Selection_Flexibility_Validation' => 'Ext_Thebing_Gui2_Selection_Flexibility_Validation',
			'Ext_TC_Vat_Gui2_Data'					=> 'Ext_TS_Vat_Gui2_Data',
			'Ext_TC_Document_Version'				=> 'Ext_Thebing_Inquiry_Document_Version',
			'Ext_TC_Format'							=> 'Ext_Thebing_Format',
			'Ext_TC_Gui2_Filterset'					=> 'Ext_TS_Gui2_Filterset',
			'Ext_TC_Referrer' => 'Ext_TS_Referrer',

			'Ext_TC_Marketing_Feedback_Questionary' => 'Ext_TS_Marketing_Feedback_Questionary',
			'Ext_TC_Marketing_Feedback_Questionary_Gui2_Selection_SubObjects' => 'Ext_TS_Marketing_Feedback_Questionary_Gui2_Selection_Inbox',
			'Ext_TC_Marketing_Feedback_Question'	=> 'Ext_TS_Marketing_Feedback_Question',
			'Ext_TC_Marketing_Feedback_Questionary_Process' => 'Ext_TS_Marketing_Feedback_Questionary_Process',
			'Ext_TC_Marketing_Feedback_Questionary_Generator' => 'Ext_TS_Marketing_Feedback_Questionary_Generator',
			'Ext_TC_Marketing_Feedback_Questionary_Process_Gui2_Data' => 'Ext_TS_Marketing_Feedback_Questionary_Process_Gui2_Data',

			'Ext_TC_Contact_Detail' => 'Ext_TS_Inquiry_Contact_Detail',

			'\TcComplaints\Entity\Complaint' => '\TsComplaints\Entity\Complaint',
			'\TcComplaints\Gui2\Data\Complaint' => '\TsComplaints\Gui2\Data\Complaint',
			'\TcComplaints\Gui2\Data\ComplaintHistory' => '\TsComplaints\Gui2\Data\ComplaintHistory',
			'\TcComplaints\Gui2\Data\Category' => '\TsComplaints\Gui2\Data\Category',

			'\TcStatistic\Controller\StatisticController' => '\TsStatistic\Controller\StatisticController',

			'Ext_TC_Pdf_Template' => 'Ext_TS_Pdf_Template',

			'Ext_TC_Journey' => 'Ext_TS_Inquiry_Journey',

			'Ext_TC_Flexibility' => 'Ext_Thebing_Flexibility',

			'Ext_TC_User_Gui2' => 'Ext_Thebing_User_Gui2_Data',

			'Ext_TC_User_Gui2_Icon' => 'Ext_Thebing_User_Gui2_Data_Icon',
			
			\Admin_Html::class => \Ts\Helper\Admin\Html::class,
			
			\Admin\Helper\Navigation::class => \Ts\Helper\Navigation::class,

			'Ext_TC_Countrygroup_Gui2_Data' => '\Ts\Gui2\Data\CountryGroup',

			Ext_TC_Upload::class => Ext_Thebing_Upload_File::class,
			Ext_TC_Upload_Gui2_Data::class => Ext_Thebing_Upload_Gui2::class,
		);

		Ext_TC_Factory::setAllocations($aAllocations);
	
	}
	
	/**
	 * Nummernformat in der Schulsoftware abhängig von der Schule
	 * 
	 * @return array 
	 */
	public static function getNumberFormat()
	{
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aTemp = $oSchool->getNumberFormatData();
		
		return $aTemp;
	}
	
	/**
	 *
	 * Übersetzungssprachen des Mandanten
	 * 
	 * @return array
	 */
	public static function getTranslationLanguages(string $languageIso = null) {
		
		$aBack			= array();
		$aAllLanguages	= \Ext_Thebing_Data::getSystemLanguages($languageIso);
		
		foreach($aAllLanguages as $sIso => $sLabel)
		{
			$aBack[] = array(
				'iso'	=> $sIso,
				'name'	=> $sLabel
			);
		}
		
		if(empty($aBack)){
			$aBack = parent::getTranslationLanguages();
		}
		
		return $aBack;
	}

	/**
	 * Ein Array mit gemischten Daten nach möglichen Schulzahlformaten durchgehen und versuchen
	 * darüber einen Durschnitt zu bilden 
	 * 
	 * @param array $aData
	 * @return float 
	 */
	public static function getAverageFromFormattedValue($aData) {

		$aFormat = Ext_Thebing_School::getNumberFormatArray();
		
		$iCounter = 0;
		$fAverageCalc = 0;
		
		$aData = (array)$aData;

		foreach($aData as $mAverage) {
			if(!is_numeric($mAverage)) {
				foreach($aFormat as $iFormatKey => $aFormatData) {
					$mValue = Ext_Thebing_Format::convertFloat($mAverage, false, $iFormatKey);
					
					if(is_numeric($mValue)) {
						$mAverage = $mValue;
					}
				}
			}

			if(is_numeric($mAverage)) {
				$fAverageCalc += (float)$mAverage;

				$iCounter++;
			}

		}

		if($iCounter <= 0) {
			return null;
		} else {
			$fAverage = $fAverageCalc / $iCounter;
			
			return Ext_Thebing_Format::Number($fAverage);
		}
	}

	/**
	 * Timezone determinieren und setzen
	 * @return string
	 */
	public static function getAndSetTimezone()
	{
        $iSessionSchoolId = \Ext_Thebing_School::getSchoolIdFromSession();
		$aSchools = Ext_Thebing_Client::getSchoolList(false);
		$sTimezone = '';

		// Schuleinstellung nehmen, wenn
		// 1. Schulen angelegt wurden
		// 2. Schule ausgewählt ist
		if(
			!empty($aSchools) &&
			isset($iSessionSchoolId) &&
            $iSessionSchoolId > 0
		) {
			$oSchool = Ext_Thebing_Client::getFirstSchool();
			$sTimezone = $oSchool->timezone;
		}

		// Zentrale Einstellung nehmen, wenn
		// 1. All-Schools
		// 2. Schuleinstellung leer ist
		if(empty($sTimezone)) {
			$oClient = Ext_Thebing_Client::getFirstClient();
			$sTimezone = $oClient->timezone;
		}

		$bReturn = false;
		if(!empty($sTimezone)) {
			$bReturn = self::setTimezone($sTimezone);
		}

		/*
		 * Wenn die Schule keine Zeitzone hat oder diese nicht erkannt wird,
		 * dann muss eine default Zeitzone gewählt werden in dem Fall UTC.
		 * Ebenso muss eine E-Mail an uns Thebing gesendet werden, damit man handeln kann.
		 */
		if(!$bReturn) {
			$bReturn = self::setTimezone('UTC');
			self::reportError('getLocale: Timezone "'.$sTimezone.'" doesn\'t exist on this server!');
		}

		return $bReturn;
	}
	
	public static function getNumberFormatData($iNumberFormat)
	{
		if(!is_numeric($iNumberFormat))
		{
			$oFirstSchool = Ext_Thebing_Client::getFirstSchool();
			$iNumberFormat = $oFirstSchool->number_format;
		}
		
		$aData = parent::getNumberFormatData($iNumberFormat);
		
		return $aData;
	}
	
	public static function getClientName() {
		$oClient = Ext_Thebing_System::getClient();
		$sClientName = $oClient->name;
		
		return $sClientName;
	}
	
	public static function saveClientName($sClientName) {

		// Direkt aktualisieren, da die Index Registry ziemlich unglücklich bei Client-Relationen sein kann
		DB::updateData('kolumbus_clients', array('name' => $sClientName), " `active` = 1");

//		$oClient = Ext_Thebing_System::getClient();
//		$oClient->name = $sClientName;
//		$oClient->save();
	}
	
	/**
	 * Liefert die Systemfarbe
	 * 
	 * @return string
	 */
	public static function getSystemColor() {
		
		$sSystemColor = '#ef5e50';
		
		if(Ext_Thebing_System::isAllSchools()) {
			$oObject = Ext_Thebing_System::getClient();			
		} else {
			$oObject = Ext_Thebing_School::getSchoolFromSession();
		}
		
		$aData = $oObject->getData();
		if(
			!empty($aData['system_color']) &&
			substr($aData['system_color'], 0, 1) === '#' &&
			strlen($aData['system_color']) === 7
		) {	
			$sSystemColor = $aData['system_color'];
		}

		return $sSystemColor;
	}

	/**
	 * Gibt die verfügbaren Frontend- und Backendsprachen wieder
	 *
	 * @param string $sType
	 * @return array
	 */
	public static function getLanguages($sType = 'frontend') {

		$aBack = array();
	
		if($sType == 'backend') {
			
			$aBack = parent::getLanguages($sType);

		} elseif($sType == 'frontend') {

			$aBack = Ext_Thebing_Data::getSystemLanguages();

		}

		return $aBack;

	}

	/**
	 * Gibt die Backendsprache zurück, die in den Sprachen der Installation vorkommt, 
	 * oder die Standardsprache der gewählten Schule.
	 * 
	 * @return string
	 */
	public static function getInterfaceLanguage() {

		$sLanguage = Ext_Thebing_School::fetchInterfaceLanguage();

		return $sLanguage;
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getColor($sUse = false, $iFactor = 100) {

		$aColors = [];
		$aColors['matching_share'] = '#80ff80';
		$aColors['matching_male'] = '#22BBFF';
		$aColors['matching_female'] = '#FF8080';
		$aColors['matching_other_school'] = '#E0E0EB';

		if(!$sUse) {
			return $aColors + parent::getColor();
		}

		if(!isset($aColors[$sUse])) {
			return parent::getColor($sUse, $iFactor);
		}

		return \Core\Helper\Color::applyColorFactor($aColors[$sUse], $iFactor);
	}

	/**
	 * {@inheritdoc}
	 */
	public static function getSecureDirectory($bDocumentRoot=false) {

		$sDirectory = '/storage/ts/';

		if($bDocumentRoot === true) {
			$sDirectory = Util::getDocumentRoot(false).$sDirectory;
		}

		return $sDirectory;

	}

	public static function getFrameworkLogosHook(&$mixInput) {

		$mixInput['framework_logo_small'] = '/admin/assets/media/fidelo_signet_black.svg';
		$mixInput['dark:framework_logo_small'] = '/admin/assets/media/fidelo_signet_white.svg';
		$mixInput['framework_logo'] = '/assets-public/ts/fidelo_logo_positiv.svg';
		$mixInput['dark:framework_logo'] = '/assets-public/ts/fidelo_logo_negativ.svg';
		$mixInput['start_headline_color'] = '#000000';
		$mixInput['dark:start_headline_color'] = '#ffffff';
		//$mixInput['support_logo'] = '/assets-public/ts/fidelo_school_signet_red.svg';

		$mixInput['login_logo'] = '/assets-public/ts/fidelo_school_red.svg';
		$mixInput['dark:login_logo'] = '/assets-public/ts/fidelo_logo_negativ.svg';

		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		$sLogo = null;

		if(
			$oSchool && 
			$oSchool->exist()
		) {
			$sLogo = $oSchool->getLogo();
		}

		if(!is_file(\Util::getDocumentRoot().$sLogo)) {
			$oClient = Ext_Thebing_Client::getInstance();
			$sLogo = $oClient->getFilePath(false).'logo.png';
		}

		if(is_file(\Util::getDocumentRoot().$sLogo)) {
			$mixInput['system_logo'] = $sLogo;
		}

	}

	/**
	 * Blocktage, die hintereinander sind, werden als von-bis zurückgegeben, anderenfalls werden alle aufgelistet
	 *
	 * @param array $aDays
	 * @param string $sLanguage
	 *
	 * @return string $sDays
	 */
	public static function buildJoinedWeekdaysString(array $aDays, string $sLanguage, $bShort=true) {

		$aLocaleDays = \Ext_TC_Util::getLocaleDays($sLanguage, $bShort?'short':'wide');

		asort($aDays);

		$iFirstDay = reset($aDays);
		$iLastDay = end($aDays);

		$aDaysAsKeys = array_flip($aDays);

		// Die korrekte Nummernfolge von dem ersten bis zum letzten Eintrag holen
		$aRange = range($iFirstDay , $iLastDay);

		// Prüfen ob es eine Lücke gibt
		if(!empty(array_diff($aRange, $aDays))) {

			// Lücke vorhanden - einzeln auflisten Mo, Di, Fr
			$sDays = implode(', ', array_intersect_key($aLocaleDays, $aDaysAsKeys));

		} else {

			$aDayKeysRange = array_flip([$iFirstDay, $iLastDay]);

			// Die entsprechenden übersetzten Tage (ersten und letzen) holen
			$sDays = implode('-', array_intersect_key($aLocaleDays, $aDayKeysRange));

		}

		return $sDays;
	}

	/**
	 * String (z.B. übliches WDBasic from/until) zu Carbon-Objekt konvertieren, wenn gültig
	 *
	 * @param string $sDate
	 * @return \Carbon\Carbon|null
	 */
	public static function convertDateStringToDateOrNull(string $sDate) {

		if(\Core\Helper\DateTime::isDate($sDate, 'Y-m-d')) {
			return new \Carbon\Carbon($sDate, 'UTC');
		}

		return null;

	}

}
