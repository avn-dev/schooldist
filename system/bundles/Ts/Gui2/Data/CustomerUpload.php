<?php

namespace Ts\Gui2\Data;

use Tc\Traits\Gui2\Dialog\WithAccessMatrix;
use Ts\Gui2\Data\CustomerUpload\AccessMatrix;

class CustomerUpload extends \Ext_Thebing_Gui2_Data
{
	use WithAccessMatrix;

	protected function getAccessMatrix(): \Ext_TC_Access_Matrix
	{
		return new AccessMatrix();
	}

	public static function getOrderby()
	{
		return['name' => 'ASC'];
	}

	public static function getDialog(\Ext_Thebing_Gui2 $oGui)
	{

		$aSchools = \Ext_Thebing_Client::getSchoolList(true);

		//Dialog
		$oDialog = $oGui->createDialog(
			$oGui->t('Uploadfeld "{name}" editieren'),
			$oGui->t('Neues Uploadfeld anlegen')
		);

		$oDialog->width		= 900;
		$oDialog->height	= 650;

		$oDialog->setElement($oDialog->createRow(
				$oGui->t('Name'), 'input', array('db_alias' => '', 'db_column'=>'name','required' => 1))
		);

		$oDialog->setElement($oDialog->createRow($oGui->t('Schulen'), 'select', [
			'db_column' => 'schools',
			'multiple' => 5,
			'select_options' => $aSchools,
			'jquery_multiple' => 1,
			'required' => true
		]));

		if(\Ext_Thebing_Access::hasRight('thebing_release_documents_sl')) {
			$oDialog->setElement($oDialog->createRow($oGui->t('Standard: Freigegeben für Schüler-App'), 'checkbox', ['db_column' => 'release_sl']));
		}

		return $oDialog;
	}

}