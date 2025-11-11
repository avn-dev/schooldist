<?php

class Ext_Thebing_Provision_Gui2 extends Ext_Thebing_Gui2_Data {

	public function prepareOpenDialog($sIconAction, $aSelectedIds, $iTab=false, $sAdditional=false, $bSaveSuccess = true) {

		if (
			$sIconAction == 'new' ||
			$sIconAction == 'edit'
		) {
			
			$this->getWDBasicObject($aSelectedIds);

			$gui2Dialog = self::getDialog($this->_oGui, $this->oWDBasic);

			//Dialog für $sIconKey setzen
			$this->aIconData['new']['dialog_data'] = $gui2Dialog;
			$this->aIconData['edit']['dialog_data'] = $gui2Dialog;

		}

		return parent::prepareOpenDialog($sIconAction, $aSelectedIds, $iTab, $sAdditional, $bSaveSuccess);
	}
	
	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {

		// Daten für die HTML Klassen setzten
		Ext_Thebing_Provision_Gui2_Html::$sL10NDescription = $this->_oGui->gui_description;
		Ext_Thebing_Provision_Gui2_Html::$oCalendarFormat = $this->_oGui->_oCalendarFormat;
		Ext_Thebing_Provision_Gui2_Html::$sGuiHash = $this->_oGui->hash;

		$aData = $oDialogData->generateAjaxData($aSelectedIds, $this->_oGui->hash);

		foreach($aData['tabs'] as &$aTabData) {
			switch($aTabData['options']['task']) {
				case 'course_provision':
					$aTabData['html'] = Ext_Thebing_Provision_Gui2_Html::getProvisionTabHtml('course', $oDialogData, $aSelectedIds);
					break;
				case 'accommodation_provision':
					$aTabData['html'] = Ext_Thebing_Provision_Gui2_Html::getProvisionTabHtml('accommodation', $oDialogData, $aSelectedIds);
					break;
				case 'general_provision':
					$aTabData['html'] = Ext_Thebing_Provision_Gui2_Html::getProvisionTabHtml('general', $oDialogData, $aSelectedIds);
					break;
				case 'transfer_provision':
					$aTabData['html'] = Ext_Thebing_Provision_Gui2_Html::getProvisionTabHtml('transfer', $oDialogData, $aSelectedIds);
					break;
				case 'activity_provision':
					$aTabData['html'] = Ext_Thebing_Provision_Gui2_Html::getProvisionTabHtml('activity', $oDialogData, $aSelectedIds);
					break;
			}
		}

		return $aData;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional=false, $bSave=true) {
		global $_VARS;

		$aTransferData = parent::saveDialogData($sAction, $aSelectedIds, $aData, $sAdditional, $bSave);
		$parentDialogId = 0;
		$iSelectedId = (int)$aTransferData['save_id'];

		if(!$bSave) {
			return $aTransferData;
		}
		
		if($iSelectedId > 0) {

			$category = Ext_Thebing_Provision_Group::getInstance($iSelectedId);

			if($category->old_structure) {
				
				// Speichern der Provisionen
				$aCourse = (array)$_VARS['course'];
				$aCourseAdditional = (array)$_VARS['additional_course'];
				$aAccommodation = (array)$_VARS['accommodation'];
				$aAccommodationAdditional = (array)$_VARS['additional_accommodation'];
				$aExtraNight = (array)$_VARS['extra_night'];
				$aGeneral = (array)$_VARS['general'];
				$aTransfer = (array)$_VARS['transfer'];
				$aActivitiy = (array)$_VARS['activity'];

				// Da alle Arrays gleich aufgebaut sind kann alles identisch gespeichert werden
				$this->saveProvision($category, $aCourse['school_id'], $aCourse, 'course');
				$this->saveProvision($category, $aCourse['school_id'], $aCourseAdditional, 'additional_course');
				$this->saveProvision($category, $aAccommodation['school_id'], $aAccommodation, 'accommodation');
				$this->saveProvision($category, $aAccommodation['school_id'], $aAccommodationAdditional, 'additional_accommodation');
				$this->saveProvision($category, $aAccommodation['school_id'], $aExtraNight, 'extra_night');
				$this->saveProvision($category, $aGeneral['school_id'], $aGeneral, 'general');
				$this->saveProvision($category, $aTransfer['school_id'], $aTransfer, 'transfer');
				$this->saveProvision($category, $aActivitiy['school_id'], $aActivitiy, 'activity');

			} else {
				
				$category->rates = $rates = [];

				$aRateTypes = $aData['rate_types'];

				$structure = [];
				foreach($aData['allocations'] as $key => $value) {
					
					// Keine leeren Werte speichern
					if($value === '') {
						continue;
					}
					
					$rate = Ext_Thebing_Format::convertFloat($value);
					
					$keyParts = explode('#', $key);
				
					if($keyParts[4] == 'no_parent') {
						$keyParts[4] = '';
					}
					
					$rates[] = [
						'category_id' => $category->id,	
						'type' => $keyParts[1],
						'type_id' => $keyParts[2],	
						'parent_type' => $keyParts[4],
						'parent_type_id' => $keyParts[3],	
						'rate' => $rate,
						'rate_type' => $aRateTypes[$key] ?? 'percent',
					];
					
				}

				$category->rates = $rates;
				
			}

			// Updated by & Updated on auf jeden Fall aktualisieren (#9660)
			$category->save(true, true);

			// Wenn "Als neuen Eintrag speichern" gedrückt wurde, "Parent"-Dialog merken
			if (!empty($aTransferData['data']['old_id'])) {
				$parentDialogId = $aTransferData['data']['old_id'];
			}

			// Nochmal aufrufen, um aktuelle Werte im Dialog zu haben
			$aTransferData['data'] = $this->prepareOpenDialog('edit', [$iSelectedId]);

			// Wenn "Als neuen Eintrag speichern" gedrückt wurde, den verloren gegangenen "Parent"-Dialog wieder setzen
			if ($parentDialogId != 0) {
				$aTransferData['data']['force_new_dialog'] = true;
				$aTransferData['data']['old_id'] = $parentDialogId;
			}
		}

		return $aTransferData;
	}

