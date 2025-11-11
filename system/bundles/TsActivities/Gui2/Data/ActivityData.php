<?php

namespace TsActivities\Gui2\Data;

use TsActivities\Entity\Activity;
use TsActivities\Entity\Activity\Provider;
use TsActivities\Gui2\Selection;

/**
 * @property Activity $oWDBasic
 */
class ActivityData extends \Ext_Thebing_Gui2_Data {

	const TRANSLATION_PATH = 'TS » Activities';

	public function replacePlaceholders($sTemplate, &$oWDBasic) {

		/** @var \TsActivities\Entity\Activity $oWDBasic */
		$sName = $oWDBasic->getI18NName('ts_act_i18n', 'name', \Ext_Thebing_Util::getInterfaceLanguage());
		return str_replace('{name}', $sName, $sTemplate);

	}

	protected function manipulateSqlParts(array &$aSqlParts, string $sView) {

		$entity = \DB::getDefaultConnection()->escapeString(get_class($this->oWDBasic));

		$aSqlParts['select'] .= ",
			GROUP_CONCAT(DISTINCT  CONCAT(`tc_c`.`lastname`, ', ', `tc_c`.`firstname`) SEPARATOR '<br/>') `providers`,
			GROUP_CONCAT(DISTINCT `schools`.`school_id`) `schools`
		";

		$aSqlParts['from'] .= " INNER JOIN
			`ts_activities_schools` `ts_acts_filter` ON
				`ts_acts_filter`.`activity_id` = `ts_act`.`id` LEFT OUTER JOIN
			`ts_activities_providers` `provider` ON
				`providers`.`provider_id` = `provider`.`id` LEFT JOIN
			`tc_contacts` `tc_c` ON
				`provider`.`contact_id` = `tc_c`.`id`
		";

		foreach (['app_picture' => Activity::APP_IMAGE_TAG, 'frontend_picture' => 'Frontend-Image'] as $field => $tag) {

			$aSqlParts['select'] .= ", IF(`{$field}_fmf`.`id` IS NULL, 0, 1) `{$field}` ";

			$aSqlParts['from'] .= " LEFT JOIN (
					`filemanager_tags` `{$field}_fmt` INNER JOIN
					`filemanager_files_tags` `{$field}_fmft` INNER JOIN
					`filemanager_files` `{$field}_fmf`
				) ON
					`{$field}_fmt`.`entity` = '{$entity}' AND
					`{$field}_fmt`.`tag` = '{$tag}' AND
					`{$field}_fmt`.`active` = 1 AND
					`{$field}_fmft`.`tag_id` = `{$field}_fmt`.`id` AND
					`{$field}_fmf`.`id` = `{$field}_fmft`.`file_id` AND
					`{$field}_fmf`.`entity` = '{$entity}' AND
					`{$field}_fmf`.`entity_id` = `ts_act`.`id` AND
					`{$field}_fmf`.`active` = 1
			";

		}

