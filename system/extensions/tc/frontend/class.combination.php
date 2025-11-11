<?php

use Illuminate\Support\Str;
use Smarty\Smarty;

class Ext_TC_Frontend_Combination extends Ext_TC_Frontend_CombinationTemplate {
	
	const STATUS_READY = 'ready';
	
	const STATUS_PENDING = 'pending';
	
	const STATUS_FAILED = 'fail';
	
	protected $_sTable = 'tc_frontend_combinations';
	protected $_sTableAlias = 'tc_fc';

	// TODO Die Items sollte man eigentlich auf die Attribute migrieren können. Keine Ahnung welchen Sinn sort_column hat.
	protected $_aJoinTables = array(
		'items' => array(
			'table'=>'tc_frontend_combinations_items',
			'foreign_key_field' => array('item', 'item_value'),
			'primary_key_field' => 'combination_id',
			'sort_column' => 'position'
		)
	);

	protected $_aJoinedObjects = array();
	
	protected $_aFormat = array(
		'name' => array(
			'required'	=> true,
		),
//		'key' => array(
//			'required' => true,
//			'not_changeable' => true
//		)
	);

	public $bUpdateIndexEntry = false;

	/**
	 * @var array 
	 */
	protected $_aScalarItems = array();

	/**
	 * Fehlermeldung, falls Fehler aufgetreten ist
	 * @var string
	 */
	protected $_sError;

	/**
	 * @var array
	 */
	protected $aPlausibilityDebug = array();
	/**
	 * @var string 
	 */
	protected $sMode = 'live';
	
	public $bParameterOverwritten = false;

	/**
	 * Gibt an ob die Kombination gerade aktualisiert wird
	 * 
	 * @return bool
	 */
	public function isPending() {
		return ($this->status === self::STATUS_PENDING);
	}
	
	/**
	 * Gibt an ob die Kombination fertig aktualisiert wurde
	 * 
	 * @return bool
	 */
	public function isReady() {
		return ($this->status === self::STATUS_READY);
	}
	
	/**
	 * Gibt an ob das Aktualisieren der Kombination fehlgeschlagen ist
	 * 
	 * @return bool
	 */
	public function isFailed() {
		return ($this->status === self::STATUS_FAILED);
	}

	/**
	 * Setzt den Modus mit dem die Kombination aufgerufen werden soll (live/testing)
	 * 
	 * @param string $sMode
	 */
	public function setMode($sMode) {
		
		if(!in_array($sMode, ['live', 'testing'])) {
			throw new InvalidArgumentException('Invalid frontend mode "'.$sMode.'"!');
		}
		
		$this->sMode = $sMode;
	}
	
	/**
	 * Liefert den Modus mit dem diese Kombination geladen wurde (live/testing)
	 * 
	 * @param string $sModus
	 */
	public function getMode() {
		return $this->sMode;
	}

