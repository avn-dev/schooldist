<?php

/**
 * @TODO Smarty und Routing auslagern
 */
abstract class Ext_TC_Frontend_Combination_Abstract {

	/**
	 * @var SmartyWrapper 
	 */
	protected $_oSmarty;
	
	/**
	 * Smarty Caching aktivieren
	 * 
	 * @var bool
	 */
	protected $_bSmartyCaching = false;
	
	/**
	 * @var Ext_TC_Frontend_Combination 
	 */
	protected $_oCombination;
	/**
	 *
	 * @var Ext_TC_Frontend_Combination_Helper_Caching 
	 */
	protected $oCachingHelper;
	
	/**
	 * @var Ext_TC_Frontend_Template 
	 */
	protected $_oTemplate;

	/**
	 * @var bool
	 */
	protected $_bDebug = false;

	/**
	 * MVC_Request-Objekt benutzen!
	 *
	 * @TODO Entfernen
	 * @deprecated
	 * @var array
	 */
	protected $_aVars = array();

	/**
	 * @var array
	 */
	protected $_aUserData = array();

	/**
	 * @var array
	 */
	protected $aPlausibilityDebug = array();
	
	/**
	 * String mit einer Fehlermeldung, falls ein Fehler aufgetreten ist
	 *
	 * @var string
	 */
	protected $_sError = '';
	
	/**
	 * @var MVC_Request
	 */
	protected $_oRequest;
	
	/**
	 * Code des geparsten Templates
	 *
	 * @var string
	 */
	protected $_sContent;

	/**
	 * @var \Core\Handler\SessionHandler
	 */
	protected $oSession;

	/**
	 * @var Ext_TC_Frontend_Log
	 */
	protected $oLog;

	/**
	 * @param Ext_TC_Frontend_Combination $oCombination
	 * @param SmartyWrapper $oSmarty
	 */
	public function __construct(Ext_TC_Frontend_Combination $oCombination, SmartyWrapper $oSmarty = null) {

		if($oSmarty){
			$this->setSmartyObject($oSmarty);
			$this->_assignByRef('oCombination', $this);
		}
		$this->setCombination($oCombination);
		
		$this->oCachingHelper = new Ext_TC_Frontend_Combination_Helper_Caching($oCombination);

		// Session wird nicht immer benötigt, aber getInstance() startet immer pauschal eine Session
		//$this->oSession = \Core\Handler\SessionHandler::getInstance();

	}

	/**
	 * Kombination initialisieren: Interface initialisieren
	 *
	 * @param Illuminate\Http\Request $oRequest
	 * @param string $sLanguage Sprache überschreiben
	 * @throws Exception
	 */
	public function initCombination(\Illuminate\Http\Request $oRequest, string $sLanguage = null) {

		// Sollte von Widgets nicht mehr verwendet werden (DI über Controller), wird aber intern benötigt
		$this->setRequest($oRequest);

		if (empty($sLanguage)) {
			$sLanguage = $this->getCombination()->getLanguage();
		}

		// Im PP (Konsole) passiert alles im Backend-Kontext
		if (\System::getInterface() === 'frontend') {
			\System::setInterfaceLanguage($sLanguage);
		}

		\Factory::executeStatic('System', 'setLocale');

	}

	/**
	 * Set the Debugmode Variable
	 *
	 * @param bool $bDebug
	 */
	public function setDebug($bDebug) {
		$this->_bDebug = (boolean)$bDebug;
	}

	/**
	 * Set the current Combination
	 *
	 * @param Ext_TC_Frontend_Combination $oCombination
	 */
	public function setCombination(Ext_TC_Frontend_Combination &$oCombination) {
		$this->_oCombination = &$oCombination;
	}

	/**
	 * @return Ext_TC_Frontend_Combination
	 */
	public function getCombination() {
		return $this->_oCombination;
	}

	/**
	 * Set Smarty Object for the Template
	 *
	 * @param SmartyWrapper $oSmarty
	 */
	public function setSmartyObject(SmartyWrapper &$oSmarty) {
		$this->_oSmarty = &$oSmarty;		
	}

	/**
	 * Setzt den Smarty Cache zurück 
	 */
	public function cleanSmartyCache() {
		
		$sCacheId = $this->getSmartyCacheId();

		$this->_oSmarty->clearCache(null, $sCacheId);

	}

	/**
	 * Aktiviert falls gesetzt das Smarty Caching
	 * 
	 * @throws Exception 
	 */
	public function setSmartyCaching() {
		
	}

