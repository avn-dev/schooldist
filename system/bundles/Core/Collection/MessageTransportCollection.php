<?php

namespace Core\Collection;

use Core\Notifications\Channels\MessageTransport;
use Illuminate\Support\Collection;

class MessageTransportCollection extends Collection
{
	const STATUS_ALL_FAILED = 'ALL_MESSAGES_FAILED';
	const STATUS_SOME_FAILED = 'SOME_MESSAGES_FAILED';
	const STATUS_ALL_SENT = 'ALL_MESSAGES_SENT';

	public function hasFailedTransports(): bool
	{
		$failed = $this->first(fn (MessageTransport $transport) => !$transport->successfully());
		return $failed !== null;
	}

	public function hasSuccessfullyTransports(): bool
	{
		$successfully = $this->first(fn (MessageTransport $transport) => $transport->successfully());
		return $successfully !== null;
	}

	public function getStatus(): string
	{
		[$failed, $successfully] = $this->partition(fn (MessageTransport $transport) => !$transport->successfully());

		if ($failed->isNotEmpty()) {
			return $successfully->isNotEmpty() ? self::STATUS_SOME_FAILED : self::STATUS_ALL_FAILED;
		}

		return self::STATUS_ALL_SENT;
	}

}