<?php

namespace TsTuition\Entity\Course\Program;

use TsTuition\Entity\Course\Program;
use Carbon\Carbon;

/**
 * @property int $program_id
 * @property string $type
 * @property int $type_id
 * @property string $from (DATE)
 * @property string $until (DATE)
 */
class Service extends \Ext_Thebing_Basic {

	const TYPE_COURSE = 'course';

	protected $_sTableAlias = 'ts_tcps';

	protected $_sTable = 'ts_tuition_courses_programs_services';

	protected $_aJoinedObjects = [
		'program' => [
			'class' => Program::class,
			'key' => 'program_id',
			'type' => 'parent',
			'readonly' => true
		]
	];

	public function getType(): string {
		return $this->type;
	}

	public function getTypeId(): int {
		return (int)$this->type_id;
	}

	public function isCourse(): bool {
		return ($this->type === self::TYPE_COURSE);
	}

	public function getProgram(): Program {
		return $this->getJoinedObject('program');
	}

	public function hasDates(): bool {
		return ($this->from !== null && $this->until !== null);
	}

	public function getFrom(): ?Carbon {

		if($this->from !== null) {
			return Carbon::parse($this->from)->startOfDay();
		}

		return null;
	}

	public function getUntil(): ?Carbon {

		if($this->until !== null) {
			return Carbon::parse($this->until)->endOfDay();
		}

		return null;
	}

	public function getWeeks(): int {

		if(
			!is_null($from = $this->getFrom()) &&
			!is_null($until = $this->getUntil())
		) {
			return $until->diffInWeeks($from);
		}

		return 0;
	}

	public function setService(\Ext_Thebing_Basic $service) {

		if($service instanceof \Ext_Thebing_Tuition_Course) {
			$this->type = self::TYPE_COURSE;
		} else {
			throw new \RuntimeException(sprintf('Service is currently not supported ("%s") for program!', get_class($service)));
		}

		$this->type_id = (int)$service->getId();

		return $this;
	}

	public function getService(): \Ext_Thebing_Tuition_Course {

		switch ($this->type) {
			case self::TYPE_COURSE:
				$service = \Ext_Thebing_Tuition_Course::getInstance($this->type_id);
				break;
			default:
				throw new \RuntimeException(sprintf('Unknown program service type "%s"!', $this->type));
		}

		return $service;
	}

}