	public function saveProvision($oGroup, $iSchoolId, $aData, $sType){

		$iSchoolId = (int)$iSchoolId;

		if($aData['school_id']){
			unset($aData['school_id']);
		}
        
		// Schulid für diesen Tab
		if($iSchoolId < 1){
			// Tab nicht speichern
			return ;
		}
		
		// Schulobject für den Tab
		$oSchool = Ext_Thebing_School::getInstance($iSchoolId);

		// NEUE Daten speichern	
		foreach((array)$aData as $iSeason => $mSeasonData){

			if(!is_array($mSeasonData)){
				// Alle weiteren male speichern da hier schon die Objekte existieren
				$fProvision = Ext_Thebing_Format::convertFloat($mSeasonData);

				$oProvision = Ext_Thebing_Provision_Group_Provision::getInstance($iSeason);
				$oProvision->provision = $fProvision;
				$oProvision->save();
			} else {
				// Erstes speichern da hieralle IDs mitgeschickt werden müssen
				foreach((array)$mSeasonData as $iCategory => $aCategoryData){

					foreach((array)$aCategoryData as $iItemId => $mItemData){

						foreach((array)$mItemData as $iAdditionalId => $fProvision){
							$fProvision						= Ext_Thebing_Format::convertFloat($fProvision);

							$oProvision = Ext_Thebing_Provision_Group_Provision::getInstance(0);
							$oProvision->active				= 1;
							$oProvision->group_id			= (int)$oGroup->id;
							$oProvision->school_id			= (int)$oSchool->id;
							$oProvision->category_id		= (int)$iCategory;
							$oProvision->season_id			= (int)$iSeason;
							$oProvision->type_id			= (int)$iItemId;
							$oProvision->additional_id		= (int)$iAdditionalId; // Hier Room Id mitspeichern
							$oProvision->type				= $sType;
							$oProvision->provision			= $fProvision;
							$oProvision->save();

						}
					}
				}
			}
		}
		
	}

