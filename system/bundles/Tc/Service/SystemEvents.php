<?php

namespace Tc\Service;
use Core\Notifications\AnnouncementNotification;
use Core\Notifications\PopupNotification;
use Illuminate\Support\Facades\Notification;
use Tc\Entity\EventManagement;
use Tc\Events\NewFideloNews;

class SystemEvents
{
	public static function dispatchNewsEvents(bool $caching = false): void
	{
		$lastNewsKey = \System::d('tc_news_last_dispatched', null);

		// TODO entfernen
		if ($lastNewsKey === null) {
			$legacyNewsKey = \WDCache::get('tc_welcome_news_last_key', true);
			\System::s('tc_news_last_dispatched', (int)$legacyNewsKey);
			$lastNewsKey = $legacyNewsKey;
		}

		if (empty([$dateAsOf, $entries] = \Ext_TC_Welcome::readFile($caching, false))) {
			return;
		}

		if (!empty($entries)) {

			$newLastNewsKey = max(array_column($entries, 'key'));

			if (null === $lastNewsKey) {
				$lastNewsKey = $newLastNewsKey; // Ab heute starten
			}

			$newEntries = array_filter($entries, fn ($entry) => $entry['key'] > $lastNewsKey);

			if (!empty($newEntries)) {

				$eventEntity = EventManagement::query()->onlyValid()->where('event_name', NewFideloNews::class)->first();

				foreach ($newEntries as $entry) {

					$notification = NewFideloNews::buildSystemUserNotification($entry);

					if (
						!$eventEntity ||
						$notification instanceof AnnouncementNotification ||
						$notification instanceof PopupNotification
					) {
						// Notification immer an alle Benutzer schicken
						$users = \Factory::executeStatic(\User::class, 'query')->get();
						Notification::sendNow($users, $notification, ['database']);
					} else {
						// Über Event-Manager
						NewFideloNews::dispatch($entry);
					}

				}

			}

			\System::s('tc_news_last_dispatched', $newLastNewsKey);

		}

	}

}