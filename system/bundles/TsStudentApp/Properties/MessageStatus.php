<?php

namespace TsStudentApp\Properties;

use TsStudentApp\Properties\Traits\HasPlaceholders;
use TsStudentApp\Service\MessengerService;

/**
 * TODO noch nicht benutzt
 */
class MessageStatus implements Property
{
	use HasPlaceholders;

	const PROPERTY = 'message-status-{id}';

	public function __construct(private readonly MessengerService $messenger) {}

	public function rawProperty(): string
	{
		return self::PROPERTY;
	}

	public function data(): mixed
	{
		return $this->getMessage()->status;
	}

	public function destroy(): bool
	{
		return $this->getMessage()->status === \Communication\Enums\MessageStatus::SEEN->value;
	}

	private function getMessage(): \Ext_TC_Communication_Message
	{
		return \Ext_TC_Communication_Message::getInstance($this->placeholders['id']);
	}

}