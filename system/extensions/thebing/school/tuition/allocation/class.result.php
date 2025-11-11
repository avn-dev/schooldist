<?php

/**
 * @deprecated Sowas in der Art sollte nicht mehr verwendet werden. Ist zwar praktisch, aber nicht anständig mit Objekte zu verwenden.
 */
class Ext_Thebing_School_Tuition_Allocation_Result {

	/**
	 * @var Ext_TS_Inquiry 
	 */
	protected $oInquiry = null;
	
	/**
	 * @var Ext_TS_Inquiry_Journey_Course 
	 */
	protected $oInquiryCourse = null;
	
	/**
	 * @var Ext_Thebing_Tuition_Course
	 */
	protected $oCourse = null;

	/**
	 * @var \TsTuition\Entity\Course\Program\Service
	 */
	protected $oProgramService= null;
	
	/**
	 * @var Ext_Thebing_Tuition_Class 
	 */
	protected $oClass = null;

	/**
	 * @var DateTime 
	 */
	protected $dWeek = null;
	
	/**
	 * @var bool 
	 */
	protected $sGroupedBy = null;
	
	/**
	 * @var bool
	 */
	protected $bBlockWeekSortDesc = false;

	/**
	 * @var array
	 */
	protected $aTimePeriod = [];

	/**
	 * @var int|null
	 */
	protected $iLimit = null;

	/**
	 * @param Ext_TS_Inquiry $oInquiry
	 */
	public function setInquiry(Ext_TS_Inquiry $oInquiry) {
		$this->oInquiry = $oInquiry;
	}

	/**
	 * @param Ext_TS_Inquiry_Journey_Course $oInquiryCourse
	 */
	public function setInquiryCourse(Ext_TS_Inquiry_Journey_Course $oInquiryCourse) {
		$this->oInquiryCourse = $oInquiryCourse;
	}

	/**
	 * @param Ext_Thebing_Tuition_Class $oClass
	 */
	public function setClass(Ext_Thebing_Tuition_Class $oClass) {
		$this->oClass = $oClass;
	}
	
	/**
	 * @param Ext_Thebing_Tuition_Course $oCourse
	 */
	public function setCourse(Ext_Thebing_Tuition_Course $oCourse) {
		$this->oCourse = $oCourse;
	}

	public function setProgramService(\TsTuition\Entity\Course\Program\Service $oService) {
		$this->oProgramService = $oService;
	}

	/**
	 * @param DateTime $oWeek
	 */
	public function setWeek(DateTime $oWeek) {
		$this->dWeek = $oWeek;
	}

	/**
	 * @param int|null $iLimit
	 */
	public function setLimit(int $iLimit = null) {
		$this->iLimit = $iLimit;
	}
	
	/**
	 * @param bool $bGroupedDays
	 */
	public function setGroupedDays($bGroupedDays) {
		if($bGroupedDays) {
			$this->sGroupedBy = 'days';
		}
	}
	
	public function setGroupByWeek() {
		$this->sGroupedBy = 'weeks';
	}
	
	/**
	 * Wenn TRUE wird ktb.week DESC sortiert
	 * @param bool $bBlockWeekSortDesc
	 */
	public function setBlockWeekSortDesc($bBlockWeekSortDesc) {
		$this->bBlockWeekSortDesc = $bBlockWeekSortDesc;
	}

	/**
	 * @param string $sType
	 * @param DateTime $dFrom
	 * @param DateTime $dUntil
	 */
	public function setTimePeriod($sType, $dFrom, $dUntil) {
		$this->aTimePeriod = [$sType, $dFrom, $dUntil];
	}
		
