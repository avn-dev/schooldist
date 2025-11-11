<?php

class Ext_Thebing_Tuition_Level_Gui2_Data extends Ext_Thebing_Gui2_Data {

	public function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {
		if($sError === 'LEVEL_IN_USE') {
			$sMessage = $this->t('Der ausgewählte Eintrag wird noch bei Klassen, Buchungen oder Anfragen verwendet.');
		} elseif($sError === 'LEVEL_ASSIGNMENT_IN_USE') {
			$sMessage = $this->t('Der ausgewählte Prozentbereich wird schon bei einem anderen Level verwendet.');
		}elseif($sError === 'LEVEL_BOTH_FIELDS') {
			$sMessage = $this->t('Es darf nicht nur ein Feld der Automatischen Levelzuweisung ausgefüllt sein.');
		}elseif($sError === 'LEVEL_FROM_LOWER') {
			$sMessage = $this->t('Der "bis"-Wert muss über dem "Von"-Wert liegen');
		} else {
			$sMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sMessage;
	}
	
	static public function getDialog(Ext_Thebing_Gui2 $oGui) {

		$aSchools = Ext_Thebing_Client::getSchoolList(true);
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$sDefaultLang = $oSchool->getInterfaceLanguage();
		$aLevelsTypes = Ext_Thebing_Util::getStudentLevels();
		
		/*$oDefaultColumn = $oGui->getDefaultColumn();
		$oDefaultColumn->addValidUntilColumn();
		$oDefaultColumn->setAliasForAll('ktul');
		$oGui->setDefaultColumn($oDefaultColumn);

		$oGui->addDefaultColumns();*/
		
		$sTitle = L10N::t('Leistungsstand "{name}" editieren', $oGui->gui_description);
		$sTitle = str_replace("{name}", '{name_'.$sDefaultLang.'}', $sTitle);
		$oDialog = $oGui->createDialog($sTitle, L10N::t('Neuen Leistungsstand anlegen', $oGui->gui_description));
		$oDialog->width = 900;
		$oDialog->height = 650;
		$oDialog->aOptions['section'] = 'tuition_course_proficiency';

		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;
		$oDialog->save_bar_default_option = 'new';
		
		$translationLanguages = \Ext_Thebing_Util::getTranslationLanguages();
		
		$oDialog->setElement(
			$oDialog->createI18NRow(
				$oGui->t('Bezeichnung'), 
				[
					'type' => 'input',
					'db_alias' => 'ktul', 
					'db_column_prefix' => 'name_',
					'required' => 1
				],
				$translationLanguages
			)
		);
				
		$oDialog->setElement($oDialog->createRow(
				L10N::t(
						'Kürzel', $oGui->gui_description), 
						'input', array('db_alias' => 'ktul', 
						'db_column'=>'name_short','required' => 1)));
		$oDialog->setElement($oDialog->createRow(
				L10N::t(
						'Typ', $oGui->gui_description), 
						'select', array('db_alias' => 'ktul', 
						'db_column'=>'type', 
						'select_options' => $aLevelsTypes)));
		$oDialog->setElement($oDialog->createRow(
				$oGui->t('Schulen'), 
				'select', 
				array(
					'db_alias' => 'ktul', 
					'db_column'=>'schools', 
					'select_options' => $aSchools,
					'multiple'=>5,
					'jquery_multiple' => true,
					'required' => true
				)
			)
		);

		$oDialog->setElement($oDialog->createMultiRow($oGui->t('Automatische Levelzuweisung (Einstufungstest)'), [
			'db_alias' => 'ktul',
			'items' => [
				[
					'text_before' => $oGui->t('Von '),
					'db_column' => 'automatic_assignment_from',
					'input' => 'input',
					'style' => 'width: 100px;',
					'text_after' => '%',
					'format' => new \Ext_Thebing_Gui2_Format_Float(2, true, true),
					'dependency_visibility' => [
						'db_column' => 'type',
						'db_alias' => 'ktul',
						'on_values' => ['internal']
					]
				],
				[
					'text_before' => $oGui->t('bis '),
					'db_column' => 'automatic_assignment_until',
					'input' => 'input',
					'style' => 'width: 100px;',
					'text_after' => '%',
					'format' => new \Ext_Thebing_Gui2_Format_Float(2, true, true),
					'dependency_visibility' => [
						'db_column' => 'type',
						'db_alias' => 'ktul',
						'on_values' => ['internal']
					]
				],
			],
		]));

		return $oDialog;
	}

	static public function manipulateSearchFilter(Ext_Thebing_Gui2 $oGui) {
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$defaultLang = $oSchool->getInterfaceLanguage();
		
		return [
			'column' => [
				'id',
				'name_'.$defaultLang
			]
		];
	}

}
