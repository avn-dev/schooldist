<?php

namespace TsIvvy\Api\Operations;

use TsIvvy\Api;
use TsIvvy\Api\Request;
use TsIvvy\DTO\BlockoutSpace;
use TsIvvy\Exceptions\RuntimeException;
use Illuminate\Support\Collection;

/**
 * https://developer.ivvy.com/venues/getoraddbookingdata/remove-blockoutspace
 */
class RemoveBlockoutSpace extends AbstractOperation {

	private $blockoutSpace;

	public function getUri(): string {
		// Achtung in der API-Doku steht es falsch
		return $this->buildUri('venue', 'removeSpaceBlockout');
	}

	public function __construct(BlockoutSpace $blockoutSpace) {
		$this->blockoutSpace = $blockoutSpace;
	}

	public function manipulateRequest(Request $request) {

		$ivvyId = $this->blockoutSpace->getId();
		$venueId = $this->blockoutSpace->getVenueId();

		if ($ivvyId === null) {
			throw new RuntimeException(sprintf('Missing id of blockout space "%s"!', $this->blockoutSpace->getName()));
		}

		if ($venueId === null) {
			throw new RuntimeException(sprintf('Missing venue id of blockout space "%s"!', $this->blockoutSpace->getName()));
		}

		$request->set('id', $ivvyId);
		$request->set('venueId', $venueId);

	}

}
