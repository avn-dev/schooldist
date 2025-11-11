<?php

/**
 * Klasse für den Zugriff auf die Konfiguration
 * Inklusive Fake-WDBasic-Methoden
 * ACHTUNG: IST NUR EINE FAKE-WDBASIC Instanz
 */
class Ext_TC_Config extends Ext_TC_Basic {
	
	protected $aConfig = array();

	protected $_sTable = 'system_config';
	
	protected $_aFormat = array();
	
	/**
	 * @var static
	 */
	protected static $oInstance;
	
	protected $_sPrimaryColumn = 'c_key';
	
	public function __construct($iDataID = 0, $sTable = null, $loadNew = false) {

		$this->_loadData($loadNew);
		
		$this->_aData['c_key'] = $iDataID;
		
	}

	/**
	 * WDBasic Fake Methoden
	 */
	public function __set($sName, $mValue) {

		if(
			isset($this->aConfig[$sName]) ||
			isset($this->_aFormat[$sName])
		) {
			
			$this->set($sName, $mValue);

		} else {
			throw new Exception('No valid config value "'.$sName.'".');
		}

	}
	
	public function __get($sName) {

		if(
			isset($this->aConfig[$sName]) ||
			isset($this->_aFormat[$sName])
		) {

			$mValue = $this->getValue($sName);

		} else {
			throw new Exception('No valid config value "'.$sName.'".');
		}

		return $mValue;
	}

	public function getJoinedObject($sMixed, $sKey=null) {
		return $this;
	}

	/**
	 * @param int $iDataID
	 * @return static
	 */
	public static function getInstance($iDataID = 0) {

		if(self::$oInstance === null) {
			self::$oInstance = new static();
		}

		self::$oInstance->_aData['c_key'] = $iDataID;
		
		return self::$oInstance;

	}

	/**
	 * @deprecated
	 *
	 * @param $sName
	 * @return mixed
	 */
	public function getValue($sName) {
		return $this->aConfig[$sName];
	}

	/**
	 * @deprecated
	 *
	 * @param $sName
	 * @return mixed
	 */
	public function get($sName) {

		/*
		 * TODO - In der Agentur schlug hier beim Update etwas fehl da $this->aConfig[$sName] bereits ein Array mit
		 * ['key' => '', 'value' => ''] war. Erst nachdem man den Cache geleert hat war es ein der Wert aus c_value
		 */
		if(
			is_array($this->aConfig[$sName]) &&
			isset($this->aConfig[$sName]['key']) &&
			isset($this->aConfig[$sName]['value'])
		) {
			return $this->aConfig[$sName];
		}

		return ['key' => $sName, 'value' => $this->aConfig[$sName]];
	}

	/**
	 * @param string $sName
	 * @param mixed $mValue
	 * @throws \DomainException
	 */
	public function set($sName, $mValue) {
		
		// Daten manipulieren
		$this->manipulateConfigData($sName, $mValue);

		if (isset($this->_aFormat[$sName]['validate'])) {
			$oValidate = new WDValidate();
			$oValidate->check = $this->_aFormat[$sName]['validate'];
			$oValidate->parameter = $this->_aFormat[$sName]['validate_value'];
			$oValidate->value = $mValue;
			if (!$oValidate->execute()) {
				throw new \DomainException(sprintf('Invalid value for config value %s, check %s', $sName, $this->_aFormat[$sName]['validate']));
			}
		}

		if($mValue === null) {
			$mValue = '';
		}

		// Änderung direkt speichern
		$this->saveValue($sName, $mValue);
		
		// Änderung loggen
		$oUser = System::getCurrentUser();
		$oLogger = Log::getLogger();
		$oLogger->addInfo('Configuration changed: '.$sName, [
			'new' => $mValue,
			'old' => $this->getValue($sName),
			'user_id' => $oUser->id
		]);

		// Geänderte Daten in Cache schreiben
		$this->_loadData(true);

	}

	protected function saveValue($sName, $mValue) {

		if(is_array($mValue)) {
			if (!empty($this->_aFormat[$sName]['json'])) {
				$mValue = json_encode($mValue);
			} else {
				$mValue = serialize($mValue);
			}
		}

		System::s($sName, $mValue);
	}


	public function validate($bThrowExceptions=false) {
		
		return true;
		
	}
	
	static public function deleteCache() {
		$oConfig = Factory::getInstance('Ext_TC_Config');
		$oConfig->_loadData(true);
	} 
	
	public function save($bLog=true){
		
	}
	
	protected function _loadData($bLoadNew = false){
		
		$aCache = WDCache::get('Ext_TC_Config::_loadData');

		if(
			empty($aCache) ||
			$bLoadNew
		) {

			$sSql = " SELECT c_key, c_value FROM `system_config` ";
			$this->aConfig = DB::getQueryPairs($sSql);

			foreach ($this->aConfig as $sKey => &$mValue) {
				if (!empty($this->_aFormat[$sKey]['json'])) {
					$mValue = json_decode($mValue, true);
				}
			}

			$aCache = $this->aConfig;

			WDCache::set('Ext_TC_Config::_loadData', 68400, $this->aConfig);

		}

		$this->aConfig = $aCache;
		
		$this->_aData = [
			'c_key' => null,
			'c_value' => null
		];

	}
	
	public function exist() {

		// Man muss auch Einstellungen anlegen können, die es vorher noch nicht gab
		return true;
		
		if(isset($this->aConfig[$this->_aData[$this->_sPrimaryColumn]])) {
			return true;
		}
		
		return false;
	}

	/**
	 * @deprecated
	 */
	protected function manipulateConfigData($sKey, $sValue) {
		
	}

	/**
	 * @see \AdminTools\Gui2\SettingsGui::executeGuiCreatedHook()
	 */
	public function getInternalSettings(): array {

		return [
			['key' => 'tc_flex_fields_per_section_limit', 'label' => 'Custom Fields: Max fields per section', 'type' => 'input', 'form_text' => 'Default: '.Ext_TC_Flexibility::FIELD_LIMIT_PER_SECTION],
			['key' => 'tc_flex_fields_per_section_visible_limit', 'label' => 'Custom Fields: Max fields per list', 'type' => 'input', 'form_text' => 'Default: '.(Ext_TC_Flexibility::FIELD_LIMIT_PER_SECTION / 2)],
			['key' => 'zendesk_id', 'label' => 'Zendesk-ID', 'type' => 'input'],
			['key' => 'version', 'label' => 'Version', 'type' => 'input'],
		];

	}
	
}
