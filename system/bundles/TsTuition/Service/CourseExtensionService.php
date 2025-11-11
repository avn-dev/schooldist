<?php

namespace TsTuition\Service;

use DateTime;
use Ext_Thebing_School_Tuition_Allocation;
use Ext_TS_Inquiry;
use TsTuition\Entity\Course\Program\Service as ProgramService;

class CourseExtensionService
{
    public function extendAllocations(Ext_TS_Inquiry $booking): void
    {
        // Hole alle Kursbuchungen der Buchung
        $courseBookings = $booking->getCourses();

        // Gruppiere Kursbuchungen nach program_service_id
        $groupedCourseBookings = [];
        foreach ($courseBookings as $courseBooking) {
			
			// Das wird vorerst nicht klappen
			if($courseBooking->flexible_allocation) {
				continue;
			}
			
            $services = $courseBooking->getProgram()->getServices(ProgramService::TYPE_COURSE);
            foreach ($services as $service) {
                $groupedCourseBookings[$service->id][] = [
                    'courseBooking' => $courseBooking,
                    'service' => $service
                ];
            }

        }

        foreach ($groupedCourseBookings as $programServiceId => $courseBookingsForService) {

            // Sortiere die Kursbuchungen nach "from" (Startdatum)
            usort($courseBookingsForService, function (array $a, array $b): int {
                return (new DateTime($a['courseBooking']->from)) <=> (new DateTime($b['courseBooking']->from));
            });

            $previousAllocations = collect();
            $previousUntilWeek = null;

            foreach ($courseBookingsForService as $entry) {
                $courseBooking = $entry['courseBooking'];
                $service = $entry['service'];

                // Hole alle Klassenzuweisungen der aktuellen Kursbuchung
                $allocations = Ext_Thebing_School_Tuition_Allocation::query()
                    ->where('inquiry_course_id', $courseBooking->id)
                    ->where('program_service_id', $service->id)
                    ->get();

				// Nur Zuweisungen der neuesten Woche
				if(!empty($allocations)) {

					$maxWeekDate = $allocations->max(fn($allocation) => new DateTime($allocation->getBlock()->week));

					$allocations = $allocations->filter(function ($item)use($maxWeekDate) {
						return $item->getBlock()->week === $maxWeekDate->format('Y-m-d');
					});

				}

                // Wähle die Basiszuweisungen aus aktuellen oder vorherigen Zuweisungen
                $baseAllocations = $allocations->isEmpty() ? $previousAllocations : $allocations;

                $currentBookingStartWeek = (new DateTime($courseBooking->from))->modify('monday this week');

				// Es gibt Zuweisungen und es gibt keinen Vorkurs oder zwischen Vorkurs und aktuellem Kurs gibt es keine Pause
                if (
					!$allocations->isEmpty() || // Wenn diese Kursbuchung Zuweisungen hat, dann dürfen fehlende Wochen dieser Kursbuchung automatisch zugewiesen werden
					(
						!$previousAllocations->isEmpty() && 
						$previousUntilWeek->modify('next monday') == $currentBookingStartWeek
					)
				) {

                    // Wenn es für diese Kursbuchung Zuweisungen gibt
					if(!$allocations->isEmpty()) {
						// Finde das höchste Datum aus den Basiszuweisungen
						$maxWeekDate = $allocations->max(fn($allocation) => new DateTime($allocation->getBlock()->week));
						$currentWeek = (clone $maxWeekDate)->modify('+1 week');
					} else {
						$currentWeek = clone $currentBookingStartWeek;
					}

                    $untilDate = new DateTime($courseBooking->until);

                    // Generiere neue Zuweisungen für fehlende Wochen
                    $this->generateAllocations($currentWeek, $untilDate, $baseAllocations, $courseBooking->id, $service->id);
                }

                // Speichere aktuelle Zuweisungen für den nächsten Durchlauf
                if (!$allocations->isEmpty()) {
                    $previousAllocations = $allocations;
                }

                // Aktualisiere das Enddatum der aktuellen Woche
                $previousUntilWeek = new DateTime($courseBooking->until);
            }
        }
    }

    private function generateAllocations(DateTime $currentWeek, DateTime $untilDate, \Illuminate\Support\Collection $baseAllocations, int $courseBookingId, int $programServiceId): void
    {
		
		$groupByClass = [];
		foreach($baseAllocations as $baseAllocation) {
			
			$baseBlock = $baseAllocation->getBlock();
			$class = $baseBlock->getClass();
			
			$groupByClass[$class->id][implode('_', [$baseBlock->template_id, $baseBlock->days])] = $baseAllocation;
			
		}

		foreach($groupByClass as $classId=>$classBaseAllocations) {

			$class = \Ext_Thebing_Tuition_Class::getInstance($classId);
			
			$currentClassWeek = clone $currentWeek;

			while ($currentClassWeek < $untilDate) {

				$blocks = $class->getBlocks($currentClassWeek->format('Y-m-d'));
				
				/** @var \Ext_Thebing_School_Tuition_Block $block */
				foreach($blocks as $block) {

					$classBaseAllocation = $classBaseAllocations[implode('_', [$block->template_id, $block->days])];
					
					if(empty($classBaseAllocation)) {
						__pout('No class base allocation');
						continue;
					}

					$result = $block->addInquiryCourse($courseBookingId, $programServiceId, $classBaseAllocation->room_id);

					if($result['allocation'] instanceof Ext_Thebing_School_Tuition_Allocation) {
						$result['allocation']->automatic = 1;
						$result['allocation']->save();
					}

				}
				
				// Nächste Woche
				$currentClassWeek->modify('+1 week');
				
			}

		}
    }

}
