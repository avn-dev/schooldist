<?php

namespace Ts\Gui2\Data;

class VisumStatus extends \Ext_Thebing_Gui2_Data
{

	static public function getOrderby()
	{
		return ['kvs.name' => 'ASC'];
	}

	static public function getWhere()
	{
		return ['client_id' => \Ext_Thebing_Client::getClientId(),
			'school_id' => \Core\Handler\SessionHandler::getInstance()->get('sid'), 
			'active' => 1];
	}

	static public function getDialog(\Ext_Thebing_Gui2 $oGui)
	{
		$oDialog = $oGui->createDialog(
				$oGui->t('Visum Typ "{name}" editieren'), 
				$oGui->t('Neuen Visum Typ anlegen'));

		$aFlexFieldOptions = [];
		$aFlexFields = \Ext_TC_Flexibility::getFields('student_record_visum_status');
		foreach($aFlexFields as $oField) {
			if($oField->type != 3) { // Keine Überschrift
				$aFlexFieldOptions[$oField->id] = $oField->title;
			}
		}

		$oDialog->setElement($oDialog->createRow(
				$oGui->t('Name'), 'input', array('db_alias' => 'kvs', 'db_column'=>'name','required' => 1)));
		//$oDialog->setElement($oDialog->createRow(
		//L10N::t('Nummerkreis - Format', $oGui->gui_description), 'input', array('db_alias' => '', 'db_column'=>'numberformat','required' => 1)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Felder'), 'select', [
			'db_alias' => 'kvs',
			'db_column' => 'flex_fields',
			'multiple' => 5,
			'select_options' => $aFlexFieldOptions,
			'jquery_multiple' => 1
		]));

		return $oDialog;
	}

}
