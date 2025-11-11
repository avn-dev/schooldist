<?php

namespace Ts\Gui2\Data;

class AbsenceCategories extends \Ext_Thebing_Gui2_Data
{

	static public function getOrderby()
    {

		return ['name'=>'ASC'];
	}

	static public function getWhere()
    {
		return ['client_id' => \Ext_Thebing_Client::getClientId()];
	}

	static public function getDialog(\Ext_Thebing_Gui2 $oGui)
    {

		$oDialog = $oGui->createDialog(
			$oGui->t('Abwesenheitskategorie "{name}"'),
			$oGui->t('Neue Abwesenheitskategorie')
        );

		$oDialog->setElement($oDialog->createRow(
			$oGui->t('Name'), 'input', array('db_column'=>'name', 'required'=>1))
        );
		$oDialog->setElement($oDialog->createRow(
			$oGui->t('Farbe'), 'color', array('db_column'=>'color', 'required'=>1))
        );

		$oDialog->width = 950;
		$oDialog->height = 300;

		$oDialog->save_as_new_button		= true;
		$oDialog->save_bar_options			= true;
		$oDialog->save_bar_default_option	= 'new';

		return $oDialog;
	}

}
