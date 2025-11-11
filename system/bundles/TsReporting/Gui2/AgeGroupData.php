<?php

namespace TsReporting\Gui2;

class AgeGroupData extends \Ext_Gui2_Data
{
	public static function createDialog(\Ext_Gui2 $gui): \Ext_Gui2_Dialog
	{
		$dialog = $gui->createDialog($gui->t('Altersgruppe "{name}" bearbeiten'), $gui->t('Neue Altersgruppe anlegen'));

		$dialog->setElement($dialog->createRow($gui->t('Name'), 'input', [
			'db_column' => 'name',
			'required' => true
		]));

		$dialog->setElement($dialog->createRow($gui->t('Alter von'), 'input', [
			'db_column' => 'age_from',
			'required' => true
		]));

		$dialog->setElement($dialog->createRow($gui->t('Alter bis'), 'input', [
			'db_column' => 'age_until',
			'required' => true
		]));

		return $dialog;
	}
}