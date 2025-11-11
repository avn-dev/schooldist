<?php

namespace Core\Traits;

use Core\Interfaces\Notification\Button;
use Illuminate\Support\Arr;

trait WithButtons
{
	private array $buttons = [];

	public function button(Button|array $button): static
	{
		$this->buttons = array_merge($this->buttons, Arr::wrap($button));
		return $this;
	}

	public function getButtons(): array
	{
		return $this->buttons;
	}
}