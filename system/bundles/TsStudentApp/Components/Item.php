<?php

namespace TsStudentApp\Components;

class Item implements Component
{
	private array $cssClass = [];

	private string $label = '';

	private ?string $icon = null;
	private ?string $avatar = null;

	private array $lines = [];

	private bool $toggle = false;

	public function getKey(): string
	{
		if ($this->toggle) {
			return 'item-toggle';
		}
		return 'item';
	}

	public function label(string $label): static
	{
		$this->label = $label;
		return $this;
	}

	public function line(string $line): static
	{
		$this->lines[] = $line;
		return $this;
	}

	public function icon(string $icon): static
	{
		$this->icon = $icon;
		return $this;
	}

	public function avatar(string $avatar): static
	{
		$this->avatar = $avatar;
		return $this;
	}

	public function toggle(): static
	{
		$this->toggle = true;
		return $this;
	}

	public function rounded(): static
	{
		$this->cssClass[] = ' rounded-box';
		return $this;
	}

	public function toArray(): array
	{
		return [
			'cssClass' => implode(' ', $this->cssClass),
			'label' => $this->label,
			'lines' => $this->lines,
			'icon' => $this->icon,
			'avatar' => $this->avatar,
		];
	}
}