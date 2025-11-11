<?php

namespace Communication\Events;

use Core\Collection\MessageTransportCollection;
use Illuminate\Foundation\Events\Dispatchable;

class MessagesSent
{
	use Dispatchable;

	public function __construct(
		 private MessageTransportCollection $transports
	) {}

	public function getTransportCollection(): MessageTransportCollection
	{
		return $this->transports;
	}
}