	/**
	 * @param $sName
	 * @param $mValue
	 */
	public function __set($sName, $mValue) {
		
		if($sName === 'items_office') {
			global $_VARS;
			
			$oLog = \Log::getLogger('frontend-combinations-office');
			
			$iOldOffice = $this->items_office;
			
			$oUser = \Ext_TC_System::getCurrentUser();
			
			if($mValue == 0 || !is_numeric($mValue)) {
				$aLog = array('combination_id' => $this->id, 'key' => $this->key, 'usage' => $this->usage, 'original office' => $iOldOffice, 'new office' => $mValue, 'vars' => $_VARS, 'backtrace' => Util::getBacktrace(), 'user_id' => $oUser->id);
				$oLog->addError('Office id is empty!', $aLog);
				Ext_TC_Util::sendErrorMessage([$aLog, $_SERVER, $_REQUEST], 'Office id is empty!');
			} else {
				$oLog->addInfo('Change office id', array('combination_id' => $this->id, 'key' => $this->key, 'usage' => $this->usage, 'original office' => $iOldOffice, 'new office' => $mValue, 'vars' => $_VARS, 'backtrace' => Util::getBacktrace(), 'user_id' => $oUser->id));
			}
		}
		
		if($sName === 'items_schools') {
			global $_VARS;
			
			$oLog = \Log::getLogger('frontend-combinations-schools');
			
			$aOldSchools = (array)$this->items_schools;
			
			$oUser = \Ext_TC_System::getCurrentUser();
			
			if(!is_array($mValue)) {
				$mValue = (array)$mValue;
			}
			
			if(count($aOldSchools) > count($mValue)) {
				$aDiff = array_diff($aOldSchools, $mValue);
				$oLog->addError('Removed schools from combination', array('combination_id' => $this->id, 'key' => $this->key, 'removed schools' => array_values($aDiff), 'vars' => $_VARS, 'backtrace' => Util::getBacktrace(), 'user_id' => $oUser->id));
				
			} else if(count($aOldSchools) < count($mValue)) {
				$aDiff = array_diff($mValue, $aOldSchools);
				$oLog->addInfo('Added schools to combination', array('combination_id' => $this->id, 'key' => $this->key, 'added schools' => array_values($aDiff), 'vars' => $_VARS, 'backtrace' => Util::getBacktrace(), 'user_id' => $oUser->id));
			} else {
				$oLog->addInfo('No schools changed', array('combination_id' => $this->id, 'key' => $this->key, 'old schools' => count($aOldSchools), 'new schools' => count($mValue), 'vars' => $_VARS, 'backtrace' => Util::getBacktrace(), 'user_id' => $oUser->id));
			}	
			
		}

		if(mb_strpos($sName, 'items_') === 0) {

			$aName = explode("_", $sName, 2);
			$sItem = $aName[1];

			$aItems = (array)$this->items;
			foreach($aItems as $iKey=>$aItem) {
				if($aItem['item'] == $sItem) {
					unset($aItems[$iKey]);
				}
			}
			$mValue = (array)$mValue;
			foreach($mValue as $iValue) {
				$aItems[] = array(
					'item'=>$sItem,
					'item_value'=>$iValue
				);
			}
			
			$this->items = $aItems;

		} else {
			parent::__set($sName, $mValue);
		}

	}

	/**
	 * @param $sName
	 * @return array|mixed|string
	 */
	public function __get($sName) {
		
		if(mb_strpos($sName, 'items_') === 0) {

			$aExplode = explode("_", $sName, 2);
			$sItem = $aExplode[1];
			$aItems = (array)$this->items;
			
			foreach($aItems as $iKey=>$aItem) {
				if($aItem['item'] == $sItem) {
					if(!isset($mValue)) {
						$mValue = array();
					}
					$mValue[] = $aItem['item_value'];
				}
			}

			// Skalare Werte
			if(
				is_array($mValue ?? null) &&
				$this->isScalarParam($sItem)
			) {
				$mValue = reset($mValue);
			}
			
		} else {
			$mValue = parent::__get($sName);
		}

		return $mValue ?? null;
	}

    public function getSetting(string $key) {
        return $this->{'items_'.$key};
    }

	/**
	 * Check the scalability of a key
	 * 
	 * @param string $sKey
	 * @return boolean
	 */
	public function isScalarParam($sKey) {
		$bCheck = in_array($sKey, $this->_aScalarItems);
		return $bCheck;
	}
	
	/**
	 * @param $sKey
	 * @return bool|static
	 */
	public static function getByKey($sKey) {

		$oRepository = Ext_TC_Factory::executeStatic('Ext_TC_Frontend_Combination', 'getRepository');
		$oCombination = $oRepository->findOneBy(array('key' => $sKey));

		if($oCombination instanceof Ext_TC_Frontend_Combination) { 
			return $oCombination;
		} 
		
		return false;	
	}

