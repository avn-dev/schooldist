<?php

namespace TsTuition\Gui2\Format\Course;

use TsTuition\Enums\LessonsUnit;

class Lessons extends \Ext_Gui2_View_Format_Abstract {

	const MAX_LESSONS_SHOWN = 2;

	public function format($mValue, &$oColumn = null, &$aResultData = null)
	{
		if (empty($mValue)) {
			return '';
		}

		$courses = explode('{||}', $mValue);
		$formatted = [];

		$floatFormat = new \Ext_Thebing_Gui2_Format_Float();

		foreach ($courses as $course) {
			[$lessonsList, $lessonsUnit] = explode('{|}', $course);
			$lessonsList = array_map(fn ($lessons) => '<span class="badge">'.$floatFormat->format($lessons, $oColumn, $aResultData).'</span>', (array)json_decode($lessonsList, true));
			$unitLabel = LessonsUnit::from($lessonsUnit)->getLabelText($this->oGui->getLanguageObject());

			$lessonsPackages = count($lessonsList);

			if ($lessonsPackages > self::MAX_LESSONS_SHOWN) {
				$lessonsList = (array)array_slice($lessonsList, 0, self::MAX_LESSONS_SHOWN);
				$lessonsList[] = '<span class="badge">+'.$lessonsPackages - self::MAX_LESSONS_SHOWN.'</span>';
			}

			$formatted[] = sprintf('%s %s', implode('&nbsp;', $lessonsList), $unitLabel);
		}

		return implode('<br/>', $formatted);
	}
}