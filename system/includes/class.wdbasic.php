<?php

use Core\Traits\WdBasic\DefinedAttributeTrait;
use Illuminate\Database\Eloquent\Concerns;

/**
 * @param array $aData
 * @param int $id
 */
class WDBasic
{
	use DefinedAttributeTrait,
		Concerns\HasGlobalScopes,
		Concerns\HasEvents,
		\Core\Traits\WdBasic\HasEntityLock;

	/**_
	 * The data array
	 * 
	 * @var array
	 */
	protected $_aData = array();

	/**
	 * The DB table name
	 * 
	 * @var string
	 */
	protected $_sTable;

	/**
	 * Alias der Tabelle (Optional)
	 * @var <string> 
	 */
	protected $_sTableAlias = '';

	/**
	 * Felder die bei getArrayList gecached werden sollen, wenn leer, dann werden alle Felder gecached
	 * @var array
	 */
	protected $_aListCacheFields = array();

	/**
	 * The array of global scopes on the model.
	 *
	 * @var array
	 */
	protected static $globalScopes = [];

	/**
	 * Manuell gesetztes Repository
	 *
	 * @var array
	 */
	protected static array $repository = [];

	/**
	 * List cache array
	 * 
	 * @var array
	 */
	protected static $_aArrayListCache;

	/**
	 * The INPUT/OUTPUT format settings to use in extended classes
	 * 
	 * Format settings:
	 * 
		= array(
	  		'created' => array(
	  			'format'	=> 'TIMESTAMP',
				'required'	=> true,
				'not_changeable' => false				
			),
			'changed' => array(
				'format'	=> 'TIMESTAMP'
			),
			'password' => array(
				'format'	=> 'AES',
				'second'	=> AES_PASS,_decodeRecipients
				'required'	=> true
			),
			'mail' => array(
				'validate'	=> 'MAIL'
			),
			'nickname' => array(
				'validate'	=> '_ALNUM',
				'required'	=> true
			),
			'regex' => array(
				'validate'			=> 'REGEX',
				'validate_value'	=> '[0-1][a-Z]'
			),
			'birthday' => array(
				'validate'	=> array(
					'DATE',
					'DATE_PAST'				
				),
				'validate_separate' => true		// bricht die Validierung ab, sobald bei einem Fall ein Fehler aufgetreten ist
			),
	 		'zip' => array(
				'validate'			 => 'ZIP',
				'parameter_settings' => array(
					'type' => 'field'/'method',
					'source' => 'country_iso'/'getCountryIso'
				)
			),
	 		'phone' => array(
				'validate'			 => 'PHONE_ITU',
				'parameter_settings' => array(
					'type' => 'field'/'method',
					'source' => 'country_iso'/'getCountryIso'
				)
			),
		)
	 * 
	 * @var array
	 */
	protected $_aFormat = array();

	/**
	 * Eine Liste mit Klassen, die sich auf dieses Object beziehen, bzw. 
	 * mit diesem verknüpft sind (parent: n-1, 1-1, child: 1-n, n-m)
	 *
	 * array(
	 *		'ALIAS'=>array(
	 *			'class'=>'Ext_Class',
	 *			'key'=>'class_id',
	 *			'type'=>'child' / 'parent',
	 *			'check_active'=>true,
	 *			'orderby'=>position,
	 *			'orderby_type'=>ASC,
	 *			'orderby_set'=>false
	 *			'query' => false,
     *          'readonly' => false,
	 *			'cloneable' => true,
	 *			'static_key_fields' = array('field' => 'value'),
	 *			'on_delete' => 'cascade' / '' / 'detach' ( nur bei "childs" möglich ),
	 *			'bidirectional' => false // legt fest, ob eine Verknüpfung in beide Richtungen besteht
	 *		)
	 * )
	 *
	 * @var array
	 */
	protected $_aJoinedObjects = array();

	/**
	 * Array mit allen JoinedObjekt-Kindern
	 *
	 * @var WDBasic[][]
	 */
	protected $_aJoinedObjectChilds = array();

	/**
	 * Array mit allen JoinObjects die verwendet wurden
	 * @var array
	 */
	protected $_aCleanJoinedObjectChilds = array();
	
	/**
	 * Ein Array mit JoinedObjectChilds-Keys, welches von createCopy() gefüllt wird.
	 * 
	 * Der erste Key ist der Key des JoinedObjectChilds, 
	 * der ein Array enthält mit der alten ID als Schlüssel und der neuen ID als Wert.
	 * 
	 * array(
	 *		'<Joined Object Key>' => array(
	 *				'<alte ID>' => '<neue ID>',
	 *				'<alte ID>' => '<neue ID>'
	 *			)
	 * )
	 * 
	 * 
	 * @see self::createCopy()
	 * @var array
	 */
	protected $_aJoinedObjectCopyIds = array();

	/**
	 * Ein Array mit den Keys die beim Speichern umgeschrieben wurden. Bei neuen JoinedObjectChilds wird
	 * der Key mit der ID ausgetauscht
	 * 
	 * array(
	 *		'<Joined Object Key>' => array(
	 *				'<alte ID>' => '<neue ID>',
	 *				'<alte ID>' => '<neue ID>'
	 *			)
	 * )
	 * 
	 * @var array 
	 */
	protected $aJoinedObjectChildKeyMapping = array();

	/**
	 * Eine Liste mit Verknüpfungen (1-n)
	 *
	 * @TODO class + on_delete funktionieren nicht (nicht implementiert)
	 *
	 * array(
	 *		'items'=>array(
	 *				'table'=>'',
	 *				'foreign_key_field'=>'',
	 *				'primary_key_field'=>'id',
	 *				'sort_column'=>'',
	 *				'class'=>'', // funktioniert nut wenn bei foreign_key_field ein String angegeben ist mit dem Feldname der die ID der angegebenen Klasse enthält
	 *				'autoload'=>true,
	 *				'check_active'=>true,
	 *				'delete_check'=>false,
	 *				'cloneable' => true,
	 *				'static_key_fields'=>array(),
	 *				'join_operator' => 'LEFT OUTER JOIN' // aktuell nur bei getListQueryData,
	 *				'i18n' => false, // hierbei wird pro Sprache ein Join erzeugt im Query per getListQuery Data
	 * 				'readonly' => false // Nur abrufen, nicht speichern,
	 *              'readonly_class' => false // Verknüpfte Objekte werden nicht gespeichert, nicht erlaubt in Verbindung mit on_ oder neuen Objekten
	 *				'on_delete' => no_action (delete, no_action, no_purge) - delete ignoriert check_active!
	 *				'direct_child' => false // Direktes Kind ohne Verknüpfungstabelle
	 *				'bidirectional' => false // legt fest, ob eine Verknüpfung in beide Richtungen besteht, geht nur bei readonly JoinTableObjects (class)
	 *			)
	 * )
	 *
	 * foreign_key_field kann auch ein Array sein
	 *
	 * @var <array>
	 */
	protected $_aJoinTables = array();
	protected $_aAutoJoinTables = array();
	protected $_aJoinTablesLoaded = array();

	/**
	 * Array mit allen Objekten die über eine JoinTable geholt wurden
	 *
	 * @var WDBasic[][]
	 */
	protected $_aJoinTablesObjects = array();

	/**
	 * Array mit Daten der Join Tabellen
	 * @var <array>
	 */
	protected $_aJoinData;
	
	/**
	 * The execution settings to use in extended classes
	 * 
		= array(
			'forbid' => array(
				'save' 	=> array('password' => 'AES', 'test' => 'MD5'),
				'load'	=> array('password' => 'AES')
			)
		)
	 * WICHTIG bei JoinTables sollte im Array immer ein Wdbasic "ID" Feld auf ein Index Feld zeigen da dann alle einträge des aktuellen Objectes gelöscht werden müssten
	 * @var array
	 */
	protected $_aSettings = array();


	/**
	 * The intern flag of pre defined _aData array
	 */
	protected $_bDefaultData = true;

	/**
	 * Hier werden die Daten des Objektes nach dem laden gespeichert
	 * Ermöglicht einen Vergleich vor dem Speichern mit Daten aus der Datenbank und den neuen Daten
	 * @var <array>
	 */
	protected $_aOriginalData = array();

	protected $_aOriginalJoinData = array();


	protected $_bAutoFormat = false;


	/**
	 * The DB table columns description
	 * 
	 * @var array
	 */
	protected static $_aTable;
	protected static $_aIndexes;

	/**
	 * The list of instances of this class
	 */
	private static $aInstance = [];

	/**
	 * Class name, needed with php < 5.3
	 */
	protected static $sClassName = 'WDBasic';

	/**
	 * Name of DB Connection
	 */
	protected $_sDbConnectionName = 'default';

	/**
	 * Instance of DB Connection
	 * @var DB
	 */
	protected $_oDb = null;

	protected $_bOverwriteCurrentTimestamp = false;

	/**
	 * `changed` bei Änderung verändern (ON UPDATE CURRENT TIMESTAMP) oder behalten
	 *
	 * @var bool
	 */
	protected $bKeepCurrentTimestamp = false;

	/**
	 * delete() löscht Datensatz mit Abhängigkeiten komplett, unabhängig von active und validate()
	 *
	 * @TODO Als Parameter in delete() einbauen (dafür müssen aber alle abgeleiteten Methoden angepasst werden!)
	 *
	 * @var bool
	 */
	protected $bPurgeDelete = false;

	/**
	 * List of all Field in WDSearch Indexes
	 *
	 * array(
	 * 'index hash' =>
	 *		array(
	 *			'einfaches wdbasic field 1' => 'index field',
	 *			'einfaches wdbasic field 2 => array('index field 1', 'index field 2'),
	 *			'einfaches wdbasic field 3 => array('wdbasic feld' => 'index feld'),
	 *			'jointable 1' => 'index field', // es wird als wert "_all" gesetzt auser es wäre hier auch in 'id' => 'index feld' definiert dann wird das gesetzt mit der wdbasic id
	 *			'jointable 2' => array('wdbasic feld' => 'index feld') // geht nur bei jointables!
	 *		)
	 * )
	 *
	 *
	 * @var type
	 */
	protected $_aWDSearchIndexFields = array();

	/**
	 * WDBasic Attribute
	 *
	 * @var array
	 */
	protected $_aAttributes = array();

	/**
	 * Name of the primary column
	 * @var string
	 */
	protected $_sPrimaryColumn = 'id';
	
	/**
	 * Name of the sort column
	 * @var string
	 */
	protected $_sSortColumn = 'position';
	
	/**
	 * Hier wird vor dem Aufruf von save() ein Primary-Wert gespeichert, damit dieser beim INSERT eingetragen wird
	 * Wird benötigt für Primaries, die nicht auto-increment haben (z.B. nicht numerisch)
	 * @var string
	 */
	protected $_sSaveWithPrimaryValue;

	/**
	 * @var bool
	 */
	protected $_bDisableValidate = false;

	protected $toBeCleaned = [];

	protected bool $bCreateCopy = false;

	/**
	 * The event dispatcher instance.
	 *
	 * @var \Illuminate\Contracts\Events\Dispatcher
	 */
	protected static $dispatcher;

	/* ==================================================================================================== */

	/**
	 * The constructor
	 *
	 * @param int : The data ID
	 * @param string : The name of DB table
	 */
	public function __construct($iDataID = 0, $sTable = null, $bAutoFormat = false) {

		// Init DB Connection
		if(is_null($this->_oDb)) {
			$this->_oDb = DB::getConnection($this->_sDbConnectionName);
		}

		// Set a new table name if required
		if(
			!empty($sTable) && 
			is_string($sTable)
		) {
			$this->_sTable = $sTable;
		}
	
		if($bAutoFormat) {
			$this->_bAutoFormat = true;
		}

		// Set intern flag
		if(!empty($this->_aData)) {
			$this->_bDefaultData = false;
		}

		// Alle Tables entfernen, die nicht autoload true haben
		$this->_aAutoJoinTables = array();
		foreach((array)$this->_aJoinTables as $sKey=>$aTable) {
			if(
				!isset($aTable['autoload']) ||
				$aTable['autoload'] === true
			) {
				$this->_aAutoJoinTables[$sKey] = $aTable;
			}
		}

		if(empty(self::$_aTable[$this->_sTable])) {
			// Check the name of the table
			$this->_checkTableName();

			// Get table fields
			$this->_getTableFields();
		}

		// Prepare the data array
		$this->_prepareDataFields();

		// Load the data into data array
		$this->_loadData($iDataID);

	}

	public function __sleep(): array
	{
		$this->_oDb = null;
		$reflection = new \ReflectionClass($this);
		$properties = $reflection->getProperties();
		$propertyNames = [];
		foreach ($properties as $property) {
			$propertyNames[] = $property->getName();
		}

		return $propertyNames;
	}

