<?
/**
 * UML - https://redmine.thebing.com/redmine/issues/278
 */
class Ext_TC_Frontend_Form_Field_Referrer extends Ext_TC_Frontend_Form_Field_Select {
	
	protected $_sTemplateType = 'referrer';

		
	/**
	 * get all field of the referrer
	 * @return Ext_TC_Referrer_Field <array> 
	 */
	public function getFields(){
		return array();
	}

}