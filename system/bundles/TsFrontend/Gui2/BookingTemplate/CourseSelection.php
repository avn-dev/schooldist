<?php

namespace TsFrontend\Gui2\BookingTemplate;

use TsFrontend\Entity\BookingTemplate;

class CourseSelection extends \Ext_Gui2_View_Selection_Abstract
{
	public function getOptions($selectedIds, $saveField, &$entity)
	{
		/** @var BookingTemplate $entity */
		$options = ['' => ''];

		$blocks = $entity->getForm()->getFilteredBlocks(\Ext_Thebing_Form_Page_Block::TYPE_COURSES);

		foreach ($blocks as $block) {
			foreach ($block->getServiceBlockServices() as $course) {
				if ($course->school_id == $entity->school_id) {
					$options[$course->id] = $course->getName();
				}
			}
		}

		return $options;
	}
}