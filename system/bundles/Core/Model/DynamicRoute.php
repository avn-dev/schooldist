<?php

namespace Core\Model;

use Illuminate\Support\Facades\Route;

class DynamicRoute {
	
	private $sName;
	
	private $sPath;
	
	private $sHost;
	
	private $aController;
	
	private $aDefaults = [];
	
	private $aRequirements = [];
	
	public function setName($sName) {
		$this->sName = $sName;
	}
	
	public function setPath($sPath) {
		$this->sPath = $sPath;
	}
	
	public function setHost($sHost) {
		$this->sHost = $sHost;
	}
	
	public function setRequirements($aRequirements) {
		$this->aRequirements = $aRequirements;
	}
	
	public function getRequirements() {
		return $this->aRequirements;
	}
	
	public function setDefault($sKey, $mValue) {
		$this->aDefaults[$sKey] = $mValue;
	}
	
	public function getDefault($sKey) {
		return $this->aDefaults[$sKey];
	}
	
	public function getDefaults() {
		return $this->aDefaults;
	}

	public function setController($sClass, $sMethod) {
		$this->aController = [
			$sClass,
			$sMethod
		];
	}	
	
	public function getController() {
		return $this->aController;
	}	
	
	public function getName() {
		return $this->sName;
	}
	
	public function getPath() {
		return $this->sPath;
	}
	
	public function getArray() {

		$aDynamicRoute = [
			'path' => $this->sPath,
			'controller' => $this->aController,
			'host' => $this->sHost,
			'defaults' => $this->aDefaults,
			'requirements' => $this->aRequirements
		];
		
		return $aDynamicRoute;
	}
	
	public function addLaravelRoute() {

		$route = Route::any($this->getPath(), $this->getController());
		$route->name($this->getName());
		$defaults = $this->getDefaults();
		foreach($defaults as $defaultKey=>$defaultValue) {
			$route->defaults($defaultKey, $defaultValue);
		}

	}
	
}
