<?php

/**
 * @link https://redmine.fidelo.com/projects/framework/wiki/Smarty-Platzhalter
 *
 * @todo type = 0 und loop = 'method' => Einbauen das Paramter aus 'method_parameter' übergeben werden
 * @todo Standardplatzhalter für PDF (Seiten, today) und Communication (TMC, today)
 * @todo Fehlerspeicher über Rekursion weiterreichen 
 * @todo Caching für das ersetzen einbauen (Kompliziert)
 * @todo Alte Platzhalterklasse entfernen
 */
abstract class Ext_TC_Placeholder_Abstract {

	const TC_PLACEHOLDER_CACHE_GROUP = 'TC_PLACEHOLDER_CACHE_GROUP';

	/**
	 * Sammelt Platzhalter variable_names, um in der Liste nur ein mal
	 * die Platzhalter relatives dieser Klasse anzuzeigen und für alle
	 * weiteren eine Verlinkung auf die erste erzeugen zu können. Zur
	 * Identifikation im Frontend wird der Key in diesem Array benutzt.
	 * @var array
	 */
	static protected array $used_placeholder_variable_name_list = [];

	/**
	 * L10N für Backendübersetzungen
	 * @var string
	 */
	protected static $_sL10N = 'Thebing Core » Placeholder';

	/**
	 * Ausgabe-/Frontendsprache
	 * @var string
	 */
	protected $_sDisplayLanguage = 'en';
	static protected $sDisplayLanguage = 'en';
	
	/**
	 * Konfiguration von verfügbaren Platzhaltern
	 *
	 * @link https://redmine.fidelo.com/projects/framework/wiki/Smarty-Platzhalter
	 */
	protected $_aPlaceholders = array();

	/**
	 * Array mit Einstellungen
	 * variable_name = Name der Smarty Variable die das WDBasic-Objekt enthält
	 * 
	 * @var array 
	 */
	protected $_aSettings = array();
	
	/**
	 * Instanz der Ext_TC_Basic
	 * @var Ext_TC_Basic
	 */
	protected $_oWDBasic;
	
	/**
	 * Klassennamen der WDBasic
	 * @var string 
	 */
	protected $_sWDBasic;
	
	/**
	 * Interner Fehlerspeicher
	 * @var array 
	 */
	protected $_aErrors = array();

	/**
	 * Smarty object
	 * @var SmartyWrapper
	 */
	protected $_oSmarty;
	
	/**
	 * Util
	 * @var Ext_TC_Placeholder_Util 
	 */
	protected $oPlaceholderUtil;
	
	/**
	 * Das höchste Level einer Platzhaltertabelle
	 * @var int
	 */
	protected $_iPlaceholderTableMaxLevel = 0;
	
	/**
	 * Speichert alle Kinder und verhindert Endlosschleifen
	 * @var array
	 */
	public $aChildObjects = array();
	
	/**
	 * Struktur der Platzhalter um die Rekursion der Platzhalter sortieren zu können
	 * @var array
	 */
	protected static $structure = [];
	
	/**
	 * Prefix für Platzhalternamen
	 * @var string
	 */
	protected $_sPlaceholderVariablePrefix = '';

	/**
	 * Prefix Keyword für Platzhalternamen
	 * @var string
	 */
	protected $placeholderVariablePrefixKeyword = '';

	/**
	 * Name des Foreach Index des aktuellen Loops
	 * @var string
	 */
	protected $_sForeachIndex = '';

	/**
	 * Helfer-Klasse für flxible Platzhalter (Gui-Designer, Flexible Felder, ...)
	 * @var Ext_TC_Placeholder_Helper_Flexible 
	 */
	protected $_oFlexiblePlaceholderHelper = null;
	
	/**
	 * Section aus dem GUI-Designer (inquiry/enquiry/students)
	 * @var string 
	 */
	protected $_sGuiDesignerSectionKey = '';
	
	/**
	 * Sections der Flexiblen Felder
	 * @var array 
	 */
	protected $_aFlexibleFieldsSections = array();

	/**
	 * Prefix des Parent-Elementes
	 * @var string 
	 */
	protected $_sParentPlaceholderPrefix = '';
	
	/**
	 * Platzhalter für pdf, communication oder anderes 
	 * @var string
	 */
	protected $_sType = 'pdf';
	
	/**
	 * Objekte, auf die sich die Platzhalter beziehen sollen
	 * @var array 
	 */
	protected $_aObjects = array();

	/**
	 * Absender bei Typ Communication
	 * @var Ext_TC_User
	 */
	protected $_oCommunicationSender;
	
	protected $_aLastUsedLevel = array();
	protected $_iLastLevel = 1;

	/**
	 * Prefix für das aktuelle Level
	 * @var string
	 */
	protected $levelPrefix;

	/**
	 * Prefix für das gesamte Level (-> der Weg bis zu dem Platzhalter)
	 * @var string
	 */
	protected $fullLevelPrefix;

	/**
	 * Finale Ausgabe, ALLE Platzhalter ersetzen, bei false wird 'only_final_output' geprüft
	 * @var bool
	 */
	protected $finalOutput = true;
	protected $listNotReplaced = [];

	/**
	 * Konstruktor: Überprüft das übergebene Objekt
	 * 
	 * @param $oWDBasic
	 * @throws Exception 
	 */
	final public function __construct($oWDBasic, SmartyWrapper $oSmarty=null) {

		if(!method_exists($oWDBasic, 'getPlaceholderClass')) {
			throw new Exception('No valid object "'.get_class($oWDBasic).'"');	
		}
		
		$sPlaceholderClass = $oWDBasic->getPlaceholderClass();

		if(!is_a($this, $sPlaceholderClass)) {
			throw new Exception('Wrong Ext_TC_Basic object "'.get_class($oWDBasic).'", "'.$sPlaceholderClass.'"');
		}
		
		$this->_oWDBasic = $oWDBasic;
		
		$this->_sWDBasic = get_class($oWDBasic);

		if($oSmarty !== null) {
			$this->_oSmarty = $oSmarty;
		}

		if(
			empty($this->_aFlexibleFieldsSections) &&
			method_exists($this->_oWDBasic, 'getFlexibleFieldsConfig')
		) {
			$this->_aFlexibleFieldsSections = array_keys($this->_oWDBasic->getFlexibleFieldsConfig());
		}
		
		$this->oPlaceholderUtil = new Ext_TC_Placeholder_Util();
	}

	/**
	 * Setzt den Typ der Platzhalterverwendung
	 * @param string $sType 
	 */
	public function setType($sType) {
		
		$this->_sType = $sType;
		
	}
	
	/**
	 * Set das Objekt des Absenders
	 * 
	 * @param Ext_TC_User $oSender 
	 */
	public function setCommunicationSender(Ext_TC_User $oSender) {

		if($this->_sType != 'communication') {
			throw new Exception('Communication sender is only allowed with type "communication"!');
		}

		$this->_oCommunicationSender = $oSender;

	}
	
	/**
	 * Display Language
	 * @param string $sLanguage 
	 */	
	public function setDisplayLanguage($sLanguage) {
		
		$this->_sDisplayLanguage = $sLanguage;
		static::$sDisplayLanguage = $sLanguage;
		
	}

	/**
	 * return string
	 */	
	public function getDisplayLanguage() {
		
		return $this->_sDisplayLanguage;
		
	}

