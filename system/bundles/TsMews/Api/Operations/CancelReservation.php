<?php

namespace TsMews\Api\Operations;

use TsMews\Api\Request;
use TsMews\Exceptions\MissingIdentifierException;
use TsMews\Interfaces\Operation;
use Illuminate\Support\Collection;

/**
 * https://mews-systems.gitbook.io/connector-api/operations/reservations#cancel-reservation
 */
class CancelReservation implements Operation {

    private $allocation;

    public function __construct(\Ext_Thebing_Accommodation_Allocation $allocation) {
        $this->allocation = $allocation;
    }

    public function getUri(): string {
        return '/reservations/cancel';
    }

    public function manipulateRequest(Request $request): Request {

    	$mewsId = $this->allocation->getMeta('mews_id');

        if ($mewsId === null) {
            throw new MissingIdentifierException(sprintf('Missing mews identifier for allocation "%s"', $this->allocation->getId()));
        }

        $request->set("ReservationIds", [$mewsId]);
        $request->set("ChargeCancellationFee", false);
        $request->set("Notes", "Cancellation through Connector API");

        return $request;
    }

	public function handleResponse(Collection $response) {
    	$this->allocation->unsetMeta('mews_id');
	}

}
