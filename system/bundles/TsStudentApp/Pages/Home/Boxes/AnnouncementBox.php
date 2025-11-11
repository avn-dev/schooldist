<?php

namespace TsStudentApp\Pages\Home\Boxes;

use Core\Service\HtmlPurifier;
use Ts\Service\Inquiry\SchedulerService;
use TsStudentApp\AppInterface;
use TsStudentApp\Components\Component;
use TsStudentApp\Entity\AppContent;

class AnnouncementBox implements Box
{
	const KEY = 'announcements';

	public function __construct(private AppInterface $appInterface, private SchedulerService $schedulerService) {}

	public function generate(): ?Component
	{
		$entries = AppContent::query()
			->onlyValid()
			->where('school_id', $this->appInterface->getSchool()->getId())
			->where('type', \TsStudentApp\Enums\AppContentType::ANNOUNCEMENT)
			->where('released', 1)
			->get();

		if ($entries->isEmpty()) {
			return null;
		}

		$buildCard = function (string $title, string $content) {
			$set = HtmlPurifier::SETS[HtmlPurifier::SET_FRONTEND];
			$set['html'][] = 'div[style]';
			$set['html'][] = 'img[src|alt|width|height|style]';
			$set['css'][] = 'text-align';

			$content = $this->appInterface->replaceInquiryPlaceholders($content);

			return \TsStudentApp\Facades\Component::Card()
				->title($title)
				->shadow(false)
				->rounded()
				->content(\TsStudentApp\Facades\Component::HtmlBox($content, $set))
				->cssClass('ion-no-margin-top');
		};

		$language = $this->appInterface->getLanguageObject()->getLanguage();

//		if ($entries->count() === 1) {
//
//			$container = \TsStudentApp\Facades\Component::Container()
//				//->add(\TsStudentApp\Facades\Component::Heading($this->appInterface->t('Announcements')))
//			;
//
//			$container->add($buildCard(
//				$entries->first()->getI18NName('i18n', 'title', $language),
//				$entries->first()->getI18NName('i18n', 'content', $language)
//			));
//
//			return $container;
//		}
//
//		$slider = \TsStudentApp\Facades\Component::Slider()
//			->title($this->appInterface->t('Announcements'));
//
//		foreach ($entries as $entry) {
//			$slider->slide($buildCard(
//				$entry->getI18NName('i18n', 'title', $language),
//				$entry->getI18NName('i18n', 'content', $language)
//			));
//		}
//
//		return $slider;
//
		$container = \TsStudentApp\Facades\Component::Container();

//		if ($entries->count() > 1) {
			$container->add(\TsStudentApp\Facades\Component::Heading($this->appInterface->t('Announcements')));
//		}

		$slider = \TsStudentApp\Facades\Component::Slider()
			->title($this->appInterface->t('Announcements'));

		foreach ($entries as $entry) {
			$slider->slide($buildCard(
				$entry->getI18NName('i18n', 'title', $language),
				$entry->getI18NName('i18n', 'content', $language)
			));
		}

		$container->add($slider);

		return $container;

	}
}