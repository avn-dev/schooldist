<?php

use Communication\Interfaces\Model\CommunicationSubObject;
use Ts\Entity\Inquiry\Journey\Course\LessonsContingent;
use TsTuition\Entity\Block\Unit;

/**
 * @property int $id
 * @property $changed
 * @property $created
 * @property int $active
 * @property int $creator_od
 * @property int $block_id
 * @property int $room_id
 * @property int $inquiry_course_id
 * @property int $course_id // deprecated
 * @property int $program_service_id
 * @property int $lesson_duration
 * @property $automatic
 *
 * @method static Ext_Thebing_School_Tuition_AllocationRepository getRepository()
 */
class Ext_Thebing_School_Tuition_Allocation extends Ext_Thebing_Basic implements \Communication\Interfaces\Model\HasCommunication {

	protected $_sTable = 'kolumbus_tuition_blocks_inquiries_courses';
	protected $_sTableAlias = 'ktbic';
	
	/**
	 * @var string
	 */
	protected $_sPlaceholderClass = \Ext_Thebing_School_Tuition_Allocation_Placeholder::class;

	public $bIsNew = null;

	protected $_aJoinedObjects = array(
		'block'	=> array(
			'class'	=> 'Ext_Thebing_School_Tuition_Block',
			'key'	=> 'block_id',
		),
		'inquiry_course'	=> array(
			'class'	=> 'Ext_TS_Inquiry_Journey_Course',
			'key'	=> 'inquiry_course_id',
		),
		'course'	=> array(
			'class'	=> 'Ext_Thebing_Tuition_Course',
			'key'	=> 'course_id',
		),
		'program_service'	=> array(
			'class'	=> \TsTuition\Entity\Course\Program\Service::class,
			'key'	=> 'program_service_id',
		),
		'attendance' => array(
			'class' => 'Ext_Thebing_Tuition_Attendance',
			'key' => 'allocation_id',
			'type' => 'child',
			'on_delete' => 'cascade',
			'check_active' => true
		),
	);

	protected $_aFormat = array(
		'block_id' => array(
			'validate' => 'INT_POSITIVE'
		),
		'inquiry_course_id' => array(
			'validate' => 'INT_POSITIVE'
		),
		'course_id' => array(
			'validate' => 'INT_POSITIVE'
		),
	);
	
	protected $_aLessonDurations = null;
	
	protected $_aAttendances = null;

	protected $_aFlexibleFieldsConfig = [
		// Flex-Values der Anwesenheit haben Block-Allocation-ID…
		'tuition_attendance_register' => []
	];
	
	/**
	 *
	 * Konstruktor wird in dieser Klasse abgeleitet, damit der restliche parent kram 
	 * nicht ausgeführt werden per parameter aus performance gründen
	 * 
	 * @param type $iDataID
	 * @param type $sTable
	 * @param type $bAutoFormat 
	 */
	public function __construct($iDataID = 0, $sTable = null, $bAutoFormat = false, $bInitTableData = true) {
		if($bInitTableData){
			parent::__construct($iDataID, $sTable, $bAutoFormat);
		}
	}
	
	public function __get($sName) {
		
		Ext_Gui2_Index_Registry::set($this);
		
		if($sName == 'customer_name'){
			
			$oJourneyCourse = $this->getJourneyCourse();
			$oInquiry		= $oJourneyCourse->getInquiry();
			$oTraveller		= $oInquiry->getFirstTraveller();
			
			return $oTraveller->getName();
			
		}else{
			return parent::__get($sName);
		}
	}

	/**
	 *
	 * @param <mixed> $bJoinBlocks
	 * @param <mixed> $bJoinInquiryCourses
	 * @param <mixed> $bJoinCourse
	 * @return string
	 */
	protected function _getDefaultFromPart($mJoinBlocks, $mJoinInquiryCourses, $mJoinCourse)
	{
		$sSqlDefault = "
			FROM
				#table #table_alias
		";

		if($mJoinBlocks)
		{
			$sSqlDefault .= " ".$mJoinBlocks." JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`id` = #table_alias.`block_id` AND
					`ktb`.`active` = 1
			";
		}

		if($mJoinInquiryCourses)
		{
			$sSqlDefault .= " ".$mJoinInquiryCourses." JOIN
				 `ts_inquiries_journeys_courses` `kic` ON
					`kic`.`id` = #table_alias.`inquiry_course_id` AND
					`kic`.`active` = 1
			";
		}

		if($mJoinCourse)
		{
			$sSqlDefault .= " ".$mJoinCourse." JOIN
				 `kolumbus_tuition_courses` `ktc` ON
					`ktc`.`id` = #table_alias.`course_id` AND
					`ktc`.`active` = 1
			";
		}

		return $sSqlDefault;
	}

	protected function _getDefaultReplaceArray()
	{
		$aSql = array(
			'table'			=> $this->_sTable,
			'table_alias'	=> $this->_sTableAlias,
		);

		return $aSql;
	}

	/**
	 *
	 * @param <int> $iInquiryId
	 * @param <bool> $bCount
	 * @param <bool> $bSubstitute
	 * @return <int> / <array>
	 */
	public function getTeachersForInquiryCourse(Ext_TS_Inquiry_Journey_Course $oInquiryCourse, \TsTuition\Entity\Course\Program\Service $oProgramService, $bCount=false, $bSubstitute=false)
	{
		if($bCount)
		{
			$sSelect = "COUNT(DISTINCT `kt`.`id`)";
		}
		else
		{
			$sSelect = "*";
		}

		$sSql = "
			SELECT
				".$sSelect."
		";

		$sSql .= $this->_getDefaultFromPart('INNER', 'INNER', false);

		if($bSubstitute)
		{
			$sSql .= " INNER JOIN
						`kolumbus_tuition_blocks_substitute_teachers` `ktbst` ON
							`ktb`.`id` = `ktbst`.`block_id` AND
							`ktbst`.`active` = 1
			";

			$sJoinToTeacher = '`ktbst`.`teacher_id`';
		}
		else
		{
			$sJoinToTeacher = '`ktb`.`teacher_id`';
		}


		$sSql .= " INNER JOIN `ts_teachers` `kt` ON
						".$sJoinToTeacher." = `kt`.`id` AND
						`kt`.`active` = 1
		";

		$sSql .= " WHERE
					#table_alias.`active` = 1 AND
					`kic`.`id` = :inquiry_course_id AND
					`ktbic`.`program_service_id` = :program_service_id
				GROUP BY
					`kic`.`id`
		";

		$aSql						= $this->_getDefaultReplaceArray();
		$aSql['inquiry_course_id']	= (int)$oInquiryCourse->getId();
		$aSql['program_service_id']	= (int)$oProgramService->getId();

		if($bCount)
		{
			$mReturn = (int)DB::getQueryOne($sSql,$aSql);
		}
		else
		{
			$mReturn = DB::getPreparedQueryData($sSql,$aSql);
		}

		return $mReturn;
	}

