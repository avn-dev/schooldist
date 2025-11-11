<?php

class Ext_TC_Gui2_Design_Tab_Element extends Ext_TC_Basic {

	const ACTION_EDIT = 'edit';

	const ACTION_DELETE = 'delete';

	const ACTION_MOVE = 'move';

	const EVENT_ADDED = 'added';

	const EVENT_EDITED = 'edited';

	const EVENT_CLONED = 'cloned';

	protected $_sTable = 'tc_gui2_designs_tabs_elements';
	
	########################################################
	## @author CW
	## Spezial Config Variablen
	## wegen gewohnheit wurden sie wie in der gui benannt
	## die Eigenschaften die es in der GUI nicht gibt wurden 
	## ebenfalls im gleichen schema benannt da diese variablen
	## alle an der geleichen stelle definiert werden und
	## ich nicht zwischen namensformatierungen springen wollte
	########################################################
	
	// DB Column for Dialog, etc..
	public $db_column				= '';
	// DB Alias for Dialog, etc..
	public $db_alias				= '';
	// Format Class for Dialog
	public $format					= '';
	// definiert die selection klasse
	public $selection				= '';
	// definiert die select options
	public $select_options			= [];
	// mehrfachauswahl
	public $multiple				= 0;
	// definiert die css klasse
	public $class					= '';
	// definiert die css klasse
	public $display_age				= 0;
	// setzt das Element auf readonly
	public $readonly				= false;
	// setzt das Element auf disabled
	public $disabled				= true;
	// gibt an ob das Element editiert werden kann
	public $editable				= true;
	// gibt an ob das Element gelöscht werden kann
	public $deleteable				= true;
	// Hier kann eine Closure definiert werden um das Element abhängig vom WDBasic-Objekt zu verändern
	public $entityCallback 			= null;
	// Aktionen die auf das Element ausgeführt werden dürfen
	public $actions = ['*'];
	// Event-Listeners die bei einer bestimmten Aktion des Elementes ausgeführt werden
	protected $eventListeners = [];

	/**
	 * Hash eines CONTAINER Elementes welches als (pflicht) Elternerlement dient 
	 * Wenn angegeben kann das Element nur dort hineingezogen werden
	 * 
	 * null = überall
	 * '' = Nur im Root (Anfrage oder Buchung)
	 * 'ELEMENT_HASH' = Nur im entsprechenden Block
	 * 
	 * @var string
	 */
	public $allowed_parent = null;
	// call_user_func for Dialog ROW ( Param. 1 -> oDialog )
	public $special_dialog_method	= '';
	// definiert ob der Spezial bereich(falls defineiert) sich wiederholt
	public $special_repeat			= true;
	// Object mit infos äber die abhängigkeit
	public $special_dependence	= '';
	// if this Element for Filter Options avaiable
	public $filterelement = 1;
	// Kann das Element als Pflichtfeld markiert werden?
	public $required_setting = false;

	########################################################
	
	
	// Aktuelle ID des Eintrages
	// wenn element im GUI Dialog zusammengebaut wird
	public $selected_id = 0;

	protected $_sDesignerSection = '';


	// Counter fär Flags von Abhängigen Elementen
	protected static $self_flag_count = 1;
	// Aktuelle Flags der Container Elemente die Abhängigkeiten haben und bereits einmal geflagt wurden
	protected static $self_flag = array();
	// Aktuellen Designer ID(Hash)
	protected $designer_id = '';

	public function __construct($iDataID = 0, $sTable = null) {
		parent::__construct($iDataID, $sTable);
		
		if($iDataID > 0){
			$this->mergeDesignerElementValues();
		}
	}

	public static function getObjectFromArray(array $aData) {
		$element = parent::getObjectFromArray($aData);
		$element->mergeDesignerElementValues();
		return $element;
	}

	public function mergeDesignerElementValues() {

		$sSection = $this->getSection();
		$this->_sDesignerSection = $sSection;

		// Default einstellungen des Designerobject einspielen
		$oDesignerElement = $this->searchDesignerElement();

		if($oDesignerElement instanceof Ext_TC_Gui2_Design_Tab_Element){
			$this->selection				= $oDesignerElement->selection;
			$this->select_options			= $oDesignerElement->select_options;
			$this->allowed_parent			= $oDesignerElement->allowed_parent;
			$this->format					= $oDesignerElement->format;
			$this->db_alias					= $oDesignerElement->db_alias;
			$this->db_column				= $oDesignerElement->db_column;
			$this->special_dialog_method	= $oDesignerElement->special_dialog_method;
			$this->special_repeat			= $oDesignerElement->special_repeat;
			$this->special_dependence		= $oDesignerElement->special_dependence;
			$this->class					= $oDesignerElement->class;
			$this->display_age				= $oDesignerElement->display_age;
			$this->filterelement			= $oDesignerElement->filterelement;
			$this->multiple					= $oDesignerElement->multiple;
			$this->editable					= $oDesignerElement->editable;
			$this->deleteable				= $oDesignerElement->deleteable;
			$this->entityCallback			= $oDesignerElement->entityCallback;
			$this->actions					= $oDesignerElement->actions;
			$this->required_setting			= $oDesignerElement->required_setting;
			$this->eventListeners			= $oDesignerElement->getEventListeners();
		}

	}

	public function isEditable(): bool {
		return $this->editable;
	}

	public function isDeleteable(): bool {
		return $this->deleteable;
	}

	public function isFlexibleField(): bool {
		return $this->type === 'flexibility';
	}

	public function isRequired(): bool {

		if ($this->isFlexibleField()) {
			// Flexible Felder haben ihre eigenen Einstellungen
			$flexField = Ext_TC_Flexibility::getInstance($this->special_type);
			return $flexField->isRequired();
		}

		return $this->required;
	}

