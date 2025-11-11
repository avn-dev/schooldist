<?php

namespace Ts\Gui2\Data;

class StudentStatusList extends \Ext_Thebing_Gui2_Data
{
	
	static public function getOrderby()
	{
		return ['position' => 'ASC'];
	}

	static public function getDialog(\Ext_Thebing_Gui2 $oGui)
	{
		$aSchools = \Ext_Thebing_Client::getSchoolList(true);
		$oGui->row_sortable	= true;
		$oDialog = $oGui->createDialog($oGui->t('Schülerstatus editieren').' - {text}',
										$oGui->t('Neuen Schülerstatus anlegen')
		);

		$oDialog->setElement($oDialog->createRow($oGui->t('Bezeichnung'), 'input', array('db_alias' => '', 'db_column'=>'text','required' => 1))
		);

		$oDialog->setElement($oDialog->createRow($oGui->t('Schulen'), 'select', [
			'db_alias' => '',
			'db_column' => 'schools',
			'multiple' => 5,
			'select_options' => $aSchools,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'required' => 1,
		]));

		return $oDialog;
	}

}
