<?php

namespace TsTuition\Entity;

class SuperordinateCourse extends \Ext_Thebing_Basic {
	
	protected $_sPlaceholderClass = \TsTuition\Service\Placeholder\SuperordinateCourse::class;

	protected $_sTable = 'ts_superordinate_courses';
	protected $_sTableAlias = 'ts_sc';
		
	protected $_sEditorIdColumn = 'editor_id';

	protected $_aJoinTables = array(
		'ts_sc_i18n' => array(
			'table' => 'ts_superordinate_courses_i18n',
	 		'foreign_key_field' => array('language_iso', 'name'),
	 		'primary_key_field'	=> 'superordinate_course_id'
		)
	);
	
	protected $_aJoinedObjects = [
		'courses' => [
			'class'	=> \Ext_Thebing_Tuition_Course::class,
			'key' => 'superordinate_course_id',
			'check_active' => true,
			'type' => 'child',
			'bidirectional' => true,
			'cloneable' => false
		],
	];
	
	protected $_aFlexibleFieldsConfig = [
		'tuition_courses_superordinate' => [],
	];
	
	public function __get($name) {

		if($name == 'name') {
			return $this->getI18NName('ts_sc_i18n', 'name');
		} elseif(strpos($name, 'name_') === 0) {
			$language = str_replace('name_', '', $name);
			return $this->getI18NName('ts_sc_i18n', 'name', $language);
		}

		return parent::__get($name);
	}
	
	public function __set($name, $value) {
		 
		if(strpos($name, 'name_') === 0) {
			$language = str_replace('name_', '', $name);
			$this->setI18NName($value, $language, 'name', 'ts_sc_i18n');
		} else {
			parent::__set($name, $value);
		}
		
	}

}
