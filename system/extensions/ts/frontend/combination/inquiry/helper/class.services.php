<?php

use Core\DTO\DateRange;
use Ts\Dto\CourseStartDate;

/**
 * Helper-Klasse zum Holen der im Formular verfügbaren Leistungen und Zeiträume inkl. Instanz-Cache
 */
class Ext_TS_Frontend_Combination_Inquiry_Helper_Services {

	/**
	 * Anmeldeformular V2 vs. V3 (V2: $bGenerateAllData = true)
	 *
	 * @var bool
	 */
	public $bGenerateAllData = true;

	/**
	 * @var Ext_Thebing_Form
	 */
	private $oForm;

	/**
	 * @var Ext_Thebing_School
	 */
	private $oSchool;

	/**
	 * @var string
	 */
	private $sLanguage;

	/**
	 * @var Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Course[]
	 */
	private $aCourses = [];

	/**
	 * @var DateRange[]
	 */
	private $aCourseDurations = [];

	/**
	 * @var Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Accommodation[]
	 */
	private $aAccommodations = [];

	/**
	 * @var Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Transfer[]
	 */
	private $aTransfers = [];

	/**
	 * @var Ext_Thebing_Insurance[]
	 */
	private $aInsurances = [];

	/**
	 * @var Ext_Thebing_School_Additionalcost[]
	 */
	private $aFees = [];

	/**
	 * @var string[]
	 */
	private $aLevels = [];

	/**
	 * @param Ext_Thebing_Form $oForm
	 * @param Ext_Thebing_School $oSchool
	 * @param string $sLanguage
	 */
	public function __construct(Ext_Thebing_Form $oForm, Ext_Thebing_School $oSchool, string $sLanguage) {
		$this->oForm = $oForm;
		$this->oSchool = $oSchool;
		$this->sLanguage = $sLanguage;
	}

