<?php

use Core\Entity\System\Elements;

/*
 * webDynamics Basis Klasse
 */
class webdynamics {

	var $arrServiceProviders = array();
	var $arrHooks = array();
	var $arrTemplatePaths = array();
	var $arrIncludeFiles = array();
	var $strInterface;
	var $bFilesIncluded = false;

	protected static $aInstances = array();

	public static function resetInstances() {
		self::$aInstances = array();
	}

	public static function resetInstance($sInterface) {
		if(isset(self::$aInstances[$sInterface])) {
			unset(self::$aInstances[$sInterface]);
		}
	}
	
	/**
	 * Singleton
	 * @param string $sInterface
	 * @return webdynamics
	 */
	public static function getInstance($sInterface) {

		if(!isset(self::$aInstances[$sInterface])) {
			$oWebdynamics = new webdynamics($sInterface);
			$oWebdynamics->boot();
			
			self::$aInstances[$sInterface] = $oWebdynamics;
		}

		return self::$aInstances[$sInterface];

	}

	/**
	 * Singleton Konstruktor
	 * @global array $session_data
	 * @param string $strInterface
	 */
	private function __construct($strInterface = 'backend') {

		// Defaultwert setzen für Abwärtskompatibilität
		// @todo prüfen ob das noch benötigt wird
		if($strInterface == 'backend') {
			System::setInterface('backend');
		} else {
			System::setInterface('frontend');
		}

		$this->strInterface = $strInterface;		
	}

	/**
	 * Gibt das aktuell verwendete Interface zurück
	 *
	 * @return string
	 */
	public function getInterface() {
		return $this->strInterface;
	}
	
	/**
	 * Setzt die Werte aus der system_elements Konfiguration
	 * 
	 * @return bool
	 */
	public function boot() {
	
		// Zusammengefasstes Array für system_elements holen
		$aSystemElements = (new Core\Service\SystemElements())
				->getConfig();

		if(!isset($aSystemElements[$this->strInterface])) {
			return false; 
		}
		
		$aConfig = $aSystemElements[$this->strInterface];

		// Service Providers

		if(isset($aConfig['providers'])) {
			$this->setServiceProviders($aConfig['providers']);
		}

		// Hooks

		if(isset($aConfig['hooks'])) {
			foreach ($aConfig['hooks'] as $aHook) {
				$this->addHook($aHook['hook'], $aHook['module'], $aHook['element']);
			}
		}

		// Factory Allocations
		
		if(isset($aConfig['factory_allocations'])) {
			// @todo das hier ist nicht optimal - z.b. wenn man im Backend und Frontend 
			// zwei verschiedene Klassen benutzen will
			\Factory::setAllocations($aConfig['factory_allocations']);
		}

		// Includes (*.backend.php|*.frontend.php)
		// nur den Dateipfad merken, diese werden später erst inkludiert (sobald man weiß ob das Interface benutzt wird)
		
		if(isset($aConfig['includes'])) {
			$this->addIncludeFiles($aConfig['includes']);
		}

		return true;
	}
	
	/**
	 * Inkludiert die *.backend.php|*.frontend.php Dateien
	 * 
	 * @return bool
	 */
	public function getIncludes() {	
		
		if($this->bFilesIncluded) {
			return false;
		}
		
		foreach($this->arrIncludeFiles as $sFile) {
			$this->includeFile($sFile);
		}
		
		$this->bFilesIncluded = true;
		
		return true;
	}
	
	/**
	 * @todo diese Methode sollte nicht mehr benutzt werden
	 * @deprecated
	 */
	public function getBackendIncludes() {
		webdynamics::getInstance('backend')->getIncludes();
	}

	/**
	 * @todo diese Methode sollte nicht mehr benutzt werden
	 * @deprecated
	 */
	public function getFrontendIncludes() {		
		webdynamics::getInstance('frontend')->getIncludes();
	}

	public function addIncludeFiles(array $arrFilePaths) {
		$this->arrIncludeFiles = array_merge($this->arrIncludeFiles, $arrFilePaths);

		// Wenn die Dateien bereits includiert wurden muss die Datei direkt inkludiert werden
		if($this->bFilesIncluded) {
			foreach ($arrFilePaths as $strFilePath) {
				$this->includeFile($strFilePath);
			}
		}
	}

	/**
	 * @param string $strFilePath
	 */
	function addIncludeFile(string $strFilePath) {
		$this->arrIncludeFiles[] = $strFilePath;
		
		// Wenn die Dateien bereits includiert wurden muss die Datei direkt inkludiert werden
		if($this->bFilesIncluded) {
			$this->includeFile($strFilePath);
		}		
	}
	
	/**
	 * @param string $strHook
	 * @param string $strModule
	 * @param string $strElement
	 */
	function addHook($strHook, $strModule, $strElement = 'modul') {
		$this->arrHooks[$strHook][$strModule] = $strElement;
	}

	function hasHook($strHook, $strModule=null) {

		if($strModule === null) {
			return isset($this->arrHooks[$strHook]);
		}

		return isset($this->arrHooks[$strHook][$strModule]);
	}

	/**
	 * @param string $strHook
	 * @param array $mixInput
	 */
	function executeHook($strHook, &...$mixInput) {

		if(!empty($this->arrHooks[$strHook])) {
			foreach($this->arrHooks[$strHook] as $strModule => $strElement) {	
				if($strElement === 'bundle' || class_exists($strModule)) {
					/** @var \Core\Service\Hook\AbstractHook $objClass */
					$objClass = new $strModule($this->strInterface, $strHook);
					call_user_func_array([$objClass, 'run'], $mixInput);
				} else {
					// legacy
					$strClass = $strModule."_".$this->strInterface;
					$objClass = new $strClass();
					// wegen dem $... ist $mixInput jetzt ein Array
					$rmLegacy = &$mixInput[0];					
					$objClass->executeHook($strHook, $rmLegacy);
				}
			}
		}
	
	}

	public function setServiceProviders($aServiceProviderClasses) {
		$this->arrServiceProviders = array_merge($this->arrServiceProviders, $aServiceProviderClasses);
	}

	public function addServiceProvider($sServiceProviderClass) {
		$this->arrServiceProviders[] = $sServiceProviderClass;
	}

	public function getServiceProviders() {
		return $this->arrServiceProviders;
	}

	/**
	 * @param string $sFile
	 */
	private function includeFile(string $sFile) {
		/**
		 * Wichtig, damit es in den Includes unter dieser Variable zur 
		 * Verfügung steht
		 */
		$objWebDynamics = $this;

		// TODO _once ist scheinbar zwingend notwendig, da eine Datei sonst mehrfach eingebunden wird?
		include_once $sFile;

	}

}
