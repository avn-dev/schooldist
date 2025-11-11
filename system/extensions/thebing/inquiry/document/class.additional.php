<?php

class Ext_Thebing_Inquiry_Document_Additional {

	protected $_aConfig = array(
		'class_js'				=> 'StudentlistGui',
		'include_js_files'		=> true,
		'icon_status_active'	=> false,
		'use_template_type'		=> false,
		'add_label_group'		=> true,
		'icons_bar_position'	=> false,
		'filter_bar_position'	=> false,
		'allow_multiple'		=> true,
		'add_language_filter'	=> true,
		'corresponding_language_column' => true,
		'filter_label_mode'		=> 'label',
		'icons_at_first_pos'	=> false,
		'access_document_edit'	=> null,
		'access_document_open'	=> null,
		'data_class' => null,
		'column_group_corresponding_language' => null
	);

	public function __set($sName, $mValue) {

		if(
			array_key_exists($sName, $this->_aConfig) && 
			!empty($sName)
		) {
			$this->_aConfig[$sName] = $mValue;
		}

		return $this;
	}

	public function __get($sName) {

		if(array_key_exists($sName, $this->_aConfig) && !empty($sName)) {
			return $this->_aConfig[$sName];
		}

		return false;
	}

}