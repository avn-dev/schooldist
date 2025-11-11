<?php

namespace Core\Entity\System;

class Elements extends \WDBasic {
	
	protected $_sTable = 'system_elements';
	protected $_sTableAlias = 'se';

	public function getName() {	
		return $this->title;		
	}
	
	public function isBundle() {
		return $this->element === 'bundle';
	}
	
	public function isModule() {
		return $this->element === 'modul';
	}
	
	public function isFrontendElement() {
		return $this->include_frontend == 1;
	}
	
	public function isBackendElement() {
		return $this->include_backend == 1;
	}
	
}