	/**
	 * Verfügbare Kurse aus den Formulareinstellungen auslesen inkl. Startdaten
	 *
	 * @return Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Course[]
	 */
	public function getCourses() {

		if(!empty($this->aCourses)) {
			return $this->aCourses;
		}

		$aBlocks = $this->oForm->getFilteredBlocks(fn(Ext_Thebing_Form_Page_Block $block) => $block->block_id == Ext_Thebing_Form_Page_Block::TYPE_COURSES && $block->getSetting('based_on') === 'availability');
		if (empty($aBlocks)) {
			return [];
		}

		$aSettings = [];
		foreach ($aBlocks as $oBlock) {
			foreach ($oBlock->getSettings() as $sKey => $mValue) {
				$aSettings[] = [$oBlock, $sKey, $mValue];
			}
		}

		$aAdditionalServices = []; /** @var Ext_Thebing_School_Additionalcost[] $aAdditionalServices */

		foreach($aSettings as $aSetting) {

			[$oBlock, $sKey, $mValue] = $aSetting;

			if(
				$mValue && ( // Checkbox
					\Illuminate\Support\Str::startsWith($sKey, 'course_') || (
						// Array besteht aus allen irgendwie verfügbaren Kursen
						$oBlock->getSetting('limit_following_'.$this->oSchool->getId()) &&
						\Illuminate\Support\Str::startsWith($sKey, 'coursefollowing_')
					)
				)
			) {
				$aData = explode('_', $sKey);
				$oCourse = Ext_Thebing_Tuition_Course::getInstance((int)end($aData));
				if(
					$oCourse->exist() &&
					$oCourse->school_id == $this->oSchool->id &&
					$oCourse->isValid()
				) {
//					if(!isset($this->aCourses[$oCourse->id])) {
						$oDateRange = self::getCourseDurationFromAndUntil($oCourse, $this->bGenerateAllData);

						// Für V2 bei nicht-Montag eine Woche zurückspringen, damit die aktuelle Woche noch zur Verfügung steht
						if (
							$this->bGenerateAllData &&
							(int)$oDateRange->from->format('N') !== 1
						) {
							$oDateRange->from->sub(new DateInterval('P1W'));

							// Da der Starttag nicht mehr korrigiert wird, muss man das für V2 so zurecht hacken, dass es nur Montag gibt
							$this->oSchool->course_startday = 1;
						}

						$oDto = new Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Course();
						if (isset($this->aCourses[$oCourse->id])) {
							$oDto = $this->aCourses[$oCourse->id];
						}

						$oDto->oCourse = $oCourse;
						$oDto->aLevels = $this->getCourseLevels($oCourse);
						$oDto->aStartDates = $oCourse->getStartDatesWithDurations($oDateRange->from, $oDateRange->until);
						$oDto->aBlocks[] = $oBlock;

						// Sonderkonditionen für V2
						if ($this->bGenerateAllData) {
							// V2 kann nur Montag als Starttag, daher alles andere aussortieren
							$oDto->aStartDates = array_filter($oDto->aStartDates, function (CourseStartDate $oStartDate) {
								return (int)$oStartDate->start->format('N') === 1;
							});
						}

						// Kurse ohne Startdaten nicht anzeigen
						if(empty($oDto->aStartDates)) {
							continue;
						}

						$this->aCourses[$oCourse->id] = $oDto;
//					}

					if (
						$oBlock->getSetting('show_level') === 'all' ||
						$oBlock->getSetting('show_level') === 'individual' &&
						$oBlock->getSetting('show_level_course_'.$oCourse->id)
					) {
						$this->aCourses[$oCourse->id]->bShowLevel = true;
					}

					if(strpos($sKey, 'course_') !== false) {
						// Als erster Kurs verfügbar (wenn Option aktiviert)
						$this->aCourses[$oCourse->id]->bFirstCourse = true;
					}

					if(
						!$this->bGenerateAllData ||
						strpos($sKey, 'coursefollowing') !== false ||
						// Wenn Option nicht aktiv, muss Kurs immer hier markiert werden (V3)
						!$oBlock->getSetting('limit_following_'.$this->oSchool->getId())
					) {
						// Als nachfolgender Kurs verfügbar (wenn Option aktiviert)
						$this->aCourses[$oCourse->id]->bFollowingCourse = true;
					}
				}
			}

			$this->buildAdditionalServices($sKey, $mValue, Ext_Thebing_School_Additionalcost::TYPE_COURSE, $oBlock, $aAdditionalServices);

		}

		// Zusatzleistungen
		foreach ($aAdditionalServices as $oFee) {
			foreach ($this->aCourses as $oDto) {
				if (in_array($oDto->oCourse->id, $oFee->costs_courses)) {
					$oDto->additionalServices[] = $oFee;
				}
			}
		}

		uasort($this->aCourses, function(Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Course $oDto1, Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Course $oDto2) {
			return $oDto1->oCourse->position > $oDto2->oCourse->position;
		});

		return $this->aCourses;

	}

