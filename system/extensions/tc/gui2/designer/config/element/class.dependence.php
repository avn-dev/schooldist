<?php

class Ext_TC_Gui2_Designer_Config_Element_Dependence {
	
	protected $_sOwnClass			= '';
	protected $_sDependenceKey		= '';
	protected $_sDependenceType		= '';
	protected $_sDependenceTable	= '';
	protected $_sDependenceFk		= '';
	protected $_sDependencePk		= 'id';
	protected $_sDependenceStaticFields	= [];
	protected $_aDependenceMethodCall	= [];
	protected $_sDependenceChildAlias;


	/**
	 * setzt die klasse des Objectes des Elementes
	 * @param string $sOwnClass
	 */
	public function setOwnClass($sOwnClass){
		$this->_sOwnClass = $sOwnClass;
	}
	
	/**
	 * setzt den Typ der beziehung
	 * jointable/parent/child
	 * @todo child einbauen 
	 * child -> das element ist ein kind element => id ( des haupteintrages (gui) ) steht im element object
	 * @param string $sDependenceType
	 */
	public function setDependenceType($sDependenceType, $sDependenceKey = null){
		$this->_sDependenceType = $sDependenceType;
		if($sDependenceKey !== null) {
			$this->_sDependenceKey = $sDependenceKey;
		}
	}
	
	/**
	 * Setzt den JoinTable Key
	 * @param string $sDependenceKey
	 */
	public function setDependenceKey($sDependenceKey = '' ){
		$this->_sDependenceKey = $sDependenceKey;
	}
	
	/**
	 * set Die abhängige tabelle ( nötig bei Jointable )
	 * @param string $sDependenceTable
	 */
	public function setDependenceTable($sDependenceTable){
		$this->_sDependenceTable = $sDependenceTable;
	}
	
	/**
	 * setzt den Fremdschlüssel
	 * @param string $sDependenceFk
	 */
	public function setDependenceFk($sDependenceFk){
		$this->_sDependenceFk = $sDependenceFk;
	}
	
	/**
	 * setzt den Primärschlüssen
	 * @param string $sDependencePk
	 */
	public function setDependencePk($sDependencePk){
		$this->_sDependencePk = $sDependencePk;
	}

	/**
	 * setzt den Alias der Childs
	 * @param string $sAlias
	 */
	public function setDependenceChildAlias($sAlias){
		$this->_sDependenceChildAlias = $sAlias;
	}

	/**
	 * array with static fields and values for Join table dependency
	 * array( field => value )
	 * @return array $aFields
	 */
	public function setDependenceStaticFields(array $aFields){
		$this->_sDependenceStaticFields = $aFields;
	}

	public function setDependenceMethodCall(string $sMethod, array $aParameters = []) {
		$this->_aDependenceMethodCall = [$sMethod, $aParameters];
	}

	/**
	 * gibt den klassennamenzurück
	 * @return string
	 */
	public function getOwnClass(){
		return $this->_sOwnClass;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getDependenceType(){
		return $this->_sDependenceType;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getDependenceKey(){
		return $this->_sDependenceKey;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getDependenceTable(){
		return $this->_sDependenceTable;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getDependenceFk(){
		return $this->_sDependenceFk;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getDependencePk(){
		return $this->_sDependencePk;
	}

	/**
	 * array with static fields and values for Join table dependency
	 * array( field => value )
	 * @return array
	 */
	public function getDependenceStaticFields(){
		return (array)$this->_sDependenceStaticFields;
	}

	/**
	 *
	 * @return string
	 */
	public function getDependenceChildAlias(){
		return $this->_sDependenceChildAlias;
	}

	/**
	 * @return array
	 */
	public function getDependenceMethodCall(): array {
		return $this->_aDependenceMethodCall;
	}

}
