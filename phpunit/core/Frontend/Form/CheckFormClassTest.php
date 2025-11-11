<?php
/**
 * Prüft, ob die Formularklasse korrekt funktioniert
 */
class CheckFormClassTest extends coreTestSetup
{
	public function setUp()
	{
		error_reporting(E_ALL & ~(E_STRICT|E_NOTICE));
	}
	
	/**
	 * Testet ob die Formularklasse für Ext_TC_Contact erzeugt werden kann
	 */
//	public function testFormEntity()
//	{
//
//        $oTemplate	= $this->_getTemplateMock();
//
//		$oEntity	= new Ext_TC_Contact();
//		
//		$oForm		= new Ext_TC_Frontend_Form($oTemplate, $oEntity);		
//		
//		if($oForm){
//			$bAssert = true;
//		} else {
//			$bAssert = false;
//		}
//		
//		$this->assertTrue($bAssert, 'Error while creating Form Object');
//		
//	}
	
	/**
	 * testet ob man ein "firstname" feld aufrufen kann wenn das Template ein solches angelegt hat 
	 */
	public function testGetField(){
		
		// Feld anlegen
		$oField = new Ext_TC_Frontend_Template_Field();
		$oField->placeholder = 'firstname';
		$oField->field = 'tc_co.firstname';
		$oField->area = 'standard';
		$oField->display = 'input';
		
        $oTemplate	= $this->_getTemplateMock();
		$oTemplate->expects($this->any())
				->method('getJoinedObjectChilds')
				->with('fields')
				->will($this->returnValue(array($oField)));

		$oEntity	= new Ext_TC_Contact();
		
		$oForm		= new Ext_TC_Frontend_Form($oTemplate, $oEntity);
		
		$oFormField = $oForm->getField('firstname');

		if($oFormField instanceof Ext_TC_Frontend_Form_Field_Abstract){
			$bAssert = true;
		} else {
			$bAssert = false;
		}
		
		$this->assertTrue($bAssert, 'Error while creating Form Field Object');

	}
	
	/**
	 * testet ob man ein "firstname" feld aufrufen kann wenn das Template ein solches angelegt hat 
	 */
//	public function testNoErrors(){
//		
//		$oField = new Ext_TC_Frontend_Template_Field();
//		$oField->placeholder = 'firstname';
//		
//        $oTemplate	= $this->_getTemplateMock();
//		$oTemplate->expects($this->any())
//				->method('getJoinedObjectChilds')
//				->with('fields')
//				->will($this->returnValue(array($oField)));
//
//		$oEntity	= new Ext_TC_Contact();
//		
//		$oForm		= new Ext_TC_Frontend_Form($oTemplate, $oEntity);
//
//		$aErrors	= $oForm->getErrors();
//		
//		if(
//			empty($aErrors) &&
//			is_array($aErrors)
//		){
//			$bAssert = true;
//		} else {
//			$bAssert = false;
//		}
//		
//		$this->assertTrue($bAssert, 'Error Array is not Valid or not Empty');
//	}
	
	/**
	 * testet ob man ein "firstname" feld aufrufen kann wenn das Template ein solches angelegt hat 
	 */
//	public function testAddEmailForm(){
//		
//		$oField = new Ext_TC_Frontend_Template_Field();
//		$oField->placeholder = 'firstname';
//		
//        $oTemplate	= $this->_getTemplateMock();
//		$oTemplate->expects($this->any())
//				->method('getJoinedObjectChilds')
//				->with('fields')
//				->will($this->returnValue(array($oField)));
//
//		$oEntity	= new Ext_TC_Contact();
//		
//		$oForm		= new Ext_TC_Frontend_Form($oTemplate, $oEntity);
//
//		$oSubForm		= $oForm->addForm('email');
//
//		if(
//			$oSubForm instanceof Ext_TC_Frontend_Form
//		){
//			$bAssert = true;
//		} else {
//			$bAssert = false;
//		}
//		
//		$this->assertTrue($bAssert, 'Child Form is not Valid');
//	}
	

	/**
	 * generiert ein Mock Object für das Template
	 * @return type 
	 */
	protected function _getTemplateMock(){
		// Create a mock for the Observer class,
        // only mock the getMessages() and getCss() method.
        $oTemplate = $this->getMock('Ext_TC_Frontend_Template', array('getMessages', 'getCss', 'getJoinedObjectChilds'));
		
		return $oTemplate;
	}

}