	public function getSection(){
		$oTab = $this->getTab();
		$oDesign = $oTab->getDesign();
		$sSection = $oDesign->section;
		return $sSection;
	}

	/**
	 *
	 * @return Ext_TC_Gui2_Design_Tab
	 */
	public function getTab(){
		$oTab = Ext_TC_Gui2_Design_Tab::getInstance($this->tab_id);
		return $oTab;
	}
	
	protected $_aFormat = array(
		'tab_id' => array(
			'validate' => 'INT_NOTNEGATIVE'
		)
	);
	
	/**
	 * Join Tables
	 */
	protected $_aJoinTables = array(
		'i18n' => array(
			'table' => 'tc_gui2_designs_tabs_elements_i18n',
			'foreign_key_field' => array('language_iso', 'name'),
			'primary_key_field' => 'element_id'
		)
		
	);
	
	/**
	 * Join Objects
	 */
	protected $_aJoinedObjects = array(
		'elements' => array(
			'class'=>'Ext_TC_Gui2_Design_Tab_Element',
			'key'=>'parent_element_id',
			'type'=>'child',
			'check_active'=>true,
			'orderby'=>'position',
			// Standardmäßig nicht klonen, da die Elemente durch die parent_element_id noch untereinander verknüpft sind
			'cloneable' => false
		),
		'select_options' => array(
			'class'=>'Ext_TC_Gui2_Design_Tab_Element_Selectoption',
			'key'=>'element_id',
			'type'=>'child',
			'check_active'=>true,
			'cloneable' => false
		)		
	);
	
	/**
	 * gibt die aktuellen daten zuräck
	 * @return type 
	 */
	public function getArray() {
		$aData = $this->_aData;
		$aData['allowed_parent'] = $this->allowed_parent;
		return $aData;
	}
	
	/**
	 * wrapper fär den Element Hash
	 * @param type $sName
	 * @return type 
	 */
	public function __get($sName) {
		if($sName == 'element_hash'){
			return $this->generateDesignerID(true);
		}
		return parent::__get($sName);
	}
	
	/**
	 * Wrapper fär den Element Hash
	 * @param type $sName
	 * @param type $mValue 
	 */
	public function __set($sName, $mValue) {
		
		if($sName == 'designer_section') {

			$this->_sDesignerSection = $mValue;

		} elseif($sName == 'element_hash'){
		
			$sDesignerClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Designer');
			$oDesigner = new $sDesignerClass(0);
			if(!empty($this->_sDesignerSection)){
				$oDesigner->setSection($this->_sDesignerSection);
			}
			
			$oElement = $oDesigner->findElementWithHash($mValue);

			if($oElement instanceof Ext_TC_Gui2_Design_Tab_Element) {
				$this->type				= $oElement->type;
				$this->special_type		= $oElement->special_type;
			} elseif($oElement instanceof Ext_TC_Flexibility) {
				$this->type				= 'flexibility';
				$this->special_type		= $oElement->id;
			}

			$this->designer_id = $mValue;

		} elseif(strpos($sName, 'name_') === 0) {
			
			$sLanguage = str_replace('name_', '', $sName);
			$this->setI18NName($mValue, $sLanguage);
			
		} else {
			parent::__set($sName, $mValue);
		}
		
	}


	/**
	 * get the Value for an Element/Entry Set
	 * @param integer $iEntryId
	 * @return string 
	 */
	public function getValue($iEntryId, $iAdditionalId = 0, $iAdditionalClass = ''){

		$aResult = DB::getJoinData(
			'tc_gui2_designs_tabs_elements_values',
			array(
				'element_id'		=> (int)$this->id,
				'additional_id'		 => (int)$iAdditionalId,
				'additional_class'	=> (string)$iAdditionalClass,
				'entry_id'			=> (int)$iEntryId
				),
			'value'
		);

		return (string)$aResult[0];
	}

	public function getSelectValue($iSelectOption, $sLang = '') {
		
		$oSelectOption = $this->getJoinedObjectChild('select_options', $iSelectOption);
		if($oSelectOption) {
			return $oSelectOption->getName($sLang);
		}
		
		return '';
	}
	
	/**
	 * Set the Value of an Element/Entry Set
	 * @param integer $iEntryId
	 * @param string $mValue 
	 */
	public function setValue($iEntryId, $mValue, $iAdditionalId = 0, $sAdditionalClass = ''){
		DB::updateJoinData(
			'tc_gui2_designs_tabs_elements_values',
			array(
				'entry_id'		=> (int)$iEntryId, 
				'element_id'	=> (int)$this->id, 
				'additional_id' => (int)$iAdditionalId, 
				'additional_class' => (string)$sAdditionalClass
				),
			$mValue,
			'value'
		);
	}
	
	/**
	 * Check if the Parent Element ist Valid
	 * @param integer $iParentElement
	 * @return boolean 
	 */
	public function checkParent($iParentElement){

		$oElement = $this->searchDesignerElement();

		$bValid = false;

		if($oElement->allowed_parent === null) {

			$bValid = true;

		} else {
			
			$sParentHash = '';
			
			// Wenn das Element im Root ist, dann muss ParentHash leerer String sein
			if($iParentElement > 0) {
				$oParent = Ext_TC_Gui2_Design_Tab_Element::getInstance($iParentElement);

				// Auf das nächste Elternelement gehen
				while(
					$oParent->type === 'content' &&
					$oParent->special_type === ''
				) {
					$oParent = $oParent->getParent();
				}

				if($oParent instanceof self) {
					$sParentHash = $oParent->generateDesignerID();	
				}
				
			}

			if($sParentHash === $oElement->allowed_parent){
				$bValid = true;
			} else if($oParent->parent_element_id > 0){
				$bValid = $oParent->checkParent($iParentElement);
			}
		}

		return $bValid;
	}
	
