<?php

namespace TsIvvy\Api\Operations;

use TsIvvy\Api\Request;

/**
 * https://developer.ivvy.com/venues/venuedata/get-venue
 */
class GetVenue extends AbstractOperation {

	private $venueId;

	public function getUri(): string {
		return $this->buildUri('venue', 'getVenue');
	}

	public function __construct(int $venueId) {
		$this->venueId = $venueId;
	}

	public function manipulateRequest(Request $request) {
		$request->set('id', $this->venueId);
	}

}
