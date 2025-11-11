<?php

namespace Core\Interfaces;

use Core\Enums\AlertLevel;

interface HasAlertLevel extends HasIcon
{
	public function alert(AlertLevel $level, string $icon = null): static;

	public function getAlertLevel(): ?AlertLevel;
}