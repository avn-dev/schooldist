<?php

class Ext_Thebing_Insurances_Gui2_Style_Communication extends Ext_Gui2_View_Style_Abstract {

	public function getStyle($mValue, &$oColumn, &$aRowData) {

		$bHasDate = \Core\Helper\DateTime::isDate($mValue, 'Y-m-d');

		if (
			$bHasDate &&
			$aRowData['changes_'.$oColumn->db_column] // changes_confirm, changes_info_customer, changes_info_provider
		) {
			return 'background-color: '.Ext_Thebing_Util::getColor('neutral');
		}

		if (!$bHasDate) {
			return 'background-color: '.Ext_Thebing_Util::getColor('bad');
		}

		return 'background-color: '.Ext_Thebing_Util::getColor('good');

	}

}
