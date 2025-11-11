<?php

namespace Core\Helper\Config\Php;

use Illuminate\Support\Arr;

class File extends \Core\Helper\Routing\AbstractFile {

	protected $aConfig;
	
	public function parseContent() {
		// kein require_once!
		$this->aConfig = require $this->_sFileName;
	}

	public function hasConfig(string $sKey) {
		return Arr::has($this->aConfig, $sKey);
	}
	
	public function get(string $sKey, $mDefault = null) {
		return Arr::get($this->aConfig, $sKey, $mDefault);
	}

}
