<?php

namespace Core\Helper\Routing\Yaml;

use Symfony\Component\Yaml\Yaml as YamlParser;

/**
 * Diese Klasse soll eine composer.json-Datei darstellen. 
 */
class File extends \Core\Helper\Routing\AbstractFile {

	protected $aRoutes;

	public function parseContent(){
		// Prüfe, ob die Datei existiert und lesbar ist 
		if (!file_exists($this->_sFileName)){
			throw new \RuntimeException('FileNotFound: ' . $this->_sFileName);
		} elseif (!is_readable($this->_sFileName)){
			throw new \RuntimeException('FileNotReadable: Permission denied in ' . $this->_sFileName);
		}

		$oYamlParser = new YamlParser();
		$sYamlContent = file_get_contents($this->_sFileName);
		$this->aRoutes = $oYamlParser->parse($sYamlContent);

	}

	public function getRoutes() {
		return (array)$this->aRoutes;
	}

}