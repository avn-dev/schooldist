<?php

namespace TsStudentApp\Properties;

use TsStudentApp\Service\MessengerService;
use TsStudentApp\Properties\Traits\HasPlaceholders;

class LastMessages implements Property
{
	use HasPlaceholders;

	const PROPERTY = 'last-messages-{limit}';

	public function __construct(private readonly MessengerService $messenger) {}

	public function rawProperty(): string
	{
		return self::PROPERTY;
	}

	public function data(): mixed
	{
		return $this->messenger->getLastMessages($this->placeholders['limit'], ['out'])
			->map(function($message) {
				return $message->toArray();
			})
			->toArray();
	}

	public function destroy(): bool
	{
		return false;
	}
}