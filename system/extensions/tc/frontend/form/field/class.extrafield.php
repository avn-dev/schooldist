<?
/**
 * UML - https://redmine.thebing.com/redmine/issues/278
 */
class Ext_TC_Frontend_Form_Field_Extrafield extends Ext_TC_Frontend_Form_Field_Checkbox {
	
	protected $_sTemplateType = 'checkbox_text';
	
	/**
	 * get the Info Text for the Extrafield
	 * @return string 
	 */
	public function getInfotext(){
		
		$sContent = '';
		
		// Entity holen
		$oEntity = $this->_oForm->getEntity();
		// keine "object" ID da scheinbar gesagt wurde das die extrafelder auf core nicht gehen müssen ( eine jointable "objects" wurde nur in der TA ableitung definiert )
		// daher definiere ich hier direkt office_id und nicht "object_id"
		$iObject = $oEntity->office_id;

		$oExtrafield = $this->_oMapping->getConfig('object');
	
		if($oExtrafield) {
			/* @var $oExtrafield Ext_TC_Extrafield */
			$oContent = $oExtrafield->getContent($iObject);
			/* @var $oContent Ext_TC_Frontend_Extrafield_Content */
			if($oContent) {				
				$sContent = $oContent->getI18NName('tc_fcc_i18n', 'content', $this->_oForm->getInterfaceLanguage());
			}
		}
		
		return $sContent;
	}
	
		/**
	 * gibt den Entity Feld Namen zurück,
	 * wird vorallem für validieren und speichern benötigt
	 * @return string 
	 */
	public function getEntityFieldName($bWithPrefix = true){
		$sIdentifier = $this->_oTemplate->getEntityFieldName();
		$sIdentifier .= '.';		
		$sIdentifier .= $this->_oTemplate->getEntityFieldAlias();
		return $sIdentifier;
	}
	
	public function getEntityFieldAlias() {
		return '';
	}
	
}
