<?php

/**
 * 
 */
class Ext_TC_System_Checks_Frontend_Field_Mode extends GlobalChecks {
    
    public function getTitle() {
        return 'New option for template fields';
    }
    
    public function getDescription() {
        return 'Adds possibility to edit field templates.';
    }
    
    public function executeCheck() {

        set_time_limit(120);
		ini_set("memory_limit", '512M');

		/*
		 * Falls Prefix angegeben ist, dann field_mode auf "prefix" stellen
		 */
		$sSql = "
			SELECT 
				`template_id`
			FROM 
				`tc_frontend_templates_templates`
			WHERE
				`type` = 'fieldtemplateprefix' AND
				`template` != ''
				";
		$aTemplates = DB::getQueryCol($sSql);

		if(!empty($aTemplates)) {
			foreach($aTemplates as $iTemplateId) {
				$oTemplate = Ext_TC_Frontend_Template::getInstance($iTemplateId);
				$oTemplate->field_mode = 'prefix';
				$oTemplate->save();
			}
		}

        return true;
    }

}
