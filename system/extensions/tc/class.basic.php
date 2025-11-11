<?php

class Ext_TC_Basic extends WDBasic {

	use Tc\Traits\Flexibility, Tc\Traits\Placeholder;
	
	/**
	 * @var array
	 */
	protected $_aMappingClass = array();

	/**
	 * @var array
	 */
	public $_timestamps	= array();

	/**
	 * @var string
	 */
	protected $_sEditorIdColumn = 'editor_id';

	/**
	 * I18N-List cache array
	 * 
	 * @var array
	 */
	protected static $_aArrayListI18NCache;

	/**
	 * Werte für GUI Design Elemente die über ein Formular Object gesetzt werden
	 *
	 * @var array
	 */
	protected $_aGuiDesignerElementValues = array();

	/**
	 * Flag um den Changed Status zu definieren
	 *
	 * @var bool|null
	 */
	protected $_bChanged = null;

	/**
	 * Objekt nicht im Index aktualisieren (beim Speichern)
	 *
	 * @var bool
	 */
	public $bUpdateIndexEntry = true;

	/**
	 * Die "Updated by" und "Updated on" Spalten auch aktualisieren wenn sich nichts geändert hat (z.B. wenn
	 * ein Dialog gespeichert wird, aber nur Beziehungen geändert wurden).
	 *
	 * @see Ext_TC_Basic::checkUpdateUser()
	 * @var bool
	 */
	protected $bForceUpdateUser = false;

	/**
	 * `editor_id` bei Änderung verändern oder behalten
	 *
	 * @var bool
	 */
	protected $bKeepCurrentEditor = false;

	/**
	 * @param int $iDataID
	 * @param string|null $sTable
	 */
	public function __construct($iDataID = 0, $sTable = null) {
		
		if($this->_timestamps) {
			foreach((array)$this->_timestamps as $sField) {
				$this->_aFormat[$sField] = array('format'=>'TIMESTAMP');
			}
		}

		// Call parent constructor with auto format
		parent::__construct($iDataID, $sTable, true);
		
	}
	

    /**
     * Wird unteranderem für den index benutzt
	 *
     * @return string
     */
    public function getCreatedForIndex() {

        $iCreated = $this->created;
        $sCreated = strftime('%Y-%m-%dT%H:%M:%S', $iCreated);

        return $sCreated;
    }
    
    /**
     * Wird unteranderem für den index benutzt
	 *
     * @return string
     */
    public function getChangedForIndex() {

        $iChanged = $this->changed;
        $sChanged = strftime('%Y-%m-%dT%H:%M:%S', $iChanged);

        return $sChanged;
    }

	public function getTimeStampForIndex($sField) {

        $iTimestamp = $this->$sField;
		
		if($iTimestamp === null) {
			return null;
		}
		
        $sTimestamp = strftime('%Y-%m-%dT%H:%M:%S', $iTimestamp);

        return $sTimestamp;
    }
	
	/**
	 * Migration: getData()
	 *
	 * @deprecated
	 * @return array
	 */
	public function getArray() {
		return $this->_aData;
	}

