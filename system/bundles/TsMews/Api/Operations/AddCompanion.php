<?php

namespace TsMews\Api\Operations;

use TsMews\Api\Request;
use TsMews\Entity\Allocation;
use TsMews\Entity\Customer;
use TsMews\Entity\Room;
use TsMews\Exceptions\FailedException;
use TsMews\Exceptions\MissingIdentifierException;
use TsMews\Interfaces\Operation;
use Illuminate\Support\Collection;

/**
 * https://mews-systems.gitbook.io/connector-api/operations/reservations#add-reservation-companion
 */
class AddCompanion implements Operation {

    private $parentAllocation;

    private $allocation;

    private $customer;

	public function getUri(): string {
		return '/reservations/addCompanion';
	}

    public function __construct(\Ext_Thebing_Accommodation_Allocation $parentAllocation, \Ext_Thebing_Accommodation_Allocation $allocation) {
        $this->parentAllocation = $parentAllocation;
        $this->allocation = $allocation;
        $this->customer = $allocation->getCustomer();
    }

    public function manipulateRequest(Request $request): Request {

    	$parentMewsId = $this->parentAllocation->getMeta('mews_id');
    	$customerMewsId = $this->customer->getMeta('mews_id');

        if ($parentMewsId === null) {
            throw new MissingIdentifierException(sprintf('Missing mews identifier for allocation "%s"', $this->parentAllocation->getId()));
        }

        if ($customerMewsId === null) {
            throw new MissingIdentifierException(sprintf('Missing mews identifier for customer "%s"', $this->customer->getName()));
        }

        $request->set('ReservationId', $parentMewsId);
        $request->set('CustomerId', $customerMewsId);

        return $request;
    }

	public function handleResponse(Collection $response) {
		$this->allocation->setMeta('mews_id', $this->parentAllocation->getMeta('mews_id'));

		// Nicht über $this->allocation->save() gehen da sonst die Hooks erneut ausgeführt werden

		$metadata = $this->allocation->getJoinedObjectChilds(\WDBasic_Attribute::TABLE_KEY, true);

		foreach ($metadata as $metaObject) {
			if (!$metaObject->exist()) {
				$metaObject->save();
			}
		}
	}

}
