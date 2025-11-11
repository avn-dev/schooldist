<?php

namespace TsTuition\Events;

use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagementData;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\EventManager\Process;
use Ts\Interfaces\Events\InquiryEvent;
use Ts\Traits\Events\Manageable\WithManageableExecutionTime;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Traits\Events\Manageable\WithManageableCustomerCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;

// Nicht benutzen!
class LastLevelChange implements ManageableEvent, InquiryEvent
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableExecutionTime,
		WithManageableCustomerCommunication,
		WithManageableSchoolCommunication,
		WithManageableSystemUserCommunication;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Anzahl an Wochen in einem Level');
	}

	public function __construct(
		private \Ext_TS_Inquiry_Journey_Course $journeyCourse,
	) {}

	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->journeyCourse->getJourney()->getInquiry();
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->journeyCourse->getJourney()->getSchool();
	}

	public static function dispatchScheduled(Carbon $time, Process $process, \Ext_Thebing_School $school): void
	{
		$check = $time->clone()
			->subWeeks($process->getSetting('weeks'))
			->startOfWeek(Carbon::MONDAY);

		// Kurse herausfinden welche seit X Wochen keine Änderung des Levels hatten
		$journeyCourses = \Ext_TS_Inquiry_Journey_Course::query()
			->select('ts_ijc.*')
			->join('ts_inquiries_journeys as ts_ij', function (JoinClause $join) use ($school) {
				$join->on('ts_ij.id', 'ts_ijc.journey_id')
					->where('ts_ij.active', 1)
					->where('ts_ij.school_id', $school->id);
			})
			->join('kolumbus_tuition_progress as ktp', function (JoinClause $join) use ($check) {
				$join->on('ktp.id', 'ts_ijc.index_latest_level_change_progress_id')
					->where('ktp.active', 1)
					->where('ktp.week', $check->toDateString());
			})
			->where('ts_ijc.visible', 1)
			->whereNotNull('ts_ijc.index_latest_level_change_progress_id')
			// Nur aktuelle Schüler
			->whereDate('ts_ijc.until', '>=', $time)
			->get();

		foreach ($journeyCourses as $journeyCourse) {
			self::dispatch($journeyCourse);
		}

	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $eventTab, EventManagementData $data): void
	{
		self::addExecutionTimeRow($dialog, $eventTab, $data);
		self::addExecutionWeekdayRow($dialog, $eventTab, $data);
	}

}