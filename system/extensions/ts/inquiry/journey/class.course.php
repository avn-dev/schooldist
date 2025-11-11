<?php

use Illuminate\Support\Arr;
use TsRegistrationForm\Interfaces\RegistrationInquiryService;
use TsCompany\Entity\JobOpportunity\StudentAllocation;
use TsTuition\Enums\LessonsUnit;

/**
 * @todo: from und until umstellen -> mysql date
 *
 * @property int $id
 * @property int $journey_id
 * @property string $changed (TIMESTAMP)
 * @property string $created (TIMESTAMP)
 * @property int|string $course_id
 * @property int|string $courselanguage_id
 * @property int|string $level_id
 * @property int|string $weeks
 * @property int|string $program_id
 * @property string $from (DATE)
 * @property string $until (DATE)
 * @property int $eduleave
 * @property int $calculate
 * @property int $visible
 * @property int $active
 * @property int $creator_id
 * @property int $for_tuition
 * @property int $program_change
 * @property float|string $units
 * @property int $flexible_allocation
 * @property string $number
 * @property int $numberrange_id
 * @property int $groups_course_id
 * @property string $comment
 * @property int $editor_id
 * @property array $index_attendance_warning
 * @property array $structure
 * @property array $tuition_index
 * @property int|string $automatic_renewal_origin
 * @property string $automatic_renewal_cancellation DATE
 * @property int $state (Bit)
 * @property string $lessons_catch_up_original_until DATE
 */
class Ext_TS_Inquiry_Journey_Course extends Ext_TS_Inquiry_Journey_Service implements Ext_TS_Service_Interface_Course, RegistrationInquiryService {

	use Ts\Traits\Numberrange;
	use Ts\Traits\Course\AdjustData;
	use Ts\Traits\LineItems\Course;
	use Core\Traits\WdBasic\MetableTrait;

	const STATE_EXTENDED_DUE_CANCELLATION = 1;

	protected $_sTable = 'ts_inquiries_journeys_courses';
    
	protected $_sTableAlias = 'ts_ijc';

	protected $_sPlaceholderClass = Ext_TS_Inquiry_Journey_Course_Placeholder::class;

    protected $sNumberrangeClass = Ext_TS_Numberrange_JourneyCourse::class;
	
	protected $_aFormat = array(
//		'journey_id'		=> array(
//			'required'			=> true,
//			'validate'			=> 'INT_POSITIVE',
//			'not_changeable'	=> true
//		),
		'level_id' => array(
			'validate' => 'INT'
		),
		'from' => array(
			'validate' => 'DATE',
			'required'=>true,
		),
		'until' => array(
			'validate' => 'DATE',
			'required'=>true,
		),
		'weeks' => array(
			'validate' => 'INT_POSITIVE',
			'required' => true
		),
		'index_attendance_warning' => [
			'format' => 'JSON',
		],
	);
	
	protected $_aJoinedObjects = array(
		// Da Klassenplanung überall Querys abfeuert, muss das hier readonly sein
        'tuition_blocks' => array(
			'class'					=> 'Ext_Thebing_School_Tuition_Allocation',
			'key'					=> 'inquiry_course_id',
			'check_active'			=> true,
			'type'					=> 'child',
			'readonly'				=> true,
        ),
		'tuition_blocks_writeable' => array(
			'class' => 'Ext_Thebing_School_Tuition_Allocation',
			'key' => 'inquiry_course_id',
			'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade',
			'bidirectional' => true
		),
		'course' => array(
			'class'					=> 'Ext_Thebing_Tuition_Course',
			'key'					=> 'course_id',
			'check_active'			=> true
        ),
		'level' => array(
			'class'					=> 'Ext_Thebing_Tuition_Level',
			'key'					=> 'level_id',
			'check_active'			=> true
		),
		'additionalservices' => [
			'class' => Ext_TS_Inquiry_Journey_Additionalservice::class,
			'key' => 'relation_id',
			'static_key_fields' => ['relation' => 'course'],
			'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade'
		],
		'program' => [
			'class' => \TsTuition\Entity\Course\Program::class,
			'key' => 'program_id',
			'check_active' => true
		],
		'job_allocations' => [
			'class' => \TsCompany\Entity\JobOpportunity\StudentAllocation::class,
			'key' => 'inquiry_course_id',
			'check_active' => true,
			'type' => 'child',
			'on_delete' => 'cascade'
		],
		'course_language' => [
			'class' => Ext_Thebing_Tuition_LevelGroup::class,
			'key' => 'courselanguage_id',
			'check_active' => true
		],
		'lessons_contingents' => [
			'class' => \Ts\Entity\Inquiry\Journey\Course\LessonsContingent::class,
			'key' => 'journey_course_id',
			'type' => 'child',
			'bidirectional' => true
		],
    );

	protected $_aJoinTables = array(
//        'module' => array(
//			'table' => 'kolumbus_inquiries_courses_modules',
//	 		'primary_key_field' => 'inquiry_course_id',
//			'autoload' => false
//        ),
		'tuition_index' => [
			'table'				=> 'ts_inquiries_journeys_courses_tuition_index',
			'primary_key_field'	=> 'journey_course_id',
			'autoload'			=> false,
			'readonly'			=> true
		],
		'additionalservices' => [
			'table' => 'ts_inquiries_journeys_additionalservices',
			'foreign_key_field' => 'additionalservice_id',
	 		'primary_key_field' => 'relation_id',
			'static_key_fields' => ['relation' => 'course'],
			'class' => Ext_Thebing_School_Additionalcost::class,
			'check_active' => true,
			'readonly' => true,
			'autoload' => false
		],
		// Wird nur bei Anfragen verwendet
		'travellers' => [
			'table' => 'ts_inquiries_journeys_courses_to_travellers',
			'foreign_key_field' => 'contact_id',
			'primary_key_field' => 'journey_course_id',
			'class' => Ext_TS_Inquiry_Contact_Traveller::class,
			'autoload' => false
		]
    );

	protected $_oCourse = null;

	/**
	 * @TODO Entfernen (Platzhalterlogik, welches dem Objekt einen externen State gibt)
	 */
	public $iCurrentWeek = null;

	protected $sInfoTemplateType = 'course';
	
	protected $_aFlexibleFieldsConfig = [
		'student_record_journey_course' => []
	];

	public function __get($key)
	{
		if ($key === 'units_dummy') {
			// Wird nur für den Anfragen-Dialog benötigt
			return $this->_aData['units'];
		}

		return parent::__get($key);
	}

	/**
	 * Delete All Tuition Data of Inquiry Course
	 */
	public function deleteTuition(){
		//TODO
	}

	public function deleteCoOp(bool $force = false) {

		if (!$force) {
			$finalAllocations = $this->getJobAllocations(StudentAllocation::STATUS_ALLOCATED);
			if (!empty($finalAllocations)) {
				// Wenn es bereits eine feste Zuweisung gibt können die Job-Zuweisungen nicht gelöscht werden
				throw new \Ts\Exception\Inquiry\Course\ExistingJobAllocation('Cannot delete job allocation because of final assignment.');
			}
		}

		$allocations = $this->getJobAllocations();

		foreach ($allocations as $allocation) {
			$this->deleteJoinedObjectChild('job_allocations', $allocation);
		}

	}

	public function getJobAllocations(int $flag = null): array {

		$allocations = $this->getJoinedObjectChilds('job_allocations', true);

		if ($flag !== null) {
			$allocations = array_filter($allocations, function (StudentAllocation $allocation) use ($flag) {
				return $allocation->hasFlag('status', $flag);
			});
		}

		return $allocations;
	}

