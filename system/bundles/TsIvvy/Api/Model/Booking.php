<?php

namespace TsIvvy\Api\Model;

use TsIvvy\Api;
use Illuminate\Support\Collection;

class Booking extends Model {

	// vorraussichtlich
	const STATUS_PROSPECTIVE = 1;

	const STATUS_TENTATIVE = 2;

	const STATUS_CONFIRMED = 3;

	const STATUS_CANCELED = 4;

	public function isCanceled(): bool {
		return ($this->getStatus() === self::STATUS_CANCELED);
	}

	public function isTentative(): bool {
		return ($this->getStatus() === self::STATUS_TENTATIVE);
	}

	public function isConfirmed(): bool {
		return ($this->getStatus() === self::STATUS_CONFIRMED);
	}

	public function isPlanned(): bool {
		return ($this->getStatus() === self::STATUS_PROSPECTIVE);
	}

	public function getCode(): string {
		return $this->data->get('code', '');
	}

	public function getStatus(): int {
		return $this->data->get('currentStatus', 0);
	}

	public function getBooker(): User {
		$this->complete();
		return new User($this->api, $this->data->get('bookedByUser', []), true);
	}

	/**
	 * @return Collection|Session[]
	 */
	public function getSessions(): Collection {
		$this->complete();
		return collect($this->data->get('sessions', []))
			->map(function(array $session) {
				$session = new Session($this->api, $session, true);
				$session->setTimezone($this->getTimezone());
				return $session;
			});
	}

	public function getTimezone(): \DateTimeZone {

		$timezone = $this->data->get('venueTimezone');

		if(!is_null($timezone)) {
			return new \DateTimeZone($timezone);
		}

		return (new \DateTime())->getTimezone();
	}

	/**
	 * Manche Daten stehen erst zur Verfügung wenn man die Buchung nochmal einzeln anfragt
	 */
	public function complete(): void {

		if($this->complete) {
			return;
		}

		// Daten der Buchung über die Api nachladen
		$this->data = $this->api->request(new Api\Operations\GetBooking($this->getId()));
		$this->complete = true;
	}

}
