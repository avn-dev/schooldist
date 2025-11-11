<?php

namespace Communication\Interfaces\Notifications;

interface NotificationRoute
{
	public function toNotificationRoute(string $channel);

	public function getNotificationName(string $channel): ?string;

}