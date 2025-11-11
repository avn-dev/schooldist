<?php

class Ext_Thebing_Accommodation_Roomtype_Gui2 extends Ext_Thebing_Gui2_Data {

	/**
	 * {@inheritdoc}
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null)  {

		if($sError == 'JOURNEY_ACCOMMODATIONS_EXISTS') {
			$sErrorMessage = $this->t('Es existieren noch Buchungen zu dieser Raumart.');
		} else {
			$sErrorMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sErrorMessage;

	}
	
	static public function getOrderby() {
		
		$sDefaultLang = \Ext_Thebing_Util::getInterfaceLanguage();
		
		return ['kar.name_'.$sDefaultLang => 'ASC'];
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
	
	static public function getDialog(\Ext_Gui2 $oGui) {

		$sDefaultLang = \Ext_Thebing_Util::getInterfaceLanguage();
		$aTranslationLanguages	= Ext_Thebing_Util::getTranslationLanguages();
		
		$aMatchingTypes = \Ext_Thebing_Accommodation_Roomtype::getTypeOptions($oGui->getLanguageObject());
		
		$aSchools = Ext_Thebing_Client::getSchoolList(true);

		$oDialog = $oGui->createDialog(
			$oGui->t('Zimmer editieren').' - {name_'.$sDefaultLang.'}',
			$oGui->t('Neues Zimmer anlegen')
		);

		$oDialog->width = 900;
		$oDialog->height = 650;
		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;
		$oDialog->save_bar_default_option = 'new';
		$oDialog->aOptions['section'] = 'roomtypes';

		$oDialog->setElement($oDialog->createI18NRow($oGui-> t('Bezeichnung', $oGui->gui_description), [
			'db_column_prefix' => 'name_',
			'db_alias' => 'kar',
			'required' => true
		], $aTranslationLanguages));

		$oDialog->setElement($oDialog->createI18NRow($oGui-> t('Kürzel', $oGui->gui_description), [
			'db_column_prefix' => 'short_',
			'db_alias' => 'kar',
			'required' => true
		], $aTranslationLanguages));

		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Schulen'),
				'select',
				[
					'db_alias' => '',
					'db_column' => 'schools',
					'multiple' => 5,
					'select_options' => $aSchools,
					'jquery_multiple' => 1,
					'searchable' => 1,
					'required' => 1,
				]
			)
		);

		$oDialog->setElement(
			$oDialog->createRow(
				L10N::t('Zimmer Typ', $oGui->gui_description),
				'select',
				[
					'db_alias' => 'kar',
					'db_column'=> 'type',
					'select_options' => $aMatchingTypes,
				]
				)
		);	
		
		return $oDialog;
	}
	
}
