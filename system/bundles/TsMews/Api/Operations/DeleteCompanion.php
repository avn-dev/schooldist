<?php

namespace TsMews\Api\Operations;

use TsMews\Api\Request;
use TsMews\Entity\Allocation;
use TsMews\Entity\Customer;
use TsMews\Entity\Room;
use TsMews\Exceptions\MissingIdentifierException;
use TsMews\Interfaces\Operation;
use Illuminate\Support\Collection;

/**
 * https://mews-systems.gitbook.io/connector-api/operations/reservations#delete-reservation-companion
 */
class DeleteCompanion implements Operation {

    private $allocation;

    private $customer;

    public function __construct(\Ext_Thebing_Accommodation_Allocation $allocation, \Ext_TS_Inquiry_Contact_Abstract $customer) {
        $this->allocation = $allocation;
        $this->customer = $customer;
    }

    public function getUri(): string {
        return '/reservations/deleteCompanion';
    }

    public function manipulateRequest(Request $request): Request {

    	$allocationMewsId = $this->allocation->getMeta('mews_id');
    	$customerMewsId = $this->customer->getMeta('mews_id');

        if ($allocationMewsId === null) {
            throw new MissingIdentifierException(sprintf('Missing mews identifier for allocation "%s"', $this->allocation->getId()));
        }

        if ($customerMewsId === null) {
            throw new MissingIdentifierException(sprintf('Missing mews identifier for customer "%s"', $this->customer->getName()));
        }

        $request->set('ReservationId', $allocationMewsId);
        $request->set('CustomerId', $customerMewsId);

        return $request;
    }

}