	public function switchAjaxRequest($_VARS) {

		$aTransfer = $this->_switchAjaxRequest($_VARS);

		switch($_VARS['task']){
			case 'updateProvisionTab':
				$iSelectedId	= (int)$_VARS['id'][0];
				$iSchool		= (int)$_VARS['school_id'];
				$sType			= $_VARS['type'];
				$aYears			= (array)$_VARS['save']['year_select'];

				$aTransfer						= array();
				$aTransfer['action']			= 'updateProvisionTabCallback';
				$aTransfer['error']				= array();
				$aTransfer['data']['id']		= 'ID_'.$iSelectedId;
				$aTransfer['data']['iSchool']	= $iSchool;
				$aTransfer['data']['type']		= $sType;
				$aTransfer['data']['years']		= $aYears;
				$aTransfer['data']['html']		= Ext_Thebing_Provision_Gui2_Html::getProvisionTable($iSelectedId, $iSchool, $sType, $aYears);
				$aTransfer['success_message']	= $this->t('Provisionen wurden geladen.');

				break;
		}


		echo json_encode($aTransfer);
	}

	static public function getDialog(\Ext_Gui2 $gui, \Ext_Thebing_Provision_Group $category=null) {

		// Dialog
		$oDialog = $gui->createDialog($gui->t('Provisionsgruppe "{name}" editieren'), $gui->t('Neue Provisionsgruppe anlegen'));
		$oDialog->width = 1400;
		$oDialog->save_as_new_button = true;

		if (
			$category === null ||
			!$category->exist()
		) {

			$tabInfo = $oDialog->createTab($gui->t('Daten'));
			$tabInfo->setElement($oDialog->createRow($gui->t('Bezeichnung'), 'input', ['db_alias' => '', 'db_column'=>'name','required' => 1]));
			$tabInfo->setElement($oDialog->createRow($gui->t('Kommentar'), 'textarea', ['db_alias' => '', 'db_column'=>'comment']));
			$tabInfo->setElement($oDialog->createRow($gui->t('Struktur'), 'select', [
				'db_column'=> 'old_structure',
				'select_options' => [
					0 => $gui->t('Kategorie pro Schule'),
					1 => $gui->t('Kategorie pro Agentur')
				]
			]));
			$tabInfo->setElement(
				$oDialog->createNotification($gui->t('Bitte beachten'), $gui->t('Bevor Sie die Provisionssätze dieser Provisionskategorie eintragen können müssen Sie den Dialog einmal speichern.'), 'info')
			);

			$oDialog->setElement($tabInfo);

		} else {
			if($category->old_structure) {

				$oTabInfo = $oDialog->createTab($gui->t('Daten'));
				$oTabCourse			= $oDialog->createTab($gui->t('Kurse'));
				$oTabAccommodation	= $oDialog->createTab($gui->t('Unterkünfte'));
				$oTabGeneral		= $oDialog->createTab($gui->t('Zusatz'));
				$oTabTransfer		= $oDialog->createTab($gui->t('Transfer'));
				$oTabActivities = $oDialog->createTab($gui->t('Aktivitäten'));

				$oTabInfo->setElement($oDialog->createRow($gui->t('Bezeichnung'), 'input', array('db_alias' => 'ts_coc', 'db_column'=>'name','required' => 1)));
				$oTabInfo->setElement($oDialog->createRow($gui->t('Kommentar'), 'textarea', array('db_alias' => 'ts_coc', 'db_column'=>'comment')));

				//$oTabInfo->setElement($oDialog->createNotification($this->t('Bitte beachten'), $this->t('Diese Art von Provisionskategorie ist veraltet. Bitte lege eine neue Provisionskategorie an, um von der vereinfachten Pflege zu profitieren oder stelle die Provisionskategorie mit der folgenden Einstellungen unwiderbringlich auf die neue Struktur um. Vorhandenen Provisionssätze werden dabei nicht übernommen!'), 'info'));
				//$oTabInfo->setElement($oDialog->createRow($this->t('Alte Struktur beibehalten'), 'checkbox', array('db_alias' => 'ts_coc', 'db_column'=>'old_structure', 'value'=>1)));

				$oTabInfo->aOptions = array(
					'task' => 'info_data'
				);
				$oTabCourse->aOptions = array(
					'task' => 'course_provision'
				);
				$oTabAccommodation->aOptions = array(
					'task' => 'accommodation_provision'
				);
				$oTabGeneral->aOptions = array(
					'task' => 'general_provision'
				);
				$oTabTransfer->aOptions = array(
					'task' => 'transfer_provision'
				);
				$oTabActivities->aOptions = array(
					'task' => 'activity_provision'
				);

				$oDialog->setElement($oTabInfo);
				$oDialog->setElement($oTabCourse);
				$oDialog->setElement($oTabAccommodation);
				$oDialog->setElement($oTabGeneral);
				$oDialog->setElement($oTabTransfer);
				$oDialog->setElement($oTabActivities);

			} else {

				$tabInfo = $oDialog->createTab($gui->t('Daten'));
				$tabRates = $oDialog->createTab($gui->t('Provisionssätze'));

				$tabInfo->setElement($oDialog->createRow($gui->t('Bezeichnung'), 'input', array('db_alias' => '', 'db_column'=>'name','required' => 1)));
				$tabInfo->setElement($oDialog->createRow($gui->t('Schule'), 'select',
					[
						'db_alias' => '',
						'db_column'=>'school_id',
						'class' => 'school',
						'select_options' => \Ext_Thebing_Util::addEmptyItem(Ext_Thebing_Client::getSchoolList(true)),
						'required' => 1,
						'dependency_visibility' => [
							'db_alias' => 'ts_apr',
							'db_column' => 'requirement',
							'on_values' => ['member']
						],
						'events' => [
							[
								'event' => 'change',
								'function' => 'reloadDialogTab',
								'parameter' => 'aDialogData.id, 1'
							]
						]
					]));
				$tabInfo->setElement($oDialog->createRow($gui->t('Kommentar'), 'textarea', array('db_alias' => '', 'db_column'=>'comment')));

				$tabInfo->setElement($oDialog->create('h4')->setElement($gui->t('Einstellungen')));

				$serviceTypes = self::getServiceTypes();

				foreach($serviceTypes as $serviceType) {

					$elementOptions = [
						'class' => 'settings',
						'events' => [
							[
								'event' => 'change',
								'function' => 'reloadDialogTab',
								'parameter' => 'aDialogData.id, 1'
							]
						]
					];

					$row = \TsAccounting\Gui2\Data\Company::_getAccountSettingsRow('commission', $serviceType, $gui, $oDialog, $elementOptions);

					$tabInfo->setElement($row);
				}

				if($category) {

					$type = 'commission';

					$category->createCourseAllocations($type, $category, 'commission_course');

					$category->createAdditionalAllocations($type, 'course', $category, 'commission_additional_course');

					$category->createAccommodationAllocations($type, $category, 'commission_accommodation');

					$category->createAdditionalAllocations($type, 'accommodation', $category, 'commission_additional_accommodation');

					$category->createAdditionalAllocations($type, 'general', $category, 'commission_additional_general');

					$category->createInsuranceAllocations($type, $category, 'commission_insurance');

					$category->createActivityAllocation($type, $category, 'commission_activity');

					$category->createOtherAllocations($type);

					$category->setAllocationValues();

					$groupedAllocations = $category->getGroupedAllocations($category->allocations);

					$accountType = 'commission';
					$colspan = 2;

					$table = new Ext_Gui2_Html_Table();
					$table->class = 'table table-condensed table-hover';

					foreach($groupedAllocations['commission'] as $typeAllocations => $elementData) {

						$serviceType = str_replace('_allocations', '', $typeAllocations);

						$aTypeData = \TsAccounting\Gui2\Data\Company::getServiceTypeOptions($serviceType, $accountType);

						$serviceTypeName = (string)$aTypeData['real_name'];

						if(!empty($serviceTypeName)) {
							$serviceTypeName = $gui->t($serviceTypeName);
						}

						if(!empty($serviceTypeName)) {
							self::_addSubHeader($serviceTypeName, $colspan, $table, true);
						}

						if(isset($elementData['elements'])) {
							$aElements = $elementData['elements'];

							self::_addElementsToTable($gui, $accountType, $aElements, $table, $category);
						}

						if(isset($elementData['school_data'])) {

							foreach($elementData['school_data'] as $iSchoolId => $aSchoolElements) {

								if(isset($aSchoolElements['parent_data'])) {

									foreach($aSchoolElements['parent_data'] as $iParentTypeId => $aElements) {

										$aDataParent = array(
											1 => $serviceType,
											3 => $iParentTypeId,
										);

										$sParentName = (string)$category->getAccountName($aDataParent);

										$oLabel = new Ext_Gui2_Html_Label();

										$oLabel->style = 'font-weight:bold; margin-left:5px; line-height:22px;';

										$oLabel->setElement($sParentName);

										self::_addSubHeader($oLabel, $colspan, $table);

										self::_addElementsToTable($gui, $accountType, $aElements, $table, $category);
									}

								} else {
									self::_addElementsToTable($gui, $accountType, $aSchoolElements, $table, $category);
								}

							}
						}
					}

					$tabRates->setElement($table);#(new Ext_Gui2_Html_Div())->setElement('<pre>'.print_r($groupedAllocations['commission'], 1).'</pre>'));

				}

				$oDialog->setElement($tabInfo);
				$oDialog->setElement($tabRates);

			}
		}

		return $oDialog;
	}
	
