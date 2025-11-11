<?php

class Ext_Thebing_School_Cost_Gui2_Data extends Ext_Thebing_Gui2_Data {

	/**
	 * @see parent
	 * @param string $sAction
	 * @param array $aSelectedIds
	 * @param array $aData
	 * @param string $sAdditional
	 * @param bool $bSave
	 * @return array
	 */
	static public function getOrderby()
    {
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$sInterfaceLanguage	= $oSchool->getInterfaceLanguage();
		
		return ['name_'.$sInterfaceLanguage => 'ASC'];
	}

	static public function getWhere()
    {
		
		return ['idSchool' => Core\Handler\SessionHandler::getInstance()->get('sid')];
	}

	static public function manipulateSearchFilter()
    {
		$frontendLanguage = Factory::executeStatic('Ext_TC_Util', 'getInterfaceLanguage');

		return [
			'column' => [
				'id',
				'name_'.$frontendLanguage
			]
		];
	}

	static public function getDialog(Ext_Thebing_Gui2 $oGui)
    {
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();
		$aTranslationLanguages = Ext_Thebing_Util::getTranslationLanguages();
		$aCourseList = $oSchool->getCourseList();
		$aAccommodations = $oSchool->getAccommodationCombinations();

		$aCostTypes = Ext_Thebing_Price::getCostTypes();

		$aTimes = Ext_Thebing_School_Additionalcost::getTimepointOptions($oGui->getLanguageObject());
		$aGroupOptions = Ext_Thebing_Util::getGroupAdditionalcostOptions();

		// Dialog
		$sTitleEdit = $oGui->t('Kosten "{name}" editieren', $oGui->gui_description);
		$sTitleEdit = str_replace('{name}', '{name_'.$sInterfaceLanguage.'}', $sTitleEdit);

		$oDialog = $oGui->createDialog($sTitleEdit, $oGui->t('Neuen Kosten anlegen'));
		$oDialog->width	= 900;
		$oDialog->height = 650;
		$oDialog->save_as_new_button = true;


		$oTab = $oDialog->createTab($oGui->t('Allgemeines'));
		$oTab->aOptions = array(
		  'access' => '',
		  'section' => 'marketing_additional_costs'
		);

		$oTab->setElement(
			$oDialog->createI18NRow(
				$oGui->t('Bezeichnung'), 
				[
					'db_alias'	=> 'kcos',
					'db_column_prefix'	=> 'name_',
					'required'	=> true
				], 
				$aTranslationLanguages
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Typ'),
				'select',
				array(
					'db_alias'			=> 'kcos',
					'db_column'			=> 'type',
					'select_options'	=> $aCostTypes,
					'events'			=> array(
						array(
							'event'		=>'change',
							'function'	=>'costToggle'
						)
					)
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Kurse'),
				'select',
				array(
					'db_alias'			=> 'kcos',
					'db_column'			=> 'costs_courses',
					'select_options'	=> $aCourseList,
					'multiple'			=> 5,
					'jquery_multiple'	=> 1,
					'style'				=> 'height: 105px;',
					'searchable'		=> 1
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Unterkünfte'),
				'select',
				array(
					'db_alias'			=> 'kcos',
					'db_column'			=> 'costs_accommodations',
					'select_options'	=> $aAccommodations,
					'multiple'			=> 5,
					'jquery_multiple'	=> 1,
					'style'				=> 'height: 105px;',
					'searchable'		=> 1
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Begrenzte Stückzahl'),
				'checkbox',
				[
					'db_alias' => 'kcos',
					'db_column' => 'limited_quantity',
					'class' => 'stock-checkbox',
					'events' => [
						[
							'event' 		=> 'change',
							'function' 		=> 'toggleStockTab'
						]
					]
				]
			)
		);

		$oTab->setElement($oDialog->createRow($oGui->t('Begrenzte Verfügbarkeit'), 'checkbox', [
			'db_column' => 'limited_availability',
			'child_visibility' => [
				[
					'class' => 'availability-tab-btn',
					'on_values' => [
						'1'
					]
				]
			]
		]));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t("Preisberechnung"));
		$oTab->setElement($oH3);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Berechnung'),
				'select',
				array(
					'db_alias'			=> 'kcos',
					'db_column'			=> 'charge',
					'dependency'		=> array(array('db_alias'=>'kcos', 'db_column' => 'type')),
					'selection'			=> new Ext_Thebing_Gui2_Selection_Marketing_Additionalcost_Charge(),
					'required' => true
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Preisberechnung'),
				'select',
				array(
					'db_alias'			=> 'kcos',
					'db_column'			=> 'calculate',
					'dependency'		=> array(array('db_alias'=>'kcos', 'db_column' => 'type')),
					'selection'			=> new Ext_Thebing_Gui2_Selection_Marketing_Additionalcost_Calculate(),
					// 'required' => true // Funktioniert wegen 0 nicht, siehe Ext_Thebing_School_Additionalcost::validate()
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Abhängigkeit von Dauer?'),
				'checkbox',
				array(
					'db_alias'			=> 'kcos',
					'db_column'			=> 'dependency_on_duration',
					'events'			=> array(
						array(
							'event'		=>'change',
							'function'	=>'toggleCalculationSettings'
						)
					),
					'dependency_visibility' => [
						'db_alias' => 'kcos',
						'db_column' => 'charge',
						'on_values' => ['auto']
					],
				)
			)
		);

		$oJoinContainer = $oDialog->createJoinedObjectContainer('calculation_combination', array('min' => 0, 'max' => 10));

		$oJoinContainer->setElement($oJoinContainer->createMultiRow($oGui->t('Nur berechnen, wenn Leistung'), array(
			'db_alias' => 'kcpcc',
			'items' => array(
				array(
					'db_column' => 'symbol',
					'input' => 'select',
					'select_options' => array(
						'<' => '<',
						'=' => '=',
						'>' => '>'
					),
					'text_after' => '&nbsp;',
					'style' => 'width: 60px;'
				),
				array(
					'db_column' => 'factor',
					'input' => 'input',
					'text_after' => ' '.$oGui->t('Wochen'),
					'style' => 'width: 60px;'
				)
			))));

		$oTab->setElement($oJoinContainer);

		$oTab->setElement($oDialog->createRow($oGui->t('Abhängigkeit vom Alter'), 'checkbox', [
			'db_column' => 'dependency_on_age',
			'dependency_visibility' => [
				'db_alias' => 'kcos',
				'db_column' => 'charge',
				'on_values' => ['auto']
			],
			'events' => [
				[
					'event' =>'change',
					'function' =>'toggleCalculationSettings'
				]
			]
		]));

		$oTab->setElement($oDialog->createMultiRow($oGui->t('Nur berechnen wenn Alter'), [
			'db_alias' => 'dependencies_age',
			'input_container' => true,
			'multi_rows' => true,
			'row_class' => 'age_container',
			'items' => [
				[
					'db_column' => 'operator',
					'input' => 'select',
					'select_options' => [
						'<' => '<',
						'=' => '=',
						'>' => '>'
					],
					'text_after' => '&nbsp;',
					'style' => 'width: 60px;',
					'jointable' => true
				],
				[
					'db_column' => 'age',
					'input' => 'input',
					'text_after' => ' '.$oGui->t('Alter'),
					'style' => 'width: 60px;',
					'jointable' => true
				]
			]
		]));

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Anzeigen, wenn Preis 0 ist?'),
				'checkbox',
				array(
					'db_alias'			=> 'kcos',
					'db_column'			=> 'no_price_display',
					'dependency_visibility' => [
						'db_alias' => 'kcos',
						'db_column' => 'charge',
						'on_values' => ['auto']
					],
				)
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Betrag dem Anbieter gutschreiben bei Berechnung'),
				'select',
				[
					'db_alias' => 'kcos',
					'db_column' => 'credit_provider',
					'select_options' => [
						0 => $oGui->t('Nein'),
						Ext_Thebing_School_Additionalcost::CREDIT_PROVIDER_ALL => $oGui->t('Ja, zusätzlich dem Anbieter gutschreiben'),
						Ext_Thebing_School_Additionalcost::CREDIT_PROVIDER_ONLY_PROVIDER => $oGui->t('Ja, ausschliesslich dem Anbieter gutschreiben')
					],
					'dependency_visibility' => [
						'db_alias' => 'kcos',
						'db_column' => 'type',
						'on_values' => ['1']
					],
				]
			)
		);

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Buchhaltung'));
		$oTab->setElement($oH3);

		if(Ext_Thebing_Access::hasLicenceRight('thebing_accounting_booking_stack')) {
			$oTab->setElement($oDialog->createRow($oGui->t('Zeitpunkt'), 'select', array('db_alias' => 'kcos', 'db_column'=>'timepoint', 'select_options' => $aTimes)));
		}

		$oTab->setElement(
			$oDialog->createRow(
				L10N::t('Kostenstelle basierend auf Leistungskategorie', $oGui->gui_description),
				'checkbox',
				[
					'db_alias' => 'kcos',
					'db_column' => 'use_service_category_cost_center',
					'dependency_visibility' => [
						'db_alias' => 'kcos',
						'db_column' => 'type',
						'on_values' => ['0', '1']
					],
				]
			)
		);

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Kostenstelle'),
				'input',
				[
					'db_column'=>'cost_center',
					'db_alias' => 'kcos',
					'dependency_visibility' => [
						'db_alias' => 'kcos',
						'db_column' => 'use_service_category_cost_center',
						'on_values' => ['']
					],
				]
			)
		);

		$oDivGroupsettings	= $oDialog->create('div');
		$oDivGroupsettings->id = 'groupsettings';

		$oH3 = $oDialog->create('h4');
		$oH3 ->setElement(L10N::t('Gruppeneinstellungen', $oGui->gui_description));
		$oDivGroupsettings		->setElement($oH3);

		$oDivGroupsettings->setElement($oDialog->createRow(L10N::t('Generelle Kosten', $oGui->gui_description), 'select', array('db_alias' => '', 'db_column'=>'group_option', 'select_options' => $aGroupOptions)));

		$oTab->setElement($oDivGroupsettings);

		$oDialog->setElement($oTab);

		$oTab = $oDialog->createTab($oGui->t('Frontend'));

		$oTab->class = 'frontend-tab';

		$oTab->setElement(
			$oDialog->createRow(
				L10N::t('Icon-Klasse', $oGui->gui_description),
				'input',
				[
					'db_column'=>'frontend_icon_class',
					'db_alias' => ''
				]
			)
		);

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

		$oDialog->setElement($oTab);


		$oTab = $oDialog->createTab($oGui->t('Bestand'));
		$oTab->class = 'stock-tab';

		$oFactory = new \Ext_Gui2_Factory('ts_pos_stock');
		$oStockGui = $oFactory->createGui();
		$oStockGui->foreign_key = 'cost_id';
		$oStockGui->foreign_key_alias = 'ts_ps';

		$oTab->setElement($oStockGui);

		$oDialog->setElement($oTab);

		$oTabAvailability = $oDialog->createTab($oGui->t('Verfügbarkeit'));
		$oTabAvailability->class = 'availability-tab';
		$oTabAvailability->class_btn = 'availability-tab-btn';

		$oGuiAvailability = new \Ext_TC_Validity_Gui2(md5('ts_additionalcosts_validities'));
		$oGuiAvailability->gui_description = $oGui->gui_description;
		$oGuiAvailability->parent_hash = $oGui->hash;
		$oGuiAvailability->calendar_format = new \Ext_Thebing_Gui2_Format_Date();
		$oGuiAvailability->parent_primary_key = 'id';

		$oGuiAvailability->setOption('validity_no_required_fields', true);
		$oGuiAvailability->setOption('validity_hide_select', true);
		$oGuiAvailability->setOption('validity_show_valid_from', true);
		$oGuiAvailability->setOption('validity_show_valid_until', true);
		$oGuiAvailability->setOption('validity_show_comment_field', true);

		$oGuiAvailability->setTableData('limit', 30);

		$oGuiAvailability->setWDBasic('\Ts\Entity\Additionalcost\Validity');
		$oGuiAvailability->foreign_key = 'cost_id';

		$oTabAvailability->setElement($oGuiAvailability);
		$oDialog->setElement($oTabAvailability);

		return $oDialog;
	}

	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true)
    {

		$aTransfer = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		if ($bSave) {
			$iCost = (int)$aTransfer['save_id'];
			$oAdditionalCost = Ext_Thebing_School_Additionalcost::getInstance($iCost);

			// Wenn "Abhängigkeit von Dauer" nicht ausgewählt wurde, darf es auch keine Kombinationen
			// geben
			if ($oAdditionalCost->dependency_on_duration == 0) {

				$aCombinations = $oAdditionalCost->getPriceCalculationCombinations();

				if (!empty($aCombinations)) {
					$oAdditionalCost->cleanJoinedObjectChilds('calculation_combination');
				}

				$oAdditionalCost->save();
			}
		}
		
		return $aTransfer;
	}
	
}
	
