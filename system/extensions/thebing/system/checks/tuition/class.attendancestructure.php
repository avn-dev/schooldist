<?php

/**
 * Anwesenheitsstruktur umstellen und im Anschluss den Index für die Anwesenheit aufbauen, sicherstellen
 * dass die Reihenfolge der Checks richtig ausgeführt wird...
 * 
 * @author Mehmet Durmaz
 */
class Ext_Thebing_System_Checks_Tuition_AttendanceStructure extends GlobalChecks
{
	public function getTitle()
	{
		return 'Attendance Allocation & Attendance Index';
	}
	
	public function getDescription()
	{
		return 'Change attendance allocations and create index new.';
	}
	
	public function executeCheck()
	{
		$oCheckNewStructure = new Ext_Thebing_System_Checks_Tuition_AttendanceAllocation();
		$oCheckNewStructure->executeCheck();
		
		$oCheckCreateIndex = new Ext_Thebing_System_Checks_Index_Reset_Attendance();
		$oCheckCreateIndex->executeCheck();

		return true;
	}
}