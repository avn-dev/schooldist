<?php

class Ext_Thebing_Tuition_Course_Category extends Ext_Thebing_Basic {

	// Tabellenname
	protected $_sTable = 'ts_tuition_coursecategories';

	// Tabellenalias
	protected $_sTableAlias = 'ktcc';

	protected $_aAttributes = [
		'frontend_icon_class' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		],
		'cost_center' => [
			'class' => 'WDBasic_Attribute_Type_Varchar'
		]
	];

	protected $_aJoinTables = array(
		// @todo Das hier ist falsch! Das ist eine Entität und muss daher ausschliesslich über JoinedObjects definiert sein.
		'courses' => array(
			'table' => 'kolumbus_tuition_courses',
			'primary_key_field'		=> 'category_id',
			'autoload'				=> false,
			'delete_check'			=> true,
			'check_active'			=> true,
			'cloneable' => false
		),
		'schools'=> [
			'table' => 'ts_tuition_coursecategories_to_schools',
			'primary_key_field' => 'category_id',
			'foreign_key_field' => 'school_id',
		]
	);

	protected $_aJoinedObjects = [
		'courses' => [
			'class' => 'Ext_Thebing_Tuition_Course',
			'type' => 'child',
			'key' => 'category_id',
			'readonly' => true,
			'check_active' => true,
			'orderby' => 'position',
			'cloneable' => false
		]
	];
	
	protected $_aFlexibleFieldsConfig = [
		'tuition_course_categories' => []
	];

	public function getName($sIso = null) {

		if ($sIso === null) {
			$school = Ext_Thebing_School::getSchoolFromSessionOrFirstSchool();
			$sIso = $school->getInterfaceLanguage();
		}

		$sField = 'name_'.$sIso;

		return $this->$sField;

	}

	public function  __get($sName)
	{
		if('planification_template' == $sName)
		{
			$sHtmlPlanificationTemplate = $this->_aData['planification_template'];
	
			if(empty($sHtmlPlanificationTemplate))
			{
				$sHtmlPlanificationTemplate = '
					  <strong>{name}</strong><br/>
					  <em>{blockname}</em><br/>
					  {time}<br />
					  {week}<br />
					  {courses}<br/>
					  {level}<br/>
					  <span>{students}</span><br/>
					  {teacher}<br/>
					  {units}
				';
			}

			return $sHtmlPlanificationTemplate;
		}
		else
		{
			return parent::__get($sName);
		}
	}

	public function manipulateSqlParts(&$aSqlParts, $sView = null) {
		
		$aSqlParts['select'] .= ", GROUP_CONCAT(schools.school_id) `schools`";
		
	}
	
}
