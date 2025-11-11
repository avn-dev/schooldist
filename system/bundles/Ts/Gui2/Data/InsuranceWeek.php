<?php
namespace Ts\Gui2\Data;

class InsuranceWeek extends \Ext_Thebing_Gui2_Data
{
	
	static public function getOrderby()
	{
		return ['kinsw.title' => 'ASC'];
	}
	
	static public function getListWhere()
	{

		$oClient = \Ext_Thebing_Client::getFirstClient();

		$aWhere = ['active' => 1];
		if($oClient->insurance_price_method == 1) {
			$aWhere['startweek'] = array('!=', 0);
		} else {
			$aWhere['startweek'] = array('=', 0);
		}

		return $aWhere;
	}

	static public function getDialog(\Ext_Thebing_Gui2 $oGui)
	{
		$oClient = \Ext_Thebing_Client::getFirstClient();
		$oDialog = $oGui->createDialog($oGui->t('Woche "{title}"'), $oGui->t('Neue Woche')
		);
		$oDiv = $oDialog->createRow($oGui->t(
				'Bezeichnung'), 
				'input', array(
					'db_column' => 'title', 
					'required' => 1));
		$oDialog->setElement($oDiv);

		if($oClient->insurance_price_method == 0) {
			$oDiv = $oDialog->createRow($oGui->t(
					'Wochenanzahl'), 
					'input', array(
						'db_column' => 'weeks', 
						'required' => 1)
			);
			$oDialog->setElement($oDiv);
		} else {
			$oDiv = $oDialog->createRow($oGui->t(
					'Startwoche'), 
					'input', array(
						'db_column' => 'startweek', 
						'required' => 1));
			$oDialog->setElement($oDiv);
			$oDiv = $oDialog->createRow($oGui->t(
					'Wochenanzahl'), 
					'input', array(
						'db_column' => 'weeks')
			);
			$oDialog->setElement($oDiv);
		}
		$oDiv = $oDialog->createRow($oGui->t(
				'Extrawoche'), 
				'checkbox', array(
					'db_column' => 'extra')
		);
		$oDialog->setElement($oDiv);
		
		return $oDialog;

	}
	
}
