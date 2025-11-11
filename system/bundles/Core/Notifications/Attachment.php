<?php

namespace Core\Notifications;

use Core\Interfaces\HasIcon;
use Core\Traits\WithIcon;
use Illuminate\Support\Str;

/**
 * TODO auf eine existierende File-Klasse (Symfony, Laravel umstellen)
 */
class Attachment implements HasIcon
{
	use WithIcon;

	public function __construct(
		protected string $filePath,
		protected ?string $fileName = null,
		protected ?\WDBasic $entity = null,
	) {
		if (!file_exists($filePath)) {
			throw new \RuntimeException(sprintf('Attachment file does not exist [%s]', $filePath));
		}

		if (empty($fileName)) {
			$this->fileName = basename($filePath);
		}
	}

	public function getUrl(): string
	{
		return '/storage/'.Str::after($this->filePath, 'storage/');
	}

	public function getFilePath(): string
	{
		return $this->filePath;
	}

	public function getFileName(): ?string
	{
		return $this->fileName;
	}

	public function getEntity(): ?\WDBasic
	{
		return $this->entity;
	}

	public function getReadableFileSize(): string
	{
		return \Util::formatFilesize(filesize($this->filePath));
	}
}