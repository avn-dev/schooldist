<?php

class Ext_Thebing_Insurances_Gui2_Insurance extends Ext_Thebing_Gui2_Data {

	/**
	 * @param bool $bForSelect
	 * @param int $iSchoolId
	 * @return mixed[]
	 */
	static public function getOrderby() {
		
		return ['kins.number' => 'ASC'];
	}

	static public function getDialog(Ext_Thebing_Gui2 $oGui){
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$sInterfaceLanguage = $oSchool->getInterfaceLanguage();
		$aTranslationLanguages = Ext_Thebing_Util::getTranslationLanguages();
		
		$sTitle = L10N::t('Versicherung "{name}" bearbeiten', $oGui->gui_description);
		$sTitle = str_replace('{name}', '{name_' . $sInterfaceLanguage . '}', $sTitle);

		$oDialog = $oGui->createDialog($sTitle, $oGui->t('Neue Versicherung anlegen'));

		$oTabData = $oDialog->createTab(L10N::t('Daten', $oGui->gui_description));
		$oTabData->aOptions = array(
		  'access' => '',
		  'section' => 'insurances'
		);

		$provider = Ext_TC_Util::addEmptyItem(Ext_Thebing_Insurances_Gui2_Insurance::getProvider());
		$oDiv = $oDialog->createRow(L10N::t('Anbieter', $oGui->gui_description), 'select', array('db_alias'=>'kins', 'db_column' => 'provider_id', 'required' => 1, 'select_options' => $provider));
		$oTabData->setElement($oDiv);

		$aSearch = $aAliace = array();

		$oTabData->setElement(
			$oDialog->createI18NRow(
				$oGui->t('Bezeichnung'),
				[
					'required'=>true,
					'db_alias'=>'kins',
					'db_column_prefix' => 'name_'
				],
				$aTranslationLanguages
			)
		);

		$oDiv = $oDialog->createRow(L10N::t('Nummer', $oGui->gui_description), 'input', array('db_alias'=>'kins', 'db_column' => 'number'));
		$oTabData->setElement($oDiv);

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Preisberechnung'));
		$oTabData->setElement($oH3);

		$oDiv = $oDialog->createRow(L10N::t('Preisstruktur', $oGui->gui_description), 'select', array('db_alias'=>'kins', 'db_column' => 'payment', 'select_options' => Ext_Thebing_Insurances_Gui2_Insurance::getCalculationMethods($oGui->gui_description)));
		$oTabData->setElement($oDiv);
		$oDiv = $oDialog->createRow(L10N::t('Wochen', $oGui->gui_description), 'select', [
			'db_alias'=>'kins',
			'db_column' => 'weeks',
			'multiple' => 10,
			'select_options' => Ext_Thebing_Insurances_Gui2_Insurance::getWeeks(),
			'jquery_multiple' => 1,
			'searchable' => 1,
			'dependency_visibility' => [
				'db_alias'=>'kins',
				'db_column' => 'payment',
				'on_values' => [Ext_Thebing_Insurance::TYPE_WEEK]
			],
		]);
		$oTabData->setElement($oDiv);

		$oH3 = $oDialog->create('h4');
		$oH3->setElement($oGui->t('Buchhaltung'));
		$oTabData->setElement($oH3);

		$oTabData->setElement(
			$oDialog->createRow(
				$oGui->t('Kostenstelle', $oGui->gui_description),
				'input',
				[
					'db_column' => 'cost_center',
					'db_alias'=>'kins'
				]
			)
		);

		$oDialog->setElement($oTabData);

		$oTab = $oDialog->createTab($oGui->t('Frontend'));
		$oDialog->setElement($oTab);

		//$oTab->setElement($oDialog->createRow($oGui->t('Starttag'), 'select', [
		//	'db_column' => 'start_day',
		//	'select_options' => Ext_TC_Util::getDays(),
		//	'dependency_visibility' => [
		//		'db_alias'=>'kins',
		//		'db_column' => 'payment',
		//		'on_values' => [Ext_Thebing_Insurance::TYPE_WEEK]
		//	],
		//]));

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

		if(Ext_Thebing_Access::hasRight('thebing_insurance_resources_insurances_documents')) {
			$oTab = $oDialog->createTab($oGui->t('Dokumente'));

			$oTab->setElement($oTab->getDialog()->createRow($oGui->t('Vorlagen'), 'select', [
				'db_column' => 'pdf_templates',
				'selection' => new Ext_TS_Gui2_Selection_Service_PdfTemplate('insurance'),
				'multiple' => 5,
				'jquery_multiple' => true,
				'searchable' => true,
				'style' => 'height: 105px;'
			]));

			$oDialog->setElement($oTab);
		}

		return $oDialog;
	}

