<?php

namespace Tc\Entity\EventManagement;

use Illuminate\Support\Arr;
use Tc\Entity\AbstractManagedEntity;
use Tc\Entity\EventManagement;
use Tc\Interfaces\EventManager\Process\Task;

abstract class AbstractManagedChild extends AbstractManagedEntity implements Task
{
	protected $_sTable = 'tc_event_management_childs';

	protected $_sTableAlias = 'tc_emc';

	protected $_aJoinedObjects = [
		'event' => [
			'class' => EventManagement::class,
			'key' => 'event_id',
			'type' => 'parent'
		]
	];

	public function getIdentifier(): string|int
	{
		return $this->id;
	}

	public function getClass(): string
	{
		return $this->class;
	}

	public function getEvent(): EventManagement
	{
		return $this->getJoinedObject('event');
	}

	public function getSettings(): array
	{
		return $this->getAllMetaData();
	}

	public function getSetting(string $key, $default = null)
	{
		return Arr::get($this->getSettings(), $key, $default);
	}

}