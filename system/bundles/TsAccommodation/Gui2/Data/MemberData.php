<?php

namespace TsAccommodation\Gui2\Data;

class MemberData extends \Ext_Thebing_Gui2_Data {

	public static function getDialog(\Ext_Gui2 $oGui) {

		$aGenders = \Ext_TC_Util::getPersonTitles();

		$oDialog = $oGui->createDialog($oGui->t('Zugehöriger "{firstname}" editieren'), $oGui->t('Neuen Zugehörigen anlegen'));

		$oDialog->aOptions['section'] = 'accommodation_providers_members';

		$oDialog->setElement($oDialog->createRow($oGui->t('Anrede'), 'select', array(
			'db_alias' => 'tc_c',
			'db_column' => 'gender',
			'required' => 1,
			'select_options' => $aGenders,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Vorname'), 'input', array(
			'db_column' => 'firstname',
			'db_alias' => 'tc_c',
			'required' => true,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Nachname'), 'input', array(
			'db_alias' => 'tc_c',
			'db_column' => 'lastname',
			'required' => 1,
		)));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Kontaktdaten'));
		$oDialog->setElement($oH3);

		$oDialog->setElement($oDialog->createRow($oGui->t('Geburtstag'), 'calendar', array(
			'db_alias' => 'tc_c',
			'db_column' => 'birthday',
			'required' => 1,
			'format' => new \Ext_Thebing_Gui2_Format_Date(),
			'display_age' => true
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Telefon'), 'input', array(
			'db_alias' => 'tc_c',
			'db_column' => 'detail_phone_private',
			'required' => 0,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('E-Mail'), 'input', array(
			'db_alias' => 'tc_c',
			'db_column' => 'email'
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Fax'), 'input', array(
			'db_alias' => 'tc_c',
			'db_column' => 'detail_fax',
			'required' => 0,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Skype'), 'input', array(
			'db_alias' => 'tc_c',
			'db_column' => 'detail_skype',
			'required' => 0,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Kommentar'), 'textarea', array(
			'db_alias' => 'tc_c',
			'db_column' => 'detail_comment',
			'required' => 0,
		)));

		return $oDialog;

	}

}
