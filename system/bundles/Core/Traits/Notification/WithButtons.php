<?php

namespace Core\Traits\Notification;

use Core\Interfaces\Notification\Button;
use Illuminate\Support\Arr;

trait WithButtons
{
	/**
	 * @var Button[]
	 */
	private array $buttons = [];

	public function button(Button|array $button): static
	{
		$this->buttons = array_merge($this->buttons, Arr::wrap($button));
		return $this;
	}

	/**
	 * @return Button[]
	 */
	public function getButtons(): array
	{
		return $this->buttons;
	}
}