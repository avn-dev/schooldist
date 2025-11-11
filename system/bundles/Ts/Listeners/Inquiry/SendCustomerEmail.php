<?php

namespace Ts\Listeners\Inquiry;

use Core\Service\NotificationService;
use Psr\Log\LoggerInterface;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Tc\Traits\Listeners\SendNotificationTrait;
use Ts\Interfaces\Events\InquiryEvent;
use Ts\Interfaces\Events\MultipleInquiriesEvent;
use Ts\Traits\Events\Listeners\SendManageableTemplateNotification;

class SendCustomerEmail implements Manageable
{
	use ManageableTrait,
		SendNotificationTrait,
		SendManageableTemplateNotification;

	private function logger(): LoggerInterface
	{
		return NotificationService::getLogger('SendCustomerEmail');
	}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Kunde: E-Mail versenden');
	}

	public static function toReadable(Settings $settings): string
	{
		$template = self::getEmailTemplate($settings->getSetting('template_id'));
		$sendMode = $settings->getSetting('send_mode');

		$readable = sprintf(
			EventManager::l10n()->translate('Kunde: E-Mail "%s" versenden'),
			$template->name
		);

		if ($sendMode === \Ext_TC_Communication::SEND_MODE_SPOOL) {
			$readable .= sprintf(' (%s)', EventManager::l10n()->translate('Entwurf'));
		}

		return $readable;
	}

	public function handle(MultipleInquiriesEvent|InquiryEvent $payload): void
	{
		if ($payload instanceof InquiryEvent) {
			$inquiries = [$payload->getInquiry()];
		} else {
			$inquiries = $payload->getInquiries();
		}

		foreach ($inquiries as $inquiry) {

			$traveller = $inquiry->getTraveller();

			$notification = null;
			if ($this->isManaged()) {
				$notification = $this->getManagedTemplateNotification($inquiry);
			}

			/**
			 * Möglichkeit die Notification über das Event anzupassen oder Informationen zu ergänzen (z.b. Anhänge)
			 */
			if (method_exists($payload, 'getCustomerNotification')) {
				$notification = $payload->getCustomerNotification($this, $notification, $traveller);
			} else if (method_exists($payload, 'getNotification')) {
				$notification = $payload->getNotification($this, $notification);
			}

			if ($notification) {

				// z.B. Anhänge
				$this->bindEventPayloadToNotification($payload, $notification);

				$this->checkQueue($notification);

				$this->bindRelation($notification, $inquiry);

				$traveller->notifyNow($notification, ['mail']);

			} else {
				$this->logger()->error('No notification object', ['event' => $payload::class, 'inquiry_id' => $inquiry->id, 'customer_id' => $traveller->id]);
			}
		}

	}

}
