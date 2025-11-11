<?php

namespace TsActivities\Service;

use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Collection;
use Spatie\Period;
use TsActivities\Dto\ActivityBlockWeekCombination;
use TsActivities\Dto\BlockEvent;
use TsActivities\Entity\Activity\Block;
use TsActivities\Entity\Activity\BlockTraveller;
use TsActivities\Enums\AssignmentSource;
use TsActivities\Events\ActivityBooked;
use TsActivities\Events\ActivityCancelled;
use TsActivities\Exceptions\AlreadyAllocatedException;
use TsActivities\Exceptions\InvalidAssignmentException;
use TsActivities\Exceptions\OverbookingException;

class ActivityService {

	public function __construct(private AssignmentSource $source = AssignmentSource::SCHEDULER) {}

	/**
	 * Liefert alle Blöcke (BlockTraveller) die zu der Buchung verknüpft sind
	 *
	 * @param \Ext_TS_Inquiry $inquiry
	 * @return Collection<BlockTraveller>
	 */
	public function getBlocksOfInquiry(\Ext_TS_Inquiry $inquiry): Collection {

		$traveller = $inquiry->getTraveller();

		$journeyActivityIds = collect($inquiry->getActivities())
			->map(function($journeyActivity) {
				return $journeyActivity->getId();
			})
			->values()
			->toArray();

		$blocks = BlockTraveller::getRepository()
			->findBy(['traveller_id' => $traveller->getId(), 'journey_activity_id' => $journeyActivityIds]);

		return collect($blocks);
	}

	/**
	 * Liefert alle buchbaren Blöcke für eine Buchung
	 *
	 * @param \Ext_TS_Inquiry $inquiry
	 * @param \DateTimeInterface $from
	 * @param \DateTimeInterface $until
	 * @return Collection|ActivityBlockWeekCombination[]
	 * @throws \Exception
	 */
	public function searchAvailableBlocksForInquiry(\Ext_TS_Inquiry $inquiry, \DateTimeInterface $from, \DateTimeInterface $until): Collection {

		$available = collect([]);
		$fullLimit = 90;

		$current = new DateTime();
//		$from = new DateTime($inquiry->getServiceFrom());
//		$until = new DateTime($inquiry->getServiceUntil());

		if($current <= $until) {
			if($current > $from) {
				// Während der Buchung mit dem aktuellen Datum arbeiten damit alte Blöcke nicht angezeigt werden
				$from = $current;
			}

			$blocks = $this->searchGroupedBlocksForTimeframe($from, $until, $inquiry->getSchool());

			foreach($blocks as $block) {

				if ($this->source->isFrontend() && empty($block['frontend_release'])) {
					// Block soll nicht im Frontend angezeigt werden
					continue;
				}

				$blockEntity = Block::getInstance($block['id']);
				$activities = $blockEntity->getActivities();

				// Jede Aktivität in dem Block kann einzeln gebucht werden
				foreach($activities as $activity) {

					$combination = new ActivityBlockWeekCombination($blockEntity, $activity, Carbon::parse($block['week']));
					$combination->dates = $block['dates'];

					if (!$activity->isValidForInquiry($inquiry)) {
						continue;
					}

					try {
						$this->checkActivityBlockForInquiry($combination, $inquiry);
					} catch (AlreadyAllocatedException) {
						continue;
					}

					$freeSeats = $blockEntity->getFreeSeats($activity, new DateTime($block['week']));
					if ($freeSeats === 0) {
						$combination->status = [ActivityBlockWeekCombination::STATUS_FULL];
					} elseif (
						$freeSeats !== null &&
						(int)$activity->max_students > 0
					) {
						// Erklärung: Siehe #16797#note-8
						$combination->status = [];

						if ($blockEntity->frontend_release === Block::FRONTEND_BOOKABLE) {
							$combination->status[] = ActivityBlockWeekCombination::STATUS_BOOKABLE;
						} else {
							$combination->status[] = ActivityBlockWeekCombination::STATUS_VISIBLE;
						}

						$occupied = (int)$activity->max_students - $freeSeats;
						$threshold = floor((int)$activity->max_students * ($fullLimit / 100));
						if ($occupied >= $threshold) {
							$combination->status[] = ActivityBlockWeekCombination::STATUS_ALMOST_FULL;
						}
					}

//					$clone = $block;
//					$clone['name'] .= ' - '.$activity->getName($languageIso);
//					$clone['description'] = $activity->getDescription($languageIso);
//					$clone['activity_id'] = $activity->getId();
//					$clone['for_free'] = $activity->isFreeOfCharge();
//					$clone['free_seats'] = $blockEntity->getFreeSeats($activity, new DateTime($block['week']));
//					$clone['price'] = 0;

					if(!$activity->isFreeOfCharge()) {

						$price = (new Amount())->calculateForInquiry($inquiry, $activity, $combination->dates[0]->start, count($combination->dates));

						// Wenn die Preiskalkulation fehlschlägt die Aktivität nicht anzeigen (Amount::$aErrors)
						if(!$price instanceof \Ts\Model\Price) {
							continue;
						}

						$price2 = $price->getPrice();

						// Die Aktivität soll für die App immer angezeigt werden, da es hierfür released_for_app gibt
//						if(
//							$price2 === 0.0 &&
//							!$activity->showWithoutPrice()
//						) {
//							continue;
//						} else {
						if ($price2 > 0) {
							$currency = $inquiry->getCurrency(true);
							$currency->bThinspaceSign = true;
//							$clone['price'] = \Ext_Thebing_Format::Number($clone['price'], $currency, $inquiry->getSchool());
							$combination->price = \Ext_Thebing_Format::Number($price2, $currency, $inquiry->getSchool());
						}
					}

					if ($combination->price === null) {
						$combination->status[] = ActivityBlockWeekCombination::STATUS_PRICE_FOR_FREE;
					}

					$available->push($combination);
				}
			}
		}

		return $available;
	}