	/**
	 * 
	 * @param Ext_TC_Placeholder_Helper_Flexible $oPlaceholderHelper
	 */
	public function setFlexiblePlaceholderHelper(Ext_TC_Placeholder_Helper_Flexible $oPlaceholderHelper) {

		$this->_oFlexiblePlaceholderHelper = $oPlaceholderHelper;
		
	}
	
	/**
	 * speichert den Prefix des Parent-Elementes
	 * @param string $sParentPrefix
	 */
	public function setParentPlaceholderPrefix($sParentPrefix) {
		
		$this->_sParentPlaceholderPrefix = $sParentPrefix;
		
	}
	
	/**
	 * Objects
	 * @param array $aObjectIds
	 */
	public function setObjects($aObjectIds) {
		$this->_aObjects =  (array)$aObjectIds;
	}
	
	/**
	 * Gibt eine Einstellung zurück
	 * @param string $setting
	 * @return mixed
	 */
	final public function getSetting(string $setting, $default = null) {
		return \Illuminate\Support\Arr::get($this->_aSettings, $setting, $default);
	}
	
	final public function setSetting($sSetting, $mValue) {
		
		$this->_aSettings[$sSetting] = $mValue;
		
	}

	final public function setFinalOutput(bool $finalOutput) {
		$this->finalOutput = $finalOutput;
		return $this;
	}

	final public function getListNotReplaced() {
		return $this->listNotReplaced;
	}


	/**
	 * Möglichkeit, bestimmte Platzhalter dieser Klasse auszuschließen
	 *
	 * @param array $aPlaceholders
	 * @return self
	 */
	final public function excludePlaceholders(array $aPlaceholders) {

		// Nur Platzhalter exkludieren die entweder keine $sourceClassName haben oder aus dieser Klasse stammen
		$excludedPlaceholders = array_filter($aPlaceholders, fn ($placeholder) => !is_array($placeholder) || $placeholder[0] === $this::class);

		$excludedPlaceholders = array_map(fn ($placeholder) => is_array($placeholder) ? $placeholder[1] : $placeholder , $excludedPlaceholders);

		$this->fillPlaceholders();

		// Platzhalter exkludieren
		$this->_aPlaceholders = array_diff_key($this->_aPlaceholders, array_flip(array_unique($excludedPlaceholders)));

		$this->setSetting('excluded_placeholders', $aPlaceholders);

		return $this;
	}

	/**
	 * Ersetzt alle Platzhalter in einem Template
	 *  
	 * @todo Funktionalität auch bei Verknüpfungen ohne Einträge ermöglichen
	 * @param string $sTemplate
	 * @return string
	 */
	final public function replace($sTemplate, $bFinalOutput = true) {

		$this->setFinalOutput($bFinalOutput);

		self::$structure = [$this];

		// Leeres Template nicht bearbeiten
		$sTemplate = trim($sTemplate);
		if(empty($sTemplate)) {
			return $sTemplate;
		}
		
		if(!$this->_oSmarty) {
			$this->_oSmarty = Ext_TC_Factory::getObject('SmartyWrapper');
		}

		/**
		 * @todo Korrekt und sauber umsetzen über das Platzhalterobjekt
		 */
		if($this->_sType == 'communication') {
			
			$sTemplate = str_replace('{sender_firstname}', $this->_oCommunicationSender?->firstname, $sTemplate);
			$sTemplate = str_replace('{sender_lastname}', $this->_oCommunicationSender?->lastname, $sTemplate);
			$sTemplate = str_replace('{sender_email}', $this->_oCommunicationSender?->email, $sTemplate);
			
			// Schul-Platzhalter
			$sTemplate = str_replace('{system_user_name}', $this->_oCommunicationSender?->getName(), $sTemplate);
			$sTemplate = str_replace('{system_user_firstname}', $this->_oCommunicationSender?->firstname, $sTemplate);
			$sTemplate = str_replace('{system_user_surname}', $this->_oCommunicationSender?->lastname, $sTemplate);
			$sTemplate = str_replace('{system_user_email}', $this->_oCommunicationSender?->email, $sTemplate);
			$sTemplate = str_replace('{system_user_phone}', $this->_oCommunicationSender?->phone, $sTemplate);
			$sTemplate = str_replace('{system_user_fax}', $this->_oCommunicationSender?->phone, $sTemplate);

		}

		// Rekursion so effizient wie möglich machen
		$this->getPlaceholderStructure();
		$this->sortPlaceholderStructure();

		$listNotReplaced = [];

		// Platzhalter holen und durch Smarty-Platzhalter ersetzen
		foreach (self::$structure as $placeholderObject) {
			// Template vorbereiten
			$sTemplate = $placeholderObject->prepareTemplate($sTemplate);
			$listNotReplaced = array_unique([...$listNotReplaced, ...$placeholderObject->getListNotReplaced()]);
		}

		// @todo Überprüfen, ob alle Platzhalter ersetzt werden konnten
		$this->_aErrors = array();
		try {

			// Ausgaben beim Templateparsen abfangen
			ob_start();
			$sTemplate = $this->_oSmarty->fetch('string:'.$sTemplate);
			// Ausgabe leeren
			ob_end_clean();

		} catch (Ext_TC_Exception $oException) {

			// Ausgabe leeren
			ob_end_clean();

			$sTemplate = '';

			$sMessage = $oException->getMessage();
			$sKey = $oException->getKey();

			switch($sKey) {
				case 'PLACEHOLDER_CHILD_WITH_INDEX_NOT_DEFINED':
				case 'PLACEHOLDER_CHILDS_NOT_DEFINED':
				default:
					$this->_aErrors[] = array('SMARTY_EXCEPTION' => $sMessage);
					break;
			}
			
		} catch (Exception $oException) {

			// Ausgabe leeren
			ob_end_clean();

			$sTemplate = '';

			$sMessage = $oException->getMessage();

			if(strpos($sMessage, 'unknown tag') !== false) {

				$iMatch = preg_match('/unknown tag "(.*)"/', $sMessage, $aMatch);
				
				if($iMatch > 0) {

					$this->_aErrors[] = array('UNKNOWN_TAG' => $aMatch[1]);
					
				} else {

					$this->_aErrors[] = array('UNKNOWN_TAG' => $sMessage);
					
				}
				
			} else {
				
				$this->_aErrors[] = array('SMARTY_EXCEPTION' => $sMessage);
				
			}

		}

		return $sTemplate;
	}

	public function sortPlaceholderStructure() {
		usort(self::$structure, function ($a, $b) {
			return $a->_iLastLevel <=> $b->_iLastLevel;
		});
	}

	public function prepareTemplate($sTemplate) {

		$fullLevelPrefix = $this->fullLevelPrefix;

		// Wenn vor dem Platzhalter noch ein Prefix steht, aber nicht der fullLevelPrefix davor steht, dann skippen
		// (-> fullLevelPrefix eingebaut für sortierung der Rekursion)
		// In eine closure gemacht, da es bei anderen Vorkommen kein fullLevelPrefix gibt und da nur nach dem Prefix geschaut wird
		$skipPlaceholder = function($prefix, $placeholder) use($fullLevelPrefix) {
			// Loop-Indizes entfernen
			$cleanPrefix = preg_replace('/#\d+/', '', $prefix);
			if(
				empty($prefix) ||
				str_contains($fullLevelPrefix, $cleanPrefix)
			) {
				return false;
			}
			return true;
		};

		$aPlaceholders = $this->oPlaceholderUtil->getPlaceholdersInTemplate($sTemplate, $skipPlaceholder);

		// Platzhalter durchgehen und durch Smarty-Platzhalter ersetzen
		foreach((array)$aPlaceholders as $sPlaceholder => $aPlaceholder) {

			foreach((array)$aPlaceholder as $sItem => $aItem) {
				$sTemplate = $this->_preparePlaceholder($sTemplate, $sPlaceholder, $aItem);
			}

		}

		return $sTemplate;
	}
	
