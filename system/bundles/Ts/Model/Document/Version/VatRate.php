<?php

namespace Ts\Model\Document\Version;

class VatRate {
	
	use \Tc\Traits\Placeholder;
	
	protected $_sPlaceholderClass = '\Ts\Model\Document\Version\VatRatePlaceholder';
	
	/**
	 * @var array
	 */
	public $lines = [];
	
	public function getLines() {
		return implode(', ', (array)$this->lines);
	}
	
	public function getLinesCount() {
		return count((array)$this->lines);
	}
	
}
