<?php
/**
 * UML - https://redmine.thebing.com/redmine/issues/278
 */
interface Ext_TC_Frontend_Form_Entity_Interface {
	
	/**
	 * get the Mapping object of the Enity
	 * @param $sMappingType Type of the Mapping
	 * @return Ext_TC_Frontend_Mapping 
	 */
	public function getMapping($sMappingType);
	
	/**
	 * @param string $sMappingType
	 */
	public function getMappingFields($sMappingType);
	
	/**
	 * add a Child for the given Identifier
	 * @return Ext_TC_Frontend_Form_Entity_Interface
	 */
	public function addChild($sChildIdentifier, $sJoinedObjectCacheKey);
	
	/**
	 * remove a Child from the Entity 
	 */
	public function removeChild($sChildIdentifier, $iCount);
	
	/**
	 * validate the Entity
	 * @return boolean 
	 */
	public function validate($bThrowExceptions = false);
	
	/**
	 * save the Entity
	 * @return boolean 
	 */
	public function save();


	/**
	 * Magic Method are needed for set and get values 
	 */
	public function __set($sField, $mValue);
	public function __get($sField);
	
}