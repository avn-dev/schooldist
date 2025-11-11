<?php

namespace TsAccommodation\Entity\Matching;

class Criterion {
	
	private $sType = 'select';
	private $sAccommodationType = 'checkbox';
	private $aOptions = [];
	
	public function __construct() {
		
	}
	
	public function setType($sType) {
		
		$this->sType = $sType;
		
		return $this;
	}
	
	public function setAccommodationType($sAccommodationType) {
		
		$this->sAccommodationType = $sAccommodationType;
		
		return $this;
	}
	
	public function setField($sField) {
		
		$this->sField = $sField;
		
		return $this;
	}
	
	public function setLabel($sLabel) {
		
		$this->sLabel = $sLabel;
		
		return $this;
	}
	
	public function setOptions(array $aOptions) {
		
		$this->aOptions = $aOptions;
		
		return $this;
	}
	
	public function setAccommodationField($sAccommodationField) {
		
		$this->sAccommodationField = $sAccommodationField;
		
		return $this;
	}
	
	public function getAccommodationField() {
		return $this->sAccommodationField;
	}
	
	public function getField() {
		return $this->sField;
	}
	
	/**
	 * @param bool $bL10N
	 * @return string
	 */
	public function getLabel(bool $bL10N=false) {
		
		if($bL10N === true) {
			return \L10N::t($this->sLabel, \Ext_Thebing_Matching::L10N_DESCRIPTION);
		}
		
		return $this->sLabel;
	}
	
	public function getOptions() {
		return $this->aOptions;
	}
	
	public function getType() {
		return $this->sType;
	}
	
	public function getAccommodationType() {
		return $this->sAccommodationType;
	}
	
}
