<?php

namespace Ts\Service\Inquiry;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tc\Service\LanguageAbstract;
use Ts\Service\Inquiry\Scheduler\EventDto;
use TsActivities\Entity\Activity\BlockTraveller;
use TsActivities\Service\ActivityService;

class SchedulerService
{
	const TYPE_TUITION = 'tuition';
	const TYPE_ACTIVITY = 'activity';

	private Carbon $from;

	private Carbon $until;

	private array $types = [self::TYPE_TUITION, self::TYPE_ACTIVITY];

	public function __construct(private \Ext_TS_Inquiry $inquiry, private LanguageAbstract $l10n) {
		$this->from = $this->inquiry->getServiceFrom(true);
		$this->until = $this->inquiry->getServiceUntil(true);
	}

	public function upcoming(): static
	{
		$this->from = Carbon::now();
		return $this;
	}

	public function only(string $type): static
	{
		$this->types = [$type];
		return $this;
	}

	public function forPeriod(Carbon $from, Carbon $until) {
		$this->from = $from;
		$this->until = $until;
		return $this;
	}

	public function forWeek(Carbon $startOfWeek) {
		$until = $startOfWeek->clone()->addDays(7)->endOfDay();
		return $this->forPeriod($startOfWeek->startOfDay(), $until);
	}

	public function forMonth(Carbon $date) {
		$from = $date->clone()->startOfMonth()->startOfDay();
		$until = $date->clone()->endOfMonth()->endOfDay();
		return $this->forPeriod($from, $until);
	}

	/**
	 * @param int|null $limit
	 * @return Collection
	 */
	public function get(int $limit = null): Collection
	{
		$events = collect();

		if (in_array(self::TYPE_TUITION, $this->types)) {
			$events = $events->merge($this->getTuitionEvents($limit));
		}

		if (in_array(self::TYPE_ACTIVITY, $this->types)) {
			$events = $events->merge($this->getActivityEvents($limit));
		}

		$events = $events->sortBy(fn (EventDto $event) => $event->start->getTimestamp());

		if ($limit !== null) {
			$events = $events->splice(0, $limit);
		}

		return $events->values();
	}

	/**
	 * TODO das könnte aktuell je nach Anzahl an Events lange dauern
	 *
	 * @param mixed $id
	 * @param string|null $type
	 * @return EventDto|null
	 */
	public function getById(mixed $id, string $type = null): ?EventDto
	{
		return $this->get()
			->first(fn (EventDto $event) => $event->id === $id);
	}

	public function getTuitionEvents(int $limit = null): Collection
	{
		$search = new \Ext_Thebing_School_Tuition_Allocation_Result();
		$search->setInquiry($this->inquiry);
		$search->setTimePeriod('block_day', $this->from, $this->until);
		if ($limit !== null) {
			$search->setLimit($limit);
		}

		$blockDays = $search->fetch();

		return collect($blockDays)->map(function (array $blockDay) {
			$start = Carbon::parse(sprintf('%s %s', $blockDay['block_day_date'], $blockDay['block_from']));
			$end = Carbon::parse(sprintf('%s %s', $blockDay['block_day_date'], $blockDay['block_until']));

			$id = implode('_', [$blockDay['block_allocation_id'], $start->getTimestamp(), $blockDay['classroom_id']]);

			return (new EventDto(self::TYPE_TUITION, md5($id), $start, $end, (string)$blockDay['class_name'], (string)$blockDay['classroom']))
				->additional('block_day', $blockDay);
		});
	}

	public function getActivityEvents(int $limit = null): Collection
	{
		$blockTravellers = (new ActivityService())->getBlocksOfInquiry($this->inquiry);

		$events = [];
		foreach($blockTravellers as $blockTraveller) {
			/* @var BlockTraveller $blockTraveller */
			$datePeriods = $blockTraveller->generateBlockEvents();

			$activity = $blockTraveller->getJourneyActivity()->getActivity();

			$block = $blockTraveller->getBlock();

			foreach($datePeriods as $blockEvent) {

				if ($blockEvent->start->isBetween($this->from, $this->until)) {
					$title = sprintf('%s – %s', $block->getName(), $activity->getName($this->l10n->getLanguage()));

					$id = implode('_', [$blockTraveller->id, $blockEvent->start->getTimestamp(), $blockEvent->end->getTimestamp()]);

					$events[] = (new EventDto(self::TYPE_ACTIVITY, md5($id), $blockEvent->start, $blockEvent->end, $title, $blockEvent->place))
						->additional('block_traveller', $blockTraveller)
						->additional('block_event', $blockEvent);
				}

				if ($limit !== null && count($events) === $limit) {
					break;
				}
			}

		}

		return collect($events);
	}
}