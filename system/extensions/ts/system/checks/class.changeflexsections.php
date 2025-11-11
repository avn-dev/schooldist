<?php

class Ext_TS_System_Checks_ChangeFlexSections extends Ext_Thebing_System_Checks_Enquiry_Filterset {

	public function getTitle() {
		$sTitle = 'Bugfix of individuel fields';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = 'Bugfix of individuel fields';
		return $sDescription;
	}

	public function isNeeded() {
		return true;
	}

	public function executeCheck() {
		
		$sSql = 'UPDATE 
                `tc_flex_sections` 
            SET 
                `category` = "schools", 
                `type` = "schools_accounting"
            WHERE
                `type` = "admin_schools"'
        ;
        
        DB::executeQuery($sSql);
        
		
		$sSql = 'UPDATE 
                `tc_flex_sections` 
            SET 
                `category` = "companies", 
                `type` = "companies_options"
            WHERE
                `type` = "admin_company"'
        ;
        
        DB::executeQuery($sSql);
        
		return true;
	}
		
}
