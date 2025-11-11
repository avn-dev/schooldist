<?php

/**
 * @TODO Welchen Zweck hat teacher_id?
 *
 * @TODO Diese Entität stellt eine 1:1-Beziehung zu Ext_Thebing_School_Tuition_Allocation dar und
 * 		enthält viele redundante Werte aus der Zuweisung und dem Block.
 *
 * @property int $id
 * @property $created
 * @property $changed
 * @property int $active
 * @property int $creator_id
 * @property int $user_id
 * @property float $mo
 * @property float $di
 * @property float $mi
 * @property float $do
 * @property float $fr
 * @property float $sa
 * @property float $so
 * @property int $excused BIT
 * @property string $score
 * @property string $comment
 * @property int $allocation_id
 * @property int $inquiry_id
 * @property int $journey_course_id
 * @property int $program_service_id
 * @property int $teacher_id
 * @property string $week DATE
 * @property int $course_id
 * @property string $student_login DATE
 * @property array absense_reasons
 * @method static \Ext_Thebing_Tuition_AttendanceRepository getRepository()
 */
class Ext_Thebing_Tuition_Attendance extends Ext_Thebing_Basic {

	const DAY_MAPPING = [
		1 => 'mo',
		2 => 'di',
		3 => 'mi',
		4 => 'do',
		5 => 'fr',
		6 => 'sa',
		7 => 'so',
	];
	
	protected $_sTable = 'kolumbus_tuition_attendance';

	protected $_sTableAlias = 'kta';

	protected $_aFormat = [
		'allocation_id' => [
			'validate' => 'INT_POSITIVE'
		],
		'inquiry_id' => [
			'validate' => 'INT_POSITIVE'
		],
		'journey_course_id' => [
			'validate' => 'INT_POSITIVE'
		],
		'week' => [
			'required' => 'true'
		],
		'program_service_id' => [
			'validate' => 'INT_POSITIVE'
		],
		'duration' => [
			'validate' => 'FLOAT_NOTNEGATIVE'
		],
		'attended' => [
			'validate' => 'FLOAT_NOTNEGATIVE'
		],
		'absence_reasons' => [
			'format' => 'JSON'
		]
	];

	protected $_aJoinedObjects = array(
		'allocation'	=> array(
			'class'	=> 'Ext_Thebing_School_Tuition_Allocation',
			'key'	=> 'allocation_id',
		),
	);

	protected $_aAttributes = array(
		'student_login_release' => array(
			'class' => 'WDBasic_Attribute_Type_TinyInt',
		)
	);

	public function __get($sField){

		Ext_Gui2_Index_Registry::set($this);
		
		if(
			$sField == 'contact_name' ||
			$sField == 'customer_name'
		) {

			$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance($this->id);
			$oInquiry		= Ext_TS_Inquiry::getInstance($oInquiryCourse->inquiry_id);
			$oCustomer		= $oInquiry->getCustomer();
			$mValue			= $oCustomer->getName();

			return $mValue;
		}

		return parent::__get($sField);
	}

	/**
	 * @param int $iInquiryCourseId
	 * @param bool $bExpected
	 * @return float
	 */
	public static function getAttendanceForInquiryCourse($iInquiryCourseId, $bExpected = false) {

		$oJourneyCourse		= Ext_TS_Inquiry_Journey_Course::getInstance($iInquiryCourseId);
		
		$oAttendanceIndex	= new Ext_Thebing_Tuition_Attendance_Index();
		
		return $oAttendanceIndex->getAttendanceForJourneyCourse($oJourneyCourse, true, ['expected' => $bExpected]);

	}

	public static function getAttendanceForInquiry($iInquiryId, $bExpected = false)
	{
		$oInquiry			= Ext_TS_Inquiry::getInstance($iInquiryId);
		
		$oAttendanceIndex	= new Ext_Thebing_Tuition_Attendance_Index();
		
		return $oAttendanceIndex->getAttendanceForInquiry($oInquiry, true, ['expected' => $bExpected]);
	}

	public static function getAttendanceForInquiryCourseProgramService(Ext_TS_Inquiry_Journey_Course $oJourneyCourse, \TsTuition\Entity\Course\Program\Service $oProgramService, $bExpected = false) {

		$oAttendanceIndex	= new Ext_Thebing_Tuition_Attendance_Index();

		return $oAttendanceIndex->getAttendanceForJourneyCourseProgramService($oJourneyCourse, $oProgramService, true, ['expected' => $bExpected]);

	}

	public static function getAverageScoreForInquiryCourse($iInquiryCourseId, $iProgramServiceId)
	{
		$sSql = "
			SELECT
				`score`
			FROM
				`kolumbus_tuition_attendance` `kta` INNER JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
					`ktbic`.`id` = `kta`.`allocation_id` AND
					`ktbic`.`active` = 1
			WHERE
				`kta`.`active` = 1 AND
				`ktbic`.`inquiry_course_id` = :inquiry_course_id AND
				`ktbic`.`program_service_id` = :program_service_id
		";

		$aSql = array(
			'inquiry_course_id' => $iInquiryCourseId,
			'program_service_id' => $iProgramServiceId,
		);

		$aResult	= DB::getQueryCol($sSql, $aSql);

		$fAverage	= Ext_Thebing_Util::getAverageFromFormattedValue($aResult);
		
		return $fAverage;
	}

