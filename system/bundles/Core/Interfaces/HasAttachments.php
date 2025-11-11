<?php

namespace Core\Interfaces;

use Core\Notifications\Attachment;

interface HasAttachments
{
	public function attach(Attachment|array $attachment): static;

	/**
	 * @return Attachment[]
	 */
	public function getAttachments(): array;
}