<?php

namespace Tc\Gui2\Data\Employee;

class Category extends \Ext_TC_Gui2_Data {

	public static function getDialog(\Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Mitarbeiterkategorie " {name} " editieren'), $oGui->t('Mitarbeiterkategorie anlegen'));

		$oDialog->setElement($oDialog->createRow($oGui->t('Bezeichnung'), 'input', [
			'db_alias' => 'tc_ec',
			'db_column' => 'name',
			'required' => true
		]));

		$oDialog->setElement($oDialog->createRow(
			$oGui->t('Funktionen'),
			'select',
			[
				'db_alias' => '',
				'db_column' => 'functions',
				'select_options' => \Ext_TC_Factory::executeStatic('Ext_TC_User', 'getAvailableFunctions'),
				'style' => 'height: 60px;',
				'multiple' => 5,
				'jquery_multiple' => 1,
			]
		));

		return $oDialog;
	}

}