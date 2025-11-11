<?php

class Ext_Thebing_Insurances_Gui2_Provider extends Ext_Thebing_Gui2_Data
{
	protected function _getErrorMessage($sError, $sField='', $sLabel='', $sAction = null, $sAdditional = null)
	{
		switch($sError)
		{
			case 'EXISTING_JOINED_ITEMS':
			{
				$sMessage = L10N::t('Dieser Eintrag ist noch mit Versicherungen verknüpft.', $this->_oGui->gui_description);

				return $sMessage;

				break;
			}
			default:
				return parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}
	}
	static public function getOrderby() {

//			$oGui->addDefaultColumns();

		return ['kinsp.company' => 'ASC'];
	}
	
	static public function getDialog(\Ext_Gui2 $oGui) {
		
		$oDialog = $oGui->createDialog($oGui->t('Anbieter "{company}"'), $oGui->t('Neuer Anbieter'));

		$aCountries	= Ext_Thebing_Data::getCountryList();
		$aCountries	= array_merge(array('' => ''), $aCountries);

		$oDiv = $oDialog->createRow(L10N::t('Firma', $oGui->gui_description), 'input', array('db_alias'=>'kinsp', 'db_column' => 'company', 'required' => 1));
		$oDialog->setElement($oDiv);
		$oDiv = $oDialog->createRow(L10N::t('Ansprechpartner', $oGui->gui_description), 'input', array('db_alias'=>'kinsp', 'db_column' => 'contact'));
		$oDialog->setElement($oDiv);
		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Adresse'));
		$oDialog->setElement($oH3);
		$oDiv = $oDialog->createRow(L10N::t('Adresse', $oGui->gui_description), 'input', array('db_alias'=>'kinsp', 'db_column' => 'address1'));
		$oDialog->setElement($oDiv);
		$oDiv = $oDialog->createRow(L10N::t('Adresszusatz', $oGui->gui_description), 'input', array('db_alias'=>'kinsp', 'db_column' => 'address2'));
		$oDialog->setElement($oDiv);
		$oDiv = $oDialog->createRow(L10N::t('PLZ', $oGui->gui_description), 'input', array('db_alias'=>'kinsp', 'db_column' => 'zip'));
		$oDialog->setElement($oDiv);
		$oDiv = $oDialog->createRow(L10N::t('Ort', $oGui->gui_description), 'input', array('db_alias'=>'kinsp', 'db_column' => 'city'));
		$oDialog->setElement($oDiv);
		$oDiv = $oDialog->createRow(L10N::t('Land', $oGui->gui_description), 'select', array('db_alias'=>'kinsp', 'db_column' => 'country', 'select_options' => $aCountries));
		$oDialog->setElement($oDiv);
		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Kontaktdaten'));
		$oDialog->setElement($oH3);
		$oDiv = $oDialog->createRow(L10N::t('Telefon', $oGui->gui_description), 'input', array('db_alias'=>'kinsp', 'db_column' => 'phone1'));
		$oDialog->setElement($oDiv);
		$oDiv = $oDialog->createRow(L10N::t('Telefon 2', $oGui->gui_description), 'input', array('db_alias'=>'kinsp', 'db_column' => 'phone2'));
		$oDialog->setElement($oDiv);
		$oDiv = $oDialog->createRow(L10N::t('Fax', $oGui->gui_description), 'input', array('db_alias'=>'kinsp', 'db_column' => 'fax'));
		$oDialog->setElement($oDiv);
		$oDiv = $oDialog->createRow(L10N::t('Skype', $oGui->gui_description), 'input', array('db_alias'=>'kinsp', 'db_column' => 'skype_id'));
		$oDialog->setElement($oDiv);
		$oDiv = $oDialog->createRow(L10N::t('E-Mail', $oGui->gui_description), 'input', array('db_alias'=>'kinsp', 'db_column' => 'email', 'required' => 1));
		$oDialog->setElement($oDiv);
		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Sonstiges'));
		$oDialog->setElement($oH3);
		$oDiv = $oDialog->createRow(L10N::t('Internetseite', $oGui->gui_description), 'input', array('db_alias'=>'kinsp', 'db_column' => 'homepage'));
		$oDialog->setElement($oDiv);

		return $oDialog;
	}
	
}