	/**
	 * Check if the Course has Changed
	 * This Method must called bevor you call the save() Methode
	 *
	 * @TODO Brauchbar umbauen… Diese Methode prüft nur auf rechnungsrelevante Änderungen
	 *
	 * @return <bol>
	 */
	public function checkForChange(Ext_Thebing_Inquiry_Group_Course $oCourse = null, $sModus = 'complete'){

		if($this->id <= 0) {
			return 'new';
		}

		if($this->active == 0) {
			return 'delete';
		}

		if($oCourse == null) {
			$aOriginalData = $this->getOriginalData();
		} else {
			$aOriginalData = $oCourse->getData();
		}

		if($sModus == 'complete') {
			if(
				(int)$this->course_id	!= (int)$aOriginalData['course_id'] ||
				(int)$this->courselanguage_id != (int)$aOriginalData['courselanguage_id'] ||
				//(int)$this->level_id	!= (int)$aOriginalData['level_id'] ||
				$this->from				!= $aOriginalData['from'] ||
				$this->until			!= $aOriginalData['until'] ||
				(int)$this->weeks		!= (int)$aOriginalData['weeks'] ||
				(int)$this->units		!= (int)$aOriginalData['units'] ||
				(int)$this->visible		!= (int)$aOriginalData['visible']
			){
				return 'edit';
			} elseif ($oCourse !== null) {
				// Zusatzleistungen des Gruppen-Kurses mit Zusatzleistungen der Kursbuchung vergleichen
				$journeyGroupAdditionalServiceIds = [];
				foreach ($oCourse->additionalservices as $additionalserviceData) {
					$journeyGroupAdditionalServiceIds[] = $additionalserviceData['additionalservice_id'];
				}
				$journeyCourseAdditionalServiceIds = [];
				foreach ($this->getJoinedObjectChilds('additionalservices') as $additionalservice) {
					$journeyCourseAdditionalServiceIds[] = $additionalservice->additionalservice_id;
				}
				// Sortieren für den Vergleich
				sort($journeyGroupAdditionalServiceIds);
				sort($journeyCourseAdditionalServiceIds);
				if ($journeyGroupAdditionalServiceIds !== $journeyCourseAdditionalServiceIds) {
					return 'edit';
				}
			}
		} elseif($sModus == 'only_time'){
			if(
				$this->from				!= $aOriginalData['from'] ||
				$this->until			!= $aOriginalData['until'] ||
				(int)$this->weeks		!= (int)$aOriginalData['weeks'] ||
				(int)$this->units		!= (int)$aOriginalData['units']
			){
				return 'edit';
			}
		}

		
		return false;
	}

	public static function saveCourses($iInquiry,$aCourses,$bSortArray = true) {
		global $session_data;
		if($iInquiry > 0) {
			$oInquiry = new Ext_TS_Inquiry($iInquiry);
			$oSchool = $oInquiry->getSchool();

			if($oSchool->id > 0) {

				$iSchool = $oSchool->id;
				$aCoursesNew = array();
				if($bSortArray) {
					foreach((array)$aCourses as $sKey => $aCourse) {
						foreach((array)$aCourse as $iKey => $mValue) {
							$aCoursesNew[$iKey][$sKey] = $mValue;
						}
					}
				} else {
					$aCoursesNew = $aCourses;
				}
				foreach((array)$aCoursesNew as $aCourse) {

					$aError = false;
					$oCourse = new Ext_TS_Inquiry_Journey_Course();
					foreach($aCourse as $sField => $sValue) {

						if($sField == 'from' || $sField == 'until') {

							$sValue = Ext_Thebing_Format::ConvertDate($sValue,$iSchool);

							if((int)$sValue <= 0) {
								$aError = true;
							}
						}
						if($sField == 'course_id' && $sValue <= 0) {
							$aError = true;
						}
						if ( ($sField != 'units') && ($sValue == "") ) {
							$aError = true;
						}
						$oCourse->$sField = $sValue;
					}
					$oCourse->inquiry_id = $iInquiry;

					if($aError == false) {
						$oCourse->created = time();
						$oCourse->changed = time();
						$oCourse->save();
					}
					

				}
			}
			return true;
		} else {
			throw new Exception(" No Inquiry Data ");
		}
		return false;
	}

	/**
	 * get level in week for a course
	 *
	 * @param string $dWeek
	 * @return int
	 */
	public function getProgress($dWeek, $iLevelGroupId) {

		$iLevel = Ext_Thebing_Tuition_Progress::getProgress($this->id, 'period', 'id', $dWeek, $iLevelGroupId);

		return $iLevel;
	}

	/**
	 * get allocations in week for a course
	 *
	 * @return \Ext_Thebing_School_Tuition_Allocation[]
	 */
	public function getAllocationsByWeek(string $week) {
		return \Ext_Thebing_School_Tuition_Allocation::query()
			->select('ktbic.*')
			->join('kolumbus_tuition_blocks as ktb', function (\Illuminate\Database\Query\JoinClause $join) {
				$join->on('ktb.id', 'ktbic.block_id')
					->where('ktb.active', 1);
			})
			->where('ktbic.inquiry_course_id', $this->id)
			->where('ktbic.active', 1)
			->where('ktb.week', $week)
			->orderBy('ktb.week')
			->get();
	}

	/**
	 * Fortschritt (Tuition Progress) für Kurs speichern
	 *
	 * @param DateTime $dWeek
	 * @param Ext_Thebing_Tuition_Level $oLevel
	 * @param Ext_Thebing_Tuition_LevelGroup $oLevelGroup
	 * @param Ext_Thebing_Tuition_Course $oCourse
	 * @param int|null $iSelectedDay Ausgewählter Tag der Woche (null zum Deaktivieren der taggenauen Abfrage)
	 * @param bool $bCheckDifferentLevels
	 * @return array|bool
	 */
	public function saveProgress(DateTime $dWeek, Ext_Thebing_Tuition_Level $oLevel, Ext_Thebing_Tuition_LevelGroup $oLevelGroup, \TsTuition\Entity\Course\Program\Service $oProgramService, $iSelectedDay, $bCheckDifferentLevels=false) {

		// Sicherstellen, dass nur Montage übergeben werden
		if($dWeek->format('N') != 1) {
			throw new InvalidArgumentException('Given week identifier isn\'t a monday');
		}

		$aErrors = array();
		$sWeek = $dWeek->format('Y-m-d');
		$oInquiryCourse = self::getInstance($this->id);

		// Für taggenaue Abfrage auch ausgewählten Wochentag und Starttag der Kurswoche berücksichtigen
		if($iSelectedDay !== null) {
			$dWeekday = Ext_Thebing_Util::getRealDateFromTuitionWeek($dWeek, $iSelectedDay, $this->getSchool()->course_startday);
		}

		// Prüfen, ob Kunde mit seinem Kurs-Leistungszeitraum in den ausgewählten Tag des Blocks reinfällt
		// @TODO Diese taggenaue Abfrage müsste hier eigentlich rausfliegen, da es hier nur um das Speichern gehen sollte
		if(
			$iSelectedDay === null || ( // Bei $iSelectedDay === null darf nicht taggenau prüfen #6906
				$dWeekday >= new DateTime($oInquiryCourse->from) &&
				$dWeekday <= new DateTime($oInquiryCourse->until)
			)
		) {

			$oProgress = Ext_Thebing_Tuition_Progress::findByInquiryAndLevelGroupAndWeekAndCourse($oInquiryCourse, $oProgramService, $oLevelGroup, $sWeek);

			if(
				$bCheckDifferentLevels && // Wenn bei Änderungen erst einmal überprüft werden soll ohne zu speichern
				$oProgress->active == 1 && // Wenn gefundener Fortschritt nicht ein gelöschter Datensatz ist (aktive Zuweisung)
				(int)$oProgress->level != (int)$oLevel->id // Wenn Fortschritt vorher/nachher nicht übereinstimmt
			) {

				$aErrorData						= $oProgress->getArray();

				// Diese Informationen müssen vorsichtshalber gesetzt werden, falls bei 
				// findByInquiryAndLevelGroupAndWeek ein leeres Objekt zurück kommt
				$aErrorData['inquiry_id']		= $this->inquiry_id;
				$aErrorData['courselanguage_id'] = $oLevelGroup->id;
				$aErrorData['week']				= $sWeek;

				// Das komplette Objekt wäre viel zu heftig, da das ganz viele sein könnten, wenn eine Klasse eine sehr 
				// lange Laufzeit hat oder im Kopierdialog sehr viele Klassen noch nicht in der nächsten Woche existieren
				$aErrors['different_level'] = $aErrorData;

			}

			$oProgress->inquiry_course_id = $this->id;
			$oProgress->course_id = $oProgramService->getTypeId();
			$oProgress->program_service_id = $oProgramService->getId();
			$oProgress->week = $sWeek;
			$oProgress->level = $oLevel->id;
			$oProgress->inquiry_id = $this->inquiry_id;
			$oProgress->courselanguage_id = $oLevelGroup->id;
			$oProgress->active = 1;
			$oProgress->save();

			// Internes Level als Index-Spalte vorhanden: Inquiry aktualisieren
			Ext_Gui2_Index_Stack::add('ts_inquiry', $this->inquiry_id, 1);

		} else {

			// @TODO Hier sollte eine Fehlermeldung geliefert werden

		}
		
		if(empty($aErrors)) {
			return true;
		} else {
			return $aErrors;
		}

	}

