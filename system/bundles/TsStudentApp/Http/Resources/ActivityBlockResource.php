<?php

namespace TsStudentApp\Http\Resources;

use Carbon\Carbon;
use Illuminate\Container\Container;
use Illuminate\Http\Resources\Json\JsonResource;
use TsActivities\Dto\ActivityBlockWeekCombination;
use TsActivities\Dto\BlockEvent;
use TsStudentApp\AppInterface;

/**
 * @mixin ActivityBlockWeekCombination
 */
class ActivityBlockResource extends JsonResource
{
	private AppInterface $appInterface;

	public function __construct($resource)
	{
		parent::__construct($resource);
		$this->appInterface = Container::getInstance()->make(AppInterface::class);
	}

	public function toArray($request)
	{
		$dates = $this->getDates()->map(function (BlockEvent $event) {
			return [
				'place' => $event->place,
				'weekday' => $this->appInterface->formatDate2($event->start, 'dddd'),
				'start' => $this->appInterface->formatDate2($event->start, 'LL'),
				'start_time' => $this->appInterface->formatDate2($event->start, 'LT'),
				'start_long' => $this->appInterface->formatDate2($event->start, 'LLLL'),
				'end_time' => $this->appInterface->formatDate2($event->end, 'LT'),
				'start_iso' => Carbon::instance($event->start)->toIso8601String()
			];
		});

		$short = '';
		if (!empty($this->dates[0]->place)) {
			$short .= $this->dates[0]->place.'<br>';
		}
		$short .= $dates[0]['start_long'];
		if (count($this->dates) > 1) {
			$short .= sprintf(' (%d %s)', count($this->dates), $this->appInterface->t('Blöcke'));
		}

		return [
			'key' => $this->buildKey(true),
			'block_id' => (int)$this->block->id,
			'activity_id' => (int)$this->activity->id,
			'title' => $this->buildName($this->appInterface->getLanguageObject()->getLanguage()),
			'description' => $this->activity->getDescription($this->appInterface->getLanguageObject()->getLanguage()),
			'short' => $short,
			'price' => $this->price,
			//'price_reduced' => '€ 40,00', // > 2.1.0
			//'reduced_price' => '€40,00', // < 2.1.0
			'forFree' => $this->activity->isFreeOfCharge(), // TODO Entfernen wenn alle Apps >= 2.1.0
			'image' => $this->activity->getAppImage() ? $this->appInterface->image('activity', $this->activity->id) : null,
			'week' => $this->week->toDateString(),
			'dates' => $dates,
			'status' => $this->status,
			'status_colors' => [
				ActivityBlockWeekCombination::STATUS_BOOKED => 'success',
				ActivityBlockWeekCombination::STATUS_VISIBLE => 'medium',
				ActivityBlockWeekCombination::STATUS_FULL => 'danger',
				ActivityBlockWeekCombination::STATUS_ALMOST_FULL => 'warning'
			]
		];
	}
}