	/**
     * Returns the values of _aData
     *
     * @param string : The name of a variable or a key
     * @throws Exception
     * @return mixed : Mixed value
     */
	public function __get($sName) {

		if(array_key_exists($sName, $this->_aData)) {

			$mValue = $this->_aData[$sName];	

			if(isset($this->_aFormat[$sName]['format'])) {
				if($this->_aFormat[$sName]['format'] == 'TIMESTAMP') {

					$this->_checkTableData();

					$sType = self::$_aTable[$this->_sTable][$sName]['Type'];

					switch($sType) {
						case 'timestamp':
							$sPart = WDDate::DB_TIMESTAMP;
							break;
						case 'date':
							$sPart = WDDate::DB_DATE;
							break;
						case 'datetime':
							$sPart = WDDate::DB_DATETIME;
							break;
						default:
							$sPart = false;
							break;
					}

					if($sPart) {
						$bCheck = WDDate::isDate($mValue, $sPart);
						if($bCheck) {
							$oDate = new WDDate($mValue, $sPart);
							$mValue = $oDate->get(WDDate::TIMESTAMP);
						}
					}

				} else if($this->_aFormat[$sName]['format'] == 'JSON') {

					$mValue = json_decode($mValue, true);

				} else if($this->_aFormat[$sName]['format'] == 'ENCRYPTED') {
					try {
						$mValue = \Illuminate\Support\Facades\Crypt::decrypt($mValue);
					} catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
						// Noch nicht verschlüsselt
					}
				}
			}

			return $mValue;

		} elseif(array_key_exists($sName, $this->_aJoinTables)) {

			// Daten laden, falls noch nicht geschehen
			if(!isset($this->_aJoinTablesLoaded[$sName])) {
				$aTable = $this->_aJoinTables[$sName];
				$this->_aJoinData[$sName] = $this->_getJoinTableData($aTable);
				$this->_aOriginalJoinData[$sName] = $this->_aJoinData[$sName];
				$this->_aJoinTablesLoaded[$sName] = 1;
			}

			return (array)$this->_aJoinData[$sName];

		} elseif($sName == 'aData') {
			return $this->_aData;
		} elseif(isset($this->_aAttributes[$sName])) {
			return $this->getDefinedAttribute($sName);
		} else {
			static::deleteTableCache();
			throw new Exception('Requested data "'.$sName.'" of class "'.get_class($this).'" do not exists!');
		}

	}

	/**
	 * Implementierung von Magic isset, damit isset() und empty() mit dem Magic Getter funktionieren
	 *
	 * @param string $sKey
	 * @return bool
	 */
	public function __isset($sKey) {
		try {
			$this->__get($sKey);
			return true;
		} catch(Exception $e) {
			// Requested data bla, bla, bla
			return false;
		}
	}

    /**
     * Sets a value to the _aData
     *
     * @param $sName
     * @param $mValue
     * @throws Exception
     * @internal param $string : The key
     * @internal param $mixed : The value
     */
	public function __set($sName, $mValue) {

		if(array_key_exists($sName, $this->_aData)) {

			$this->_checkTableData();

			if (
				(!defined('APP_ENV') || APP_ENV !== 'testing') &&
				self::$_aTable[$this->_sTable][$sName]['Key'] === 'PRIMARY'
			) {
				throw new Exception('The value of key "'.$sName.'" cannot be changed manually!');
			}

			// Wenn man den Wert nicht überschreiben darf
			if(
				$this->_aData[$this->_sPrimaryColumn] != 0 &&
				isset($this->_aFormat[$sName]) &&
				isset($this->_aFormat[$sName]['not_changeable']) &&
				$this->_aFormat[$sName]['not_changeable'] === true &&
				$this->_aData[$sName] != $mValue
			) {
				throw new Exception('The value of key "'.$sName.'" cannot be changed after insert!');
			}

			if(isset($this->_aFormat[$sName]['format'])) {

				switch($this->_aFormat[$sName]['format']) {
					case 'TIMESTAMP': {
						switch(self::$_aTable[$this->_sTable][$sName]['Type']) {
							case 'timestamp':
								$sPart = WDDate::DB_TIMESTAMP;
								break;
							case 'date':
								$sPart = WDDate::DB_DATE;
								break;
							case 'datetime':
								$sPart = WDDate::DB_DATETIME;
								break;
							default:
								$sPart = false;
								break;
						}
						if(
							$sPart &&
							$mValue !== ''
						) {
							$bCheck = WDDate::isDate($mValue, WDDate::TIMESTAMP);
							if($bCheck) {
								$oDate = new WDDate($mValue);
								$mValue = $oDate->get($sPart);
							}
						}
						break;
					}
					case 'TIME': {
						if($mValue == '') {
							$mValue = NULL;
						}
						break;
					}
					case 'PASSWORD':

						if(!empty($mValue)) {
							$mValue = password_hash($mValue, PASSWORD_DEFAULT);
						}

						break;
					case 'MD5': {
						// TODO
						if(!preg_match('/^[abcdef0-9]{32}$/i', $mValue)) {

							if(
								!isset($this->_aSettings['forbid']['save'][$sName]) ||
								$this->_aSettings['forbid']['save'][$sName] != 'MD5'
							) {
								$mValue = md5($mValue);
							}

						}
						break;
					}
					case 'ENCRYPTED': {
						if ($mValue !== '') {
							$mValue = \Illuminate\Support\Facades\Crypt::encrypt($mValue);
						}
						break;
					}
					case 'JSON': {
						$mValue = json_encode($mValue);
						break;
					}
				}

			}

			$this->_aData[$sName] = $mValue;

			// Wenn ein Foreign Key verändert wird, muss ein vorhandenes JoinedObject ersetzt werden
			if(isset(self::$_aTable[$this->_sTable][$sName]['joined_parent_key'])) {
				// Objekt Schlüssel
				$sJoinedObjectKey = self::$_aTable[$this->_sTable][$sName]['joined_parent_key'];
				if (
					// Nur ersetzen wenn wirklich die ID verändert wird, da ansonsten bidirectional sinnlos ist
					!empty($this->_aJoinedObjects[$sJoinedObjectKey]['object']) && (
						empty($mValue) ||
						$mValue != $this->_aJoinedObjects[$sJoinedObjectKey]['object']->id
					)
				) {
					// Aktuelles Objekt entfernen
					unset($this->_aJoinedObjects[$sJoinedObjectKey]['object']);
					// Neues Objekt aufrufen
					$this->getJoinedObject($sJoinedObjectKey);
				}
			}
			
		} elseif(array_key_exists($sName, $this->_aJoinTables)) {

			// Daten abrufen, damit sie in OriginalData drin stehen
			$this->$sName;
			$this->_aJoinData[$sName] = (array)$mValue;

			// Wenn es JoinTable Objekte gibt, dann eventuelle Änderung übernehmen
			if(isset($this->_aJoinTablesObjects[$sName])) {
				// Aktuelles Objekt Array zurücksetzen und Einträge neu holen
				$this->_aJoinTablesObjects[$sName] = array();
				$this->getJoinTableObjects($sName);
			}

		} elseif(isset($this->_aAttributes[$sName])){
			$this->setDefinedAttribute($sName, $mValue);
		} else {
			static::deleteTableCache();
			throw new Exception('The key "'.$sName.'" of class "'.get_class($this).'" does not exists!');
		}

	}
	
	/**
	 * @return bool
	 * @throws Exception
	 */
    public function isActive(){
		
        if(
			!$this->hasActiveField() ||
			$this->getData('active')			
		) {
            return true;
        }

        return false;
    }

	/**
	 * @return bool
	 */
    public function exist() {

        if($this->getId() > 0) {
            return true;
        }

        return false;
    }

	/**
	 * @return int
	 * @throws Exception
	 */
    public function getId() {
        $iReturn = $this->getData($this->_sPrimaryColumn);
        return $iReturn;
    }

	/**
	 * Funktion um zu überprüfen ob ein Objekt geupdated oder geinserted werden muss beim speichern
	 * (wird aber zurzeit nur für den Index verwendet)
	 * 
	 * @return boolean 
	 */
	public function isNew() {
		return !$this->exist();
	}

	/**
	 * Lädt JoinTable-Daten neu, falls sich was in der DB direkt geändert hat
	 * @param string $sKey
	 */
	public function reloadJoinTable($sKey){
		unset($this->_aJoinData[$sKey]);
		unset($this->_aJoinTablesLoaded[$sKey]);
		unset($this->_aJoinTablesObjects[$sKey]);
		$this->$sKey;
	}
	
	/**
	 * Setzt ein Parent-Objekt
	 * @param string $sKey
	 * @param WDBasic $oParent
	 * @throws Exception 
	 */
	public function setJoinedObject($sKey, $oParent) {
		
		$this->_checkJoinedObject($sKey, 'parent');

		if(
			get_class($oParent) != $this->_aJoinedObjects[$sKey]['class'] &&
			!is_subclass_of($oParent, $this->_aJoinedObjects[$sKey]['class'])
		) {
			throw new Exception('Object is no instance of "'.$this->_aJoinedObjects[$sKey]['class'].'"');
		}

		$this->_aJoinedObjects[$sKey]['object'] = $oParent;

	}
	
	/**
	 * Holt ein verknüpftes Objekt anhand von Object name oder Alias
	 * Wenn nur Alias angegeben ist, dann muss in der Klasse definiert sein,
	 * welche Klasse mit diesem Alias wie verknüpft ist
	 */
	public function getJoinedObject($sMixed, $sKey=null) {

		$oObject = null;

		// Wenn kein Alias oder Classname angegeben, dann direkt return
		if(empty($sMixed)) {
			return $this;
		}

		// Wenn der Alias bereits definiert ist
		if(
			!array_key_exists($sMixed, $this->_aJoinedObjects) &&
			class_exists($sMixed) &&
			!is_null($sKey)
		) {

			$this->_aJoinedObjects[$sMixed] = array(
                'class'=>$sMixed,
                'key'=>$sKey,
                'type'=>'parent'
            );

		}

		// Wenn der Eintrag jetzt definiert ist
		if(array_key_exists($sMixed, $this->_aJoinedObjects)) {

			$this->_checkJoinedObject($sMixed, 'parent');
			
			// Wenn das Objekt noch nicht definiert ist
			if(!array_key_exists('object', $this->_aJoinedObjects[$sMixed])) {

				$sKeyField = $this->_aJoinedObjects[$sMixed]['key'];

				// TODO Hier kann überhaupt nicht unterschieden werden, ob es bei fehlenden IDs das Objekt nun gibt oder nicht
				// Es kommt bei null also IMMER ein Objekt zurück
				$this->_aJoinedObjects[$sMixed]['object'] = Factory::getInstance($this->_aJoinedObjects[$sMixed]['class'], (int)$this->$sKeyField);

				$this->checkJoinedObjectBidirectional($sMixed);

			}
			
			return $this->_aJoinedObjects[$sMixed]['object'];

		}

		// TODO Was ist das eigentlich für ein Schwachsinn? Wenn $sMixed gesetzt ist und nicht existiert, sollte eine Exception geworfen werden
		return $this;

	}

	public function hasJoinedObject(string $key):bool {
		
		$this->_checkJoinedObject($key, 'parent');

		$keyField = $this->_aJoinedObjects[$key]['key'];

		// Wenn noch keine Parent-ID gespeichert ist und auch noch kein Objekt gesetzt wurde
		if(
			empty($this->{$keyField}) &&
			!array_key_exists('object', $this->_aJoinedObjects[$key])
		) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Prüft, ob es für ein JoinedObject Verwandte gibt, die gesetzt werden müssen
	 * 
	 * @param string $sKey
	 * @return boolean 
	 */
	protected function checkJoinedObjectBidirectional($sKey) {
		
		// Ist dieses JoinedObject bidirektional?
		if(
			empty($this->_aJoinedObjects[$sKey]['bidirectional']) ||
			$this->_aJoinedObjects[$sKey]['bidirectional'] !== true
		) {
			return false;
		}
		
		// Kinder checken (n)
		if($this->_aJoinedObjects[$sKey]['type'] == 'child') {
			
			$aChilds = $this->getJoinedObjectChilds($sKey, true);

			$aJoinedObject = null;
			foreach($aChilds as $oChild) {

				// Suchen des passenden JoinedObjects
				if($aJoinedObject === null) {

					$aJoinedObject = $this->_searchRelatedJoinedObject($oChild);

				}

				// Elternobjekt setzen
				$oChild->setJoinedObject($aJoinedObject['key'], $this);

			}

		// Eltern checken (1)
		} else {
			
			$oParent = $this->getJoinedObject($sKey);
			
			$aJoinedObject = $this->_searchRelatedJoinedObject($oParent, 'child');
			
			$oParent->getJoinedObjectChild($aJoinedObject['key'], $this, null, true);

		}

	}
	
	/**
	 * Sucht aus einem verwandeten Objekt die entsprechende JoinedObject Konfiguration
	 * @param WDBasic $oObject
	 * @return array 
	 * @throws Exception 
	 */
	protected function _searchRelatedJoinedObject($oObject, $sType='parent') {

		$aJoinedObjectData = null;
		$sJoinedObjectKey = null;
		
		$aJoinedObjects = $oObject->getJoinedObjectData();
		foreach($aJoinedObjects as $sKey=>$aJoinedObject) {

			if(
				(
					$aJoinedObject['class'] == get_class($this) ||
					is_subclass_of($this, $aJoinedObject['class'])
				) &&
				(
					!isset($aJoinedObject['type']) ||
					$aJoinedObject['type'] == $sType
				)
			) {
				$aJoinedObjectData = $aJoinedObject;
				$sJoinedObjectKey = $sKey;
				break;
			}

		}

		if($aJoinedObjectData === null) {
			throw new Exception('No relation found for bidirectional JoinedObject "'.get_class($oObject).'" with type "'.$sType.'".');
		}

		$aReturn = array(
			'key'=>$sJoinedObjectKey, 
			'data' => $aJoinedObjectData
		);
		
		return $aReturn;

	}
	
	/**
	 * Gibt die JoinedObject Konfiguration zurück
	 * 
	 * @param string $sKey
	 * @return array 
	 */
	public function getJoinedObjectData($sKey=null) {
		
		if($sKey !== null) {
			$aReturn = (array)$this->_aJoinedObjects[$sKey];
		} else {
			$aReturn = (array)$this->_aJoinedObjects;
		}
		
		return $aReturn;

	}

	/**
	 * Speichert zu bereinigende JoinedObjects
	 * @param <type> $sKey
	 */
	public function cleanJoinedObjectChilds($sKey) {

		if($this->_aJoinedObjects[$sKey]['type'] == 'parent') {
			throw new Exception('Joined object must be child!');
		} else if(
			!isset($this->_aJoinedObjects[$sKey])
		) {
			throw new Exception('Unknown Joined object alias');
		}

		$this->_aCleanJoinedObjectChilds[$sKey] = 1;
		$this->_aJoinedObjectChilds[$sKey] = array();

	}

	/**
	 * Löscht ein JoinedObject-Child
	 * 
	 * @param string $sKey
	 * @param int/object $mObject
	 * @return boolean 
	 */
	public function deleteJoinedObjectChild($sKey, &$mObject) {
		
		$this->_checkJoinedObject($sKey, 'child');
		
		$bSuccess = false;
		
		if(!is_object($mObject)) {

			if(isset($this->_aJoinedObjectChilds[$sKey][$mObject])) {
				// Object löschen und Referenz entfernen
				$this->_aJoinedObjectChilds[$sKey][$mObject]->delete();
				unset($this->_aJoinedObjectChilds[$sKey][$mObject]);
				$bSuccess = true;
			}

		} else {

			if(!empty($this->_aJoinedObjectChilds[$sKey])) {

				foreach($this->_aJoinedObjectChilds[$sKey] as $iKey=>$oObject) {
					if($oObject === $mObject) {

						if(!empty($this->_aJoinedObjects[$sKey]['handler'])) {

							/** @var \Gui2\Handler\JoinedObject $handler */
							$handler = new ($this->_aJoinedObjects[$sKey]['handler']);
							$handler->delete($this, $oObject);
							
						} else {
							$oObject->delete();							
						}
						unset($this->_aJoinedObjectChilds[$sKey][$iKey]);
						$mObject = null;
						unset($mObject);
						$bSuccess = true;
						break;
					}
				}
				
			}

		}

		return $bSuccess;

	}
	
	/**
	 * Prüfen, ob der JoinedObject Key passt
	 * 
	 * @param string $sKey
	 * @param string $sCheck
	 * @throws Exception 
	 */
	protected function _checkJoinedObject($sKey, $sCheck='parent') {

		// Key prüfen
		if(!isset($this->_aJoinedObjects[$sKey])) {
			throw new Exception('Unknown joined object alias "'.$sKey.'" in class "'.get_class($this).'"!');
		}

		// Default Wert setzen
		if(!isset($this->_aJoinedObjects[$sKey]['type'])) {
			$this->_aJoinedObjects[$sKey]['type'] = 'parent';
		}
		
		// Typ prüfen
		if($this->_aJoinedObjects[$sKey]['type'] != $sCheck) {
			throw new Exception('Joined object "'.$sKey.'" must be type '.$sCheck.'!');
		}

	}

	/**
	 *
	 * @param string $sKey
	 * @param int $iJoinedObjectId
	 * @return WDBasic
	 */
	public function getJoinedObjectChild($sKey, $mJoinedObject=0, $sJoinedObjectCacheKey=null, $bSkipBidirectionalCheck=false) {

		$this->_checkJoinedObject($sKey, 'child');

		// Sicherstellen, dass die JoinedObjectChilds schon aufgerufen wurde
		if(!isset($this->_aJoinedObjectChilds[$sKey])) {
			$this->_aJoinedObjectChilds[$sKey] = array();
			$this->getJoinedObjectChilds($sKey);
		}

		if(
			is_object($mJoinedObject) && 
			$mJoinedObject instanceof $this->_aJoinedObjects[$sKey]['class']
		) {
			$oChildObject = $mJoinedObject;
			$iJoinedObjectId = $oChildObject->id;
		} else {
			$iJoinedObjectId = $mJoinedObject;
		}
		
		$sKeyField = $this->_aJoinedObjects[$sKey]['key'];

		// Wenn kein individueller Key übergeben wird
		if($sJoinedObjectCacheKey === null) {
			$sJoinedObjectCacheKey = $iJoinedObjectId;
		}
		
		// Wenn das Objekt im Cache schon da ist
		if(
			$sJoinedObjectCacheKey != 0 &&
			isset($this->_aJoinedObjectChilds[$sKey][$sJoinedObjectCacheKey])
		) {

			$oChildObject = $this->_aJoinedObjectChilds[$sKey][$sJoinedObjectCacheKey];

		} else {

			if(!isset($oChildObject)) {
				$oChildObject = Factory::getInstance($this->_aJoinedObjects[$sKey]['class'], $iJoinedObjectId);
			}

			if(
				!empty($sKeyField) &&
				$iJoinedObjectId > 0 &&
                $this->_aData[$this->_sPrimaryColumn] > 0 &&
				$oChildObject->$sKeyField != $this->_aData[$this->_sPrimaryColumn]
			) {
				throw new Exception('Child belongs not to this object!');
			}

			if(
				!empty($sKeyField) &&
				$iJoinedObjectId == 0 &&
				$this->_aData[$this->_sPrimaryColumn] > 0
			) {
				$oChildObject->$sKeyField = $this->_aData[$this->_sPrimaryColumn];
			}
			
			// Statische Felder in der Child setzen, wenn es ein 'neues' Kind ist
			// Ansonsten Fehler werfen, wenn Wert abweicht vom Original
			if(isset($this->_aJoinedObjects[$sKey]['static_key_fields'])){
				$aStaticKeyFields = $this->_aJoinedObjects[$sKey]['static_key_fields'];
				
				foreach($aStaticKeyFields as $sStaticFieldKey => $sStaticFieldVaue){
					if(
						!empty($oChildObject->$sStaticFieldKey) &&
						$oChildObject->$sStaticFieldKey != $sStaticFieldVaue
					){
						// Fehler Werfen Wert 'sollte' nicht veränderbar sein
						throw new Exception('Static Field Value should not be changed!');
					}
					
					if($oChildObject->id == 0 ){
						// Bei neuen Kindern statische Felder setzen
						$oChildObject->$sStaticFieldKey = $sStaticFieldVaue;
					}
				}
			}
			
			

			// Object per Referenz cachen
			if($oChildObject->id > 0) {
				$iKey = $oChildObject->id;
			} else {
				if($sJoinedObjectCacheKey == 0) {

					if(!empty($this->_aJoinedObjectChilds[$sKey])) {
						$aKeys = array_keys($this->_aJoinedObjectChilds[$sKey]);
					} else {
						$aKeys = array();
					}

					$iKey = $this->_getLowestFromArray($aKeys);

				} else {
					$iKey = $sJoinedObjectCacheKey;
				}
			}

			$this->_aJoinedObjectChilds[$sKey][$iKey] =& $oChildObject;

			if($bSkipBidirectionalCheck === false) {
				$this->checkJoinedObjectBidirectional($sKey);
			}
			
		}

		return $oChildObject;

	}

	/**
	 * @param string $sKey
	 * @param WDBasic $oChild
	 */
	public function setJoinedObjectChild($sKey, $oChild) {
		$this->getJoinedObjectChild($sKey, $oChild);
	}
	
	/**
	 * @TODO $bCheckCache Default = true oder Parameter ganz entfernen, weil das absolut nicht relational ist
	 * 
	 * @param string $sMixed Alias
	 * @return array
	 * @todo Query Infos direkt Objekt übergeben -> getObjectFromArray
	 */
	public function getJoinedObjectChilds($sMixed = null, $bCheckCache=false) {

		$aReturn = array();		
		$aObjectKeys = array();

		if($sMixed) {
			$aReturn[$sMixed] = array();
			$aObjectKeys = array($sMixed);
		} else {
			foreach($this->_aJoinedObjects as $sKey=>$aJoinedObject) {
				if($aJoinedObject['type'] == 'child') {
					$aObjectKeys[] = $sKey;
				}
			}
		}

		foreach($aObjectKeys as $sKey) {

			$this->_checkJoinedObject($sKey, 'child');

			if(
				$bCheckCache === true &&
				isset($this->_aJoinedObjectChilds[$sKey])
			) {

				$aChilds = $this->_aJoinedObjectChilds[$sKey];


				// Wir müssen aktive prüfen falls es aus dem Cache kommt
				// da es sein kann das im scriptablauf ein object bereits gelöscht wurde
				// wenn die childs vorher aber shconmal abgerufen wurden würde es immer noch
				// hier drin stehen, was natürlich fatal ist
				if(
					isset($this->_aJoinedObjects[$sKey]['check_active']) &&
					$this->_aJoinedObjects[$sKey]['check_active'] === true
				) {
					foreach($aChilds as $iKey => $oChild){
						if($oChild->active == 0){
							unset($aChilds[$iKey]);
						}
					}
				}
			
				$aReturn[$sKey] = $aChilds;
				
			} else {

				if(!empty($this->_aJoinedObjects[$sKey]['handler'])) {

					/** @var \Gui2\Handler\JoinedObject $handler */
					$handler = new ($this->_aJoinedObjects[$sKey]['handler']);
					$childs = $handler->get($this);
					
					$this->_aJoinedObjectChilds[$sKey] = $aReturn[$sKey] = [];
					foreach($childs as $child) {
						$aReturn[$sKey][$child->getId()] = $child;
						$this->_aJoinedObjectChilds[$sKey][$child->getId()] = $child;
					}
					
				} else
				// Nur bei bestehenden Objekten abfragen
				// neue "id 0" Objekte können in der DB gar nichts haben
				// und evt gibt es fehlerhafte DB Einträge wo die eltern id nicht gesetzt wurde
				// ein neues onjekt hätte dann diese objekte direkt als kinder was falsch wäre
				if(!empty($this->_aData[$this->_sPrimaryColumn])) {
					
					// Eintrag setzen, damit Cache funktioniert
					if(!isset($this->_aJoinedObjectChilds[$sKey])) {
						$this->_aJoinedObjectChilds[$sKey] = array();
					}
					
					$sKeyField = $this->_aJoinedObjects[$sKey]['key'];

					$oEmptyObject = Factory::getInstance($this->_aJoinedObjects[$sKey]['class'], 0);

					$aSql = array(
						'parent_fieldname' => $sKeyField,
						'this_id' => $this->_aData[$this->_sPrimaryColumn],
						'table' => $oEmptyObject->getTableName()
					);

					$sSelect = '`id`';
					if(
						isset($this->_aJoinedObjects[$sKey]['child_array_key'])
					)
					{
						$sSelect .= ', '.$this->_aJoinedObjects[$sKey]['child_array_key'].' `child_key`';
					}

					$sSql = "
						SELECT
							".$sSelect."
						FROM
							#table
						WHERE
							#parent_fieldname = :this_id";
					
					// Abfrage für Static Key Fields (Falls gesetzt)
					$aQueryParts = $this->_getJoinedObjectsStaticFieldsQueryPart($sKey);
					$sSql .= $aQueryParts['sql'];
					$aSql += $aQueryParts['placeholder'];

					if(
						isset($this->_aJoinedObjects[$sKey]['check_active']) &&
						$this->_aJoinedObjects[$sKey]['check_active'] === true &&
						$this->bPurgeDelete === false // check_active umgehen, damit keine Leichen in der DB verbleiben
					) {
						$sSql .= " AND `active` = 1 ";
					}

					if(
						isset($this->_aJoinedObjects[$sKey]['orderby']) &&
						$this->_aJoinedObjects[$sKey]['orderby'] != ""
					) {

						$aOrderBys = (array)$this->_aJoinedObjects[$sKey]['orderby'];
						$aOrderByTypes = array();
						if(isset($this->_aJoinedObjects[$sKey]['orderby_type'])) {
							$aOrderByTypes = (array)$this->_aJoinedObjects[$sKey]['orderby_type'];
						}

						$sSql .= " ORDER BY";

						foreach($aOrderBys as $iKey => $sOrderBy) {
							if(isset($aOrderByTypes[$iKey])) {
								$sOrderByType = $aOrderByTypes[$iKey];
							} else {
								$sOrderByType = 'ASC';
							}
							
							$sSql .= ' `' . $sOrderBy . '` ' . $sOrderByType . ',';
						}
						
						$sSql = substr($sSql, 0, -1);
						
					} else {
						$sSql .= " ORDER BY `id` ASC";
					}

					$aResult = DB::getPreparedQueryData($sSql, $aSql);

					foreach($aResult as $aChild) {

						$iObjectId = $aChild['id'];
						$mChildKey = $iObjectId;

						if(
							isset($aChild['child_key']) &&
							!empty($aChild['child_key'])
						){
							$mChildKey = $aChild['child_key'];
						}

						$aReturn[$sKey][$mChildKey] = Factory::getInstance($this->_aJoinedObjects[$sKey]['class'], (int)$iObjectId);

						$this->_aJoinedObjectChilds[$sKey][$mChildKey] = $aReturn[$sKey][$mChildKey];
					}
					
				}
				
			}

		}
		
		if($sMixed) {
			return $aReturn[$sMixed];
		} else {
			return $aReturn;
		}

	}

	/**
	 * Returns the instance of an object by data ID
	 *
	 * @TODO: Bei WDBasic Tabellenname im Cache berücksichtigen
	 *
	 * @param int $iDataID
	 * @return static
	 */
	static public function getInstance($iDataID = 0) {

		$sClass = get_called_class();

		// Der direkte Aufruf in der WDBasic ist nicht erlaubt
		if($sClass == 'WDBasic') {
			throw new Exception('Direct call of WDBasic::getInstance is not allowed!');
		}

		// Wenn neuer Eintrag, immer direkt neues Objekt zurückgeben
		if($iDataID == 0) {
			return new $sClass($iDataID);
		}

		if(!isset(self::$aInstance[$sClass][$iDataID])) {
			
			try {
				
				if(!isset(self::$aInstance[$sClass])) {
					self::$aInstance[$sClass] = [];
				}

				self::$aInstance[$sClass][$iDataID] = new $sClass($iDataID);

			} catch(Exception $e) {
				Util::handleErrorMessage($e->getMessage());
				throw $e; // Ohne throw ist das return null, aber die Methode darf kein null liefern
			}
		}

		return self::$aInstance[$sClass][$iDataID];

	}

	/**
	 * Setz ein Objekt in das Instanz-Array
	 * @todo Zentralisieren, wenn getInstance überall rausfliegt
	 * @param WDBasic $oEntity
	 */
	static public function setInstance(WDBasic $oEntity) {

		if(!$oEntity->hasEmptyPrimaryKey()) {
			$mPrimaryKey = $oEntity->getPrimaryKeyValue();			
			$sClass = get_class($oEntity);
			
			// Objekt immer überschreiben
			self::$aInstance[$sClass][$mPrimaryKey] = $oEntity;
		}

	}

	public function getClassName() {
		return get_class($this);
	}

	/**
	 * Läd alle autojointables die noch nicht geladen wurden
	 */
	public function loadAutoJoinTableData() {

		foreach((array)$this->_aAutoJoinTables as $sKey=>$aTable) {

			// Nur nachladen, wenn noch nicht geschehen
			if(!isset($this->_aJoinTablesLoaded[$sKey])) {
				$this->_aJoinData[$sKey] = $this->_getJoinTableData($aTable);
				$this->_aOriginalJoinData[$sKey] = $this->_aJoinData[$sKey];
				$this->_aJoinTablesLoaded[$sKey] = 1;
			}

		}

	}
	
	public function saveWithPrimary($mPrimary) {

		$this->_sSaveWithPrimaryValue = $mPrimary;

		$this->save();

	}

	protected function setUserIds($bInsert) {
		
		$oUser = \System::getCurrentUser();

		if(
			is_object($oUser) &&
			$oUser->id > 0
		) {
			
			if(array_key_exists('editor_id', $this->_aData) === true) {
				$this->_aData['editor_id'] = $oUser->id;
			}

			if(
				$bInsert === true &&
				array_key_exists('creator_id', $this->_aData) === true &&
				empty($this->_aData['creator_id']) 
			) {
				$this->_aData['creator_id'] = $oUser->id;
			}

		}
		
	}

	public function saveQuietly()
	{
		return static::withoutEvents(fn () => $this->save());
	}

	/**
	 * Saves the _aData into the DB
	 *
	 * @return object : $this
	 */
	public function save() {

		$this->_checkTableData();

		$oDB = $this->getDbConnection();

		// Prepare and validate the _aData fields
		$aInsert = $this->_prepareSaving();
        
		// Eintrag anlegen, falls neu
		$bInsert = false;

		// Mapping zurücksetzen damit neue Zuweisungen gesetzt werden können
		$this->aJoinedObjectChildKeyMapping = array();

		// Muss mit numerischen und nicht numerischen Primärschlüssel klappen
		if(
			(
				is_numeric($this->_aData[$this->_sPrimaryColumn]) &&
				$this->_aData[$this->_sPrimaryColumn] <= 0
			) ||
			(
				!is_numeric($this->_aData[$this->_sPrimaryColumn]) &&
				empty($this->_aData[$this->_sPrimaryColumn])
			)
		) {

			$aSQL = array();

			$sSQL = "
				INSERT INTO
					`".$this->_sTable."`
				SET
			";

			$sSet = '';
			foreach((array)$aInsert as $sKey => $mValue) {

				$aSQL[] = $mValue;

				$sSet .= " `".$sKey."` = ?, ";
			}

			if(array_key_exists('created', self::$_aTable[$this->_sTable])) {
				$sSet .=  " `created` = NOW() ";
			} else {
				$sSet = rtrim($sSet, ', ');
			}

			if(empty($sSet)) {
				$sSet = "`".$this->_sPrimaryColumn."` = ''";
			}

			$sSQL .= $sSet;

            $stmt       = $oDB->getPreparedStatement($sSQL, md5($sSQL), $oDB);
			$iInsertId  = $oDB->executePreparedStatement($stmt, $aSQL);

			// Wenn kein Autoincrement
			if(empty(self::$_aTable[$this->_sTable][$this->_sPrimaryColumn]['Extra'])) {
				$this->_aData[$this->_sPrimaryColumn] = $aInsert[$this->_sPrimaryColumn];
			} else {
				$this->_aData[$this->_sPrimaryColumn] = $iInsertId;
			}
			
			$bInsert = true;

		}
        
		// Setzt Bearbeiter und optional Ersteller
		$this->setUserIds($bInsert);
        
		// Wenn kein Eintrag vorhanden
		if(empty($this->_aData[$this->_sPrimaryColumn])) {
			return false;
		}

		if(is_numeric($this->_aData[$this->_sPrimaryColumn])) {
			$this->_aData[$this->_sPrimaryColumn] = (int)$this->_aData[$this->_sPrimaryColumn];
		}
		
		$aSQL = array();

		$sSQL = "
			UPDATE
				`".$this->_sTable."`
			SET
		";

		foreach((array)$this->_aData as $sKey => $mValue) {

			if(
				(
					self::$_aTable[$this->_sTable][$sKey]['Default'] !== 'CURRENT_TIMESTAMP' ||
					$this->_bOverwriteCurrentTimestamp
				) &&
				$sKey !== 'created'
			) {
				// PDO kann nicht integer korrekt in bit umwandeln, wenn man kein bindparams benutzt
				if (str_starts_with(self::$_aTable[$this->_sTable][$sKey]['Type'], 'bit')) {
					$sSQL .= " `".$sKey."` = b'".decbin($mValue)."', ";
				} else {
					$sSQL .= " `" . $sKey . "` = ?, ";
					$aSQL[] = $mValue;
				}

			}

			if(
				$this->bKeepCurrentTimestamp &&
				stripos(self::$_aTable[$this->_sTable][$sKey]['Extra'], 'ON UPDATE CURRENT_TIMESTAMP') !== false
			) {
				// Niemals auf _aData verlassen
				$sSQL .= " `{$sKey}` = `{$sKey}`, ";
			}

		}
        
        // Parent Joined Objects fremdschlüssel setzten
        // wenn man setJoinedObject macht auf einem Leeren object muss beim insert alle Parent ids
        if(is_array($this->_aJoinedObjects)){
            foreach($this->_aJoinedObjects as $sKey => $aObjectData){
                if(
					($aObjectData['type'] ?? 'child') == 'parent' &&
                   isset($aObjectData['object']) &&
                   $aObjectData['object']->id > 0
                ){
                    $sSQL .= " `".$aObjectData['key']."` = ?, ";
                    $aSQL[] = $aObjectData['object']->id;
                }
            }
        }
        
		$sSQL = substr($sSQL, 0, -2);
		$sSQL .= " WHERE `".$this->_sPrimaryColumn."` = ?";
		$aSQL[] = $this->_aData[$this->_sPrimaryColumn];

        $stmt       = $oDB->getPreparedStatement($sSQL, null, $oDB);
        $bSuccess   = true;
        
        try {
            $oDB->executePreparedStatement($stmt, $aSQL);
        } catch (Exception $exc) {
            $bSuccess = false;
        }

        // Wenn nicht erfolgreich und neuer Eintrag, Blankoeintrag wieder löschen
		if(
			$bSuccess === false &&
			$bInsert === true
		) {
			$sSql = "
					DELETE FROM
						#table
					WHERE
						`id` = :id
					LIMIT 1
					";
			$aSql = array();
			$aSql['table'] = $this->_sTable;
			$aSql['id'] = (int)$this->_aData[$this->_sPrimaryColumn];
			$oDB->preparedQuery($sSql, $aSql);
		}

        // wenn fehler dann die vorher abge
        if($bSuccess) {

			// Join Tabellen schreiben
			foreach((array)$this->_aJoinTables as $sKey=>$aTable) {

				// Nur Speichern, wenn auch geladen und Speichern erlaubt
				if(
					(
						!isset($aTable['readonly']) ||
						$aTable['readonly'] === false
					) &&
					isset($this->_aJoinTablesLoaded[$sKey])
				) {

					$sJoinTablePrimaryKeyField = $aTable['primary_key_field'];
					
					// Wenn es zu der JoinTable Objekte gibt
					if(isset($this->_aJoinTablesObjects[$sKey])) {
						
						// Array zurücksetzen
						$this->_aJoinData[$sKey] = array();

						foreach($this->_aJoinTablesObjects[$sKey] as $iJoinTableKey=>$oJoinTableObject) {

							if(
								!isset($aTable['readonly_class']) ||
								$aTable['readonly_class'] === false
							) {

								// Kindobjekt, keine Verknüpfungstabelle
								if($aTable['direct_child'] === true) {
									$oJoinTableObject->$sJoinTablePrimaryKeyField = (int)$this->_aData[$this->_sPrimaryColumn];
								}

								if($this->_bDisableValidate) {
									$oJoinTableObject->disableValidate();
								}

								// Objekt speichern und ID merken
								$oJoinTableObject->save();

							} elseif($aTable['direct_child'] === true) {
								throw new RuntimeException('Setting "direct_child" are not allowed with setting "readonly_class"!');
							} elseif($oJoinTableObject->id == 0) {
								throw new RuntimeException('Setting "readonly_class" can not work with new join table objects!');
							}

							$iJoinTableId = $oJoinTableObject->id;
							
							// Neue Objekte mit neuem Key im Array speichern
							if($iJoinTableKey <= 0) {
								unset($this->_aJoinTablesObjects[$sKey][$iJoinTableKey]);
								$this->_aJoinTablesObjects[$sKey][$iJoinTableId] = $oJoinTableObject;
							}

							// ID in Array für JoinTable setzen
							$this->_aJoinData[$sKey][] = $iJoinTableId;

						}
					}

					// Bei direkten Kindern muss das nicht gespeichert werden, falls die Objekte durchlaufen wurden
					if(
						($aTable['direct_child'] ?? null) !== true ||
						!isset($this->_aJoinTablesObjects[$sKey])
					) {

						$aKeys = array($sJoinTablePrimaryKeyField=>(int)$this->_aData[$this->_sPrimaryColumn]);

						if(!empty($aTable['static_key_fields'])){
							foreach((array)$aTable['static_key_fields'] as $sField => $mValue){
								$aKeys[$sField] = $mValue;
							}
						}

						if(!empty($aTable['check_active'])) {
							$aKeys['active'] = 1;
						}

						$sSortColumn = false;
						if(isset($aTable['sort_column'])){
							$sSortColumn = $aTable['sort_column'];
						}

						if(!is_array($aTable['foreign_key_field'])) {
							$oDB->updateJoin($aTable['table'], $aKeys, (array)$this->_aJoinData[$sKey], $aTable['foreign_key_field'], $sSortColumn);
						} else {
							$oDB->updateJoin($aTable['table'], $aKeys, (array)$this->_aJoinData[$sKey], false, $sSortColumn);
						}

					}

				}

			}

			$this->saveJoinedObjectChilds();

			// Encrypt the data if required
			foreach($this->_aFormat as $sKey => $aValue)
			{
				if(isset($aValue['format']) && $aValue['format'] == 'AES')
				{
					if(!isset($this->_aSettings['forbid']['save'][$sKey]) || $this->_aSettings['forbid']['save'][$sKey] != 'AES')
					{
						$sSQL = "
							UPDATE ".$this->_sTable."
							SET `".$sKey."` = AES_ENCRYPT(:sValue, '".$aValue['second']."')
							WHERE `id` = :iDataID
							LIMIT 1
						";
						$aSQL = array(
							'sValue'	=> $this->_aData[$sKey],
							'iDataID'	=> $this->_aData[$this->_sPrimaryColumn]
						);
						$oDB->preparedQuery($sSQL, $aSQL);
					}
				}
			}

			/**
			 * Wenn die WDBasic einfluss auf ihrgendwelche WDSearch Indexe hat muss geprüft werden
			 * ob sich werte geänder haben und entsprechende Flags geschrieben werden
			 */
			if(
				!empty($this->_aWDSearchIndexFields) &&
				!$bInsert
			){

				$sWDSearchStatus = 'changed';
				$aData = $this->_aData['active'];

				// Wenn gelöscht wurde muss der status auf deleted gesetztw erden
				if(
					isset ($aData['active']) &&
					$aData['active'] <= 0
				){
					$sWDSearchStatus = 'deleted';
				}

				$aJoinTables = $this->_aJoinTables;

				$aChanges = $this->getIntersectionData();

				// Indexfelder durchgehen
				foreach($this->_aWDSearchIndexFields as $sGuiHash => $aFields){

					foreach($aFields as $sField => $mIndexField){

						// Schauen ob es sich um eine Jointable handelt
						if(
							isset ($aJoinTables[$sField])
						){
							// wenn ja
							if(!empty($aChanges[$sField])){

								// Schauen ob wir die Index info für das ID Feld für die WDBasic haben
								if(
									isset ($aFields['id']) ||
									is_array($mIndexField)
								){
									// Bei jointable ist es möglich eine weitere Ebene zu definieren
									// damit wir sagen konnen das bei änderungen ein bestimmtes index feld mit einem bestimmten wdbasic feld gefüllt wird
									if(is_array($mIndexField)){
										$sIndexField	= reset($mIndexField);
										$sField			= key($mIndexField);
										// Id muss aus der aktuelle data kommen wegen neuanlegen
										if($sField == 'id'){
											$sIndexValue	= $this->getData($sField);
										} else {
											$sIndexValue	= $this->getOriginalData($sField);
										}
									} else {
										$sIndexField = $aFields['id'];
										$sIndexValue = $this->_aData[$this->_sPrimaryColumn];
									}
									// dann direkt sagen das die ganze Entity betroffen ist ( bzw. alles was damit zu tun hat)
									Ext_Gui2_Data::writeGUIWDSearchIndexChange($sGuiHash, $sIndexField, $sIndexValue, $sWDSearchStatus);
								} else {
									// ansonsten ein "_all" flag für die Jointable setzten
									Ext_Gui2_Data::writeGUIWDSearchIndexChange($sGuiHash, $mIndexField, '_all', $sWDSearchStatus);
								}
							}

						// Wenn es ein normales feld ist
						} else {

							// Bei änderung
							if(!empty($aChanges[$sField])){

								if(
									is_array($mIndexField)
								){
									$sField = key($mIndexField);
									if(!is_numeric($sField)){
										$sIndexField = reset($mIndexField);
										if($sField == 'id'){
											$sIndexValue	= $this->getData($sField);
										} else {
											$sIndexValue	= $this->getOriginalData($sField);
										}
									}
								} else {
									$sIndexValue = $this->getOriginalData($sField);
								}

								// Information schreiben das das feld verändert wurde
								// wichtig ist das der ALTE wert gesetzt wird da dieser im index geändert werden muss
								Ext_Gui2_Data::writeGUIWDSearchIndexChange($sGuiHash, $sIndexField, $sIndexValue, $sWDSearchStatus);
							}
						}
					}
				}

			}

		}

		// Reload the data in the data array
		$this->_loadData($this->_aData[$this->_sPrimaryColumn]);

		// Cache löschen sobald ein Eintrag verändert wird
		WDCache::delete($this->_getArrayListCacheKey(true));
		WDCache::delete($this->_getArrayListCacheKey(false));

		// Nach dem Speichern aus dem Persister entfernen
		$oPersister = WDBasic_Persister::getInstance();
		$oPersister->detach($this);

		if($bInsert === true) {
			self::setInstance($this);
			$this->fireModelEvent('created', false);
		} else if ($this->isActive()) {
			$this->fireModelEvent('updated', false);
		}

		$this->fireModelEvent('saved', false);

		return $this;
	}

	public function saveJoinedObjectChilds() {
		
		// Alle gecacheten Childs speichern
		$aJoinedObjectChildsCache = array();
		foreach($this->_aJoinedObjectChilds as $sKey=>$aChildObjects) {

			$sKeyField = $this->_aJoinedObjects[$sKey]['key'];
			$bReadonly = $this->_aJoinedObjects[$sKey]['readonly'] ?? null;

			if($bReadonly === true){
				continue;
			}

			// Statische Key-Felder die für jedes Kind mitgespeichert werden müssen
			$aStaticKeyFields = array();
			if(isset($this->_aJoinedObjects[$sKey]['static_key_fields'])){
				$aStaticKeyFields = $this->_aJoinedObjects[$sKey]['static_key_fields'];
			}

			$sPositionField = $this->_aJoinedObjects[$sKey]['orderby'] ?? null;
			$bSetPositionField = true;
			if(isset($this->_aJoinedObjects[$sKey]['orderby_set'])) {
				$bSetPositionField = $this->_aJoinedObjects[$sKey]['orderby_set'];	
			}

			$iPosition = 1;
			if(is_array($aChildObjects)) {
				
				foreach($aChildObjects as $iChildObjectKey=>$oChildObject) {

					// Position schreiben, falls leer und gewünscht
					if(
						$bSetPositionField === true && 
						!empty($sPositionField) &&
						$oChildObject->$sPositionField == 0
					) {
						$oChildObject->$sPositionField = $iPosition;
					}

					if(!empty($this->_aJoinedObjects[$sKey]['handler'])) {

						/** @var \Gui2\Handler\JoinedObject $handler */
						$handler = new ($this->_aJoinedObjects[$sKey]['handler']);
						$handler->save($this, $oChildObject);

					} else {

						// Statische Felder Schreiben
						foreach($aStaticKeyFields as $sStaticKey => $sStaticValue){
							$oChildObject->$sStaticKey = $sStaticValue;
						}

						// Es kann sein, dass es keine ID gibt (Fake-Objekte)
						if(!empty($this->_aData[$this->_sPrimaryColumn])) {
							$oChildObject->$sKeyField = $this->_aData[$this->_sPrimaryColumn];
						}

						if($this->_bDisableValidate) {
							$oChildObject->disableValidate();
						}

						$oChildObject->save(); 
						
					}

					// Neuen Index setzen wenn nicht identisch mit ID
					if($iChildObjectKey != $oChildObject->id) {
						unset($this->_aJoinedObjectChilds[$sKey][$iChildObjectKey]);
						$this->_aJoinedObjectChilds[$sKey][$oChildObject->id] = $oChildObject;
						// Alten Key merken
						$this->aJoinedObjectChildKeyMapping[$sKey][$iChildObjectKey] = $oChildObject->id;							
					}

					$aJoinedObjectChildsCache[$sKey][] = $oChildObject->id;
					$iPosition++;
				}
			}
		}

		// Nicht mehr benötigte JoinedObjectChilds löschen

		foreach($this->_aCleanJoinedObjectChilds as $sJoinedObjectKey => $iValue) {

			$sKeyField = $this->_aJoinedObjects[$sJoinedObjectKey]['key'];

			$oEmptyObject = Factory::getInstance($this->_aJoinedObjects[$sJoinedObjectKey]['class'], 0);

			$aSql = array(
				'parent_fieldname' => $sKeyField,
				'this_id' => $this->_aData[$this->_sPrimaryColumn],
				'table' => $oEmptyObject->getTableName()
			);

			if($this->_aJoinedObjects[$sJoinedObjectKey]['check_active'] === true) {
				$sSql = "
					UPDATE
						#table
					SET
						`active` = 0";
			} else {
				$sSql = "
					DELETE FROM
						#table";
			}

			$sSql .= "
					WHERE
						#parent_fieldname = :this_id";

			// Nur Felder löschen die zu diesen static_key_fields gehören (falls angegeben)
			$aQueryParts = $this->_getJoinedObjectsStaticFieldsQueryPart($sJoinedObjectKey);
			$sSql .= $aQueryParts['sql'];
			$aSql += $aQueryParts['placeholder'];

			if(!empty($aJoinedObjectChildsCache[$sJoinedObjectKey])) {
				$sSql .= " AND
						`id` NOT IN (:object_ids)";
				$aSql['object_ids'] = $aJoinedObjectChildsCache[$sJoinedObjectKey];
			}

			DB::executePreparedQuery($sSql, $aSql);

		}
		
	}
	
	protected function _getJoinedObjectsStaticFieldsQueryPart($sJoinedObjectKey){
		$aQueryParts = array();
		
		// Query Part
		$sSql = '';
		// Platzhalter
		$aSql = array();
			
		if(isset($this->_aJoinedObjects[$sJoinedObjectKey]['static_key_fields'])){
			$aStaticKeyFields = $this->_aJoinedObjects[$sJoinedObjectKey]['static_key_fields'];
			
			// Counter für jedes Static Field
			$iStaticFieldCounter = 0;
			
			foreach($aStaticKeyFields as $sStaticKey => $sStaticField){
				$sSql .= " AND #static_key_field_" . $iStaticFieldCounter . " = :static_key_value_" . $iStaticFieldCounter;		
				
				$aSql['static_key_field_'.$iStaticFieldCounter] = $sStaticKey;
				$aSql['static_key_value_'.$iStaticFieldCounter] = $sStaticField;
				
				$iStaticFieldCounter++;
			}
		}	
		
		$aQueryParts['sql'] = $sSql;
		$aQueryParts['placeholder'] = $aSql;
		
		return $aQueryParts;
	}
	
	
	/**
	 * Write Log
	 * ( Bitte nicht in die Save einbauen da sonst bei Thebing doppelte Logs geschrieben werden
	 * wurde wieder rausgenommen da wir genauere Logs definieren müssen )
	 */
	public function log($sAction, $aIntersectionData = []) {

		$iId = $this->_aData[$this->_sPrimaryColumn];
		$sAction = self::$sClassName.': '.$sAction.' ID['.$iId.']';
		\Log::enterLog(0, $sAction);
	}

	public function checkIgnoringErrors(){
		return true;
	}

	/**
	 * Validiert Kindobjekte
	 * 
	 * @param array $aChilds
	 * @param array $aErrors
	 */
	protected function _validateChilds(array $aChildConfiguration, array &$aChilds, array &$aErrors) {

		foreach($aChilds as $sKey => $aObjects) {

			if(
				!empty($aObjects) &&
				(
					!isset($aChildConfiguration[$sKey]['readonly']) ||
					$aChildConfiguration[$sKey]['readonly'] !== true
				)
			) {
				
				// Alle Objekte durchlaufen
				foreach($aObjects as $iObjectKey => $oObject) {

					// JoinTable-Objekte derselben Klasse dürfen nicht validiert werden (Endlosschleife)
					if(
						get_class($this) == get_class($oObject) &&
						$this->id == $oObject->id
					) {
						continue;
					}					
					
					if(!empty($aChildConfiguration[$sKey]['handler'])) {

						/** @var \Gui2\Handler\JoinedObject $handler */
						$handler = new ($aChildConfiguration[$sKey]['handler']);
						$aJoinedErrors = $handler->validate($this, $oObject);
						
						// Wenn Fehler vorkommen
						if(is_array($aJoinedErrors)) {
							foreach($aJoinedErrors as $sField=>$aJoinedError) {
								$aErrors[$sField] = $aJoinedError;
							}
						}
						
					} else {
						
						$aJoinedErrors = $oObject->validate();

						// Wenn Fehler vorkommen
						if(is_array($aJoinedErrors)) {
							foreach($aJoinedErrors as $sField=>$aJoinedError) {
								$aErrors[$sKey.'['.$iObjectKey.'].'.$sField] = $aJoinedError;
							}
						}
						
					}
					
				}
			}
		}

	}
	
	/**
	 * Validiert alle verknüpften Eltern-Objekte
	 * 
	 * @param bool $bThrowExceptions
	 * @return boolean/array 
	 */
	public function validateParents($bThrowExceptions = false) {

		$aErrors = array();

		// Validate der verknüpften Objekte aufrufen
		foreach((array)$this->_aJoinedObjects as $sKey=>$aObject) {
			if(
				$aObject['type'] != 'child' &&
				isset($aObject['object']) &&
				is_object($aObject['object'])
			) {
				$aJoinedErrors = $aObject['object']->validate($bThrowExceptions);
				if(is_array($aJoinedErrors)) {
					foreach((array)$aJoinedErrors as $sField=>$aJoinedError) {
						$aErrors[$sKey.'.'.$sField] = $aJoinedError;
					}
				}
			}
		}

		if(empty($aErrors)) {
			return true;
		}

		return $aErrors;

	}

	/**
	 * Speichert alle Eltern-Objekte 
	 */
	public function saveParents() {

		// Save der verknüpften Eltern-Objekte aufrufen
		foreach((array)$this->_aJoinedObjects as $aObject) {

			// Ist es ein Eltern-JoinObject, ist ein Object vorhanden
			if(
				$aObject['type'] != 'child' &&
				isset($aObject['object']) &&
				is_object($aObject['object'])
			) {
				// Readonly Eltern-Joinobjekte nicht speichern
				if(
					isset($aObject['readonly']) &&
					$aObject['readonly'] === true
				) {
					continue;
				}

				$aObject['object']->save();
				$sKeyField = $aObject['key'];
				if(!empty($sKeyField)) {
					$this->$sKeyField = $aObject['object']->id;
				}
			}

		}

	}

	/**
	 * Deaktiviert das Validieren
	 */
	public function disableValidate() {
		$this->_bDisableValidate = true;
	}
	
	/**
	 * Validates the _aData
	 * 
	 * @param Boolean $bThrowExceptions
	 * @return mixed : TRUE || The errors array
	 * @throws Exception
	 */
	public function validate($bThrowExceptions = false) {

		if($this->_bDisableValidate) {
			return true;
		}

		$aErrors = array();

		$this->_checkTableData();
/*

	TODO @MK:
		Eine neue Validierungsmethode, die in einer DB-Tabelle das Vorkommen des Values in einer Spalte prüft.
		Ist so zu sagen FOREIGN KEY Prüfung.
		Die ist eine Copy&Paste von privat und dient nur zur ungefähren Vorstellung, wie sowas umgesetzt werden könnte.
	BEI FRAGEN -> AN ALEXEY WENDEN

	case self::DB_VALUE:
	{
		$sSQL = "SELECT #sColumn AS `x`, #sColumn AS `y` FROM #sTable";
		$aSQL = array(
			'sColumn'	=> $this->_aFormat[$sKey]['settings'][$sCheck]['column'],
			'sTable'	=> $this->_aFormat[$sKey]['settings'][$sCheck]['table']
		);

		if(isset($this->_aFormat[$sKey]['settings'][$sCheck]['where']))
		{
			$sSQL .= " WHERE " . $this->_aFormat[$sKey]['settings'][$sCheck]['where'];
		}

		$aTempValues = (array)DB::fetchPairsS($sSQL, $aSQL);

		$bCheck = array_key_exists($mValue, $aTempValues);

		break;
	}
*/

		// Validate der verknüpften JoinTable Objekte aufrufen
		$this->_validateChilds($this->_aJoinedObjects, $this->_aJoinedObjectChilds, $aErrors);
		// @TODO WENN in 2 Objekten die gleiche Jointable angegeben wurde und VOR dem speichern/löschen/validieren in beiden Objekten die Jointableobjects geladen wurden
		// gibt es eine endlosschleife! da beide ihre Kinder validieren wollen und dies in einer endlosschelife endet
		$this->_validateChilds($this->_aJoinTables, $this->_aJoinTablesObjects, $aErrors);

		$sFieldPrefix = '';
		if($this->_sTableAlias) {
			$sFieldPrefix = $this->_sTableAlias.'.';
		}

		// Unique keys überprüfen
		foreach((array)(self::$_aIndexes[$this->_sTable]['UNIQUE'] ?? []) as $sIndex=>$aIndex) {
			$mUnique = $this->_isUnique($sIndex);
			if($mUnique !== true) {

				if($bThrowExceptions) {
					throw new Exception('The value of key "'.$sIndex.'" is not unique!');
				}

				foreach((array)$mUnique as $sField) {
					$aErrors[$sFieldPrefix.$sField][] = 'NOT_UNIQUE';
				}

			}
		}

		foreach($this->_aData as $sKey => $mValue) {

			if(empty(self::$_aTable[$this->_sTable])) {
				$this->_getTableFields();
			}
			
			// Leere DATE Felder vorbereiten
			if(
				(
					self::$_aTable[$this->_sTable][$sKey]['Type'] == 'date' &&
					$mValue == '0000-00-00'
				) ||
				(
					self::$_aTable[$this->_sTable][$sKey]['Type'] == 'datetime' &&
					$mValue	== '0000-00-00 00:00:00'
				)/* ||
				(
					(
						substr(self::$_aTable[$this->_sTable][$sKey]['Type'], 0, 3) == 'int'		||
						substr(self::$_aTable[$this->_sTable][$sKey]['Type'], 0, 7) == 'tinyint'	||
						substr(self::$_aTable[$this->_sTable][$sKey]['Type'], 0, 5) == 'float'		||
						substr(self::$_aTable[$this->_sTable][$sKey]['Type'], 0, 6) == 'double'		||
						substr(self::$_aTable[$this->_sTable][$sKey]['Type'], 0, 7) == 'decimal'
					) &&
					is_numeric($mValue) &&
					(float)$mValue == 0
				)*/
			) {
				$mValue = '';
			}
			
			if(!array_key_exists($sKey, (array)self::$_aTable[$this->_sTable]))
			{
				static::deleteTableCache();
				if($bThrowExceptions)
				{
					throw new Exception('Undefined table field "'.$sKey.'"!');
				}
				$aErrors[$sFieldPrefix.$sKey][] = 'UNDEFINED "'.$sKey.'"';
			}

			// Nicht $mValue sondern $this->_aData[$sKey], damit es per Referenz verändert werden kann
			$this->validateField($bThrowExceptions, $sFieldPrefix, $sKey, $mValue, $aErrors);
			
			$this->_aData[$sKey] = $mValue;
			
		}

		foreach($this->getAttributes() as $attribute) {

			$this->validateField($bThrowExceptions, $sFieldPrefix, $attribute->key, $attribute->value, $aErrors);
			
		}

		if(empty($aErrors))
		{
			$aErrors = true;
		}

		return $aErrors;

	}

	protected function validateField(bool $bThrowExceptions, string $sFieldPrefix, string $sKey, &$mValue, array &$aErrors):void {

		// ================================================== // Validate
		//
		// Nur ausführen, wenn auch ein Wert gesetzt wurde. Wenn ein Feld required und leer ist, wird der Fehler oben schon abgefangen
		if(
			isset($this->_aFormat[$sKey]['validate']) &&
			(
				(
					$mValue !== '' &&
					!is_null($mValue)
				) ||
				$mValue === false
			)&& 
			// nur aktive Einträge auf required prüfen damit löschen immer klappt
			// #4016
			(
				$this->hasActiveField() === false ||
				(int)$this->_aData['active'] === 1
			)
		) {

			$bSeparateValidation = false;

			// Die Einstellung macht nur Sinn, wenn bei 'validate' ein Array mit mehreren Einträgen existiert.
			// Bricht ab, wenn bei einem Validierungsfall ein Fehler aufgetreten ist
			if(!empty($this->_aFormat[$sKey]['validate_separate'])) {
				$bSeparateValidation = (bool) $this->_aFormat[$sKey]['validate_separate'];
			}

			if(!is_array($this->_aFormat[$sKey]['validate'])) {
				$this->_aFormat[$sKey]['validate'] = array($this->_aFormat[$sKey]['validate']);
			}
			foreach((array)$this->_aFormat[$sKey]['validate'] as $sValidate) {
				if($sValidate ==  'UNIQUE') {

					$bCheck = $this->_isUnique($sKey, true);
					if($bCheck !== true) {
						$bCheck = false;
					}

				} else {

					// TODO Evtl. noch nicht die optimalste Stelle
					// Wenn Feld ein Foreign Key mit geladenem JoinedObject ist: Jegliche Validierung überspringen, da der Wert schlicht noch nicht existiert
					if (
						!empty(self::$_aTable[$this->_sTable][$sKey]['joined_parent_key']) &&
						!empty($this->_aJoinedObjects[self::$_aTable[$this->_sTable][$sKey]['joined_parent_key']]['object'])
					) {
						continue;
					}

					$oValidator = new WDValidate();
					$oValidator->value = $mValue;
					$oValidator->check = $sValidate;

					// TODO validate kann ein Array sein, diese beiden Werte gelten aber für alle Validierungen (Index einbauen?)
					if(!empty($this->_aFormat[$sKey]['validate_value'])) {
						$oValidator->parameter = $this->_aFormat[$sKey]['validate_value'];
					} elseif(isset($this->_aFormat[$sKey]['parameter_settings'])) {
						$oValidator->parameter = $this->getValidationParameterFromSettings($this->_aFormat[$sKey]['parameter_settings']);
					}						

					$bCheck = $oValidator->execute();

					if($bCheck === true) {
						// Der Validator kann auch Werte verändern / korrekt formatieren
						$mValue = $oValidator->value;
					}

				}

				if(!$bCheck) {

					if($bThrowExceptions) {
						throw new Exception('The value "'.$mValue.'" of key "'.$sKey.'" is not valid! (Check: '.$sValidate.', Class: '.get_class($this).')');
					}

					$aErrors[$sFieldPrefix.$sKey][] = 'INVALID_'.$sValidate;

					// Wenn der key 'validate_separate' gesetzt wurde, dann muss hier abgebrochen werden.
					if($bSeparateValidation === true) {
						break;
					}

				}

			}

			// Wenn ein Fehler vorhanden ist für dieses Feld, Durchlauf abrechen und mit nächstem Eintrag fotfahren
			if(!empty($aErrors[$sFieldPrefix.$sKey])) {
				return;
			}

		}

		if(
			(
				(
					$mValue === '' ||
					is_null($mValue)
				)
				&& isset($this->_aFormat[$sKey]['required'])
				&& $this->_aFormat[$sKey]['required'] == true
				&& 
				// nur aktive Einträge auf required prüfen damit löschen immer klappt
				// #4016
				(
						$this->_aData['active'] === null ||
						$this->_aData['active'] == 1 # Darf NICHT identisch verglichen werden! #4648
				)
			)/*
			 * Auch leere Werte können einen gültigen Unique-Key darstellen ||
			(
				empty($mValue) &&
				self::$_aTable[$this->_sTable][$sKey]['Key'] == 'UNIQUE'
			)*/
		) {
			if($bThrowExceptions)
			{
				throw new Exception('The value of "'.$sKey.'" cannot be empty ('.$this->_sTable.')!');
			}
			$aErrors[$sFieldPrefix.$sKey][] = 'EMPTY';
		}

		if(
			isset($this->_aFormat[$sKey]['unique']) &&
			$this->_aFormat[$sKey]['unique'] == 1 &&
			$this->_isUnique($sKey, true) !== true
		) {
			if($bThrowExceptions)
			{
				throw new Exception('The value of key "'.$sKey.'" is not unique ('.$this->_sTable.')!');
			}
			$aErrors[$sFieldPrefix.$sKey][] = 'NOT_UNIQUE';
		}

		if(
			self::$_aTable[$this->_sTable][$sKey]['Type'] == 'date' &&
			!empty($mValue)
		) {
			$bCheck = WDDate::isDate($mValue, WDDate::DB_DATE);
			if(!$bCheck) {
				if($bThrowExceptions) {
					throw new Exception('The value of key "'.$sKey.'" is no valid date ('.$this->_sTable.')!');
				}
				$aErrors[$sFieldPrefix.$sKey][] = 'INVALID_DATE';
			}
		}

		if(
			self::$_aTable[$this->_sTable][$sKey]['Type'] == 'datetime' &&
			!empty($mValue)
		) {
			$bCheck = WDDate::isDate($mValue, WDDate::DB_DATETIME);
			if(!$bCheck) {
				if($bThrowExceptions) {
					throw new Exception('The value of key "'.$sKey.'" is no valid datetime ('.$this->_sTable.')!');
				}
				$aErrors[$sFieldPrefix.$sKey][] = 'INVALID_DATE_TIME';
			}
		}

		if(self::$_aTable[$this->_sTable][$sKey]['Type'] == 'timestamp')
		{
			if
			(
				self::$_aTable[$this->_sTable][$sKey]['Default'] == 'CURRENT_TIMESTAMP' && !is_null($mValue) ||
				(
					self::$_aTable[$this->_sTable][$sKey]['Default'] != 'CURRENT_TIMESTAMP' &&
					self::$_aTable[$this->_sTable][$sKey]['Default'] != $mValue &&
					!empty($mValue)
				)
			) {

				if($this->_getPreparedDate($mValue) === false)
				{
					if($bThrowExceptions)
					{
						throw new Exception('Invalid value of TIMESTAMP field "'.$sKey.'": '.$mValue);
					}
					$aErrors[$sFieldPrefix.$sKey][] = 'INVALID_DATE';
				}

			}
		}

		if
		(
			!empty($mValue) &&
			(
				substr(self::$_aTable[$this->_sTable][$sKey]['Type'], 0, 3) == 'int'		||
				substr(self::$_aTable[$this->_sTable][$sKey]['Type'], 0, 7) == 'tinyint'	||
				substr(self::$_aTable[$this->_sTable][$sKey]['Type'], 0, 8) == 'smallint'	||
				substr(self::$_aTable[$this->_sTable][$sKey]['Type'], 0, 5) == 'float'		||
				substr(self::$_aTable[$this->_sTable][$sKey]['Type'], 0, 6) == 'double'		||
				substr(self::$_aTable[$this->_sTable][$sKey]['Type'], 0, 7) == 'decimal'
			)
		)
		{


			if(!is_numeric($mValue)) {

				if($bThrowExceptions)
				{
					throw new Exception('The value of "'.$sKey.'" is not numeric!');
				}
				$aErrors[$sFieldPrefix.$sKey][] = 'NOT_NUMERIC';

			} else {

				$bMatch = preg_match('/^([a-z]+)\(([0-9]+)\)( unsigned)?/', self::$_aTable[$this->_sTable][$sKey]['Type'], $aTypeMatch);

				if(!empty($aTypeMatch)) {

					$bUnsigned = !empty($aTypeMatch[3]);

					$aMax = DB::getMaxIntegerValue($aTypeMatch[1], $bUnsigned);

					if($mValue < $aMax[0]) {
						if($bThrowExceptions)
						{
							throw new Exception('The value of "'.$sKey.'" is to small!');
						}
						$aErrors[$sFieldPrefix.$sKey][] = 'TO_SMALL';
					} elseif($mValue > $aMax[1]) {
						if($bThrowExceptions)
						{
							throw new Exception('The value of "'.$sKey.'" is to high!');
						}
						$aErrors[$sFieldPrefix.$sKey][] = 'TO_HIGH';
					}

				}

			}

		}

		if(substr(self::$_aTable[$this->_sTable][$sKey]['Type'], 0, 7) == 'varchar')
		{
			$iLength = str_replace('varchar(', '', str_replace(')', '', self::$_aTable[$this->_sTable][$sKey]['Type']));

			if(mb_strlen($mValue, 'utf-8') > $iLength)
			{
				if($bThrowExceptions)
				{
					throw new Exception('The content of value of "'.$sKey.'" is to long!');
				}
				$aErrors[$sFieldPrefix.$sKey][] = 'TO_LONG';
			}
		}

	}

	/**
	 * Liefert den Wert für die Valdierung über $_aFormats
	 * 
	 * array(
	 *		'type' => 'field'/'method',
	 *		'source' => 'name'/'getName'
	 * )
	 * 
	 * @param array $aSettings
	 * @return mixed
	 * @throws Exception
	 */
	final protected function getValidationParameterFromSettings(array $aSettings) {
		$aRequiredArrayKeys = array('type', 'source');
		
		$aUndefinedRequiredSettings = array_diff($aRequiredArrayKeys, array_keys($aSettings));
		
		if(!empty($aUndefinedRequiredSettings)) {
			throw new Exception('Missing settings "'.implode(', ', $aUndefinedRequiredSettings).'" for validation in class "'.get_class($this).'"');
		}
		
		$sSource = (string) $aSettings['source'];
		
		switch($aSettings['type']) {
			case 'field':
				$mParameter = $this->$sSource;
				break;
			case 'method':
				$mParameter = $this->$sSource();
				break;
			default:
				$mParameter = '';
		}
		
		return $mParameter;
	}
	
/* ==================================================================================================== */

	/**
	 * Formats the OUTPUT by _aFormat
	 * 
	 * @return string : Prepared SELECT for the query
	 */
	protected function _formatSelect($bLoadSingle=false)
	{
		if(!is_array($this->_aFormat) || empty($this->_aFormat))
		{
			return "";
		}

		$this->_checkTableData();

		$sFormat = "";
		foreach((array)$this->_aFormat as $sKey => $aValue) {

			if(isset($aValue['format'])) {

				$sFieldString = '';
				if(!empty($this->_sTableAlias)) {
					$sFieldString .= '`'.$this->_sTableAlias.'`.';
				}
				$sFieldString .= '`'.$sKey.'`';

				switch($aValue['format'])
				{
					case 'TIMESTAMP':
					{
						if(!$bLoadSingle) {
							$sFormat .= ", UNIX_TIMESTAMP(".$sFieldString.") AS `".$sKey."`";
						}
						break;
					}
					case 'AES':
					{
						if(!isset($aValue['second']) || empty($aValue['second']) || !is_scalar($aValue['second']))
						{
							throw new Exception('The second decoration attribute does not exists!');
						}
						if(self::$_aTable[$this->_sTable][$sKey]['Type'] != 'blob')
						{
							throw new Exception('Cannot decrypt non "blob" column!');
						}

						if(!isset($this->_aSettings['forbid']['load'][$sKey]) || $this->_aSettings['forbid']['load'][$sKey] != 'AES')
						{
							$sFormat .= ", AES_DECRYPT(".$sFieldString.", '".$aValue['second']."') AS `".$sKey."`";
						}
						break;
					}
					case 'TIME':
					case 'DATE':
					case 'DATE_TIME':
					case 'PASSWORD':
					case 'MD5':
					case 'ENCRYPTED':
					case 'JSON':
					{
						break;
					}
					default: throw new Exception('Unknown format decorator! ('.$aValue['format'].')');
				}
			}
		}

		return $sFormat;

	}


	/**
	 * Prepares a date
	 * 
	 * @param mixed : The timestamp in following formats
	 * 						- The date as "YmdHis" >>> "20001122223344" (22.11.2000, 22:33:44)
	 * 						- The unix timestamp >>> "1232201652"
	 * 						- DB timestamp >>> "2000-11-22 22:33:44"
	 * 
	 * @return string : Prepared timestamp in DB format >>> "2000-11-22 22:33:44"
	 */
	private function _getPreparedDate($mValue)
	{
		if(is_numeric($mValue))
		{
			// Format: "20001122223344" >>> 22.11.2000, 22:33:44
			if(strlen($mValue) == 14)
			{
				$mValue =
					substr($mValue, 0, 4).'-'.
					substr($mValue, 4, 2).'-'.
					substr($mValue, 6, 2).' '.
					substr($mValue, 8, 2).':'.
					substr($mValue, 10, 2).':'.
					substr($mValue, 12);
				return $this->_getPreparedDate($mValue);
			}
			// Format: The unix timestamp
			else if($mValue >= 0)
			{
				return $this->_getPreparedDate(date('Y-m-d H:i:s', $mValue));
			}
			else
			{
				return false;
			}
		}
		// Format: DB timestamp >>> "2000-11-22 22:33:44"
		else if(is_string($mValue) && strlen($mValue) == 19)
		{
			$aCheck = @explode(' ', $mValue);
			$aCheck[0] = @explode('-', (string)$aCheck[0]);
			$aCheck[1] = @explode(':', (string)$aCheck[1]);

			if
			(
				// Check the number of entries
				( count($aCheck) != 2 || count($aCheck[0]) != 3 || count($aCheck[1]) != 3 ) ||
				// Check the length of date numbers
				( strlen($aCheck[0][0]) != 4 || strlen($aCheck[0][1]) != 2 || strlen($aCheck[0][2]) != 2 ) ||
				// Check the size underflow of date numbers
				( $aCheck[0][0] < 1901 || $aCheck[0][1] < 1 || $aCheck[0][2] < 1 ) ||
				// Check the size overflow of time numbers
				( $aCheck[0][0] > 2037 || $aCheck[0][1] > 12 || $aCheck[0][2] > 31 ) ||
				// Check the length of time numbers
				( strlen($aCheck[1][0]) != 2 || strlen($aCheck[1][1]) != 2 || strlen($aCheck[1][2]) != 2 ) ||
				// Check the size underflow of time numbers
				( $aCheck[1][0] < 0 || $aCheck[1][1] < 0 || $aCheck[1][2] < 0 ) ||
				// Check the size overflow of time numbers
				( $aCheck[1][0] > 23 || $aCheck[1][1] > 59 || $aCheck[1][2] > 59 )
			)
			{
				if($mValue == '0000-00-00 00:00:00')
				{
					return $mValue;
				}

				return false;
			}
			else
			{
				return $mValue;
			}
		}
		// Format: DB timestamp >>> "2000-11-22"
		else if(is_string($mValue) && strlen($mValue) == 10)
		{

			$aCheck = @explode('-', (string)$mValue);

			if
			(
				// Check the length of date numbers
				( strlen($aCheck[0]) != 4 || strlen($aCheck[1]) != 2 || strlen($aCheck[2]) != 2 ) ||
				// Check the size underflow of date numbers
				( $aCheck[0] < 1900 || $aCheck[1] < 1 || $aCheck[2] < 1 ) ||
				// Check the size overflow of time numbers
				( $aCheck[0] > 9999 || $aCheck[1] > 12 || $aCheck[2] > 31 )
			)
			{
				if($mValue == '0000-00-00')
				{
					return $mValue;
				}

				return false;
			}
			else
			{
				return $mValue;
			}
		}
		else
		{
			return false;
		}
	}


	/**
	 * Checks if the value of the key in _aData is unique
	 * 
	 * @param string	: The key
	 * @param mixed		: The value
	 * @return bool		: TRUE | FALSE | ARRAY
	 */
	protected function _isUnique($sIndex, $bDirect=false)
	{
		if(empty($sIndex) || !is_scalar($sIndex))
		{
			return false;
		}

		$oDB = $this->getDbConnection();

		if($bDirect) {
			$aFields = array($sIndex);
		} else {
			$aFields = self::$_aIndexes[$this->_sTable]['UNIQUE'][$sIndex];
		}

		$aSql = array(
			'table'		=> $this->_sTable,
			'id'		=> (int)$this->_aData[$this->_sPrimaryColumn]
		);

		$sSql = "
			SELECT *
			FROM #table
			WHERE ";

		$bHasFields = false;

		foreach((array)$aFields as $iField=>$sField) {

			// NULL-Werte dürfen bei Unique mehrfach vorkommen
			if($this->_aData[$sField] === null) {
				continue;
			}

			$sSql .= " #field_".$iField." = :value_".$iField." AND ";

			$aSql['field_'.$iField] = $sField;
			$aSql['value_'.$iField] = $this->_aData[$sField];
            
            $sFieldType = (string)self::$_aTable[$this->_sTable][$sField]['Type'];
            
            // Casting für Performance
            if(strpos($sFieldType, 'int') === 0){
                $aSql['value_'.$iField] = (int)$aSql['value_'.$iField];
            } else if(strpos($sFieldType, 'float') === 0){
                $aSql['value_'.$iField] = (float)$aSql['value_'.$iField];
            }

			$bHasFields = true;

		}

		$sSql .= " `id` != :id LIMIT 1
		";

		$mCheck = $oDB->queryRow($sSql, $aSql);

		if(
			$bHasFields === true &&
			!empty($mCheck)
		) {
			return $aFields;
		}

		return true;

	}

	/**
	 * Loads the data into the _aData by ID
	 *
	 * @param int : The data ID
	 */
	protected function _loadData($iDataID) {

		$oDB = $this->getDbConnection();

		if(!empty($iDataID)) {

			if(is_numeric($iDataID)) {
				$iDataID = (int)$iDataID;
			}
			
			$sFormat = $this->_formatSelect(true);

			$sTableAlias = '';
			if(!empty($this->_sTableAlias)) {
				$sTableAlias .= '`'.$this->_sTableAlias.'`';
			}

			$sSQL = "
				SELECT * {FORMAT}
				FROM
					`".$this->_sTable."`
				WHERE
					`".$this->_sPrimaryColumn."` = :id
				LIMIT 1
			";
            
            $sSQL = str_replace('{FORMAT}', $sFormat, $sSQL);
            
			$aSQL = array(
				'id' => $iDataID
			);
			
            $aData = $oDB->queryRow($sSQL, $aSQL);

			// ID ist immer INT
			// TODO Darauf sollte man sich nicht verlassen, da der Wert auch trotzdem einfach mal ein String sein kann
			if($this->_sPrimaryColumn == 'id') {
				$aData['id'] = (int)($aData['id'] ?? 0);
			}

			if(!empty($aData[$this->_sPrimaryColumn])) {

				if($this->_bDefaultData) {

					$this->_aData = $aData;

				} else {

					foreach((array)$this->_aData as $sKey => $mValue)
					{
						if(array_key_exists($sKey, $aData))
						{
							$this->_aData[$sKey] = $aData[$sKey];

							unset($aData[$sKey]);

							if(empty($aData))
							{
								break;
							}
						}
					}
				}

			}

		}

		if(
			!empty($this->_aAutoJoinTables) &&
			empty($this->_aJoinData)
		) {
			// Leere Einträge für die Join Tabellen
			foreach((array)$this->_aAutoJoinTables as $sKey=>$aTable) {
				$this->_aJoinData[$sKey] = array();
			}
		}

		$this->_aOriginalData = $this->_aData;
		$this->_aOriginalJoinData = $this->_aJoinData;

	}
    
    public function getJoinTableValues(){
        return $this->_aJoinData;
    }

	public function addJoinTable(array $aJoinData){

		// Join Tables ergänzen
		$this->_aJoinTables = $this->_aJoinTables + $aJoinData;

		// Neue Autoload Tabellen setzten
		foreach((array)$aJoinData as $sKey=>$aTable) {

			if(
				!isset($aTable['autoload']) ||
				$aTable['autoload'] === true
			) {
				$this->_aAutoJoinTables[$sKey] = $aTable;
			}

		}

	}
	
	/**
	 * prüft, ob in der Datenbank das Feld 'active' gesetzt wurde
	 * @return boolean
	 */
	public function hasActiveField(){
		
		$this->_checkTableData();
		
		if(isset(self::$_aTable[$this->_sTable]['active'])){
			return true;
		}
		
		return false;
	}
	
	/**
	 * @return string
	 */
	public function getSortColumn() {
		return $this->_sSortColumn;
	}
	
	/**
	 * prüft, ob in der Datenbank das Sortierfeld vorhanden ist
	 * @return boolean
	 */
	public function hasSortColumn(){
		
		$this->_checkTableData();
		
		if(isset(self::$_aTable[$this->_sTable][$this->_sSortColumn])){
			return true;
		}
		
		return false;
	}

	/**
	 * Liefert ein Array mit allen über eine JoinTable verknüpften Objekten
	 * 
	 * @param string $sKey
	 * @return array
	 */
	public function getJoinTableObjects($sKey) {

		$aTable = $this->_getJoinTableObjectConfig($sKey);

		$aReturn = (array)$this->$sKey;
		
		// Wenn noch keine Objekte vorhanden, dann leeres Array setzen
		if(!isset($this->_aJoinTablesObjects[$sKey])) {
			$this->_aJoinTablesObjects[$sKey] = array();
		}

		foreach($aReturn as $iForeignId) {
			// Objekt wird nur neu geholt, wenn noch nicht im Array vorhanden
			if(!isset($this->_aJoinTablesObjects[$sKey][$iForeignId])) {

				$oObject = Factory::getInstance($aTable['class'], (int)$iForeignId);

				// TODO Es gibt das Attribut check_active, aber hier wird einfach immer fest auf active geprüft
				$bHasActiveField = $oObject->hasActiveField();
				
				// Objekt nur hinzufügen, wenn 'active' auf 1 steht
				if(
					$bHasActiveField === false ||
					$oObject->active == 1
				) {					
					$this->_aJoinTablesObjects[$sKey][$iForeignId] = $oObject;
				}
			}
		}

		return $this->_aJoinTablesObjects[$sKey];

	}

	/**
	 * Liefert ein leeres JoinTable-Objekt und merkt sich die Verknüpfung zur JoinTable
	 * 
	 * @param string $sKey
	 * @param int $iId
	 * @param bool $bCacheEmptyObject
	 * @return WDBasic
	 */
	public function getJoinTableObject($sKey, $iId = 0, $bCacheEmptyObject = true) {

		$iNewKey = null;
		
		$aTable = $this->_getJoinTableObjectConfig($sKey);

		// Sicherstellen, dass die JoinTable schon aufgerufen wurde
		if(!isset($this->_aJoinTablesLoaded[$sKey])) {
			$this->$sKey;
		}
		
		if(
			$iId != 0 &&
			isset ($this->_aJoinTablesObjects[$sKey][$iId])
		){
			return $this->_aJoinTablesObjects[$sKey][$iId];
		} else if($iId < 0){
			$iNewKey = $iId;
			$iId = 0;
		}

		$oObject = Factory::getInstance($aTable['class'], $iId);

		if(
			$oObject->id <= 0 &&
			$bCacheEmptyObject === false	
		) {
			// Wenn das leere Objekt nicht in den Cache geschrieben werden soll
			return $oObject;
		}
		
		if($oObject->id <= 0) {

			if(!empty($aTable['bidirectional'])) {
				$oObject->addJoinTableObject($aTable['bidirectional'], $this);
			}

			// Wenn noch keine Objekte vorhanden, dann leeres Array setzen
			if(!isset($this->_aJoinTablesObjects[$sKey])) {
				$this->_aJoinTablesObjects[$sKey] = array();
			}

			if($iNewKey === null){
				// Neue Einträge müssen einen negativen Key haben, damit vorhandene Objekte nicht beeinflusst werden können
				$aKeys = array_keys($this->_aJoinTablesObjects[$sKey]);
				$iNewKey = $this->_getLowestFromArray($aKeys);
			}

			// Object in Array speichern
			$this->_aJoinTablesObjects[$sKey][$iNewKey] =& $oObject;

			$this->_completeJoinTableObjects($sKey);		
		} else {
			$this->addJoinTableObject($sKey, $oObject);
		}

		return $oObject;

	}

	protected function _getLowestFromArray(array $aKeys) {

		if (!empty($aKeys)) {
			sort($aKeys, SORT_NUMERIC);
			$iLowestKey = reset($aKeys);
		}

		if (
			!isset($iLowestKey) ||
			$iLowestKey > 0
		) {
			$iLowestKey = 1;
		}

		// Der erste Wert muss 0 sein
		return intval($iLowestKey) - 1;
	}
	
	/**
	 * Entfernt einen Eintrag aus dem JoinTableObject Speicher
	 * Löscht nicht das Objekt an sich!
	 * 
	 * @param string $sKey
	 * @param int $iKey 
	 */
	public function removeJoinTableObject($sKey, $mObject) {
		
		// Gibt es Objekte für den Key?
		if(isset($this->_aJoinTablesObjects[$sKey])) {

			$iKey = null;

			// Wenn ein Objekt übergeben wurde
			if(is_object($mObject)) {

				if(!empty($this->_aJoinTablesObjects[$sKey])) {

					foreach($this->_aJoinTablesObjects[$sKey] as $iChildKey=>$oObject) {
						if($oObject === $mObject) {
							$iKey = $iChildKey;
							break;
						}
					}

				}

			} else {

				$iKey = $mObject;

			}

			// Gibt es den Eintrag?
			if(
				$iKey !== null &&	
				isset($this->_aJoinTablesObjects[$sKey][$iKey])
			) {

				unset($this->_aJoinTablesObjects[$sKey][$iKey]);
				$iJoinTableDataKey = array_search($iKey, (array)$this->_aJoinData[$sKey]);
				// Entsprechende ID aus dem JoinTable löschen
				if($iJoinTableDataKey !== false) {
					unset($this->_aJoinData[$sKey][$iJoinTableDataKey]);
				}
			} else {
				throw new Exception('No jointable entry found for jointable "'.$sKey.'" and key "'.$iKey.'"!');
			}

		} else {
			throw new Exception('No jointable found for jointable "'.$sKey.'"!');
		}

	}
	
	/**
	 * Fügt einen JoinTable-Eintrag als Objekt hinzu
	 * Wenn das passiert muss sichergestellt sein, dass alle Objekte im Array sind
	 * @param string $sKey
	 * @param WDBasic $oObject
	 * @param int $iKey
	 * @throws Exception
	 */
	public function addJoinTableObject($sKey, $oObject, $iKey = 0) {

		$aTable = $this->_getJoinTableObjectConfig($sKey);
		
		// Klassenname überprüfen
		if($aTable['class'] != get_class($oObject)) {
			throw new Exception('Object "'.get_class($oObject).'" is no instance of class "'.$aTable['class'].'"');
		}

		// Sichergehen das die Join Table schon mind. 1 mal geladen wurde
		// da ansonsten array_search einen Fehler werfen würde
		if($this->_aJoinData[$sKey] == null){
			$this->$sKey;
		}

		if(!isset($this->_aJoinTablesObjects[$sKey])) {
			$this->_aJoinTablesObjects[$sKey] = array();
		}
		
		if($iKey === 0) {			
			if($oObject->id <= 0) {
				// Neue Einträge müssen einen negativen Key haben, damit vorhandene Objekte nicht beeinflusst werden können
				$aKeys = array_keys($this->_aJoinTablesObjects[$sKey]);
				$iKey = (int) $this->_getLowestFromArray($aKeys);
			} else {
				$iKey = (int) $oObject->id;
			}
		}
		
		// Objekt ergänzen
		$this->_aJoinTablesObjects[$sKey][$iKey] = $oObject;

		// Prüfen, ob Objekt schon in Array steht
		$iJoinTableDataKey = array_search($iKey, $this->_aJoinData[$sKey]);

		// Entsprechende ID in die JoinTable setzen
		if($iJoinTableDataKey === false) {
			// ID ergänzen
			$this->_aJoinData[$sKey][] = $iKey;
		}
		
		$this->_completeJoinTableObjects($sKey, $iKey);
	}
	
	/**
	 * JoinTable-Objects vervollständigen
	 * @param string $sKey
	 * @param mixed $mObjectKey
	 */
	protected function _completeJoinTableObjects($sKey, $mObjectKey=null) {
		$aTable = $this->_getJoinTableObjectConfig($sKey);

		if(is_array($this->_aJoinData[$sKey])) {
			foreach($this->_aJoinData[$sKey] as $iObjectId) {
				if(
					!isset($this->_aJoinTablesObjects[$sKey][$iObjectId]) &&
					(int)$mObjectKey !== (int)$iObjectId
				) {
					$oObject = Factory::getInstance($aTable['class'], $iObjectId);

					$bHasActiveField = $oObject->hasActiveField();
					
					// Objekt nur hinzufügen, wenn 'active' auf 1 steht
					if(
						$bHasActiveField === false ||
						$oObject->active == 1
					) {
						$this->_aJoinTablesObjects[$sKey][$iForeignId] = $oObject;
					}
				}
			}
		}
		
	}
	
	/**
	 * Prüft die JoinTable auf Eignung und gibt die Konfiguration zurück
	 * @param JoinTable Key
	 * @return array
	 * @throws Exception 
	 */
	protected function _getJoinTableObjectConfig($sKey) {

		$aTable = $this->_aJoinTables[$sKey];

		if(empty($aTable)) {
			throw new Exception('JoinTable "'.$sKey.'" in class "'.get_class($this).'" does not exists!');
		}
		
		// Objekte bei JoinTable gehen nur, wenn als foreign_key_field ein Feld angegeben ist
		if(
			empty($aTable['class']) ||
			empty($aTable['foreign_key_field']) ||
			!is_string($aTable['foreign_key_field'])
		) {
			throw new Exception('Classname or foreign key field for join table "'.$sKey.'" is missing or foreign key field is not a string!');
		}

		return $aTable;

	}

	/**
	 * Prüft, ob eine JoinTable existiert und gibt diese zurück
	 * @param string $sKey
	 * @return array 
	 */
	public function getJoinTable($sKey) {
		
		$aTable = $this->_aJoinTables[$sKey];

		if(!empty($aTable)) {
			return $aTable;
		}

		return false;
		
	}
	
	public function getJoinTableData($sKey) {

		$aTable = $this->_aJoinTables[$sKey];

		if(!empty($aTable)) {
			$aReturn = $this->_getJoinTableData($aTable);
			return $aReturn;
		}

		return false;
	}

	protected function _getJoinTableData($aTable) {

		$aReturn = array();

		if($this->_aData[$this->_sPrimaryColumn] <= 0)
		{
			// Winning of performance
			return $aReturn;
		}

		$oDB = $this->getDbConnection();

		$aKeys = array($aTable['primary_key_field']=>(int)$this->_aData[$this->_sPrimaryColumn]);

		if(!empty($aTable['static_key_fields'])){
			foreach((array)$aTable['static_key_fields'] as $sField => $mValue){
				$aKeys[$sField] = $mValue;
			}
		}

		if(isset($aTable['check_active']) && $aTable['check_active']) {
			$aKeys['active'] = 1;
		}
 		
		$sSortColumn = false;
		if(isset($aTable['sort_column'])){
			$sSortColumn = $aTable['sort_column'];
		}
		
		if(
			isset($aTable['foreign_key_field']) &&
			!is_array($aTable['foreign_key_field'])
		) {
			$aReturn = $oDB->getJoin($aTable['table'], $aKeys, $aTable['foreign_key_field'], $sSortColumn);
		} else {
			$aReturn = $oDB->getJoin($aTable['table'], $aKeys, false, $sSortColumn);
		}

		return $aReturn;
	}

	/**
	 * Prepares the _aData fields
	 * @todo Auslagern und nicht ans Objekt hängen, sondern an die Tabelle
	 */
	protected function _getTableFields() {

		$oDB = $this->getDbConnection();

		// Fremdschlüssel der JoinedObjects prüfen
		$aJoinedObjectKeys = array();
		foreach($this->_aJoinedObjects as $sAlias=>$aJoinedObject) {
			
			// Nur Typ parent berücksichtigen
			if(
				empty($aJoinedObject['type']) ||
				$aJoinedObject['type'] !== 'child'
			) {
				$aJoinedObjectKeys[$aJoinedObject['key']] = $sAlias;
			}
			
		}

		// Get the table description
		try {

			//self::$_aTable[$this->_sTable] = $this->_oDb->describe($this->_sTable);
			$sCacheKey = 'wdbasic_table_description_'.$this->_sTable;
			
			$aTableDescription = WDCache::get($sCacheKey);

			if($aTableDescription === null) {
			
				$aTableDescription = array();
				$aTableDescription['table'] = $oDB->queryRows("DESCRIBE `".$this->_sTable."`");
				$aTableDescription['indexes'] = $oDB->queryRows("SHOW INDEXES FROM `".$this->_sTable."`");

				// Die Tabellendefinition darf nicht leer sein!
				if(
					empty($aTableDescription['table'])
				) {
					throw new Exception('Request table info failed!');
				}
				
				WDCache::set($sCacheKey, 3600, $aTableDescription);

			}

			self::$_aTable[$this->_sTable] = $aTableDescription['table'];
			self::$_aIndexes[$this->_sTable] = $aTableDescription['indexes'];

		}
		catch(Exception $e)
		{
			throw new Exception('The DB table "'.$this->_sTable.'" does not exists!');
		}

		// Set the default values and redesign the _aTable
		$aTable = array();
		foreach((array)self::$_aTable[$this->_sTable] as $iKey => $aColumn)
		{
			
			if(isset($aJoinedObjectKeys[$aColumn['Field']])) {
				$aColumn['joined_parent_key'] = $aJoinedObjectKeys[$aColumn['Field']];
			}

			// MariaDB liefert das als current_timestamp(), MySQL als CURRENT_TIMESTAMP
			if(stripos((string)$aColumn['Default'], 'current_timestamp') !== false) {
				$aColumn['Default'] = 'CURRENT_TIMESTAMP';
			}

			$aTable[$aColumn['Field']] = $aColumn;
			
		}

		$aIndexes = array();
		foreach((array)self::$_aIndexes[$this->_sTable] as $iKey => $aValue) {

			$aTable[$aValue['Column_name']]['Index'] = $aValue['Key_name'];

			if($aValue['Key_name'] == 'PRIMARY') {
				$aTable[$aValue['Column_name']]['Key'] = 'PRIMARY';
			} else if((int)$aValue['Non_unique'] == 0) {
				$aTable[$aValue['Column_name']]['Key'] = 'UNIQUE';
				$aTable[$aValue['Column_name']]['Unique'] = true;
			} else {
				$aTable[$aValue['Column_name']]['Key'] = 'INDEX';
			}

			if($aValue['Key_name'] == 'PRIMARY') {
				$aIndexes[$aTable[$aValue['Column_name']]['Key']][] = $aValue['Column_name'];
			} else {
				$aIndexes[$aTable[$aValue['Column_name']]['Key']][$aValue['Key_name']][] = $aValue['Column_name'];
			}
		}
	
		// Redesign the _aTable
		self::$_aTable[$this->_sTable] = $aTable;
		self::$_aIndexes[$this->_sTable] = $aIndexes;

	}

	/**
	 * Prepares the _aData fields
	 */
	protected function _prepareDataFields() {

		$this->_checkTableData();

		// Set the default values and redesign the _aTable
		foreach((array)self::$_aTable[$this->_sTable] as $sKey => $aColumn)	{

			if($this->_bDefaultData) {

				if($aColumn['Default'] == 'CURRENT_TIMESTAMP') {
					$this->_aData[$aColumn['Field']] = null;
				} else if($aColumn['Key'] == 'PRIMARY') {
					// Wenn Primary-Spalte "id" ist, dann "0" als Wert setzen
					if($aColumn['Field'] == 'id') {
						$this->_aData[$aColumn['Field']] = 0;
					} else {
						$this->_aData[$aColumn['Field']] = '';
					}
				} else {

					if(
						$aColumn['Null'] == 'NO' &&
						is_null($aColumn['Default'])
					) {
						$aColumn['Default'] = '';
					}
					// damit bei neuen Objekten welche kein Load Data aufrufen
					// auch der "default" wert drin steht, bei Date feldern steht das nicht unter "Default" drin!
					if(
						$aColumn['Type'] == 'date' &&
						empty($aColumn['Default']) &&
						$aColumn['Null'] !== 'YES'
					) {
						$aColumn['Default'] = '0000-00-00';
					}

					if(
						$aColumn['Type'] == 'timestamp' &&
						$aColumn['Null'] != 'YES'
					) {
						$this->_aData[$aColumn['Field']] = 0;
					} else {
						$this->_aData[$aColumn['Field']] = $aColumn['Default'];
					}

					// Bit zu Integer konvertieren
					if(
						// Vorab strpos, da das hier tausende Male durchgelaufen werden kann
						strpos($aColumn['Type'], 'bit') !== false &&
						Illuminate\Support\Str::startsWith($aColumn['Type'], 'bit') &&
						Illuminate\Support\Str::startsWith($aColumn['Default'], 'b\'')
					) {
						$this->_aData[$aColumn['Field']] = str_replace(['b\'', '\''], '', $aColumn['Default']);
						$this->_aData[$aColumn['Field']] = bindec($this->_aData[$aColumn['Field']]);
					}

				}

			}

			if($this->_bAutoFormat) {

				if(
					!isset($this->_aFormat[$aColumn['Field']]) ||
					!isset($this->_aFormat[$aColumn['Field']]['format'])
				) {

					$sFormat = null;
					if($aColumn['Type'] == 'timestamp') {
						$sFormat = 'TIMESTAMP';
					} elseif($aColumn['Type'] == 'time') {
						$sFormat = 'TIME';
					} elseif($aColumn['Type'] == 'date') {
						$sFormat = 'DATE';
					} elseif($aColumn['Type'] == 'datetime') {
						$sFormat = 'DATE_TIME';
					}

					if($sFormat) {

						if(!array_key_exists($aColumn['Field'], $this->_aFormat)) {
							$this->_aFormat[$aColumn['Field']] = array();
						}

						$this->_aFormat[$aColumn['Field']]['format'] = $sFormat;

					}

				}

			}

		}

	}

	/**
	 * Prepares the data for an INSERT / UPDATE
	 * 
	 * @return array : The prepared INSERT data
	 */
	protected function _prepareSaving() {

		$this->_checkTableData();

		$aInsert = array();

		// Validate
		$this->validate(true);

		foreach((array)$this->_aData as $sKey => $mValue)
		{
			
			// ================================================== // Prepare INSERT

			if($this->_aData[$this->_sPrimaryColumn] <= 0) {
				if(
					isset($this->_aFormat[$sKey]['required']) ||
					(
						!empty(self::$_aTable[$this->_sTable][$sKey]['Unique']) &&
						self::$_aTable[$this->_sTable][$sKey]['Unique'] == true
					)
				) {
					$aInsert[$sKey] = $this->_aData[$sKey];
				} elseif(
					self::$_aTable[$this->_sTable][$sKey]['Key'] == 'PRIMARY' &&
					!empty($this->_sSaveWithPrimaryValue)
				) {
					$aInsert[$sKey] = $this->_sSaveWithPrimaryValue;
				}
			}

		}
                

		// Nach Verwendung immer zurücksetzen
		$this->_sSaveWithPrimaryValue = null;
		
		return $aInsert;

	}

	public function getTableName() {
		return $this->_sTable;
	}

	public function getJoinTables($bOnlyAuto=false) {
		if($bOnlyAuto) {
			$aData = $this->_aAutoJoinTables;
		} else {
			$aData = $this->_aJoinTables;
		}
		return $aData;
	}

	/**
	 * setzt einen eintrag auf inaktiv
	 */
	public function delete() {

		$this->getAttributes(); // Definitoon setzen

		$oDB = $this->getDbConnection();

		// TODO check_active wird nicht beachtet
		foreach((array)$this->_aJoinTables as $sKey	=> $aTable) {

			if(
				isset($aTable['on_delete']) &&
				$aTable['on_delete'] == 'delete'
			) {
				$this->$sKey = array();
			}

		}

		if($this->_aData[$this->_sPrimaryColumn] <= 0){
			return false;
		}
		
		$aErrors = array();
		
		if(
			array_key_exists('active', (array)$this->_aData)
		) {
			$this->active = 0;
		}

		$mValidate = true;
		if($this->bPurgeDelete === false) {
			$mValidate = $this->validate();
		}

		if($mValidate !== true) {
			$aErrors = $mValidate;
		}

		/**
		 * Abhängigkeiten prüfen und falls vorhanden, nicht löschen!
		 */
		foreach((array)$this->_aJoinTables as $sKey=>$aTable) {

			if(
				$this->bPurgeDelete === false &&
				$aTable['delete_check'] === true
			) {
				$aJoinTableData = $this->_getJoinTableData($aTable);
				if(!empty($aJoinTableData)) {
					$aErrors[$sKey][] = 'EXISTING_JOINED_ITEMS';
				}
			}

		}

		/**
		 * Abhängige Objecte werden behandelt falls "on_delete" definiert wurde
		 */
		if(empty($aErrors)) {

			foreach((array)$this->_aJoinedObjects as $sJoindObjectAlaias => $aJoinedObjectData) {

				if($aJoinedObjectData['type'] == 'child') {

					if(
						$this->bPurgeDelete &&
						isset($aJoinedObjectData['check_active'])
					) {
						$bCheckActive = $aJoinedObjectData['check_active'];
						$this->_aJoinedObjects[$sJoindObjectAlaias]['check_active'] = false;
					}

					$aChilds = $this->getJoinedObjectChilds($sJoindObjectAlaias, false);

					if(
						$this->bPurgeDelete &&
						isset($aJoinedObjectData['check_active'])
					) {
						$this->_aJoinedObjects[$sJoindObjectAlaias]['check_active'] = $bCheckActive;
					}

					foreach((array)$aChilds as $iChildKey=>$oChild) {

						if(
							$this->bPurgeDelete &&
							$aJoinedObjectData['on_delete'] !== 'no_purge'
						) {
							$aJoinedObjectData['on_delete'] = 'cascade';
							$oChild->bPurgeDelete = true;
						}

						// Wenn "cascade" gesetzt dann Kinder löschen
						if(strtolower($aJoinedObjectData['on_delete']) == 'cascade') {

							$mSuccess = $oChild->delete();

							if($mSuccess !== true){
								if(is_array($mSuccess)){
									$aErrors = array_merge($aErrors, $mSuccess);
								}
								break;
							}

						// Detach = Verknüpfung (ID) entfernen
						} elseif(strtolower($aJoinedObjectData['on_delete']) == 'detach') {

							unset($this->_aJoinedObjectChilds[$sJoindObjectAlaias][$iChildKey]);

							$oChild->{$aJoinedObjectData['key']} = 0;
							$mSuccess = $oChild->validate();

							if($mSuccess !== true) {
								if(is_array($mSuccess)) {
									$aErrors = array_merge($aErrors, $mSuccess);
								}
								break;
							} else {
								$oChild->save();
							}

						}

					}
				
				}
			}
		}

		if(
			empty($aErrors) &&
			array_key_exists('active', (array)$this->_aData) &&
			$this->bPurgeDelete === false
		) {

			$this->fireModelEvent('deleted', false);

			$this->save();
			return true;

		} else if(empty($aErrors)) {

			if(!$this->bPurgeDelete) {
				// Eintrag vorher speichern, damit JoinTables geleert werden
				$this->save();
			} else {
				// JoinTables leeren, kein active prüfen
				// save() an der Stelle nicht ausführen, da in Ableitungen ggf. nochmal save() aufgerufen wird
				foreach($this->_aJoinTables as $aJoinTable) {
					if(
						!isset($aJoinTable['on_delete']) ||
						$aJoinTable['on_delete'] !== 'no_purge'
					) {
						$aKeys = [$aJoinTable['primary_key_field'] => (int)$this->_aData[$this->_sPrimaryColumn]];
						$oDB->updateJoin($aJoinTable['table'], $aKeys, [], false);
					}
				}
			}

			// if no active field and no errors
			// delete the entry and remove the object reference
			$sSql = "DELETE FROM #table WHERE `id` = :id LIMIT 1";
			$aSql = array(
				'table' => $this->_sTable,
				'id' => $this->_aData[$this->_sPrimaryColumn]
			);
			$oDB->preparedQuery($sSql, $aSql);

			$this->fireModelEvent('deleted', false);

			return true;
		}

		return $aErrors;
	}
 
	/**
	 * Erzeugt ein Query für eine Liste mit Items dieses Objektes
	 * @param Ext_Gui2 $oGui
	 * @param array $aLanguages
	 * @return array
	 */
	public function getListQueryData($oGui = null) {

		$aQueryData = $this->_buildQueryData($oGui);
		
		return $aQueryData;

	}

	/**
	 * Gibt einem die Möglichkeit die einzelnen SQL Parts zu verändern
	 * @see \Ext_Gui2_Data::manipulateSqlParts()
	 * @deprecated
	 * @internal
	 */
	public function manipulateSqlParts(&$aSqlParts, $sView=null) {

		

	}

	/**
	 * Liefert die Config für ein JoinedObject
	 * @param type $sAlias
	 * @return type
	 */
	public function getJoinedObjectConfig($sAlias){
		return $this->_aJoinedObjects[$sAlias];
	}

	/**
	 * get the Table Alias
	 * @return sring
	 */
	public function getTableAlias(){
		return $this->_sTableAlias;
	}

	/**
	 * @return DB
	 * @throws Exception
	 */
	public function getDbConnection() {
		
		// Init DB Connection
		// if no DB Object or the Connection is lost!
		if(
			is_null($this->_oDb) ||
			!$this->_oDb->getConnectionStatus()
		) {
			$this->_oDb = DB::getConnection($this->_sDbConnectionName);
		}

		return $this->_oDb;
	}

	/**
	 * Kopie (inklusive Kindern) erzeugen
	 *
	 * @TODO Hier muss endlich validate() eingebaut werden!
	 *
	 * @param string $sForeignIdField
	 * @param int $iForeignId
	 * @param array $aOptions
	 * @return static
	 */
	public function createCopy($sForeignIdField = null, $iForeignId = null, $aOptions = array()) {

		$this->bCreateCopy = true;

		// IDs zurücksetzen
		$this->_aJoinedObjectCopyIds = array();
		
		$this->loadAutoJoinTableData();
		$this->getAttributes();

		$sClass = get_class($this);

		/* @var $oClone static */
		$oClone = new $sClass(0, $this->_sTable);
		$oClone->bCreateCopy = $this->bCreateCopy;

		$aCloneData = $this->_aData;

		$aCloneData['id'] = 0;

		if(
			$sForeignIdField &&
			$iForeignId
		) {
			$aCloneData[$sForeignIdField] = (int)$iForeignId;
		}

		$oClone->_aData = $aCloneData;

		if(
			!isset($aOptions['copy_recursive_unique']) ||
			!is_bool($aOptions['copy_recursive_unique'])
		) {
			$aOptions['copy_recursive_unique'] = false;
		}

		$bSave = true;

		if(
			isset($aOptions['save']) &&
			$aOptions['save'] === false
		){
			$bSave = false;
		}

		/**
		 * Handhabung mit UNIQUE-Keys IM OBJEKT (oder Schein-Uniques)
		 */
		if(isset($aOptions['copy_unique'])) {

			foreach($aOptions['copy_unique'] as $sUNKey => $sFormat) {

				$oClone->_createCopyUniqueCheck($sUNKey, $sFormat, $aOptions['copy_recursive_unique']);

			}

		}

		// Cloneable JoinTables ergänzen
		foreach($this->_aJoinTables as $sJoinTableKey=>$aJoinTable) {
			if(
				isset($aJoinTable['cloneable']) &&
				$aJoinTable['cloneable'] === true
			) {
				$this->$sJoinTableKey;
			}
		}
		
		$aCloneJoinData = (array)$this->_aJoinData;

		/**
		 * JoinTables
		 */
		foreach($aCloneJoinData as $sJoinKey => $aJoinData) {

			$sTable = $this->_aJoinTables[$sJoinKey]['table'];
			$sRow	= $this->_aJoinTables[$sJoinKey]['primary_key_field'];
			$bCloneTrue = $this->_aJoinTables[$sJoinKey]['cloneable'];
			$bCloneEntites = $this->_aJoinTables[$sJoinKey]['clone_entities'];

			if($bCloneTrue === false){
				unset($aCloneJoinData[$sJoinKey]);
				continue;
			}

			if($bCloneEntites === true) {
				
				$aJoinTableEntites = $this->getJoinTableObjects($sJoinKey);
				if(!empty($aJoinTableEntites)) {
					
					foreach($aJoinTableEntites as $oJoinTableEntity) {
						
						$oJoinTableClone = $oJoinTableEntity->createCopy();
						if(!$oJoinTableClone) {
							throw new RuntimeException('No valid clone!');
						}
						$oClone->addJoinTableObject($sJoinKey, $oJoinTableClone);

					}
					
				}
				
			} else {
			
				foreach($aJoinData as &$mData) {

					if(
						is_array($mData) &&
						isset($mData['id'])
					) {
						$mData['id'] = 0;
					}

				}

				$oClone->$sJoinKey = $aJoinData;

			}

		}

        /**
         * JoinedObject parents
         */
        $aJoinedObjectParents = $this->getJoinedObjectData();

        foreach($aJoinedObjectParents as $sJoinedObjectKey=>$aJoinedObjectParent) {

            // Nur Eltern, die auch clonable sind
            if(
                $aJoinedObjectParent['type'] === 'parent' &&
                $aJoinedObjectParent['cloneable'] === true
            ) {

                $oJoinedObject = $this->getJoinedObject($sJoinedObjectKey);

                // Parent muss ein vorhandenes Objekt sein
                if($oJoinedObject->id > 0) {
                    $oJoinedObjectClone = $oJoinedObject->createCopy();
                    $sKeyField = $aJoinedObjectParent['key'];
                    $oClone->$sKeyField = (int)$oJoinedObjectClone->id;
                }

            }

        }

		// Nachfolgende Save(validierung) nur machen wenn
		// wir auch eine ID haben, manchmal haben wir auch dummy objekte die wir clonen wollen!
		if(
			$this->_aData[$this->_sPrimaryColumn] > 0 &&
			$bSave
		){

			/**
			 * @todo validate und Fehler abfangen!!!
			 */
			$oClone->saveQuietly();

		}

		// Wenn im Child bereits Joined-Objects geladen wurden, wurden diese bereits durch $oClone->save() dupliziert, werden aber durch den unteren Code nochmal dupliziert
		foreach (array_keys($oClone->_aJoinedObjects) as $sKey) {
			if (
				!empty($oClone->_aJoinedObjectChilds[$sKey]) &&
				$oClone->_aJoinedObjects[$sKey]['cloneable'] !== false
			) {
				throw new RuntimeException('createCopy: Joined objects has been loaded but must be empty to not duplicate childs: '.get_class($this).'::$'.$sKey);
			}
		}

		/**
		 * JoinedObject childs
		 */
		$aJoinedObjectChilds = $this->getJoinedObjectChilds();
		foreach($aJoinedObjectChilds as $sKey=>$aJoinedObjectChild) {

			$aJoinedObjectData = $this->_aJoinedObjects[$sKey];

			if($aJoinedObjectData['cloneable'] !== false) {

				foreach($aJoinedObjectChild as $iJoinedObjectId=>$oJoinedObject) {

					$oJoinedObjectClone = $oJoinedObject->createCopy($aJoinedObjectData['key'], $oClone->id);
					$this->_aJoinedObjectCopyIds[$sKey][$oJoinedObject->id] = $oJoinedObjectClone->id;

				}
			}
		}

		$this->bCreateCopy = false;
		$oClone->bCreateCopy = $this->bCreateCopy;
		
		return $oClone;
	}
	
	/**
	 * Gibt die Zuordnungsliste vom Kopieren der JoinedObjects zurück
	 * @return array
	 */
	public function getJoinedObjectCopyIds() {
		
		return (array)$this->_aJoinedObjectCopyIds;		
		
	}
	
	/**
	 * @param string $sKey
	 * @return array
	 */
	public function getJoinedObjectChildsKeyMapping($sKey) {

		$this->_checkJoinedObject($sKey, 'child');
		
		if(!isset($this->aJoinedObjectChildKeyMapping[$sKey])) {
			return array();
		}
		
		return $this->aJoinedObjectChildKeyMapping[$sKey];
	}


	/**
	 * Recursive check for unique fields for the copy function
	 */
	protected function _createCopyUniqueCheck($sUNKey, $sFormat, $bResursive)
	{
		$bIsUnique = $this->_isUnique($sUNKey, true);
		
		// Anstatt eines erwarteten false, gibt _isUnique() ein Array zurück...
		if(is_array($bIsUnique)) {

			$sValue = $sFormat;

			if(strpos($sValue, ':random8') !== false){
				$sValue = str_replace(':random8', \Util::generateRandomString(8), $sValue);
			}

			if(strpos($sValue, ':random16') !== false){
				$sValue = str_replace(':random16', \Util::generateRandomString(16), $sValue);
			}

			if(strpos($sValue, ':random32') !== false){
				$sValue = str_replace(':random32', \Util::generateRandomString(32), $sValue);
			}

			if(strpos($sValue, ':value') !== false){
				$sValue = str_replace(':value', $this->_aData[$sUNKey], $sValue);
			}

			$this->_aData[$sUNKey] = $sValue;

			if($bResursive) {
				$bUniqueCheck = $this->_createCopyUniqueCheck($sUNKey, $sFormat, true);
			}
			
		}
	}

	/**
	 * Erstellt eine Instanz ohne einen Query abzufeuern, in dem die Werte als
	 * Array übergeben werden
	 * Das Object wird nicht in den Cache geschrieben, da es eventuell nicht
	 * vollständig ist.
	 *
	 * Achtung: _aOriginalData wird nicht befüllt; getIntersectionData arbeitet somit auch nicht korrekt!
	 *
	 * Funktioniert erst ab PHP 5.3 wegen Später statischer Bindung
	 *
	 * @author MK
	 * @param array $aData
	 * @throws UnexpectedValueException
	 * @return static
	 */
	public static function getObjectFromArray(array $aData) {

		/**
		 * Falls Created or Changed ein Timestamp ist,
		 * wird dieses zu einem MySQL Datetime Format
		 * umgeändert
		 * @param $sField
		 */
		$oConvertToDateTime = function($sField) use(&$aData) {
			if(
				isset($aData[$sField]) &&
				is_numeric($aData[$sField])
			) {
				$dDateTime = DateTime::createFromFormat('U', $aData[$sField]);
				$aData[$sField] = $dDateTime->format('Y-m-d H:i:s');
			}
		};
		$oConvertToDateTime('changed');
		$oConvertToDateTime('created');

		// Leeres Objekt holen
		$sClass = get_called_class();
		/* @var $oObject WDBasic */
		$oObject = new $sClass();
		
		$sPrimaryColumn = $oObject->getPrimaryColumn();

		// ID muss übergeben werden
		if(!isset($aData[$sPrimaryColumn])) {
			throw new UnexpectedValueException('Value of "id" is missing!');
		}

		// Prüfen, ob das Objekt schon im Instanz-Cache ist
		if(
			!empty($aData[$sPrimaryColumn]) &&
			isset(self::$aInstance[$sClass][$aData[$sPrimaryColumn]])
		) {
			return self::$aInstance[$sClass][$aData[$sPrimaryColumn]];
		}

		// Prüfen, ob alle übergebenen Werte in _aData vorkommen und ob alle Daten übergeben wurden
		// TODO mit array_diff_key() lösen
		$aTest = $oObject->_aData;
		foreach($aData as $sKey => $mValue) {
			if(!array_key_exists($sKey, $oObject->_aData)) {
				static::deleteTableCache(); // Totalen Absturz verhindern, wenn neue Spalten bei wichtigen Objekten dazugekommen sind
				throw new UnexpectedValueException('Key "'.$sKey.'" of class "'.get_class($oObject).'" is not available!');
			} else {
				unset($aTest[$sKey]);
			}
		}

		// @TODO _aOriginalData wird nicht befüllt
		// _aData auf die übergebenen Werte reduzieren
		$oObject->_aData = $aData;
		$oObject->_aOriginalData = $aData;

		/**
		 * Wenn das Array $aTest leer ist, dann ist das Objekt vollständig und kann gecached werden
		 * @todo: Klappt nicht, wenn die getInstance abgeleitet ist, da $aInstance dann in der Ableitung definiert ist
		 */
		if(
			$oObject->id > 0 &&
			empty($aTest)
		) {
			self::$aInstance[$sClass][$oObject->id] = $oObject;
		}

		return $oObject;

	}

	public function getData($sName=false) {

		if($sName !== false) {
			if(array_key_exists($sName, $this->_aData)) {
				return $this->_aData[$sName];
			} else {
				throw new Exception('Requested data "'.$sName.'" of class "'.  get_class($this).'" do not exists!');
			}
		} else {
			return $this->_aData;
		}

	}

	public function getOriginalData($sName=false) {

		if($sName !== false) {
			if(array_key_exists($sName, $this->_aOriginalData)) {
				return $this->_aOriginalData[$sName];
			} else {
				throw new Exception('Requested data do not exists!');
			}
		} else {
			return $this->_aOriginalData;
		}

	}

	/**
	 * Checks existence of original join data
	 * @param string $name
	 * @return bool
	 */
	public function originalJoinDataExists(string $name): bool {

		return array_key_exists($name, $this->_aOriginalJoinData);

	}

	/**
	 * Returns original join data
	 * @param string $name
	 * @return mixed
	 * @throws Exception
	 */
	public function getOriginalJoinData(string $name): mixed {

		if(array_key_exists($name, $this->_aOriginalJoinData)) {
			return $this->_aOriginalJoinData[$name];
		} else {
			throw new Exception('Requested data do not exists!');
		}

	}

	/**
	 * Schnittmenge der geänderten Datensätze bestimmen
	 *
	 * Diese Methode gibt ein Array zurück, bestehend aus den Daten, welche verändert wurden.
	 * Dies betrifft neue Daten oder Daten, die nicht mehr den alten Daten gleichen.
	 * Für gelöschte Daten muss man manuell einen Abgleich durchführen
	 *
	 * @return array
	 */
	public function getIntersectionData() {

		$aDiffData		= (array)$this->getData();
		$aOriginalData	= (array)$this->getOriginalData();

		foreach($aOriginalData as $iKey => $mValue){
			
			$mCompareValue = $aDiffData[$iKey];
			
			$aField = self::$_aTable[$this->_sTable][$iKey];
			
			// Wenn felddaten da sind und das feld ein DB_DATE ist
			// müssen leere stings umgewandelt werde da der vergleich sonst nicht geht
			// die gui setzt bei leeren datepicker feldern nämlich einen leeren string
			if(
				!empty($aField) &&
				$aField['Type'] == 'date'
			){
				
				if($mValue === ''){
					$mValue = '0000-00-00';
				}
				if($mCompareValue === ''){
					$mCompareValue = '0000-00-00';
				}
			}
			
			
			//numerische Werte typecasten
			if(is_numeric($mValue)){
				$mValue			= (float)$mValue;
				$mCompareValue	= (float)$mCompareValue;
			}
			//nicht veränderte Daten ignorieren
			if($mValue == $mCompareValue){
				unset($aDiffData[$iKey]);
			}
		}

		$this->loadAutoJoinTableData();

		//Schnittmenge der joined tables
		$aOriginalJoinData	= (array)$this->_aOriginalJoinData;
		$aJoinData			= (array)$this->_aJoinData;

		foreach($aJoinData as $sJoinKey => $aJoin) {

			if(
				isset($aOriginalJoinData[$sJoinKey]) &&
				isset($this->_aJoinTables[$sJoinKey])
			) {

				//wenn Sortierung bei joinedtables nicht aktiv ist, keine Keys vergleichen
				if(isset($this->_aJoinTables[$sJoinKey]['sort_column']))
				{
					$bCheckOrder = true;
				}
				else
				{
					$bCheckOrder = false;
				}

				$aDiff = Util::getArrayRecursiveDiff(array_values($aJoin), array_values($aOriginalJoinData[$sJoinKey]), $bCheckOrder);
				
				if(
					!empty($aDiff)
				) {
					$aDiffData[$sJoinKey] = $aDiff;//wenn geht geben wir am besten immer die Differenz aus den jetzigen Daten zurück
				}
				else
				{
					$aDiff2 = Util::getArrayRecursiveDiff(array_values($aOriginalJoinData[$sJoinKey]), array_values($aJoin), $bCheckOrder);

					if(
						!empty($aDiff2)
					) {
						$aDiffData[$sJoinKey] = $aDiff2;//wenn geht geben wir am besten immer die Differenz aus den jetzigen Daten zurück
					}	
				}

			}

		}

		// Daten die ignoriert werden können
		if(isset($aDiffData['created']))
		{
			unset($aDiffData['created']);
		}
		unset($aDiffData['changed']);
		unset($aDiffData['position']);
		unset($aDiffData['editor_id']);

		return $aDiffData;

	}
	
	/**
	 *
	 * @return array 
	 */
	public function getTableFields()
	{
		$this->_checkTableData();

		return self::$_aTable;
	}
	
//	/**
//	 * Löscht die aktuelle Instance aus dem Cache 
//	 */
//	public function resetInstance() {
//		
//		
//		
//	}
//	
//	/**
//	 * Löscht alle Instanzen dieser Klasse aus dem Cache-Array 
//	 */
//	public static function resetInstanceCache() {
//		
//		$sClass = static::$sClassName;
//
//		// Gibt es Instanzen?
//		if(
//			!empty(static::$aInstance) &&
//			!empty(static::$aInstance[$sClass])
//		) {
//			
//			foreach(static::$aInstance[$sClass] as $iKey=>$oInstance) {
//				
//				// Instanz entfernen
//				unset(static::$aInstance[$sClass][$iKey]);
//				$oInstance = null;
//				unset($oInstance);
//				
//			}
//			
//		}
//		
//	}

	/**
	 * Prüfen, ob die Daten im statischen Cache vorhanden sind 
	 */
	protected function _checkTableData()
	{
		if(!isset(self::$_aTable[$this->_sTable]))
		{
			$this->_getTableFields();
		}
	}

	
/* ==================================================================================================== */

	/**
	 * Checks the name of the DB table
	 */
	final protected function _checkTableName(){

		if(
			empty($this->_sTable) || 
			!is_string($this->_sTable)
		) {
			throw new Exception('The name of DB table is not defined!');
		}
	
	}
	
    final static public function clearAllInstances(){
        // Alle Klassen holen
		$aClasses = get_declared_classes();
		
		foreach((array)$aClasses as $sClass){
			
			// Wenn eine Ext_ oder eine WD(Basic/...) dann caches leeren
			if(
				(
					strpos(strtolower($sClass), 'ext_') === 0 ||
					strpos(strtolower($sClass), 'wd') === 0	
				) &&
				strtolower($sClass) !== 'wddate' &&
				strtolower($sClass) !== 'ext_gui2_session' &&
				strtolower($sClass) !== 'ext_thebing_access_client' &&
                strtolower($sClass) !== 'ext_tc_access_data' &&
                strtolower($sClass) !== 'ext_gui2_index_stack' &&
				strtolower($sClass) !== 'ext_tc_factory'
			){
	
				// Zur sicherheit...
				try {

					// Reflection bauen
					$oReflection	= new ReflectionClass($sClass);
					$aProperties	= $oReflection->getProperties(ReflectionProperty::IS_STATIC);

					//Statische Properties durchgehen
					foreach($aProperties as $oProperty){

						/* @var $oProperty ReflectionProperty */
						$sProperty = $oProperty->name;
						// Instance zurücksetzten
						$oProperty->setAccessible(true);

						if(
							$sProperty == 'aApplications' || // Ext_TA_Admin_Templates_Outputformat::$aApplications
							$sProperty == '_aApplicationAllocations' || // Ext_TC_Communication
							$sProperty == 'sDefaultLanguage' || // L10N
							$sProperty == 'sCodeField' || // L10N
							$sProperty == 'sClassName' || //WDBasic
							$sProperty == '_sInterfaceLanguage' || // Interface-Sprache System
							$sProperty == '_sStaticTable' ||
							(
								strpos($sProperty, 'Class') !== false &&
								strpos($sProperty, '_s') === 0 // _sXXClass = "blabla" sind statische Variablen wo klassen namen enthalten sind
							)
						){
							// nicht löschen
						} else if(
							strpos($sProperty, 'a') === 0 ||
							strpos($sProperty, '_a') === 0
						){
							$oProperty->setValue(null, array());
						} else if(
							strpos($sProperty, 'i') === 0 ||
							strpos($sProperty, '_i') === 0
						){
							$oProperty->setValue(null, 0);
						} else if(
							strpos($sProperty, 's') === 0 ||
							strpos($sProperty, '_s') === 0
						){
							$oProperty->setValue(null, '');
						}
					}

				} catch (Exception $exc) {

				}
			}
			
			
		}
    }
    
	/**
	 * geht alle Klassen ( falls rekursive ) durch bzw. nur die angebene
	 * und löscht alle aInstance Inhalte raus.
	 * @param string $sClass
	 * @param boolean $bRecursive 
	 */
	final static public function clearInstances($sClass, $bRecursive = false){
		
		$aClasses = array();
		
		$bClearAll = false;
		if($sClass === null){
			$bClearAll = true;
		} else {

			// Alle Klassen holen wenn rekrusive ansonsten nur die eigene
			if($bRecursive){
				$aClasses = get_declared_classes();
			} else {
				$aClasses = array($sClass);
			}
		}

		foreach((array)$aClasses as $sCurrentClass){

			// zentrales aInstance leeren
			if(isset(self::$aInstance[$sCurrentClass])) {
				self::$aInstance[$sCurrentClass] = array();
			}

			$sCurrentClass = strtolower($sCurrentClass);
			$sClassNameWithSpacer = $sClass.'_';

			if(
				(
					$bRecursive &&// nur wenn die Klasse mit der angegebenen anfängt	
					strpos($sCurrentClass, $sClassNameWithSpacer) === 0
				) ||
				$sCurrentClass === $sClass || // Oder genau die gleiche klasse
				$bClearAll // oder wenn alle instanzen gekillt werden sollen
			){
			
				// Zur sicherheit...
				try {

					// Reflection bauen
					$oReflection	= new ReflectionClass($sCurrentClass);
					$aProperties	= $oReflection->getProperties(ReflectionProperty::IS_STATIC);

					//Statische Properties durchgehen
					foreach($aProperties as $oProperty){

						/* @var $oProperty ReflectionProperty */
						$sProperty = $oProperty->name;

						if(
							$sProperty == 'aInstance'
						){
							// Instance zurücksetzten
							$oProperty->setAccessible(true);
							$oProperty->setValue(null, null);
                            break;
						}

					}

				} catch (Exception $exc) {
					__pout($exc,1);
				}

			}
			
		}
		
		// Am ende müssen wir noch die aInstance von der Hauptwdbasic durchgehen und alle Objekte rauskillen
		// die dort mit der angegebenen Klasse vorhanden sind
		// da ab php 5.3 eine aInstance Ableitung nichtmehr nötig wäre
		$oReflection	= new ReflectionClass('WDBasic');
		$aProperties	= $oReflection->getProperties(ReflectionProperty::IS_STATIC);
		//Statische Properties durchgehen
		foreach($aProperties as $oProperty){

			/* @var $oProperty ReflectionProperty */
			$sProperty = $oProperty->name;

			if(
				$sProperty == 'aInstance'
			){
				try {
					// Instance zurücksetzten
					$oProperty->setAccessible(true);
					$aInstances = $oProperty->getValue(null);
					foreach($aInstances as $sInstanceClass => $oObject){
						if(
							(
								$bRecursive && // nur wenn die Klasse mit der angegebenen anfängt
								strpos($sInstanceClass, $sClassNameWithSpacer) === 0	
							) ||
							(
								$sInstanceClass === $sClass
							) ||
							$bClearAll
						){
							$aInstances[$sInstanceClass] = null;
						}
					}
					$oProperty->setValue(null, $aInstances);
				} catch (Exception $exc) {
					__pout($exc,1);
				}
                break;
			}
		}
	}
	
	public function removeJoinedObjectChildByKey($sKey, $sJoinedObjectCacheKey = null) {

		if (
			isset($this->_aJoinedObjectChilds[$sKey]) &&
			isset($this->_aJoinedObjectChilds[$sKey][$sJoinedObjectCacheKey])
		) {
			if (
				$this->_aJoinedObjectChilds[$sKey][$sJoinedObjectCacheKey]->id > 0 &&
				$this->_aJoinedObjectChilds[$sKey][$sJoinedObjectCacheKey]->hasActiveField()
			) {
				$this->_aJoinedObjectChilds[$sKey][$sJoinedObjectCacheKey]->active = 0;
			} else {
				unset($this->_aJoinedObjectChilds[$sKey][$sJoinedObjectCacheKey]);
			}
			
		} else {
			throw new \InvalidArgumentException(sprintf('Joined Object Child not found: %d', $sJoinedObjectCacheKey));
		}

	}
	
	/**
	 * Standard Query bilden anhand der Tabellen/Joined tables Informationen
	 * 
	 * @param Ext_Gui2 $oGui
	 * @return array 
	 */
	protected function _buildQueryData($oGui = null)
	{
		$aQueryData = array();

		$sFormat = $this->_formatSelect();

		$aQueryData['data'] = array();

		$sTableAlias = $this->_sTableAlias;

		if(empty($sTableAlias)) {
			$sTableAlias = $this->_sTable;
		}

		$sAliasString = '';
		$sAliasName = '';
		if(!empty($sTableAlias)) {
			$sAliasString .= '`'.$sTableAlias.'`.';
			$sAliasName .= '`'.$sTableAlias.'`';
		}

		$aQueryData['sql'] = "
				SELECT
					".$sAliasString."*
					{FORMAT}
				FROM
					`{TABLE}` ".$sAliasName."
			";

		$iJoinCount = 1;
		$iStaticJoinCount = 1;

		foreach((array)$this->_aAutoJoinTables as $sJoinAlias => $aJoinData) {

			$aLoops = array(''=>array());
			if(
				isset($aJoinData['i18n']) &&
				$aJoinData['i18n'] === true &&
				$oGui instanceof Ext_Gui2 &&
				$oGui->i18n_languages !== null &&
				is_array($oGui->i18n_languages)
			) {
				$aLoops = array();
				foreach($oGui->i18n_languages as $aLanguage) {
					$aLoops[$aLanguage['iso']] = array('language_iso'=>$aLanguage['iso']);
				}
			}

			foreach($aLoops as $sLoop=>$aLoop) {
			
				// Where Part
				if(
					isset($aJoinData['i18n']) &&
					$aJoinData['i18n'] === true &&
					!empty($aJoinData['foreign_key_field'])
				) {
					$aJoinData['foreign_key_field'] = (array)$aJoinData['foreign_key_field'];
					foreach($aJoinData['foreign_key_field'] as $sKeyField) {
						if($sKeyField != 'language_iso') {
							$aQueryData['data']['join_table_select_alias_'.$iJoinCount.'_'.$sKeyField] = $sKeyField.'_'.$sLoop;
							$aQueryData['data']['join_table_select_'.$iJoinCount.'_'.$sKeyField] = $sKeyField;
							$sFormat .= ", #join_alias_".$iJoinCount.".#join_table_select_".$iJoinCount."_".$sKeyField." #join_table_select_alias_".$iJoinCount."_".$sKeyField."";
						}
					}
				}
				
				// Select Part
				$sJoinOperator = 'LEFT OUTER JOIN';

				if(!empty($aJoinData['join_operator'])){
					$sJoinOperator = $aJoinData['join_operator'];
				}

				$aQueryData['sql'] .= " ".$sJoinOperator."
										#join_table_".$iJoinCount." #join_alias_".$iJoinCount." ON
											#join_alias_".$iJoinCount.".#join_pk_".$iJoinCount." = ".$sAliasString."`id` ";

				if(!empty($aJoinData['check_active'])) {
					$aQueryData['sql'] .= " AND 
											#join_alias_".$iJoinCount.".`active` = 1 ";
				}

				foreach($aLoop as $sKey=>$sValue) {
					$aQueryData['data']['join_table_on_key_'.$iJoinCount.'_'.$sKey] = $sKey;
					$aQueryData['data']['join_table_on_val_'.$iJoinCount.'_'.$sKey] = $sValue;
					$aQueryData['sql'] .= " AND 
											#join_alias_".$iJoinCount.".#join_table_on_key_".$iJoinCount."_".$sKey." = :join_table_on_val_".$iJoinCount."_".$sKey." ";
				}

				if(!empty($aJoinData['static_key_fields'])){
					foreach((array)$aJoinData['static_key_fields'] as $sField => $mValue){
						// Auto-ID-Spalte überspringen
						if(
							$sField == 'id' &&
							$mValue = 'AUTO'
						) {
							continue;
						}
						$aQueryData['sql'] .= " AND #join_alias_".$iJoinCount.".#static_join_field_".$iStaticJoinCount." = :static_join_field_value_".$iStaticJoinCount." ";
						$aQueryData['data']['static_join_field_'.$iStaticJoinCount] = $sField;
						$aQueryData['data']['static_join_field_value_'.$iStaticJoinCount] = $mValue;
						$aKeys[$sField] = $mValue;
						$iStaticJoinCount++;
					}
				}

				$sTempJoinAlias = $sJoinAlias;
				if($sLoop) {
					$sTempJoinAlias = $sTempJoinAlias.'_'.$sLoop;
				}
				
				$aQueryData['data']['join_table_'.$iJoinCount]	=  $aJoinData['table'];
				$aQueryData['data']['join_pk_'.$iJoinCount]		=  $aJoinData['primary_key_field'];
				$aQueryData['data']['join_alias_'.$iJoinCount]	=  $sTempJoinAlias;

				$iJoinCount++;
			}

		}
		
		foreach((array)$this->_aJoinedObjects as $sJoinAlias => $aJoinedObjectData){
			
			if(isset($aJoinedObjectData['query']) && $aJoinedObjectData['query'] === true){
				
				$sJoinOperator = 'LEFT OUTER JOIN';

				if($aJoinedObjectData['type'] == 'child'){
					$aQueryData['sql'] .= " ".$sJoinOperator."
										#join_table_".$iJoinCount." #join_alias_".$iJoinCount." ON
										#join_alias_".$iJoinCount.".#join_fk_".$iJoinCount." = ".$sAliasString."`id`
									";
				} else if($aJoinedObjectData['type'] == 'parent'){
					$aQueryData['sql'] .= " ".$sJoinOperator."
										#join_table_".$iJoinCount." #join_alias_".$iJoinCount." ON
										#join_alias_".$iJoinCount.".`id` = ".$sAliasString."#join_fk_".$iJoinCount."
									";
				}

				if($aJoinedObjectData['check_active']) {
					$aQueryData['sql'] .= " AND #join_alias_".$iJoinCount.".`active` = 1 ";
				}

				$sJoinedObject = $aJoinedObjectData['class'];
				$oObject = new $sJoinedObject(0);
				$sJoinedObjectTable = $oObject->getTableName();

				$aQueryData['data']['join_table_'.$iJoinCount]	=  $sJoinedObjectTable;
				$aQueryData['data']['join_fk_'.$iJoinCount]		=  $aJoinedObjectData['key'];
				$aQueryData['data']['join_alias_'.$iJoinCount]	=  $sJoinAlias;

				$iJoinCount++;
				
			}
		}

		if(array_key_exists('active', $this->_aData)) {
			$aQueryData['sql'] .= " WHERE ".$sAliasString."`active` = 1 ";
		}

		if(count($this->_aAutoJoinTables) > 0){
			$aQueryData['sql'] .= "GROUP BY ".$sAliasString."`id` ";
		}

		if(array_key_exists('id', $this->_aData)) {
			$aQueryData['sql'] .= "ORDER BY ".$sAliasString."`id` ASC ";
		}

		$aQueryData['sql'] = str_replace('{FORMAT}', $sFormat, $aQueryData['sql']);
		$aQueryData['sql'] = str_replace('{TABLE}', $this->_sTable, $aQueryData['sql']);

		return $aQueryData;
	}
    /**
	 * wird benutzt in der getArrayList um den cache key dafür zu definieren
	 * kann abgeleitet werden um spezielle filter noch einzufügen
	 * @return string 
	 */
	protected function _getArrayListCacheKey($bCheckValid = true)
	{
		// sTable und NICHT Classname
		// wir haben mache stellen im COre wo wir get Array list machen, wo aber zum speichern eine TA/TS Klasse genommen wird
		// => unterschiedliche classnames => cache wird beim speichern nicht geleert
		// beispiel: pdf layouts bei Agentur
		$sCacheKey = $this->_sTable . '_' . 'array_list' . '_' . (int) $bCheckValid;
		
		return $sCacheKey;
	}

	/**
	 * @param bool $bForSelect
	 * @param string $sNameField
	 * @param bool $bCheckValid
	 * @return array
	 * @deprecated
	 */
	public function getArrayList($bForSelect = false, $sNameField = 'name', $bCheckValid = true , $bIgnorePosition = false) {
		
		//cache key holen, kann abgeleitet werden, falls noch spezielle filter rein müssen,
		//wie z.B. die client_id/school_id in der Schulsoftware...
		$sCacheKey = $this->_getArrayListCacheKey($bCheckValid);

		if(!isset(self::$_aArrayListCache[$sCacheKey])) {

			$aArrayList = WDCache::get($sCacheKey);

			if(
				//nicht auf empty überprüfen, sonst würde man bei einem leeren
				//ergebnis immer wieder versuchen den cache zu bilden
				!is_array($aArrayList)
			) {
				/**
				 * Vorsicht, alles was hier manipuliert wird, muss in _getArrayListCacheKey auch berücksichtigt werden
				 */
				$sSql = $this->_getSqlForList($bCheckValid);

				$aSql = array('table' => $this->_sTable);

				$aResult = DB::getPreparedQueryData($sSql, $aSql);

				//vorbereiten falls nur bestimmte felder gecached werden sollen
				$aCacheInsert = $this->_prepareArrayListResult($aResult);

				//objekt cache
				self::$_aArrayListCache[$sCacheKey] = $aCacheInsert;
				
				//memcache oder dbcache einfügen
				WDCache::set($sCacheKey, 86400, $aCacheInsert);
				
			} else {
				//objekt cache
				self::$_aArrayListCache[$sCacheKey] = $aArrayList;
			}

		}

		//falls für select etc. nocheinmal das array vorbereiten
		$aBack = $this->_prepareArrayListByOptions($sCacheKey, $bForSelect, $sNameField, $bIgnorePosition);

		return $aBack;

	}
	
	/**
	 * wird benutzt in der getArrayList um das Array nach select etc. zu ändern
	 * @param string $sCacheKey
	 * @param bool $bForSelect
	 * @param string $sNameField
	 * @param bool $bIgnorePosition
	 * @return array 
	 */
	protected function _prepareArrayListByOptions($sCacheKey, $bForSelect = false, $sNameField = 'name', $bIgnorePosition = false){
		
		$aArrayList = (array)self::$_aArrayListCache[$sCacheKey];

		$aBack		= array();

		if(
			$bForSelect
		){
			foreach ($aArrayList as $aData) {
				$aBack[$aData['id']] = ($aData[$sNameField] ?? null);
			}
		}else{
			$aBack = $aArrayList;
		}

		if(
			$bForSelect &&
			(
				!array_key_exists('position', $this->_aData) ||
				$bIgnorePosition
			) 
		){
			asort($aBack);
		}
		
		return $aBack;

	}
	
	/**
	 * wird benutzt in der getArrayList falls nicht alle Felder benötigt werden zum cachen
	 * bzw kann abgeleitet werden um das array noch einmal zu manipulieren bevor gecached wird
	 * @param array $aResult
	 * @return array 
	 */
	protected function _prepareArrayListResult($aResult){
		
		$aResult		= (array)$aResult;
		
		$aCacheInsert	= array();

		$aListCacheFields = (array)$this->_aListCacheFields;

		foreach($aResult as $aRowData){
			
			if(
				empty($aListCacheFields)
			){
				$aCacheInsert[$aRowData['id']] = $aRowData;
			} else {
				foreach($aListCacheFields as $sField){
					if(
						isset($aRowData[$sField])
					){
						$aCacheInsert[$aRowData['id']][$sField] = $aRowData[$sField];
					}
				}
			}

		}
		
		return $aCacheInsert;
	}

	/**
	 * standard query für eine Liste wie z.B. arraylist, objectlist...
	 * @todo die getListQueryData sollte diese Funktion benutzen, die funktion vielleicht in v5???
	 * @param bool $bCheckValid
	 * @return string 
	 */
	protected function _getSqlForList($bCheckValid = true){
		
		$sAliasBase = $sAlias = $this->_sTableAlias;
		if(!empty($sAlias)){
			$sAliasBase =  '`'.$sAlias.'`';
			$sAlias = $sAliasBase.'.';
		}

		$sSql = " 
			SELECT 
				* 
			FROM 
				#table " . $sAliasBase . "
			";

		$sSqlWhere = '';

		if(array_key_exists('active', $this->_aData)) {
			$sSqlWhere .= " WHERE ".$sAlias."`active` = 1 ";
		}

		if(
			$bCheckValid &&
			array_key_exists('valid_until', $this->_aData) &&
			!array_key_exists('valid_from', $this->_aData)
		) {

			if(!empty($sSqlWhere)){
				$sSqlWhere .= " AND ";
			} else {
				$sSqlWhere .= " WHERE ";
			}

			$sCurDate = date('Y-m-d'); // Damit MySQL den Query vielleicht cachen kann
			$sSqlWhere .= " (".$sAlias."`valid_until` = '0000-00-00' OR ".$sAlias."`valid_until` >= '".$sCurDate."') ";
			
		}

		$sSql .= $sSqlWhere;

		if(array_key_exists('position', $this->_aData)) {
			$sSql .= " ORDER BY 
				".$sAlias."`position` ASC";
		}
		
		return $sSql;
	}

	/**
	 * Sollte wie bei Eloquent generell immer ausgeführt werden, wird aber aktuell nur für den Query-Builder benutzt, um
	 * die Features vom Eloquent-Builder nach dem gleichen Schema in der WDBasic zu ermöglichen
	 *
	 * @return void
	 */
	public static function booted() {}

	/**
	 * Start new query
	 *
	 * @return \Core\Database\WDBasic\Builder
	 */
	public static function query(): \Core\Database\WDBasic\Builder {
		return (new static)->newQuery();
	}

	/**
	 * Get a new query builder for the model's table.
	 *
	 * @return \Core\Database\WDBasic\Builder
	 */
	public function newQuery() {

		if ($this->hasActiveField()) {
			static::addGlobalScope(new \Core\Database\WDBasic\Scope\SoftDeletingScope());
		}

		if (array_key_exists('valid_until', $this->_aData)) {
			static::addGlobalScope(new \Core\Database\WDBasic\Scope\ValidUntilScope());
		}

		static::addGlobalScope(new \Core\Database\WDBasic\Scope\InPeriodScope('service_from', 'service_until'));

		// TODO das sollte eigentlich irgendwo zentral passieren, wird aber aktuell nur für den Query-Builder benutzt
		static::booted();

		$query = $this->newQueryWithoutScopes();

		return $this->registerGlobalScopes($query);
	}

	/**
	 * Get a new query builder that doesn't have any global scopes.
	 *
	 * @return \Core\Database\WDBasic\Builder
	 */
	public function newQueryWithoutScopes() {

		$alias = $this->getTableAlias();

		$query = new \Core\Database\Query\Builder($this->getDbConnection());
		$query->from($this->getTableName(), !empty($alias) ? $alias : null);

		if ($this->hasSortColumn()) {
			$query->orderBy($this->qualifyColumn($this->getSortColumn()));
		}

		return new \Core\Database\WDBasic\Builder($this, $query);
	}

	/**
	 * Register the global scopes for this builder instance.
	 *
	 * @param  \Core\Database\WDBasic\Builder $builder
	 * @return \Core\Database\WDBasic\Builder
	 */
	public function registerGlobalScopes($builder) {

		foreach ($this->getGlobalScopes() as $identifier => $scope) {
			$builder->withGlobalScope($identifier, $scope);
		}

		return $builder;
	}

	/**
	 * @param $column
	 * @return string
	 */
	public function qualifyColumn($column) {

		$alias = $this->getTableAlias();

		if (!empty($alias)) {
			return $alias.'.'.$column;
		}

		return $this->getTableName().'.'.$column;
	}

	/**
	 * Repository setzen, hauptsächlich für Mock-Objekte
	 *
	 * @param WDBasic_Repository|null $repository
	 * @return void
	 */
	public static function setRepository(?WDBasic_Repository $repository) {
		if ($repository !== null) {
			static::$repository[static::class] = $repository;
		} else if (isset(static::$repository[static::class])) {
			unset(static::$repository[static::class]);
		}
	}

	/**
	 * Gibt den Repository zu einer Entität zurück
	 *
	 * @param string $sClassName Der Name der Entity-Klasse
	 * @return \WDBasic_Repository Den Repository zu der Entity-Klasse
	 * @throws Exception is not an instance of
	 */
	public static function getRepository() {

		$sClassName = get_called_class();

		if (isset(static::$repository[$sClassName])) {
			return static::$repository[$sClassName];
		}

		$oClass = new $sClassName();

		$oDb = $oClass->getDbConnection();

		$sRepository = 'Repository';
		$sCustomRepositoryClassName = '\\' . $sClassName . $sRepository;

		// Wenn eine benutzerdefiniert Repository existiert
		if (class_exists($sCustomRepositoryClassName)) {

			$oCustomRepositoryClass = new $sCustomRepositoryClassName($oDb, $oClass);

			// Wenn der benutzerdefinierte Repository von WDBasicRepository erbt 
			if ($oCustomRepositoryClass instanceof WDBasic_Repository) {
				// Gib den benutzerdefiniert Repository zurück (autoload, namespaces)
				return $oCustomRepositoryClass;
			} else {
				throw new Exception($sCustomRepositoryClassName . ' is not an instance of \WDBasicRepository');
			}
		} else {
			// Gib die standard Repository zurück
			return new WDBasic_Repository($oDb, $oClass);
		}
	}

	/**
	 * Übergibt die aktuelle Instanz an den Persister
	 */
	public function persist() {
		
		$oPersister = WDBasic_Persister::getInstance();
		$oPersister->attach($this);

	}

	public function getPrimaryColumn() {
		return $this->_sPrimaryColumn;
	}

	/**
	 * Liefert den Primary-Key der Entität zurück
	 * 
	 * @return mixed
	 */
	public function getPrimaryKeyValue() {
		$sPrimaryColumn = $this->getPrimaryColumn();
		return $this->$sPrimaryColumn;
	}
	
	/**
	 * Prüft ob der Primary-Key der Entität leer ist
	 * 
	 * @return bool
	 */
	public function hasEmptyPrimaryKey() {
		$mPrimaryKey = $this->getPrimaryKeyValue();
		return empty($mPrimaryKey);
	}
	
	/**
	 * `changed` bei Änderung verändern (ON UPDATE CURRENT TIMESTAMP) oder behalten
	 */
	public function disableUpdateOfCurrentTimestamp() {
		$this->bKeepCurrentTimestamp = true;
	}

	/**
	 * delete() löscht Datensatz mit Abhängigkeiten komplett, unabhängig von active und validate()
	 */
	public function enablePurgeDelete() {
		$this->bPurgeDelete = true;
	}

	/**
	 * Tabellen-Cache dieser WDBasic löschen
	 */
	public static function deleteTableCache() {
		$oSelf = new static();
		WDCache::delete('wdbasic_table_description_'.$oSelf->_sTable);
		WDCache::delete('db_table_description_'.$oSelf->_sTable);
	}

	/**
	 * Bereinigt Felder aus den Daten dieses Objektes damit z.B. keine Passwörter im Log gespeichert werden
	 * @param array $data
	 * @return array
	 */
	public function cleanData(array $data) {
		
		foreach($this->toBeCleaned as $field) {
			if(!empty($data[$field])) {
				$data[$field] = '***';
			}
		}
		
		return $data;
	}
	
	/**
	 * Datenbank-Verbindung nach unserialize() neu setzen
	 */
	public function __wakeup() {
		$this->_oDb = null;
		$this->getDbConnection();
	}

	public function updateField(string $field) {
		
		if(!$this->exist()) {
			return false;
		}
		
		if(!isset($this->_aData[$field])) {
			throw new InvalidArgumentException('Field "'.$field.'" not existing!');
		}
		
		$sqlQuery = "UPDATE #table SET #field = :value WHERE `id` = :id";

		$sqlParams = [
			'table' => $this->_sTable,
			'field' => $field,
			'value' => $this->_aData[$field],
			'id' => $this->id
		];
		
		$this->_oDb->preparedQuery($sqlQuery, $sqlParams);
		
		// Ich weiß, Ext_TC_Log gibt es hier eigentlich nicht...
		$this->log(Ext_TC_Log::UPDATED, [$field=>$this->_aData[$field]]);
		
		return true;
	}
	
}
