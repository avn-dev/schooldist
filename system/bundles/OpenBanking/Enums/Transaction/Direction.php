<?php

namespace OpenBanking\Enums\Transaction;

enum Direction: string
{
	case OUTGOING = 'outgoing';
	case INCOMING = 'incoming';

	public function isOutgoing(): bool
	{
		return $this === self::OUTGOING;
	}

	public function isIncoming(): bool
	{
		return $this === self::INCOMING;
	}
}
