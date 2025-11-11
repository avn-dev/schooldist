<?php

namespace Core\Traits;

use Core\Enums\AlertLevel;

trait WithAlertLevel
{
	use WithIcon;

	private ?AlertLevel $alertLevel = null;

	public function alert(AlertLevel $level, string $icon = null): static
	{
		$this->alertLevel = $level;
		if ($icon) {
			$this->icon($icon);
		}
		return $this;
	}

	public function getAlertLevel(): ?AlertLevel
	{
		return $this->alertLevel;
	}

	public function getIcon(): ?string
	{
		if (empty($this->icon)) {
			return $this->getAlertLevel()?->getIcon();
		}
		return $this->icon;
	}
}