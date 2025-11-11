<?php

namespace TsTuition\Communication\Application;

use Ts\Communication\Application\Booking;

class PlacementTest extends Booking
{
	public static function getFlags(): array
	{
		return static::withInquiryPlacementtestFlags();
	}
}