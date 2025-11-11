<?php

use Ts\Dto\CourseStartDate;
use TsTuition\Generator\StartDatesGenerator;
use TsTuition\Entity\Course\Program;
use Illuminate\Support\Collection;

/**
 * @property string|int $id
 * @property string|int $active
 * @property string $valid_until DATE
 * @property string|int $creator_id
 * @property string|int $user_id
 * @property string|int $idClient
 * @property string $changed
 * @property string $created
 * @property string|int $position
 * @property string|int $category_id
 * @property int $different_price_per_language
 * @property string|int $superordinate_course_id
 * @property string|null $lessons_unit
 * @property string|float $lesson_duration
 * @property float[] $lessons_list
 * @property string|int $lessons_fix
 * @property string|int $school_id
 * @property string|int $online
 * @property string $name_short
 * @property string $name_en
 * @property string $description_en
 * @property string|int $publicholiday
 * @property string|int $schoolholiday
 * @property string|int $per_unit Kursstyp – TYPE_-Konstanten
 * @property string $price_calculation
 * @property string|int $avaibility Wirklich falsch geschrieben, AVAILABILITY_-Konstanten
 * @property $minimum_duration
 * @property $maximum_duration
 * @property $fix_duration
 * @property string|int $start_level_id
 * @property string|int $numerrange_id
 * @property string|int $only_for_combination_courses
 * @property string|int skip_ongoing_price_calculation
 * @property string|int $minimum_age
 * @property string|int $average_age
 * @property string|int $maximum_age
 * @property string|int $minimum_students
 * @property string|int $average_students
 * @property string|int $maximum_students
 * @property string $frontend_name_en
 * @property string $uk_quarterly_course_type
 * @property int|null $frontend_min_bookable_days_ahead
 * @property int|string $catch_up_on_cancelled_lessons
 * @property int|string $automatic_renewal
 * @property int|string $automatic_renewal_weeks_before
 * @property string $automatic_renewal_duration_type
 * @property int|string $automatic_renewal_duration_weeks
 * @property int $allow_parallel_tuition_allocations
 * @property array $course_languages
 * @method static \Ext_Thebing_Tuition_CourseRepository getRepository()
 */
class Ext_Thebing_Tuition_Course extends Ext_Thebing_Basic implements \Ts\Interfaces\Entity\DocumentRelation {

	use \Tc\Traits\Placeholder, \Ts\Traits\Entity\HasDocuments;
	
	/**
	 * Verfügbarkeit > keine Angabe
	 *
	 * @see Ext_Thebing_Tuition_Course::$avaibility
	 * @var integer
	 */
	const AVAILABILITY_UNDEFINED = 0;

	/**
	 * Verfügbarkeit > Immer verfügbar (Starttag der Schule)
	 *
	 * @see Ext_Thebing_Tuition_Course::$avaibility
	 * @var integer
	 */
	const AVAILABILITY_ALWAYS = 1;

	/**
	 * Verfügbarkeit > Nicht verfügbar
	 *
	 * @see Ext_Thebing_Tuition_Course::$avaibility
	 * @var integer
	 */
	const AVAILABILITY_NEVER = 2;

	/**
	 * Verfügbarkeit > Immer verfügbar (jeden Tag)
	 *
	 * @see Ext_Thebing_Tuition_Course::$avaibility
	 * @var integer
	 */
	const AVAILABILITY_ALWAYS_EACH_DAY = 3;

	/**
	 * Verfügbarkeit > Feste Starttermine
	 *
	 * @see Ext_Thebing_Tuition_Course::$avaibility
	 * @var integer
	 */
	const AVAILABILITY_STARTDATES = 4;

	const TYPE_PER_WEEK = 0;

	const TYPE_PER_UNIT = 1;

	const TYPE_EXAMINATION = 2;

	const TYPE_COMBINATION = 3;

	const TYPE_EMPLOYMENT = 4;

	const TYPE_PROGRAM = 5;

	// Cache Key für max_students von allen Kursen
	const ALL_MAX_STUDENTS_CACHE_KEY = 'Ext_Thebing_Tuition_Course_All_Max_Students';

	static protected $aStudentsMax = null;

	// Tabellennname Kurs
	protected $_sTable = 'kolumbus_tuition_courses';
	
	// Alias Kurs
	protected $_sTableAlias = 'ktc';
	
	// Flag wird in der Data-Ableitung gesetzt um festzustellen ob das Speichern über die Sortierung stattfindet
	public $bSaveBySort = false;

	protected $bCombinationCoursesRemoved = false;

	protected $_sPlaceholderClass = 'Ext_Thebing_Tuition_Course_Placeholder';

	protected $_aAttributes = [
		'canvas_id' => [
			'type' => 'string'
		],
		'show_prices_per_payment_conditon_select' => [
			'type' => 'int'
		],
		'prices_per_payment_condition' => [
			'type' => 'array'
		],
		'contact_persons' => [
			'type' => 'array'
		],
		'automatic_renewal_weeks_before' => [
			'type' => 'int'
		],
		'automatic_renewal_duration_type' => [
			'type' => 'string'
		],
		'automatic_renewal_duration_weeks' => [
			'type' => 'int'
		],
		'schoolholiday_scheduling' => [
			'type' => 'int'
		]
	];
	
