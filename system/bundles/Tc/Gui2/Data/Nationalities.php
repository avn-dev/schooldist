<?php

namespace Tc\Gui2\Data;

class Nationalities extends \Ext_TC_Gui2_Data {

	public static function getOrderby() {
		return ['cn_iso_2' => 'ASC'];
	}

	/**
	 * @inheritdoc
	 */
	public function prepareColumnListByRef(&$aColumnList) {

		parent::prepareColumnListByRef($aColumnList);

		$positionBeforeDefaultColumns = count($aColumnList) - 4;
		// Alle Anzeigesprachen
		$languages = \System::d('allowed_languages');

		foreach ($languages as $langueIso => $language) {
			$oColumn = new \Ext_Gui2_Head();
			$oColumn->db_column = 'cn_short_'.$langueIso;
			$oColumn->title = 'Kurzform-Name ('.$language.')';
			$oColumn->width = \Ext_TC_Util::getTableColumnWidth('name');
			$oColumn->sortable = false;
			$oColumn->inplaceEditor = true;
			array_splice($aColumnList, $positionBeforeDefaultColumns, 0, [$oColumn]);

			$oColumn = new \Ext_Gui2_Head();
			$oColumn->db_column = 'nationality_'.$langueIso;
			$oColumn->title = 'Nationalität ('.$language.')';
			$oColumn->width = \Ext_TC_Util::getTableColumnWidth('name');
			$oColumn->sortable = false;
			$oColumn->inplaceEditor = true;
			array_splice($aColumnList, $positionBeforeDefaultColumns, 0, [$oColumn]);
		}

	}

	public static function getDialog(\Ext_Gui2 $oGui)
	{

		$oDialog = $oGui->createDialog('', $oGui->t('Neue Nationalität anlegen'));

		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;

		$oDialog->setElement($oDialog->createRow('ISO2', 'input', [
			'db_column' => 'cn_iso_2',
			'required' => true,
		]));

		$oDialog->setElement($oDialog->createRow('ISO3', 'input', [
			'db_column' => 'cn_iso_3',
			'required' => true
		]));

		$oDialog->setElement($oDialog->createRow('ISO-Nummer', 'input', [
			'db_column' => 'cn_iso_nr',
		]));

		return $oDialog;
	}

}
