<?php

class Ext_Thebing_Examination_Templates_Gui2 extends Ext_Thebing_Gui2_Data {

	public function getTranslations($sL10NDescription) {

		$aData = parent::getTranslations($sL10NDescription);

		$aData['tuition_examination_unit_days'] = L10N::t('Tage', $sL10NDescription);
		$aData['tuition_examination_unit_weeks'] = L10N::t('Wochen', $sL10NDescription);
		$aData['tuition_examination_add_term'] = L10N::t('Termin hinzufügen', $sL10NDescription);
		$aData['tuition_examination_remove_term'] = L10N::t('Termin löschen', $sL10NDescription);

		return $aData;

	}

	/**
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param string $sType Entspricht JoinedObject-Key
	 * @return Ext_Gui2_Dialog_JoinedObjectContainer
	 */
	public static function getTermsJoinedObjectContainer(Ext_Gui2_Dialog $oDialog, $sType) {

		$aItems = [
			[
				'db_column' => 'period',
				'input' => 'select',
				'select_options' => self::getPeriodSelectOptions($oDialog->oGui),
				'style' => 'width: 105px; margin-right: 5px;'
			],
			[
				'db_column' => 'period_length',
				'input' => 'input',
				'style' => 'width: 40px; margin-right: 5px;'
			],
			[
				'db_column' => 'period_unit',
				'input' => 'select',
				'select_options' => self::getPeriodUnitSelectOptions($oDialog->oGui),
				'style' => 'width: 85px; margin-right: 5px;'
			]
		];

		if($sType === 'terms_fix') {
			$aItems[] = [
				'db_column' => 'start_date',
				'input' => 'calendar',
				'format' => new Ext_Thebing_Gui2_Format_Date(),
			];
		} elseif($sType === 'terms_individual') {
			$aItems[] = [
				'db_column' => 'start_from',
				'input' => 'select',
				'select_options' => self::getStartFromSelectOptions($oDialog->oGui),
				'style' => 'width: 120px;'
			];
		}

		$oJoinContainer = $oDialog->createJoinedObjectContainer($sType, ['min' => 0, 'max' => 100]);
		$oJoinContainer->setElement($oJoinContainer->createMultiRow($oDialog->oGui->t('Prüfungstermin'), [
			'db_alias' => 'kett',
			'items' => $aItems
		]));

		$oJoinContainer->style = 'width: 100%';

		return $oJoinContainer;

	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getPeriodSelectOptions(Ext_Gui2 $oGui) {
		return [
			'one_time' => $oGui->t('einmalig'),
			'recurring' => $oGui->t('regelmäßig'),
		];
	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getPeriodUnitSelectOptions(Ext_Gui2 $oGui) {
		return [
			'days' => $oGui->t('Tage'),
			'weeks' => $oGui->t('Wochen'),
		];
	}

	/**
	 * @param Ext_Gui2 $oGui
	 * @return array
	 */
	public static function getStartFromSelectOptions(Ext_Gui2 $oGui) {
		return [
			//'before_course_start' => $oDialog->oGui->t('vor Kursstart'),
			'after_course_start' => $oGui->t('nach Kursstart'),
			'before_course_end' => $oGui->t('vor Kursende'),
			//'after_course_end' => $oDialog->oGui->t('nach Kursende')
		];
	}

	static public function getOrderby(){
		
		return ['kext.title' => 'ASC'];
	}

	static public function getWhere(){
		
		$oSchool			= Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId			= $oSchool->id;

		return ['kext.school_id' => $iSchoolId, 'kext.active' => 1];
	}

	static public function getDialog(Ext_Gui2 $oGui){
		
		$oSchool = Ext_Thebing_School::getSchoolFromSession();
		$iSchoolId = $oSchool->id;
		$sSchoolLanguage = $oSchool->getLanguage();
		$aTemplates = Ext_Thebing_Pdf_Template_Search::s('document_examination', $sSchoolLanguage, $iSchoolId, null, true);
		$aSectionCategories	= Ext_Thebing_Examination_SectionCategory::getOptionList();
		$aCourses = $oSchool->getCourseList(true, false, true);
		
		$oDialog = $oGui->createDialog(
				$oGui->t("Prüfungsbereich \"{title}\" editieren"), 
				$oGui->t('Neuen Prüfungsbereich anlegen'));
		$oDialog->width = 900;
		$oDialog->height = 650;

		$oDialog->setElement($oDialog->createRow($oGui->t('Name'), 'input', array(
				'db_alias'			=> 'kext',
				'db_column'			=> 'title',
				'required'			=> 1,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Kommentar'), 'textarea', array(
				'db_alias'			=> 'kext',
				'db_column'			=> 'comment',
				'required'			=> 0,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Kurse'), 'select', array(
				'db_alias'			=> 'kext',
				'db_column'			=> 'courses',
				'required'			=> 0,
				'select_options'	=> $aCourses,
				'multiple'			=> 5,
				'jquery_multiple'	=> 1,
				'style'				=> 'height: 105px;',
				'searchable'		=> 1,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Prüfungskategorien'), 'select', array(
				'db_alias'			=> '',
				'db_column'			=> 'sections',
				'required'			=> 0,
				'select_options'	=> $aSectionCategories,
				'multiple'			=> 5,
				'jquery_multiple'	=> 1,
				'style'				=> 'height: 105px;',
				'sortable'			=> 1,
				'searchable'		=> 1,
		)));

		$oDialog->setElement($oDialog->createRow($oGui->t('Vorlage'), 'select', array(
			'db_alias' => 'kext',
			'db_column' => 'pdf_template_id',
			'required' => 1,
			'select_options' => $aTemplates,
		)));

		$oDialog->setElement($oDialog->create('h4')->setElement($oGui->t('Feste Termine')));
		$oDialog->setElement(Ext_Thebing_Examination_Templates_Gui2::getTermsJoinedObjectContainer($oDialog, 'terms_fix'));

		$oDialog->setElement($oDialog->create('h4')->setElement($oGui->t('Individuelle Termine')));
		$oDialog->setElement(Ext_Thebing_Examination_Templates_Gui2::getTermsJoinedObjectContainer($oDialog, 'terms_individual'));

		return $oDialog;
	}

}