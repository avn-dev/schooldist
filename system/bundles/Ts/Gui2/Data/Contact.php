<?php

namespace Ts\Gui2\Data;

class Contact extends \Ext_Thebing_Gui2_Data
{
	/**
	 * {@inheritdoc}
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave = true, $sAction = 'edit', $bPrepareOpenDialog = true)
	{
		$aTransfer = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		foreach((array)$aTransfer['error'] as $iKey => $mError) {
			if(
				is_array($mError) &&
				$mError['input']['dbalias'] === 'contacts_to_emailaddresses' &&
				$mError['input']['dbcolumn'] === 'email'
			) {
				$aTransfer['error'][$iKey]['error_id'] = '';
				$aTransfer['error'][$iKey]['input']['dbalias'] = '';
			}
		}

		return $aTransfer;

	}

	public static function getDialog(\Ext_Gui2 $oGui)
	{
		$aGenders = \Ext_TC_Util::getPersonTitles();

		$oDialog = $oGui->createDialog($oGui->t('Kontakt "{firstname}" editieren'), $oGui->t('Neuen Kontakt anlegen'));

		$oDialog->aOptions['section'] = 'admin_contacts';

		$oRow = new \Ext_Gui2_Html_Div();
		$oRow->class = 'row';

		$oColLeft = new \Ext_Gui2_Html_Div();
		$oColLeft->class = 'col-md-6';
		
		$oColLeft->setElement($oDialog->createRow($oGui->t('Anrede'), 'select', array(
			'db_column' => 'gender',
			'required' => 1,
			'select_options' => $aGenders,
		)));
		
		$oColLeft->setElement($oDialog->createRow($oGui->t('Vorname'), 'input', array(
			'db_column' => 'firstname',
			'db_alias' => 'cdb1',
			'required' => true,
		)));

		$oColLeft->setElement($oDialog->createRow($oGui->t('Nachname'), 'input', array(
			'db_column' => 'lastname',
			'db_alias' => 'cdb1',
			'required' => 1,
		)));

		$oColLeft->setElement($oDialog->createRow($oGui->t('Geburtstag'), 'calendar', array(
			'db_column' => 'birthday',
			'db_alias' => 'cdb1',
			'format' => new \Ext_Thebing_Gui2_Format_Date()
		)));

		$oColLeft->setElement($oDialog->createRow($oGui->t('E-Mail'), 'input', array(
			'db_column' => 'email',
			'required' => 0,
		)));

		$oRow->setElement($oColLeft);

		$oColRight = new \Ext_Gui2_Html_Div();
		$oColRight->class = 'col-md-6';
		
		$oColRight->setElement($oDialog->createRow($oGui->t('Telefon'), 'input', array(
			'db_column' => 'detail_phone_private',
			'required' => 0,
		)));

		$oColRight->setElement($oDialog->createRow($oGui->t('Telefon Mobil'), 'input', array(
			'db_column' => 'detail_phone_mobile',
			'required' => 0,
		)));

		$oColRight->setElement($oDialog->createRow($oGui->t('Telefon Büro'), 'input', array(
			'db_column' => 'detail_phone_office',
			'required' => 0,
		)));

		$oColRight->setElement($oDialog->createRow($oGui->t('Fax'), 'input', array(
			'db_column' => 'detail_fax',
			'required' => 0,
		)));

		$oColRight->setElement($oDialog->createRow($oGui->t('Skype'), 'input', array(
			'db_column' => 'detail_skype',
			'required' => 0,
		)));
		
		$oRow->setElement($oColRight);
		
		$oDialog->setElement($oRow);

		if($oGui->getOption('dialog_addresses', true)) {

			$oH3 = $oDialog->create('h4');
			$oH3->setElement($oGui->t('Adressen'));
			$oDialog->setElement($oH3);

			$oAddressContainer = $oDialog->createJoinedObjectContainer('contacts_to_addresses', ['min' => 0, 'max' => 10]);
			$oAddressContainer->add_label = 'Adresse hinzufügen';
			$oAddressContainer->remove_label = 'Adresse entfernen';

			$oAddressContainer->setElement(
				$oAddressContainer->createRow(
					$oGui->t('Firma'),
					'input',
					[
						'db_alias' => 'tc_a',
						'db_column' => 'company',
						'required' => 0,
					]
				)
			);

			$oAddressContainer->setElement(
				$oAddressContainer->createRow(
					$oGui->t('Straße'),
					'input',
					[
						'db_alias' => 'tc_a',
						'db_column' => 'address',
						'required' => 0,
					]
				)
			);

			$oAddressContainer->setElement($oAddressContainer->createRow($oGui->t('Adresszusatz'), 'input', array(
				'db_alias' => 'tc_a',
				'db_column' => 'address_addon'
			)));

			$oAddressContainer->setElement($oAddressContainer->createRow($oGui->t('PLZ'), 'input', array(
				'db_alias' => 'tc_a',
				'db_column' => 'zip'
			)));

			$oAddressContainer->setElement($oAddressContainer->createRow($oGui->t('Stadt'), 'input', array(
				'db_alias' => 'tc_a',
				'db_column' => 'city'
			)));

			$oAddressContainer->setElement($oAddressContainer->createRow($oGui->t('Bundesland'), 'input', array(
				'db_alias' => 'tc_a',
				'db_column' => 'state'
			)));

			$aCountries = \Ext_Thebing_Data::getCountryList(true, true);
			$oAddressContainer->setElement($oAddressContainer->createRow($oGui->t('Land'), 'select', array(
				'db_alias' => 'tc_a',
				'db_column' => 'country_iso',
				'select_options' => $aCountries
			)));

			$oDialog->setElement($oAddressContainer);

		}

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Sonstiges'));
		$oDialog->setElement($oH3);

		$oDialog->setElement($oDialog->createRow($oGui->t('Kommentar'), 'textarea', array(
			'db_column' => 'detail_comment',
			'required' => 0,
		)));

		return $oDialog;
	}

	public static function getOrderby()
	{
		return [
			'cdb1.lastname' => 'ASC',
		];
	}

	static public function getTypeOptions(\Ext_Thebing_Gui2 $oGui)
	{
		
		$aOptions = [
			'accommodation' => $oGui->t('Unterkunft'),
			'enquiry' => $oGui->t('Anfrage'),
			'booking' => $oGui->t('Buchung')
		];
		
		asort($aOptions);
		
		$aOptions = \Ext_Thebing_Util::addEmptyItem($aOptions);
		
		return $aOptions;
	}
	
}
