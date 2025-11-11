<?php

namespace Core\Traits\Notification;

use Core\Notifications\Attachment;
use Illuminate\Support\Arr;

trait WithAttachments
{
	/**
	 * @var Attachment[]
	 */
	private array $attachments = [];

	public function attach(Attachment|array $attachment): static
	{
		$this->attachments = array_merge($this->attachments, Arr::wrap($attachment));
		return $this;
	}

	/**
	 * @return Attachment[]
	 */
	public function getAttachments(): array
	{
		return $this->attachments;
	}
}