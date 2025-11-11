<?php

namespace TsTuition\Gui2\Icon;

use Illuminate\Support\Arr;

class CourseLanguages extends \Ext_Gui2_View_Icon_Abstract
{
	public function getStatus(&$selectedIds, &$rowData, &$element)
	{
		if ($element->task === 'deleteRow' && !empty($selectedIds)) {
			$entity = \Ext_Thebing_Tuition_LevelGroup::getInstance(Arr::first($selectedIds));
			return empty($entity->tuition_classes) && empty($entity->inquiry_courses);
		}

		return parent::getStatus($selectedIds, $rowData, $element);
	}
}