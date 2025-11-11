<?php

class Ext_Thebing_Tuition_Gui2_Colors_Gui2 extends Ext_Thebing_Gui2_Data {

	static public function getDialog(\Ext_Gui2 $oGui) {
		
		$oDialog					= $oGui->createDialog($oGui->t('Klassenfarbe "{title}" editieren'),$oGui->t('Neuen Klassenfarbe anlegen'));
		$oDialog->width				= 900;
		$oDialog->height			= 650;

		$oDialog->save_as_new_button		= true;
		$oDialog->save_bar_options			= true;
		$oDialog->save_bar_default_option	= 'new';

		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;

		$oDialog->setElement($oDialog->createRow($oGui->t('Titel'), 'input', array(
				'db_column'			=> 'title',
				'required'			=> 1,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Farbcode'), 'color', array(
				'db_column'			=> 'code',
				'required'			=> 1,
		)));
		
		return $oDialog;
	}
	
	static public function getOrderby(){

		return [
			'title' => 'ASC'
		];
	}
	
	static public function getWhere() {

		$oSchool				= Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId				= $oSchool->id;

		return ['school_id' => $iSchoolId];
}

}