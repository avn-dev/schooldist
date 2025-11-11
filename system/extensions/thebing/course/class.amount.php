<?php

class Ext_Thebing_Course_Amount {

	use \Ts\Traits\SpecialAmount;
	
	protected $_iCourseId = 0;
	protected $_iWeeks = 0;

	/** @var int|float  */
	protected $_iUnits = 0;
	protected $_iCalculateStart = 0;
	protected $_iCalculateEnd = 0;
	protected $_iDiscountTimepoint = 0;	
	protected $nationality;

	/**
	 * @var Ext_Thebing_Saison
	 */
	protected $_oSaison = null;

	/**
	 * @var Ext_Thebing_Currency
	 */
	protected $_oCurrency = null;

	/**
	 * @var Ext_Thebing_Agency
	 */
	protected $_oAgency = null;

	/**
	 * @var Ext_TS_Inquiry_Abstract
	 */
	protected $_oInquiry = null;

	/**
	 * @var Ext_TS_Inquiry_Journey_Course|Ext_TS_Enquiry_Combination_Course
	 */
	protected $_oInquiryCourse = null;

	protected $_sCalculateType = 'price';
	protected $_sCalculateModel = 'week';

	/**
	 * @var Ext_Thebing_School
	 */
	protected $_oSchool = null;

	protected $_bAgencyAmount = false;

	// Special
	protected $_fSpecialAmount = 0;
	protected $_fSpecialAmountNetto = 0;
	protected $_aSpecialBlocks = [];

	public $aErrors = [];
	public $aWeekPrices = []; // Immer brutto!
	public $aWeekPricesSpecial = [];

	public $aCalculationDescription;
	public $aSpecialCalculationDescription = [];

	public $aSpecialPeriods = [];
	public $oSpecialPeriod;
	
//	public function  __construct() {
//	}
//
//	public function setCourse($iCourse){
//		$this->_iCourseId = $iCourse;
//	}
//
//	public function setWeeks($iWeeks){
//		$this->_iWeeks = $iWeeks;
//	}
//
//	public function setUnits($iUnits){
//		$this->_iUnits = $iUnits;
//	}

	/**
	 * Liefert den SpecialBetrag
	 */
	public function getSpecialAmount($bNetto = false):float {
		if($bNetto == true) {
			return $this->_fSpecialAmountNetto;
		} else {
			return $this->_fSpecialAmount;
		}
	}

	public function getCalculationDescription() {
		return $this->aCalculationDescription;
	}

	/**
	 * Liefert die verwendeten Special Blöcke
	 */
	public function getSpecialBlocks():array {
		return $this->_aSpecialBlocks;
	}
		
	/**
	 * Setze die Schule
	 *
	 * @param int $iSchool
	 */
	public function setSchool($iSchool = null) {

		if($iSchool === null) {
			$this->setSchoolObject(Ext_Thebing_School::getSchoolFromSession());
			return true;
		}

		$iSchool = (int)$iSchool;
		if($iSchool <= 0) {
			return false;
		}

		$this->setSchoolObject(Ext_Thebing_School::getInstance($iSchool));
		return true;

	}

	/**
	 * @param Ext_Thebing_School $oSchool
	 */
	public function setSchoolObject(Ext_Thebing_School $oSchool = null) {
		$this->_oSchool = $oSchool;
	}

	/**
	 * Setzt den Zeitraum der Berechnung
	 *
	 * @param int $iStart
	 * @param int $iEnd
	 */
	public function setCalculateTime($iStart, $iEnd){
		$this->_iCalculateStart = $iStart;
		$this->_iCalculateEnd = $iEnd;
	}

	/**
	 * @param int $iCurrency
	 */
	public function setCurrency($iCurrency){
		$this->setCurrencyObject(new Ext_Thebing_Currency($iCurrency));
	}

	/**
	 * @param Ext_Thebing_Currency $oCurrency
	 */
	public function setCurrencyObject(Ext_Thebing_Currency $oCurrency = null) {
		$this->_oCurrency = $oCurrency;
	}

	public function setAgency($iAgency, $iSchool = 0){

		$oSchool = null;
		if($iSchool > 0) {
			// Schule muss gesetzt werden für All-School Inbox
            // KEIN GET INSTANCE!
			$oSchool = new Ext_Thebing_School($iSchool);
		}

		$oAgency = Ext_Thebing_Agency::getInstance($iAgency);
		$oAgency->setSchool($oSchool); // TODO $oSchool kann null sein, aber setSchool() würde das nicht akzeptieren
		$this->_oAgency = $oAgency;
	}

	/**
	 * Setzt alle Informationen einer gebuchten Unterkufnt
	 *
	 * @throws \LogicException
	 * @param int|Ext_TS_Service_Interface_Course $mInquiryCourse
	 * @return bool
	 */
	public function setInquiryCourse($mInquiryCourse){

		if($mInquiryCourse instanceof Ext_TS_Service_Interface_Course) {
			$oInquiryCourse = $mInquiryCourse;
		} elseif(is_numeric($mInquiryCourse)) {
			$oInquiryCourse	= Ext_TS_Inquiry_Journey_Course::getInstance($mInquiryCourse);
		} else {
			$sMsg = 'Course Service must be an instance of Ext_TS_Service_Interface_Course!';
			throw new \LogicException($sMsg);
		}

		// Für die Preisberechnung in der Preisliste brauche ich auch noch nicht gespeicherte Kursbuchungen
		if($oInquiryCourse->active != 1) {
			return false;
		}

		$this->_oInquiryCourse = $oInquiryCourse;
		$this->_iCourseId = (int)$this->_oInquiryCourse->course_id;
		$this->_iWeeks = (int)$this->_oInquiryCourse->weeks;
		$this->_iUnits = (float)$this->_oInquiryCourse->getUnits();
		$this->_oInquiry = $this->_oInquiryCourse->getInquiry();
		$this->nationality = $this->_oInquiry->getCustomer()->nationality;

		if(empty($this->nationality)) {
			$this->nationality = null;
		}

		$this->_iDiscountTimepoint = $this->_oInquiry->getCreatedForDiscount(); // Zeitpunkt für Frühbucherrabattbestimmung

		$this->setSchoolObject($this->_oInquiry->getSchool());
		$this->setCurrency($this->_oInquiry->getCurrency());
		$this->setAgency($this->_oInquiry->agency_id, $this->_oSchool->id);

		$oWDDate = new WDDate();

		$oWDDate->set($oInquiryCourse->from, WDDate::DB_DATE);
		$iStart = $oWDDate->get(WDDate::TIMESTAMP);
		$this->_iCalculateStart = $iStart;

		$oWDDate->set($oInquiryCourse->until, WDDate::DB_DATE);
		$iEnd = $oWDDate->get(WDDate::TIMESTAMP);
		$this->_iCalculateEnd = $iEnd;

		return true;

	}

	/**
	 * @return bool
	 */
	protected function checkSchool() {
		return $this->_oSchool instanceof Ext_Thebing_School;
	}

