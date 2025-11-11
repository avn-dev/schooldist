<?php

class Ext_TS_Vat_Gui2_Data extends Ext_TC_Vat_Gui2_Data {
	
	protected $_aElementCache = array();
	
	const ALLOCATION_DIALOG_ID = 'VAT_ALLOCATION';
	
	const COMMISSION_VAT_DOMESTIC = 'domestic';
	const COMMISSION_VAT_ABROAD = 'abroad';
	
	/**
	 * ergänzt die dritte Zeile der GUI
	 * @param Ext_Gui2 $oGui
	 * @param Ext_Gui2_Bar $oBar
	 */
	public static function setAdditionalBarData(&$oGui, $oBar) {
		
		$oLabelGroup	= $oBar->createLabelGroup($oGui->t('Weitere Aktionen'));
		$oBar->setElement($oLabelGroup);
		
		$oDialog = $oGui->createDialog($oGui->t('Zuweisungen'), $oGui->t('Zuweisungen'), $oGui->t('Zuweisungen'));
		$oDialog->action = 'allocate_vat_rates_dialog';
		$oDialog->sDialogIDTag = self::ALLOCATION_DIALOG_ID.'_';
		
		$oIcon = $oBar->createIcon(Ext_TC_Util::getIcon('allocate'), 'openDialog', $oGui->t('Zuweisungen'));
		$oIcon->access = array(
			'thebing_vat_allocation',
			''
		);
		
		$oIcon->label = $oGui->t('Zuweisungen');
		$oIcon->action = 'edit';
		$oIcon->additional = 'allocate_vat_rates';
		$oIcon->dialog_data = $oDialog;
		$oIcon->active = 1;
		$oBar->setElement($oIcon);
		
	}
	
