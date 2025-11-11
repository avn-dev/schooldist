<?php

namespace TsStudentApp\Pages\Home\Boxes;

use Ts\Service\Inquiry\SchedulerService;
use TsStudentApp\AppInterface;
use TsStudentApp\Components\Component;

class NextEventsBox implements Box
{
	const KEY = 'next-events';

	public function __construct(private AppInterface $appInterface, private SchedulerService $schedulerService) {}

	public function generate(): ?Component
	{
		$events = $this->schedulerService->upcoming()->get(5);

		$container = \TsStudentApp\Facades\Component::Container();

		$container->add(\TsStudentApp\Facades\Component::Heading($this->appInterface->t('Your next dates')));

		$container->add(\TsStudentApp\Facades\Component::EventsSlider($events));

		return $container;

	}
}