	public static function getCourseNameForEditData($sCourse, $sLevel, $sFrom, $sUntil, $iWeeks, $iSchoolId, $bPerUnit = false) {
		$sFrom = Ext_Thebing_Format::LocalDate($sFrom, $iSchoolId);
		$sUntil = Ext_Thebing_Format::LocalDate($sUntil, $iSchoolId);
		$sWeeks = "";
		if($iWeeks == 1) {
			$sWeeks = Ext_Thebing_L10N::t('Woche');
		} else if($iWeeks > 1) {
			$sWeeks = Ext_Thebing_L10N::t('Wochen');
		}
		if ($bPerUnit !== false) {
			if($iWeeks == 1) {
				$sWeeks = Ext_Thebing_L10N::t('Lektion');
			} else if($iWeeks > 1) {
				$sWeeks = Ext_Thebing_L10N::t('Lektionen');
			}
		}

		$sName = $sCourse.', ';
		if($sLevel != ""){
			$sName .= $sLevel.', ';
		}
		$sName .= $sFrom." - ";
		$sName .= $sUntil;
		$sName .= " (".$iWeeks." ".$sWeeks.")";

		return $sName;
	}

	public static function getCourseExtraCostIDs($iCourseId = 0) {
		$sSql = "SELECT
					`kcf`.`cost_id` 
				FROM 
					`ts_inquiries_journeys_courses` AS `kic` INNER JOIN
					`kolumbus_course_fee` AS `kcf` ON
						`kcf`.`course_id` = `kic`.`course_id`
				WHERE 
					`kic`.`id` = :course_id
					";


		$aSql['course_id'] = (int)$iCourseId;
		$aResult = DB::getPreparedQueryData($sSql,$aSql);
		return $aResult;
	}

	public static function getLevelName ( $iLevelId = 0 ) {
		if ((int)$iLevelId < 1) {
			return "";
		}

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$sLang = $oSchool->getInterfaceLanguage();

		$sSQL = "
	    	SELECT
	    		#field
	    	FROM
	    		#table
	    	WHERE
	    		`id` = :level_id
	    ";
		$aSQL = array(
				"table"		=> "ts_tuition_levels",
				"level_id"	=> (int)$iLevelId,
				'field'		=> 'name_'.$sLang
		);
		$sLevelName = DB::getQueryOne($sSQL, $aSQL);
		return $sLevelName;
	}

	public static function _sortDates($sX, $sY) {
		return (strcmp($sX, $sY));
	}

	/**
	 * Gibt den entsprechenden Wert aus dem TuitionIndex zurück.
	 * @param string $sKey
	 * @param WDDate $oDate
	 */
	public function getTuitionIndexValue($sKey, \DateTimeInterface|WDDate $oDate=null) {
		
		if($oDate !== null && $oDate instanceof WDDate) {
			$oDate = new \Core\Helper\DateTime($oDate->get(WDDate::DB_DATE));
		}

		$mValue = Ext_TS_Inquiry_TuitionIndex::getWeekValue($sKey, $this, $oDate);

		return $mValue;

	}

	public function getTuitionCourseWeekStatus(\DateTimeInterface $date = null): ?string
	{
		if (!$date) {
			$date = \Carbon\Carbon::today()->startOfWeek();
		}

		if ($this->iCurrentWeek != null) {
			// Im Wochenloop ist das aktuelle Datum das Datum des Durchlaufs!
			// Die aktuelle Kurswoche wird durch den Loop gesetzt
			$courseWeekIterator = $this->iCurrentWeek;
		} else {
			// Aktuelles Datum ist tatsächliches Datum
			// Aktuelle Kurswoche wird anhand dieses Datums ermittelt
			$courseWeekIterator = $this->getTuitionIndexValue('current_week', $date);
		}

		// Array mit Kurswochen mit ihrem Startdatum in der Woche
		$courseWeeksWithDates = $this->getCourseWeeksWithDates();

		if (isset($courseWeeksWithDates[$courseWeekIterator])) {
			/** @var DateTime $courseWeek */
			$courseWeek = $courseWeeksWithDates[$courseWeekIterator];

			// Nächste Woche
			$nextWeek = (new DateTime())->add(new DateInterval('P1W'));

			// Kalenderwoche vergleichen zum Herausfinden, ob dies die aktuelle Woche ist
			if(date('Y-W') == $courseWeek->format('Y-W')) {
				return 'current';
			} elseif($nextWeek->format('Y-W') == $courseWeek->format('Y-W')) {
				return 'next';
			} elseif($courseWeekIterator == 1) {
				return 'first';
			} elseif($courseWeekIterator == $this->weeks) {
				return 'last';
			}

			return 'inner';
		}

		return null;
	}

	/**
	 * 
	 * @param integer $iAdditionalCostId
	 * @param integer $iWeeks
	 * @param integer $iCourseCount
	 * @param Tc\Service\LanguageAbstract $oLanguage
	 * @return string
	 */
	public function getAdditionalCostInfo($iAdditionalCostId, $iWeeks, $iCourseCount, Tc\Service\LanguageAbstract $oLanguage) {

		$oInquiry = $this->getInquiry();

		$oAdditionalCost = Ext_Thebing_School_Additionalcost::getInstance($iAdditionalCostId);

		$oCourse						= $this->getCourse();		
		$sCourseDescription				= $oCourse->getName($oLanguage->getLanguage());

		// Name definieren
		$sCostName = $oAdditionalCost->getName($oLanguage->getLanguage());

		if((int) $oAdditionalCost->calculate == 2) {
			
			$sWeeks = $oLanguage->translate('Woche');
			if($iWeeks > 1) {
				$sWeeks = $oLanguage->translate('Wochen');
			}

			if($iCourseCount > 1) {
				$sCostName .= ' (' . $iWeeks . ' ' . $sWeeks . ' '.$sCourseDescription . ')';
			}
			
		} elseif(
			(int) $oAdditionalCost->calculate == 1 ||
			$oAdditionalCost->charge == 'semi'
		) {
			// Wenn nur 1 Kurs braucht der Kursname hier nicht stehen
			if($iCourseCount > 1) {
				$sCostName = trim($sCostName);
				$sCostName .= ' ('.$sCourseDescription . ')';
			}
		}

		// Gruppen Guide checken und Amount löschen
		if(
			$oInquiry->hasGroup() &&
			$oInquiry->isGuide() &&
			(
				$oInquiry->getJourneyTravellerOption('free_course_fee') ||
				$oInquiry->getJourneyTravellerOption('free_all')
			)
		) {
			// Gratis-Gruppen-Guides extra aufführen in Maske
			$sCostName .= ' ('.$oLanguage->translate('gratis').')';
		}

		return $sCostName;
	}
	
	/**
	 * @deprecated
	 * @param int $iSchoolId
	 * @param string $sDisplayLanguage
	 * @return string
	 */
	public function getInfo($iSchoolId = false, $sDisplayLanguage = false, \TsTuition\Entity\Course\Program\Service $oProgramService = null) {
		return self::getOutputInfo($this, $iSchoolId, $sDisplayLanguage, $oProgramService);
	}

