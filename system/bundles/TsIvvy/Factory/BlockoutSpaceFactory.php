<?php

namespace TsIvvy\Factory;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use TsIvvy\Api;
use TsIvvy\DTO\BlockoutSpace;
use TsIvvy\Exceptions\RuntimeException;
use TsIvvy\Handler\ExternalApp;

class BlockoutSpaceFactory {

	public static function fromTuitionBlock(\Ext_Thebing_School_Tuition_Block $block, int $day, int $roomId): BlockoutSpace {

		$class = $block->getClass();

		$room = \Ext_Thebing_Tuition_Classroom::getInstance($roomId);

		$ivvyRoomIds = ExternalApp::getIvvyRoomIdsForFideloRoom($room);

		$template = $block->getTemplate();

		$dates = $block->getDaysAsDateTimeObjects();
		$date = $dates[$day];

		$dateStart = (new Carbon($date))->setTimeFromTimeString($template->from);
		$dateEnd = (new Carbon($date))->setTimeFromTimeString($template->until);

		$venueId = $ivvyRoomIds['venue_id'] ?? null;

		if ($venueId === null) {
			$venueId = $block->getMeta(Api::buildBlockoutVenueMetaKey($day, $roomId), null);
		}

		$dto = new BlockoutSpace(
			$block->getMeta(Api::buildBlockoutMetaKey($day, $roomId), null),
			$class->name,
			$ivvyRoomIds['room_id'] ?? null,
			$venueId,
			\User::getInstance($class->creator_id),
			CarbonPeriod::create($dateStart, $dateEnd)
		);

		$dto->additional('day', $day);
		$dto->additional('room_id', $roomId);

		return $dto;
	}

	public static function fromAccommodationAllocation(\Ext_Thebing_Accommodation_Allocation $allocation): BlockoutSpace {

		if($allocation->isReservation()) {
			$reservationData = $allocation->getReservationData();
			$name = (isset($reservationData['comment'])) ? $reservationData['comment'] : "Fidelo";
		} else {
			$traveller = $allocation->getInquiry()->getFirstTraveller();
			$name = $traveller->getName();
		}

		$ivvyRoomIds = ExternalApp::getIvvyRoomIdsForFideloRoom($allocation->getRoom());

		$venueId = 	$allocation->getMeta('ivvy_blockout_venue_id', $ivvyRoomIds['venue_id'] ?? null);

		$dto = new BlockoutSpace(
			$allocation->getMeta('ivvy_blockout_id', null),
			$name,
			$ivvyRoomIds['room_id'],
			$venueId,
			\User::getInstance($allocation->creator_id),
			$allocation->getPeriodWithTime()
		);

		return $dto;
	}

}
