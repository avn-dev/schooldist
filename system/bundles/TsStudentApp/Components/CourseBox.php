<?php

namespace TsStudentApp\Components;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Ts\Service\Inquiry\Scheduler\EventDto;
use TsStudentApp\AppInterface;
use TsStudentApp\Http\Resources\TeacherResource;

class CourseBox implements Component
{
	private ?EventDto $event = null;

	public function __construct(
		private readonly AppInterface $appInterface,
		private readonly Request $request
	) {}

	public function getKey(): string
	{
		return 'course';
	}

	public function event(EventDto $event): static
	{
		$this->event = $event;
		return $this;
	}

	public function toArray(): array
	{
		$blockDay = $this->event->getAdditional('block_day');

		$teacher = \Ext_Thebing_Teacher::getInstance($blockDay['teacher_id']);

		$description = [];
		$description[] = sprintf('<strong>%s</strong>', $blockDay['class_name']);
		$description[] = sprintf(
			'%s &#x2022; %s – %s &#x2022; %s',
			$this->appInterface->formatDate2($this->event->start, 'L'),
			$this->appInterface->formatDate2($this->event->start, 'LT'),
			$this->appInterface->formatDate2($this->event->end, 'LT'),
			$blockDay['classroom']
		);

		$array = [];
		$array['description'] = implode('<br/>', $description);
		if ($teacher->exist()) {
			$array['teacher'] = (new TeacherResource($teacher))->toArray($this->request);
		}

		return $array;
	}
}