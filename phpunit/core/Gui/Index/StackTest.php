<?php



class GuiIndexStackTest extends coreTestSetup
{
	
    public function setUp()
    {
        WDCache::flush();
        Ext_Gui2_Index_Stack::deleteAllUsedIndexStacks();
    }
    
	public function testGet(){
		Ext_Gui2_Index_Stack::get('testindex');
        $this->assertTrue(true);
	}
    
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testGetThrowExceptionByInt(){
		Ext_Gui2_Index_Stack::get(1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testGetThrowExceptionByArray(){
		Ext_Gui2_Index_Stack::get(array());
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testGetThrowExceptionByObject(){
		Ext_Gui2_Index_Stack::get(new stdClass());
	}
	
	
	public function testMergeIndexStacks(){

		$aStack1 = array(
			10 => 2,
			11 => 1,
			20 => 1,
			22 => 0
		);
		
		$aStack2 = array(
			2 => 0,
			4 => 1,
			11 => 2,
			20 => 0,
			21 => 1
		);

		
		$aStackFinal = array(
			2 => 0,
			4 => 1,
			10 => 2,
			11 => 1,
			20 => 0,
			21 => 1,
			22 => 0
		);
		
		$aStack = Ext_Gui2_Index_Stack::mergeIndexStacks($aStack1, $aStack2);

		$this->assertEquals($aStackFinal, $aStack);
	}
    
    public function testGetFromDB(){
		$aResult = Ext_Gui2_Index_Stack::getFromDB('testindex');
        $this->assertEquals(array(), $aResult);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testGetFromDBExceptionByInt(){
		Ext_Gui2_Index_Stack::getFromDB(1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testGetFromDBExceptionByArray(){
		Ext_Gui2_Index_Stack::getFromDB(array());
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testGetFromDBExceptionByObject(){
		Ext_Gui2_Index_Stack::getFromDB(new stdClass());
	}
	
	/*
	 * no Exception
	 */
	public function testAdd(){
		Ext_Gui2_Index_Stack::add('Testindex', 1, 0);
        $aResult = Ext_Gui2_Index_Stack::get('Testindex');
		$this->assertEquals(array(1 => 0), $aResult);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Prioirty must be positive or zero!
	 */
	public function testAddNegativPriorityException(){
		Ext_Gui2_Index_Stack::add('Testindex', 1, -1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testAddExceptionIndexNameIsInt(){
		Ext_Gui2_Index_Stack::add(1, 1, 1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testAddExceptionIndexNameIsArray(){
		Ext_Gui2_Index_Stack::add(array(), 1, 1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testAddExceptionIndexNameIsObject(){
		Ext_Gui2_Index_Stack::add(new stdClass(), 1, 1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index ID must be a Number!
	 */
	public function testAddExceptionIndexIdIsNotNumeric(){
		Ext_Gui2_Index_Stack::add('testindex', 'abc', 1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index ID must be positive!
	 */
	public function testAddExceptionIndexIdIsZero(){
		Ext_Gui2_Index_Stack::add('testindex', 0, 1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index ID must be positive!
	 */
	public function testAddExceptionIndexIdIsNegative(){
		Ext_Gui2_Index_Stack::add('testindex', -1, 1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index ID must be a Number!
	 */
	public function testAddExceptionIndexIdIsArray(){
		Ext_Gui2_Index_Stack::add('testindex', array(), 1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index ID must be a Number!
	 */
	public function testAddExceptionIndexIdIsObject(){
		Ext_Gui2_Index_Stack::add('testindex', new stdClass(), 1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Prioirty must be a Number!
	 */
	public function testAddExceptionIndexPrioIsNotNumeric(){
		Ext_Gui2_Index_Stack::add('testindex', 1, 'abc');
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Prioirty must be a Number!
	 */
	public function testAddExceptionIndexPrioIsArray(){
		Ext_Gui2_Index_Stack::add('testindex', 1, array());
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Prioirty must be a Number!
	 */
	public function testAddExceptionIndexPrioIsObject(){
		Ext_Gui2_Index_Stack::add('testindex', 1, new stdClass());
	}	
    
    public function testDelete(){
		Ext_Gui2_Index_Stack::delete('testindex', 1);
        $this->assertTrue(true);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testDeleteExceptionIndexNameIsInt(){
		Ext_Gui2_Index_Stack::delete(1, 1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testDeleteExceptionIndexNameIsArray(){
		Ext_Gui2_Index_Stack::delete(array(), 1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index Name must be a String!
	 */
	public function testDeleteExceptionIndexNameIsObject(){
		Ext_Gui2_Index_Stack::delete(new stdClass(), 1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index ID must be a Number!
	 */
	public function testDeleteExceptionIndexIdIsNotNummeric(){
		Ext_Gui2_Index_Stack::delete('testindex', 'abc');
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index ID must be a Number!
	 */
	public function testDeleteExceptionIndexIdIsArray(){
		Ext_Gui2_Index_Stack::delete('testindex', array());
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Index ID must be a Number!
	 */
	public function testDeleteExceptionIndexIdIsObject(){
		Ext_Gui2_Index_Stack::delete('testindex', new stdClass());
	}
	
	/**
	 * Nach dem löschen muss das static attribut leer sein 
	 */
	public function testDeleteAllUsedIndexStacks(){
        Ext_Gui2_Index_Stack::add('testindex', 1, 1);
        Ext_Gui2_Index_Stack::add('testindex', 2, 2);
		Ext_Gui2_Index_Stack::deleteAllUsedIndexStacks();
		$aAllStacks = Ext_Gui2_Index_Stack::getAll();
		$this->assertEmpty($aAllStacks);
	}
	
	
    public function testExecute(){
		$bSuccess = Ext_Gui2_Index_Stack::execute(0, 0);
        $this->assertTrue($bSuccess);
	}
    
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Max. Priority must be a Number!
	 */
	public function testExecuteExceptionPriorityIsNotNummeric(){
		Ext_Gui2_Index_Stack::execute('abc');
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Max. Priority must be zero or positiv!
	 */
	public function testExecuteExceptionPriorityIsNegative(){
		Ext_Gui2_Index_Stack::execute(-1);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Execute Limit must be a Number!
	 */
	public function testExecuteExceptionLimitIsNotNummeric(){
		Ext_Gui2_Index_Stack::execute(0, 'abc');
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Execute Limit must be zero or positiv!
	 */
	public function testExecuteExceptionLimitIsNegative(){
		Ext_Gui2_Index_Stack::execute(0, -1);
	}
}