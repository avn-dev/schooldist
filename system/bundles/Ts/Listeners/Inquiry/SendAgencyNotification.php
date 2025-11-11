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
use Ts\Traits\Events\Listeners\SendManageableTemplateNotification;

class SendAgencyNotification implements Manageable
{
	use ManageableTrait,
		SendNotificationTrait,
		SendManageableTemplateNotification;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Agentur: E-Mail versenden');
	}

	public static function toReadable(Settings $settings): string
	{
		$template = self::getEmailTemplate($settings->getSetting('template_id'));
		$sendMode = $settings->getSetting('send_mode');

		$readable = sprintf(
			EventManager::l10n()->translate('Agentur: E-Mail "%s" versenden'),
			$template->name
		);

		if ($sendMode === \Ext_TC_Communication::SEND_MODE_SPOOL) {
			$readable .= sprintf(' (%s)', EventManager::l10n()->translate('Entwurf'));
		}

		return $readable;
	}

	protected function logger(): LoggerInterface
	{
		return NotificationService::getLogger('SendAgencyNotification');
	}

	public function handle(InquiryEvent $payload): void
	{
		$inquiry = $payload->getInquiry();

		if (!$inquiry->hasAgency()) {
			$this->logger()->error('No agency', ['inquiry_id' => $inquiry->id, 'event' => $payload::class]);
			return;
		}

		$agency = $inquiry->getAgency();

		$agencyContact = $inquiry->getAgencyContact();
		if (!$agencyContact->exist()) {
			$agencyContact = $agency->getMasterContact();
		}

		if (!$agencyContact instanceof \TsCompany\Entity\Contact || !$agencyContact->exist()) {
			$this->logger()->error('Agency contact missing', ['inquiry_id' => $inquiry->id, 'agency_id' => $agency->id, 'event' => $payload::class]);
			return;
		}

		$notification = null;
		if ($this->isManaged()) {
			$notification = $this->getManagedTemplateNotification($inquiry);
		}

		/**
		 * Möglichkeit die Notification über das Event anzupassen oder Informationen zu ergänzen (z.b. Anhänge)
		 */
		if (method_exists($payload, 'getAgencyNotification')) {
			$notification = $payload->getAgencyNotification($this, $notification, $agencyContact);
		} else if (method_exists($payload, 'getNotification')) {
			$notification = $payload->getNotification($this, $notification);
		}

		if ($notification) {

			// z.B. Anhänge
			$this->bindEventPayloadToNotification($payload, $notification);

			$this->checkQueue($notification);

			$this->bindRelation($notification, $agency);

			$agencyContact->notifyNow($notification);

		} else {
			$this->logger()->error('No notification object', ['event' => $payload::class, 'inquiry_id' => $inquiry->id]);
		}
	}

}
