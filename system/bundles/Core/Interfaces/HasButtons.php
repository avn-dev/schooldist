<?php

namespace Core\Interfaces;

use Core\Interfaces\Notification\Button;

interface HasButtons
{
	public function button(Button|array $button): static;

	public function getButtons(): array;
}