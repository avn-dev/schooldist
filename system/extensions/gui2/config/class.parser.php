<?php

class Ext_Gui2_Config_Parser {

	protected $sConfigName = '';
	protected $_sConfigFile = '';
	protected $_aConfig = array();
	protected $_aDefaultBarConfig = array();
	protected $_aDefaultColumnConfig = array();
	private $bCanIgnoreCache = false;

	protected static $_aCache = array();
	protected static $_aColumnsCache = array();

	/**
	 * Name der letzten Config, die geladen wurde (bei $_sConfigFile ist es auch die letzte Datei)
	 *
	 * @TODO: Keine Ahnung, ob $bExtension noch benötigt wird, weil Dateiname seit Fidelo-Relaunch generell nicht mehr funktionieren dürfte
	 *
	 * @param bool $bExtension
	 * @return mixed|string
	 */
	public function getName($bExtension=true) {

		if($this->sConfigName === self::getDefaultConfigFileName()) {
			// Keine Ahnung, ob das passieren kann
			throw new RuntimeException('getName() would return gui2_default');
		}

		return $this->sConfigName;

//		$aFileData = explode('/', $this->_sConfigFile);
//		$sName = end($aFileData);
//
//		if(!$bExtension) {
//			$sName = basename($sName, ".yml");
//		}
//
//		return $sName;

	}

	/**
	 * load default Config and (optional) the given Config
	 * @param string $sConfig
	 * @param bool $bCanIgnoreCache
	 */
	public function __construct($sConfig = '', $bCanIgnoreCache = false) {

		$this->bCanIgnoreCache = $bCanIgnoreCache;

		$sDefaultConfig = self::getDefaultConfigFileName();

		$this->setConfig($sDefaultConfig);
		$this->load(false);

		$this->_createDefaultDummies();

		/** @var Ext_Gui2 $sClass */
		$sClass = Factory::getClassName('Ext_Gui2');
		$sClass::manipulateDefaultConfig($this->_aConfig);

		if(!empty($sConfig)) {
			$this->setConfig($sConfig);
			$this->load();
		}

	}
	
	/**
	 * Gibt den Namen der Standardkonfiguration zurück
	 * @return string
	 */
	public static function getDefaultConfigFileName() {
		return 'gui2_default';
	}

	/**
	 * get the configuration dir
	 * @param boolean $bWithRoot
	 * @return string
	 */
	public static function getConfigDir($bWithRoot = false){
		$sDir = 'system/config/gui2/';
		if($bWithRoot){
			$sDir = self::getDocumentRoot().$sDir;
		}
		return $sDir;
	}

	/**
	 * @param string $sConfig
	 */
	public function setConfig($sConfig) {

		$sConfig = explode('.', $sConfig);
		$sConfig = reset($sConfig);
		$aConfig = explode('_', $sConfig, 2);

		$sBundleName = ucfirst(array_shift($aConfig));
		$sNameSpace = $sBundleName.'/Resources/config/gui2/';
		$sFileName = 'system/bundles/'.$sNameSpace.$aConfig[0].'.yml';

		// Hier sollten dann eigentlich die Namen der letzten Child-Ebene drin stehen
		$this->sConfigName = $sConfig;
		$this->setFile($sFileName);

	}

	/**
	 * set a Config File (relative Path)
	 * @param string $sConfigFile
	 */
	public function setFile($sConfigFile){
		
		$sConfigFile = ltrim($sConfigFile, '/');
		
		$this->_sConfigFile = $sConfigFile;
	}
	