	/**
	 * Zeitraum für Start und Ende der Kursdaten
	 *
	 * @param Ext_Thebing_Tuition_Course $oCourse
	 * @param bool $bIgnoreMaxSetting
	 * @return DateRange
	 */
	public static function getCourseDurationFromAndUntil(Ext_Thebing_Tuition_Course $oCourse, $bIgnoreMaxSetting = false) {

		$dFrom = \Carbon\Carbon::now();

		$iDuration = (int)\System::d('ts_frontend_max_course_availability');
		if ($bIgnoreMaxSetting || $iDuration < 1 || $iDuration > 104) {
			// Da der Kurs immer Start + Duration ist, muss ein Jahr abgezogen werden, da ansonsten bei allen anderen Leistungen 1 Jahr fehlen würde
			$iDuration = 52 * ((int)$oCourse->getSchool()->frontend_years_of_bookable_services - 1);
		}

		$dUntil = $dFrom->copy()->addWeeks($iDuration);

		// Bei bestimmten Startdaten kann das Enddatum später als ein Jahr in der Zukunft sein
		if($oCourse->avaibility == Ext_Thebing_Tuition_Course::AVAILABILITY_STARTDATES) {
			$aStartDates = $oCourse->getConfiguredStartDates();
			$aEndDates = [];
			foreach($aStartDates as $oStartDate) {

				if(\Core\Helper\DateTime::isDate($oStartDate->end_date, 'Y-m-d')) {
					$dEndDate = new DateTime($oStartDate->end_date);
				} elseif(\Core\Helper\DateTime::isDate($oStartDate->last_start_date, 'Y-m-d')) {
					$dEndDate = new DateTime($oStartDate->last_start_date);
				} else {
					$dEndDate = new DateTime($oStartDate->start_date);
					$iMaxWeeks = max($oStartDate->maximum_duration, $oStartDate->fix_duration, 1);
					$dEndDate->modify('+'.$iMaxWeeks.' weeks');
				}

				$aEndDates[] = $dEndDate;
			}

			if(!empty($aEndDates)) {
				$dUntil = max($aEndDates);
			}
		}

		return new DateRange($dFrom, $dUntil);
	}

	/**
	 * @param Ext_Thebing_Tuition_Course $oCourse
	 * @return string[]
	 */
	private function getCourseLevels(Ext_Thebing_Tuition_Course $oCourse) {

		if(!$this->bGenerateAllData) {
			// Wird in V3 nicht mehr verwendet, da start_level_id direkt übergeben wird
			return [];
		}

		if(empty($this->aLevels)) {
			$this->aLevels = $this->oSchool->getCourseLevelList();
		}

		if(empty($oCourse->start_level_id)) {
			$aOptions = $this->aLevels;
		} else {
			// Da niedrigere Level oben stehen, kann man die hier einfach durchlaufen
			$bFound = false;
			$aOptions = array_filter($this->aLevels, function($iLevelId) use($oCourse, &$bFound) {
				if($iLevelId == 0) {
					return true; // addEmptyItem()
				}
				if($iLevelId == $oCourse->start_level_id) {
					$bFound = true; // Startlevel gefunden, ab jetzt jedes weitere aufnehmen
				}
				return $bFound;
			}, ARRAY_FILTER_USE_KEY);
		}

		return $aOptions;

	}

	/**
	 * Über alle Kurse alle verfügbaren Start- und Enddatum ermitteln
	 *
	 * @return \Core\DTO\DateRange[]
	 */
	public function getCourseDurations() {

		if(!empty($this->aCourseDurations)) {
			return $this->aCourseDurations;
		}

		$oSchool = $this->oSchool;

		$dFirstStartDate = null;
		$dLastEndDate = null;

		// Alle Startdaten durchlaufen und frühstes Startdatum sowie spätestes Enddatum ermitteln
		foreach($this->getCourses() as $oDto) {
			foreach($oDto->aStartDates as $oStartDateDto) {
				if($dFirstStartDate === null) {
					$dFirstStartDate = $oStartDateDto->start;
				}
				if($dLastEndDate === null) {
					$dLastEndDate = $oStartDateDto->end;
				}

				$dFirstStartDate = min($dFirstStartDate, $oStartDateDto->start);
				$dLastEndDate = max($dLastEndDate, $oStartDateDto->end);
			}
		}

		// Einstellungen können auch so sein, dass gar kein Starttermin rauskommt
		if(
			$dFirstStartDate === null ||
			$dLastEndDate === null
		) {
			return $this->aCourseDurations;
		}

		// Komplett-Zeitraum durchlaufen und alle benötigten Werte für SelectOptionsInRange erzeugen
		$oDatePeriod = new DatePeriod($dFirstStartDate, new DateInterval('P1W'), $dLastEndDate);
		foreach($oDatePeriod as $dDate) {
			/** @var \DateTime $dDate */
			$dEndDate = clone $dDate;
			$dEndDate->add(new DateInterval('P'.(count(Ext_Thebing_Util::getCourseWeekDays($oSchool->course_startday)) - 1).'D'));
			$this->aCourseDurations[] = new DateRange($dDate, $dEndDate);
		}

		return $this->aCourseDurations;

	}