	/**
	 * check if the Element has an Doubler
	 * @return boolean 
	 */
	public function checkForDoubleEntry(){

		$sSql = " SELECT
						`tc_gdte_1`.`id`
					FROM
						`tc_gui2_designs_tabs_elements` `tc_gdte_1` INNER JOIN
						`tc_gui2_designs_tabs_elements` `tc_gdte_2` ON
							`tc_gdte_1`.`special_type` = `tc_gdte_2`.`special_type` AND
							`tc_gdte_1`.`id` != `tc_gdte_2`.`id`
					WHERE
						`tc_gdte_1`.`id` = :id AND
						`tc_gdte_1`.`special_type` != '' AND
						`tc_gdte_1`.`active` = 1	 AND
						`tc_gdte_2`.`active` = 1
					GROUP BY
						`tc_gdte_1`.`id`
				";
		$aSql = array('id' => (int)$this->id);
		$aResult = DB::getPreparedQueryData($sSql, $aSql);
		$oDB = DB::getDefaultConnection();

		if(count($aResult) > 0){
			$bCheck = true;
		} else {
			$bCheck = false;
		}
		
		return $bCheck;
	}
	
	/**
	 * Gibt den Namen in der passenden Sprache zuräck
	 * @return type 
	 */
	public function getName(){
		
		$aData = $this->i18n;

		foreach((array)$aData as $aLanguage) {
			
			if($aLanguage['language_iso'] == Ext_TC_System::getInterfaceLanguage()) {
				$sName = $aLanguage['name'];
			}
			
		}
		
		// Wenn keiner angegeben nehme den namen des Designers
		if(
			empty($sName)
		){
			$sName = $this->searchDesignerName();
		}
		
		return $sName;
	}
	
	/**
	 * Gibt alle Kind Elemente
	 * @return Ext_TC_Gui2_Design_Tab_Element[] 
	 */
	public function getChildElements(){
		$aElements = $this->getJoinedObjectChilds('elements');
		return $aElements;
	}
	
	/**
	 * gibt alle Kind Elemente in Spalte x
	 * @param int $iColumn
	 * @return Ext_TC_Gui2_Design_Tab_Element[]
	 */
	public function getChildsForColumn($iColumn){
		$aElements = $this->getChildElements();
		
		$aChilds = array();
		foreach((array)$aElements as $oElement){
			if($oElement->parent_element_column == $iColumn){
				$aChilds[] = $oElement;
			}
		}
		return $aChilds;
	}
	
	/**
	 * generiert eine eindeutige ID(Hash) die fär den Designer benätigt wird
	 * anhand dieser ID kann jeder eintrag eindeutig zur uhrsprungs konfig des Designers zuräckverfolgt werden
	 * @param bool $bSkipId
	 * @return type 
	 */
	public function generateDesignerID($bSkipId=false) {

		$sBack = 'element_';
		if(
			$this->special_type == "" &&
			$bSkipId === false
		){
			$sBack = md5('element_'.$this->id);
		} else {
			$sBack .= md5($this->type.'_'.$this->special_type);
		}

		return $sBack;
	}
	
	/**
	 * Suche findet nach Hash mit und ohne ID statt, da je nach Kontext ein anderer Hash bereit steht
	 *
	 * @return Ext_TC_Gui2_Design_Tab_Element
	 */
	public function searchDesignerElement() {

		$sDesignerClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Designer');
		/* @var \Ext_TC_Gui2_Designer $oDesigner */
		$oDesigner = new $sDesignerClass(0);

		if(!empty($this->_sDesignerSection)){
			$oDesigner->setSection($this->_sDesignerSection);
		}

		$sHash = $this->generateDesignerID();

		$oElement = $oDesigner->findElementWithHash($sHash);
		if($oElement === false) {
			$sHashWithoutId = $this->generateDesignerID(true);
			$oElement = $oDesigner->findElementWithHash($sHashWithoutId);
		}

		return $oElement;
	}
	
	/**
	 * sucht den Namen des Elementes welcher im Designer benutzt wird
	 * @return type 
	 */
	public function searchDesignerName(){
		
		$oElement = $this->searchDesignerElement();

		if($oElement){
			return $oElement->getName();
		}
		
		return '';
	}
	
	/**
	 * Generiert den HTML Block eines Elementes fär den DESIGNER
	 * @param type $iParentColumn
	 * @param type $sParentHash
	 * @return type 
	 */
	public function generateDesignerContentHtml($iParentColumn = 0, $sParentHash = ''){

		// Smarty starten
		$oSmarty = new SmartyWrapper();
		//
		$oSmarty->assign('sCalendarIconPath', Ext_TC_Util::getIcon('calendar'));

		$sCode = "";

		$sTpl = 'tab/content.tpl';

		switch ($this->type) {
			case 'input':
				$sTpl = 'tab/input.tpl';
				break;
			case 'textarea':
				$sTpl = 'tab/textarea.tpl';
				break;
			case 'html':
				$sTpl = 'tab/html.tpl';
				break;
			case 'date':
				$sTpl = 'tab/calendar.tpl';
				break;
			case 'checkbox':
				$sTpl = 'tab/checkbox.tpl';
				break;
			case 'upload':
				$sTpl = 'tab/upload.tpl';
				break;
			case 'image':
				$sTpl = 'tab/image.tpl';
				break;
			case 'select':
				$sTpl = 'tab/select.tpl';
				break;
			default:
			case 'content':
				// Wenn content gebe das html der enthaltenen elmenete wieder
				// und zwar fär die aktuelle spalte
				if($iParentColumn == 2){
					$aChilds = $this->getChildsForColumn(2);
					foreach((array)$aChilds as $oChild){
						$oChild->readonly = $this->readonly;
						$oChild->disabled = $this->disabled;
						
						$sCode .= $oChild->generateDesignerHtml($sParentHash);
					}
				} else {
					$aChilds = $this->getChildsForColumn(1);
					foreach((array)$aChilds as $oChild){
						$oChild->readonly = $this->readonly;
						$oChild->disabled = $this->disabled;
						$sCode .= $oChild->generateDesignerHtml($sParentHash);
					}
				}
				return $sCode;
				break;
		}

		// Rendern
		$sCode = $oSmarty->fetch(Ext_TC_Gui2_Designer::getTemplatePath() . $sTpl);

		
		return $sCode;
	}

