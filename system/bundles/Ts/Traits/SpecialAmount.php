<?php

namespace Ts\Traits;

trait SpecialAmount {

	protected $specialCodes = [];

	abstract public function getSpecialBlocks():array;
	
	abstract public function getSpecialAmount():float;
	 
	public function getSpecialCodes():array {
		return $this->specialCodes;
	}
	
}
