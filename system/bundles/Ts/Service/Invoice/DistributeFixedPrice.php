<?php

namespace Ts\Service\Invoice;

class DistributeFixedPrice {
	
	protected $inquiry;

	public function __construct(\Ext_TS_Inquiry $inquiry) {
		$this->inquiry = $inquiry;
	}
	
	/**
	 * Sieht kompliziert und langsam aus. Keine Ahnung, ob es einen besseren Weg gibt.
	 * Sollte aber eigentlich eher in der Preisberechnung sein.
	 * 
	 * @param array $items
	 */
	public function run(array &$items) {
		
		// Items von durch Ferien gesplittete Festpreis-Kurse ermitteln
		$journey = $this->inquiry->getJourney();
		$holidays = $this->inquiry->getJoinedObjectChilds('holidays');
		
		$courseSplittings = [];
		
		foreach($holidays as $holiday) {
			$splittings = $holiday->getSplittings();
			foreach($splittings as $splitting) {

				$found = false;
				foreach($courseSplittings as &$courseSplitting) {
					if(
						in_array($splitting->journey_course_id, $courseSplitting) || 
						in_array($splitting->journey_split_course_id, $courseSplitting)
					) {
						$courseSplitting[$splitting->journey_course_id] = $splitting->journey_course_id;
						$courseSplitting[$splitting->journey_split_course_id] = $splitting->journey_split_course_id;
						$found = true;
						break;
					}
				}

				if(!$found) {
					$courseSplittings[] = [
						$splitting->journey_course_id => $splitting->journey_course_id,
						$splitting->journey_split_course_id => $splitting->journey_split_course_id
					];
				}
					
			}
		}
		
		foreach($courseSplittings as $courseSplitting) {
			
			$courseId = null;
			foreach($courseSplitting as $courseSplittingId) {
				
				$journeyCourse = \Ext_TS_Inquiry_Journey_Course::getInstance($courseSplittingId);

				$course = $journeyCourse->getCourse();

				// Hier sind nur Festpreiskurse relevant
				if(!$course->hasFixedPrice()) {
					continue 2;
				}
				
				// Prüfen, ob alle noch denselben Kurse haben
				if(
					$courseId !== null &&
					$courseId != $course->id
				) {
					continue 2;
				}
				
				$courseId = $course->id;
				
			}

			$amountFields = [
				'amount',
				'amount_net',
				'amount_provision',
				'amount_discount'
			];
			
			$courseSplittingItems = [];
			$amounts = [];
			$totalDays = 0;
			// Items von allen dreien ermitteln
			foreach($items as &$item) {
				if(
					$item['type'] == 'course' &&
					in_array($item['type_id'], $courseSplitting)
				) {
					foreach($amountFields as $amountField) {
						$amounts[$amountField] += $item[$amountField];
					}
					
					$from = new \Carbon\Carbon($item['from']);
					$until = new \Carbon\Carbon($item['until']);
					$item['additional_info']['days'] = $from->diffInDays($until);
					$totalDays += $item['additional_info']['days'];
					$courseSplittingItems[] = &$item;
				}
			}

			$checkAmounts = $amounts;
			foreach($courseSplittingItems as &$item) {
				foreach($amountFields as $amountField) {
					$item[$amountField] = round($amounts[$amountField] / $totalDays * $item['additional_info']['days']);
					$checkAmounts[$amountField] -= $item[$amountField];
				}
			}
			
			// Eventuelle Restbeträge verteilen
			foreach($amountFields as $amountField) {
				if(!empty($checkAmounts[$amountField])) {
					$item[$amountField] += $checkAmounts[$amountField];
				}
			}
			
		}
		
	}
	
}
