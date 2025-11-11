<?php

namespace Ts\Traits;

trait Numberrange {

	public function getNumber() {

		if (array_key_exists('number', $this->_aData)) {
			return $this->_aData['number'];
		}

		$aNumbers = $this->numbers;
		if(!empty($aNumbers)) {
			return $aNumbers[0]['number'];
		}

		return null;

	}
	
	public function generateNumber() {

		// TODO: getObject() gibt es überhaupt nicht im Parent
		/** @var \TsCompany\Service\NumberRange|\Ext_TS_Numberrange_Accommodation $sClass */
		// TODO Ersetzen durch Methode?
		$sClass = $this->sNumberrangeClass;
		
		$bGenerated = false;
		$mNumber = $this->getNumber();
		$oNumberRange = $sClass::getObject($this);

		if(
			empty($mNumber) &&
			$oNumberRange !== null
		) {
			$oNumberRange->setDependencyEntity($this);
			// @TODO Nummernkreissperre?
			$sNumber = $oNumberRange->generateNumber();

			if (array_key_exists('number', $this->_aData)) {
				$this->number = $sNumber;
				$this->numberrange_id = $oNumberRange->id;
			} else {
				$aNumbers = [[
					'number' => $sNumber,
					'numberrange_id' => $oNumberRange->id
				]];
				$this->numbers = $aNumbers;
			}

			$bGenerated = true;
		}

		return $bGenerated;

	}
	
}