	/**
	 * @param string $sKey
	 * @return Ext_TC_Frontend_Combination_Abstract|null
	 */
	public static function getUsageObjectByKey($sKey) {

		$oCombination = static::getByKey($sKey);
		if($oCombination instanceof static) {
			return $oCombination->getObjectForUsage();
		}

		return null;

	}

	/**
	 * Bei jeder Veränderung Cache löschen!
	 *
	 * @param bool $bLog
	 * @return Ext_TC_Basic
	 */
	public function save($bLog = true) {
		
		if($bLog) {
			$sCacheKey = 'tc_api_data_cache_'.$this->id;
			WDCache::delete($sCacheKey);
		}

		if($this->bParameterOverwritten === false) {
			// nur speichern wenn die items nicht über das Snippet überschrieben wurden
			parent::save($bLog);
		} else {
			Ext_TC_Util::sendErrorMessage([$this->_aData, $this->items, \Util::getBacktrace()], 'Trying to save frontend combination with overwritten items');
		}

		return $this;
	}

	/**
	 * @param $sFileKey
	 * @param bool $bDebug
	 * @return array|bool
	 */
	public function getFileData($sFileKey, $bDebug = false) {

		$aFileData = array();

		$aFiles = $this->_getUsageFiles();

//		if($bDebug) {
//			__pout('Filekey: '.$sFileKey);
//			__pout($aFiles);
//		}
		
		if(isset($aFiles[$sFileKey])){

			$sFile = $aFiles[$sFileKey];
			$sFileForCheck = \Util::getDocumentRoot().$sFile;

			if(is_file($sFileForCheck)) {

				$aTemp = (array)explode('.', $sFile);

				$sFileType = end($aTemp);
				$sFileType = mb_strtolower($sFileType);

				$sContentType = '';

				switch($sFileType) {
					case 'js':
						$sContentType = 'text/javascript';
						break;
					case 'css':
						$sContentType = 'text/css';
						break;
					case 'jpg':
						$sContentType = 'image/jpeg';
						break;
					case 'gif':
						$sContentType = 'image/gif';
						break;
					case 'png':
						$sContentType = 'image/png';
						break;
					case 'pdf':
						$sContentType = 'application/pdf';
						break;

				}

				if(!empty($sContentType)) {
					$aFileData['content_type'] = $sContentType;
					$aFileData['file'] = $sFile;
					$aFileData['is_attachment'] = false;

					if($sFileType === 'pdf') {
						$aFileData['is_attachment'] = true;
						$aFileData['file_name'] = Util::getCleanFilename(basename($sFile));
					}

					return $aFileData;
				} else if($bDebug){
					__pout('No Content Type found');
				}

			} else if($bDebug) {
				__pout('File not found: '.$sFileForCheck);
			}

		}

		return false;
	}

	/**
	 * Generates content
	 *
	 * @param SmartyWrapper $oSmarty
	 * @param Ext_TC_Frontend_Template $oTemplate
	 * @param MVC_Request $oRequest
	 * @param bool $bDebug
	 * @return bool|string
	 * @throws Exception
	 */
	public function generateContent(SmartyWrapper &$oSmarty, Ext_TC_Frontend_Template &$oTemplate, MVC_Request $oRequest, $bDebug = false) {
		global $_VARS, $user_data;

		// Zusätzliches Vorlagen-Verzeichnis
		$oSmarty->addTemplateDir(Util::getDocumentRoot().'storage/');

		$oObject = $this->_getObjectForUsage($oSmarty);

		// Objekte und Parameter setzen
		$oObject->initCombination($oRequest);
		$oObject->setRequest($oRequest);
		$oObject->initDefaultVars();
		$oObject->setVars($_VARS);
		$oObject->setUserData($user_data);
		$oObject->setDebug($bDebug);
		$oObject->setTemplate($oTemplate);

		// Caching aktivieren, falls erfordert
		$oObject->setSmartyCaching();

		try {
			// Anfrage bearbeiten und Infos dem Smarty-Template übergeben
			$oObject->handleRequest();

			// Smarty-Template parsen
			$oObject->parseTemplate();

			// Methode, um Aktionen nach dem Parsen des Templates ausführen zu können
			$oObject->executePostParsingHook();

			$this->_sError = $oObject->getError();

		} catch (\Throwable $e) {

			if(\System::d('debugmode') == 2) {
				throw $e;
			}

			$this->_sError = $e->getMessage();
		}
		
		// Wenn kein Fehler aufgetreten ist, Inhalt zurückgeben.
		if(empty($this->_sError)) {
			// Inhalt holen
			$sContent = $oObject->getContent();
			
			if(
				$oRequest->get('api_version') >= 2 &&
				is_array($sContent)
			) {
				$sContent = json_encode($sContent);
			}
			
			return $sContent;
		} elseif($bDebug === true) {
			return $this->_sError;
		}

		return false;
	}

