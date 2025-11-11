<?php

namespace TsIvvy\Api\Operations;

use TsIvvy\Api\Request;

/**
 * https://developer.ivvy.com/venues/venuedata/get-venue-list
 */
class GetVenueList extends AbstractOperation {

	public function getUri(): string {
		return $this->buildUri('venue', 'getVenueList');
	}

	public function manipulateRequest(Request $request) {
		$request->set('perPage', 5);
		$request->set('start', 0);
	}

}