	/**
	 * 
	 * @param Ext_Gui2_Dialog $oDialogData
	 * @param type $aSelectedIds
	 * @param type $sAdditional
	 * @return type
	 */
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {
		
		if($sAdditional == 'allocate_vat_rates') {

			#$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);

			$oDialogData->getDataObject()->setWDBasic('\Ts\Model\VatAllocation');
			
			// Elemente des Dialoges zurücksetzen
			$oDialogData->aElements = array();	
			// alle Schulen des Clienten holen
			$aSchools = self::getSchools();

			// Wenn keine Schulen vorhanden sind, dann muss eine Fehlermeldung angezeigt werden,
			// damit der Dialog richtig angezeigt wird.
			if (empty($aSchools)) {
				$oNotification = $oDialogData->createNotification($this->_oGui->t('Fehler beim Laden der Zuweisungen'), $this->_oGui->t('Sie haben keine Schule in der die Steuern aktiviert sind.'), 'info');
				$oDialogData->setElement($oNotification);
				$oDialogData->save_button = false;
			}
			
			$aCountries = self::getSchoolCountries($aSchools);
			$aVatRates	= $this->_getVatRates();

			// Länder der Schulen durchlaufen
			foreach($aCountries as $sIso => $sValue) {
				
				// Pro Land wird ein Tab erzeugt
				$oTab = $oDialogData->createTab('<img src="/admin/media/flag_'.strtolower($sIso).'.gif" /> '. $sValue);

				// Prüfen ob schon Umsatzsteuersätze für das jeweilige Land angelegt wurden
				if(empty($aVatRates[$sIso])) {
					
					$oNotification = $oDialogData->createNotification($this->_oGui->t('Fehler beim Laden der Zuweisungen'), sprintf($this->_oGui->t('Sie haben noch keine Umsatzssteuer für das Land "%s" angelegt'), $sValue), 'info');
					$oTab->setElement($oNotification);
					
				} else {

					$oTab->setElement($oDialogData->createRow($this->_oGui->t('Zeitpunkt für die Ermittlung des Steuersatzes'), 'select', ['db_column'=>'ts_vat_reference_date_'.$sIso, 'default_value'=> System::d('ts_vat_reference_date_'.$sIso, Ext_TS_VAT::REFERENCE_DATE_CURRENT), 'select_options'=> Ext_Thebing_Util::addEmptyItem([Ext_TS_VAT::REFERENCE_DATE_CURRENT=>$this->t('Aktuelles Datum'), Ext_TS_VAT::REFERENCE_DATE_SERVICE_START=>$this->t('Leistungsbeginn'), Ext_TS_VAT::REFERENCE_DATE_SERVICE_END=>$this->t('Leistungsende')])]));
					
					$oTab->setElement(
						$oDialogData->createRow(
							$this->_oGui->t('Separate Steuerzuweisung für Provisionsrechnungen'), 
							'checkbox', 
							[
								'db_column'=>'ts_vat_commission_country_'.$sIso, 
								'default_value'=> System::d('ts_vat_commission_country_'.$sIso),
								'child_visibility' => [
									[
										'id' => 'commission_country_container',
										'on_values' => [
											1
										]
									]
								]
							]
						)
					);
					
					// Umsatzsteuersätze des Landes durchlaufen
					foreach((array)$aVatRates[$sIso] as $oVatRate) {

						$this->addServiceSelects($oTab, $sIso, $oVatRate);
						
					}

					$oDiv = $oDialogData->create('fieldset');
					$oDiv->id = 'commission_country_container';
					$oDivLegend = new Ext_Gui2_Html_Fieldset_Legend;
					$oDivLegend->setElement($this->t('Steuerzuweisung für Provisionsrechnungen nach Agenturland'));
					$oDiv->setElement($oDivLegend);
					
					$oObjectContainer = $oDialogData->createJoinedObjectContainer('commission_vats_'.$sIso);
					$oObjectContainer->setElement($oObjectContainer->createRow($this->_oGui->t('Inland/Ausland'), 'select', ['db_column'=>'commission_vat', 'db_alias'=>$sIso, 'select_options'=> Ext_Thebing_Util::addEmptyItem([self::COMMISSION_VAT_DOMESTIC=>$this->t('Inland'), self::COMMISSION_VAT_ABROAD=>$this->t('Ausland')])]));
					
					$aVatOptions = [];
					foreach((array)$aVatRates[$sIso] as $oVatRate) {
						$aVatOptions[$oVatRate->id] = $oVatRate->getName();
					}

					$oObjectContainer->setElement($oObjectContainer->createRow($this->_oGui->t('Steuersatz'), 'select', ['db_column'=>'vat_rate_id', 'db_alias'=>$sIso, 'select_options'=> Ext_Thebing_Util::addEmptyItem($aVatOptions)]));
					
					$this->addServiceSelects($oObjectContainer, $sIso);
					
					$oDiv->setElement($oObjectContainer);
					
					$oTab->setElement($oDiv);
					
				}
				
				$oDialogData->setElement($oTab);
			}
			
		}
		
		$aData = parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);
		
