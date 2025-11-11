<?php

namespace Communication\Interfaces;

use Communication\Dto\ChannelConfig;
use Core\Notifications\Recipient;

interface CommunicationChannel
{
	public function enabledCommunicationMode(): static;

	public function setCommunicationConfig(array $config): static;

	public function getCommunicationConfig(): ChannelConfig;

	public function validateRoute($route): bool;

}