<?php

namespace TsActivities\Dto;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\Period;
use TsActivities\Entity\Activity;

/**
 * Was genau dieses DTO nun beschreibt, ist auch nicht so ganz klar:
 *   * Ein Activity\Block kann über diverse Wochen und Tage gehen
 *   * Ein Activity\Block kann diverse Aktivitäten haben, aber diese sind alle einzeln buchbar, müssen also wieder pro Block gruppiert (vermehrt) werden
 *   * Das ganze muss pro Woche (in welchen Wochen Activity\Block stattfinden kann) bereitgestellt werden
 *   * Das alles wird dann so für die App benötigt, da hier die geplanten Aktivitäten zur Buchung angezeigt werden
 *
 * Die STATUS-Konstanten werden auch in der App verwendet!
 */
class ActivityBlockWeekCombination
{
	const STATUS_BOOKABLE = 'bookable';

	const STATUS_VISIBLE = 'visible';

	const STATUS_BOOKED = 'booked';

	const STATUS_FULL = 'full';

	const STATUS_ALMOST_FULL = 'almost_full';

	const STATUS_PRICE_FOR_FREE = 'price_for_free';

	const STATUS_CANCELABLE = 'cancelable';

	public Activity\Block $block;

	public Activity $activity;

	public Carbon $week;

	public ?string $price = null;

	public array $status = [self::STATUS_BOOKABLE];

	/**
	 * @var BlockEvent[]
	 */
	public array $dates = [];

	public function __construct(Activity\Block $block, Activity $activity, Carbon $week)
	{
		if (
			!$block->exist() ||
			!$activity->exist()
		) {
			throw new \InvalidArgumentException('Block or activity does not exist');
		}

		$this->block = $block;
		$this->activity = $activity;
		$this->week = $week;
	}

	public function buildKey(bool $hashed = false): string
	{
		$key = sprintf('%d_%d_%s', $this->activity->id, $this->block->id, $this->week->toDateString());
		return ($hashed) ? md5($key) : $key;
	}

	public function buildName(string $language): string
	{
		return sprintf('%s – %s', $this->block->getName(), $this->activity->getName($language));
	}

	/**
	 * @return BlockEvent[]
	 */
	public function createBlockEvents(): array
	{
		return array_map(function (Activity\BlockDay $day) {
			$start = $this->week->clone()->addDays($day->day - 1)->setTimeFromTimeString($day->start_time);
			$end = $start->clone()->setTimeFromTimeString($day->end_time);
			return new BlockEvent($start, $end, $day->place);
		}, $this->block->getDays());
	}

	public function createPeriodCollection(): Period\PeriodCollection
	{
		return array_reduce($this->createBlockEvents(), function (Period\PeriodCollection $dates, BlockEvent $event) {
			$dates[] = Period\Period::make($event->start, $event->end, Period\Precision::MINUTE());
			return $dates;
		}, new Period\PeriodCollection());

	}

	public function getDates(): Collection
	{
		return collect($this->dates);
	}

	
	public function isVisibleInFrontend(Carbon $date): bool
	{
		if (
			(
				$this->block->frontend_release !== Activity\Block::FRONTEND_VISIBLE &&
				$this->block->frontend_release !== Activity\Block::FRONTEND_BOOKABLE
			) ||
			empty($this->dates)
		) {
			return false;
		}

		// Prüfen, ob die Mindestanzahl an Tagen vor der geplanten Aktivität eingehalten wird
		if (!empty($minDaysAhead = $this->block->frontend_min_visible_days_ahead)) {
			/* @var BlockEvent $firstEvent */
			$firstEvent = $this->getDates()->first();
			$minDate = $firstEvent->start->clone()->subDays((int)$minDaysAhead);

			if ($minDate > $date) {
				return false;
			}
		}

		return true;
	}
	
	public function isBookableInFrontend(Carbon $date): bool
	{
		if (
			$this->block->frontend_release !== Activity\Block::FRONTEND_BOOKABLE ||
			empty($this->dates) ||
			!$this->isVisibleInFrontend($date)
		) {
			// Wenn die Aktivität nicht sichtbar sein soll dann natürlich auch nicht buchbar
			return false;
		}

		// Prüfen, ob die Mindestanzahl an Tagen vor der geplanten Aktivität eingehalten wird
		if (!empty($minDaysAhead = $this->block->frontend_min_bookable_days_ahead)) {
			/* @var BlockEvent $firstEvent */
			$firstEvent = $this->getDates()->first();
			$minDate = $firstEvent->start->clone()->subDays((int)$minDaysAhead);

			if ($minDate < $date) {
				return false;
			}
		}

		return true;
	}
}