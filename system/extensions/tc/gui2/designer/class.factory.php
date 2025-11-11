<?
/**
 * Factory Klasse
 * Dient dazu auf den unterschiendlichen Systemen (Agency/School/Core) 
 * die passende Klassen zu bekommen
 * Dadurch muss man nicht jede Stelle ableiten sondern wirklich nur die Spezielen
 */
class Ext_TC_Gui2_Designer_Factory {
	
	// Generelle Informationen definieren
	public $sL10NPath					= '';
	public $aLanguages					= array();
	protected $_aOptions					= array();
	
	// List of all Designer Classes for the Sections
	public $aSectionDataClassList			= array(
														'test' => 'Ext_TC_Gui2_Designer_Data',
														'test2' => 'Ext_TC_Gui2_Designer_Data'
													);
	public $aSectionList					= array(
														'test' => 'Test Section',
														'test2' => 'Test Section 2'
												);
	
	// Aktuelle Section, wird gesetzt sobald n Designer Object für ein Design erstellt wird
	public $sSection						= '';
	
		
	public function setOption($sOption, $mValue){
		$this->aOptions[$sOption] = $mValue;
	}
	
		
	public function getOption($sOption) {
		return $this->aOptions[$sOption];
	}
	
	public function save() {
		$_SESSION['Gui2Designer']['factory'] = &$this;
	}
	
	// Designer Gui (page) generieren
	public function displayPage($aOptional = array()) {

		// GUIs Laden
		$sClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Design_Gui2');
		$sDataClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Design_Gui2_Data');
		$oGui = new $sClass($sDataClass);
		$oGui->gui_description = $this->sL10NPath;
		$oGui->gui_title = Ext_TC_Factory::executeStatic('Ext_TC_System_Navigation', 't');

		$sClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Design_Tab_Gui2');
		$sDataClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Design_Tab_Gui2_Data');
		$oGuiTab = new $sClass($sDataClass);
		$oGuiTab->gui_description = $this->sL10NPath;
		$oGuiTab->gui_title = $oGuiTab->t('1. Tabs');

		$sClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Design_Tab_Element_Gui2');
		$sDataClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Design_Tab_Element_Gui2_Data');
		$oGuiTabElement = new $sClass($sDataClass);
		$oGuiTabElement->gui_description = $this->sL10NPath;
		$oGuiTabElement->gui_title = $oGuiTabElement->t('1.1. Tab Elemente');		//

		// Page bauen
		$oPage = new Ext_Gui2_Page();
		$oPage->setGui($oGui);
		$oPage->setGui($oGuiTab, array('hash' => $oGui->hash, 'foreign_key' => 'design_id',  'parent_primary_key' => 'id', 'reload' => true, 'force_reload' => true));
		$oPage->setGui($oGuiTabElement, array('hash' => $oGuiTab->hash, 'foreign_key' => 'tab_id',  'parent_primary_key' => 'id', 'reload' => true, 'force_reload' => true));
		
		################
		## WICHTING ####
		$_SESSION['Gui2Designer']['factory'] = &$this;
		################

		$aOptional['js'][] = '/admin/extensions/tc/gui2/gui2.js';
		$aOptional['js'][] = '/admin/extensions/tc/gui2/designer/js/designer.js';

		$aOptional['css'][] = '/admin/extensions/tc/gui2/gui2.css';
		$aOptional['css'][] = '/admin/extensions/tc/gui2/designer/css/designer.css';

		$oPage->display($aOptional);
		
	}
	
	/**
	 * Wrapper für Ext_TC_Gui2_Designer->displayDoubleElementHint()
	 * @return html 
	 */
	public function getDoubleElementHint(){

		$sDesignerClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Designer');
		$oDesigner = new $sDesignerClass(0);
		return $oDesigner->getDoubleElementHint();
		
	}
	
	/**
	 * Wrapper für Ext_TC_Gui2_Designer->getUnknownElementHint()
	 * @return html 
	 */
	public function getUnknownElementHint(){
		$sDesignerClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Designer');
		$oDesigner = new $sDesignerClass(0);
		return $oDesigner->getUnknownElementHint();
		
	}
	
	/**
	 * Wrapper für Ext_TC_Gui2_Designer->getPleaseSaveHint()
	 * @return html 
	 */
	public function getPleaseSaveHint(){

		$sDesignerClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Designer');
		$oDesigner = new $sDesignerClass(0);
		return $oDesigner->getPleaseSaveHint();
		
	}
	
	/**
	 * Wrapper für Ext_TC_Gui2_Designer::getTemplatePath()
	 * @return type 
	 */
	public function getTemplatePath(){

		$sTemplatePath = Ext_TC_Factory::executeStatic('Ext_TC_Gui2_Designer', 'getTemplatePath');

		return $sTemplatePath;		
	}
	
	/**
	 * Wrapper für Ext_TC_Gui2_Designer->getSections()
	 * @return type 
	 */
	public function getSections(){
		
		return $this->aSectionList;
		
	}
	
	public function t($sTrans){
		return L10N::t($sTrans, $this->sL10NPath);
	}
	
	
}