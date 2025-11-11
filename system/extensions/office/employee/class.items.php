<?php

class Ext_Office_Employee_Items extends WDBasic {

	protected $_sTable = 'office_employee_items';
	protected $_sTableAlias = 'oei';

	public static function getEmployees() {

		$oOfficeEmployees = new Ext_Office_Employee();

		$aEmployeesPrepare = $oOfficeEmployees->getEmployeesList(null, null, 99999999);
		
		$aEmployees = array();
		// Prepare key -> id, value -> name
		foreach($aEmployeesPrepare as $aEmployeesPrepareSingle) {
			$aEmployees[$aEmployeesPrepareSingle["id"]] = $aEmployeesPrepareSingle["name"];
		}

		return $aEmployees;
		
	}

}