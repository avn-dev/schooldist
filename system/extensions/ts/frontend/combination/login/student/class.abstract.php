<?php

/*
 * Basisklasse für die Daten des Loginformulars in allen Tabs
 */

/**
 * Description of class
 *
 * @author friedrich
 */
abstract class Ext_TS_Frontend_Combination_Login_Student_Abstract{

	/**
	 *
	 * @var Ext_TS_Frontend_Combination_Login_Abstract
	 */
	protected $_oLogin = null;

	public function  __call($sFunction, $aArguments)
	{
		$mReturn;
		
		if(
		  is_object($this->_oLogin) &&
		  $this->_oLogin instanceof Ext_TS_Frontend_Combination_Login_Student
		)
		{
			$sFunction = 'protected'.$sFunction;
			$mReturn = call_user_func_array(array($this->_oLogin, $sFunction), $aArguments);
		}
		else
		{
			$sError = 'Function "'.$sFunction.'" does not exist in "'.get_class($this).'"';
			throw new Exception($sError);
		}
		
		return $mReturn;
	}
	
	public function __construct(Ext_TS_Frontend_Combination_Login_Student &$oLogin){
		$this->_oLogin = $oLogin;

		// Daten initialisieren
		$this->_setData();
	}
	
	protected function _getInquiry(){

		$oInquiry = $this->_oLogin->getBooking();

		if($oInquiry instanceof Ext_TS_Inquiry){
			return $oInquiry;
		}else{
			throw new Exception('Invalid Inquiry');
		}
		
	}
	
	/*
	 * Setzt die Smarty Daten
	 */
	abstract protected function _setData();
}
?>
