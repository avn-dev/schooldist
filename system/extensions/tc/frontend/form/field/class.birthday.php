<?
/**
 * UML - https://redmine.thebing.com/redmine/issues/278
 */
class Ext_TC_Frontend_Form_Field_Birthday extends Ext_TC_Frontend_Form_Field_Select {
	
	protected $_sTemplateType = 'birthdate_select';
	
	public function getDayOptions(){
		$aBack = array('0' => 'DD');// DD wollte aiola
		for($i=1; $i <= 31; $i++){
			$aBack[$i] = $i;
		}
		return $aBack;
	}

	public function getMonthOptions(){
		$aBack = array('0' => 'MM');// MM wollte aiola
		for($i=1; $i <= 12; $i++){
			$aBack[$i] = $i;
		}
		return $aBack;
	}
	
	public function getYearOptions(){
		for($i=1900; $i <= date('Y'); $i++){
			$aBack[$i] = $i;
		}
		$aBack = array_reverse($aBack, true);
		$aBack = Ext_TC_Util::addEmptyItem($aBack, 'YY'); // YY wollte aiola
		return $aBack;
	}
	
	protected function _padNulls($aValue){
		$aValue['day']		= str_pad($aValue['day'], 2, '0', STR_PAD_LEFT);
		$aValue['month']	= str_pad($aValue['month'], 2, '0', STR_PAD_LEFT);
		return $aValue;
	}

		/**
	 * set the UNFORMATED Value
	 * @param string $sValue 
	 */
	public function setValue($sValue){
		
		// Wenn array dann das DB_DATE zusammenbauen
		if(is_array($sValue)){
			
			$sValue = $this->_padNulls($sValue);
			
			$sValue = $sValue['year'].'-'.$sValue['month'].'-'.$sValue['day'];
		}

		$this->_sValue			= (string)$sValue;
		$this->_sFormatedValue	= $this->formatValue($sValue);
	}
	
	
	/**
	 * set the Value
	 * @param string $sValue 
	 */
	public function setFormatedValue($sFormatedValue){
		
		// Wenn array dann das DB_DATE zusammenbauen und formatieren
		if(is_array($sFormatedValue)){
			$sFormatedValue = $this->_padNulls($sFormatedValue);
			$sFormatedValue = $sFormatedValue['year'].'-'.$sFormatedValue['month'].'-'.$sFormatedValue['day'];
			$sFormatedValue	= $this->formatValue($sFormatedValue);
		}

		$this->_sValue			= (string)$this->unformatValue($sFormatedValue);
		$this->_sFormatedValue	= $sFormatedValue;

	}
	
	public function getValue($bFormated = true, $sLanguage = null){
		
		if(
			empty($this->_sFormatedValue) &&
			empty($this->_sValue)
		) {
			$this->setValue($this->getEntityValue());
		}
		
		if($bFormated){
			return $this->_sFormatedValue;
		} else {
			return $this->_sValue;
		}
	}
	
	public function getDay(){
		$sDate = $this->getValue(false);
		$aValue = explode('-', $sDate);
		return (int)$aValue[2];
	}
	
	public function getMonth(){
		$sDate = $this->getValue(false);
		$aValue = explode('-', $sDate);
		return (int)$aValue[1];
	}
	
	public function getYear(){
		$sDate = $this->getValue(false);
		$aValue = explode('-', $sDate);
		return (int)$aValue[0];
	}
}