<?php

namespace Core\Helper\Routing;

abstract class AbstractFile {

	protected $_sFileName;
	protected $sBundle;

	public function __construct(string $sFileName) {
		// Setze den absulten Pfad
		$this->_sFileName = $sFileName;
		
		$aMatch = [];
		$iMatch = preg_match('@/bundles/([a-zA-Z0-9]+)/@', $sFileName, $aMatch);
		
		if($iMatch !== 1) {
			throw new \RuntimeException('Invalid routing file "'.$sFileName.'"!');
		}
		
		$this->sBundle = $aMatch[1];

	}

	public function getBundle() {
		return $this->sBundle;
	}
	
	abstract public function parseContent();

}