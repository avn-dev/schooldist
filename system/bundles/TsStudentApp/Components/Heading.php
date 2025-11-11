<?php

namespace TsStudentApp\Components;

class Heading implements Component
{
	private string $text = '';

	private array $cssClass = ['uppercase'];

	public function getKey(): string
	{
		return 'heading';
	}

	public function text(string $text): static
	{
		$this->text = $text;
		return $this;
	}

	public function cssClass(string $cssClass): static
	{
		$this->cssClass[] = $cssClass;
		return $this;
	}

	public function toArray(): array
	{
		return [
			'text' => $this->text,
			'cssClass' => implode(' ', $this->cssClass), // < 3.0.0
			'css_class' => implode(' ', $this->cssClass),
		];
	}
}