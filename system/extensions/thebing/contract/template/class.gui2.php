<?php

class Ext_Thebing_Contract_Template_Gui2 extends Ext_TC_Gui2_Data
{

	public static function getDialog(\Ext_Gui2 $oGui)
	{
		$aSchools = Ext_Thebing_Client::getSchoolList(true);
		$oContractTemplate = new Ext_Thebing_Contract_Template();
		$aTypes = $oContractTemplate->getTypeArray();
		$aUsages = $oContractTemplate->getUsageArray();

		$oDialog = $oGui->createDialog($oGui->t('Vertragsvorlage editieren').' "{name}"', $oGui->t('Neue Vertragsvorlage anlegen'));
		$oDialog->width	= 900;
		$oDialog->height = 650;

		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;
		$oDialog->save_bar_default_option = 'open';

		$oDialog->setElement($oDialog->createRow($oGui->t('Bezeichnung'), 'input', array('db_alias' => 'kcontt', 'db_column'=>'name','required' => 1)));
		$oDialog->setElement($oDialog->createRow($oGui->t('Nummer'), 'input', array('db_alias' => 'kcontt', 'db_column'=>'number','required' => 1)));

		$aAdditionalPlaceholders = array(
			'Fortlaufende Nummer pro Partner - %partner_counter',
			'Fortlaufende Nummer - %contract_counter',
			'Nummer des Rahmenvertrages - %basic_contract',
		);

		$oNotification = Ext_TC_Util::getDateFormatDescription($oDialog, null, $aAdditionalPlaceholders);
		$oDialog->setElement($oNotification);

		$oDialog->setElement($oDialog->createRow($oGui->t('Art'), 'select', array('db_alias' => 'kcontt', 'db_column'=>'type', 'select_options' => \Util::addEmptyItem($aTypes), 'required' => true)));
		$oDialog->setElement($oDialog->createRow($oGui->t('Verwendung'), 'select', array('db_alias' => 'kcontt', 'db_column'=>'usage', 'select_options' => \Util::addEmptyItem($aUsages), 'required' => true)));
		$oDialog->setElement($oDialog->createRow($oGui->t('Schulen'), 'select', array('db_alias' => 'kcontt', 'db_column'=>'schools', 'select_options' => $aSchools, 'style'=>'height:110px;', 'multiple'=>5, 'jquery_multiple'=>1, 'required' => true)));

		return $oDialog;
	}

	public static function getOrderby()
	{

		return ['kcontt.name' => 'ASC'];
	}

	public static function getWhere()
	{

		return ['client_id' => \Ext_Thebing_Client::getClientId()];
	}

}