	/**
	 * Diese Methode sollte bei Vorhandensein explizit abgeleitet werden.
	 * @deprecated
	 *
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @todo $bLog Entweder benutzen oder entfernen
	 * @global array $user_data
	 * @param bool $bLog
	 * @return \Ext_TC_Basic 
	 */
	public function save($bLog = true) {
		global $user_data;

		$bInsert = $this->isNew();

		if(
			!$this->bKeepCurrentTimestamp &&
			$this->checkUpdateUser()
		) {

			if(isset($user_data['id']) && !$this->bKeepCurrentEditor) {
				$this->_aData[$this->_sEditorIdColumn] = $user_data['id'];
			}

			//wenn Bearbeiter aktualisiert wird, dann Changed auch ändern, auch wenn es sich um den
			//gleichen Benutzer handeln sollte, sonst sieht dass mysql nicht als Veränderung
			//ist eingebaut für den Fall dass nur joinedtables sich verändern
			if(array_key_exists('changed', self::$_aTable[$this->_sTable]))
			{
				//Flag damit man den Current_Timestamp überschreiben darf
				$this->_bOverwriteCurrentTimestamp = true;

				$oDate = new WDDate();
				$this->_aData['changed'] = $oDate->get(WDDate::DB_TIMESTAMP);
			}
		}

		$bChanged = $this->isChanged();

		// IntersectionData muss für den Log vorher gemacht werden, da save() _aOriginalData überschreibt
		$aIntersectionData = [];
		if(
			$bLog &&
			$bChanged
		) {
			$aIntersectionData = $this->getIntersectionData();
		}

		$mReturn = parent::save();

		// Log entry
		if($bLog) {

			if($bInsert) {
				$sAction = Ext_TC_Log::ADDED;
			} else {
				$sAction = Ext_TC_Log::UPDATED;
			}

			$this->log($sAction, $aIntersectionData);

		}

		//ArrayList Cache leeren
		WDCache::delete($this->_getArrayListCacheKey(true));
		WDCache::delete($this->_getArrayListCacheKey(false));
		
		$this->_saveGuiDesignerElementValues();

		// Flex-Werte speichern, falls vorhanden
		$this->saveFlexValues();

        // nur wenn keine Fehler da waren
		if(!is_array($mReturn)) {
            $this->updateIndexStack($bInsert, $bChanged);
        }

		return $mReturn;
	}
	
	/**
	 * prüft ob das Objekt sich geändert hat
	 *
	 * @param string $sField
	 * @return boolean 
	 */
	public function isChanged($sField = '') {
        
        $bChanged = false;
		
		if(empty($sField)){
            
            $aIntersectionData	= $this->getIntersectionData();

            if(
                $aIntersectionData !== null &&
                !empty($aIntersectionData)
            ){
                $bChanged = true;
            }
            
        } else if((string)$this->getData($sField) !== (string)$this->getOriginalData($sField)) {
			// Identisch vergleichen ohne Typsicherheit (WDBasic macht aus allem Strings) macht wenig Sinn
            $bChanged = true;
        }
		
		return $bChanged;
	}

	/**
	 * update the Index Stack if needed
	 *
	 * @param bool $bInsert
	 * @param bool $bChanged
	 * @return bool
	 */
    public function updateIndexStack($bInsert = false, $bChanged = false) {

		if($this->bUpdateIndexEntry) {
			if(
				$bChanged !== true &&
				!$bInsert
			) {
				return true;
			}

			Ext_Gui2_Index_Registry::insertRegistryTask($this);
			
		}

		return true;
    }

    /**
	 * speichert die (evt.) gesetzten Werte für GUI Design Elementer
	 * wird für das Frontend Formular gebraucht
	 */
	protected function _saveGuiDesignerElementValues() {

		// Werte holen
		$aValues = $this->_aGuiDesignerElementValues;
		// Wenn nicht leer
		if(!empty($aValues)){
			
			// Query vorbereiten
			$sSql = " 
				REPLACE INTO 
					`tc_gui2_designs_tabs_elements_values`
				SET
					`element_id` = :element_id,
					`entry_id` = :entry_id,
					`additional_id` = :additional_id,
					`additional_class` = :additional_class,
					`value` = :value";
			
			// Daten durchgehen
			foreach((array)$aValues as $iElement => $aEntries){
				
				if($aEntries['value'] === '') {
					continue;
				}
				
				$iElement			= $aEntries['element_id'];
				$iEntry				= $aEntries['entry']->id;
				$iAdditionalId		= 0;
				$sAdditionalClass	= '';
				
				// Wenn es ein Kind gibt ( wiederholbarer bereich )
				if($aEntries['additional']){
					$iAdditionalId		= $aEntries['additional']->id;
					$sAdditionalClass	= $aEntries['additional']->getClassName();
				}
	
				// Query abfeuern
				$aSql = array(
					'element_id'		=> (int)$iElement,
					'entry_id'			=> (int)$iEntry,
					'additional_id'		=> (int)$iAdditionalId,
					'additional_class'	=> (string)$sAdditionalClass,
					'value'				=> (string)$aEntries['value']
				);
				
				DB::executePreparedQuery($sSql, $aSql);
			}
		}

	}

