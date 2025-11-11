<?php

namespace TsStudentApp\Pages;

use Carbon\Carbon;
use Core\Factory\ValidatorFactory;
use Core\Service\HtmlPurifier;
use DateTime;
use Core\DTO\DateRange;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Ts\Service\Inquiry\Scheduler\EventDto;
use Ts\Service\Inquiry\SchedulerService;
use TsActivities\Entity\Activity\BlockTraveller;
use TsActivities\Service\ActivityService;
use TsStudentApp\AppInterface;
use Illuminate\Support\Str;

class Schedule extends AbstractPage {

	const EVENT_OPACITY = 60;

	const COLOR_PUBLIC_HOLIDAYS = '#CDCDCD'; // Auch in der App als Fallback definiert

	const COLOR_HOLIDAYS = '#EEEEEE'; // Auch in der App als Fallback definiert

	private $appInterface;

	private $inquiry;

	private $school;

	public function __construct(AppInterface $appInterface, \Ext_TS_Inquiry $inquiry, \Ext_Thebing_School $school) {
		$this->appInterface = $appInterface;
		$this->inquiry = $inquiry;
		$this->school = $school;
	}

	public function init(SchedulerService $schedulerService): array {

		$viewDate = Carbon::now();

		$startHour = (int) Str::before($this->school->class_time_from, ':');
		$endHour = (int) Str::before($this->school->class_time_until, ':');
		$startHourActivities = (int) Str::before($this->school->activity_starttime, ':');
		$endHourActivities = (int) Str::before($this->school->activity_endtime, ':');

		$data = [];
		// Ab v3 haben wir mehr Kontrolle über den Kalender und können die Events wöchentlich laden
		$data['mode'] = version_compare($this->appInterface->getAppVersion(), '3.0', '>=') ? 'monthly' : 'complete';
		$data['locale'] = $this->appInterface->getLanguage();
		$data['timezone'] = $viewDate->getTimezone();
		$data['view_date'] = $viewDate->format('Y-m-d');
		if (version_compare($this->appInterface->getAppVersion(), '3.0', '<')) {
			$data['weeks_starts_on'] = ($this->school->course_startday != 7) ? (int)$this->school->course_startday : 0;
		} else {
			// V-Calendar: 1 = Sonntag -> 7 = Samstag
			$data['weeks_starts_on'] = ($this->school->course_startday != 7) ? ((int)$this->school->course_startday + 1) : 1;
		}
		$data['day_start_hour'] = min($startHour, $startHourActivities);
		$data['day_end_hour'] = max($endHour, $endHourActivities);

		$data['legends'] = [
			// Bei Verändung der Holiday-Farben muss $opacity angewendet werden!
			$this->generateLegendChip('calendar-outline', $this->appInterface->t('Public holidays'), self::COLOR_PUBLIC_HOLIDAYS),
			$this->generateLegendChip('calendar-outline', $this->appInterface->t('Holidays'), self::COLOR_HOLIDAYS),
			$this->generateLegendChip('calendar-outline', $this->appInterface->t('Activities'), $this->getActivityEventColor(true)),
		];

		// Schulferien
		$schoolHolidays = $this->buildSchoolHolidays($viewDate, $data['mode']);
		// Feiertage
		$publicHolidays = $this->buildPublicHolidays($viewDate, $data['mode']);

		$data['events'] = $this->buildEvents($schedulerService, $viewDate, $data['mode']);
		$data['holidays'] = $schoolHolidays;
		$data['public_holidays'] = $publicHolidays;

		return $data;
	}

	private function getActivityEventColor($opacity = false) {

//		$color = $this->appInterface->getSchoolAppConfigValue('color_primary', '', true);
		if (empty($color)) {
			$color = (string)$this->school->system_color;
		}

		if ($opacity) {
			$color = \Core\Helper\Color::changeOpacity($color, self::EVENT_OPACITY);
		}

		return $color;

	}

