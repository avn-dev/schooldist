<?php

namespace TcApi\Interfaces\Events;

interface WebhookEvent
{
	public function getWebhookUrl(): ?string;

	public function getWebhookAction(): string;

	public function getWebhookPayload(): array;
}