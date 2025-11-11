<?php

namespace TsIvvy\Api\Operations;

use TsIvvy\Api\Request;

/**
 * https://developer.ivvy.com/venues/getoraddbookingdata/get-booking
 */
class GetBooking extends AbstractOperation {

	private $bookingId;

	public function getUri(): string {
		return $this->buildUri('venue', 'getBooking');
	}

	public function __construct(int $bookingId) {
		$this->bookingId = $bookingId;
	}

	public function manipulateRequest(Request $request) {
		$request->set('id', $this->bookingId);
	}

}