	/**
	 * Prüft ob User_id neu geschrieben werden darf
	 *
	 * @return bool
	 */
	public function checkUpdateUser() {

		global $user_data;

		$bCheck = false;

		// Wenn die Spalte existiert die User ID setzen
		if(
			array_key_exists($this->_sEditorIdColumn, (array)self::$_aTable[$this->_sTable]) &&
			!empty($user_data) &&
			$user_data['id'] > 0 &&
			$user_data['cms'] &&
			$this->_aData[$this->_sEditorIdColumn] != -1	// Systembenutzer
		) {

			$bCheck = true;

			// Welche Daten wurden verändert?
			$aDataDiff = $this->getIntersectionData();

			// Wenn keine Änderung oder nur Reihenfolge verändert, Bearbeiter nicht verändern ...
			if(
				(
					count($aDataDiff) == 1 &&
					array_key_exists('position', $aDataDiff)
				) ||
				empty($aDataDiff)
			) {
				$bCheck = false;
			}

			// ... es sei denn der Bearbeiter soll auf jeden Fall aktualisiert werden
			if($this->bForceUpdateUser) {
				$bCheck = true;
			}

		}

		return $bCheck;

	}

	/**
	 * @param string $sName
	 * @param mixed $mValue
	 * @throws Exception
	 */
	public function __set($sName, $mValue) {

		if(
			is_string($mValue) /*||
			is_numeric($mValue)*/
		) {
			$mValue = trim($mValue);
		}

		parent::__set($sName, $mValue);

	}
	
    protected static $iTemp = 0;

	/**
	 * @param string $sName
	 * @return mixed
	 * @throws ErrorException
	 * @throws Exception
	 */
    public function __get($sName) {

		$mValue = parent::__get($sName);

		// @TODO: Entfernen (manche Ext_TC_Basic-Ableitungen rufen deswegen auch direkt WDBasic::__get() auf)
        Ext_Gui2_Index_Registry::set($this);

		return $mValue;
	}

	/**
	 * Funktion, um Namensfelder über Sprachtabellen beziehen zu können
	 * 
	 * In $aFields können mehrere Felder angegeben werden, damit man 
	 *	beispielsweise Name und Abkürzung direkt bekommt. 
	 * Dies kann man dann durch eine Formatklasse schleusen!
	 * 
	 * Der Tabellenname muss ein i18n beinhalten!
	 * 
	 * @param array $aFields Die Datenbankfelder
	 * @param bool $bForSelect Formatiert die Arraykeys zur ID für Selects
	 * @param string $sIso_639_1 ISO-Code der Sprache für die I18N
	 * @return mixed
	 * @throws Exception
	 * @since 01.06.2011
	 */
	public function getArrayListI18N(array $aFields, $bForSelect = false, $sIso_639_1 = '', $bIgnorePosition = false) {

		$sCacheKey = get_class($this) . '_' . implode('_', $aFields) . '_' . (int)$bForSelect . '_' . $sIso_639_1;
		
		if(!empty(self::$_aArrayListI18NCache[$sCacheKey]))
		{
			return self::$_aArrayListI18NCache[$sCacheKey];
		}

		if(empty($sIso_639_1)){
			$sIso_639_1 = Ext_TC_System::getInterfaceLanguage();
		}
		
		if(empty($aFields)) {
			throw new Exception('No fields given!');
		}
		
		$bIsSingleField = count($aFields) === 1;
		
		$aObjects = $this->getObjectList();
		$mResult = array();
		$mTempResult = array();
		
		foreach($aObjects as $oObject) {

			foreach($oObject->_aJoinTables as $aJoinTable) {
				
				if(!mb_strpos($aJoinTable['table'], 'i18n')) {
					continue;
				}
				
				$aSql = array(
					'field' => $aFields[0],
					'table' => $aJoinTable['table'],
					'parent_key' => $aJoinTable['primary_key_field'],
					'parent_id' => $oObject->_aData['id'],
					'iso' => $sIso_639_1
				);
				
				if(!$bIsSingleField) {
					
					$i = 2;
					$aInsertFields = array();
					
					foreach($aFields as $sField) {
						$aSql['field_'.$i] = $sField;
						$aInsertFields[] = '#field_'.$i;
						$i++;
					}
					
					$sInsertFields = ' ,'.implode(',', $aInsertFields);
					
				} else {
					$sInsertFields = '';
				}
				
				$sSql = "
					SELECT
						#field " . $sInsertFields . "
					FROM
						#table
					WHERE
						#parent_key = :parent_id AND
						`language_iso` = :iso
				";

				if(
					$bForSelect && 
					$bIsSingleField
				) {
					$sSql .= "
						ORDER BY
							#field
					";
				}
				
				$aResult = array();
				$aResultQuery = DB::getQueryRow($sSql, $aSql);
				
				if(!is_array($aResultQuery)) {
					$aResultQuery = array();
				}
				$aResult = array_merge($oObject->_aData, $aResultQuery);

				if($bIsSingleField) {
					$mResult[$oObject->_aData['id']] = $aResult[$aFields[0]];
				} else { 
					
					if($bForSelect) {
						$mResult[$oObject->_aData['id']] = $aResult[$aFields[0]];
					} else {
						$mResult[] = $aResult;
					}
					
				}
				
			}
			
		}
		
		if(
			isset($oObject) &&
			$oObject instanceof WDBasic &&
			$bForSelect && 
			(	
				!array_key_exists('position', $oObject->_aData) ||
				$bIgnorePosition
			)
		){
			asort($mResult);
		}

		self::$_aArrayListI18NCache[$sCacheKey] = $mResult;

		return $mResult;
				
	}

