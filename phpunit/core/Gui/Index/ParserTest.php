<?php



class GuiIndexParserTest extends coreTestSetup
{
    
    public function setUp()
    {
        WDCache::flush();
    }

	/**
	 * test if the default json file can be load 
	 */
	public function testLoadDefault(){
		$oParser = new Ext_Gui2_Config_Parser();
		$this->assertTrue(true);
	}
	
	/**
	 * test if the get method is correkt
	 */
	public function testGetAll(){
		$oParser = new Ext_Gui2_Config_Parser();
		$aConfig = $oParser->get();
		
		$this->assertArrayHasKey('index', $aConfig);
		$this->assertArrayHasKey('bars', $aConfig);
		$this->assertArrayHasKey('columns', $aConfig);
	}
	
	/**
	 * test if the get method is correkt
	 */
	public function testGetOneLayer(){
		$oParser = new Ext_Gui2_Config_Parser();
		$aConfig = $oParser->get('index');
		
		$this->assertArrayHasKey('name', $aConfig);
	}
	
	/**
	 * test if the get method is correkt
	 */
	public function testGetTwoLayer(){
		$oParser = new Ext_Gui2_Config_Parser();
		$aConfig = $oParser->get(array('bars', 0));
		
		$this->assertArrayHasKey('position', $aConfig);
		$this->assertArrayHasKey('elements', $aConfig);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage You can only set a string, int or array as config layer
	 */
	public function testGetWithObject(){
		$oParser = new Ext_Gui2_Config_Parser();
		$aConfig = $oParser->get(new stdClass);
	}
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage Config File not found!
	 */
	public function testSetFileException(){
		$oParser = new Ext_Gui2_Config_Parser();
		$oParser->setFile('/system/extensions/gui2/index/parser/notdefault.json');
        $oParser->load();
	}
	
	/**
	 */
	public function testSetFile(){
		$oParser = new Ext_Gui2_Config_Parser();
		$oParser->setFile('/system/config/gui2/default.yml');
        $oParser->load();
		$this->assertTrue(true);
	}
	
	
	/**
	 * @expectedException        ErrorException
     * @expectedExceptionMessage The Configuration [abc] is unknown!
	 */
	public function testMergeUnknownSettingException(){
		
		$aConfig1 = array(
			'test1' => 1,
			'test2' => 2
		);
		$aConfig2 = array(
			'test1'	=> 2,
			'test2' => 2,
			'abc'	=> 4
		);
		
		$oParser = new Ext_Gui2_Config_Parser();
		$oMethod = new ReflectionMethod('Ext_Gui2_Config_Parser', '_merge');
		$oMethod->setAccessible(true);
		$oMethod->invokeArgs($oParser, array($aConfig1, $aConfig2));
	}
	
	/**
	 */
	public function testMerge(){
		
		$aConfig1 = array(
			'test1' => 1,
			'test2' => 2,
			'test3' => 3,
			'test4' => array(
				'4_1' => 'abc',
				'4_2' => 'sndb'
			),
			'test5' => array(
				'5_1' => 'abc',
				'5_2' => 'sndb'
			)
		);
		$aConfig2 = array(
			'test1'	=> 2,
			'test2' => 2,
			'test5' => array(
				'5_2' => 'aaaa'
			)
		);
		
		$aFinal = array(
			'test1'	=> 2,
			'test2' => 2,
			'test3' => 3,
			'test4' => array(
				'4_1' => 'abc',
				'4_2' => 'sndb'
			),
			'test5' => array(
				'5_1' => 'abc',
				'5_2' => 'aaaa'
			)
		);
		
		$oParser = new Ext_Gui2_Config_Parser();
		$oMethod = new ReflectionMethod('Ext_Gui2_Config_Parser', '_merge');
		$oMethod->setAccessible(true);
		$sBack = $oMethod->invokeArgs($oParser, array($aConfig1, $aConfig2));
	
		$this->assertEquals($aFinal, $sBack);
	}
}