	protected function setSaison(\Carbon\Carbon $date=null) {

		if(!$this->checkSchool()) {
			return;
		}

		$searchDate = $this->_iCalculateStart;
		if($date !== null) {
			$searchDate = $date->toDateString();
		}

		$oSeason = new Ext_Thebing_Saison($this->_oSchool, true , false, false, false);
		$iSeason = $oSeason->search($searchDate, 'course', $this->_iDiscountTimepoint);

		$oSeason->setSaisonById($iSeason);
		if($iSeason > 0) {
			$this->_oSaison = $oSeason;
		} else {
			$this->_oSaison = null;
			$this->setSaisonError();
		}

	}

	/**
	 * Setzt den Zeitpunkt für die Saisonberechnung bei frühbucher Rabatt
	 * Wichtig für das Anmeldeformular
	 *
	 * @param int $iTime 
	 */
	public function setDiscountTime($iTime){
		$this->_iDiscountTimepoint = (int)$iTime;
	}

	public function getWeekPrice($iWeekId, $bWithSign = false) {

		$fPrice = 0;

		if(!$this->_oSaison) {
			return 0;
		}

		$oPrice = new Ext_Thebing_Price($this->_oSchool, $this->_oSaison, $this->_oCurrency, '', $this->nationality, $this->_oAgency?->getCategory());
		$aWeek = $oPrice->getWeekById($iWeekId);
		if($aWeek) {
			$oPrice->setWeek($aWeek);
		}

		$course = Ext_Thebing_Tuition_Course::getInstance($this->_iCourseId);

		if($course->different_price_per_language == 1) {
			$languageId = $this->_oInquiryCourse->courselanguage_id;
		} else {
			$languageId = null;
		}

		if($this->_oInquiry->partial_invoices_terms > 0) {
			$fPrice = $oPrice->getCoursePrice($this->_iCourseId, $iWeekId, $bWithSign, $this->_oInquiry->partial_invoices_terms, $languageId);
		}

		if(empty($fPrice)) {
			$fPrice = $oPrice->getCoursePrice($this->_iCourseId, $iWeekId, $bWithSign, null, $languageId);
		}

		return $fPrice;
	}

	/**
	 * @param bool $bAgencyAmount
	 * @param int $iStartWeek
	 * @return float
	 */
	public function calculate($bAgencyAmount = false, $iStartWeek = 0) {

		$this->_bAgencyAmount = $bAgencyAmount;
		$this->aWeekPrices = array();
		$this->aWeekPricesSpecial = array();
		
		$course = $this->_oInquiryCourse->getCourse();

		if(
			!$course->calculateByUnit() &&
			$course->price_calculation === 'fixed'
		) {
			
			return $this->calculateFixed($iStartWeek);
			
		} elseif(
			!$course->calculateByUnit() &&
			$course->price_calculation === 'month'
		) {
			
			return $this->calculateMonth();
			
		} else {
			
			$this->setSaison();
			
			return $this->calculateWeek($iStartWeek);
			
		}	

	}

	/**
	 * Berücksichtig keine Ferien, keine Specials
	 * 
	 * @param int $iStartWeek
	 */
	protected function calculateMonth() {
		
		$iMonths = Ext_TS_Inquiry_Journey_Service::getMonthCount($this->_oInquiryCourse);
		
		$oCalculateStart = Carbon\Carbon::createFromTimestamp($this->_iCalculateStart);
		$oCalculateEnd = Carbon\Carbon::createFromTimestamp($this->_iCalculateEnd);

		$aCalculateMonths = \Core\Helper\DateTime::getMonthPeriods($oCalculateStart, $oCalculateEnd, false);
		
		$totalPrice = 0;
		foreach($aCalculateMonths as $iCalculateMonth=>$aCalculateMonth) {
			
			$this->_iCalculateStart = $aCalculateMonth->from->getTimestamp();
			
			$this->setSaison(new \Carbon\Carbon($aCalculateMonth->from));
			
			$factor = Ext_TS_Inquiry_Journey_Service::getMonthCount($aCalculateMonth);

			$monthPrice	= $this->getWeekPrice(0);
			
			$price = $monthPrice * $factor;
			
			$this->aWeekPrices[$this->_iCalculateStart] = $price;
			
			$totalPrice += $price;
			
		}
		
		$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount($this->aWeekPrices, $this->_oInquiryCourse, $iCalculateMonth);
		$oSpecialAmount->setCalculationType('monthly');
		$fSpecialAmount = $oSpecialAmount->getAmount();

		if($fSpecialAmount > 0) {

			$this->aSpecialPeriods = $oSpecialAmount->aSpecialPeriods;
			$this->oSpecialPeriod = $oSpecialAmount->oSpecialPeriod;

			$this->aSpecialCalculationDescription = $oSpecialAmount->getCalculation();

			$this->_aSpecialBlocks = $oSpecialAmount->getBlocks();
			$this->specialCodes = $oSpecialAmount->getSpecialCodes();

			$this->_fSpecialAmount += $fSpecialAmount;
		}

		return $totalPrice;
	}

	protected function calculateFixed($startWeek=0) {

		// Wenn es hier eine Start-Woche gibt, dann wurde der einmalige Preis bereits berechnet
		if($startWeek > 0) {
			return 0;
		}
		
		$this->setSaison();

		$price	= $this->getWeekPrice(0);

		if(
			$this->_bAgencyAmount == true &&
			$this->_oAgency
		){
			$schoolCommission = $this->_oAgency->getSchoolProvisions($this->_oSaison->getSaisonId());

			$commission = $schoolCommission->getCourseProvision($this->_iCourseId);

			if ($commission) {
				$price = $price - $commission->calculate((float)$price);
			}
			
		}
		
		$this->aWeekPrices[$this->_iCalculateStart] = $price;

		$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount($this->aWeekPrices, $this->_oInquiryCourse, 0);
		$oSpecialAmount->setCalculationType('monthly');
		$fSpecialAmount = $oSpecialAmount->getAmount();

		if($fSpecialAmount > 0) {

			$this->aSpecialPeriods = $oSpecialAmount->aSpecialPeriods;
			$this->oSpecialPeriod = $oSpecialAmount->oSpecialPeriod;

			$this->aSpecialCalculationDescription = $oSpecialAmount->getCalculation();

			$this->_aSpecialBlocks = $oSpecialAmount->getBlocks();
			$this->specialCodes = $oSpecialAmount->getSpecialCodes();

			$this->_fSpecialAmount += $fSpecialAmount;
		}

		return $price;
	}
	
	/**
	 * Kurse die außerhalb von season liegen
	 */
	public function setSaisonError() {
		$this->aErrors['course_season_not_found'][] = (int)$this->_oInquiryCourse->id;
	}

	/**
	 * season für Kurs gefunden
	 */
	public function setSeasonFound() {
		$this->aErrors['course_season_found'][] = (int)$this->_oInquiryCourse->id;
	}

	public function setWeekError($aWeekError) {
		foreach((array)$aWeekError as $sKey => $iCourseId) {
			$this->aErrors[$sKey][] = (int)$iCourseId;
		}
	}

	public function getWeekByNumber($iWeek, $oCourseUtil) {

		if($oCourseUtil->calculateByUnit()) {
			$aWeek = $this->_oSchool->getCourseUnitByPosition($iWeek, $oCourseUtil);
		} else {
			if($oCourseUtil->getField('price_calculation') === 'week') {
				$aWeek = $this->_oSchool->getWeekByPosition($iWeek, $oCourseUtil);
			} else {
				// Bei monatlich statisch 0, da es nur einen Preis gibt
				$aWeek = [
					'id' => 0,
					// Immer als Extrawoche behandeln, damit nicht nach NPS gerechnet wird
					'extraWeek' => [
						'id' => 0,
						'start_week' => 1
					]
				];
			}
		}

		return $aWeek;

	}

