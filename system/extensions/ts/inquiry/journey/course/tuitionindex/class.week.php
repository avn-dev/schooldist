<?php

class Ext_TS_Inquiry_Journey_Course_TuitionIndex_Week extends Ext_TS_Inquiry_TuitionIndex_AbstractWeek {

	protected $sTable = 'ts_inquiries_journeys_courses_tuition_index';

	/** @var Ext_Thebing_Tuition_Course */
	protected $oCourse;

	protected $oProgramService;

	/** @var int|float */
	protected $fAllocatedLessions = 0;

	protected $fCancelledLessons = 0;

	/**
	 * @param Ext_Thebing_Tuition_Course $oCourse
	 */
	public function setCourse(Ext_Thebing_Tuition_Course $oCourse) {
		$this->oCourse = $oCourse;
	}

	public function setProgramService(\TsTuition\Entity\Course\Program\Service $oProgramService) {
		$this->oProgramService = $oProgramService;
	}

	/**
	 * @param int|float $fAllocatedLessons
	 */
	public function setAllocatedLessons($fAllocatedLessons) {
		$this->fAllocatedLessions = $fAllocatedLessons;
	}

	public function setCancelledLessons($fCancelledLessons) {
		$this->fCancelledLessons = $fCancelledLessons;
	}

	/**
	 * @inheritdoc
	 */
	public function getSaveData() {

		$aData = parent::getSaveData();

		if(!$this->oEntity instanceof Ext_TS_Inquiry_Journey_Course) {
			throw new BadMethodCallException('Entity is not valid');
		}

		if(!$this->oCourse->exist()) {
			throw new BadMethodCallException('Course is not valid');
		}

		if (!$this->oProgramService->isCourse()) {
			throw new \RuntimeException(sprintf('Tuition index only works with course services of program. Type "%s" given.', $this->oProgramService->type));
		}

		$aData['journey_course_id'] = $this->oEntity->id;
		$aData['course_id'] = $this->oCourse->id;
		$aData['program_service_id'] = $this->oProgramService->id;
		$aData['allocated_lessons'] = $this->fAllocatedLessions;
		$aData['cancelled_lessons'] = $this->fCancelledLessons;

		return $aData;

	}

}
