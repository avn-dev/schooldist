<?php

/**
 * @todo alles was mit ktbic zu tun hat in Ext_Thebing_School_Tuition_Allocation verschieben
 * @todo substitute teachers in joinedtables umbauen
 */

use Carbon\Carbon;
use Spatie\Period;
use Core\Helper\DateTime;
use Illuminate\Support\Collection;
use Ts\Entity\Inquiry\Journey\Course\LessonsContingent;

/**
 * @property int id
 * @property string changed
 * @property string created
 * @property int active
 * @property int creator_id
 * @property int school_id
 * @property int teacher_id
 * @property int level_id
 * @property int class_id
 * @property string week
 * @property int template_id
 * @property int parent_id
 * @property int readonly
 * @property int $state BIT
 * @property string $description
 * @property int[] $days
 * @property int[] $rooms
 * @method static \Ext_Thebing_School_Tuition_BlockRepository getRepository()
 */
class Ext_Thebing_School_Tuition_Block extends Ext_Thebing_Basic {
	use \Core\Traits\WdBasic\MetableTrait;

	/**
	 * Zugewiesener Lehrer abwesend
	 */
	const STATE_TEACHER_ABSENCE = 1;

	/**
	 * Zugewiesener Lehrer nicht verfügbar (eingestellte Zeiten)
	 */
	const STATE_INVALID_TEACHER_AVAILABILITY = 2;

	/**
	 * Zugewiesener Lehrer hat unpassende Qualifikation (Level oder Kurskategorie)
	 */
	const STATE_INVALID_TEACHER_QUALIFICATION = 4;

	protected $_sTable = 'kolumbus_tuition_blocks';

	protected $_sOldPlaceholderClass = Ext_Thebing_School_Tuition_Block_Placeholder::class;

	protected $_sPlaceholderClass = \TsTuition\Service\Placeholder\Block::class;

	protected $_aJoinTables = array(
		'days'=>array(
			'table'=>'kolumbus_tuition_blocks_days',
			'foreign_key_field'=>'day',
			'primary_key_field'=>'block_id',
			'on_delete' => 'no_action',
		),
        'rooms'=>array(
            'class' => \Ext_Thebing_Tuition_Classroom::class,
            'table'=>'kolumbus_tuition_blocks_to_rooms',
            'foreign_key_field'=>'room_id',
            'primary_key_field'=>'block_id',
            'on_delete' => 'no_action',
        )
	 );

	protected $_aJoinedObjects = array(
		'class'	=> array(
			'class'			=> 'Ext_Thebing_Tuition_Class',
			'key'			=> 'class_id',
		),
		'teacher'	=> array(
			'class'			=> 'Ext_Thebing_Teacher',
			'key'			=> 'teacher_id',
			'check_active'	=> true,
		),
		'template'	=> array(
			'class'			=> 'Ext_Thebing_Tuition_Template',
			'key'			=> 'template_id',
		),
		'allocations' => [
			'class' => 'Ext_Thebing_School_Tuition_Allocation',
			'key' => 'block_id',
			'type' => 'child'
		],
		'daily_units' => [
			'class' => \TsTuition\Entity\Block\Unit::class,
			'key' => 'block_id',
			'type' => 'child'
		]
	);

	/**
	 * @todo week darf nicht leer sein
	 * @var <type>
	 */
	protected $_aFormat = array();

	protected $_aStudents = array();
    protected $_aGetStudentsCache = array();

	protected $_aAdditionalData = array(
       'name'				=> '',
       'from'				=> null,
       'until'				=> null,
       'lessons'			=> 0,
       'classroom'			=> '',
       'classroom_max'		=> 0,
       'teacher_firstname'	=> '',
       'teacher_lastname'	=> '',
       'level'				=> 0,
       'level_short'		=> ''
	);

	/**
	 * Lektionsanzahl oder Tage geändert: lesson_duration der Zuweisung MUSS aktualisiert werden
	 *
	 * Einerseits steckt die Anzahl der Lektionen in einer anderen Entität, andererseits wird _aOriginalData
	 * in der updateAllocations() nochmal benötigt, allerdings wurde in der saveBlocksForWeek() bereits
	 * save() aufgerufen und daher sind die Originalwerte weg.
	 *
	 * @see \Ext_Thebing_School_Tuition_Allocation::$lesson_duration
	 * @see \Ext_Thebing_Tuition_Class::saveBlocksForWeek()
	 * @var bool
	 */
	public $bUpdateAllocations = false;

	/**
	 * Dieser Flag ist eine Untermenge von $bUpdateAllocations: Falls sich irgendwas an der Vorlage geändert hat,
	 * muss überprüft werden, ob bereits zugewiesene Schüler auch in der veränderten Zeit nicht irgendwie zugewiesen sind.
	 *
	 * Bsp.: Man könnte diese Variable mit $bUpdateAllocations ersetzen, aber dann werden die Allocations eben auch immer neu berechnet.
	 *
	 * @var bool
	 */
	public $bCheckAllocations = false;

	// TODO Dringend entfernen
	protected function _loadData($iDataID) {

		parent::_loadData($iDataID);

		if($iDataID > 0) {		
			$this->loadAdditionalData();
			$this->_getStudents();
		}

	}

	/**
	 * @TODO Entfernen
	 */
	public function loadAdditionalData() {
		
		// TODO Hier hat das Objekt eine Abhängigkeit auf die Session!
		$oSchool	= Ext_Thebing_School::getSchoolFromSession();
		$sLang		= $oSchool->getInterfaceLanguage();

		$sField		= 'name_' . $sLang;

		/**
		 * Achtung, hier gibt es einen Join auf "kolumbus_tuition_blocks_to_rooms" damit der Block pro Raum aufgelistet
		 * wird
		 */
		$sSql =  "
			SELECT
				UNIX_TIMESTAMP(
					`ktb`.`created`
				) `created`,
				`ktt`.`name`,
				`ktt`.`from`,
				`ktt`.`until`,
				`ktt`.`lessons`,
				`kc`.`name` `classroom`,
				`kc`.`max_students` `classroom_max`,
				`kt`.`firstname` `teacher_firstname`,
				`kt`.`lastname` `teacher_lastname`,
				`ktul`.`" . $sField . "` `level`,
				`ktul`.`name_short` `level_short`
			FROM
				`kolumbus_tuition_blocks` `ktb` LEFT JOIN
				`kolumbus_tuition_templates` `ktt` ON
					`ktb`.`template_id` = `ktt`.`id` LEFT JOIN
				`kolumbus_tuition_blocks_to_rooms` `ktbtr` ON
					`ktbtr`.`block_id` = `ktb`.`id` LEFT JOIN
				`kolumbus_classroom` `kc` ON
					`ktbtr`.`room_id` = `kc`.`id` LEFT JOIN
				`ts_teachers` `kt` ON
					`ktb`.`teacher_id` = `kt`.`id` LEFT JOIN
				`ts_tuition_levels` `ktul` ON
					`ktb`.`level_id` = `ktul`.`id`
			WHERE
				`ktb`.`id` = :id
			ORDER BY
				`ktt`.`from` ASC
		";

		$aSql = array(
			'id' => (int)$this->id
		);

		$result = DB::getQueryRow($sSql, $aSql);

		// Falls hier aus irgendeinem Grund kein Ergebnis kommt
		if(!empty($result)) {
			$this->_aAdditionalData = $result;
		}

	}

	public function  __set($sName, $mValue) {

        if($sName === 'room_id') {
            throw new LogicException('The key "room_id" is no longer available');
        }

		if(array_key_exists($sName, $this->_aData)){
			parent::__set($sName, $mValue);
		} elseif(array_key_exists($sName, (array)$this->_aJoinTables)) {
			parent::__set($sName, $mValue);
		} elseif(isset($this->_aAttributes[$sName])){
			parent::__set($sName, $mValue);
		} else {
			$this->_aAdditionalData[$sName] = $mValue;
		}
		
	}

	public function  __get($sName)
	{

	    if($sName === 'room_id') {
	        throw new LogicException('The key "room_id" is no longer available');
        }

		if(array_key_exists($sName, $this->_aData)){
			$mReturn = $this->_aData[$sName];
		}
		elseif(array_key_exists($sName, (array)$this->_aJoinTables)) {
			$mReturn = parent::__get($sName);
		}
		elseif(array_key_exists($sName, (array)$this->_aAttributes)) {
			$mReturn = parent::__get($sName);
		}
		elseif(array_key_exists($sName, $this->_aAdditionalData)){
			switch($sName){
				case 'from':
				case 'until':
					$mValue		= $this->_aAdditionalData[$sName];
					$aTimeInfos	= explode(":",$mValue);
					$iSeconds	= ($aTimeInfos[0]*3600)+($aTimeInfos[1]*60)+$aTimeInfos[2];
					$mReturn	=  $iSeconds;
				default:
					$mReturn	= $this->_aAdditionalData[$sName];
			}
		}elseif('courses'==$sName){
			$oTuitionClass = $this->getJoinedObject('class');
			$mReturn = $oTuitionClass->courses;
		}else{
			$mReturn = parent::__get($sName);
		}

		return $mReturn;
	}

	public function getAdditionalData($sName) {
		if(array_key_exists($sName, $this->_aAdditionalData)){
			return $this->_aAdditionalData[$sName];
		}
	}

	public function getSchool() {
		return parent::getSchool();
	}

	protected function _getCourses() {

		$oSchool	= Ext_Thebing_School::getSchoolFromSession();
		$sLang		= $oSchool->getInterfaceLanguage();
		$oClass		= $this->getClass();
		$sField		= 'name_' . $sLang;

		$sSql =  "
			SELECT
				`ktclc`.`course_id` `id`,
				`ktc`.`name_short`,
				`ktc`.`maximum_students` `students_max`,
				IF(
					`ktc`.`name_short` != '', 
					`ktc`.`name_short`,
					`ktc`.#name_field
				) `course`,
				`ktc`.#name_field `complete`
			FROM
				`kolumbus_tuition_classes_courses` `ktclc` LEFT JOIN
				`kolumbus_tuition_courses` `ktc` ON
					`ktclc`.`course_id` = `ktc`.`id`
			WHERE
				`ktclc`.`class_id` = :class_id
		";
		
		$aSql = array(
			'class_id'		=> (int)$oClass->id,
			'name_field'	=> $sField,
		);
		
		$aResult =  DB::getPreparedQueryData($sSql, $aSql);

		return $aResult;
	}

	public function getCourses(){
		return $this->_getCourses();
	}

	public function delete($bLog=true, $bCheckPayments=true) {

		if($bCheckPayments) {
			$aTeacherPayments = Ext_Thebing_Teacher_Payment::searchByBlockIds([$this->id]);
			if(!empty($aTeacherPayments)) {
				return false;
			}
		}
	
		// Zuweisungen löschen
		$this->deleteAllocations();
		
		// Ersatzlehrer löschen
		$this->deleteSubstituteTeachers();
		
		$mReturn = parent::delete();

		if($mReturn === true) {
			\System::wd()->executeHook('ts_school_tuition_block_delete', $this);
		}

		return $mReturn;

	}

