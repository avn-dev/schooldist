<?
/**
 * UML - https://redmine.thebing.com/redmine/issues/278
 */
class Ext_TC_Frontend_Form_Field_Location extends Ext_TC_Frontend_Form_Field_Select {
	
	protected $_sTemplateType = 'location_select';

	public function getOptions($bUnsetEmptyOption = false, $bGrouped = false, $sLanguage = null){
		$aOptions = parent::getOptions();
		$oSelection = $this->getSelection();

		if($this->_oTemplate->display == 'location_select'){
			$aOptions = $oSelection->getGroupedOptions($aOptions);
		}
		
		return $aOptions;
	}
}
