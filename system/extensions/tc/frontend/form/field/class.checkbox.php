<?
/**
 * UML - https://redmine.thebing.com/redmine/issues/278
 */
class Ext_TC_Frontend_Form_Field_Checkbox extends Ext_TC_Frontend_Form_Field_Input {
	
	protected $_sTemplateType = 'checkbox';
	
	public function getValue($bFormated = true, $sLanguage = null){
	
		if($bFormated){
			return $this->formatValue($this->_sValue);
		} else {
			return $this->_sValue;
		}
	}
	
}