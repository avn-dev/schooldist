<?php

namespace Ts\Dto;

use Carbon\CarbonInterface;

/**
 * @see \TsTuition\Generator\StartDatesGenerator
 * @see \Ext_Thebing_Tuition_Course::getStartDatesWithDurations()
 */
class CourseStartDate {

	/**
	 * @var CarbonInterface
	 */
	public $start;

	/**
	 * @var CarbonInterface
	 */
	public $end;

	/**
	 * @var int
	 */
	public $minDuration;

	/**
	 * @var int
	 */
	public $maxDuration;

	/**
	 * @var int[]
	 */
	public $levels = [];

	/**
	 * @var int[]
	 */
	public $courselanguages = [];

	/**
	 * @var CarbonInterface[]
	 */
	public array $endDates = [];

	/**
	 * @param CarbonInterface $start
	 * @param CarbonInterface $end
	 * @param int $minDuration
	 * @param int $maxDuration
	 * @param int[] $levels
	 * @param int[] $courselanguages
	 * @param CarbonInterface[] $endDates
	 */
	public function __construct(
		CarbonInterface $start,
		CarbonInterface $end,
		int $minDuration,
		int $maxDuration,
		array $levels = [],
		array $courselanguages = [],
		array $endDates = []
	) {
		$this->start = $start;
		$this->end = $end;
		$this->minDuration = $minDuration;
		$this->maxDuration = $maxDuration;
		$this->levels = $levels;
		$this->courselanguages = $courselanguages;
		$this->endDates = $endDates;
	}

}