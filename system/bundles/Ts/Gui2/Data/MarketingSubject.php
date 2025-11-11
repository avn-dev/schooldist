<?php

namespace Ts\Gui2\Data;

class MarketingSubject extends \Ext_Thebing_Gui2_Data
{
	
	static public function getOrderby()
	{
		return ['title' => 'ASC'];
	}

	static public function getWhere()
	{
		return ['client_id' => \Ext_Thebing_Client::getClientId()];
	}

	static public function getDialog(\Ext_Thebing_Gui2 $oGui)
	{
		$oDialog = $oGui->createDialog($oGui->t('Betreff "{title}" editieren'),
									   $oGui->t('Neuen Betreff anlegen'));
		$oDialog->width = 900;
		$oDialog->height = 650;

		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;
		$oDialog->save_bar_default_option = 'new';

		$oDialog->setElement($oDialog->createRow($oGui->t('Titel'), 'input', array(
				'db_alias'			=> '',
				'db_column'			=> 'title',
				'required'			=> 1,
		)));

		return $oDialog;
	}

}

