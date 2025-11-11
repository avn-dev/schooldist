<?php

namespace Core\Interfaces\Notification;

interface Button
{
	public function getTitle(): string;

	public function isAccessible(\Access $access): bool;

}