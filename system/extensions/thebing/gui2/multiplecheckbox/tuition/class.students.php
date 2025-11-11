<?php

class Ext_Thebing_Gui2_MultipleCheckbox_Tuition_Students extends Ext_Gui2_View_MultipleCheckbox_Abstract{

	public function getStatus($iRowID, &$aColumnList, &$aResultData){
		
		// Gibt an, ob der Schüler zum gewählten Wochentag Unterricht hat.
		if(
			$aResultData['state'] & Ext_TS_Inquiry_TuitionIndex::STATE_VACATION ||
			$aResultData['state_course'] & Ext_TS_Inquiry_TuitionIndex::STATE_VACATION ||
			$aResultData['between_course_date'] == 0
		) {
			return 0;
		} else {
			return 1;
		}
	}

}