	/**
	 * load the Config and merge the new Values with the old (or defaults) 
	 * @param bool $bMerge
	 */
	public function load(bool $bMerge = true){

		$sFile = $this->_sConfigFile;

		$sCacheKey = 'Ext_Gui2_Config_Parser::load_'.$sFile;

		// Cache nur ohne Debugmodus nutzen
		if(isset(self::$_aCache[$sFile])) {
			// Wird scheinbar dafür benötigt, weil der Parser im selben Request mehrere Male (Designer & co.) aufgerufen wird?
			// Vor allem JEDES EINZELNE Filterset-Element springt nochmal in den Parser!
			$aCache = self::$_aCache[$sFile];
		} elseif(!$this->bCanIgnoreCache || System::d('debugmode') != 2) {
			$aCache = WDCache::get($sCacheKey);
		}

		if(empty($aCache)) {

			$aConfig = $this->_parseFile();

			// wenn parent da ist dann erst parent daten laden und mergen
			if(!empty($aConfig['parent'])){
				$sConfig = $this->getName();
				$sParent = $aConfig['parent'];
				$this->setConfig($sParent);
				$this->load();

				// wieder zurück setzten damit der ursprungs name bekannt bleibt
				$this->_sConfigFile = $sConfig;
			}

			if(
				$bMerge &&
				is_array($aConfig)	
			) {
				$aConfig = $this->_merge($this->_aConfig, $aConfig);

				if (isset($aConfig['extend'])) {
					// Bestimmte Konfigurationen erweitern, muss vor _validateColumns() passieren, damit die Defaultwerte
					// zu den Spalten gesetzt werden
					$aConfig = $this->_extend($aConfig, $aConfig['extend']);
				}
			}

			$aCache[$sFile] = $this->_aConfig = $aConfig;

			if($bMerge){
				$this->_validateBars();
				$this->_validateColumns();
			}

			WDCache::set($sCacheKey, 86400, $this->_aConfig, false, 'Ext_Gui2_Config_Parser::load');
			
		} else {
			$this->_aConfig = $aCache;
		}		

		if (!empty($this->_aConfig['set'])) {
			$this->mergeSet($this->_aConfig['set']);
		}

		if(!empty($this->_aConfig['hook'])) {
			$aHookData = ['config' => &$this->_aConfig, 'parser' => $this];
			System::wd()->executeHook($this->_aConfig['hook'], $aHookData);
		}		
		
		self::$_aCache[$sFile] = $this->_aConfig;
	}

	public function mergeSet(string $sSet) {

		if(!isset($this->_aConfig['sets'][$sSet])) {
			return;
		}

		// Folgende Einstellungen dürfen nicht gesetzt sein da diese einen eigenen Key "set" haben
		$aBlacklist = ['bars', 'columns'];

		$aSetConfig = array_diff_key($this->_aConfig['sets'][$sSet], array_flip($aBlacklist));

		$this->_aConfig = $this->_merge($this->_aConfig, $aSetConfig);

	}

	/**
	 * return the Value of the given Layer
	 * you define set one ore more layers deps
	 * etc. "index" or array("bars", 0)
	 * @param array|string $mLayer
	 * @return string|array|boolean|null|int 
	 */
	public function get($mLayer = array()){
		
		$this->checkLayer($mLayer);
		
		$aConfig = $this->_aConfig;
		foreach((array)$mLayer as $sLayer){
			$aConfig = $aConfig[$sLayer];
		}
		return $aConfig;
	}
	
	public function set(array $aLayer, $mValue) {

		$this->checkLayer($aLayer);

		$aConfig =& $this->_aConfig;
		foreach($aLayer as $sLayer) {
			$aConfig =& $aConfig[$sLayer];
		}

		$aConfig = $mValue;		
	}
	
	protected function checkLayer($mLayer) {
		
		if(
			!is_array($mLayer) &&
			!is_string($mLayer) &&
			!is_numeric($mLayer)
		){
			throw new ErrorException('You can only set a string, int or array as config layer');
		}
		
	}


	/**
	* Bestimmte Spalte aus der Config finden
	*
	* @param string $sDbColumn
	* @return array
	*/
	public function getColumn($sDbColumn) {
		$aColumn = false;
		$aColumns = $this->getColumns();

		foreach($aColumns as $aColumnData) {
			if($aColumnData['column'] == $sDbColumn) {
				$aColumn = $aColumnData;

				break;
			}
		}

		return $aColumn;
	}

