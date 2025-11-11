<?php

namespace TsIvvy\Api\Model;

use Core\DTO\DateRange;

class Session extends Model {

	private $timezone;

	public function setTimezone(\DateTimeZone $timezone) {
		$this->timezone = $timezone;
	}

	public function getName(): string {
		return $this->data->get('name', '');
	}

	public function getSpaceId() {
		return (int) $this->data->get('spaceId');
	}

	public function getSpaceLayoutId() {
		return (int) $this->data->get('spaceLayout');
	}

	public function getDateRange(): DateRange {
		$startDate = new \DateTime($this->data->get('startDate').' '.$this->data->get('startTime'), $this->timezone);
		$endDate = new \DateTime($this->data->get('endDate').' '.$this->data->get('endTime'), $this->timezone);

		$startDate->modify(sprintf('-%s minutes', $this->getSetupTime()));
		$endDate->modify(sprintf('+%s minutes', $this->getSetdownTime()));

		$systemTimezone = (new \DateTime())->getTimezone();

		// In System-Zeitzone umrechnen
		$startDate->setTimezone($systemTimezone);
		$endDate->setTimezone($systemTimezone);

		return new DateRange($startDate, $endDate);
	}

	public function getSetupTime(): int {

		$room = $this->api->getRoom($this->getSpaceId());
		if($room) {
			return $room->getSetupTime($this->getSpaceLayoutId());
		}

		return 0;
	}

	public function getSetdownTime(): int {
		$room = $this->api->getRoom($this->getSpaceId());
		if($room) {
			return $room->getSetdownTime($this->getSpaceLayoutId());
		}

		return 0;
	}
}