	public function getBlockContent($iRoomId, $bForExcel=false, $iDay=0) {

		$sCode = '';

		$oSchool			= Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId			= (int)$oSchool->id;

		// $oRoom nicht an getStudents() übergeben, sonst stimmen die Werte in der Platzhalterklasse nicht
		$aStudents			= $this->getStudents();
		$aStudentsBefore	= $aStudents;
		
		if($iDay > 0) {

			$dWeek = new DateTime($this->week);
			$dWeekDay = Ext_Thebing_Util::getRealDateFromTuitionWeek($dWeek, $iDay, $oSchool->course_startday);

			foreach($aStudents as $iKey => $aStudent) {

				$dFrom = new DateTime($aStudent['from']);
				$dUntil = new DateTime($aStudent['until']);

				// Prüfen, ob Kunde mit seinem Kurs-Leistungszeitraum in den ausgewählten Tag des Blocks reinfällt
				if(
					$dWeekDay < $dFrom ||
					$dWeekDay > $dUntil
				) {
					unset($this->_aStudents[$iKey]);
				}

			}

			$aStudents = $this->_aStudents;

		}

		if(!$bForExcel)
		{
			$bNotAllocated = false;
			foreach($aStudents as $aStudent) {
				if($aStudent['course_allocated'] == "0") {
					$bNotAllocated = true;
				}
			}

			$sToolBarHtml = "";

			if($this->hasClass()) {

				if (!$this->hasTeacher()) {
					$sToolBarHtml .= '<div title="' . L10N::t('Kein Lehrer vorhanden', 'Thebing » Tuition') . '"><i class="fa fa-exclamation-triangle"></i></div>';
				} elseif ($iDay > 0) {
					$oWdDate = new WDDate($this->week, WDDate::DB_DATE);
					$oWdDate->set($iDay, WDDate::WEEKDAY);
					$oAbsence = new Ext_Thebing_Absence();
					$aTeachers = array((int)$this->teacher_id);
					$aSubTeachers = $this->getSubstituteTeachers($iDay, true);
					$aTeachers = array_merge($aTeachers, $aSubTeachers);
					$aTeachers = array_unique($aTeachers);
					$aEntries = $oAbsence->getEntries($oWdDate, $oWdDate, $aTeachers, 'teacher');

					if (!empty($aEntries)) {
						$aAbsenceTeachers = array_keys($aEntries);
						foreach ($aAbsenceTeachers as $iTeacherId) {
							$oTeacher = Ext_Thebing_Teacher::getInstance($iTeacherId);
							$sMessage = L10N::t('Lehrer "%s" ist abwesend');
							$sMessage = str_replace('%s', $oTeacher->name, $sMessage);
							$sToolBarHtml .= '<div title="'.\Util::convertHtmlEntities($sMessage).'"><i class="fa fa-exclamation-triangle"></i></div>';
						}
					}
				}

				if ($bNotAllocated) {
					$sToolBarHtml .= '<div title="' . L10N::t('Schüler vorhanden, die nicht in diese Klasse passen.', 'Thebing » Tuition') . '"><i class="fa fa-exclamation-triangle"></i></div>';
				}

				if (
					$this->school_id == $iSchoolId &&
					!$this->isReadonly()
				) {
					
					if (
						\Ext_Thebing_Access::hasRight(['thebing_tuition_planificaton', 'edit']) ||
						\Ext_Thebing_Access::hasRight(['thebing_tuition_planificaton', 'show'])
					) {
						$sToolBarHtml .= '<div onclick="executeAction(' . (int)$this->id . ', \'edit\');" class="" title="' . L10N::t('Klasse bearbeiten', 'Thebing » Tuition') . '"><i class="fa fa-pencil"></i></div>';
					}
					if (\Ext_Thebing_Access::hasRight(['thebing_tuition_planificaton', 'students_assign'])) {
						$sToolBarHtml .= '<div onclick="prepareMoveStudent(false, $(\'' . sprintf('room_content_%s_%s', (int)$this->id, (int)$iRoomId) . '\'));" title="' . L10N::t('Ausgewählte Schüler zuordnen', 'Thebing » Tuition') . '"><i class="fa fa-plus"></i></div>';
					}
					if (\Ext_Thebing_Access::hasRight(['thebing_tuition_planificaton', 'students_remove'])) {
						$sToolBarHtml .= '<div onclick="clearStudents(' . (int)$this->id . ', ' . $iRoomId . ');" title="' . L10N::t('Alle Schüler entfernen', 'Thebing » Tuition') . '"><i class="fa fa-trash"></i></div>';
					}
					if(\Ext_Thebing_Access::hasRight(['thebing_tuition_planificaton', 'block_daily_comments'])) {
						$sToolBarHtml .= '<div onclick="executeAction(' . (int)$this->id . ', \'daily_comments\');" class="" title="' . L10N::t('Tägliche Kommentare', 'Thebing » Tuition') . '"><i class="far fa-comment-dots"></i></div>';
					}
				}

			}

			if(!empty($sToolBarHtml)) {
				$sCode .= '<div class="room_content_toolbar">'.$sToolBarHtml.'</div>';
			}

			$sCode .= '<div class="room_content_padding">';
		}

		if($this->hasClass()) {
			$aCourses = (array)$this->courses;
			$iCourseCategory = 0;
			foreach ($aCourses as $iCourseId) {
				$oCourse = Ext_Thebing_Tuition_Course::getInstance($iCourseId);
				$iCategoryId = (int)$oCourse->category_id;
				if (0 != $iCategoryId) {
					$iCourseCategory = $iCategoryId;
					break;
				}
			}

			$oCourseCategory = Ext_Thebing_Tuition_Course_Category::getInstance($iCourseCategory);

			$oPlaceholder = new Ext_Thebing_School_Tuition_Block_Placeholder($this->id, $iRoomId, $iDay);
			$sPlanificationTemplate = $oPlaceholder->replace($oCourseCategory->planification_template);
			$sCode .= $sPlanificationTemplate;
		} else {
			$sCode .= $this->description;
		}

		if(!$bForExcel)
		{
			$sCode .= '</div>';
		}

		$this->_aStudents = $aStudentsBefore;
		
		return $sCode;

	}