	private function generateLegendChip(string $icon, string $title, string $color): array {

		$text = '#000000';
		if (!\Core\Helper\Color::isLight($color)) {
			$text = '#EEEEEE';
		}

		return [
			'icon' => $icon,
			'title' => $title,
			'color' => $color, // TODO Entfernen, war für App v2.0.4/v2.0.5 alleiniger Background
			'background' => $color,
			'text' => $text,
			'icon_text' => 'rgba('.join(', ', \Core\Helper\Color::convertHex2RGB($text)).', 0.54)' // 0.54 wird auch von Ionic verwendet
		];

	}

	public function refresh(Request $request, SchedulerService $schedulerService): array {

		$validator = (new ValidatorFactory($this->appInterface->getLanguageObject()->getLanguage()))
			->make(data: $request->all(), rules: ['mode' => Rule::in(['weekly', 'monthly', 'complete']), 'date' => ['date']]);

		if ($validator->fails()) {
			return [
				'errors' => $validator->getMessageBag()->toArray()
			];
		}

		$mode = $request->input('mode', 'complete');
		$date = Carbon::parse($request->input('date', date('Y-m-d')));

		// Klassenplan, Aktivitäten, ...
		$events = $this->buildEvents($schedulerService, $date, $mode);
		// Schulferien
		$schoolHolidays = $this->buildSchoolHolidays($date, $mode);
		// Feiertage
		$publicHolidays = $this->buildPublicHolidays($date, $mode);

		return [
			'events' => $events,
			'holidays' => $schoolHolidays,
			'public_holidays' => $publicHolidays
		];
	}

	public function loadEvents(Request $request, SchedulerService $schedulerService)
	{
		return $this->refresh($request, $schedulerService);
	}

	private function buildEvents(SchedulerService $schedulerService, Carbon $startOfWeek, string $mode): Collection {

		if ($mode === 'weekly') {
			$schedulerService->forWeek($startOfWeek);
		} else if ($mode === 'monthly') {
			$schedulerService->forMonth($startOfWeek);
		}

		$allEvents = $schedulerService->get();

		return $allEvents->map(function (EventDto $event) {
			if ($event->type === SchedulerService::TYPE_TUITION) {
				return $this->buildTuitionEvent($event);
			} else if ($event->type === SchedulerService::TYPE_ACTIVITY) {
				return $this->buildActivityEvent($event);
			} else {
				throw new \RuntimeException(sprintf('Unknown event type for student app [%s]', $event->type));
			}
		});
	}

	private function buildTuitionEvent(EventDto $event): array {

		$blockDay = $event->getAdditional('block_day', []);

		$description = [];
		$description[] = sprintf('<strong>%s</strong>', $blockDay['class_name'] ?? '');
		$description[] = sprintf('%s – %s', $this->appInterface->formatDate2($event->start, 'LT'), $this->appInterface->formatDate2($event->end, 'LT'));
		if (!empty($blockDay['block_level'])) {
			$description[] = sprintf('%s: %s', $this->appInterface->t('Level'), $blockDay['block_level']);
		}
		if (!empty($blockDay['classroom'])) {
			$description[] = $blockDay['classroom'];
		}
		if (!empty($blockDay['teacher_id'])) {
			$description[] = $blockDay['teacher_name'];
		}

		return $this->buildEventArray($event, $event->start, $event->end, implode('<br/>', $description));
	}

	private function buildActivityEvent(EventDto $event): array {

		$description = [];
		$description[] = sprintf('<strong>%s</strong>', $event->title);
		$description[] = sprintf('%s – %s', $this->appInterface->formatDate2($event->start, 'LT'), $this->appInterface->formatDate2($event->end, 'LT'));

		if (!empty($event->location)) {
			$description[] = $event->location;
		}

		return $this->buildEventArray($event, $event->start, $event->end, implode('<br/>', $description), $this->getActivityEventColor());
	}

