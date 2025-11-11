<?php

/**
 * @TODO Liste aktualisieren und zu @property ändern
 *
 * @property string $name
 * @property string $set
 * @property $column_sortable
 * @property $row_sortable
 * @property $row_sortable_column
 * @property $row_sortable_alias
 * @property $row_style
 * @property $row_style_index
 * @property $row_icon_status_visible
 * @property $row_icon_status_active
 * @property $row_multiple_checkbox
 * @property $calendar_format
 * @property $hash
 * @property $instance_hash
 * @property $load_table_bar_data
 * @property $load_table_head_data
 * @property $load_table_pagination_data
 * @property $load_admin_header
 * @property $query_orderField
 * @property $query_orderAlias
 * @property $query_order
 * @property $query_id_column
 * @property $query_id_alias
 * @property $gui_description
 * @property $gui_title
 * @property $class_data
 * @property $class_view
 * @property $class_wdbasic
 * @property $class_js
 * @property $multiple_selection
 * @property $multiple_selection_lock
 * @property $sum_row_columns
 * @property $sum_row_columns_group_column
 * @property $iStartDebugTime
 * @property $sSection
 * @property $additional_sections
 * @property $sView
 * @property $include_jquery @TODO Entfernen
 * @property $include_jquery_multiselect @TODO Entfernen
 * @property $include_jquery_contextmenu
 * @property $include_bootstrap_tagsinput
 * @property $include_jscolor
 * @property $parent_gui
 * @property $showLeftFrame
 * @property $parent_hash
 * @property $foreign_key
 * @property $foreign_key_alias
 * @property $foreign_jointable
 * @property $parent_primary_key
 * @property $parent_filter
 * @property $force_reload
 * @property $encode_data
 * @property $encode_data_id_field
 * @property $encode_data_unset_empty
 * @property $encode_data_reload_for_icons
 * @property $bReadOnly
 * @property $decode_parent_primary_key
 * @property $access
 * @property $rows_clickable
 * @property $multiple_pdf_class
 * @property $i18n_languages
 * @property $row_contextmenu
 * @property $wdsearch
 * @property $wdsearch_index
 * @property $wdsearch_required
 * @property $wdsearch_auto_refresh
 * @property $wdsearch_use_stack
 * @property $column_flexibility
 * @property $init_observer
 * @property bool $created_by_config
 * @property bool $sidebar
 * @property string $origin
 * @property bool $api
 */
class Ext_Gui2 extends Ext_Gui2_Config_Basic { 

	use \Gui2\Traits\GuiFilterTrait;

	/**
	 * Genereller GUI2 L10N Pfad
	 *
	 * @var string
	 */
	static public $sAllGuiListL10N = "ATG V2 Translations";

	const INFO_ICON_HELP_KEY = 'HELP';

	/**
	 * @var array
	 */
	protected $_aConfig = array(
		'name'							=> '', // Name der GUI (GUI-Config oder ggf. manuell zwecks Kompatibilität)
		'set'							=> '', // Set der GUI (GUI-Config oder ggf. manuell zwecks Kompatibilität)
		'column_sortable'				=> 1, // Es kann nur Column ODER Row Sortable aktiv sein!
		'row_sortable'					=> 0, // Es kann nur Column ODER Row Sortable aktiv sein!
		'row_sortable_column'			=> 'position',
		'row_sortable_alias'			=> '',
		'row_style'						=> 'Row',		// Ext_Gui2_View_Style_Interface Klasse
		'row_style_index'               => '',
		'row_icon_status_visible'		=> '',			// Ext_Gui2_View_Icon_Interface Klasse
		'row_icon_status_active'		=> '',			// Ext_Gui2_View_Icon_Interface Klasse
		'row_multiple_checkbox'			=> false,		// Ext_Gui2_View_MultipleCheckbox_Abstract Klasse
		'calendar_format'				=> '',
		'hash'							=> '',
		'instance_hash'					=> '',
		'load_table_bar_data'			=> 1,
		'load_table_head_data'			=> 1,
		'load_table_pagination_data'	=> 1,
		'load_admin_header'				=> 1,
		'query_orderField'				=> '',
		'query_orderAlias'				=> '',
		'query_order'					=> 'DESC',
		'query_id_column'				=> 'id',
		'query_id_alias'				=> '',
		'gui_description'				=> null,
		'gui_title'						=> '',
		'class_data'					=> 'Ext_Gui2_Data',
		'class_view'					=> 'Ext_Gui2_View',
		'class_wdbasic'					=> 'WDBasic',
		'class_js'						=> 'ATG2',
		'multiple_selection'			=> 0, // Bestimmt ob es Checkboxen für eine Mehrfachauswahl gibt
		'multiple_selection_lock' => true,
		'sum_row_columns'				=> array(),// Spalten welche in einer Summenspalte summiert werden sollen
		'sum_row_columns_group_column'	=> '', // spalte nach der die Summe gruppiert werden soll
		'iStartDebugTime'				=> 0,	//debug info
		'sSection'						=> '', // man kann eine Section pro GUI übergeben z.B. Flex2
		'additional_sections' => array(), // Zusätzliche flexible Felder von anderen Entities in Liste anzeigen array(array('section'=>'XXX', 'primary_key'=>'xxx_id')
		'sView'							=> '', // man kann eine View angeben wenn z.B. Mehrere Listen über eine Classe gesteuert werden auch in JS verfügbarer flag
		'include_jquery'				=> 1, // TODO Entfernen
		'include_jquery_multiselect'	=> 1, // TODO Entfernen
		'include_jquery_contextmenu'	=> 0,
		'include_bootstrap_tagsinput' => false,
		'include_jscolor'				=> 0,
		'parent_gui'					=> array(), // Array mit Hashes anderer GUI2s die beim speichern des Dialoges auch neu geladen werden müssen
		'parent_hash'					=> '', // Hash der ELTERN Gui, wird beim Verknüpfung von GUI Tabellen benötigt
		'showLeftFrame'					=> true,	// Gibt an ob der Linke Frame standartmäßig angezeigt werden soll
		'foreign_key'					=> '', // Fremdschlüssel (String oder Array) der AKTUELLEN Gui, wird beim Verknüpfung von GUI Tabellen benötigt
		'foreign_key_alias'				=> '', // Alias des Fremdschlüssels der AKTUELLEN Gui, wird beim Verknüpfung von GUI Tabellen benötigt
		'foreign_jointable'				=> '', // Zwischentabelle in der der FK/PK für die beziehung steht (optional)
		'parent_primary_key'			=> '', // Primärschlüssel (String oder Array) der ELTERN Gui, wird beim Verknüpfung von GUI Tabellen benötigt,
		'parent_filter'					=> 0,
		'force_reload'					=> false, // Wenn true wird die GUI liste IMMER geladen auch wenn diese eine Child Gui in einem nicht aktiven Tab ist
		'encode_data'					=> array(), // Spalten/Aliase die für die GUI Interne IDs hinterlegt werden sollen um später darauf zugreifen zu können
		'encode_data_id_field'			=> false,
		'encode_data_unset_empty'		=> true, // Entfernt Einträge, die keine ID haben
		'encode_data_reload_for_icons'	=> true, // Sagt ob bei "updateicon" also beim anklicken einer Zeile die Query daten neu geladen werden oder nur encode data übermittelt wird
		'bReadOnly'						=> false,
		'decode_parent_primary_key'		=> false,
		'access'						=> 'control',
		'rows_clickable'				=> 1, // wenn 0, dann kann man die einzelnen tr's nicht anklicken
		'multiple_pdf_class'			=> '',
		'i18n_languages'				=> array(),
		'row_contextmenu'				=> null,
		'config_file'					=> '',
		'wdsearch'						=> false, // Enable WDSearch Index Search ! WARNING it will be create the index if it not exist with ALL entries!,
		'wdsearch_index'				=> '',
		'wdsearch_required'				=> false, // Definiert ob die Liste nur mit WDSearch funktioniert oder ob die normale GUI Funktionalität weiterhin besteht
		'wdsearch_auto_refresh'			=> true, // wenn true wird der index aktuallisiert sobald änderungen gefunden wurden, bei nein muss es manuell gestartet werden
		'wdsearch_use_stack'            => false, // benötigen wir damit es abwert kompatible ist!
		'column_flexibility'			=> true, // Flexibilität (Ein- und Ausblenden der Spalten)
		'init_observer'					=> false, // JS Init über onload Observer
		'created_by_config' => false, // GUI wurde über YML-Datei erzeugt
		'sidebar' => false,
		'origin' => null,
		'api' => false
	);

	/**
	 * @var null
	 */
	protected $_bCheckWDSearch = null;

	/**
	 * Array für dynamische gui options ( für individuelle dinge )
	 *
	 * @var array
	 */
	protected $aOptions = array();

	/**
	 * Beinhaltet alle Leisten
	 *
	 * @var Ext_Gui2_Bar[]
	 */
	protected $_aBar = array();

	/**
	 * Beinhaltet alle Tabellen Spalten
	 *
	 * @var array
	 */
	protected $_aColumn = array();

	/**
	 * Beinhaltet alle Spaltengruppen
	 *
	 * @var array
	 */
	protected $_aColumnGroups = array();

	/**
	 * Das Datenobjekt
	 * @var Ext_Gui2_Data
	 */
	protected $_oData;

	// Das Layout Objekt
	protected $_oView;

	// Icon Status Objecte
	protected $_oIconStatusVisible;
	protected $_oIconStatusActive;

	/**
	 * @var Ext_Gui2_View_Format_Date
	 */
	protected $_oCalendarFormat;

	/**
	 * Kontextmenü-Klasse
	 * @var Ext_Gui2_View_ContextMenu_Interface
	 */
	protected $_oContextMenu;

	/**
	 * Beinhaltet informationen zur Tabell, wird für Update/Insert genutzt
	 *
	 * @var array
	 */
	protected $_aTableData = array();

	/**
	 * Temporäres Array welches vor umformatierung der Daten "einer" Zeile gefüllt wird
	 * dient dazu beim formatieren auf andere Felder zuzugreifen
	 *
	 * @var array
	 */
	protected $_aTempTableResult = array();

	/**
	 * Debugmodus
	 *
	 * @var bool
	 */
	protected $bDebugmode = false;

	######
	## Encodierung der GUI
	## eigene GUI IDs mit hinterlegten informationen
	## Leereintrag ist WICHTIG! ID 0 darf nicht benutzt werden
	######
	protected $_aEncodeData = array(0 => array());
	protected $_aEncodeKeys = array();
	######

	protected $_aJsFiles	= array();
	protected $_aCssFiles	= array();
	
	protected $_bCSVExport	= false;
	
	/**
	 * @var \Gui2\Service\Export\ExportAbstract 
	 */
	protected $_oCSVExport  = null;

	/**
	 * @var Ext_Gui2
	 */
	private $oParent = null;

	/**
	 * @var Ext_Gui2_Page
	 */
	private $oPage = null;

