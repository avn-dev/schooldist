<?php

namespace TsMews\Api\Operations;

use TsMews\Api\Request;
use TsMews\Entity\Allocation;
use TsMews\Exceptions\MissingIdentifierException;
use TsMews\Interfaces\Operation;

/**
 * https://mews-systems.gitbook.io/connector-api/operations/reservations#update-reservations
 */
class UpdateReservation implements Operation {

    private $allocation;

    private $data;

    public function __construct(\Ext_Thebing_Accommodation_Allocation $allocation, array $data) {
        $this->allocation = $allocation;
        $this->data = $data;
    }

    public function getUri(): string {
        return '/reservations/update';
    }

    public function manipulateRequest(Request $request): Request {

		$mewsId = $this->allocation->getMeta('mews_id');

        if ($mewsId === null) {
            throw new MissingIdentifierException(sprintf('Missing mews identifier for allocation "%s"', $this->allocation->getId()));
        }

		$request->set('CheckOverbooking', true);
		$request->set('CheckRateApplicability', false);

        $reservation = [];
        $reservation['ReservationId'] = $mewsId;
        foreach ($this->data as $key => $value) {
			$reservation[$key] = $value;
        }

        $request->set('ReservationUpdates', [$reservation]);

        return $request;
    }

}
