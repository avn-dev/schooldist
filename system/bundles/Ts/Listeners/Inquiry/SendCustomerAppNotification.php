<?php

namespace Ts\Listeners\Inquiry;

use Core\Interfaces\Events\AttachmentsEvent;
use Core\Service\NotificationService;
use Psr\Log\LoggerInterface;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Facades\EventManager;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Tc\Traits\Listeners\SendNotificationTrait;
use Ts\Interfaces\Events\InquiryEvent;
use Ts\Interfaces\Events\MultipleInquiriesEvent;
use Ts\Notifications\InquiryNotification;
use Ts\Traits\Events\Listeners\SendManageableTemplateNotification;

class SendCustomerAppNotification implements Manageable
{
	use ManageableTrait,
		SendNotificationTrait,
		SendManageableTemplateNotification;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Kunde: App-Benachrichtigung versenden');
	}

	public static function toReadable(Settings $settings): string
	{
		$template = \Ext_TC_Communication_Template::getInstance($settings->getSetting('template_id'));
		$sendMode = $settings->getSetting('send_mode');

		$readable = sprintf(
			EventManager::l10n()->translate('Kunde: App-Benachrichtigung "%s" versenden'),
			$template->name
		);

		if ($sendMode === \Ext_TC_Communication::SEND_MODE_SPOOL) {
			$readable .= sprintf(' (%s)', EventManager::l10n()->translate('Nachrichten-Spool'));
		}

		return $readable;
	}

	protected function logger(): LoggerInterface
	{
		return NotificationService::getLogger('SendCustomerAppNotification');
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

			if (!$traveller->hasStudentApp()) {
				$this->logger()->error('No student device available', ['inquiry_id' => $inquiry->id, 'student_id' => $traveller->id, 'event' => $payload::class]);
				continue;
			}

			$notification = null;
			if ($this->isManaged()) {
				$notification = $this->getManagedCustomerNotification($inquiry);
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

				$traveller->notifyNow($notification, ['app']);

			} else {
				$this->logger()->error('No notification object', ['event' => $payload::class, 'inquiry_id' => $inquiry->id, 'customer_id' => $traveller->id]);
			}
		}

	}

	public function getManagedCustomerNotification(\Ext_TS_Inquiry $inquiry): ?InquiryNotification
	{
		if (null === $templateId = $this->managedObject->getSetting('template_id')) {
			return null;
		}

		$template = \Ext_TC_Communication_Template::getInstance($templateId);

		$sendMode = $this->managedObject->getSetting('send_mode', 'automatic');

		$notification = new InquiryNotification($inquiry, $template, $sendMode);

		return $notification;
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$eventName = $dataClass->oWDBasic->getEvent()->event_name;

		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Template'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_template_id',
			'select_options' => \Ext_TC_Communication_Template::getSelectOptions('app', [
				'application' => ['booking', 'cronjob'],
				'recipient' => 'customer'
			]),
			'required' => true
		]));

		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Sendevorgang'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_send_mode',
			'select_options' => [
				\Ext_TC_Communication::SEND_MODE_AUTOMATIC => $dataClass->t('Sofort'),
				\Ext_TC_Communication::SEND_MODE_SPOOL => $dataClass->t('Als Entwurf speichern (Nachricht kann geprüft und manuell verschickt werden)'),
			],
			'required' => true
		]));

		if (is_subclass_of($eventName, AttachmentsEvent::class)) {
			self::addGui2DialogAttachmentsField($dialog, $tab, $dataClass);
		}

	}

}