	/**
	 * @var bool
	 */
	public $bSkipTranslation = false;

	/**
	 * @var MVC_Request
	 */
	protected $oRequest;

	protected $aOnloadActions = [];

	protected $bDialogOnlyMode = false;

	/**
	 * @param string $sHash
	 * @param string $sInstanceHash
	 * @return Ext_Gui2
	 */
	static public function getClass($sHash, $sInstanceHash) {
		global $_VARS;
		
		$oAccess = Access::getInstance();

		$iDebugStart = Util::getMicrotime();

		$mReturn = Ext_Gui2_Session::load($oAccess->key, $sHash, $sInstanceHash);

		if(
			$mReturn !== false &&
			$mReturn instanceof Ext_Gui2
		) {
			$oGui = $mReturn;
		} else {
			throw new RuntimeException('No GUI object found!');
		}

		$sInstanceHash = $oGui->instance_hash;

		// Gui Debugmodus
		// Aktiviert unter anderem Query ausgaben etc,..
		if(isset($_VARS['gui_debugmode'])){
			$oGui->bDebugmode = true;
			$oGui->_oData->bDebugmode = true;
		} else {
			$oGui->bDebugmode = false;
			$oGui->_oData->bDebugmode = false;
		}

		if($oGui->bDebugmode){
			$iDebugEnde = Util::getMicrotime();
			$iDebugNow = $iDebugEnde - $iDebugStart;
			__pout('DEBUG # Klasse holen # '.$iDebugNow);
		}

		Ext_Gui2_GarbageCollector::touchSession($sHash, $sInstanceHash);

		return $oGui;
	}

	/**
	 * @param string $sHash
	 * @param string $sDataClass
	 * @param string $sViewClass
	 */
	public function __construct($sHash='', $sDataClass = '', $sViewClass = '', $sInstanceHash=null) {

		if($sDataClass != ''){
			$this->class_data = $sDataClass;
		}
		if($sViewClass != ''){
			$this->class_view = $sViewClass;
		}

		// hash setzten
		if($sHash == '') {
			$sHash = md5($_SERVER['PHP_SELF']);
		}

		// Eindeutiger Wert der Instanz, ermöglicht das gleichzeitige Öffnen einer Liste
		if(
			!isset($GLOBALS['gui2_instance_hash']) ||
			strlen($GLOBALS['gui2_instance_hash']) != 32
		) {
			$GLOBALS['gui2_instance_hash'] = Util::generateRandomString(32);
		}

		if($sInstanceHash === null) {
			$sInstanceHash = $GLOBALS['gui2_instance_hash'];
		}

		$this->hash 			= $sHash;
		$this->instance_hash	= $sInstanceHash;
		$this->_oData			= new $this->class_data($this);
		$this->_oView			= new $this->class_view($this);

		// IconView Klassen definieren
		$this->row_icon_status_visible	= 'Visible';
		$this->row_icon_status_active	= 'Active';

		// Kalender format auf default Date setzten
		$this->calendar_format			= 'Date';

		Ext_Gui2_GarbageCollector::touchSession($sHash, $sInstanceHash);
		
	}

	public function logCall() {
	
		if(empty($this->origin)) {
			$traceItems = debug_backtrace();

			foreach($traceItems as $traceItem) {
				if($traceItem['function'] == 'outputLegacyFile') {
					$this->origin = $traceItem['args'][0];
					break;
				} elseif($traceItem['function'] == '_createGuiObject') {
					$this->origin = $traceItem['object']->getConfigFileName();
					break;
				}
			}
		}

		if(!empty($this->origin)) {
			DB::executePreparedQuery('REPLACE INTO `gui2_lists` SET `updated` = NOW(), `hash` = :hash, `title` = :title, `description` = :description, `origin` = :origin', [
				'hash' => $this->hash,
				'title' => (string)$this->gui_title,
				'description' => $this->gui_description,
				'origin' => $this->origin
			]);
		}

	}

	public function hasNoConfigFile() {
		return empty($this->config_file);
	}
	
	public function hasConfigFile() {
		return !empty($this->config_file);
	}

	public function resetOnloadActions()
	{
		$this->aOnloadActions = [];
		return $this;
	}

	public function addOnloadAction(string $functionCall)
	{
		$this->aOnloadActions[] = $functionCall;
	}

	public function dialogOnlyMode(bool $enabled = true)
	{
		$this->bDialogOnlyMode = $enabled;
	}

	public function hasDialogOnlyMode()
	{
		return $this->bDialogOnlyMode;
	}

	public function getOnloadActions(): array
	{
		return $this->aOnloadActions;
	}

	/**
	 * @param Ext_Gui2 $oParent
	 */
	public function setParent(Ext_Gui2 $oParent) {
		$this->parent_hash = $oParent->hash;
		$this->parent_gui = array($oParent->hash);
		$this->oParent = $oParent;
	}

	/**
	 * @return Ext_Gui2
	 */
	public function getParent() {
		return $this->oParent;
	}

	/**
	 * @return bool
	 */
	public function hasParent() {
		return $this->oParent !== null;
	}

	/**
	 * @param MVC_Request $oRequest
	 */
	public function setRequest(MVC_Request $oRequest) {
		$this->oRequest = $oRequest;
		$this->_oData->setRequest($oRequest);
	}

	public function getRequest(): MVC_Request {
		return $this->oRequest;
	}

	public function setOption($sField, $mValue){
		$this->aOptions[$sField] = $mValue;
	}

	public function unsetOption($sField) {
		unset($this->aOptions[$sField]);
	}

	public function getOption($sField, $mDefault = null) {

		if(isset($this->aOptions[$sField])) {
			return $this->aOptions[$sField];
		}

		return $mDefault;

	}

	/**
	 * gibt ein Array mit den IDs der Eltern-GUI, falls vorhanden
	 * @return array 
	 */
	public function getParentGuiIds() {
		$aParentGuiIds = (array) $this->getDataObject()->getParentGuiIds();
		return $aParentGuiIds;
	}	
	
	public function __set($sConfig, $mValue){

		parent::__set($sConfig, $mValue);

		if($sConfig == 'calendar_format'){
			if(
				$mValue instanceof Ext_Gui2_View_Format_Interface
			){
				$this->_oCalendarFormat = $mValue;
			} else if(
				is_string($mValue)
			){
				$sTempView = 'Ext_Gui2_View_Format_'.$mValue;
				$this->_oCalendarFormat = new $sTempView();
			} else {
				throw new Exception("Please use a Ext_Gui2_View_Format_Interface Interface");
			}
		} else if($sConfig == 'row_icon_status_active'){
			// Object für den Icon Status Active
			if(
				$mValue instanceof Ext_Gui2_View_Icon_Interface
			){
				$this->_oIconStatusActive = $mValue;
			} else if(
				is_string($mValue)
			){
				$sTempView = 'Ext_Gui2_View_Icon_'.$mValue;
				$this->_oIconStatusActive = new $sTempView();
			} else {
				throw new Exception("Please use a Ext_Gui2_View_Icon_Interface Interface");
			}

			$this->_oIconStatusActive->setGui($this);

		} else if($sConfig == 'row_icon_status_visible'){
			// Object für den Icon Status Visible
			if(
				$mValue instanceof Ext_Gui2_View_Icon_Interface
			){
				$this->_oIconStatusVisible = $mValue;
			} else if(
				is_string($mValue)
			){
				$sTempView = 'Ext_Gui2_View_Icon_'.$mValue;
				$this->_oIconStatusVisible = new $sTempView();
			} else {
				throw new Exception("Please use a Ext_Gui2_View_Icon_Interface Interface");
			}

			$this->_oIconStatusVisible->setGui($this);

		} else if($sConfig == 'row_contextmenu') {
			if($mValue instanceof Ext_Gui2_View_ContextMenu_Interface) {
				$this->_oContextMenu = $mValue;
			} else {
				throw new Exception("Please use a Ext_Gui2_View_ContextMenu_Interface Interface");
			}
		} else if($sConfig == 'parent_gui'){

		}

	}

	/**
	 *
	 * @return Ext_Gui2_Data
	 */
	public function getDataObject() {
		return $this->_oData;
	}

	/**
	 * Gibt alle Spalten zurück
	 *
	 * Anmerkung: Präventiv auf final gesetzt, da hier eigentlich nicht rumgepfuscht werden darf.
	 * Wenn nicht immer alle Columns zurück kommen, gibt es beispielsweise Probleme beim Abspeichern
	 * der Flexibilität. Für Manipulationen ist die getVisibleColumnList() da!
	 *
	 * @see Ext_Gui2::getVisibleColumnList()
	 * @return array
	 */
	final public function getColumnList() {
		return $this->_aColumn;
	}

	/**
	 * Sichtbare Spalten: Zum Manipulieren der Anzeige
	 *
	 * Die Methode muss IMMER ein Array mit nummerischen Arrays (Liste) zurückgeben,
	 * sonst stürzt das JavaScript ab!
	 *
	 * @param string $sFlexType
	 * @return array
	 */
	public function getVisibleColumnList($sFlexType = 'list', $aColumnList=null) {

		if($aColumnList === null) {
			$aColumnList = $this->getColumnList();
		}

		// Flexiblität: Nur Spalten anzeigen, die immer aktiv sind oder eingeblendet wurden
		if($this->column_flexibility === true) {
			$oFlex = Ext_Gui2_Flex::getInstance($this->hash);
			$aColumnList = $oFlex->prepareColumnArray($aColumnList, $sFlexType);
		}

		return $aColumnList;
	}

	/*
	 * Prüft ob es eine Spalte gibt oder nicht
	 */
	public function checkColumn($sDBColumn, $sDBAlias = '', $sSelectColumn = '') {

		$bCheck = false;

		$oFlex = Ext_Gui2_Flex::getInstance($this->hash);
		$aColumnList = $oFlex->prepareColumnArray($this->_aColumn);

		foreach((array)$aColumnList as $oHead) {
			if(
				$oHead->db_column == $sDBColumn &&
				(
					empty($sDBAlias) ||
					$oHead->db_alias	== $sDBAlias
				) && (
					empty($sSelectColumn) ||
					$oHead->select_column	== $sSelectColumn
				)
			) {
				$bCheck = true;
				break;
			}
		}

		return $bCheck;
	}

	/**
	 * gibt alle Leisten zurück
	 * @param $bPositionTop
	 * @return Ext_Gui2_Bar[]
	 */
	public function getBarList($bPositionTop = true){
		$aBars = $this->_aBar;
		$aBack = array();
		foreach($aBars as $iKey => $oBar){
			if(
				(
					$bPositionTop &&
					$oBar->position == 'top'
				) ||
				(
					!$bPositionTop &&
					$oBar->position != 'top'
				)
			){
				$aBack[] = $oBar;
			}
		}
		return $aBack;
	}

