<?php

class Ext_TC_Countrygroup_Gui2_Data extends Ext_TC_Gui2_Data {

	static public function getDialog(Ext_Thebing_Gui2 $oGui) {


		$aCountries = Ext_TC_Country::getSelectOptions();
		$aSubObjectLabel = Ext_TC_Factory::executeStatic('Ext_TC_Object', 'getSubObjectLabel');

		$oDialog = $oGui->createDialog($oGui->t('Ländergruppe "{name}" editieren'), $oGui->t('Neue Ländergruppe anlegen'));

		$oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
			'db_alias' => 'tc_cg',
			'db_column' => 'name',
			'required' => 1
		)));

		$oJoinContainer = $oDialog->createJoinedObjectContainer('SubObjects', array('min'=>1, 'max'=>5));

		$oJoinContainer->setElement($oJoinContainer->createRow($aSubObjectLabel, 'select', array(
			'db_alias' => 'tc_cg_o',
			'db_column' => 'objects',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'selection' => new Ext_TC_Countrygroup_Selection_SubObjects(),
			'required' => 1,
			'searchable' => 1,
			'dependency' => array(
				array(
					'db_alias' => 'tc_cg_o',
					'db_column' => 'objects'
				)
			)
		)));

		$oJoinContainer->setElement($oJoinContainer->createRow($oGui->t('Länder'), 'select', array(
			'db_alias' => 'tc_cg_o',
			'db_column' => 'countries',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'select_options' => $aCountries,
			'searchable' => 1,
			'required' => 1
		)));

		$oDialog->setElement($oJoinContainer);

		$oDialog->access = array('core_admin_countrygroups', 'edit');

		return $oDialog;
	}
}
