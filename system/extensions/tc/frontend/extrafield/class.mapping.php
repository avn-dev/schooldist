<?php
class Ext_TC_Frontend_Extrafield_Mapping extends Ext_TC_Frontend_Mapping {
	
	public function __construct() {
		
		// Extra felder ebenfalls zur verfügung stellen
		$oExtrafield	= Ext_TC_Factory::getObject('Ext_TC_Extrafield');
		$aExtrafields	= $oExtrafield->getObjectList();
		
		$aTypes = Ext_TC_Frontend_Template_Field_Gui2_Selection_Display::getInputTypes();
		
		foreach($aExtrafields as $oExtraField){
			/* @var $oExtraField Ext_TC_Extrafield */
			$oField = $this->createField(array('Type' => 'integer'));
			$oField->addConfig('label', $oExtraField->name);
			$oField->addConfig('object', $oExtraField);
			$oField->addConfig('allowed_input_types', array(
				'checkbox_text' => $aTypes['checkbox_text']
			));
			$this->addField('checkbox.'.$oExtraField->id, $oField);
	
		}
		
	}
	
	/**
	 *
	 * @param bool $bWithDbInformation
	 * @return array 
	 */
	public function getMappingSchema($bWithDbInformation=false, $bWithOriginal=false) {
		//Kind kann(muss) Mapping-Schema verändern
		$this->configureMappingFields();
		
		$aSchema		= array();
		
		foreach($this->_aFields as $sFieldName => $oField) {
			$aFieldConfig	=  $oField->toArray();
			$sKey			= $sFieldName;	
			
			if($bWithDbInformation) {
				$aFieldConfig['db_alias']	= $sTableAlias;
				$aFieldConfig['db_column']	= $sFieldName;
			}
			
			$aSchema[$sKey] = $aFieldConfig;
		}
		
		return $aSchema;
	}
	
}