	private function buildEventArray(EventDto $eventDto, DateTime $start, DateTime $end, string $title, string $color = null, string $cssClass = null) {

		$event = [
			'start' => $start->format('Y-m-d H:i:s'),
			'end' => $end->format('Y-m-d H:i:s'),
		];

		if (version_compare($this->appInterface->getAppVersion(), '2.2', '>=')) {
			$event['description'] = $title;
			$event = Arr::prepend($event, [
				'id' => $eventDto->id,
				'type' => $eventDto->type,
				'start_date' => $eventDto->start->toDateString()
			], 'event');
		} else {
			// @deprecated
			$event['title'] = $title;
		}

		if($color !== null) {
			$event['colors'] = [
				'primary' => $color,
				'secondary' => \Core\Helper\Color::changeOpacity($color, self::EVENT_OPACITY)
			];
		}

		if($cssClass !== null) {
			$event['cssClass'] = $cssClass; // < 3.0.0
			$event['css_class'] = $cssClass;
		}

		return $event;
	}

	private function buildSchoolHolidays(Carbon $date, string $mode) {

		if ($mode === 'weekly') {
			$holidays = $this->school->getSchoolHolidays($date->clone()->startOfWeek(), $date->clone()->addWeek());
		} else if ($mode === 'monthly') {
			$holidays = $this->school->getSchoolHolidays($date->clone()->startOfMonth(), $date->clone()->startOfMonth()->addMonth());
		} else {
			$daterange = $this->getPeriodForHolidays();
			$holidays = $this->school->getSchoolHolidays($daterange->from, $daterange->until);
		}

		return array_map(function($holiday) {
			return [
				'from' => $holiday->from,
				'until' => $holiday->until,
			];
		}, $holidays);
	}

	private function buildPublicHolidays(Carbon $date, string $mode) {

		if ($mode === 'weekly') {
			$holidays = $this->school->getHolidays($date->clone()->startOfWeek()->getTimestamp(), $date->clone()->startOfWeek()->addWeek()->getTimestamp(), false);
		} else if ($mode === 'monthly') {
			$holidays = $this->school->getHolidays($date->clone()->startOfMonth()->getTimestamp(), $date->clone()->startOfMonth()->addMonth()->getTimestamp(), false);
		} else {
			$daterange = $this->getPeriodForHolidays();
			$holidays = $this->school->getHolidays($daterange->from->getTimestamp(), $daterange->until->getTimestamp(), false);
		}

		return array_map(function($holidayEntry) {
			return [
				'date' => $holidayEntry['date'],
				'title' => $holidayEntry['name']
			];
		}, $holidays);
	}

	private function toStartOfWeek(Carbon $date)
	{
		return $date->copy()->startOfWeek(($this->school->course_startday != 7) ? $this->school->course_startday : 0);
	}

	/**
	 * @todo Entscheiden welcher Zeitraum genommen werden soll
	 * @return DateRange
	 * @throws \Exception
	 */
	private function getPeriodForHolidays(): DateRange {

		return new DateRange(
			new \DateTime($this->inquiry->getServiceFrom()),
			new \DateTime($this->inquiry->getServiceUntil())
		);

		/*$aYears = [
			(new \DateTime($this->inquiry->getServiceFrom()))->format('Y') ,
			(new \DateTime($this->inquiry->getServiceUntil()))->format('Y')
		];

		$dFrom = (new \DateTime($aYears[0].'-01-01'))->setTime(0, 0);
		$dUntil = (new \DateTime($aYears[0].'-12-31'))->setTime(23, 59, 59);

		return new DateRange($dFrom, $dUntil);*/
	}

	public function getTranslations(AppInterface $appInterface): array {
		return [
			'tab.schedule.btn.today' => $appInterface->t('Today'),
			'tab.schedule.btn.previous' => $appInterface->t('Previous'),
			'tab.schedule.btn.next' => $appInterface->t('Next'),
			'tab.schedule.event.info.heading' => $appInterface->t('Informationen'),
			'tab.schedule.selection.holiday' => $appInterface->t('The selected date is during the holidays'),
		];
	}

	public function getColors(AppInterface $appInterface): array {
		// Hier muss Opacity angewendet werden, wenn die Konstanten nicht mehr verwendet werden
		return [
			'--ion-color-public-holidays' => self::COLOR_PUBLIC_HOLIDAYS,
			'--ion-color-holidays' => self::COLOR_HOLIDAYS
		];
	}

}