	/**
	 * Gibt die Referenz auf eine Bar oder das Array mit den Bars zurück
	 * @return Ext_Gui2_Bar
	 */
	public function getBar($iBar=false) {

		if($iBar !== false) {
			$oReturn = &$this->_aBar[$iBar];
		} else {
			$oReturn = &$this->_aBar;
		}

		return $oReturn;

	}

	/**
	 * @return Ext_Gui2_Bar_Filter_Abstract[]
	 */
	public function getAllFilterElements(){

		// Neue Filter (SideBar) sind direkt in GUI definiert
		if ($this->sidebar) {
			return $this->filters;
		}

		// Alte Filter müssen in den Bars zusammengesucht werden
		$aBars = $this->getBarList();
		$aList = array();
		foreach($aBars as $oBar){
			$aElements = $oBar->getElements();
			foreach($aElements as $oElement){
				if(
					$oElement->element_type == 'filter' ||
					$oElement->element_type == 'timefilter'
				){
					$aList[] = $oElement;
				}
			}
		}

		return $aList;
	}

	/**
	 * gibt alle Felder in dennen Gesucht werden soll
	 * @return type
	 */
	public function getWDSearchSearchColumns(){
		$aSearchColumns = $this->_oData->getWDSearchSearchColumns();
		return $aSearchColumns;
	}

    protected $_sDebugPart = '';
    protected $_iDebugPartTime = 0;
    protected $_iDebugStartTime = 0;
    protected $_iDebugMaxSec = 0;


    protected function setDebugEnd(){
        
        if($this->bDebugmode){
        
            $iTime = Util::getMicrotime();
            $iLastTime = $this->_iDebugPartTime;
            $iDuration = $iTime - $iLastTime;

            __pout('<< '.$iDuration.' >> '. $this->_sDebugPart.'');

            $iLastTime = $this->_iDebugStartTime;
            $iDuration = $iTime - $iLastTime;

            __pout(' TOTAL << '.$iDuration.' >>');
            
            __pout(' MAX << '.$this->_iDebugMaxSec.' >>');

            $this->_sDebugPart      = '';
            $this->_iDebugPartTime  = 0;
            $this->_iDebugStartTime  = 0;
            $this->_iDebugMaxSec  = 0;
        
        }
    }


    public function setDebugPart($sDescription, $mOptional = null){
        
        if($this->bDebugmode){
        
            $iTime = Util::getMicrotime();

            if($this->_sDebugPart != ""){
                $iLastTime = $this->_iDebugPartTime;
                $iDuration = $iTime - $iLastTime;
                if($this->_iDebugMaxSec < $iDuration){
                    $this->_iDebugMaxSec = $iDuration;
                }
                __pout('<< '.$iDuration.' >> '. $this->_sDebugPart.'');
            }
            
            if($this->_iDebugStartTime <= 0){
                $this->_iDebugStartTime = $iTime;
            }

            //__pout(' START '. $sDescription);

            $this->_sDebugPart      = $sDescription;
            $this->_iDebugPartTime  = $iTime;
        
			
			if($mOptional !== null){
				__pout($mOptional);
			}
        }
        
    }

