<?php

class Ext_Thebing_System_Checks_Tuition_LevelPositions extends Ext_TC_System_Checks_Gui2_FixSortableRow {

	protected $sTable = 'kolumbus_tuition_levels';
	protected $sGroupBy = 'idSchool';

	public function getTitle() {
		return 'Fix order of tuition levels';
	}

	public function getDescription() {
		return '';
	}

}