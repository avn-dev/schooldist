<?php

class Ext_Thebing_Gui2_Format_Teacher_Costcategory extends Ext_Thebing_Gui2_Format_Format {

	public function format($mValue, &$oColumn = null, &$aResultData = null){

		if($mValue == -1) {
			$mValue = L10N::t('Festgehalt', 'Thebing » Tuition » Teachers');
		} else {
			$aCostcategories = Ext_Thebing_Marketing_Costcategories::getTeacherCategories();
			$mValue = $aCostcategories[$mValue] ?? null;
		}

		return $mValue;

	}

}
