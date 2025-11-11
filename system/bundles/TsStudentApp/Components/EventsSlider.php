<?php

namespace TsStudentApp\Components;

use Illuminate\Support\Collection;
use Ts\Service\Inquiry\Scheduler\EventDto;
use Ts\Service\Inquiry\SchedulerService;
use TsStudentApp\AppInterface;

/*
 * TODO extends Slider
 * TODO Entfernen (Was ist hier anders im Gegensatz zum normalen Slider?)
 */
class EventsSlider implements Component
{
	protected string $title = '';

	protected string $color = '';

	private Collection $events;

	public function __construct(private readonly AppInterface $appInterface) {}

	public function getKey(): string
	{
		return 'events-slider';
	}

	/**
	 * @deprecated
	 */
	public function title(string $title): static
	{
		$this->title = $title;
		return $this;
	}

	public function color(string $color): static
	{
		$this->color = $color;
		return $this;
	}

	public function events(Collection $events): static
	{
		$this->events = $events;
		return $this;
	}

	public function toArray(): array
	{
		return [
			'title' => $this->title,
			'color' => $this->color,
			'events' => $this->buildEventsArray($this->events),
		];
	}

	private function buildEventsArray(Collection $events): Collection {
		return $events->map(function (EventDto $event) {

			$description = [];

			$title = $event->title;
			if ($event->type === SchedulerService::TYPE_TUITION) {
				$blockDay = $event->getAdditional('block_day', []);
				if (!empty($blockDay['block_level'])) {
					$title .= ' '.sprintf('(%s: %s)', $this->appInterface->t('Level'), $blockDay['block_level']);
				}
			}

			$description[] = sprintf('<strong>%s</strong>', $title);
			$description[] = sprintf(
				'%s &#x2022; %s – %s',
				$this->appInterface->formatDate2($event->start, 'L'),
				$this->appInterface->formatDate2($event->start, 'LT'),
				$this->appInterface->formatDate2($event->end, 'LT')
			);

			if (!empty($event->location)) {
				$row = [$event->location];
				if ($event->type === SchedulerService::TYPE_TUITION) {
					$blockDay = $event->getAdditional('block_day', []);
					if (!empty($blockDay['teacher_name'])) {
						$row[] = $blockDay['teacher_name'];
					}
				}

				$description[] = implode(' &#x2022; ', $row);
			}

			return [
				'event' => [
					'id' => $event->id,
					'type' => $event->type,
					'start_date' => $event->start->toDateString()
				],
				'description' => implode('<br/>', $description)
			];
		});
	}
}