		parent::manipulateSqlParts($aSqlParts, $sView);

	}

	/**
	 * @param \Ext_Gui2 $oGui
	 * @return \Ext_Gui2_Dialog
	 * @throws \Exception
	 */
	public static function getDialog(\Ext_Gui2 $oGui) {

		$aLanguages = \Ext_Thebing_Util::getTranslationLanguages();
		$aSchools = \Ext_Thebing_Util::addEmptyItem(\Ext_Thebing_Client::getSchoolList(true));

		// Dialog
		$oDialog = $oGui->createDialog($oGui->t('Aktivität "{name}" editieren'), $oGui->t('Neue Aktivität anlegen'));

		// Tab Einstellungen
		$oTab = $oDialog->createTab($oGui->t('Einstellungen'));

		$oTab->aOptions	= [
			'section' => 'requirements'
		];

		$oTab->setElement($oDialog->createI18NRow($oGui->t('Bezeichnung'), [
			'db_alias' => 'ts_act_i18n',
			'db_column' => 'name',
			'i18n_parent_column' => 'activity_id',
			'required'	=> true
		], $aLanguages));

		$oTab->setElement($oDialog->createRow($oGui->t('Abkürzung'), 'input', [
			'db_alias' => 'ts_act',
			'db_column' => 'short',
			'required'	=> true,
		]));

		$oH3 = $oDialog->create('h4')->setElement($oGui->t('Einstellungen'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Buchbar ohne Kurs'), 'checkbox', [
			'db_column' => 'without_course',
			'child_visibility' => [
				[
					'class' => 'able_without_course',
					'on_values' => [
						0
					]
				]
			]
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Anzeigen wenn Preis 0 ist?'), 'checkbox', [
			'db_column' => 'show_for_free'
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Kostenfrei?'), 'checkbox', [
			'db_column' => 'free_of_charge',
			'child_visibility' => [
				[
					'class' => 'cost_free',
					'on_values' => [
						0
					]
				]
			]
		]));

		$oBillingPeriodSelect = $oDialog->createRow($oGui->t('Abrechnung'), 'select', [
			'db_column' => 'billing_period',
			'required'	=> true,
			'select_options' => self::getBillingPeriodOptions(),
		]);
		$oBillingPeriodSelect->class = 'cost_free';
		$oTab->setElement($oBillingPeriodSelect);

		$oTab->setElement($oDialog->createRow($oGui->t('Verfügbarkeit'), 'select', [
			'db_column' => 'availability',
			'required'	=> true,
			'select_options' => self::getAvailabilityOptions(),
			'child_visibility' => [
				[
					'class' => 'GUIDialogTabHeader.availability-tab',
					'on_values' => [
						'limited_availability'
					]
				]
			]
		]));

		$oH3 = $oDialog->create('h4')->setElement($oGui->t('Schulen'));
		$oTab->setElement($oH3);

		$oJoinedObjectContainer = $oDialog->createJoinedObjectContainer('schools', [
			'min' => 1,
			'max' => count($aSchools),
		]);

		$oJoinedObjectContainer ->setElement($oJoinedObjectContainer->createRow($oGui->t('Schule'), 'select', [
			'db_column' => 'school_id',
			'db_alias' => 'ts_acts',
			'required' => true,
			'select_options' => $aSchools
		]));


		$oCourseSelect = $oJoinedObjectContainer->createRow($oGui->t('Kurse'), 'select', [
			'db_alias' => 'ts_acts',
			'db_column' => 'courses',
			'selection' => new Selection\Courses(),
			'multiple' => 5,
			'jquery_multiple' => 1,
			'required'	=> true,
			'dependency' => [
				[
					'db_column'	=> 'school_id',
					'db_alias' => 'ts_acts',
				]
			],
		]);
		$oCourseSelect->class = 'able_without_course';
		$oJoinedObjectContainer->setElement($oCourseSelect);

		$oTab->setElement($oJoinedObjectContainer);

		$oTab->setElement(
			$oDialog->createNotification(
				$oGui->t('Hinweis'),
				$oGui->t('Kombinationskurse: Bei der Buchung selber zählen nur die Einstellungen, die in dem Kombinationskurs vorgenommen wurden. Die Einstellungen der Aktivitäten zu den einzelnen Kursteilen werden nicht beachtet.'),
				'info',
				[
					'row_class' => 'able_without_course'
				]
			)
		);

		$oH3 = $oDialog->create('h4')->setElement($oGui->t('Sonstiges'));
		$oTab->setElement($oH3);

		$oTab->setElement($oDialog->createRow($oGui->t('Min. Schüler'), 'input', [
			'db_column' => 'min_students'
		]));

		$oTab->setElement($oDialog->createRow($oGui->t('Max. Schüler'), 'input', [
			'db_column' => 'max_students'
		]));

		$oTab->setElement(
			$oDialog->createRow(
				$oGui->t('Anbieter'),
				'select',
				array(
					'db_column'			=> 'providers',
					'i18n_parent_column' => 'activity_id',
					'multiple'			=> 5,
					'select_options'	=> self::getActivityProviders(),
					'jquery_multiple'	=> 1,
					'searchable'		=> 1,
					'required'			=> 1
				)
			)
		);

		$oTab->setElement($oDialog->create('h4')->setElement($oGui->t('Buchhaltung')));

		$oTab->setElement($oDialog->createRow($oGui->t('Kostenstelle'), 'input', ['db_column' => 'cost_center']));

		$oDialog->setElement($oTab);

		$oTab = $oDialog->createTab($oGui->t('Frontend'));

		$oTab->setElement($oDialog->createI18NRow($oGui->t('Beschreibung (kurz)'), [
			'type' => 'input',
			'db_alias' => 'ts_act_i18n',
			'db_column' => 'description_short',
			'i18n_parent_column' => 'activity_id',
			'required'	=> true
		], $aLanguages));

		$oTab->setElement($oDialog->createI18NRow($oGui->t('Beschreibung'), [
			'type' => 'html',
			'db_alias' => 'ts_act_i18n',
			'db_column' => 'description',
			'i18n_parent_column' => 'activity_id',
			'required'	=> true
		], $aLanguages));

		$oTab->setElement(($oDialog->createRow($oGui->t('Icon-Klasse'), 'input', [
			'db_column' => 'frontend_icon_class'
		])));

		$oDialog->setElement($oTab);

		$oTabAvailability = $oDialog->createTab($oGui->t('Verfügbarkeit'));
		$oTabAvailability->class = 'availability-tab';

		$oGuiAvailability = new \Ext_TC_Validity_Gui2(md5('ts_activities_validities'));
		$oGuiAvailability->gui_description = $oGui->gui_description;
		$oGuiAvailability->parent_hash = $oGui->hash;
		$oGuiAvailability->calendar_format = new \Ext_Thebing_Gui2_Format_Date();
		$oGuiAvailability->parent_primary_key = 'id';

		$oGuiAvailability->setOption('validity_no_required_fields', true);
		$oGuiAvailability->setOption('validity_hide_select', true);
		$oGuiAvailability->setOption('validity_show_valid_from', true);
		$oGuiAvailability->setOption('validity_show_valid_until', true);

		$oGuiAvailability->setTableData('limit', 30);

		$oGuiAvailability->setWDBasic('\TsActivities\Entity\Activity\Validity');
		$oGuiAvailability->foreign_key = 'activity_id';

		//$oGuiAvailability->access = $oGui->access; Die Rechteabrage der ValidityGui führt zu fehlern. Kann an der Stelle sowieso nicht vom Nutzer aufgerufen werden.

		$oTabAvailability->setElement($oGuiAvailability);
		$oDialog->setElement($oTabAvailability);

