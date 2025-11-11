<?php
interface Ext_TC_Mapping_Field_Interface {
	
	/**
	 *
	 * @param string $sConfig
	 * @param mixed $mValue 
	 */
	public function addConfig($sConfig, $mValue);
	
	/**
	 *
	 * @param string $sConfig 
	 */
	public function removeConfig($sConfig);
	
	/**
	 *
	 * @param string $sConfig
	 * @param mixed $mValue 
	 */
	public function changeConfig($sConfig, $mValue);
	
	/**
	 *
	 * @param string $sConfig
	 * @return mixed 
	 */
	public function getConfig($sConfig);
	
	/**
	 * add a Format to the field
	 * @param $oFormat 
	 */
	public function addFormat($oFormat);
	
	/**
	 * get the Format to the field
	 */
	public function getFormat();
	
	/**
	 * Objekt als Array
	 * @return array 
	 */
	public function toArray();
	
	/**
	 *
	 * @return bool 
	 */
	public function isOriginal();
}