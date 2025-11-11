<?php

namespace TcApi\Gui2\Data;

class ApiTokenData extends \Ext_TC_Gui2_Data {

	/**
	 *
	 * @param \Ext_Gui2 $oGui
	 */
	public static function getDialog($oGui){

		$oDialog = $oGui->createDialog($oGui->t('API Token "{token}"'), $oGui->t('Neuer API Token {token}'));

		$aApplications = \Factory::executeStatic('Ext_TC_WDMVC_Token', 'getApplications');

		$oDialog->setElement($oDialog->createRow($oGui->t('Token'), 'input', array(
			'db_alias' => '',
			'db_column' => 'token',
			'required' => true,
			'readonly' => 'readonly',
			'disabled' => false
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Bereich'), 'select', array(
			'db_alias' => '',
			'db_column' => 'applications',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'select_options' => $aApplications,
			'searchable' => 1,
			'required' => 1
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('IPs (komma getrennt)'), 'textarea', array(
			'db_alias' => '',
			'db_column' => 'ips_concat'
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Parameter'), 'textarea', array(
			'db_alias' => '',
			'db_column' => 'parameters'
		)));

		return $oDialog;
	}


}
