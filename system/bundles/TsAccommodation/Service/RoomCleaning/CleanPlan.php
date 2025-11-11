<?php

namespace TsAccommodation\Service\RoomCleaning;

use DateTime;
use Illuminate\Support\Collection;
use TsAccommodation\Entity\Cleaning\Type;
use TsAccommodation\Service\RoomCleaning\CleanPlan\Date;

class CleanPlan {

    private $allocation;

    private $onlyFullCleaning;

    private $start;

    private $end;

    private $roomCleaningDates;

    private $bedCleaningdates;

    public function __construct(\Ext_Thebing_Accommodation_Allocation $allocation, bool $onlyFullCleaning = false) {
        $this->allocation = $allocation;
        $this->onlyFullCleaning = $onlyFullCleaning;

        $this->start = DateTime::createFromFormat('Y-m-d H:i:s', $this->allocation->from);
        $this->end = DateTime::createFromFormat('Y-m-d H:i:s', $this->allocation->until);

        $this->roomCleaningDates = new Collection();
        $this->bedCleaningdates = new Collection();

        $this->build();
    }

    public function getRoomCleaningDates(): Collection {
        return $this->roomCleaningDates;
    }

    public function getBedCleaningDates(): Collection {
        return $this->bedCleaningdates;
    }

    private function build() {

        $types = Type::getRepository()->getTypesForAllocation($this->allocation);

        $this->roomCleaningDates = $this->buildRoomCleaningDates($types);

        if($this->onlyFullCleaning === false) {
            $this->bedCleaningdates = $this->buildBedCleaningDates($types);
        }

    }

    private function buildRoomCleaningDates(array $types): Collection {

        $dates = new Collection();

        foreach($types as $type) {
            /* @var Type $type */
            $cycles = $type->getRoomCleaningCycles();

            foreach($cycles as $cycle) {

                $dates->push(new Date($this->calculateDate($cycle), $type, $cycle, $this->allocation));

            }
        }

        return $dates;
    }

    private function buildBedCleaningDates(array $types): Collection {

        $dates = new Collection();

        foreach($types as $type) {
            /* @var Type $type */

            $cycles = $type->getBedCleaningCycles();

            foreach($cycles as $cycle) {
                /* @var Type\Cycle $cycle */

                if($cycle->isOnceBedClean()) {

                    // Einmalig Bett

                    $dates->push(new Date($this->calculateDate($cycle), $type, $cycle, $this->allocation));

                } else if(
                    $cycle->isRegularBedClean() &&
                    $cycle->count > 0
                ) {

                    // Regelmäßig Bett

                    $date = clone $this->start;

                    do {

						// TODO muss wahrscheinlich wie in $cycle->isFixBedClean() umgestellt werden (modify nach if)
                        $date = (clone $date)->modify(sprintf('+%s %s', $cycle->count, $cycle->count_mode));

                        if($date <= $this->end) {

                            $dates->push(new Date($date, $type, $cycle, $this->allocation));

                        }

                    } while ($date <= $this->end);

                } else if(
                    $cycle->isFixBedClean() &&
                    $cycle->count > 0
                ) {

                    // Fest Bett

                    $date = clone $this->start;
                    $date->modify(sprintf('%s this week', \Ext_Thebing_Util::convertWeekdayToEngWeekday($cycle->weekday)));

					do {

						if($date > $this->start && $date <= $this->end) {
							$dates->push(new Date($date, $type, $cycle, $this->allocation));
						}

						$date = (clone $date)->modify(sprintf('+%s weeks', $cycle->count));

					} while ($date <= $this->end);

                }

            }

        }

        return $dates;
    }

    private function calculateDate(Type\Cycle $cycle) {

        if($cycle->time === 'after_arrival') {
            $date = (clone $this->start)->modify(sprintf('+%s %s', $cycle->count, $cycle->count_mode));
        } else {
            $date = (clone $this->end)->modify(sprintf('-%s %s', $cycle->count, $cycle->count_mode));
        }

        return $date;

    }

}
