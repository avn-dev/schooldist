<?php
/**
 * WDBASIC der GUI »Felder« als Tab im Dialog
 *
 * @property int $id
 * @property int $changed
 * @property int $created
 * @property int $active
 * @property int $creator_id
 * @property int $editor_id
 * @property int $field_id
 * @property int $dependency_field_id
 * @property string $value
 *
 */
class Ext_TC_Frontend_Template_Field_Dependency extends Ext_TC_Basic {

	protected $_sTable = 'tc_frontend_templates_fields_dependencies';

	protected $_sTableAlias = 'tc_ftfd';
	
	protected $_aJoinTables = array(
		'field_values' => array(
			'table' => 'tc_frontend_templates_fields_dependencies_values',
	 		'foreign_key_field'=> 'value',
	 		'primary_key_field'=> 'dependency_id'
		)
	);
	
    protected $_aJoinedObjects = array(
		'dependency_field' => array(
			'class' => 'Ext_TC_Frontend_Template_Field',
			'key' => 'dependency_field_id',
			'type' => 'parent',
			'check_active' => true
		)
	);
    
	/**
	 * Liefert die Mappinginformationen zu Feldern, auf die sich bei der Validierung
	 * bezogen werden kann
	 * 
	 * @param Ext_TC_Frontend_Template_Field $oField
	 * 
	 * @return array
	 */
    public static function getEntityFieldMapping(Ext_TC_Frontend_Template_Field $oField) {       
        return array();
    }
    
	/**
	 * Liefert das Feldobjekt auf das sich die Abhänigikeit bezieht
	 * 
	 * @return Ext_TC_Frontend_Template_Field
	 */
	public function getParentField() {
		return $this->getJoinedObject('dependency_field');
	}
	
}
