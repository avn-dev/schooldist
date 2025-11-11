<?php

namespace TsStudentApp\Service;

use Core\Helper\BundleConfig;
use TsStudentApp\Messenger\Notification;
use TsStudentApp\Messenger\Notifications\AbstractService;
use TsStudentApp\Messenger\Message;
use TsStudentApp\Messenger\Thread\AbstractThread;
use TsStudentApp\Messenger\Token;
use Illuminate\Support\Collection;

class MessengerService {

	/**
	 * @var BundleConfig
	 */
	private BundleConfig $bundleConfig;
	/**
	 * @var \Ext_TS_Inquiry_Contact_Traveller
	 */
	private \Ext_TS_Inquiry_Contact_Traveller $student;
	/**
	 * @var \Ext_TS_Inquiry
	 */
	private \Ext_TS_Inquiry $inquiry;
	/**
	 * @var Token
	 */
	private Token $token;

	/**
	 * @var array
	 */
	private array $errors = [];


	/**
	 * MessengerService constructor.
	 *
	 * @param BundleConfig $bundleConfig
	 * @param \Ext_TS_Inquiry_Contact_Traveller $student
	 * @param \Ext_TS_Inquiry $inquiry
	 * @param Token $token
	 */
	public function __construct(BundleConfig $bundleConfig, \Ext_TS_Inquiry_Contact_Traveller $student, \Ext_TS_Inquiry $inquiry, Token $token) {
		$this->bundleConfig = $bundleConfig;
		$this->student = $student;
		$this->inquiry = $inquiry;
		$this->token = $token;
	}

	static public function getInstance(\Ext_TS_Inquiry_Contact_Traveller $student, \Ext_TS_Inquiry $inquiry) {
		
		$bundleHelper = new \Core\Helper\Bundle();
		$bundle = $bundleHelper->getBundleFromClassName(get_called_class());
		$bundleConfig = new BundleConfig($bundleHelper->getBundleConfigData($bundle, false));

		$service = new self($bundleConfig, $student, $inquiry, new Token($bundleConfig));

		return $service;
	}
	
	/**
	 * Liefert alle Threads die für die Buchung möglich sind (Buchung, Lehrer, ...)
	 *
	 * @return AbstractThread[]|Collection
	 */
	public function getThreads(): Collection {

		$threads = collect([]);

		// Generelle Kommunikation mit Schule
		$schoolThread = $this->getThreadForEntity($this->inquiry->getSchool());
		$threads->put($schoolThread->getToken(), $schoolThread);

		// Lehrer
		$teachers = $this->inquiry->getTuitionTeachers();
		foreach($teachers as $teacherData) {
			$teacherThread = $this->getThreadForEntity(\Ext_Thebing_Teacher::getInstance($teacherData['teacher_id']));
			$threads->put($teacherThread->getToken(), $teacherThread);
		}

		return $threads;
	}

	/**
	 * Liefert das Thread-Objekt anhand des Tokens
	 *
	 * @param string $token
	 * @return AbstractThread|null
	 */
	public function getThreadByToken(string $token): ?AbstractThread {

		if(null !== $entity = $this->token->decode($token)) {
			return $this->getThreadForEntity($entity);
		}

		return null;
	}

	/**
	 * Liefert das Thread-Objekt für einen Kommunikationspartner (Buchung, Lehrer)
	 *
	 * @param \WDBasic $entity
	 * @return AbstractThread|null
	 */
	public function getThreadForEntity(\WDBasic $entity): ?AbstractThread {

		$threadConfig = collect($this->bundleConfig->get('messenger.threads', []))
			->first(fn ($thread) => $thread['entity'] === $entity::class);

		if($threadConfig) {
			return new $threadConfig['class']($this->token->encode($entity), $this->student, $entity, $this->inquiry, $threadConfig);
		}

		return null;
	}

	/**
	 * Notification Service
	 *
	 * @TODO Das macht wenig Sinn, da ein Service nach dieser Logik immer FCM+APNS unterstützen müsste
	 *
	 * @param null $service
	 * @return AbstractService
	 */
	public function getNotificationService($service = null) {

		if($service === null) {
			$service = $this->bundleConfig->get('messenger.notifications.default');
		}

		$services = $this->bundleConfig->get('messenger.notifications.services');

		if(
			!isset($services[$service]) ||
			!is_a($services[$service]['class'], AbstractService::class, true)
		) {
			throw new \RuntimeException(sprintf('Invalid notification service key "%s"!',$service));
		}

		return new $services[$service]['class']($services[$service]);
	}

	public function createNotificationServiceByDevice(\Ext_TS_Inquiry_Contact_Login_Device $device): AbstractService {

		if (
			strtolower($device->os) === 'ios' &&
			version_compare($device->app_version, '3.0', '>=')
		) {
			return $this->getNotificationService('apns');
		}

		return $this->getNotificationService();

	}

	/**
	 * Verschickt eine Nachricht von dem Schüler an die Schule (wird aktuell noch nicht benutzt)
	 *
	 * @param string $threadToken
	 * @param string $title
	 * @param string $message
	 */
	/*public function sendMessageToSoftware(string $threadToken, string $title, string $message) {

		$thread = $this->getThreadByToken($threadToken);

		if(is_null($thread)) {
			throw new \InvalidArgumentException(sprintf('No thread defined for token "%s"', $threadToken));
		}

		if(!$thread->canCommunicate()) {
			throw new \RuntimeException('No communication allowed between sender and receiver');
		}

		// TODO - wahrscheinlich anders lösen
		return $thread->storeMessage($this->student, $thread->getEntity(), $this->inquiry, $title, $message, 'in');
	}*/