	/**
	 * Methode für die Verarbeitung von Ajax-Anfragen
	 *
	 * @global array $_VARS
	 * @global array $user_data
	 * @param MVC_Request $oRequest
	 * @param Ext_TC_Frontend_Template $oTemplate
	 * @param boolean $bDebug
	 * @return array
	 */
	public function executeRequest(MVC_Request $oRequest, Ext_TC_Frontend_Template $oTemplate, $bDebug = false) {
		global $_VARS, $user_data;

		$oObject = $this->_getObjectForUsage();

		$oObject->initCombination($oRequest);
		$oObject->setRequest($oRequest);
		$oObject->setVars($_VARS);
		$oObject->setUserData($user_data);
		$oObject->setCombination($this);
		$oObject->setTemplate($oTemplate);
		$oObject->setDebug($bDebug);

		$aTransfer = $oObject->handleAjaxRequest();

		return $aTransfer;
	}

	/**
	 * get File Paths
	 */
	protected function _getUsageFiles() {
		$oObject = $this->_getObjectForUsage();
		$aFiles = $oObject->getTemplateFiles();
		return $aFiles;
	}

	/**
	 * Get object for Usage
	 *
	 * @deprecated
	 * @param $oSmarty Smarty
	 * @return Ext_TC_Frontend_Combination_Abstract
	 */
	protected function _getObjectForUsage(Smarty $oSmarty = null) {

		switch($this->usage) {
			case 'feedback_form':
				return new Ext_TC_Frontend_Combination_Feedback($this, $oSmarty);
		}

		return new Ext_TC_Frontend_Combination_Default($this, $oSmarty);
	}

	/**
	 * Da die eigentliche Methode aus irgendeinem Grund protected ist…
	 *
	 * @param Smarty $oSmarty
	 * @return Ext_TC_Frontend_Combination_Abstract
	 */
	public function getObjectForUsage(Smarty $oSmarty = null) {
		return $this->_getObjectForUsage($oSmarty);
	}

	/**
	 * Gibt eine Fehlermeldung aus, falls ein Fehler aufgetreten ist
	 * 
	 * @return string/null
	 */
	public function getError() {
		return $this->_sError;
	}

	/**
	 * Check the plausibility of setted params
	 * 
	 * @return boolean
	 */
	public function checkPlausibility() {

		$bCheck = true;

		// Da die Parameterüberschreibung nur bei diesen Verwendungen möglich ist, muss auch nur hier geprüft werden
		if(in_array($this->usage, array('registration', 'registration_enquiries', 'pricelists', 'pricelayer'))) {
			$oObject = $this->_getObjectForUsage();
			$bCheck = $oObject->checkPlausibility();
			
			if($bCheck === false) {
				$this->aPlausibilityDebug = $oObject->getPlausibilityDebug();
			}
			
		}

		return $bCheck;
	}

	/**
	 * @return array
	 */
	public function getPlausibilityDebug() {
		return $this->aPlausibilityDebug;
	}
	
