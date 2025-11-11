<?php

namespace Ts\Listeners;

use Core\Interfaces\Events\AttachmentsEvent;
use Core\Interfaces\Events\SystemEvent;
use Core\Notifications\AdminNotification;
use Core\Service\NotificationService;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Psr\Log\LoggerInterface;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Tc\Traits\Listeners\SendNotificationTrait;
use Ts\Interfaces\Events\InquiryEvent;
use Ts\Traits\Events\Listeners\SendManageableTemplateNotification;

class SendIndividualEmail implements Manageable
{
	use ManageableTrait,
		SendNotificationTrait,
		SendManageableTemplateNotification;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Individuell: E-Mail versenden');
	}

	public static function toReadable(Settings $settings): string
	{
		$sendMode = $settings->getSetting('send_mode');

		if (!empty($templateId = $settings->getSetting('template_id'))) {
			$template = self::getEmailTemplate($templateId);
			$readable = sprintf(
				EventManager::l10n()->translate('Individuell: E-Mail "%s" an "%s" versenden'),
				$template->name,
				$settings->getSetting('email_addresses', '')
			);
		} else {
			$readable = sprintf(
				EventManager::l10n()->translate('Individuell: E-Mail an "%s" versenden'),
				$settings->getSetting('email_addresses', '')
			);
		}

		if ($sendMode === \Ext_TC_Communication::SEND_MODE_SPOOL) {
			$readable .= sprintf(' (%s)', EventManager::l10n()->translate('Entwurf'));
		}

		return $readable;
	}

	private function logger(): LoggerInterface
	{
		return NotificationService::getLogger('SendIndividualEmail');
	}

	public function handle($payload): void
	{
		$notification = $notifiables = null;

		if ($this->isManaged()) {
			[$notification, $notifiables] = $this->getManagedIndividualNotification($payload);
		}

		/**
		 * Möglichkeit die Notification über das Event anzupassen oder Informationen zu ergänzen (z.b. Anhänge)
		 */
		if (method_exists($payload, 'getIndividualNotification')) {
			[$notification, $notifiables] = $payload->getIndividualNotification($this, $notification, $notifiables);
		} else if (method_exists($payload, 'getNotification')) {
			$notification = $payload->getNotification($this, $notification);
		}

		if (!is_array($notifiables) || empty($notifiables)) {
			$this->logger()->error('No notifiables', ['event' => $payload::class]);
			return;
		}

		if ($notification) {

			// z.B. Anhänge
			$this->bindEventPayloadToNotification($payload, $notification);

			$this->checkQueue($notification);

			Notification::sendNow($notifiables, $notification, ['mail']);

		} else {
			$this->logger()->error('No notification object', ['event' => $payload::class]);
		}

	}

	public function getManagedIndividualNotification($payload): array
	{
		$emailAddresses = array_map(
			fn ($email) => (new AnonymousNotifiable())->route('mail', $email)->route('admin-mail', $email),
			$this->getEmailAddresses()
		);

		// TODO Notifications ohne Template ermöglichen
		if ($payload instanceof InquiryEvent && !empty($this->managedObject->getSetting('template_id'))) {
			$notification = $this->getManagedTemplateNotification($payload->getInquiry());
		} else {
			$subject = strip_tags($this->managedObject->getSetting('subject', ''));
			$message = nl2br(strip_tags($this->managedObject->getSetting('message', '')));

			$subject = $this->replacePlaceholders($payload, $subject);
			$message = $this->replacePlaceholders($payload, $message);

			$notification = (new AdminNotification($subject, $message))->bundle('Ts');
		}

		return [$notification, $emailAddresses];
	}

	private function getEmailAddresses(): array
	{
		if (is_array($emailAddresses = $this->managedObject->getSetting('email_addresses', ''))) {
			return $emailAddresses;
		}

		if (str_contains($emailAddresses, ';')) {
			$originalEmailAddresses = explode(';', $emailAddresses);
		} else {
			$originalEmailAddresses = explode(',', $emailAddresses);
		}

		$cleanEmailAddresses = array_map(fn ($email) => trim($email), $originalEmailAddresses);

		return array_filter($cleanEmailAddresses, fn ($email) => \Util::checkEmailMx($email));
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$l10n = EventManager::l10n();

		$eventName = $dataClass->oWDBasic->getEvent()->event_name;

		if (class_exists($eventName) && !is_subclass_of($eventName, SystemEvent::class)) {

			$canHaveTemplates = is_subclass_of($eventName, InquiryEvent::class);

			if ($canHaveTemplates) {

				$templates = \Ext_TC_Util::addEmptyItem(\Factory::executeStatic(\Ext_TC_Communication_AutomaticTemplate::class, 'getSelectOptionTemplates'));

				$tab->setElement($dialog->createRow($l10n->translate('Template'), 'select', [
					'db_alias' => 'tc_emc',
					'db_column' => 'meta_template_id',
					'select_options' => $templates,
					'events' => [
						[
							'event' => 'change',
							'function' => 'reloadDialogTab',
							'parameter' => 'aDialogData.id, [0, 1]'
						]
					]
				]));
			}

			if ($canHaveTemplates && !empty($dataClass->oWDBasic->meta_template_id)) {

				$dialog->setOption('placeholders', false);

				$tab->setElement($dialog->createRow($l10n->translate('Sendevorgang'), 'select', [
					'db_alias' => 'tc_emc',
					'db_column' => 'meta_send_mode',
					'select_options' => [
						\Ext_TC_Communication::SEND_MODE_AUTOMATIC => $dataClass->t('Sofort'),
						\Ext_TC_Communication::SEND_MODE_SPOOL => $dataClass->t('Als Entwurf speichern (E-Mail kann geprüft und manuell verschickt werden)'),
					],
					'required' => true
				]));

				if (is_subclass_of($eventName, AttachmentsEvent::class)) {
					self::addGui2DialogAttachmentsField($dialog, $tab, $dataClass);
				}

			} else {

				$dialog->setOption('placeholders', true);

				$tab->setElement($dialog->createRow($dataClass->t('Betreff'), 'input', [
					'db_alias' => 'tc_emc',
					'db_column' => 'meta_subject',
					'required' => true
				]));

				$tab->setElement($dialog->createRow($dataClass->t('Nachricht'), 'textarea', [
					'db_alias' => 'tc_emc',
					'db_column' => 'meta_message',
					'required' => true
				]));

			}

		}

		$tab->setElement($dialog->createRow($l10n->translate('E-Mail-Adressen'), 'input', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_email_addresses',
			'required' => true
		]));
	}
}
