<?php


class Ext_Thebing_Special_Gui2 extends Ext_Thebing_Gui2_Data {

	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

		$oDialogData = self::getAdditionalDialogData($this->_oGui, $aSelectedIds);

		$aData = $oDialogData->generateAjaxData($aSelectedIds, $this->_oGui->hash);

		return $aData;
	}
	static public function getValidityStatus(Ext_Gui2 $oGui, $bUseNumericKeys = false){
		
		if (!$bUseNumericKeys) {
		$aValidityStatus = array(
			'active' => $oGui->t('Aktiviert'),
			'inactive' => $oGui->t('Deaktiviert')
		);
		} else {
			$aValidityStatus = [
				1 => $oGui->t('Aktiviert'),
				0 => $oGui->t('Deaktiviert'),
			];
		}
		
		return $aValidityStatus;
	}
	static public function getBasedOn(Ext_Gui2 $oGui, $bUseNumericKeys = false){
		
		if (!$bUseNumericKeys) {
			$aBasedOn = array(
				'booker_date' => $oGui->t('Buchungsdatum'),
				'obligation_data' => $oGui->t('Leistungsdaten')
			);
		} else {
			$aBasedOn = [
				 2 => $oGui->t('Buchungsdatum'),
				 1 => $oGui->t('Leistungsdaten'),
			];
		}
		
		return $aBasedOn;
	}
	static public function getAvailability(Ext_Gui2 $oGui, $bUseNumericKeys = false){
		
		if (!$bUseNumericKeys) {
			$aAvailability = array(
				'limited' => $oGui->t('Begrenzt'),
				'unlimited' => $oGui->t('Unbegrenzt')
			);
		} else {
			$aAvailability = [
				1 => $oGui->t('Begrenzt'),
				2 => $oGui->t('Unbegrenzt'),
			];
		}
		
		return $aAvailability;
	}
	static public function getAgencies(){
		
		$aAgencies = Ext_Thebing_Client::getFirstClient()->getAgencies(true);
		
		return $aAgencies;
	}

	static public function getAgencyGroups(){
		
		$oClient = Ext_Thebing_Client::getFirstClient();
		$aAgencyGroups = $oClient->getAgencyGroups(true);
		
		return $aAgencyGroups;
	}

	static public function getDiscountType(Ext_Gui2 $oGui, $bUseNumericKeys = false){
		
		if (!$bUseNumericKeys) {
			$aDiscountType = array(
				'percent' => $oGui->t('Prozent'),
				'absolut' => $oGui->t('Absoluter Betrag'),
				'free_weeks' => $oGui->t('Kostenfreie Wochen')
			);
		} else {
			$aDiscountType = [
				1 => $oGui->t('Prozent'),
				2 => $oGui->t('Absoluter Betrag'),
				3 => $oGui->t('Kostenfreie Wochen'),
			];
		}
		
		return $aDiscountType;	
	}

	static public function getWhere(){
		
		$schoolOptions = \Ext_Thebing_Client::getSchoolList(true);
		
		if(empty($schoolOptions)) {
			die('No School!!');
		}

		return [
			'schools.school_id' => ['IN', array_keys($schoolOptions)]
		];
	}

	static public function getAdditionalDialogData(Ext_Gui2 $oGui, $aSelectedIds = array()){

		$oClient = Ext_Thebing_Client::getFirstClient();
		
		$iSelectedId = 0;
		if(count($aSelectedIds) > 0){
			$iSelectedId = reset($aSelectedIds);
		}

		$schoolOptions = \Ext_Thebing_Client::getSchoolList(true);
		
		// Daten holen
		$sLang = Ext_Thebing_School::fetchInterfaceLanguage();

		$aPercentOptions				= (array)Ext_Thebing_School_Special::getPercentOptions();
		$aAbsolutOptions				= (array)Ext_Thebing_School_Special::getAbsolutOptions();
		$aWeekOptions					= (array)Ext_Thebing_School_Special::getWeekOptions();
		$aPeriodType					= Ext_Thebing_Util::addEmptyItem(Ext_Thebing_School_Special::getPeriodeTypes());
		$aLimitType						= (array)Ext_Thebing_School_Special::getLimitTypes();
		$aAvailableFor					= Ext_Thebing_Util::addEmptyItem(Ext_Thebing_School_Special::getAvailableFor());
		$aAmountTypes					= (array)Ext_Thebing_School_Special::getAmountTypes();
		$aAgencyGrouping				= (array)Ext_Thebing_School_Special::getAgencyGrouping();
		
		$aAgencyGroups					= (array)$oClient->getAgencyGroups(true);
		$aAgencyCategories				= (array)Ext_Thebing_Agency::getCategoryList();
		$aAgencies						= (array)$oClient->getAgencies(true);
		$aCountries						= (array)Ext_Thebing_Data::getCountryList(true);
		$countryGroups					=  Ext_TC_Countrygroup::getSelectOptions();
		
		//-----------------------------------------------------------------------

		$aBlockPercent	= array(new Ext_Thebing_Special_Block_Block());
		$aBlockAbsolut	= array(new Ext_Thebing_Special_Block_Block());
		$aBlockWeek		= array(new Ext_Thebing_Special_Block_Block());

		$oSpecial = null;
		if($iSelectedId > 0){
			$oSpecial = Ext_Thebing_School_Special::getInstance($iSelectedId);
			$aSpecialBlocks = $oSpecial->getBlocks();

			if(count($aSpecialBlocks) > 0){
				switch($oSpecial->amount_type){
					case 1: // Prozent
							$aBlockPercent	= $aSpecialBlocks;
						break;
					case 2: // Absolut
							$aBlockAbsolut	= $aSpecialBlocks;
						break;
					case 3: // week
							$aBlockWeek	= $aSpecialBlocks;
						break;
				}
			}
			
		}

		//------------------------------------------------------------------------
 
		$oDialog			= $oGui->createDialog(self::specialT('Eintrag "{name}" bearbeiten'), self::specialT('Neuer Eintrag'));
		$oDialog->width		= 1100;
		$oDialog->height	= 650;

		$oSpecialTab		=  $oDialog->createTab(self::specialT('Daten'));
		$oSpecialHistoryTab	=  $oDialog->createTab(self::specialT('Übersicht'));
				
		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Bezeichnung'), 'input', array('db_alias' => 'ts_sp', 'db_column'=>'name', 'required' => 1, '_editable' => 1)));
		
		$oSpecialTab->setElement(
			$oDialog->createRow(
				$oGui->t('Schulen'),
				'select',
				[
					'db_alias' => 'ts_sp',
					'db_column' => 'schools',
					'multiple' => 5,
					'select_options' => $schoolOptions,
					'jquery_multiple' => 1,
					'searchable' => 1,
					'required' => 1,
				]
			)
		);
		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Kostenstelle'), 'input', array(
			'db_alias'	=> 'ts_sp',
			'db_column'	=> 'cost_center',
		)));
		
		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Aktiv'), 'checkbox', array('db_alias' => 'ts_sp', 'db_column'=>'visible', '_editable' => 1)));

		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Keine weiteren Angebote berücksichtigen'), 'checkbox', array('db_alias' => 'ts_sp', 'db_column'=>'exclusive')));

        if(System::d('debugmode') == 2) {
            $oSpecialTab->setElement($oDialog->createNotification(self::specialT('Achtung'), 'Debug-Modus aktiviert, alle Einstellungen können bearbeitet werden!', 'hint'));
        } elseif($oSpecial === null) {
            $oSpecialTab->setElement($oDialog->createNotification(self::specialT('Achtung'), self::specialT('Wenn das Angebot benutzt wurde können Einstellungen nicht zurückgenommen werden.'), 'hint'));
        } elseif($oSpecial->getUsedQuantity() > 0) {
			$oSpecialTab->setElement($oDialog->createNotification(self::specialT('Achtung'), self::specialT('Das Angebot wurde bereits verwendet. Die Einstellungen können nicht mehr verändert werden.'), 'hint'));
        }

		$oH3			= $oDialog->create('h4');
		$oH3			->setElement(self::specialT('Gültigkeit'));
		$oSpecialTab	->setElement($oH3);

		$oSpecialTab->setElement(
			$oDialog->createMultiRow(
				self::specialT('Erstellungszeitraum'), 
				[
					'items' => [
						[
							'input'=>'calendar',
							'db_alias' => 'ts_sp', 
							'db_column'=>'created_from', 
							'format' => new Ext_Thebing_Gui2_Format_Date('convert_null')
						],
						[
							'input'=>'calendar',
							'db_alias' => 'ts_sp', 
							'db_column'=>'created_until', 
							'format' => new Ext_Thebing_Gui2_Format_Date('convert_null'),
							'text_before' => '<span class="row_until">&nbsp;'.$oGui->t('bis').'</span>'
						]

					]
				]
			)
		);

		$oSpecialTab->setElement(
			$oDialog->createMultiRow(
				self::specialT('Leistungszeitraum'), 
				[
					'items' => [
						[
							'input'=>'calendar',
							'db_alias' => 'ts_sp', 
							'db_column'=>'service_from', 
							'format' => new Ext_Thebing_Gui2_Format_Date('convert_null')
						],
						[
							'input'=>'calendar',
							'db_alias' => 'ts_sp', 
							'db_column'=>'service_until', 
							'format' => new Ext_Thebing_Gui2_Format_Date('convert_null'),
							'text_before' => '<span class="row_until">&nbsp;'.$oGui->t('bis').'</span>'
						]

					]
				]
			)
		);

		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Rabattcodes'), 'checkbox', ['db_alias' => 'ts_sp', 'db_column'=>'discount_code_enabled']));
		
		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Berechnung'), 'select', array(
			'db_alias' => 'ts_sp',
			'db_column' => 'service_period_calculation',
			'select_options' => [
				'full_service_period' => self::specialT('Gesamten Leistungszeitraum beachten'),
				'partial_service_period' => self::specialT('Exakten Leistungszeitraum beachten'),
			],
			'required' => true,
		)));

		$oH3			= $oDialog->create('h4');
		$oH3			->setElement(self::specialT('Verfügbarkeit'));
		$oSpecialTab	->setElement($oH3);

		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Buchungstyp'), 'select', array('db_alias' => 'ts_sp', 'db_column'=>'limit_type', 'select_options' => $aLimitType, 'required' => 1)));
		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Anzahl'), 'input', array('db_alias' => 'ts_sp', 'db_column'=>'limit', 'required' => 1, 'class' => 'w50', 'row_id' => 'limit_input')));

		$oH3			= $oDialog->create('h4');
		$oH3			->setElement(self::specialT('Buchungstyp'));
		$oSpecialTab	->setElement($oH3);

		$oSpecialTab->setElement(
			$oDialog->createRow(
				self::specialT('Direktbuchungen'), 
				'checkbox', 
				[
					'db_alias' => 'ts_sp',
					'db_column'=>'direct_bookings',
//					'child_visibility' => [
//						[
//							'id' => 'direct_booking',
//							'on_values' => ['1']
//						]
//					]
				]
			)
		);

		$oSpecialTab->setElement(
			$oDialog->createRow(
				self::specialT('Agenturbuchungen'), 
				'checkbox', 
				[
					'db_alias' => 'ts_sp', 
					'db_column'=>'agency_bookings',
					'child_visibility' => [
						[
							'id' => 'agency_booking',
							'on_values' => ['1']
						]
					]
				]
			)
		);
		$oDivAgencyBooking = $oDialog->create('fieldset');
		$oDivAgencyBooking->id = 'agency_booking';
		$oDivAgencyBookingLegend = new Ext_Gui2_Html_Fieldset_Legend;
		$oDivAgencyBookingLegend->setElement($oGui->t('Agenturbuchungen'));
		$oDivAgencyBooking->setElement($oDivAgencyBookingLegend);
		
		$oDivAgencyBooking->setElement($oDialog->createRow(self::specialT('Agenturen gruppiert nach'), 'select', array('db_alias' => 'ts_sp', 'db_column'=>'agency_grouping', 'select_options' => $aAgencyGrouping)));
		$oDivAgencyBooking->setElement($oDialog->createRow(self::specialT('Länder'), 'select', array('db_alias' => 'ts_sp', 'db_column'=>'join_agency_countries', 'multiple' => 5, 'searchable' => 1, 'jquery_multiple' => 1, '_editable' => 1, 'row_id' => 'row_agency_country', 'select_options' => $aCountries)));
		$oDivAgencyBooking->setElement($oDialog->createRow(self::specialT('Agenturgruppen'), 'select', array('db_alias' => 'ts_sp', 'db_column'=>'join_agency_groups', 'multiple' => 5, 'searchable' => 1, 'jquery_multiple' => 1, '_editable' => 1, 'row_id' => 'row_agency_group', 'select_options' => $aAgencyGroups)));
		$oDivAgencyBooking->setElement($oDialog->createRow(self::specialT('Agenturkategorien'), 'select', array('db_alias' => 'ts_sp', 'db_column'=>'join_agency_categories', 'multiple' => 5, 'searchable' => 1, 'jquery_multiple' => 1, '_editable' => 1, 'row_id' => 'row_agency_category', 'select_options' => $aAgencyCategories)));
		$oDivAgencyBooking->setElement($oDialog->createRow(self::specialT('Agenturen'), 'select', array('db_alias' => 'ts_sp', 'db_column'=>'join_agencies', 'multiple' => 5, 'searchable' => 1, 'jquery_multiple' => 1, '_editable' => 1, 'row_id' => 'row_agencies', 'select_options' => $aAgencies)));
		$oDivAgencyBooking->setElement($oDialog->createRow(self::specialT('Ländergruppen'), 'select', array('db_alias' => 'ts_sp', 'db_column'=>'join_agency_country_groups', 'multiple' => 5, 'searchable' => 1, 'jquery_multiple' => 1, '_editable' => 1, 'row_id' => 'row_agency_country_group', 'select_options' => $countryGroups)));
		$oSpecialTab->setElement($oDivAgencyBooking);
		
		$oH3 = $oDialog->create('h4');
		$oH3->setElement(self::specialT('Filter'));
		$oSpecialTab->setElement($oH3);

		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Alle Länder'), 'checkbox', array('db_alias' => 'ts_sp', 'db_column'=>'all_countries', 'required' => 0)));
		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Länder'), 'select', array('db_alias' => 'ts_sp', 'db_column'=>'join_countries', 'multiple' => 5, 'jquery_multiple' => 1, 'searchable' => 1, 'select_options' => $aCountries)));
		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Ländergruppen'), 'select', array('db_alias' => 'ts_sp', 'db_column'=>'join_country_groups', 'multiple' => 5, 'searchable' => 1, 'jquery_multiple' => 1, 'select_options' => $countryGroups)));

		$nationalitieOptions = \Ext_Thebing_Nationality::getNationalities(true, $sLang);
		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Alle Nationalitäten'), 'checkbox', array('db_alias' => 'ts_sp', 'db_column'=>'all_nationalities', 'required' => 0)));
		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Nationalitäten'), 'select', array('db_alias' => 'ts_sp', 'db_column'=>'nationalities', 'multiple' => 5, 'jquery_multiple' => 1, 'searchable' => 1, 'select_options' => $nationalitieOptions)));
		
		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Mindestanzahl an gebuchten Kursen'), 'input', array('db_alias' => 'ts_sp', 'db_column'=>'minimum_courses', 'format'=> new \Ext_Gui2_View_Format_Null)));

		$oH3 = $oDialog->create('h4');
		$oH3->setElement(self::specialT('Schülerstatus'));
		$oSpecialTab->setElement($oH3);

		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Abhängig von Schülerstatus?'), 'checkbox', array(
			'db_column' => 'use_student_status',
			'db_alias' => 'ts_sp'
		)));
		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Schülerstatus'), 'select', array(
			'db_column' => 'student_status',
			'db_alias' => 'ts_sp',
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'selection' => new \Ts\Gui2\Selection\Special\StudentStatus(),
			'dependency_visibility' => array(
				'db_column' => 'use_student_status',
				'db_alias' => 'ts_sp',
				'on_values' => array('1')
			),
			'dependency' => [
				[
					'db_column' => 'schools',
					'db_alias' => 'ts_sp',
				],
			],
		)));

		$oH3			= $oDialog->create('h4');
		$oH3			->setElement(self::specialT('Konditionen'));
		$oSpecialTab	->setElement($oH3);

		$oSpecialTab->setElement($oDialog->createRow(self::specialT('Wieviel'), 'select', array('db_alias' => 'ts_sp', 'db_column'=>'amount_type', 'select_options' => $aAmountTypes, 'row_class' => 'select_how_many')));



		$oDiv = $oDialog->create('div');
		$oDiv->class = 'GUIDialogJoinedObjectContainer';

		//=============================================================================
		// Prozentfelder
		//=============================================================================
		$oDivPercent = $oDialog->create('div');

		// Blöcke anzeigen
		foreach((array)$aBlockPercent as $iCount =>  $oBlock){

			$aBlockData = $oBlock->getAdditionalDataForSelect();

			$aDataCourse		= array();
			$aDataAccommodation = array();
			$aDataTransfer		= array();
			$aDataGeneral		= array();
			switch($oBlock->option_id){
				case 1: // Kurse
					$aDataCourse = $aBlockData;
					break;
				case 2: // Unterkunft
					$aDataAccommodation = $aBlockData;
					break;
				case 3: // Transfer
					$aDataTransfer = $aBlockData;
					break;
				case 4: // General
					$aDataGeneral = $aBlockData;
					break;
			}

			$oFloatFormat = new Ext_Thebing_Gui2_Format_Float();			
			$fPercent = $oFloatFormat->format($oBlock->percent);
						
			$oFieldset						= $oDialog->create('div');
			$oFieldset->style				= 'margin-bottom: 5px';
			$oFieldset->class				= 'div_percent_class';
			$oFieldset->class				= 'div_special_block GUIDialogJoinedObjectContainerRow';
			$oFieldset->id					= 'div_percent_' . $iCount;

			$oFieldset->setElement(
				$oDialog->createRow(
					self::specialT('Prozent') . ' *',
					'input',
					array(
						'db_alias' => '',
						'db_column' =>'percent][amount][',
						'class' => 'percent_required',
						'default_value' => $fPercent,
						'id'=> 'percent_amount_' . $iCount,
						'skip_value_handling' => true
					)
				)
			);

			$oFieldset->setElement(
				$oDialog->createRow(
					self::specialT('Art'),
					'select',
					array(
						'db_alias' => '',
						'db_column'=>'percent][option][',
						'select_options' => $aPercentOptions,
						'class' => 'percent_kind',
						'default_value' => $oBlock->option_id,
						'id'=> 'percent_option_' . $iCount,
						'skip_value_handling' => true
					)
				)
			);

			// Wenn Kurse
			$oFieldset->setElement(
				$oDialog->createRow(
					self::specialT('Kurse'),
					'select',
					array(
						'db_alias' => '',
						'db_column'=>'percent][course]['.$iCount,
						'multiple' => 5,
						'jquery_multiple' => 1,
						'searchable' => 1,
						'selection' => new \Ts\Gui2\Selection\Special\Courses,
						'dependency' => [
							[
								'db_column' => 'schools',
								'db_alias' => 'ts_sp',
							],
						],
						'row_class' => 'percent_course',
						'value' => $aDataCourse,
						'id'=> 'percent_course_' . $iCount,
						'skip_value_handling' => true
					)
				)
			);

			// Wenn Unterkunft
			$oFieldset->setElement(
				$oDialog->createRow(
					self::specialT('Unterkunft'),
					'select',
					array(
						'db_alias' => '',
						'db_column'=>'percent][accommodation]['.$iCount,
						'multiple' => 5,
						'jquery_multiple' => 1,
						'searchable' => 1,
						'selection' => new \Ts\Gui2\Selection\Special\Accommodations(),
						'row_class' => 'percent_accommodation',
						'value' => $aDataAccommodation,
						'id'=> 'percent_accommodation_' . $iCount,
						'skip_value_handling' => true
					)
				)
			);

			// Wenn Transfer
			$oFieldset->setElement(
				$oDialog->createRow(
					self::specialT('Transferpackete'),
					'select',
					array(
						'db_alias' => '',
						'db_column'=>'percent][transfer]['.$iCount,
						'multiple' => 5,
						'jquery_multiple' => 1,
						'searchable' => 1,
						'selection' => new \Ts\Gui2\Selection\Special\Transfers,
						'row_class' => 'percent_transfer',
						'value' => $aDataTransfer,
						'id'=> 'percent_transfer_' . $iCount,
						'skip_value_handling' => true
					)
				)
			);

			// Wenn Zusatzkosten
			$oFieldset->setElement(
				$oDialog->createRow(
					self::specialT('Zusatzkosten'),
					'select',
					array(
						'db_alias' => '',
						'db_column'=>'percent][additional]['.$iCount,
						'multiple' => 5,
						'jquery_multiple' => 1,
						'searchable' => 1,
						'selection' => new \Ts\Gui2\Selection\Special\AdditionalCosts(),
						'row_class' => 'percent_additional',
						'value' => $aDataGeneral,
						'id'=> 'percent_additional_' . $iCount,
						'skip_value_handling' => true
					)
				)
			);

			$oFieldset->setElement(
				self::getDependencyDurationRow($oDialog, $oBlock, 'percent', $iCount)
			);
			
			$oFieldset->setElement(
				self::getConditionSettingRow($oDialog, $oBlock, 'percent', $iCount)
			);
			
			// Löschen
			$oFieldset->setElement(
				self::getDeleteRow($oDialog, $oGui)
			);



			$oDivPercent->setElement($oFieldset);
		}


		$oDiv->setElement($oDivPercent);

		//=============================================================================
		// Absolut pro Woche 
		//=============================================================================
		$oDivAbsolut					= $oDialog->create('div');

		// Blöcke anzeigen
		foreach((array)$aBlockAbsolut as $iCount => $oBlock){

			$aBlockData		= $oBlock->getAdditionalDataForSelect();
			$aBlockCurrency = $oBlock->getAdditionalCurrencyData();

			$aDataCourse		= array();
			$aDataAccommodation = array();
			switch($oBlock->option_id){
				case 2: // Kurse
				case 4: // Kurse
					$aDataCourse = $aBlockData;
					break;
				case 3: // Unterkunft
				case 5: // Unterkunft
					$aDataAccommodation = $aBlockData;
					break;
			}

			$oFieldset						= $oDialog->create('div');
			$oFieldset->style				= 'margin-bottom: 5px';
			$oFieldset->class				= 'div_absolut_class';
			$oFieldset->class				= 'div_special_block GUIDialogJoinedObjectContainerRow';
			$oFieldset->id					= 'div_absolut_' . $iCount;

			$aCurrencyList = [];
			$schools = Ext_Thebing_Client::getSchoolList(false, 0, true);
			foreach($schools as $school) {
				$aCurrencyList += (array)$school->getSchoolCurrencyList();
			}
			
			$oFormat = new Ext_Thebing_Gui2_Format_Float();
			// Schulwährungen
			foreach((array)$aCurrencyList as $iCurrencyId => $sSign){

				$fValue = 0;
				if(isset($aBlockCurrency[$iCurrencyId])){
					$fValue = $aBlockCurrency[$iCurrencyId];
				}

				$x = 1;
				$oFieldset->setElement(
					$oDialog->createRow(
						self::specialT('Betrag') . '*',
						'input',
						array(
							'db_alias' => '',
							'db_column' =>'absolut][amount]['.$iCount.']['.$iCurrencyId,
							'class' => 'amount absolut_required',
							'input_div_addon_style' => 'width: 100px',
							'value' => $oFormat->format($fValue),
							'id'=> 'absolut_amount_' . $iCount,
							'input_div_addon'=>' ' . $sSign,
							'skip_value_handling' => true
						)
					)
				);
			}

			// Absolut Art Select
			$oFieldset->setElement(
				$oDialog->createRow(
					self::specialT('Art'),
					'select',
					array(
						'db_alias' => '',
						'db_column'=>'absolut][option][',
						'select_options' => $aAbsolutOptions,
						'class' => 'absolut_kind',
						'value' => $oBlock->option_id,
						'id'=> 'absolut_option_' . $iCount,
						'skip_value_handling' => true
					)
				)
			);

			// Wenn Kurse
			$oFieldset->setElement(
				$oDialog->createRow(
					self::specialT('Kurse'),
					'select',
					array(
						'db_alias' => '',
						'db_column'=>'absolut][course]['.$iCount,
						'multiple' => 5,
						'jquery_multiple' => 1,
						'searchable' => 1,
						'selection' => new \Ts\Gui2\Selection\Special\Courses,
						'row_class' => 'absolut_course',
						'value' => $aDataCourse,
						'id'=> 'absolut_course_' . $iCount,
						'skip_value_handling' => true
					)
				)
			);

			// Wenn Unterkunft
			$oFieldset->setElement(
				$oDialog->createRow(
					self::specialT('Unterkunft'),
					'select',
					array(
						'db_alias' => '',
						'db_column'=>'absolut][accommodation]['.$iCount,
						'multiple' => 5,
						'jquery_multiple' => 1,
						'searchable' => 1,
						'selection' => new \Ts\Gui2\Selection\Special\Accommodations(),
						'row_class' => 'absolut_accommodation',
						'value' => $aDataAccommodation,
						'id'=> 'absolut_accommodation_' . $iCount,
						'skip_value_handling' => true
					)
				)
			);

			$oFieldset->setElement(
				self::getDependencyDurationRow($oDialog, $oBlock, 'absolut', $iCount)
			);
			
			$oFieldset->setElement(
					self::getConditionSettingRow($oDialog, $oBlock, 'absolut', $iCount)
			);
			
			// Löschen
			$oFieldset->setElement(
				self::getDeleteRow($oDialog, $oGui)
			);

			$oDivAbsolut->setElement($oFieldset);
		}
		

		$oDiv->setElement($oDivAbsolut);

		//=============================================================================
		// Freie Wochen
		//=============================================================================
		$oDivWeek						= $oDialog->create('div');

		// Blöcke anzeigen
		foreach((array)$aBlockWeek as $iCount => $oBlock){

			$aBlockData		= $oBlock->getAdditionalDataForSelect();

			$aDataCourse		= array();
			$aDataAccommodation = array();
			switch($oBlock->option_id){
				case 1: // Kurse
					$aDataCourse = $aBlockData;
					break;
				case 2: // Unterkunft
					$aDataAccommodation = $aBlockData;
					break;
			}

			$oFieldset						= $oDialog->create('div');
			$oFieldset->style				= 'margin-bottom: 5px';
			$oFieldset->class				= 'div_week_class';
			$oFieldset->class				= 'div_special_block GUIDialogJoinedObjectContainerRow';
			$oFieldset->id					= 'div_week_' . $iCount;

			$oFieldset->setElement(
						$oDialog->createRow(
								self::specialT('Anzahl gebuchter Wochen') . ' *',
								'input',
								array(
									'db_alias' => '',
									'db_column' =>'week][weeks][',
									'class' => 'w50 week_required',
									'value' => $oBlock->weeks,
									'id'=> 'week_weeks_' . $iCount,
									'skip_value_handling' => true
								)
						)
			);

			$oFieldset->setElement(
						$oDialog->createRow(
								self::specialT('Anzahl freie Wochen') . ' *',
								'input',
								array(
									'db_alias' => '',
									'db_column' =>'week][free][',
									'class' => 'w50 week_required',
									'value' => $oBlock->free_weeks,
									'id'=> 'week_free_' . $iCount,
									'skip_value_handling' => true
								)
						)
			);

			// Week Art Select
			$oFieldset->setElement(
							$oDialog->createRow(
									self::specialT('Art'),
									'select',
									array(
										'db_alias' => '',
										'db_column'=>'week][option][',
										'select_options' => $aWeekOptions,
										'class' => 'week_kind',
										'value' => $oBlock->option_id,
										'id'=> 'absolut_week_' . $iCount,
										'skip_value_handling' => true
									)
							)
			);

			// Wenn Kurse 
			$oFieldset->setElement(
				$oDialog->createRow(
					self::specialT('Kurse'),
					'select',
					array(
						'db_alias' => '',
						'db_column'=>'week][course]['.$iCount,
						'multiple' => 5,
						'jquery_multiple' => 1,
						'searchable' => 1,
						'selection' => new \Ts\Gui2\Selection\Special\Courses,
						'row_class' => 'week_course',
						'value' => $aDataCourse,
						'id'=> 'week_course_' . $iCount,
						'class' => 'course_select',
						'skip_value_handling' => true
					)
				)
			);

			// Wenn Unterkunft
			$oFieldset->setElement(
				$oDialog->createRow(
					self::specialT('Unterkunft'),
					'select',
					array(
						'db_alias' => '',
						'db_column'=>'week][accommodation]['.$iCount,
						'multiple' => 5,
						'jquery_multiple' => 1,
						'searchable' => 1,
						'selection' => new \Ts\Gui2\Selection\Special\Accommodations(),
						'row_class' => 'week_accommodation',
						'value' => $aDataAccommodation,
						'id'=> 'week_accommodation_' . $iCount,
						'skip_value_handling' => true
					)
				)
			);

			// Löschen
			$oFieldset->setElement(
				self::getDeleteRow($oDialog, $oGui)
			);

			$oDivWeek->setElement($oFieldset);
		}
		

		$oDiv->setElement($oDivWeek);

		//=============================================================================
		// Row klonen
		//=============================================================================
		if(
			empty($oSpecial) ||
			$oSpecial->getUsedQuantity() == 0 ||
			System::d('debugmode') == 2
		) {
			$oDiv->setElement(
				Ext_Thebing_Gui2_Util::getIconRow(
						$oDialog,
						$oGui->t('Eintrag hinzufügen'),
						Ext_Thebing_Util::getIcon('add'),
						array(
							'onclick' => "var oGui = aGUI['".$oGui->hash."']; oGui.cloneSpecialBlock(this); return false;",
							'row_class' => 'plus_icon'
						)
				)
			);
		}

		$oSpecialTab->setElement($oDiv);
		$oSpecialHistoryTab->setElement(self::getHistoryGui($oGui));
		
		$discountCodesTab = $oDialog->createTab(self::specialT('Rabattcodes'));
		$discountCodesTab->class = 'discount-code-tab';
		if(!$oSpecial->discount_code_enabled) {
			$discountCodesTab->hidden = true;
		}
		
		$gui2Factory = new \Ext_Gui2_Factory('Ts_marketing_special_codes');
		$guiCodes = $gui2Factory->createGui(null, $oGui);		
		
		$discountCodesTab->setElement($guiCodes);		
		
		$oDialog->setElement($oSpecialTab);
		$oDialog->setElement($discountCodesTab);
		$oDialog->setElement($oSpecialHistoryTab);

		// Elemente deaktivieren, wenn Special schon benutzt wurde
		if(!self::isFullyEditable($oSpecial)) {
			$oDisableElements = function($oElement) use(&$oDisableElements) {
				foreach($oElement->getElements() as $oElement2) {
					if(
						$oElement2 instanceof Ext_Gui2_Html_Input ||
						$oElement2 instanceof Ext_Gui2_Html_Select
					) {
						if(!$oElement2->_editable) {
							$oElement2->bReadOnly = true;
							//$oElement2->bDisabledByReadonly = false;
						}
					} elseif(method_exists($oElement2, 'getElements')) {
						$oDisableElements($oElement2);
					}
				}
			};

			$oDisableElements($oDialog);
		}

		return $oDialog;
	}

	static protected function isFullyEditable(Ext_Thebing_School_Special $oSpecial=null) {
		if(
			!empty($oSpecial) &&
			$oSpecial->getUsedQuantity() > 0 &&
			System::d('debugmode') != 2
		) {
			return false;
		}
		
		return true;
	}


	public static function getDependencyDurationRow(Ext_Gui2_Dialog $oDialog, Ext_Thebing_Special_Block_Block $oBlock, $sType, $iCount) {

		$aOptions = array(
			'db_alias' => '',
			 // $iCount ist wichtig, da neben der Checkbox noch ein hidden-Feld mit demselben name-Attribut existiert
			'db_column'=> $sType.'][dependency_on_duration]['.$iCount,
			'row_class' => $sType.'_dependency_on_duration',
			'id'=> $sType.'_dependencyduration_' . $iCount,			
			'class' => 'switch_condition_block',
			'skip_value_handling' => true
		);

		if($oBlock->dependency_on_duration == 1) {
			$aOptions['value'] = 1;
		}
		
		$oRow = $oDialog->createRow(
			self::specialT('Abhängigkeit von Dauer?'),
			'checkbox',
			$aOptions
		);
		
		return $oRow;
	}
	
	public static function getConditionSettingRow(Ext_Gui2_Dialog $oDialog, Ext_Thebing_Special_Block_Block $oBlock, $sType, $iCount) {
		
		$sStyle = 'display: block;';
		if(
			$oBlock->dependency_on_duration == 0 ||
			$oBlock->dependency_on_duration == ''
		) {
			$sStyle = 'display: none;';
		}
		
		$aConditions = $oBlock->getJoinedObjectChilds('conditions', true);
		if(empty($aConditions)) {
			$aConditions = array(
				new Ext_Thebing_Special_Block_Condition()
			);
		}		
		
		$oDiv = new Ext_Gui2_Html_Div();
		$oDiv->style = $sStyle;
		$oDiv->class = $sType.'_condition_block condition_block clearfix GUIDialogJoinedObjectContainer bordered';
		$oDiv->id = $sType.'_conditionblock_' . $iCount;		
		
		$aSelectOptions = array(
			'1' => '<',
			'2' => '=',
			'3' => '>'
		);	
		
		$iCounter = 1;
		foreach($aConditions as $oCondition) {
			$sConditionWeek = (int)$oCondition->weeks;
			if($sConditionWeek <= 0) {
				$sConditionWeek = '';
			}
						
			$oConditionRow = new Ext_Gui2_Html_Div();
			$oConditionRow->style = 'padding: 0;';
			$oConditionRow->class = 'condition_row';
			$oConditionRow->id = 'condition_row_'.$iCount.'_'.$iCounter;
			$oConditionRow->__set('data-block', $iCount);
			
			$oRow = $oDialog->createMultiRow(self::specialT('Nur berechnen, wenn Leistung'), array(
				'db_alias' => '',
				'row_class' => $sType.'_conditions',
				//'row_style' => 'float: left; border-bottom: none;',
				'items' => array(
					array(
						'db_column' => $sType.'][condition_symbol]['.$iCount.'][',
						'input' => 'select',
						'select_options' => Ext_Thebing_Util::addEmptyItem($aSelectOptions),
						'text_after' => '&nbsp;',
						// Funktioniert bei mehr als einem Wert trotz skip_value_handling nicht
						// Liegt vielleicht an denselben Namen der Rows und der komischen Behandlung bei default_value/value
						//'value' => (int)$oCondition->symbol,
						'default_value' => (int)$oCondition->symbol,
						'class' => 'condition_symbol txt w80',
						'style' => 'width: 80px',
						'skip_value_handling' => true
					),
					array(
						'db_column' => $sType.'][condition_weeks]['.$iCount.'][',
						'input' => 'input',
						'text_after' => self::specialT('Wochen'),
						'value' => $sConditionWeek,
						'class' => 'condition_week txt w80',
						'style' => 'width: 80px',
						'skip_value_handling' => true
					)
				)
			));
			
			$oConditionRow->setElement($oRow);
					
			$oButtonDelete = new Ext_Gui2_Html_I();
			$oButtonDelete->class = 'fa fa-minus-circle btn btn-gray delete_condtion_row condtion_row_button pull-right';
			$oButtonDelete->style = 'cursor: pointer;';
			$oButtonDelete->__set('data-counter', $iCounter);
			
			if ($iCounter == 1) {
				$oButtonDelete->style .= 'visibility: hidden;';
			}
			
			$oConditionRow->setElement($oButtonDelete);
						
			$oDiv->setElement($oConditionRow);

			++$iCounter;
		}

		$oButtonContainer = new Ext_Gui2_Html_Div();
		$oButtonAdd = new Ext_Gui2_Html_I();
		$oButtonAdd->class = 'fa fa-plus-circle btn btn-primary add_condtion_row condtion_row_button pull-right';
		$oButtonAdd->style = 'cursor: pointer;';

		$oButtonContainer->setElement($oButtonAdd);

		$oDiv->setElement($oButtonContainer);

		return $oDiv;
	}
	
	/**
	 * @param Ext_Gui2 $oGui
	 * @return Ext_Gui2
	 */
	public static function getHistoryGui(&$oGui) {
		
		$oInnerGui = $oGui->createChildGui(md5('thebing_special_history'), 'Ext_Thebing_Special_History_Gui2');
		
		$oInnerGui->query_id_column		= 'id';
		$oInnerGui->query_id_alias		= 'kips';
		$oInnerGui->foreign_key			= 'special_id';
		$oInnerGui->foreign_key_alias	= '';
		$oInnerGui->parent_primary_key	= 'id';
		$oInnerGui->load_admin_header	= false;
		$oInnerGui->multiple_selection  = false;

		$oInnerGui->setWDBasic('Ext_Thebing_Inquiry_Special_Position');
		$oInnerGui->setTableData('orderby', array(
			'kidv.date' => 'DESC'
		));
		$oInnerGui->setTableData('limit', 30);

		$oBar = $oInnerGui->createBar();
		$oBar->setElement($oBar->createPagination(false, true));
		$oBar->createCSVExportWithLabel();
		$oInnerGui->setBar($oBar);

		$oColumnGroupCustomer = $oInnerGui->createColumnGroup();
		$oColumnGroupCustomer->title = $oInnerGui->t('Kunde');
		
		$oColumnGroupInvoice = $oInnerGui->createColumnGroup();
		$oColumnGroupInvoice->title = $oInnerGui->t('Rechnung');
		
		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'lastname';
		$oColumn->db_alias = 'cdb1';
		$oColumn->title = $oGui->t('Name');
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('customer_name');
		$oColumn->width_resize = true;
		$oColumn->format = new Ext_Thebing_Gui2_Format_CustomerName();
		$oColumn->group			= $oColumnGroupCustomer;
		$oInnerGui->setColumn($oColumn);
		
		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'customerNumber';
		$oColumn->db_alias = '';
		$oColumn->title = $oGui->t('Kundennummer');
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('customer_number');
		$oColumn->width_resize = false;
		$oColumn->group			= $oColumnGroupCustomer;
		$oInnerGui->setColumn($oColumn);
		
		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'document_number';
		$oColumn->db_alias = 'kid';
		$oColumn->title = $oGui->t('Nummer');
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('customer_number');
		$oColumn->format = new Ext_Gui2_View_Format_StringParts(',');
		$oColumn->width_resize = false;
		$oColumn->group			= $oColumnGroupInvoice;
		$oInnerGui->setColumn($oColumn);
		
		$oColumn = $oInnerGui->createColumn(); 
		$oColumn->db_column		= 'date';
		$oColumn->db_alias		= 'kidv';
		$oColumn->title			= $oGui->t('Datum');
		$oColumn->width			= Ext_Thebing_Util::getTableColumnWidth('date');
		$oColumn->format		= new Ext_Thebing_Gui2_Format_Date();
		$oColumn->width_resize	= false;
		$oColumn->group			= $oColumnGroupInvoice;
		$oInnerGui->setColumn($oColumn);
		
		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'amount';
		$oColumn->db_alias = '';
		$oColumn->title = $oGui->t('Betrag');
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('amount');
		$oColumn->width_resize = false;
		$oColumn->format = new Ext_Thebing_Gui2_Format_Document_Amount();
		$oColumn->sortable = 0;//wurde so besprochen, siehe T-2851
		$oColumn->group			= $oColumnGroupInvoice;
		$oInnerGui->setColumn($oColumn);

		$oColumn = $oInnerGui->createColumn();
		$oColumn->db_column = 'code';
		$oColumn->db_alias = '';
		$oColumn->title = $oGui->t('Rabattcode');
		$oColumn->width = Ext_Thebing_Util::getTableColumnWidth('name');
		$oColumn->width_resize = false;
		$oInnerGui->setColumn($oColumn);
		
		return $oInnerGui;
	}

	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true) {
		global $_VARS;

		// Dynamische Dialogdaten
		$aDataPercent			= $aData['percent'];
		$aDataAbsolut			= $aData['absolut'];
		$aDataWeek				= $aData['week'];

		// soll nicht über die standartfunktion gesp. werden
		unset(
			$_VARS['save']['percent'],
			$_VARS['save']['absolut'],
			$_VARS['save']['week'],
			$aData['percent'],
			$aData['absolut'],
			$aData['week']
		);

		$oSpecial = $this->getWDBasicObject($aSelectedIds);

		if(!self::isFullyEditable($oSpecial)) {
			
			/*
			 * BESCHEUERT!
			 * Deaktivierte (disabled) Multiselects werden nicht übermittelt und die Werte werden geleert, durch einen 
			 * Workaround in der Ext_Gui2_Dialog_Data. Daher müssen die Werte hier gesetzt werden.
			 */
			$aData['schools']['ts_sp'] = $oSpecial->schools;
			$aData['student_status']['ts_sp'] = $oSpecial->student_status;
			
		}

		$aTransferData = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);

		$iSpecialId = (int)$aTransferData['save_id'];
		$oSpecial = Ext_Thebing_School_Special::getInstance($iSpecialId);
		
		$aError = array();
		// Speichern der Dynamischen Dialogdaten
		if(
			empty($aTransferData['error']) &&
			$oSpecial->exist() &&
			// Elemente stehen auf disabled und dann darf das hier auf keinen Fall ausgeführt werden
			($oSpecial->getUsedQuantity() == 0 || System::d('debugmode') == 2)
		){
			// Löschen aller Blockdaten
			// @TODO Das ist Müll und muss raus; durch JoinedObjectContainer ersetzen!
			/*
			 * Anmerkung: Das hat vielleicht doch sein Daseinsberechtigung, denn alte Inquiry-Postionen
			 * bleiben mit dem gelöschten Block und der demnach verwendeten Einstellung verknüpft (used = 1).
			 * Würde beim verknüpften Block die Prozentzahl verändert würden, dürften
			 * komische Ergebnisse beim Aktualisieren/Diff auftreten
			 */
			$oSpecial->deleteBlockData();

			switch($oSpecial->amount_type){
				case 1: // Prozent
					$aError = $oSpecial->savePercentData($aDataPercent);
					break;
				case 2: // Absolut
					$aError = $oSpecial->saveAbsolutData($aDataAbsolut);
					break;
				case 3: // Week
					$aError = $oSpecial->saveWeekData($aDataWeek);
					break;
			}
		}
		

		$aData = $this->prepareOpenDialog($sAction, array($iSpecialId),false,false,false);
		$aTransferData['data'] = $aData; 

		return $aTransferData;
	}

	private static function getDeleteRow($oDialog, &$oGui){
		$oRow = Ext_Thebing_Gui2_Util::getIconRow(
					$oDialog,
					'',
					Ext_Thebing_Util::getIcon('delete'),
					array(
						'onclick' => "var oGui = aGUI['".$oGui->hash."']; oGui.deleteSpecialBlock(this); return false;",
						'row_class' => 'delete_box',
					)
			);

		return $oRow;
	}

	public static function specialT($sValue){

		return L10N::t($sValue, 'Thebing » Marketing » Special');
	}

	public function getTranslations($sL10NDescription){
		
		$aTranslations = parent::getTranslations($sL10NDescription);
		$aTranslations['delete_condition_row_question'] = self::specialT('Möchten Sie diesen Eintrag wirklich löschen?');
		
		return $aTranslations;		
	}

	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {
		switch($sError) {
			case 'USED_OPTION_REMOVED':
				return self::specialT('Bereits gespeicherte Optionen können nach Verwendung des Angebots nicht entfernt werden.');
			case 'DISCOUNT_CODE_DISABLED_BUT_USED_CODES_EXIST':
				return self::specialT('Checkbox "Rabattcodes" deaktiviert, obwohl bereits Codes verwendet wurden.');
			default:
				return parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}
	}

	public static function getFilterQueryCountryGroupsFilter() {

		$countryGroups = Ext_TC_Countrygroup::query()
			->get();

		$result = [];

		foreach($countryGroups as $countryGroup) {

			$countryGroup = Ext_TC_Countrygroup::getInstance($countryGroup->id);
			$countryGroupObjects = $countryGroup->getJoinedObjectChilds('SubObjects');
			$countryIsos = reset($countryGroupObjects)->countries;

			if(!empty($countryIsos)) {
				$countryIsos = implode("', '", $countryIsos);
			}

			$result[$countryGroup->id] = "`join_agency_country_groups`.`country_group_id` IN (" . $countryGroup->id . ") OR `join_country_groups`.`country_group_id` IN (" . $countryGroup->id . ") OR `join_agency_countries`.`agency_country_id` IN ('" . $countryIsos . "')";
		}

		return $result;

	}
	
}