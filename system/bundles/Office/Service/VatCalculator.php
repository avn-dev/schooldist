<?php

namespace Office\Service;

class VatCalculator extends \Mpociot\VatCalculator\VatCalculator {
	
	public function check($sCountryIso, $sZipCode = null) {
		
		if(!$this->shouldCollectVAT(strtoupper($sCountryIso))) {
			return false;
		}
		
		if(
			!empty($sZipCode) &&
			isset($this->postalCodeExceptions[strtoupper($sCountryIso)])
		) {			
			foreach($this->postalCodeExceptions[strtoupper($sCountryIso)] as $aPostalCodeException) {
                if(preg_match($aPostalCodeException['postalCode'], $sZipCode)) {
                    return false;
                }
			}
		}
		
		return true;
	}
	
}

