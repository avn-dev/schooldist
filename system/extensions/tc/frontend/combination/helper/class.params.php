<?php

class Ext_TC_Frontend_Combination_Helper_Params {
	/**
	 * @var Ext_TC_Frontend_Combination 
	 */
	private $oCombination;
	
	/**
	 * Konstruktor
	 * 
	 * @param Ext_TC_Frontend_Combination $oCombination
	 */
	public function __construct(Ext_TC_Frontend_Combination $oCombination) {
		$this->oCombination = $oCombination;
	}
	
	/**
	 * Prüft, ob in dem Request-Objekte Werte vorhanden sind, die in der Kombination überschrieben werden
	 * sollen
	 * 
	 * @param MVC_Request $oRequest
	 */
	public function overwrite(MVC_Request $oRequest) {
		
		$aCombinationParams = $oRequest->input('frontend_combination_params', array());

		if(
			$this->isOverwriteable() &&
			!empty($aCombinationParams) &&
			is_array($aCombinationParams)
		) {			
			
			if(isset($aCombinationParams['combination_mode'])) {
				$this->oCombination->setMode($aCombinationParams['combination_mode']);
				unset($aCombinationParams['combination_mode']);
			}
			
			// Gesetzte Parameter durchlaufen und vorhandene damit ersetzen
			foreach($aCombinationParams as $sKey => $mValue) {
				
				$bScalar = $this->oCombination->isScalarParam($sKey);

				if(
					$bScalar &&
					!is_scalar($mValue)
				) {
					throw new InvalidArgumentException('Parameter "' . $sKey . '" is not scalar!');
				}

				$sTemp = 'items_' . $sKey;

				$this->oCombination->$sTemp = $mValue;
				
				$this->oCombination->bParameterOverwritten = true;
				
			}	
			
		}
		
	}
	
	/**
	 * Prüft, ob die Werte einer Kombination verändert werden dürfen
	 *
	 * @TODO: TA-Zeug auf TC!
	 *
	 * @return bool
	 */
	private function isOverwriteable() {
		
		if($this->oCombination->overwritable) {
			return true;
		}
		
		return false;
	}
	
	/**
	 * 
	 * @param bool $bDebug
	 * @return bool
	 */
	public function checkPlausibility($bDebug = false) {
		$bCheck = $this->oCombination->checkPlausibility();
		
		if(
			$bDebug === true &&
			$bCheck === false
		) {		
			$aPlausibilityDebug = $this->oCombination->getPlausibilityDebug();
			__out($aPlausibilityDebug);			
		}
		
		return $bCheck;
	}
	
}

