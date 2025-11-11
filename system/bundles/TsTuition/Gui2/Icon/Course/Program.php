<?php

namespace TsTuition\Gui2\Icon\Course;

class Program extends \Ext_Gui2_View_Icon_Abstract {

	public function getStatus(&$aSelectedIds, &$aRowData, &$oElement) {

		if(
			!empty($aSelectedIds) &&
			(
				$oElement->action === 'edit' ||
				$oElement->task === 'deleteRow'
			)
		) {
			if(
				!\Ext_Thebing_Util::canOverwriteCourseSettings() &&
				\TsTuition\Entity\Course\Program::getRepository()->hasJourneyCourses((int)reset($aSelectedIds))
			) {
				return false;
			}

			return true;
		}

		return $oElement->active;
	}

}
