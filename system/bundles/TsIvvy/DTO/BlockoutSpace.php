<?php

namespace TsIvvy\DTO;

use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class BlockoutSpace {

	private $id;

	private $name;

	private $venueId;

	private $spaceId;

	private $booker;

	private $period;

	private $additionalData = [];

	public function __construct($id, string $name, $spaceId, $venueId, \User $booker, CarbonPeriod $period) {
		$this->id = $id;
		$this->name = $name;
		$this->spaceId = $spaceId;
		$this->venueId = $venueId;
		$this->booker = $booker;
		$this->period = $period;
	}

	public function additional(string $key, $value) {
		$this->additionalData[$key] = $value;
		return $this;
	}

	public function getId() {
		return $this->id;
	}

	public function unsetId() {
		$this->id = null;
		return $this;
	}

	public function getName() {
		return $this->name;
	}

	public function getSpaceId() {
		return $this->spaceId;
	}

	public function getVenueId() {
		return $this->venueId;
	}

	public function getBooker(): \User {
		return $this->booker;
	}

	public function getPeriod(): CarbonPeriod {
		return $this->period;
	}

}
