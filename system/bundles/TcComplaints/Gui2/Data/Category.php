<?php

namespace TcComplaints\Gui2\Data;

use \TcComplaints\Gui2\Data\Complaint as TcComplaint_Data;
use \Ext_TC_Factory;
use \Ext_TC_Util;

class Category extends \Ext_TC_Gui2_Data {

	/**
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 * @throws \Exception
	 */
	public static function getDialog(\Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Kategorie "{title}" editieren'), $oGui->t('Kategorie anlegen'));

		$oDialog->setElement($oDialog->createRow($oGui->t('Bezeichnung'), 'input', array(
			'db_alias' => 'tc_cc',
			'db_column' => 'title',
			'required' => true
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Abkürzung'), 'input', array(
			'db_alias' => 'tc_cc',
			'db_column' => 'short_name',
			'required' => true
		)));

		$aAreas = \Ext_TC_Factory::executeStatic('\TcComplaints\Gui2\Data\Complaint', 'getAreas', [\Ext_TC_System::getInterfaceLanguage()]);

		$oDialog->setElement($oDialog->createRow($oGui->t('Bereich'), 'select', array(
			'db_alias' => 'tc_cc',
			'db_column' => 'type',
			'select_options' => $aAreas,
			'required' => true
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Beschreibung'), 'html', array(
			'db_alias' => 'tc_cc',
			'db_column' => 'description',
			'required' => true,
			'advanced' => true
		)));

		return $oDialog;

	}

	/**
	 * @return array|object
	 */
	public static function getCategoriesFilterOptions() {

		$aOptions = Ext_TC_Factory::executeStatic('\TcComplaints\Entity\Category', 'getSelectOptions');
		$aOptions = Ext_TC_Util::addEmptyItem($aOptions);

		return $aOptions;

	}

}