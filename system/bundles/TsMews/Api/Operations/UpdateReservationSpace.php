<?php

namespace TsMews\Api\Operations;

use TsMews\Api;
use TsMews\Api\Request;
use TsMews\Entity\Allocation;
use TsMews\Entity\Room;
use TsMews\Exceptions\MissingIdentifierException;
use TsMews\Handler\ExternalApp;
use TsMews\Interfaces\Operation;

/**
 *
 * /reservations/updateSpace gibt es nicht mehr. Das läuft jetzt alles über /reservations/update weshalb hier die
 * UpdateReservation-Operation aufgerufen wird
 *
 * @package TsMews\Api\Operations
 */
class UpdateReservationSpace implements Operation {

    private $allocation;

    private $room;

    public function __construct(\Ext_Thebing_Accommodation_Allocation $allocation, \Ext_Thebing_Accommodation_Room $room) {
        $this->allocation = $allocation;
        $this->room = $room;
    }

    public function getUri(): string {
        return '/reservations/update';
    }

    public function manipulateRequest(Request $request): Request {

    	$allocationMewsId = $this->allocation->getMeta('mews_id');

        if ($allocationMewsId === null) {
            throw new MissingIdentifierException(sprintf('Missing mews identifier for allocation "%s"', $this->allocation->getId()));
        }

        $spaceId = ExternalApp::getRoomId($this->room);

        if (empty($spaceId)) {
            $provider = $this->room->getProvider();
            throw new MissingIdentifierException(sprintf('Missing mews identifier for bed "%s - %s"', $provider->getName(), $this->room->getName()));
        }

        $fakeOperation = new UpdateReservation($this->allocation, [
        	'AssignedResourceId' => ['Value' => $spaceId]
		]);

        $fakeOperation->manipulateRequest($request);

        return $request;
    }
}
