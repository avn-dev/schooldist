<?php

namespace TsAccounting\Dto\BookingStack;

class ExportFileContent
{
	public function __construct(
		private string $fileName,
		private string $content,
		private array $data
	) {}

	public function getFileName(): string
	{
		return $this->fileName;
	}

	public function getContent(): string
	{
		return $this->content;
	}

	public function getData(): array
	{
		return $this->data;
	}
}