	/**
	 * generiert einen hash der den Eintrag bzw. die "Art" des Elementes wiederspiegelt
	 * @return string
	 */
	protected function _generateCacheKey(){

		$sCacheKey = 'element_'.$this->id;

		if($sCacheKey <= 0){
			$sCacheKey = $this->generateDesignerID();
		}

		return $sCacheKey;
	}
	
	/**
	 * Generiert das HTML des ELEMENTES für den DESIGNER
	 * @param type $sParentHash
	 * @return type 
	 */
	public function generateDesignerHtml($sParentHash = ''){

		// Smarty starten
		$oSmarty = new SmartyWrapper();

		// Titel suchen
		$sTitle = $this->searchDesignerName();

		if($this->type == 'content'){

			$sTitle = $this->getName();

			// Hash Merken fär die Kinder
			if(
				empty($sParentHash) &&
				$this->special_type != ""
			){
				$sParentHash = $this->generateDesignerID();
			}

		}

		// factory holen
		$oFactory = $_SESSION['Gui2Designer']['factory'];

		$sUnknownTitel = "";
		if(empty($sTitle)){
			$sUnknownTitel = L10N::t('Unbekanntes Element', $oFactory->sL10NPath);
		}

		// Variablen zuweisen
		$oSmarty->assign('sRemoveIconPath', Ext_TC_Util::getIcon('delete'));
		$oSmarty->assign('sEditIconPath', Ext_TC_Util::getIcon('edit'));
		$oSmarty->assign('sAddIconPath', Ext_TC_Util::getIcon('add'));
		$oSmarty->assign('sCalendarIconPath', Ext_TC_Util::getIcon('calendar'));
		$oSmarty->assign('sTitel', $sTitle);
		$oSmarty->assign('sUnknownTitel', $sUnknownTitel);
		$oSmarty->assign('sType', $this->type);
		$oSmarty->assign('iElement', $this->id);
		$oSmarty->assign('oElement', $this);
		$oSmarty->assign('sParentHash', $sParentHash);

		if($this->type != 'content'){
			$oSmarty->assign('sLabel', $this->getName());
			$oSmarty->assign('sContent', $this->generateDesignerContentHtml());
		} else {
			// Rendern
			$oSmarty->assign('iColumn', $this->column_count);
			$oSmarty->assign('sContent', $this->generateDesignerContentHtml(1, $sParentHash));
			$oSmarty->assign('sContent2', $this->generateDesignerContentHtml(2, $sParentHash));
		}

		$bDoubleCheck = false;
		// voräbergehend deaktiviert.
		// da bei ableitungen die Design pro bereich nicht eindeutig sein kännen ( z.b agency -> offices )
		// klappt das hier nicht wirklich, wir mässen uns hier erst was anderes äberlegen
		//$bDoubleCheck = $this->checkForDoubleEntry();

		$sHint = "";

		if(empty($sTitle)){
			$sHint = $oFactory->getUnknownElementHint();
			$sHint .= '<br/>';
		} else if($bDoubleCheck){
			$sHint = $oFactory->getDoubleElementHint();
			$sHint .= '<br/>';
		}

		$oSmarty->assign('sHint', $sHint);

		// Rendern
		$sCode = $oSmarty->fetch($oFactory->getTemplatePath() . 'tab/element.tpl');

		return $sCode;
	}
	
	/**
	 * Läscht auch die Kinder
	 * @return type 
	 */
	public function delete() {
		$bSuccess = parent::delete();
		
		$aChilds = $this->getChildElements();
		
		foreach((array)$aChilds as $oChild){
			$oChild->delete();
		}
		
		return $bSuccess;
	}
	
