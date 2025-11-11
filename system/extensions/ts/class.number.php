<?php

class Ext_TS_Number extends Ext_TC_Number {
	
	/**
	 * Zahlenformatierung in Abhängigkeit der gewählten Schule
	 * @return int
	 */
	public static function getNumberFormatSettings() {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$iFormat = $oSchool->number_format;

		return $iFormat;
	}

}