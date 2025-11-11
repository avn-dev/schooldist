<?php

/**
 * Unittest für die generierung der Buchungsätze
 * 
 */
class GeneratorTest extends PHPUnit_Framework_TestCase
{
    
    protected $_sAddressType    = 'address';
    protected $_sCustomerNumber = 'dummyNumberABC';
    protected $_sAgencyNumber   = 'dummyAgencyNumberABC';


    protected function getSchoolMock(){
        $oMock = $this->getMockBuilder('Ext_Thebing_School')
            ->disableOriginalConstructor()
            ->getMock();
        return $oMock;
    }
    
    protected function getCompanyMock(){
        $oMock = $this->getMockBuilder(\TsAccounting\Entity\Company::class)
            ->disableOriginalConstructor()
            ->getMock();
        return $oMock;
    }
    
    protected function getVersionMock(){
        
        $oMock = $this->getMockBuilder('Ext_Thebing_Inquiry_Document_Version')
            ->disableOriginalConstructor()
            ->setMethods(array('getAddressType'))
            ->getMock();
        
        $oMock->expects($this->any())
                    ->method('getAddressType')
                    ->will($this->returnValue($this->_sAddressType));
        
        return $oMock;
    }
    
    protected function getAgencyMock(){
        $oMock = $this->getMockBuilder('Ext_Thebing_Agency')
            ->disableOriginalConstructor()
            ->setMethods(array('getNumber'))
            ->getMock();
        
        $oMock->expects($this->any())
                    ->method('getNumber')
                    ->will($this->returnValue($this->_sAgencyNumber));
        return $oMock;
    }
    
    protected function getInquiryMock(){
        $oMock = $this->getMockBuilder('Ext_TS_Inquiry')
            ->disableOriginalConstructor()
             ->setMethods(array('getAgency', 'getFirstTraveller'))
            ->getMock();
        
        $oMock->expects($this->any())
                    ->method('getAgency')
                    ->will($this->returnValue($this->getAgencyMock()));
        
        $oMock->expects($this->any())
                    ->method('getFirstTraveller')
                    ->will($this->returnValue($this->getTravellerMock()));
        
        return $oMock;
    }
    
    protected function getTravellerMock(){
        $oMock = $this->getMockBuilder('Ext_TS_Inquiry_Contact_Traveller')
             ->setMethods(array('getCustomerNumber'))
            ->disableOriginalConstructor()
            ->getMock();
        $oMock->expects($this->any())
                    ->method('getCustomerNumber')
                    ->will($this->returnValue($this->_sCustomerNumber));
        return $oMock;
    }
    
    
    protected function getGenerator(){
        
        $oDocument = $this->getMockBuilder('Ext_Thebing_Inquiry_Document')
            ->disableOriginalConstructor()
            ->setMethods(array('getInbox', 'getSchool', 'getCompany', 'getLastVersion', 'getInquiry', '__get'))
            ->getMock();
        
        $oDocument->expects($this->any())
                    ->method('__get')
                    ->with($this->equalTo('type'))
                    ->will($this->returnValue('brutto'));
        
        $oDocument->expects($this->any())
                    ->method('getInbox')
                    ->with($this->equalTo(true))
                    ->will($this->returnValue(false));
        
        $oDocument->expects($this->any())
                    ->method('getSchool')
                    ->will($this->returnValue($this->getSchoolMock()));
        
        $oDocument->expects($this->any())
                    ->method('getCompany')
                    ->will($this->returnValue($this->getCompanyMock()));
        
        $oDocument->expects($this->any())
                    ->method('getLastVersion')
                    ->will($this->returnValue($this->getVersionMock()));
        
        $oDocument->expects($this->any())
                    ->method('getInquiry')
                    ->will($this->returnValue($this->getInquiryMock()));



		$oGenerator = new Ext_TS_Accounting_Bookingstack_Generator_Document($oDocument);
        
        return $oGenerator;
    }
    