	/**
	 * Liefert ein Array mit allen Objekten dieser Entität
	 *
	 * Diese Methode ist veraltet! Repository benutzen!
	 *
	 * @deprecated
	 * @param bool $bCheckValid
	 * @return static[]
	 */
	public static function getObjectList($bCheckValid = true) {

		$oSelf = new static();

		$sSql = $oSelf->_getSqlForList($bCheckValid);
		
		$aSql = array('table' => $oSelf->_sTable);
		
		$aResult = DB::getQueryRows($sSql, $aSql);
		
		$aBack = array();

		$sClassName = get_class($oSelf);

		foreach((array)$aResult as $aData) {
			$iId = (int)$aData['id'];
			$aBack[$iId] = call_user_func(array($sClassName, 'getObjectFromArray'), $aData);
		}

		return $aBack;
	}

	/**
	 * @return Collection
	 */
    static public function getCollection() {
        
        $sClass = get_called_class();
   
        $oTemp = new $sClass();
        // jaja "eig" geht das nicht ;) aber dank php schon :P
        // (protected von ausen öffnen)
        $sSql = $oTemp->_getSqlForList();
		
		$aSql = array('table' => $oTemp->_sTable);
		
        $oDB = DB::getDefaultConnection();
		$aResult = $oDB->getCollection($sSql, $aSql);
        
        return $aResult;
    }

	/**
	 * @param string $sJoinTable
	 * @param string $sField
	 * @param null $sLanguage
	 * @return string
	 * @throws Exception
	 */
	public function getI18NName($sJoinTable, $sField, $sLanguage=null) {

		if(
			$sLanguage === null ||
			empty($sLanguage)
		) {
			$sLanguage = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getInterfaceLanguage');
		}

		$aJoinData = (array)$this->$sJoinTable;
		
		foreach($aJoinData as $aLanguage) {
			if($aLanguage['language_iso'] == $sLanguage) {
				$sReturn = (string)$aLanguage[$sField];
				return $sReturn;
			}
		}
		
		return '';
	}

	/**
	 * @param string $sValue
	 * @param string $sLanguage
	 * @param string $sField
	 * @param string $sJoinTable
	 */
	public function setI18NName($sValue, $sLanguage = null, $sField = 'name', $sJoinTable = 'i18n'){

		if(
			$sLanguage === null ||
			empty($sLanguage)
		) {
			$sLanguage = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getInterfaceLanguage');
		}

		$aJoinData = (array)$this->$sJoinTable;
		$bFound = false;

		foreach($aJoinData as $iKey => $aLanguage) {
			if($aLanguage['language_iso'] == $sLanguage) {
				$aJoinData[$iKey][$sField] = $sValue;
				$bFound = true;
				break;
			}
		}
		
		if(!$bFound){
			// wenn nicht gefunden ergänzen
			$aJoinData[] = array('language_iso' => $sLanguage, $sField => $sValue);
		}

		$this->$sJoinTable = $aJoinData;
		
	}

