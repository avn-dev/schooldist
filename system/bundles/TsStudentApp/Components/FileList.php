<?php

namespace TsStudentApp\Components;

class FileList implements Component
{
	private array $files = [];

	public function getKey(): string
	{
		return 'file-list';
	}

	public function file(string $file, string $name, string $icon = 'document-outline'): static
	{
		$this->files[] = ['path' => $file, 'name' => $name, 'icon' => $icon];
		return $this;
	}

	public function toArray(): array
	{
		return [
			'files' => $this->files
		];
	}
}