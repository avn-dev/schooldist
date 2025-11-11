<?php

namespace TsIvvy\Service;

use Core\DTO\DateRange;
use TsIvvy\Api;
use TsIvvy\Exceptions\FailedException;
use TsIvvy\Factory\BlockoutSpaceFactory;
use TsIvvy\Handler\ExternalApp;
use TsIvvy\Api\Model\Booking;
use TsIvvy\Api\Model\Session;
use Core\Facade\Cache;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Hier passiert die komplette Kommunikation mit Ivvy. Dabei wird für jeden Block einer Klasse ein Blockout-Space in Ivvy
 * eingetragen. Andersrum (Ivvy zu Fidelo) werden Sessions einer Ivvy-Buchung in Fidelo als einzelner Tuition-Block (ohne Klasse)
 * oder Abwesenheit einer Unterkunft gesetzt.
 *
 * @package TsIvvy
 */
class Synchronization {

	private static $entityCache = [];

	/**
	 * Synchronisiert alle Buchungen und Sessions aus Ivvy heraus in unsere Software
	 *
	 * Richtung: Ivvy -> Fidelo
	 *
	 * @param \DateTime|null $modifiedAfter
	 * @param \DateTime|null $modifiedBefore
	 * @throws \Exception
	 */
	public static function syncFromIvvy(\DateTime $modifiedAfter = null, \DateTime $modifiedBefore = null) {

		$bookingsList = Api::default()->getBookingList($modifiedAfter, $modifiedBefore);

		$synced = $failed = 0;

		foreach ($bookingsList as $booking) {

			// Fidelo Blöcke ignorieren
			// TODO unnötig nach der Blockout-Umstellung (es sollten keine Fidelo-Buchungen mehr existieren)
			if (Str::startsWith($booking->getCode(), Api::ENTITY_CODE_PREFIX)) {
				continue;
			}

			try {
				if (self::syncIvvyBookingToFidelo($booking)) {
					++$synced;
				}
			} catch (\Throwable $e) {
				Api::getLogger()->error('Sync of booking failed', ['booking' => $booking->getName().' ('.$booking->getId().')', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
				++$failed;
			}

		}

		return [$synced, $failed];
	}

	public static function syncIvvyBookingToFidelo(Booking $booking) {

		// Alle Entitäten holen die zu dieser Buchung in Fidelo angelegt wurden
		$fideloEntities = self::getEntitiesByIvvyBooking($booking->getId());

		$synced = false;

		// https://developer.ivvy.com/venues/getoraddbookingdata/add-or-update-booking#booking-status
		if (in_array($booking->getStatus(), [Booking::STATUS_PROSPECTIVE, Booking::STATUS_TENTATIVE, Booking::STATUS_CONFIRMED])) {

			$sessions = $booking->getSessions();

			if ($sessions->isNotEmpty()) {

				foreach ($sessions as $session) {

					// Den Raum in Fidelo anhand der Space-Id der Session suchen
					$fideloRoom = ExternalApp::getFideloRoomForIvvyRoom($session->getSpaceId());

					if ($fideloRoom) {

						if ($fideloRoom instanceof \Ext_Thebing_Tuition_Classroom) {
							// Als Tuition-Block anlegen
							$entities = self::syncAsFideloTuitionBlock($booking, $session, $fideloRoom, $fideloEntities);
						} else if ($fideloRoom instanceof \Ext_Thebing_Accommodation_Room) {
							// Als Unterkunftsabwesenheit anlegen
							$entities = self::syncAsFideloAccommodationAbsence($booking, $session, $fideloRoom, $fideloEntities);
						} else {
							continue;
						}

						foreach ($entities as $entity) {
							// die benutzte Session aus dem Array schmeißen damit diese unten nicht gelöscht wird
							$fideloEntities->forget($entity->getTableName() . '_' . $entity->getId());
						}

						$synced = true;
					}
				}
			}
		}

		// Nicht verwendete Entitäten in Fidelo löschen
		$fideloEntities->each(function(\WDBasic $entity) {
			$entity->delete();
		});

		return $synced;
	}

	/**
	 * @param Booking $booking
	 * @param Session $session
	 * @param \Ext_Thebing_Tuition_Classroom $room
	 * @param Collection $fideloEntities
	 * @return \Ext_Thebing_School_Tuition_Block[]
	 * @throws \Exception
	 */
	private static function syncAsFideloTuitionBlock(Booking $booking, Session $session, \Ext_Thebing_Tuition_Classroom $room, Collection $fideloEntities): array {

		$originalDateRange = $session->getDateRange();
		$fideloDateRanges = [];

		// Zeitraum auf eigene Tage ausplitten um pro Tag einen Fidelo-Block anzulegen
		do {

			$newFrom = clone $originalDateRange->from;

			if ($originalDateRange->from->format('Y-m-d') !== $originalDateRange->until->format('Y-m-d')) {
				$newUntil = (clone $originalDateRange->from)->setTime(23, 59, 59);
			} else {
				$newUntil = clone $originalDateRange->until;
			}

			$fideloDateRanges[] = new DateRange($newFrom, $newUntil);

			$originalDateRange->from = $originalDateRange->from->modify('+1day')->setTime(0, 0, 0);

		} while ($originalDateRange->from->format('Y-m-d') <= $originalDateRange->until->format('Y-m-d'));

		// Prüfen, ob es bereits Blöcke mit der Ivvy-Session-Id gibt
		$existingTuitionBlocks = $fideloEntities->filter(fn(\WDBasic $entity) => $entity instanceof \Ext_Thebing_School_Tuition_Block)
			->filter(fn (\Ext_Thebing_School_Tuition_Block $block) => $block->getMeta('ivvy_session_id') == $session->getId())
			->values();

		$fideloTuitionBlocks = [];
		foreach ($fideloDateRanges as $index => $dateRange) {

			if (!isset($existingTuitionBlocks[$index])) {
				$fideloTuitionBlock = new \Ext_Thebing_School_Tuition_Block();
				$fideloTuitionBlock->setMeta('ivvy_booking_id', $booking->getId());
				$fideloTuitionBlock->setMeta('ivvy_session_id', $session->getId());
			} else {
				$fideloTuitionBlock = $existingTuitionBlocks[$index];
			}

			$template = $fideloTuitionBlock->getTemplate();
			$template->school_id = $room->idSchool;
			$template->name = $dateRange->from->format('H:i') . ' - ' . $dateRange->until->format('H:i');
			$template->from = $dateRange->from->format('H:i:s');
			$template->until = $dateRange->until->format('H:i:s');
			$template->custom = 1;
			$template->lessons = 1;
			$template->save();

			$description = [
				'Ivvy',
				'<strong>' .$booking->getName(). ' (' . $session->getName() . ')</strong>',
				$booking->getBooker()->getFullName(),
				$template->name
			];

			$weekDate = (clone $dateRange->from)->modify('monday this week');

			$fideloTuitionBlock->active = 1;
			$fideloTuitionBlock->school_id = $room->idSchool;
			$fideloTuitionBlock->teacher_id = 0;
			$fideloTuitionBlock->level_id = 0;
			$fideloTuitionBlock->class_id = 0;
			$fideloTuitionBlock->week = $weekDate->format('Y-m-d');
			$fideloTuitionBlock->days = [$dateRange->from->format('N')];
			$fideloTuitionBlock->description = '<p style="text-align: center;">' . implode('<br/>', $description) . '</p>';
			$fideloTuitionBlock->readonly = 1;
			$fideloTuitionBlock->setRoomIds([$room->getId()]);
			$fideloTuitionBlock->save();

			Api::getLogger()->info('Synced tuition block', [
				'fidelo_id' => $fideloTuitionBlock->id, 'booking_id' => $booking->getId(), 'session_id' => $session->getId(), 'booking' => $booking->getName(), 'name' => $session->getName(), 'room' => $room->name, 'from' => $dateRange->from->format('Y-m-d'), 'until' => $dateRange->until->format('Y-m-d')
			]);

			$fideloTuitionBlocks[] = $fideloTuitionBlock;
		}

		return $fideloTuitionBlocks;
	}

	/**
	 * @param Booking $booking
	 * @param Session $session
	 * @param \Ext_Thebing_Accommodation_Room $room
	 * @param Collection $fideloEntities
	 * @return \Ext_Thebing_Absence[]
	 * @throws \Exception
	 */
	private static function syncAsFideloAccommodationAbsence(Booking $booking, Session $session, \Ext_Thebing_Accommodation_Room $room, Collection $fideloEntities): array {

		$dateRange = $session->getDateRange();

		// Prüfen ob es bereits eine Abwesenheit mit der Ivvy-Session-Id gibt
		$absence = $fideloEntities->filter(function(\WDBasic $entity) {
				return $entity instanceof \Ext_Thebing_Absence;
			})
			->first(function (\Ext_Thebing_Absence $absence) use ($session) {
				return ($absence->getMeta('ivvy_session_id') == $session->getId());
			});

		if ($absence === null) {
			$absence = new \Ext_Thebing_Absence();
			$absence->active = 1;
			$absence->item = 'accommodation';
			$absence->category_id = ExternalApp::getAbsenceCategory();
			$absence->setMeta('ivvy_booking_id', $booking->getId());
			$absence->setMeta('ivvy_session_id', $session->getId());
		}

		$booker = $booking->getBooker();

		// Tage für Abwesenheit berechnen

		$cloneFrom = (clone $dateRange->from)->setTime(12,0,0);
		$cloneUntil = (clone $dateRange->until)->setTime(12,0,0);

		$days = $cloneUntil->diff($cloneFrom)->days;
		$days++;

		$absence->item_id = $room->getId();
		$absence->days = $days;
		$absence->from = $dateRange->from->format('Y-m-d');
		$absence->until = $dateRange->until->format('Y-m-d');
		$absence->comment =
			#"ID: ".$session->getId()."<br/>".
			"Name: ".$booker->getName()."<br/>".
			"Start: ".$dateRange->from->format('d.m.Y H:i').'Uhr'."<br/>".
			"Ende: ".$dateRange->until->format('d.m.Y H:i').'Uhr'
		;

		$absence->save();

		Api::getLogger()->info('Synced accommodation absence', [
			'fidelo_id' => $absence->id, 'booking_id' => $booking->getId(), 'session_id' => $session->getId(), 'booking' => $booking->getName(), 'name' => $session->getName(), 'room' => $room->name, 'from' => $dateRange->from->format('Y-m-d'), 'until' => $dateRange->until->format('Y-m-d')
		]);

		return [$absence];
	}

	/**
	 * Schreibt eine Entität zu Synchronisierung ins PP
	 * Richtung: Ivvy -> Fidelo
	 *
	 * @param \WDBasic $entity
	 * @param int $prio
	 * @throws \Exception
	 */
	public static function writeEntityToStack(\WDBasic $entity, int $prio = 2) {

		$data = ['entity' => get_class($entity), 'entity_id' => $entity->getId()];

		$access = \Access::getInstance();
		if ($access) {
			$data['user_id'] = $access->id;
		}

		\Core\Entity\ParallelProcessing\Stack::getRepository()->writeToStack('ts-ivvy/sync-entity', $data, $prio);

	}

	/**
	 * Synchronisiert eine Entität mit Ivvy
	 * Richtung: Ivvy -> Fidelo
	 *
	 * @param \WDBasic $entity
	 * @return bool
	 * @throws \Exception
	 */
	public static function syncEntityToIvvy(\WDBasic $entity) {

		$cacheKey = 'ivvy_'.get_class($entity).'_'.$entity->getId();

		// Cache setzen falls save() mehrfach aufgerufen wird
		if(Cache::exists($cacheKey)) {
			return false;
		}

		Cache::put($cacheKey, 60*5, 1);

		try {

			if ($entity instanceof \Ext_Thebing_School_Tuition_Block) {
				$synced = self::syncClassBlockToIvvy($entity);
			} else if ($entity instanceof \Ext_Thebing_Accommodation_Allocation) {
				$synced = self::syncAccommodationAllocationToIvvy($entity);
			} else {
				throw new \RuntimeException(sprintf('No ivvy action defined for entity "%s"!', get_class($entity)));
			}

		} catch(\Throwable $e) {

			Cache::forget($cacheKey);

			throw $e;
		}

		Cache::forget($cacheKey);

		return $synced;
	}

	/**
	 * Synchronisiert einen Tuition-Block mit Ivvy. Dabei wird pro Wochentag und pro Raum ein Blockout in Ivvy generiert (sofern
	 * die Räume stimmen)
	 *
	 * Richtung: Fidelo -> Ivvy
	 *
	 * @param \Ext_Thebing_School_Tuition_Block $block
	 * @return bool
	 * @throws \Exception
	 */
	private static function syncClassBlockToIvvy(\Ext_Thebing_School_Tuition_Block $block) {

		if($block->class_id <= 0) {
			return false;
		}

		if ($block->isActive()) {

			$synced = false;

			// Alle Blockout-IDs des Block holen, pro Tag und pro Raum existiert ein Blockout in Ivvy
			$existingBlockoutIds = self::getGroupedIvvyBlockoutIds($block);

			$days = $block->days;
			$rooms = $block->getRooms();

			foreach ($days as $day) {
				foreach ($rooms as $room) {

					// Prüfen ob dem Fidelo-Raum ein Ivvy-Raum zugewiesen ist
					if (!self::checkRoomForIvvy($room)) {
						continue;
					}

					$blockoutSpace = BlockoutSpaceFactory::fromTuitionBlock($block, $day, $room->getId());

					try {
						// Blockout hinzufügen oder updaten
						$response = Api::default()->sendBlockoutSpace($blockoutSpace);
					} catch (FailedException $e) {
						if (str_contains($e->getMessage(), 'The space blockout does not exist')) {
							$block->unsetMeta(Api::buildBlockoutMetaKey($day, $room->getId()));
							$block->unsetMeta(Api::buildBlockoutVenueMetaKey($day, $room->getId()));
							$blockoutSpace->unsetId();

							$response = Api::default()->sendBlockoutSpace($blockoutSpace);
						} else {
							throw $e;
						}
					}

					$block->setMeta(Api::buildBlockoutMetaKey($day, $room->getId()), $response->get('id'));
					$block->setMeta(Api::buildBlockoutVenueMetaKey($day, $room->getId()), $blockoutSpace->getVenueId());

					// Nicht über $block->save() gehen da sonst die Hooks erneut ausgeführt werden

					$metadata = $block->getJoinedObjectChilds('attributes', true);

					foreach ($metadata as $metaObject) {
						$metaObject->save();
					}

					unset($existingBlockoutIds[$day][$room->getId()]);
					$synced = true;
				}
			}

			// Falls sich Räume oder Wochentage geändert haben müssen die noch existierenden Ivvy-Blockouts gelöscht werden
			foreach ($existingBlockoutIds as $day => $roomIds) {
				foreach ($roomIds as $roomId => $sessionId) {
					self::deleteSpecificClassBlockInIvvy($block, $day, $roomId);
					$synced = true;
				}
			}

			return $synced;
		}

		// Darf nicht mehr in Ivvy existieren, schauen ob gelöscht werden muss
		return self::deleteCompleteClassBlockInIvvy($block);
	}

	/**
	 * Löscht den kompletten Tuition-Block in Ivvy
	 *
	 * Richtung: Fidelo -> Ivvy
	 *
	 * @param \Ext_Thebing_School_Tuition_Block $block
	 */
	private static function deleteCompleteClassBlockInIvvy(\Ext_Thebing_School_Tuition_Block $block) {

		$existingBlockoutIds = self::getGroupedIvvyBlockoutIds($block);

		$synced = [];

		foreach ($existingBlockoutIds as $day => $roomIds) {
			foreach ($roomIds as $roomId => $blockoutId) {
				$synced[] = self::deleteSpecificClassBlockInIvvy($block, $day, $roomId);
			}
		}

		return in_array(true, $synced);
	}

	/**
	 * Löscht einen spezifischen Tuition-Block in Ivvy. Pro Wochentag und pro Raum existiert ein Blockout in Ivvy
	 *
	 * Richtung: Fidelo -> Ivvy
	 *
	 * @param \Ext_Thebing_School_Tuition_Block $block
	 * @param int $day
	 * @param int $roomId
	 * @return bool
	 * @throws \Exception
	 */
	public static function deleteSpecificClassBlockInIvvy(\Ext_Thebing_School_Tuition_Block $block, int $day, int $roomId) {

		$ivvyId = $block->getMeta(Api::buildBlockoutMetaKey($day, $roomId));

		if($ivvyId !== null) {

			$blockoutSpace = BlockoutSpaceFactory::fromTuitionBlock($block, $day, $roomId);

			try {
				Api::default()->removeBlockoutSpace($blockoutSpace);
			} catch (FailedException $e) {
				if (!str_contains($e->getMessage(), 'The space blockout could not be found')) {
					throw $e;
				}
			}

			$block->unsetMeta(Api::buildBlockoutMetaKey($day, $roomId));
			$block->unsetMeta(Api::buildBlockoutVenueMetaKey($day, $roomId));

			return true;
		}

		return false;
	}

	/**
	 * Synchronisiert ein Unterkunftszuweisung mit Ivvy, dabei wird die Zuweisung als Blockout angelegt
	 *
	 * @param \Ext_Thebing_Accommodation_Allocation $allocation
	 * @return bool
	 * @throws \Exception
	 */
	private static function syncAccommodationAllocationToIvvy(\Ext_Thebing_Accommodation_Allocation $allocation) {

		if(
			$allocation->isActive() &&
			$allocation->hasRoom() &&
			$allocation->status == 0 &&
			$allocation->active_storno == 1 &&
			self::checkRoomForIvvy($allocation->getRoom())
		) {
			$blockoutSpace = BlockoutSpaceFactory::fromAccommodationAllocation($allocation);

			try {
				// Blockout hinzufügen oder updaten
				$response = Api::default()->sendBlockoutSpace($blockoutSpace);
			} catch (FailedException $e) {
				if (str_contains($e->getMessage(), 'The space blockout does not exist')) {
					$allocation->unsetMeta('ivvy_blockout_id');
					$allocation->unsetMeta('ivvy_blockout_venue_id');
					$blockoutSpace->unsetId();

					$response = Api::default()->sendBlockoutSpace($blockoutSpace);
				} else {
					throw $e;
				}
			}

			$allocation->setMeta('ivvy_blockout_id', $response->get('id'));
			$allocation->setMeta('ivvy_blockout_venue_id', $blockoutSpace->getVenueId());

			// Nicht über $allocation->save() gehen da sonst die Hooks erneut ausgeführt werden

			$metadata = $allocation->getJoinedObjectChilds('attributes', true);

			foreach ($metadata as $metaObject) {
				$metaObject->save();
			}

			return true;
		}

		// Darf nicht mehr in Ivvy existieren, schauen ob gelöscht werden muss
		return self::deleteAccommodationAllocationInIvvy($allocation);
	}

	/**
	 * @param \Ext_Thebing_Accommodation_Allocation $allocation
	 * @return bool
	 */
	public static function deleteAccommodationAllocationInIvvy(\Ext_Thebing_Accommodation_Allocation $allocation) {

		$ivvyId = $allocation->getMeta('ivvy_blockout_id');

		if($ivvyId !== null) {

			$blockoutSpace = BlockoutSpaceFactory::fromAccommodationAllocation($allocation);

			try {
				Api::default()->removeBlockoutSpace($blockoutSpace);
			} catch (FailedException $e) {
				if (!str_contains($e->getMessage(), 'The space blockout could not be found')) {
					throw $e;
				}
			}

			$allocation->unsetMeta('ivvy_blockout_id');
			$allocation->unsetMeta('ivvy_blockout_venue_id');

			return true;
		}

		return false;

	}

	/**
	 * Prüft ob der übergebene Raum mit Ivvy verknüpft ist
	 *
	 * @param \Ext_Thebing_Accommodation_Room|\Ext_Thebing_Tuition_Classroom $room
	 * @return bool
	 */
	private static function checkRoomForIvvy(\WDBasic $room): bool {

		$ivvyRoomData = ExternalApp::getIvvyRoomIdsForFideloRoom($room);

		if(!is_null($ivvyRoomData)) {
			// Es gibt eine Zuweisung zu einem Ivvy-Raum
			return true;
		}

		return false;
	}

	/**
	 * Gruppiert die bestehenden Blockout-IDs eines Tuition-Blocks nach Wochentag und Raum. In Ivvy existiert pro Wochentag
	 * und Raum ein Blockout in Ivvy
	 *
	 * @param \Ext_Thebing_School_Tuition_Block $block
	 * @return array
	 */
	private static function getGroupedIvvyBlockoutIds(\Ext_Thebing_School_Tuition_Block $block): array {

		$metaData = $block->getAllMetaData();

		$blockoutIds = [];
		foreach($metaData as $metaKey => $metaValue) {

			if (strpos($metaKey, 'ivvy_blockout_id_') === false) {
				continue;
			}

			// day und room_id aus dem Key herausfiltern
			$name = str_replace('ivvy_blockout_id_', '', $metaKey);
			[$day, $roomId] = explode('_', $name);
			$blockoutIds[$day][$roomId] = $metaValue;
		}

		return $blockoutIds;
	}

	/**
	 * Liefert alle Fidelo Entitäten die zu einer Ivvy-Buchung angelegt wurden
	 *
	 * @param $ivvyBookingId
	 * @return Collection
	 * @throws \Exception
	 */
	private static function getEntitiesByIvvyBooking($ivvyBookingId): Collection {

		$sql = "
			SELECT
				*
			FROM 
				`wdbasic_attributes`
			WHERE
				`key` = 'ivvy_booking_id' AND
			    `value` = :ivvy_booking_id 
		";

		$attributes = (array)\DB::getPreparedQueryData($sql, ['ivvy_booking_id' => $ivvyBookingId]);

		$mapping = [
			'kolumbus_tuition_blocks' => \Ext_Thebing_School_Tuition_Block::class,
			'kolumbus_absence' => \Ext_Thebing_Absence::class,
		];

		$entities = [];
		foreach($attributes as $attribute) {

			if(!isset($mapping[$attribute['entity']])) {
				throw new \RuntimeException(sprintf('No ivvy entity mapping found for table "%s"', $attribute['entity']));
			}

			/* @var \WDBasic $entity */
			$entity = \Factory::executeStatic($mapping[$attribute['entity']], 'getInstance', [$attribute['entity_id']]);

			$entities[$entity->getTableName().'_'.$entity->getId()] = $entity;
		}

		return collect($entities);
	}
}
