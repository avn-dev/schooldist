<?php

namespace Ts\Gui2\Data;

class AgencyGroup extends \Ext_Thebing_Gui2_Data
{
	
	static public function getOrderby()
	{
		return ['name' => 'ASC'];
	}

	static public function getWhere()
	{
		return ['idClient' => \Ext_Thebing_Client::getClientId()];
	}

	static public function getDialog(\Ext_Thebing_Gui2 $oGui)
	{
		
		$oDialog = $oGui->createDialog(
			$oGui->t('Agenturgruppe "{name}" editieren'),
			$oGui->t('Neue Agenturgruppe anlegen')
		);

		$oDialog->width	= 900;
		$oDialog->height = 650;

		$oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
				'db_alias' => '',
				'db_column'	=> 'name',
				'required' => 1,
		))
		);

		return $oDialog;
	}

}
