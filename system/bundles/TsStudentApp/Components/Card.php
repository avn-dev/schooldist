<?php

namespace TsStudentApp\Components;

use Illuminate\Support\Arr;

class Card implements Component
{
	private ?string $title = null;

	private ?string $subtitle = null;

	private array $cssClass = [];

	private string $color = '';

	private array $content = [];

	public function getKey(): string
	{
		return 'card';
	}

	public function title(?string $title): static
	{
		$this->title = $title;
		return $this;
	}

	public function subtitle(?string $subtitle): static
	{
		$this->subtitle = $subtitle;
		return $this;
	}

	public function color(string $color): static
	{
		$this->color = $color;
		return $this;
	}

	public function shadow(bool $shadow): static
	{
		if (!$shadow) {
			$this->cssClass[] = 'no-shadow';
		} else {
			if (in_array('no-shadow', $this->cssClass)) {
				unset($this->cssClass[array_search('no-shadow', $this->cssClass)]);
			}
		}

		return $this;
	}

	public function rounded(): static
	{
		$this->cssClass[] = 'rounded-box';
		$this->shadow(false);
		return $this;
	}

	public function cssClass(?string $cssClass): static
	{
		$this->cssClass[] = $cssClass;
		return $this;
	}

	public function content(Component $component): static
	{
		$this->content[] = [
			'name' => $component->getKey(),
			'data' => $component->toArray(),
		];
		return $this;
	}

	public function toArray(): array
	{
		return [
			'title' => $this->title,
			'subtitle' => $this->subtitle,
			'color' => $this->color,
			'cssClass' => implode(' ', $this->cssClass), // < 3.0.0
			'css_class' => implode(' ', $this->cssClass),
			'content' => $this->content,
		];
	}
}