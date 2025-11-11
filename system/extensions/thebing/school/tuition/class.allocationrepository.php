<?php

class Ext_Thebing_School_Tuition_AllocationRepository extends WDBasic_Repository {

	/**
	 * @param Ext_Thebing_School_Tuition_Allocation $oAllocation
	 * @return float[]
	 */
	public function getWeekDayDurations(Ext_Thebing_School_Tuition_Allocation $oAllocation) {

		$sSql = "
			SELECT DISTINCT
				`ktb`.`id`,
				`ktt`.`lessons` `lessons`,
				`ktcl`.`lesson_duration`,
				`ktbd`.`day` as `day`,
				`ktc`.`id` `course_id`
			FROM
				`ts_inquiries_journeys_courses` `kic` LEFT OUTER JOIN
				`ts_tuition_courses_programs_services` `ts_tcps` ON
					`ts_tcps`.`program_id` = `kic`.`program_id` AND
					`ts_tcps`.`type` = '".\TsTuition\Entity\Course\Program\Service::TYPE_COURSE."' AND
					`ts_tcps`.`active` = 1 LEFT OUTER JOIN
				`kolumbus_tuition_courses` `ktc` ON
					`ktc`.`id` = `ts_tcps`.`type_id` AND
					`ktc`.`per_unit` != ".Ext_Thebing_Tuition_Course::TYPE_EMPLOYMENT." LEFT OUTER JOIN
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic` ON
					`ktbic`.`inquiry_course_id` = `kic`.`id` AND
					`ktbic`.`program_service_id` = `ts_tcps`.`id` AND
					`ktbic`.`active` = 1 LEFT JOIN
				`kolumbus_tuition_blocks` `ktb` ON
					`ktb`.`id` = `ktbic`.`block_id` AND
					`ktb`.`active` = 1 LEFT JOIN
				`kolumbus_tuition_classes` `ktcl` ON
					`ktcl`.`id` = `ktb`.`class_id` AND
					`ktcl`.`active` = 1 INNER JOIN
				`kolumbus_tuition_classes_courses` `ktcc` ON
					`ktcc`.`class_id` = `ktcl`.`id` LEFT OUTER JOIN
				`kolumbus_tuition_blocks_days` `ktbd` ON
					`ktbd`.`block_id` = `ktb`.`id` LEFT OUTER JOIN
				`kolumbus_tuition_templates` `ktt` ON
					`ktt`.`id` = `ktb`.`template_id`
			WHERE
				`kic`.`id` = :inquiry_course_id AND
				`kic`.`for_tuition` = 1 AND
				`kic`.`active` = 1 AND
				`ktb`.`teacher_id` = :teacher_id AND
				`ktb`.`week` = :week AND
				`ktbic`.`course_id` = :course_id AND
				`ktbic`.`block_id` = :block_id
    	";

		$oBlock = $oAllocation->getBlock();

		$aSql = [
			'inquiry_course_id'	=> $oAllocation->inquiry_course_id,
			'course_id' => $oAllocation->course_id,
			'week' => $oBlock->week,
			'teacher_id' => $oBlock->teacher_id,
			'block_id' => $oBlock->id,
		];

		$aTemp = DB::getPreparedQueryData($sSql,$aSql);

		$aDurations = [];
		foreach($aTemp as $aBlock) {
			$aDurations[$aBlock['day']] += (float)$aBlock['lesson_duration'] * (float)$aBlock['lessons'];
		}

		return $aDurations;

	}

	/**
	 * @param int[] $blockIds
	 * @param int $roomId
	 * @return Ext_Thebing_School_Tuition_Allocation[]
	 */
	public function findByBlocksAndRoom(array $blockIds, int $roomId): array {

		$where = [];

		foreach ($blockIds as $blockId) {

			$blockId = (int)$blockId;
			$block = Ext_Thebing_School_Tuition_Block::getInstance($blockId);

			try {
				$roomId2 = $block->adjustAllocationRoomId($roomId);
			} catch (RuntimeException $e) {
				// Bei Folgewochen werden hier einfach alle Blöcke reingeknallt, unabhängig davon, ob es dort überhaupt Zuweisungen gibt
				continue;
			}

			$where[] = "
					(
						`ktbic`.`block_id` = {$blockId} AND
						`ktbic`.`room_id` = {$roomId2} AND
						`ktbic`.`active` = 1
					)
				";
		}

		return $this->findBlocksByWhereParts($where);

	}

	/**
	 * @param int[] $blockIds
	 * @param int[] $journeyCourseIds
	 * @return Ext_Thebing_School_Tuition_Allocation[]
	 */
	public function findByBlocksAndJourneyCourses(array $blockJourneyCourseIds): array {

		$where = [];

		foreach ($blockJourneyCourseIds as $blockId=>$journeyCourseIds) {
			foreach ($journeyCourseIds as $journeyCourseId) {

				$blockId = (int)$blockId;
				$journeyCourseId = (int)$journeyCourseId;

				$where[] = "
					(
						`ktbic`.`block_id` = {$blockId} AND
						`ktbic`.`inquiry_course_id` = {$journeyCourseId} AND
						`ktbic`.`active` = 1
					)
				";
			}
		}

		return $this->findBlocksByWhereParts($where);

	}

	private function findBlocksByWhereParts(array $whereParts): array {

		$where = join(' OR ', $whereParts);

		$sql = "
			SELECT
				`ktbic`.*
			FROM
				`kolumbus_tuition_blocks_inquiries_courses` `ktbic`
			WHERE
				{$where}
		";

		$result = array_map(function (array $row) {
			return Ext_Thebing_School_Tuition_Allocation::getObjectFromArray($row);
		}, (array)\DB::getQueryRows($sql));

		return $result;

	}

    /**
     * @param Ext_Thebing_School_Tuition_Block $block
     * @param Ext_TS_Inquiry $inquiry
     * @return Ext_Thebing_School_Tuition_Allocation|null
     */
	public function findAllocationForBlock(\Ext_Thebing_School_Tuition_Block $block, \Ext_TS_Inquiry $inquiry) {

	    $courseIds = collect($inquiry->getCourses())
            ->map(function($course) {
                return $course->getId();
            })
            ->toArray();

	    return $this->findOneBy(['block_id' => $block->getId(), 'inquiry_course_id' => $courseIds]);

    }

}
