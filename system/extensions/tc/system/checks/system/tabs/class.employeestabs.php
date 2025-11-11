<?php

class Ext_TC_System_Checks_System_Tabs_EmployeesTabs extends Ext_TC_System_Checks_System_Tabs_AbstractMoved {

	protected function getMovedTabs(): array {

		return [
			'/gui2/page/tc_employee_categories' => '/gui2/page/Tc_system_type_mappings/users'
		];

	}
}