	/**
	 * Bereitet die Daten für die Tabelle auf
	 *
	 * @param array $aFilterValues
	 * @param array $aOrderBy
	 * @param array $aSelectedIds
	 * @param string $sFlexType
	 * @param bool $bSkipLimit
	 * @return array
	 * @throws Exception
	 */
	public function getTableData($aFilterValues = array(), $aOrderBy = array(), $aSelectedIds = array(), $sFlexType = 'list', $bSkipLimit=false) {
		global $_VARS;

		$bWDSearchIndexExist = false;
		$bCreateWDSearchIndex = false;
		$bCreateCompleteWDSearchIndex = false;
		$aSidebarFilters = [];
		
		$aSelectedIds		= (array)$aSelectedIds;
		$aParentGuiIds		= array();

		if(isset($_VARS['parent_gui_id']))
		{
			$aParentGuiIds		= (array)$_VARS['parent_gui_id'];
		}

		// Ich habe mal die $aColumnList hierhin verschoben, damit die flexiblen Felder auch beim Sortieren markiert werden können,
		// die kommen erst nach dem prepareColumnListByRef hinzu...
		$aColumnList = $this->getColumnList();

		// Kann abgeleitet werden um Spalten zu manipulieren
		$this->_oData->prepareColumnListByRef($aColumnList);

		$aColumnListVisible = $this->getVisibleColumnList($sFlexType, $aColumnList);

		// Neue Filter: Entweder initiales loadTable oder FilterQuery wurde geändert
		if (
			$this->sidebar &&
			$this->load_table_bar_data && (
				$aFilterValues === null ||
				(
					$this->oRequest &&
					$this->oRequest->has('filter_query_changed')
				)
			)
		) {
			$this->prepareFilterQueries();
			$this->setFilterQuery();
			$aSidebarFilters = $this->prepareFilters($aFilterValues);
		}

		// Wenn WDSearch aktiv ist
		if(
			$this->checkWDSearch()
		) {

            $this->setDebugPart('WDSearch vorbereiten');

            // Offset setzten
			$iOffset = (int)($_VARS['offset'] ?? 0);
			if($iOffset <= 0){
				$iOffset = 0;
			}

			//Limit ermitteln
			$iLimit					= (int)$this->_aTableData['limit'];

            $sIndexName             = $this->wdsearch_index;
            $sIndexTypeName         = $sIndexName;
            
			if(empty($sIndexName)){
                $sIndexName				= $this->_oData->getWDSearchIndexName();
                $sIndexTypeName			= $this->_oData->getWDSearchIndexTypeName();
            }

			$sIdField                   = $this->query_id_column;
			
			$sForeignKeyField			= $this->foreign_key;
			
            $sConfig                    = $this->config_file;
	
            if($this->wdsearch_use_stack){
                $sIndexName             = Ext_Gui2_Index_Generator::createIndexName($sIndexName);
                $sIndexTypeName         = '';
            }

            $this->setDebugPart('WDSearch Index aufbauen');

			// Index holen
			$oIndex					= new \ElasticaAdapter\Adapter\Index($sIndexName);
			//$oIndex->open();
			// Gibt es den Index schon?
			try {
				$bWDSearchIndexExist = $oIndex->exists();
			} catch (\Elastica\Exception\Bulk\ResponseException $e) {
				$bWDSearchIndexExist = false;
			}

            $this->setDebugPart('WDSearch Spaltendaten holen');

			##
			## Spalten Liste holen
			##
			$aColumnListFull	= $aColumnList;
			$aAdditionalColumns = $this->_oData->getWDSearchAdditionalDataColumns();
			$aAdditionalColumnsForMapping = $this->_oData->getWDSearchAdditionalDataColumns(true);
			$aSearchColumns		= $this->getWDSearchSearchColumns();

			$aDataColumns = array();
            
             if(empty($aOrderBy)){
                $aDefaultOrderBy        = (array)$this->_aTableData['orderby'];
				if(!empty($aDefaultOrderBy)) {
					$sDefaultOrderByOrder   = reset($aDefaultOrderBy);
					$sDefaultOrderByColumn  = key($aDefaultOrderBy);
					$aOrderBy['db_column']  = $sDefaultOrderByColumn;
					$aOrderBy['order']      = $sDefaultOrderByOrder;
				}
            }
            
			if(!empty($aOrderBy['db_column'])) {
				$aOrderBy['db_column_query'] = $aOrderBy['db_column'];
			}
            
            $this->setDebugPart('WDSearch Orderby CSS Daten setzten');

			/*
			 * Sichtbare Spalten durchgehen
			 * Nur nach sichtbaren Spalten kann sortiert werden
			 * @todo Ist nicht gerade performant alle Spalten immer zu durchlaufen! 
			 * Man muss sich merken welche zuletzt sortiert war und man sollte per Key direkt auf die Spalten zugreifen können
			 */
			foreach($aColumnListFull as &$oColumn) {

				// CSS löschen
				$oColumn->order = '';
				$oColumn->removeCssClass('sortdesc');
				$oColumn->removeCssClass('sortasc');

				// Wenn es die gewünschte spalte ist
				if(
					$oColumn->db_column == $aOrderBy['db_column'] ||
					(
						$oColumn->sortable_column !== null &&
						$oColumn->sortable_column == $aOrderBy['db_column']
					)
				) {

					// Sortierung setzten + CSS

					if($aOrderBy['order'] == 'DESC') {
						$oColumn->order = 'ASC';
						$oColumn->removeCssClass('sortasc');
						$oColumn->addCssClass('sortdesc');
					} else {
						$aOrderBy['order'] = 'ASC';
						$oColumn->order = 'DESC';
						$oColumn->removeCssClass('sortdesc');
						$oColumn->addCssClass('sortasc');
					}
					
				}

			}
            
            $this->setDebugPart('WDSearch Spaltendaten sortieren');

			if(!$this->wdsearch_use_stack){
                // Alle Sichtbaren Spalten als Datenfelder merken
                foreach($aColumnListVisible as $oCurrentColumn){
                    $sSelectColumn = $oCurrentColumn->select_column;
                    if(empty($sSelectColumn)){
                        $sSelectColumn = $oCurrentColumn->db_column;
                    }
                    $aDataColumns[] = $sSelectColumn;
                }

                // Alle zusätzlichenfelder als Datenfelder makieren
                foreach($aAdditionalColumns as $sColumn => $aMapping){
                    $aDataColumns[] = $sColumn;
                }

                $aDataColumns[] = $sIdField;
                $aDataColumns = array_unique($aDataColumns);
            } else {
//                $sIndex = $this->wdsearch_index;
//                $oParser = new Ext_Gui2_Config_Parser($sIndex);
//                $aConfigColumns = $oParser->getColumns();
//                foreach ($aConfigColumns as $aConfigColumn) {
//                    $aDataColumns[] = $aConfigColumn['column'];
//                }
                $aDataColumns = array();
                $aSearchColumns = array();
            }

			
			$aWDSearchChangedDocuments		= array();

			// Soll neu indiziert werden?
			if(isset($_VARS['createNewWDSearchIndex']) && $_VARS['createNewWDSearchIndex'] == 1){
				$bCreateWDSearchIndex = true;
				$bCreateCompleteWDSearchIndex = true;
				$bWDSearchIndexExist = false;
			} else if($this->wdsearch_auto_refresh) {
                
                $this->setDebugPart('WDSearch Änderungen suchen');
                
				$aWDSearchChangedDocuments      = $this->_oData->getWDSearchChangedDocuments('changed');
				$aWDSearchDeletedDocumentIDs    = $this->_oData->getWDSearchChangedDocumentIds();
				$aWDSearchChangedDocumentsIDs   = array();
				foreach($aWDSearchChangedDocuments as $aDoc){
					$aWDSearchChangedDocumentsIDs[] = $aDoc['_id'];
				}

				if(
					!empty($aWDSearchChangedDocuments) ||
					!empty($aWDSearchDeletedDocumentIDs)
				){
					$bCreateWDSearchIndex = true;
				}
			}

			if(
				$this->wdsearch_required &&
//				!$this->checkWDSearch()
				!$bWDSearchIndexExist
			){
				$aBack = array();
				$aBack['forced_transferdata'] = array();
				$aBack['forced_transferdata']['action'] = 'showError';
				$aBack['forced_transferdata']['error'][] = $this->t('Fehler', self::$sAllGuiListL10N);
				$aBack['forced_transferdata']['error'][] = $this->t('Elasticsearch antwortet nicht.', self::$sAllGuiListL10N);
				return $aBack;
			// Wenn es den Index bereits gibt und er nicht erneut geniert werden muss
			} else if(
				$bWDSearchIndexExist &&
				!$bCreateWDSearchIndex
			){

                $this->setDebugPart('WDSearch Object erzeugen und Filter setzten');
				// Suche bauen
				$oSearch = new ElasticaAdapter\Facade\Elastica($sIndexName, $sIndexTypeName);

                 if(!$bSkipLimit){
                    // limit setzen
                    $oSearch->setLimit($iLimit, $iOffset);
                } else {
                    // #4230 hier MUSS ein Limit gesetzt werden ( 0 geht nicht ) es soll aber alles angezeigt werden
                    // daher ein absurd hohes limit angeben ;)
					// Mehr als 10000 geht nicht mehr bei ES 5, ansonsten muss das über die Scroll API gelöst werden
                    $oSearch->setLimit(999999, 0);
                }


				##
				## ORDER BY
				##

				if(!empty($aOrderBy)) {

					if(empty($aOrderBy['order'])) {
						$aOrderBy['order'] = 'desc';
					}

					$sIdField = $this->query_id_column;

					$aSort = [$aOrderBy['db_column_query'] => $aOrderBy['order']];

					// User beschweren sich darüber, dass sich bei gleichen Werten die Sortierung ändert, wie kurios #13072
					$aSort['id'] = 'desc';

					$oSearch->setSort($aSort);

				}

				$this->_addWDSearchIDFilter($oSearch, $aSelectedIds, $sIdField);
                
				$oParentGui         = $this->getParentClass();
                
				if($this->decode_parent_primary_key) {
					
					if($oParentGui) {
						$sPrimaryKey	= $this->parent_primary_key;
						
						$aParentGuiIds	= $oParentGui->decodeId($aParentGuiIds, $sPrimaryKey);
					}
                    
				} else if(
					$this->parent_primary_key != "" && 
					$this->parent_primary_key != "id" && 
					$this->_oGui->foreign_jointable == ""
                ) {

                    $sParentWDBasic     = $oParentGui->class_wdbasic;
                    $sPk                = $this->parent_primary_key;
                    foreach($aParentGuiIds as $iKey => $iParentId){
                        $oParentWDBasic         = call_user_func(array($sParentWDBasic, 'getInstance'), $iParentId);
                        $aParentGuiIds[$iKey]   = (int)$oParentWDBasic->$sPk;
                    }
                    
                }

				// Bei Child-GUI die Parent-ID als Query setzen
				if(
                    $oParentGui &&
                    $this->wdsearch_use_stack &&
					!empty($sForeignKeyField) // Nur, wenn Foreign-Key auch angegeben ist (ansonsten leeres Feld)
                ){
                    if(empty($aParentGuiIds)){
                        $aParentGuiIds = array('1000000'); // wenn eltern gui da ist MUSS eine ID da sein! ( minuszahl geht nicht! )
                    }

                    $this->_addWDSearchIDFilter($oSearch, $aParentGuiIds, $sForeignKeyField);
                }

				##
				###################
				##
                
                if($this->wdsearch_use_stack){
                    
                    $this->setDebugPart('WDSearch Gui Config auslesen');

                    ##
                    ## Where Part einbauen
                    ##

                    //$oConfig = new Ext_Gui2_Config_Parser($sConfig);
                    //$aWhere = $oConfig->get('where');
                    
                    $this->setDebugPart('WDSearch Gui Config WHERE bauen');
                    
                    //if(is_array($aWhere)){
                        //$aWhere = (array)$oConfig->callMethod($aWhere, $this);
                        $aWhere = $this->_aTableData['where'];
                        Ext_Gui2_Data::buildWherePart($aWhere, $oSearch);
                    //}

                    ##
                    ####################
                    ##
                }
                
                $this->setDebugPart('WDSearch suche starten');

				if($aFilterValues === null) {
					$aFilterValues = [];
				}
				
				// Suchergebniss holen
				$aResult = $this->_oData->getWDSearchResult($aFilterValues, $oSearch, $aDataColumns, $aSearchColumns);

                $this->setDebugPart('WDSearch Ergebniss Array aufbauen');
                
				// limit daten zusammensetzen
				$iEnd = $iLimit + $iOffset;
				$iCount = $aResult['total']['value'];

				if($iCount < $iEnd){
					$iEnd = $iCount;
				}

				foreach((array)$aResult['hits'] as $aEntries) {
					// Seit ElasticSearch 1.0 sind alle Felder Arrays, damit kommt die GUI aber nicht klar
					if(is_array($aEntries)) {
						foreach($aEntries['fields'] as &$mFieldData) {
							if(
								is_array($mFieldData) &&
								count($mFieldData) === 1
							) {
								$mFieldData = reset($mFieldData);
							}

						}
					}
					$aData[] = $aEntries['fields'];
				}

				$aBack				= array();
				$aBack['data']		= (array)$aData;
				$aBack['count']		= $iCount;
				$aBack['offset']	= $iOffset;
				$aBack['end']		= $iEnd;
				$aBack['show']		= $iLimit;
				// Wenn kein Limit dann ist das Ende die Gesammtanzahl
				if(empty($iLimit)){
					$aBack['end'] = $iCount;
				}

				$aResult			= $aBack;

                $this->setDebugPart('WDSearch ende');

			} else if(
				!$bWDSearchIndexExist &&
				!$bCreateWDSearchIndex
			) {
				if(!$this->wdsearch_use_stack){
                    $aBack = array();
                    $aBack['forced_transferdata'] = array();
                    $aBack['forced_transferdata']['action'] = 'displayWDSearchStartIndicate';
                    $aBack['forced_transferdata']['error'] = array();
                    $aBack['forced_transferdata']['data'] = array(
                        'id' => 'WDSEARCH',
                        'title' => L10N::t('Suchindizierung', Ext_Gui2::$sAllGuiListL10N),
                        'html' => L10N::t('Der Index dieser Liste ist nicht mehr aktuell. <br/><br/> Es werden nun alle Einträge einmalig indiziert. <br/><br/> Dies kann je nach Liste <b>mehrere Minuten</b> dauern.', Ext_Gui2::$sAllGuiListL10N)
                    );
                    return $aBack; 
                } else {
                    $aBack = array();
                    $aBack['forced_transferdata'] = array();
                    $aBack['forced_transferdata']['action'] = 'executeIndexStack';
                    $aBack['forced_transferdata']['error'] = array();
                }
			} else if(
				$_VARS['task'] != 'updateIcons' &&
				$bCreateWDSearchIndex
			) {

				// TODO Braucht man das noch?
				if(
					empty($aWDSearchChangedDocuments) &&
					empty($aWDSearchDeletedDocumentIDs)
				){
					$oIndex->create(array(), true);
				} else {
					$oIndex->create(array(), false);
				}

				if(!empty($aWDSearchDeletedDocumentIDs)){
					$oIndex->deleteDocuments($aWDSearchDeletedDocumentIDs);
				}

				$sIdField					= $this->query_id_column;

				// Wenn alles neugeneriert wird dann auch das Mapping setzten
				// ansonsten ist es nicht nötig
				if(empty($aWDSearchChangedDocuments)){

					$aMappingData				= array();
					$aMappingData[$sIdField]	= array('store' => true, 'type' => 'integer', 'index' => false);

					foreach((array)$aColumnListFull as $oCurrentColumn){

						$sColumn = $oCurrentColumn->db_column;

						if($oCurrentColumn->select_column){
							$sColumn = $oCurrentColumn->select_column;
						}

						// KEINE TOCKEN DURCH DEN ANALYER ERZEUGEN LASSEN SONST KANN MAN NICHT SORTIEREN
						$sIndex		= 'analyzed';
						$sAnalyzer	= 'standard';

						switch ($oCurrentColumn->wdsearch_type) {
							case 'email':
								$sAnalyzer	= 'keyword';
								break;
							case 'int':
							case 'mediumint':
							case 'integer':
							case 'float':
								$sIndex		= 'not_analyzed';
								break;
						}

						// NICHT ANALYSIEREN! Ansonsten kann man nicht sortieren sobald mind. 1 Leerzeichen bzw. eben mehr Token erzeugt wurden!
						$aMappingData[$sColumn] = array('store' => true, 'type' => 'text', 'index' => true, 'analyzer' => $sAnalyzer, "term_vector" => "with_positions_offsets");
						$aMappingData[$sColumn.'_original'] = array('store' => true, 'type' => 'text', 'index' => false, 'analyzer' => 'keyword'); // keyword for sorting
					}

					foreach($aAdditionalColumnsForMapping as $sColumn => $aMapping){
						$aMappingData[$sColumn] = $aMapping;
					}

					$oIndex->createMapping($aMappingData);

				}

				// Liste mit ALLEN DATEN laden
				$bSkipLimit = true;
				$aSelectedIds = array();
				// Wenn wir bestimmte Doc. aktuallisieren wollen
				// dann setzte die entsprechenden IDS
				if(!empty($aWDSearchChangedDocuments)){
					foreach($aWDSearchChangedDocuments as $aDoc){
						$aSelectedIds[] = $aDoc['fields'][$sIdField];
					}
				}

				$mOldWhere = $this->_aTableData['where'];
				$this->_aTableData['where'] = '';

			}

		}

		if($bSkipLimit){
			// Limit muss hier so hoch gestellt werden, da bei CSV Export von ca. 9000
			// Einträgen und 60 Spalten ca. 500k Schleifendurchläufe (s.u.)512MB nicht mehr reicht!
			set_time_limit(240);
			ini_set("memory_limit", '4G'); 
		}

		if(
			!$this->checkWDSearch() ||
			$bCreateWDSearchIndex
		){

			if($bCreateCompleteWDSearchIndex){
				set_time_limit(600); // 10min
				ini_set("memory_limit", '512M');
			}

			$aResult = $this->_oData->getTableQueryData($aFilterValues, $aOrderBy, $aSelectedIds, $bSkipLimit);

			// Where part wieder in die GUI zurück setzten
			if(
				!empty($mOldWhere)
			){
				$this->_aTableData['where'] = $mOldWhere;
			}

		}

		/**
		* Hier haben wir ein Problem mit der Reihenfolge, wenn zuerst manipulate aufgerufen wird,
		* können wir die encoded_gui_id in manipulate nicht verwenden (z.B. bei multiple_checkbox_id).
		* Wenn zuerst encode aufgerufen wird, können wir die manipulierten Daten nicht kodieren.
		* Der 2.Fall ist natürlich viel schlimmer, darum rufen wir zurzeit manipulate zuerst auf.
		* Dafür müssen wir uns noch eine Lösung ausdenken.
		*/

		// Kann abgeleitet werden um Daten zu manipulieren
		$this->_oData->manipulateTableDataResultsByRef($aResult);

		$aResult = $this->_encodeResultToGuiResult($aResult);

        $this->setDebugPart('Spalten Daten holen');

		$aColumnList = $aColumnListVisible;

		$aData = array();

		// Spalten definieren
		$aData['head'] = array();
		if($this->load_table_head_data == 1) {

			foreach((array)$aColumnList as $iKey => $oCurrentColumn) {

				/** @var Ext_Gui2_Head $oCurrentColumn */
				if(!is_object($oCurrentColumn)){
					throw new Exception("Column must be an Object");
				}

				$aData['head'][$iKey] = $oCurrentColumn->getConfigArray();

				// Bei I18N-Spalten originalen Titel (ohne Flagge) setzen und ggf. ISO -Kürzelergänzen
				if(
					$this->_bCSVExport === true &&
					!empty($aData['head'][$iKey]['i18n'])
				) { // Aus Array abfragen, da GUI2-Config-Zeug kein __isset() hat
					$aData['head'][$iKey]['title'] = $aData['head'][$iKey]['i18n']['original_title'];

					if($aData['head'][$iKey]['i18n']['all']) {
						$aData['head'][$iKey]['title'] .= ' '.strtoupper($aData['head'][$iKey]['i18n']['language']);
					}
				}

				try {
					if(
						$oCurrentColumn instanceof Ext_Gui2_Head &&
						$oCurrentColumn->group instanceof Ext_Gui2_HeadGroup
					) {
						$aData['has_column_group'] = true;
						$aData['head'][$iKey]['group'] = $oCurrentColumn->group->title;
						$aData['head'][$iKey]['group_small'] = $oCurrentColumn->group->small;
					}
				} catch(Exception $e) {
					\Util::handleErrorMessage($e->getMessage());
				}

				unset($aData['head'][$iKey]['format']);

			}

			// Export ausgabe und unsets um speicher frei zu geben
			if($this->_bCSVExport){
				$this->_oCSVExport->sendHeader();
				$this->_oCSVExport->setColumnList($aColumnList);
				$this->_sendExportHeaderRow($aData['head']);
				unset($aData['head']);
			}
			
		}

		$this->setDebugPart('Formatieren der Zeilen');

		// Tabellen inhalt

		$aData['body'] = array();
		$aData['sum'] = array();

		$aSummColumns = (array)$this->sum_row_columns;
		$sSumColumnGroupColumn = $this->sum_row_columns_group_column;
		$iSummColumns = count($aSummColumns);
		$aLastSumColumnResultData = array();

		// Zurücksetzen, ansonsten kann es vorkommen das in einer Child-Gui ein falsches Result von einem vorherigen
		// Parent-Eintrag gesetzt ist wenn $aResult['data'] für den aktuellen Parent-Eintrag leer ist
		$this->_aTempTableResult = [];

		if(is_array($aResult['data'])) {

			// WICHTIG
			// Die Keys sind teilweise sehr kurz gefasst da sonst die datengröße zu groß wird!
			// bitte keys NICHT AUSSCHREIBEN
			foreach($aResult['data'] as $iKey => $aResultData) {

				$this->_aTempTableResult = $aResultData;

				$iGuiRowID  = $this->_oData->getIdOfRow($aResultData, 1);
				$iRowID     = $this->_oData->getIdOfRow($aResultData);

				// ID für die linke Checkbox in der Tabelle
				if(!empty($aResultData['multiple_checkbox_id'])) {
					$aData['body'][$iKey]['multiple_checkbox_id'] = $aResultData['multiple_checkbox_id'];
				}
				// Name für die linke Checkbox in der Tabelle
				if(!empty($aResultData['multiple_checkbox_name'])) {
					$aData['body'][$iKey]['multiple_checkbox_name'] = $aResultData['multiple_checkbox_name'];
				}

				if(
					$this->multiple_selection == 1 &&
					$this->row_multiple_checkbox
				) {
					$mRowMultipleCheckbox = $this->row_multiple_checkbox;
					if(is_object($mRowMultipleCheckbox)) {
						if($mRowMultipleCheckbox instanceof Ext_Gui2_View_MultipleCheckbox_Abstract) {
							$mCheckboxStatus = $mRowMultipleCheckbox->getStatus($iRowID, $aColumnList, $aResultData);
							if($mCheckboxStatus == 0) {
								$aData['body'][$iKey]['multiple_checkbox_hide'] = true;
							}
						} else {
							throw new Exception("Please use an instance of Ext_Gui2_View_MultipleCheckbox_Abstract");
						}
					}
				}

				$aData['body'][$iKey]['id'] = $iGuiRowID;
				$aData['body'][$iKey]['style'] = $this->_oView->getRowDisplayStyle($iRowID, $aColumnList, $aResultData);

				if(
					$this->getParent() &&
					!empty($aResultData['parent_gui_id'])
				) {
					$aData['body'][$iKey]['parent_gui_id'] = $aResultData['parent_gui_id'];
				}

				// Kontextmenü
				if($this->_oContextMenu instanceof Ext_Gui2_View_ContextMenu_Interface) {
					$aData['body'][$iKey]['contextmenu'] = $this->_oContextMenu->getOptions($aResultData);
				}

				##########################
				## WDSearch Document    ##
				##########################
				if(
					$this->checkWDSearch() &&
					$bCreateWDSearchIndex
				){
					$iRowID = $aResultData[$this->query_id_column];
					// Wenn nur bestimmte aktuallisiert werden sollen
					// alle anderen überspringen
					if(
						!empty($aWDSearchChangedDocumentsIDs) &&
						!in_array($iRowID, $aWDSearchChangedDocumentsIDs)
					){
						continue;
					}
					$oDoc = $oType->createDocument($iRowID);
					$oDoc->set($this->query_id_column, (string)$iRowID);
					// Alle zusätzlichenfelder als Datenfelder makieren
					foreach($aAdditionalColumns as $sColumn => $aMapping){
						$oDoc->set($sColumn, (string)$aResultData[$sColumn]);
					}
				}

				##########################
				foreach((array)$aColumnList as $iCKey => $oCurrentColumn) {

					$sSelectColumn = $oCurrentColumn->db_column;
					$oColumnFormat = $oCurrentColumn->format;
					$oPostColumnFormat = $oCurrentColumn->post_format;

					if($oCurrentColumn->select_column){
						$sSelectColumn = $oCurrentColumn->select_column;
					}

					// Originalwert setzen
					if(
						$bWDSearchIndexExist &&
						!$bCreateWDSearchIndex
					){
						$sOriginalValue = $aResultData[$sSelectColumn.'_original'] ?? null;
					} else {
						$sOriginalValue = $aResultData[$sSelectColumn] ?? null;
					}
					
					$sDisplayValue = $aResultData[$sSelectColumn] ?? null;

					if($sOriginalValue === null) {
						$sOriginalValue = $sDisplayValue;
					}
					
					// Beim CSV-Export und abgekürzten Werten soll alles ausgeschrieben werden (#2497)
					if(
						$sFlexType !== 'list' && 
						$oColumnFormat instanceof Ext_Gui2_View_Format_ToolTip
					) {
						$oColumnFormat->sFlexType = $sFlexType;
						$aDisplayTitle	= $this->_oView->getColumnDisplayTitle($oCurrentColumn, $aResultData);
						$sDisplayValue	= $aDisplayTitle['data']['content'];
					} else if(!$this->wdsearch_use_stack) {
						if(
							$bWDSearchIndexExist &&
							!$bCreateWDSearchIndex
						){
							$sDisplayValue = $aResultData[$sSelectColumn];
						} else {
							$sDisplayValue = $this->_oView->getColumnDisplayValue($sOriginalValue, $oCurrentColumn, $aResultData, $sFlexType);
						}
					} else {
						#$sDisplayValue = $sOriginalValue;	

						/**
						 * #5378
						 * Daten für Alter dürfen erst hier umgewandelt werden.
						 * 
						 * siehe: Ext_Gui2_Index_Generator::_formatValue();
						 */
						if($oColumnFormat instanceof Ext_Gui2_View_Format_Age) {
							$sDisplayValue = $oColumnFormat->format($sDisplayValue, $oCurrentColumn, $aResultData);
						}
					}

					// Nachträgliche Formatierung (relevant bei Index-Gui
					if($oPostColumnFormat instanceof Ext_Gui2_View_Format_Abstract) {
						$sDisplayValue = $oPostColumnFormat->format($sDisplayValue, $oCurrentColumn, $aResultData);
					}

					if(
						(
							$bCreateWDSearchIndex &&
							$iCKey >= $iOffset &&
							$iCKey <= ($iLimit - 1) &&
							empty($aSelectedIds) &&
							!$bCreateCompleteWDSearchIndex
						) ||
						(
							$bCreateWDSearchIndex &&
							$iCKey >= $iOffset &&
							$iLimit == 0 &&
							empty($aSelectedIds) &&
							!$bCreateCompleteWDSearchIndex
						) ||
						(
							!empty($aSelectedIds) &&
							in_array($iRowID, $aSelectedIds)
						) ||
						(
							!$bCreateWDSearchIndex &&
							empty($aSelectedIds)
						)
					){

						$aData['body'][$iKey]['items'][$iCKey]['text'] = $sDisplayValue;
						$aData['body'][$iKey]['items'][$iCKey]['original'] = $sOriginalValue;
						$aData['body'][$iKey]['items'][$iCKey]['db_alias']				= $oCurrentColumn->db_alias;
						$aData['body'][$iKey]['items'][$iCKey]['db_column']				= $oCurrentColumn->db_column;
						$aData['body'][$iKey]['items'][$iCKey]['db_type']				= $oCurrentColumn->db_type;
						$aData['body'][$iKey]['items'][$iCKey]['id']					= $iGuiRowID;

						// Inhalt für title / tooltip oder Inhalt für title / tooltip (MVC)
						$aData['body'][$iKey]['items'][$iCKey]['title']					= $this->_oView->getColumnDisplayTitle($oCurrentColumn, $aResultData, 1);

						// Hintergrund
						$sBackground													= $this->_oView->getColumnDisplayStyle($sOriginalValue, $oCurrentColumn, $aResultData);
						if(!empty($sBackground)){
							$aData['body'][$iKey]['items'][$iCKey]['style']				= $sBackground;
						}

						// Zellenevent
						$sEvent															= $this->_oView->getColumnEvent($sOriginalValue, $oCurrentColumn, $aResultData);
						if(!empty($sEvent)){
							$aData['body'][$iKey]['items'][$iCKey]['event']				= $sEvent;
						}

						// Zellenevent Function
						$aEventFunction													= $this->_oView->getColumnEventFunction($sOriginalValue, $oCurrentColumn, $aResultData);
						if(!empty($aEventFunction)){
							$aData['body'][$iKey]['items'][$iCKey]['eventfunction']		= $aEventFunction;
						}

						$sTextAlign														= $this->_oView->getColumnTextAlign($oCurrentColumn);
						if(!empty($sTextAlign) && strtolower($sTextAlign) != 'left'){
							$aData['body'][$iKey]['items'][$iCKey]['ta']				= $sTextAlign;
						}

						if($oCurrentColumn->inplaceEditor == 1){
							$aData['body'][$iKey]['items'][$iCKey]['ie']				= 1;
							$aData['body'][$iKey]['items'][$iCKey]['ie_type']			= $oCurrentColumn->inplaceEditorType;
							$aData['body'][$iKey]['items'][$iCKey]['ie_options']		= $oCurrentColumn->inplaceEditorOptions;
							$aData['body'][$iKey]['items'][$iCKey]['ie_direct']			= $oCurrentColumn->inplaceEditorStart;
						}

						if($oCurrentColumn->css_overflow != false){
							$aData['body'][$iKey]['items'][$iCKey]['css_overflow']		= $oCurrentColumn->css_overflow;
						}

						// Wenn informationen für summen angeben wurden
						// dann addieren die werte der entsprechenden Spalten
						if($iSummColumns > 0){
							$mSumGroupValue = '';
							if(!empty($sSumColumnGroupColumn)){
								$mSumGroupValue = $aResultData[$sSumColumnGroupColumn];
							}

							if(in_array($sSelectColumn, $aSummColumns)){
								$fSumm = (float)$this->_oView->getColumnValue($sOriginalValue, $oCurrentColumn, $aResultData);

								$aLastSumColumnResultData[$mSumGroupValue] = $aResultData;
								$aData['sum'][$mSumGroupValue][$iCKey] += $fSumm;
							} else {
								$aData['sum'][$mSumGroupValue][$iCKey] = '';
							}
						}

					}

					##########################
					## WDSearch Fields      ##
					##########################
					if(
						$this->checkWDSearch() &&
						$bCreateWDSearchIndex
					){
						if($sDisplayValue != '0000-00-00'){
							$oDoc->set($sSelectColumn, $sDisplayValue);
						}
						if($sOriginalValue != '0000-00-00'){
							$oDoc->set($sSelectColumn.'_original', $sOriginalValue);
						}
					}
					##########################
				} 

				##########################
				## WDSearch Document    ##
				##########################
				if(
					$this->checkWDSearch() &&
					$bCreateWDSearchIndex
				){
					$oType->addDocument($oDoc);
				}
				##########################

				// Export ausgabe und unsets um speicher frei zu geben
				if($this->_bCSVExport){
					$this->_sendExportRow($aData['body'][$iKey], $aResultData);
					$aData['body'][$iKey] = array();
				}
			}
		}
        
		##########################
		## WDSearch Index       ##
		##########################
		if(
			$this->checkWDSearch() &&
			$bCreateWDSearchIndex
		){
            
            $this->setDebugPart('WDSearch index refreshen');
            
			$oIndex->refresh();
			$oIndex->forceMerge(true);
			//$oIndex->close();
		}
		##########################

        $this->setDebugPart('Formatieren der Summen Zeile');


		// Summen erneut durchlaufen und Formatieren
		foreach($aData['sum'] as $sSumKey => $aColumns){
			foreach($aColumns as $iCKey => $fSum){
				$oCurrentColumn = $aColumnList[$iCKey];
				$sDisplaySum = '';
				if(is_numeric($fSum)){
					 $sDisplaySum = $this->_oView->getColumnDisplayValue($fSum, $oCurrentColumn, $aLastSumColumnResultData[$sSumKey], 'list', true);
				}
				$aData['sum'][$sSumKey][$iCKey] = $sDisplaySum;
			}
		}

		$aData['sum'] = array_values($aData['sum']);

		if($this->_bCSVExport){
			$this->_sendExportRow($aData['sum']);
			$aData['sum'] = array();
		}
		
        $this->setDebugPart('Laden der Leisten Daten');

		$aData['loadBars'] = 0;
		if($this->load_table_bar_data == 1) {

			// Filter und Werte mitschicken; hiermit werden alle Werte ersetzt
			if (!empty($aSidebarFilters)) {
				$aData['filters_queries'] = $this->filterQueries;
				$aData['filters'] = $aSidebarFilters;
				$aData['filters_query'] = $this->filterQuery;
			}

			$aData['loadBars']	= 1;
			// Daten der oberen Leisten laden
			$aBarList = $this->getBarList();
			$aData['bar_top'] = array();
			// Daten durchlaufen und aufbereiten
			foreach($aBarList as $iKey => $oBar){
				if(is_object($oBar)){
					$aData['bar_top'][$iKey] = $oBar->getRequestBarData($aSelectedIds, $aResult['data'], $this);
				}
			}

			// Daten der unteren Leisten laden
			$aBarList = $this->getBarList(false);
			$aData['bar_bottom'] = array();
			// Daten durchlaufen und aufbereiten
			foreach((array)$aBarList as $iKey => $oBar){
				if(is_object($oBar)){
					$aData['bar_bottom'][$iKey] = $oBar->getRequestBarData($aSelectedIds, $aResult['data'], $this);
				}
			}

			// Calendar format
			$aData['sCalendarFormat'] = $this->_oCalendarFormat->format_js;

		}

        $this->setDebugPart('Laden der Pagination Daten');
        
		// Wenn der Query kein Limit hat, dann fehlt "show"
		if(empty($aResult['show'])) {
			$aResult['show'] = $aResult['count'];
		}

		if($this->load_table_pagination_data == 1){
			$aData['pagination']['offset']		= $aResult['offset'];
			$aData['pagination']['end']			= $aResult['end'];
			$aData['pagination']['total']		= $aResult['count'];
			$aData['pagination']['show']		= $aResult['show'];
			$this->_oData->iPaginationOffset	= $aResult['offset'];
			$this->_oData->iPaginationEnd		= $aResult['end'];
			$this->_oData->iPaginationTotal		= $aResult['count'];
			$this->_oData->iPaginationShow		= $aResult['show'];
		} else {
			$aData['pagination']['offset']	= $this->_oData->iPaginationOffset;
			$aData['pagination']['end']		= $this->_oData->iPaginationEnd;
			$aData['pagination']['total']	= $this->_oData->iPaginationTotal;
			$aData['pagination']['show']	= $this->_oData->iPaginationShow;
		}

        $this->setDebugPart('Laden der Übersetzungen');

		// Übersetzungen für JS
		$aData['translations'] = $this->_oData->getTranslationsCache($this->gui_description);

        $this->setDebugPart('Definieren der GUI Options');

		// Gui Optionen übermitteln
		$aData['options'] = $this->generateOptionsArray();

		// und jetzt die suche ansich starten damit pagination + filter klappen
		if($bCreateWDSearchIndex){

			$this->_oData->deletedWDSearchIndexChanges();

			$aBack = array();
			$aBack['forced_transferdata'] = array();
			$aBack['forced_transferdata']['action'] = 'displayWDSearchStartIndicateCallback';
			$aBack['forced_transferdata']['data'] = array(
				'id' => 'WDSEARCH'
			);
			return $aBack;
		}
        
        $this->setDebugEnd();

		return $aData;

	}

