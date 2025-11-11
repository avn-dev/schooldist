<?php

namespace Tc\Service\EventManager;

use Tc\Interfaces\Events\Settings;

class TestingSettings implements Settings
{

	public function __construct(
		private readonly array $settings
	) {}

	public function getSettings(): array
	{
		return $this->settings;
	}

	public function getSetting(string $key, $default = null)
	{
		return $this->settings[$key] ?? $default;
	}
}