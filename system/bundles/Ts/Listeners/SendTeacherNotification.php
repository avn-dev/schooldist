<?php

namespace Ts\Listeners;

use Core\Interfaces\Events\AttachmentsEvent;
use Core\Interfaces\Events\SystemEvent;
use Core\Notifications\AdminNotification;
use Core\Service\NotificationService;
use Psr\Log\LoggerInterface;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Tc\Traits\Listeners\SendNotificationTrait;
use Ts\Interfaces\Events\MultipleTeachersEvent;
use Ts\Interfaces\Events\TeacherEvent;

class SendTeacherNotification implements Manageable
{
	use ManageableTrait,
		SendNotificationTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Lehrer: E-Mail versenden');
	}

	public static function toReadable(Settings $settings): string
	{
		return EventManager::l10n()->translate('Nachricht an Lehrer versenden');
	}

	protected function logger(): LoggerInterface
	{
		return NotificationService::getLogger('SendTeacherNotification');
	}

	public function handle(TeacherEvent|MultipleTeachersEvent $payload): void
	{
		if ($payload instanceof MultipleTeachersEvent) {
			$teachers = $payload->getTeachers();
		} else {
			$teachers = [$payload->getTeacher()];
		}

		foreach ($teachers as $teacher) {

			if (!$teacher->exist()) {
				$this->logger()->error('No teacher object', ['event' => $payload::class]);
				continue;
			}

			$notification = null;
			if ($this->isManaged()) {
				$notification = $this->getManagedTeacherNotification($payload, $teacher);
			}

			/**
			 * Möglichkeit die Notification über das Event anzupassen oder Informationen zu ergänzen (z.b. Anhänge)
			 */
			if (method_exists($payload, 'getTeacherNotification')) {
				$notification = $payload->getTeacherNotification($this, $notification, $teacher);
			} else if (method_exists($payload, 'getNotification')) {
				$notification = $payload->getNotification($this, $notification);
			}

			if ($notification) {

				// z.B. Anhänge
				$this->bindEventPayloadToNotification($payload, $notification);

				$this->checkQueue($notification);

				$teacher->notifyNow($notification);

			} else {
				$this->logger()->error('No notification object', ['event' => $payload::class, 'teacher_id' => $teacher->id]);
			}
		}
	}

	private function getManagedTeacherNotification($payload, \Ext_Thebing_Teacher $teacher): AdminNotification
	{
		$subject = strip_tags($this->managedObject->getSetting('subject', ''));
		$message = nl2br(strip_tags($this->managedObject->getSetting('message', '')));

		$subject = $this->replacePlaceholders($payload, $subject);
		$message = $this->replacePlaceholders($payload, $message);

		$notification = (new AdminNotification($subject, $message))->bundle('Ts');

		return $notification;
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		// TODO anders lösen
		$dialog->setOption('placeholders', true);

		$eventName = $dataClass->oWDBasic->getEvent()->event_name;

		if (!class_exists($eventName) || !is_subclass_of($eventName, SystemEvent::class)) {

			$tab->setElement($dialog->createRow($dataClass->t('Betreff'), 'input', [
				'db_alias' => 'tc_emc',
				'db_column' => 'meta_subject'
			]));

			$tab->setElement($dialog->createRow($dataClass->t('Nachricht'), 'textarea', [
				'db_alias' => 'tc_emc',
				'db_column' => 'meta_message'
			]));

			if (is_subclass_of($eventName, AttachmentsEvent::class)) {
				self::addGui2DialogAttachmentsField($dialog, $tab, $dataClass);
			}
		}
	}

}
