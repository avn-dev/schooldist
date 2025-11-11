<?php

namespace TsIvvy\Api\Model;

use Illuminate\Support\Collection;

class Room extends Model {

	public function getId() {
		return implode('_', [$this->getVenueId(), $this->data->get('id')]);
	}

	/**
	 * Die Venue-Id des Raumes wird in Fidelo gesetzt und kommt nicht über die Api
	 * TsIvvy\Api\Model\Venue::getRooms()
	 *
	 * @return mixed
	 */
	public function getVenueId() {
		return $this->data->get('venueId', 0);
	}

	public function getName(): string {
		return $this->data->get('name', "Unknown");
	}

	public function getLayouts(): Collection {
		return collect($this->data->get('layouts', []));
	}

	public function getLayout(int $layoutId): ?array {
		return $this->getLayouts()->first(function($layout) use($layoutId) {
			return ($layout['id'] === $layoutId);
		});
	}

	public function getSetupTime(int $layoutId) {
		$layout = $this->getLayout($layoutId);
		if(is_array($layout)) {
			return $layout['timeForSetup'];
		}

		return 0;
	}

	public function getSetdownTime(int $layoutId) {

		$layout = $this->getLayout($layoutId);
		if(is_array($layout)) {
			return $layout['timeForTakedown'];
		}

		return 0;
	}

}