	/**
	 * @param $mInquiryCourse
	 * @return array
	 * @throws Exception
	 */
	public static function getAttendanceDataForInquiryCourse($mInquiryCourse){

		if(is_numeric($mInquiryCourse)){
			$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance($mInquiryCourse);
		}elseif(
			is_object($mInquiryCourse) &&
			$mInquiryCourse instanceof Ext_TS_Inquiry_Journey_Course
		){
			$oInquiryCourse = $mInquiryCourse;
		}else{
			return array();
		}

		$aData				= array();
		$iInquiryCourseId	= (int)$oInquiryCourse->id;
		$dFrom				= $oInquiryCourse->from;
		$iWeeks				= $oInquiryCourse->weeks;
		$oDateFormat		= new Ext_Thebing_Gui2_Format_Date();
		
//		$oFormatPercent		= new Ext_Thebing_Gui2_Format_Tuition_Attendance_Percent();

		if(WDDate::isDate($dFrom, WDDate::DB_DATE)){

			$oDate	= new WDDate($dFrom, WDDate::DB_DATE);
			$aWeek	= Ext_Thebing_Util::getWeekTimestamps($oDate->get(WDDate::TIMESTAMP));
			$oDate->set($aWeek['start'], WDDate::TIMESTAMP);

			for($iCounter=0;$iCounter < $iWeeks; $iCounter++){

				$iWeekNum = $oDate->get(WDDate::WEEK);

				$dFilterFrom = $oDate->get(WDDate::DB_DATE);
				$oDate->add(6, WDDate::DAY);
				$dFilterUntil = $oDate->get(WDDate::DB_DATE);


				
				#$fAttendanceForInquiryCourseWeek = self::getAttendanceForInquiryCourse($iInquiryCourseId);

				$fAttendanceForInquiryCourseWeek  = $oInquiryCourse->getAttendance($dFilterFrom, $dFilterUntil);

//				$sAttendanceForInquiryCourseWeek = $oFormatPercent->format($fAttendanceForInquiryCourseWeek);

				$aData[] = array(
//					'attendance'	=> $sAttendanceForInquiryCourseWeek,
					'attendance'	=> $fAttendanceForInquiryCourseWeek,
					'week_num'		=> $iWeekNum,
					'from'			=> $dFilterFrom,
					'until'			=> $dFilterUntil,
				);

				$oDate->add(1, WDDate::DAY);
			}
		}

		return $aData;
	}

	/**
	 * @param $mInquiryCourse
	 * @param \Tc\Service\LanguageAbstract $oLanguage
	 * @return string
	 */
	public static function getAttendanceTableForInquiryCourse($mInquiryCourse, \Tc\Service\LanguageAbstract $oLanguage) {
		
		if(is_numeric($mInquiryCourse)){
			$oInquiryCourse = Ext_TS_Inquiry_Journey_Course::getInstance($mInquiryCourse);
		}elseif(
			is_object($mInquiryCourse) &&
			$mInquiryCourse instanceof Ext_TS_Inquiry_Journey_Course
		){
			$oInquiryCourse = $mInquiryCourse;
		}else{
			return '';
		}

		$oCourse			= $oInquiryCourse->getCourse();
		$sCourse			= $oCourse->name_short;
		$aAttendanceData	= self::getAttendanceDataForInquiryCourse($oInquiryCourse);
		
		$sTable = '';

		if(!empty($aAttendanceData)) {

			$sTable .= '<table>';

			$sTable .= '<tr>';
			$sTable .= '<th>'.$sCourse.'</th>';
			$sTable .= '</tr>';

			$oDateFormat = new Ext_Thebing_Gui2_Format_Date();

			$dNow = new DateTime();

			foreach($aAttendanceData as $aData) {

				// Prüfen ob die ein Eintrag nicht zukünftig ist, ansonsten muss er entfernt werden.
				$dUntil = new DateTime($aData['until']);

				if($dUntil > $dNow) {
					continue;
				}

				$sFrom = $oDateFormat->formatByValue($aData['from']);
				$sUntil = $oDateFormat->formatByValue($aData['until']);

				$sWeek = $oLanguage->translate('Woche').' '.$aData['week_num'];
				$sPeriod = $sFrom.' - '.$sUntil;
				$fAttendance = $aData['attendance'];
				
				if($fAttendance === null) {
					$fAttendance = $oLanguage->translate('N/A');
				} elseif(is_numeric($fAttendance)) {
					$fAttendance = round($fAttendance,2).'%';
				}

				$sString = $sWeek.', '.$sPeriod.', '.$fAttendance;

				$sTable .= '<tr>';
				$sTable .= '<td>'.$sString.'</td>';
				$sTable .= '</tr>';
			}

			$sTable .= '</table>';
		}

		return $sTable;
	}