	/**
	 * @TODO Dieses Konstrukt sollte entfernt werden
	 * @TODO Das ist ein Setter!
	 *
	 * @param int $iDay
	 */
	protected function _getStudents($iDay = 0, $bForce = false) {

		$aSql = array();
		$aSql['block_id']	= (int)$this->_aData['id'];
		$aSql['class_id']	= (int)$this->_aData['class_id'];
		$aSql['school_id']	= (int)$this->_aData['school_id'];

		$sWhereShowWithoutInvoice = Ext_Thebing_System::getWhereFilterStudentsByClientConfig('`ki`');

        $aCache = $this->_aGetStudentsCache;

        if($bForce || !array_key_exists($sWhereShowWithoutInvoice, $aCache)){

            $sSql = "
                    SELECT
                        /* Kein key darf einen Key aus ktbic überschreiben! */
                        `ktbic`.*,
                        `cd1`.`id` `student_id`,
                        `cd1`.`language` `mother_tongue`,
                        IF(
                            `ktclc`.`course_id` IS NOT NULL, 1, 0
                        ) `course_allocated`,
                        `kic`.`from`,
                        `kic`.`until`,
                        `kic`.`flexible_allocation`,
                        `ki`.`id` `inquiry_id`,
                        `kic`.`courselanguage_id`,
                        `ktbic`.`room_id`,
                        `ktbic`.`program_service_id`,
                        `ktbic`.`id` `id`
                    FROM
                        `kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN
                        `kolumbus_tuition_blocks` `ktb` ON
                            `ktb`.`id` = `ktbic`.`block_id` AND
                            `ktb`.`active` = 1 INNER JOIN
                        `ts_inquiries_journeys_courses` `kic` ON
                            `ktbic`.`inquiry_course_id` = `kic`.`id` AND
                            `kic`.`active` = 1 AND
                            `kic`.`for_tuition` = 1 AND
                            `kic`.`visible` = 1 INNER JOIN
                        `ts_inquiries_journeys` `ts_i_j` ON
                            `ts_i_j`.`id` = `kic`.`journey_id` AND
                            `ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
                            `ts_i_j`.`active` = 1 INNER JOIN
                        `ts_inquiries` `ki` ON
                            `ki`.`id` = `ts_i_j`.`inquiry_id` AND
                            `ki`.`active` = 1 INNER JOIN
                        `ts_inquiries_to_contacts` `ts_i_to_c` ON
                            `ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
                            `ts_i_to_c`.`type` = 'traveller' INNER JOIN
                        `tc_contacts` cd1 ON
                            `ts_i_to_c`.`contact_id` = `cd1`.`id` AND
                            `cd1`.`active` = 1 LEFT JOIN
						`kolumbus_tuition_courses` `ktc` ON
							`ktc`.`id` = `ktbic`.`course_id` AND
							`ktc`.`active` = 1 LEFT JOIN
                        `kolumbus_tuition_classes_courses` `ktclc` ON
                            `ktclc`.`class_id` = :class_id AND
                            `ktclc`.`course_id` = `ktbic`.`course_id`
                    WHERE
                        `ts_i_j`.`school_id` = :school_id AND
                        `ki`.`canceled` <= 0 AND
                        `ktbic`.`block_id` = :block_id AND
                        `ktbic`.`active` = 1 AND
                        `ki`.`active` = 1 AND
                        `kic`.`visible` = 1
                        ".$sWhereShowWithoutInvoice."
                    GROUP BY
                        `ktbic`.`id`
            ";

            $this->_aStudents = DB::getPreparedQueryData($sSql, $aSql);

            $this->_aGetStudentsCache[$sWhereShowWithoutInvoice] = $this->_aStudents;
        } else {
            $this->_aStudents = $aCache[$sWhereShowWithoutInvoice];
        }		

	}

	/**
	 * @deprecated
	 * @see getAllocations()
	 *
	 * @param Ext_Thebing_Tuition_Classroom|null $room
	 * @return array
	 */
	public function getStudents(\Ext_Thebing_Tuition_Classroom $room = null) {

	    $students = $this->_aStudents;

	    if(!is_null($room)) {
	        $students = array_filter($students, function($student) use ($room) {
	            return ($student['room_id'] == $room->getId());
            });
        }

        return $students;
	}

	/**
	 * Allocations über getObjectFromArray() aus dem _getStudents-Konstrukt
	 *
	 * @return Ext_Thebing_School_Tuition_Allocation[]
	 */
	public function getAllocations() {

		$this->_getStudents();

		$fields = collect(DB::describeTable('kolumbus_tuition_blocks_inquiries_courses'))->keys();

		$allocations = array_map(function (array $row) use ($fields) {
			$data = $fields->mapWithKeys(function ($field) use ($row) {
				return [$field => $row[$field]];
			});
			return Ext_Thebing_School_Tuition_Allocation::getObjectFromArray($data->toArray());
		}, $this->_aStudents);

		return $allocations;

	}

	public function checkInquiryCourseLevel($iInquiryCourseId, $iLevelGroupId) {

		$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance((int)$iInquiryCourseId);
		$journey = $oInquiryCourse->getJourney();
		$inquiry = $journey->getInquiry();

		$iLevel = $oInquiryCourse->getProgress($this->_aData['week'], $iLevelGroupId);
		$iLevelPlacementtestResult = Ext_Thebing_Placementtests_Results::getLevelForInquiryAndLanguage($inquiry->id, $oInquiryCourse->getCourseLanguage()->id);

		if(
			$this->level_id == 0 ||
			$this->level_id == $iLevel ||
			(
				empty($iLevel) &&
				$this->level_id == $iLevelPlacementtestResult
			)
		) {
			return true;
		} else {
			return false;
		}

	}

	public function getBlockingInquiryCourseAllocations(\Ext_TS_Inquiry_Journey_Course $journeyCourse, \TsTuition\Entity\Course\Program\Service $programService): array
	{
		$allocations = (array)$this->getInquiryCourseAllocations($journeyCourse);

		$course = $programService->getService();

		$blockingAllocations = array_filter(
			$allocations,
			// Andere Zuweisungen die keine parallelen Zuweisungen erlauben oder Zuweisungen derselben Leistung
			function ($allocation) use ($course, $programService) {
				$isSameService = (int)$allocation['block_inquiry_program_service_id'] === (int)$programService->id;
				if (!$course->allowsParallelTuitionAllocations()) {
					return $isSameService || (int)$allocation['allow_parallel_tuition_allocations'] === 0;
				}
				return $isSameService;
			}
		);

		return $blockingAllocations;
	}

	public function getInquiryCourseAllocations($mInquiryCourse) {

		if($mInquiryCourse instanceof Ext_TS_Inquiry_Journey_Course) {
			$oInquiryCourse = $mInquiryCourse;
		} else {
			$oInquiryCourse	= Ext_TS_Inquiry_Journey_Course::getInstance($mInquiryCourse);
		}

		$oCustomer = $oInquiryCourse->getCustomer();
		$oSchool = $this->getSchool();
		
		$oTemplate = Ext_Thebing_Tuition_Template::getInstance($this->template_id);

		$sWhereShowWithoutInvoice = Ext_Thebing_System::getWhereFilterStudentsByClientConfig('`ki`');

		$sSql = "
				SELECT
					`ktb`.*,
					`ktt`.`name`,
					`ktt`.`from`,
					`ktt`.`until`,
					`ktt`.`lessons`,
					`ktc`.`name` `class_name`,
					`kcr`.`name` `room_name`,
					`ktbic`.`id` as `block_inquiry_course_id`,
					`ktbic`.`program_service_id` as `block_inquiry_program_service_id`,
					`program_course`.`allow_parallel_tuition_allocations`
				FROM
					`kolumbus_tuition_blocks` `ktb` INNER JOIN
					`kolumbus_tuition_classes` `ktc` ON
						`ktb`.`class_id` = `ktc`.`id` INNER JOIN
					`kolumbus_tuition_templates` `ktt` ON
						`ktb`.`template_id` = `ktt`.`id` INNER JOIN
					`kolumbus_tuition_blocks_days` `ktbd` ON
						`ktbd`.`block_id` = `ktb`.`id` INNER JOIN
					`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
						`ktbic`.`block_id` = `ktb`.`id` INNER JOIN
					`ts_tuition_courses_programs_services` `ts_tcps` ON 
						`ts_tcps`.`id` = `ktbic`.`program_service_id` INNER JOIN
					`kolumbus_tuition_courses` `program_course` ON 
						`program_course`.`id` = `ts_tcps`.`type_id` LEFT JOIN
					`kolumbus_classroom` `kcr` ON
						`ktbic`.`room_id` = `kcr`.`id` INNER JOIN
					`ts_inquiries_journeys_courses` `kic` ON
						`kic`.`id` = `ktbic`.`inquiry_course_id` INNER JOIN
					`ts_inquiries_journeys` `ts_i_j` ON
						`ts_i_j`.`id` = `kic`.`journey_id` AND
						`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
						`ts_i_j`.`active` = 1 INNER JOIN
					`ts_inquiries` `ki` ON
						`ki`.`id` = `ts_i_j`.`inquiry_id` INNER JOIN
					`ts_inquiries_to_contacts` `ts_i_to_c` ON
						`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
						`ts_i_to_c`.`type` = 'traveller' INNER JOIN
					`tc_contacts` `cdb1` ON
						`cdb1`.`id` = `ts_i_to_c`.`contact_id` INNER JOIN
					`customer_db_2` `cdb2` ON
						`cdb2`.`id` = `ktb`.`school_id`
				WHERE
					`ktb`.`class_id` != :self_class_id AND
					`ktb`.`school_id` = :school_id AND
					`ktb`.`week` = :week AND
					`ktb`.`active` = 1 AND
					(
						`ktt`.`from` < :until AND
						`ktt`.`until` > :from
					) AND
					`ktbd`.`day` IN (:days) AND
					getRealDateFromTuitionWeek(
						`ktb`.`week`,
						`ktbd`.`day`,
						`cdb2`.`course_startday`
					) BETWEEN `kic`.`from` AND `kic`.`until` AND
					`cdb1`.`id` = :customer_id AND
					`ktbic`.`active` = 1 AND
					`kic`.`active` = 1 AND
					`ki`.`active` = 1 AND
					`cdb1`.`active` = 1 AND
					`ki`.`canceled` <= 0 
					".$sWhereShowWithoutInvoice."
				GROUP BY
					`ktb`.`id`
				";

		$aDaysCheck = array();
		$dWeek = new DateTime($this->week);
		foreach((array)$this->days as $iDay) {
			$dDay = Ext_Thebing_Util::getRealDateFromTuitionWeek($dWeek, $iDay, $oSchool->course_startday);
			if($dDay->isBetween(new DateTime($oInquiryCourse->from), new DateTime($oInquiryCourse->until))) {
				$aDaysCheck[] = $iDay;
			}
		}

		$aSql = array();
		//$aSql['self_id']			= (int)$this->id;
		$aSql['self_class_id']		= (int)$this->class_id;
		$aSql['days']				= $aDaysCheck;
		$aSql['school_id']			= (int)$this->school_id;
		$aSql['customer_id']		= (int)$oCustomer->id;
		$aSql['week']				= $this->week;
		$aSql['from']				= $oTemplate->from;
		$aSql['until']				= $oTemplate->until;
		$aCheck = DB::getPreparedQueryData($sSql, $aSql);

		return $aCheck;
	}

	/**
	 * @param Ext_TS_Inquiry_Journey_Course|int $mInquiryCourse
	 * @param int $iProgramServiceId
	 * @param int $iRoomId
	 * @param int $bAllocationCheck
	 * @param bool $bReturnBlocks
	 * @return array|bool|string
	 */
	public function checkInquiryCourse($mInquiryCourse, $iProgramServiceId, $iRoomId, $bAllocationCheck=1, $bReturnBlocks = false) {

		if($mInquiryCourse instanceof Ext_TS_Inquiry_Journey_Course) {
			$oInquiryCourse = $mInquiryCourse;
		} else {
			$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance((int)$mInquiryCourse);
		}

		$oProgramService = \TsTuition\Entity\Course\Program\Service::getInstance($iProgramServiceId);
		$oCourse = $oProgramService->getService();

		if(!$oProgramService->isCourse()) {
			// Sollte nicht vorkommen, da im Interface nicht zuweisbar
			return 'wrong_course';
		}

		//combination courses
//		$oMasterCourse = new Ext_Thebing_Course_Util();
//        $oMasterCourse->setCourse($oInquiryCourse->course_id);
//        $aComboCourses = array();
//        $aIds = array();
//        if ($oMasterCourse->isCombination()) {
//            $aComboCourses = $oMasterCourse->getCombinedCourses();
//            foreach ($aComboCourses as $key => $oCourse) {
//                $aIds[] = $oCourse->getCourseId();
//            }
//        }

		$aCourses		= (array)$this->courses;
		$bCheckCourses	= false;

        foreach($aCourses as $iBlockCourseId) {
			if(
				$iBlockCourseId == $oInquiryCourse->course_id ||
				$iBlockCourseId == $oCourse->getId()
			) {
				$bCheckCourses = true;
			}
		}

		//if no course dont check allocated
		if(!$bCheckCourses){
			return 'no_course';
		}

		$class = $this->getClass();
		
		if(
			$class->courselanguage_id !== null &&
			$class->courselanguage_id != $oInquiryCourse->courselanguage_id
		) {
			return 'wrong_course_language';
		}

		// Überprüfen, ob $iCourseId auch zur Kursbuchung gehört (falls falsche ID übermittelt wird oder Liste veraltet)
		$oProgram = $oInquiryCourse->getProgram();

		$oProgramCoursesIds = $oProgram->getCourses()
			->filter(function(\Ext_Thebing_Tuition_Course $course) {
				return !$course->isEmployment();
			})
			->map(function(\Ext_Thebing_Tuition_Course $course) {
				return (int)$course->getId();
			})
		;

		if(!$oProgramCoursesIds->contains((int) $oCourse->getId())) {
			return 'wrong_course';
		}

		// Prüfen, ob die Kursbuchung noch genug Lektionen übrig hat bei Lektionskursen
		if($oCourse->isPerUnitCourse()) {
			$remaining = $oInquiryCourse->getTuitionRemainingLessons($oProgramService, new \Carbon\Carbon($this->week));
			$blockLessons = $this->getNumberOfLessons();

			if (bccomp($remaining['remaining_lessons'], $blockLessons, 5) === -1) {
				return 'not_enough_lessons';
			}
		}

		$oRoom = \Ext_Thebing_Tuition_Classroom::getInstance($iRoomId);

		if($iRoomId == -1) {
			$oRoom->online = 1;
		}

		if(
			$oCourse->isOnline() &&
			!$oRoom->isOnline()
		) {
			return 'no_online_room';
		}

		// Bei mehreren Räumen im Block muss geprüft werden, ob der gewählte Raum (Drag & Drop) durchgängig verfügbar ist
		// Bsp.: Klasse mit mehrere Räumen hat ab Woche 2 andere Räume, aber Schüler wird in Woche 1 zugewiesen
		try {
			$this->adjustAllocationRoomId($iRoomId);
		} catch (RuntimeException $e) {
			if (\Illuminate\Support\Str::startsWith($e->getMessage(), 'Room does not belong to block')) {
				return 'wrong_room_multiple_allocation';
			}
			throw $e;
		}

		if($bAllocationCheck) {
			$blockingAllocations = $this->getBlockingInquiryCourseAllocations($oInquiryCourse, $oProgramService);

			if(!empty($blockingAllocations)) {
				return 'allocated';
			}
		}

		$aActivities = \TsActivities\Entity\Activity\BlockTraveller::getRepository()
			->checkOverlappingAllocations($oInquiryCourse->getJourney()->getInquiry(), Carbon::parse($this->week), $this->createPeriodCollection());

		if ($aActivities->isNotEmpty()) {
			return 'allocated_activity';
		}

		$aPayments = $this->getPayments();
		if(!empty($aPayments)){
			return 'payments_exists';
		}

        if ($bReturnBlocks) {

			$aAllocations = $this->getInquiryCourseAllocations($oInquiryCourse);

			if(!empty($aAllocations)) {
				return $aAllocations;
			} else {
			    return array();
			}
        }

		return true;

	}

	/**
	 * Löscht Zuweisungen für die inquiryCourseId und blockIds
	 * @param array $blockIds
	 * @param int $inquiryCourseId
	 * @return array
	 */
	public function deactivateBlocksInquiryCoursesById(array $blockIds = [], int $inquiryCourseId = 0): array
	{
		$deleteAllocationsErrors = [];
        if (
			!empty($blockIds) &&
			$inquiryCourseId > 0
		) {
			$allocations = \Ext_Thebing_School_Tuition_Allocation::query()
				->whereIn('block_id', $blockIds)
				->where('inquiry_course_id', $inquiryCourseId)
				->get();
			foreach ($allocations as $allocation) {
				$deleteAllocationsErrors = array_merge($deleteAllocationsErrors, $allocation->validateDelete());
			}
			if (empty($deleteAllocationsErrors)) {
				foreach ($allocations as $allocation) {
					$allocation->delete();
				}
				System::wd()->executeHook('ts_class_assignment_deactivate', $this, $blockIds, $inquiryCourseId);
			}
	   	}
		return $deleteAllocationsErrors;
	}

	/** @var array Ext_Thebing_School_Tuition_Allocation[] */
	private static $aNewAllocationsObjectCache = [];

	/**
	 * Kursbuchung zu Block zuweisen
	 *
	 * Achtung, die Methode prüft keine Überschneidungen!
	 * Doch, macht sie mittlerweile. Sie entfernt diese dann. 
	 *
	 * Theoretisch darf diese Methode auch gar keine Überprüfungen mehr machen (außer Exceptions), weil das alles
	 * von der checkInquiryCourse gemacht werden muss. Man hätte auch einfach nur eine Methode verwenden können und
	 * Exceptions, aber so weit hat man damals nicht gedacht.
	 *
	 * @param Ext_TS_Inquiry_Journey_Course|int $mInquiryCourse
	 * @param int $iProgramServiceId
	 * @param int $iRoomId
	 * @return array
	 */
	public function addInquiryCourse($mInquiryCourse, $iProgramServiceId, $iRoomId, $overwriteExistingAllocation=true) {

		if($mInquiryCourse instanceof Ext_TS_Inquiry_Journey_Course) {
			$oInquiryCourse	= $mInquiryCourse;
			$iInquiryCourseId = $oInquiryCourse->id;
		} else {
			$iInquiryCourseId = $mInquiryCourse;
			$oInquiryCourse	= Ext_TS_Inquiry_Journey_Course::getInstance($iInquiryCourseId);
		}

		$oProgramService = \TsTuition\Entity\Course\Program\Service::getInstance($iProgramServiceId);
		/* @var Ext_Thebing_Tuition_Course $oCourse */
		$oCourse = $oProgramService->getService();

		// Prüfen, ob Kurszeitraum noch in die Blockwoche (Starttag) fällt
		if(count($this->days) > 0) {
			if($oInquiryCourse->from === '0000-00-00') {
				throw new RuntimeException('Invalid journey course given');
			}

			// Wenn die Programmleistung selber ein Von-Bis hat dann diesen Zeitraum auch benutzen
			$dCourseFrom = $oProgramService->getFrom();
			$dCourseUntil = $oProgramService->getUntil();
			// Ansonsten die Daten vom Journey-Course nehmen
			if(is_null($dCourseFrom) && is_null($dCourseUntil)) {
				$dCourseFrom = new DateTime($oInquiryCourse->from);
				$dCourseUntil = new DateTime($oInquiryCourse->until);
			}

			$aBlockDayDates = $this->getDaysAsDateTimeObjects();

			// Überschneidung der Zeiträume In BEIDE Richtungen prüfen (relevant für Zuweisung nach Ferien)
			$bOverlap = \Core\Helper\DateTime::checkDateRangeOverlap($dCourseFrom, $dCourseUntil, min($aBlockDayDates), max($aBlockDayDates));

			if(!$bOverlap) {
				// Keine Zuweisung für diese Woche erstellen, da Kurszeitraum außerhalb der Blockwoche liegt
				return 'expired';
			}
		}

		// Prüfen, ob übergebener Kurs wirklich zur Kursbuchung gehört
		$oJourneyCourseCourse = $oInquiryCourse->getCourse();
		$aChildCourses = $oJourneyCourseCourse->getChildCoursesOrSameCourse($oInquiryCourse->getProgram());
		$bCourseFound = false;
		foreach($aChildCourses as $oChildCourse) {
			if($oCourse->id == $oChildCourse->id) {
				$bCourseFound = true;
				break;
			}
		}

		if(!$bCourseFound) {
			Ext_TC_Util::reportError('addInquiryCourse(): course_id doesn\'t belong to journey course', print_r([$oInquiryCourse->id, $oProgramService->id, $oCourse->id, $oJourneyCourseCourse->id], true));
			throw new RuntimeException('Given course id ('.$oCourse->getId().') doesn\'t belong to this journey course id ('.$iInquiryCourseId.')!');
		}

		$iRoomId = $this->adjustAllocationRoomId((int)$iRoomId);

		$aOldBlocks	= array();

		if (!$oCourse->allowsParallelTuitionAllocations()) {
			// check if student is allocated for this period
			$blockingAllocations = $this->getBlockingInquiryCourseAllocations($oInquiryCourse, $oProgramService);

			if(!empty($blockingAllocations)) {

				if($overwriteExistingAllocation === false) {
					return 'already_assigned';
				}

				foreach($blockingAllocations as $aItem) {
					$oAllocationOld = Ext_Thebing_School_Tuition_Allocation::getInstance((int)$aItem['block_inquiry_course_id']);
					$oAllocationOld->delete();
					$aOldBlocks[] = (int)$aItem['id'];
				}
			}
		}

		// Prüfen, ob die Kursbuchung noch genug Lektionen übrig hat bei Lektionskursen
		if($oProgramService->getService()->isPerUnitCourse()) {
			$remaining = $oInquiryCourse->getTuitionRemainingLessons($oProgramService, new \Carbon\Carbon($this->week));
			$blockLessons = $this->getNumberOfLessons();

			if (bccomp($remaining['remaining_lessons'], $blockLessons, 5) === -1) {
				return 'not_enough_lessons';
			}
		}
		
		if($oInquiryCourse->id != 0) {
			$oAllocation = Ext_Thebing_School_Tuition_Allocation::findByUniqueKeys($this->id, $iInquiryCourseId);
			$oAllocation->inquiry_course_id = $iInquiryCourseId;
		} else {
			$sKey = $this->id.'_'.spl_object_hash($oInquiryCourse).'_'.$iProgramServiceId;
			if(isset(self::$aNewAllocationsObjectCache[$sKey])) {
				/*
				 * Bei sehr stark verschachtelten Kursen und Ferien kann es passieren, dass Zuweisungen gleiche Daten haben.
				 * Das fängt die Klassenplanung an sich durch das findByUniqueKeys() ab, nur gibt es den Schlüssel ohne IDs nicht.
				 * Damit nicht doppelte, neue Kinder mit gleichem UNIQUE-Schlüssel gespeichert werden,
				 * muss hier die Funktion von findByUniqueKeys() mit einem statischen Cache emuliert werden… #9911
				 */
				$oAllocation = self::$aNewAllocationsObjectCache[$sKey];
			} else {
				// ID 0 funktioniert schlecht mit direktem save() und tuition_blocks ist readonly, aber hier fehlt die ID
				// TODO Keine Ahnung, ob das hier #3969 wieder auslösen könnte
				$oAllocation = $oInquiryCourse->getJoinedObjectChild('tuition_blocks_writeable');
				self::$aNewAllocationsObjectCache[$sKey] = $oAllocation;
			}
		}

		// Den Flag setzen, dass dieses Objekt neu ist, obwohl eine ID eventuell schon vorhanden ist
		// Siehe Ext_Thebing_School_Tuition_Allocation->isNew() Kommentar...
		$oAllocation->bIsNew = true;

		$oAllocation->room_id = $iRoomId;
		$oAllocation->block_id = $this->id;
		$oAllocation->course_id = $oCourse->getId();
		$oAllocation->program_service_id = $iProgramServiceId;

		// Lektionsdauer berechnen
		// TODO: Eigentlich ist das nicht gut, da der Wert nun redundant ist und andere Stellen den Wert live ausrechnen
		// Beispiel: Tuition-Index oder die Checkboxen der Anwesenheit
		$oAllocation->lesson_duration = $this->calculateAllocationLessonDuration($oInquiryCourse);

		//falls ein inaktives gefunden wurde
		$oAllocation->active = 1;

		if($oInquiryCourse->id != 0) {
			$oAllocation->save();
		}

		// Den internen $_aStudents array aktualisieren
		$this->_getStudents(bForce: true);

		// Prüfen ob Klasse bestätigt werden muss
		$class = $this->getClass();
		$class->checkConfirmAfterAddingStudent();

		return [
			'allocation' => $oAllocation,
			'replaced' => $aOldBlocks
		];

	}

	/**
	 * Lektionsdauer der Zuweisungen neu berechnen, wenn Lektionsanzahl oder Tage verändert wurden
	 */
	public function updateAllocations() {

        if (
			$this->bUpdateAllocations
		) {
			/* @var Ext_Thebing_School_Tuition_Allocation[] $aAllocations */
            $aAllocations = $this->getJoinedObjectChilds('allocations');
            $aRooms = $this->getRooms();
			// Um den passenden Raum zu finden werden zunächst Räume der Klasse ermittelt, die in allen Blöcken ab diesem vorkommen
			$intersectingRooms = \TsTuition\Helper\Rooms::getIntersectingRoomIds($this->getClass(), $this->parent_id ?: $this->id, $this->week);
			// Diese Räume wollen wir nicht, sondern einen der Räume, die nicht immer zur verfügung stehen. Falls vorhanden.
			$uncommonRooms = array_values(
				array_diff(
					collect($aRooms)
						->pluck('id')
						->toArray(),
					$intersectingRooms
				)
			);

			foreach ($aAllocations as $oAllocation) {

			    if (!empty($this->checkIfChanged('days'))) {
                    $oAllocation->lesson_duration = $this->calculateAllocationLessonDuration($oAllocation->getJourneyCourse());
                    $bSave = true;
                }

				/*$oAttendance = $oAllocation->getAttendance();

				// Wenn sich die Woche des Blockes geändert hat müssen die Anwesenheiten auch auf die neue Woche umgeschrieben werden
				if ($oAttendance && $oAttendance->week !== $this->week) {
					$oAttendance->week = $this->week;
					$bSave = true;
				}*/

				// RoomId neu setzen
				if (!in_array($oAllocation->room_id, $this->getRoomIds())) {
					$oCourse = $oAllocation->getCourse();
					// Zuerst wird geschaut, ob es einen Raum gibt, bei dem isOnline stimmt und der nicht in jeder Woche zur verfügung steht
					$possibleRooms = collect($aRooms)
						->filter(
							fn($room) =>
								$oCourse->isOnline() == $room->isOnline() &&
								in_array($room->id, $uncommonRooms)
						)
						->pluck('id')
						->toArray();
					if (count($possibleRooms)) {
						// Wenn es einen nicht überall existierenden Raum gibt, dann wird der genommen
						$oAllocation->room_id = reset($possibleRooms);
					} else {
						// Wenn es keinen nicht überall existierenden Raum gibt, dann wird der nächste passende genommen
						$possibleRooms = collect($aRooms)
							->filter(fn($room) => $oCourse->isOnline() == $room->isOnline())
							->pluck('id')
							->toArray();
						if (count($possibleRooms)) {
							$oAllocation->room_id = reset($possibleRooms);
						} else {
							// Kein Raum gefunden
							$oAllocation->room_id = 0;
						}
					}
					// TODO: Kann nach Testphase entfernt werden
					if (\Util::isDebugIP()) {
						Log::getLogger()->info('updateAllocationRooms', ['roomIds' =>
							collect($aRooms)
								->pluck('id')
								->toArray(),
							'uncommon' => $uncommonRooms,
							'intersecting' => $intersectingRooms,
							'possible' => $possibleRooms
						]);
					}
					$bSave = true;
				}
				if ($bSave) {
					$oAllocation->save();
				}
			}
		}
	}


	/**
	 * Dauer der Kurszuweisung (in Minuten) berechnen für den Block (ganze Woche)
	 *
	 * @param Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 * @return float
	 */
	public function calculateAllocationLessonDuration(Ext_TS_Inquiry_Journey_Course $oJourneyCourse) {

		// Hier muss geprüft werden welche Tage vom Block in den Kurszeitraum fallen
		$aDays = array_filter($this->days, function($iDay) use($oJourneyCourse) {
			$dBlockDay = Ext_Thebing_Util::getRealDateFromTuitionWeek(new DateTime($this->week), $iDay, $this->getSchool()->course_startday);
			$dFrom = new DateTime($oJourneyCourse->from);
			$dUntil = new DateTime($oJourneyCourse->until);

			return $dBlockDay->isBetween($dFrom, $dUntil);
		});

		$fLessonDuration = count($aDays) * $this->getLessonDuration();

		return $fLessonDuration;

	}

	public function getLessonDuration(float $lessonDuration = null) {

		if ($lessonDuration === null) {
			$lessonDuration = floatval($this->getClass()->lesson_duration);
		}

		$template = $this->getTemplate();

		$lessonDuration = floatval($template->lessons) * $lessonDuration;

		return $lessonDuration;
	}

	public function getNumberOfLessons(): float
	{
		$template = $this->getTemplate();

		$days = count($this->createPeriodCollection(false));
		$lessons = floatval($template->lessons) * $days;

		return $lessons;
	}

	/**
	 * @todo in validate reinpacken
	 * @param bool|int $iLevel
	 * @param bool $bNoCheck
	 * @param array $aFilteredBlockIds
	 * @param self[] $aCurrentClassBlocks
	 * @return mixed
	 */
	public function checkExistingBlocks($iLevel=false, $bNoCheck=false, $aFilteredBlockIds=array(), $aCurrentClassBlocks=array()) {

		$aErrors = array();

		$oClass		= $this->getClass();
		$iSchoolId	= (int)$this->school_id;
		$aRoomIds	= $this->getRoomIds();
		$iTeacherId	= (int)$this->teacher_id;
		$sWeek		= $this->week;
		$aDays		= $this->days;
		$iTemplate	= (int)$this->template_id;
		$iBlock		= (int)$this->id;
		$aCourses	= (array)$oClass->courses;

		$oTeacher = null;
		if($iTeacherId > 0) {
			$oTeacher = Ext_Thebing_Teacher::getInstance($iTeacherId);
		}

		if(empty($aDays)) {
			$aErrors[] = 'no_days';
		}

		if(empty($aCourses)) {
			$aErrors[] = 'no_courses';
		}
		
		foreach ((array)$aCourses as $key => $iCourseId ) {
			
			$oCourse	= Ext_Thebing_Tuition_Course::getInstance($iCourseId);
		    
			if ( !self::checkCourseAvailabity ( (int)$iCourseId, $sWeek, $aDays, $iSchoolId ) ) {
		        $aErrors[] = 'course_not_available';
		    }
			
			$bCourseValidity = $this->_isObjectValid($oCourse);
			
			if(
				!$bCourseValidity
			){
				$aErrors['course_'.$iCourseId] = 'course_not_valid';
			}
			
			if($oTeacher) {				
				$oCourseCategory = $oCourse->getCategory();				
				$bCheckCourseCategory = $oTeacher->checkCourseCategory($oCourseCategory);
				if(!$bCheckCourseCategory) {
					$aErrors['course_category_'.$oCourseCategory->id] = 'teacher_course_category';
				}				
			}			
		}

		// get template info
		$aTemplate = DB::getRowData('kolumbus_tuition_templates', $iTemplate);

		$sWhere = "";

        if($iBlock) {
			if(!is_array($aFilteredBlockIds)){
				$aFilteredBlockIds = array();
			}
			$aFilteredBlockIds[] = $iBlock;
            
			$sWhere .= " AND ktb.id NOT IN (:block_ids) ";
		}

		if(!empty($aDays)) {
			$sWhere .= " AND
					ktbd.day IN (".implode(",", $aDays).")";
		}

		foreach($aCurrentClassBlocks as $oTmpBlock) {

			if($this->id == $oTmpBlock->id) {
				continue;
			}

			// Räume dürfen nicht gemischt werden, da sonst nicht ermittelbar ist, in welchen Raum der Schüler zugewiesen werden soll
			if (
				!empty($aRoomIds) &&
				count($aRoomIds) > 2 &&
				!empty(array_diff($aRoomIds, $oTmpBlock->getRoomIds()))
			) {
				$aErrors[] = 'room_multiple_incompatibility';
			}

			// Prüfen, ob Blöcke in selber Klasse sich zeitlich überschneiden
			foreach($aDays as $iDay) {
				if(in_array($iDay, $oTmpBlock->days)) {

					$aTmpTemplate = DB::getRowData('kolumbus_tuition_templates', $oTmpBlock->template_id);
					$oTimeFrom = new DateTime($aTemplate['from']);
					$oTimeUntil = new DateTime($aTemplate['until']);
					$oTmpTimeFrom = new DateTime($aTmpTemplate['from']);
					$oTmpTimeUntil = new DateTime($aTmpTemplate['until']);

					if(
						$oTimeFrom < $oTmpTimeUntil &&
						$oTmpTimeFrom < $oTimeUntil
					) {
						$aErrors[] = 'block_overlapping';
					}
				}
			}

		}

		// check if room is allocated
		// @TODO Auf Objekte umstellen
		if(!empty($aRoomIds)) {

			$sSql = "
					SELECT
						ktb.*,
						ktt.name,
						ktt.from,
						ktt.until,
						ktt.lessons
					FROM
						`kolumbus_tuition_blocks` ktb JOIN
						`kolumbus_tuition_blocks_to_rooms` `ktbtr` ON 
						    `ktbtr`.`block_id` = `ktb`.`id` JOIN
						`kolumbus_tuition_templates` ktt ON
							ktb.template_id = ktt.id JOIN
						`kolumbus_tuition_blocks_days` ktbd ON
							ktbd.block_id = ktb.id
					WHERE
						ktb.`week` = :week AND
						ktbtr.`room_id` IN (:room_ids) AND
						ktb.`active` = 1 AND
						(
							ktt.`from` < :until AND
							ktt.`until` > :from
						)
						".$sWhere."
					";
			//$aSql['school_id']	= (int)$iSchoolId;
			
			// Kein Raum und virtueller Raum kann beliebig oft verwendet werden
			$aRoomIds = array_filter($aRoomIds, function($iRoomId) {
				if($iRoomId < 1) {
					return false;
				}
				return true;
			});

			$aSql				= array();
			$aSql['room_ids']	= $aRoomIds;
			$aSql['week']		= $sWeek;
			$aSql['from']		= $aTemplate['from'];
			$aSql['until']		= $aTemplate['until'];
			$aSql['block_ids']	= $aFilteredBlockIds;

			$aCheck = DB::getPreparedQueryData($sSql, $aSql);

			if(!empty($aCheck)) {
				$aErrors[] = 'room_allocated';
			}else{
			    foreach($aRoomIds as $iRoomId) {
                    $oRoom = Ext_Thebing_Tuition_Classroom::getInstance($iRoomId);
                    $bValidityRoom = $this->_isObjectValid($oRoom);
                    if(
                        !$bValidityRoom
                    ){
                        $aErrors[] = 'room_not_valid';
                    }
                }
			}
		}

		// check if teacher is allocated
		if($iTeacherId > 0) {

			$sSql = "
					SELECT
						ktb.*,
						ktt.name,
						ktt.from,
						ktt.until,
						ktt.lessons
					FROM
						`kolumbus_tuition_blocks` ktb JOIN
						`kolumbus_tuition_templates` ktt ON
							ktb.template_id = ktt.id JOIN
						`kolumbus_tuition_blocks_days` ktbd ON
							ktbd.block_id = ktb.id
					WHERE
						ktb.`school_id` = :school_id AND
						ktb.`week` = :week AND
						ktb.`teacher_id` = :teacher_id AND
						ktb.`active` = 1 AND
						(
							ktt.`from` < :until AND
							ktt.`until` > :from
						)
						".$sWhere."
					";
			$aSql = array();
			$aSql['school_id']	= (int)$iSchoolId;
			$aSql['teacher_id']	= (int)$iTeacherId;
			$aSql['week']		= $sWeek;
			$aSql['from']		= $aTemplate['from'];
			$aSql['until']		= $aTemplate['until'];
			$aSql['block_ids']	= $aFilteredBlockIds;

			$aCheck = DB::getPreparedQueryData($sSql, $aSql);

			if(!empty($aCheck)) {
				$aErrors[] = 'teacher_allocated';
			}else{
				$oTeacher = Ext_Thebing_Teacher::getInstance($iTeacherId);
				$bValidityRoom = $this->_isObjectValid($oTeacher);
				if(
					!$bValidityRoom
				){
					$aErrors[] = 'teacher_not_valid';
				}	
			}

			// check if teacher is able to work in block-time
			// by it's 'timeFrom' and 'timeTo' entries in table `kolumbus_teacher_schedule`
			foreach ((array)$aDays as $key => $iDay) {

				$oWeek = new WDDate($sWeek, WDDate::DB_DATE);
				$oWeek->add(($iDay-1), WDDate::DAY);
				$sDay = $oWeek->get(WDDate::DB_DATE);

				$sSql = "
						SELECT
							*
						FROM
							`kolumbus_absence`
						WHERE
							`item` = 'teacher' AND
							`item_id` = :item_id AND
							`active` = 1 AND
							DATE(:day) BETWEEN `from` AND `until`
						";
				$aSql = array();
				$aSql['item_id'] = (int)$iTeacherId;
				$aSql['day']     = $sDay;

				$aCheck = DB::getPreparedQueryData($sSql, $aSql);
				if(!empty($aCheck)) {
					$aErrors[] = 'teacher_holiday';
					break;
				}

			}

			if(!$bNoCheck) {

				// check if teacher is able to work in block-time
				// by it's 'timeFrom' and 'timeTo' entries in table `kolumbus_teacher_schedule`
				$aDayObjects = $this->getDaysAsDateTimeObjects();
				/**
				 * @todo Daten nicht jedesmal per Query abfragen -> Cachen
				 */
				foreach($aDayObjects as $iDay=>$dDayObject) {
					$sSql = "
							SELECT
								kts.*
							FROM
								`kolumbus_teacher_schedule` `kts`
							WHERE
								`kts`.`idTeacher` = :teacher_id AND
								`kts`.`active` = 1 AND
								`kts`.`timeFrom` <= :from AND
								`kts`.`timeTo` >= :until AND
								`kts`.`idDay` = :day AND
								(
									`kts`.`valid_from` IS NULL OR 
									`kts`.`valid_from` <= '".$dDayObject->format('Y-m-d')."'
								) AND
								(
									`kts`.`valid_until` IS NULL OR 
									`kts`.`valid_until` >= '".$dDayObject->format('Y-m-d')."'
								)
							";
					$aSql = array();
					$aSql['teacher_id'] = (int)$iTeacherId;
					$aSql['from']       = $aTemplate['from'];
					$aSql['until']      = $aTemplate['until'];
					$aSql['day']        = (int)$iDay;

					$aCheck_B = DB::getPreparedQueryData($sSql, $aSql);
					if(empty($aCheck_B)) {
						$aErrors[] = 'teacher_worktime';
						break;
					}
				}

				if ($iLevel !== false) {
					// check if teacher is able to teach this level
					$sSql = "
							SELECT
								ktl.*
							FROM
								`kolumbus_teacher_levels` ktl
							WHERE
								ktl.`teacher_id` = :teacher_id AND
								ktl.`level_id` ".(is_array($iLevel) ? "IN (".implode(",",$iLevel).")" : "= ".(int)$iLevel ). "". // :level_id
							"";
					$aSql = array();
					$aSql['teacher_id']	= (int)$iTeacherId;
					$aSql['level_id']	= (int)$iLevel;
					$aCheck = DB::getPreparedQueryData($sSql, $aSql);
					if(empty($aCheck)) {
						$aErrors[] = 'teacher_level';
					}
				}
		
			}
		}

		//payments überprüfen
		$aExistingPayments = $this->getPayments();

		if(!empty($aExistingPayments))
		{
			$aCompareFields = ['template_id', 'teacher_id', 'days'];

			if(!empty($this->checkIfChanged($aCompareFields))){
				$aErrors[] = 'payments_exists';
			}
		}

		if(
			!empty($this->checkIfChanged('template_id')) ||
			!empty($this->checkIfChanged('week'))
		) {
			$bHasAttendance	= $this->hasAttendanceEntries();
			
			if ($bHasAttendance) {
				$aErrors[] = 'attendance_exists';
			}
		}

		if(!empty($this->checkIfChanged('days'))) {
			$deletedDays = array_intersect_key(
				\Ext_Thebing_Tuition_Attendance::DAY_MAPPING,
				array_flip(array_diff($this->_aOriginalJoinData['days'], $this->days))
			);

			if (!empty($deletedDays)) {
				// Prüfen ob für die gelöschten Tage Anwesenheiten existieren
				$bHasAttendance	= $this->hasAttendanceEntries($deletedDays);

				if($bHasAttendance) {
					$aErrors[] = 'attendance_exists_for_days';
				}
			}
		}

		if(
			!empty($this->checkIfChanged('rooms')) &&
			!empty($this->rooms)
		) {

			$oGrouped = collect($this->getAllocations())->mapToGroups(function (Ext_Thebing_School_Tuition_Allocation $oAllocation) {
				if ($oAllocation->getCourse()->isOnline()) {
					return ['online' => $oAllocation];
				} else {
					return ['offline' => $oAllocation];
				}
			});

			if ($oGrouped->isNotEmpty()) {
				// Bei Onlinekursen muss auch ein Onlineklassenzimmer vorhanden sein
				if ($oGrouped->has('online') && !$this->hasOnlineRooms()) {
					$aErrors[] = 'no_online_room_allocated_students';
				}

				// Bei Offlinekursen muss auch ein Offlineklassenzimmer vorhanden sein
				if ($oGrouped->has('offline') && !$this->hasOfflineRooms()) {
					$aErrors[] = 'no_offline_room_allocated_students';
				}

			}

		}

		// Wenn sich irgendwas an der Vorlage veränddert hat oder die Zuweisungen neu berechnet werden (Alternativfall), muss überprüft werden,
		//   ob die Zuweisungen auch zu den neuen Einstellungen stattfinden können, damit kein Schüler bspw. später überlappt.
		if (
			$this->bCheckAllocations ||
			$this->bUpdateAllocations
		) {
			foreach ($this->getAllocations() as $oAllocation) {
				$mCheck = $this->checkInquiryCourse($oAllocation->inquiry_course_id, $oAllocation->course_id, $oAllocation->room_id);
				if ($mCheck === 'allocated') {
					$aErrors[] = 'students_overlapping';
					break;
				}
			}
		}

		if(!empty($aErrors)) {
			return $aErrors;
		} else {
			return true;
		}

	}

	public function saveSubstituteTeachers($aValues) {

		$aAllocationBlock = array();
	
		foreach((array)$this->days as $iDay) {

			$aTeachersForDay = (array)$aValues[$iDay]['teacher'];

			foreach($aTeachersForDay as $iKey=>$iTeacher) {

				// check if teacher is allocated
				$sSql = "
						SELECT
							ktb.*,
							ktt.name,
							ktt.from,
							ktt.until,
							ktt.lessons,
							ktbst.teacher_id substitute_teacher_id
						FROM
							`kolumbus_tuition_blocks` ktb JOIN
							`kolumbus_tuition_templates` ktt ON
								ktb.template_id = ktt.id JOIN
							`kolumbus_tuition_blocks_days` ktbd ON
								ktbd.block_id = ktb.id LEFT JOIN
							`kolumbus_tuition_blocks_substitute_teachers` `ktbst` ON
								`ktbst`.`block_id` = `ktb`.`id` AND
								`ktbst`.`active` = 1 AND
								(
									TIME_TO_SEC(`ktbst`.`from`) < :until AND
									TIME_TO_SEC(`ktbst`.`until`) > :from
								) AND
								`ktbst`.`day` = :day INNER JOIN
							`ts_teachers` `kt` ON
								(
									`kt`.`id` = `ktbst`.`teacher_id` OR
									`kt`.`id` = `ktb`.`teacher_id`
								) AND
								`kt`.`active` = 1
						WHERE
							ktb.`school_id` = :school_id AND
							ktb.`week` = :week AND
							kt.`id` = :teacher_id AND
							ktb.`active` = 1 AND
							(
								TIME_TO_SEC(ktt.`from`) < :until AND
								TIME_TO_SEC(ktt.`until`) > :from
							) AND
							ktb.id != :block_id AND
							ktbd.day = :day
						";
				$aSql = array();
				$aSql['block_id']	= (int)$this->id;
				$aSql['school_id']	= (int)$this->school_id;
				$aSql['teacher_id']	= (int)$iTeacher;
				$aSql['week']		= $this->week;
				$aSql['day']		= $iDay;
				$aSql['from']		= $aValues[$iDay]['from'][$iKey];
				$aSql['until']		= $aValues[$iDay]['to'][$iKey];
				$aCheck = DB::getPreparedQueryData($sSql, $aSql);
					
				if(!empty($aCheck)){
					$aAllocationBlock = array_merge($aAllocationBlock, $aCheck);
				}

			}
			


			$oWdDate	= new WDDate($this->week, WDDate::DB_DATE);
			$oWdDate->set($iDay,WDDate::WEEKDAY);
			$oAbsence	= new Ext_Thebing_Absence();
			$aEntries	= $oAbsence->getEntries($oWdDate, $oWdDate, array_values($aTeachersForDay), 'teacher');
			if(!empty($aEntries)){
				return 'teacher_absence';
			}
		}
	
		if(!empty($aAllocationBlock)) {
			return array('allocated_block' => $aAllocationBlock);
		}

		// save
		$sSql = "
				DELETE FROM
					kolumbus_tuition_blocks_substitute_teachers
				WHERE
					block_id = :block_id
					";
		$aSql = array();
		$aSql['block_id'] = (int)$this->id;
		DB::executePreparedQuery($sSql, $aSql);

		foreach((array)$this->days as $iDay) {
			foreach((array)$aValues[$iDay]['teacher'] as $iKey=>$iTeacher) {
				$sSql = "
						INSERT INTO
							`kolumbus_tuition_blocks_substitute_teachers`
						SET
							`block_id` = :block_id,
							`day` = :day,
							`teacher_id` = :teacher_id,
							`from` = :from,
							`until` = :until,
							`lessons` = :lessons
							";
				$aSql = array();
				$aSql['block_id'] = (int)$this->id;
				$aSql['day'] = (int)$iDay;
				$aSql['teacher_id'] = (int)$iTeacher;
				$aSql['lessons'] = Ext_Thebing_Format::convertFloat($aValues[$iDay]['lessons'][$iKey]);
				$aSql['from'] = gmdate("H:i:s", $aValues[$iDay]['from'][$iKey]);
				$aSql['until'] = gmdate("H:i:s", $aValues[$iDay]['to'][$iKey]);
				DB::executePreparedQuery($sSql, $aSql);
			}
		}

		return true;
	}

	public function getSubstituteTeachers($iDay=0, $bOnlyIds=false) {

//		if(is_null($this->_aSubTeachers)){
			
			$sSelect = "
						*,
						`from` `time_from`,
						`until` `time_until`,
						TIME_TO_SEC(`from`) `from`,
						TIME_TO_SEC(`until`) `until`
			";
			if($bOnlyIds){
				$sSelect = '`teacher_id`';
			}

			$sSql = "
					SELECT
						".$sSelect."
					FROM
						`kolumbus_tuition_blocks_substitute_teachers`
					WHERE
						`block_id` = :block_id AND
						`active` = 1
						";
			$aSql = array();

			if($iDay>0){
				$sSql .= " AND
					`day` = :day
				";
				$aSql['day'] = $iDay;
			}
			$aSql['block_id'] = (int)$this->id;

			if($bOnlyIds){
				$aTeachers = (array)DB::getQueryCol($sSql, $aSql);
			}else{
				$aTeachers = DB::getPreparedQueryData($sSql, $aSql);
			}

//			$this->_aSubTeachers = $aTeachers;
//		}
//		else{
//			$aTeachers = (array)$this->_aSubTeachers;
//		}

		return $aTeachers;

	}

	public function isReadonly(): bool {
		return ($this->readonly == 1);
	}

	public function hasClass(): bool {
		return ($this->class_id > 0);
	}

	public function hasTeacher() {
		if($this->teacher_id > 0) {
			return true;
		} else {
			return false;
		}
	}

	public function getGroupedBlocks() {

		$aBlockIds = $this->getClass()->getBlocks(strtotime($this->week), true);

		return $aBlockIds;
	}

	public function checkCourseAvailabity ( $iCourseId = null, $sWeek = null, $aDays = array(), $iSchoolId=0 ) {

	 if ( $iCourseId == null ) return (false);
        if ( $sWeek == null )     return (false);
        if ( count($aDays) < 0 )  return (false);

        $oCourse = new Ext_Thebing_Course_Util();
        $oCourse->setCourse($iCourseId);

        foreach ($aDays as $key => $iDay) {
           
			$oDate = new WDDate($sWeek,  WDDate::DB_DATE);
			$oDate->add($iDay-1, WDDate::DAY);

			#$oDateUntil = new WDDate($sWeek,  WDDate::DB_DATE);
			#$oDateUntil->add(6, WDDate::DAY);

			$oAbsence = new Ext_Thebing_Absence();
			$aSchoolHolidaysCheck = $oAbsence->getEntries($oDate, $oDate, $iSchoolId, 'holiday');

			#$iTime = $oDate->get(WDDate::TIMESTAMP);

			if ( !empty($aSchoolHolidaysCheck) && $oCourse->getCourseObject()->schoolholiday != 1 ) {
                return (false);
            }

			// Fehler wird an allen Stellen übersprungen und beim Kopieren verhindert dies das automatische Zuweisen der Schüler #6220
			// Wird der Kunde manuell bei Feiertagen zugewiesen, wird er aktuell ohnehin mit den vollen Lektionen zugewiesen
//            else
//            if ( Ext_Thebing_Holiday_Holiday::isHoliday($oDate, $iSchoolId) && !$oCourse->isAvailableOnHolidays(0) ) {
//                return (false);
//            }
        }

        return (true);

    }

	/**
	 * @return array
	 */
	public function getTimes() {

		$aTimes = [];

		if(
			!empty($this->_aAdditionalData['from']) &&
			!empty($this->_aAdditionalData['until'])
		) {
			$aTimes['from'] = $this->_aAdditionalData['from'];
			$aTimes['until'] = $this->_aAdditionalData['until'];
			return $aTimes;
		} 
		
		$oTemplate = $this->getTemplate();

		if(
			$oTemplate instanceof Ext_Thebing_Tuition_Template && 
			$oTemplate->exist()
		) {
			$aTimes['from'] = $oTemplate->from;
			$aTimes['until'] = $oTemplate->until;
			return $aTimes;
		}

	}

	public function getFormattedTimes() {

		$times = $this->getTimes();
		$formatClass = new \Ext_Thebing_Gui2_Format_Time();

		$formattedTimes = [];
		foreach ($times as $time) {
			$formattedTimes[] = $formatClass->format($time);
		}

		return $formattedTimes;
	}

	/**
	 * @param $mWeek
	 * @param bool $bDontFilterClass
	 * @param bool $sFrom
	 * @param bool $sUntil
	 * @param array $aDays
	 * @return array
	 * @throws Exception
	 */
	public function getAvailableRooms($mWeek, $bDontFilterClass, $sFrom=false, $sUntil=false, $aDays=array()) {

		if ($mWeek instanceof \DateTime) {
			$sWeek = $mWeek->format('Y-m-d');
		} else if(is_numeric($mWeek)) {
			$sWeek = date('Y-m-d', $mWeek);
		} else {
			$sWeek = $mWeek;
		}

		if(empty($sWeek)) {
			$sWeek = $this->week;
		}
		
		if(
			empty($sFrom) ||
			empty($sUntil)
		) {
			$aTimes = $this->getTimes();
			$sFrom = $aTimes['from'];
			$sUntil = $aTimes['until'];
		}

		if(empty($aDays)) {
			$aDays = $this->days;
		}

		$sWhereAddon = "";
		if($bDontFilterClass){
			$sWhereAddon = " AND ktb.class_id != :class_id";
		}

		$sSql = "
				SELECT
					ktbtr.room_id
				FROM
					`kolumbus_tuition_blocks` ktb JOIN
					`kolumbus_tuition_blocks_to_rooms` ktbtr ON
					    `ktbtr`.`block_id` = `ktb`.`id` JOIN
					`kolumbus_tuition_templates` ktt ON
						ktb.template_id = ktt.id JOIN
					`kolumbus_tuition_blocks_days` ktbd ON
						ktbd.block_id = ktb.id
				WHERE
					ktb.`week` = :week AND
					ktb.`active` = 1 AND
					ktb.`school_id` = :school_id AND
					(
						ktt.`from` < :until AND
						ktt.`until` > :from
					) AND
					ktb.id != :block_id AND
					ktbd.day IN (:days)
					".$sWhereAddon."
				GROUP BY
					ktbtr.room_id
			";

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aSql = array();
		$aSql['school_id'] = (int)$oSchool->id;
		$aSql['week'] = $sWeek;
		$aSql['from'] = $sFrom;
		$aSql['until'] = $sUntil;
		$aSql['days'] = $aDays;
		$aSql['block_id'] = (int)$this->id;
		$aSql['class_id'] = (int)$this->class_id;

		$aAllocatedRooms = (array)DB::getQueryCol($sSql, $aSql);

		$aRoomsAll = $oSchool->getClassRooms(true);
		$aRoomsAvailable = array();

		foreach($aRoomsAll as $iRoomId => $sRoomName) {
			if(!in_array($iRoomId, $aAllocatedRooms)) {
				$tags = Ext_Thebing_Tuition_Classroom::getTags($iRoomId);
				if(!empty($tags)) {
					$sRoomName .= ' ('.implode(', ', $tags).')';
				}
				$aRoomsAvailable[$iRoomId] = $sRoomName;
			}
		}

		$aRoomIds = $this->getRoomIds();

		foreach($aRoomIds as $iRoomId) {
            if (
				$iRoomId > 0 &&
                !array_key_exists($iRoomId, $aRoomsAvailable)
            ) {
                $oRoom = Ext_Thebing_Tuition_Classroom::getInstance($iRoomId);
				$tags = Ext_Thebing_Tuition_Classroom::getTags($oRoom->id);
				$sRoomName = $oRoom->getName();
				if(!empty($tags)) {
					$sRoomName .= ' ('.implode(', ', $tags).')';
				}
                $aRoomsAvailable[$oRoom->id] = $sRoomName;
            }
        }

		return $aRoomsAvailable;
	}

	public function getAvailableTeachers($mWeek, $bDontFilterClass, $sFrom=false, $sUntil=false, $aDays=array()) {

		if(is_numeric($mWeek)) {
			$sWeek = date('Y-m-d', $mWeek);
		} else {
			$sWeek = $mWeek;
		}
		
		if(empty($sWeek)) {
			$sWeek = $this->week;
		}
		
		$this->week = $sWeek;
		
		if(
			empty($sFrom) ||
			empty($sUntil)
		) {
			$aTimes = $this->getTimes();
			$sFrom = $aTimes['from'];
			$sUntil = $aTimes['until'];
		}
		
		if(!empty($aDays)) {
			$this->days = $aDays;
		}

		$sWhereAddon = "";
		if($bDontFilterClass) {
			$sWhereAddon = " AND ktb.class_id != :class_id";
		}

		// Bereits zugewiesen
		$sSql = "
				SELECT
					ktb.teacher_id
				FROM
					`kolumbus_tuition_blocks` ktb JOIN
					`kolumbus_tuition_templates` ktt ON
						ktb.template_id = ktt.id JOIN
					`kolumbus_tuition_blocks_days` ktbd ON
						ktbd.block_id = ktb.id
				WHERE
					ktb.`week` = :week AND
					ktb.`active` = 1 AND
					(
						ktt.`from` < :until AND
						ktt.`until` > :from
					) AND
					ktb.id != :block_id AND
					ktbd.day IN (:days)
					".$sWhereAddon."
				GROUP BY
					ktb.teacher_id
			";

		$oSchool			= Ext_Thebing_School::getSchoolFromSession();
		$aSql				= array();
		$aSql['school_id']	= (int)$oSchool->id;
		$aSql['week']		= $sWeek;
		$aSql['from']		= $sFrom;
		$aSql['until']		= $sUntil;
		$aSql['days']		= $this->days;
		$aSql['block_id']	= (int)$this->id;
		$aSql['class_id']	= (int)$this->class_id;

		$aTeachersAllocated = (array)DB::getQueryCol($sSql, $aSql);
		
		// Zeitliche Verfügbarkeit / Abwesenheit
		
		$aWeek = Ext_Thebing_Util::getWeekTimestamps($sWeek);

		$aSql = [];
		$aSql['from'] = $sFrom;
		$aSql['until'] = $sUntil;
		
		$aSql['school_id']	= (int)$oSchool->id;
		$aSql['valid_date'] = date('Y-m-d', $aWeek['start']);
		$aSql['start'] = date('Y-m-d', $aWeek['start']);
		$aSql['end'] = date('Y-m-d', $aWeek['end']);
		
		$aTeacherScheduleOn = [];
		
		$aDayObjects = $this->getDaysAsDateTimeObjects();
		foreach($aDayObjects as $iDay=>$dDayObject) {
			$aTeacherScheduleOn[] = "
				`kts`.`idDay` = ".(int)$iDay." AND 
				`kts`.`timeFrom` <= :from AND
				`kts`.`timeTo` >= :until AND
				(
					`kts`.`valid_from` IS NULL OR 
					`kts`.`valid_from` <= '".$dDayObject->format('Y-m-d')."'
				) AND
				(
					`kts`.`valid_until` IS NULL OR 
					`kts`.`valid_until` >= '".$dDayObject->format('Y-m-d')."'
				)
			";
		}

		if(!empty($aTeacherScheduleOn)) {
			$sTeacherScheduleOn = " AND (".implode(' OR ', $aTeacherScheduleOn).") ";
		} else {
			$sTeacherScheduleOn = "";
		}

		$sSql =  "
				SELECT 
					kt.*,
					COUNT(DISTINCT `kts`.`id`) `schedule`,
					COALESCE(SUM(
						DATEDIFF(
							IF(kab.until > :end, :end, kab.until),
							IF(kab.from < :start, :start, kab.from)
						) + 1
					), 0) `holidays`
				FROM
					`ts_teachers` kt JOIN
					`ts_teachers_to_schools` `ts_ts` ON
						kt.id = `ts_ts`.`teacher_id` LEFT JOIN
					`kolumbus_absence` kab ON
						kt.id = kab.item_id AND
						kab.item = 'teacher' AND
						kab.active = 1 AND
						kab.until >= :start AND
						kab.from <= :end LEFT JOIN 
					`kolumbus_teacher_schedule` `kts` ON
						`kts`.`idTeacher` = `kt`.`id` AND
						`kts`.`active` = 1
						".$sTeacherScheduleOn."
				WHERE
					 `ts_ts`.`school_id` = :school_id AND
					 `kt`.`active` = 1 AND
					 (
						`kt`.`valid_until` >= :valid_date OR
						`kt`.`valid_until` = '0000-00-00'
					 )
				GROUP BY
					kt.id
				ORDER BY 
					`kts`.`priority` ASC,
					`kt`.`lastname` ASC,
					`kt`.`firstname` ASC
					";
		$aTeachersAll = DB::getQueryRows($sSql, $aSql);

		$oFormat = new Ext_Thebing_Gui2_Format_TeacherName();		
		
		$aTeachersAvailable = array();

		foreach($aTeachersAll as $aTeacherData) {
			$iTeacherId = $aTeacherData['id'];
			if(
				$aTeacherData['holidays'] < 7 &&
				$aTeacherData['schedule'] >= count($aDayObjects) &&
				!in_array($iTeacherId, $aTeachersAllocated) && 
				$iTeacherId > 0
			) {
				$aTeachersAvailable[$iTeacherId] = $oFormat->formatByResult($aTeacherData);
			}
		}

		$iTeacherId = (int)$this->teacher_id;

		if(
			!array_key_exists($iTeacherId, $aTeachersAvailable) &&
			$iTeacherId > 0
		) {
			$oTeacher = Ext_Thebing_Teacher::getInstance($iTeacherId);
			$aTeachersAvailable[$oTeacher->id] = $oTeacher->getName();
		}

		return $aTeachersAvailable;
	}

	public function checkIfChanged($aCompareFields=false): array
	{
		if(empty($aCompareFields))
		{
			$aCompareFields = array(
				'teacher_id',
				'rooms',
				'week',
				'template_id',
				'days',
				'description',
			);
		}

		$aCompareFields = (array)$aCompareFields;

		$aChangedFields = [];
		foreach ($aCompareFields as $sField) {
			if(
				!in_array($sField, ['days', 'rooms']) && // Wird unten geprüft
				$this->_aData[$sField] != $this->_aOriginalData[$sField]
			) {
				$aChangedFields[] = $sField;
			}
		}

		if (in_array('days', $aCompareFields)) {

			$aDaysOld		= (array)$this->_aOriginalJoinData['days'];
			$aDays			= (array)$this->days;

			$aDiff1 = array_diff($aDaysOld, $aDays);
			$aDiff2	= array_diff($aDays, $aDaysOld);

			if(
				!empty($aDiff1) || 
				!empty($aDiff2))
			{
				$aChangedFields[] = 'days';
			}	
		}

        if (in_array('rooms', $aCompareFields)) {

            $aRoomsOld		= (array)$this->_aOriginalJoinData['rooms'];
            $aRooms			= (array)$this->rooms;

            if(
                (empty($aRoomsOld) && !empty($aRooms)) ||
                (empty($aRooms) && !empty($aRoomsOld))
            ) {
				$aChangedFields[] = 'rooms';
            } else {

                $aDiff1 = array_diff($aRoomsOld, $aRooms);
                $aDiff2	= array_diff($aRooms, $aRoomsOld);

                if (
                    !empty($aDiff1) ||
                    !empty($aDiff2))
                {
					$aChangedFields[] = 'rooms';
                }
            }
        }

		return array_unique($aChangedFields);
	}

	/**
	 *
	 * @return Ext_Thebing_Tuition_Class
	 */
	public function getClass()
	{
		return $this->getJoinedObject('class');
	}

	public function getInquiriesCourses(\Ext_Thebing_Tuition_Classroom $oRoom = null){

		$aInquiriesCourses = array();

		$aStudents = $this->getStudents($oRoom);

		if(is_array($aStudents) && !empty($aStudents))
		{
			foreach($aStudents as $aStudentData)
			{
				$aInquiriesCourses[] = array(
					'inquiry_course' => $aStudentData['inquiry_course_id'],
					'room_id' => $aStudentData['room_id'],
					'program_service_id' => $aStudentData['program_service_id'],
					'course' => (int)$aStudentData['course_id'],
					'flexible_allocation' => $aStudentData['flexible_allocation']
				);
			}
		}

		return $aInquiriesCourses;
	}
	
	/**
	 * @return Ext_Thebing_Teacher_Payment[]
	 */
	public function getPayments() {

		/** @var Ext_Thebing_Teacher $oTeacher */
		$oTeacher = $this->getJoinedObject('teacher');
		$dWeek = new DateTime($this->week);

		$aPayments = $oTeacher->getLessonPayments($dWeek, $this->id);
		$aPaymentIds = array_map(function($oPayment) {
			return $oPayment->id;
		}, $aPayments);

		// Zahlungen ergänzen, die direkt diesem Block zugewiesen sind (pro Lektion wöchentlich)
		// Wenn man einen Lehrer aus dem Block löscht, ist teacher_id hier bereits 0
		if($this->exist()) {
			$aBlockPayments = Ext_Thebing_Teacher_Payment::searchByBlockIds([$this->id]);
			foreach($aBlockPayments as $oBlockPayment) {
				if(!in_array($oBlockPayment->id, $aPaymentIds)) {
					$aPayments[] = $oBlockPayment;
				}
			}
		}

		return $aPayments;

	}
	
	protected function _isObjectValid(Ext_TC_Basic $oObject)
	{
		$sWeek		= $this->week;
		
		if(
			$sWeek == '0000-00-00' ||
			!WDDate::isDate($sWeek, WDDate::DB_DATE)
		){
			return true;
		}
		
		$aDays		= (array)$this->days;
		$oDate		= new WDDate();
		
		foreach($aDays as $iDay){

			$oDate->set($sWeek, WDDate::DB_DATE);

			$iDaysToAdd = $iDay - 1;

			$oDate->add($iDaysToAdd, WDDate::DAY);

			$sCurrentDate = $oDate->get(WDDate::DB_DATE);

			$bValidity = $oObject->isValid($sCurrentDate);

			if(
				!$bValidity
			){
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * Lehrer und Raum für einen Block freisetzen
	 * @param array $aFields
	 * @param int $iAllWeeks
	 * @return bool
	 */
	public function resetData(array $aFields, int $iAllWeeks = 0): bool
	{
		//Freisetzen nur bei vorhandenen Objekten möglich
		if($this->id <= 0 || empty($aFields)) {
			return false;
		}

		if (in_array('teacher',  $aFields)) {
			$this->teacher_id = 0;
		}
		if (in_array('rooms',  $aFields)) {
			$this->setRoomIds([]);
		}
		if (in_array('description',  $aFields)) {
			$this->description = '';
		}
		$this->save();
		
		if($iAllWeeks==1)
		{
			// Daten für Folgewochen über die Datenbank freisetzen, wegen Performance

			if($this->parent_id > 0) {
				$iParentId	= $this->parent_id;
			} else {
				$iParentId	= $this->id;
			}

			$blockIds = \DB::table('kolumbus_tuition_blocks')
				->select('id')
				->where('active', 1)
				->where('class_id', $this->class_id)
				->where('parent_id', $iParentId)
				->where('week', '>', $this->week)
				->pluck('id')
				// Sicherheitshalber auf mehrere Queries aufteilen. Hier könnten viele Block-IDs kommen und ich weiß nicht
				// wie gut WHERE IN damit umgehen kann
				->chunk(50);

			$blockIds->each(function ($chunkIds) use ($aFields) {

				$aChangeData = [];
				if (in_array('teacher',  $aFields)) {
					$aChangeData['teacher_id'] = 0;
				}

				if (in_array('description',  $aFields)) {
					$aChangeData['description'] = '';
				}

				if (!empty($aChangeData)) {
					\DB::updateData('kolumbus_tuition_blocks', $aChangeData, ['id' => $chunkIds->toArray()]);
				}

				if (in_array('rooms',  $aFields)) {
					\DB::executePreparedQuery(
						"DELETE FROM `kolumbus_tuition_blocks_to_rooms` WHERE `block_id` IN (:block_ids)",
						['block_ids' => $chunkIds->toArray()]
					);
				}
			});

		}
		
		return true;

	}
	
	/**
	 *
	 * @return Ext_Thebing_Teacher 
	 */
	public function getTeacher()
	{
		$oTeacher = $this->getJoinedObject('teacher');
		
		return $oTeacher;
	}
	
	/**
	 *
	 * @return Ext_Thebing_Tuition_Template
	 */
	public function getTemplate()
	{
		$oTemplate = $this->getJoinedObject('template');
		
		return $oTemplate;
	}
	
	public static function getBlocksByTemplate(Ext_Thebing_Tuition_Template $oTemplate)
	{
		$sSql = '
			SELECT
				*
			FROM
				`kolumbus_tuition_blocks`
			WHERE
				`template_id` = :template_id AND
				`active` = 1
		';
		
		$aSql = array(
			'template_id' => (int)$oTemplate->id,
		);
		
		$oDB			= DB::getDefaultConnection();
		$oCollection	= $oDB->getCollection($sSql, $aSql);
		
		return $oCollection;
	}
	
	public function deleteAllocations()
	{
		// Zuweisungen löschen
		array_map(fn ($allocation) => $allocation->delete(), $this->getAllocations());
	}
	
	public function deleteSubstituteTeachers()
	{
		// Ersatzlehrer löschen
		$aSubstituteTeachers = (array)$this->getSubstituteTeachers(0, true);
		
		$sSql = '
			UPDATE
				`kolumbus_tuition_blocks_substitute_teachers`
			SET
				`active` = 0
			WHERE
				`id` IN(:substitute_teacher_ids)
		';
		
		$aSql = array(
			'substitute_teacher_ids' => $aSubstituteTeachers,
		);
		
		DB::executePreparedQuery($sSql, $aSql);
	}

	/**
	 * Alle Zuweisungen mit dem Block-Level synronisieren
	 *
	 * @param bool $bCheckDifferentLevels
	 * @param array $aWeekBlackList
	 * @param int $iSelectedDay
	 * @return array|bool
	 * @throws Exception
	 */
	public function saveProgressForAllocations($bCheckDifferentLevels, array $aWeekBlackList = array()) {

		$aErrors = array();
		$aStudents = $this->getStudents();
		$dCurrentWeek = new DateTime($this->week);
		$iLevel = $this->level_id;

		foreach($aStudents as $aAllocationData) {

			$iInquiryId = (int)$aAllocationData['inquiry_id'];
			$iLevelGroupId = (int)$aAllocationData['courselanguage_id'];
			$sKey = $iInquiryId . '_' . $iLevelGroupId;

			if(!isset($aWeekBlackList[$sKey])) {

				$oJourneyCourse = Ext_TS_Inquiry_Journey_Course::getInstance($aAllocationData['inquiry_course_id']);
				$oLevel = Ext_Thebing_Tuition_Level::getInstance($iLevel);
				$oLevelGroup = Ext_Thebing_Tuition_LevelGroup::getInstance($iLevelGroupId);
				$oProgramService = \TsTuition\Entity\Course\Program\Service::getInstance($aAllocationData['program_service_id']);

				// $iSelectedDay = null, da das ohne taggenaue Abfrage passieren muss
				$mReturn = $oJourneyCourse->saveProgress($dCurrentWeek, $oLevel, $oLevelGroup, $oProgramService, null, $bCheckDifferentLevels);

				if(is_array($mReturn)) {

					if(isset($mReturn['different_level'])) {

						// Falls ein Fehler übermittelt wird, das Blocklevel und Schülerlevel nicht übereinstimmt,
						// dann packen wir das Blocklevel & die BlockId auch noch mit zu, damit wir das in nem Tab
						// Perfekt darstellen können
						$mReturn['different_level']['block_week']	= $this->week;
						$mReturn['different_level']['block_level']	= $iLevel;
						$mReturn['different_level']['class_id']		= $this->getClass()->id;
						
						// Die Fehler so aufbauen, dass später eine Klasse nicht pro Block unnötig viele Schülernamen
						// angezeigt bekommt, da pro Buchung + Levelgruppe nur ein Datensatz pro Woche existieren kann
						$aErrors[$sKey] = $mReturn['different_level'];
					}
					else
					{
						$aErrors[] = $mReturn;
					}

				}
			}
		}
		
		if(empty($aErrors))
		{
			return true;
		}
		else
		{
			return $aErrors;
		}
	}

	/**
     * Nicht mehr benutzen - Räume werden jetzt n:m abgespeichert
     *
     * @deprecated ->getRooms() benutzen
	 * @return Ext_Thebing_Tuition_Classroom
	 */
	public function getRoom()
	{
	    throw new LogicException('The room_id is no longer available in tuition blocks (n:m)! See ->getRooms()');
	}

    /**
     * @return array|Ext_Thebing_Tuition_Classroom[]
     */
	public function getRooms() {
	    $aRooms = $this->getJoinTableObjects('rooms');
		
		// Virtuelle Räume müssen als online gekennzeichnet sein
		if(array_key_exists(-1, $aRooms)) {
			$aRooms[-1]->online = 1;
		}

		return $aRooms;
    }

    public function getRoomIds() {
	    return $this->rooms;
    }

    public function setRoomIds(array $roomIds) {
        $this->rooms = $roomIds;
    }

    public function getOnlineRooms(): Collection {
        return collect($this->getRooms())
            ->filter(function($room) {
                return $room->isOnline();
            });
    }

    public function getOfflineRooms(): Collection {
        return collect($this->getRooms())
            ->filter(function($room) {
                return $room->isOffline();
            });
    }

    public function hasOnlineRooms(): bool {
	    return $this->getOnlineRooms()->isNotEmpty();
    }

    public function hasOfflineRooms(): bool {
        return $this->getOfflineRooms()->isNotEmpty();
    }

	public function hasAttendanceEntries($aCheckDays = [])
	{
		$oIndex			= new Ext_Thebing_Tuition_Attendance_Index();
		$bHasEntries	= $oIndex->hasEntriesWithBlock($this, $aCheckDays);

		return $bHasEntries;
	}

	/**
	 * Der Block ist nur editierbar solange er nur an einem Tag in einem Raum stattfindet
	 *
	 * @param Ext_Thebing_Teacher $teacher
	 * @return bool
	 */
	public function isEditableByTeacher(Ext_Thebing_Teacher $teacher): bool
	{
		if (!$teacher->hasAccessRight(Ext_Thebing_Teacher::ACCESS_CLASS_SCHEDULING_EDIT)) {
			return false;
		}

		if ($this->exist()) {

			if ($this->isLocked()) {
				return false;
			}

			$template = $this->getTemplate();

			if (
				(int)$this->teacher_id !== (int)$teacher->id || // TODO Ersatzlehrer?
				count($this->days) > 1 ||
				count($this->rooms) > 1 ||
				(int)$template->custom === 0
			) {
				return false;
			}
		}

		return true;
	}

	/**
	 * überprüft, ob der Schüler der übergebenen Kurses bereits zu diesem Block
	 * zugwiesen wurde
	 * 
	 * @param Ext_TS_Inquiry_Journey_Course|int $mInquiryCourse
	 * @return boolean
	 */
	public function isDoubleAllocation($mInquiryCourse) {

		if($mInquiryCourse instanceof Ext_TS_Inquiry_Journey_Course) {
			$oInquiryCourse = $mInquiryCourse;
		} else {
			$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance((int)$mInquiryCourse);
		}

		$aBlockedInquiries = array();
		
		$aStudents = $this->getStudents();
		// Array mit allen bereits zugewiesenen Schülern
		foreach($aStudents as $aStudent) {
			$oTempInquiry = Ext_TS_Inquiry::getInstance($aStudent['inquiry_id']);
			$oTempContact = $oTempInquiry->getCustomer();
			$aBlockedInquiries[$oTempContact->id] = $aStudent['inquiry_course_id'];
		}

		$oContact = $oInquiryCourse->getCustomer();

		// Prüfen, ob der Schüler diesem Block bereits zugewiesen wurde
		if(isset($aBlockedInquiries[$oContact->id])) {								
			return true;
		}
		
		return false;
	}

	/**
	 * Alle Kinder-Blöcke (rekursiv und in beide Richtungen) dieses Blocks suchen
	 *
	 * $dUntil ist dafür da, damit bei großen Klassen diese Methode nicht ewig lange braucht!
	 *
	 * @param \DateTime $dFrom
	 * @param \DateTime $dUntil
	 * @return Ext_Thebing_School_Tuition_Block[]
	 */
	public function getRelevantBlocks(\DateTime $dFrom=null, \DateTime $dUntil=null) {

		$aBlocks = [];
		$aBlocks[$this->id] = $this;

		// Wenn Zeitraum vorhanden: Nur Blöcke des Zeitraums betrachten
		$sStartWeek = false;
		$sOperator = '=';
		$aDateRange = [];
		if(
			$dFrom !== null &&
			$dUntil !== null
		) {
			$aDateRange = [$dFrom, $dUntil];
		} elseif($dFrom !== null) {
			$sStartWeek = $dFrom->format('Y-m-d');
			$sOperator = '>=';
		}

		$oClass = $this->getClass();
		$aClassBlocks = $oClass->getBlocks($sStartWeek, false, $sOperator, $aDateRange);

		// Blöcke, die eine parent_id haben, die nicht im Array ist, hinzufügen
		// Das ist wichtig, denn sonst können die zusammenhängenden Blöcke nicht ermittelt werden
		foreach($aClassBlocks as $oClassBlock) {
			if(
				$oClassBlock->parent_id > 0 &&
				!isset($aClassBlocks[$oClassBlock->parent_id])
			) {
				$oBlock = Ext_Thebing_School_Tuition_Block::getInstance($oClassBlock->parent_id);
				if($oBlock->exist()) {
					$aClassBlocks[$oBlock->id] = $oBlock;
				} else {
					throw new RuntimeException('Block with id '.$oClassBlock->parent_id.' does not exist! (requested by block '.$oClassBlock->id.')');
				}
			}
		}

		for($i=0; $i < count($aClassBlocks); $i++) {

			foreach($aClassBlocks as $oClassBlock) {
				if(
					$oClassBlock->parent_id == $this->id ||
					$oClassBlock->id == $this->parent_id ||
					isset($aBlocks[$oClassBlock->parent_id])
				) {
					$aBlocks[$oClassBlock->id] = $oClassBlock;
				}
			}

		}

		uasort($aBlocks, function(Ext_Thebing_School_Tuition_Block $oBlock1, Ext_Thebing_School_Tuition_Block $oBlock2) {
			return new DateTime($oBlock1->week) > new DateTime($oBlock2->week);
		});

		if($dFrom !== null) {
			$aBlocks = array_filter($aBlocks, function(Ext_Thebing_School_Tuition_Block $oBlock) use($dFrom) {
				$dWeek = new DateTime($oBlock->week);
				return $dWeek >= $dFrom;
			});
		}

		return $aBlocks;

	}

	/**
	 * Starttag des Blocks (inklusive Uhrzeit) ermitteln
	 *
	 * @return DateTime
	 */
	public function getStartDay() {

		$oTemplate = $this->getTemplate();
		$aDays = $this->getDaysAsDateTimeObjects();

		 /** @var \Core\Helper\DateTime $dDate */
		$dDate = min($aDays);
		$dDate->modify($oTemplate->from);

		return $dDate;
	}

	/**
	 * Liefert alle Tage des Blocks als DateTime-Objekte
	 *
	 * Da es sich hier um DateTime-Objekte handelt, kann man auch min() und max() benutzen.
	 * Die Methode beachtet außerdem den Starttag der Kurswoche und verschiebt die Tage dementsprechend.
	 *
	 * @deprecated
	 * @see createPeriodCollection()
	 * @return \Core\Helper\DateTime[]
	 */
	public function getDaysAsDateTimeObjects() {

		$aReturn = [];
		$aDays = $this->days;
		$dWeek = new \Core\Helper\DateTime($this->week);
		$oSchool = $this->getSchool();

		sort($aDays);
		foreach($aDays as $iDay) {
			$aReturn[$iDay] = Ext_Thebing_Util::getRealDateFromTuitionWeek($dWeek, $iDay, $oSchool->course_startday);
		}

		return $aReturn;

	}

	/**
	 * Alle Tage des Blocks inkl. Uhrzeiten
	 *
	 * @param bool $withCanceled
	 * @return Period\PeriodCollection
	 */
	public function createPeriodCollection(bool $withCanceled = true): Period\PeriodCollection {

		$school = $this->getSchool();
		$week = Carbon::parse($this->week, $school->getTimezone());
		$template = $this->getTemplate();
		$days = $this->days;
		sort($days);

		$dates = new Period\PeriodCollection();
		foreach ($this->days as $day) {
			if (!$withCanceled && $this->getUnit($day)->isCancelled()) {
				// Nicht stattgefundene Einheiten weglassen
				continue;
			}
			$start = Ext_Thebing_Util::getRealDateFromTuitionWeek($week, $day, $school->course_startday);
			$start->setTimeFromTimeString($template->from);
			$end = $start->clone()->setTimeFromTimeString($template->until);

			$dates[] = Period\Period::make($start, $end, Period\Precision::MINUTE());
		}

		return $dates;
	}

	/**
	 * @inheritdoc
	 */
	public function save($bLog = true) {

		// Wenn Level oder Lehrer aktualisiert: Inquiry hat entsprechende GUI-Spalten, daher aktualisieren
		if(
			$this->_aData['level_id'] != $this->_aOriginalData['level_id'] ||
			$this->_aData['teacher_id'] != $this->_aOriginalData['teacher_id'] ||
			$this->bUpdateAllocations
		) {
			$this->_getStudents();
			$aStudents = $this->getStudents();
			foreach($aStudents as $aStudent) {
				// Eigentlich kann das auch über die Registry klappen, aber eben auch nicht
				Ext_Gui2_Index_Stack::add('ts_inquiry', $aStudent['inquiry_id'], 3);

				// Prinzipiell passiert das schon durch addInquiryCourse(), aber ob diese immer ausgeführt wird, ist fraglich
				// Wenn die Anzahl der Lektionen des Blocks verändert wird, muss auf jeden Fall der Tuition-Index aktualisiert werden!
				if($this->bUpdateAllocations) {
					$oStackRepository = \Core\Entity\ParallelProcessing\Stack::getRepository();
					$oStackRepository->writeToStack('ts/tuition-index', ['inquiry_id' => $aStudent['inquiry_id']], 1);
				}
			}
		}

		$mReturn = parent::save($bLog);

		// in delete() gibt es einen eigenen Hook
		if($mReturn instanceof WDBasic && $this->isActive()) {
			\System::wd()->executeHook('ts_school_tuition_block_save', $this);
		}

		return $mReturn;

	}

	public function getWeekRelativeToLevel() {
		
		$sSql = "
			SELECT 
				week 
			FROM 
				kolumbus_tuition_blocks 
			WHERE 
				class_id = :class_id AND 
				active = 1 AND 
				week < :week AND 
				level_id != :level_id 
			ORDER BY 
				week DESC 
			LIMIT 1";
		
		$aSql = [
			'class_id' => (int)$this->class_id,
			'week' => (string)$this->week,
			'level_id' => (int)$this->level_id
		];
		
		$sWeek = DB::getQueryOne($sSql, $aSql);
		
		return $sWeek;
	}

	/**
	 * Raum korrigieren, wenn Raum nicht im Block existiert
	 *
	 * Das passiert dann, wenn eine Klasse mehrere Blöcke hat und unterschiedliche Räume zugewiesen wurden. Beim
	 * Drag & Drop wird die ID des ausgewählten Raums ermittelt, allerdings wird der Schüler in allen Blöcken der
	 * Klasse zugewiesen (ohne flexible Zuweisung). Hier muss der Raum aber dann nicht übereinstimmen. Früher hatte
	 * die Zuweisung keine room_id.
	 *
	 * @param int $roomId
	 * @return int
	 */
	public function adjustAllocationRoomId(int $roomId): int {

		$roomIds = $this->getRoomIds();

		if (!in_array($roomId, $roomIds)) {
			if (count($roomIds) > 2) {
				// Wenn der Block mehr als einen Raum hat, ist nicht eindeutig, was hier passieren soll. Das sollte auch gar nicht möglich sein und vom Klassendialog abgefangen werden.
				// Achtung: Exception wird mit Text abgefangen
				// 2025-04: Es ist nun möglich 2 unterschiedliche Räume zu haben, falls der Raum in diesem Block nicht verfügbar ist, wird der Raum aus den 2 gewählt, der nicht überall verfügbar ist (uncommon)
				throw new RuntimeException(sprintf('Room does not belong to block and block has more than one room. Block: %d Room: %d', $roomId, $this->id));
			}
			$uncommonRooms = array_values(array_diff($roomIds, \TsTuition\Helper\Rooms::getIntersectingRoomIds($this->getClass(), $this->parent_id ? : $this->id, $this->week)));
			// TODO: Kann nach Testphase entfernt werden
			if (\Util::isDebugIP()) {
				Log::getLogger()->info('adjustAllocationRooms', [
					'roomIds' => $roomIds,
					'common' => \TsTuition\Helper\Rooms::getIntersectingRoomIds($this->getClass(), $this->parent_id ?: $this->id, $this->week),
					'uncommon' => $uncommonRooms
				]);
			}
			if (count($uncommonRooms)) {
				$roomIds = [$uncommonRooms[0]];
			}
			return (int)reset($roomIds); // Keine Räume = room_id 0
		}

		return $roomId;

	}

	public static function getObjectFromArray(array $aData) {
		
		$oObject = parent::getObjectFromArray($aData);
		$oObject->loadAdditionalData();
		
		return $oObject;
	}

	/**
	 * Der Block ist gesperrt sobald es eine Lehrerzahlung gibt
	 *
	 * @return bool
	 */
	public function isLocked(): bool
	{
		if (!$this->exist()) {
			return false;
		}

		$template = $this->getTemplate();

		$teacherPayments = \Ext_Thebing_Teacher_Payment::searchByBlockTemplate($template->id);

		return !empty($teacherPayments);
	}

	public function getUnit(int $day): TsTuition\Entity\Block\Unit
	{
		$unit = $this->getJoinedObjectChildByValue('daily_units', 'day', $day);

		if (!$unit) {
			$unit = $this->getJoinedObjectChild('daily_units');
			$unit->day = $day;
		}

		return $unit;
	}

	public function isLastWeek() {

		$oClass		= $this->getClass();

		$iStartWeek	= $oClass->start_week_timestamp;
		$iAllWeeks	= (int)$oClass->weeks;

		$oWdDate	= new WDDate($this->week, WDDate::DB_DATE);
		$iWeekDiff	= $oWdDate->getDiff(WDDate::WEEK, $iStartWeek, WDDate::TIMESTAMP);

		$iCurrentWeek = $iWeekDiff + 1;
		if($iCurrentWeek<$iAllWeeks) {
			return false;
		} else {
			return true;
		}

	}

	public function getDates() {

		$blockDates	= $this->createPeriodCollection();

		$resultBlockDates = [];
		foreach($blockDates as $blockDate) {

			// Es geht nur um die Tage, deswegen wird durch LocalDate() die Uhrzeit "weggestrichen" und
			// ob ->start() oder ->end() ist in dem Fall egal (gleicher Tag).
			$resultBlockDates[] = Ext_Thebing_Format::LocalDate($blockDate->start());
		}

		return $resultBlockDates;
	}

	public function getDays($language) {

		$days = $this->days;
		$formatDaysArray = Ext_Thebing_Util::getLocaleDays($language, 'wide');

		$resultDays = [];
		foreach ($days as $day) {
			$resultDays[] = $formatDaysArray[$day];
		}

		return $resultDays;
	}

	public function getLevel(): ?Ext_Thebing_Tuition_Level
	{
		if (!empty($this->level_id)) {
			return Ext_Thebing_Tuition_Level::getInstance($this->level_id);
		}

		return null;
	}
}
