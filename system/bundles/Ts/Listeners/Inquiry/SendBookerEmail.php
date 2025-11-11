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

class SendBookerEmail implements Manageable {
	use ManageableTrait,
		SendNotificationTrait,
		SendManageableTemplateNotification;

	public static function getTitle(): string {
		return EventManager::l10n()->translate('Buchungskontakt: E-Mail versenden');
	}

	public static function toReadable(Settings $settings): string {
		$template = self::getEmailTemplate($settings->getSetting('template_id'));
		$sendMode = $settings->getSetting('send_mode');

		$readable = sprintf(
			EventManager::l10n()->translate('Buchungskontakt: E-Mail "%s" versenden'),
			$template->name
		);

		if ($sendMode === \Ext_TC_Communication::SEND_MODE_SPOOL) {
			$readable .= sprintf(' (%s)', EventManager::l10n()->translate('Entwurf'));
		}

		return $readable;
	}

	private function logger(): LoggerInterface {
		return NotificationService::getLogger('SendBookerEmail');
	}

	public function handle(MultipleInquiriesEvent|InquiryEvent $payload): void {
		if ($payload instanceof InquiryEvent) {
			$inquiries = [$payload->getInquiry()];
		} else {
			$inquiries = $payload->getInquiries();
		}

		foreach ($inquiries as $inquiry) {

			$booker = $inquiry->getBooker();

			$notification = null;
			if ($this->isManaged()) {
				$notification = $this->getManagedTemplateNotification($inquiry);
			}

			/**
			 * Möglichkeit die Notification über das Event anzupassen oder Informationen zu ergänzen (z.b. Anhänge)
			 */
			if (method_exists($payload, 'getBookerNotification')) {
				$notification = $payload->getBookerNotification($this, $notification, $booker);
			} else if (method_exists($payload, 'getNotification')) {
				$notification = $payload->getNotification($this, $notification);
			}

			if ($notification) {

				// z.B. Anhänge
				$this->bindEventPayloadToNotification($payload, $notification);

				$this->checkQueue($notification);

				$this->bindRelation($notification, $inquiry);

				$booker->notifyNow($notification, ['mail']);

			} else {
				$this->logger()->error('No notification object', ['event' => $payload::class, 'inquiry_id' => $inquiry->id, 'booker_id' => $booker->id]);
			}
		}

	}

}
