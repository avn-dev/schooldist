<?php

namespace Core\Traits\WdBasic;

use Core\Entity\System\UserNotification;

/**
 * Ersetzt Laravel Eloquent trait: \Illuminate\Notifications\HasDatabaseNotifications
 */
trait HasDatabaseNotifications
{
	/**
	 * Get the entity's notifications.
	 *
	 * @return \Core\Database\WDBasic\Builder
	 */
	public function notifications()
	{
		return UserNotification::query()
			->where('notifiable', $this->getId())
			->orderBy('created', 'desc')
			->orderBy('id', 'desc');
	}

	/**
	 * @return \Core\Database\WDBasic\Builder
	 */
	public function readNotifications()
	{
		return $this->notifications()->whereNotNull('read_at');
	}

	/**
	 * @return \Core\Database\WDBasic\Builder
	 */
	public function unreadNotifications()
	{
		return $this->notifications()->whereNull('read_at');
	}
}