	/**
	 * Verfügbare Unterkünfte aus den Formulareinstellungen auslesen und Startdaten in Abhängigkeit zu den Kursen ermitteln
	 *
	 * @return Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Accommodation[]
	 */
	public function getAccommodations() {

		if(!empty($this->aAccommodations)) {
			return $this->aAccommodations;
		}

		$oBlock = $this->oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_ACCOMMODATIONS);

		if(!$this->bGenerateAllData) {
			$aCourseDurations = []; // Startdaten für Unterkünfte werden in V3 per AJAX geholt, da das ab zwei Kursen nicht mehr funktionieren kann
		} elseif($this->oForm->acc_depending_on_course) {
			$aCourseDurations = $this->getCourseDurations();
		} else {
			// Startdaten pauschal ein Jahr im Voraus generieren
			$aCourseDurations = [];
			$dFrom = new \DateTime('now');
			$dFrom = Ext_Thebing_Util::getNextCourseStartDay($dFrom, $this->oSchool->course_startday);
			$dFrom->add(new \DateInterval('P1W'));
			$dUntil = clone $dFrom;
			$dUntil->add(new \DateInterval('P2Y')); // P2Y, damit in P1Y+P52W noch Enddaten verfügbar sind
			$oDatePeriod = new DatePeriod($dFrom, new \DateInterval('P1W'), $dUntil);
			foreach($oDatePeriod as $iKey => $dDate) {
				$aCourseDurations[] = new \Core\DTO\DateRange($dDate, $dDate);
			}
		}

		if($oBlock === null) {
			return [];
		}

		$aAccommodationCombinations = $this->oSchool->getAccommodationMealCombinations();
		$aAdditionalServices = []; /** @var Ext_Thebing_School_Additionalcost[] $aAdditionalServices */

		$aSettings = $oBlock->getSettings();
		foreach($aSettings as $sKey => $mValue) {

			if(
				$mValue && // Checkbox
				strpos($sKey, 'accommodation_') !== false
			) {

				// Typ, Kategorie, Raum, Verpflegung, Schule
				[, $iCategoryId, $iRoomTypeId, $iMealTypeId, $iSchoolId] = explode('_', $sKey, 5);

				if($iSchoolId != $this->oSchool->id) {
					continue;
				}

				// Wie im SR mit gültigen Kombinationen vergleichen (valid_until und überhaupt gültige Kombinationen)
				if(
					!isset($aAccommodationCombinations[$iCategoryId]) ||
					!isset($aAccommodationCombinations[$iCategoryId][$iRoomTypeId]) ||
					!in_array($iMealTypeId, $aAccommodationCombinations[$iCategoryId][$iRoomTypeId])
				) {
					continue;
				}

				$oAccommodationCategory = Ext_Thebing_Accommodation_Category::getInstance($iCategoryId);
				$oRoomtype = Ext_Thebing_Accommodation_Roomtype::getInstance($iRoomTypeId);
				$oMeal = Ext_Thebing_Accommodation_Meal::getInstance($iMealTypeId);

				// Wird analog beim Speichern überprüft, darf daher nicht einfach fehlen
				if(
					!$oAccommodationCategory->belongsToSchool($this->oSchool) ||
					!$oRoomtype->belongsToSchool($this->oSchool) ||
					!$oMeal->belongsToSchool($this->oSchool) ||
					!$oAccommodationCategory->isValid() ||
					!$oRoomtype->isValid() ||
					!$oMeal->isValid()
				) {
					continue;
				}

				$oDto = new Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Accommodation();
				$oDto->oCategory = $oAccommodationCategory;
				$oDto->oRoomtype = $oRoomtype;
				$oDto->oMeal = $oMeal;
				$this->aAccommodations[] = $oDto;

				foreach($aCourseDurations as $iKey => $oDateRange) {
					$oDto2 = new Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Accommodation_BookableDate();
					$oDto2->dCourseStart = $oDateRange->from;
					$oDto2->aStartDates = $this->oSchool->getAccommodationStartDates($oDateRange->from, $oAccommodationCategory);
					$oDto2->aEndDates = $this->oSchool->getAccommodationEndDates($oDateRange->from, $oAccommodationCategory, 1);
					$oDto->aBookableDates[$iKey + 1] = $oDto2; // $iKey + 1 stellt die Woche als Index dar
				}

			}

			$this->buildAdditionalServices($sKey, $mValue, Ext_Thebing_School_Additionalcost::TYPE_ACCOMMODATION, $oBlock, $aAdditionalServices);

		}

