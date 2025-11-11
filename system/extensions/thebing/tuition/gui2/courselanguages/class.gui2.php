<?php

class Ext_Thebing_Tuition_Gui2_Courselanguages_Gui2 extends Ext_Thebing_Gui2_Data {

	static public function getDialog(\Ext_Gui2 $oGui) {

		$sTitleEdit = $oGui->t('Kurssprache editieren');
		$sTitleNew = $oGui->t('Neue Kurssprache anlegen');

		$oDialog = $oGui->createDialog($sTitleEdit, $sTitleNew);
		$oDialog->width = 900;
		$oDialog->height = 650;
		$oDialog->aOptions['section'] = 'tuition_course_languages';

		$oDialog->setElement(
			$oDialog->createI18NRow(
				$oGui-> t('Name', $oGui->gui_description),
				[
					'db_column_prefix' => 'name_',
					'db_alias' => '',
					'required' => true
				],
				Ext_Thebing_Util::getTranslationLanguages()
			)
		);

		$oH3 = $oDialog->create('h4')->setElement($oGui->t('Frontend'));
		$oDialog->setElement($oH3);

		$oDialog->setElement(
			$oDialog->createRow(
				L10N::t('Icon-Klasse', $oGui->gui_description),
				'input',
				[
					'db_column'=>'frontend_icon_class',
					'db_alias' => ''
				]
			)
		);

		$languages = Data_Languages::getList(\System::getInterfaceLanguage());
		$languages = Ext_TC_Util::addEmptyItem($languages);
		$oDialog->setElement(
			$oDialog->createRow(
				$oGui->t('Sprache'),
				'select',
				[
					'db_column'=>'language_iso',
					'db_alias' => '',
					'select_options' => $languages
				]
			)
		);
		return $oDialog;
	}
	
	static public function manipulateSearchFilter() {
		
		return [
			'column' => [
				'id',
				'name_'.System::getInterfaceLanguage()
			]
		];
		
	}

	protected function _getJoinedItemsErrorLabel($label)
	{
		return match ($label) {
			'tuition_classes' => $this->t('Klassen'),
			default => parent::_getJoinedItemsErrorLabel($label)
		};
	}

}