	public function generateOptionsArray() {

		$aOptions = $this->aOptions;

		$aOptions['row_sortable'] = $this->row_sortable;
		$aOptions['multiple_selection'] = $this->multiple_selection;
		$aOptions['column_sortable'] = $this->row_sortable ? 0 : $this->column_sortable;

		$aOptions['rows_clickable'] = $this->rows_clickable;
		$aOptions['info_icon_edit_mode'] = \Core\Handler\SessionHandler::getInstance()->get('system_infotexts_mode') === true;
		$aOptions['info_icon_help_key'] = self::INFO_ICON_HELP_KEY;
		$aOptions['info_icon_filter_key'] = \Ext_Gui2_Bar_Filter_Abstract::INFO_ICON_KEY;

		$helpTexts = collect(\Gui2\Entity\InfoText::getRepository()->findLanguageValuesForGuiDialog($this, self::INFO_ICON_HELP_KEY, 'en'));

		if (filter_var($helpUrl = data_get($helpTexts->first(), 'value'), FILTER_VALIDATE_URL)) {
			if (str_contains($helpUrl, 'support.fidelo.com')) {
				// Zendesk-URLs auf SSO umleiten (TODO existiert nur auf TC)
				$helpUrl = '/zendesk/sso?r='.urlencode($helpUrl);
			}
			$aOptions['help_url'] = $helpUrl;
		}

		return $aOptions;

	}