	/**
	 * get all columns with the default columns and the row style column
	 *
	 * @see \Ext_Gui2_Index_Generator::getFields()
	 *
	 * @return array
	 */
	public function getColumns(){
		
		$sKey = $this->getName();

		if(!isset(self::$_aColumnsCache[$sKey])) {

			$aFields    = $this->get('columns');  

			$aDefaultColumns    = (array)$this->get('default_columns');  

			$aFields            = array_merge($aFields, $aDefaultColumns);

			$aFinalFields       = array();

			foreach($aFields as $iKey => $aField){

				$aField['_column']   = $aField['column']; // original column merken

				// TODO Dieses Faken der Columns ist ziemlicher Käse, da an diversen Stellen weiterhin die Ursprungscolumn benötigt wird
				if(!empty($aField['i18n'])) {

					if ($aField['format'] === Ext_TC_Gui2_Format_YesNo::class) {
						throw new DomainException('Wrong usage of Ext_TC_Gui2_Format_YesNo and Elasticsearch. Field: '.$aField['column'].' Migration: post_format');
					}

					$aBackendLangs = Factory::executeStatic('Util', 'getLanguages', ['backend']);
					
					if(
						isset($aField['i18n']['interface']) &&
						$aField['i18n']['interface'] === 'frontend'
					) {
						
						$aFrontendLangs = Factory::executeStatic('Util', 'getLanguages');
						
						// Solange nicht alle Frontend-Sprachen angezeigt werden, wird nur die Schnittmenge benötigt
						if(empty($aField['i18n']['all'])) {
							$aLangs = array_intersect_key($aBackendLangs, $aFrontendLangs);
						} else {
							$aLangs = $aFrontendLangs;
						}
					} else {
						// Sprachen sind jetzt einstellbar, daher war der Abgleich nicht mehr sinnvoll wegen den lokalisierten Sprachen (de_CH).
						$aLangs = $aBackendLangs;
					}

					foreach($aLangs as $sIso => $sLanguage) {
						$aLangField             = $aField;
						$sLang                  = $sIso;
						$aLangField['column']   = $aLangField['column'].'_'.$sLang;
						$aLangField['img']      = '<img src="'.Util::getFlagIcon($sLang).'" /> ';
						$aLangField['language'] = $sLang;
						if(
							!empty($aField['i18n']['jointable'])
						) {
							$aLangField['data']     = (string)$aField['i18n']['jointable'];
							$aLangField['alias']    = $sLang;
							$aLangField['format']    = 'Ext_Gui2_View_Format_Column_Language';
							$aLangField['format_params'] = array($sLang, $aField['column']);
						}
						$aFinalFields[] = $aLangField;
					}

				} else {
					$aFinalFields[] = $aField;
				}
			}

			$aFinalFields = $this->_addFiltersetColumns($aFinalFields);

			self::$_aColumnsCache[$sKey] = $aFinalFields;
		}

		return self::$_aColumnsCache[$sKey];
	}