		// Zusatzleistungen
		foreach ($aAdditionalServices as $oFee) {
			foreach ($this->aAccommodations as $oDto) {
				$sKey = sprintf('%s_%s_%s', $oDto->oCategory->id, $oDto->oRoomtype->id, $oDto->oMeal->id);
				if (in_array($sKey, $oFee->costs_accommodations)) {
					$oDto->additionalServices[] = $oFee;
				}
			}
		}

		uasort($this->aAccommodations, function(Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Accommodation $oDto1, Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Accommodation $oDto2) {
			return $oDto1->oCategory->position > $oDto2->oCategory->position;
		});

		return $this->aAccommodations;

	}

	/**
	 * Verfügbare Transferkombinationen aus den Formulareinstellungen auslesen (alle!)
	 *
	 * @return Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Transfer[]
	 */
	public function getTransfers() {

		if(!empty($this->aTransfers)) {
			return $this->aTransfers;
		}

		$oBlock = $this->oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_TRANSFERS);

		if($oBlock === null) {
			return [];
		}

		$aSettings = $oBlock->getSettings();
		foreach($aSettings as $sKey => $mValue) {
			if(
				!$mValue || ( // Checkbox
					strpos($sKey, 'transfer_arr_'.$this->oSchool->id.'_') === false &&
					strpos($sKey, 'transfer_dep_'.$this->oSchool->id.'_') === false
				)
			) {
				continue;
			}

			// Format: transfer_arr_1_location_13_to_accommodation_0
			$aData = explode('_', $sKey);
			if(count($aData) !== 8) {
				// Anmerkung: Früher wurde für den Von-Ort der Key mit fünf Werten benutzt, nicht die Kombination
				continue;
			}

			if(
				!is_string($aData[1]) ||
				!is_string($aData[3]) ||
				!is_numeric($aData[4]) ||
				!is_string($aData[6]) ||
				!is_numeric($aData[7])
			) {
				throw new RuntimeException('Something is wrong with transfer setting of form: "'.$sKey.'"');
			}

			$oDto = new Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Transfer();
			$oDto->sFromKey = $aData[3].'_'.$aData[4];
			$oDto->sFromLabel = Ext_TS_Transfer_Location::getLabel($aData[3], $aData[4], $this->sLanguage);
			$oDto->sToKey = $aData[6].'_'.$aData[7];
			$oDto->sToLabel = Ext_TS_Transfer_Location::getLabel($aData[6], $aData[7], $this->sLanguage);

			// Prüfen, ob eingestellte Transferstationen überhaupt noch gültig sind
			if($aData[3] === 'location') {
				$oTransferLocation = Ext_TS_Transfer_Location::getInstance($aData[4]);
				$oDto->fromPosition = (int)$oTransferLocation->position;
				if(!$oTransferLocation->isValid()) {
					continue;
				}
			}

			if($aData[6] === 'location') {
				$oTransferLocation = Ext_TS_Transfer_Location::getInstance($aData[7]);
				$oDto->toPosition = (int)$oTransferLocation->position;
				if(!$oTransferLocation->isValid()) {
					continue;
				}
			}

			$this->aTransfers[] = $oDto;

			if($aData[1] === 'arr') {
				$oDto->sType = 'arrival';
			} elseif($aData[1] === 'dep') {
				$oDto->sType = 'departure';
			} else {
				throw new RuntimeException('Unknown transfer type: "'.$aData[1].'"');
			}

		}

		return $this->aTransfers;

	}

	/**
	 * Verfügbare Versicherungen aus den Formulareinstellungen
	 *
	 * @return Ext_Thebing_Insurance[]
	 */
	public function getInsurances() {

		if(!empty($this->aInsurances)) {
			return $this->aInsurances;
		}

		$oBlock = $this->oForm->getFixedBlock(Ext_Thebing_Form_Page_Block::TYPE_INSURANCES);

		if($oBlock === null) {
			return [];
		}

		$aSettings = $oBlock->getSettings();
		foreach($aSettings as $sKey => $mValue) {

			if(
				strpos($sKey, 'insurance_'.$this->oSchool->id.'_') !== 0 ||
				!$mValue
			) {
				continue;
			}

			$aData = explode('_', $sKey, 3);

			$oInsurance = Ext_Thebing_Insurance::getInstance($aData[2]);
			if (!$oInsurance->isValid()) {
				continue;
			}

			$this->aInsurances[] = $oInsurance;

		}

		return $this->aInsurances;

	}

	/**
	 * Verfügbare zusätzliche generelle Gebühren aus den Formulareinstellungen
	 *
	 * @return Ext_Thebing_School_Additionalcost[]
	 */
	public function getFees() {

		if(!empty($this->aFees)) {
			return $this->aFees;
		}

		$aBlocks = $this->oForm->getFilteredBlocks($this->oForm->createFilteredBlocksCallbackType(Ext_Thebing_Form_Page_Block::TYPE_FEES));
		if (empty($aBlocks)) {
			return [];
		}

		foreach ($aBlocks as $oBlock) {
			foreach ($oBlock->getSettings() as $sKey => $mValue) {

				if(
					strpos($sKey, 'cost_'.$this->oSchool->id.'_') !== 0 ||
					!$mValue
				) {
					continue;
				}

				$aData = explode('_', $sKey, 3);

				$oFee = Ext_Thebing_School_Additionalcost::getInstance($aData[2]);
				$oFee->transients['blocks'][] = $oBlock;
				if(
					!$oFee->isValid() ||
					// Typ kann ja lustig verändert werden
					$oFee->type != Ext_Thebing_School_Additionalcost::TYPE_GENERAL ||
					$oFee->charge !== 'manual'
				) {
					continue;
				}

				$this->aFees[$oFee->id] = $oFee;

			}
		}

		$this->aFees = array_values($this->aFees);

		return $this->aFees;

	}

	/**
	 * @return \TsActivities\Entity\Activity[]
	 */
	public function getActivities(): array {

		$blocks = $this->oForm->getFilteredBlocks($this->oForm->createFilteredBlocksCallbackType(Ext_Thebing_Form_Page_Block::TYPE_ACTIVITY));
		if (empty($blocks)) {
			return [];
		}

		$activities = [];

		foreach ($blocks as $block) {

			$settings = [];

			// Basierend auf Planung: Alle in der Schule verfügbaren Akvititäten generieren, welche nachher dann aussortiert werden – es geht vor allem um die Struktur
			if ($block->getSetting('based_on') === 'scheduling') {
				$schoolActivities = TsActivities\Entity\Activity::getRepository()->getActivitiesBySchool($this->oSchool);
				foreach ($schoolActivities as $schoolActivity) {
					$settings[sprintf('activity_%d_%d', $this->oSchool->id, $schoolActivity->id)] = 1;
				}
			} else {
				$settings = $block->getSettings();
			}

			foreach ($settings as $key => $value) {

				if (
					!$value ||
					!\Illuminate\Support\Str::startsWith($key, 'activity_'.$this->oSchool->id.'_')
				) {
					continue;
				}

				[, , $activityId] = explode('_', $key, 3);
				$activity = \TsActivities\Entity\Activity::getInstance($activityId);
				if ($activity->isValid()) {
					$activity->transients['blocks'][] = $block;
					$activities[$activity->id] = $activity;
				}

			}
		}

		usort($activities, function (\TsActivities\Entity\Activity $activity1, \TsActivities\Entity\Activity $activity2) {
			return $activity1->position > $activity2->position;
		});

		return $activities;

	}

	private function buildAdditionalServices(string $sKey, $mValue, int $iType, Ext_Thebing_Form_Page_Block $oBlock, array &$aAdditionalServices) {

		if (
			$mValue && // Checkbox
			\Illuminate\Support\Str::startsWith($sKey, 'additionalservice_')
		) {
			$sId = \Illuminate\Support\Str::afterLast($sKey, '_');
			$oFee = Ext_Thebing_School_Additionalcost::getInstance($sId);
			if (
				$oFee->isValid() &&
				$oFee->idSchool == $this->oSchool->id &&
				$oFee->type == $iType &&
				$oFee->charge === 'semi'
			) {
				$oFee->transients['blocks'][] = $oBlock;
				$aAdditionalServices[] = $oFee;
			}
		}

	}

}