	/**
	 * Vergleicht ob das Object gültig ist bezogen auf sein Valid until + active
	 *
	 * @param string|null $sDbDate
	 * @return bool
	 */
	public function isValid($sDbDate = null) {

		if(empty($sDbDate)) {
			$iTimestamp = time();
		} else {
			if(WDDate::isDate($sDbDate, WDDate::DB_DATE)) {
				$oDate = new WDDate($sDbDate, WDDate::DB_DATE);
				$iTimestamp = $oDate->get(WDDate::TIMESTAMP);
			} else {
				$iTimestamp = time();
			}
		}

		if(
			array_key_exists('active', $this->_aData) &&
			$this->active == 0
		) {
			return false;
		}
	
		if(
			array_key_exists('valid_until', $this->_aData) &&
			!array_key_exists('valid_from', $this->_aData)
		) {
			
			$oWDDate = new WDDate($this->valid_until, WDDate::DB_DATE);
			$oWDDate->set('23:59:59', WDDate::TIMES);
			$iCompare = $oWDDate->compare($iTimestamp, WDDate::TIMESTAMP);

			if($iCompare < 0){
				return false;
			}

		} else if(
			array_key_exists('valid_until', $this->_aData) &&
			array_key_exists('valid_from', $this->_aData)
		) {

			$oWDDate = new WDDate($this->valid_from, WDDate::DB_DATE);
			$iFrom =  $oWDDate->get(WDDate::TIMESTAMP);
			
			if($this->valid_until != "" && $this->valid_until != '0000-00-00'){
				$oWDDate->set($this->valid_until, WDDate::DB_DATE);
				$iTo = $oWDDate->get(WDDate::TIMESTAMP);
				$bBetween = Ext_TC_Util::between($iTimestamp, $iFrom, $iTo);
			} else {
				if($iFrom <= $iTimestamp){
					$bBetween = true;
				}
			}

			if(!$bBetween){
				return false;
			}
		}

		return true;
	}
	
	/**
	 * Diese Methode liefert ein Child zurück, bei welchem der Feldwert zutrifft.
	 * 
	 * @param string $sKey Key des Childs
	 * @param string $sField Name des Datenbankfeldes
	 * @param mixed $mValue Wert
	 * @return Ext_TC_Basic|null
	 */
	public function getJoinedObjectChildByValue($sKey, $mField, $mValue=null) {

		$aValues = [];
		if(is_string($mField)) {
			$aValues[$mField] = $mValue;
		} else {
			$aValues = $mField;
		}
		
		$mReturn = null;
		$aChilds = $this->getJoinedObjectChilds($sKey, true);
		
		foreach((array)$aChilds as $oObject) {
			$bMatch = true;
			foreach($aValues as $sField=>$mValue) {
				if($oObject->$sField != $mValue) {
					$bMatch = false;
				}	
			}
			if($bMatch === true) {
				$mReturn = $oObject;
				break;
			}
		}
		
		return $mReturn;
	}
	
	/**
	 * Diese Methode liefert ein Child zurück, bei welchem der Feldwert zutrifft.
	 * 
	 * @param string $sKey Key des Childs
	 * @param string $sField Name des Datenbankfeldes
	 * @param mixed $mValue Wert
	 * @return Ext_TC_Basic|null
	 */
	public function getJoinTableObjectsByValue($sKey, $sField, $mValue) {

		$mReturn = null;
		$aChilds = $this->getJoinTableObjects($sKey);
		
		foreach((array)$aChilds as $oObject) {
			if($oObject->$sField == $mValue) {
				$mReturn = $oObject;
				break;
			}
		}
		
		return $mReturn;
	}

	/**
	 * @param $sKey
	 * @param $mField
	 * @param $mValue
	 *
	 * @return mixed|WDBasic
	 * @throws Exception
	 */
	public function getJoinedObjectChildByValueOrNew($sKey, $mField, $mValue=null) {

		$aValues = [];
		if(is_string($mField)) {
			$aValues[$mField] = $mValue;
		} else {
			$aValues = $mField;
		}
		
		$oChild = $this->getJoinedObjectChildByValue($sKey, $aValues);

		if($oChild === null) {
			$oChild = $this->getJoinedObjectChild($sKey);
			foreach($aValues as $sField=>$mValue) {
				$oChild->$sField = $mValue;
			}
		}

		return $oChild;
	}