	/**
	 * Generiert den HTML für den GUI DIALOG
	 * @param Ext_Gui2_Dialog $oDialog
	 * @param Ext_Gui2 $oGui
	 * @param WDBasic $oWDBasic
	 * @param int $iLoopId
	 * @param bool $bSpecialName
	 * @return Ext_Gui2_Html_Div 
	 */
	public function generateGuiDialogElement(&$oDialog, &$oGui, \WDBasic $oWDBasic, $iLoopId = 0, $bSpecialName = false){

		$this->_sDesignerSection = $oGui->sDesignerSection;

		if(!is_null($this->entityCallback) && is_callable($this->entityCallback)) {
			($this->entityCallback)($this, $oWDBasic);
		}

		// Standardmässig n leeres Div
		$oRow = new Ext_Gui2_Html_Div();
		
		$aInputOptions = array();
		$sType = $this->type;

		switch($this->type){
			case 'select':

				//
				// SELECT OPTION
				//
				$aSelectOptions = array();
				if($this->special_type == "") {
					$aSOptions = $this->getJoinedObjectChilds('select_options');
					foreach ((array)$aSOptions as $oOption) {
						$aSelectOptions[$oOption->id] = $oOption->getName();
					}
				} else if (!empty($this->select_options)) {
					$aSelectOptions = $this->select_options;
				} else {
					$mSelection = $this->selection;

					if($mSelection){
						if(!is_object($mSelection)){
							$mSelection = new $mSelection();
						}
						$aSelectOptions = $mSelection->getOptions($iLoopId, array('db_alias' => $this->db_alias, 'db_column' =>  $this->db_column), $oWDBasic);
					}
				}
				$aInputOptions['select_options'] = $aSelectOptions;
				$mSelection = $this->selection;
				if(!empty($mSelection)){
					$aInputOptions['selection'] = $this->selection;
				}
				if($this->multiple == 1){
					$aInputOptions['multiple'] = 5;
					$aInputOptions['jquery_multiple'] = 1;
					$aInputOptions['searchable'] = 1;
					$aInputOptions['style'] = 'height: 105px;';
				}

				//
			case 'input':
			case 'date':
				$aInputOptions['type'] = 'text';
				if($sType == 'date'){
					$sType = 'calendar';
					$aInputOptions['display_age'] = $this->display_age;
				}
			case 'textarea':
			case 'checkbox':
			case 'html':
			case 'upload':
				//
				// INPUT OPTIONS
				//		
				$aInputOptions['db_column'] = 'DESIGN_ELEMENT';
				$aInputOptions['db_alias']	= $this->id;
				$aInputOptions['required']	= 0;

				if(
					$this->special_type != ""
				){
					$oElement = $this->searchDesignerElement();
					
					$aInputOptions['db_column'] = $oElement->db_column;
					$aInputOptions['db_alias']	= $oElement->db_alias;

					if($this->format != ""){
						$sFormatClass = $oElement->format;
						if(is_object($sFormatClass)){
							$aInputOptions['format']	= $sFormatClass;
						} else {
							$aInputOptions['format']	= new $sFormatClass();
						}
					}
				}
				
				// Wenn in einem Loop
				if(
					$bSpecialName
				){
					$sAlias = $aInputOptions['db_alias'];
					// Alias ergänzen
					if(!empty($aInputOptions['db_alias'])){
						$aInputOptions['db_alias'] = $aInputOptions['db_alias'].']['.(int)$iLoopId;
					} else {
						$aInputOptions['db_alias'] = 'dummyalias]['.(int)$iLoopId;
					}
                    
					$aInputOptions['no_savedata'] = true;
					// Eltern element suchen um die Klasse zu bekommen
					$oParent = $this->searchSpecialParentElement();
					if($oParent){
						// Wenn ein Object definiert wurde merke dir die Save Daten 
						// damit wir später saven + auslesen kännen
						$oDependence = $oParent->special_dependence;
						if($oDependence instanceof Ext_TC_Gui2_Designer_Config_Element_Dependence){
							$aSpecialSaveData = $oGui->getOption('special_save_data');
							$sElementHash = $this->generateDesignerID();
							$aSpecialSaveData[$oDependence->getOwnClass()][$iLoopId][$sElementHash] = $aInputOptions;
							$aSpecialSaveData[$oDependence->getOwnClass()][$iLoopId][$sElementHash]['db_alias_original'] = $sAlias;
							$aSpecialSaveData[$oDependence->getOwnClass()][$iLoopId][$sElementHash]['dependence'] = $oDependence;
							$oGui->setOption('special_save_data', $aSpecialSaveData);
						}
					}
					
				}

				// Wenn pflicht
				if($this->required){
					$aInputOptions['required'] = 1;
				}

				if($this->readonly){
					$aInputOptions['readonly'] = true;
					if($this->disabled === false) {
						$aInputOptions['disabled'] = false;
					}
				}
				
				$aInputOptions['class'] = 'txt';
				
				if($this->class != ""){
					$aInputOptions['class'] = $this->class;
				}
	
				//
               
                if($this->type != 'upload'){
                    $oRow = $oDialog->createRow($this->getName(), $sType, $aInputOptions);
                 // Upload nur ein hidden feld + div
                } else {
					
					$aSelectedIds = $oGui->getSelectedIds();					
					$iParent = (int) reset($aSelectedIds);
					
                    $sNamespace     = self::buildTCUploadNamespace($this->id, $iParent, $iLoopId);

                    $oRow = new Ext_Gui2_Html_Div();
                    $oLabel = new Ext_Gui2_Html_Label();
                    $oLabel->setElement($this->getName());
                    $oRow->setElement($oLabel);

                    $oHidden = $oDialog->createSaveField('hidden', $aInputOptions);
                    $oHidden->value = $sNamespace;
                    $oRow->setElement($oHidden);

                    $oDiv = new Ext_Gui2_Html_Div();
                    $oDiv->class = 'uploader';
                    $oDiv->setDataAttribute('namespace', $sNamespace);
                    $oDiv->setDataAttribute('id', $iLoopId);
					$oDiv->setDataAttribute('selected', $iParent);
                    $oRow->setElement($oDiv);
                }

				break;
			case 'headline':
				$oRow = new Ext_Gui2_Html_H3();
				$oRow->setElement($this->getName());
				break;
			case 'special':
				// Spezialfälle

				if(
					$this->special_type != ""
				) {
					// Angegebene Methode aufrufen um das Element zu erzeugen
					$oElement	= $this->searchDesignerElement();
					$mMethod	= $oElement->special_dialog_method;

					if(!empty($mMethod)){
						// aktuelle Loop id und Dialog äbergeben
						$oRow = call_user_func($mMethod, $iLoopId, $oDialog, $this->selected_id, $oGui, $mMethod[0], $this->readonly, $this);
					}
				}
				break;
			case 'flexibility':

				// Individuelle Felder
				if(
					$this->special_type > 0
				){

					$sFieldIdentifierPrefix = 'flex';
					$iSelectedId = $this->selected_id;

					if($bSpecialName === true) {
						$sFieldIdentifierPrefix = 'flex_childs['.$this->searchParentDependencyClass().']';
						$iSelectedId = $iLoopId;
					}

					// Angegebene Methode aufrufen um das Element zu erzeugen
					$oElement = $this->searchDesignerElement();

					if($oElement !== false) {
						$oRow = $oElement->getDialogRow($iLoopId, $oDialog, $iSelectedId, $oGui, $sFieldIdentifierPrefix, $this->readonly, $this->disabled);
					}

				}
				break;
			// Mehrspaltiger bereich
			case 'content':
			default:
				
				$aContentLoopIds	= array();
				$bSpecialContent	= false;
				$mMethod			= false;

				if($bSpecialName){
					$aContentLoopIds = array($iLoopId);
				} 

				// Wenn spezialfall, dann Loop Ids holen
				if(
					$this->special_type != ""
				){

					// Definieren das wir in einem Spezial Content sind
					$bSpecialContent = true;
					// Designer Element holen um auf default configs zu kommen
					$oDesignerElement = $this->searchDesignerElement();
		
					// Merken das man die Name Attribute umschreiben muss
					$bSpecialName = true;
					$bSkipCopyElement = false;

					$mMethod	= $oDesignerElement->special_dialog_method;

					$oDependence = $oDesignerElement->special_dependence;

					// IDs der Einträge ermitteln
					$aContentLoopIds = $oGui->getDesignerLoopIds($oDependence);

					// Wenn der bereich sich wiederholen soll
					if($oDesignerElement->special_repeat){
						// Wenn Repeat an den anfang einen (-1) Eintrag der zum koppieren benutzt wird
						array_unshift($aContentLoopIds, -1);
					} else if($oDependence) {
						// wenn es sich nicht wiederholen soll muss trozdem das array mit einem eintrag gefüllt werden
						// ansonsten läuft die schleife nicht durch
						if(empty($aContentLoopIds)){
							$aContentLoopIds = array(0);
						}
						// -1 daten sowie add button übersprinbgen
						$bSkipCopyElement = true;
					} else {
						// Merken das man die Name Attribute nicht umschreiben muss
						$bSpecialName = false;
						// wenn es sich nicht wiederholen soll muss trozdem das array mit einem eintrag gefüllt werden
						// ansonsten läuft die schleife nicht durch
						$aContentLoopIds = array(0);
					}
		
				}
				
				// Wenn leer muss mind 1 eintrag ergänzt werden wegen der schleife
				if(empty($aContentLoopIds)){
					$aContentLoopIds = array(0);
				}

				$oRow = new Ext_Gui2_Html_Div();
				// Sicherstellen das Keys num. sind
				$aContentLoopIds = array_values($aContentLoopIds);

				// Loop IDs durchlaufen
				foreach((array)$aContentLoopIds as $i => $iLoopId) {
					
					$sStyle = "";
					$sClass = 'bootstrap-row';
					$sId	= "";
					// Wenn spezialcontent
					if($bSpecialContent){
						// Classe setzten
						$sClass = 'designDiv';
						// Wenn Name der bereich sich wiederholen wird
						// und es der erste durchlauf ist
						if(
							$bSpecialName &&
							$i == 0 &&
							!$bSkipCopyElement
						){
							// Klasse setzten
							$sClass = 'designDiv copyDesignDiv '.$this->special_type;
							$sId = 'design_div_'.$this->special_type.'_-1';
							// Hidden
							$sStyle = "display:none;";
						} else if($bSpecialName){
							$sClass = 'designDiv '.$this->special_type;
							$sId = 'design_div_'.$this->special_type.'_'.$iLoopId;
						}
						
					}

					$oRowDiv = new Ext_Gui2_Html_Div();
					$oRowDiv->class = $sClass;
					$oRowDiv->style = $sStyle;
					$oRowDiv->id = $sId;

					// Wenn mehr als 1 Spalte
					if($this->column_count >= 2) {

						for($iColumn = 1; $iColumn <= $this->column_count; ++$iColumn) {

							$oColumn = new Ext_Gui2_Html_Div();
							$oColumn->class = "GUIDialogMultiColumn col-md-6";

							$aColumnElements = (array) $this->getChildsForColumn($iColumn);

							foreach((array)$aColumnElements as $oColumnElement){
								/* @var Ext_TC_Gui2_Design_Tab_Element $oColumnElement */
								$oColumnElement->selected_id = $this->selected_id;
								$oColumnElement->readonly = $this->readonly;
								$oColumnElement->disabled = $this->disabled;
								$oColumnRow = $oColumnElement->generateGuiDialogElement($oDialog, $oGui, $oWDBasic, $iLoopId, $bSpecialName);
								$oColumn->setElement($oColumnRow);
							}

							$oRowDiv->setElement($oColumn);
						}

					} else {
						// Einspaltig
						$aElements = $this->getChildElements();
						// Rechte Elemente erzeugen
						foreach((array)$aElements as $oElement){
							/* @var Ext_TC_Gui2_Design_Tab_Element $oElement */
							$oElement->selected_id = $this->selected_id;
							$oElement->readonly = $this->readonly;
							$oElement->disabled = $this->disabled;
							$oCurrentRow = $oElement->generateGuiDialogElement($oDialog, $oGui, $oWDBasic, $iLoopId, $bSpecialName);
							$oRowDiv->setElement($oCurrentRow);
						}						
					}

					if(!empty($mMethod)){
						// aktuelle Loop id und Dialog übergeben
						$oRowAddon = call_user_func($mMethod, $iLoopId, $oDialog, $this->selected_id, $oGui, $mMethod[0], false, $this);
						$oRowDiv->setElement($oRowAddon);
					}

					$oCleaner = new Ext_Gui2_Html_Div();
					$oCleaner->class = 'divCleaner';
					$oRowDiv->setElement($oCleaner);

					// DELETE Button
					// wenn spezial content und wiederholungen
					if(
						$bSpecialContent &&
						$bSpecialName &&
						!$bSkipCopyElement
					){
						
						$oDeleteButton = new Ext_Gui2_Html_Button();
						$oDeleteButton->type = 'button';
						if($this->readonly) {
							$oDeleteButton->class = 'btn btn-sm btn-default guiBarInactive removeDesignButton remove_'.$this->special_type;
						} else {
							$oDeleteButton->class = 'btn btn-sm btn-default removeDesignButton remove_'.$this->special_type;
						}
						$oDeleteButton->id = 'remove_'.$this->special_type.'_'.$iLoopId;
						$oIcon = new \Ext_Gui2_Html_I();
						$oIcon->class = 'fa '.Ext_TA_Util::getIcon('delete');
						$oDeleteButton->setElement($oIcon);
						$oDeleteButton->setElement(' '.L10N::t('Eintrag löschen'));
						$oRowDiv->setElement($oDeleteButton);

					}

					$oRow->setElement($oRowDiv);
					
					// ADD Button
					// wenn spezial content und wiederholungen
					// und wenn letzter durchlauf
					if(
						($i + 1) == count($aContentLoopIds) && 
						$bSpecialContent &&
						$bSpecialName &&
						!$bSkipCopyElement
					){

						$oAddButtonContainer = new Ext_Gui2_Html_Div();
						$oAddButtonContainer->class = 'add-btn-container form-inline form-group-sm';
						
						$oInput = new Ext_Gui2_Html_Input();
						$oInput->class = 'txt form-control input-sm addDesignInput';
						//$oInput->style = 'width:20px;';
						$oInput->value = '1';
						$oInput->id = 'add_'.$this->special_type.'_input';
						
						if($this->readonly) {
							$oInput->readonly = true;
							$oInput->class .= ' txt readonly';
						}
						
						$oAddButtonContainer->setElement($oInput);
						
						$oNewButton = new Ext_Gui2_Html_Button();
						$oNewButton->type = 'button';
						if($this->readonly) {
							$oNewButton->class = 'btn btn-sm btn-primary guiBarInactive addDesignButton add_'.$this->special_type;
						} else {
							$oNewButton->class = 'btn btn-sm btn-primary addDesignButton add_'.$this->special_type;
						}
						if ($this->isRequired()) {
							$oNewButton->class .= ' required';
							$oNewButton->setDataAttribute('container-name', $this->getName());
						}

						$oNewButton->id = 'add_'.$this->special_type.'_btn';
						$oIcon = new \Ext_Gui2_Html_I();
						$oIcon->class = 'fa '.Ext_TA_Util::getIcon('add');
						$oNewButton->setElement($oIcon);
						$oNewButton->setElement(' '.L10N::t('Eintrag hinzufügen'));
						$oAddButtonContainer->setElement($oNewButton);
						
						$oRow->setElement($oAddButtonContainer);
												
						$oCleaner = new Ext_Gui2_Html_Div();
						$oCleaner->class = 'divCleaner';
						$oRow->setElement($oCleaner);
					}
				}

				break;
		}

		if ($oRow instanceof Ext_Gui2_Html_Abstract) {
			$oRow->class .= ' box_container gui-design-element';
			$oRow->setDataAttribute('element', $this->getId());
		}
		
		return $oRow;
	}
    
