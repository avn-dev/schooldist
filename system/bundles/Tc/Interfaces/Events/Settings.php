<?php

namespace Tc\Interfaces\Events;

interface Settings
{
	public function getSettings(): array;

	public function getSetting(string $key, $default = null);
}