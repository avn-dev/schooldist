<?php

namespace TsTuition\Events;

use Carbon\Carbon;
use Core\Interfaces\HasIcon;
use Core\Traits\WithIcon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Foundation\Events\Dispatchable;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\EventManager\Process;
use Tc\Traits\Events\Manageable\WithManageableSystemUserCommunication;
use Tc\Traits\Events\ManageableEventTrait;
use Ts\Interfaces\Events\Inquiry\JourneyCourseEvent;
use Ts\Interfaces\Events\InquiryEvent;
use Ts\Traits\Events\Manageable\WithManageableCustomerCommunication;
use Ts\Traits\Events\Manageable\WithManageableExecutionTime;
use Ts\Traits\Events\Manageable\WithManageableIndividualCommunication;
use Ts\Traits\Events\Manageable\WithManageableSchoolCommunication;
use Ts\Traits\Events\Manageable\WithManageableTeacherCommunication;
use TsTuition\Dto\StudentCourseWeekAllocation;
use TsTuition\Entity\Course\Program\Service;
use Ts\Interfaces\Events\TeacherEvent;

class CurrentAllocatedStudents implements ManageableEvent, InquiryEvent, JourneyCourseEvent, TeacherEvent, HasIcon
{
	use Dispatchable,
		ManageableEventTrait,
		WithManageableSystemUserCommunication,
		WithManageableCustomerCommunication,
		WithManageableSchoolCommunication,
		WithManageableIndividualCommunication,
		WithManageableTeacherCommunication,
		WithManageableExecutionTime,
		WithIcon;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Aktuell einer Klasse zugewiesene Schüler');
	}

	public function __construct(
		private Carbon $week,
		private \Ext_TS_Inquiry_Journey_Course $journeyCourse,
		private Service $programService
	) {}

	public function getIcon(): ?string
	{
		return 'fas fa-user-graduate';
	}

	public function getWeek(): Carbon
	{
		return $this->week;
	}

	public function getInquiry(): \Ext_TS_Inquiry
	{
		return $this->journeyCourse->getJourney()->getInquiry();
	}

	public function getJourneyCourse(): \Ext_TS_Inquiry_Journey_Course
	{
		return $this->journeyCourse;
	}

	public function getProgramService(): Service
	{
		return $this->programService;
	}

	public function getSchool(): \Ext_Thebing_School
	{
		return $this->getInquiry()->getSchool();
	}

	public function getTeacher(): \Ext_Thebing_Teacher
	{
		$lastBlock = collect($this->getJourneyCourse()->getJoinedObjectChilds('tuition_blocks'))
			->map(fn ($tuitionBlock) => $tuitionBlock->getBlock())
			->sortBy('week')
			->last(fn ($block) => (
				Carbon::now()->gt(Carbon::parse($block->week)) &&
				$block->hasTeacher()
			));

		return $lastBlock->getTeacher();
	}

	public static function getPlaceholderObject(self $event = null): ?\Ext_TC_Placeholder_Abstract
	{
		// TODO eigentlich könnte man dem Event eine eigene Platzhalterklasse geben, aber da die Methode sowohl in dem Trait
		// als auch hier getPlaceholderObject() heißt geht das aktuell nicht
		$entity = ($event)
			? new StudentCourseWeekAllocation($event->getWeek(), $event->getJourneyCourse(), $event->getProgramService())
			: new StudentCourseWeekAllocation();
		return $entity->getPlaceholderObject();
	}

	public static function dispatchScheduled(Carbon $time, Process $process, \Ext_Thebing_School $school): void
	{
		$week = $time->clone()->startOfWeek(($school->course_startday != 7) ? $school->course_startday : 0);

		$allocations = \Ext_Thebing_School_Tuition_Allocation::query()
			->select('ktbic.*')
			->join('kolumbus_tuition_blocks as ktb',  function (JoinClause $join) use ($school) {
				$join->on('ktb.id', '=', 'ktbic.block_id')
					->where('ktb.active', 1)
					->where('ktb.school_id', $school->id);
			})
			->join('ts_inquiries_journeys_courses as ts_ijc', function (JoinClause $join) {
				$join->on('ts_ijc.id', '=', 'ktbic.inquiry_course_id')
					->where('ts_ijc.active', 1);
			})
			->join('ts_inquiries_journeys as ts_ij',  function (JoinClause $join) {
				$join->on('ts_ij.id', '=', 'ts_ijc.journey_id')
					->where('ts_ij.active', 1);
			})
			->join('ts_inquiries as ts_i',  function (JoinClause $join) {
				$join->on('ts_i.id', '=', 'ts_ij.inquiry_id')
					->where('ts_i.active', 1);
			})
			->whereDate('ktb.week', $week)
			// Jeder Unterkurs nur einmal
			->groupBy('ktbic.inquiry_course_id', 'ktbic.program_service_id')
			->get();

		foreach ($allocations as $allocation) {
			self::dispatch($week, $allocation->getJourneyCourse(), $allocation->getProgramService());
		}

	}

	protected static function getWeekdaySelectOptions(): array
	{
		// Keine "Täglich"-Option
		return \Ext_TC_Util::getWeekdaySelectOptions(EventManager::l10n()->getLanguage());
	}

	public static function manageEventListenersAndConditions()
	{
		self::addManageableCondition(Conditions\CourseAttendance::class);
		self::addManageableCondition(Conditions\CourseWeek::class);
		self::addManageableCondition(Conditions\LessonContingent::class);
		self::addManageableCondition(\Ts\Events\Inquiry\Conditions\CourseCategory::class);
		self::addManageableCondition(\Ts\Events\Inquiry\Conditions\Course::class);
	}

}