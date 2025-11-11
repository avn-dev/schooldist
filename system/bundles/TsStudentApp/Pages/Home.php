<?php

namespace TsStudentApp\Pages;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use TsStudentApp\AppInterface;
use TsStudentApp\Helper\DynamicComponentsResponse;
use TsStudentApp\Http\Resources\StudentResource;
use TsStudentApp\Pages\Home\Boxes;
use TsStudentApp\Service\MessengerService;

class Home extends AbstractPage {

	public function __construct(
		private Container $container,
		private AppInterface $appInterface,
		private MessengerService $messenger
	) {}

	public function init(Request $request): array {

		if (version_compare($this->appInterface->getAppVersion(), '2.2', '<')) {

			$school = $this->appInterface->getSchool();

			$welcome = $school->getMeta('student_app_welcome_text_'.$this->appInterface->getLanguage());
			if (empty($welcome)) {
				// Sonst wird es nicht ersetzt
				$welcome = '<span></span>';
			}

			$file = $school->getFirstFile(\TsActivities\Entity\Activity::APP_IMAGE_TAG);

			return [
				'title' => $school->getMeta('student_app_welcome_title_'.$this->appInterface->getLanguage()),
				'sub_title' => $school->getName(),
				'image' => $file ? $this->appInterface->image('school_image', $school->getId()) : null,
				'welcome' => $welcome,
				'messages' => $this->getLastMessages(),
				'image_style' => [
					// Zum Resetten auf initial setzen
					'height' => \System::d('ts_student_app_welcome_height', '15vh'),
					'object-fit' => 'cover',
					//'object-position' => 'center center'
				]
			];
		}

		$student = $this->appInterface->getStudent();

		return array_merge(
			['student' => (new StudentResource($student))->toArray($request)],
			$this->refresh2()
		);

	}

	/**
	 * has_refresh immer auf false, da student (image) immer mitkommen muss – das war vorher egal, weil has_refresh kaputt war
	 */
	public function refresh2() {

		if (version_compare($this->appInterface->getAppVersion(), '2.2', '<')) {
			return [
				'messages' => $this->getLastMessages()
			];
		}

		// TODO Sortierung einstellbar machen
		$boxOrder = [
			Boxes\DuePaymentBox::KEY,
			Boxes\NextEventsBox::KEY,
			Boxes\AnnouncementBox::KEY,
//			Boxes\NextCourseBox::KEY,
			Boxes\LastMessagesBox::KEY,
			Boxes\ActivityAdvertiseBox::KEY,
		];

		$activated = $this->appInterface->getSchool()->getMeta('student_app_home_boxes');
		if (empty($activated)) {
			$activated = array_keys(array_filter(self::getBoxes(), fn ($box) => $box['default']));
		}

		$boxes = array_intersect($boxOrder, $activated);

		$response = new DynamicComponentsResponse();

		foreach ($boxes as $box) {
			$component = $this->getBox($box)->generate();
			if ($component) {
				$response->add($component);
			}
		}

		return [
			'components' => $response->toArray(),
		];
	}

	/**
	 * @deprecated für >= 2.2.0
	 * @return mixed
	 */
	private function getLastMessages() {
		return $this->messenger->getLastMessages(3, ['out'])
			->map(function($message) {
				return $message->toArray();
			});
	}

	public function getTranslations(AppInterface $appInterface): array {
		return [
			'tab.home.student.welcome' => $appInterface->t('Welcome'),
			'tab.home.no_messages' => $appInterface->t('No messages found'),
			'tab.home.turn_notifications.title' => $appInterface->t('Turn on notifications'),
			'tab.home.turn_notifications.content' => $appInterface->t('Don\'t miss important messages from your school! Turn on notifications in settings.'),
			'tab.home.turn_notifications.open' => $appInterface->t('Enable notifications in settings'),
			// Deprecated
			'tab.home.last_message' => $appInterface->t('Last messages'),
		];
	}

	public function getBox(string $key): Boxes\Box {
		$boxClass = Arr::get(self::getBoxes(), $key.'.class');

		if ($boxClass === null) {
			throw new \RuntimeException(sprintf('Unknown box key [%s]', $key));
		}

		return $this->container->make($boxClass);
	}

	public static function getBoxes(): array {
		return [
			Boxes\NextEventsBox::KEY => ['title' => 'Nächste Events', 'class' => Boxes\NextEventsBox::class, 'default' => true],
//			Boxes\NextCourseBox::KEY => ['title' => 'Nächster Kurs', 'class' => Boxes\NextCourseBox::class],
			Boxes\LastMessagesBox::KEY => ['title' => 'Letzte Nachrichten', 'class' => Boxes\LastMessagesBox::class, 'default' => true],
			Boxes\ActivityAdvertiseBox::KEY => ['title' => 'Aktivitäten bewerben', 'class' => Boxes\ActivityAdvertiseBox::class],
			Boxes\DuePaymentBox::KEY => ['title' => 'Fällige Zahlung', 'class' => Boxes\DuePaymentBox::class, 'default' => true],
			Boxes\AnnouncementBox::KEY => ['title' => 'Ankündigungen', 'class' => Boxes\AnnouncementBox::class, 'default' => false],
		];
	}
}