    public static function buildTCUploadNamespace($iDesignElementId, $iParent, $iSubElement){
        $sNamespace     = $iDesignElementId;
		if($iParent){
            $sNamespace     .= '_'.$iParent;
        }
        if($iSubElement){
            $sNamespace     .= '_'.$iSubElement;
        }
        // md5 machen da die klassennamen ggf sehr lang sind
        $sNamespace     = 'gui_designer_'.$sNamespace;
        return $sNamespace;
    }
	
	/**
	 * Check if the Element has defined Allowed Child Elements
	 * @return boolean 
	 */
	public function checkForAssignedAllowedElements(){
		
		$sDesignerClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Designer');
		$oDesigner = new $sDesignerClass(0);
		if(!empty($this->_sDesignerSection)){
			$oDesigner->setSection($this->_sDesignerSection);
		}
		
		$aElements = $oDesigner->getFixElements(false);
		
		$sHash = $this->generateDesignerID();
		
		foreach((array)$aElements as $oElement){
			
			if(
				$oElement->allowed_parent !== null &&
				$oElement->allowed_parent === $sHash
			){
				return true; 
			}
		}
	
		return false;
	}
	
	/**
	 * return the parent element
	 * @return self 
	 */
	public function getParent(){
		
		$iParent = (int)$this->parent_element_id;
		if($iParent <= 0){
			return false;
		}
		
		$oParent = Ext_TC_Gui2_Design_Tab_Element::getInstance($iParent);
		
		return $oParent;
	}
	
