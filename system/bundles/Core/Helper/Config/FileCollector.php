<?php

namespace Core\Helper\Config;

use Core\Helper\Config\Php\FileCollector as PhpFileCollector;
use Core\Helper\Config\Yaml\FileCollector as YamlFileCollector;

class FileCollector {
	
	/**
	 * @return \Core\Helper\Routing\AbstractFile[]
	 */
	public function collectAllFileParts() {
		
		$aFiles = (new PhpFileCollector())->collectAllFileParts();

		$aFiles = array_merge($aFiles, (new YamlFileCollector)->collectAllFileParts());
		
		return $aFiles;
	}
	
}

