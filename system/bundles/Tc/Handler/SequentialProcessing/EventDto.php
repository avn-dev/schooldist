<?php

namespace Tc\Handler\SequentialProcessing;

class EventDto
{
	public function __construct(private string $eventName, private $payload) {}

	public function getEventName(): string
	{
		return $this->eventName;
	}

	public function getPayload()
	{
		return $this->payload;
	}
}