	/**
	 * Liefert alle Blöcke in einem Zeitraum mit dem Unterschied, dass ein Block pro Woche nur einmal aufgelistet wird
	 * und nicht pro eingestelltem Wochentag
	 *
	 * @param \DateTimeInterface $start
	 * @param \DateTimeInterface $end
	 * @return Collection<array>
	 */
	public function searchGroupedBlocksForTimeframe(\DateTimeInterface $start, \DateTimeInterface $end, \Ext_Thebing_School $school): Collection {

		$blocks = $this->searchBlocksForTimeframe($start, $end, $school);
		$grouped = collect([]);

		$blocks->each(function($block) use ($grouped) {

			// Nach Woche gruppieren damit eine Aktivität nur einmal pro Woche aufgelistet wird
			$key = implode('_', [$block['id'], $block['week']]);

			$place = $block['place'];
			$start = $block['start'];
			$end = $block['end'];

			if($grouped->has($key)) {
				$block = $grouped->get($key);
			}

			$block['dates'][] = new BlockEvent($start, $end, $place);

			unset($block['start']);
			unset($block['end']);

			$grouped->put($key, $block);
		});

		return $grouped;
	}

	/**
	 * Liefert alle Blöcke in einem Zeitraum - die eingestellten Wochentage pro Block werden mit aufgelistet
	 *
	 * @param \DateTimeInterface $start
	 * @param \DateTimeInterface $end
	 * @return Collection
	 */
	public function searchBlocksForTimeframe(\DateTimeInterface $start, \DateTimeInterface $end, \Ext_Thebing_School $school): Collection {

		$blocks = Block::getRepository()
			->getBlocksForTimeframe($start, $end, $school);

		$weekBlocks = collect([]);
		foreach($blocks as $block) {
			$weekBlocks = $weekBlocks->merge($this->buildWeekBlocks($block, $school));
		}

		// TODO getBlocksForTimeframe vergleicht Tage, aber hier werden Uhrzeiten verglichen?
		return $weekBlocks
			// die Repository-Methode liefert nicht das korrekte Ergebnis
			->filter(fn ($block) => $block['start'] >= $start && $block['start'] <= $end)
			->sortBy(fn ($block) => $block['start']);
	}

