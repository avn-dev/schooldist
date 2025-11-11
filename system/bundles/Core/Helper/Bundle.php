<?php

namespace Core\Helper;

use Symfony\Component\Yaml\Yaml as YamlParser;
use Core\Exception;

class Bundle {
	
	/**
	 * Konvertiert einen String in das richtige Bundle-Format
	 * - CamelCase (core -> Core)
	 * - Bindestriche (camel-case -> CamelCase)
	 * 
	 * @param string $sConvert
	 * @return string
	 */
	public function convertBundleName($sConvert) {
		return \Util::convertHyphenLowerCaseToPascalCase($sConvert);
	}
	
	/**
	 * Liefert das Verzeichnis eines Bundles
	 * 
	 * @param string $sBundle
	 * @return string
	 * @throws \RuntimeException
	 */
	public function getBundleDirectory($sBundle, $bThrowException = true) {

		$sBundleDir = \Util::getDocumentRoot() . 'system/bundles/' . $sBundle;
		
		if($bThrowException && !is_dir($sBundleDir)) {
			throw new Exception\Bundle\UnknownBundleException('Unknown bundle "'.$sBundle.'"');
		}
		
		return $sBundleDir;
	}
	
	public function getBundleResourcesDirectory($sBundle, $bThrowException = true) {

		$sBundleRessourcesDir = $this->getBundleDirectory($sBundle, $bThrowException).'/Resources';
		
		if($bThrowException && !is_dir($sBundleRessourcesDir)) {
			throw new Exception\Bundle\NoResourcesException('Unable to find bundle resources directory!');
		}
		
		return $sBundleRessourcesDir;
	}
	
	/**
	 * Liefert den Pfad zu der Config-Datei eines Bundles
	 * 
	 * @param string $sBundle
	 * @return string
	 * @throws \RuntimeException
	 */
	public function getBundleConfigFile($sBundle, $sFile='config.yml', $bThrowException = true) {
		
		$sConfigFile = $this->getBundleResourcesDirectory($sBundle, $bThrowException).'/config/'.$sFile;
		
		if($bThrowException && !is_file($sConfigFile)) {
			throw new Exception\Bundle\NoConfigFileException('No config file found in bundle "'.$sBundle.'"');
		}
		
		return $sConfigFile;
	}
	
	/**
	 * Liest eine Datei aus dem Resourcen-Ordner des Bundles. Dabei wird geprüft ob diese
	 * Datei als PHP-Datei oder als YML-Datei vorliegt
	 * 
	 * @todo caching
	 * 
	 * @param string $sBundle
	 * @param string $sFile
	 * @return array
	 */
	public function readBundleFile($sBundle, $sFile = 'config', $bThrowException = true) {
		
		$sConfigFile = $this->getBundleConfigFile($sBundle, $sFile.'.php', false);
		
		$aBundleConfig = [];
		if(file_exists($sConfigFile)) {
			$aBundleConfig = require $sConfigFile;			
		} else {
			$sConfigFile = $this->getBundleConfigFile($sBundle, $sFile.'.yml', $bThrowException);
			
			if(file_exists($sConfigFile)) {
				$oYamlParser = new YamlParser();
				$sYamlContent = file_get_contents($sConfigFile);
				$aBundleConfig = $oYamlParser->parse($sYamlContent);
			}
		}
		
		return $aBundleConfig;
		
	}
	
	/**
	 * Liefert Daten aus der Config-Datei eines Bundles
	 * - Config-Datei unter: $sBundle/Resources/config/config(.yml|.php)
	 * 
	 * @param string $sBundle
	 * @return array
	 */
	public function getBundleConfigData($sBundle, $bThrowException = true) {
		return $this->readBundleFile($sBundle, 'config', $bThrowException);
	}

	/**
	 * @param string $sClass
	 * @return string
	 */
	public function getBundleFromClassName($sClass) {
		
		$sClass = trim($sClass, '\\');
		list($sBundle, $sDummy) = explode('\\', $sClass, 2);
				
		return $sBundle;
	}

	public static function getAllBundles() {
		$aPaths = glob(\Util::getDocumentRoot() . 'system/bundles/*', GLOB_ONLYDIR);
		return array_map('basename', $aPaths);
	}

    public static function getAllServiceProviders() {

        $aPaths = glob(\Util::getDocumentRoot() . 'system/bundles/*/ServiceProvider.php');

        return array_map(function($sPath) {
            return '\\'.str_replace([\Util::getDocumentRoot() . 'system/bundles/', '.php', '/'], ['', '', '\\'], $sPath);
        }, $aPaths);
    }

}


