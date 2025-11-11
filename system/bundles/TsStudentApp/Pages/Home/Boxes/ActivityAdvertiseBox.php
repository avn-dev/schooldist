<?php

namespace TsStudentApp\Pages\Home\Boxes;

use Carbon\Carbon;
use TsActivities\Dto\ActivityBlockWeekCombination;
use TsActivities\Service\ActivityService;
use TsStudentApp\AppInterface;
use TsStudentApp\Components\Component;

class ActivityAdvertiseBox implements Box
{
	const KEY = 'activity-advertisement';

	public function __construct(
		private AppInterface $appInterface,
		private ActivityService $activityService
	) {}

	public function generate(): ?Component
	{
		$inquiry = $this->appInterface->getInquiry();

		$blocks = $this->activityService->searchAvailableBlocksForInquiry($inquiry, Carbon::now(), $inquiry->getServiceUntil(true))
			->filter(function (ActivityBlockWeekCombination $combination) {
				$visible = $combination->isBookableInFrontend(Carbon::now()) || $combination->isVisibleInFrontend(Carbon::now());
				return $visible && (bool)$combination->block->advertise;
			})
			->sort(function (ActivityBlockWeekCombination $combination, ActivityBlockWeekCombination $combination2) {
				return $combination->dates[0]->start > $combination2->dates[0]->start;
			})
			->slice(0, 5)
			->values();

		if ($blocks->isEmpty()) {
			return null;
		}

		$container = \TsStudentApp\Facades\Component::Container();

		$container->add(\TsStudentApp\Facades\Component::Heading($this->appInterface->t('You might like these activities')));

		$container->add(\TsStudentApp\Facades\Component::ActivitiesSlider($blocks));

		return $container;

	}
}