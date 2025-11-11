<?php

namespace TsTuition\Dto;

use Carbon\Carbon;
use Ext_TS_Inquiry_Journey_Course;
use Tc\Traits\Placeholder;
use TsTuition\Entity\Course\Program\Service;

class StudentCourseWeekAllocation
{
	use Placeholder;

	protected $_sPlaceholderClass = \TsTuition\Service\Placeholder\StudentCourseWeekAllocation::class;

	public function __construct(
		private ?Carbon $week = null,
		private ?\Ext_TS_Inquiry_Journey_Course $journeyCourse = null,
		private ?Service $programService = null
	) {}

	public function getWeek(): ?Carbon
	{
		return $this->week;
	}

	public function getJourneyCourse(): ?Ext_TS_Inquiry_Journey_Course
	{
		return $this->journeyCourse;
	}

	public function getCourse(): ?\Ext_Thebing_Tuition_Course
	{
		return $this->programService?->getService();
	}

}