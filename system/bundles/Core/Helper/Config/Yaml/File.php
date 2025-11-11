<?php

namespace Core\Helper\Config\Yaml;

/**
 * Diese Klasse soll eine composer.json-Datei darstellen.
 */
class File extends \Core\Helper\Routing\Yaml\File {

	public function get(string $sKey) {		
		if(!empty($this->aRoutes[$sKey])) {
			return $this->aRoutes[$sKey];
		}
	}

}