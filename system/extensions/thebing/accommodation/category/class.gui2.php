<?php

class Ext_Thebing_Accommodation_Category_Gui2 extends Ext_Thebing_Gui2_Data {

	/**
	 * {@inheritdoc}
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null)  {

		if($sError == 'JOURNEY_ACCOMMODATIONS_EXISTS') {
			$sErrorMessage = $this->t('Es existieren noch Buchungen zu dieser Unterkunftskategorie.');
		} else {
			$sErrorMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sErrorMessage;

	}

	static public function getPriceOptions() {

		$oGui = new Ext_Thebing_Gui2;
		$oGui->gui_description = 'Thebing » Accommodation » Categories';
		
		$aPriceOptions = [
			Ext_Thebing_Accommodation_Amount::PRICE_PER_WEEK => $oGui->t('Preis pro Woche'),
			Ext_Thebing_Accommodation_Amount::PRICE_PER_NIGHT => $oGui->t('Preis pro Nacht'),
			Ext_Thebing_Accommodation_Amount::PRICE_PER_NIGHT_WEEKS => $oGui->t('Preis pro Nacht (Wochenstruktur)')
		];
		
		return $aPriceOptions;
	}
	
	static public function getOrderby() {

		$sDefaultLang = \Ext_Thebing_Util::getInterfaceLanguage();

		return ['kac.name_'.$sDefaultLang => 'ASC'];
	}
	
	static public function getDialog(\Ext_Gui2 $oGui) {

		$sDefaultLang = \Ext_Thebing_Util::getInterfaceLanguage();
		$aTranslationLanguages	= Ext_Thebing_Util::getTranslationLanguages();
		
		$aMatchingTypes = \Ext_Thebing_Accommodation_Category::getTypeOptions($oGui->getLanguageObject());
		$aSchools = Ext_Thebing_Client::getSchoolList(true);

		$aRequirements = \TsAccommodation\Entity\Requirement::getSelectOptions();

		$oDialog = $oGui->createDialog(
			sprintf($oGui->t('Kategorie "%s" editieren'), '{name_'.$sDefaultLang.'}'),
			L10N::t('Neue Kategorie anlegen', $oGui->gui_description)
		);

		$oDialog->width = 900;
		$oDialog->height = 650;
		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;
		$oDialog->save_bar_default_option = 'new';

		$oTab = $oDialog->createTab($oGui->t('Daten'));
		$oTab->aOptions['section'] = 'accommodations';

		$oDialog->setElement($oTab);

		$oTab->setElement($oDialog->createI18NRow($oGui-> t('Bezeichnung', $oGui->gui_description), [
			'db_column_prefix' => 'name_',
			'db_alias' => 'kac',
			'required' => true
		], $aTranslationLanguages));

		$oTab->setElement($oDialog->createI18NRow($oGui-> t('Kürzel', $oGui->gui_description), [
			'db_column_prefix' => 'short_',
			'db_alias' => 'kac',
			'required' => true
		], $aTranslationLanguages));

		$oTab->setElement(
			$oDialog->createRow(
				L10N::t('Kategorietyp', $oGui->gui_description),
				'select',
				[
					'db_alias' => 'kac',
					'db_column' => 'type_id',
					'select_options' => Ext_Thebing_Util::addEmptyItem($aMatchingTypes, '', ''),
					'required' => true
				]
			)
		);

		$oSchoolSettingsContainer = $oDialog->createJoinedObjectContainer('school_settings');

		$oSchoolSettingsContainer->setElement(
			$oSchoolSettingsContainer->createRow(
				$oGui->t('Schulen'),
				'select',
				[
					'db_alias' => 'ts_acs',
					'db_column' => 'schools',
					'multiple' => 5,
					'select_options' => $aSchools,
					'jquery_multiple' => 1,
					'searchable' => 1,
					'required' => 1,
				]
			)
		);

		$aPriceOptions = Ext_Thebing_Accommodation_Category_Gui2::getPriceOptions();

		$oSchoolSettingsContainer->setElement(
			$oSchoolSettingsContainer->createRow(
				L10N::t('Preise je Nacht', $oGui->gui_description),
				'select',
				[
					'db_alias' => 'ts_acs',
					'db_column' => 'price_night',
					'select_options' => Ext_Thebing_Util::addEmptyItem($aPriceOptions, '', ''),
				]
			)
		);

		$oSchoolSettingsContainer->setElement(
			$oSchoolSettingsContainer->createRow(
				L10N::t('Wochen', $oGui->gui_description),
				'select',
				[
					'db_alias' => 'ts_acs',
					'db_column' => 'weeks',
					'select_options' => [],
					'selection' => new Ext_Thebing_Gui2_Selection_School_Week(),
					'style' => 'height: 110px;',
					'multiple' => 5,
					'jquery_multiple' => 1,
					'dependency' => [
						[
							'db_alias' => 'ts_acs', 
							'db_column' => 'schools',
						],
					],
					'dependency_visibility' => [
						'db_alias' => 'ts_acs',
						'db_column' => 'price_night',
						'on_values' => [Ext_Thebing_Accommodation_Amount::PRICE_PER_WEEK, Ext_Thebing_Accommodation_Amount::PRICE_PER_NIGHT_WEEKS]
					]
				]
			)
		);

		$oTab->setElement($oSchoolSettingsContainer);

		$oH3 = $oDialog->create('h4');
		$oH3->setElement(L10N::t('Check-in', $oGui->gui_description));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Starttag der Unterkunftswoche'), 'select', [
			'db_column' => 'accommodation_start',
			'db_alias' => 'kac',
			'select_options' => \Ext_Thebing_Util::addEmptyItem(\Ext_Thebing_School_Gui2::getAccommodationStartDays(), $oGui->t('Einstellung der Schule verwenden'), ''),
			'format' => new \Ext_Gui2_View_Format_Null(),
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Anzahl Nächte der letzten Unterkunftswoche'), 'select', [
			'db_column' => 'inclusive_nights',
			'db_alias' => 'kac',
			'select_options' => \Ext_Thebing_Util::addEmptyItem(\Ext_Thebing_School_Gui2::getAccommodationInclusiveNightOptions(), $oGui->t('Einstellung der Schule verwenden'), ''),
			'format' => new \Ext_Gui2_View_Format_Null(),
		]));

		$oTab->setElement(
			$oDialog->createRow(
				L10N::t('Einzugszeit ( hh:mm )', $oGui->gui_description),
				'input',
				[
					'db_alias' => 'kac',
					'db_column' => 'arrival_time',
					'format' => new Ext_Thebing_Gui2_Format_Time(),
				]
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				L10N::t('Auszugszeit ( hh:mm )', $oGui->gui_description),
				'input',
				[
					'db_alias' => 'kac',
					'db_column' => 'departure_time',
					'format' => new Ext_Thebing_Gui2_Format_Time(),
				]
			)
		);

		$oH3 = $oDialog->create('h4');
		$oH3->setElement(L10N::t('Voraussetzungen', $oGui->gui_description));
		$oTab->setElement($oH3);
		$oTab->setElement(
			$oDialog->createRow(
				L10N::t('Voraussetzungen', $oGui->gui_description),
				'select',
				[
					'db_alias' => '',
					'db_column' => 'requirements',
					'select_options' => $aRequirements,
					'style' => 'height: 60px;',
					'multiple' => 5,
					'jquery_multiple' => 1,
				]
			)
		);

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Buchhaltung'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Kostenstelle'), 'input', array( 'db_column' => 'cost_center', 'db_alias' => 'kac')));

		$oTab = $oDialog->createTab($oGui->t('Frontend'));
		$oDialog->setElement($oTab);

		$oTab->setElement(
			$oDialog->createI18NRow(
				$oGui-> t('Beschreibung', $oGui->gui_description),
				[
					'db_column_prefix' => 'description_',
					'db_alias' => ''
				],
				$aTranslationLanguages
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Maximale Extranächte vorher', $oGui->gui_description),
				'input',
				[
					'db_alias' => 'kac',
					'db_column' => 'max_extra_nights_prev',
				]
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Maximale Extrannächte nachher', $oGui->gui_description),
				'input',
				[
					'db_alias' => 'kac',
					'db_column' => 'max_extra_nights_after',
				]
			)
		);

		if(Ext_Thebing_Access::hasRight('thebing_accommodation_categories_documents')) {
			$oTab = $oDialog->createTab($oGui->t('Dokumente'));
			$oDialog->setElement($oTab);

			$oTab->setElement($oTab->getDialog()->createRow($oGui->t('Vorlagen'), 'select', [
				'db_column' => 'pdf_templates',
				'selection' => new Ext_TS_Gui2_Selection_Service_PdfTemplate('accommodation'),
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
		
		return $oDialog;
	}
	
	static public function manipulateSearchFilter(\Ext_Gui2 $oGui) {
		
		$defaultLang = \Ext_Thebing_Util::getInterfaceLanguage();
		
		return [
			'column' => [
				'id',
				'name_'.$defaultLang
			]
		];
		
	}
	
}
