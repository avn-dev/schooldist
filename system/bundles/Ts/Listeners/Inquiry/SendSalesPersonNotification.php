<?php

namespace Ts\Listeners\Inquiry;

use Core\Interfaces\Events\AttachmentsEvent;
use Core\Interfaces\Events\SystemEvent;
use Core\Service\NotificationService;
use Psr\Log\LoggerInterface;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Tc\Traits\Listeners\Manageable\SendSystemUserNotificationTrait;
use Ts\Interfaces\Events\InquiryEvent;
use Ts\Traits\Events\Listeners\SendManageableTemplateNotification;

class SendSalesPersonNotification implements Manageable
{
	use ManageableTrait,
		SendManageableTemplateNotification,
		SendSystemUserNotificationTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Vertriebsmitarbeiter: Systembenachrichtigung versenden');
	}

	public static function toReadable(Settings $settings): string
	{
		if ($settings->getSetting('template_id') !== null) {
			$template = self::getEmailTemplate($settings->getSetting('template_id'));
			return sprintf(EventManager::l10n()->translate('Vertriebsmitarbeiter: E-Mail "%s" versenden'), $template->name);
		}

		return self::getTitle();
	}

	protected function logger(): LoggerInterface
	{
		return NotificationService::getLogger('SendSalesPersonNotification');
	}

	public function handle(InquiryEvent $payload): void
	{
		$inquiry = $payload->getInquiry();

		if (empty($inquiry->sales_person_id)) {
			$this->logger()->error('Sales person missing', ['inquiry_id' => $inquiry->id, 'event' => $payload::class]);
			return;
		}

		$salesperson = $inquiry->getSalesPerson();

		$notification = null;
		if ($this->isManaged()) {
			if (!empty($this->managedObject->getSetting('template_id'))) {
				$notification = $this->getManagedTemplateNotification($inquiry);
			} else {
				$notification = $this->getManagedSystemUserNotification($payload);
			}
		}

		/**
		 * Möglichkeit die Notification über das Event anzupassen oder Informationen zu ergänzen (z.b. Anhänge)
		 */
		if (method_exists($payload, 'getSalesPersonNotification')) {
			$notification = $payload->getSalesPersonNotification($this, $notification, $salesperson);
		} else if (method_exists($payload, 'getNotification')) {
			$notification = $payload->getNotification($this, $notification);
		}

		if ($notification) {

			// z.B. Anhänge
			$this->bindEventPayloadToNotification($payload, $notification);

			$this->checkQueue($notification);

			$this->bindRelation($notification, $inquiry);

			$salesperson->notifyNow($notification);

		} else {
			$this->logger()->error('No notification object', ['event' => $payload::class, 'inquiry_id' => $inquiry->id]);
		}

	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$eventName = $dataClass->oWDBasic->getEvent()->event_name;

		if (!class_exists($eventName) || !is_subclass_of($eventName, SystemEvent::class)) {

			$templates = \Factory::executeStatic(\Ext_TC_Communication_AutomaticTemplate::class, 'getSelectOptionTemplates');

			$tab->setElement($dialog->createRow($dataClass->t('Template'), 'select', [
				'db_alias' => 'tc_emc',
				'db_column' => 'meta_template_id',
				'select_options' => \Ext_TC_Util::addEmptyItem($templates, $dataClass->t('Eigene Nachricht definieren')),
				'events' => [
					[
						'event' => 'change',
						'function' => 'reloadDialogTab',
						'parameter' => 'aDialogData.id, [0, 1]'
					]
				]
			]));

			if (!empty($dataClass->oWDBasic->meta_template_id)) {

				$dialog->setOption('placeholders', false);

				$tab->setElement($dialog->createRow($dataClass->t('Sendevorgang'), 'select', [
					'db_alias' => 'tc_emc',
					'db_column' => 'meta_send_mode',
					'select_options' => [
						\Ext_TC_Communication::SEND_MODE_AUTOMATIC => $dataClass->t('Sofort'),
						\Ext_TC_Communication::SEND_MODE_SPOOL => $dataClass->t('Als Entwurf speichern (E-Mail kann geprüft und manuell verschickt werden)'),
					],
					'required' => true,
				]));

			} else {

				$dialog->setOption('placeholders', true);

				$eventTitle = EventManager::getEventTitle($dataClass->oWDBasic->getEvent()->event_name);
				$entityTitle = $dataClass->oWDBasic->getEvent()->name;

				$tab->setElement($dialog->createRow($dataClass->t('Gruppierung'), 'select', [
					'db_alias' => 'tc_emc',
					'db_column' => 'meta_group_setting',
					'default' => self::GROUP_EVENT_TITLE,
					'select_options' => [
						self::GROUP_EVENT_TITLE => sprintf($dataClass->t('Vordefinierter Name des Ereignisses').': "%s"', $eventTitle),
						self::GROUP_EVENT_ENTITY => sprintf($dataClass->t('Bezeichnung des Ereignisses').': "%s"', $entityTitle),
						self::GROUP_OWN_TITLE => $dataClass->t('Eigene Gruppierung definieren'),
					]
				]));

				$tab->setElement($dialog->createRow($dataClass->t('Eigene Gruppierung'), 'input', [
					'db_alias' => 'tc_emc',
					'db_column' => 'meta_own_group_title',
					'required' => true,
					'dependency_visibility' => [
						'db_alias' => 'tc_emc',
						'db_column' => 'meta_group_setting',
						'on_values' => [self::GROUP_OWN_TITLE]
					]
				]));

				$tab->setElement($dialog->createRow($dataClass->t('Nachricht'), 'textarea', [
					'db_alias' => 'tc_emc',
					'db_column' => 'meta_message'
				]));

				self::addChannelField($dialog, $tab, $dataClass);

				$tab->setElement($dialog->createNotification(
					$dataClass->t('Achtung'),
					$dataClass->t('Die Systembenachrichtigung werden nur verschickt wenn der Vertriebsmitarbeiter auch als Systembenutzer eingetragen ist'),
					'info'
				));
			}

			if (is_subclass_of($eventName, AttachmentsEvent::class)) {
				self::addGui2DialogAttachmentsField($dialog, $tab, $dataClass);
			}

		}
	}

}
