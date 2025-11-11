<?php

namespace TcComplaints\Gui2\Data;

use \Ext_TC_Factory;
use \Ext_TC_Util;

class SubCategory extends \Ext_TC_Gui2_Data {

	/**
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 * @throws \Exception
	 */
	public static function getDialog(\Ext_Gui2 $oGui) {

		$oDialog = $oGui->createDialog($oGui->t('Unterkategorie " {title} " editieren'), $oGui->t('Unterkategorie anlegen'));

		$oDialog->setElement($oDialog->createRow($oGui->t('Bezeichnung'), 'input', array(
			'db_alias' => 'tc_ccsc',
			'db_column' => 'title',
			'required' => true
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Abkürzung'), 'input', array(
			'db_alias' => 'tc_ccsc',
			'db_column' => 'short_name',
			'required' => true
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Beschreibung'), 'html', array(
			'db_alias' => 'tc_ccsc',
			'db_column' => 'description',
			'required' => true,
			'advanced' => true
		)));

		return $oDialog;

	}

	/**
	 * @return array
	 */
	public static function getSubCategoriesFilterOptions() {

		$aOptions = Ext_TC_Factory::executeStatic('\TcComplaints\Entity\SubCategory', 'getSelectOptions');
		$aOptions = Ext_TC_Util::addEmptyItem($aOptions);

		return $aOptions;

	}

	/**
	 * Diese Methode soll einfach ein leeres Array zurück geben, da der Unterkategorie-Filter in der Liste
	 * nicht gefüllt werden soll, erst nachdem eine Auswahl des Kategorie-Filters getroffen wurde
	 *
	 * @return array
	 */
	public static function getEmptySubCategoriesFilterOptions() {
		return array();
	}

}