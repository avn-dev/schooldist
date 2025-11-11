<?php

include_once \Util::getDocumentRoot().'phpunit/core/testSetup.php';

/**
 * Prüft, ob die Formularklasse korrekt funktioniert
 */
class WDBasicClearInstancesTest extends coreTestSetup
{

	public function testClearInstancesNotRecrusive(){

		$oObject1 = Ext_TC_Communication_Message::getInstance(1);
		$oObject2 = Ext_TC_Communication_Message_Address::getInstance(1);
		
		$oObject1->type = 'abc';
		$oObject2->name = '123';
		
		#######
		## Schauen ob die Instance korrekt klappt
		$oObject1 = Ext_TC_Communication_Message::getInstance(1);
		$oObject2 = Ext_TC_Communication_Message_Address::getInstance(1);
		
		$this->assertEquals('abc', $oObject1->type, 'Main Object getInstance failed!');
		$this->assertEquals('123', $oObject2->name, 'Sub Object getInstance failed!');
		#######
		
		WDBasic::clearInstances('Ext_TC_Communication_Message', false);
		
		###
		# Schauen ob es korrekt geleert wurde
		$oObject1 = Ext_TC_Communication_Message::getInstance(1);
		$oObject2 = Ext_TC_Communication_Message_Address::getInstance(1);

		$this->assertEquals('email', $oObject1->type, 'Main Object is wrong!');
		$this->assertEquals('123', $oObject2->name, 'Sub Object is wrong!');
		#####
		
	}

	
	public function testClearInstancesRecrusive(){

		$oObject1 = Ext_TC_Communication_Message::getInstance(1);
		$oObject2 = Ext_TC_Communication_Message_Address::getInstance(1);
		
		$oObject1->type = 'abc';
		$oObject2->name = '123';
		
		#######
		## Schauen ob die Instance korrekt klappt
		$oObject1 = Ext_TC_Communication_Message::getInstance(1);
		$oObject2 = Ext_TC_Communication_Message_Address::getInstance(1);
		
		$this->assertEquals('abc', $oObject1->type);
		$this->assertEquals('123', $oObject2->name);
		#######
		
		WDBasic::clearInstances('Ext_TC_Communication_Message', true);
		
		###
		# Schauen ob es korrekt geleert wurde
		$oObject1 = Ext_TC_Communication_Message::getInstance(1);
		$oObject2 = Ext_TC_Communication_Message_Address::getInstance(1);
		
		$this->assertEquals('email', $oObject1->type, 'Main Object is wrong!');
		$this->assertEquals('', $oObject2->name, 'Sub Object is wrong!');
		#####
	}
}