	/**
	 * Liefert die Anzahl der besuchten Lektionen
	 *
	 * Da hier auch Kombinationskurse berücksichtigt werden müssen,
	 * 	können nicht die normalen Query-Teile der Anwesenheit benutzt werden.
	 * Der Subquery dort verhindert nämlich eine Summierung.
	 *
	 * @param Ext_TS_Inquiry_Journey_Course[] $aInquiryCourses
	 * @return null|float
	 */
	public static function getAttendanceLessonsForInquiryCourses(array $aInquiryCourses) {

		$sSelectPart = self::getAttendanceSqlPartDaySelect();
		$fLessonsTotal = 0;
		$fLessons = null;

		foreach($aInquiryCourses as $oInquiryCourse) {
			$aSql = array(
				'journey_course_id' => $oInquiryCourse->id
			);

			$sSql = "
				SELECT
					SUM(`sub`.`lessons`)
				FROM (
					SELECT
						IF(
							`sub_sub`.`sum_lessons` = `block_lesson_duration`,
							0, -- Wenn summierte Abwesenheit genauso groß wie `ktbic`.`lesson_duration`, dann 0 Lektionen
							IF(
								`sub_sub`.`sum_lessons` = 0,
								`block_lesson_duration` / `class_lesson_duration`, -- Wenn keine Abwesenheit eingetragen, dann alle Lektionen
								`sub_sub`.`sum_lessons` / `class_lesson_duration` -- Ansonsten besuchte Lektionen errechnen
							)
						) `lessons`
					FROM (
						SELECT
							{$sSelectPart} `sum_lessons`,
							`ktbic`.`lesson_duration` `block_lesson_duration`,
							`ktcl`.`lesson_duration` `class_lesson_duration`
						FROM
							`kolumbus_tuition_attendance` `kta` INNER JOIN
							`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
								`ktbic`.`id` = `kta`.`allocation_id` AND
								`ktbic`.`active` = 1 INNER JOIN
							`kolumbus_tuition_blocks` `ktb` ON
							    `ktb`.`id` = `ktbic`.`block_id` AND
							    `ktb`.`active` = 1 INNER JOIN
							`kolumbus_tuition_classes` `ktcl` ON
							    `ktcl`.`id` = `ktb`.`class_id` AND
							    `ktcl`.`active` = 1 INNER JOIN
							`customer_db_2` `cdb2` ON
								`cdb2`.`id` = `ktb`.`school_id`
						WHERE
							`kta`.`journey_course_id` = :journey_course_id AND
							`kta`.`active` = 1
						GROUP BY
							`kta`.`id`
					) `sub_sub`
				) `sub`
			";

			$mResult = DB::getQueryOne($sSql, $aSql);
			if(is_numeric($mResult)) {
				$fLessonsTotal += $mResult;
			}
		}

		if($fLessonsTotal > 0) {
			$fLessons = $fLessonsTotal;
		}

		// Null, wenn keine Anwesenheit eingetragen!
		return $fLessons;
	}

	/**
	 * @return string
	 */
	public static function getAttendanceSqlPartDaySelect() {

		return "
			SUM(
				IFNULL(IF(`excused` & 1, 0, `mo`), 0) +
				IFNULL(IF(`excused` & 2, 0, `di`), 0) +
				IFNULL(IF(`excused` & 4, 0, `mi`), 0) +
				IFNULL(IF(`excused` & 8, 0, `do`), 0) +
				IFNULL(IF(`excused` & 16, 0, `fr`), 0) +
				IFNULL(IF(`excused` & 32, 0, `sa`), 0) +
				IFNULL(IF(`excused` & 64, 0, `so`), 0)
			)
		";

	}

	/**
	 * @param string $sView
	 * @param array $aFields
	 * @return string
	 */
	public static function getAttendanceSql($sView, array $aFields) {

		$sSelectPart = self::getAttendanceSqlPartDaySelect();
		$sWhere = self::getWhereSql($sView, $aFields);

		// Anzahl der besuchten Lektionen in Prozent
		$sSelect = "
			IF(
				SUM(`ktbic_sub`.`lesson_duration`) > 0,
				100 -
					(
						{$sSelectPart} / SUM(`ktbic_sub`.`lesson_duration`) * 100
					),
				NULL
			)
		";

		if(empty($aFields['expected'])) {
			// Normale Anwesenheit: Ausgehend von eingetragenen Anwesenheiten pro Woche
			$sSql = "
				/* NORMALE ANWESENHEIT */
				SELECT
					IF(
						SUM(`kta_sub`.`duration`) > 0,
						SUM(`kta_sub`.`attended`) / ( 
							SUM(`kta_sub`.`duration`) - IF(
								`cdb2_sub`.`tuition_excused_absence_calculation` = 'exclude',
								SUM(
									IFNULL(IF( `kta_sub`.`excused` & 1, `mo`, 0), 0) +
									IFNULL(IF( `kta_sub`.`excused` & 2, `di`, 0), 0) +
									IFNULL(IF( `kta_sub`.`excused` & 4, `mi`, 0), 0) +
									IFNULL(IF( `kta_sub`.`excused` & 8, `do`, 0), 0) +
									IFNULL(IF( `kta_sub`.`excused` & 16, `fr`, 0), 0) +
									IFNULL(IF( `kta_sub`.`excused` & 32, `sa`, 0), 0) +
									IFNULL(IF( `kta_sub`.`excused` & 64, `so`, 0), 0)
								),
							0
							)
						 ) * 100, 
					NULL) `sum_lessons`
				FROM
					`kolumbus_tuition_attendance` `kta_sub` INNER JOIN
					`kolumbus_tuition_blocks_inquiries_courses` `ktbic_sub` ON
						`ktbic_sub`.`id` = `kta_sub`.`allocation_id` AND
						`ktbic_sub`.`active` = 1 INNER JOIN
					`kolumbus_tuition_blocks` `ktb_sub` ON
						`ktb_sub`.`id` = `ktbic_sub`.`block_id` AND
						`ktb_sub`.`active` = 1 INNER JOIN
					`customer_db_2` `cdb2_sub` ON
						`cdb2_sub`.`id` = `ktb_sub`.`school_id`
				WHERE
					`kta_sub`.`active` = 1 AND
					{$sWhere}	
			";
		} else {
			// Erwartete Anwesenheit: Ausgehend von Zuweisungen
			$sSql = "
				/* ERWARTETE ANWESENHEIT */
				SELECT
					{$sSelect} `sum_lessons`
				FROM
					`kolumbus_tuition_blocks_inquiries_courses` `ktbic_sub` INNER JOIN
					`kolumbus_tuition_blocks` `ktb_sub` ON
						`ktb_sub`.`id` = `ktbic_sub`.`block_id` AND
						`ktb_sub`.`active` = 1 INNER JOIN
					`customer_db_2` `cdb2_sub` ON
						`cdb2_sub`.`id` = `ktb_sub`.`school_id` LEFT JOIN
					`kolumbus_tuition_attendance` `kta_sub` ON
						`kta_sub`.`allocation_id` = `ktbic_sub`.`id` AND
						`kta_sub`.`journey_course_id` = `ktbic_sub`.`inquiry_course_id` AND
						`kta_sub`.`active` = 1 
				WHERE
					`ktbic_sub`.`active` = 1 AND
					{$sWhere}
					
			";
		}
		
		return $sSql;
	}
	
	protected static function getWhereSql($sView, array $aFields) {
		if($sView == 'journey_course_teacher') {
			if(
				!isset($aFields['journey_course_id']) ||
				!isset($aFields['teacher_id']) ||
				!isset($aFields['block_id'])
			) {
				throw new Exception('You have to define journey_course_id, teacher_id and block_id for this view!');
			}

			// block_id wichtig für Kombinationskurse, da diese ansonsten zusammen summiert werden
			$sWhere = '
				`kta_sub`.`journey_course_id` = '.$aFields['journey_course_id'].' AND
				`ktbic_sub`.`id` = '.$aFields['block_id'].' AND
				`ktb_sub`.`teacher_id` = '.$aFields['teacher_id'].'
			';
		} elseif($sView == 'journey_course') {
			if(
				!isset($aFields['journey_course_id']) ||
				!isset($aFields['course_id'])
			) {
				throw new Exception('You have to define journey_course_id and course_id for this view!');
			}

			// course_id wichtig für Kombinationskurse, da diese ansonsten zusammen summiert werden
			if(empty($aFields['expected'])) {
				$sWhere = '
					`kta_sub`.`journey_course_id` = '.$aFields['journey_course_id'].' AND
					`ktbic_sub`.`course_id` = '.$aFields['course_id'].'
				';
			} else {
				$sWhere = "
					`ktbic_sub`.`inquiry_course_id` = {$aFields['journey_course_id']} AND
					`ktbic_sub`.`course_id` = {$aFields['course_id']}
				";
			}

		} elseif($sView == 'journey_course_block') {
			if(
				!isset($aFields['journey_course_id']) ||
				!isset($aFields['block_id'])
			) {
				throw new Exception('You have to define journey_course_id and block_id for this view!');
			}

			// course_id wichtig für Kombinationskurse, da diese ansonsten zusammen summiert werden
			if(empty($aFields['expected'])) {
				$sWhere = '
					`kta_sub`.`journey_course_id` = '.$aFields['journey_course_id'].' AND
					`ktbic_sub`.`id` = '.$aFields['block_id'].'
				';
			} else {
				$sWhere = "
					`ktbic_sub`.`inquiry_course_id` = {$aFields['journey_course_id']} AND
					`ktbic_sub`.`id` = ".$aFields['block_id']."
				";
			}

		} elseif($sView == 'journey_course_program_service') {
			if(
				!isset($aFields['journey_course_id']) ||
				!isset($aFields['program_service_id'])
			) {
				throw new Exception('You have to define journey_course_id and program_service_id for this view!');
			}

			$journeyCourseComparison = (str_starts_with($aFields['journey_course_id'], 'RAW:'))
				? substr($aFields['journey_course_id'], 4)
				: '= '.$aFields['journey_course_id'];

			if(empty($aFields['expected'])) {
				$sWhere = '
					`kta_sub`.`journey_course_id` '.$journeyCourseComparison.' AND
					`kta_sub`.`program_service_id` = '.$aFields['program_service_id'].' 
				';
			} else {
				$sWhere = "
					`ktbic_sub`.`inquiry_course_id` {$journeyCourseComparison} AND
					`ktbic_sub`.`program_service_id` = {$aFields['program_service_id']} 
				";
			}


		} elseif($sView === 'journey_course_only') {

			if(!isset($aFields['journey_course_id'])) {
				throw new Exception('You have to define journey_course_id for this view!');
			}

			if(empty($aFields['expected'])) {
				$sWhere = '
					`kta_sub`.`journey_course_id` = '.$aFields['journey_course_id'].'
				';
			} else {
				$sWhere = "
					`ktbic_sub`.`inquiry_course_id` = {$aFields['journey_course_id']}
				";
			}

		} elseif($sView == 'inquiry') {

			if(!isset($aFields['inquiry_id'])) {
				throw new Exception('You have to define inquiry_id for this view!');
			}

			if(empty($aFields['expected'])) {
				if(empty($aFields['multiple'])) {
					$sWhere = " `kta_sub`.`inquiry_id` = {$aFields['inquiry_id']} ";
				} else {
					// Kundenübergreifende Anwesenheit #10613
					$sWhere = " `kta_sub`.`inquiry_id` IN (".join(',', $aFields['inquiry_id']).") ";
				}
			} else {
				if(!empty($aFields['multiple'])) {
					throw new BadMethodCallException('Option expected is not implemented');
				}

				// TODO In der ktbic gibt es keine inquiry_id, in der kta hierwegen schon
				$sWhere = "
					`ktbic_sub`.`inquiry_course_id` IN (
						SELECT
							`ts_ijc_sub`.`id`
						FROM
							`ts_inquiries` `ts_i_sub` INNER JOIN
							`ts_inquiries_journeys` `ts_ij_sub` ON
								`ts_ij_sub`.`inquiry_id` = `ts_i_sub`.`id` AND
								`ts_ij_sub`.`active` = 1 AND
								`ts_ij_sub`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' INNER JOIN
							`ts_inquiries_journeys_courses` `ts_ijc_sub` ON
								`ts_ijc_sub`.`journey_id` = `ts_ij_sub`.`id` AND
								`ts_ijc_sub`.`active` = 1
						WHERE
							`ts_i_sub`.`id` = {$aFields['inquiry_id']}
					)
				";
			}

		} elseif($sView === 'inquiry_teacher') {

			if(
				!isset($aFields['inquiry_id']) ||
				!isset($aFields['teacher_id'])
			) {
				throw new Exception('You have to define inquiry_id and teacher_id for this view!');
			}

			$sWhere = '
				`kta_sub`.`inquiry_id` = '.$aFields['inquiry_id'].' AND
				`ktb_sub`.`teacher_id` = '.$aFields['teacher_id'].'
			';

		} else {
			throw new Exception("View '$sView' not supported for attendance!");
		}
		
		if(isset($aFields['week_filter']))
		{
			$aWeekFilter = (array)$aFields['week_filter'];
			
			if(isset($aWeekFilter['week']))
			{
				$sWhere .= ' AND 
					`kta_sub`.`week` = '.$aWeekFilter['week'].'
				';
			}
			elseif(
				isset($aWeekFilter['week_from']) &&
				isset($aWeekFilter['week_until'])
			)
			{
				$sWhere .= ' AND 
					`kta_sub`.`week` <= '.$aWeekFilter['week_until'].' AND
					`kta_sub`.`week` >= '.$aWeekFilter['week_from'].'
				';
			}
		}
		
		return $sWhere;
	}

	/**
	 * @return Ext_TS_Inquiry
	 */
	public function getInquiry() {
		return Ext_TS_Inquiry::getInstance($this->inquiry_id);
	}

	/**
	 * @return Ext_Thebing_School_Tuition_Allocation
	 */
	public function getAllocation() {
		return $this->getJoinedObject('allocation');
	}
	
    public function isReleased()
    {
        return $this->student_login_release != '0000-00-00';
    }

	/**
	 * Alle Anwesenheits-Einträge ermitteln, welche innerhalb eines bestimmten Zeitraums stattfinden
	 *
	 * @param DateTime $dFrom
	 * @param DateTime $dUntil
	 * @param array $aFilter
	 * @return Ext_Thebing_Tuition_Attendance[]
	 */
	public static function getAttendancesEntriesForPeriod(DateTime $dFrom, DateTime $dUntil, $aFilter) {

		$aSql = [
			'from' => $dFrom->format('Y-m-d'),
			'until' => $dUntil->format('Y-m-d')
		];

		$sSql = "
			SELECT
				`kta`.*
			FROM
				`kolumbus_tuition_attendance` `kta` INNER JOIN
				`ts_inquiries_journeys_courses` `ts_ijc` ON
					`ts_ijc`.`id` = `kta`.`journey_course_id` AND
					`ts_ijc`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys` `ts_ij` ON
					`ts_ij`.`id` = `ts_ijc`.`journey_id` AND
					`ts_ij`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' AND
					`ts_ijc`.`active` = 1 INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ts_ij`.`school_id`
			WHERE
				`kta`.`active`= 1 AND
				getCorrectCourseStartDay(`kta`.`week`, `cdb2`.`course_startday`) <= :until AND
				getCorrectCourseStartDay(`kta`.`week`, `cdb2`.`course_startday`) + INTERVAL 6 DAY >= :from
		";

		$i = 0;
		foreach($aFilter as $sField => $mValue) {
			$sFieldKey = 'field_'.$i++;
			$aSql[$sFieldKey] = $mValue;
			$sSql .= " AND $sField = :$sFieldKey ";
		}

		$aResult = (array)DB::getQueryRows($sSql, $aSql);

		$aResult = array_map(function($aRow) {
			return Ext_Thebing_Tuition_Attendance::getObjectFromArray($aRow);
		}, $aResult);

		return $aResult;

	}

	/**
	 * Tage mit kompletter An- oder Abwesenheit berechnen (Gruppierung nach Tagen), basierend auf Klassentagen
	 *
	 * @param string $sType
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param Datetime $dFrom
	 * @param DateTime $dUntil
	 * @param Ext_TS_Inquiry_Journey_Course $oJourneyCourse
	 * @param bool $bCountDayOnce Tag nur einmal oder mehrfach zählen (bei mehreren Einträgen an diesem Tag)
	 * @return DateTime[]
	 */
	public static function getPresentOrAbsentDays($sType, Ext_TS_Inquiry $oInquiry, Datetime $dFrom=null, DateTime $dUntil=null, Ext_TS_Inquiry_Journey_Course $oJourneyCourse=null, $bCountDayOnce=true) {

		$aDays = [];
		$aCriteria = ['inquiry_id' => $oInquiry->id];
		if($oJourneyCourse instanceof Ext_TS_Inquiry_Journey_Course) {
			$aCriteria['journey_course_id'] = $oJourneyCourse->id;
		}

		// Zugewiesene Tage (Klassenplanung) und Anwesenheitseinträge holen
		$aClassDays = $oInquiry->getClassDays($dFrom, $dUntil, $oJourneyCourse);
		$aAttendanceEntries = self::getRepository()->findBy($aCriteria); /** @var self[] $aAttendanceEntries */

		if(empty($aAttendanceEntries)) {
			return [];
		}

		// Beim Berechnen der (kompletten) Abwesenheitstage wird die Dauer pro Tag benötigt (Zuweisung)
		$aAttendanceWeekDurations = [];
		if(
			$sType === 'absent' ||
			$sType === 'absent_partial'
		) {
			foreach($aAttendanceEntries as $oAttendance) {
				$aAttendanceWeekDurations[$oAttendance->id] = Ext_Thebing_School_Tuition_Allocation::getRepository()->getWeekDayDurations($oAttendance->getAllocation());
			}
		}

		foreach($aClassDays as $dDay) {

			// Welche Anwesenheitseinträge fallen in den Tag?
			$aAttendancesInPeriod = array_filter($aAttendanceEntries, function($oAttendance) use($dDay) {
				$dAttendanceFrom = new DateTime($oAttendance->week);
				$dAttendanceUntil = clone $dAttendanceFrom;
				$dAttendanceUntil->add(new DateInterval('P6D'));
				return $dDay->isBetween($dAttendanceFrom, $dAttendanceUntil);
			});

			if($sType === 'present') {
				// Prüfen, ob es in irgendeinem Anwesenheits-Eintrag für diesen Tag eine Abwesenheit gibt
				$bPresent = true;
				foreach($aAttendancesInPeriod as $oAttendance) {
					$sDayField = Ext_TC_Util::convertWeekdayToString($dDay->format('N'));
					$fAbsence = (float)$oAttendance->$sDayField;
					if($fAbsence > 0) {
						$bPresent = false;
						break;
					}
				}

				// Wenn keine Abwesenheit am ganzen Tag: Tag komplett da
				if($bPresent) {
					$aDays[] = $dDay;
				}

			} elseif(
				$sType === 'absent' ||
				$sType === 'absent_partial'
			) {

				// Abwesenheit und Lektionsdauer pro Tag summieren
				$fCompleteDayAbsence = 0;
				$fCompleteDayDuration = 0;
				$aDaysWithAbsence = [];
				foreach($aAttendancesInPeriod as $oAttendance) {
					$sDayField = Ext_TC_Util::convertWeekdayToString($dDay->format('N'));
					$fAbsence = (float)$oAttendance->$sDayField;
					$fCompleteDayAbsence += $fAbsence;
					$fCompleteDayDuration += $aAttendanceWeekDurations[$oAttendance->id][$dDay->format('N')];

					// Tage mit irgendeiner Abwesenheit für Tage mit teilweiser Abwesenheit sammeln
					if($fAbsence > 0) {
						$aDaysWithAbsence[] = $dDay;
					}
				}

				if(
					// Wenn leer: Muss abgefangen werden, da Schüler nicht gefehlt hat (aber: 0 == 0)
					!empty($aAttendancesInPeriod) &&
					// Wenn Abwesenheit gleich Lektionsdauer oder Abwesenheit größer: Tag komplett gefehlt
					bccomp($fCompleteDayAbsence, $fCompleteDayDuration, 2) >= 0
				) {
					if($sType === 'absent') {
						// Nur beim entsprechen Typ zählen
						$aDays[] = $dDay;
					}
				} elseif(count($aDaysWithAbsence) > 0) {
					// absent und absent_partial schließen sich aus, daher elseif!
					if($sType === 'absent_partial') {
						// Tage mit Abwesenheit: Nur beim entsprechenden Typ zählen
						$aDays = array_merge($aDays, $aDaysWithAbsence);
					}
				}
			}
		}

		if($bCountDayOnce) {
			$aDays = array_unique($aDays, SORT_REGULAR);
		}

		return $aDays;

	}

	/**
	 * Abwesenheit neu berechnen, die alte Lektionsdauer ist notwendig um zu erkennen ob der Schüler als komplett abwesend
	 * markiert war
	 *
	 * @param float $originalLessonDuration
	 * @return $this
	 */
	public function recalculateAbsence(float $originalLessonDuration): static
	{
		$block = $this->getAllocation()?->getBlock();

		if ($block && $block->exist()) {

			$originalFullDuration = $block->getLessonDuration($originalLessonDuration);
			$newFullDuration = $block->getLessonDuration();

			if (bccomp($originalFullDuration, $newFullDuration, 2) !== 0) {

				foreach (\Ext_Thebing_Tuition_Attendance::DAY_MAPPING as $day) {

					$currentValue = floatval($this->$day);

					if (bccomp($currentValue, 0, 2) === 0) {
						// Keine Abwesenheit eingetragen
						continue;
					}

					if (bccomp($currentValue, $originalFullDuration, 2) === 0) {
						// Komplett abwesend
						$this->$day = $newFullDuration;
					} else if (bccomp($currentValue, $newFullDuration, 2) === 1) {
						// NICHT komplett abwesend, aber eingetragene Abwesenheit ist größer als neue Dauer, demnach war er
						// nach der neuen Dauer komplett abwesend
						$this->$day = $newFullDuration;
					} else {
						// NICHT komplett abwesend, eingetragene Abwesenheit ist kleiner als neue Dauer
						// -> nichts machen
					}
				}

			}
		}

		return $this;
	}

	public function refreshIndex()
	{
		$allocation = $this->getAllocation();
		/* @var \Ext_Thebing_School_Tuition_Block $block */
		$block = $allocation->getJoinedObject('block');
		/* @var \Ext_Thebing_School $school */
		$school = $block->getSchool();

		$lessonDuration = $block->getLessonDuration();

		// Tage außerhalb des Leistungszeitraums oder Blockzeitraums auf null setzen
		$days = $allocation->getBlock()->createPeriodCollection();
		$coursePeriod = $allocation->getJourneyCourse()->createPeriod();
		foreach (Ext_Thebing_Tuition_Attendance::DAY_MAPPING as $day => $dayShort) {
			if ($days->filter(function (\Spatie\Period\Period $period) use ($coursePeriod, $day) {
				$period2 = \Spatie\Period\Period::make($period->start(), $period->end(), \Spatie\Period\Precision::DAY()); // MINUTE auf DAY
				return $day === (int)$period->start()->format('N') && $coursePeriod->contains($period2);
			})->isEmpty()) {
				$this->$dayShort = null;
			}
		}

		$days =
			intval(in_array(1, $block->days) && !is_null($this->mo)) +
			intval(in_array(2, $block->days) && !is_null($this->di)) +
			intval(in_array(3, $block->days) && !is_null($this->mi)) +
			intval(in_array(4, $block->days) && !is_null($this->do)) +
			intval(in_array(5, $block->days) && !is_null($this->fr)) +
			intval(in_array(6, $block->days) && !is_null($this->sa)) +
			intval(in_array(7, $block->days) && !is_null($this->so));

		// Entschuldigte Abwesenheit nur als "Anwesend" berechnen wenn die Schule es so eingestellt ist
		$absent =
			(($school->tuition_excused_absence_calculation === 'include' && $this->excused & 1)?0:$this->mo) +
			(($school->tuition_excused_absence_calculation === 'include' && $this->excused & 2)?0:$this->di) +
			(($school->tuition_excused_absence_calculation === 'include' && $this->excused & 4)?0:$this->mi) +
			(($school->tuition_excused_absence_calculation === 'include' && $this->excused & 8)?0:$this->do) +
			(($school->tuition_excused_absence_calculation === 'include' && $this->excused & 16)?0:$this->fr) +
			(($school->tuition_excused_absence_calculation === 'include' && $this->excused & 32)?0:$this->sa) +
			(($school->tuition_excused_absence_calculation === 'include' && $this->excused & 64)?0:$this->so);

		// Minuten der erfassten Lektionen und der anwesenden Lektionen in Minuten speichern
		// TODO Gleiches Problem wie bei ktbic.lesson_duration: Manchmal wird gerechnet, manchmal werden die Spalten benutzt
		// TODO Wofür existiert eigentlich diese Spalte duration? Hat ktbic.lesson_duration nicht immer den gleichen Wert?
		$this->duration = $days * intval($lessonDuration);
		$this->attended = $this->duration - intval($absent);

		/*
		 * Check if all possible days have been edited and completed has to be set 1
		 */
		$this->completed = ($days >= count($block->days)) ? 1 : 0;
		return $this;
	}

	public function save($bLog = true) {

		$this->refreshIndex();

		System::wd()->executeHook('ts_tuition_attendance_save', $this);

		$saved = parent::save($bLog);

		if (!is_array($saved)) {
			/* @var \Ext_Thebing_School_Tuition_Block $block  */
			$block = $this->getAllocation()?->getBlock();
			if ($block) {
				// Sobald eine Anwesenheit eingetragen wird muss der Block als "Stattgefunden" markiert werden. Über das PP
				// weil es sein kann dass mehrere Anwesenheiten eines Blocks gespeichert werden und es sonst zu Fehlern kommt
				(new \TsTuition\Service\BlockStatusService($block))->lazyUpdate();
			}
		}

		return $saved;
	}

	public function isAbsent($day, $checkExcused=false) {
		
		if(is_numeric($day)) {
			$numericDay = (int)$day;
			$day = self::DAY_MAPPING[$day];
		} else {
			$numericDay = (int)array_flip(self::DAY_MAPPING)[$day];
		}
		
		if(
			$this->$day !== null &&
			$this->$day > 0
		) {
			
			if($checkExcused) {
				if($this->excused & pow(2, ($numericDay - 1))) {
					return true;
				}
			} else {
				return true;
			}
			
		}
		
		return false;
	}

	/**
	 * Returns true, if active attendances with the given conditions exist
	 * Active means "active=1" and min one weekday is not null
	 * @param array $conditions
	 * @param \Carbon\Carbon|null $firstDay
	 * @param \Carbon\Carbon|null $lastDay
	 * @return \Illuminate\Support\Collection
	 */
	static public function getActiveAttendances(
		array $conditions = [],
		?\Carbon\Carbon $firstDay = null,
		?\Carbon\Carbon $lastDay = null
	): \Illuminate\Support\Collection
	{
		$attendanceQuery = Ext_Thebing_Tuition_Attendance::query();
		foreach ($conditions as $condition) {
			$attendanceQuery->where(...$condition);
		}
		// Falls Zeitraum nicht gesetzt wird, dann muss einer der Wochentage nicht NULL sein.
		if (empty($firstDay) && empty($lastDay)) {
			$attendanceQuery->where(function ($query) {
				foreach (self::DAY_MAPPING as $day) {
					$query->orWhereNotNull($day);
				}
			});
		} else {
			// Bedingungen für Zeitraum erstellen.
			foreach (['firstDay' => $firstDay, 'lastDay' => $lastDay] as $dateConditionType => $date) {
				if (empty($date)) {
					continue;
				}
				// Wochentag des Datums
				$dayOfWeek = $date->dayOfWeek;
				// Montag der Woche für das Datum ermitteln.
				$monday = $date->copy();
				if (!$monday->isMonday()) {
					$monday = $monday->previous(\Carbon\Carbon::MONDAY);
				}
				// Query erstellen
				$attendanceQuery->where(function ($query) use ($dateConditionType, $monday, $dayOfWeek) {
					// Zunächst alle vergangenen Wochen oder zukünftigen Wochen
					// (je nachdem ob es um Zeit vor oder nach dem Zeitraum geht) einschließen.
					// Hier muss nur einer der Wochentage nicht NULL sein.
					$query->where(function ($query) use ($dateConditionType, $monday, $dayOfWeek) {
						$query->where('week', $dateConditionType == 'firstDay' ? '>' : '<', $monday->format('Y-m-d'))
							->where(function ($query) {
								foreach (self::DAY_MAPPING as $day) {
									$query->orWhereNotNull($day);
								}
							});
							// Nun zur aktuellen Woche. Hier geht es jetzt um einzelne Tage.
					})->orWhere(function ($query) use ($dayOfWeek, $monday, $dateConditionType) {
						$query->where('week', '=', $monday->format('Y-m-d'))
							// Nun herausfinden welche Tage der aktuellen Woche nicht NULL sein müssen
							->where(function ($query) use ($dayOfWeek, $dateConditionType) {
								// Wenn der type 'firstDay' dann suchen wir Wochentage nicht NUll am und nach angegebenem Datum.
								// Also von dayOfWeek bis 7.
								// Wenn der type 'lastDay' dann suchen wir Wochentage nicht NUll vor und am angegebenem Datum.
								// Also 1 bis dayOfWeek.
								$startDays = $dateConditionType == 'firstDay' ? $dayOfWeek : 1;
								$maxDays = $dateConditionType == 'firstDay' ? 7 : $dayOfWeek;
								for ($i = $startDays; $i <= $maxDays; $i++) {
									$query->orWhereNotNull(self::DAY_MAPPING[$i]);
								}
							});
					});
				});
			}
		}
		return $attendanceQuery->get();
	}
	
}
