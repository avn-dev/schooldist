<?php

namespace Tc\Listeners;

use Core\Interfaces\Events\AttachmentsEvent;
use Core\Interfaces\Events\SystemEvent;
use Core\Service\NotificationService;
use Illuminate\Support\Facades\Notification;
use Psr\Log\LoggerInterface;
use Tc\Entity\SystemTypeMapping;
use Tc\Events\EntityEventDispatched;
use Tc\Facades\EventManager;
use Tc\Gui2\Data\EventManagement\TaskData;
use Tc\Interfaces\EventManager\Manageable;
use Tc\Traits\Events\ManageableTrait;
use Tc\Traits\Listeners\Manageable\SendSystemUserNotificationTrait;

class SendSystemUserNotification implements Manageable
{
	use ManageableTrait,
		SendSystemUserNotificationTrait;

	public static function getTitle(): string
	{
		return EventManager::l10n()->translate('Mitarbeiter: Systembenachrichtigung versenden');
	}

	protected function logger(): LoggerInterface
	{
		return NotificationService::getLogger('SendSystemUserNotification');
	}

	public function handle($payload)
	{
		$notification = $users = null;

		if ($this->isManaged()) {
			$users = $this->managedObject->getSetting('users', []);
			if ($payload instanceof EntityEventDispatched) {
				$notification = $payload->getEvent()->getEntitySubscriptionNotification();
			} else {
				$notification = $this->getManagedSystemUserNotification($payload);
			}
		}

		/**
		 * Möglichkeit die Notification über das Event anzupassen oder Informationen zu ergänzen (z.b. Anhänge)
		 */
		if (method_exists($payload, 'getSystemUserNotification')) {
			[$notification, $users] = $payload->getSystemUserNotification($this, $notification, $users);
		} else if (method_exists($payload, 'getNotification')) {
			$notification = $payload->getNotification($this, $notification);
		}

		if (is_array($users) && $notification) {

			$this->bindEventPayloadToNotification($payload, $notification);

			$channels = ($this->isManaged())
				? $this->managedObject->getSetting('channels', null)
				: null;

            Notification::sendNow($users, $notification, !empty($channels) ? $channels : null);
		}
	}

	public static function prepareGui2Dialog(\Ext_Gui2_Dialog $dialog, \Ext_Gui2_Dialog_Tab $tab, TaskData $dataClass): void
	{
		// TODO anders lösen
		$dialog->setOption('placeholders', true);

		$eventName = $dataClass->oWDBasic->getEvent()->event_name;

		if (!class_exists($eventName) || !is_subclass_of($eventName, SystemEvent::class)) {
			self::addMessageFields($dialog, $tab, $dataClass);

			if (is_subclass_of($eventName, AttachmentsEvent::class)) {
				self::addGui2DialogAttachmentsField($dialog, $tab, $dataClass);
			}
		}

		$tab->setElement($dialog->createRow($dataClass->t('Empfängergruppe'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'meta_receivers_type',
			'select_options' => [
				'users' => $dataClass->t('Einzelne Benutzer'),
				'user_groups' => $dataClass->t('Bestimmte Benutzergruppen')
			],
			'child_visibility' => [
				[
					'db_alias' => 'tc_emc',
					'db_column' => 'users',
					'on_values' => ['users']
				],
				[
					'db_alias' => 'tc_emc',
					'db_column' => 'user_groups',
					'on_values' => ['user_groups']
				],
			]
		]));

		$tab->setElement($dialog->createRow($dataClass->t('Benutzer'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'users',
			'multiple' => 5,
			'jquery_multiple' => true,
			'searchable' => true,
			'required' => true,
			'select_options' => collect(\Factory::executeStatic(\User::class, 'getList'))
				->mapWithKeys(fn ($user) => [$user['id'] => implode(', ', [$user['lastname'], $user['firstname']])])
		]));

		$tab->setElement($dialog->createRow($dataClass->t('Benutzergruppen'), 'select', [
			'db_alias' => 'tc_emc',
			'db_column' => 'user_groups',
			'multiple' => 5,
			'jquery_multiple' => true,
			'searchable' => true,
			'required' => true,
			'select_options' => SystemTypeMapping::query()
				->pluck('name', 'id')
		]));

		self::addChannelField($dialog, $tab, $dataClass);

	}

}
