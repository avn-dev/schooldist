<?php

namespace TsTuition\Helper;

class Rooms
{
	/**
	 * @var array
	 */
	static protected array $intersectingRoomIdsCache = [];

	/**
	 * Gibt die Room Ids zurück, die alle Blocks der Klasse ab weekFrom gemeinsam haben.
	 * @param \Ext_Thebing_Tuition_Class $class
	 * @param int $blockParentId
	 * @param string $weekFrom
	 * @return array
	 */
	static public function getIntersectingRoomIds(\Ext_Thebing_Tuition_Class $class, int $blockParentId, string $weekFrom): array
	{
		if (!isset(self::$intersectingRoomIdsCache[$class->id])) {
			self::$intersectingRoomIdsCache[$class->id] = [];
			foreach (collect($class->getBlocks(\Carbon\Carbon::now()->startOfWeek(), false, '>=')) as $block) {
				self::$intersectingRoomIdsCache[$class->id][$block->parent_id ? : $block->id][(int)str_replace("-", "", $block->week)] = $block->getRoomIds();
			}
		}
		$intersectingRoomIdsBlock = self::$intersectingRoomIdsCache[$class->id][$blockParentId] ?? [];
		$intersectingRoomIdsAllWeeks = collect($intersectingRoomIdsBlock)
			->filter(fn(array $roomIds, int $week) => $week >= (int)str_replace("-", "", $weekFrom))
			->toArray();

		return !empty($intersectingRoomIdsAllWeeks) ? array_intersect(...$intersectingRoomIdsAllWeeks) : [];
	}
}