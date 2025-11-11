<?php

namespace Core\Interfaces\Notification;

interface Queueable
{
	public function queue(int $priority): static;

	public function shouldQueue(): bool;

	public function getQueuePriority(): ?int;
}
