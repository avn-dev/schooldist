<?php

namespace TsTuition\Gui2\Selection;

class LessonDuration extends \Ext_Gui2_View_Selection_Abstract
{
	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic)
	{
		$lessonDurations = collect($oWDBasic->getJoinTableObjects('courses'))
			->mapWithKeys(fn (\Ext_Thebing_Tuition_Course $course) => [$course->lesson_duration => \Ext_Thebing_Format::Number($course->lesson_duration, null, $oWDBasic->school_id)]);

		if ($oWDBasic->lesson_duration === null) {
			$oWDBasic->lesson_duration = $lessonDurations->keys()->first();
		}

		return $lessonDurations->toArray();
	}
}