    protected $_aFormat = array(
		'category_id' => [
			'required' => true,
			'validate' => 'INT_POSITIVE'
		],
		'name_short' => array(
			'required' => true
		),
		'maximum_students' => array(
			'validate'=>'INT_NOTNEGATIVE'
		),
		'lesson_duration' => array(
			'validate'=>'FLOAT_NOTNEGATIVE'
		),
		'average_students'=>array(
			'validate'=>'INT_NOTNEGATIVE'
		),
		'minimum_students'=>array(
			'validate'=>'INT_NOTNEGATIVE'
		),
		'minimum_age'=>array(
			'validate'=>'INT_NOTNEGATIVE'
		),
		'average_age'=>array(
			'validate'=>'INT_NOTNEGATIVE'
		),
		'maximum_age'=>array(
			'validate'=>'INT_NOTNEGATIVE'
		),
		'start_level_id'=>array(
			'validate'=>'INT_NOTNEGATIVE'
		),
		'avaibility'=>array(
			//'validate'=>'INT_POSITIVE'
		),
		'minimum_duration'=>array(
			'validate'=>'INT_NOTNEGATIVE'
		),
		'maximum_duration'=>array(
			'validate'=>'INT_NOTNEGATIVE'
		),
		'fix_duration'=>array(
			'validate'=>'INT_NOTNEGATIVE'
		),
		'frontend_min_bookable_days_ahead' => [
			'validate' => 'INT_NOTNEGATIVE'
		],
		'automatic_renewal_weeks_before' => [
			'validate' => 'INT_NOTNEGATIVE'
		],
		'automatic_renewal_duration_weeks' => [
			'validate' => 'INT_NOTNEGATIVE'
		]
	);

	protected $_aJoinTables = array(
		/*'combined_courses' => array(
			'table' => 'ts_tuition_courses_to_courses',
			'class' => 'Ext_Thebing_Tuition_Course',
			'foreign_key_field' => 'course_id',
			'primary_key_field' => 'master_id',
			'static_key_fields'	=> ['type' => 'combination'],
			'delete_check' => true,
			'autoload' => false,
		),
		'combined_courses_parents' => [
			'table' => 'ts_tuition_courses_to_courses',
			'foreign_key_field' => 'master_id',
			'primary_key_field' => 'course_id',
			'static_key_fields'	=> ['type' => 'combination'],
			'autoload' => false,
			'readonly' => true
		],*/
		'preparation_courses' => [
			'table' => 'ts_tuition_courses_to_courses',
			'class' => 'Ext_Thebing_Tuition_Course',
			'foreign_key_field' => 'course_id',
			'primary_key_field' => 'master_id',
			'static_key_fields'	=> ['type' => 'preparation'],
			'delete_check' => true,
			'autoload' => false
		],
		'preparation_courses_parents' => [
			'table' => 'ts_tuition_courses_to_courses',
			'class' => 'Ext_Thebing_Tuition_Course',
			'foreign_key_field' => 'master_id',
			'primary_key_field' => 'course_id',
			'static_key_fields'	=> ['type' => 'preparation'],
			'autoload' => false,
			'readonly' => true
		],
		'course_costs' => array(
			'table' => 'kolumbus_costs_courses',
			'foreign_key_field' => '',
			'primary_key_field' => 'customer_db_3_id',
			//'delete_check' => true,
			'autoload' => false,
		),
		'classes_courses'=>array(
			'table' => 'kolumbus_tuition_classes_courses',
			'primary_key_field' => 'course_id',
			'autoload' => false,
			'delete_check' => true,
		),
		'weeks' => array(
			'table' => 'kolumbus_tuition_courses_to_weeks',
			'primary_key_field' => 'course_id',
			'foreign_key_field' => 'week_id',
			'autoload' => false,
		),
		'units' => array(
			'table' => 'kolumbus_tuition_courses_to_units',
			'primary_key_field' => 'course_id',
			'foreign_key_field' => 'unit_id',
			'autoload' => false,
		),
		'accommodation_combinations' => array(
			'table' => 'kolumbus_tuition_courses_accommodation_combinations',
			'primary_key_field' => 'course_id',
			'foreign_key_field' => [
				'accommodation_category_id',
				'roomtype_id',
				'meal_id'
			],
			'autoload' => false,
		),
		'pdf_templates' => [
			'table' => 'kolumbus_pdf_templates_services',
			'class' => 'Ext_Thebing_Pdf_Template',
			'primary_key_field' => 'service_id',
			'foreign_key_field' => 'template_id',
			'static_key_fields'	=> ['service_type' => 'course'],
			'autoload' => false
		],
		'course_languages' => [
			'table' => 'ts_tuition_courses_to_courselanguages',
			'class' => 'Ext_Thebing_Tuition_LevelGroup',
			'primary_key_field' => 'course_id',
			'foreign_key_field' => 'courselanguage_id',
			'autoload' => false
		],

	);

	protected $_aJoinedObjects = array(
		'start_dates' => array(
			'class' => 'Ext_Thebing_Tuition_Course_Startdate',
			'key' => 'course_id',
			'type' => 'child',
			'check_active' => true,
			'orderby' => 'start_date',
			'orderby_type' => 'ASC',
			'on_delete' => 'cascade'
		),
		'programs' => array(
			'class' => \TsTuition\Entity\Course\Program::class,
			'key' => 'course_id',
			'type' => 'child',
			'check_active' => true,
			'on_delete' => 'cascade',
			'cloneable' => false // Werden ohnehin neu geschrieben und bei Kombinationskursen aus dem Dialog wieder gesetzt
		),
		'superordinate_course' => [
			'class' => \TsTuition\Entity\SuperordinateCourse::class,
			'key' => 'superordinate_course_id',
			'type' => 'parent',
			'check_active' => true,
			'bidirectional' => true
		]
	);

	protected $_aFlexibleFieldsConfig = [
		'tuition_courses_general' => []
	];

    /**
     * Online-Kurs? Also Online 1 oder Hybrid 2
     *
     * @return bool
     */
    public function isOnline(): bool {
        return ($this->online == 1);
    }
	