	/**
	 * return the first "special" parent element 
	 * @return self
	 */
	public function searchSpecialParentElement(){
		
		$oParent = $this;
		
		do {
			$oParent = $oParent->getParent();
		} while ($oParent && $oParent->special_type == "");
		
		return $oParent;
		
	}
	
	/**
	 * search the correct Allowed Parent Element for the current Element
	 * @return type 
	 */
	public function searchAllowedParentElement(){
		
		$sDesignerClass = Ext_TC_Factory::getClassName('Ext_TC_Gui2_Designer');
		$oDesigner = new $sDesignerClass(0);

		if(!empty($this->_sDesignerSection)){
			$oDesigner->setSection($this->_sDesignerSection);
		}

		$oElement = $oDesigner->findElementWithHash($this->allowed_parent);
		
		if($oElement){
			// Section muss immer übergeben werden!
			$oElement->designer_section = $this->_sDesignerSection;
			return $oElement;
		}
		
		return false;
	}
	
	/**
	 * Dependency-Klasse des Eltern-Elementes
	 * @return string
	 */
	public function searchParentDependencyClass() {

		$oParent = $this->searchSpecialParentElement();
		$sClass = '';

        if($oParent) {

            $oElement = $oParent->searchDesignerElement();
            $oDependency = $oElement->special_dependence;

            if(
                $oDependency != '' &&
                $oDependency instanceof Ext_TC_Gui2_Designer_Config_Element_Dependence
            ) {
                $sClass = $oDependency->getOwnClass();
            }

        }
		
		return $sClass;
	}	
	
