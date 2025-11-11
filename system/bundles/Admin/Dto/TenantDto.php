<?php

namespace Admin\Dto;

use Illuminate\Contracts\Support\Arrayable;

class TenantDto implements Arrayable
{

	public function __construct(
		private string $key,
		private string $label,
		private ?string $logo = null,
		private bool $showLabel = true,
		private bool $selected = false,
		private ?string $color = null,
		private ?string $text = null
	) {}

	public function showLabel(bool $value): static
	{
		$this->showLabel = $value;
		return $this;
	}
	/**
	 * @return string
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	public function toArray(): array
	{
		return [
			'key' => $this->key,
			'label' => $this->label,
			'logo' => $this->logo,
			'show_label' => $this->showLabel,
			'color' => $this->color,
			'text' => $this->text,
			'selected' => $this->selected,
		];
	}
}