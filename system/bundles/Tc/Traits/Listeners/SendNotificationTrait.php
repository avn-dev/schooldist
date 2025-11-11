<?php

namespace Tc\Traits\Listeners;

use Core\Interfaces\Events\AttachmentsEvent;
use Core\Interfaces\HasAlertLevel;
use Core\Interfaces\HasAttachments;
use Core\Interfaces\HasButtons;
use Core\Interfaces\HasIcon;
use Core\Interfaces\Notification\Queueable;
use Illuminate\Notifications\Notification;
use Psr\Log\LoggerInterface;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Interfaces\EventManager\ManageableEvent;
use Tc\Interfaces\EventManager\ManageableNotification;

trait SendNotificationTrait
{
	abstract protected function logger(): LoggerInterface;

	protected function replacePlaceholders($event, string $text): string
	{
		if (null !== ($placeholderObject = $this->getPlaceholderObject($event))) {
			$text = $placeholderObject->replace($text);

			if (!empty($errors = $placeholderObject->getErrors())) {
				$this->logger()->error('Placeholders failed', ['placeholders' => $placeholderObject::class, 'errors' => $errors]);
				// TODO Exception?
			}
		}

		if (!empty($text) && str_contains($text, '{')) {
			// Smarty-Platzhalter ersetzen
			$text = \Factory::getObject(\SmartyWrapper::class)->fetch('string:' . $text);
		}

		return $text;
	}

	protected function getPlaceholderObject($event): ?\Ext_TC_Placeholder_Abstract
	{
		// Platzhalterklasse für das Event vorhanden?
		if (is_object($event) && method_exists($event, 'getPlaceholderObject')) {
			return $event::getPlaceholderObject($event);
		}

		return null;
	}

	protected function bindEventPayloadToNotification($event, Notification $notification)
	{
		// Verknüpfung mit Ereignissteuerung

		if ($notification instanceof ManageableNotification) {
			$process = ($event instanceof ManageableEvent) ? $event->getManagedObject() : null;
			$task = ($this instanceof Manageable) ? $this->getManagedObject() : null;

			if ($process) {
				$notification->bindProcess($process, $task);
			}
		}

		// Icon

		if ($event instanceof HasIcon && $notification instanceof HasIcon) {
			$notification->icon($event->getIcon());
		}

		// Alerts

		if ($event instanceof HasAlertLevel && $notification instanceof HasAlertLevel) {
			$notification->alert($event->getAlertLevel());
		}

		// Buttons

		if ($event instanceof HasButtons && $notification instanceof HasButtons) {
			$notification->button($event->getButtons());
		}

		// z.B. Anhänge

		if ($event instanceof AttachmentsEvent && $notification instanceof HasAttachments) {
			$withAttachments = ($this->isManaged())
				? !((bool)$this->managedObject->getSetting('no_attachments', false))
				: true;

			if ($withAttachments) {
				$notification->attach($event->getAttachments($this));
			}
		}

	}

	protected function checkQueue(Notification $notification, int $prio = 10): void
	{
		if ($notification instanceof Queueable) {
			$notification->queue($prio);
		}
	}

	protected function bindRelation($notification, \WDBasic|array $relation)
	{
		// Interface?
		if (method_exists($notification, 'relation')) {
			$notification->relation($relation);
		}
	}

	protected static function addGui2DialogAttachmentsField(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass)
	{
		$tab->setElement($dialog->createRow(EventManager::l10n()->translate('Ohne Anhang versenden'), 'checkbox', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_no_attachments'
		]));
	}
}