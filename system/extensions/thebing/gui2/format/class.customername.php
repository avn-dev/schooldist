<?php

class Ext_Thebing_Gui2_Format_CustomerName extends Ext_Thebing_Gui2_Format_Name {

	// Statische Methode für aufrufe unabhängig der GUI 2
	// falls das Customer Objekt vorhanden ist ->name nutzen
	static public function manually_format($sLastname, $sFirstname){
		$aTemp = array('lastname' => $sLastname, 'firstname' => $sFirstname);
		$oDummy;
		$oFormat = new Ext_Thebing_Gui2_Format_CustomerName();
		return $oFormat->format('', $oDummy, $aTemp);
	}
}