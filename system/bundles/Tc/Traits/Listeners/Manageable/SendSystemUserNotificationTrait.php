<?php

namespace Tc\Traits\Listeners\Manageable;

use Core\Interfaces\Events\SystemEvent;
use Psr\Log\LoggerInterface;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Notifications\SystemUserNotification;
use Tc\Traits\Listeners\SendNotificationTrait;

trait SendSystemUserNotificationTrait
{
	use SendNotificationTrait;

	const GROUP_EVENT_TITLE = 'event_title';
	const GROUP_EVENT_ENTITY = 'event_entity';
	const GROUP_OWN_TITLE = 'own_title';

	/**
	 * Generiert eine Notification anhand der Einstellungen in der Ereignissteuerung
	 *
	 * @param $payload
	 * @return SystemUserNotification|null
	 */
	public function getManagedSystemUserNotification($payload): ?SystemUserNotification
	{
		if ($payload instanceof SystemEvent) {
			// Für Systemevents können keine eigenen Texte definiert werden, diese müssen von dem Event geliefert werden.
			// z.b. Systemupdates, News
			return null;
		}

		$notification = new SystemUserNotification('');

		$setting = $this->managedObject->getSetting('group_setting', self::GROUP_EVENT_TITLE);

		$group = match ($setting) {
			self::GROUP_EVENT_TITLE => EventManager::getEventTitle($payload::class),
			self::GROUP_EVENT_ENTITY => $this->managedObject->getEvent()->name,
			self::GROUP_OWN_TITLE => $this->managedObject->getSetting('own_group_title', '')
		};

		if (empty($group)) {
			// Fallback
			$group = $this::getTitle();
		}

		$message = nl2br(strip_tags($this->managedObject->getSetting('message', '')));
		$message = $this->replacePlaceholders($payload, $message);

		$notification->group($group);
		$notification->message($message);

		return $notification;
	}

	protected static function addMessageFields(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$event = $dataClass->oWDBasic->getEvent();

		$tab->setElement($dialog->createRow($dataClass->t('Gruppierung'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_group_setting',
			'default' => self::GROUP_EVENT_TITLE,
			'select_options' => [
				self::GROUP_EVENT_TITLE => sprintf($dataClass->t('Vordefinierter Name des Ereignisses').': "%s"', EventManager::getEventTitle($event->event_name)),
				self::GROUP_EVENT_ENTITY => sprintf($dataClass->t('Bezeichnung des Ereignisses').': "%s"', $event->name),
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
	}

	protected static function addChannelField(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		$tab->setElement($dialog->createRow($dataClass->t('Kanäle eingrenzen'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_channels',
			'multiple' => 5,
			'jquery_multiple' => true,
			'searchable' => true,
			'select_options' => [
				'database' => $dataClass->t('Systembenachrichtigung'),
				'admin-mail' => $dataClass->t('E-Mail'),
			]
		]));
	}
}