<?php

namespace TsStudentApp\Pages\Home\Boxes;

use Ts\Service\Inquiry\SchedulerService;
use TsStudentApp\AppInterface;
use TsStudentApp\Components\Component;
use TsStudentApp\Components\Container;

/**
 * @deprecated
 */
class NextCourseBox implements Box
{
	const KEY = 'next-course';

	public function __construct(private AppInterface $appInterface, private SchedulerService $schedulerService) {}

	public function generate(): ?Component
	{
		$events = $this->schedulerService->upcoming()->get(1);

		return \TsStudentApp\Facades\Component::EventsSlider($events)
			->title($this->appInterface->t('Your next course'));

		$events = $this->schedulerService->only(SchedulerService::TYPE_TUITION)->upcoming()->get(1);

		if ($events->isEmpty()) {
			return null;
		}

		$container = new Container();
		$container->add(\TsStudentApp\Facades\Component::Heading($this->appInterface->t('Your next course')));
		$container->add(\TsStudentApp\Facades\Component::CourseBox($events->first()));

		return $container;
	}
}