	final public function replaceForPdf($oDocument, $oVersion, $sTemplate) {
		
		$sTemplate = $this->replace($sTemplate);
		
		return $sTemplate;
	}
	
	/**
	 *
	 * @param object $oBasic
	 * @param array $aPlaceholder
	 * @param array $aChilds
	 * @param object $oChild 
	 */
	public static function getBasicLoopObjects(&$oBasic, array $aPlaceholder, &$aChilds, &$oChild, $bCreateEmpty=true) {

		if(
			!isset($aPlaceholder['source'])
		) {
			throw new InvalidArgumentException('Needed $aPlaceholder keys missing');
		}

		$sLoop = $aPlaceholder['loop'] ?? null;
		$sSource = $aPlaceholder['source'] ?? null;

		switch($sLoop) {
			case 'method':		

				if(isset($aPlaceholder['pass_language'])){	
					$aPlaceholder['method_parameter'][] = static::$sDisplayLanguage;
				} else if (isset($aPlaceholder['pass_language_object'])) {
					$aPlaceholder['method_parameter'][] = new \Tc\Service\Language\Frontend(static::$sDisplayLanguage);
				}

				if(!empty($aPlaceholder['method_parameter'])) {
					$aChilds = (array)call_user_func_array([$oBasic, $sSource], $aPlaceholder['method_parameter']);
				} else {
					$aChilds = (array)$oBasic->$sSource();
				}
			
				// Wenn leer und class angegeben: Leeres Objekt erzeugen
				if(
					$bCreateEmpty === true &&
					empty($aChilds) &&
					!empty($aPlaceholder['class'])
				) {
					$aChilds[] = new $aPlaceholder['class']();
				}

				// Wenn leer (auch class ist leer): Exception, da ansonsten Fatal Error
				if(
					$bCreateEmpty === true &&
					empty($aChilds)
				) {
					throw new RuntimeException('Ext_TC_Placeholder_Abstract::getBasicLoopObjects(): Method for loop returned nothing and no class is given');
				} else {
					$oChild	= reset($aChilds);
				}

				break;
			case 'join_table':

				if (!$oBasic instanceof \Ext_TC_Basic) {
					throw new \RuntimeException('No Ext_TC_Basic object given for loop type "'.$sLoop.'" ['.__METHOD__.']');
				}

				// WDBasic holen 
				$aChilds = $oBasic->getJoinTableObjects($sSource);

				// Wenn keine Kinder, dann leeres Kind erzeugen
				if(empty($aChilds)) {
					if($bCreateEmpty === true) {
						// Dieses Objekt darf nicht in den Cache der JoinTableObjects geschrieben werden! Ansonsten kann es passieren
						// das diese mit abgespeichert/validiert werden, wenn man das Platzhalterobjekt auf ein Entity mit id > 0 aufruft
						$oChild = $oBasic->getJoinTableObject($sSource, 0, false);
						$aChilds = array($oChild);
					}
				} else {
					$oChild = reset($aChilds);
				}

				break;
			case 'flex_field_childs':

				if (!$oBasic instanceof \Ext_TC_Basic) {
					throw new \RuntimeException('No Ext_TC_Basic object given for loop type "'.$sLoop.'" ['.__METHOD__.']');
				}

				/* @var Ext_TC_Flexibility $oField */
				$oField = Ext_TC_Factory::getInstance('Ext_TC_Flexibility', $aPlaceholder['source']);

				if(!is_null($oParentEntity = $oBasic->getPlaceholderParentEntity())) {
					$aContainers = $oField->getFormattedPlaceholderValue($oParentEntity, $oBasic, static::$sDisplayLanguage);
				} else {
					$aContainers = $oField->getFormattedValue($oBasic, static::$sDisplayLanguage);
				}

				// Immer alle Felder mit Defaultwert hinzufügen um die Exception zu vermeiden (Ext_TC_Placeholder_Helper_DataModel::__get());
				$aDefaultValues = collect($oField->getChildFields())->mapWithKeys(function($oChildField) {
						return ['field_'.$oChildField->getId() => ''];
					})
					->prepend($aPlaceholder['source'], 'parent_field');

				$aChilds = [];
				foreach ($aContainers as $aContainer) {

					$aContainer = collect($aContainer)
						->mapWithKeys(function($mValue, $iFieldId) {
							return ['field_'.$iFieldId => $mValue];
						});

					$aChilds[] = new \Ext_TC_Placeholder_Helper_DataModel(Ext_TC_Placeholder_Helper_Flexible_Placeholder::class, $aContainer->toArray(), $aDefaultValues->toArray());
				}

				if(empty($aChilds) && $bCreateEmpty === true) {
					$aChilds[] = new \Ext_TC_Placeholder_Helper_DataModel(Ext_TC_Placeholder_Helper_Flexible_Placeholder::class, $aDefaultValues->toArray());
				}

				$oChild = reset($aChilds);

				break;
			case 'joined_object':
			default:

				if (!$oBasic instanceof \Ext_TC_Basic) {
					throw new \RuntimeException('No Ext_TC_Basic object given for loop type "'.$sLoop.'" ['.__METHOD__.']');
				}

				// WDBasic holen
				$aChilds = $oBasic->getJoinedObjectChilds($sSource, true);
 
				// Wenn keine Kinder, dann leeres Kind erzeugen
				if(empty($aChilds)) {
					if($bCreateEmpty === true) {
						// Hier darf nicht mit getJoinedObjectChild() gearbeitet werden da ansonsten leere Objekte
						// gespeichert werden können wenn auf das WDBasic-Objekt nochmal save() aufgerufen wird
						$aJoinedObjectData = $oBasic->getJoinedObjectData($sSource);
						$oChild = new $aJoinedObjectData['class']();
						#$oChild = $oBasic->getJoinedObjectChild($sSource);
						$aChilds = array($oChild);
					}
				} else {
					$oChild = reset($aChilds);
				}

				break;
		}

		if(!empty($aChilds)) {
			// Immer Parent setzen für abhängige Eigenschaften
			foreach($aChilds as &$oThisChild) {
				$oThisChild->setPlaceholderParentEntity($oBasic);
			}
		}
		
		if(!empty($oChild)) {
			$oChild->setPlaceholderParentEntity($oBasic);
		}
		
	}
	
