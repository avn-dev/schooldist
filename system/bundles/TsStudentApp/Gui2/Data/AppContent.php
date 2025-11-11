<?php

namespace TsStudentApp\Gui2\Data;

class AppContent extends \Ext_Thebing_Gui2_Data {

	/**
	 * @param \Ext_TC_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 */
	public static function getDialog($oGui) {

		$aLanguages = self::getLanguages();

		$oDialog = $oGui->createDialog($oGui->t('Eintrag editieren'), $oGui->t('Neuen Eintrag anlegen'));

		$oTab = $oDialog->createTab($oGui->t('Inhalt'));

		$oTab->setElement($oDialog->createRow($oGui->t('Freigegeben?'), 'checkbox', array(
			'db_column' => 'released'
		)));

		$oTab->setElement($oDialog->createI18NRow($oGui->t('Titel'), array(
			'db_alias' => 'i18n',
			'db_column'=> 'title',
			'i18n_parent_column' => 'entry_id',
			'required' => true
		), $aLanguages));

		$oTab->setElement($oDialog->createRow($oGui->t('Typ'), 'select', array(
			'db_column' => 'type',
			'select_options' => self::getTypeOptions($oGui)
		)));

		$oTab->setElement($oDialog->createI18NRow($oGui->t('Inhalt'), array(
			'type' => 'html',
			'db_alias' => 'i18n',
			'db_column'=> 'content',
			'i18n_parent_column' => 'entry_id',
			'advanced' => true
		), $aLanguages));

		$oDialog->setElement($oTab);

		$oTabPlaceholder = $oDialog->createTab($oGui->t('Platzhalter'));

		$oPlaceholerObject = (new \Ext_TS_Inquiry())->getPlaceholderObject();
		$oTabPlaceholder->setElement($oPlaceholerObject->displayPlaceholderTable());

		$oDialog->setElement($oTabPlaceholder);

		return $oDialog;

	}

	public static function getListWhere() {
		$aWhere	= array();

		$oSchool = \Ext_Thebing_School::getSchoolFromSession();
		if($oSchool->id > 0) {
			$aWhere['ts_sac.school_id'] = (int)$oSchool->id;
		};

		return $aWhere;
	}

	public static function getLanguages(): array {

		$aLanguages = \Ext_Thebing_Util::getTranslationLanguages();

		if (\System::d('ts_student_app_languages')) {
			$aConfiguredLanguages = json_decode(\System::d('ts_student_app_languages'), true);
			$aLanguages = array_filter($aLanguages, function ($aLanguage) use ($aConfiguredLanguages) {
				return in_array($aLanguage['iso'], $aConfiguredLanguages);
			});
		}

		return $aLanguages;

	}

	public static function getTypeOptions(\Ext_TC_Gui2 $gui2) {
		return collect(\TsStudentApp\Enums\AppContentType::cases())
			->mapWithKeys(fn ($enum) => [$enum->value => $enum->getLabelText($gui2->getLanguageObject())])
			->toArray();
	}

}