	/**
	 * Sucht anhand gesetzter Einstellungen
	 * @return array
	 */
	public function fetch() {				

		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		$sSubPartSumOfLessons = Ext_Thebing_School_Tuition_Allocation::getSumOfLessonsSubSql('`kic`.`id`', '`ktc`.`id`');												

		$sSelect = "";
		$sWhere = '';

		// check inquiry
		if($this->oInquiry) {
			$aSql['inquiry_id'] = $this->oInquiry->id;
			$sWhere .= ' `ts_i_j`.`inquiry_id` = :inquiry_id AND ';
		}
		// check inquiry course 
		if($this->oInquiryCourse) {
			$aSql['inquiry_course_id'] = $this->oInquiryCourse->id;			
			$sWhere .= ' `ktbic`.`inquiry_course_id` = :inquiry_course_id AND ';
		}
		// check course 
		if($this->oCourse) {
			$aSql['course_id'] = $this->oCourse->id;
			$sWhere .= ' `ktbic`.`course_id` = :course_id AND ';
		}
		// check program service
		if($this->oProgramService) {
			$aSql['program_service_id'] = $this->oProgramService->id;
			$sWhere .= ' `ktbic`.`program_service_id` = :program_service_id AND ';
		}
		if($this->oClass) {
			$aSql['class_id'] = $this->oClass->id;
			$sWhere .= ' `ktcl`.`id` = :class_id AND ';
		}

		// check week
		$sWeek = '';
		if($this->dWeek) {
			$sWeekJoin = '';
			$aSql['week'] = $this->dWeek->format('Y-m-d');
			
			/*if(
				// Wenn der InquiryCourse nicht vorhanden ist muss ein Verknüpfung zu der Inquiry
				// abgefragt werden, da ansonsten die höchste Woche aller Zuweisungen abfragt 
				//  und ggf. eine Woche zurückliefert wird, die beispielsweise in die Ferien eines Schülers fällt
				$this->oInquiry &&
				!$this->oInquiryCourse
			) {
				$sWeekJoin = '
					INNER JOIN  `ts_inquiries_journeys_courses` `ts_ijc2` ON
						`ts_ijc2`.`id` = `ktbic2`.`inquiry_course_id` AND
						`ts_ijc2`.`active` = 1 INNER JOIN  
					`ts_inquiries_journeys` `ta_ij2` ON
						`ta_ij2`.`id` = `ts_ijc2`.`journey_id` AND
						`ta_ij2`.`active` = 1 AND
						`ta_ij2`.`inquiry_id` = :inquiry_id
				';
			}*/
			
			$sWeek = 'AND
				`ktb`.`week` <= :week ';
		}
	
		// where statement
		// @TODO Ist es richtig, dass WHERE nur eingebaut wird, wenn es auch ein anderes WHERE gibt (sollte diese Abfrage ganz weg)?
		if($sWhere !== '') {
			$sWhere = "
				WHERE
					".$sWhere."
					`ktbic`.`active` = 1 AND
					/* Hier muss geprüft werden, ob der tatsächliche Tag in den Kurszeitraum fällt */
					getRealDateFromTuitionWeek(
						`ktb`.`week`,
						`ktbd`.`day`,
						`cdb2`.`course_startday`
					) BETWEEN `kic`.`from` AND `kic`.`until`
			";
		}
		// grouped days 
		if($this->sGroupedBy === 'days') {
			$sSelect = ', GROUP_CONCAT(`ktbd`.`day` ORDER BY `ktbd`.`day` ASC) `days` ';
			$sGroupBy = ' GROUP BY `ktb`.`id` ';
		} elseif($this->sGroupedBy === 'weeks') {
			$sGroupBy = ' GROUP BY `ktb`.`id`, `ktb`.`week` ';
		} else {
			// Muss wegen Vertretungslehrer-Einträgen immer gruppiert werden
			$sGroupBy = ' GROUP BY `ktb`.`id`, `ktbd`.`day` ';
		}

		$sHaving = '';
		if(
			!empty($this->aTimePeriod) &&
			$this->aTimePeriod[0] === 'block_day'
		) {
			// Zeitperiode: Nur Block-Tage, die in den Zeitraum reinfallen
			$aSql['period_from'] = $this->aTimePeriod[1]->format('Y-m-d');
			$aSql['period_until'] = $this->aTimePeriod[2]->format('Y-m-d');
			$sHaving = ' HAVING `block_day_date` BETWEEN :period_from AND :period_until';
		}

		// order 
		$sBlockWeekSort = 'ASC';
		if($this->bBlockWeekSortDesc) {
			$sBlockWeekSort = 'DESC';
		}

		// Tage nach Starttag des Kurses sortieren
		if($oSchool->course_startday == 1) {
			$sDayOrderBy = " `ktbd`.`day` ";
		} else {
			$aDays = array();
			for($i=0; $i < 7; $i++) {
				$iDay = $i + $oSchool->course_startday;
				if($iDay > 7) {
					$iDay -= 7;
				}
				$aDays[] = $iDay;
			}

			$sDayOrderBy = " FIELD(`ktbd`.`day`, ".join(',', $aDays).")";
		}

		$sSql = 
			"SELECT 
				`ktc`.#name_field `course_name`,
				`ktt`.`lessons` `allocated_lessons`,
				`ktc`.`per_unit`,
				`kic`.`from` `inquiry_course_from`,
				`kic`.`weeks` `inquiry_course_weeks`,
				`ktcl`.`start_week`,
				`ktcl`.`weeks` `class_weeks`,
				`ktcl`.`id` `class_id`,
				`ktb`.`id` `block_id`,				
				`ktc`.`id` `course_id`,
				`ktbd`.`day` `day`,
				`ktb`.`week` + INTERVAL (`ktbd`.`day` - 1) DAY `block_day_date`,
				`ktc`.`name_short` `course_short`,
				`ts_tctc`.`courselanguage_id` `courselanguage_id`,
				`ktcl`.`lesson_duration`,
				`ktbic`.`id` `block_allocation_id`,
				`ktbic`.`inquiry_course_id` `inquiry_course_id`,
				`ktbic`.`program_service_id` `program_service_id`,
				`ktbic`.`room_id` `classroom_id`,
				`ktb`.`week` `block_week`,
				`ktb`.`description` `block_description`,
				`ktb`.`description_student` `block_description_student`,
				`ktt`.`from` `block_from`,
				`ktt`.`until` `block_until`,												
				`kc`.`name` `classroom`,
				`ktcl`.`name` `class_name`,
				`kt`.`id` `teacher_id`,
				CONCAT(`kt`.`lastname`, ', ', `kt`.`firstname`) `teacher_name`,
				`kt`.`lastname` `teacher_lastname`,
				`kt`.`firstname` `teacher_firstname`,
				`kic`.`units`,
				`ts_ijclc`.`lessons` `course_lessons`,
				IF(
					`ts_ijclc`.`lessons_unit` = 'absolute',
					(
						" . $sSubPartSumOfLessons . '
					),
					0
				) `allocated_units`,
				`ktul_block`.`name_short` `block_level`,
				`ktp`.`level` `progress_level`,
				`cdb2`.`tuition_excused_absence_calculation`,
				ktbic.automatic '
				. $sSelect . "
			FROM 
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` INNER JOIN 
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`id` = `ktbic`.`block_id` AND
					`ktb`.`active` = 1 
					" . $sWeek . " INNER JOIN
				`ts_inquiries_journeys_courses` `kic` ON
					`kic`.`id` = `ktbic`.`inquiry_course_id` AND					
					`kic`.`active` = 1 AND
					`kic`.`visible` = 1 INNER JOIN
				`ts_inquiries_journeys_courses_lessons_contingent` `ts_ijclc` ON
					`ts_ijclc`.`journey_course_id` = `ktbic`.`inquiry_course_id` AND
					`ts_ijclc`.`program_service_id` = `ktbic`.`program_service_id` INNER JOIN
				`ts_inquiries_journeys` `ts_i_j` ON
					`ts_i_j`.`id` = `kic`.`journey_id` AND
					`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_i_j`.`active` = 1 INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ts_i_j`.`school_id` INNER JOIN
				`kolumbus_tuition_templates` `ktt` ON
					`ktt`.`id` = `ktb`.`template_id` AND
					`ktt`.`active` = 1 INNER JOIN
				`kolumbus_tuition_classes` `ktcl` ON
					`ktcl`.`id` = `ktb`.`class_id` LEFT JOIN
				`kolumbus_classroom` `kc` ON
					`kc`.`id` = `ktbic`.`room_id` INNER JOIN
				`kolumbus_tuition_courses` `ktc` ON
					`ktc`.`id` = `ktbic`.`course_id` LEFT JOIN
				`ts_tuition_courses_to_courselanguages` `ts_tctc` ON
					`ts_tctc`.`course_id` = `ktc`.`id` LEFT JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
					`ktbd`.`block_id` = `ktb`.`id` LEFT JOIN
				`kolumbus_tuition_blocks_substitute_teachers` `ktbst` ON
					`ktbst`.`block_id` = `ktb`.`id` AND
					`ktbst`.`day` = `ktbd`.`day` AND
					`ktbst`.`active` = 1 LEFT JOIN
				`ts_teachers` `kt` ON
					`kt`.`id` = IF(
						`ktbst`.`lessons` >= `ktt`.`lessons`,
						`ktbst`.`teacher_id`,
						`ktb`.`teacher_id`
					) LEFT JOIN
				`ts_tuition_levels` `ktul_block` ON
					`ktul_block`.`id` = `ktb`.`level_id` LEFT JOIN
				`kolumbus_tuition_progress` `ktp` ON
					`ktp`.`courselanguage_id` = `kic`.`courselanguage_id` AND
					`ktp`.`inquiry_id` = `ts_i_j`.`inquiry_id` AND
					`ktp`.`active` = 1 AND
					`ktp`.`week` = (
						SELECT
							MAX(`ktp_sub`.`week`)
						FROM
							`kolumbus_tuition_progress` `ktp_sub`
						WHERE
							`ktp_sub`.`courselanguage_id` = `kic`.`courselanguage_id` AND
							`ktp_sub`.`inquiry_id` = `ts_i_j`.`inquiry_id` AND
							`ktp_sub`.`active` = 1 AND
							`ktp_sub`.`week` <= `ktb`.`week`
					)
				". $sWhere . ' '
				. $sGroupBy . ' '
				. $sHaving .
				' ORDER BY
					`block_week` ' . $sBlockWeekSort . ', 
					'.$sDayOrderBy.' ASC,
					`block_from` ASC ';

		if(!empty($this->iLimit)) {
			$sSql .= ' LIMIT '.$this->iLimit;
		}

		// course_name
		$sInterfaceLanguage	= $oSchool->getInterfaceLanguage();
		$aSql['name_field'] = 'name_' . $sInterfaceLanguage;

		$aResult = DB::getPreparedQueryData($sSql, $aSql);

		return $aResult;
	}
	
}
