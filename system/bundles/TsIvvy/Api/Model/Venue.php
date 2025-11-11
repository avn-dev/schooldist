<?php

namespace TsIvvy\Api\Model;

use TsIvvy\Api;
use Illuminate\Support\Collection;

class Venue extends Model {

	/**
	 * @return Collection|Room[]
	 */
	public function getRooms(): Collection {
		$this->complete();
		return collect($this->data->get('functionSpaces', []))
			->map(function(array $room) {
				$room['venueId'] = $this->getId();
				return new Room($this->api, $room, true);
			});
	}

	public function getPackages(): Collection {
		$this->complete();
		return collect($this->data->get('packages', []))
			->map(function(array $package) {
				$package['venueId'] = $this->getId();
				return new Model($this->api, $package, true);
			});
	}

	/**
	 * Manche Daten stehen erst zur Verfügung wenn man den Verantstaltungsort nochmal einzeln anfragt
	 */
	public function complete(): void {

		if($this->complete) {
			return;
		}

		// Daten über die Api nachladen
		$this->data = $this->api->request(new Api\Operations\GetVenue($this->getId()));
		$this->complete = true;
	}

}
