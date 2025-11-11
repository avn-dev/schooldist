<?php

/**
 * Dummy-Klasse für Platzhalter-Klasse
 * 
 * Wird für Platzhalter benötigt, die nicht direkt an eine WDBasic gebunden sind
 */
class Ext_TC_Placeholder_Abstract_Object {
	use \Tc\Traits\Placeholder;
	
	protected $_sPlaceholderClass = null;
	
	protected $aData = array();

	public function __construct(string $sPlaceholderClass, array $aData = []) {
		if(!is_a($sPlaceholderClass, Ext_TC_Placeholder_Abstract::class, true)) {
			throw new Exception(sprintf('Placeholder class "%s" must be an instance of "%s"', $sPlaceholderClass, Ext_TC_Placeholder_Abstract::class));
		}

		$this->_sPlaceholderClass = $sPlaceholderClass;
		$this->aData = $aData;
	}

	/**
	 * getter-Methode für die Klasse
	 * 
	 * @param string $sKey
	 * @param mixed $mValue
	 */
	public function __set($sKey, $mValue) {
		$this->aData[$sKey] = $mValue;
	}
	
	/**
	 * setter-Methode für die Klasse
	 * 
	 * @param string $sKey
	 * @return mixed
	 * @throws Exception
	 */
	public function __get($sKey) {
		
		if($sKey == 'id') {
			return 0;
		}
		
		if(!isset($this->_aData[$sKey])) {
			throw new Exception('Requested data "'.$sKey.'" of class "'.get_class($this).'" do not exists!');
		}
		
		return $this->aData[$sKey];
	}
	
}
