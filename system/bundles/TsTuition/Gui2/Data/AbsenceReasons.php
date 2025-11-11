<?php

namespace TsTuition\Gui2\Data;

class AbsenceReasons extends \Ext_Thebing_Gui2_Data {
	
	public static function getOrderby() {
		return ['ts_tar.key'=>'ASC'];
	}
	
	static public function getDialog(\Ext_Gui2 $gui) {

		$defaultLang = \Ext_Thebing_Util::getInterfaceLanguage();
		$translationLanguages = \Ext_Thebing_Util::getTranslationLanguages();
				
		$dialog = $gui->createDialog(
			$gui->t('Abwesenheitsgrund "{name_'.$defaultLang.'}"'),
			$gui->t('Neuer Abwesenheitsgrund')
        );
		
		$dialog->setElement($dialog->createRow(
			$gui->t('Kürzel'), 'input', ['db_alias' => 'ts_tar', 'db_column'=>'key', 'required'=>1])
        );
		
		$dialog->setElement($dialog->createI18NRow($gui->t('Bezeichnung'), [
			'db_column_prefix' => 'name_',
			'db_alias' => 'ts_tar',
			'required' => true
		], $translationLanguages));

		$dialog->setElement($dialog->createRow(
			$gui->t('Im Lehrerportal verfügbar'), 'checkbox', ['db_alias' => 'ts_tar', 'db_column'=>'teacher_portal_available'])
        );
		
		$dialog->width = 950;
		$dialog->height = 500;

		$dialog->save_as_new_button		= true;
		$dialog->save_bar_options			= true;
		$dialog->save_bar_default_option	= 'new';

		return $dialog;
	}
		
}
