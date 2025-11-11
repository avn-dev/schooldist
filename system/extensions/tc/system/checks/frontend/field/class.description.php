<?php

class Ext_TC_System_Checks_Frontend_Field_Description extends GlobalChecks {
    
    public function getTitle() {
        return 'Frontend Field Description';
    }
    
    public function getDescription() {
        return 'Prepare database for i18n structure';
    }
    
    public function executeCheck() {
        
        set_time_limit(120);
		ini_set("memory_limit", '512M');
		
		// Wurde der Check bereits ausgeführt?
		$bCheck = DB::getDefaultConnection()->checkField('tc_frontend_templates_fields', 'description');
		if($bCheck === false) {
			return true;
		}
		
		// Backups -------------------------------------------------------------
		
		$bBackup = Util::backupTable('tc_frontend_templates_fields');
        if(!$bBackup){
			throw new Exception('Backup Error!');
		}
        
        DB::begin('Ext_TC_System_Checks_Frontend_Field_Description');
        
        try {
            $aLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getTranslationLanguages');
            
            $aTemplateFields = $this->getTemplateFields();
            
            foreach($aTemplateFields as $aTemplateField) {
                foreach ($aLanguages as $aLanguage) {
                    DB::insertData('tc_frontend_templates_fields_i18n', array('field_id' => $aTemplateField['id'], 'language_iso' => $aLanguage['iso'], 'description' => $aTemplateField['description']));
                }
            }
                        
            $this->dropColumn('tc_frontend_templates_fields', 'description');
            
        } catch (Exception $ex) {
            __pout($ex);
            DB::rollback('Ext_TC_System_Checks_Frontend_Field_Description');
            return false;
        }
        
        DB::commit('Ext_TC_System_Checks_Frontend_Field_Description');
        
        return true;
    }
 
    protected function getTemplateFields() {
        
        $sSql = "
            SELECT
                *
            FROM
                `tc_frontend_templates_fields`                
        ";
        
        return (array) DB::getQueryData($sSql);
    }
    
    protected function dropColumn($sTable, $sColumn) {
        
        $sSql = "
            ALTER TABLE #table DROP #column
        ";
        
        DB::executePreparedQuery($sSql, array('table' => $sTable, 'column' => $sColumn));
    }
    
}
