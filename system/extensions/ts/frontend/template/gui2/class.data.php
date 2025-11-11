<?php

class Ext_TS_Frontend_Template_Gui2_Data extends Ext_TC_Frontend_Template_Gui2_Data {

	/**
	 * @see \Ext_TS_Frontend_Combination::_getObjectForUsage()
	 *
	 * @inheritdoc
	 */
	public static function getUsageOptions($bWithEmptyItem = false) {

		$aOptions = parent::getUsageOptions($bWithEmptyItem);

		$aOptions[Ext_Thebing_Form::TYPE_REGISTRATION_V3] = L10N::t('Anmeldeformular V3', self::TRANSLATION_PATH);
		$aOptions['pricelist'] = L10N::t('Preisliste', self::TRANSLATION_PATH);
		$aOptions['agency_login'] = L10N::t('Agentur-Portal', self::TRANSLATION_PATH);
		if (\TcExternalApps\Service\AppService::hasApp(\TsContactLogin\Handler\ExternalApp::APP_NAME)) {
			$aOptions['student_login'] = L10N::t('Schüler-Portal', self::TRANSLATION_PATH);
		}
		$aOptions['payment_form'] = L10N::t('Zahlungsformular', self::TRANSLATION_PATH);
		$aOptions['course_details'] = L10N::t('Kursdetails', self::TRANSLATION_PATH);
		$aOptions['course_list'] = L10N::t('Kursliste', self::TRANSLATION_PATH);
		$aOptions['accommodation_categories'] = L10N::t('Unterkunftskategorien', self::TRANSLATION_PATH);
		$aOptions['course_categories'] = L10N::t('Kurskategorien', self::TRANSLATION_PATH);
		$aOptions['accommodation_category'] = L10N::t('Unterkunftskategorie', self::TRANSLATION_PATH);
		$aOptions['course_category'] = L10N::t('Kurskategorie', self::TRANSLATION_PATH);
		$aOptions['placementtest'] = L10N::t('Einstufungstest', self::TRANSLATION_PATH);

		asort($aOptions);

		return $aOptions;
	}

	/**
	 * @inheritdoc
	 */
	public static function getUsagesWithDefaultTemplates() {

		$aDefaultTemplates = parent::getUsagesWithDefaultTemplates();

		$aDefaultTemplates[Ext_Thebing_Form::TYPE_REGISTRATION_NEW] = Util::getDocumentRoot().'storage/templates/form/form_new.tpl';
		$aDefaultTemplates[Ext_Thebing_Form::TYPE_ENQUIRY] = Util::getDocumentRoot().'storage/templates/form/form_new.tpl';
		$aDefaultTemplates['pricelist'] = Util::getDocumentRoot().'storage/templates/pricelist.tpl';
		$aDefaultTemplates['agency_login'] = Util::getDocumentRoot().'storage/templates/login/agency/agency_login.tpl';
		$aDefaultTemplates['student_login'] = Util::getDocumentRoot().'storage/templates/login/student/student_login.tpl';
		$aDefaultTemplates['placementtest'] = Util::getDocumentRoot().'storage/templates/placementtest.tpl';

		return $aDefaultTemplates;

	}

	/**
	 * @return string
	 * @param bool $bWithDocumentRoot
	 */
	public static function getTemplatePath($bWithDocumentRoot = true) {

		$sPath = 'storage/templates/form/';
		if($bWithDocumentRoot) {
			return Util::getDocumentRoot().$sPath;
		}

		return $sPath;

	}

	/**
	 * @param string $sFormType
	 * @return string
	 */
	public static function getDefaultCSSTemplate($sFormType) {

		return file_get_contents(self::getTemplatePath().'form_new.css.tpl');

	}

}
