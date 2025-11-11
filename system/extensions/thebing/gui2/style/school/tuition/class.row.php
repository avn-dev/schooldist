<?php

class Ext_Thebing_Gui2_Style_School_Tuition_Row extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData) {
		
		//Bei zugewiesenen Schülern, soll nur die Farbe falls Zuweisung verloren gegangen ist angezeigt werden
		if($aRowData['view'] == 'allocated')
		{
			if($aRowData['course_allocated'] == '0')
			{
				return 'background-color: '.Ext_Thebing_Util::getColor('bad').';';
			}
		}
		//bei Minusstunden nur bei nicht zugewiesenen anzeigen
		elseif($aRowData['remaining_lessons'] < 0) {
			return 'background-color: '.Ext_Thebing_Util::getColor('changed').';';
		} elseif((int)$aRowData['state'] & 8) {
			// Schülerferien
			return 'background-color: '.Ext_Thebing_Util::getColor('storno').'; ';
		} elseif(
			$aRowData['state'] & Ext_TS_Inquiry_TuitionIndex::STATE_VACATION ||
			$aRowData['state_course'] & Ext_TS_Inquiry_TuitionIndex::STATE_VACATION ||
			$aRowData['between_course_date'] == 0
		) {
			return 'color: '.Ext_Thebing_Util::getColor('inactive_font').';font-style:italic; ';
		}

		return null;

	}

}