	public function getWeekByCount($iWeekCount, $oCourseUtil) {

		if(
			$oCourseUtil->calculateByUnit()
		) {
			$aWeek = $this->_oSchool->getCourseUnitByNumberOfCourseUnits($iWeekCount, $oCourseUtil);
		} else {
			if($oCourseUtil->getField('price_calculation') === 'week') {
				$aWeek = $this->_oSchool->getWeekByNumberOfWeeks($iWeekCount, $oCourseUtil);
			} else {
				// Bei monatlich statisch 0, da es nur einen Preis gibt
				$aWeek = [
					'id' => 0
				];
			}
		}

		return $aWeek;

	}

	/**
	 * @param int $iStartWeek Nur für fortlaufende Preisberechnung relevant
	 * @return float
	 */
	public function calculateWeek($iStartWeek = 0, $holidayCheck=true) {

		// Saison Marken die zum Berechnen von Lektionskursen verwendet wurde
		$iTempLectionSeason = null;

		// Util Klasse aufbauen (hat diverse methoden um an wochen etc. einer Unterkunft zu kommen)
		$oCourseUtil = new Ext_Thebing_Course_Util($this->_oSchool);
		$oCourseUtil->setCourse($this->_iCourseId);

		// Gibt die Tatsächliche Woche an die gerade berechnet wird
		$iCurrentCourseWeek = 0;

		$aCalculation = [];

		// Auf Feriensplittung prüfen!
		// Nur bei nicht foltlaufend nötig also wenn keine start woche angegeben ist
		// Hier werden die totalen Wochen vom Kurs summiert, damit die korrekte Preiswoche gefunden wird
		if(
			$holidayCheck === true &&
			$iStartWeek <= 0 &&
			$this->_oInquiryCourse &&
			$this->_oInquiryCourse->checkHoliday()
//			$this->_oInquiryCourse->id
		) {

			$iTempAllOtherWeeks = 0;
			$iTempAllOtherUnits = 0;
			$iTempAllWeeksBefore = 0;
			$iTempAllUnitsBefore = 0;

			/*
			 * Früher wurden hier nur die Schulferien beachtet, in der buildItems() wurde das dann über $iStartWeek gelöst,
			 * auch für Schülerferien. Da man das scheinbar übersah, wurden hier (Amount-Klasse) ein paar Sachen auskommentiert,
			 * da ansonsten zu viele Wochen zusammen summiert wurden.
			 *
			 * Zudem muss es einen Bug gegeben haben, dass mehrfach gesplittete Kurse nicht korrekt berechnet wurden,
			 * da immer nur der nächste Teil beachtet werden konnte.
			 *
			 * Die gleiche Implementierung existiert auch in der Accommodation_Amount.
			 */
			$aRelatedCourses = $this->_oInquiryCourse->getRelatedServices();
			$aRelatedCourses = array_filter($aRelatedCourses, function(Ext_TS_Inquiry_Journey_Course $oJourneyCourse) {
				return $oJourneyCourse !== $this->_oInquiryCourse;
			});

			// Prüfen, ob alte und neue Methode dasselbe zurückliefern – das hier ist die Preisberechnung!
			/*$aStructurIDs = Ext_Thebing_Inquiry_Course_Structure::findAllOtherAssignments($this->_oInquiryCourse->id);
			$aRelatedCourseIds = array_column($aRelatedCourses, 'id');
			if(
				// Im Frontend (Preisberechnung) gibt es keine IDs, daher auch niemals ein Resultat vom Query
				System::getInterface() === 'backend' &&
				array_diff($aRelatedCourseIds, $aStructurIDs) !== array_diff($aStructurIDs, $aRelatedCourseIds)
			) {
				// findHolidayStructure() schafft bei Schülerferien nur eine Ebene, während die neue Methode die ganze Kette schafft #12973
				if(count($this->_oInquiry->holidays) <= 1) {
					throw new RuntimeException('Price calculation error: $aRelatedCourses != $aStructurIDs');
				}
			}*/

			foreach($aRelatedCourses as $oTempInquiryCourse) {

				if($oTempInquiryCourse->active != 1) {
					continue;
				}

				$iTempAllOtherWeeks += $oTempInquiryCourse->weeks;
				$iTempAllOtherUnits += $oTempInquiryCourse->getUnits();
				$oWDDate = new WDDate($this->_oInquiryCourse->from, WDDate::DB_DATE);
				if($oWDDate->compare($oTempInquiryCourse->from, WDDate::DB_DATE) == 1) {
					$iTempAllWeeksBefore += $oTempInquiryCourse->weeks;
					$iTempAllUnitsBefore += $oTempInquiryCourse->getUnits();
				}

			}

			if(
				(
					$this->_oSchool->price_structure_week == 0 &&
					!$oCourseUtil->calculateByUnit()
				) || (
					$this->_oSchool->price_structure_unit == 0 &&
					$oCourseUtil->calculateByUnit()
				)
			) {

				if($oCourseUtil->calculateByUnit()) {
					// WICHTIG! Das Darf hier nicht sein, da bei durch Ferien
					// gesplittete Lektionskurse sonst falsch berechnet wird!!!

					// Wieder drin, vorher wurde in buildItems() bei Ferien fortlaufende Preisberechnung gefakt
					$iStartWeek = $iTempAllUnitsBefore;
				} else {
					$iStartWeek = $iTempAllWeeksBefore;
				}

			} elseif(
				(
					$this->_oSchool->price_structure_week == 1 &&
					!$oCourseUtil->calculateByUnit()
				) || (
					$this->_oSchool->price_structure_unit == 1 &&
					$oCourseUtil->calculateByUnit()
				)
			) {

				if($oCourseUtil->calculateByUnit()) {
					$iStartWeek = $iTempAllOtherUnits;
				} else {
					$iStartWeek = $iTempAllOtherWeeks;
				}

			}

		}

		$fAmount = 0;
		$aPriceFinal = [];

		$iWeeks = $this->_iWeeks + $iStartWeek;
		$iWeekCount = 1;
		$iWeekCount = $iWeekCount + $iStartWeek;

		$iUnits = $this->_iUnits + $iStartWeek;
		$iUnitCount = 1;
		$iUnitCount = $iUnitCount + $iStartWeek;

		$iCalculateStartOrginal = $this->_iCalculateStart;

		$aPriceStructureSingle = Ext_Thebing_School_Gui2::getPriceStructureSingle();
		$aPriceStructureWeeks = Ext_Thebing_School_Gui2::getPriceStructureWeeks();

		####################################
		## PREISE                         ##
		## Preisberechnung Preis je woche ##
		## "normale Preisstruktur"		  ##
		####################################
		if(
			(
				$this->_oSchool->price_structure_week == 0 &&
				!$oCourseUtil->calculateByUnit()
			) || (
				$this->_oSchool->price_structure_unit == 0 &&
				$oCourseUtil->calculateByUnit()
			)
		) { // NORMALE PREISSTRUKTUR

			if($oCourseUtil->calculateByUnit()) {
				$aCalculationDescription[] = $aPriceStructureSingle[0];
			} else {
				$aCalculationDescription[] = $aPriceStructureWeeks[0];
			}

			$bSeasonChanged = false;
            $bSeasonChangedForExtraWeek = false;
			$aLastSeasonWeek = 0;
			$iLastPrice = 0;
			$aExtraWeekCount = [];
			$iPriceFromLastWeekOfLastSaisionForCurrentSaision = 0;

			// letzte Saison ist die Aktuelle da wir gerade erst starten :)
			$oLastSeason = $this->_oSaison;

			$iLastPriceOfWeek = 0;

			$iCounter = $iWeeks;
			$iStartCount = $iWeekCount;
			if($oCourseUtil->calculateByUnit()) {
				$iUnitCount = (float)$this->_iUnits;
				$iCounter = $iUnits;
			    $iStartCount = $iUnitCount;
			}

			// Endlosschleife ausschließen
			if($iCounter > 20000) {
				$iCounter = 20000; 
			}

			$iSetPrice = 0;
			for($i = $iStartCount; $i <= $iCounter; $i++) {

				$aCalculation[$iSetPrice] = Ext_Thebing_Format::LocalDate($this->_oSaison->valid_from).' - '.Ext_Thebing_Format::LocalDate($this->_oSaison->valid_until).': ';
				
				// Fehler zwischenspeichern
				$aErrorsCache = $this->aErrors;
				
				// Gerade zu berechnende Woche DIESES Kurses
				$iCurrentCourseWeek++;

				// Wenn keine Saison gefunden wird kann nichts berechnet werden
				if(
					!$this->_oSaison ||
					 $this->_oSaison->getSaisonId() <= 0
				) {
					// Fehlerarray fullen damit ein Fehler in der buchungsmaske sichtbar wird
					$this->setSaisonError();
					continue;
				} else {
					// saison gefunden (nur zur Info im Rechnungsdialog)
					$this->setSeasonFound();
				}

				$iSaisonId = $this->_oSaison->getSaisonId();
				$iPrice = 0;
				$aPriceOfWeek = [];

				// Saison merken für Lektionskurse
				if($oCourseUtil->calculateByUnit()) {
					$iTempLectionSeason = (int)$iSaisonId;
				}

				// Wenn $iStartWeek > 0 => gibt es einen vorgänger kurs
				$aWeekForDiff = [];
				if($iStartWeek > 0) {
				    $aWeekForDiff = $this->getWeekByNumber($iStartWeek, $oCourseUtil);
				}

				// Aktuelle Woche
				$aWeek = $this->getWeekByNumber($i, $oCourseUtil);
	
				// Fehler bei der Wochenberechnung
				if(isset($aWeek['error'])){
					$this->setWeekError($aWeek['error']);
				}

				if(!empty($aWeek)) {

					// Wenn sich die Saison ändert
					if(
						$bSeasonChanged //&&
						//$iLastSaisionWeek > 0 // Auskommentiert da es sonst nicht klappt wenn wir nur extra wochen haben + saison wechsel
					) {
						
						// Der Preis der letzten Saison und der dort benutzen Woche ausrechnen
						$iPriceFromLastWeekOfLastSaisionForCurrentSaision = $this->getWeekPrice($aLastSeasonWeek['id']);
						$sPriceFromLastWeekOfLastSaisionForCurrentSaision = $iPriceFromLastWeekOfLastSaisionForCurrentSaision.' ('.$aLastSeasonWeek['start_week'].'W)';

						// Aktueller Preis ermitteln
						$iPriceNow = $this->getWeekPrice($aWeek['id']);
						$sPriceNow = $iPriceNow.' ('.$aWeek['start_week'].'W)';

						// Wenn wir nun in einer Extra woche sind
						// muss anderst gerechnet werden
						if($aWeek['extraWeek']) {

                            $iExtraWeekStart = $aWeek['start_week'];
							if(!$aWeek['start_week']){
								$iExtraWeekStart = $aWeek['extraWeek']['start_week'] - 1;
							}
                            
							// Anzahl der Extra wochen berechnen
							$aExtraWeekCount[$iSaisonId]	= ($i - $iExtraWeekStart);
							// Extraprochenpreis
							$iExtraWeekPrice				= $this->getWeekPrice($aWeek['extraWeek']['id']);

							// Preis + Extrawochenreis * anzahl der Extra wochen DIESER Saison
							if(!$oCourseUtil->calculateByUnit()) {
								// Wochenpreis
								$iTempPrice	= $this->getWeekPrice($aWeek['id']);

								$sPriceNow = $iTempPrice.' ('.$aWeek['start_week'].'W) + ('.$iExtraWeekPrice.' * '.$aExtraWeekCount[$iSaisonId].'EW)';
								$iPriceNow = $iTempPrice + ($iExtraWeekPrice * $aExtraWeekCount[$iSaisonId]);
							}

							// Preis der Letzten woche der letzten Saison anpassen, Extrawochenpreis aller Extrawochen der LETZTEN Saison muss hinzuadiert werden
							$sPriceFromLastWeekOfLastSaisionForCurrentSaision .= ' + ('.$iExtraWeekPrice.' * '.(int)$aExtraWeekCount[$oLastSeason->getSaisonId()].'EW)';
							$iPriceFromLastWeekOfLastSaisionForCurrentSaision = $iPriceFromLastWeekOfLastSaisionForCurrentSaision + ((int)$aExtraWeekCount[$oLastSeason->getSaisonId()] * $iExtraWeekPrice);

							$aPriceOfWeek['price'] = $iExtraWeekPrice;
							$aPriceOfWeek['type'] = 'single';

						}

						// den kompletten Preis, den der gesamte Kurs in der neuen Saison kosten würde
						// muss um den Preis verringert werden die in der alten (ersten) saison stattfinden
						$aCalculation[$iSetPrice] .= $sPriceNow.' - '.$sPriceFromLastWeekOfLastSaisionForCurrentSaision;

						$iPrice = $iPriceNow - $iPriceFromLastWeekOfLastSaisionForCurrentSaision;

						if(!$aWeek['extraWeek']) {

							// Preis der letzten Woche in dieser Saison holen
							$aLastWeek = $this->getWeekByNumber($i-1, $oCourseUtil);
							$iLastWeekPrice	= $this->getWeekPrice($aLastWeek['id']);

							// Preis der Vorwoche von dem Preis der aktuellen Woche abziehen, um den Preis der einzelnen Woche zu erhalten
							$aPriceOfWeek['price'] = $iPriceNow - $iLastWeekPrice;
							$aPriceOfWeek['type'] = 'single';
						}

					} else {

						// Wenn extra Wochen
						if($aWeek['extraWeek']){

							if(!isset($aWeek['start_week'])) {
								$aWeek['start_week'] = 0;
							}

							// Extrawochenpreis
							$iExtraWeekPrice = $this->getWeekPrice($aWeek['extraWeek']['id']);
							
							// Bei Lektionskurs und Extralektion wird nur mit den Extralektionen gerechnet
							if (!$oCourseUtil->calculateByUnit()) {

								// Anzahl Extra Wochen in dieser Saison
								$iExtraWeekCount = ($i - (int)$aWeek['start_week']);

								if($iExtraWeekCount <= 0){
									$iExtraWeekCount = 1;
								}

								$aExtraWeekCount[$iSaisonId] = $iExtraWeekCount;

								// Normalerpreis
								$iTempPrice = 0;
								if(!empty($aWeek['id'])) {
									$iTempPrice = $this->getWeekPrice($aWeek['id']);
								}

								// --------------------------------

								// Wenn jemand NUR 1 Kurs bucht (gibt nur 1. Extrawoche in der Preispflege)
								// muss hier der TempPrice resettet werden da er sonst um 1 Woche zu hoch ist!
								if($iCounter == 1 ){
									$iTempPrice = 0;
								}
								
								$fMultiplier = $aExtraWeekCount[$iSaisonId];

								// Preis errechnen
								$aCalculation[$iSetPrice] .= $iTempPrice.' + '.$iExtraWeekPrice.' * '.$fMultiplier.'EW';
								
								$iPrice	= $iTempPrice + $iExtraWeekPrice * $fMultiplier;

								$aPriceOfWeek['price'] = $iExtraWeekPrice;
								
								$aPriceOfWeek['type'] = 'single';
								
							} else {
								
								$aCalculation[$iSetPrice] .= $iExtraWeekPrice.' * '.$i.'W';
								
								$iPrice = $iExtraWeekPrice * $i;

								$aPriceOfWeek['price'] = $iExtraWeekPrice;
								$aPriceOfWeek['type'] = 'single';
								
							}

						// Wenn keine Extra Woche
						} else {

							// Hole den Preis
							$iPrice = $this->getWeekPrice($aWeek['id']);
						
							$aCalculation[$iSetPrice] .= $iPrice.' ('.$aWeek['start_week'].'W)';

							$aPriceOfWeek['price'] = $iPrice;
							$aPriceOfWeek['type'] = 'total_weeks';

						}

					}

				}

				// Wenn eine Startwoche gesetzt wurden, dann muss die Differenz abgezogen werden
				if(
					$iStartWeek > 0 &&
					!$oCourseUtil->calculateByUnit()
				) {

					$aCalculation[$iSetPrice] .= ', SW: '.$iStartWeek;

					$tmpCalculateStart = $this->_iCalculateStart;

//					$calculateStartOrginal = \Carbon\Carbon::createFromTimestamp($iCalculateStartOrginal);
//					$calculateStartOrginal->subWeeks($iStartWeek);
//					
//					$this->_iCalculateStart = $calculateStartOrginal->getTimestamp();

					$originalWeeks = $this->_iWeeks;
					$this->_iWeeks = $iStartWeek;
					$iPriceForDiff = $this->calculateWeek(0, false);
					$this->_iWeeks = $originalWeeks;
					
					// Startpunkt nicht beeinflussen
					$this->_iCalculateStart = $tmpCalculateStart;
					
					
					// Preis für die Wochenanzahl des letzten Kurses holen
//					if(!$aWeekForDiff['extraWeek']) {
//						$iPriceForDiff = $this->getWeekPrice($aWeekForDiff['id']);
//					} else {
//						$iPriceForDiff = $this->getWeekPrice($aWeekForDiff['extraWeek']['id']);
//
//						$aCalculation[$iSetPrice] .= ', EW: '.$iPriceForDiff.' * '.$iStartWeek;
//						$iPriceForDiff = $iPriceForDiff * $iStartWeek;
//					}

					// aktueller Breis abzüglich preis für wochenanzahl des letzten Kurses
					$aCalculation[$iSetPrice] .= ', '.$iPrice.' - '.$iPriceForDiff;
					$iPrice = $iPrice - $iPriceForDiff;

					$aPriceOfWeek['price'] = $iPrice;
					$aPriceOfWeek['type'] = 'total_weeks';

				}

				//$iLastPriceOfWeek	= $iPriceOfWeekTemp; // In #5013 auskommentiert

				if(
					$this->_oSaison !== null &&
					$this->_bAgencyAmount == true
				){
					
					$oSchoolProvision = $this->_oAgency->getSchoolProvisions($this->_oSaison->getSaisonId());

					$oProvision = $oSchoolProvision->getCourseProvision($this->_iCourseId);

					if ($oProvision) {
						$iPrice = $iPrice - $oProvision->calculate((float)$iPrice);
					}

					// Damit das Array mit den Wochenpreisen auch bei Agenturpreisen korrektbefüllt wird
					// Edit: Wird nicht benötigt, falls man jedoch die Netto Preise benötigt könnte man diese Zeile
					// verwenden um z.B. ein aWeekPrices(Netto) zu befüllen
					// Achtung: Das darf hier nicht einfach wieder auskommentiert werden, ansonsten sind Special-Provisionen falsch #7282
					#$iPriceOfWeek = ($iPriceOfWeek * ($oSchoolProvision->getCourseProvision($this->_iCourseId)/ 100));

				}

				$oWDDateTemp = new WDDate($this->_iCalculateStart);
				$aTemp = $oWDDateTemp->getWeekLimits();

				switch($aPriceOfWeek['type']) {
					case 'total_weeks':
						$iPriceOfWeek = $aPriceOfWeek['price'] - $iLastPriceOfWeek;
						$iLastPriceOfWeek = $aPriceOfWeek['price'];
						break;
					case 'single':
						$iPriceOfWeek = $aPriceOfWeek['price'];
						break;
					default:
						throw new UnexpectedValueException();
				}

				// Wochenpreis merken, falls man später sehen möchte welcher preis für welche Woche errechnet wurde
				// Sehr wichtig für Special!
				$this->aWeekPrices[$aTemp['start']] = $iPriceOfWeek;

				// Letzte Woche des Kurses bei Wochenkursen
				if(
					!$oCourseUtil->calculateByUnit() &&
					$i == $iCounter
				) {
					$aPriceFinal[] = $iPrice;
					$iSetPrice++;
										
					// Startwoche darf nur einmal angewendet werden
					$iStartWeek = 0;
					
					break;
				}

				$oLastSeasonTemp = $this->_oSaison;
				
				//Rechne 7 Tage hinzu um festzustellen ob die kommende Woche eine neue Saison ist
				if (
					!$oCourseUtil->calculateByUnit() &&
					$i != $iCounter
				) {

					$oWDDate = new WDDate($this->_iCalculateStart, WDDate::TIMESTAMP);

					$oWDDate->add(1, WDDate::WEEK);

					$iNextWeekStart = $oWDDate->get(WDDate::TIMESTAMP);
					$this->_iCalculateStart = $iNextWeekStart;

					$this->setSaison();

					// Wenn keine Saison gefunden wird kann nichts berechnet werden
					if(
						!$this->_oSaison ||
						 $this->_oSaison->getSaisonId() <= 0
					){
						// Fehlerarray fullen damit ein Fehler in der buchungsmaske sichtbar wird
						$this->setSaisonError();
						break;
					} else {
						// saison gefunden (nur zur Info im Rechnungsdialog)
						$this->setSeasonFound();
					}

				}

				// Wenn die kommende Saision nicht die aktuelle ist Speicher die Id der Woche
				if(
					$holidayCheck !== true &&
					is_object($this->_oSaison) &&
					$oLastSeasonTemp !== null &&
					$oLastSeasonTemp->getSaisonId() != $this->_oSaison->getSaisonId()
				) {
					
					$oLastSeason		= $oLastSeasonTemp;
					$aLastSeasonWeek	= $aWeek;
					// bei Saison Wechsel wird der Preis in das Price Array geschrieben
					$aPriceFinal[]		= $iPrice;
					$iSetPrice++;
						
					// Startwoche darf nur einmal angewendet werden
					$iStartWeek = 0;
					
					$iLastPrice			= $iPrice;
					$bSeasonChanged	= true;
                    $bSeasonChangedForExtraWeek = true;

				} elseif($oCourseUtil->calculateByUnit()) {
					$aPriceFinal[] = $iPrice;
					$iSetPrice++;

					// Startwoche darf nur einmal angewendet werden
					$iStartWeek = 0;
                    //MEGA BUG! eig. muss hier $bSeasonChanged auf false gesetzt werden!!!
                    // aus irgend einem grund klappt es troudem nur bei Special-Extrawochen nicht!!
                    // sollte aber trozdem mal gefixed werden immerhin denk das script nach 1 Wechsel das jede weitere Woche
                    // auch ein Wechsel ist!
                    $bSeasonChangedForExtraWeek = false;
					
				} else {
					
					// Extrawochen Cache muss geleert werden, wenn die Woche nicht relevant ist
					if($oLastSeasonTemp !== null) {
						unset($aExtraWeekCount[$oLastSeasonTemp->getSaisonId()]);
					}

					// Woche wird übersprungen, Fehler aus Cache laden
					$this->aErrors = $aErrorsCache;
                    //MEGA BUG! eig. muss hier $bSeasonChanged auf false gesetzt werden!!!
                    // aus irgend einem grund klappt es troudem nur bei Special-Extrawochen nicht!!
                    // sollte aber trozdem mal gefixed werden immerhin denk das script nach 1 Wechsel das jede weitere Woche
                    // auch ein Wechsel ist!
                    $bSeasonChangedForExtraWeek = false;

				}

			}

			// Für Special Array mit Preisen pro Woche vorbereiten NUR Falls es das Preis/pro woche array nicht gibt!
			if(
				count($this->aWeekPrices) != (int)$this->_iWeeks
			) {

				$fAmountTemp = array_sum($aPriceFinal);
				$fAmountWeek = $fAmountTemp / $this->_iWeeks;

				$dCalculationWeek = \Core\Helper\DateTime::createFromLocalTimestamp($iCalculateStartOrginal);

				for($iCount = 0; $iCount < (int)$this->_iWeeks; $iCount++) {

					$this->aWeekPricesSpecial[$dCalculationWeek->getTimestamp()] = $fAmountWeek;

					// Wochenstart muss separat mitberechnet werden, da eigene Schleife
					//$dCalculationWeek = \Core\Helper\DateTime::createFromLocalTimestamp($iCalculateStartOrginal);

					// Wochenweise Special berechnen
					// Special: Berechnen ob der preis ge-specialt werden muss
					$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount($fAmountWeek, $this->_oInquiryCourse, ($iCount + 1));
					$oSpecialAmount->setCalculationType('weekly');
					//$oSpecialAmount->setCalculationWeek($dCalculationWeek);
					$fSpecialAmount = $oSpecialAmount->getAmount();

					$this->aSpecialCalculationDescription = array_merge($this->aSpecialCalculationDescription, $oSpecialAmount->getCalculation());

					if($fSpecialAmount > 0) {
						$this->_aSpecialBlocks = array_merge($this->_aSpecialBlocks, $oSpecialAmount->getBlocks());
						$this->specialCodes = array_merge($this->specialCodes, $oSpecialAmount->getSpecialCodes());
						
						if($this->_bAgencyAmount == true) {
							// TODO: Fehlt hier die Provisionsberechnung, analog zum else-Fall?
							// Oder ist das schon abgedeckt, weil $iPrice der Nettobetrag ist?
							$this->_fSpecialAmountNetto += $fSpecialAmount;
						} else {
							$this->_fSpecialAmount += $fSpecialAmount;
						}
					}

					$dCalculationWeek->add(new DateInterval('P1W'));

				}

			} else {

				// Falls es das Array gibt wird dieses verwendet!
				$this->aWeekPricesSpecial = $this->aWeekPrices;

				$iTempCount = 1;
				foreach($this->aWeekPrices as $iTempTimestamp => $fAmountWeek) {

					// Wochenweise Special berechnen
					// Special: Berechnen ob der preis ge-specialt werden muss
					$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount([$iTempTimestamp => $fAmountWeek], $this->_oInquiryCourse, $iTempCount);
					$oSpecialAmount->setCalculationType('weekly');
					//$oSpecialAmount->setCalculationWeek(\Core\Helper\DateTime::createFromLocalTimestamp($iTempTimestamp));
					$fSpecialAmount = $oSpecialAmount->getAmount();

					$this->aSpecialCalculationDescription = array_merge($this->aSpecialCalculationDescription, $oSpecialAmount->getCalculation());

					if($fSpecialAmount > 0) {
						$this->_aSpecialBlocks = array_merge($this->_aSpecialBlocks, $oSpecialAmount->getBlocks());
						$this->specialCodes = array_merge($this->specialCodes, $oSpecialAmount->getSpecialCodes());
						
						if($this->_bAgencyAmount == true) {

							$oProvision = $oSchoolProvision->getCourseProvision($this->_iCourseId);
							if ($oProvision) {
								$fSpecialAmount = $fSpecialAmount - $oProvision->calculate((float)$fSpecialAmount);
							}

							$this->_fSpecialAmountNetto += $fSpecialAmount;
						} else {
							$this->_fSpecialAmount += $fSpecialAmount;
						}
					}

					$iTempCount++;

				}

			}

			// ==============================================================================

		################################
		## PREISE                     ##
		## je Zeitraum                ##
		## "Preis pro Lektion"		  ##
		## "Preis pro Woche"		  ##
		################################
		} else { // PREIS PRO WOCHE

			if(
				$oCourseUtil->calculateByUnit()
			) {
				$aCalculationDescription[] = $aPriceStructureSingle[1];
			} else {
				$aCalculationDescription[] = $aPriceStructureWeeks[1];
			}

			$aTemp = [];
			$t = 1;
			$aSpecialTemp = [];
			$iPrice = 0;
			$iPriceTemp = 0;

			$iCounter = $iWeeks;
			$iStartCount = $iWeekCount;
			if(
				$oCourseUtil->calculateByUnit()
			) {

				// Das war vorher auskommentiert, aber bei Ferien müssen wie oben alle Lektionen beachtet werden!
			    $iCounter = $iUnits;

//				if($iCounter < 1) {
//					$iStartCount = $iCounter;
//				} else {
//					$iStartCount = 1;
//				}

				$iStartCount = $iUnitCount;
				
			}

			// LETZTE Woche suchen um zu schauen ob ExtraWoche
			// seit 27.04 wird auch IMMER der Preis der Woche genommen die auf die Gesammtzahl zutrifft!
			$aWeek = $aLastWeek = $this->getWeekByCount($iCounter, $oCourseUtil);

			// Fehler in der Preisstrukturbestimmung 	
			if(isset($aWeek['error'])){
				$this->setWeekError($aWeek['error']);
			}

			// Counter für Zeitraum der nächsten Woche
			$a = 1;
			$iSetPrice = 0;
			for($i = $iStartCount; $i <= $iCounter; $i++) {
				
				// Gerade zu berechnende Woche DIESES Kurses
				$iCurrentCourseWeek++;

				// Prüfen ob Saison gültig ist
				if(
					!$this->_oSaison ||
					$this->_oSaison->getSaisonId() <= 0
				){
					$this->setSaisonError();
					continue;
				} else {
					// saison gefunden (nur zur Info im Rechnungsdialog)
					$this->setSeasonFound();
				}

				// Wochenanfang/ende
				$iLastWeek = $this->_iCalculateStart;
				$oWDDateTemp = new WDDate($iLastWeek);
				$aTemp = $oWDDateTemp->getWeekLimits();

				// Woche suchen
				//$aWeek = $this->getWeekByCount($i, $oCourseUtil);

				// Wenn letzte Woche ExtraWoche
				if(!empty($aLastWeek['extraWeek'])){
				// preis holen
					$iPrice = $this->getWeekPrice($aLastWeek['extraWeek']['id']);
				} else {
					// preis holen
					$iPrice = $this->getWeekPrice($aWeek['id']);
				}

				// Einzelpreis reduzieren, wenn weniger als 1 Lektion
				if(
					$oCourseUtil->calculateByUnit() &&
					$i < 1
				) {
					$iPrice = bcmul($iPrice, $i);
				}		

				// preis zwischenspeichern
				// Das ist immer der Bruttobetrag, weil beim Special netto immer wieder erneut ausgerechnet wird…
				$iPriceTemp = $iPrice;

				if(
					$this->_bAgencyAmount == true &&
					$this->_oAgency
				){
					$oSchoolProvision = $this->_oAgency->getSchoolProvisions($this->_oSaison->getSaisonId());

					$oProvision = $oSchoolProvision->getCourseProvision($this->_iCourseId);

					if ($oProvision) {
						$iPrice = $iPrice - $oProvision->calculate((float)$iPrice);
					}
				}

				// Special errechnen
				if($this->_oInquiry instanceof Ext_TS_Inquiry) {

					// Special: Berechnen ob der preis ge-specialt werden muss
					$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount([$aTemp['start'] => $iPriceTemp], $this->_oInquiryCourse, $iCurrentCourseWeek);
					$oSpecialAmount->setCalculationType('weekly');
					//$oSpecialAmount->setCalculationWeek(\Core\Helper\DateTime::createFromLocalTimestamp($aTemp['start']));
					$fSpecialAmount = $oSpecialAmount->getAmount();

					$this->aSpecialCalculationDescription = array_merge($this->aSpecialCalculationDescription, $oSpecialAmount->getCalculation());

					$this->_aSpecialBlocks = array_merge($this->_aSpecialBlocks, $oSpecialAmount->getBlocks());
					$this->specialCodes = array_merge($this->specialCodes, $oSpecialAmount->getSpecialCodes());
						
					// Special zeitpunkt merken, wenn ein special gefunde wurde
					if($fSpecialAmount > 0){
						$aSpecialTemp[$t] = $this->_iCalculateStart;
					}
				}
				
				$oLastSeason = $this->_oSaison;
				
				/**
				 * Rechne 7 Tage hinzu um festzustellen ob die kommende Woche eine neue Saison ist
				 * Aber nur wenn es nicht die letzte Woche ist
				 */
				if(
					!$oCourseUtil->calculateByUnit() &&
					$i != $iCounter
				) {
					$oWDDate = new WDDate($this->_iCalculateStart, WDDate::TIMESTAMP);

					$oWDDate->add(1, WDDate::WEEK);

					$iNextWeekStart = $oWDDate->get(WDDate::TIMESTAMP);
					$this->_iCalculateStart = $iNextWeekStart;
					
					$this->setSaison();

					// Prüfen ob Saison gültig ist
					if(
						!$this->_oSaison ||
						$this->_oSaison->getSaisonId() <= 0
					){
						$this->setSaisonError();
						continue;
					} else {
						// saison gefunden (nur zur Info im Rechnungsdialog)
						$this->setSeasonFound();
					}
					
				}

				// Saison merken für Lektionskurse
				if($oCourseUtil->calculateByUnit()){
					$iTempLectionSeason = (int)$this->_oSaison->getSaisonId();
				}

				// Wenn sich die Saison wechselt oder letzte Woche (wird immer durchgelaufen)
				// TODO Das ist doch total bescheuert, was hier passiert, weil die for-Schleife immer am Ende ausgeführt wird und alles finalisiert
				if(
					$this->_oSaison->getSaisonId() != $oLastSeason->getSaisonId() ||
					$i == $iCounter // ODER LETZTE WOCHE
				) {

					//davor stand hier $this->_oSeason->valid_from & $this->_oSeason->valid_until, das müsste jedoch falsch sein, weil die informationen erst nach dem
					//saisonwechsel gebildet wird, siehe auch t-4050
					$aCalculation[$iSetPrice] = Ext_Thebing_Format::LocalDate($oLastSeason->valid_from).' - '.Ext_Thebing_Format::LocalDate($oLastSeason->valid_until).': ';
					$iSetPrice++;

					// Bisherige wochen nochmal durchlaufen
					for($a = 1; $a <= $t; $a++) {

						// Preis vormerken
						$iPriceSpecial = $iPrice;

						$fFactor = 1;
						
						// Prüfen ob in den vorherigen wochen Specials gefunden wurden.
						// Wenn ja müssen die entsprechenden wochen noch umgerechnet werden, da der preis der aktuellen woche für alle vorherigen genutzt werden würde
						// TODO Kann es sein, dass dieser Block nur einmal oder keinmal ausgeführt werden soll? Hier wird ggf. $fSpecialAmount von oben überschrieben!
						if(
							$this->_oSaison->getSaisonId() != $oLastSeason->getSaisonId() && // Unten wurde von MF mal eingebaut, dass der Special-Betrag immer nochmal draufkommt
							isset($aSpecialTemp[$a]) && // TODO: Ist das auch mal nicht gefüllt oder wofür ist das da?
							$a != $t &&
							$this->_oInquiry instanceof Ext_TS_Inquiry
						) {

							// Special Preis errechnen
							$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount($iPriceTemp, $this->_oInquiryCourse, $iCurrentCourseWeek);
							$oSpecialAmount->setCalculationType('weekly');
							//oSpecialAmount->setCalculationWeek(\Core\Helper\DateTime::createFromLocalTimestamp($aSpecialTemp[$a]));
							$fSpecialAmount = $oSpecialAmount->getAmount();
							$this->_aSpecialBlocks = array_merge($this->_aSpecialBlocks, $oSpecialAmount->getBlocks());
							$this->specialCodes = array_merge($this->specialCodes, $oSpecialAmount->getSpecialCodes());

							$this->aSpecialCalculationDescription = array_merge($this->aSpecialCalculationDescription, $oSpecialAmount->getCalculation());
							
							//$fSpecialAmount = $this->_oInquiry->calculateSpecialAmount($iPriceTemp, $aSpecialTemp[$a], 'course', $this->_iCourseId);
							if($this->_bAgencyAmount == false){
								$this->_fSpecialAmount += $fSpecialAmount;
							}

							// Wenn netto muss der Special preis erneut umgerechnet werden
							// $iPriceTemp welches benutzt wird enthält immer den Brutto betrag
							if($this->_bAgencyAmount == true){
								$oSchoolProvision = $this->_oAgency->getSchoolProvisions($this->_oSaison->getSaisonId());

								$oProvision = $oSchoolProvision->getCourseProvision($this->_iCourseId);

								if ($oProvision) {
									$fSpecialAmount = $fSpecialAmount - $oProvision->calculate((float)$fSpecialAmount);
								}

								// Beträge für die einzelnen Special merken um später den einmaligen betrag errechnen zu können
								$this->_fSpecialAmountNetto += $fSpecialAmount;
							}
						}

						if(!isset($aCalculation[$iSetPrice])) {
							$aCalculation[$iSetPrice] = '';
						}
						
						$aCalculation[$iSetPrice] .= 'W '.$a.'/'.$t.' '.Ext_Thebing_Format::Number($iPriceSpecial, null, $this->_oSchool->id).' * '.$fFactor;
						$aPriceFinal[] = $iPriceSpecial * $fFactor;
						#$this->aPeriods[$dWeek->toDateString()] += $fAmount;
						$iSetPrice++;
						
					}
					// Bei Saisonwechsel wird Wochencounter zurückgesetzt
					$t = 1;
				} else {
					// Wochencounter für Wochen innerhalb einer Saison
					$t++;
				}

				// Nur bei keinem Saisonwechsel, da das hierdrüber für den Saisonwechsel schon passiert
				if($this->_oSaison->getSaisonId() == $oLastSeason->getSaisonId()) {
					if($this->_oInquiry instanceof Ext_TS_Inquiry) {
						if($this->_bAgencyAmount == true){
							$oProvision = $oSchoolProvision->getCourseProvision($this->_iCourseId);
							if ($oProvision) {
								$fSpecialAmount = $fSpecialAmount - $oProvision->calculate((float)$fSpecialAmount);
							}
							$this->_fSpecialAmountNetto += $fSpecialAmount;
						} else {
							$this->_fSpecialAmount += $fSpecialAmount;
						}
					}
				}

				// Sehr wichtig für Special!
				// Das war vorher $iPrice (also bei Agenturbetrag auch netto), muss aber brutto (wie bei NPS) sein!
				// Ansonsten würde unten die Special-Provision auf Basis des Nettopreises errechnet werden. #7282
				
				/*
				 * Wenn für die Woche schon ein Preis feststeht, eine Sekunde hochzählen. 
				 * Bei Lektionskursen kann es sein, dass alle Preise auf die erste Woche gezählt werden
				 */
				$iWeekPriceIndex = $aTemp['start'];
				while(isset($this->aWeekPrices[$iWeekPriceIndex])) {
					$iWeekPriceIndex++;
				}
				
				$this->aWeekPrices[$iWeekPriceIndex] = $iPriceTemp;

			}

			// Für Special merken
			$this->aWeekPricesSpecial = $this->aWeekPrices;

			if(count($aPriceFinal) <= 0) {
				$aCalculation[$iSetPrice] .= $iPrice.' * '.$iCounter;
				$aPriceFinal[] = $iPrice * $iCounter;
				$iSetPrice++;
			}

		}

		// Fehler ermitteln Lektionskurs
		if($oCourseUtil->calculateByUnit()) {

			// Alle eingetragenen Saisons
			$aSeasons = $this->_oSchool->getSaisonList();

			if(
				$iTempLectionSeason > 0 &&
				$this->_oInquiryCourse != null
			) {

				// Saison des Lektionskurses
				$oSeason = Ext_Thebing_Marketing_Saison::getInstance($iTempLectionSeason);			

				$oDateCourseFrom = new WDDate($this->_oInquiryCourse->from, WDDate::DB_DATE);
				$oDateCourseUntil = new WDDate($this->_oInquiryCourse->until, WDDate::DB_DATE);

				$bErrorMissingUnitSeason = false;
				$bErrorWrongUnitSeason = false;

				// Prüfen ob Lektionskurs über mehrere Saisons geht -> Fehler werfen
				foreach(array_keys((array)$aSeasons) as $iSeasonId) {

					$oSeasonTemp = Ext_Thebing_Marketing_Saison::getInstance($iSeasonId);

					$oDateSeasonFrom = new WDDate($oSeasonTemp->valid_from, WDDate::DB_DATE);
					$oDateSeasonUntil = new WDDate($oSeasonTemp->valid_until, WDDate::DB_DATE);

					$iComp = WDDate::comparePeriod($oDateSeasonFrom, $oDateSeasonUntil, $oDateCourseFrom, $oDateCourseUntil);

					if($oSeasonTemp->id == $oSeason->id) {

						// Prüfen ob die Saison den kompletten Kurs Zeitraum abdeckt
						if(
							$iComp !== WDDate::PERIOD_EQUAL &&
							$iComp !== WDDate::PERIOD_INNER
						) {
							// Fehler werfen da Kurs nicht KOMPLETT in Periode liegt
							$bErrorWrongUnitSeason = true;
						}

						// Kein fehler werfen über mehrere Saisons bei aktueller Saison
						continue;

					}

					if(
						$iComp === WDDate::PERIOD_INTERSECT_END ||
						$iComp === WDDate::PERIOD_INTERSECT_START
					) {
						$bErrorWrongUnitSeason = true;						
					}

				}

				if($bErrorMissingUnitSeason === true) {
					$this->aErrors['missing_unit_season'][] = $this->_oInquiryCourse->getCourse()->id;
				}
				if($bErrorWrongUnitSeason === true) {
					$this->aErrors['wrong_unit_season'][] = $this->_oInquiryCourse->getCourse()->id;
				}

			}

		}

		$aCalculation = array_merge($aCalculationDescription, array('line'), $aCalculation);

		$this->aCalculationDescription = $aCalculation;

		$fAmount = array_sum($aPriceFinal);

		$this->_iCalculateStart = $iCalculateStartOrginal;

		// Special: Einmaliger Fixer Rabatt auf Kurs
		// $this->aWeekPricesSpecial sollte immer Bruttopreise enthalten
		$oSpecialAmount = new Ext_Thebing_Inquiry_Special_Amount($this->aWeekPricesSpecial, $this->_oInquiryCourse);
		$oSpecialAmount->setCalculationTime($this->_iCalculateStart);
		$fSpecialAmount = $oSpecialAmount->getAmount();

		$this->aSpecialPeriods = $oSpecialAmount->aSpecialPeriods;
		$this->oSpecialPeriod = $oSpecialAmount->oSpecialPeriod;

		$this->aSpecialCalculationDescription = array_merge($this->aSpecialCalculationDescription, $oSpecialAmount->getCalculation());

		if($fSpecialAmount > 0) {

			$this->_aSpecialBlocks = array_merge($this->_aSpecialBlocks, $oSpecialAmount->getBlocks());
			$this->specialCodes = array_merge($this->specialCodes, $oSpecialAmount->getSpecialCodes());

			if($this->_bAgencyAmount == true) {
				if($oSchoolProvision) {
					$oProvision = $oSchoolProvision->getCourseProvision($this->_iCourseId);
					if ($oProvision) {
						$fSpecialAmount = $fSpecialAmount - $oProvision->calculate((float)$fSpecialAmount);
					}
				}
				$this->_fSpecialAmountNetto += $fSpecialAmount;
			} else {
				$this->_fSpecialAmount += $fSpecialAmount;
			}
		}

		return $fAmount;
	}

}
