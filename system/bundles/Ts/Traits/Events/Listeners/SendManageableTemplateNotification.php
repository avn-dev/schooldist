<?php

namespace Ts\Traits\Events\Listeners;

use Core\Interfaces\Events\AttachmentsEvent;
use Core\Interfaces\Events\SystemEvent;
use Tc\Entity\AbstractManagedEntity;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Traits\Listeners\SendNotificationTrait;
use Ts\Interfaces\Events\InquiryEvent;
use Ts\Notifications\InquiryNotification;

trait SendManageableTemplateNotification
{
	public function getManagedTemplateNotification(\Ext_TS_Inquiry $inquiry): ?InquiryNotification
	{
		if (null === $templateId = $this->managedObject->getSetting('template_id')) {
			return null;
		}

		$template = self::getEmailTemplate($templateId);

		$sendMode = $this->managedObject->getSetting('send_mode', 'automatic');

		$notification = new InquiryNotification($inquiry, $template, $sendMode);

		return $notification;
	}

	protected static function getEmailTemplate(int $templateId): \Ext_TC_Communication_Template
	{
		return \Ext_TC_Communication_Template::getInstance($templateId);
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$eventName = $dataClass->oWDBasic->getEvent()->event_name;

		if (!class_exists($eventName) || !is_subclass_of($eventName, SystemEvent::class)) {

			$templates = \Ext_TC_Util::addEmptyItem(\Factory::executeStatic(\Ext_TC_Communication_AutomaticTemplate::class, 'getSelectOptionTemplates'));

			$tab->setElement($dialog->createRow($dataClass->t('Template'), 'select', [
				'db_alias' => 'tc_emc',
				'db_column' => 'meta_template_id',
				'select_options' => $templates,
				'required' => true
			]));

			$tab->setElement($dialog->createRow($dataClass->t('Sendevorgang'), 'select', [
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

		}
	}

}