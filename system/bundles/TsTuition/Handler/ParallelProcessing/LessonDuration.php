<?php

namespace TsTuition\Handler\ParallelProcessing;

use Core\Handler\ParallelProcessing\TypeHandler;

class LessonDuration extends TypeHandler {

	public function getLabel() {
		return \L10N::t('Klassenplanung: Lektionsdauer', 'School');
	}

	public function execute(array $data, $debug = false) {

		/* @var \Ext_Thebing_Tuition_Class $class */
		$class = \Ext_Thebing_Tuition_Class::query()->find($data['class_id']);

		if ($class && $class->exist()) {

			\DB::begin(__METHOD__);

			$originalLessonDuration = floatval($data['original_lesson_duration']);
			$blocks = $class->getBlocks();

			try {

				foreach ($blocks as $block) {
					$allocations = $block->getJoinedObjectChilds('allocations');

					foreach ($allocations as $allocation) {
						/* @var \Ext_Thebing_School_Tuition_Allocation $allocation */
						$attendance = $allocation->getAttendance();

						if ($attendance) {
							$attendance->recalculateAbsence($originalLessonDuration);
						}

						$allocation->recalculateLessonsDuration()->save();
					}
				}

			} catch (\Throwable $e) {
				\DB::rollback(__METHOD__);
				throw $e;
			}

			\DB::commit(__METHOD__);
		}
	}

}