<?php

namespace TsTuition\Gui2\Data;

class PlacementtestCategory extends \Ext_Thebing_Gui2_Data
{

	public static function getDialog(\Ext_Gui2 $gui)
	{

		$dialog = $gui->createDialog($gui->t('Kategorie "{category}" editieren'),$gui->t('Neue Kategorie anlegen'));
		$dialog->width = 900;
		$dialog->height = 650;

		$dialog->save_bar_default_option = 'new';
		$dialog->save_as_new_button = true;
		$dialog->save_bar_options = true;

		$dialog->setElement($dialog->createRow($gui->t('Titel'), 'input', array(
			'db_column'	=> 'category',
			'required' => 1,
		)));

		return $dialog;
	}

	public static function getOrderBy()
	{
		return['kptc.category' => 'ASC'];
	}

}