	/**
	 * 
	 * @param $oBasic
	 * @param array $aPlaceholder
	 * @param object $oParent
	 */
	public static function getBasicLoopParent(&$oBasic, $aPlaceholder, &$oParent) {

		if(
			!isset($aPlaceholder['source'])
		) {
			throw new InvalidArgumentException('Needed $aPlaceholder keys missing');
		}

		$sParent = $aPlaceholder['parent'];
		$sSource = $aPlaceholder['source'];

		switch($sParent) {
			case 'method':

				if(!empty($aPlaceholder['method_parameter'])) {
					$oParent = call_user_func_array([$oBasic, $sSource], $aPlaceholder['method_parameter']);
				} else {
					$oParent = $oBasic->$sSource();
				}

				// Wenn kein Objekt, aber class angegeben: Leeres Objekt erzeugen
				if(
					!is_object($oParent) &&
					isset($aPlaceholder['class'])
				) {
					$oParent = new $aPlaceholder['class']();
				}

				break;
			case 'join_table':

				if (!$oBasic instanceof \Ext_TC_Basic) {
					throw new \RuntimeException('No Ext_TC_Basic object given for parent type "'.$sParent.'" ['.__METHOD__.']');
				}

				// WDBasic holen
				$aParents	= (array)$oBasic->getJoinTableObjects($sSource);

				if(
					empty($aParents)
				) {
					$oParent = $oBasic->getJoinTableObject($sSource, 0, false);
				} else {
					$oParent	= reset($aParents);
				}
				break;
			case 'joined_object':
			default:

				if (!$oBasic instanceof \Ext_TC_Basic) {
					throw new \RuntimeException('No Ext_TC_Basic object given for parent type "'.$sParent.'" ['.__METHOD__.']');
				}

				// WDBasic holen
				$aJoinedObjectConfig = $oBasic->getJoinedObjectConfig($sSource);

				if (empty($aJoinedObjectConfig)) {
					throw new \RuntimeException(sprintf('Missing joined object config for placeholder parent [%s]', $sSource));
				}

				if ($oBasic->hasJoinedObject($sSource)) {
					$oParent = $oBasic->getJoinedObject($sSource);
				} else {
					$oParent = new $aJoinedObjectConfig['class']();
				}

				break;
		}

		if (is_object($oParent)) {
			$oParent->setPlaceholderChild($oBasic);
		}
	}

	/**
	 * Gibt ein Array mit allen Beziehungen zurück inklusive Objekte und 
	 * Platzhalterobjekten und ergänzt das Platzhalter-Array
	 * 
	 * @return array 
	 */
	final protected function _getRelatives($bPlaceholderList=false) {

		$aRelatives = array();

		$this->fillPlaceholders();

		foreach($this->_aPlaceholders as $sPlaceholder => &$aPlaceholder) {
			// Verhindert eine endlose Rekursion
			if ($this->checkForEndlessLoop(explode(".", $this->fullLevelPrefix), $sPlaceholder)) {
				continue;
			}
			if(
				$bPlaceholderList === true &&
				!empty($aPlaceholder['invisible'])
			) {
				continue;
			}

			$sClass = '';
			if(isset($aPlaceholder['class'])) {
				$sClass = $aPlaceholder['class'];
			}

			$aExcludePlaceholders = [
				// Problematisch bei zb. Adressen/Details die je nach Fall öfters vorkommen müssen
				//(in_array($aPlaceholder['type'], ['loop', 'parent'])) ? [$this::class, $sPlaceholder] : [],
				// Alle bisherigen Excluded-Platzhalter an die Childs weitergeben
				...$this->getSetting('excluded_placeholders', []),
				...$aPlaceholder['exclude_placeholders'] ?? []
			];

			if($aPlaceholder['type'] == 'loop') {

				$aChilds = array();
				$oChild = null;

				self::getBasicLoopObjects($this->_oWDBasic, $aPlaceholder, $aChilds, $oChild);

				if($oChild) {

					/* @var static $oPlaceholderObject */
					$oPlaceholderObject = $oChild->getPlaceholderObject($this->_oSmarty);

					if($oPlaceholderObject){

						if (!empty($aExcludePlaceholders)) {
							$oPlaceholderObject->excludePlaceholders($aExcludePlaceholders);
						}

						if(!empty($aPlaceholder['variable_name'])) {
							$oPlaceholderObject->setSetting('variable_name', $aPlaceholder['variable_name']);
						}

						$sForeachIndex = $this->_makeForeachIndex($oPlaceholderObject->getSetting('variable_name'));

						$sPrefix = $this->_sPlaceholderVariablePrefix.'->getPlaceholderChild(\''.$sPlaceholder.'\', $'.$sForeachIndex.')';
						$oPlaceholderObject->setFlexiblePlaceholderHelper($this->_oFlexiblePlaceholderHelper);
						$oPlaceholderObject->setForeachIndex($sForeachIndex);
						$oPlaceholderObject->setPrefix($sPrefix);
						$oPlaceholderObject->setLevelPrefix($sPlaceholder);
						$oPlaceholderObject->setFullLevelPrefix($this->fullLevelPrefix, $sPlaceholder);
						$oPlaceholderObject->setObjects($this->_aObjects);

						$aPlaceholder['object_name'] = $oPlaceholderObject->getSetting('variable_name');
						$aPlaceholder['prefix'] = $sPrefix;

						$aRelatives[$sPlaceholder] = array(
							'placeholder' => $oPlaceholderObject,
							'object' => $oChild,
							'childs' => $aChilds
						);
						
					}

				}
				
			} elseif($aPlaceholder['type'] == 'parent') {

				$oParent = null;

				self::getBasicLoopParent($this->_oWDBasic, $aPlaceholder, $oParent);

				if($oParent) {

					/* @var static $oPlaceholderObject */
					$oPlaceholderObject = $oParent->getPlaceholderObject($this->_oSmarty);

					if (!$oPlaceholderObject) {
						throw new \RuntimeException(sprintf('Missing parent object placeholder class [%s]', $oParent::class));
					}

					if (!empty($aExcludePlaceholders)) {
						$oPlaceholderObject->excludePlaceholders($aExcludePlaceholders);
					}

					$sPrefix = $this->_sPlaceholderVariablePrefix.'->getPlaceholderParent(\''.$aPlaceholder['parent'].'\', \''.$aPlaceholder['source'].'\', \''.$sClass.'\')';
					$oPlaceholderObject->setForeachIndex($this->_sForeachIndex);
					$oPlaceholderObject->setPrefix($sPrefix);
					$oPlaceholderObject->setLevelPrefix($sPlaceholder);
					$oPlaceholderObject->setFullLevelPrefix($this->fullLevelPrefix, $sPlaceholder);
					$oPlaceholderObject->setObjects($this->_aObjects);

					$oPlaceholderObject->aChildObjects[] = get_class($this);
					$aPlaceholder['object_name'] = $oPlaceholderObject->getSetting('variable_name');
					$aPlaceholder['prefix'] = $sPrefix;

					$aRelatives[$sPlaceholder] = array(
						'placeholder' => $oPlaceholderObject,
						'object' => $oParent
					);

				}

			}

		}

		return $aRelatives;		
	}

	/**
	 * Prüft ob der Platzhalter zu oft in die Rekursion gegangen ist. Wird die max Anzahl erreicht, wird ein Log erstellt.
	 * @param array $path
	 * @param string $placeholder
	 * @return bool
	 */
	private function checkForEndlessLoop(array $path, string $placeholder = ''): bool
	{
		$maximalesPlatzhalterVorkommenImPfad = 3;
		$counts = array_count_values($path);
		if ($counts[$placeholder] > $maximalesPlatzhalterVorkommenImPfad) {
			Log::getLogger()->info("Recursive Placeholder: " . implode(".", $path) . "." . "$placeholder has more than $maximalesPlatzhalterVorkommenImPfad times in the path. Skipping.");
			return true;
		}
		return false;
	}

	/**
	 * Weißt alle relevanten Objekte dem Smarty-Objekt zu
	 */
	protected function _assignObjects() {

		$sVariableName = $this->getSetting('variable_name');

		$this->_assignVariable($sVariableName, $this->_oWDBasic);
		// @todo Weitere Objekte aus Array lesen
		
	}

