<?php

namespace Ts\Entity\Inquiry\Journey\Course;

use TsTuition\Entity\Course\Program\Service;
use TsTuition\Service\CourseLessonsContingentService;

/**
 * @property int $journey_course_id
 * @property int $program_service_id
 * @property float $absolute
 * @property float $used
 * @property float $cancelled
 * @property float $lessons
 * @property string $lessons_unit
 * @property int $weeks
 */
class LessonsContingent extends \Ext_Thebing_Basic
{
	protected $_sTable = 'ts_inquiries_journeys_courses_lessons_contingent';

	protected $_sTableAlias = 'ts_ijcl';

	const ABSOLUTE = 1;
	const USED = 2;
	const CANCELLED = 4;

	protected $_aFormat = [
		'absolute' => [
			'validate' => 'FLOAT_NOTNEGATIVE'
		],
		'used' => [
			'validate' => 'FLOAT_NOTNEGATIVE'
		],
		'cancelled' => [
			'validate' => 'FLOAT_NOTNEGATIVE'
		]
	];

	protected $_aJoinedObjects = [
		'journey_course' => [
			'class' => \Ext_TS_Inquiry_Journey_Course::class,
			'key' => 'journey_course_id',
			'type' => 'parent',
			'bidirectional' => true
		],
		'program_service' => [
			'class' => Service::class,
			'key' => 'program_service_id',
			'type' => 'parent'
		]
	];

	public function getJourneyCourse(): \Ext_TS_Inquiry_Journey_Course
	{
		return $this->getJoinedObject('journey_course');
	}

	public function getProgramService(): Service
	{
		return $this->getJoinedObject('program_service');
	}

	public function getAbsolute(): float
	{
		return (float)$this->absolute;
	}

	public function getUsed(): float
	{
		return (float)$this->used;
	}

	public function getRemaining(): float
	{
		return bcsub($this->absolute, $this->used, 2);
	}

	public function reduce(float $lessons, int $columns): self
	{
		if ($columns & self::USED) {
			$this->used = bcsub($this->used, $lessons, 2);
		}
		if ($columns & self::CANCELLED) {
			$this->cancelled = bcsub($this->cancelled, $lessons, 2);
		}
		return $this;
	}

	public function add(float $lessons, int $columns): self
	{
		if ($columns & self::USED) {
			$this->used = bcadd($this->used, $lessons, 2);
		}
		if ($columns & self::CANCELLED) {
			$this->cancelled = bcadd($this->cancelled, $lessons, 2);
		}
		return $this;
	}

	public function refresh(int $columns = null): self
	{
		return (new CourseLessonsContingentService($this))->update($columns);
	}

	public function lazyRefresh(int $columns = null): self
	{
		(new CourseLessonsContingentService($this))->lazyUpdate($columns);
		return $this;
	}

	public function validate($bThrowExceptions = false)
	{
		$payload = parent::validate($bThrowExceptions);

		if ($payload === true) {
			$payload = [];

			if ($this->used > $this->absolute) {
				$payload[$this->_sTableAlias.'.used'][] = 'TO_HIGH';
			}

			if (empty($payload)) {
				$payload = true;
			}
		}

		return $payload;
	}

}