<?php

namespace TsStudentApp\Pages;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Ts\Service\Inquiry\Scheduler\EventDto;
use Ts\Service\Inquiry\SchedulerService;
use TsStudentApp\AppInterface;
use TsStudentApp\Facades\Component;
use TsStudentApp\Helper\DynamicComponentsResponse;

class EventInfo extends AbstractPage
{
	public function __construct(private AppInterface $appInterface) {}

	public function init(Request $request, \Ext_TS_Inquiry $inquiry, SchedulerService $schedulerService): DynamicComponentsResponse
	{
		if (
			null === ($eventType = $request->input('event_type')) ||
			null === ($eventId = $request->input('event_id'))
		) {
			// abort
			throw new \RuntimeException('Missing event parameters');
		}

		$from = $inquiry->getServiceFrom(true);
		$until = $inquiry->getServiceUntil(true);
		if (
			$request->has('start_date') &&
			!empty($startDate = $request->input('start_date'))
		) {
			$from = Carbon::createFromFormat('Y-m-d', $startDate);
		}

		if (!$from || !$until) {
			throw new \RuntimeException('Invalid scheduler period');
		}

		$eventDto = $schedulerService
			->only($eventType)
			->forPeriod($from, $until)
			->getById($eventId);

		if (!$eventDto) {
			throw new \RuntimeException(sprintf('Event not found [id: %s]', $eventId));
		}

		$response = match ($eventDto->type) {
			SchedulerService::TYPE_TUITION	=> $this->buildTuitionBlockResponse($eventDto),
			SchedulerService::TYPE_ACTIVITY => $this->buildActivityBlockResponse($eventDto),
			default => throw new \RuntimeException(sprintf('Unknown event type for student app [%s]', $eventDto->type))
		};

		if (empty($response->toArray())) {
			$noContent = Component::Container()->white()
				->add(
					Component::Card()
						->cssClass('ion-text-center')
						->shadow(false)
						->content(Component::HtmlBox($this->appInterface->t('No data found.')))
				);

			$response->add($noContent);
		}

		return $response;
	}

	private function buildTuitionBlockResponse(EventDto $event): DynamicComponentsResponse
	{
		$blockDay = $event->getAdditional('block_day');

		$block = \Ext_Thebing_School_Tuition_Block::getInstance($blockDay['block_id']);

		$response = new DynamicComponentsResponse();

		$teacher = $block->getTeacher();

		if ($teacher->exist()) {
			$separator = Component::Container()->white()
				->add(Component::Heading($this->appInterface->t('Your teacher')))
				->add(Component::TeacherBox($block->getTeacher()));

			$response->add($separator);
		}

		$contents = [];

		if (!empty($descriptionStudent = $block->description_student)) {
			$contents[] = [$this->appInterface->t('App comment'), $descriptionStudent];
		}

		if (
			$block->getSchool()->getMeta('student_app_show_tuition_block_description', 0) > 0 &&
			!empty($description = $block->description)
		) {
			$contents[] = [$this->appInterface->t('Block content'), $description];
		}

		if (
			$block->getSchool()->getMeta('student_app_show_tuition_block_daily_comments', 0) > 0 &&
			!empty($comment = $block->getUnit($event->start->format('w'))->comment)
		) {
			$contents[] = [$this->appInterface->t('Daily comment'), $comment];
		}

		if (!empty($contents)) {

			$buildCard = function (string $title, string $content) {
				return Component::Card()
					->subtitle($title)
					->color('secondary')
					->shadow(false)
					->content(Component::HtmlBox($content));
			};

			if (count($contents) === 1) {
				$first = reset($contents);
				$container = Component::Container()->white()
					->add(Component::Heading($this->appInterface->t('More information')))
					->add($buildCard($first[0], $first[1]));
				$response->add($container);
			} else {

				$slider = Component::Slider()
					->title($this->appInterface->t('More information'));

				foreach ($contents as $content) {
					$slider->slide($buildCard($content[0], $content[1]));
				}

				$response->add($slider);
			}
		}

		if (!empty($files = $block->getClass()->getFiles('App-Upload'))) {

			$separator = Component::Container()->white();

			$separator->add(Component::Heading($this->appInterface->t('Files for this class')));

			$fileContainer = Component::FileList();
			foreach ($files as $file) {
				$fileContainer->file($this->appInterface->document('class', $file->getId()), $file->file);
			}
			$separator->add($fileContainer);

			$response->add($separator);
		}

		return $response;
	}

	private function buildActivityBlockResponse(EventDto $event): DynamicComponentsResponse
	{
		/* @var \TsActivities\Entity\Activity\BlockTraveller $blockTraveller */
		$blockTraveller = $event->getAdditional('block_traveller');

		$response = new DynamicComponentsResponse();

		// TODO Mehr Inhalte
		if ($blockTraveller) {
			$activity = $blockTraveller->getActivity();

			$container = Component::Container()->white()
				->add(
					Component::Card()
						->color('secondary')
						->content(Component::HtmlBox($activity->getDescription($this->appInterface->getLanguageObject()->getLanguage())))
				);

			$response->add($container);
		}

		return $response;
	}
}