    ##
    ## Tests
    ##

    
    /**
     * @expectedException Ext_TS_Accounting_Bookingstack_Generator_Exception
     */
	public function testConstructorNoSchool()
	{

        $oDocument = $this->getMock('Ext_Thebing_Inquiry_Document', array(
            'getInbox',
            'getSchool',
            'getCompany',
            'getLastVersion'
        ));
        
		$oDocument->expects($this->any())
                    ->method('getInbox')
                    ->with($this->equalTo(true))
                    ->will($this->returnValue(false));
        
        $oDocument->expects($this->any())
                    ->method('getSchool')
                    ->will($this->returnValue(false));
        
        $oDocument->expects($this->any())
                    ->method('getCompany')
                    ->will($this->returnValue($this->getCompanyMock()));
        
        $oDocument->expects($this->any())
                    ->method('getLastVersion')
                    ->will($this->returnValue($this->getVersionMock()));

		$oGenerator = new Ext_TS_Accounting_Bookingstack_Generator_Document($oDocument);

	}
    
    /**
     * @expectedException Ext_TS_Accounting_Bookingstack_Generator_Exception
     */
	public function testConstructorNoCompany()
	{

        $oDocument = $this->getMock('Ext_Thebing_Inquiry_Document', array(
            'getInbox',
            'getSchool',
            'getCompany',
            'getLastVersion'
        ));
        
		$oDocument->expects($this->any())
                    ->method('getInbox')
                    ->with($this->equalTo(true))
                    ->will($this->returnValue(false));
        
        $oDocument->expects($this->any())
                    ->method('getSchool')
                    ->will($this->returnValue($this->getSchoolMock()));
        
        $oDocument->expects($this->any())
                    ->method('getCompany')
                    ->will($this->returnValue(false));
        
        $oDocument->expects($this->any())
                    ->method('getLastVersion')
                    ->will($this->returnValue($this->getVersionMock()));

		$oGenerator = new Ext_TS_Accounting_Bookingstack_Generator_Document($oDocument);

	}
    
    /**
     * @expectedException Ext_TS_Accounting_Bookingstack_Generator_Exception
     */
	public function testConstructorNoVersion()
	{

        $oDocument = $this->getMock('Ext_Thebing_Inquiry_Document', array(
            'getInbox',
            'getSchool',
            'getCompany',
            'getLastVersion'
        ));
        
		$oDocument->expects($this->any())
                    ->method('getInbox')
                    ->with($this->equalTo(true))
                    ->will($this->returnValue(false));
        
        $oDocument->expects($this->any())
                    ->method('getSchool')
                    ->will($this->returnValue($this->getSchoolMock()));
        
        $oDocument->expects($this->any())
                    ->method('getCompany')
                    ->will($this->returnValue($this->getCompanyMock()));
        
        $oDocument->expects($this->any())
                    ->method('getLastVersion')
                    ->will($this->returnValue(false));
        
        $oGenerator = new Ext_TS_Accounting_Bookingstack_Generator_Document($oDocument);

	}

    
    public function testGetCustomerNumber(){
        $oGenerator = $this->getGenerator();
        $sNumber = $oGenerator->getCustomerNumber();
        $this->assertEquals('dummyNumberABC', $sNumber);
    }
    
    public function testGetAgencyNumber(){
        $oGenerator = $this->getGenerator();
        $sNumber    = $oGenerator->getAgencyNumber();
        $this->assertEquals('dummyAgencyNumberABC', $sNumber);
    }
    
    public function testGetAddressType(){
        $oGenerator = $this->getGenerator();
        $sType    = $oGenerator->getAddressType();
        $this->assertEquals('address', $sType);
    }
    
    public function testAddTaxToSplittingDataByRef(){
        
        $oGen = $this->getGenerator();
        
        $aData = array(
            array(
                'amount' => 200,
                'accounting_type' => 'position'
            ),
            array(
                'amount' => 300,
                'accounting_type' => 'vat'
            )
        );
        
        $i = 1;
        
        $aEqualArray  = array(
            array(
                'amount' => 200,
                'accounting_type' => 'position'
            ),
            array(
                'amount' => 270,
                'accounting_type' => 'position'
            ),
            array(
                'amount' => 30,
                'accounting_type' => 'vat'
            )
        );
        
        $oGen->addTaxToSplittingDataByRef($aData, $i, 10);

        $this->assertEquals($aEqualArray, $aData);
        $this->assertEquals(2, $i);
    }
}
