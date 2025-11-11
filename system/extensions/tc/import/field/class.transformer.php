<?php
class Ext_TC_Import_Field_Transformer {
	
	protected $_aTransforms = array();
	protected $_aValidators = array();
	protected $_aManipulators = array();
	protected $_mFallback = null;

	/**
	 * Setzt eine Einfache Transformation von Wert A nach B
	 * @param type $mValueFrom
	 * @param type $mValueTo 
	 */
	public function setTransform($mValueFrom, $mValueTo){
		$this->_aTransforms[$mValueFrom] = $mValueTo;
	}
	
	/**
	 * setzt den Fallback wenn keine Transformation statfinden konnte
	 * @param type $mFallbackValue 
	 */
	public function setFallback($mFallbackValue){
		$this->_mFallback = $mFallbackValue;
	}
	
	/**
	 * setzt einen Validator welcher prüft ob der Wert Transformiert werden dard
	 * @param function $oValidator 
	 */
	public function setValidator($oValidator){
		$this->_aValidators[] = $oValidator;
	}
	
	/**
	 * setzt einen Manipulator welcher den Wert vor dem Transformieren manipuliert
	 * @param function $oManipulator 
	 */
	public function setManipulator($oManipulator){
		$this->_aManipulators[] = $oManipulator;
	}
	
	/**
	 * transformiert den wert
	 * 1) Manipulation
	 * 2) Validation
	 * 3) Transformation
	 * 4) Fallback (falls kein Wert bis dahin vorhanden ist)
	 * @param type $mValueFrom
	 * @return type 
	 */
	public function transform($mValueFrom, $aData){
		
		$mValueTo = null;
		$bValid = true;
		
		foreach($this->_aManipulators as $oFunction){
			$mValueFrom = $oFunction($mValueFrom, $aData);
		}

		foreach($this->_aValidators as $oFunction){
			if(!$oFunction($mValueFrom, $aData)){
				$bValid = false;
			}
		}

		if($bValid){
			foreach($this->_aTransforms as $mValue => $mValue2){
				if($mValue == $mValueFrom){
					$mValueTo = $mValue2;
				}
			}
		}
        
		if($mValueTo === null){
			$mValueTo = $this->_mFallback;
		}
        
		if($mValueTo === null){
			$mValueTo = $mValueFrom;
		}
		
		if(is_array($mValueTo)){
			$mValueTo = implode(', ', $mValueTo);
		}

		return $mValueTo;
	}
}