	static protected function getServiceTypes() {

		$serviceTypes = array(
			'course',
			'additional_course',
			'accommodation',
			'additional_accommodation',
			'additional_general',
			'insurance',
			'activity'
		);
		
		return $serviceTypes;
	}
		
	/**
	 * Zwischenüberschrift erstellen
	 * 
	 * @param mixed $mElement
	 * @param int $iColspan
	 * @param Ext_Gui2_Html_Table $oTable
	 * @param bool $bTh 
	 */
	static protected function _addSubHeader($mElement, $iColspan, Ext_Gui2_Html_Table $oTable, $bTh = false) {
		
		$oTr = new Ext_Gui2_Html_Table_tr();

		if($bTh) {
			$oTd = new Ext_Gui2_Html_Table_Tr_Th();
		} else {
			$oTd = new Ext_Gui2_Html_Table_Tr_Td();
		}

		$oTd->colspan = $iColspan;

		$oTd->setElement($mElement);

		$oTr->setElement($oTd);

		$oTable->setElement($oTr);	
	}
	
	/**
	 *
	 * @param string $sTitle
	 * @return Ext_Gui2_Html_H3
	 */
	protected function _getH3($sTitle) {
		
		$oH3 = new Ext_Gui2_Html_H3();
		$oH3->setElement($sTitle);
		
		return $oH3;
	}
	
