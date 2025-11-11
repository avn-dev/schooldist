<?php

class Ext_Thebing_Accommodation_Meal_Gui2 extends Ext_Thebing_Gui2_Data
{

	/**
	 * {@inheritdoc}
	 */
	protected function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null)
	{

		if($sError == 'JOURNEY_ACCOMMODATIONS_EXISTS') {
			$sErrorMessage = $this->t('Es existieren noch Buchungen zu dieser Verpflegung.');
		} else {
			$sErrorMessage = parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional);
		}

		return $sErrorMessage;

	}
	
	static public function getOrderby()
	{
			$sDefaultLang = \Ext_Thebing_Util::getInterfaceLanguage();

			return ['kam.name_'.$sDefaultLang => 'ASC'];
		}

	static public function getDialog(\Ext_Gui2 $oGui)
	{
		
		$aSchools = Ext_Thebing_Client::getSchoolList(true);
		$aTranslationLanguages	= Ext_Thebing_Util::getTranslationLanguages();
		$sDefaultLang = Ext_Thebing_Util::getInterfaceLanguage();
		
		$oDialog = $oGui->createDialog(
			L10N::t('Verpflegung editieren', $oGui->gui_description).' - {name_'.$sDefaultLang.'}',
			L10N::t('Neue Verpflegung anlegen', $oGui->gui_description)
		);

		$oDialog->width = 900;
		$oDialog->height = 650;
		$oDialog->save_as_new_button = true;
		$oDialog->save_bar_options = true;
		$oDialog->save_bar_default_option = 'new';
		$oDialog->aOptions['section'] = 'meals';

		$oDialog->setElement($oDialog->createI18NRow($oGui-> t('Bezeichnung', $oGui->gui_description), [
			'db_column_prefix' => 'name_',
			'db_alias' => 'kam',
			'required' => true
		], $aTranslationLanguages));

		$oDialog->setElement($oDialog->createI18NRow($oGui-> t('Kürzel', $oGui->gui_description), [
			'db_column_prefix' => 'short_',
			'db_alias' => 'kam',
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

		if(Ext_Thebing_Access::hasRight('thebing_accommodation_mealplan')) {

			$oDialog->setElement($oDialog->create('h4')->setElement($oGui->t('Mahlzeiten')));

			$oDialog->setElement(
				$oDialog->createRow(
					$oGui->t('Frühstück'),
					'checkbox',
					[
						'db_alias' => 'kam',
						'db_column' => 'meal_plan_breakfast'
					]
				)
			);

			$oDialog->setElement(
				$oDialog->createRow(
					$oGui->t('Mittagessen'),
					'checkbox',
					[
						'db_alias' => 'kam',
						'db_column' => 'meal_plan_lunch'
					]
				)
			);

			$oDialog->setElement(
				$oDialog->createRow(
					$oGui->t('Abendessen'),
					'checkbox',
					[
					'db_alias' => 'kam',
					'db_column' => 'meal_plan_dinner'
					]
				)
			);

		}

		return $oDialog;
	}

	static public function manipulateSearchFilter(\Ext_Gui2 $oGui)
	{
		
		$defaultLang = \Ext_Thebing_Util::getInterfaceLanguage();
		
		return [
			'column' => [
				'id',
				'name_'.$defaultLang
			]
		];
		
	}

}
	