/**
 * Aktive Kurseinstellung aus dem Formular (Checkbox)
 */
class Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Course {

	/**
	 * @var Ext_Thebing_Tuition_Course
	 */
	public $oCourse;

	/**
	 * @var int[]
	 */
	public $aLevels;

	/**
	 * @var CourseStartDate[]
	 */
	public $aStartDates = [];

	/**
	 * Als erster Kurs verfügbar (wenn Option aktiviert)
	 *
	 * @var bool
	 */
	public $bFirstCourse = false;

	/**
	 * Als nachfolgender Kurs verfügbar (wenn Option aktiviert)
	 *
	 * @var bool
	 */
	public $bFollowingCourse = false;

	/**
	 * V3: Level pro Kurs anzeigen
	 *
	 * @var bool
	 */
	public $bShowLevel = false;

	/**
	 * @var Ext_Thebing_Form_Page_Block[]
	 */
	public $aBlocks = [];

	/** @var Ext_Thebing_School_Additionalcost[] */
	public $additionalServices = [];

}

/**
 * Aktive Unterkunftseinstellung aus dem Formular (Checkbox)
 */
class Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Accommodation {

	/**
	 * @var Ext_Thebing_Accommodation_Category
	 */
	public $oCategory;

	/**
	 * @var Ext_Thebing_Accommodation_Roomtype
	 */
	public $oRoomtype;

	/**
	 * @var Ext_Thebing_Accommodation_Meal
	 */
	public $oMeal;

	/**
	 * @var Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Accommodation_BookableDate[]
	 */
	public $aBookableDates = [];

	/** @var Ext_Thebing_School_Additionalcost[] */
	public $additionalServices = [];

}

/**
 * DTO für Startdaten von Unterkünften
 */
class Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Accommodation_BookableDate {

	/**
	 * @var DateTime
	 */
	public $dCourseStart;

	/**
	 * @var DateTime[]
	 */
	public $aStartDates;

	/**
	 * @var DateTime[]
	 */
	public $aEndDates;

}

/**
 * DTO für Transferkombination
 *
 * @TODO Generelles Objekt für Location + Sonderfälle School/Accommodation
 */
class Ext_TS_Frontend_Combination_Inquiry_Helper_Service_DTO_Transfer {

	/**
	 * @var string
	 */
	public $sType;

	/**
	 * @var string
	 */
	public $sFromKey;

	/**
	 * @var string
	 */
	public $sFromLabel;

	/**
	 * @var string
	 */
	public $sToKey;

	/**
	 * @var string
	 */
	public $sToLabel;

	public $fromPosition = 0;

	public $toPosition = 0;

}