	public function getOneColumnData(){
		return $this->_aTempTableResult;
	}

	public function getOneColumnValue($sField){
		return $this->_aTempTableResult[$sField];
	}

#######################################
###   Methoden um Werte zu setzten  ###
#######################################


	/**
	 * Setzt den Titel der Liste
	 * @param $sTitle
	 */
	public function setTitle($sTitle){
		$this->gui_title = $sTitle;
	}

	/**
	 * @param $oBar
	 * @throws Exception
	 */
	public function setBar($oBar) {
		if(!is_object($oBar)) {
			throw new Exception("Please use a Bar Object");
		}

		$this->_aBar[] = $oBar;
	}

	/**
	 * @param Ext_Gui2_Head $oColumn
	 */
	public function setColumn(Ext_Gui2_Head $oColumn) {
		if($oColumn->group instanceof Ext_Gui2_HeadGroup) {
			$this->setOption('gui_has_column_group', true);
		}
		$this->_aColumn[] = $oColumn;
	}

	/**
	 * Setzt die Tabellen daten für Speichern/Einfügen
	 *
	 * 	$aData = array();
		$aData['table'] = 'test_wielath';
		$aData['where'] = array('active' => 1);
		$aData['limit'] = 10;
		$aData['orderby'] = array('name' => 'DESC' , 'id' => 'DESC');
	 *
	 * @param mixed $mData
	 * @param mixed $mValue
	 */
	public function setTableData($mData, $mValue=false) {

		if(is_array($mData)) {
			$aData = $mData;
		} else {
			$aData = $this->_aTableData;
			$aData[$mData] = $mValue;
		}

		if(empty($aData['select'])){
			$aData['select'][] = '*';
		}

		if(empty($aData['table'])){
			$aData['table'][] = 'not_table_name';
		}

		if($this->row_sortable == 1) {
			$aData['limit'] = 0;
		}

		$this->_aTableData = $aData;
	}



#######################################
### Methoden um Objekte zu erzeugen ###
#######################################

