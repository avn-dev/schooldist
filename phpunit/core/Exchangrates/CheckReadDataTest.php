<?php

/**
 * Unit-Test für Wechselkurse 
 */
class checkReadDataTest extends coreTestSetup
{
	
	public function setUp() {
		parent::setUp();
		
		Ext_TC_Util::insertDummy('tc_exchangerates_tables', array('id' => 1, 'name' => 'DummyTable'));
	}
	
	/**
	 * prüft, ob für eine Wechselkurs-Tabelle Wechselkurse aus den XMLs der Quellen gezogen
	 * werden können 
	 */
	public function testReadData() {
		
		$aExpected = $this->_getExpectedData();
		
		// Wechselkurs-Tabelle erzeugen
		$oExchangerateTable = $this->_createDummyExchangerateTable();
		// XML-Daten auslesen		
		$aData = $this->_getReadData($oExchangerateTable);

		$this->assertEquals($aExpected, $aData);

	}
	
	/**
	 * prüft, ob die Werte in die Datenbank gespeichert werden konnten 
	 */
	public function testSaveRates() {

		$oExchangerateTable = $this->_createDummyExchangerateTable();
		$aData = $this->_getReadData($oExchangerateTable);
		
		$bSaveRate = $oExchangerateTable->saveRateData($aData);
		
		$this->assertTrue($bSaveRate);
		
	}
	
	/**
	 * prüft, ob get getAllRateDates() die richtigen Daten zurückliefert
	 */
	public function testGetAllRateDates() {
		
		$oExchangerateTable = $this->_createDummyExchangerateTable();
		
		$aRateDates = $oExchangerateTable->getAllRateDates();
		$aExpectedDates = array('2012-09-19');
		
		$this->assertEquals($aExpectedDates, $aRateDates);
	}
	
	/**
	 * prüft, ob get getCurrentRates() die richtigen Daten zurückliefert
	 */
	public function testGetCurrentRates() {
		
		$oExchangerateTable = $this->_createDummyExchangerateTable();
		
		$aCurrentRates = $oExchangerateTable->getCurrentRates();
		$aExpectedCurrentRates = $this->_getExpectedCurrentRates();
		
		$this->assertEquals($aExpectedCurrentRates, $aCurrentRates);
	}
	
	/**
	 * prüft, ob get getRate() den richtigen Wert zurückliefert
	 */
	public function testGetRateDifferentIso() {
		$oExchangerateTable = $this->_createDummyExchangerateTable();

		$oRate = $oExchangerateTable->getRate('EUR', 'CHF', '2012-09-19');
		$iRate = $oRate->price;
		
		$this->assertEquals('1.20000', $iRate);
	}
	
	/**
	 * prüft, ob get getRate() den richtigen Wert zurückliefert, wenn zwei gleiche
	 * Iso-Codes angegeben wurden
	 */
	public function testGetRateSameIso() {
		$oExchangerateTable = $this->_createDummyExchangerateTable();
		
		$oRate = $oExchangerateTable->getRate('EUR', 'EUR', '2012-09-19');
		$iRate = $oRate->price;
		
		$this->assertEquals('1', $iRate);
	}
	
	/**
	 * prüft, ob _getXml() eine Exception wirft, wenn das XML nicht ausgelesen werden
	 * konnte 
	 * @expectedException Ext_TC_Exchangerate_Exception
	 */
	public function testGetXml() {
		
		$oSource = new Ext_TC_Exchangerate_Table_Source();
		$oSource->url = 'http:://test.xml';
		
		$oMethod = new ReflectionMethod('Ext_TC_Exchangerate_Table_Source', '_getXml');
		$oMethod->setAccessible(true);

		$sXml = (array)$oMethod->invokeArgs($oSource, array());		
	}
	
	/**
	 * gibt die Daten zurück, die aus den XMLs ausgelesen wurden
	 * @param Ext_TC_Exchangerate_Table $oExchangerateTable
	 * @return array 
	 */
	protected function _getReadData(Ext_TC_Exchangerate_Table $oExchangerateTable) {
		
		// Mock-Objekte der Quellen holen
		$aSources = $this->_getSources();
		// Daten auslesen
		$aData = $oExchangerateTable->readData($aSources);

		return $aData;
	}
	
	/**
	 * Erzeugt ein Dummy-Objekt einer Wechselkurs-Tabelle
	 * @return Ext_TC_Exchangerate_Table 
	 */
	protected function _createDummyExchangerateTable() {		
		$oExchangerateTable = Ext_TC_Exchangerate_Table::getInstance(1);				
		return $oExchangerateTable;
	}
	
