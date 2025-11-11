<?php

namespace TsTuition\Gui2\Data;

class PlacementtestQuestionAnswer extends \Ext_Thebing_Gui2_Data
{

	public static function getDialog(\Ext_Gui2 $gui)
	{

		$dialog					= $gui->createDialog($gui->t('Antwort editieren'),$gui->t('Neue Antwort anlegen'));
		$dialog->width				= 900;
		$dialog->height			= 650;

		$dialog->save_as_new_button		= true;
		$dialog->save_bar_options			= true;
		$dialog->save_bar_default_option	= 'new';

		$dialog->setElement($dialog->createRow($gui->t('Antwort'), 'html', array(
			'db_column'			=> 'text',
			'required'			=> 1,
		)));

		$dialog->setElement($dialog->createRow($gui->t('Richtige Antwort'), 'checkbox', array(
			'db_column'			=> 'right_answer',
		)));

		return $dialog;
	}

	public static function getOrderBy()
	{
		return['position' => 'ASC'];
	}

}
