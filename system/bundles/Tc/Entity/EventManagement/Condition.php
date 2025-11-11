<?php

namespace Tc\Entity\EventManagement;

use Core\Database\WDBasic\Builder;
use Tc\Entity\AbstractManagedEntity;
use Tc\Entity\EventManagement;
use Tc\Enums\EventManager\Process\TaskType;
use Tc\Interfaces\EventManager\Process\Task;

/**
 * @property int $event_id
 * @property string $class
 */
class Condition extends AbstractManagedChild
{
	const TYPE = 'condition';

	public function getType(): TaskType
	{
		return TaskType::CONDITION;
	}

	public static function booted()
	{
		static::addGlobalScope('type', function (Builder $builder) {
			$builder->where($builder->getModel()->qualifyColumn('type'), self::TYPE);
		});
	}

}
