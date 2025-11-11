<?php

namespace Core\Interfaces\Events;

interface AttachmentsEvent
{
	public function getAttachments($listener): array;
}