	/**
	 *
	 * @param string $sType
	 * @return Ext_TC_Index_Mapping_Abstract 
	 */
	public function getMapping($sType) {

		if(
			isset($this->_aMappingClass[$sType])
		) {
			$sClass		= $this->_aMappingClass[$sType];
			$oMapping	= new $sClass(get_class($this), $sType);
	
			if(
				$oMapping instanceof Ext_TC_Mapping_Abstract
			){
				return $oMapping;
			}
			
		}
		
		return false;
	}
	
	/**
	 * Get all Mapping Field from self
	 *
	 * @param string $sMappingType
	 * @return array 
	 */
	public function getMappingFields($sMappingType) {
		
		$oMapping = $this->getMapping($sMappingType);
		$aSchema = $oMapping->getMappingSchema(true);
		
		return $aSchema;
	}
	
	/**
	 * @return array 
	 */
	public function getTableFields() {
		return self::$_aTable;
	}

	/**
	 * Speichert die Werte für die gui design felder
	 *
	 * @param $iElement
	 * @param string $mValue
	 * @param bool|WDBasic $oParentEntity
	 */
	public function setGuiDesignerValue($iElement, $mValue, $oParentEntity = false){
		
		$sEntityClass		= get_class($this);
		
		$sParentEntityClass	= '';
		if($oParentEntity){
			$sParentEntityClass = $oParentEntity->getClassName();
		}
		
		$oEntry				= $this;
		$oAdditional		= false;
		
		if(
			$sEntityClass != $sParentEntityClass && 
			!empty($sParentEntityClass)
		){
			$oAdditional		= $this;
			$oEntry				= $oParentEntity;
		}

		$this->_aGuiDesignerElementValues[$iElement] = array(
			'additional'	=> $oAdditional,
			'entry'			=> $oEntry,
			'element_id'	=> $iElement,
			'value'			=> $mValue
		);
		
	}

	/**
	 * @param int $iElement
	 * @return string
	 */
	public function getGuiDesignerValue($iElement){
		$aValue = $this->_aGuiDesignerElementValues[$iElement];
		return (string)$aValue['value'];
	}
	
	/**
	 * Liefert den Namen der Klasse für die Darstellung
	 *
	 * @param bool $bPlural
	 * @return string
	 * @throws Exception
	 */
	public static function getClassLabel($bPlural = false) {
		throw new Exception('Please overwrite!');
	}

	static protected $_aChildDataCache = array();

	/**
	 * @TODO Entfernen, wird nur noch beim Import benötigt
	 * @internal
	 *
	 * gibt die Informationen zu einem Kind zurück
	 *  hierbei ist es egal ob joined object oder jointable
	 * @param WDBasic $oChild
	 */
	public function getChildData($oChild) {
		
        if($oChild instanceof Ext_TC_Import_NonBasic_Entity) {
            return $oChild->getChildData();
        }
        
		$sChild = get_class($oChild);
		$aData  = null;
		$aCache	= self::$_aChildDataCache;
		
		if(!isset($aCache[$sChild])){
			
			foreach($this->_aJoinedObjects as $sKey => $aObject){
				if($aObject['class'] == $sChild){
					$aData = array();
					if($aObject['type'] != 'child'){
						$aData['type'] = 'joinedobject_parent';
					} else {
						$aData['type'] = 'joinedobject_child';
					}
					$aData['key']	= $sKey;
					$aData['data']	= $aObject;
				}
			}
			
			if($aData === null){
				foreach($this->_aJoinTables as $sKey => $aTable){
					if($aTable['class'] && $aTable['class'] == $sChild){
						$aData = array();
						$aData['type']	= 'jointable';
						$aData['key']	= $sKey;
						$aData['data']	= $aTable;
					}
				}
			}
			
			
			self::$_aChildDataCache[$sChild] = $aData;
		}
		
		return self::$_aChildDataCache[$sChild];
	}
	
	/**
	 * Basis Query Part für den Index
	 * 
	 * @param Ext_Gui2 $oGui
	 * @return array 
	 */
	public function getListQueryDataForIndex($oGui = null) {
		$aQueryData = $this->getListQueryData($oGui);
		return $aQueryData;
	}

