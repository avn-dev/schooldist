<?php

namespace TsIvvy\Api\Operations;

use TsIvvy\Api;
use TsIvvy\Api\Request;
use TsIvvy\Traits\Operation\Pagination;

/**
 * https://developer.ivvy.com/venues/getoraddbookingdata/get-booking-list
 */
class GetBookingList extends AbstractOperation {
	use Pagination;

	private $venueId;

	/**
	 * @var \DateTime
	 */
	private $modifiedBefore;

	private $modifiedAfter;

	public function getUri(): string {
		return $this->buildUri('venue', 'getBookingList');
	}

	public function __construct(int $venueId) {
		$this->venueId = $venueId;
	}

	public function setModifiedAfter(\DateTime $dateTime) {
		$this->modifiedAfter = $dateTime;
	}

	public function setModifiedBefore(\DateTime $dateTime) {
		$this->modifiedAfter = $dateTime;
	}

	public function manipulateRequest(Request $request) {

		$this->setPaginationValues($request);

		$request->set('venueId', $this->venueId);

		// Fidelo Blöcke nicht laden
		$request->filter('code__NOTCONTAINS', Api::ENTITY_CODE_PREFIX);
		
		if($this->modifiedBefore) {
			$request->filter('modifiedDateBefore', $this->modifiedBefore);
		}

		if($this->modifiedAfter) {
			$request->filter('modifiedDateAfter', $this->modifiedAfter);
		}
	}

}
