<?php

namespace TsIvvy\Api\Operations;

use TsIvvy\Api\Model\Booking;
use TsIvvy\Api\Request;
use TsIvvy\Entity\TuitionClass;
use TsIvvy\Exceptions\RuntimeException;
use TsIvvy\Handler\ExternalApp;
use Illuminate\Support\Collection;

/**
 * https://developer.ivvy.com/venues/getoraddbookingdata/change-booking-status
 * TODO - entfernen
 */
class CancelBooking extends AbstractOperation {

	private $ivvyBookingId;

	private $ivvyVenueId;

	private $ivvyCancelReasonId;

	public function getUri(): string {
		return $this->buildUri('venue', 'changeBookingStatus');
	}

	public function __construct($ivvyBookingId, $ivvyVenueId, $ivvyCancelReasonId) {
		$this->ivvyBookingId = $ivvyBookingId;
		$this->ivvyVenueId = $ivvyVenueId;
		$this->ivvyCancelReasonId = $ivvyCancelReasonId;
	}

	public function manipulateRequest(Request $request) {
		$request->set('id', $this->ivvyBookingId);
		$request->set('venueId', $this->ivvyVenueId);
		$request->set('currentStatus', Booking::STATUS_CANCELED);
		$request->set('cancelReasonId', $this->ivvyCancelReasonId);
		$request->set('cancelClosedDate', (new \DateTime())->format('Y-m-d'));
	}

}