	/**
	 * Kopiert von PlanningController.
	 *
	 * Baut die Blöcke für die Folgewochen auf
	 *
	 * @param array $block
	 * @return array
	 */
	private function buildWeekBlocks(array $block, \Ext_Thebing_School $school): Collection {

		// Datumsobjekt erstellen
		$start = Carbon::parse($block['start_week'], $school->getTimezone());
		$activityIds = array_map('intval', explode(',', $block['activity_ids']));

		// Tag = Montag? Falls nicht, modify oder add
		if($block['day'] > 1) {
			$start->addDays($block['day'] - 1);
		}

		if(
			empty($block['repeat_weeks']) ||
			$block['repeat_weeks'] > $block['weeks']
		) {
			$block['repeat_weeks'] = 1;
		}

		$blocks = collect([]);
		for($i = 0; $i < $block['weeks']; $i = $i + $block['repeat_weeks']) {

			$startTime = explode(':', $block['start_time']);
			$endTime = explode(':', $block['end_time']);

			$date = clone $start;
			$date->modify('+'.$i.' weeks');

			$week = clone $date;
			if((int)$week->format('N') !== 1) {
				$week->modify('last monday');
			}
			$startTime = (clone $date)->setTime($startTime[0], $startTime[1], $startTime[2]);
			$endTime = (clone $date)->setTime($endTime[0], $endTime[1], $endTime[2]);

			$newBlock = $block;
			$newBlock['week'] = $week->format('Y-m-d');
			$newBlock['start_week'] = $date->format('Y-m-d');
			$newBlock['start'] = $startTime;
			$newBlock['end'] = $endTime;
			$newBlock['activity_ids'] = $activityIds;

			$activityDurationInMinutes = abs((new DateTime($startTime))->getTimestamp() - (new DateTime($endTime))->getTimestamp()) / 60;
			$newBlock['activity_duration'] = (int)$activityDurationInMinutes;

			$lastMaxStudentCount = null;
			foreach ($activityIds as $activityId) {
				$activity = \TsActivities\Entity\Activity::getInstance($activityId);
				$activityMaxStudents = $activity->max_students;
				if (
					!empty($activityMaxStudents) &&
					(
						$activityMaxStudents <= $lastMaxStudentCount ||
						$lastMaxStudentCount === null
					)
				) {
					$lastMaxStudentCount = $activityMaxStudents;
				}
			}

			if ($lastMaxStudentCount == null) {
				$lastMaxStudentCount = 0;
			}

			$newBlock['students'] .= $block['student_count'].' / ' . $lastMaxStudentCount;

			$blocks->push($newBlock);
		}

		return $blocks;
	}

	/**
	 * @param \Ext_TS_Inquiry $inquiry
	 * @param ActivityBlockWeekCombination $activityCombination
	 * @param \Ext_TS_Inquiry_Journey_Activity|null $journeyActivity
	 * @return BlockTraveller
	 * @throws AlreadyAllocatedException
	 * @throws InvalidAssignmentException
	 * @throws OverbookingException
	 */
	public function assignBlockToInquiry(\Ext_TS_Inquiry $inquiry, ActivityBlockWeekCombination $activityCombination, \Ext_TS_Inquiry_Journey_Activity $journeyActivity = null, array $ignoreErrors = []): BlockTraveller {

		$block = $activityCombination->block;
		$activity = $activityCombination->activity;
		$date = Carbon::instance(\Ext_Thebing_Util::getPreviousCourseStartDay($activityCombination->week, 1));

		if (!in_array($activity->id, $block->activities)) {
			throw new \LogicException(sprintf('Activity %d does not belong to block %d', $activity->id, $block->id));
		}

		$this->checkActivityBlockForInquiry($activityCombination, $inquiry, $ignoreErrors);

		if (!$activity->isValidForInquiry($inquiry)) {
			throw new InvalidAssignmentException(sprintf('Activity "%s" can not be assigned to inquiry "%s"', $activity->getName(), $inquiry->getNumber()));
		}

		if (!$block->hasFreeSeats($activity, $date)) {
			throw new OverbookingException(sprintf('No free seats for activity "%s" in block "%s"', $activity->getName(), $block->getName()));
		}

		if (
			$journeyActivity &&
			!$journeyActivity->isActive()
		) {
			// Wenn die Aktivitätsbuchung gelöscht wird, der Schüler aber noch in der Zuweisung angezeigt wird (analog zur Klassenplanung)
			throw new \LogicException(sprintf('Journey activity %d does not exist anymore', $journeyActivity->id));
		}

		if ($journeyActivity === null) {

			$comment = sprintf('Automatisch über %s gebucht.', \System::wd()->getInterface() === 'backend' ? 'Aktivitätsplanung' : 'App');

			/** @var \Ext_TS_Inquiry_Journey_Activity $journeyActivity */
			$journeyActivity = $inquiry->getJourney()->getJoinedObjectChild('activities');
			$journeyActivity->activity_id = $activity->getId();
			$journeyActivity->from = $date->toDateString();
			$journeyActivity->until = $date->clone()->addDays(6)->toDateString(); // Logik von calculateUntil im SR
			$journeyActivity->comment = \L10N::t($comment, \TsActivities\Gui2\Data\ActivityData::TRANSLATION_PATH);
			$journeyActivity->weeks = 1;
			if ($activity->isCalculatedPerBlock()) {
				$journeyActivity->blocks = count($activityCombination->dates);
			}
			$journeyActivity->save();

			// Buchung rosa markieren
			if ($inquiry->hasProformaOrInvoice()) {
				\Ext_Thebing_Inquiry_Document_Version::setChange($inquiry->getId(), $journeyActivity->getId(), 'activity', 'new');
			}

		}

		/** @var BlockTraveller $blockTraveller */
		$blockTraveller = BlockTraveller::query()
			->where('block_id', $block->getId())
			->where('traveller_id', $inquiry->getCustomer()->getId())
			->where('journey_activity_id', $journeyActivity->getId())
			->where('week', $date->toDateString())
			->withTrashed()
			->firstOrNew();

		$blockTraveller->block_id = $block->getId();
		$blockTraveller->traveller_id = $inquiry->getCustomer()->getId();
		$blockTraveller->journey_activity_id = $journeyActivity->getId();
		$blockTraveller->week = $date->format('Y-m-d');
		$blockTraveller->active = 1;
		$blockTraveller->save();

		\Ext_Gui2_Index_Stack::add('ts_inquiry', $inquiry->getId(), 1);

		ActivityBooked::dispatch($this->source, $blockTraveller);

		return $blockTraveller;

	}

