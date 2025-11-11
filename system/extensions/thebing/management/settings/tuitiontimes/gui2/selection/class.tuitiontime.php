<?php

class Ext_Thebing_Management_Settings_TuitionTimes_Gui2_Selection_TuitionTime extends Ext_Gui2_View_Selection_Abstract {

	public function getOptions($aSelectedIds, $aSaveField, &$entity) {
		/** @var Ext_Thebing_Management_Settings_TuitionTime $entity */

		$tuitionTimesUsed = array_column(Ext_Thebing_Management_Settings_TuitionTime::findAll(), 'tuition_time_id');

		$options = \Ext_Thebing_Management_Settings_TuitionTimes_Gui2_Data::getTuitionTemplateOptions();

		$options = array_filter($options, function($id) use ($tuitionTimesUsed, $entity) {
			return $id == $entity->tuition_time_id || !in_array($id, $tuitionTimesUsed);
		}, ARRAY_FILTER_USE_KEY);

		$options = Util::addEmptyItem($options);

		return $options;

	}

}