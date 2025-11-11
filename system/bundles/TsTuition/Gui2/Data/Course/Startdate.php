<?php

namespace TsTuition\Gui2\Data\Course;

class Startdate extends \Ext_Thebing_Gui2_Data {
	
	protected function _buildWherePart($aWhere) {
		
		$course = $this->oWDBasic->getCourse();
		
		if(!$course->canHaveStartDates()) {
			$aWhere['type'] = \Ext_Thebing_Tuition_Course_Startdate::TYPE_NOT_AVAILABLE;
		}

		return parent::_buildWherePart($aWhere);
	}

	protected function getEditDialogHTML(&$oDialogData, $aSelectedIds, $sAdditional = false) {
		
		$school = \Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
		/* @var \Ext_Thebing_Tuition_Course_Startdate $startdate */
		$startdate = $this->getWDBasicObject($aSelectedIds);
				
		$oDialogData->aElements = [];
		
		$oDialogData->setElement($oDialogData->createRow($this->t('Typ'), 'select', [
			'db_column' => 'type',
			'selection' => new \TsTuition\Gui2\Selection\Course\AvailabiltyType,
			'required' => true,
			'child_visibility' => [
				[
					'class' => 'start_date_field',
					'on_values' => [\Ext_Thebing_Tuition_Course_Startdate::TYPE_START_DATE]
				]
			],
			'events' => [
				[
					'event' => 'change',
					'function' => 'reloadDialogTab',
					'parameter' => 'aDialogData.id, 0'
				]
			]
		]));

		$oDialogData->setElement($oDialogData->createRow($this->t('Startdatum'), 'calendar', array(
			'db_column' => 'start_date',
			'db_alias'	=> '',
			'required'	=> 1,
			'format'	=> new \Ext_Thebing_Gui2_Format_Date(),
		)));

		if($startdate->type == \Ext_Thebing_Tuition_Course_Startdate::TYPE_START_DATE) {

			$oDialogData->setElement($oDialogData->createRow($this->t('Einzelnes Startdatum'), 'checkbox', array(
				'db_column' => 'single_date',
				'row_class' => 'start_date_field',
			)));

			$oDialogData->setElement($oDialogData->createRow($this->t('Wiederholung'), 'input', array(
				'db_column' => 'period',
				'db_alias'	=> '',
				'format'	=> new \Ext_Thebing_Gui2_Format_Int(),
				'required' => true,
				'input_div_addon' => $this->t('Wochen'),
				'row_class' => 'start_date_field',
				'dependency_visibility' => [
					'db_column' => 'single_date',
					'on_values' => [0]
				]
			)));

			$oDialogData->setElement($oDialogData->createRow($this->t('Letztes Startdatum'), 'calendar', array(
				'db_column' => 'last_start_date',
				'db_alias'	=> '',
				'format'	=> new \Ext_Thebing_Gui2_Format_Date(),
				'required' => true,
				'row_class' => 'start_date_field',
				'dependency_visibility' => [
					'db_column' => 'single_date',
					'on_values' => [0]
				]
			)));

			$oDialogData->setElement($oDialogData->createRow($this->t('Letztes Enddatum'), 'calendar', array(
				'db_column' => 'end_date',
				'db_alias'	=> '',
				'format'	=> new \Ext_Thebing_Gui2_Format_Date(),
				'dependency_visibility' => [
					'db_column' => 'single_date',
					'on_values' => [0]
				]
			)));

			$oDialogData->setElement($oDialogData->createRow($this->t('Minimale Kursdauer'), 'input', array(
				'db_column' => 'minimum_duration',
				'db_alias'	=> '',
				'row_class' => 'start_date_field',
				'format' => new \Ext_Gui2_View_Format_Null()
			)));

			$oDialogData->setElement($oDialogData->createRow($this->t('Maximale Kursdauer'), 'input', array(
				'db_column' => 'maximum_duration',
				'db_alias'	=> '',
				'row_class' => 'start_date_field',
				'format' => new \Ext_Gui2_View_Format_Null()
			)));

			$oDialogData->setElement($oDialogData->createRow($this->t('Fixe Kursdauer'), 'input', array(
				'db_column' => 'fix_duration',
				'db_alias'	=> '',
				'row_class' => 'start_date_field',
				'format' => new \Ext_Gui2_View_Format_Null()
			)));

			$oDialogData->setElement($oDialogData->createRow($this->t('Abhängig von Level'), 'checkbox', array(
				'db_column' => 'depending_on_level',
				'db_alias' => '',
				'row_class' => 'start_date_field',
				'skip_value_handling' => true
			)));

			$oDialogData->setElement($oDialogData->createRow($this->t('Level'), 'select', [
				'db_column' => 'levels',
				'select_options' => $school->getCourseLevelList(),
				'multiple' => 5,
				'jquery_multiple' => true,
				'row_class' => 'start_date_field',
				'dependency_visibility' => [
					'db_column' => 'depending_on_level',
					'on_values' => ['1']
				],
			]));

			$oDialogData->setElement($oDialogData->createRow($this->t('Abhängig von Sprache'), 'checkbox', array(
				'db_column' => 'depending_on_courselanguage',
				'db_alias' => '',
				'row_class' => 'start_date_field',
				'skip_value_handling' => true
			)));

			$course = $startdate->getCourse();
			$courseLanguages = array_intersect_key(
				\Ext_Thebing_Tuition_LevelGroup::getSelectOptions($this->_oGui, null, [$course->getSchool()]),
				array_flip($course->course_languages)
			);

			$oDialogData->setElement($oDialogData->createRow($this->t('Kurssprache'), 'select', [
				'db_column' => 'courselanguages',
				'select_options' => $courseLanguages,
				'multiple' => 5,
				'jquery_multiple' => true,
				'row_class' => 'start_date_field',
				'dependency_visibility' => [
					'db_column' => 'depending_on_courselanguage',
					'on_values' => ['1']
				],
			]));
			
		} else {
			
			$oDialogData->setElement($oDialogData->createRow($this->t('Enddatum'), 'calendar', array(
				'db_column' => 'end_date',
				'db_alias'	=> '',
				'format'	=> new \Ext_Thebing_Gui2_Format_Date(),
				'required' => true
			)));

		}
		
		return parent::getEditDialogHTML($oDialogData, $aSelectedIds, $sAdditional);
	}
	
}