	/**
	 * Variable in Smarty zuweisen, sofern noch nicht vorhanden
	 * 
	 * @param string $sName
	 * @param mixed $mVariable 
	 * @param bool $bForce // TODO umbenennen
	 */
	protected function _assignVariable(&$sName, &$mVariable, $bForce = false) {

		if(!isset($this->_oSmarty->tpl_vars[$sName])) {
			$this->_oSmarty->assign($sName, $mVariable);
		} else {

			if ($bForce) {
				$sOriginalName = $sName;
				$count = 1;
				do {
					$sName = $sOriginalName.'_'.$count;
					$count++;
				} while (isset($this->_oSmarty->tpl_vars[$sName]));
				$this->_oSmarty->assign($sName, $mVariable);
			}

			/**
			 * @todo Exception wieder einkommentieren, sobald Klasse überarbeitet
			 */
			//throw new Exception('Overwriting smarty vars is not allowed! ('.get_class($this->_oWDBasic).')');
		}

	}
	
	/**
	 * Ersetzt einzelne Platzhalter durch Smarty-Platzhalter
	 * 
	 * @param string $sTemplate
	 * @param string $sPlaceholder
	 * @param array $aItem
	 * @return string
	 */
	final protected function _preparePlaceholder($sTemplate, $sPlaceholder, $aItem) {

		$sReplace = null;
		$bReplaceClosingTag = false;
		$bFormat = false;

		$this->fillPlaceholders();

		if(!isset($this->_aPlaceholders[$sPlaceholder])) {
			$this->_aErrors[] = array('UNKNOWN_PLACEHOLDER' => $sPlaceholder);
		} else {
			
			$aPlaceholder =& $this->_aPlaceholders[$sPlaceholder];

			if (
				$this->finalOutput === true ||
				!isset($aPlaceholder['only_final_output']) ||
				$aPlaceholder['only_final_output'] === false
			) {

				/**
				 * Format-Klasse anwenden, wenn definiert und kein Modifier angegeben
				 */
				if(
					isset($aPlaceholder['format']) &&
					empty($aItem['modifier'])
				) {

					$sClassName = $aPlaceholder['format'];
					$aFormatParameters = $aPlaceholder['format_parameter'] ?? [];

					$oFormat = \Factory::getObject($sClassName, $aFormatParameters);

					$sFormatObjectVariable = $this->preparePlaceholderSettingClass($sClassName);

					if($oFormat instanceof Ext_Gui2_View_Format_Abstract){

						$languageObject = new \Tc\Service\Language\Frontend($this->_sDisplayLanguage);
						$oFormat->setLanguageObject($languageObject);

						// spezielle Platzhalter Format-Klasse
						if($oFormat instanceof Ext_TC_Placeholder_Format_Abstract) {
							$oFormat->bindPlaceholder($aPlaceholder);
							$oFormat->setDisplayLanguage($this->_sDisplayLanguage);
						} else {
							$oFormat->setLanguage($this->_sDisplayLanguage);
						}

						$this->_assignVariable($sFormatObjectVariable, $oFormat, true);
						$bFormat = true;
					}

				}

				switch($aPlaceholder['type']) {
					case 'loop':

						$sForeachSource = '$'.$aPlaceholder['variable_name'];

						$sClass = '';
						if(isset($aPlaceholder['class'])) {
							$sClass = $aPlaceholder['class'];
						}

						if(empty($aPlaceholder['object_name'])) {
							$aPlaceholder['object_name'] = $this->makeObjectName($aPlaceholder['variable_name']);
						}

						// Name des Objektes ermitteln
						$sForeachSource = '$'.$this->getPrefix().'->getPlaceholderChilds(\''.$sPlaceholder.'\', false)';

						if(mb_substr($aItem['suffix'], 0, 1) == '@') {
							$sReplace = '$'.$aPlaceholder['object_name'];
							#$sReplace = '$'.$this->_makeForeachIndex($aPlaceholder['object_name']);
							$bReplaceClosingTag = false;
						} else {
							$sReplace = '{foreach '.$sForeachSource.' as $'.$this->_makeForeachIndex($aPlaceholder['object_name']).' => $'.$aPlaceholder['object_name'].'}';
							$bReplaceClosingTag = true;
						}

						break;
					case 'method':

						// Übergibt die Paramter und _sDisplayLanguage
						$aMethodParameter = array();

						if(isset($aPlaceholder['pass_language'])){
							$aMethodParameter[] = "'".$this->_sDisplayLanguage."'";
						}

						if(isset($aPlaceholder['method_parameter'])){

							if(!is_array($aPlaceholder['method_parameter'])) {
								$aPlaceholder['method_parameter'] = array($aPlaceholder['method_parameter']);
							}

							foreach($aPlaceholder['method_parameter'] as $sParameter){

								if(is_bool($sParameter) == true) {
									$aMethodParameter[] = 'true';
								} elseif(is_numeric($sParameter)) {
									$aMethodParameter[] = $sParameter;
								} else {
									$aMethodParameter[] = "'".$sParameter."'";
								}

							}

						}

						if(isset($aPlaceholder['pass_language_last'])){
							$aMethodParameter[] = "'".$this->_sDisplayLanguage."'";
						}

						$sMethodParameter = implode(', ', $aMethodParameter);

						$sCurrentPrefix = $this->_makeForeachIndexPrefix($aItem);

						// prüft ob Ausgabe formatiert werden muss
						if($bFormat) {
							$sReplace = '$'.$sFormatObjectVariable.'->formatByValue($'.$sCurrentPrefix.'->'.$aPlaceholder['source'].'('.$sMethodParameter.'))';
						} else {
							$sReplace = '$'.$sCurrentPrefix.'->'.$aPlaceholder['source'].'('.$sMethodParameter.')';
						}

						break;
					case 'class':

						$sCurrentPrefix = $this->_makeForeachIndexPrefix($aItem);

						$oPlaceholderExternClass = new $aPlaceholder['source']($this->_oSmarty);

						if ($oPlaceholderExternClass instanceof \Tc\Traits\Placeholder\ReplaceInterface) {
							$oPlaceholderExternClass->setPlaceholder($aItem);
							if ($oPlaceholderExternClass->isModifierAware()) {
								// TODO So umbauen, dass die Klasse ihre Modifier konsumiert, den Rest aber belässt, sodass diese weiter durch Smarty gehen
								$aItem['suffix'] = '';
							}
						} elseif ($oPlaceholderExternClass instanceof Ext_TC_Placeholder_Abstract_Replace) {
							if (isset($aPlaceholder['method_parameter'])) {
								$oPlaceholderExternClass->bindParameters($aPlaceholder['method_parameter']);
							}

							if ($aPlaceholder['pass_language'] === true) {
								$oPlaceholderExternClass->setDisplayLanguage($this->_sDisplayLanguage);
							}
						} else {
							throw new DomainException('Placeholder replace class is of wrong type');
						}

						$sExternClassVariable = $this->preparePlaceholderSettingClass($aPlaceholder['source'] . $sPlaceholder);
						$this->_assignVariable($sExternClassVariable, $oPlaceholderExternClass);

						if(!empty($this->_sParentPlaceholderPrefix)) {
							$sReplace = '$'.$sExternClassVariable.'->replace($'.$sCurrentPrefix.', $'.$this->_sParentPlaceholderPrefix.')';
						} else {
							$sReplace = '$'.$sExternClassVariable.'->replace($'.$sCurrentPrefix.')';
						}

						break;
					case 'gui_designer':

						$sCurrentPrefix = $this->_makeForeachIndexPrefix($aItem);

						$sReplace = '$oGuiDesignElement'.$aPlaceholder['element_id'].'->getValue($'.$this->_sParentPlaceholderPrefix.'->id, $'.$sCurrentPrefix.'->id, "'.$this->_sWDBasic.'")';

						if($bFormat) {
							$sReplace = '$'.$sFormatObjectVariable.'->formatByValue('.$sReplace.')';
						}

						break;

					case 'flexible_field':

						$sCurrentPrefix = $this->_makeForeachIndexPrefix($aItem);

						$sReplace = '$oFlexibleField'.$aPlaceholder['element_id'];

						if(!empty($this->_sParentPlaceholderPrefix)) {
							// Wenn Parentobjekt vorhanden, dann dieses mitliefern
							$sReplace .= '->getFormattedPlaceholderValue($'.$this->_sParentPlaceholderPrefix.', $'.$sCurrentPrefix.', "'.$this->_sDisplayLanguage.'")';
						} else {
							$sReplace .= '->getFormattedValue($'.$sCurrentPrefix.', "'.$this->_sDisplayLanguage.'")';
						}

						if($bFormat) {
							$sReplace = '$'.$sFormatObjectVariable.'->formatByValue('.$sReplace.')';
						}

						break;
					case 'field':
					default:

						$sCurrentPrefix = $this->_makeForeachIndexPrefix($aItem);

						// prüft ob Ausgabe formatiert werden muss
						if($bFormat) {
							$sReplace = '$'.$sFormatObjectVariable.'->formatByValue($'.$sCurrentPrefix.'->'.$aPlaceholder['source'].')';
						} else {
							$sReplace = '$'.$sCurrentPrefix.'->'.$aPlaceholder['source'].'';
						}

						break;
				}
			} else {

				if (!in_array($aPlaceholder['type'], ['method', 'field', 'class'])) {
					throw new \RuntimeException(sprintf('Cannot set ["only_final_output" = true] on placeholders of type "%s" [%s]', $aPlaceholder['type'], $sPlaceholder));
				}

				if (!empty($aItem['if'])) {
					throw new \RuntimeException(sprintf('Cannot use placeholder in if-condition [%s]', $sPlaceholder));
				}

				$this->listNotReplaced[] = $aItem['complete'];

			}

		}

		// Platzhalter nur ersetzen, wenn er gefunden wurde
		if($sReplace !== null) {

			// @todo Wert optional durch Frontendübersetzung schicken? |L10N}

			if($bReplaceClosingTag === true) {
				$sTemplate = str_replace('{'.$aItem['complete'].'}', $sReplace, $sTemplate);
				$sTemplate = str_replace('{/'.$sPlaceholder.'}', '{/foreach}', $sTemplate);
			} else {
				$sTemplate = str_replace('{'.$aItem['complete'].'}', '{'.$aItem['if'].$sReplace.$aItem['suffix'].'}', $sTemplate);
			}

		}

		return $sTemplate;
	}