	public function removeBlockFromInquiry(\Ext_TS_Inquiry $inquiry, \TsActivities\Dto\ActivityBlockWeekCombination $activityCombination, \Ext_TS_Inquiry_Journey_Activity $journeyActivity): BlockTraveller {

		$block = $activityCombination->block;
		$date = Carbon::instance(\Ext_Thebing_Util::getPreviousCourseStartDay($activityCombination->week, 1));

		/** @var BlockTraveller $blockTraveller */
		$blockTraveller = BlockTraveller::query()
			->where('block_id', $block->getId())
			->where('traveller_id', $inquiry->getCustomer()->getId())
			->where('journey_activity_id', $journeyActivity->getId())
			->where('week', $date->toDateString())
			->first();

		if (!$blockTraveller) {
			throw new \RuntimeException(sprintf(
				'No block traveller found for inquiry [inquiry_id: %d, block_id: %d, week: %s]',
				$inquiry->getId(),
				$block->getId(),
				$date->toDateString(),
			));
		}

		$blockTraveller->delete();

		\Ext_Gui2_Index_Stack::add('ts_inquiry', $inquiry->getId(), 1);

		ActivityCancelled::dispatch($this->source, $blockTraveller);

		return $blockTraveller;
	}

	/**
	 * Auf vorhandene Zuweisungen prüfen
	 *
	 * @param ActivityBlockWeekCombination $combination
	 * @param \Ext_TS_Inquiry $inquiry
	 * @param array $ignoreErrors
	 * @return void
	 * @throws AlreadyAllocatedException
	 */
	private function checkActivityBlockForInquiry(ActivityBlockWeekCombination $combination, \Ext_TS_Inquiry $inquiry, array $ignoreErrors = []): void {

		$school = $inquiry->getSchool();
		$periods = $combination->createPeriodCollection();

		// Gleicher oder anderer Aktivität bereits zugewiesen
		$overlapping = BlockTraveller::getRepository()->checkOverlappingAllocations($inquiry, $combination->week, $periods);
		if ($overlapping->isNotEmpty()) {
			$block = Block::getInstance($overlapping->first()['block_id']);
			throw new AlreadyAllocatedException($block->id == $combination->block->id ? 'same' : 'other', $block->getFirstActivity()->getName());
		}

		if (
			!$this->canHaveParallelTuitionAllocations($school) &&
			!in_array('parallel_tuition_allocation', $ignoreErrors)
		) {
			// Zuweisung in Klassenplanung
			$search = new \Ext_Thebing_School_Tuition_Allocation_Result();
			$search->setInquiry($inquiry);
			$search->setWeek($combination->week);
			$allocations = $search->fetch();

			foreach ($allocations as $allocation) {
				$start = Carbon::parse($allocation['block_day_date'], $school->getTimezone())->setTimeFromTimeString($allocation['block_from']);
				$end = $start->clone()->setTimeFromTimeString($allocation['block_until']);
				$period = Period\Period::make($start, $end, Period\Precision::MINUTE());

				foreach ($periods as $period2) {
					if ($period2->overlapsWith($period)) {
						throw (new AlreadyAllocatedException('class', $allocation['class_name'], 'parallel_tuition_allocation'))->skipable();
					}
				}
			}
		}

	}

	/**
	 * Einstellungen prüfen, ob eine Aktivität parallel zu einer Tuition-Allocation zugewiesen werden kann. Für die App
	 * gibt es eine Einstellung, in der Software wird eine Warning ausgegeben
	 *
	 * @param \Ext_Thebing_School $school
	 * @return bool
	 */
	private function canHaveParallelTuitionAllocations(\Ext_Thebing_School $school): bool {

		if (
			$this->source->isFrontend() &&
			(int)$school->activity_parallel_frontend === 1
		) {
			return true;
		}

		return false;
	}
}
