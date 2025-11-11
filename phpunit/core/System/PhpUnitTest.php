<?php

include_once \Util::getDocumentRoot().'phpunit/core/testSetup.php';

/**
 * Prüft, ob die Formularklasse korrekt funktioniert
 */
class PhpUnitTest extends coreTestSetup
{

	public function testSetWDBasic(){

		$oTestInstance = Ext_TC_User::getInstance(1);
		$oTestInstance->username = 'TestDummyName';

		if(
			$oTestInstance->username == 'TestDummyName'
		){
			$this->assertTrue(true);
		} else {
			$this->assertTrue(false, 'error while creating a new object for User ID 1');
		}
		
	}

	/**
     * @depends testSetWDBasic
     */
	public function testGetWDBasicInstance(){
		
		$oTestInstance = Ext_TC_User::getInstance(1);

		if(
			$oTestInstance->username != 'TestDummyName'
		){
			$this->assertTrue(true);			
		} else {
			$this->assertTrue(false, 'Sorry, i have an WDBasic instance from an other test! Please fix this!');
		}
		
	}
	
}