    public function isHybrid(): bool {
        return ($this->online == 2);
    }

	public function canBeOnline(): bool {
        return ($this->online > 0);
    }
	
	public function isPerWeekCourse(): bool {
		return ((int)$this->per_unit === self::TYPE_PER_WEEK);
	}

	public function isPerUnitCourse(): bool {
		return ((int)$this->per_unit === self::TYPE_PER_UNIT);
	}

	public function isExaminationCourse(): bool {
		return ((int)$this->per_unit === self::TYPE_EXAMINATION);
	}

	public function isCombinationCourse(): bool {
		//return (bool)$this->combination;
    	return ((int)$this->per_unit === self::TYPE_COMBINATION);
	}

	public function isEmployment(): bool {
		return ((int)$this->per_unit === self::TYPE_EMPLOYMENT);
	}

	public function isProgram(): bool {
		return ((int)$this->per_unit === self::TYPE_PROGRAM);
	}

	public function calculateByUnit(): bool {
		return ($this->isPerUnitCourse() || $this->isExaminationCourse());		
	}

	/**
	 * Kursart mit eigenen Lektionen
	 *
	 * @return bool
	 */
	public function canHaveLessons(): bool
	{
		return in_array(
			$this->per_unit,
			[self::TYPE_PER_WEEK, self::TYPE_PER_UNIT, self::TYPE_EXAMINATION]
		);
	}

	public function getLessons(): ?\TsTuition\Dto\CourseLessons
	{
		
		if (
			!$this->exist() ||
			!$this->canHaveLessons()
		) {
			return null;
		}

		return new \TsTuition\Dto\CourseLessons(
			array_map('floatval', $this->lessons_list),
			\TsTuition\Enums\LessonsUnit::from($this->lessons_unit),
			(bool)$this->lessons_fix
		);
	}

	public function getChildLessons(): ?\TsTuition\Dto\CourseLessons
	{
		if (!$this->canHaveChildCourses()) {
			return null;
		}

		return new \TsTuition\Dto\CourseLessons(
			array_map('floatval', $this->lessons_list),
			\TsTuition\Enums\LessonsUnit::from($this->lessons_unit),
			(bool)$this->lessons_fix
		);
	}

	/**
	 * Kursarten die Unterkurse haben
	 *
	 * @return bool
	 */
	public function canHaveChildCourses(): bool {
    	return ($this->isCombinationCourse() || $this->isProgram());
	}

	public function allowsParallelTuitionAllocations(): bool
	{
		if (!$this->isExaminationCourse()) {
			return false;
		}

		return (bool)$this->allow_parallel_tuition_allocations;
	}

	/**
	 * @return Collection
	 */
	public function getPrograms(): Collection {
    	return collect($this->getJoinedObjectChilds('programs', true))
			->sortBy(fn(Program $program) => $program->getFrom() ?: $program->id);
	}

	public function getFirstProgram(): ?\TsTuition\Entity\Course\Program {
		$programs = $this->getPrograms();
		if ($programs->count() > 1) {
			throw new \RuntimeException('There is more than one program for getFirstProgram (course: '.$this->id.')');
		}
		return $programs->first();
	}

	public function getField($sField) {
		return $this->$sField;
	}	

	public function getName($sLang = '') {

		// TODO is_bool() eingebaut für unterschiedliche Signatur bei \Ext_TS_Enquiry_Combination_Course::getCourseName()
		if(empty($sLang) || is_bool($sLang)) {
			$iSchoolId = (int)$this->getSchoolId();
			if($iSchoolId > 0) {
				$oSchool = Ext_Thebing_School::getInstance((int)$this->getSchoolId());
			} else {
				$oSchool = Ext_Thebing_Client::getFirstSchool();
			}
			if($oSchool) {
				$sLang = $oSchool->getInterfaceLanguage();
			} else {
				$sLang = 'en';
			}
		}

		$sName = $this->_aData['name_'.$sLang];
		return $sName;

	}

	public function getFrontendName($language) {

		$sName = $this->_aData['frontend_name_'.$language] ?? null;

		return $sName;
	}

	public static function getByShortcut($sShortcut, $iClientId, $iSchoolId) {

		$sSql = "
			SELECT
				`id`
			FROM
				#table
			WHERE
				`name_short` = :shortcut AND
				#school_id_field = :school_id AND
				`active` = 1
			LIMIT 1
		";

		$oSelf = new self();
		$sSchoolIdField = $oSelf->_checkSchoolIdField();
		$sTable = $oSelf->getTableName();

		$aSql = array(
			'shortcut' => $sShortcut,
			'school_id' => $iSchoolId,
			'school_id_field' => $sSchoolIdField,
			'table' => $sTable,
		);

		$iCourseId = DB::getQueryOne($sSql, $aSql);

		if(!$iCourseId) {
			return false;
		}

		$oCourse = self::getInstance($iCourseId);

		return $oCourse;

	}

	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		$sTable = '`'.$this->_sTable.'`';
		$sTableAlias = '`'.$this->_sTableAlias.'`';

