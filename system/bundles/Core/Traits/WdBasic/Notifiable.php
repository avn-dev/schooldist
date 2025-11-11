<?php

namespace Core\Traits\WdBasic;

use Illuminate\Notifications\RoutesNotifications;

/**
 * Ersetzt Laravel Eloquent trait: \Illuminate\Notifications\Notifiable
 */
trait Notifiable
{
	use HasDatabaseNotifications, RoutesNotifications;
}
