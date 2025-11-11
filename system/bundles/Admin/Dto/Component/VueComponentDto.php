<?php

namespace Admin\Dto\Component;

class VueComponentDto
{
	public function __construct(private string $name, private string $filePath) {}

	public function getName(): string
	{
		return $this->name;
	}

	public function getFilePath(): string
	{
		return $this->filePath;
	}
}