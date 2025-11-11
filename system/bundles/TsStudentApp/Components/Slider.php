<?php

namespace TsStudentApp\Components;

class Slider implements Component
{
	protected string $title = '';

	protected array $components = [];

	protected array $cssClass = [];

	public function getKey(): string
	{
		return 'swiper';
	}

	/**
	 * @deprecated
	 */
	public function title(string $title):static
	{
		$this->title = $title;
		return $this;
	}

	public function dark():static
	{
		$this->cssClass[] = ' dark';
		return $this;
	}

	public function slide(Component $component): static
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
			'title' => $this->title,
			'components' => $this->components,
			'cssClass' => implode(' ', $this->cssClass), // < 3.0.0
			'css_class' => implode(' ', $this->cssClass)
		];
	}
}