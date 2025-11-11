<?php


class Ext_TS_Gui2_Format_Age extends Ext_Gui2_View_Format_Abstract {
	
	protected $_sDatePart;
	protected $_oDate;
	protected $_oDateNow;
	protected $_sColumn = null;

	public function __construct($sDatePart=false, $sColumn = null) {
		
		if(!$sDatePart) {
			$sDatePart = WDDate::DB_DATE;
		}
		
		$this->_sDatePart = $sDatePart;
		
		$this->_oDate = new WDDate();
		$this->_oDateNow = new WDDate();
		$this->_sColumn = $sColumn;
	}
	
	public function format($mValue, &$oColumn = null, &$aResultData = null) {
		
		try {
			
			if($this->_sColumn !== null)
			{
				$mValue = $aResultData[$this->_sColumn];
			}
			
            $iAge = 0;
            
			if(
               $mValue != '0000-00-00' &&
               !empty($mValue)
            ) {
                $this->_oDate->set($mValue, $this->_sDatePart);
                $iAge = $this->_oDate->getAge($this->_oDateNow);

                $iAge = Ext_Thebing_Format::Int($iAge, null, (int)$aResultData['school_id']);
            }
			
			return $iAge;
			
		} catch(Throwable $e) {
			return '';
		}
		
	}

	public function align(&$oColumn = null){
		return 'right';
	}

}
