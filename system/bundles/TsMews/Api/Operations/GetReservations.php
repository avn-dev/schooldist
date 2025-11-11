<?php

namespace TsMews\Api\Operations;

use Core\DTO\DateRange;
use TsMews\Api;
use TsMews\Api\Request;
use TsMews\Handler\ExternalApp;
use TsMews\Interfaces\Operation;

/**
 * https://mews-systems.gitbook.io/connector-api/operations/reservations#get-all-reservations
 */
class GetReservations implements Operation {

    private $dateRange;

    private $reservationIds = [];

    private $extentReservations = true;

    private $extentReservationGroups = false;

    private $extentCustomers = true;

    public function inDateRange(DateRange $dateRange) {
		$this->dateRange = $dateRange;
		return $this;
	}

	public function byIds(array $reservationIds) {
		$this->reservationIds = $reservationIds;
		return $this;
	}

    public function getUri(): string {
        return '/reservations/getAll';
    }

    public function manipulateRequest(Request $request): Request {

    	if (!empty($this->reservationIds)) {

			$request->set('ReservationIds', $this->reservationIds);

		} else if ($this->dateRange) {

			$request->set('TimeFilter', 'Start');
			// Start of the interval in UTC timezone in ISO 8601 format.
			$request->set('StartUtc', $this->dateRange->from);
			// End of the interval in UTC timezone in ISO 8601 format.
			$request->set('EndUtc', $this->dateRange->until);
			// state of reservations
			$request->set('States', [Api::STATE_OPTIONAL, Api::STATE_CONFIRMED, Api::STATE_CANCELED, Api::STATE_STARTED, Api::STATE_PROCESSED]);
			// nur in Zimmern suchen für die eine Mews-Id eingetragen ist
			$request->set('AssignedResourceIds', ExternalApp::getMewsRoomIds()->values()->toArray());

		} else {
    		throw new \RuntimeException('Please define date range or specify request by reservation ids.');
		}

        // Extent
        $request->set('Extent', [
            'Reservations' => $this->extentReservations,
            'ReservationGroups' => $this->extentReservationGroups,
            'Customers' => $this->extentCustomers
        ]);

        return $request;
    }
}