	/**
	 * Wenn ein Feld einen Analyzer angegeben hat, aber keine sortable_column, muss der Originalwert als Multifield
	 * ergänzt werden. Ansonsten würde Elasticsearch basierend auf dem tokenisierten String sortieren, was nicht das
	 * gewünschte Resultat bringt.
	 *
	 * https://www.elastic.co/guide/en/elasticsearch/guide/master/multi-fields.html
	 *
	 * @param array $aFields
	 * @return array
	 */
	protected function _addFiltersetColumns($aFields) {

		// Denn $aField['index']['mapping']['index'] durch die default.yml standardmäßig auf true steht.
		$bIsIndex = $this->isIndexGui();

		if($bIsIndex === false) {
			return $aFields;
		}

		foreach($aFields as $iKey => $aField) {

			if(
				$aField['visible'] && // Nicht sichtbare Felder können auch nicht sortiert werden
				$aField['sortable'] &&
				!empty($aField['filterset']['type']) &&
				isset($aField['index']['mapping']['index']) &&
				(
					(
						is_string($aField['index']['mapping']['index']) &&
						$aField['index']['mapping']['index'] != "not_analyzed"
					) ||
					$aField['index']['mapping']['index'] === true
				) &&
				//$aField['index']['add_original'] !== true &&
				empty($aField['sortable_column']) // aber nur wenn nicht manuell eine andere Spalte angegeben wurde
			) {

				if(!in_array($aField['index']['mapping']['type'], ['string', 'text', 'keyword'])) {
					throw new LogicException('Field "'.$aField['column'].'" has wrong type for auto adding multifield for sorting.');
				}

				if(!empty($aField['index']['add_original'])) {
					// Das soll einfach richtig in der YML eingestellt werden
					throw new LogicException('Field "'.$aField['column'].'" has add_original but not sortable_column.');
				}

				$sType = $aField['index']['mapping']['type'];
				if($sType === 'string' || $sType === 'text') {
					$sType = 'keyword';
				}

				$aFields[$iKey]['sortable_column'] = $aField['column'].'.'.'original';
				$aFields[$iKey]['index']['mapping']['fields'] = [
					'original' => [
						'type' => $sType
					]
				];

				/*
				 * Zitat:
				 * The naive approach to indexing the same string in two ways would be to include two separate fields
				 * in the document: one that is analyzed for searching, and one that is not_analyzed for sorting.
				 */
//				$sNewColumn  = $aField['column'].'_filter';
//
//				$aFields[$iKey]['sortable_column']	= $sNewColumn;
//
//				$aField['index']['mapping']['index'] = 'not_analyzed';
//				$aField['column']			= $sNewColumn;
//				$aField['seperator']		= '</br>';
//				$aField['visible']			= false;
//				$aField['format']			= 'Ext_Gui2_View_Format_Function';
//				$aField['format_params']	= array('mb_strtolower');
//				$aField['filterset']		= null;
//
//				$aFields[] = $aField;

			}
		}

		return $aFields;
	}

	/**
	 * @return bool
	 */
	protected function isIndexGui() {

		$sIndex = $this->get(array('index', 'name'));
		if($sIndex != "default"){
			return true;
		}

		return false;
	}

	/**
	 * create an array with all keys of all current columns and bars
	 */
	protected function _createDefaultDummies(){

		$aBarsettings = array();
		$aColumnsettings = array();

		foreach($this->_aConfig['bars'] as $aBardata){
			foreach($aBardata as $sSetting => $mValue){
				$aBarsettings[$sSetting] = $mValue;
			}
		}

		foreach($this->_aConfig['columns'] as $aColumndata){
			foreach($aColumndata as $sSetting => $mValue){
				$aColumnsettings[$sSetting] = $mValue;
			}
		}

		// Default Bar und Column merken da wir diese beim Mergen komplett aus der neuen Config nehmen
		// daher brauchen wir diese Default werte um später prüfen zu können ob auch wirklich keine nicht
		// verfügbaren Konfigurationen benutzt wurden
		$this->_aDefaultBarConfig		= $aBarsettings;
		$this->_aDefaultColumnConfig	= $aColumnsettings;
	}

	 /**
	 * validate all Bars
	 * the method will check if a unknown setting are used
	 * @return boolean
	 * @throws ErrorException 
	 * @TODO einzelne configs der einzelnen Elmente validieren
	 */
	protected function _validateBars() {
		
		$aDefault = $this->_aDefaultBarConfig;

		if(
			!empty($aDefault) &&
			isset($this->_aConfig['bars'])
		){
			foreach($this->_aConfig['bars'] as $aBar){
				foreach($aBar as $sConfig => $mValue){
					if(!array_key_exists($sConfig, $aDefault)){
						throw new ErrorException('The Bar Configuration ['.$sConfig.'] is unknown!');
					}
				}
			}
		}
		
		return true;
	}
	