	/**
	 * Erzeugt ein Objekt einer Wechselkurs-Quelle
	 * @param string $sName
	 * @return Ext_TC_Exchangerate_Table 
	 */
	protected function _createMockSourceObject($sXML) {

		// Mock, der das auslesen des XMLs simuliert		
		$oSource = $this->getMock('Ext_TC_Exchangerate_Table_Source', array('_getXml'));
		$oSource->expects($this->any())
				->method('_getXml')
				->will($this->returnValue($sXML));
		
		$oSource->name = 'XXX';
		$oSource->date_position = "->rates['date']";
		$oSource->date_format = "%Y-%m-%d";
		$oSource->container = "->rates->rate";
		$oSource->rate = "->value";
		$oSource->separator = ".";
		$oSource->source_currency = "->base";
		$oSource->source_currency_searchterm = "";
		$oSource->reverse = 0;		
		$oSource->target_currency = "->currency";
		$oSource->target_currency_searchterm = "";
		$oSource->child_element = 1;
		$oSource->table_id = 1;
		
		return $oSource;		
	}
	
	/**
	 * gibt eine Array mit drei Mock-Objekten zurück
	 * @return array 
	 */
	protected function _getSources() {
		$aSources = array();

		// XML
		$sXML1 = '<?xml version="1.0" encoding="UTF-8"?><thebing><rates date="2012-09-19"><rate><base>EUR</base><currency>USD</currency><value>1.3</value></rate><rate><base>EUR</base><currency>GBP</currency><value>0.8</value></rate></rates></thebing>';
		$sXML2 = '<?xml version="1.0" encoding="UTF-8"?><thebing><rates date="2012-09-19"><rate><base>EUR</base><currency>CHF</currency><value>1.2</value></rate><rate><base>EUR</base><currency>USD</currency><value>1.25</value></rate></rates></thebing>';
		$sXML3 = '<?xml version="1.0" encoding="UTF-8"?><thebing><rates date="2012-09-19"><rate><base>USD</base><currency>EUR</currency><value>0.8</value></rate><rate><base>USD</base><currency>GBP</currency><value>0.6</value></rate></rates></thebing>';
		
		// Quellen erzeugen
		$aSources[] = $this->_createMockSourceObject($sXML1);
		$aSources[] = $this->_createMockSourceObject($sXML2);
		$aSources[] = $this->_createMockSourceObject($sXML3);		
		
		return $aSources;
	}


	/**
	 * Gibt das Array zurück, welches nach dem auslesen des XMLs erwartet wird
	 * @return array 
	 */
	protected function _getExpectedData() {
		
		$aExpected = array(
			'EUR_USD' => array(
				'source_id' => 0,
				'table_id' => 1,
				'date' => '2012-09-19',
				'price' => '1.3',
				'currency_iso_from' => 'EUR',
				'currency_iso_to' => 'USD'			
			),
			'EUR_GBP' => array(
				'source_id' => 0,
				'table_id' => 1,
				'date' => '2012-09-19',
				'price' => '0.8',
				'currency_iso_from' => 'EUR',
				'currency_iso_to' => 'GBP'			
			),
			'EUR_CHF' => array(
				'source_id' => 0,
				'table_id' => 1,
				'date' => '2012-09-19',
				'price' => '1.2',
				'currency_iso_from' => 'EUR',
				'currency_iso_to' => 'CHF'			
			),
			'USD_EUR' => array(
				'source_id' => 0,
				'table_id' => 1,
				'date' => '2012-09-19',
				'price' => '0.8',
				'currency_iso_from' => 'USD',
				'currency_iso_to' => 'EUR'			
			),
			'USD_GBP' => array(
				'source_id' => 0,
				'table_id' => 1,
				'date' => '2012-09-19',
				'price' => '0.6',
				'currency_iso_from' => 'USD',
				'currency_iso_to' => 'GBP'			
			)			
		);
		
		return $aExpected;
	}
	
	/**
	 * gibt das Array zurück, welches beim Aufruf von getCurrentRates() erwartet wird
	 * @return array 
	 */
	protected function _getExpectedCurrentRates() {
		$aReturn = array(
			array(
				'currency_from' => 'EUR',
				'currency_to' => 'CHF',
				'rate' => '1.20000'
			),
			array(
				'currency_from' => 'EUR',
				'currency_to' => 'GBP',
				'rate' => '0.80000'
			),
			array(
				'currency_from' => 'EUR',
				'currency_to' => 'USD',
				'rate' => '1.30000'
			),
			array(
				'currency_from' => 'USD',
				'currency_to' => 'EUR',
				'rate' => '0.80000'
			),
			array(
				'currency_from' => 'USD',
				'currency_to' => 'GBP',
				'rate' => '0.60000'
			)
		);
		
		return $aReturn;
	}
	
}