	/**
	 * Speichern loggen
	 *
	 * @param string $sAction
	 * @param array $aIntersectionData
	 */
	public function log($sAction, $aIntersectionData = []) {
		Ext_TC_Log::logEntityAction($this, $sAction, $aIntersectionData);
	}

	/**
	 * @return array|bool|mixed
	 */
	public function delete() {

		if(!$this->hasActiveField()) {
			// Falls kein "active" Feld da ist, kommt die parent delete() nicht in die save() Methode rein,
			// das macht Problemen bei Objekten die mit Indexen verknüpft sind
			$this->updateIndexStack(false, true);
		}

		$bSuccess = parent::delete();

		// Log entry
		if($bSuccess) {
			if($this->bPurgeDelete) {
				$this->deleteFlexValues();

				// Aus Index löschen
				// delete() mit purge ruft nicht save() auf und nach delete() gibt es das Objekt für getInstance() auch nicht mehr
				$aMapping = Factory::executeStatic('Ext_Gui2', 'getIndexEntityMapping');
				if(isset($aMapping[$this->getClassName()])) {
					$oGenerator = new Ext_Gui2_Index_Generator($aMapping[$this->getClassName()]);
					$oGenerator->deleteIndexEntry($this);
				}
			}
			$this->log(Ext_TC_Log::DELETED);
		}

		return $bSuccess;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getIndexData() {
		return $this->getData();
	}
	
	/**
	 * Wird vor setCommunicationFlags() ausgeführt und dient
	 * dafür, dass vor dem setzen der Makierung für die Kommunikation
	 * noch Dinge ausgeführt werden können
	 *
	 * @param array $aFlags
	 * @param array $aEmail
	 * @param array $aErrors
	 */
	public function prepareCommunicationFlags($aFlags, &$aEmail, &$aErrors) {
		
	}

	/**
	 * @inheritdoc
	 *
	 * Ableitung, damit die Werte der FlexFelder kopiert werden
	 */
	public function createCopy($sForeignIdField = null, $iForeignId = null, $aOptions = array()) {
		
		DB::begin('Ext_TC_Basic::createCopy');
		
		$oClone = parent::createCopy($sForeignIdField, $iForeignId, $aOptions);
		
		// Werte aus flexiblen Feldern kopieren
		$aFlexValues = $this->getFlexValues();

		if(!empty($aFlexValues)) {
			foreach($aFlexValues as $iFlexFieldId=>$mValue) {
				$oClone->setFlexValue($iFlexFieldId, $mValue);
			}
			$oClone->saveFlexValues();
		}

		DB::commit('Ext_TC_Basic::createCopy');
		
		return $oClone;
	}

	/**
	 * WDBasic-Wert für Index holen
	 *
	 * 0 oder '' sind in ES immer noch Werte, müssen aber nicht im Index stehen.
	 * Zusätzlich funktioniert 0000-00-00 bei Datumsfeldern nicht und '' wird ab Elasticsearch 5 ebenso oben sortiert.
	 * Das würde korrekt ohne Wrapper funktionieren wenn die DB-Spalten entsprechend NULL und nicht 0 enthielten.
	 *
	 * @param string $sField
	 * @return mixed|null
	 */
	public function getValueForIndex($sField) {

		if(
			empty($this->$sField) ||
			$this->$sField === '0000-00-00'
		) {
			return null;
		}

		return $this->$sField;

	}

	/**
	 * Beliebter Fall in Thebing: Updated verändern, obwohl sich überhaupt nichts an der Entität verändert hat
	 *
	 * save()/validate() umgehen, da das mehr Probleme schafft als löst
	 */
	public function updateChangedData() {

		$oUser = System::getCurrentUser();

		$sSql = "
			UPDATE
				`{$this->_sTable}`
			SET
				`{$this->_sEditorIdColumn}` = :user_id,
				`changed` = NOW()
			WHERE
				`id` = :id
		";

		DB::executePreparedQuery($sSql, [
			'id' => $this->id,
			'user_id' => $oUser->id
		]);

	}

	/**
	 * `editor_id` bei Änderung verändern oder behalten
	 */
	public function disableUpdateOfEditor() {
		$this->bKeepCurrentEditor = true;
	}

}
