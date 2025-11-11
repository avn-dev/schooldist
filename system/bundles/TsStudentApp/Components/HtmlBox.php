<?php

namespace TsStudentApp\Components;

use Core\Service\HtmlPurifier;

class HtmlBox implements Component
{
	private string $content = '';

	private string|array $set = HtmlPurifier::SET_FRONTEND;

	public function getKey(): string
	{
		return 'html-box';
	}

	public function tags(string|array$set): static
	{
		$this->set = $set;
		return $this;
	}

	public function content(string $content): static
	{
		$this->content = $content;
		return $this;
	}

	public function toArray(): array
	{
		$content = (new HtmlPurifier($this->set))->purify($this->content);

		return [
			'html' => $content,
		];
	}
}