<?
/**
 * UML - https://redmine.thebing.com/redmine/issues/278
 */
abstract class Ext_TC_Frontend_Template_Option_Abstract {

	protected $_sType = '';
	protected $_sValue = '';


	/**
	 * set the Type
	 * @param string $sType 
	 */
	public function setType($sType){
		$this->_sType = $sType;
	}
	
	/**
	 * set the Value
	 * @param string $sValue 
	 */
	public function setValue($sValue){
		$this->_sValue = $sValue;
	}
	
	/**
	 * get the Type
	 * @return string 
	 */
	public function getType(){
		return $this->_sType;
	}
	
	/**
	 * get the Value
	 * @return string 
	 */
	public function getValue(){
		return $this->_sValue;
	}
	
}