//		$oTabDocuments = $oDialog->createTab($oGui->t('Dokumente'));
//
//		$oTabDocuments->setElement($oDialog->createRow($oGui->t('Vorlagen'), 'select', [
//			'db_column' => 'pdf_templates',
//			'selection' => new \Ext_TS_Gui2_Selection_Service_PdfTemplate('activity'),
//			'multiple' => 5,
//			'jquery_multiple' => true,
//			'searchable' => true,
//			'style' => 'height: 105px;'
//		]));
//
//		$oDialog->setElement($oTabDocuments);

		return $oDialog;
	}

	/**
	 * @return array
	 */
	public static function getBillingPeriodOptions() {
		$aBillingPeriods = [
			'payment_per_week' => \L10N::t('Wochenweise', self::TRANSLATION_PATH),
			'payment_per_block' => \L10N::t('Blockweise', self::TRANSLATION_PATH)
		];
		return $aBillingPeriods;
	}

	/**
	 * @return array
	 */
	public static function getAvailabilityOptions() {
		$aAvailability = [
			\TsActivities\Entity\Activity::AVAILABILITY_ALWAYS => \L10N::t('Immer verfügbar', self::TRANSLATION_PATH),
			\TsActivities\Entity\Activity::AVAILABILITY_LIMITED => \L10N::t('Begrenzt verfügbar', self::TRANSLATION_PATH)
		];
		return $aAvailability;
	}

	/**
	 * @return mixed
	 * @throws \Exception
	 */
	public static function getActivityProviders() {
		$oRepository = Provider::getRepository();
		$aProviders = $oRepository->getProviderSelectList();
		return $aProviders;
	}

	public function _getErrorMessage($sError, $sField, $sLabel = '', $sAction = null, $sAdditional = null) {

		return match ($sError) {
			'ACTIVITY_IN_USE' => $this->t('Der ausgewählte Eintrag wird noch in Buchungen oder in der Planung verwendet'),
			default => parent::_getErrorMessage($sError, $sField, $sLabel, $sAction, $sAdditional)
		};

	}

	/**
	 * Statischer YML-Mist (booked_activities.yml) braucht wieder globalen Kontext
	 *
	 * @internal
	 */
	public static function getActivityOptions() {

		return Activity::getRepository()->getSelectOptions(\Ext_Thebing_School::getSchoolFromSession());

	}

}