	final protected function preparePlaceholderSettingClass($sClassName) {
		$sObjectVariable = str_replace('Ext_', '', $sClassName);
		$sObjectVariable = str_replace('_', '', $sObjectVariable);
		$sObjectVariable = str_replace('\\', '', $sObjectVariable);
		$sObjectVariable = 'o'.$sObjectVariable;
		
		return $sObjectVariable;
	}
	
	/**
	 * Generiert einen Index-Namen für Foreach
	 * 
	 * @param string $sName
	 * @return string 
	 */
	final protected function _makeForeachIndex($sName) {

		if (ctype_lower($sName[0]) && ctype_upper($sName[1])) {
			return 'i'.mb_substr($sName, 1);
		} else {
			return 'i'.mb_strtoupper($sName[0]).mb_substr($sName, 1);
		}
	}

	/**
	 * @param string $sName
	 * @return string
	 */
	final protected function makeObjectName($sName) {

		if (ctype_lower($sName[0]) && ctype_upper($sName[1])) {
			return 'o'.mb_substr(rtrim($sName, 's'), 1);
		} else {
			return 'o'.mb_strtoupper($sName[0]).mb_substr(rtrim($sName, 's'), 1);
		}

		return $sName;
	}


	/**
	 * Generiert den finalen Prefix, um einen Index anzusprechen
	 * Beispiel: {student_course_loop#2.student_first_name}
	 *
	 * @param array $aItem
	 * @return string
	 */
	final protected function _makeForeachIndexPrefix($aItem){
		
		$sCurrentPrefix = $this->getPrefix();		

		// Wenn ein Loop-Index angegeben ist, dann diesen verwenden
		if($aItem['direct_loop_index']) {
			$sCurrentPrefix = str_replace('$'.$this->_sForeachIndex, $aItem['direct_loop_index']-1, $sCurrentPrefix);
		}

		return $sCurrentPrefix;

	}

	/**
	 * Ersetzt alle Platzhalter durch Smarty-Platzhalter
	 * 
	 * @param string $sTemplate 
	 * @return string
	 * 
	 */
	final public function getPlaceholderStructure() {

		$this->setFlexiblePlaceholder(true);

		// Prefix (Objektname) setzen (Aufruf)
		if(empty($this->_sPlaceholderVariablePrefix)) {
			$this->_sPlaceholderVariablePrefix = $this->getSetting('variable_name');
		}

		// Prefix (Objektname) setzen (Platzhalter "Keyword")
		if(empty($this->placeholderVariablePrefixKeyword)) {
			$this->placeholderVariablePrefixKeyword = $this->levelPrefix;
		}

		// Alle Verwandten holen
		$aRelatives = $this->_getRelatives();

		// Aktuelles Objekt zuweisen
		$this->_assignObjects();

		// Alle weiteren Platzhalterklassen anwenden und Objekte zuweisen
		foreach($aRelatives as $sPlaceholder=>$aRelative) {

			// @todo Stimmt die Variablenbezeichnung? Sind doch auch Childs drin.
			$oParentPlaceholderObject = $aRelative['placeholder'];
			if($oParentPlaceholderObject instanceof Ext_TC_Placeholder_Abstract) {

				$aPlaceholder = $this->_aPlaceholders[$sPlaceholder];

				if(!empty($aRelative['object'])) {
					$this->_assignVariable($aPlaceholder['object_name'], $aRelative['object']);
				}				

				if(!empty($aRelative['childs'])) {
					$this->_assignVariable($aPlaceholder['variable_name'], $aRelative['childs']);
				}

				$oParentPlaceholderObject->setFinalOutput($this->finalOutput);

				// Attribute, die rekursiv weitergegeben werden
				$oParentPlaceholderObject->setDisplayLanguage($this->_sDisplayLanguage);
				$oParentPlaceholderObject->setParentPlaceholderPrefix($this->_sPlaceholderVariablePrefix);

				// Verschachtelte Platzhalter vorbereiten
				$oParentPlaceholderObject->setLevelPrefix($sPlaceholder);
				$oParentPlaceholderObject->setFullLevelPrefix($this->fullLevelPrefix, $sPlaceholder);

				$oParentPlaceholderObject->setObjects($this->_aObjects);
				$oParentPlaceholderObject->setFlexiblePlaceholderHelper($this->_oFlexiblePlaceholderHelper);

				$oParentPlaceholderObject->setLevel($this->_iLastLevel+1);

				$oParentPlaceholderObject->getPlaceholderStructure();

				self::$structure[] = $oParentPlaceholderObject;

			}

		}
	}

	protected function setLevelPrefix(string $levelPrefix) {
		$this->levelPrefix = $levelPrefix;
	}

