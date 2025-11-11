<?php

namespace TsMews\Api\Operations;

use TsMews\Api\Request;
use TsMews\Entity\Allocation;
use TsMews\Exceptions\MissingIdentifierException;
use TsMews\Interfaces\Operation;

/**
 * https://mews-systems.gitbook.io/connector-api/operations/reservations#confirm-reservation
 */
class ConfirmReservation implements Operation {

    private $allocation;

    public function __construct(\Ext_Thebing_Accommodation_Allocation $allocation) {
        $this->allocation = $allocation;
    }

    public function getUri(): string {
        return '/reservations/confirm';
    }

    public function manipulateRequest(Request $request): Request {

    	$mewsId = $this->allocation->getMeta('mews_id');

        if ($mewsId === null) {
            throw new MissingIdentifierException(sprintf('Missing mews identifier for allocation "%s"', $this->allocation->getId()));
        }

        $request->set("ReservationIds", [$mewsId]);
        $request->set("SendConfirmationEmail", false);

        return $request;
    }

}
