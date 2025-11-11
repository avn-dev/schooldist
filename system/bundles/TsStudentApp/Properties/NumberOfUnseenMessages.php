<?php

namespace TsStudentApp\Properties;

use TsStudentApp\Service\MessengerService;

class NumberOfUnseenMessages implements Property
{
	const PROPERTY = 'unread-messages';

	public function __construct(private readonly MessengerService $messenger) {}

	public function property(): string
	{
		return self::PROPERTY;
	}

	public function data(): mixed
	{
		return $this->messenger->getNumberOfUnreadMessages();
	}

	public function destroy(): bool
	{
		return false;
	}
}