<?php 

class Ext_TC_Pdf_Layout extends Ext_TC_Basic {

	protected $_sTable = 'tc_pdf_layouts';
	protected $_sTableAlias = 'tc_pl';

	protected $_aFormat = array(
		'font_type' => array(
			'required' => true
		),
		'font_spacing' => array(
			'validate' => 'FLOAT'
		)
	);

	protected $_aJoinTables = array(
		'elements' => array(
			'table' => 'tc_pdf_layouts_elements',
			'foreign_key_field' => '',
			'primary_key_field' => 'layout_id',
			'sort_column' => 'position'
		),
		'vat_display' => array(
			'table' => 'tc_pdf_layouts_options',
			'foreign_key_field' => 'value',
			'primary_key_field' => 'layout_id',
			'static_key_fields' => array('key'=>'vat_display')
		)
	);

	public function getElements(){
		
		$aResult = $this->elements;

		$aBack = array();
		foreach($aResult as $aData){
			if($aData['active'] > 0){
				$aBack[] = Ext_TC_Pdf_Layout_Element::getInstance($aData['id']);
			}
		}

		return $aBack;

	}

	/*
	 * Liefert mir alle editierbaren Elemente des Layouts 
	 */
	public function getEditableElements(){
		$aResult = Ext_TC_Util::convertDataIntoObject($this->elements, 'Ext_TC_Pdf_Layout_Element');
		return $aResult;
	}

}