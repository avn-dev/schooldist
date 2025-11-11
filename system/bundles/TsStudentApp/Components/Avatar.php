<?php

namespace TsStudentApp\Components;

class Avatar implements Component
{
	private string $imageUrl = '';

	private array $cssClass = [];

	public function getKey(): string
	{
		return 'ion-avatar';
	}

	public function image(string $imageUrl): static
	{
		$this->imageUrl = $imageUrl;
		return $this;
	}

	public function shadowed(): static
	{
		$this->cssClass[] = 'with-shadow';
		return $this;
	}

	public function toArray(): array
	{
		return [
			'image' => $this->imageUrl,
			'cssClass' => implode(' ', $this->cssClass), // < 3.0.0
			'class_class' => implode(' ', $this->cssClass)
		];
	}
}