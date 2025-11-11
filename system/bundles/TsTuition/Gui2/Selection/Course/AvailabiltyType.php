<?php

namespace TsTuition\Gui2\Selection\Course;

class AvailabiltyType extends \Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$oWDBasic): array {
		/* @var \Ext_Thebing_Tuition_Course $course */
		$course = $oWDBasic->getCourse();
	
		$options = [''];
		
		if($course->canHaveStartDates()) {
			$options[\Ext_Thebing_Tuition_Course_Startdate::TYPE_START_DATE] = $this->_oGui->t('Startdatum');
		}

		$options[\Ext_Thebing_Tuition_Course_Startdate::TYPE_NOT_AVAILABLE] = $this->_oGui->t('Nicht verfügbar');

		return $options;
	}
	
}
