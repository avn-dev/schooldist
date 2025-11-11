<?php



class GuiIndexRegistryTest extends coreTestSetup
{
    
    public function setUp()
    {
        WDCache::flush();
        Ext_Gui2_Index_Registry::clear();
    }
	
	/**
	 * @expectedException        PHPUnit_Framework_Error
	 */
	public function testSetExceptionWrongObject(){
		$oObject = new stdClass;
		Ext_Gui2_Index_Registry::set($oObject);
	}

    
	public function testSetWithInvalidObject(){
        
        Ext_Gui2_Index_Registry::enable();
        
		$oObject = $this->getMock('Ext_TC_User', array('exist', 'isActive'));
		$oObject->expects($this->any())
                 ->method('exist')
				 ->will($this->returnValue(false));
		$oObject->expects($this->any())
                 ->method('isActive')
				 ->will($this->returnValue(true));
		$bSuccess = Ext_Gui2_Index_Registry::set($oObject);
        $this->assertFalse($bSuccess);
	}
	

	public function testSetWithInactiveObject(){
        
        Ext_Gui2_Index_Registry::enable();
        
		$oObject = $this->getMock('Ext_TC_User', array('exist', 'isActive'));
		$oObject->expects($this->any())
                 ->method('exist')
				 ->will($this->returnValue(true));
		$oObject->expects($this->any())
                 ->method('isActive')
				 ->will($this->returnValue(false));
		$bSuccess = Ext_Gui2_Index_Registry::set($oObject);
        $this->assertFalse($bSuccess);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Priority must be a Number!
	 */
	public function testSetExceptionPriorityIsNotNumeric(){
        
        Ext_Gui2_Index_Registry::enable();
        
		$oObject = $this->getMock('Ext_TC_User', array('exist', 'isActive'));
		$oObject->expects($this->any())
                 ->method('exist')
				 ->will($this->returnValue(true));
		$oObject->expects($this->any())
                 ->method('isActive')
				 ->will($this->returnValue(true));
		Ext_Gui2_Index_Registry::set($oObject, 'abc');
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Priority must be zero or positiv!
	 */
	public function testSetExceptionPriorityIsNegative(){
        
        Ext_Gui2_Index_Registry::enable();
        
		$oObject = $this->getMock('Ext_TC_User', array('exist', 'isActive'));
		$oObject->expects($this->any())
                 ->method('exist')
				 ->will($this->returnValue(true));
		$oObject->expects($this->any())
                 ->method('isActive')
				 ->will($this->returnValue(true));
		Ext_Gui2_Index_Registry::set($oObject, -1);
	}
	

	public function testSetNoException(){
        
        Ext_Gui2_Index_Registry::enable();
        
		$oObject = $this->getMock('Ext_TC_User', array('exist', 'isActive'));
		$oObject->expects($this->any())
                 ->method('exist')
				 ->will($this->returnValue(true));
		$oObject->expects($this->any())
                 ->method('isActive')
				 ->will($this->returnValue(true));
		Ext_Gui2_Index_Registry::set($oObject, 0);
		$this->assertTrue(true);
	}
	
	public function testSetOverwritePriority(){
		
		Ext_Gui2_Index_Registry::enable();
		
		$oObject = $this->getMock('Ext_TC_User', array('exist', 'isActive'));
		$oObject->expects($this->any())
                 ->method('exist')
				 ->will($this->returnValue(true));
		$oObject->expects($this->any())
                 ->method('isActive')
				 ->will($this->returnValue(true));
		
		// Null ist default
		$iPrio = Ext_Gui2_Index_Registry::getPriorityFromCache($oObject);
		$this->assertEquals(null, $iPrio);
		
		// 1 da wir 1 setzten
		Ext_Gui2_Index_Registry::set($oObject, 1);
		$iPrio = Ext_Gui2_Index_Registry::getPriorityFromCache($oObject);
		$this->assertEquals(1, $iPrio);
		
		// immer noch 1 da wir 2 setzten das aber höher(unwichtiger) ist daher bleibt 1
		Ext_Gui2_Index_Registry::set($oObject, 2);
		$iPrio = Ext_Gui2_Index_Registry::getPriorityFromCache($oObject);
		$this->assertEquals(1, $iPrio);
		
		// nun 0 da null wichtiger ist als die 1
		Ext_Gui2_Index_Registry::set($oObject, 0);
		$iPrio = Ext_Gui2_Index_Registry::getPriorityFromCache($oObject);
		$this->assertEquals(0, $iPrio);
		
	}
	
	
	
	
	
	
	/**
	 * @expectedException        PHPUnit_Framework_Error
	 */
	public function testGetExceptionWrongObject(){
		$oObject = new stdClass();
		Ext_Gui2_Index_Registry::get($oObject);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Object ist not valid!
	 */
	public function testGetExceptionInvalidObject(){
		$oObject = $this->getMock('Ext_TC_User', array('exist'));
		$oObject->expects($this->any())
                 ->method('exist')
				 ->will($this->returnValue(false));
		Ext_Gui2_Index_Registry::get($oObject);
	}

	
	/**
	 * das abfragen der DB testen wir nicht da wir davon ausgehen das die DB Klasse geht! 
	 */
	public function testGetNoException(){
		$oObject = $this->getMock('Ext_TC_User', array('exist'));
		$oObject->expects($this->any())
                 ->method('exist')
				 ->will($this->returnValue(true));
		$aData = Ext_Gui2_Index_Registry::get($oObject);
		if(is_array($aData)){
			$this->assertTrue(true);
		} else {
			$this->assertTrue(false);
		}
	}
	
	
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testSaveExceptionIndexNameIsInt(){
		Ext_Gui2_Index_Registry::save(1, 1);
	}	
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testSaveExceptionIndexNameIsArray(){
		Ext_Gui2_Index_Registry::save(array(), 1);
	}	
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testSaveExceptionIndexNameIsObject(){
		Ext_Gui2_Index_Registry::save(new stdClass, 1);
	}
    
    public function testSave(){
		Ext_Gui2_Index_Registry::save('default', 1);
        $this->assertTrue(true);
	}
		

	/**
	 * @expectedException        PHPUnit_Framework_Error
	 */
	public function testUpdateStackExceptionWrongObject(){
		$oObject = new stdClass();
		Ext_Gui2_Index_Registry::updateStack($oObject);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Object ist not valid!
	 */
	public function testUpdateStackExceptionInvalidObject(){
		$oObject = $this->getMock('Ext_TC_User', array('exist'));
		$oObject->expects($this->any())
                 ->method('exist')
				 ->will($this->returnValue(false));
		Ext_Gui2_Index_Registry::updateStack($oObject);
	}
	

	public function testUpdateStack(){
		$oObject = $this->getMock('Ext_TC_User', array('exist'));
		$oObject->expects($this->any())
                 ->method('exist')
				 ->will($this->returnValue(true));
		$bBack = Ext_Gui2_Index_Registry::updateStack($oObject);
		$this->assertTrue($bBack);
	}	

}