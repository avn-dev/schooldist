<?php

class Ext_TC_ZenDesk_Errors_Collection implements IteratorAggregate {
	
	private $aErrors = array();

    public function getIterator() {
        return new Ext_TC_ZenDesk_Errors_Iterator($this->aErrors);
    }

	/**
	 * @return boolean
	 */
	public function isEmpty() {

		if(empty($this->aErrors)) {
			return true;
		} 
		
		return false;
	}
	
	/**
	 * @param stdObj $oError
	 */
	protected function setErrorObject($oError) {
		
		$oZenDeskError = new Ext_TC_ZenDesk_Errors_Error;

		$oZenDeskError->sError = $oError->error;
		$oZenDeskError->sDescription = $oError->description;
		
		$aDetails = $oError->details;
		
		if(!empty($aDetails)) {
			foreach($aDetails as $sField=>$aDetailErrors) {
				foreach($aDetailErrors as $oDetailError) {
					$this->add($oDetailError);
				}
			}
		}
		
		$this->aErrors[] = $oZenDeskError;
	}

	/**
	 * @param string $sError
	 */
	protected function setErrorString($sError) {

		$oZenDeskError = new Ext_TC_ZenDesk_Errors_Error;
		
		$oZenDeskError->sDescription = $sError;
		
		$this->aErrors[] = $oZenDeskError;
	}
	
	/**
	 * 
	 * @param string|object $mError
	 * @throws Exception
	 */
    public function add($mError) {

		if(is_object($mError)) {
			$oZenDeskError = $this->setErrorObject($mError);
		} elseif(is_scalar($mError)) {
			$oZenDeskError = $this->setErrorString($mError);
		} else {
			throw new Exception('Invalid error type "'.gettype($mError).'".');
		}

    }

}