	/**
	 * Sendet eine Nachricht von einem Kommunikationspartner an den Schüler (+ Notification)
	 *
	 * @param \WDBasic $sender
	 * @param string $title
	 * @param string $message
	 * @throws \Exception
	 */
	public function sendMessageToDevices(\WDBasic $sender, string $title, string $message): bool {

		$thread = $this->getThreadForEntity($sender);

		if(is_null($thread)) {
			throw new \InvalidArgumentException(sprintf('No thread defined for entity "%s"', get_class($sender)));
		}

		if(!$thread->canCommunicate()) {
			throw new \RuntimeException('No communication allowed between sender and receiver');
		}

		$notification = new Notification($title, $message);
		$notification->openThread($thread);

		$hasNotified = $this->sendNotificationToDevices($notification);

		return $hasNotified;
	}

	/**
	 * Sendet eine Nachricht an alle Devices des Schülers
	 * TODO - last_action einfließen lassen
	 *
	 * @param Notification $notification
	 * @return bool
	 * @throws \Exception
	 */
	public function sendNotificationToDevices(Notification $notification): bool {

		/** @var \Ext_TS_Inquiry_Contact_Login $login */
		$login = \Ext_TS_Inquiry_Contact_Login::query()
			->where('contact_id', $this->student->getId())
			->first();

		if(!is_null($login)) {

			// Notification-Service (z.b. Firebase)
//			$notificationService = $this->getNotificationService();

			// Alle Devices holen an die eine Notification geschickt werden kann
			$devices = collect($login->getDevices())
				->filter(function ($device) /*use ($notificationService)*/ {
					$notificationService = $this->createNotificationServiceByDevice($device);
					return $notificationService->canNotify($device);
				});

			if($devices->isNotEmpty()) {

				$additional = $notification->getAdditional();

				// Inquiry-ID muss immer da sein
				if(!isset($additional['inquiry_id'])) {
					$additional['inquiry_id'] = $this->inquiry->getId();
				}

				// Falls eine Seite als Klasse angegeben wurde
				if(
					isset($additional['page']) &&
					class_exists($additional['page'])
				) {
					$additional['page'] = collect($this->bundleConfig->get('pages', []))
						->mapWithKeys(function($page, $key) {
							return [$page['data'] => $key];
						})
						->get($additional['page']);
				}

				$oneSuccessful = false;
				
				// Notification an die Devices senden
				foreach($devices as $device) {

					// Migration für App 2 => 3, weil App-Kontext nicht existiert (Methode und Klasse wird an diversen Stellen aufgerufen?)
					$additional2 = $additional;
					if (
						version_compare($device->app_version, '3.0', '>=') &&
						$additional['task'] === 'openThread'
					) {
						$notification2 = clone $notification;
						$notification2->openPage('messenger-thread', ['thread' => $additional['thread']]);
						$additional2 = $notification2->getAdditional();
					}

					$notificationService = $this->createNotificationServiceByDevice($device);

					$success = $notificationService->notify($device, $notification->getTitle(), $notification->getMessage(), $additional2, $notification->getImage());
					// Wenn der Versand an mindestens ein Device erfolgreich war, dann zählt der Versand insgesamt als erfolgreich
					if($success === true) {
						$oneSuccessful = true;
					}
				}

				if($oneSuccessful === false) {
					$this->errors += $notificationService->getErrors();
				}
				
				return $oneSuccessful;
			} else {
				/*
				 * @todo Ist das so richtig?
				 */
				$this->errors += ['No devices with permission!'];
			}
		}

		return false;
	}

	/**
	 * Liefert die letzten Nachrichten für den Schüler
	 *
	 * @param int $limit
	 * @return Collection
	 */
	public function getLastMessages(int $limit, array $directions = ['in', 'out']): Collection {

		$threads = $this->getThreads();
		$messages = collect([]);

		foreach($threads as $thread) {
			// versuchen von jedem Thread immer $limit Nachrichten zu holen
			$messages = $messages->merge($thread->getMessages($limit, null, $directions));
		}

		return $messages
			->sortByDesc(fn (Message $message) => $message->getDate())
			->take($limit)
			->values();
	}

	public function getNumberOfUnreadMessages(): ?int {
		$count = collect($this->getThreads())
			->map(fn (AbstractThread $thread) => $thread->getNumberOfUnreadMessages())
			->sum();

		if (empty($count)) {
			return null;
		}

		return $count;
	}

	/**
	 * Statische Helper-Methode um eine Notification an den Schüler zu schicken
	 *
	 * @param \Ext_TS_Inquiry_Contact_Traveller $student
	 * @param \Ext_TS_Inquiry $inquiry
	 * @param Notification $notification
	 * @return bool
	 * @throws \Exception
	 */
	public static function sendNotificationToStudent(\Ext_TS_Inquiry_Contact_Traveller $student, \Ext_TS_Inquiry $inquiry, Notification $notification): bool {

		$service = self::getInstance($student, $inquiry);
		
		return $service->sendNotificationToDevices($notification);
	}

	/**
	 * Statische Helper-Methode um eine Nachricht an den Schüler zu verschicken
	 *
	 * @param \Ext_TS_Inquiry_Contact_Traveller $student
	 * @param \WDBasic $entity
	 * @param \Ext_TS_Inquiry $inquiry
	 * @param string $title
	 * @param string $message
	 * @throws \Exception
	 */
	public static function sendMessageToStudent(\Ext_TS_Inquiry_Contact_Traveller $student, \WDBasic $entity, \Ext_TS_Inquiry $inquiry, string $title, string $message): bool {

		$service = self::getInstance($student, $inquiry);
		
		return $service->sendMessageToDevices($entity, $title, $message);
	}

	public function getErrors() {
		return $this->errors;
	}
	
}