	/**
	 * Erzeug ein verknüpftes GUI Objekt
	 * @param <type> $sHash
	 * @param <type> $sDataClass
	 * @param <type> $sViewClass
	 * @return Ext_Gui2
	 */
	public function createChildGui($sHash='', $sDataClass = '', $sViewClass = '') {

		$GLOBALS['gui2_instance_hash'] = $this->instance_hash;

		$sClass	= get_class($this);

		/** @var Ext_Gui2 $oChildGui */
		$oChildGui = new $sClass($sHash, $sDataClass, $sViewClass);
		$oChildGui->setParent($this);
		$oChildGui->gui_description = $this->gui_description;

		return $oChildGui;
	}

	/**
	 * Erzeugt ein Leisten Objekt
	 *
	 * @return Ext_Gui2_Bar
	 */
	public function createBar($sType = '') {

		$oBar = Factory::getObject('Ext_Gui2_Bar', [$this]);
		
		switch($sType){
			case 'hint':
				$oBar->setConfig('class', 'GUIBarHint');

				$oIcon = $oBar->createIcon('/admin/extensions/gui2/exclamation.png', '', $this->t('Hinweis'));
				$oIcon->active = 1;
				$oIcon->additional = 'GUIHint';
				$oBar->setElement($oIcon);
				break;
			case 'info':
				$oBar->setConfig('class', 'GUIBarInfo');

				$oIcon = $oBar->createIcon('/admin/extensions/gui2/information.png', '', $this->t('Information'));
				$oIcon->active = 1;
				$oIcon->additional = 'GUIInfo';
				$oBar->setElement($oIcon);
				break;
			case 'error':
				$oBar->setConfig('class', 'GUIBarError');

				$oIcon = $oBar->createIcon('/admin/extensions/gui2/error.png', '', $this->t('Fehler'));
				$oIcon->active = 1;
				$oIcon->additional = 'GUIError';
				$oBar->setElement($oIcon);
				break;
			case 'legend':
				$oBar = new Ext_Gui2_Bar_Legend($this);
				break;
		}
		return $oBar;
	}

	/**
	 * Erzeugt ein Spalten Objekt
	 */
	public function createColumn(){
		$oColumn = new Ext_Gui2_Head();
		return $oColumn;
	}

	/**
	 * Erzeugt ein Spaltengruppen Objekt
	 * @param string $sKey Optionaler Schlüssel um die ColumnGruppen später einfacher zu unterscheiden
	 * @return Ext_Gui2_HeadGroup
	 */
	public function createColumnGroup($sKey = ''){

		$oColumn = new Ext_Gui2_HeadGroup();

		if(empty($sKey)){
			$this->_aColumnGroups[] = $oColumn;
		}else{
			$this->_aColumnGroups[$sKey] = $oColumn;
		}

		return $oColumn;
	}

	/**
	 * Dialogobjekt erstellen
	 *
	 * @param type $sTitle
	 * @param type $sTitleNew
	 * @param type $sTitleMultiple
	 * @return Ext_Gui2_Dialog
	 */
	public function createDialog($sTitle='', $sTitleNew='', $sTitleMultiple='') {
		$oDialog = new Ext_Gui2_Dialog($sTitle, $sTitleNew, $sTitleMultiple);
		$oDialog->oGui =& $this;
		return $oDialog;
	}

	public function createDialogUpload($sTitle, &$oDialog, $sDbColumn, $sDbAlias = '', $sUploadPath = '/storage/atg2/uploads/', $bReadOnly = false, $aOptions = array()) {
		$oUpload = new Ext_Gui2_Dialog_Upload($this, $sTitle, $oDialog, $sDbColumn, $sDbAlias, $sUploadPath, $bReadOnly, $aOptions);
		return $oUpload;
	}

	/**
	 * Fügt einen Dialog zur Gui hinzu, ohne ihm ein Icon zuweisen zu müssen
	 *
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param string $sAction
	 * @param string $sAdditional
	 */
	public function addDialog($oDialog, $sAction, $sAdditional=null) {

		$oHiddenIcon = new Ext_Gui2_Bar_Icon('');
		$oHiddenIcon->action = $sAction;

		if($sAdditional !== null) {
			$oHiddenIcon->additional = $sAdditional;
		}

		$oHiddenIcon->dialog_data = $oDialog;

		$this->getDataObject()->aIconData[$oHiddenIcon->getKey()] = $oHiddenIcon->getConfigArray();

	}

#########################################
### Methoden zum generieren der Liste ###
#########################################

	public function checkAccess($aAccess){
		return true;
	}

	public function startPageOutput($bFirst = true){

		$oAccess = Access::getInstance();

		if(
			is_array($this->access)
		){
			$this->checkAccess($this->access);
		} else if(
			$this->access != '' &&
			!$oAccess->hasRight($this->access)
		){
			return false;
		}

		$oHtml = new Ext_Gui2_Html($this);
		$oHtml->generatePageHtml($bFirst);

		// um Caching Probleme vermeiden
//		if(!is_array($_SESSION['gui2'][$this->hash])) {
//			$_SESSION['gui2'][$this->hash] = array();
//		}

		//$_SESSION['gui2'][$this->hash][$this->instance_hash] = $this;
		$this->save();
		
		$this->logCall();

	}
	
	public function canDisplay()
	{	
		$oAccess = Access::getInstance();

		if(
			$this->access != '' &&
			(
				!$oAccess instanceof Access_Backend ||
				!$oAccess->hasRight($this->access)
			)
		){
			return false;
		}
		
		return true;
	}

	/**
	 * Gibt die Liste aus
	 */
	public function display($aOptionalData = array(), $bNoJavaScript=false) {

		$this->addOptionalData($aOptionalData);

		$oHtml = new Ext_Gui2_Html($this);

		$oHtml->generateHtml($bNoJavaScript);

		$this->save();

		$this->logCall();
		
	}
	
	public function getHtmlHeader() {

		$oHtml = new Ext_Gui2_Html($this);
		$aOptions = $oHtml->generateHtmlHeader();

		$sReturn = $aOptions['additional_top'];
		$sReturn .= $aOptions['additional'];
		$sReturn .= $aOptions['additional_bottom'];
		return $sReturn;

	}

	public function getJsFooter() {
		$oHtml = new Ext_Gui2_Html($this);
		$sJs = $oHtml->getJsFooter();
		return $sJs;
	}

	public function getJsInitCode() {
		$oHtml = new Ext_Gui2_Html($this);
		$oJs = $oHtml->getJsInitializationCode();
		return $oJs;

	}


	/**
	 * speichert die Liste in der Session
	 */
	public function save() {

		$oAccess = Access::getInstance();

		$oData = $this->getDataObject();

		//Muss auf null gesetzt werden, wegen der Meldung "Serialization of Illuminate\Http\UploadedFile is not allowed" in der serialize()
		$this->oRequest = null;
		$oData->resetRequest();

		if($this->oParent) {
			$this->oParent->oRequest = null;
			// Hier darf nur der Request entfernt werden und nicht die WDBasic (resetRequest)!
			$this->oParent->getDataObject()->request = null;
		}
		
		// WDBasic zurücksetzten damit beim öffnen eines anderen Dialoges nichts durcheinanderkommt
		$oData->oWDBasic = null;
		
		// WDBasic aus Dialog-Data genauso entfernen
		// Anmerkung: Das ist auch wichtig damit die Session nicht vollläuft (JoinedObjects usw.)
		foreach($oData->aIconData as &$aIcon) {
			if (
				!empty($aIcon['dialog_data']) &&
				$aIcon['dialog_data'] instanceof Ext_Gui2_Dialog
			) {
				/** @var Ext_Gui2_Dialog $oDialog */
				// TODO Das müsste optional laufen, also einstellbar pro Dialog (z.B. Marker Interface)
//				if ($mFactory = $aIcon['dialog_data']->getOption('factory')) {
//					$aIcon['dialog_data'] = new \Gui2\Dialog\LazyDialog($mFactory);
//					continue;
//				}

				$oDialog = $aIcon['dialog_data'];
				$oDialog->getDataObject()->resetWDBasicObject();
			}
		}

		Ext_Gui2_Session::write($oAccess->key, $this);

	}

#########################################
###     Methoden für den Request      ###
#########################################

	public function switchAjaxRequest($_VARS){

		$oData = $this->_oData;
		$oData->setRequest($this->oRequest);

		// WDBasic zurücksetzten damit beim öffnen eines anderen Dialoges nichts durcheinanderkommt
		$oData->oWDBasic = null;

		$oData->switchAjaxRequest($_VARS);

		$this->save();

	}

	/**
	 * Setzt den Namen der WDBasic Klasse die verwendet werden soll
	 * Wenn nix gesetzt ist, wird WDBasic verwendet
	 * @param string $sClassName
	 * @return string aktueller name der Klasse
	 */
	public function setWDBasic($sClassName) {

		if(empty($sClassName)) {
			$sClassName = 'WDBasic';
		}
		$this->class_wdbasic = $sClassName;

		$oClass = new $sClassName();

		$aData = array();
		$aData['table'] = $oClass->getTableName();
		$this->setTableData($aData);

		return $this->class_wdbasic;

	}

	/**
	 * Gibt die benutzte WDBasic zurück
	 * Benutzt die übergebene ID als ID für das Object
	 * @param <int> $iId
	 * @return WDBasic
	 */
	public function getWDBasic($iId = 0) {

		$sClassName = $this->class_wdbasic;
		if(empty($sClassName)) {
			$sClassName = 'WDBasic';
		}

		$oClass = $sClassName::getInstance((int)$iId);

		return $oClass;

	}

	/**
	 * Funktion gibt alle relevanten Infos der Parent GUIs zurück wenn es welche gibt
	 */
	public function getParentGuiData(){
		$aReturn = array();

		foreach((array)$this->getConfig('parent_gui') as $sParentHash){
			$oParentGui = $this->getParentClass($sParentHash);

			$aInfo = array();
			$aInfo['hash']		= (string)$sParentHash;
			$aInfo['class_js']	= (string)$oParentGui->class_js;
			$aReturn[] = $aInfo;
		}

		return $aReturn;
	}

	/**
	 * Prüft ob Encode der Gui angeschalten ist
	 * @return <bol>
	 */
	final public function checkEncode(){
		$aEncodeData	= $this->encode_data;

		// Wenn keine Encode Daten angebenen, mache nichts
		if(
			empty($aEncodeData) ||
			$aEncodeData == 0
		){
			return false;
		}

		return true;
	}

	/**
	 * Encodiert das Result
	 * @param <array> $aResult
	 * @return <array>
	 */
	private function _encodeResultToGuiResult($aResult){

		$iIdColumn = $this->query_id_column;

		// Wenn keine Encode Daten angebenen, mache nichts
		if(!$this->checkEncode()){
			return $aResult;
		}

		// Result durchgehen und die ID in eine GUI ID umwandeln
		// wenn es nicht klappt -> result löschen
		foreach((array)$aResult['data'] as $iKey => $aData){
			// Schauen ob die ID vorhanden ist
			if(
				isset($aData[$iIdColumn]) ||
				!$this->encode_data_unset_empty
			) {
				// Daten encodieren und Gui ID zurückgeben
				$aResult['data'][$iKey]['encoded_gui_id'] = $this->_encodeData($aData);
			} else {
				unset($aResult['data'][$iKey]);
			}
		}

		return $aResult;
	}

