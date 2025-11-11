<?php

namespace Ts\Gui2\Data;

class AgencyList extends \Ext_Thebing_Gui2_Data
{
	
	static public function getOrderby()
	{
		return['created' => 'DESC'];
	}

	static public function getWhere()
	{
		return ['client_id' => \Ext_Thebing_Client::getClientId()];
	}

	static public function getDialog(\Ext_Thebing_Gui2 $oGui)
	{
		
		$aAgencies = \Ext_Thebing_Client::getFirstClient()->getAgencies(true);
		
		$oDialog = $oGui->createDialog(
			$oGui->t('Agenturliste "{name}" editieren'),
			$oGui->t('Neue Agenturliste anlegen')
		);

		$oDialog->width		= 900;
		$oDialog->height	= 650;

		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;
		$oDialog->save_bar_default_option = 'new';

		$oDialog->setElement($oDialog->createRow(
				$oGui->t('Bezeichnung'), 'input', array('db_alias'=>'', 'db_column'=>'name', 'required'=>true))
		);

		$oDialog->setElement($oDialog->createRow(
				$oGui->t('Beschreibung'), 'textarea', array('db_alias'=>'', 'db_column'=>'description'))
		);

		$oDialog->setElement($oDialog->createRow(
				$oGui->t('Agenturen'), 'select', array( 'db_alias' => '','db_column'=>'join_agencies', 'select_options' => $aAgencies, 'multiple'=>6, 'jquery_multiple'=>1, 'searchable' => true))
		);

		return $oDialog;
	}

}
