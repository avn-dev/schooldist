<?php

namespace TsAccommodation\Service;

use DateTime;
use TsAccommodation\Entity\Cleaning\Type;
use TsAccommodation\Service\RoomCleaning\CleanOrder;
use TsAccommodation\Service\RoomCleaning\CleanPlan;
use Illuminate\Support\Collection;

class RoomCleaningService {

    protected static $allocationCleanPlanCache = [];

    /**
     * Liefert die Reinigungspläne für ein bestimmtes Datum
     *
     * @param DateTime $date
     * @param bool $onlyFullCleaning
     * @return Collection|CleanOrder[]
     */
    public function getCleanOrdersForDate(DateTime $date, bool $onlyFullCleaning = false): Collection {

        // Alle Räumen für die Reinungstypen eingestellt sind
        $roomIds = Type::getRepository()
            ->getAllRoomIds();

        $orders = new Collection();

        foreach ($roomIds as $roomId) {

            $cleanOrder = $this->getCleaningOrderForRoom($roomId, $date, $onlyFullCleaning);

            // nur wenn Reinigung notwendig ist
            if($cleanOrder->isNotEmpty()) {
                $orders->push($cleanOrder);
            }
        }

        return $orders;
    }

    /**
     * Baut den Reinigungsplan für ein Zimmer an einem bestimmten Datum auf
     *
     * @param int $roomId
     * @param DateTime $date
     * @param bool $onlyFullCleaning
     * @return CleanOrder
     * @throws \Exception
     */
    public function getCleaningOrderForRoom(int $roomId, DateTime $date, bool $onlyFullCleaning = false): CleanOrder {

        // Allocations zu dem Tag
        $currentAllocations = (new \Ext_TS_Matching())->getAllocationOfRoom($roomId, $date, $date);
        $nextAllocations = (new \Ext_TS_Matching())->getNextAllocationsOfRoom($roomId, $date);

        $searchNextArrival = function(int $bed) use($nextAllocations) {
            $allocation = collect($nextAllocations)
                ->first(function($allocation) use ($bed) {
                    return ((int)$allocation['bed'] === $bed);
                });

            return ($allocation) ? (new DateTime())->setTimestamp($allocation['from']) : null;
        };

        $cleanOrder = new CleanOrder(\Ext_Thebing_Accommodation_Room::getInstance($roomId), $date);

        if(is_array($currentAllocations)) {

            $ids = array_column($currentAllocations, 'id');

            $allocations = \Ext_Thebing_Accommodation_Allocation::getRepository()
                ->findBy(['id' => $ids]);

            $roomCleaningDates = [];

            foreach($allocations as $allocation) {

                //if(strpos($allocation->from, $date->format('Y-m-d')) !== false) {
				//    continue;
				//}

                $nextArrival = $searchNextArrival((int)$allocation->bed);

                $cacheKey = implode('_', [$allocation->getId(), (int) $onlyFullCleaning]);

                if(isset(self::$allocationCleanPlanCache[$cacheKey])) {
                    $cleanPlan = self::$allocationCleanPlanCache[$cacheKey];
                } else {
                    $cleanPlan = new CleanPlan($allocation, $onlyFullCleaning);
                }

                self::$allocationCleanPlanCache[$cacheKey] = $cleanPlan;

                // Datum für komplette Raumreinigung
                $roomCleaningDate = $cleanPlan->getRoomCleaningDates()
                    ->first(function($roomCleaningDate) use ($date) {
                        return ($roomCleaningDate->date->format('Y-m-d') === $date->format('Y-m-d'));
                    });

                if($onlyFullCleaning === false) {

                    // Datum für Bettreinigung
                    $bedCleaningDate = $cleanPlan->getBedCleaningDates()
                        ->first(function($bedCleaningDate) use ($date) {
                            return ($bedCleaningDate->date->format('Y-m-d') === $date->format('Y-m-d'));
                        });

                    if($bedCleaningDate) {

                        $add = true;

                        /*if(
                            // Bei fester Bettenreinigung kann es eine Abhängigkeit zur Zimmerreinigung geben. Wenn innerhalb
                            // der nächsten Tage eine Zimmerreinigung stattfinden muss das Bett nicht gereinigt werden
                            $bedCleaningDate->cycle->isFixBedClean() &&
                            $bedCleaningDate->cycle->isDependendingOnFullCleaning()
                        ) {

                            for($day = 1; $day <= $bedCleaningDate->cycle->depending_days; ++$day) {

                                $checkDate = (clone $bedCleaningDate->date)->modify(sprintf('+%s days', $day));

                                // Zimmerreinigungsplan für die nächsten Tage holen
                                $fullCleanOrder = $this->getCleaningOrderForRoom($roomId, $checkDate, true);

                                if(
                                    !$fullCleanOrder->isEmpty() &&
                                    $fullCleanOrder->complete()
                                ) {
                                    // Zimmerreinigung findet bald statt und keine Anreise dazwischen
                                    $add = false;
                                    break;
                                } elseif (
                                    // Falls vor einer Zimmerreinigung eine Anreise stattfindet muss die Bettreinigung stattfinden
                                    $nextArrival &&
                                    $nextArrival->format('Y-m-d') === $checkDate->format('Y-m-d')
                                ) {
                                    // $add auf true lassen und schleife abbrechen
                                    break;
                                }

                            }
                        }*/

                        if($add) {
                            // Bettreinigung auf den Reinigungsplan schreiben (wird bei Zimmerreinigung unten überschrieben)
                            $cleanOrder->bed($allocation->bed, $bedCleaningDate->type, $bedCleaningDate->cycle, $allocation, $nextArrival);
                        }
                    }
                }

                // Immer hinzufügen um unten auf null zu prüfen
                $roomCleaningDates[] = $roomCleaningDate;
            }

            // Wenn es kein null in dem Array gibt haben alle Allocations die Zimmerreinigung an demselben Tag
            if(!empty($roomCleaningDates) && !in_array(null, $roomCleaningDates)) {

                $cleanOrder->complete();

                $room = \Ext_Thebing_Accommodation_Room::getInstance($roomId);
                $beds = $room->getNumberOfBeds();

                $roomCleaningDate = reset($roomCleaningDates);

                // Alle Betten auf den Reinigungsplan schreiben
                for($bed = 1; $bed <= $beds; ++$bed) {

                    // Allocation zu dem Bett raussuchen
                    $allocation = collect($allocations)
                        ->first(function($allocation) use ($bed) {
                            return ((int)$allocation->bed === $bed);
                        });

                    $nextArrival = $searchNextArrival($bed);



                    $cleanOrder->bed($bed, $roomCleaningDate->type, $roomCleaningDate->cycle, $allocation, $nextArrival);

                }

            }

        }

        return $cleanOrder;
    }

}
