<?php

namespace TsStudentApp\Pages;

use Illuminate\Http\Request;
use TsStudentApp\AppInterface;
use TsStudentApp\Http\Resources\MessengerThreadResource;
use TsStudentApp\Messenger\Thread\AbstractThread;
use TsStudentApp\Service\MessengerService;

class Messenger extends AbstractPage {

	private $appInterface;

	private $messenger;

	private $student;

	private $inquiry;

	public function __construct(AppInterface $appInterface, MessengerService $messenger, \Ext_TS_Inquiry_Contact_Traveller $student, \Ext_TS_Inquiry $inquiry) {
		$this->appInterface = $appInterface;
		$this->messenger = $messenger;
		$this->student = $student;
		$this->inquiry = $inquiry;
	}

	public function init(Request $request) {
		$data = $this->refresh($request);
		// plus button freischalten
		$data['plus_button'] = false;

		$studentMessages = (bool)$this->appInterface->getSchool()->getMeta('student_app_student_messages', false);

		if (
			version_compare($this->appInterface->getAppVersion(), '2.2', '>=') &&
			// Nur einblenden wenn der Schüler auch wirklich Nachrichten versenden kann
			$studentMessages
		) {
			// Ab 2.1.0 können Schüler auch Nachrichten schreiben
			$data['plus_button'] = true;
		}

		return $data;
	}

	public function refresh(Request $request) {

		$threadsObjects = $this->messenger->getThreads();

		$threads = $threadsObjects
			->sortByDesc(function(AbstractThread $thread) {
				return $thread->getLastContact();
			})
			->values();

		return [
			'threads' => MessengerThreadResource::collection($threads)->toArray($request)
		];
	}

	public function getTranslations(AppInterface $appInterface): array {
		return [
			'tab.messenger.search' => $appInterface->t('Search'),
			'tab.messenger.no_threads' => $appInterface->t('No messages available'),
			'tab.messenger.last_contact' => $appInterface->t('Last contact'),
			'tab.messenger.all_contacts' => $appInterface->t('All contacts'),
			'tab.messenger.message.file' => $appInterface->t('File'),
		];
	}

}