	public function getMatchingPercent(Ext_TS_Inquiry_Journey_Course $oJourneyCourse, \TsTuition\Entity\Course\Program\Service $oProgramService, $sCustomerDbField)
	{
		$sSelectMain = "
			SELECT
				(
					(
						SUM(`match_with_other`) /
						SUM(`all_students`)
					) * 100
				) `match_percent`
			FROM
			(
		";

		$sSql = "
			SELECT
				IF(
					`cdb1`.#matching_field = `cdb1_other`.#matching_field,
					1,
					0
				) as `match_with_other`,
				1 as `all_students`
		";

		$sSql .= $this->_getDefaultFromPart('INNER', 'INNER', false);

		$sSql .= " INNER JOIN
			`ts_inquiries_journeys` `ts_i_j` ON
				`ts_i_j`.`id` = `kic`.`journey_id` AND
				`ts_i_j`.`active` = 1 AND
				`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' INNER JOIN
			`ts_inquiries` `ki` ON
				`ki`.`id` = `ts_i_j`.`inquiry_id` AND
				`ki`.`active` = 1 INNER JOIN
			`ts_inquiries_to_contacts` `ts_i_to_c` ON
				`ts_i_to_c`.`inquiry_id` = `ki`.`id` AND
				`ts_i_to_c`.`type` = 'traveller' INNER JOIN
			`tc_contacts` `cdb1` ON
				`cdb1`.`id` = `ts_i_to_c`.`contact_id` AND
				`cdb1`.`active` = 1 INNER JOIN
			#table `ktbic_other` ON
				`ktbic_other`.`block_id` = `ktbic`.`block_id` AND
				`ktbic_other`.`active` = 1 INNER JOIN
			`ts_inquiries_journeys_courses` `kic_other` ON
				`kic_other`.`id` = `ktbic_other`.`inquiry_course_id` AND
				`kic_other`.`active` = 1 INNER JOIN
			`ts_inquiries_journeys` `ts_i_j_other` ON
				`ts_i_j_other`.`id` = `kic_other`.`journey_id` AND
				`ts_i_j_other`.`active` = 1 AND
				`ts_i_j_other`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' INNER JOIN
			`ts_inquiries` `ki_other` ON
				`ki_other`.`id` = `ts_i_j_other`.`inquiry_id` AND
				`ki_other`.`active` = 1 INNER JOIN
			`ts_inquiries_to_contacts` `ts_i_to_c_other` ON
				`ts_i_to_c_other`.`inquiry_id` = `ki_other`.`id` AND
				`ts_i_to_c_other`.`type` = 'traveller' INNER JOIN
			`tc_contacts` `cdb1_other` ON
				`cdb1_other`.`id` = `ts_i_to_c_other`.`contact_id` AND
				`cdb1_other`.`active` = 1
		";

		$sSql .= " WHERE
					#table_alias.`active` = 1 AND
					`kic`.`id` = :inquiry_course_id AND
					`ktbic_other`.`program_service_id` = :program_service_id
				  GROUP BY
					`cdb1`.`id`,
					`cdb1_other`.`id`
		";

		$sSelectMain .= $sSql;

		$sSelectMain .= "
			) `from_part`
			GROUP BY
				NULL
		";

		$aSql						= $this->_getDefaultReplaceArray();
		$aSql['inquiry_course_id']	= (int)$oJourneyCourse->getId();
		$aSql['program_service_id']	= (int)$oProgramService->getId();
		$aSql['matching_field']		= (string)$sCustomerDbField;

		$fResult = (float)DB::getQueryOne($sSelectMain,$aSql);

		return $fResult;
	}

	/**
	 * @param $sWeek
	 * @param Ext_TS_Inquiry_Journey_Course $oInquiryCourse
	 * @param Ext_Thebing_Tuition_Course $oCourse
	 * @param bool $bGroupedDays
	 * @param int|null $iLimit
	 *
	 * @return array
	 */
	public static function getAllocationByInquiryCourse(string $sWeek, Ext_TS_Inquiry_Journey_Course $oInquiryCourse, Ext_Thebing_Tuition_Course $oCourse, bool $bGroupedDays = true, int $iLimit = null) {

		$oAllocation = new Ext_Thebing_School_Tuition_Allocation_Result();
		$oAllocation->setInquiryCourse($oInquiryCourse);
		$oAllocation->setCourse($oCourse);
		$oAllocation->setWeek(new DateTime($sWeek));
		$oAllocation->setGroupedDays($bGroupedDays);
		$oAllocation->setBlockWeekSortDesc(true);
		$oAllocation->setLimit($iLimit);
		$aResult = $oAllocation->fetch();
		
		return $aResult;
	}

	/**
	 * @param string $sWeek
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param bool $bGroupedDays
	 * @param int|null $iLimit
	 *
	 * @return array
	 */
	public static function getAllocationByInquiry(string $sWeek, Ext_TS_Inquiry $oInquiry, bool $bGroupedDays = true, int $iLimit = null) {

		$oAllocation = new Ext_Thebing_School_Tuition_Allocation_Result();
		$oAllocation->setInquiry($oInquiry);
		$oAllocation->setWeek(new DateTime($sWeek));
		$oAllocation->setGroupedDays($bGroupedDays);
		$oAllocation->setBlockWeekSortDesc(true);
		$oAllocation->setLimit($iLimit);
		$aResult = $oAllocation->fetch();

		return $aResult;
	}

	/**
	 * Prüft, ob Kurszuweisungen verändert werden müssen
	 * @param Ext_TS_Inquiry_Journey_Course $oInquiryCourse
	 */
	public static function checkJourneyCourseAllocations(\Ext_TS_Inquiry_Journey_Course $oInquiryCourse) {

		/** @var \Illuminate\Support\Collection|self[] $aAllocations */
		$aAllocations = self::query()
			->where('inquiry_course_id', $oInquiryCourse->id)
			->get();

		$program = $oInquiryCourse->getProgram();
		$programServices = $program->getServices(\TsTuition\Entity\Course\Program\Service::TYPE_COURSE);

		foreach ($aAllocations as $oAllocation) {

			// $iNewCourseId ist immer ein Unterkurs
			$iNewCourseId = $oAllocation->checkCourseAvailability($oInquiryCourse);

			if ($iNewCourseId === null) {
				$oAllocation->delete();
			} else {

				$oNewCourse = Ext_Thebing_Tuition_Course::getInstance($iNewCourseId);

				// Der der Klasse zugewiesene Kurs kann bei Wechsel des gebuchten Kurses eine andere Program-Service-ID haben, daher ermitteln und anpassen
				$allocationProgramService = TsTuition\Entity\Course\Program\Service::getInstance($oAllocation->program_service_id);

				if (!$allocationProgramService->isCourse()) {
					// An dieser Stelle dürfen nur Kurse vorhanden sein, ansonsten wurden die Allocations falsch gelöscht als das Programm geändert wurde
					throw new \RuntimeException(sprintf('Invalid tuition allocation for program service type "%s" [%d]', $allocationProgramService->type, $allocationProgramService->id));
				}

				$programCourseService = $programServices->firstWhere('type_id', $iNewCourseId);

				if (!$programCourseService) {
					// Sollte eigentlich durch checkCourseAvailability() abgefangen sein
					throw new \RuntimeException('Unable to find new program service for new course');
				}

				$oAllocation->course_id = $iNewCourseId;
				$oAllocation->program_service_id = $programCourseService->id;

				// Lektionsdauer neu berechnen (eigentlich nur relevant für erste und letzte Zuweisung)
				$oAllocation->lesson_duration = $oAllocation->getBlock()->calculateAllocationLessonDuration($oInquiryCourse);

				$oAllocation->save();
			}
		}

	}

	/**
	 * @param $iNewCourse
	 * @return bool
	 */
	public function checkCourseAvailability(\Ext_TS_Inquiry_Journey_Course $oJourneyCourse) {
		
		$oNewCourse = $oJourneyCourse->getCourse();

		/**
		 * Wenn Kombinationskurs, dann nur schauen, ob der bisher zugewiesene 
		 * Kurs im alten und neuen Kombinationskurs vorkommt
		 */
		if ($oNewCourse->isCombinationCourse() || $oNewCourse->isProgram()) {

			$oProgram = $oJourneyCourse->getProgram();
			$aAvailableCourseIds = $oProgram->getCourses()->map(fn (Ext_Thebing_Tuition_Course $oCourse) => $oCourse->getId())
				->toArray();

			$iCheckCourseId = (int)$this->course_id;

		/**
		 * Bei normalem Kurs prüfen, ob der neue Kurs auch in der Klasse 
		 * unterrichtet wird
		 */
		} else {

			$oBlock				= Ext_Thebing_School_Tuition_Block::getInstance((int)$this->block_id);
			$oClass				= $oBlock->getClass();
			$aAvailableCourseIds	= (array)$oClass->courses;

			$iCheckCourseId = (int)$oNewCourse->id;
			
		}

		if (in_array($iCheckCourseId, $aAvailableCourseIds)) {
			return $iCheckCourseId;
		}

		return null;
	}

	/**
	 * @TODO Was passiert eigentlich, wenn man eine Zuweisung von einem Kombinationskurs löscht? Werden dann alle einfach gelöscht?
	 *
	 * @param $iBlockId
	 * @param $iRoomId
	 * @param $aInquiryCourseIds
	 * @param $iAllWeeks
	 * @return array
	 */
	public static function deleteInquiryCourseAllocations($iBlockId, $iRoomId, $aInquiryCourseIds, $iAllWeeks) {

		$iBlockId			= (int)$iBlockId;
        $iRoomId			= (int)$iRoomId;
		$iAllWeeks			= (int)$iAllWeeks;

		$oBlock = Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);
		$oClass	= $oBlock->getClass();
		$oSchool = $oClass->getSchool();

		if($iAllWeeks==1) {
			$sOperator = '>=';
		} else {
			$sOperator = '=';
		}

		$aBlocks = $oClass->getBlocks($oBlock->week, false, $sOperator);
		$aBlockIds = array_map(function($oBlock) {
			return $oBlock->id;
		}, $aBlocks);

		// Auf vorhandene Zahlungen überprüfen
		foreach($aBlocks as $oTmpBlock) {
			if(!empty($oTmpBlock->getPayments())) {
				return [
					'success' => 0,
					'error' => 'payments_exists'
				];
			}
		}

		$blockJourneyCourseIds = [];
		
		foreach($aInquiryCourseIds as $inquiryCourseKey) {
			list($journeyCourseId, $courseId, $inquiryId) = explode('_', $inquiryCourseKey);
			$journeyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($journeyCourseId);
			if($iAllWeeks != 1 && $journeyCourse->flexible_allocation == 1) {
				$blockJourneyCourseIds[$oBlock->id][] = $journeyCourseId;
			} else {
				foreach($aBlocks as $tmpBlock) {
					$blockJourneyCourseIds[$tmpBlock->id][] = $journeyCourseId;
				}
			}
			
		}
		
		/*
		 * Wenn Löschen aus allen Folgewochen und automatische Zuweisung nach Ferien:
		 * IDs ergänzen mit IDs der gesplitteten Kursbuchungen, damit alle Zuweisungen gelöscht werden
		 */
		if(
			$iAllWeeks &&
			$oSchool->tuition_automatic_holiday_allocation
		) {
			foreach($aInquiryCourseIds as $sJourneyCourseId) {
				list($journeyCourseId, $iCourseId, $iInquiryId) = explode('_', $sJourneyCourseId);
				$journeyCourse = Ext_TS_Inquiry_Journey_Course::getInstance($journeyCourseId);
				$aRelatedJourneyCourses = $journeyCourse->getRelatedServices();
				foreach($aRelatedJourneyCourses as $oRelatedJourneyCourse) {
					foreach($aBlocks as $tmpBlock) {
						$blockJourneyCourseIds[$tmpBlock->id][$oRelatedJourneyCourse->id] = $oRelatedJourneyCourse->id;
					}
				}
			}
		}

		$aErrors = [];

		// Laut MK ist der Raum egal, da immer das gleiche rauskäme
		$aAllocations = self::getRepository()->findByBlocksAndJourneyCourses($blockJourneyCourseIds);

		if(
			!is_array($aAllocations)
		)
		{
			$aAllocations	= array();
			$aErrors		= array('no_allocations_found');
		}

		// Prüfen, ob alle Zuweisungen gelöscht werden dürfen
		$deleteAllocationsErrors = [];
		foreach ($aAllocations as $allocation)
		{
			$deleteAllocationsErrors = array_merge($deleteAllocationsErrors, $allocation->validateDelete());
		}
		if (!empty($deleteAllocationsErrors)) {
			return [
				'success' => 0,
				'error' => $deleteAllocationsErrors
			];
		}

		foreach ($aAllocations as $oAllocation) {
			try {
				$mReturn = $oAllocation->delete();
				if (is_array($mReturn)) {
					$aErrors += $mReturn;
				}
			} catch (DB_QueryFailedException $e) {
				$aErrors[] = $e->getMessage();
			}
		}

		if(empty($aErrors))
		{

            $aBlockIds = array_map(function($iBlockId) use ($iRoomId) {
                return [
                    'block_id' => $iBlockId,
                    'room_id' => $iRoomId,
                ];
            }, $aBlockIds);

			return array(
				'success'		=> 1,
				'block_ids'		=> array_values($aBlockIds)
			);
		}
		else
		{
			return array(
				'success'		=> 0,
			);
		}
	}

	/**
	 * @param $iInquiryCourseId
	 * @param array $aFilter
	 * @return array
	 */
	public static function getAllocationsByInquiryCourse($iInquiryCourseId, $aFilter = array()) {

		$oAllocation = new self();

		$aSql = $oAllocation->_getDefaultReplaceArray();

		$sSql = "
			SELECT
				#table_alias.`id`
		";

		$sSql .= $oAllocation->_getDefaultFromPart('INNER', false, false);

		$sSql .= " 
			WHERE 
				#table_alias.`inquiry_course_id` = :inquiry_course_id AND
				#table_alias.`active` = 1
			";
		
		$iCounter = 1;
		
		foreach($aFilter as $sColumn => $mValue)
		{
			$sOperator = '=';
			
			if(is_array($mValue))
			{
				if(isset($mValue['operator']))
				{
					$sOperator = $mValue['operator'];
				}
				
				if(isset($mValue['value']))
				{
					$mValue = $mValue['value'];
				}
			}
			
			$sSql .= " AND
				".$sColumn." ".$sOperator." :filter_".$iCounter."
			";
			
			$aSql['filter_' . $iCounter] = $mValue;
			
			$iCounter++;
		}

		$aSql['inquiry_course_id'] = (int)$iInquiryCourseId;

		$aReturn = DB::getQueryCol($sSql, $aSql);

		return (array)$aReturn;

	}

	/**
	 * @param Ext_TS_Inquiry $oInquiry
	 * @param array $aFilter
	 * @return array
	 */
	public static function getAllocationIdsByInquiry(Ext_TS_Inquiry $oInquiry, $aFilter = array()) {

		$oAllocation = new self();

		$aSql = $oAllocation->_getDefaultReplaceArray();

		$sSql = "
			SELECT
				#table_alias.`id`
		";

		$sSql .= $oAllocation->_getDefaultFromPart('INNER', 'INNER', false);
		
		$sSql .= " INNER JOIN
			`ts_inquiries_journeys` `ts_i_j` ON
				`ts_i_j`.`id` = `kic`.`journey_id` AND
				`ts_i_j`.`active` = 1 AND
				`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."'
		";

		$sSql .= " 
			WHERE 
				`ts_i_j`.`inquiry_id` = :inquiry_id AND
				#table_alias.`active` = 1
			";
		
		$iCounter = 1;
		
		foreach($aFilter as $sColumn => $mValue)
		{
			$sOperator = '=';
			
			if(is_array($mValue))
			{
				if(isset($mValue['operator']))
				{
					$sOperator = $mValue['operator'];
				}
				
				if(isset($mValue['value']))
				{
					$mValue = $mValue['value'];
				}
			}
			
			$sSql .= " AND
				".$sColumn." ".$sOperator." :filter_".$iCounter."
			";
			
			$aSql['filter_' . $iCounter] = $mValue;
			
			$iCounter++;
		}

		$aSql['inquiry_id'] = (int)$oInquiry->id;

		$aReturn = DB::getQueryCol($sSql, $aSql);

		return (array)$aReturn;
	}

	/**
	 * @param int $iBlockId
	 * @param int $iInquiryCourseId
	 * @param int $iProgramServiceId
	 * @return Ext_Thebing_School_Tuition_Allocation
	 */
	public static function findByUniqueKeys($iBlockId, $iInquiryCourseId) {

		$oAllocation = new self();

		$sSql = "
			SELECT
				#table_alias.`id`
		";

		$sSql .= $oAllocation->_getDefaultFromPart(false, false, false);

		//nicht auf active = 1 Abfragen!!!
		$sSql .= "
			WHERE
				#table_alias.`block_id` = :block_id AND
				#table_alias.`inquiry_course_id` = :inquiry_course_id
		";

		$aSql = $oAllocation->_getDefaultReplaceArray();

		$aSql['block_id']			= (int)$iBlockId;
		$aSql['inquiry_course_id']	= (int)$iInquiryCourseId;

		$iAllocation = (int)DB::getQueryOne($sSql, $aSql);

		$oAllocation = self::getInstance($iAllocation);

		return $oAllocation;
	}

	/**
	 * @return Ext_Thebing_School_Tuition_Block
	 */
	public function getBlock() {
		$iBlockId	= (int)$this->block_id;
		$oBlock		= Ext_Thebing_School_Tuition_Block::getInstance($iBlockId);

		return $oBlock;
	}

    /**
     * @return Ext_Thebing_Tuition_Classroom
     */
    public function getRoom()
    {
        $iRoomId = (int)$this->room_id;
        $oRoom = Ext_Thebing_Tuition_Classroom::getInstance($iRoomId);
        return $oRoom;
    }

	/**
	 * TODO auf program_service_id umstellen
	 *
	 * @param $sColumnInquiryCourse
	 * @param $sColumnCourse
	 * @param null $sWeek
	 * @return string
	 */
	public static function getSumOfLessonsSubSql($sColumnInquiryCourse, $sColumnCourse, $sWeek = null, bool $bWithCancelled = true) {

		$sWhere = '';
		
		if($sWeek) {
			$sWhere .= ' AND `ktb2`.`week` = ' . $sWeek;
		}

		if (!$bWithCancelled) {
			$sWhere .= " AND
				(
					`ts_tbdu`.`state` IS NULL ||
					NOT `ts_tbdu`.`state` & '".Unit::STATE_CANCELLED."'
				)
			";
		}
		
		$sSql = "
			SELECT
				SUM(`ktt2`.`lessons`)
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic2` INNER JOIN
				`ts_inquiries_journeys_courses` `kic2` ON
					`kic2`.`id` = `ktbic2`.`inquiry_course_id` AND
					`kic2`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks` `ktb2` ON
					`ktb2`.`id` = `ktbic2`.`block_id` AND
					`ktb2`.`active` = 1 INNER JOIN
				`kolumbus_tuition_templates` `ktt2` ON
					`ktt2`.`id` = `ktb2`.`template_id` AND
					`ktt2`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd2` ON
					 `ktb2`.`id` = `ktbd2`.`block_id` LEFT JOIN 
			   	`ts_tuition_blocks_daily_units` `ts_tbdu` ON 
			   		`ts_tbdu`.`block_id` = `ktb2`.`id` AND
			   		`ts_tbdu`.`day` = `ktbd2`.`day` INNER JOIN
				`kolumbus_tuition_classes_courses` `ktclc2` ON
					`ktb2`.`class_id` = `ktclc2`.`class_id` AND
					`ktbic2`.`course_id` = `ktclc2`.`course_id` INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ktb2`.`school_id`
			 WHERE
				`ktbic2`.`inquiry_course_id` = ".$sColumnInquiryCourse." AND
				`ktbic2`.`course_id` = ".$sColumnCourse." AND
				`ktbic2`.`active` = 1 AND
				getRealDateFromTuitionWeek(
					`ktb2`.`week`,
					`ktbd2`.`day`,
					`cdb2`.`course_startday`
				) BETWEEN `kic2`.`from` AND `kic2`.`until`				
				".$sWhere."
		";
		
		return $sSql;
	}

	/**
	 * @param $sColumnInquiryId
	 * @param null $sWeek
	 * @return string
	 */
	public static function getSumOfInquiryLessonsSubSql($sColumnInquiryId, $sWeek = null, bool $bWithCancelled = true) {

		$sWhere = '';

		if($sWeek) {
			$sWhere .= ' AND `ktb2`.`week` = ' . $sWeek;
		}

		if (!$bWithCancelled) {
			$sWhere .= " AND
				(
					`ts_tbdu`.`state` IS NULL ||
					NOT `ts_tbdu`.`state` & '".Unit::STATE_CANCELLED."'
				)
			";
		}

		$sSql = "
			SELECT
				SUM(`ktt2`.`lessons`)
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic2` INNER JOIN
				`ts_inquiries_journeys_courses` `kic2` ON
					`kic2`.`id` = `ktbic2`.`inquiry_course_id` AND
					`kic2`.`active` = 1 INNER JOIN
				`ts_inquiries_journeys` `ts_ij2` ON
					`ts_ij2`.`id` = `kic2`.`journey_id` INNER JOIN
				`kolumbus_tuition_blocks` `ktb2` ON
					`ktb2`.`id` = `ktbic2`.`block_id` AND
					`ktb2`.`active` = 1 INNER JOIN
				`kolumbus_tuition_templates` `ktt2` ON
					`ktt2`.`id` = `ktb2`.`template_id` AND
					`ktt2`.`active` = 1 INNER JOIN
				`kolumbus_tuition_blocks_days` `ktbd2` ON
					 `ktb2`.`id` = `ktbd2`.`block_id` LEFT JOIN 
			   	`ts_tuition_blocks_daily_units` `ts_tbdu` ON 
			   		`ts_tbdu`.`block_id` = `ktb2`.`id` AND
			   		`ts_tbdu`.`day` = `ktbd2`.`day` INNER JOIN
				`kolumbus_tuition_classes_courses` `ktclc2` ON
					`ktb2`.`class_id` = `ktclc2`.`class_id` AND
					`ktbic2`.`course_id` = `ktclc2`.`course_id` INNER JOIN
				`customer_db_2` `cdb2` ON
					`cdb2`.`id` = `ktb2`.`school_id`
			WHERE
				`ktbic2`.`active` = 1 AND
				`ts_ij2`.`inquiry_id` = ".$sColumnInquiryId." AND
				getRealDateFromTuitionWeek(
					`ktb2`.`week`,
					`ktbd2`.`day`,
					`cdb2`.`course_startday`
				) BETWEEN `kic2`.`from` AND `kic2`.`until`
				".$sWhere."
		";

		return $sSql;
	}

	/**
	 * @return Ext_TS_Inquiry_Journey_Course 
	 */
	public function getJourneyCourse() {
		$oJourneyCourse = $this->getJoinedObject('inquiry_course');
		return $oJourneyCourse;
	}
	
	/**
	 * @return Ext_Thebing_Tuition_Course 
	 */
	public function getCourse() {
		$oCourse = $this->getJoinedObject('course');
		return $oCourse;
	}

	public function getProgramService(): \TsTuition\Entity\Course\Program\Service {
		$oCourse = $this->getJoinedObject('program_service');
		return $oCourse;
	}

	public function getTuitionCourseWeekStatus(): ?string
	{
		$blockWeek = \Carbon\Carbon::parse($this->getBlock()->week);
		$status = $this->getJourneyCourse()->getTuitionCourseWeekStatus($blockWeek);

		return $status;
	}

	/**
	 * Summe der Lektionsdauer pro Tag
	 *
	 * @param bool $sFrom
	 * @param bool $sUntil
	 * @param bool $bEncode
	 * @return array|null|string
	 */
	public function getLessonDurations($sFrom = false, $sUntil = false, $bEncode=false) {

		if($this->_aLessonDurations === null)
		{
			// Wenn noch nicht initalisiert, aufbauen...
			
			$aLessonDurations	= array();
			$oBlock				= $this->getBlock();
			$oTemplate			= $oBlock->getTemplate();
			$sWeek				= $oBlock->week;
			$aDays				= (array)$oBlock->days;
			$oCourse			= $this->getCourse();
			$fDuration			= $oBlock->getLessonDuration();

			// Alle Wochentage durchlaufen
			foreach($aDays as $iDay)
			{
				// immer 1Tag weniger addieren, da die Woche eh immer mit dem Montag anfängt
				$iDaysToAdd = $iDay - 1;
				
				$oDate = new WDDate($sWeek, WDDate::DB_DATE);
				$oDate->add($iDaysToAdd, WDDate::DAY);
				
				$sDateOfDay = $oDate->get(WDDate::DB_DATE);
				
				$aLessonDurations[$sDateOfDay] = $fDuration;
			}
			
			// Falls ein Kurs nicht ab Montag beginnt, müssen auch die Kurs von/bis Daten gefiltert werden
			$aLessonDurations	= $this->filterJourneyCourseDates($aLessonDurations);
			
			$this->_aLessonDurations = $aLessonDurations;
		}
		
		$aLessonDurations = $this->_aLessonDurations;
		
		// Wenn Zeitraum angegeben, filtern
		$aLessonDurations = $this->filterDates($aLessonDurations, $sFrom, $sUntil);

		if($bEncode)
		{
			$aLessonDurations = json_encode($aLessonDurations);
		} 

		return $aLessonDurations;
	}

	/**
	 * Zu der Zuweisung verbundene Anwesenheit
	 *
	 * @return Ext_Thebing_Tuition_Attendance|null
	 */
	public function getAttendance(): ?Ext_Thebing_Tuition_Attendance {

		$aAttendances = $this->getJoinedObjectChilds('attendance');
		
		if(!empty($aAttendances)) {
			// Es gibt nur ein Anwesenheitseintrag pro Zuweisung
			return reset($aAttendances);
		}
		
		return null;
	}

	/**
	 * Anwesenheitsliste pro Tag
	 *
	 * @param bool $sFrom
	 * @param bool $sUntil
	 * @param bool $bEncode
	 * @return array|null|string
	 */
	public function getAttendances($sFrom = false, $sUntil = false, $bEncode = false) {

		if($this->_aAttendances === null)
		{
			$aAttendances	= array();
			$oAttendance	= $this->getAttendance();

			// Falls Anwesenheit zu der Zuweisung existiert
			if($oAttendance)
			{
				$oBlock		= $this->getBlock();
				$sWeek		= $oBlock->week;
				$aDays		= (array)$oBlock->days;
				$aAttendanceDays = Ext_Thebing_Tuition_Attendance::DAY_MAPPING;

				// Alle Wochentage durchlaufen
				foreach($aDays as $iDay)
				{
					$sDay = $aAttendanceDays[$iDay];
					
					// immer 1Tag weniger addieren, da die Woche eh immer mit dem Montag anfängt
					$iDaysToAdd = $iDay - 1;

					$oDate = new WDDate($sWeek, WDDate::DB_DATE);
					$oDate->add($iDaysToAdd, WDDate::DAY);

					$sDateOfDay = $oDate->get(WDDate::DB_DATE);

					$aAttendances[$sDateOfDay] = $oAttendance->$sDay;
				}

				// Falls ein Kurs nicht ab Montag beginnt, müssen auch die Kurs von/bis Daten gefiltert werden
				$aAttendances = $this->filterJourneyCourseDates($aAttendances);
			}
			
			$this->_aAttendances = $aAttendances;
		}
		
		$aAttendances = $this->_aAttendances;
		
		// Wenn Zeitraum angegeben, filtern
		$aAttendances = $this->filterDates($aAttendances, $sFrom, $sUntil);
		
		if($bEncode)
		{
			$aAttendances = json_encode($aAttendances);
		}

		return $aAttendances;
	}

	/**
	 * Ein Array mit verschiedenen Datumszeitpunkten nach einem von/bis Zeitraum filtern
	 *
	 * @param $aDates
	 * @param $sFrom
	 * @param $sUntil
	 * @return mixed
	 * @throws Exception
	 */
	public function filterDates($aDates, $sFrom, $sUntil) {

		$oDate = new WDDate();
		
		// Wenn Zeitraum angegeben, filtern
		if($sFrom && $sUntil)
		{
			if(
				WDDate::isDate($sFrom, WDDate::DB_DATE) &&
				WDDate::isDate($sUntil, WDDate::DB_DATE)
			)
			{
				foreach($aDates as $sDateOfDay => $fDuration)
				{
					$oDate->set($sDateOfDay, WDDate::DB_DATE);

					if(
						!$oDate->isBetween(WDDate::DB_DATE, $sFrom, $sUntil)
					)
					{
						unset($aDates[$sDateOfDay]);
					}
				}
			}
			else
			{
				throw new Exception('invalid from or until!');
			}
		}
		elseif(
			$sFrom ||
			$sUntil
		)
		{
			throw new Exception('you cant set only one of the timeframe!');
		}
		
		return $aDates;
	}

	/**
	 * Überprüfen ob bestimmte Zeiträume im gebuchten Kurszeitraum stattfinden
	 *
	 * @param array $aDates
	 * @return array|mixed
	 */
	public function filterJourneyCourseDates(array $aDates) {

		$oJourneyCourse		= $this->getJourneyCourse();
		$sFromMin			= $oJourneyCourse->from;
		$sUntilMax			= $oJourneyCourse->until;

		$aDates				= $this->filterDates($aDates, $sFromMin, $sUntilMax);
		
		return $aDates;
	}

	/**
	 * Summe der Lektionsdauer pro Tag manuell setzen
	 * 
	 * @param array $aLessonDurations
	 */
	public function setLessonDurations(array $aLessonDurations) {
		$this->_aLessonDurations = $aLessonDurations;
	}
	
	/**
	 *
	 * Anwesenheiten pro Tag manuell setzen
	 * 
	 * @param array $aAttendances 
	 */
	public function setAttendances(array $aAttendances)
	{
		$this->_aAttendances = $aAttendances;
	}
	
	/**
	 *
	 * Diese Funktion wird vom Anwesenheitsindex benutzt(für Ansicht "Bearbeiten"), um nach der Blockwoche zu filtern
	 * 
	 * @return string 
	 */
	public function getBlockWeek()
	{
		$oBlock			= $this->getBlock();
		$sBlockWeek		= $oBlock->week;
		
		return $sBlockWeek;
	}
	
	/**
	 *
	 * Diese Funktion wird vom Anwesenheitsindex benutzt(für Ansicht "Liste"), um den ersten/letzten Tag der Blockwoche zu erhalten
	 * 
	 * @return string 
	 */
	public function getBlockWeekLimit($sType)
	{
		$oBlock			= $this->getBlock();
		$aDays			= $oBlock->days;
		$sBlockWeek		= $oBlock->week;
		
		sort($aDays);
		
		if($sType == 'start')
		{
			$iDay		= reset($aDays);
		}
		else
		{
			$iDay		= end($aDays);
		}
		
		$iDaysToAdd		= $iDay - 1;
		
		$oDate			= new WDDate($sBlockWeek, WDDate::DB_DATE);
		$oDate->add($iDaysToAdd, WDDate::DAY);
		
		$sBlockLimit	= $oDate->get(WDDate::DB_DATE);
		
		if(!WDDate::isDate($sBlockLimit, WDDate::DB_DATE))
		{
			return null;
		}

		return $sBlockLimit;
	}
	
	/**
	 *
	 * Eingetragene Abwesenheit pro Tag liefern
	 * 
	 * @param string $sDay (Tagkürzel, "mo","di","mi","do","fr","sa","so")
	 */
	public function getAttendanceValue($sDay)
	{
		$fAttendance	= 0;
		$oAttendance	= $this->getAttendance();

		if($oAttendance)
		{
			$fAttendance = $oAttendance->$sDay;
		}

		return $fAttendance;
	}
	
	/**
	 * Wird für den Index benutzt um Zuweisungen zu finden mit der gleichen Woche+gebuchter Kurs+Lehrer
	 * 
	 * @return array 
	 */
	public function getSameAllocationsJourneyCourseTeacher($bFilterWeek=true)
	{
		$oBlock		= $this->getBlock();
		
		if(!$oBlock->hasTeacher())
		{
			// Wenn kein Lehrer, dann nur eigene Zuweisung speichern
			$aResult	= array($this->id);
		}
		else
		{
			$aResult	= $this->getSameAllocations('journey_course_teacher', $bFilterWeek);
		}
		
		return $aResult;
	}
	
	/**
	 * Wird für den Index benutzt um Zuweisungen zu finden mit der gleichen Woche+gebuchter Kurs
	 * 
	 * @return array 
	 */
	public function getSameAllocationsJourneyCourse($bFilterWeek=true)
	{
		$aResult = $this->getSameAllocations('journey_course', $bFilterWeek);
		
		return $aResult;
	}
	
	/**
	 * Wird für den Index benutzt um Zuweisungen zu finden mit der gleichen Woche+gleiche Buchung
	 * 
	 * @return array 
	 */
	public function getSameAllocationsInquiry($bFilterWeek=true)
	{
		$aResult = $this->getSameAllocations('inquiry', $bFilterWeek);
		
		return $aResult;
	}
	
	/**
	 * Anwesenheit berechnen und ausgeben für "gebuchten Kurs" + "Lehrer" nicht zeitbezogen
	 * 
	 * @return string
	 */
	public function getJourneyCourseTeacherAttendanceAll()
	{
		$aAllocations	= $this->getSameAllocationsJourneyCourseTeacher(false);

		$fAttendance	= $this->getAttendanceByAllocations($aAllocations);

		return $fAttendance;
	}
	
	/**
	 * Anwesenheit berechnen und ausgeben für "gebuchten Kurs" nicht zeitbezogen
	 * 
	 * @return string
	 */
	public function getJourneyCourseAttendanceAll()
	{
		$aAllocations	= $this->getSameAllocationsJourneyCourse(false);

		$fAttendance	= $this->getAttendanceByAllocations($aAllocations);

		return $fAttendance;
	}
	
	/**
	 * Anwesenheit berechnen und ausgeben für "gebuchten Kurs" nicht zeitbezogen
	 * 
	 * @return string
	 */
	public function getInquiryAttendanceAll()
	{
		$aAllocations	= $this->getSameAllocationsInquiry(false);

		$fAttendance	= $this->getAttendanceByAllocations($aAllocations);

		return $fAttendance;
	}
	
	public function getAttendanceByAllocations(array $aAllocations)
	{
		$oCalculator	= new Ext_Thebing_Tuition_Attendance_Calculator();

		foreach($aAllocations as $iAllocation)
		{
			$oAllocation = self::getInstance($iAllocation);
			
			$oCalculator->addAllocation($oAllocation);
		}
		
		$fAttendance = $oCalculator->calculate();
		
		return $fAttendance;
	}
	
	/**
	 * Wird für den Index benutzt um Zuweisungen zu finden mit gleichen Typen
	 * 
	 * @return array 
	 */
	public function getSameAllocations($sType, $bFilterWeek=true)
	{
		if($sType == 'inquiry')
		{
			$sWhere = ' AND
				`ts_i_j`.`inquiry_id` = :inquiry_id
			';
		}
		elseif($sType == 'journey_course')
		{
			$sWhere = ' AND
				`kic`.`id` = :inquiry_course_id
			';
		}
		else
		{
			$sWhere = ' AND
				`kic`.`id` = :inquiry_course_id AND
				`ktb`.`teacher_id` = :teacher_id
			';	
		}
		
		$aSql = $this->_getDefaultReplaceArray();

		$sSql = "
			SELECT
				#table_alias.`id`
		";

		$sSql .= $this->_getDefaultFromPart('INNER', 'INNER', false);
		
		$sSql .= " INNER JOIN
			`ts_inquiries_journeys` `ts_i_j` ON
				`ts_i_j`.`id` = `kic`.`journey_id` AND
				`ts_i_j`.`active` = 1 AND
				`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."'
		";
		
		$sSql .= ' WHERE
			#table_alias.`active` = 1
		';
		
		$sSql .= $sWhere;

		$oBlock						= $this->getBlock();
		$oJourneyCourse				= $this->getJourneyCourse();
		$oInquiry					= $oJourneyCourse->getInquiry();
		
		$aSql['inquiry_course_id']	= (int)$this->inquiry_course_id;
		$aSql['teacher_id']			= (int)$oBlock->teacher_id;
		$aSql['inquiry_id']			= (int)$oInquiry->id;
		
		if($bFilterWeek)
		{
			$sSql .= ' AND
				`ktb`.`week` = :week
			';
			
			$aSql['week']				= $oBlock->week;
		}
		
		$aResult = (array)DB::getQueryCol($sSql, $aSql);

		return $aResult;
	}

	/**
	 * -1 = N/A
	 *  0 = nicht kritisch
	 *  1 = kritisch
	 * @return int
	 */
	public function isAttendanceCritical()
	{
		$iIsCritical = -1;
		$fAttendance = $this->getInquiryAttendanceAll();

		if(
			$fAttendance !== false &&
			$fAttendance != -1
		) {
			$oJourneyCourse = $this->getJourneyCourse();
			$oSchool = $oJourneyCourse->getSchool();
			$iCritical = (int)$oSchool->critical_attendance;

			if($fAttendance <= $iCritical) {
				$iIsCritical = 1;
			} else {
				$iIsCritical = 0;
			}
		}

		return $iIsCritical;
	}
	
	public function getAttendanceScore()
	{
		$oAttendance = $this->getAttendance();
		
		if($oAttendance)
		{
			return $oAttendance->score;
		}
		else
		{
			return '';
		}
	}
	
	public function getAttendanceComment()
	{
		$oAttendance = $this->getAttendance();
		
		if($oAttendance)
		{
			return $oAttendance->comment;
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Die isNew() Funktion in dieser Klasse speziell ableiten, da bei den Zuweisungen nach inaktiven Einträgen gesucht und so
	 * aktiviert & abgespeichert wird, das passt dem Index aber überhaupt nicht, da die nur anhand der ID feststellen kann ob
	 * ein Eintrag neu oder alt ist
	 * 
	 * @return type 
	 */
	public function isNew()
	{
		if($this->bIsNew !== null)
		{
			return $this->bIsNew;
		}
		else
		{
			return parent::isNew();
		}
	}
	
	/**
	 * Überprüfen ob irgendein Block-Tag in den Kurszeitraum passt
	 * 
	 * @return boolean 
	 */
	public function hasValidBlockDays()
	{
		$bSuccess			= false;
		$oBlock				= $this->getBlock();
		$aDays				= $oBlock->days;
		$sWeek				= $oBlock->week;
		$oJourneyCourse		= $this->getJourneyCourse();
		$sCourseFrom		= $oJourneyCourse->from;
		$sCourseUntil		= $oJourneyCourse->until;
		
		foreach($aDays as $iDay)
		{
			$oDate		= new WDDate($sWeek, WDDate::DB_DATE);
			
			$iDaysToAdd = $iDay - 1;
			
			$oDate->add($iDaysToAdd, WDDate::DAY);
			
			if($oDate->isBetween(WDDate::DB_DATE, $sCourseFrom, $sCourseUntil))
			{
				$bSuccess = true;
			}
		}
		
		return $bSuccess;
	}
	
	/**
	 * Aktueller Fortschritt pro Zuweisung
	 * @return type 
	 */
	public function getProgress()
	{
		$oBlock				= $this->getBlock();
		$oDate				= new WDDate($oBlock->week, WDDate::DB_DATE);
		$oDate->set(7, WDDate::WEEKDAY);
		$sUntil				= $oDate->get(WDDate::DB_DATE);
		
		$oCourse			= $this->getCourse();
		$oLevelGroup		= $oCourse->getLevelgroup();
		
		$iProgress			= Ext_Thebing_Tuition_Progress::getProgress($this->inquiry_course_id, 'period', 'id', $sUntil, $oLevelGroup->id);
		
		return $iProgress;
	}
	
	public function manipulateSqlPartsAfterBuild(&$aSqlParts, Ext_Gui2 $oGui) {

		$sLang = \Ext_Thebing_School::fetchInterfaceLanguage();

		if($oGui->sView == 'edit')
		{
			$aWeekFilter = array(
				'week' => ':filter_course_period_from',
			);
		}
		else
		{
			$aWeekFilter = array(
				'week_from' => ':filter_course_period_from',
				'week_until' => ':filter_course_period_until',
			);
		}

		// Allgemein
		$sJourneyCourseTeacherAllAttendance = Ext_Thebing_Tuition_Attendance::getAttendanceSql('journey_course_teacher', array(
			'journey_course_id' => '`ts_ijc`.`id`',
			'teacher_id' => '`ktb`.`teacher_id`',
			'block_id' => '`ktbic`.`id`'
		));
		
		$sJourneyCourseAllAttendance = Ext_Thebing_Tuition_Attendance::getAttendanceSql('journey_course', array(
			'journey_course_id' => '`ts_ijc`.`id`',
			'course_id' => '`ktc`.`id`'
		));
		
		$sInquiryAllAttendance = Ext_Thebing_Tuition_Attendance::getAttendanceSql('inquiry', array(
			'inquiry_id' => '`ts_i`.`id`'
		));
		
		// Zeitbezogen
		$sJourneyCourseTeacherPeriodAttendance = Ext_Thebing_Tuition_Attendance::getAttendanceSql('journey_course_teacher', array(
			'journey_course_id' => '`ts_ijc`.`id`',
			'teacher_id' => '`ktb`.`teacher_id`',
			'block_id' => '`ktbic`.`id`',
			'week_filter' => $aWeekFilter,
		));

		$sJourneyCoursePeriodAttendance = Ext_Thebing_Tuition_Attendance::getAttendanceSql('journey_course', array(
			'journey_course_id' => '`ts_ijc`.`id`',
			'course_id' => '`ktc`.`id`',
			'week_filter' => $aWeekFilter,
		));
		
		$sInquiryPeriodAttendance = Ext_Thebing_Tuition_Attendance::getAttendanceSql('inquiry', array(
			'inquiry_id' => '`ts_i`.`id`',
			'week_filter' => $aWeekFilter,
		));

		// Erwartete Anwesenheit (absolut)
		$sInquiryAllExpecteAttendance = Ext_Thebing_Tuition_Attendance::getAttendanceSql('inquiry', [
			'inquiry_id' => '`ts_i`.`id`',
			'expected' => true
		]);

		$sJourneyCourseAllExpectedAttendance = Ext_Thebing_Tuition_Attendance::getAttendanceSql('journey_course', [
			'journey_course_id' => '`ts_ijc`.`id`',
			'course_id' => '`ktc`.`id`',
			'expected' => true
		]);

		$aSqlParts['select'] .= "
			,
			`tc_c_n`.`number` `customer_number`,
			`tc_c`.`firstname`,
			`tc_c`.`lastname`,
			CONCAT(`tc_c`.`lastname`, ', ', `tc_c`.`firstname`)`customer_name`,
			`tc_c`.`nationality`,
			`tc_c`.`birthday` `customer_birthday`,
			`ktcl`.`id` `class_id`,
			`ktcl`.`name` `class_name`,
			`ts_ijc`.`from` `course_start`,
			`ts_ijc`.`until` `course_end`,
			`ts_ijc`.`index_attendance_warning`,
			`ts_ijc`.`courselanguage_id`,
			`kt`.`firstname` `teacher_firstname`,
			`kt`.`lastname` `teacher_lastname`,
			CONCAT(`kt`.`lastname`, ', ', `kt`.`firstname`) `teacher_name`, /* ORDER BY */
			`ktc`.`name_short` `course_name_short`,
			`tc_c`.`corresponding_language` `corresponding_language`,
			`ts_j_t_v_d`.`status` `visa`,
			`kta`.`id` `attendance_id`,
			`kta`.`mo`,
			`kta`.`di`,
			`kta`.`mi`,
			`kta`.`do`,
			`kta`.`fr`,
			`kta`.`sa`,
			`kta`.`so`,
			`kta`.`excused`,
			`kta`.`online`,
			`kta`.`score`,
			`kta`.`comment`,
            `kta`.`student_login_release`,
			`kta`.`absence_reasons`,
			`ts_i`.`id` `inquiry_id`,
			`ts_i_j`.`id` `journey_id`,
            `ts_i`.`inbox` `inbox`,
            `ts_i`.`agency_id`,
            `ts_i`.`checkin`,
			`kg`.`short` `group_short`,
			GROUP_CONCAT(`ktbd`.`day`) `days`,
			`ktb`.`week` `block_week`,
			`ts_ijc`.`from` `journey_course_from`,
			`ts_ijc`.`until` `journey_course_until`,
			`ktt`.`name` `template_name`,
			`ktt`.`from` `template_from`,
			`ktt`.`until` `template_until`,
			`ktt`.`custom` `template_custom`,
			`ktl`.`name_short` `level_name_short`,
			`ktcc`.`name_{$sLang}` `course_category_name`,
			`ts_i`.`sponsored`,
			(
				".$sJourneyCourseTeacherAllAttendance."
			) `journey_course_teacher_all_attendance`,
			(
				".$sJourneyCourseAllAttendance."
			) `journey_course_all_attendance`,
			(
				".$sInquiryAllAttendance."
			) `inquiry_all_attendance`,
			(
				".$sJourneyCourseTeacherPeriodAttendance."
			) `journey_course_teacher_period_attendance`,
			(
				".$sJourneyCoursePeriodAttendance."
			) `journey_course_period_attendance`,
			(
				".$sInquiryPeriodAttendance."
			) `inquiry_period_attendance`,
			(
				".$sInquiryAllExpecteAttendance."
			) `inquiry_all_expected_attendance`,
			(
				".$sJourneyCourseAllExpectedAttendance."
			) `journey_course_all_expected_attendance`,
			(
				SELECT
					GROUP_CONCAT(
						CONCAT(`sub_ts_tbdd`.`day`, '-',`sub_ts_tbdd`.`state`+0) 
						SEPARATOR '|'
					) `states`
				FROM
					`ts_tuition_blocks_daily_units` `sub_ts_tbdd`
				WHERE
					`sub_ts_tbdd`.`block_id` = `ktb`.`id`
			) `block_states`,
			IF(`kta`.`id` IS NULL, 0, 1) `has_attendance`,
			IF(:attendance_view != 'inquiry', `ts_ijc`.`id`, NULL) `inquiry_journey_course_id_communication`
		";
		
		$aSqlParts['from'] .= " JOIN
			#allocation_ids_tmp_tbl `ts_aitt` ON
				`ktbic`.`id` = `ts_aitt`.`id` INNER JOIN 
			`kolumbus_tuition_blocks` `ktb` ON
				`ktb`.`id` = `".$this->_sTableAlias."`.`block_id` AND
				`ktb`.`active` = 1 INNER JOIN
			`kolumbus_tuition_classes` `ktcl` ON
				`ktcl`.`id` = `ktb`.`class_id` AND
				`ktcl`.`active` = 1 INNER JOIN
			`ts_inquiries_journeys_courses`	`ts_ijc` ON
				`ts_ijc`.`id` = `".$this->_sTableAlias."`.`inquiry_course_id` AND
				`ts_ijc`.`active` = 1 INNER JOIN
			`ts_inquiries_journeys` `ts_i_j` ON
				`ts_i_j`.`id` = `ts_ijc`.`journey_id` AND
				`ts_i_j`.`active` = 1 AND
				`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."' INNER JOIN
			`ts_inquiries` `ts_i` ON
				`ts_i`.`id` = `ts_i_j`.`inquiry_id` AND
				`ts_i`.`active` = 1 LEFT JOIN
			`kolumbus_groups` `kg` ON
				`kg`.`id` = `ts_i`.`group_id` INNER JOIN
			`ts_inquiries_to_contacts` `ts_i_to_c` ON
				`ts_i_to_c`.`inquiry_id` = `ts_i`.`id` AND
				`ts_i_to_c`.`type` = 'traveller' INNER JOIN
			`tc_contacts` `tc_c` ON
				`tc_c`.`id` = `ts_i_to_c`.`contact_id` AND
				`tc_c`.`active` = 1 INNER JOIN
			`tc_contacts_numbers` `tc_c_n` ON
				`tc_c_n`.`contact_id` = `tc_c`.`id` INNER JOIN
			`kolumbus_tuition_templates` `ktt` ON
				`ktt`.`id` = `ktb`.`template_id` AND
				`ktt`.`active` = 1 INNER JOIN
			`kolumbus_tuition_courses` `ktc` ON
				`ktc`.`id` = `".$this->_sTableAlias."`.`course_id` AND
				`ktc`.`active` = 1 INNER JOIN
			`ts_tuition_coursecategories` `ktcc` ON
				`ktcc`.`id` = `ktc`.`category_id` LEFT JOIN
			 `ts_journeys_travellers_visa_data` `ts_j_t_v_d` ON
				`ts_j_t_v_d`.`journey_id` = `ts_i_j`.`id` AND
				`ts_j_t_v_d`.`traveller_id` = `tc_c`.`id` LEFT JOIN
			`kolumbus_tuition_attendance` `kta` ON
				`kta`.`allocation_id` = `".$this->_sTableAlias."`.`id` AND
				`kta`.`active` = 1 LEFT JOIN
			`ts_teachers`	`kt` ON
				`kt`.`id` = `ktb`.`teacher_id` AND
				`kt`.`active` = 1 INNER JOIN
			`kolumbus_tuition_blocks_days` `ktbd` ON
				`ktbd`.`block_id` = `ktb`.`id` LEFT JOIN
			`kolumbus_tuition_progress` `ktp` ON
				`ktp`.`courselanguage_id` = `ts_ijc`.`courselanguage_id` AND
				`ktp`.`inquiry_id` = `ts_i`.`id` AND
				`ktp`.`active` = 1 AND
				`ktp`.`week` = (
					SELECT
						MAX(`ktp_sub`.`week`)
					FROM
						`kolumbus_tuition_progress` `ktp_sub`
					WHERE
						`ktp_sub`.`courselanguage_id` = `ts_ijc`.`courselanguage_id` AND
						`ktp_sub`.`inquiry_id` = `ts_i`.`id` AND
						`ktp_sub`.`week` <= :filter_course_period_until
				) LEFT JOIN
			/* TODO: Wird kptr noch benötigt? Theoretisch müsste hier wg. Zuweisung bereits immer ein Progress-Eintrag existieren */
			`ts_placementtests_results` `kptr` ON
				`kptr`.`inquiry_id` = `ts_i`.`id` AND
				`kptr`.`active` = 1 LEFT JOIN
			`ts_tuition_levels` `ktl` ON
				`ktl`.`id` = IFNULL(`ktp`.`level`, `kptr`.`level_id`) AND
				`ktl`.`active` = 1
		";
		
		$aSqlParts['where'] .= " AND (
				`kta`.`id` IS NULL OR
				`kta`.`active` = 1
			) AND
			`ts_i`.`canceled` <= 0
		";

		$aSqlParts['groupby'] = "
			IF(
				:attendance_view = 'inquiry',
				`ts_i`.`id`,
				IF(
					:attendance_view = 'journey_course',
					CONCAT(`ts_ijc`.`id`, '_', `ktc`.`id`),
					IF(
						:attendance_view = 'journey_course_teacher',
						CONCAT(`ts_ijc`.`id`, '_', `ktc`.`id`, '_', `kt`.`id`),
						`".$this->_sTableAlias."`.`id`
					)
				)
			)
		";
		
		$aSqlParts['orderby'] =  "
			`".$this->_sTableAlias."`.`id` DESC
		";
		
	}
	
	protected function _buildQueryData($oGui = null) {

		$aQueryData = parent::_buildQueryData();
		
		$sSql = $aQueryData['sql'];
		
		$aQueryParts = DB::splitQuery($sSql);
		
		$this->manipulateSqlPartsAfterBuild($aQueryParts, $oGui);
		
		$sSqlNew = DB::buildQueryPartsToSql($aQueryParts);
		
		$aQueryData['sql'] = $sSqlNew;

		return $aQueryData;
	}
	
	public function getAttendancePossibleAllocationsTmpTable($sAttendanceView, array $aFilter)
	{
		$aSql = $this->_getDefaultReplaceArray();

		$sSql = 'SELECT #table_alias.`id`';
		$sSql .= $this->_getDefaultFromPart('INNER', 'INNER', false);
		$sSql .= "
			INNER JOIN `ts_inquiries_journeys` `ts_i_j`
				ON `ts_i_j`.`id` = `kic`.`journey_id`
				AND `ts_i_j`.`active` = 1
				AND `ts_i_j`.`type` & '" . \Ext_TS_Inquiry_Journey::TYPE_BOOKING . "'
		";
		$sSql .= ' WHERE 1 ';

		if (isset($aFilter['school_id'])) {
			$sSql .= ' AND `ts_i_j`.`school_id` = :school_id';
			$aSql['school_id'] = $aFilter['school_id'];
		}

		if (isset($aFilter['week_from'])) {
			if ($sAttendanceView === 'allocation') {
				$sSql .= ' AND `ktb`.`week` = :week';
				$aSql['week'] = $aFilter['week_from'];
			} elseif (isset($aFilter['week_until'])) {
				$sSql .= ' AND `ktb`.`week` >= :week_from AND `ktb`.`week` <= :week_until';
				$aSql['week_from'] = $aFilter['week_from'];
				$aSql['week_until'] = $aFilter['week_until'];
			}
		}

		// Name der temporären Tabelle dynamisch generieren
		$sTmpTable = 'tmp_attendance_ids_' . uniqid();

		// Vorhandene evtl. löschen (Sicherheitsmaßnahme)
		\DB::executeQuery("DROP TEMPORARY TABLE IF EXISTS `$sTmpTable`");

		// Temporäre Tabelle anlegen und füllen
		$createSql = "
			CREATE TEMPORARY TABLE `$sTmpTable` (
				id INT NOT NULL PRIMARY KEY
			) ENGINE=MEMORY
			AS $sSql
		";

		\DB::executePreparedQuery($createSql, $aSql);

		// Optional: Index sicherstellen (MariaDB legt PRIMARY automatisch an)
		// \DB::executeQuery("ALTER TABLE `$sTmpTable` ADD PRIMARY KEY (id)");

		return $sTmpTable;
	}

	public function getAttendancePossibleAllocations($sAttendanceView, array $aFilter)
	{
		$aSql = $this->_getDefaultReplaceArray();
		
		$sSql = 'SELECT #table_alias.`id`';
		
		$sSql .= $this->_getDefaultFromPart('INNER', 'INNER', false);
		
		$sSql .= " INNER JOIN
			`ts_inquiries_journeys` `ts_i_j` ON
				`ts_i_j`.`id` = `kic`.`journey_id` AND
				`ts_i_j`.`active` = 1 AND
				`ts_i_j`.`type` & '".\Ext_TS_Inquiry_Journey::TYPE_BOOKING."'
		";
		
		$sSql .= '
			WHERE 1
		';
		
		if(isset($aFilter['school_id']))
		{
			$sSql .= ' AND
				`ts_i_j`.`school_id` = :school_id
			';
			
			$aSql['school_id'] = $aFilter['school_id'];
		}
		
		if(isset($aFilter['week_from']))
		{
			if($sAttendanceView == 'allocation')
			{
				$sSql .= ' AND
					`ktb`.`week` = :week
				';	
				
				$aSql['week'] = $aFilter['week_from'];
			}
			elseif(isset($aFilter['week_until']))
			{
				$sSql .= ' AND
					`ktb`.`week` >= :week_from AND
					`ktb`.`week` <= :week_until
				';	
				
				$aSql['week_from'] = $aFilter['week_from'];
				$aSql['week_until'] = $aFilter['week_until'];
			}
			
		}
		
		/*
		$sSql .= ' GROUP BY ';
		
		if($sAttendanceView == 'inquiry')
		{
			$sSql .= '`ts_i_j`.`inquiry_id`';
		}
		elseif($sAttendanceView == 'journey_course')
		{
			$sSql .= '`kic`.`id`';
		}
		elseif($sAttendanceView == 'journey_course_teacher')
		{
			$sSql .= '`kic`.`id`, `ktb`.`teacher_id`';
		}
		else
		{
			$sSql .= '#table_alias.`id`';
		}
		*/
		
		$aIds = (array)DB::getQueryCol($sSql, $aSql);
		
		return $aIds;
	}

	public function recalculateLessonsDuration(): static
	{
		$journeyCourse = $this->getJourneyCourse();
		$block = $this->getBlock();

		$this->lesson_duration = $block->calculateAllocationLessonDuration($journeyCourse);

		return $this;
	}

	public function save($bLog = true) {

		// Wenn neu oder gelöscht wurde muss das Kontingent des JourneyCourse angepasst werden
		$changeContingent = $this->isNew() || $this->active !== $this->getOriginalData('active');

		parent::save($bLog);

		/* Dieser Teil scheint keinen Zweck zu erfüllen, da exist() nur false sein kann, wenn es ein neuer Eintrag ist und save fehlschlägt.
		*  Dann existieren aber keine attendances. Save und delete der attendances sollten über joinedObjectChild laufen.
		$oAttendance = $this->getAttendance();
		$this->exist() ? $oAttendance?->save() : $oAttendance?->delete();
		*/

		// Tuition-Index muss aktualisiert werden, da zugewiesene Lektionen wichtig für die Listen in der Klassenplanung sind
		$oInquiry = $this->getJourneyCourse()->getInquiry();

		if ($oInquiry) {
			\Core\Facade\SequentialProcessing::add('ts/tuition-index', $oInquiry);
			\Ext_Gui2_Index_Stack::add('ts_inquiry', $oInquiry->getId(), 3);
		}

		if ($this->exist() && $changeContingent) {
			// Lektionskontingent der Buchung aktualisieren
			$contingent = $this->getJourneyCourse()->getLessonsContingent($this->getProgramService());
			$contingent->refresh(LessonsContingent::USED | LessonsContingent::CANCELLED)->lock()->save();
		}

		System::wd()->executeHook('ts_class_assignment_save', $this);

		return $this;

	}

	/**
	 * Methode für usort() zum Sortieren von Zuweisungen anhand der Start-Uhrzeiten
	 *
	 * @param Ext_Thebing_School_Tuition_Allocation $oAllocation1
	 * @param Ext_Thebing_School_Tuition_Allocation $oAllocation2
	 * @return int
	 */
	public static function sortAllocationsByTime($oAllocation1, $oAllocation2) {

		$oDate1 = $oAllocation1->getBlock()->getStartDay();
		$oDate2 = $oAllocation2->getBlock()->getStartDay();

		if($oDate1 > $oDate2) {
			return 1;
		} elseif($oDate1 < $oDate2) {
			return -1;
		}

		return 0;
	}

	/**
	 * @return int
	 */
	public function getSubObject() {
		return $this->getJourneyCourse()->getJourney()->getSchool()->id;
	}

	public function getCorrespondenceLanguage() {
		$oInquiry = $this->getJourneyCourse()->getJourney()->getInquiry();
		$oContact = $oInquiry->getTraveller();
		return $oContact->getCorrespondenceLanguage();
	}

	/**
	 * Gibt einen Array mit Fehlermeldungen zurück, warum die Zuweisung nicht gelöscht werden darf.
	 * @return array
	 */
	public function validateDelete(): array
	{
		$errors = [];
		$school = \Ext_Thebing_School::getSchoolFromSession();
		if (!$school->tuition_allow_allocation_with_attendances_modification) {
			$attendances = Ext_Thebing_Tuition_Attendance::getActiveAttendances([
				['allocation_id', '=', $this->id]
			]);
			if ($attendances->isNotEmpty()) {
				$travellerName = $this->getJourneyCourse()
					->getJourney()
					->getInquiry()
					->getTraveller()
					->getName();
				$className = $this->getJoinedObject('block')
					->getClass()
					->getName();
				$dateFormat = new Ext_TC_Gui2_Format_Date();
				$week = $dateFormat->format($this->getBlockWeek());
				$errors[] = sprintf(L10N::t('Es gibt eingetragene Anwesenheiten für den Schüler %s in der Klasse %s in der Woche %s.'), $travellerName, $className, $week);
			}
		}
		return $errors;
	}

	public function getCommunicationDefaultApplication(): string
	{
		return \TsTuition\Communication\Application\Allocation::class;
	}

	public function getCommunicationLabel(\Tc\Service\LanguageAbstract $l10n): string
	{
		$inquiry = $this->getJourneyCourse()->getJourney()->getInquiry();
		return $inquiry->getCommunicationLabel($l10n);
	}

	public function getCommunicationSubObject(): CommunicationSubObject
	{
		return $this->getBlock()->getSchool();
	}

	public function getCommunicationAdditionalRelations(): array
	{
		return [
			$this->getJourneyCourse()->getJourney()->getInquiry()
		];
	}
}
