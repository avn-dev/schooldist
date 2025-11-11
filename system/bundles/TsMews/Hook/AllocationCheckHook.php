<?php

namespace TsMews\Hook;

use Core\DTO\DateRange;
use TsMews\Api;
use TsMews\Handler\ExternalApp;

class AllocationCheckHook extends AbstractMewsHook {

    public function run(\Ext_Thebing_Allocation $allocation, array &$errors) {

        if (!$this->hasApp()) {
            return;
        }

        // Auf Überschneidungen in Mews prüfen
        $this->check($allocation, $errors);

    }

    private function check(\Ext_Thebing_Allocation $allocation, array &$errors): void {

        $room = $allocation->getRoom();

        if ($room) {

            $provider = $room->getProvider();

            if ($provider && $this->checkProviderSync($provider)) {

                $from = $allocation->getFrom();
                $until = $allocation->getUntil();

                $dateRange = new DateRange(clone $from, clone $until);
                $existing = Api::default()->searchReservations($dateRange);

                $categoryId = $provider->default_category_id;
                $journeyAccommodation = $allocation->getAccommodation();

                if (in_array($journeyAccommodation->accommodation_id, $provider->accommodation_categories)) {
                    $categoryId = $journeyAccommodation->accommodation_id;
                }

                $category = \Ext_Thebing_Accommodation_Category::getInstance($categoryId);

                [$h, $i, $s] = explode(':', $category->arrival_time);
                $from->setTime($h, $i, $s);
                [$h, $i, $s] = explode(':', $category->departure_time);
                $until->setTime($h, $i, $s);

                $reservations = collect($existing->get('Reservations'));

                $roomId = ExternalApp::getRoomId($allocation->getRoom());

                $blocking = $reservations->filter(function($reservation) use ($roomId, $from, $until) {

                    if ($roomId === $reservation['AssignedSpaceId']) {
                        $reservationFrom = \DateTime::createFromFormat(DATE_ISO8601, $reservation['StartUtc'], new \DateTimeZone('UTC'));
                        $reservationUntil = \DateTime::createFromFormat(DATE_ISO8601, $reservation['EndUtc'], new \DateTimeZone('UTC'));

                        $now = new \DateTime();
                        $reservationFrom->setTimezone($now->getTimezone());
                        $reservationUntil->setTimezone($now->getTimezone());

                        if(\Core\Helper\DateTime::checkDateRangeOverlap($from, $until, $reservationFrom, $reservationUntil)) {
                            return true;
                        }
                    }

                    return false;
                });

                if ($blocking->isNotEmpty()) {
                    $errors[] = \L10N::t('Es gibt Überschneidungen mit einer Reservierung in Ihrem Mews-System');
                }

            }
        }

    }

}
