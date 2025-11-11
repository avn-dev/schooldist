<?php

class Ext_Thebing_Transfer_Package_Gui2_Data extends Ext_Thebing_Gui2_Basic_School {

	/**
	 * {@inheritdoc}
	 */
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $sAction='edit', $bPrepareOpenDialog = true) {
		global $_VARS;

		if(
			$aSaveData['individually_transfer'] == 1 &&
			$_VARS['ignore_errors'] != 1
		) {
			$bSave = false;
			$aErrors[] = [
				'message' => $this->_oGui->t('Bitte beachten Sie dass eingegebene Preise und Kosten nun nur noch für zusätzliche Transfere genutzt werden.'),
				'type' => 'hint'
			];
		}

		$aData = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $sAction, $bPrepareOpenDialog);

		if(!empty($aErrors)) {
			$aData['error'] = $aErrors;
			$aData['data']['show_skip_errors_checkbox'] = 1;
		}

		return $aData;
	}
	
	static public function getDialog(\Ext_Gui2 $oGui) {
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$sDescription = 'Thebing » Marketing » Costs';
		$aDays = Ext_Thebing_Transfer_Package::getDayList($sDescription);
		$aLocations = Ext_TS_Transfer_Location::getLocations(true, $oSchool->id);
		$aAccommodationCategories = Ext_Thebing_Data_Accommodation::getAccommodationCategories(true);
		$aAccommodationProviders = $oSchool->getTransferLocations();
		$aCurrency = $oSchool->getSchoolCurrencyList();
		$aAccommodationProvidersWithTransfer = Ext_Thebing_Accommodation::getAllProvidersWithTransferOption($oSchool);
		$aSaisonsPrices = $oSchool->getSaisonList(true, true);
		$aSaisonsCost = $oSchool->getSaisonList(true, false, false, true);

		$oDialog = $oGui->createDialog('{name}', L10N::t('Neues Paket', $sDescription));
		$oDialog->width = 950;

		$oTab = $oDialog->createTab(L10N::t('Details', $sDescription));

		$oTab->setElement($oDialog->createRow(L10N::t('Name', $sDescription), 'input', array('db_column' => 'name', 'required' => 1)));
		$oTab->setElement($oDialog->createRow(L10N::t('Preispacket', $sDescription), 'checkbox', array('db_column' => 'price_package')));
		$oTab->setElement($oDialog->createRow(L10N::t('Kostenpacket', $sDescription), 'checkbox', array('db_column' => 'cost_package')));
		$oTab->setElement($oDialog->createRow(L10N::t('Individueller Transfer', $sDescription), 'checkbox', array('db_column' => 'individually_transfer')));
		$oTab->setElement($oDialog->createRow(L10N::t('Gültig von (hh:mm)', $sDescription), 'input', array('db_column' => 'time_from', 'value' => '00:00', 'required' => 1, 'format' => new Ext_Thebing_Gui2_Format_Time())));
		$oTab->setElement($oDialog->createRow(L10N::t('Gültig bis (hh:mm)', $sDescription), 'input', array('db_column' => 'time_until', 'value' => '23:59', 'required' => 1, 'format' => new Ext_Thebing_Gui2_Format_Time())));
		$oTab->setElement($oDialog->createRow(L10N::t('Währung', $sDescription), 'select', array( 'db_column' => 'currency_id', 'select_options' => $aCurrency, 'required' => 1)));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Buchhaltung'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Kostenstelle'), 'input', array( 'db_column' => 'cost_center')));

		$oDialog->setElement($oTab);
		$oTab = $oDialog->createTab(L10N::t('Preis/Kosten', $sDescription));

		$oTab->setElement($oDialog->createRow(L10N::t('Preis', $sDescription), 'input', array('db_column' => 'amount_price', 'db_alias'=>'ktrp', 'format' => new Ext_Thebing_Gui2_Format_Amount())));
		$oTab->setElement($oDialog->createRow(L10N::t('Preis Hin & Zurück', $sDescription), 'input', array('db_column' => 'amount_price_two_way', 'db_alias'=>'ktrp', 'format' => new Ext_Thebing_Gui2_Format_Amount())));
		$oTab->setElement($oDialog->createRow(L10N::t('Preis - Saisons', $sDescription), 'select', array( 'db_column' => 'join_saisons_prices', 'select_options' => $aSaisonsPrices, 'jquery_multiple' => 1, 'searchable' => 1, 'multiple' => 4)));
		$oTab->setElement($oDialog->createRow(L10N::t('Kosten', $sDescription), 'input', array('db_column' => 'amount_cost', 'db_alias'=>'ktrp', 'format' => new Ext_Thebing_Gui2_Format_Amount())));
		$oTab->setElement($oDialog->createRow(L10N::t('Kosten - Saisons', $sDescription), 'select', array( 'db_column' => 'join_saisons_costs', 'select_options' => $aSaisonsCost, 'jquery_multiple' => 1, 'searchable' => 1, 'multiple' => 4)));

		$oDialog->setElement($oTab);
		$oTab = $oDialog->createTab(L10N::t('Einstellungen', $sDescription));

		$oTab->setElement($oDialog->createRow(L10N::t('Wochentage', $sDescription), 'select', array( 'db_column' => 'join_days', 'jquery_multiple' => 1, 'searchable' => 1, 'multiple' => 8, 'select_options' => $aDays, 'required' => 1)));
		$oTab->setElement($oDialog->createRow(L10N::t('Transfer - Anbieter', $sDescription), 'select', array( 'db_column' => 'join_providers_transfer', 'jquery_multiple' => 1, 'searchable' => 1, 'multiple' => 4, 'selection' => new Ext_Thebing_Transfer_Package_Gui2_Selection_Provider)));
		$oTab->setElement($oDialog->createRow(L10N::t('Transfer - Unterkunftsanbieter', $sDescription), 'select', array('db_column' => 'join_providers_accommodation', 'jquery_multiple' => 1, 'searchable' => 1, 'multiple' => 4, 'select_options' => $aAccommodationProvidersWithTransfer)));

		$oH3 = new Ext_Gui2_Html_H4();
		$oH3->setElement(L10N::t('Transfer - Abfahrt', $sDescription));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow(L10N::t('Transfer - Orte', $sDescription), 'select', array( 'db_column' => 'join_from_locations', 'jquery_multiple' => 1, 'searchable' => 1, 'multiple' => 4, 'select_options' => $aLocations)));
		$oTab->setElement($oDialog->createRow(L10N::t('Unterkunft - Kategorien', $sDescription), 'select', array( 'db_column' => 'join_from_accommodation_categories', 'jquery_multiple' => 1, 'searchable' => 1, 'multiple' => 4, 'select_options' => $aAccommodationCategories)));
		$oTab->setElement($oDialog->createRow(L10N::t('Unterkunft - Anbieter', $sDescription), 'select', array( 'db_column' => 'join_from_accommodation_providers', 'jquery_multiple' => 1, 'searchable' => 1, 'multiple' => 4, 'select_options' => $aAccommodationProviders)));

		$oH3 = new Ext_Gui2_Html_H4();
		$oH3->setElement(L10N::t('Transfer - Ziel', $sDescription));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow(L10N::t('Transfer - Orte', $sDescription), 'select', array( 'db_column' => 'join_to_locations', 'jquery_multiple' => 1, 'searchable' => 1, 'multiple' => 4, 'select_options' => $aLocations)));
		$oTab->setElement($oDialog->createRow(L10N::t('Unterkunft - Kategorien', $sDescription), 'select', array( 'db_column' => 'join_to_accommodation_categories', 'jquery_multiple' => 1, 'searchable' => 1, 'multiple' => 4, 'select_options' => $aAccommodationCategories)));
		$oTab->setElement($oDialog->createRow(L10N::t('Unterkunft - Anbieter', $sDescription), 'select', array( 'db_column' => 'join_to_accommodation_providers', 'jquery_multiple' => 1, 'searchable' => 1, 'multiple' => 4, 'select_options' => $aAccommodationProviders)));

		$oDialog->setElement($oTab);

		if(Ext_Thebing_Access::hasRight('thebing_pickup_resources_packages_documents')) {
			$oTab = $oDialog->createTab($oGui->t('Dokumente'));
			$oDialog->setElement($oTab);

			$oTab->setElement($oTab->getDialog()->createRow($oGui->t('Vorlagen'), 'select', [
				'db_column' => 'pdf_templates',
				'selection' => new Ext_TS_Gui2_Selection_Service_PdfTemplate('transfer'),
				'multiple' => 5,
				'jquery_multiple' => true,
				'searchable' => true,
				'style' => 'height: 105px;',
				'dependency' => [
					[
						'db_alias' => '',
						'db_column' => 'schools',
					],
				],
			]));
		}

		$oDialog->save_as_new_button = true;
		
		return $oDialog;
	}
	
	static public function getOrderby(){

		return [
			'created' => 'DESC'
		];
	}
	
	static public function getSeasonSelectOptions(){

		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aSaisons = $oSchool->getSaisonList(true, true, false, true);
		return $aSaisons;
	}
	
	static public function getTransferSelectOptions(){

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$aProviders = $oSchool->getTransferProvider(true);
		return $aProviders;
	}
	

}