	/**
	 * Set Template Object
	 *
	 * @param Ext_TC_Frontend_Template $oTemplate
	 */
	public function setTemplate(Ext_TC_Frontend_Template &$oTemplate) {
		$this->_oTemplate = &$oTemplate;
	}

	/**
	 * Set all $_VARS
	 *
	 * @param array $aVars
	 */
	public function setVars($aVars) {
		$this->_aVars = (array)$aVars;
	}

	/**
	 * Setzt das Request Objekt
	 *
	 * @param MVC_Request
	 */
	public function setRequest($oRequest) {
		$this->_oRequest = $oRequest;
	}

	/**
	 * @return MVC_Request
	 */
	public function getRequest() {
		return $this->_oRequest;
	}

	/**
	 * Set Userdata
	 *
	 * @param array $aUserData
	 */
	public function setUserData($aUserData) {
		$this->_aUserData = (array)$aUserData;
	}

	/**
	 * Call protected Methods named by _TASK
	 */
	public function handleRequest() {

		if(!empty($this->_aVars['task'])) {
			$sTask = \Util::getCleanFilename($this->_aVars['task'], '_', false, false);
		} else {
			$sTask = 'default';
		}

		$sFunction = '_'.$sTask;

		$this->logUsage($sFunction);

		if (method_exists($this, 'taskMaster')) {
			$this->taskMaster($sTask);
		} else if(method_exists($this, $sFunction)) {
			$this->$sFunction();
		} else {
			throw new UnexpectedValueException('Method "'.$sFunction.'" not found!');
		}

	}

	/**
	 * Abgeleitet wegen der Schul-ID die überschrieben sein könnte
	 * 
	 * @todo Ist nicht vollständig und optimal gelöst!
	 *
	 * @return string
	 */
	protected function getSmartyCacheId() {

		$sLanguage = '';
		$aLanguages = $this->_oCombination->getLanguages();
		if(!empty($aLanguages)) {
			$sLanguage = reset($aLanguages);
		}

		$sCacheId =
			$this->_oTemplate->key."_".
			$this->_oCombination->key.'_'.
			$sLanguage.'_'.
			$this->_oCombination->last_cache_refresh.'_'.
			$this->_oTemplate->changed.'_'.
			$this->_oCombination->getItemsAsString().'_'.
			$this->_oCombination->getMode();

		$sCacheId = md5($sCacheId);
		
		return $sCacheId;
	}
	
	/**
	 * @todo Ist nicht vollständig und optimal gelöst!
	 *
	 * @return string
	 */
	protected function getCombinationCacheId($sLanguage=null) {

		if($sLanguage === null) {
			$aLanguages = $this->_oCombination->getLanguages();
			if(!empty($aLanguages)) {
				$sLanguage = reset($aLanguages);
			}
		}

		$sCacheId =
			get_class($this->_oCombination)."_".
			$this->_oCombination->id.'_'.
			$sLanguage.'_'.
			$this->_oCombination->getItemsAsString().'_'.
			$this->_oCombination->getMode();

		$sCacheId = md5($sCacheId);

		return $sCacheId;
	}

	/**
	 * Parsed das Smarty-Template
	 * 
	 * @return boolean
	 * @throws Exception 
	 */
	public function parseTemplate() {
		
		// Prüfen, ob Template vorhanden ist
		if(!$this->_oTemplate instanceof Ext_TC_Frontend_Template) {
			throw new RuntimeException('Template object is missing!');
		}

		try {

            $oCombinationCacheHandler = new TcCache\Handler\CombinationCache();
			$oCombinationCacheHandler->setCombination($this->_oCombination);

            $bCacheFound = true;
			
			$sSmartyCacheId = $this->getSmartyCacheId();
			
            // Caching aktiviert
            if(
                $this->_bSmartyCaching === true &&
                System::d('debugmode') !== 2
            ) {
                // Cache Datei suchen
                $sResult = $oCombinationCacheHandler->getCache($sSmartyCacheId);
            }

            if(empty($sResult)) {
                // Template parsen
                $bCacheFound = false;
				$sResult = $this->_oSmarty->fetch($this->_oTemplate->getTemplateForSmarty());
				$oHtmlMinifyService = new \TcFrontend\Service\HtmlMinifyService();
				$sResult = $oHtmlMinifyService->minify($sResult);
            }

            if(
				$this->_bSmartyCaching === true &&
                $bCacheFound === false &&
                System::d('debugmode') !== 2
            ) {
                $oCombinationCacheHandler->writeCache($sSmartyCacheId, $sResult);
            }

            $this->_sContent = $sResult;
			
            return $sResult;

        } catch(\Throwable $e) {
			$this->_sError = $e->getMessage();
			if($this->_bDebug) {
				$this->_sError .= ', File: '.$e->getFile();
				$this->_sError .= ', Line: '.$e->getLine();
			}
			return false;
		}
		
	}
	