	/**
	 * validate all Columns
	 * the method will check if a unknown setting are used
	 * @return boolean
	 * @throws ErrorException 
	 */
	protected function _validateColumns(){
		$aDefault = $this->_aDefaultColumnConfig;

		if(!empty($aDefault)){

			foreach(['columns', 'default_columns'] as $sColumnsConfigKey){
				foreach($this->_aConfig[$sColumnsConfigKey] as $iKey => $aColumn){

					// write the default values from all Keys who wasnt set
					foreach($aDefault as $sConfig => $mValue){
						if(!isset($aColumn[$sConfig])){
							$aColumn[$sConfig] = $mValue;
						}
					}

					// check if the keys are valid
					foreach($aColumn as $sConfig => $mValue){
						if(!array_key_exists($sConfig, $aDefault)){
							throw new ErrorException('The Column Configuration ['.$sConfig.'] is unknown!');
						}
					}

					$this->_aConfig[$sColumnsConfigKey][$iKey] = $aColumn;
				}
			}

		}
		
		return true;
	}

	/**
	 * merge 2 Config arrays
	 * all settings in $aNewConfig will be used and all settings who wasnt defined in aNewConfig but in aOldConfig will be also used
	 * only Bars, Columns, css, js and priority would be completly used from aNewConfig
	 * @param array $aOldConfig
	 * @param array $aNewConfig
	 * @return array 
	 */
	protected function _merge(array $aOldConfig, array $aNewConfig){

		foreach($aNewConfig as $sNewConfig => $mNewValue) {

			// Wenn in Old der Config wert nicht enthalten ist muss ein Fehler geworfen werden
			// es wird IMMER die Default Config eingelesen welche alle möglichen Konifgurationen enthält
			// wenn man also etwas definiert was es im default nicht gibt kann dieser wert nicht verarbeitet werden
			// da dann auch die PHP umsetzung dafür fehlt
			if(
				!is_numeric($sNewConfig) &&
				!array_key_exists($sNewConfig, $aOldConfig) &&
				$aOldConfig !== null
			){
				throw new ErrorException('The Configuration ['.$sNewConfig.'] is unknown!');
			}
			
			if(
				is_array($mNewValue) &&
				$sNewConfig != 'sets' &&
				$sNewConfig != 'bars' &&
				$sNewConfig != 'columns' &&
				$sNewConfig != 'default_columns' &&
				$sNewConfig != 'css' &&
				$sNewConfig != 'js' &&
				$sNewConfig != 'priority' &&
				$sNewConfig != 'encode_data' &&
				$sNewConfig != 'flex_format' &&
				$sNewConfig != 'title' &&
				$sNewConfig != 'additional_flex' &&
				$sNewConfig !== 'options' &&
				$sNewConfig !== 'i18n_languages' &&
				$sNewConfig !== 'where' &&
				$sNewConfig !== 'orderby' &&
				$sNewConfig !== 'extend' &&
				$sNewConfig !== 'filter_values' &&
				$sNewConfig !== 'access'
			) {
				$mValue = $this->_merge($aOldConfig[$sNewConfig], $mNewValue);
			} else {
				$mValue = $mNewValue;
			}
			$aOldConfig[$sNewConfig] = $mValue;
		}

		return $aOldConfig;
	}

	/**
	 * Einstellungen eines Config-Arrays erweitern
	 * @param array $aConfig
	 * @param array $aExtend
	 * @return array
	 */
	protected function _extend(array $aConfig, array $aExtend) {

		foreach($aExtend as $sConfig => $aExtendConfig) {

			if (empty($aExtendConfig)) {
				continue;
			}

			if (
				// Nur bei bestimmten Eingeschaften erlauben
				$sConfig === 'bars' ||
				$sConfig === 'columns' ||
				$sConfig === 'js' ||
				$sConfig === 'css'
			) {
				if (isset($aConfig[$sConfig])) {
					if (!is_array($aConfig[$sConfig])) {
						throw new \LogicException(sprintf('Cannot extend config value [%s]', $sConfig));
					}
					$aConfig[$sConfig] = array_merge($aConfig[$sConfig], $aExtendConfig);
				} else {
					$aConfig[$sConfig] = $aExtendConfig;
				}
			}
		}

		return $aConfig;
	}

