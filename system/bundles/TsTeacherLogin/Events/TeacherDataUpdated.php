<?php

namespace TsTeacherLogin\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;

class TeacherDataUpdated implements ManageableEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication;

	public function __construct(
		private readonly \Ext_Thebing_Teacher $teacher
	) {}

	public function getTeacher(): \Ext_Thebing_Teacher
	{
		return $this->teacher;
	}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Lehrer hat seine Daten aktualisiert');
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		$teacher = ($event) ? $event->getTeacher() : new \Ext_Thebing_Teacher();
		return $teacher->getPlaceholderObject();
	}
}