	/**
	 * Hook Methode zum Überschreiben; wird nach dem Parsen aufgerufen 
	 */
	public function executePostParsingHook() {
		
	}
	
	/**
	 * Gibt den geparsten Inhalt zurück
	 * 
	 * @return string
	 */
	public function getContent() {

		if($this->_oRequest->get('api_version') >= 2) {
			return [
				'content' => $this->_sContent,
				'usage' => $this->_oCombination->usage
			];
		} else {
			return $this->_sContent;
		}
		
	}
	
	/**
	 * Gibt die Fehlermeldung zurück, falls vorhanden
	 * @return string
	 */
	public function getError() {
		return $this->_sError;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function handleAjaxRequest() {

		if(!empty($this->_aVars['task'])) {
			$sTask = $this->_aVars['task'];
		} else {
			$sTask = 'default';
		}

		$sFunction = '_'.$sTask.'Ajax';

		$this->logUsage($sFunction);

		if(method_exists($this, $sFunction)){
			$aData = $this->$sFunction();
		} else {
			throw new UnexpectedValueException('Method "'.$sFunction.'" not found!');
		}

		// Transferdata
		$aTransfer = array();
		// This Method of Object "Thebing" will be call after the Request finished
		// Default Methodename is "Task"Callback()
		$aTransfer['method'] = $sTask.'Callback';
		// This Data will be the Parameter 1 für the "Method"
		$aTransfer['data'] = $aData;
		// Wenn dem Request eine eindeutige ID zugewiesen wurde
		if(!empty($this->_aVars['request'])) {
			$aTransfer['request'] = (int) $this->_aVars['request'];
		}

		return $aTransfer;
	}
	
	/**
	 * get all Fiels that are available for ?get_file=KEY
	 *
	 * @return string
	 */
	public function getTemplateFiles() {
		$aFiles = array();
		$aFiles['AZBWXNRV'] = '/storage/public/frontend/tc/js/thebing.js';
		$aFiles['GEFTLETU'] = '/storage/public/frontend/tc/js/form_combination.js';
		return $aFiles;
	}

	/**
	 * Default Method wenn kein task zutrifft, oder kein task definiert ist
	 */
	protected function _default() {
		
	}

	/**
	 * Default Ajax Method and starts the defaultCallback() JS Method
	 *
	 * @return array();
	 */
	protected function _defaultAjax() {
		return array();
	}

	/**
	 * Variablen an Smarty vergeben
	 * 
	 * @param string $sVar
	 * @param mixed $mArguments
	 */
	protected function _assign($sVar, $mArguments) {
		if(is_string($sVar)) {
			$this->_oSmarty->assign($sVar, $mArguments);
		}	
	}

	/**
	 * Variablen an Smarty als Referenz vergeben
	 * 
	 * @param string $sVar
	 * @param mixed $mArguments
	 */
	protected function _assignByRef($sVar, $mArguments) {
		if(is_string($sVar)) {
			$this->_oSmarty->assign($sVar, $mArguments);
		}	
	}

	/**
	 * Setzen von standard Variablen
	 */
	public function initDefaultVars() {
		
	}

	/**
	 * Wird beim täglichen Cronjobdurchlauf aufgerufen, sowie
	 * bei dem Kombinations-Aktualisierungs Button
	 * Ist nur ein Wrapper, damit der Context global auf Frontend umgestellt werden kann
	 */
	final public function initializeData() {
		
		// Context auf Frontend umstellen, damit Übersetzungen korrekt geholt werden
		$sInterface = System::wd()->getInterface();
		if($sInterface != 'frontend') {
			//webdynamics::getInstance('frontend');
			\System::setInterface('frontend');
			System::wd()->getIncludes();
		}

		$sSearchString = Util::generateRandomString(7);

		// @TODO: Auf log()-Methode umstellen
		$oLog = Log::getLogger('combination');
		$oLog->addInfo($sInterface.' - Start combination initializing - "'.$this->_oCombination->getName().'" (ID: '.$this->_oCombination->id.', Usage: '.$this->_oCombination->usage.'), Request-URI: '.$_SERVER['REQUEST_URI'].', ARGV: '.implode(" ", (array)$_SERVER['argv']).' - '.$sSearchString);
		
		$iStarttime = microtime(true);
		try {
			$this->executeInitializeData();
		} catch(Exception $e) {
			// Funktioniert im TypeHandler nicht
			$this->_oCombination->updateState(Ext_TC_Frontend_Combination::STATUS_FAILED);
			throw $e;
		}
		$iEndtime = microtime(true);

		// @todo Fehler / Exception abfangen und fail übermitteln
		$this->_oCombination->updateState(Ext_TC_Frontend_Combination::STATUS_READY);
		
		$oLog->addInfo($sInterface.' - End of combination initializing - "'.$this->_oCombination->getName().'" (ID: '.$this->_oCombination->id.', Usage: '.$this->_oCombination->usage.') Total: '.round($iEndtime - $iStarttime).'s, Queries: '.DB::getDefaultConnection()->getQueryCount().', Request-URI: '.$_SERVER['REQUEST_URI'].', ARGV: '.implode(" ", (array)$_SERVER['argv']).' - '.$sSearchString);
		
		// Context auf Backend zurückstellen
		if($sInterface != 'frontend') {
			webdynamics::getInstance($sInterface);
		}
		
	}

    /**
     * Wird beim täglichen Cronjobdurchlauf aufgerufen, sowie
     * bei dem Kombinations-Aktualisierungs Button
     */
	protected function executeInitializeData() {

	}
	
	/**
	 * Item für die Kombination holen
	 * @param string $sItem / parameter ohne items_ aufrufen
	 * @return mixed
	 */
	protected function _getCombinationItem($sItem) {

		$oCombination = $this->_oCombination;
		$sItemName = 'items_'.$sItem;

		return $oCombination->$sItemName;
	}

	/**
	 * @param string $sTranslation
	 * @return string
	 */
	public function t($sTranslation) {

		$sTranslation = Ext_TC_L10N::t($sTranslation, $this->_oCombination->getLanguage());

		return $sTranslation;
	}

	/**
	 * @deprecated
	 * @param string $sKey
	 * @return bool
	 */
	protected function _getParam($sKey) {

		if(
			is_string($sKey) &&
			isset($this->_aVars[$sKey])
		) {
			return $this->_aVars[$sKey];
		} else {
			return false;
		}

	}

	/**
	 * Check the plausibility of setted params by given selection
	 * 
	 * This method exists to avoid a lot of redundances in child classes
	 * 
	 * @param string $sKey
	 * @param object $oSelection
	 * @param boolean $bCheck
	 */
	protected function _checkPlausibilitySelection($sKey, $oSelection, &$bCheck) {

		if($bCheck === false) {
			return;
		}

		$aAllowedValues	= (array)$oSelection->getOptions(null, null, $this->_oCombination);
		$aAllowedValues	= array_keys($aAllowedValues);

		$aCurrentValues = (array)$this->_oCombination->$sKey;

		$aValidValues = [];
		$aRemovedValues = [];
		foreach($aCurrentValues as $iValue) {
			if(in_array($iValue, $aAllowedValues)) {
				$aValidValues[] = $iValue;
			} else {
				$aRemovedValues[] = $iValue;
			}
		}

		if(!empty($aRemovedValues)) {
			$this->aPlausibilityDebug[] = 'Removed invalid values for key "'.$sKey.'" ('
			                            . 'original values: "'.implode(', ', $aCurrentValues).'"; '
			                            . 'removed values: "'.implode(', ', $aRemovedValues).'"; '
			                            . 'remaining values: "'.implode(', ', $aValidValues).'"; '
			                            . 'possible values: "'.implode(', ', $aAllowedValues).'"; '
			                            . 'combination: "'.$this->_oCombination->key.'")';
			$this->_oCombination->$sKey = $aValidValues;
		}

		if(empty($aValidValues)) {
			$this->aPlausibilityDebug[] = 'No valid values remaining for key "'.$sKey.'" ('
			                            . 'original values: "'.implode(', ', $aCurrentValues).'"; '
			                            . 'possible values: "'.implode(', ', $aAllowedValues).'"; '
			                            . 'combination: "'.$this->_oCombination->key.'")';
			$bCheck = false;
		}

	}

	/**
	 * @return array
	 */
	public function getPlausibilityDebug() {
		return $this->aPlausibilityDebug;
	}

	/**
	 * Meldung loggen
	 *
	 * @param string $sMessage
	 * @param array $mOptional
	 * @param bool|true $bError
	 */
	public function log($sMessage, $mOptional=array(), $bError=true) {

		$oLogger = Ext_TC_Log::getLogger('frontend', 'combination');
		$sClass = get_class($this);

		$sMessage = $sClass.': '.$sMessage;

		if(!is_array($mOptional)) {
			$mOptional = array($mOptional);
		}

		$mOptional['combination'] = $this->getCombination()->getData();
		$mOptional['request_all'] = $this->getRequest()->all();
		$mOptional['frontend_log'] = $this->getFrontendLog()?->getData();
		$mOptional['user_agent'] = $this->getRequest()->userAgent();

		if($bError) {
			$oLogger->error($sMessage, $mOptional);
		} else {
			$oLogger->info($sMessage, $mOptional);
		}

	}

	public function setHeaderInformations() {}

	/**
	 * URL der aufrufenden Seite (nur Fidelo Snippet)
	 *
	 * @param string|null $sAdditionalQuery
	 * @return string
	 */
	public function getRequestingUrl($sAdditionalQuery = null) {

		if(!$this->_oRequest->exists('X-Originating-URI')) {
			// Wurde erst später hinzugefügt
			throw new RuntimeException('X-Originating-URI missing!');
		}

		$sUrl = '';
		
		$sHttps = $this->_oRequest->get('X-Originating-HTTPS');

		if(
			!empty($sHttps) && 
			$sHttps !== 'off'
		) {
			$sUrl .= 'https://';
		} else {
			$sUrl .= 'http://';
		}
		
		$sUrl .= $this->_oRequest->get('X-Originating-Host');
		$sUrl .= $this->_oRequest->get('X-Originating-URI');
		
		if($sAdditionalQuery !== null) {
			
			$aUrl = parse_url($sUrl);
			if(!empty($aUrl['query'])) {
				$sUrl = str_replace($aUrl['query'], $aUrl['query'].'&'.$sAdditionalQuery, $sUrl);
			} else {
				$sUrl .= '?'.$sAdditionalQuery;
			}

		}
		
		return $sUrl;
	}

	/**
	 * @param string $sTask
	 * @param bool $bSnippet
	 */
	public function logUsage($sTask, $bSnippet = true) {

		$oLog = new Ext_TC_Frontend_Log();
		$oLog->combination_id = $this->_oCombination->id;
		$oLog->template_id = $this->_oTemplate->id;
		$oLog->method = $sTask;

		if($this->oSession) {
			$oLog->session_id = $this->oSession->getId();
		}

		// Snippet hält sich nicht an Proxy-Standard
		if($bSnippet) {
			$oLog->url = $this->getRequestingUrl();
			$oLog->user_agent = $this->_oRequest->get('X-Originating-Agent');
			$oLog->ip = $this->_oRequest->get('X-Originating-IP');
		} else {
			// Wenn hier nur eine Domain zurückkommt, liegt das an Privacy-Geschichten des Browsers
			$sUrl = $this->_oRequest->headers->get('referer');

			// Widget als IFrame: Hier ist der Referrer dann der Proxy, aber die App gibt den echten Referrer als Parameter mit
			if (
				$this->_oRequest->input('iframe') &&
				$this->_oRequest->has('referrer')
			) {
				$sUrl = $this->_oRequest->input('referrer');
			}

			if (empty($sUrl) && $this->_oRequest->header('X-Fidelo-Widget-Path')) {
				// Sollte eigentlich nicht vorkommen, aber wenn Script direkt (und ohne Referrer) aufgerufen wurde
				$sUrl = $this->_oRequest->getSchemeAndHttpHost().'/'.$this->_oRequest->header('X-Fidelo-Widget-Path');
			}

			$oLog->url = $sUrl;
			$oLog->user_agent = $this->_oRequest->userAgent();
			$oLog->ip = $this->_oRequest->ip(); // Hier muss auch der Trusted Proxy gesetzt werden, sonst funktioniert das nicht
		}

		if(!System::d('frontend_store_ips')) {
			$oLog->ip = null;
		}

		$oLog->save();

		$this->oLog = $oLog;

	}

	/**
	 * @return Ext_TC_Frontend_Log
	 */
	public function getFrontendLog(): ?Ext_TC_Frontend_Log {
		return $this->oLog;
	}

}
