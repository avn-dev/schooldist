<?php

namespace TsAccommodation\Service\RoomCleaning;

use DateTime;
use TsAccommodation\Entity\Cleaning\Type;
use Illuminate\Support\Collection;

class CleanOrder {

    private $date;

    private $complete = false;

    private $room;

    private $beds;

    public function __construct(\Ext_Thebing_Accommodation_Room $room, DateTime $date) {
        $this->date = $date;

        $this->room = $room;
        $this->beds = new Collection();
    }

    public function complete(): self {
        $this->complete = true;
        return $this;
    }

    public function bed(int $bed, Type $type, Type\Cycle $cycle, \Ext_Thebing_Accommodation_Allocation $allocation = null, $nextArrival = null): self {

		$array = [
			'bed' => $bed,
			'type' => $type,
			'cycle' => $cycle,
			'allocation' => $allocation,
			'next_arrival' => $nextArrival
		];

		if ((int)\System::d('cleaning_schedule_bed_multiple', 0) === 1) {
			// Bett mehrfach auflisten
			$this->beds->push($array);
		} else {
			$this->beds->put($bed, $array);
		}

        return $this;
    }

    public function isEmpty(): bool {
        return $this->beds->isEmpty();
    }

    public function isNotEmpty(): bool {
        return $this->beds->isNotEmpty();
    }

    public function needsCompleteCleaning(): bool {
        return $this->complete;
    }

    public function getRoom(): \Ext_Thebing_Accommodation_Room {
        return $this->room;
    }

    public function getBedsForCleaning(): Collection {
        return $this->beds;
    }

}