		$aSqlParts['select'] .= "
			, `ktcc`.`name_".\Ext_Thebing_School::fetchInterfaceLanguage()."` `category_name`
			, GROUP_CONCAT(`ts_tctc`.`courselanguage_id`) `course_languages`
			, (
				IF(
					".$sTableAlias . ".`per_unit` IN (4,5),
					0,
					(
						SELECT 
							GROUP_CONCAT(CONCAT(`ktc_program`.`lessons_list`, '{|}', `ktc_program`.`lessons_unit`) SEPARATOR '{||}')
						FROM
							`ts_tuition_courses_programs` `ts_tcp`  INNER JOIN
							`ts_tuition_courses_programs_services` `ts_tcps` ON
								`ts_tcps`.`program_id` = `ts_tcp`.`id` AND
								`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
								`ts_tcps`.`active` = 1 INNER JOIN
							`kolumbus_tuition_courses` `ktc_program` ON
								`ktc_program`.`id` = `ts_tcps`.`type_id` AND
								`ktc_program`.`active` = 1
						WHERE 
							`ts_tcp`.`course_id` = ".$sTableAlias . ".`id` AND
							`ts_tcp`.`active` = 1
					)
				)		
			) `lessons`
		";

		$aSqlParts['from'] .= ' LEFT JOIN
				`ts_tuition_coursecategories` `ktcc` ON
					`ktcc`.`id` = '.$sTableAlias.'.`category_id` AND
					`ktcc`.`active` = 1 LEFT JOIN
				`ts_tuition_courses_to_courselanguages` `ts_tctc` ON
					`ts_tctc`.`course_id` = '.$sTableAlias.'.`id`
		';

		$aSqlParts['groupby'] = $sTableAlias . '.`id`';

	}

	/**
	 * Returns the values of _aData
	 *
	 * @param string : The name of a variable or a key
	 * @return mixed : Mixed value
	 */
	public function __get($sName) {

		// Wochen serialized abspeichern
		if(
			(
				$sName == 'weeks' &&
				!in_array((int)$this->_aData['per_unit'], [self::TYPE_PER_WEEK, self::TYPE_COMBINATION, self::TYPE_EMPLOYMENT, self::TYPE_PROGRAM])
			) || (
				$sName == 'units' &&
				$this->_aData['per_unit'] != 1
			)
		) {
			Ext_Gui2_Index_Registry::set($this);
			return array();
		}

		if($sName === 'accommodation_combinations_joined') {
			
			$mValue = (array)$this->accommodation_combinations;
			foreach($mValue as &$mCombination) {
				$mCombination = $mCombination['accommodation_category_id'].'_'.$mCombination['roomtype_id'].'_'.$mCombination['meal_id'];
			}

			return $mValue;

		} else if($sName === 'combined_courses') {

			if($this->isCombinationCourse()) {
				return collect($this->getChildCourses())
					->map(function(Ext_Thebing_Tuition_Course $course) {
						return $course->getId();
					})
					->values()
					->toArray();
			}

			return [];

		} else if ($sName === 'lessons_list') {
			return json_decode(parent::__get($sName), true) ?? [];
		}

		$mValue = parent::__get($sName);

		return $mValue;
	}

	public function __set($sName, $mValue) {
		
		if($sName === 'accommodation_combinations_joined') {

			$sName = 'accommodation_combinations';
			$mValue = (array)$mValue;
			foreach($mValue as &$mCombination) {
				$aCombination = explode('_', $mCombination);
				$mCombination = [
					'accommodation_category_id' => $aCombination[0],
					'roomtype_id' => $aCombination[1],
					'meal_id' => $aCombination[2]
				];
			}

		} else if($sName === 'combined_courses') {

			if($this->isCombinationCourse()) {

				$aOriginalCombinationCourses = $this->combined_courses;
				$this->bCombinationCoursesRemoved = false;

				if(
					$this->exist() &&
					!empty(array_diff((array)$aOriginalCombinationCourses, (array)$mValue))
				) {
					// Flag setzen um in der Validierung zu wissen das sich die Kurse verändert haben
					$this->bCombinationCoursesRemoved = true;
				}

				// Falls noch weitere Programme existieren werden diese in der save() gelöscht
				$oProgram = $this->getFirstProgram();
				if(is_null($oProgram)) {
					$oProgram = $this->getJoinedObjectChild('programs');
				}

				$aServices = $oProgram->getServices()->values()->toArray();

				foreach($mValue as $iIndex => $iCombinationCourse) {

					if(isset($aServices[$iIndex])) {
						$oService = $aServices[$iIndex];
						unset($aServices[$iIndex]);
					} else {
						$oService = $oProgram->getJoinedObjectChild('services');
					}

					$oService->type = \TsTuition\Entity\Course\Program\Service::TYPE_COURSE;
					$oService->type_id = $iCombinationCourse;
					$oService->from = null;
					$oService->until = null;
				}

				// Nicht mehr benötigte rauswerfen
				foreach($aServices as $oService) {
					$oProgram->removeJoinedObjectChildByKey('services', $oService->getId());
				}

			}

			return;
		} else if($sName === 'lessons_list') {
			if (!empty($mValue)) {
				$mValue = array_filter(array_map('floatval', $mValue));
			}
			$mValue = json_encode($mValue);
		}

		parent::__set($sName, $mValue);

	}
	
	/**
	 * Liefert mir alle Zusatzkosten die dem Kurs zugeordnet sind und automatisch berechnet werden.
	 *
	 * @return Ext_Thebing_School_Additionalcost[]
	 */
	public function getAdditionalCosts($bWithSemi = false) {

		$aReturn = array();

		foreach((array)$this->course_costs as $aData) {
			$oObject = Ext_Thebing_School_Additionalcost::getInstance($aData['kolumbus_costs_id']);
			if(
				$oObject->active == 1 &&
				$oObject->type == '0' &&
				($bWithSemi || $oObject->charge === 'auto')
			) {
				if (
						$oObject->valid_until == '0000-00-00' ||
						new DateTime($oObject->valid_until) >= new DateTime()
				) {
					$aReturn[] = $oObject;
				}
			}
		}

		return $aReturn;

	}

	/**
	 * @return Ext_Thebing_Tuition_Course_Category 
	 */
	public function getCategory(){
		return Ext_Thebing_Tuition_Course_Category::getInstance($this->category_id);
	}

	public function save($bLog = true) {

		$bNew = !$this->exist();

		if ($this->isPerUnitCourse() || $this->isExaminationCourse()) {
			// Feld wird im Dialog ausgeblendet und vorher wurde das über per_unit gesteuert
			// Der Typ wird aktuell nur in der Preisberechnung verwendet, da ansonsten überall Weichen mit per_unit == 1 eingebaut sind (ggf. nicht korrekt für Examen/Probeunterricht?)
			$this->price_calculation = 'unit';
		}

		if ($this->isPerWeekCourse() || $this->isExaminationCourse()) {
			$this->lessons_unit = \TsTuition\Enums\LessonsUnit::PER_WEEK->value;
		} else if ($this->isPerUnitCourse()) {
			if (count($this->lessons_list) > 1) {
				// Sobald es mehrere Anzahlen an Lektionen gibt, ist die Auswahl für Buchungen auf diese beschränkt
				$this->lessons_fix = 1;
			}
		} else {
			$this->lessons_unit = null;
		}

		if (!$this->isExaminationCourse()) {
			// Nur bei Prüfungen sind parallele Zuweisungen erlaubt
			$this->allow_parallel_tuition_allocations = 0;
		}

		// Verhalten vom Dialog: Ausgeblendete Felder leeren
		if (
			$this->isProgram() ||
			!in_array($this->avaibility, [self::AVAILABILITY_ALWAYS, self::AVAILABILITY_ALWAYS_EACH_DAY])
		) {
			$this->minimum_duration = null;
			$this->maximum_duration = null;
			$this->fix_duration = null;
		}

		// Wenn es ein neuer Kurs ist oder sich die Kursart geändert hat müssen die Programme neu generiert werden.
		if (
			$this->bSaveBySort === false && (
				$bNew ||
				$this->getOriginalData('per_unit') !== $this->per_unit
			)
		) {
			$this->rewritePrograms();
		}

		if ($this->show_prices_per_payment_conditon_select != 1) {
			$this->prices_per_payment_condition = null;
		}

		parent::save($bLog);

		if ($bNew && !$this->canHaveChildCourses()) {
			// Bei neuen Kursen muss die Kurs-Id nachträglich in die Programmleistung geschrieben werden
			$this->getFirstProgram()
				->getFirstService()
				->setService($this)
				->save();
		}

		$this->bCombinationCoursesRemoved = false;

		// Cache mit allen Kurse löschen
		WDCache::delete(self::ALL_MAX_STUDENTS_CACHE_KEY);

		System::wd()->executeHook('ts_course_save', $this);
		
		return $this;
	}

	protected function rewritePrograms() {

		if ($this->isProgram()) {
			// Für Programme gibt es eine eigene Pflege
			return;
		}

		// Für alle anderen Kurse müssen die Programme im Hintergrund geschrieben werden

		$oProgramCollection = $this->getPrograms();

		// Alle anderen Kurse haben nur ein Programm
		if($oProgramCollection->isNotEmpty()) {
			$oNeededProgram = $oProgramCollection->shift();
		} else {
			$oNeededProgram = $this->getJoinedObjectChild('programs');
		}

		// Alle Kurse die nicht vom Typ "Kombinationskurs" sind haben nur eine Leistung im Programm
		if(!$this->isCombinationCourse()) {
			$oServiceCollection = $oNeededProgram->getServices();

			if($oServiceCollection->isNotEmpty()) {
				$oNeededService = $oServiceCollection->shift();
			} else {
				$oNeededService = $oNeededProgram->getJoinedObjectChild('services');
			}

			// Bei neuen Kursen ist die ID hier immer 0 da die Programme vor dem save() geschrieben werden, die ID wird
			// nach dem save() gesetzt
			$oNeededService->setService($this);
			$oNeededService->from = null;
			$oNeededService->until = null;

			// Alle anderen Leistungen löschen
			foreach($oServiceCollection as $oService) {
				$oNeededProgram->removeJoinedObjectChildByKey('services', $oService->getId());
			}
		}

		// Alle anderen Programme löschen
		foreach($oProgramCollection as $oProgram) {
			$this->removeJoinedObjectChildByKey('services', $oProgram->getId());
		}


	}

	protected function _clearRuntimes() {
		$sSql = "
			UPDATE
				`kolumbus_course_runtimes`
			SET
				`active` = 0
			WHERE
				`course_id` = :course_id
		";
		$aSql = array(
			'course_id' => $this->id
		);
		return DB::executePreparedQuery($sSql, $aSql);
	}

	protected function _clearStartDates() {
		$sSql = "
			UPDATE
				`kolumbus_course_startdates`
			SET
				`active` = 0
			WHERE
				`course_id` = :course_id
		";
		$aSql = array(
			'course_id' => $this->id
		);
		return DB::executePreparedQuery($sSql, $aSql);
	}

	public static function getLevelGroupedCourses() {

		// @TODO Das sieht falsch aus für All Schools
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId = (int)$oSchool->id;

		$aResult = (array)DB::getQueryPairs("
			SELECT
				`ktc`.`id`,
				`ktlg`.`id`
			FROM
				`kolumbus_tuition_courses` `ktc` JOIN
				`ts_tuition_courses_to_courselanguages` `ts_tctc` ON
					`ktc`.`id` = `ts_tctc`.`course_id` JOIN
				`ts_tuition_courselanguages` `ktlg` ON
					`ktlg`.`id` = `ts_tctc`.`courselanguage_id`
			WHERE
				`ktc`.`active` = 1 AND
				`ktlg`.`active` = 1 AND
				`ktc`.`school_id` = :school_id
		", ['school_id' => $iSchoolId]);

		return $aResult;

	}

	public function validate($bThrowExceptions = false) {

		$mReturn = parent::validate($bThrowExceptions);

		if($mReturn === true) {
			$mReturn = array();
		}

		$sTableAlias = $this->_sTableAlias;
		if(!empty($sTableAlias)) {
			$sTableAlias .= '.';
		}

		$sValidUntilKey = $sTableAlias.'valid_until';

		$aInquiryCourseAllocations = $this->getInquiryCourses($this->valid_until);
		if(!empty($aInquiryCourseAllocations)) {
			
			/*
			 * @todo Information über die Buchungen in Fehlermeldung anzeigen. 
			 * Bei inaktiven Kursen findet man das sonst so gut wie nicht raus.
			 */
			foreach($aInquiryCourseAllocations as $iJourneyCourseId) {
				#$oJourneyCourse = Ext_TS_Inquiry_Journey_Course::getInstance($iJourneyCourseId);
				#echo $oJourneyCourse->getCustomer()->getCustomerNumber()." ";
			}
			
			if(!isset($mReturn[$sValidUntilKey])) {
				$mReturn[$sValidUntilKey] = array();
			}
			$mReturn[$sValidUntilKey][] = 'JOURNEY_COURSES_FOUND';
		}

		$aClassesAllocations = $this->getClasses($this->valid_until);
		if(!empty($aClassesAllocations)) {
			
			/*
			 * @todo Information über die Klassen in Fehlermeldung anzeigen
			 */
			
			if(!isset($mReturn[$sValidUntilKey])) {
				$mReturn[$sValidUntilKey] = array();
			}
			$mReturn[$sValidUntilKey][] = 'CLASSES_FOUND';
		}

		if ($this->canHaveLessons()) {
			if (empty($this->lessons_list)) {
				$mReturn[$sTableAlias.'lessons_list'] = 'EMPTY';
			} else {
				$check = array_map(
					fn ($loop) => (new WDValidate())->value($loop)->on('FLOAT_NOTNEGATIVE')->execute(),
					$this->lessons_list
				);

				if (in_array(false, $check)) {
					$mReturn[$sTableAlias.'lessons_list'] = 'INVALID_FLOAT_NOTNEGATIVE';
				}
			}

			if (count($this->lessons_list) > 1 && ($this->isPerWeekCourse() || $this->isExaminationCourse())) {
				$mReturn[$sTableAlias.'lessons_list'] = 'TOO_MANY';
			}
		}

		// Die Zahl darf nicht 0 sein, da die Anwesenheit sonst nicht funktioniert (Division durch 0 usw.)
		if (
			!$this->isCombinationCourse() &&
			!$this->isProgram() &&
			!$this->isEmployment() &&
			empty((float)$this->lesson_duration)
		) {
			$mReturn[$sTableAlias.'lesson_duration'] = 'INVALID_FLOAT_NOTNEGATIVE';
		}

		if(!\Ext_Thebing_Util::canOverwriteCourseSettings()) {

			// Einstellungen für Kombinationskurs dürfen nicht mehr verändert werden (außer neue Kurse hinzufügen)
			// Da diverse Sachen (Blockzuweisungen, Anwesenheit, Progress, Transcripts) auf den Unterkursen basieren, würden Änderungen riesige Probleme verursachen
			if(
				$this->bCombinationCoursesRemoved ||
				(
					$this->getOriginalData('per_unit') == self::TYPE_COMBINATION &&
					$this->per_unit != self::TYPE_COMBINATION
				)
			) {
				if(!empty($this->getInquiryCourses())) {
					$mReturn['combined_courses'] = 'COMBINATION_COURSE_USED';
				}
			}

			$lessonsListOriginal = json_decode($this->getOriginalData('lessons_list'), true) ?? [];
			$lessonsDiff = [
				...array_diff($this->lessons_list, $lessonsListOriginal),
				...array_diff($lessonsListOriginal, $this->lessons_list),
			];

			// Darf nicht einfach mehr verändert werden, da Tuition-Index und Zuweisungen nicht aktualisiert werden #12330
			if(
				$this->exist() && (
					!empty($lessonsDiff) ||
					$this->lessons_unit != $this->getOriginalData('lessons_unit') ||
					$this->lesson_duration != $this->getOriginalData('lesson_duration')
				) &&
				static::getRepository()->hasTuitionAllocation($this) &&
				\System::d('debugmode') == 0 #20505
			) {
				$sKey = 'lessons_list';
				if($this->lesson_duration != $this->getOriginalData('lesson_duration')) {
					$sKey = 'lesson_duration';
				}

				$mReturn[$sTableAlias.$sKey] = 'TUITION_ALLOCATIONS_FOUND';
			}

		}

		// Klick auf All + verhindern
		if(count($this->pdf_templates) > System::d('ts_max_attached_additional_docments', Ext_Thebing_Document::MAX_ATTACHED_ADDITIONAL_DOCUMENTS)) {
			$mReturn = ['pdf_templates' => 'TOO_MANY'];
		}

		// Wenn der Kombinationskurse bereits verwendet wurde, sorgt das Löschen von Unterkursen für Bugs (#15639)
		if (
			!$this->active &&
			!empty($this->getParentCourses())
		) {
			$mReturn[] = 'ALLOCATED_TO_PARENT_COURSE';
		}

		if ($this->automatic_renewal_weeks_before > \TsTuition\Service\CourseRenewalService::MAX_WEEKS) {
			$mReturn = ['automatic_renewal_weeks_before' => 'TO_HIGH'];
		}

		foreach ($this->_aData as $sField => $mValue) {
			// Keine Romane in Tooltips zulassen (werden auf Smartphones abgeschnitten)
			if (
				strpos($sField, 'description_') !== false &&
				mb_strlen(strip_tags($mValue)) > 255
			) {
				$mReturn[$sTableAlias.$sField] = 'TO_LONG';
			}
		}

		if(empty($mReturn)) {
			$mReturn = true;
		}

		return $mReturn;

	}

	/**
	 * Buchungen die diesen Kurs gebucht haben
	 *
	 * @param bool|string $sUntilDate
	 * @return array
	 */
	public function getInquiryCourses($sUntilDate = false) {

		if(
			$sUntilDate !== false &&
			(
				$sUntilDate == '0000-00-00' ||
				!WDDate::isDate($sUntilDate, WDDate::DB_DATE)
			)
		) {
			return array();
		}

		$sWhere = '';

		if($sUntilDate) {
			$sWhere .= ' AND :until < `ts_ijc`.`until`';
		}

		$sSql = "
			SELECT
				`ts_ijc`.`id`
			FROM
				`ts_inquiries_journeys_courses` `ts_ijc` INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`id` = `ts_ijc`.`journey_id` AND
					`ts_ij`.`active` = 1 INNER JOIN
				`ts_inquiries` `ts_i` ON
					`ts_i`.`id` = `ts_ij`.`inquiry_id` AND
					`ts_i`.`active` = 1
			WHERE
				`ts_ijc`.`active` = 1 AND
				`ts_ijc`.`course_id` = :self_id
				".$sWhere."
		";

		$aResult = (array)DB::getQueryCol($sSql, array(
			'self_id' => (int)$this->id,
			'until' => $sUntilDate,
		));

		return $aResult;

	}

	/**
	 * Alle Klassen zu einem Kurs
	 *
	 * @param string|bool $sUntilDate
	 * @return array
	 */
	public function getClasses($sUntilDate = false) {

		if(
			$sUntilDate !== false &&
			(
				$sUntilDate == '0000-00-00' ||
				!WDDate::isDate($sUntilDate, WDDate::DB_DATE)
			)
		) {
			return array();
		}

		$iSelfId = (int)$this->id;
		$sFrom = '';
		$sWhere	= '';

		$aSql = array(
			'self_id' => $iSelfId,
			'until' => $sUntilDate,
		);

		if($sUntilDate) {
			$sFrom .= "
				INNER JOIN
					`customer_db_2` `cdb2`
				ON
					`cdb2`.`id` = `ktcl`.`school_id`
			";
			$sWhere .= " AND
				:until < getRealDateFromTuitionWeek(`ktb`.`week`, `ktbd`.`day`, `cdb2`.`course_startday`)
			";
		}

		$sSql = "
			SELECT
				`ktcl`.`id`
			FROM
				`kolumbus_tuition_classes` `ktcl`
			INNER JOIN
				`kolumbus_tuition_classes_courses` `ktclc`
			ON
				`ktclc`.`class_id` = `ktcl`.`id` AND
				`ktclc`.`course_id` = :self_id
			INNER JOIN
				`kolumbus_tuition_blocks` `ktb`
			ON
				`ktb`.`class_id` = `ktcl`.`id` AND
				`ktb`.`active` = 1
			INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd`
			ON
				`ktbd`.`block_id` = `ktb`.`id` AND
				`ktbd`.`day` = (
					SELECT
						MAX(`day`)
					FROM
						`kolumbus_tuition_blocks_days`
					WHERE
						`block_id` = `ktb`.`id`
				)
				".$sFrom."
			WHERE
				`ktcl`.`active` = 1
				".$sWhere."
			GROUP BY
				`ktcl`.`id`
		";

		$aResult = (array)DB::getQueryCol($sSql, $aSql);
		return $aResult;

	}

	/**
	 * Liefert die Levelgruppe dieses Kurses
	 *
	 * @return Ext_Thebing_Tuition_LevelGroup 
	 */
	public function getLevelgroup() {
		$courseLanguages = $this->getCourseLanguages();
		if(!empty($courseLanguages)) {
			return reset($courseLanguages);
		}
	}

	/**
	 * @return Ext_Thebing_Tuition_LevelGroup[]
	 */
	public function getCourseLanguages() {
		return $this->getJoinTableObjects('course_languages');
	}

	public function getType(): string {

		return match ((int)$this->per_unit) {
			self::TYPE_PER_UNIT => 'unit',
			self::TYPE_EXAMINATION => 'exam',
			self::TYPE_COMBINATION => 'combination',
			self::TYPE_EMPLOYMENT => 'employment',
			self::TYPE_PROGRAM => 'program',
			default => 'week',
		};

	}

	/**
	 * Kurskürzel
	 *
	 * @return string
	 */
	public function getShortName() {
		return $this->name_short;
	}

	/**
	 * Alle generierten Starttage dieses Kurses inkl. minimaler und maximaler Laufzeit
	 */
	public function getStartDatesWithDurations(\DateTime $dFrom, \DateTime $dUntil, bool $generateEndDates = false): array {

		$dFrom = \Carbon\Carbon::instance($dFrom);
		$dUntil = \Carbon\Carbon::instance($dUntil);
		
		$oGenerator = new StartDatesGenerator($this, $dFrom, $dUntil);
		$oGenerator->setGenerateEndDates($generateEndDates);

		return $oGenerator->generate();
	}

	/**
	 * Gibt das "gültig bis"-Datum des Kurses zurück oder null wenn das Datum nicht gesetzt ist.
	 *
	 * Uhrzeit des Datums wird auf 00:00:00 in der aktuellen Zeitzone gesetzt sein.
	 *
	 * @return null|DateTime
	 */
	public function getValidUntil() {

		if($this->valid_until == '0000-00-00') {
			return null;
		}

		$dValidUntil = new \Carbon\Carbon($this->valid_until);
		$dValidUntil->setTime(0, 0, 0);

		return $dValidUntil;
	}

	/**
	 * Gibt die eingestellten festen Starttermine des Kurses zurück.
	 *
	 * Wenn eine andere Verfügbarkeits-Einstellung eingestellt ist, wird ein leeres Array zurück gegeben.
	 *
	 * Die Einträge sind werden nach Startdatum aufsteigend sortiert zurück gegeben.
	 *
	 * @return Ext_Thebing_Tuition_Course_Startdate[]
	 * @see Ext_Thebing_Tuition_Course::AVAILABILITY_STARTDATES
	 */
	public function getConfiguredStartDates() {

		$aStartDates = array();

		if($this->avaibility != self::AVAILABILITY_STARTDATES) {
			return $aStartDates;
		}

		$aStartDates = Ext_Thebing_Tuition_Course_Startdate::query()
			->where('course_id', $this->id)
			->where('type', Ext_Thebing_Tuition_Course_Startdate::TYPE_START_DATE)
			->get();
		
		return $aStartDates;
	}

	public function getNotAvailableDates() {

		$aStartDates = Ext_Thebing_Tuition_Course_Startdate::query()
			->where('course_id', $this->id)
			->where('type', Ext_Thebing_Tuition_Course_Startdate::TYPE_NOT_AVAILABLE)
			->get();
		
		return $aStartDates;
	}

	/**
	 * Zugewiesene Unterkurse zurückliefern (nur relevant bei Kombinationskursen)
	 *
	 * @return static[]
	 */
	public function getChildCourses() {

		if(
			!$this->isCombinationCourse() &&
			!$this->isProgram()
		) {
			return [];
		}

		$aPrograms = $this->getPrograms();

		$aCourses = [];
		foreach($aPrograms as $oProgram) {
			$aCourses = array_merge($aCourses, $oProgram->getCourses()->toArray());
		}

		return $aCourses;
	}

	public function getParentCourses() {

		// Kombinationskurse und Programme können nicht als Childs vorkommen
		if($this->canHaveChildCourses()) {
			return [];
		}

		$sSql = "
			SELECT
				DISTINCT `ktc`.`id`
			FROM
				`ts_tuition_courses_programs_services` `ts_tcps` INNER JOIN
				`ts_tuition_courses_programs` `ts_tcp` ON
					`ts_tcp`.`id` = `ts_tcps`.`program_id` AND 
					`ts_tcp`.`active` = 1 INNER JOIN
				`kolumbus_tuition_courses` `ktc` ON
					`ktc`.`id` = `ts_tcp`.`course_id` AND 
					`ktc`.`id` != :id AND 
					`ktc`.`active` = 1
			WHERE
				`ts_tcps`.`type` = :type AND 
				`ts_tcps`.`type_id` = :id AND 
				`ts_tcps`.`active` = 1
		";

		$aParents = (array)\DB::getQueryCol($sSql, ['type' => \TsTuition\Entity\Course\Program\Service::TYPE_COURSE, 'id' => $this->getId()]);

		return $aParents;
	}

	/**
	 * Wenn Kombinationskurs: Unterkurse zurückliefern, ansonsten eigenen Kurs (if/else sparen)
	 *
	 * @return static[]
	 */
	public function getChildCoursesOrSameCourse(\TsTuition\Entity\Course\Program $oProgram = null) {

		// Zeit sparen. Man könnte hier auch immer über die Programme gehen
		if(!$this->canHaveChildCourses()) {
			return [$this];
		}

		if(!is_null($oProgram)) {
			$aPrograms = [$oProgram];
		} else {
			$aPrograms = $this->getPrograms()->toArray();
		}

		$aCourses = [];
		foreach($aPrograms as $oProgram) {
			$aCourses = array_merge($aCourses, $oProgram->getCourses()->toArray());
		}

		return $aCourses;
	}

	public static function getMaxUnits(): int {
		return (int)System::d('school_tuition_max_units', 500);
	}

	public static function getExamFakeUnitResource() {

		$oUnit = new Ext_Thebing_School_TeachingUnit();
		$oUnit->title = '1';
		$oUnit->start_unit = 1;
		$oUnit->unit_count = 1;

		return [$oUnit->getData()];

	}

	/**
	 * @todo Ist nur eine Zwischenlösung wegen der Kurssprachenumstellung. Muss kurzfristig raus!
	 * @deprecated 
	 * @return int
	 */
	public function getFirstCourselanguageId() {
		$courseLanguageIds = $this->course_languages;
		if(!empty($courseLanguageIds)) {
			return reset($courseLanguageIds);
		}
	}

	public function canHaveStartDates(): bool {

		if(
			$this->avaibility == self::AVAILABILITY_STARTDATES ||
			$this->per_unit  == self::TYPE_PROGRAM
		) {
			return true;
		}

		return false;
	}

	public function getCurrency() {
		
	}

	public function getDocumentLanguage() {

	}

	public function getTypeForNumberrange($sDocumentType, $mTemplateType = null) {
		return 'additional_document';
	}

	public function getSchool() {
		return Ext_Thebing_School::getInstance($this->school_id);
	}
	
	public function hasFixedPrice() {
		if($this->price_calculation === 'fixed') {
			return true;
		}
		return false;
	}
	
}
