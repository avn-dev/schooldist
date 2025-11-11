<?php

namespace Ts\Events;

use Ts\Interfaces\Events\SchoolEvent;
use Tc\Events\ManageableScheduler as BaseManageableScheduler;
use Tc\Facades\EventManager;

class ManageableScheduler extends BaseManageableScheduler implements SchoolEvent
{

	public function __construct(
		\DateTime $dateTime,
		private \Ext_Thebing_School $school
	) {
		parent::__construct($dateTime);
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->school;
	}

	public static function getDescription(): ?string
	{
		return sprintf(
			EventManager::l10n()->translate('Dieses Event wird pro Schule an der angegebenen Uhrzeit ausgeführt. Wenn sie die Ausführung auf eine Schule begrenzen wollen fügen Sie bitte die optionale Bedingung "%s" hinzu.'),
			Conditions\SchoolCondition::getTitle()
		);
	}

	public static function manageListenersAndConditions(): void
	{
		// Conditions
		self::addManageableCondition(Conditions\SchoolCondition::class);
	}

}