	protected function setFullLevelPrefix(?string $parentLevelPrefix, string $levelPrefix) {
		$this->fullLevelPrefix = '';
		if (!empty($parentLevelPrefix)) {
			$this->fullLevelPrefix = ($parentLevelPrefix??'').'.';
		}
		$this->fullLevelPrefix .= $levelPrefix;
	}

	public function setLevel(int $level) {
		$this->_iLastLevel = $level;
	}

	/**
	 * 
	 * @param string $sForeachIndex
	 */
	public function setForeachIndex($sForeachIndex) {
		$this->_sForeachIndex = $sForeachIndex;
	}
	
	/**
	 * 
	 * @return string $sForeachIndex
	 */
	public function getForeachIndex() {
		return $this->_sForeachIndex;
	}

	/**
	 *
	 * @param string $sPrefix
	 */
	public function setPrefix($sPrefix) {
		$this->_sPlaceholderVariablePrefix = $sPrefix;
	}
	
	/**
	 *
	 * @return string $sPrefix
	 */
	public function getPrefix() {
		return $this->_sPlaceholderVariablePrefix;
	}
	
	/**
	 * Erzeugt einen CamelCase String
	 * 
	 * @param string $sPlaceholder
	 * @return string 
	 */
	protected function _getPlaceholderVariablePrefix($sPlaceholder) {
		
		return $sPlaceholder;
		
	}
	
	/**
	 * Gibt alle Fehler zurück
	 * 
	 * @return array 
	 */
	public function getErrors() {
		
		return $this->_aErrors;
		
	}

	/**
	 * Frontendübersetzung
	 *
	 * @deprecated
	 * @param string $sL10N
	 * @param string $sLang
	 * @return string 
	 */
	public function tf($sL10N, $sLang = '') {

		// Sprache muss übergeben werden
		if(empty($sLang)) {
			$sLang = $this->_sDisplayLanguage;
		}

		$sText = self::translateFrontend($sL10N, $sLang);

		return $sText;

	}

	/**
	 * Backendübersetzung
	 * 
	 * @param string $sText
	 * @return string 
	 */
	public function tb($sText) {

		$sText = L10N::t($sText, self::$_sL10N);

		return $sText;

	}

	/**
	 * Übersetzt einen Text mit Frondendübersetzungen
	 *
	 * @deprecated
	 * @param $sL10N
	 * @param $sLang
	 * @return string
	 */
	public static function translateFrontend($sL10N, $sLang) {
		$oLanguage = new \Tc\Service\Language\Frontend($sLang);
		return $oLanguage->translate($sL10N);
	}

	/**
	 * Gibt eine rekursiv erstellte Liste aller Platzhalter zurück
	 *
	 * @param bool $bUseCache
	 * @param bool $initialCall
	 * @return array
	 */
	final public function getPlaceholdersList(bool $bUseCache = true, bool $initialCall = true): array {

		$sCacheKey = 'tc_placeholder_list_'.get_class($this).'_'.$this->_oWDBasic->id;
		
		$aPlaceholders = WDCache::get($sCacheKey);

		// Wenn Cache nicht gefüllt
		if(
			$bUseCache !== true ||
			$aPlaceholders === null ||
			System::d('debugmode') == 2
		) {

			if ($initialCall) {
				// Gesammelte, verwendete variable_name Einträge löschen
				self::$used_placeholder_variable_name_list = [];
			}
			$this->setFlexiblePlaceholder();

			$aRelatives = $this->_getRelatives(true);

			foreach ($this->_aPlaceholders as $sPlaceholder => $aPlaceholder) {
				 if (isset($aRelatives[$sPlaceholder])) {
					$oPlaceholderObject = $aRelatives[$sPlaceholder]['placeholder'];
					if ($oPlaceholderObject instanceof self) {
						// Platzhalter nur in oberster Ebene cachen
						$position = array_search($sPlaceholder, self::$used_placeholder_variable_name_list);
						if ($position !== false) {
							$aPlaceholder['placeholders'] = [
								'...' => [
									'label' => 'Platzhalter siehe oben',
									'placeholder_scroll_id' => $position
								]
							];
						} else {
							self::$used_placeholder_variable_name_list[] = $sPlaceholder;
							// scroll Id setzen
							$aPlaceholder['placeholder_scroll_id'] = count(self::$used_placeholder_variable_name_list)-1;
							$aRelativePlaceholders = $oPlaceholderObject->getPlaceholdersList(false, false);
							$aPlaceholder['placeholders'] = $aRelativePlaceholders;
						}
					}

				}

				$aPlaceholders[$sPlaceholder] = $aPlaceholder;

			}

			if($bUseCache === true) {
				// Liste für eine Stunde cachen
				WDCache::set($sCacheKey, (1*60*60), $aPlaceholders, false, self::TC_PLACEHOLDER_CACHE_GROUP);
			}

		}

		return $aPlaceholders;
	}

	/**
	 * Löscht alle Platzhalter-Cache-Einträge
	 */
	static public function clearCache() {

		WDCache::deleteGroup(self::TC_PLACEHOLDER_CACHE_GROUP);

	}

	/**
	 * setzt die individuell angelegten Platzhalter (Gui-Designer, flexible Felder, ...)
	 * @param bool $bAssign
	 */
	public function setFlexiblePlaceholder($bAssign = false) {

		$this->fillPlaceholders();

        $aDynamicPlaceholders = $this->_getDynamicPlaceholders();
        if(!empty($aDynamicPlaceholders)) {
            $this->_aPlaceholders = $aDynamicPlaceholders + $this->_aPlaceholders;
        }

		if($this->_oFlexiblePlaceholderHelper === null) {
			$this->_prepareFlexiblePlaceholderHelper();
		}
		
		$oFlexiblePlaceholderHelper = $this->_oFlexiblePlaceholderHelper;

		$oFlexiblePlaceholderHelper->bAssignData			 = $bAssign;
		$oFlexiblePlaceholderHelper->aFlexibleFieldsSections = $this->_aFlexibleFieldsSections;
		$oFlexiblePlaceholderHelper->sGuiDesignerSectionKey	 = $this->_sGuiDesignerSectionKey;

		$aFlexiblePlaceholder = $oFlexiblePlaceholderHelper->getFlexiblePlaceholder($this->_oWDBasic);

		if(!empty($aFlexiblePlaceholder)) {				
			foreach($aFlexiblePlaceholder as $aPlaceholder) {
				$this->_aPlaceholders = $aPlaceholder + $this->_aPlaceholders;
			}				
		}
		
	}

    /**
     * @return array
     */
    protected function _getDynamicPlaceholders() {
        return array();
    }
	
	/**
	 * generiert eine Instanz der Helper-Klasse für flexible Platzhalter
	 */
	protected function _prepareFlexiblePlaceholderHelper() {
		if($this->_oFlexiblePlaceholderHelper === null) {
			$oFlexiblePlaceholderHelper = new Ext_TC_Placeholder_Helper_Flexible($this->_oSmarty);
			$oFlexiblePlaceholderHelper->setObjects($this->_aObjects);

			$this->_oFlexiblePlaceholderHelper = $oFlexiblePlaceholderHelper;
		}
	}


    /**
     * Prapare and display the placeholder table
     *
     * @param string $sType
     * @return string
     */
	public function displayPlaceholderTable($sType = '') {
		
		$aPlaceholders = $this->getPlaceholdersList();

		$sHtml = $this->_printPlaceholderList($aPlaceholders, $sType);

		return $sHtml;
	}

