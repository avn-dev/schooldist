<?php

namespace TsStudentApp\Components;

class Container implements Component
{
	private array $components = [];

	private string $color = 'light';

	public function getKey(): string
	{
		return 'container';
	}

	public function dark(): static
	{
		$this->color = 'dark';
		return $this;
	}

	public function white(): static
	{
		$this->color = '';
		return $this;
	}

	public function add(Component $component): static
	{
		$this->components[] = [
			'name' => $component->getKey(),
			'data' => $component->toArray(),
		];
		return $this;
	}

	public function toArray(): array
	{
		return [
			'cssClass' => $this->color, // < 3.0.0
			'css_class' => $this->color,
			'components' => $this->components
		];
	}
}