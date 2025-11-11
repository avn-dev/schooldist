<?php


class Ext_Gui2_Config_Basic {
	
	protected $_aConfig = array();
	
	/**
	 *
	 * @var Ext_TC_Gui2_Filterset_Bar_Element
	 */
	protected $_oDesignElement = null;
	
	/**
	 * Set Configurations Werte
	 * @param $sConfig
	 * @param $mValue
	 */
	
	public function __set($sConfig, $mValue){
		if(property_exists($this, $sConfig)){
			$this->$sConfig = $mValue;
		}
		$this->setConfig($sConfig, $mValue);
	}

	public function checkConfig($sConfig, $mValue){
		return true;
	}

	public function setConfig($sConfig, $mValue){
		
		if(key_exists($sConfig, $this->_aConfig)){
			if($this->checkConfig($sConfig, $mValue)){
				$this->_aConfig[$sConfig] = $mValue;
			} else {
				// Alte klasse speichert sich in der session und verursacht fehler
				if($sConfig == 'visible'){
					return 1;
				} else {
					throw new Exception("Configuration wrong [".$sConfig."]");
				}
			}
		} else {
			throw new Exception("Configuration unknown [".$sConfig."]");
		}
		
	}

	/**
	 * Liefert Konfigurationswerte
	 *
	 * @param string $sConfig
	 * @return mixed
	 */
	public function __get($sConfig) {
		if(property_exists($this, $sConfig)) {
			return $this->$sConfig;
		}
		return $this->getConfig($sConfig);
	}

	/**
	 * @param string $sConfig
	 * @return bool
	 */
	public function __isset($sConfig) {
		if(
			isset($this->$sConfig) ||
			key_exists($sConfig, $this->_aConfig)
		) {
			return true;
		}
		return false;
	}
	
	public function getConfig($sConfig){

		if(key_exists($sConfig, $this->_aConfig)) {
			return $this->_aConfig[$sConfig];
		} else {
			// Alte klasse speichert sich in der session und verursacht fehler
			if(
				$sConfig == 'visible' ||
				$sConfig == 'flexibility'
			) {
				return 1;
			} elseif(
				$sConfig == 'query_value_key' ||
				$sConfig == 'encode_data_id_field' ||
				$sConfig == 'use_coalesce' ||
				$sConfig == 'rows_clickable' ||
				$sConfig == 'instance_hash' ||
				$sConfig == 'required' ||
				$sConfig == 'force_reload'
			) {
				return false;
			} elseif(
				$sConfig == 'column_flexibility'
			) {
				return true;
			} elseif(
				$sConfig == 'foreign_jointable'
			){
				return '';
			}else {
				//throw new Exception("Configuration unknown [".$sConfig."]");
//				return '';
				return null;
			}
		}
	}

	/**
	 * @todo Nur die benötigten Werte an JS weitergeben
	 * @return array
	 */
	public function getConfigArray(){
		return $this->_aConfig;
	}
	
	/**
	 * Design Bar Element in das Zeitfilter Element setzen, um Informationen für "basierend auf" zu bekommen
	 * 
	 * @param Ext_TC_Gui2_Filterset_Bar_Element $oElement 
	 */
	public function setDesignElement(Ext_TC_Gui2_Filterset_Bar_Element $oDesignElement)
	{
		$this->_oDesignElement		= $oDesignElement;
		
		$this->_prepareElementFromDesignElement();
	}
	
	/**
	 * Kann abgeleitet werden, falls anhand des DesignElements irgendwelche Daten hier rein gesetzt
	 * werden müssen
	 * 
	 * @return bool 
	 */
	protected function _prepareElementFromDesignElement()
	{
		return true;
	}
	
}