	public function encodeData($aData){
		$iGuiId = $this->_encodeData($aData);
		return $iGuiId;
	}

	/**
	 * Encodiert die Daten
	 * und gibt die dabei generierte Gui ID zurück
	 * @param <array> $aData
	 * @return <int> $iGuiId
	 */
	final protected function _encodeData($aData){

		// Gui Daten holden
		$aEncodeData	= $this->encode_data;
		$iIdColumn		= $this->query_id_column;

		$mRealId		= $aData[$iIdColumn];

		// ID ist immer der erste wert
		$aFinalEncodeData = array($iIdColumn => $mRealId);
		$aEncodeKey = array($mRealId);

		// Alle angegebenen Infos zur genierieten ID hinzufügen
		foreach((array)$aEncodeData as $sColumn) {

			// Wenn ID dann überspringe es, da ID automatisch selectiert wird
			if(
				$sColumn == $iIdColumn ||
				!is_string($sColumn)
			){
				continue;
			}

			// Wenn Wert numerisch ist, dann als INT behandeln
			if(is_numeric($aData[$sColumn])) {
				if(strpos($aData[$sColumn], '.') !== false){
					$aData[$sColumn] = (float)$aData[$sColumn];
				} else {
					$aData[$sColumn] = (int)$aData[$sColumn];
				}
			}

			// Prüfen ob der Wer vorhanden ist, ansonsten NULL schreiben
			if(isset($aData[$sColumn])){
				$aFinalEncodeData[$sColumn] = $aData[$sColumn];
			} else {
				$aFinalEncodeData[$sColumn] = NULL;
			}
			$aEncodeKey[] = $aData[$sColumn];
		}

		$sEncodeKey = implode("_", $aEncodeKey);

		if(array_key_exists($sEncodeKey, $this->_aEncodeKeys)) {
			$iGuiId = $this->_aEncodeKeys[$sEncodeKey];
		} else {
			// EncodeData ERWEITERN, IDs werden IMMER WEITER geschrieben
			$iGuiId = count($this->_aEncodeData);
			$this->_aEncodeData[$iGuiId] = $aFinalEncodeData;

			$this->_aEncodeKeys[$sEncodeKey] = $iGuiId;
		}

		return $iGuiId;

	}

	final public function updateEncodedId($iGuiId, $sKey, $mValue) {

		if(
			isset($this->_aEncodeData[$iGuiId]) &&
			array_key_exists($sKey, $this->_aEncodeData[$iGuiId])
		) {
			$this->_aEncodeData[$iGuiId][$sKey] = $mValue;
		}

	}

	final public function encodeId($mIds){

		// Wenn keine Encode Daten angebenen, mache nichts
		if(!$this->checkEncode()){
			return $mIds;
		}

		$iIdColumn		= $this->query_id_column;

		if(is_array($mIds)){
			foreach($mIds as $iKey => $iId){
				$mIds[$iKey] = $this->_encodeData(array($iIdColumn => $iId));
			}
		} else {
			$mIds = $this->_encodeData(array($iIdColumn => $mIds));
		}

		return $mIds;

	}

	/**
	 * Decodiert eine Gui ID und liefert alle Werte
	 * oder die angebenen
	 * @param <int> $iGuiId
	 * @param <mixed> $mColumn
	 * @return <mixed>
	 */
	final public function decodeId($mGuiId, $mColumn = '') {

		$mBack = array();

		// Gui Encode Daten Holen
		$aEncodeData = $this->_aEncodeData;

		if(!$this->checkEncode()) {
			return $mGuiId;
		}

		foreach((array)$mGuiId as $iKey => $iGuiId) {

			// Alle Daten durchlaufen
			foreach((array)$aEncodeData as $iCurrentGuiId => $aData) {

				// Wenn es der Aktuelle Datensatz ist
				if($iCurrentGuiId == $iGuiId) {

					// liefer den verlangten Wert zurück
					if($mColumn == ''){
						$mBack[$iKey] =  $aData;
					} else if(is_string($mColumn)){
						$mBack[$iKey] =  $aData[$mColumn];
					} else if(is_array($mColumn)){
						foreach((array)$mColumn as $sColumn){
							$mBack[$iKey][] =  $aData[$sColumn];
						}
					} else {
						$mBack[$iKey] =  false;
					}
				}

			}
		}

		// Wenn Array übergeben, dann gebe auche in Array zurück
		if(is_array($mGuiId)){
			return $mBack;
		} else {
			return reset($mBack);
		}

	}

	public function t($sTranslate, $sDescription = null) {

		if($this->bSkipTranslation !== false) {
			return $sTranslate;
		}

		if($this->gui_description === null) {
			throw new RuntimeException('Empty translation group is not allowed!');
		}

		if($sDescription === null) {
			$sDescription = $this->gui_description;
		}

		return L10N::t($sTranslate, $sDescription);
	}

	/**
	 *
	 * @return Ext_Gui2
	 */
	public function getParentClass($sParentHash=false){

		if(!$sParentHash){
			$sParentHash = $this->parent_hash;
		}
		if(empty($sParentHash)){
			return false;
		}

		return self::getClass($sParentHash, $this->instance_hash);

	}

	/**
	 * Fügt I18N Spalten in die GUI
	 *
	 * Redundant mit addLanguageColumns(), die andere Methode kann aber mehr!
	 *
	 * @deprecated
	 *
	 * @param type $aLanguages
	 * @param type $sColumn
	 * @param type $sJoinAlias
	 * @param type $sTitle
	 * @param type $bResize
	 */
	public function addI18nColumns($sTitle, array $aOptions, array $aLanguages=null) {

		if(
			$aLanguages === null &&
			$this->i18n_languages
		) {
			$aLanguages = (array)$this->i18n_languages;
		}

		foreach($aLanguages as $aLanguage) {

			$oColumn				= $this->createColumn();
			$oColumn->db_column		= $aOptions['join_table_field'];
			$oColumn->db_alias		= $aOptions['join_table_key'].'_'.$aLanguage['iso'];
			$oColumn->select_column	= $aOptions['join_table_field'].'_'.$aLanguage['iso'];
			$oColumn->title			= '<img src="'.Util::getFlagIcon($aLanguage['iso']).'" title="'.\Util::convertHtmlEntities($aLanguage['name']).'" /> '.$sTitle;
			if(isset($aOptions['width'])) {
				$oColumn->width			= $aOptions['width'];
			}
			if(isset($aOptions['width_resize'])) {
				$oColumn->width_resize	= $aOptions['width_resize'];
			}
			if(isset($aOptions['format'])) {
				$oColumn->format		= $aOptions['format'];
			}
			$oColumn->sortable		= 1;
			$this->setColumn($oColumn);

		}

	}

	public function checkWDSearch() {

		if($this->_bCheckWDSearch === null) {

			$bWDSearch = $this->wdsearch;
			return $bWDSearch;

			// Komische Prüfung, die nie etwas brachte, da immer true
			/*if($bWDSearch) {

				// hier muss ein anderer index benutzt werden da sonst immer gedacht werden würde das es den index schon gibt
				$oIndex	= new \ElasticaAdapter\Adapter\Index('wdsearch_check_index');

					$this->_bCheckWDSearch = true;

			} else {

				$this->_bCheckWDSearch = false;

			}*/

		}

		return $this->_bCheckWDSearch;

	}

	public function addCss($sCss, $sExt = '') {
		$this->_aCssFiles[] = '/admin/extensions/'.$sExt.'/'.$sCss;
	}

	public function addJs($sJs, $sExt = '') {
		$this->_aJsFiles[] = '/admin/extensions/'.$sExt.'/'.$sJs;
	}
	
	public function addOptionalData($aOptionalData){
		foreach((array)($aOptionalData['js'] ?? []) as $sFile){
			$this->_aJsFiles[] = $sFile;
		}
		foreach((array)($aOptionalData['css'] ?? []) as $sFile){
			$this->_aCssFiles[] = $sFile;
		}
	}

	public function getJSandCSSFiles() {
		$aArray = array(
			'js' => $this->_aJsFiles,
			'css' => $this->_aCssFiles
		);

		return $aArray;
	}

	/**
	 * aktiviert das Exportieren 
	 */
	public function enableCSVExport() {
		$sCharset	= $this->getDataObject()->getCharsetForExport();
		$sSeparator	= $this->getDataObject()->getSeparatorForExport();
		$this->_bCSVExport = true;
		
		$this->_oCSVExport = new Gui2\Service\Export\Csv($this->gui_title);
		$this->_oCSVExport->setCharset($sCharset);
		$this->_oCSVExport->setSeperator($sSeparator);
		
	}
	
	/**
	 * aktiviert das Exportieren 
	 */
	public function enableExcelExport() {
		
		$this->_bCSVExport = true;
		$this->_oCSVExport = new Gui2\Service\Export\Excel($this->gui_title);
		
	}
	
	/**
	 *deaktiviert das Exportieren 
	 */
	public function disableExport() {
		$this->_bCSVExport = true;
		$this->_oCSVExport->end();
	}
		
	protected function _sendExportHeaderRow($aRow){
		$this->_oCSVExport->sendLine($aRow);
	}
	
	protected function _sendExportRow($aRow, $aResultData=null){
		$this->_oCSVExport->sendLine($aRow['items'], $aResultData);
	}

	/**
	 * Methode, die aufgerufen wird, wenn die GUI erstellt wurde und Werte gesetzt wurden
	 */
	public function executeGuiCreatedHook()
	{

	}

	protected function _addWDSearchIDFilter(\ElasticaAdapter\Facade\Elastica $oSearch, array $aSelectedIds, $sIdField)
	{
		$this->_oData->addWDSearchIDFilter($oSearch, $aSelectedIds, $sIdField);
	}
    
    public function getUploadRequestFile()
    {
        return '/gui2/request';
    }

	/**
	 * Werte aus der GUI default.yml überschreiben
	 *
	 * @param array $aConfig
	 */
	public static function manipulateDefaultConfig(array &$aConfig) {

	}

	/**
	 * Mapping von Entitäten auf ihren Index (wenn diese überhaupt einen haben)
	 *
	 * @return string[]
	 */
	public static function getIndexEntityMapping() {
		return [];
	}

	/**
	 * Setzt die aktuelle Page
	 *
	 * @param Ext_Gui2_Page $oPage
	 */
	public function setPage(Ext_Gui2_Page $oPage) {
		$this->oPage = $oPage;
	}

	/**
	 * Gibt die aktuelle Page der Gui zurück falls vorhanden, ansonsten wird null zurückgegeben!
	 *
	 * @return Ext_Gui2_Page
	 */
	public function getPage() {
		return $this->oPage;
	}
	
	/**
	 * Speziell für API-Anfrage, damit da Manipulationen durchgeführt werden können
	 */
	static public function manipulateApiRequest(\MVC_Request $request) {
		
	}
	
}