	/**
	 * Zuweisungselemente in die Tabelle einfügen
	 * 
	 * @param string $accountType
	 * @param array $elements
	 * @param Ext_Gui2_Html_Table $table
	 * @param \TsAccounting\Entity\Company\AccountAllocation $category
	 */
	static protected function _addElementsToTable(Ext_Gui2 $gui, string $accountType, array $elements, Ext_Gui2_Html_Table $table, Ext_Thebing_Provision_Group $category) {

		$i = 0;
		$arrow = false;
		foreach($elements as $sRowKey => $aKeys) {

			if(
				$i == 0 && # Wenn es die erste Zeile ist und es mehr als ein Element gibt
				count($elements) > 1
			) {
				$arrow = true;
				$i++;
			}
			$tr = new Ext_Gui2_Html_Table_tr();

			$td = new Ext_Gui2_Html_Table_Tr_Td();

			$sTypeName = (string)$category->getAccountName($sRowKey);

			$type = explode('#', $sRowKey)[1];

			if(empty($sTypeName)) {
				$sTypeName = $gui->t('Alle');
			}

			$td->setElement($sTypeName);

			$tr->setElement($td);

			$td = new Ext_Gui2_Html_Table_Tr_Td();

			$sKeyCheck = $sRowKey;# . $category->sKeyDelimiter . $aHeader['currency_iso'] . $category->sKeyDelimiter . $aHeader['vat_rate'];

			if($category->hasKey($sKeyCheck)) {

				$aAllocation = $category->getAllocation($sKeyCheck);

				$oDiv = new Ext_Gui2_Html_Div();
				$oDiv->class = 'input-group input-group-sm';
				$oDiv->title = $gui->t('Provisionssatz');
				$oDiv->style = ' width: 200px;';
				$oInput = new Ext_Gui2_Html_Input();
				$oInput->type = 'text';
				$oInput->name = 'save[allocations][' . $sKeyCheck . ']';
				$oInput->id	= 'allocations_' . $sKeyCheck;
				$oInput->title = $gui->t('Provisionssatz');
				$oInput->placeholder = $gui->t('Provisionssatz');
				$oInput->class = 'txt w100 form-control input-sm '.$type.'-input';

				if(!empty($aAllocation['rate'])) {
					$oInput->value = Ext_Thebing_Format::Number($aAllocation['rate'], null, null, true, 5);
				}

				$oDiv->setElement($oInput);

				$oDivAddon = new Ext_Gui2_Html_Div();
				$oDivAddon->class = 'input-group-addon';
				$oDivAddon->style = 'padding: 0';

				$oSelect = new Ext_Gui2_Html_Select();
				$oSelect->class = 'txt w100 form-control input-sm '.$type.'-select';
				$oSelect->name = 'save[rate_types][' . $sKeyCheck . ']';
				$oSelect->id	= 'rate_type_' . $sKeyCheck;

				if($arrow) {
					$oDivAddon2 = new Ext_Gui2_Html_Div();
					$oDivAddon2->class = 'input-group-btn';

					$btnArrow = new Ext_Gui2_Html_Button();
					$btnArrow->class = 'btn btn-default btn-flat copy_value';
					$btnArrow->setDataAttribute('type', $type);

					$i = new Ext_Gui2_Html_I();
					$i->class = 'fa fa-chevron-down';

					$btnArrow->setElement($i);
					$oDivAddon2->setElement($btnArrow);

				}
				$l10n = $gui->getLanguageObject();

				foreach (\Ts\Enums\CommissionType::cases() as $enum) {
					$oSelect->addOption($enum->value, $enum->getLabel($l10n), $aAllocation['rate_type'] === $enum->value);
				}

				$oDivAddon->setElement($oSelect);

				$oDiv->setElement($oDivAddon);
				if($arrow) {
					$oDiv->setElement($oDivAddon2);
					$arrow = false;
				}

				$td->setElement($oDiv);

			}

			$tr->setElement($td);

			$table->setElement($tr);

		}
		
	}

	public static function getOrderby()
	{
		return['ts_coc.name' => 'ASC'];
	}
}