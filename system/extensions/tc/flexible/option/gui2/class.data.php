<?php

class Ext_TC_Flexible_Option_Gui2_Data extends Ext_TC_Gui2_Data {
	
	public function switchAjaxRequest($_VARS) {
		
		if(
			$_VARS['task'] === 'confirm' &&
			$_VARS['action'] === 'add_separator'
		) {
			
			$oSeparator = new Ext_TC_Flexible_Option();
			$oSeparator->separator_option = 1;
			$oSeparator->title = '-';
			$oSeparator->field_id = (int) reset($_VARS['parent_gui_id']);
			
			$aAllLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getTranslationLanguages');
			$aValues = [];
			foreach($aAllLanguages as $aLanguage) {
				$aValues[] = [
					'lang_id' => $aLanguage['iso'],
					'title' => ''
				];
			}
			
			$oSeparator->kfsfov = $aValues;
			
			$oSeparator->save();

			$_VARS['task'] = 'loadTable';
			unset($_VARS['action']);
			
		}

		parent::switchAjaxRequest($_VARS);
	}
	
}