	public static function getInsurancesListForInbox($bForSelect = false, $iSchoolId = 0) {

		if($iSchoolId > 0) {
			$oSchool = Ext_Thebing_School::getInstance($iSchoolId); 
		} else {
			$oSchool = Ext_Thebing_School::getSchoolFromSession();
		}

		$sDefaultLang = $oSchool->getInterfaceLanguage();

		$sSQL = "
			SELECT
				`kins`.`id`,
				`kins`.#sName AS `title`,
				`kins`.`payment`
			FROM
				`kolumbus_insurances` AS `kins`
			INNER JOIN
				`kolumbus_insurance_providers` AS `kinsp`
			ON
				`kins`.`provider_id` = `kinsp`.`id`
			WHERE
				`kins`.`active` = 1 AND
				`kinsp`.`active` = 1
			ORDER BY
				`kins`.#sName
		";
		$aSQL = [
			'sName' => 'name_'.$sDefaultLang,
		];
		$aInsurances = DB::getPreparedQueryData($sSQL, $aSQL);

		// Komisches Format für SR (kein $bForSelect)
		if($bForSelect) {
			$InsurancesTemp = [];
			$InsurancesTemp[0]['id'] = 0;
			$InsurancesTemp[0]['title'] = L10N::t('keine Versicherung');
			$InsurancesTemp[0]['payment'] = 0;
			foreach((array)$aInsurances as $aInsurance){
				$InsurancesTemp[] = $aInsurance;
			}
			$aInsurances = $InsurancesTemp;
		}

		if ($bForSelect === 2) {
			$aReturn = [];
			unset($aInsurances[0]);
			foreach ($aInsurances as $aInsurance) {
				$aReturn[$aInsurance['id']] = $aInsurance['title'];
			}
			return $aReturn;
		}

		return (array)$aInsurances;
	}

	/**
	 * @param bool $bForSelect
	 * @return mixed[]
	 */
	public static function getProvider($bForSelect = true) {

		$sSQL = "
			SELECT
				*
			FROM
				`kolumbus_insurance_providers`
			WHERE
				`active` = 1
			ORDER BY
				`company`
		";
		$aSQL = [];
		$aProviders = DB::getPreparedQueryData($sSQL, $aSQL);

		if($bForSelect) {
			$aProvidersTemp = [];
			foreach((array)$aProviders as $aProvider) {
				$aProvidersTemp[$aProvider['id']] = $aProvider['company'];
			}
			$aProviders = $aProvidersTemp;
		}

		return (array)$aProviders;

	}

	/**
	 * @param string $sTrace
	 * @param string $sLang
	 * @return mixed[]
	 */
	public static function getCalculationMethods($sTrace, $sLang = '') {

		$oSchool = Ext_Thebing_School::getSchoolFromSession();

		if(empty($sLang)) {
			$sLang = $oSchool->getInterfaceLanguage();
		}

		$oClient = $oSchool->getClient();

		$aMethods = [
			1 => Ext_Thebing_L10N::t('Einmalig', $sLang, $sTrace),
			2 => Ext_Thebing_L10N::t('Pro Tag', $sLang, $sTrace),
		];

		if($oClient->insurance_price_method == 1) {
			$aMethods[3] = Ext_Thebing_L10N::t('Preis pro Woche', $sLang, $sTrace);
		} else {
			$aMethods[3] = Ext_Thebing_L10N::t('Normale Preisstruktur', $sLang, $sTrace);
		}

		return $aMethods;

	}

	/**
	 * @param bool $bForSelect
	 * @return mixed[]
	 */
	public static function getWeeks($bForSelect = true) {

		$sWhere = "";

		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$oClient = $oSchool->getClient();

		if($oClient->insurance_price_method == 1) {
			$sWhere .= " AND `startweek` != 0 ";
		} else {
			$sWhere .= " AND `startweek` = 0 ";
		}

		$sSQL = "
			SELECT
				*
			FROM
				`kolumbus_insurance_weeks`
			WHERE
				`active` = 1
				".$sWhere."
			ORDER BY
				`position`,
				`id`
		";
		$aSQL = [];
		$aWeeks = DB::getPreparedQueryData($sSQL, $aSQL);

		if($bForSelect) {
			$aWeeksTemp = array();
			foreach((array)$aWeeks as $aWeek) {
				$aWeeksTemp[$aWeek['id']] = $aWeek['title'];
			}
			$aWeeks = $aWeeksTemp;
		}

		return (array)$aWeeks;

	}

	static public function manipulateSearchFilter()
	{
		// TODO warum nicht alle Sprachen? Ist überall so..
		$language = \Ext_Thebing_Util::getInterfaceLanguage();

		return [
			'column' => [
				'number',
				'name_'.$language
			]
		];
	}

}