	/**
	 * @deprecated
	 * @param Ext_TS_Service_Interface_Course|Ext_TS_Inquiry_Journey_Course|Ext_TS_Enquiry_Combination_Course $oServiceCourse
	 * @param int $iSchoolId
	 * @param string $sDisplayLanguage
	 * @return string
	 */
	public static function getOutputInfo(Ext_TS_Service_Interface_Course $oServiceCourse, $iSchoolId = null, $sDisplayLanguage = null, \TsTuition\Entity\Course\Program\Service $oProgramService = null) {

		$iWeeks = $oServiceCourse->weeks;
		$sForm = $oServiceCourse->from;
		$sUntil = $oServiceCourse->until;

		if(!is_null($oProgramService)) {
			$oCourse = $oProgramService->getService();

			if($oProgramService->hasDates()) {
				$iWeeks = $oProgramService->getWeeks();
				$sForm = $oProgramService->getFrom()->toDateString();
				$sUntil = $oProgramService->getUntil()->toDateString();
			}
		} else {
			$oCourse = $oServiceCourse->getCourse();
		}

		$aParams = array(
			'course' => $oServiceCourse->getCourse(),
			'weeks' => $iWeeks,
			'from' => $sForm,
			'until' => $sUntil,
			'units' => $oServiceCourse->units,
			'school_id' => $iSchoolId,
			'language' => $sDisplayLanguage,
			'format' => false
		);

		if($oServiceCourse instanceof Ext_TS_Enquiry_Combination_Course) {
			$oInquiry = $oServiceCourse->getEnquiry();
		} else {
			/** @var Ext_TS_Inquiry $oInquiry */
			$oInquiry = $oServiceCourse->getInquiry();
		}

		if(!$aParams['school_id']) {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();

			$aParams['school_id'] = $oSchool->id;
		} else {
			$oSchool = Ext_Thebing_School::getInstance($aParams['school_id']);
		}

		if(!$aParams['language']) {
			$aParams['language'] = $oSchool->getInterfaceLanguage();
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Generate units part of name

		if(
			$oCourse->per_unit != $oCourse::TYPE_PER_UNIT &&
			$oCourse->getField('price_calculation') === 'month'
		) {
			$iMonths = Ext_TS_Inquiry_Journey_Service::getMonthCount($oServiceCourse);
			$sLabel = $iMonths === 1 ? 'Monat' : 'Monate';
			$sName = sprintf('%d %s', $iMonths, Ext_TC_Placeholder_Abstract::translateFrontend($sLabel, $aParams['language']));
		} elseif($oCourse->per_unit != $oCourse::TYPE_PER_UNIT) {
			$sName = $aParams['weeks'] . ' ';

			if($aParams['weeks'] == 1) {
				$sName .= \Ext_TC_Placeholder_Abstract::translateFrontend('Woche', $aParams['language']);
			} else {
				$sName .= \Ext_TC_Placeholder_Abstract::translateFrontend('Wochen', $aParams['language']);
			}
		} else {
			$sName = $aParams['units'] . ' ';

			if($aParams['units'] == 1) {
				$sName .= \Ext_TC_Placeholder_Abstract::translateFrontend('Lektion', $aParams['language']);
			} else {
				$sName .= \Ext_TC_Placeholder_Abstract::translateFrontend('Lektionen', $aParams['language']);
			}
		}

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Generate name and dates

		$sName .= ' ' . $oCourse->getName($aParams['language']) . ' ';

//		if($aParams['format']) {
//			$sFormat = Ext_Thebing_Format::getDateFormat($aParams['school_id']);
//
//			if(WDDate::isDate($aParams['from'], WDDate::DB_DATE)) {
//				$oDate = new WDDate($aParams['from'], WDDate::DB_DATE);
//			} else {
//				$oDate = new WDDate((int)$aParams['from']);
//			}
//
//			$sName .= $oDate->get(WDDate::STRFTIME, $aParams['format']) . ' - ';
//
//			if(WDDate::isDate($aParams['until'], WDDate::DB_DATE)) {
//				$oDate = new WDDate($aParams['until'], WDDate::DB_DATE);
//			} else {
//				$oDate = new WDDate((int)$aParams['until']);
//			}
//
//			$sName .= $oDate->get(WDDate::STRFTIME, $aParams['format']);
//		} else {
			$sName .= Ext_Thebing_Format::LocalDate($aParams['from'], $aParams['school_id']) . ' - ';
			$sName .= Ext_Thebing_Format::LocalDate($aParams['until'], $aParams['school_id']);
//		}
//
//		$oInquiry = false;
//
//		if(!empty($aParams['inquiry']))
//		{
//			$oInquiry = $aParams['inquiry'];
//		}
//		elseif(!empty($aParams['inquiry_id']))
//		{
//			$oInquiry = Ext_TS_Inquiry::getInstance($aParams['inquiry_id']);
//		}

		// Gratis-Gruppen-Guides extra aufführen in Maske
		if(
			is_object($oInquiry) &&
			$oInquiry instanceof Ext_TS_Inquiry_Abstract &&
			$oInquiry->hasGroup() &&
			$oInquiry->hasTraveller() &&
			$oInquiry->isGuide() &&
			(
				$oInquiry->getJourneyTravellerOption('free_course') ||
				$oInquiry->getJourneyTravellerOption('free_all')
			)
		) {
			$sName .= ' ('.\Ext_TC_Placeholder_Abstract::translateFrontend('gratis', $aParams['language']) . ')';
		}

		return $sName;
	}


	/*
	 * Beschreibender Text, wenn der Kurs als Special auf der Rechnung erscheint
	 */
	public function getSpecialInfo($iSchoolId, $sDisplayLanguage) {

		$oFrontendLanguage = new \Tc\Service\Language\Frontend($sDisplayLanguage);
		
		$sName = $this->getLineItemDescription($oFrontendLanguage);

		$sName = $oFrontendLanguage->translate('Vergünstigung für:') . ' ' . $sName;

		return $sName;
	}

	/**
	 * @todo TuitionIndex hab ich erstmal entfernt, wurde zu oft und zu früh 
	 * aufgerufen, Daten waren noch nicht komplett gespeichert
	 * @param boolean $bLog
	 * @return mixed
	 */
	public function save($bLog = true) {

		$bNew = !$this->exist();
		$aOriginalData = $this->_aOriginalData;

		// Das darf nicht leer sein, aber $_aFormat funktioniert auch nicht mit Joined Objects
		if (
			$this->active &&
			$this->visible && (
				empty($this->course_id) ||
				empty($this->program_id)
			)
		) {
			throw new RuntimeException('Journey course is missing course_id or program_id');
		}

		$course = $this->getCourse();
		
		// Feld darf nur bei Lektionskursen einen Wert haben
		if(!$course->isPerUnitCourse()) {
			$this->units = 0;
		}
		
		// Prüfungen können Nummernkreise haben
		if ($course->getType() === 'exam') {
			if ($this->_aData['from'] !== $this->_aOriginalData['from']) {
				$this->number = null;
			}

			$this->generateNumber();
		}

		$this->adjustData();

		/*
		 * @todo Ist nur temporär hier wg. der Kurssprachenumstellung. Muss wieder raus!
		 */
		if(empty($this->courselanguage_id)) {
			$this->courselanguage_id = $this->getCourse()->getFirstCourselanguageId();
		}

		$tuitionFieldsChanged = false;
		if (
			$aOriginalData['course_id'] != $this->course_id ||
			$aOriginalData['program_id'] != $this->program_id ||
			$aOriginalData['weeks'] != $this->weeks ||
			$aOriginalData['units'] != $this->units
		) {
			$tuitionFieldsChanged = true;
		}

		if ($bNew || $tuitionFieldsChanged) {
			
			// Lektionskontingent anlegen
			$this->adjustLessonsContingents();
			
			\Core\Entity\ParallelProcessing\Stack::getRepository()->writeToStack('ts-tuition/course-extension-allocation', ['inquiry_id'=>$this->getJourney()->getInquiry()->id], 1);

		}

		$mReturn = parent::save($bLog);

		// Wenn verkürzt oder verlängert wird: Einträge aus der Klassenplanung entfernen
		// Auf $bNew prüfen, da man das einerseits da nicht braucht und andererseits die Methoden mit dem ID-Gepfusche nicht funktionieren
		if(!$bNew) {
			$this->adjustProgress();
			$this->adjustTuitionAllocations();
		}

		if($tuitionFieldsChanged && $aOriginalData['course_id'] > 0) {
			// Wenn Kurs verändert wird & Datensatz nicht neu, Einträge in der Klassenplanung ändern
			Ext_Thebing_School_Tuition_Allocation::checkJourneyCourseAllocations($this);
		}

		if (
			($aOriginalData['course_id'] != $this->course_id || $aOriginalData['program_id'] != $this->program_id) &&
			$this->_aData['course_id'] > 0
		) {
			\Ts\Events\Inquiry\Services\CourseBooked::dispatch($this->getJourney()->getInquiry(), $this);
		}

		return $mReturn;

	}

	public function adjustLessonsContingents(): static
	{
		if ($this->isActive()) {
			$program = $this->getProgram();
			$programServices = $program->getServices(\TsTuition\Entity\Course\Program\Service::TYPE_COURSE);

			foreach ($programServices as $programService) {
				$contingent = $this->getLessonsContingent($programService);
				$contingent->refresh(\Ts\Entity\Inquiry\Journey\Course\LessonsContingent::ABSOLUTE);
			}
		}

		return $this;
	}

	/**
	 * Bei Veränderungen der Kursbuchung die Zuweisungen in der Klassenplanung anpassen
	 *
	 * Es werden immer alle Zuweisung außerhalb des Zeitraums gelöscht (oder alle bei active/visible = 0). Etwaige
	 * Zuweisungen nach Veränderung von Ferien sind immer neue Zuweisungen.
	 *
	 * @TODO Eventuell neu implementieren, da das auch einfacher gehen müsste.
	 */
	public function adjustTuitionAllocations() {

		$bDeleted = !$this->active || !$this->visible;
		$oSchool = $this->getSchool();

		// Wenn diese Kursbuchung nicht gelöscht wurde, nur Zuweisungen suchen, die außerhalb dieses Zeitraumes noch existieren
		$sWhere = '';
		if(!$bDeleted) {
			$sWhere = " AND
				(
					SELECT
						SUM(
							IF(
								getRealDateFromTuitionWeek(
									`ktb`.`week`,
									`ktbd`.`day`,
									`cdb2`.`course_startday`
								) BETWEEN :from AND :until,
								1,
								0
							)
						)
					FROM
						`kolumbus_tuition_blocks_days` `ktbd`
					WHERE
						`ktbd`.`block_id` = `ktb`.`id`
				) = 0
			";
		}

		$sSql = "
			SELECT
				`ktbic`.*
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`id` = `ktbic`.`block_id` AND
					`ktb`.`active` = 1 INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` =`ktb`.`school_id`
			WHERE
				`ktbic`.`inquiry_course_id` = :id AND
				`ktbic`.`active` = 1
				$sWhere
			ORDER BY
				`ktb`.`week`
		";

		$aResult = (array)DB::getQueryRows($sSql, $this->getData());

		// Wenn es keine Zuweisungen gibt, muss auch nichts gemacht werden
		if(empty($aResult)) {
			return;
		}

		// Wenn automatische Zuweisung nach Ferien: Kursstruktur ermitteln und prüfen, ob Kurs Ferien hat
		$bCheckHolidays = false;
		$aCourseStructureWeeks = [];
		if(
			!$bDeleted &&
			$oSchool->tuition_automatic_holiday_allocation
		) {
			$aCourseStructureWeeks = $this->getCourseStructureWeeks();
			foreach($aCourseStructureWeeks as $aWeek) {
				if($aWeek['type'] === 'holiday') {
					$bCheckHolidays = true;
					break;
				}
			}
		}

		/** @var Ext_Thebing_School_Tuition_Allocation[] $aAllAllocations */
		$aAllAllocations = array_map(fn(array $aAllocation) => Ext_Thebing_School_Tuition_Allocation::getObjectFromArray($aAllocation), $aResult);
		$aHolidayAllocations = array_filter($aAllAllocations, function (Ext_Thebing_School_Tuition_Allocation $oAllocation) use ($bCheckHolidays, $aCourseStructureWeeks) {
			$sBlockWeek = (new DateTime($oAllocation->getBlock()->week))->format('Y-W');
			return $bCheckHolidays && isset($aCourseStructureWeeks[$sBlockWeek]);
		});

		// Zuweisungen, die nun in Ferien liegen, durchlaufen und ggf. neu zuweisen
		$iOffset = 0;
		foreach($aHolidayAllocations as $oAllocation) {
			$oBlock = $oAllocation->getBlock();
			$dBlockWeek = \Carbon\Carbon::parse($oBlock->week);

			// Einen Tag addieren, damit Block derselben Woche nicht wieder dabei ist
			$dBlockWeek->addDay();

			// Da die Blöcke mit jeder Zuweisung neu geholt werden, muss ein Offset »gesammelt« werden, damit nicht wieder in der gleichen Woche zugewiesen wird
			$dBlockWeek->addWeeks($iOffset);

			// Nachfolgende Blöcke dieses Blocks ab der Ẃoche der Zuweisung
			$aRelevantBlocks = $oBlock->getRelevantBlocks($dBlockWeek);

			// Blöcke durchlaufen und ersten Block finden, wo eine Zuweisung gemacht werden kann
			foreach($aRelevantBlocks as $oRelevantBlock) {
				$dRelevantBlockWeek = new DateTime($oRelevantBlock->week);
				$sRelevantBlockWeekKey = $dRelevantBlockWeek->format('Y-W');

				// Woche des Blocks existiert nicht im Leistungszeitraum der Kursstruktur: Abbruch
				if(!isset($aCourseStructureWeeks[$sRelevantBlockWeekKey])) {
					break;
				}

				// Woche des aktuellen Blocks fällt in Ferienzeitraum der Kursstruktur: Block überspringen und nächsten nehmen
				if($aCourseStructureWeeks[$sRelevantBlockWeekKey]['type'] === 'holiday') {
					continue;
				}

				// Kursbuchung dieser Woche aus der Kursstruktur, nicht die Kursbuchung aus der Zuweisung
				$oJourneyCourse = $aCourseStructureWeeks[$sRelevantBlockWeekKey]['journey_course']; /** @var Ext_TS_Inquiry_Journey_Course $oJourneyCourse */

				if(
					// Hier sollte man mal die Glaskugel befragen, welche Methode eigentlich was macht
//					!$oRelevantBlock->isDoubleAllocation($oJourneyCourse) &&
					$oRelevantBlock->checkInquiryCourse($oJourneyCourse, $oAllocation->program_service_id, $oAllocation->room_id) === true
				) {
					// Da Journey-Kurs auch ID 0 haben kann, muss hier ein Objekt weitergegeben werden…
					$mAssignment = $oRelevantBlock->addInquiryCourse($oJourneyCourse, $oAllocation->program_service_id, $oAllocation->room_id);

					if(
						$mAssignment === 'expired' ||
						!empty($mAssignment['replaced'])
					) {
						throw new RuntimeException('Holiday block allocation failed despite check: '.print_r($mAssignment, true));
					}

					$iOffset = max($iOffset, $dBlockWeek->diffInWeeks($dRelevantBlockWeek));

					$this->aErrors['course_adjusted_allocations_holiday_adjustments'][] = [$oAllocation, $mAssignment['allocation']];

				} else {
					// Wenn nicht zugewiesen werden konnte, kann die Zuweisung nur gelöscht werden und das soll gesondert erwähnt werden
					$this->aErrors['course_adjusted_allocations_holiday_deletions'][] = $oAllocation;
				}

				// Zuweisung wurde entweder verschoben (neu angelegt) oder wird gelöscht; Schleife nur relevant für Ferien-Zeiträume (continue oben)
				break;

			}
		}

		foreach($aAllAllocations as $oAllocation) {
			// Zuweisung und Anwesenheit löschen
			$oAllocation->delete();

			// Verschobene Zuweisungen sollen hier natürlich nicht wieder angezeigt werden
			if(!in_array($oAllocation, $aHolidayAllocations)) {
				$this->aErrors['course_adjusted_allocations_deletions'][] = $oAllocation;
			}
		}

	}

	/**
	 * Internen Fortschritt löschen, welcher nicht mehr in den Leistungszeitraum passt
	 */
	public function adjustProgress() {

		$sSql = "
			UPDATE
				`kolumbus_tuition_progress`
			SET
				`active` = 0
			WHERE
				`inquiry_course_id` = :id
		";

		$aSql = ['id' => $this->id];

		if(
			$this->active == 1 &&
			$this->visible == 1
		) {

			$from = new DateTime($this->from);
			$monday = $from->modify('monday this week')->format('Y-m-d');

			$sSql .= " AND
				`week` NOT BETWEEN :from AND :until
			";

			$aSql['from'] = $monday;
			$aSql['until'] = $this->until;
		}


		DB::executePreparedQuery($sSql, $aSql);

	}

	/**
	 * Liefert die Levelgruppe
	 * @return Ext_Thebing_Tuition_LevelGroup 
	 */
	public function getLevelGroup(){
		
		$oLevelGroup = $this->getCourseLanguage();
		
		return $oLevelGroup;
	}

	public function getCustomer(){
		$oInquiry = $this->getInquiry();
		return $oInquiry->getCustomer();
	}
	
	/**
	 *
	 * @return Ext_Thebing_Tuition_Course
	 */
	public function getCourse()
	{
		// Umgebaut, ansonsten hat man einen falschen Kurs wenn getCourse() aufgerufen wurde und im Anschluss die course_id
		// verändert wird. Dieses $_oCourse macht eigentlich nur Sinn wenn setCourse() benutzt wird. Aufgefallen beim
		// Ändern eines Gruppenkurses in adjustData()
		if($this->_oCourse !== null) {
			return $this->_oCourse;
		}
		
		return Ext_Thebing_Tuition_Course::getInstance($this->course_id);
	}

	/**
	 * @return \TsTuition\Entity\Course\Program
	 */
	public function getProgram() {
		return $this->getJoinedObject('program');
	}

	/**
	 * @TODO Entfernen (was soll dieser zusätzliche State?)
	 *
	 * @param Ext_Thebing_Tuition_Course $oCourse
	 */
	public function setCourse(Ext_Thebing_Tuition_Course $oCourse)
	{
		$this->_oCourse = $oCourse;
	}
	
	/*
	 * Liefert die Kursklänge des Kurses
	 */
	public function getLength(){
		
		$iLength = 0;
		
		$oDateFrom = new WDDate();	
		$oDateFrom->set($this->from, WDDate::DB_DATE);
		
		$oDateUntil = new WDDate();	
		$oDateUntil->set($this->until, WDDate::DB_DATE);
		
		$iLength = $oDateUntil->getDiff(WDDate::DAY, $oDateFrom);
		
		
		return $iLength;
	}
	
	/*
	 * Prüft welche Bezahlungen für diesen Kurs  getätigt wurden
	 */
	public function getPayments(){
		
		$aPayments = array();
		
		$aTuitionBlockInquirieCourses = $this->getJoinedObjectChilds('tuition_blocks');
		
		foreach((array)$aTuitionBlockInquirieCourses as $oTuitionBlockInquirieCourse) {
			/** @var Ext_Thebing_School_Tuition_Block $oTuitionBlock */
			$oTuitionBlock = $oTuitionBlockInquirieCourse->getJoinedObject('block');

			if(is_object($oTuitionBlock)) {

				// rausfinden ob ein Lehrer für den Kurszeitpunkt bereits bezahlt wurde
				$aPaymentsTemp = $oTuitionBlock->getPayments();

				$aPayments = array_merge($aPayments, $aPaymentsTemp);

			}
		}
		
		return $aPayments;
	}
	
	public function validate($bThrowExceptions = false) {

		$aErrors = parent::validate($bThrowExceptions);
	
		if(
			$aErrors === true
		){
			$aErrors = array();
		}
		
		$oCourse = $this->getJoinedObject('course');

		if(
			!$oCourse->isValid($this->until) &&
            $this->isActive()
		){
			$aErrors[$this->_sTableAlias.'.course_id'][] = 'COURSE_NOT_VALID';
		}

		if(
			$this->active &&
			$this->units > Ext_Thebing_Tuition_Course::getMaxUnits()
		) {
			$aErrors[$this->_sTableAlias.'.units'][] = 'TO_HIGH';
		}

		if ($this->exist()) {
			$attendances = new \Illuminate\Support\Collection();
			if (
				(
					$this->visible == 0 &&
					$this->visible != $this->getOriginalData('visible')
				) || (
					$this->active == 0 &&
					$this->active != $this->getOriginalData('active')
				) || (
					$this->course_id != $this->getOriginalData('course_id')
				)
			) {
				$attendances->merge(Ext_Thebing_Tuition_Attendance::getActiveAttendances([
					['journey_course_id', '=', $this->id]
				]));
			}
			if (
				$this->until != $this->getOriginalData('until') ||
				$this->from != $this->getOriginalData('from')
			) {
				$attendances = $attendances->merge(Ext_Thebing_Tuition_Attendance::getActiveAttendances(
					[
						['journey_course_id', '=', $this->id]
					],
					\Carbon\Carbon::parse($this->until)->addDay()
				));
				$attendances = $attendances->merge(Ext_Thebing_Tuition_Attendance::getActiveAttendances(
					[
						['journey_course_id', '=', $this->id]
					],
					null,
					\Carbon\Carbon::parse($this->from)->subDay()
				));
			}
			if (
				(
					$attendances->isNotEmpty() &&
					!\Ext_Thebing_School::getSchoolFromSession()?->tuition_allow_allocation_with_attendances_modification
				) &&
				(
					!isset($aErrors[$this->_sTableAlias . '.course_id']) ||
					!in_array('ATTENDANCES_EXIST', $aErrors[$this->_sTableAlias . '.course_id'])
				)
			) {
				$aErrors[$this->_sTableAlias . '.course_id'][] = 'ATTENDANCES_EXIST';
			}
		}

		if(empty($aErrors)){
			$aErrors = true;
		}
		
		return $aErrors;
	}
	
	public function getKey()
	{
		return 'course';
	}
	
	public function checkHoliday()
	{
		return true;
	}
	
	/**
	 * Kursstart (wenn $iCurrentWeek gesetzt, ändert sich der Wert)
	 * 
	 * @return string 
	 */
	public function getFrom()
	{
		// TODO Diese Platzhalterlogik gehört hier nicht rein!
		if($this->iCurrentWeek !== null)
		{
			// Wenn aktuelle Woche gesetzt ist
			
			// x Wochen je nach CurrentWeek hinzufügen
			$oDate		= new WDDate($this->from, WDDate::DB_DATE);
			$iWeekAdd	= $this->iCurrentWeek - 1;// CurrentWeek wird immer ab 1 gesetzt, darum immer -1 adden
			$oDate->add($iWeekAdd, WDDate::WEEK);
			
			// Wenn nicht erste Woche, dann immer den Wochentag auf Montag setzen, falls der Kurs ab Dienstag beginnt
			if($this->iCurrentWeek != 1)
			{
				$oDate->set(1, WDDate::WEEKDAY);
			}
			
			$sFrom		= $oDate->get(WDDate::DB_DATE);
		}
		else
		{
			$sFrom = $this->from;
		}
		
		// wegen index
		// 0000-00-00 darf es nicht geben und ist gleichbedeuten mit
		// existiert nicht daher "null"
		if($sFrom == '0000-00-00'){
			$sFrom = null;
		}

		return $sFrom;
	}

	public function getProgramServiceFrom(\TsTuition\Entity\Course\Program\Service $service): ?\Carbon\Carbon {

		if (null !== ($from = $service->getFrom())) {
			return $from;
		}

		if (null !== ($from = $this->getFrom())) {
			return \Carbon\Carbon::parse($from);
		}

		return null;
	}

	/**
	 * Kursende (wenn $iCurrentWeek gesetzt, ändert sich der Wert)
	 * 
	 * @return string 
	 */
	public function getUntil()
	{
		// TODO Diese Platzhalterlogik gehört hier nicht rein!
		if($this->iCurrentWeek !== null)
		{
			// Wenn aktuelle Woche gesetzt ist
			
		
			// 6 Tage auf das von-Datum setzen (Mo-So)
			$sFrom = $this->getFrom();
			$oDate = new WDDate($sFrom, WDDate::DB_DATE);
			$oDate->add(6, WDDate::DAY);

			if($oDate->compare($this->until, WDDate::DB_DATE) > 0)
			{
				// Wenn das -bis Datum größer ist als das ursprüngliche -bis Datum,
				// dann den OriginalWert setzen, da dieser nie überschritten werden darf
				$oDate->set($this->until, WDDate::DB_DATE);
			}
			
			$sUntil = $oDate->get(WDDate::DB_DATE);
		}
		else
		{
			$sUntil = $this->until;
		}
		
		// wegen index
		// 0000-00-00 darf es nicht geben und ist gleichbedeuten mit
		// existiert nicht daher "null"
		if($sUntil == '0000-00-00'){
			$sUntil = null;
		}
		
		return $sUntil;
	}

	/**
	 * Kurszeitraum als date_range für Elasticsearch
	 *
	 * @return array|null
	 */
	public function getFromAndUntilRangeForIndex() {

		if(
			!Core\Helper\DateTime::isDate($this->from, 'Y-m-d') ||
			!Core\Helper\DateTime::isDate($this->until, 'Y-m-d')
		) {
			return null;
		}

		// Zusätzliche Abfrage damit "kaputte" Kursbuchungen trotzdem in den Index eingetragen werden
		$dFrom = new DateTime($this->from);
		$dUntil = new DateTime($this->until);

		if($dFrom > $dUntil) {
			return null;
		}
		
		return [
			'gte' => $this->from,
			'lte' => $this->until
		];

	}
	
	/**
	 *
	 * @return Ext_Thebing_Tuition_Level 
	 */
	public function getLevel() {
		return $this->getJoinedObject('level');
	}
	
	public function setArrayData(array $aSetData)
	{
		foreach($aSetData as $sKey => $mData)
		{
			if(isset($this->_aData[$sKey]))
			{
				$this->_aData[$sKey] = $mData;
			}
		}
	}
    
    public function getServiceStartDay(){
        return $this->getSchool()->getCourseStartDay();
    }
    
    public function getServiceEndDay(){
        return $this->getSchool()->getCourseEndDay();
    }
	
    /**
     * definiert ob der Service in Schulferien blockiert wird oder ob er trozdem stattfinden kann
     * @return boolean
     */
    public function splitByHolidays(){
        $oCourse = $this->getCourse();
        return (int)$oCourse->schoolholiday === 0;
    }
	
	public function getAttendance($dFrom = false, $dUntil = false, $aFilter = [])
	{
		$oAttendanceIndex = new Ext_Thebing_Tuition_Attendance_Index();
		
		if($dFrom)
		{
			if($dUntil)
			{
				$aFilter['week_from']	= $dFrom;
				$aFilter['week_until']	= $dUntil;
			}
			else
			{
				$aFilter['week']		= $dFrom;
			}
		}
	
		$fAttendance = $oAttendanceIndex->getAttendanceForJourneyCourse($this, true, $aFilter);
		
		return $fAttendance;
	}

	/**
	 * Liefert die Kurswochen mit ihrem Startdatum in der Woche
	 * Immer Montag, egal welcher Kursstarttag ist!
	 * 
	 * @return array
	 */
	public function getCourseWeeksWithDates() {
		
		$aWeeks = [];

		$from = new DateTime($this->from);
		$week = \Ext_Thebing_Util::getPreviousCourseStartDay($from, $this->getSchool()->course_startday);
		
		for($i = 0; $i < $this->weeks; ++$i) {
			$oDate = clone $week;
			$oDate->add(new DateInterval('P'.($i).'W'));
			$aWeeks[$i + 1] = $oDate;
		}

		return $aWeeks;
	}
	
	/**
	 * liefert den Kursname inkl. gebuchter Lektionen (falls vorhanden)
	 *
	 * @param bool $bShort
	 * @param bool $bUnits
	 * @return string
	 */
	public function getCourseName($bShort = false, $bUnits = false) {
		
		$oCourse = $this->getCourse();
		
		if($bShort) {
			$sName = $oCourse->getShortName();
		} else {
			$sName = $oCourse->getName();
		}

		if($bUnits) {
			$iUnits = $this->getUnits();
			if($iUnits > 0) {
				$oFloatFormat = new Ext_Thebing_Gui2_Format_Float(2, false);
				$sName .= ' ('.$oFloatFormat->format($iUnits).')';
			}
		}

		return $sName;

	}

	/**
	 * Liefert die Anzahl der gebuchten Lektionen dieses Kurses.
	 *
	 * @return float
	 */
	public function getUnits() {

		$courseProgram = $this->getProgram()->getServices(\TsTuition\Entity\Course\Program\Service::TYPE_COURSE);

		$units = $courseProgram->map(fn ($service) => $this->getLessonsContingent($service)->absolute)
			->sum();

		return (float)$units;
	}

	/**
	 * @param $aSqlParts
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView = null) {

		$this->addStudentListParts($aSqlParts);

		$sInterfaceLanguage = Ext_TC_System::getInterfaceLanguage();

		$aSqlParts['select'] .= ",
			`ktc`.`id` `course_id`,
			`ktc`.`name_short`,
			`ts_ijc`.`number`,
			`ktc`.`name_".$sInterfaceLanguage."` `course_full_name`,
			`ts_i`.`checkin`,
			IF(
				`k_tl`.`type` = 'normal',
				`k_tl`.`name_".$sInterfaceLanguage."`,
				''
			) `level_name`,
			SUM(`ts_ijclc`.`absolute`) `lessons_absolute`,
			SUM(`ts_ijclc`.`absolute` - `ts_ijclc`.`used`) `lessons_remaining`,
			SUM(`ts_ijclc`.`used`) `lessons_used`,
			SUM(`ts_ijclc`.`cancelled`) `lessons_cancelled`,
			`ts_i`.`amount` + 
			`ts_i`.`amount_initial` `amount_total_original`,
			`ts_i`.`amount` + 
			`ts_i`.`amount_initial` - 
			`ts_i`.`amount_payed_prior_to_arrival` - 
			`ts_i`.`amount_payed_at_school` - 
			`ts_i`.`amount_payed_refund` `amount_open_original`,
			`ts_i`.`currency_id`,
			`ktp`.`week` `latest_level_change_week`,
			`ktp`.`level` `latest_level_change_level_id`
		";

		$aSqlParts['from'] .= " INNER JOIN
			`kolumbus_tuition_courses` `ktc` ON
				`ktc`.`id` = `ts_ijc`.`course_id` AND
				`ktc`.`active` = 1 LEFT JOIN
			`ts_tuition_levels` `k_tl` ON
				`k_tl`.`id` = `ts_ijc`.`level_id` AND
				`k_tl`.`active` = 1 LEFT JOIN
			`kolumbus_tuition_progress` `ktp` ON
				`ktp`.`id` = `ts_ijc`.`index_latest_level_change_progress_id` LEFT JOIN
			`ts_tuition_courses_programs_services` `ts_ijcps` ON
				`ts_ijcps`.`program_id` = `ts_ijc`.`program_id` AND
				`ts_ijcps`.`active` = 1 LEFT JOIN
			`ts_inquiries_journeys_courses_lessons_contingent` `ts_ijclc` ON
				`ts_ijclc`.`journey_course_id` = `ts_ijc`.`id` AND
				`ts_ijclc`.`program_service_id` = `ts_ijcps`.`id`		
		";

		$aSqlParts['groupby'] = "
			`ts_ijc`.`id`
		";

	}

	/**
	 * Errechnet den Kurspreis.
	 *
	 * Die Methode berücksichtigt den Frühbucherrabatt ausgehend vom aktuellen Datum.
	 *
	 * Der zurückgegebene Preis beinhaltet die Umsatzsteuer, Zusatzkosten werden nicht beachtet da diese nicht vom
	 * Kurs alleine sondern der gesamten Buchung abhängen.
	 *
	 * @return float
	 */
	/*public function getCoursePrice() {

		$oInquiry = $this->getInquiry();
		$oCurrency = Ext_Thebing_Currency::getInstance($oInquiry->currency_id);
		$oJourney = $oInquiry->getJourney();
		$oSchool = Ext_Thebing_School::createSchoolObjectFromArgument($oJourney->school_id);

		$dFrom = \DateTime::createFromFormat('Y-m-d', $this->from);
		$dUntil = \DateTime::createFromFormat('Y-m-d', $this->until);

		$oAmount = new Ext_Thebing_Course_Amount();
		$oAmount->setCourse($this->course_id);
		$oAmount->setUnits($this->units);
		$oAmount->setWeeks($this->weeks);
		$oAmount->setSchoolObject($oSchool);
		$oAmount->setCalculateTime($dFrom->getTimestamp(), $dUntil->getTimestamp());
		$oAmount->setDiscountTime(time()); // Für Frühbucherrabatt
		$oAmount->setCurrencyObject($oCurrency);
		$fPrice = $oAmount->calculate();

		if($oSchool->getTaxStatus() == Ext_Thebing_School::TAX_EXCLUSIVE) {

			$iTaxCategory = Ext_TS_Vat::getDefaultCombination('Ext_Thebing_Tuition_Course', $this->course_id, $oSchool);
			$iTaxRate = 0;
			if($iTaxCategory > 0) {
				$iTaxRate = Ext_TS_Vat::getTaxRate($iTaxCategory, $oSchool->id);
			}

			$aTax = Ext_TS_Vat::calculateExclusiveTaxes($fPrice, $iTaxRate);
			$fPrice += $aTax['amount'];

		}

		return (float)$fPrice;

	}*/

	/**
	 * Alle Wochen zurückliefern, welche im absoluten Leistungszeitraum der Kursstruktur dieser Kursbuchung vorkommen (inkl. Ferien)
	 *
	 * @return DateTime[]
	 */
	public function getCourseStructureWeeks() {

		$aRelatedJourneyCourses = $this->getRelatedServices();

		// Alle Kurse durchlaufen und Wochen ermitteln
		$aWeeks = $aDates = [];
		foreach($aRelatedJourneyCourses as $oRelatedJourneyCourse) {
			$dFrom = Ext_Thebing_Util::getWeekFromCourseStartDate(new DateTime($oRelatedJourneyCourse->from));
			$dUntil = new DateTime($oRelatedJourneyCourse->until);

			/**
			 * Closure für unten
			 * @param DateTime $dDate
			 */
			$oAddToArrays = function(DateTime $dDate) use($oRelatedJourneyCourse, &$aDates, &$aWeeks) {
				$aDates[] = $dDate;
				// Gehen wir mal davon aus, dass sich Kursbuchungen in einer Kursstruktur niemals überlappen
				$aWeeks[$dDate->format('Y-W')] = [
					'type' => 'service',
					'date' => $dDate,
					'journey_course' => $oRelatedJourneyCourse
				];
			};

			// Wochen des Kurses durchiterieren und Arrays aufbauen
			$oWeekIterator = new DatePeriod($dFrom, new DateInterval('P1W'), $dUntil);
			$bDatePeriodUsed = false;
			foreach($oWeekIterator as $dDate) {
				$oAddToArrays($dDate);
				$bDatePeriodUsed = true;
			}

			// Glückwunsch an Preston, die Kurse mit einem Zeitraum von einem Tag anlegen und damit selbst DatePeriod ($oWeekIterator) sprengen #10006
			if(!$bDatePeriodUsed) {
				$oAddToArrays($dFrom);
			}
		}

		// Von min() bis max() durchlaufen und Wochen ohne Kurse DIESER Kursstruktur ermitteln, das sind dann Ferien
		$oWeekIterator = new DatePeriod(min($aDates), new DateInterval('P1W'), max($aDates));
		foreach($oWeekIterator as $dDate) {
			if(!in_array($dDate, $aDates)) {
				$aWeeks[$dDate->format('Y-W')] = [
					'type' => 'holiday',
					'date' => $dDate
				];
			}
		}

		uasort($aWeeks, function($aWeek1, $aWeek2) {
			return $aWeek1['date'] > $aWeek2['date'];
		});

		return $aWeeks;

	}

	public function getRegistrationFormData(): array {

		$from = \Ext_Thebing_Util::convertDateStringToDateOrNull($this->from);

		// Felder sperren: disabled oder hidden
		$fields = [
			'course' => $this->transients['field_state_course'] ?? null,
			'language' => $this->transients['field_state_language'] ?? null,
			'start' => $this->transients['field_state_start'] ?? null,
			'duration' => $this->transients['field_state_duration'] ?? null,
			'holiday_split' => !empty($this->transients['field_state_holiday_split'])
		];

		if (!empty($this->transients['field_state_holiday_split'])) {
			$fields['course'] = 'disabled';
			$fields['start'] = 'disabled';
			$fields['duration'] = 'disabled';
		}

		return [
			'course' => !empty($this->course_id) ? (string)$this->course_id : null,
			'level' => !empty($this->level_id) ? (int)$this->level_id : null,
			'start' => $from !== null ? 'date:'.$from->toDateString() : null,
			'duration' => !empty($this->weeks) ? (int)$this->weeks : null,
			'units' => !empty((float)$this->units) ? (float)$this->units : null,
			'program' => !empty($this->program_id) ? (int)$this->program_id : null,
			'language' => !empty($this->courselanguage_id) ? (int)$this->courselanguage_id : null,
			'additional_services' => array_values(array_map(function (Ext_Thebing_School_Additionalcost $oFee) {
				// getRegistrationFormData-Aufruf nicht möglich, da falsches Objekt
				return ['fee' => $oFee->id];
			}, $this->getJoinTableObjects('additionalservices'))),
			'field_state' => $fields
		];

	}
	
	/**
	 * Liefert alle Unterkunftskosten die automatisch berechnet werden.
	 *
	 * @return Ext_Thebing_School_Additionalcost[]
	 */
	public function getAdditionalCosts() {
		
		$course = $this->getCourse();
		$additionalCosts = $course->getAdditionalCosts();

		$this->checkAdditionalServicesValidity($additionalCosts);
		
		// Manuell gebuchte Zusatzleistungen
		$semiAutomaticCosts = $this->getJoinTableObjects('additionalservices');
		if(!empty($semiAutomaticCosts)) {
			$additionalCosts = array_merge($additionalCosts, $semiAutomaticCosts);
		}

		return $additionalCosts;
	}

	public function getCourseLanguage() {
		
		$courseLanguage = $this->getJoinedObject('course_language');
		
		if(empty($courseLanguage)) {
			$oCourse = $this->getCourse();
			$courseLanguage = $oCourse->getLevelgroup();
		}

		return $courseLanguage;
	}
	
	public function getCourseLanguageName($sIso) {
		
		$courseLanguage = $this->getJoinedObject('course_language');
		
		if($courseLanguage) {
			return $courseLanguage->getName($sIso);
		}
		
	}

	public function getTuitionStartLevel(): string {

		// Achtung: Bei Kombinationskursen und Programmen soll hier immer der erste Kurs genommen werden
		$iProgramServiceId = $this->getProgram()?->getFirstService(\TsTuition\Entity\Course\Program\Service::TYPE_COURSE)?->id;

		$sLevel = (string)Ext_Thebing_Tuition_Progress::getStartLevel($this->id, $iProgramServiceId);

		return $sLevel;
	}

	public function getTuitionRemainingLessons(\TsTuition\Entity\Course\Program\Service $service, \Carbon\Carbon $week)
	{
		$inquiry = $this->getJourney()->getInquiry();

		$search = new Ext_Thebing_School_Tuition_Allocation_Result();
		$search->setInquiry($inquiry);
		$search->setInquiryCourse($this);
		$search->setProgramService($service);
		$search->setWeek($week);
		$search->setBlockWeekSortDesc(true);
		$result = $search->fetch();

		$lessonContingent = $this->getLessonsContingent($service);
		$courseLessons = $lessonContingent->lessons;

		$remaining = [
			'course_lessons' => $courseLessons,
			'allocated_lessons' => 0,
			'remaining_lessons' => $courseLessons
		];

		foreach ($result as $row) {
			
			if (
				LessonsUnit::from($lessonContingent->lessons_unit)->isPerWeek() &&
				$row['block_week'] !== $week->toDateString()
			) {
				// Bei Lektionen pro Woche interessiert nur, wie viele Lektionen in der angegebenen
				// Woche noch zur Verfügung stehen.
				continue;
			}
			
			if(
				$row['per_unit'] == 0 || 
				$row['units'] == 0
			) {
				$remaining['allocated_lessons'] += $row['allocated_lessons'];
				$remaining['remaining_lessons'] -= $row['allocated_lessons'];
			} else {
				$remaining['allocated_lessons']	= $row['allocated_units'];
				$remaining['remaining_lessons'] = $row['course_lessons'] - $row['allocated_units'];
			}
			
		}

		return $remaining;
	}

	public function getLessonsContingent(\TsTuition\Entity\Course\Program\Service $programService): \Ts\Entity\Inquiry\Journey\Course\LessonsContingent
	{
		$contingent = $this->getJoinedObjectChildByValue('lessons_contingents', 'program_service_id', $programService->id);

		if (!$contingent) {
			/** @var \Ts\Entity\Inquiry\Journey\Course\LessonsContingent $contingent */
			$contingent = $this->getJoinedObjectChild('lessons_contingents');
			$contingent->program_service_id = $programService->id;
		}

		return $contingent;
	}

	public function getPlacementtestLink() {
		return '[PLACEMENTTEST:'.$this->courselanguage_id.':'.$this->getJourney()->getInquiry()->id.']';
	}

	public function getHalloAiPlacementtestLink() {
		return '[PLACEMENTTESTHALLOAI:'.$this->courselanguage_id.':'.$this->getJourney()->getInquiry()->id.']';
	}

	public function getFirstTuitionAllocation():array {

		$sqlQuery = "
			SELECT
				`ktbd`.`day` `weekday`,
				`ktt`.`from`,
				`ktt`.`until`
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`id` = `ktbic`.`block_id` AND
					`ktb`.`active` = 1 INNER JOIN
				`kolumbus_tuition_templates` `ktt` ON
					`ktt`.`id` = `ktb`.`template_id` AND
					`ktt`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
					 `ktb`.`id` = `ktbd`.`block_id`
			 WHERE
				`ktbic`.`inquiry_course_id` = :id AND
				`ktbic`.`active` = 1
			ORDER BY
				`ktb`.`week`,
				`ktbd`.`day`
			LIMIT 1
				";
		$sqlParams = [
			'id' => $this->id
		];
		$firstAllocation = \DB::getQueryRow($sqlQuery, $sqlParams);

		return $firstAllocation;
	}

}