	/**
	 * get Flag src for the Current Element
	 * @return type 
	 */
	public function getSelfFlagSrc(){
		
		$aFlags = array();
		$aFlags[1] = 'bullet_black';
		$aFlags[2] = 'bullet_blue';
		$aFlags[3] = 'bullet_orange';
		$aFlags[4] = 'bullet_pink';
		$aFlags[5] = 'bullet_purple';
		$aFlags[6] = 'bullet_red';
	
		if(
			$this->type == 'content' &&
			$this->checkForAssignedAllowedElements()
		){
			
			$sHash = $this->generateDesignerID();
			
			if(self::$self_flag[$sHash]  > 0){
				$iFlag = self::$self_flag[$sHash];
			} else {
				$iFlag = self::$self_flag_count;
				self::$self_flag[$sHash] = $iFlag;
				self::$self_flag_count++;
				if(self::$self_flag_count >= 6){
					self::$self_flag_count = 1;
				}
			}
			
			$sImg = Ext_TC_Util::getIcon($aFlags[$iFlag]);
			
			return $sImg;
		}
		
		return '';
	}
	
	/**
	 * get the Flag Src of the nedded Parent Element
	 * @return type 
	 */
	public function getParentFlagSrc(){
		
		if(
			$this->allowed_parent !== null
		){
			$oElement = $this->searchAllowedParentElement();
			if($oElement){
				$sImg = $oElement->getSelfFlagSrc();
				return $sImg;
			}
		}
		
		return '';
		
	}

	public function save($bLog = true) {

		if (empty($this->db_column)) {
			$this->mergeDesignerElementValues();
		}

		$bNew = !$this->exist();

		$mReturn = parent::save($bLog);

		if ($this->exist() && $this->isActive()) {
			if ($bNew) {
				if (\DB::getLastTransactionPoint() === 'copy_design_dialog') {
					$this->trigger(self::EVENT_CLONED);
				} else {
					$this->trigger(self::EVENT_ADDED);
				}
			} else  {
				$this->trigger(self::EVENT_EDITED);
			}
		}

		//WDCache::delete('Ext_TC_Gui2_Design_Tab_Element::getValue');
		//WDCache::delete('Ext_TC_Gui2_Design_Tab_Element::checkParent');
		//WDCache::delete('Ext_TC_Gui2_Design_Tab_Element::checkForDoubleEntry');
		//WDCache::delete('Ext_TC_Gui2_Design_Tab_Element::generateDesignerContentHtml');
		//WDCache::delete('Ext_TC_Gui2_Design_Tab::getMainElements');
		//WDCache::delete('Ext_TC_Gui2_Design_Tab_Element::generateDesignerHtml');
		//WDCache::delete('Ext_TC_Gui2_Design_Tab_Element::searchDesignerElement');

		return $mReturn;
	}	
		
	/**
	 * Felder, bei den Platzhalter eingetragen werden können
	 * @return array
	 */
	public static function getPlaceholderDependencyVisibility() {
		$oDataClass = new Ext_TC_Gui2_Designer_Data();
		$aDynamicFields = $oDataClass->getDynamicTabElements();
		
		$aWhiteList = array('input', 'textarea', 'html', 'checkbox', 'date', 'select');
		
		$aReturn = array();
		foreach($aDynamicFields as $oField) {
			if(in_array($oField->type, $aWhiteList)) {
				$aReturn[] = $oField->generateDesignerID(true);
			}
		}		
		
		return $aReturn;
	}

	public function createCopy($sForeignIdField = null, $iForeignId = null, $aOptions = array()) {

		$oClone = parent::createCopy($sForeignIdField, $iForeignId, $aOptions);

		if ($oClone->exist()) {

			$aChildElements = $this->getChildElements();

			foreach ($aChildElements as $oElement) {
				// tab_id muss hier übergeben werden da diese auch für die weiteren Childs da sein muss. Die paren_element_id
				// kann anschließend gesetzt werden
				$oElementClone = $oElement->createCopy('tab_id', $oClone->tab_id, $aOptions);
				$oElementClone->parent_element_id = $oClone->getId();
				$oElementClone->save();
			}

		}

		return $oClone;
	}

	/**
	 * Events
	 * TODO das läuft noch nicht
	 */

	public function getEventListeners(): array {
		return $this->eventListeners;
	}

	public function on(string $eventName, \Closure $callback) {
		$this->eventListeners[$eventName][] = $callback;
		return $this;
	}

	public function trigger(string $eventName) {

		if (!isset($this->eventListeners[$eventName])) {
			return;
		}

		foreach ($this->eventListeners[$eventName] as $callback) {
			$callback($this);
		}

		return true;
	}

}