	/**
	 * @return array
	 */
	public function getLanguages() {
		$aRetVal = (array)$this->items_languages;
		return $aRetVal;
	}

	/**
	 * @return string
	 */
	public function getLanguage() {
		$sRetVal = $this->items_language;

		if($sRetVal === null) {
			$aLanguages = $this->getLanguages();
			if(!empty($aLanguages)) {
				$sRetVal = reset($aLanguages);
			}
		}
		
		return $sRetVal;
	}

	/**
	 * @return array
	 */
	public function getMixedCurrencies() {
		$aRetVal = $this->items_mixedcurrencies;
		return $aRetVal;
	}

	/**
	 * @TODO Was hat das auf TC zu suchen?
	 *
	 * @return array
	 */
	public function getOffices() {
		$aRetVal = $this->items_offices;
		return $aRetVal;
	}

	/**
	 * @return array
	 */
	public function getProductLines() {
		$aRetVal = $this->items_productlines;
		return $aRetVal;
	}

	/**
	 * @return array
	 */
	public function getSeasons() {
		$aRetVal = $this->items_seasons;
		return $aRetVal;
	}

	/**
	 * @return integer
	 */
	public function getSchool() {
		$iRetVal = $this->items_school;
		return $iRetVal;
	}

	public function getKey() {
		return $this->key;
	}

	/**
	 * Aktualisiert den Status und bei Erfolg wird der Aktualisierungszeitpunkt gesetzt
	 * @param string $sStatus
	 */
	public function updateState($sStatus = 'ready') {

		$aData = [ 'status' => $sStatus ];

		if($sStatus === self::STATUS_READY) {
			$aData['last_cache_refresh'] = (new \Core\Helper\DateTime())->format('Y-m-d H:i:s');
		}

        \DB::updateData($this->_sTable, $aData, [ 'id' => $this->id ]);
	}
	
	public function setHeaderInformations() {		
		
	}
	
	/**
	 * Liefert alle Settings als String z.B. für die Verwendung in Cache-Keys 
	 * @return string
	 */
	public function getItemsAsString() {
		
		$aItems = (array)$this->items;

		if(!empty($aItems)) {

			$aGroupedItems = [];
			foreach($aItems as $aItem) {
				$aGroupedItems[$aItem['item']][] = $aItem['item_value'];
			}
			
			foreach($aGroupedItems as $sItem=>&$aValue) {
				$aValue = $sItem.'-'.implode('-', array_unique($aValue));
			}

			// Die Items müssen immer gleich sortiert sein. Der String wird fürs Caching benutzt und wir hatten den Fall
			// das beim Laden und Schreiben des Caches unterschiedliche Keys generiert wurden weil die Items in einer anderen
			// Reihenfolge waren
			sort($aGroupedItems);

			return implode('_', $aGroupedItems);

		}

		return '';
	}

	/**
	 * Request abgleichen mit Whitelist
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return bool
	 */
	public function validateReferrer(\Illuminate\Http\Request $request): bool {

		$allowed = Str::of($this->items_domains)
			->explode(',')
			->map(function ($domain) {
				return trim($domain);
			});

		// Proxy darf immer zugreifen und muss es beim iframe auch, da referer dann proxy.fidelo.com
		$allowed[] = System::d('proxy_host', 'proxy.fidelo.com');

		$referrer = parse_url($request->headers->get('referer'), PHP_URL_HOST);
		if (empty($referrer)) {
			$referrer = $request->getHost();
		}

		// Mix von proxy.fidelo.com und us.proxy.fidelo.com abfangen
		if (str_ends_with($referrer, 'proxy.fidelo.com')) {
			return true;
		}

		return $allowed->contains($referrer);

	}

	/**
	 * Kann die Kombination gesperrt werden für das Aktualisieren?
	 *
	 * Das ist eigentlich nur für die großen Agentur-Kombinationen notwendig.
	 *
	 * @return bool
	 */
	public function canLockStatus(): bool {

		return false;

	}
	
}
