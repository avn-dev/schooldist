<?php

namespace Core\Traits\Notification;

trait WithQueue
{
	private int $queuePriority = 0;

	public function queue(?int $priority = 0): static
	{
		$this->queuePriority = $priority;
		return $this;
	}

	public function shouldQueue(): bool
	{
		return $this->queuePriority > 0;
	}

	public function getQueuePriority(): int
	{
		return $this->queuePriority;
	}
}