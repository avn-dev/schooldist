<?php

namespace Ts\Listeners;

use Core\Interfaces\Events\AttachmentsEvent;
use Core\Notifications\AdminNotification;
use Core\Service\NotificationService;
use Illuminate\Support\Facades\Notification;
use Psr\Log\LoggerInterface;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\Events\Settings;
use Tc\Traits\Events\ManageableTrait;
use Tc\Traits\Listeners\SendNotificationTrait;
use Ts\Interfaces\Events\InquiryEvent;
use Ts\Interfaces\Events\MultipleSchoolsEvent;
use Ts\Interfaces\Events\SchoolEvent;
use Ts\Traits\Events\Listeners\SendManageableTemplateNotification;

class SendSchoolNotification implements Manageable
{
	use ManageableTrait,
		SendNotificationTrait,
		SendManageableTemplateNotification {
			prepareGui2Dialog as traitPrepareGui2Dialog;
		}

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Nachricht an Schule versenden');
	}

	public static function toReadable(Settings $settings): string
	{
		if (!empty($settings->getSetting('template_id'))) {
			$template = self::getEmailTemplate($settings->getSetting('template_id'));
			return sprintf(EventManager::l10n()->translate('Schule: E-Mail "%s" versenden'), $template->name);
		}

		return EventManager::l10n()->translate('Nachricht an Schule versenden');
	}

	private function logger(): LoggerInterface
	{
		return NotificationService::getLogger('SendSchoolEmail');
	}

	public function handle($payload): void
	{
		if ($payload instanceof SchoolEvent) {
			$schools = [$payload->getSchool()];
		} else if ($payload instanceof MultipleSchoolsEvent) {
			$schools = $payload->getSchools();
		} else if ($this->isManaged()) {
			$schools = \Ext_Thebing_School::query()->whereIn('id', $this->managedObject->getSetting('school_ids'))->get()->toArray();
		} else {
			$schools = \Ext_Thebing_School::query()->get()->toArray();
		}

		if (empty($schools)) {
			$this->logger()->error('No school objects', ['event' => $payload::class]);
			return;
		}

		$notification = null;
		if ($this->isManaged()) {
			if ($payload instanceof InquiryEvent && !empty($this->managedObject->getSetting('template_id'))) {
				$notification = $this->getManagedTemplateNotification($payload->getInquiry());
			} else {
				$notification = $this->getManagedSchoolNotification($payload);
			}
		}

		/**
		 * Möglichkeit die Notification über das Event anzupassen oder Informationen zu ergänzen (z.b. Anhänge)
		 */
		if (method_exists($payload, 'getSchoolNotification')) {
			$notification = $payload->getSchoolNotification($this, $notification, $schools);
		} else if (method_exists($payload, 'getNotification')) {
			$notification = $payload->getNotification($this, $notification);
		}

		if ($notification) {

			// z.B. Anhänge
			$this->bindEventPayloadToNotification($payload, $notification);

			$this->checkQueue($notification);

			// Channels werden über die Notification bestimmt (via())
			Notification::sendNow($schools, $notification);

		} else {
			$this->logger()->error('No notification object', ['event' => $payload::class]);
		}
	}

	private function getManagedSchoolNotification($payload): AdminNotification
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
		$eventName = $dataClass->oWDBasic->getEvent()->event_name;

		if (
			class_exists($eventName) &&
			// TODO Klarer definieren (instance of SystemEvents?)
			!is_subclass_of($eventName, SchoolEvent::class) &&
			!is_subclass_of($eventName, MultipleSchoolsEvent::class)
		) {

			$tab->setElement($dialog->createRow($dataClass->t('Schulen'), 'select', [
				'db_alias' => 'tc_emc',
				'db_column' => 'meta_school_ids',
				'multiple' => 5,
				'jquery_multiple' => 1,
				'searchable' => 1,
				'select_options' => \Ext_Thebing_School::query()->pluck('ext_1', 'id')->toArray()
			]));

		} else {

			$canHaveTemplates = is_subclass_of($eventName, InquiryEvent::class);

			if ($canHaveTemplates) {
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
			}

			if ($canHaveTemplates && !empty($dataClass->oWDBasic->meta_template_id)) {

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

		if (is_subclass_of($eventName, AttachmentsEvent::class)) {
			self::addGui2DialogAttachmentsField($dialog, $tab, $dataClass);
		}
	}

}
