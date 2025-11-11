<?php

namespace Tc\Listeners;

use Core\Interfaces\Events\SystemEvent;
use Core\Notifications\AdminNotification;
use Core\Service\NotificationService;
use Psr\Log\LoggerInterface;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Traits\Events\ManageableTrait;
use Tc\Traits\Listeners\SendNotificationTrait;
use Tc\Interfaces\Events\AccommodationEvent;

class SendAccommodationProviderNotification implements Manageable
{
	use ManageableTrait,
		SendNotificationTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Unterkunftsanbieter: E-Mail versenden');
	}

	public static function toReadable(): string
	{
		return EventManager::l10n()->translate('Nachricht an Unterkunftsanbieter versenden');
	}

	public function handle(AccommodationEvent $payload): void
	{
		// TODO $accommodation kann hier niemals null sein weil das Interface schon voraussagt dass es eine geben muss
		if (null === $accommodation = $payload->getAccommodation()) {
			$this->logger()->info('Accommodation missing', ['event' => $payload::class]);
			return;
		}

		if (empty($accommodation->email)) {
			$this->logger()->error('No notifiables', ['event' => $payload::class]);
			return;
		}

		$notification = null;
		if ($this->isManaged()) {
			$notification = $this->getManagedAccommodationNotification($payload);
		}

		//		/**
//		 * Möglichkeit die Notification zu ändern oder Informationen zu ergänzen (z.b. Anhänge)
//		 */
//		if (method_exists($payload, 'getAccommodationNotification')) {
//			$notification = $payload->getAccommodationNotification($this, $notification, $accommodation);
//		} else if (method_exists($payload, 'getNotification')) {
//			$notification = $payload->getNotification($this, $notification);
//		}


		if ($notification) {

			// z.B. Anhänge
			$this->bindEventPayloadToNotification($payload, $notification);

			$accommodation->notifyNow($notification);

		} else {
			$this->logger()->error('No notification object', ['event' => $payload::class, 'accommodation_id' => $accommodation->id]);
		}
	}

	private function getManagedAccommodationNotification($payload)
	{
		$subject = nl2br(strip_tags($this->managedObject->getSetting('subject', '')));
		$message = nl2br(strip_tags($this->managedObject->getSetting('message', '')));

		if (
			method_exists($payload, 'getPlaceholderObject') &&
			null !== ($placeholderObject = $payload::getPlaceholderObject($payload))
		) {
			$subject = $placeholderObject->replace($subject);
			$message = $placeholderObject->replace($message);
		}

		if (str_contains($message, '{')) {
			$message = \Factory::getObject(\SmartyWrapper::class)->fetch('string:' . $message);
		}

		$notification = (new AdminNotification($subject, $message));

		return $notification;
	}

	protected function logger(): LoggerInterface
	{
		return NotificationService::getLogger('SendAccommodationNotification');
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		// TODO anders lösen
		$dialog->setOption('placeholders', true);

		if (
			!class_exists($dataClass->oWDBasic->getEvent()->event_name) ||
			!is_subclass_of($dataClass->oWDBasic->getEvent()->event_name, SystemEvent::class)
		) {
			$tab->setElement($dialog->createRow($dataClass->t('Betreff'), 'input', [
				'db_alias' => 'tc_emc',
				'db_column' => 'meta_subject'
			]));

			$tab->setElement($dialog->createRow($dataClass->t('Nachricht'), 'textarea', [
				'db_alias' => 'tc_emc',
				'db_column' => 'meta_message'
			]));
		}
	}

}

