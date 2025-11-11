<?php

/**
 * Feldtypen pro Verfügbarkeiten manipulieren
 */
class Ext_Thebing_Gui2_Selection_Flexibility_FieldType extends Ext_TC_Gui2_Selection_Flexibility_Section_Filter
{
	/**
	 * Filterdaten definieren, nach diesen Sektionsarten dürfen nur Folgende "Überprüfen mit" Optionen dargestellt werden
	 * 
	 * @return array 
	 */
	protected function _getFilterData()
	{
		$aFilterData = array(
			'tuition_attendance_register' => array(
				'0',
				'2',
				'5',
			)
		);
		
		return $aFilterData;
	}
}