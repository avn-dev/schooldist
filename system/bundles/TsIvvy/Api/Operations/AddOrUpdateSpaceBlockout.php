<?php

namespace TsIvvy\Api\Operations;

use TsIvvy\Api\Request;
use Illuminate\Support\Collection;
use TsIvvy\DTO\BlockoutSpace;
use TsIvvy\Exceptions\RuntimeException;
use TsIvvy\Handler\ExternalApp;

/**
 * https://developer.ivvy.com/venues/getoraddbookingdata/add-or-update-blockoutspace
 */
class AddOrUpdateSpaceBlockout extends AbstractOperation {

	private $blockoutSpace;

	public function getUri(): string {
		return $this->buildUri('venue', 'addOrUpdateSpaceBlockout');
	}

	public function __construct(BlockoutSpace $blockoutSpace) {
		$this->blockoutSpace = $blockoutSpace;
	}

	public function manipulateRequest(Request $request) {

		$bookerId = ExternalApp::getIvvyUserId($this->blockoutSpace->getBooker()->getId());

		if ($bookerId === null) {
			throw new RuntimeException(sprintf('Missing ivvy booker id of "%s"!', $this->blockoutSpace->getBooker()->getName()));
		}

		$spaceId = $this->blockoutSpace->getSpaceId();
		$venueId = $this->blockoutSpace->getVenueId();

		if ($spaceId === null) {
			throw new RuntimeException(sprintf('Missing ivvy space id of blockout "%s"!', $this->blockoutSpace->getName()));
		}

		if ($venueId === null) {
			throw new RuntimeException(sprintf('Missing ivvy venue id of blockout "%s"!', $this->blockoutSpace->getName()));
		}

		// Zeit für Auf-/Abbau ergänzen

		$dateRange = $this->blockoutSpace->getPeriod();

		$startDate = $dateRange->getStartDate();
		$endDate = $dateRange->getEndDate();

		if (is_numeric($setupTime = ExternalApp::getSetupTime())) {
			$startDate->subMinutes($setupTime);
		}

		if (is_numeric($setdownTime = ExternalApp::getSetdownTime())) {
			$endDate->addMinutes($setdownTime);
		}

		if (null !== $id = $this->blockoutSpace->getId()) {
			$request->set('id', $id); // update
		}

		$request->set('name', $this->blockoutSpace->getName());
		$request->set('venueId', $venueId);
		$request->set('spaceId', $spaceId);
		$request->set('bookedById', (int)$bookerId);
		$request->set('blockMethod', 'single');
		$request->set('startDateTime', $startDate->toDateTimeString());
		$request->set('endDateTime', $endDate->toDateTimeString());

	}

}