	/**
	 * Liefert alle definierten Platzhalter. Über die Methode können auch Platzhalter dynamisch generiert werden
	 * 
	 * @return array
	 */
	public function getPlaceholders() {
		return $this->_aPlaceholders;
	}

	public function getPlaceholder($sPlaceholder) {
		$this->fillPlaceholders();
		return $this->_aPlaceholders[$sPlaceholder];
	}

    /**
     * Erzeugt rekursiv den Inhalt der Platzhaltertabelle
     *
     * @param string $aPlaceholders
     * @param string $sType
     * @param int $iLevel
     * @return string
     */
	final protected function _printPlaceholderTableContent($aPlaceholders, $sType = '', $iLevel = 1) {
		
		$sHtml = '';
		
		// Setzt das höchste Level
		$this->_iPlaceholderTableMaxLevel = max($this->_iPlaceholderTableMaxLevel, $iLevel);

		static $aIdStack;
		if(empty($aIdStack)) {
			$aIdStack = array(
				'current_level' => 0,
				'last_level_id' => 1,
				'ids' => array(
					1 => 0
				)
			);
		}

		foreach((array)$aPlaceholders as $sKey => $aPlaceholder) {
			
			// Unsichtbare / ungültige Platzhalter nicht (mehr) anzeigen
			if ($this->isInvisible($sKey, $aPlaceholder)) {
				continue;
			}

			$sTrClass = 'placeholderTableRow';
			$labelAddon = null;
			// Je nach Typ anders darstellen (mit CSS-Klassen)
			switch($aPlaceholder['type']) { 
				case 'loop':
					$sPlaceholder = '{'.$sKey.'} ... {/'.$sKey.'}';
					$sTrClass .= ' loop';
					break;
				case 'parent':
					$sPlaceholder = '';
					$sTrClass .= ' parent';
					$labelAddon = '{'.$sKey.'…}';
					break;
				default:
					$sPlaceholder = '{'.$sKey.'}';
					if(!isset($aIdStack['ids'][$iLevel])) {
						$aIdStack['ids'][$iLevel] = 0;
					}
					++$aIdStack['ids'][$iLevel];
					break;
			}

			$iDataLevel = $iLevel;
			
			// Verschachtelung bestimmen, um daraus die ID bauen zu können
			if($iLevel > $aIdStack['current_level']) {
				$aIdStack['ids'][$iLevel] = 1;
				if(!isset($aIdStack['ids'][$iLevel - 1])) {
					$aIdStack['ids'][$iLevel - 1] = 0;
				}
				++$aIdStack['ids'][$iLevel - 1] ;
			}
			$aIdStack['current_level'] = $iLevel;
			
			// ID zusammenbauen
			$sId = '';
			for($iCount = 0; $iCount < $aIdStack['current_level']; ++$iCount) {
				$iId = $aIdStack['ids'][$iCount + 1];
				if($iCount > 0) {
					$sId .= '_';
				}
				
				// Für Parents und Loops die ID temporär erhöhen, damit die Verschachtelung weiter stimmt
				if(
					$iCount === $aIdStack['current_level'] - 1 &&
					(
						$aPlaceholder['type'] === 'loop' ||
						$aPlaceholder['type'] === 'parent'
					)
				) {
					++$iId;
				}
				
				$sId .= 'tr'.$iId;
			}

			$sHtml .= '<tr id="'.$sType.'_'.$sId.'" class="'.$sTrClass.'" data-level="'.$iDataLevel.'">';
			
			for($i=0; $i<$iLevel; $i++) {
				$sHtml .= '<td class="indent"></td>';
			}
			
			// Prüft, ob das Label von dem Platzhalter übersetzt werden soll
			$sLabel = $aPlaceholder['label'];
			if(
				!isset($aPlaceholder['translate_label']) ||
				$aPlaceholder['translate_label'] == true
			) {
				$sLabel = $this->tb($sLabel);
			}
			// scrollTo Elemente erst nach Übersetzung anbinden
			if ($sKey == '...') {
				$sLabel = "<div class=\"scrollButton\" onclick=\"scrollToPlaceholder('".$aPlaceholder['placeholder_scroll_id']."')\">".$sLabel." &nbsp;&uArr;</div>";
			} else {
				$sLabel  = '<span id = "placeholderScrollId'.$aPlaceholder['placeholder_scroll_id'].'"></span>'.$sLabel ;
			}
			if($labelAddon !== null) {
				$sLabel .= ' '.$labelAddon;
			}

			if(!empty($sPlaceholder)) {
				$sHtml .= '<td colspan="[CS'.$iLevel.']">'.$sPlaceholder.'</td>';
				$sHtml .= '<td>' . $sLabel . '</td></tr>';
			} else {
				$sHtml .= '<td colspan="[CS'.($iLevel-1).']">' . $sLabel . '</td></tr>';
			}

			if(!empty($aPlaceholder['placeholders'])) {
				$sHtml .= $this->_printPlaceholderTableContent($aPlaceholder['placeholders'], $sType, ($iLevel+1));
			}

		}
		
		return $sHtml;
		
	}
	
	/**
	 * Get the placeholder table HTML code
	 * 
	 * @param array $aPlaceholders
	 * @return string
	 */
	final protected function _printPlaceholderList($aPlaceholders, $sType = '', $aFilter=array()) {

		$sHtml = $this->_printPlaceholderTableContent($aPlaceholders, $sType);

		$sHtml .= '</table>';
		
		$sHtmlHead = '';

		$sHtmlHead .= '<table id="'.$sType.'_palceholder_table" cellspacing="0" cellpadding="2" style="width:100%;" class="table table-bordered highlightRows placeholdertable">';

		$sHtmlHead .= '
			<colgroup>
		';	
		
		$iWidth = 300 - ($this->_iPlaceholderTableMaxLevel+1) * 6;

		// Alle Ebenen durchlaufen und Colspan setzen
		for($i=0; $i < $this->_iPlaceholderTableMaxLevel; $i++) {
			$iColspan = $this->_iPlaceholderTableMaxLevel-$i+1;
			
			$sHtml = str_replace('[CS'.$i.']', $iColspan, $sHtml);
			$sHtmlHead .= '<col style="width:6px;" />';
		}
		
		$sHtmlHead .= '
				<col style="width:'.$iWidth.'px;" />
				<col style="width:auto;" />
			</colgroup>
		';
		
		$sHtmlHead .= '
			<tr class="placeholderTableHeader">
				<th style="line-height:18px;" colspan="'.($this->_iPlaceholderTableMaxLevel+1).'">' . $this->tb('Platzhalter') . '</th>
				<th style="line-height:18px;">' . $this->tb('Beschreibung') . '</th>
			</tr>
		';
		
		$sHtml = $sHtmlHead.$sHtml;
		
		return $sHtml;

	}

	protected function isInvisible(string $placeholder, array $config): bool
	{
		if (isset($config['invisible'])) {
			if (is_bool($config['invisible'])) {
				return $config['invisible'];
			} else if (is_callable($config['invisible'])) {
				return $config['invisible']($this->_oWDBasic, $placeholder);
			}
		}

		return false;
	}

	final protected function fillPlaceholders(): void
	{
		if (empty($this->_aPlaceholders)) {
			// Um dynamische Platzhalterkonfigurationen zu ermöglichen, dürfen die Platzhalter nicht in $this->_aPlaceholders
			// definiert werden. Stattdessen können sie über getPlaceholders() dynamisch erzeugt werden – diese Methode wird
			// nur einmal aufgerufen.
			$this->_aPlaceholders = $this->getPlaceholders();
		}
	}

}