	/**
	 * Parse a Config file to Array
	 * @return array
	 * @throws ErrorException 
	 */
	protected function _parseFile() {

		$aFileData = explode('.', $this->_sConfigFile);
		$sFileType = end($aFileData);

		if(
			$sFileType !== 'json' &&
			$sFileType !== 'yml'
		){
			throw new ErrorException('Only JSON or YAML can be converted!');
		}

		$sContent = $this->_getFileContent();

		if(empty($sContent)) {
			throw new ErrorException('Empty config file!');
		}
		
		if($sFileType === 'json'){
			$aConfig = json_decode($sContent, true);
		} else {

			if(Util::checkPHP53()){
				//Symfony2 Yaml Parser, läuft nur mit PHP 5.3
				$sYamlClass = '\Symfony\Component\Yaml\Parser';
			}else{
				//Symfony1 Yaml Parser
				$sYamlClass = 'sfYamlParser';
			}

			$oYaml = new $sYamlClass();
			$aConfig = $oYaml->parse($sContent);

		}

		if(
			$sFileType === 'json' &&
			$aConfig === null
		) {
			throw new ErrorException('JSON Convert failed!');
		}

		return $aConfig;
	}
	
	/**
	 * get the Content of the Config File
	 * @return type
	 * @throws ErrorException 
	 */
	protected function _getFileContent(){
		
		$sFile = self::getDocumentRoot().$this->_sConfigFile;
		
		$sContent = '';

		if(is_file($sFile)){
			$sContent = file_get_contents($sFile);
		} else {
			throw new ErrorException('Config File "'.$sFile.'" not found!');
		}
		
		return $sContent;
	}

	/**
	 * call a Method, you can ygiv an array with 2 or more strings
	 * the first sting is the class
	 * the second is the method
	 * and all others are parameters pass to the method
	 * if the factory pass the $mOptionalParameter it will be the last Parameter
	 * (ausgelegt auf die verschachtelung wie sie in der config passiert)
	 * @param array $aMethodData
	 * @param mixed $mOptionalParameter
	 * @return mixed
	 * @throws ErrorException 
	 */
	public static function callMethod($aMethodData, $mOptionalParameter = null){
		
		if(!is_array($aMethodData)){
			throw new ErrorException('You need a array with 2 Params for a Method Call!');
		} else if(count($aMethodData) < 2){
			throw new ErrorException('You can only set 2 or more Params for a Method Call!');
		}
  
		$sClass		= $aMethodData[0];
		$sMethod	= $aMethodData[1];
		unset($aMethodData[0]);
		unset($aMethodData[1]);
		$aParams	= (array)$aMethodData;
		
		if(
			!is_array($mOptionalParameter) && 
			$mOptionalParameter !== null
		){
			$mOptionalParameter = array($mOptionalParameter);
		}
		
		foreach((array)$mOptionalParameter as $mParameter){
			$aParams[]	= $mParameter;
		}
		
		try {
			$mValue = Factory::executeStatic($sClass, $sMethod, $aParams);
		} catch (Exception $exc) {
			__pout($aMethodData);
			__pout($exc->getMessage());
			__pout($exc->getTraceAsString());
			throw new RuntimeException('Cannot call method "'.$sClass.'::'.$sMethod.'" ('.$exc->getMessage().')');
		}

		return $mValue;
	}
	
	/**
	 * call a object with additional params
	 * (ausgelegt auf die verschachtelung wie sie in der config passiert)
	 * @param string $sClass
	 * @param array $aParams
	 * @return object
	 */
	public function callObject($sClass, $aParams = array()){
		$oObject = Factory::getObject($sClass, $aParams);
		return $oObject;
	}

	public function callClass($sClass){
		$sClassName = Factory::getClassName($sClass);
		return $sClassName;
	}

	public static function clearWDCache() {
		WDCache::deleteGroup('Ext_Gui2_Config_Parser::load');
	}

	public static function getDocumentRoot(){
		$sRoot = Util::getDocumentRoot();
		return $sRoot;
	}

}
