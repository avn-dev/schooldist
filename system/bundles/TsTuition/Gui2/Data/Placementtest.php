<?php

namespace TsTuition\Gui2\Data;

class Placementtest extends \Ext_Thebing_Gui2_Data
{

	public static function getDialog(\Ext_Gui2 $gui)
	{

		$dialog = $gui->createDialog($gui->t('Einstufungstest "{name}" editieren'), $gui->t('Neuen Einstufungstest anlegen'));

		$dialog->save_as_new_button = true;
		$dialog->save_bar_options = true;

		$dialog->setElement($dialog->createRow($gui->t('Name'), 'input', array(
			'db_alias' => '',
			'db_column' => 'name',
			'required' => true,
		)));

		$languages = \Ext_Thebing_Tuition_LevelGroup::getSelectOptions(null, \Ext_Thebing_School::fetchInterfaceLanguage());

		$dialog->setElement($dialog->createRow($gui->t('Kurssprache'), 'select', array(
			'db_column'=>'courselanguage_id',
			'select_options' => \Ext_Thebing_Util::addEmptyItem($languages),
			'required' => true
		)));

		$dialog->setElement(
			$dialog->createRow(
				$gui->t('Genauigkeit der Fehlererkennung (Angabe in Prozent)'),
				'input',
				array(
					'db_alias' => 'ts_pt',
					'db_column' => 'placementtest_accuracy_in_percent',
					'format' => new \Ext_Thebing_Gui2_Format_Float(2, true, true),
					'required' => true
				)
			)
		);

		return $dialog;
	}

}