		return $aData;
	}
	
	protected function addServiceSelects($oContainer, $sIso, $oVatRate=null) {
		
		$oDialog = $oContainer->getDialog();
		
		$aSchools = self::getSchools();
		
		// Elemente, für die ein MS aufgebaut wird
		$aElements = $this->_getElementData($aSchools, $sIso);

		if($oVatRate !== null) {
			// Überschrift
			$oH3 = $oDialog->create('h4');
			$oH3->setElement($oVatRate->name);
			$oContainer->setElement($oH3);
		}

		// Pro Element ein MS aufbauen
		foreach($aElements as $sType => $aData) {

			// Zusätzliche Kosten werden in einem größeren MS dargestellt
			$iMultiple = 5;
			if($sType == 'costs') {
				$iMultiple *= 2;
			}

			// Vorausgewählte Werte holen							
			#$aDefaultValue = $this->_getDefaultValue($oVatRate, $sIso, $aData['class'], $aData['data']);
			// alle Einträge rausfiltern, die bei einer anderen Kombination bereits ausgewählt wurden
			
			if($oContainer instanceof Ext_Gui2_Dialog_JoinedObjectContainer) {
				$sColumn = 'combination-container-'.$aData['class'];
			} else {
				$sColumn = 'combination-'.$aData['class'];			
			}

			if($oVatRate !== null) {
				$sAlias = $sIso.'_'.$oVatRate->id;
			} else {
				$sAlias = $sIso;
			}
			
			$oContainer->setElement($oContainer->createRow($this->_oGui->t($aData['label']), 'select', array(
				'db_column'			=> $sColumn,
				'db_alias'			=> $sAlias,
				'select_options'	=> (array)$aData['data'],
				'multiple'			=> $iMultiple,
				'jquery_multiple'	=> true,
				#'default_value'		=> $aDefaultValue,
				'searchable'		=> true,
				'class'				=> 'vat_allocation_multiselect'
			)));				
		}

	}


	/**
	 * gibt die Liste der verfügbaren Schulen zurück
	 * @return array Ext_Thebing_School
	 */
	static public function getSchools() {
		
		$aSchools = Ext_Thebing_Client::getSchoolList(false, 0, true);
		
		$aReturn = array();
		foreach ($aSchools as $oSchool) {			
			$iTax = (int) $oSchool->tax;
			// Schulen, die "keine Steuer" ausgewählt haben, dürfen nicht berücksichtigt werden
			if($iTax > 0) {
				$aReturn[$oSchool->id] = $oSchool;
			}
		}
			
		return $aReturn;
	}
	
	/**
	 * alle Länder der angelegten Schulen holen
	 * @param array $aSchools
	 * @return array
	 */
	static public function getSchoolCountries($aSchools) {
		
		$aReturn = array();			
		$aCountries	= Ext_Thebing_Data::getCountryList(false);
		
		// Schulen durchlaufen
		foreach ($aSchools as $oSchool) {
			/* @var $oSchool Ext_Thebing_School */
			if(
				$oSchool->country_id != '' &&
				empty($aReturn[$oSchool->country_id])
			) {
				$aReturn[$oSchool->country_id] = $aCountries[$oSchool->country_id];
			}
		}
		
		return $aReturn;
	}
	
	/**
	 * baut ein Array mit den Umsatzsteuersätzen auf
	 * @return array
	 */
	protected function _getVatRates() {
		
		$aVatRates = Ext_TS_Vat::getSelectOptions();
		
		$aReturn = array();		
		foreach($aVatRates as $iId => $sVatRate) {
			$oVatRate = Ext_TS_Vat::getInstance($iId); 
			// Nach Land filtern
			$aReturn[$oVatRate->country][$oVatRate->id] = $oVatRate;
		}
		
		return $aReturn;
	}
	
	/**
	 * Baut ein Array mit den Sachen auf, die nachher für das MS wichtig sind
	 * Pro Element wird ein MS aufgebaut
	 * @param array $aSchools
	 * @param string $sIso
	 * @return array
	 */
	protected function _getElementData($aSchools, $sIso) {

		// Cache prüfen
		if(empty($this->_aElementCache[$sIso])) {

			$sLang = System::getInterfaceLanguage();
			
			// Alle Schulen durchlaufen und die schulbezogenen Daten sammeln
			foreach($aSchools as $oSchool) {
				/* @var $oSchool Ext_Thebing_School */

				$sSchool = $oSchool->short;

				// Kurse
				$aSchoolCourses = (array) $oSchool->getCourseList();
				foreach($aSchoolCourses as $iCourseId => $sCourseValue) {
					$this->_aElementCache[$oSchool->country_id]['courses'][$iCourseId] = $sSchool.' - '.$sCourseValue;
				}

				//Unterkunft
				$aSchoolAccommodations = (array) $oSchool->getAccommodationList();
				foreach($aSchoolAccommodations as $iAccommodationId => $sAccommodationValue) {
					$this->_aElementCache[$oSchool->country_id]['accommodations'][$iAccommodationId] = $sSchool.' - '.$sAccommodationValue;
				}

//				//Generelle Gebühren
//				$aSchoolCosts = (array) $oSchool->getGeneralCosts();
//				foreach($aSchoolCosts as $oCost) {
//					$this->_aElementCache[$oSchool->country_id]['costs'][$oCost->id] = $sSchool.' - '.$oCost->getName();
//				}			
//
//				//Zusätzliche Gebühren
//				$aSchoolAdditionalCosts = $oSchool->getAdditionalCosts();				
//				foreach($aSchoolAdditionalCosts as $oAdditionalCost) {
//					$this->_aElementCache[$oSchool->country_id]['costs'][$oAdditionalCost->id] = $sSchool.' - '.$oAdditionalCost->getName();
//				}

				$oPrice							= new Ext_Thebing_Price($oSchool);
				$aAdditionalCourseCosts			= $oPrice->getAdditionalCourseCostList();
				$aAdditionalAccommodationCosts	= $oPrice->getAdditionalAccommodationCostList();
				$aAdditionalGeneralCosts		= $oPrice->getAdditionalGeneralCostList();
				$aCosts							= array_merge($aAdditionalCourseCosts, $aAdditionalAccommodationCosts, $aAdditionalGeneralCosts);
				
				foreach($aCosts as $aData) {
					$this->_aElementCache[$oSchool->country_id]['costs'][$aData['id']] = $sSchool.' - '.$aData['name_'.$sLang];
				}
				 
			}
			
			// Sonstiges
			$aInsurances = array();
			$oInsurance = new Ext_Thebing_Insurance();
			$aTempInsurances = $oInsurance->getArrayList();
			foreach($aTempInsurances as $aInsuranceData) {
				$oInsurance = Ext_Thebing_Insurance::getInstance($aInsuranceData['id']);
				$aInsurances[$oInsurance->id] = $oInsurance->getName();
			}
			$aInsurances[Ext_TS_Vat_Combination::OTHERS_TRANSFER] = $this->_oGui->t('Transfer');
			$aInsurances[Ext_TS_Vat_Combination::OTHERS_EXRAPOSITION] = $this->_oGui->t('Nicht zugeordnete Extrapositionen');
			$aInsurances[Ext_TS_Vat_Combination::OTHERS_ACTIVITY] = $this->t('Aktivitäten');
//			$aInsurances[Ext_TS_Vat_Combination::OTHERS_SPECIAL] = $this->t('Specials');
//			$aInsurances[Ext_TS_Vat_Combination::OTHERS_CANCELLATION] = $this->t('Stornogebühren');

			$this->_aElementCache['OTHER'] = $aInsurances;
			
		}

		// Elemente
		$aElements = array(
			'courses' => array(
				'class' => 'Ext_Thebing_Tuition_Course',
				'label' => 'Kurse',
				'data' => $this->_aElementCache[$sIso]['courses']
			),
			'accommodations' => array(
				'class' => 'Ext_Thebing_Accommodation',
				'label' => 'Unterkünfte',
				'data' => $this->_aElementCache[$sIso]['accommodations']
			),
			'costs' => array(
				'class' => 'Ext_Thebing_School_Cost',
				'label' => 'Zusätzliche Kosten',
				'data' => $this->_aElementCache[$sIso]['costs']
			),
			'other' => array(
				'class' => 'Ext_Thebing_Insurances',
				'label' => 'Sonstiges',
				'data' => $this->_aElementCache['OTHER']
			)
		);
		
		return $aElements;
	}
	
	protected function saveEditDialogData(array $aSelectedIds, $aSaveData, $bSave=true, $mAction='edit', $bPrepareOpenDialog = true) {
		global $_VARS;
		
		if($mAction['additional'] == 'allocate_vat_rates') {
			
			unset($_VARS['clonedata']);
			
			if($bSave === true) {
				$sSql = "TRUNCATE TABLE `ts_vat_rates_combinations_to_objects`";
				DB::executeQuery($sSql);
			}
			
		}

		$aData = parent::saveEditDialogData($aSelectedIds, $aSaveData, $bSave, $mAction, $bPrepareOpenDialog);
		$aData['data']['old_id'] = $_VARS['dialog_id'];
		$aData['data']['force_new_dialog'] = true;
		return $aData;
	}
		
}
