<?php

class Ext_TC_Gui2 extends Ext_Gui2 {

	protected $_iDesign = 0;
	protected $_aSelectedIds = array();
	protected $_oDefaultColumn = null;
	protected $sDesignerSection = '';
	protected $aFlexJoins = [];

	public function __construct($sHash='', $sDataClass = 'Ext_TC_Gui2_Data', $sViewClass = '', $sInstanceHash = null) {
		
		if(empty($sDataClass)){
			$sDataClass = 'Ext_TC_Gui2_Data';
		}
		parent::__construct($sHash, $sDataClass, $sViewClass, $sInstanceHash);
		
		// Bei Aufruf in HTML-Dateien Titel und Pfad direkt setzen
		if(
			isset($_SERVER['REQUEST_URI']) &&
			(
				strpos($_SERVER['REQUEST_URI'], '.html') !== false ||
				strpos($_SERVER['REQUEST_URI'], '/page') !== false
			)
		) {
			$this->gui_description	= Ext_TC_Factory::executeStatic('Ext_TC_System_Navigation', 'tp');
			$this->gui_title		= Ext_TC_Factory::executeStatic('Ext_TC_System_Navigation', 't');
		}

		// Standard-Datumsformat
		$this->calendar_format		= Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date');
		
		$this->addJs('gui2/gui2.js', 'tc');
		$this->addCss('gui2/gui2.css', 'tc');
		// HTML5 Uploader
		$this->addJs('uploader/js/uploader.js', 'tc');
		$this->addJs('uploader/js/helper.js', 'tc');
		$this->addCss('uploader/css/uploader.css', 'tc');
	}

	public function getLanguageObject(): \Tc\Service\LanguageAbstract {
		return (new \Tc\Service\Language\Backend(\System::getInterfaceLanguage()))
			->setContext($this->gui_description);
	}

	public function setDesignSection($sSection){
				
		$this->sDesignerSection = $sSection;
		
		$iDesign = $this->searchDesignId($sSection);
		
		if($iDesign <= 0){
			die($this->t('Es wurde kein passendes GUI Layout gefunden.'));
		}
		
		$this->_iDesign = $iDesign;
		
		$this->setOption('design', true);
	}
	
	/**
	 * Array List with IDs for Loops
	 * @param Ext_TC_Gui2_Designer_Config_Element_Dependence $oDependence
	 * @return array 
	 */
	public function getDesignerLoopIds($oDependence){

		$aList = [];

		if($oDependence){

			$sType = $oDependence->getDependenceType();

			$this->_aSelectedIds = (array)$this->_aSelectedIds;
			$iSelectedId = (int)reset($this->_aSelectedIds);

			$oWDBasic = $this->getWDBasic($iSelectedId);

			if ($sType == 'jointable') {

				if (!empty($aMethodCall = $oDependence->getDependenceMethodCall())) {
					$aJoinTableObjects = call_user_func_array([$oWDBasic, $aMethodCall[0]], [$aMethodCall[1]]);
				} else {
					$sKey = $oDependence->getDependenceKey();
					$aJoinTableObjects = $oWDBasic->getJoinTableObjects($sKey);
				}

				foreach((array)$aJoinTableObjects as $oObject){
					$aList[] = $oObject->getId();
				}

			} else if ($sType == 'child') {

				$this->_aSelectedIds = (array)$this->_aSelectedIds;
				$iSelectedId = reset($this->_aSelectedIds);
				$oWDBasic = $this->getWDBasic($iSelectedId);

				if (!empty($aMethodCall = $oDependence->getDependenceMethodCall())) {
					$aJoinedObjectChilds = call_user_func_array([$oWDBasic, $aMethodCall[0]], [$aMethodCall[1]]);
				} else {
					$sKey = $oDependence->getDependenceKey();
					$aJoinedObjectChilds = $oWDBasic->getJoinedObjectChilds($sKey);
				}

				foreach((array)$aJoinedObjectChilds as $oObject){
					$aList[] = $oObject->getId();
				}

			}
		}

		return $aList;
	}

	
	public function getDesignDialog($aSelectedIds=[], $aAdditionalDialogPKs=[]) {
		
		$aSelectedIds = (array)$aSelectedIds;
		$iSelectedId = reset($aSelectedIds);

		$this->_aSelectedIds = $aSelectedIds;

		//$oDialog = Ext_TC_Gui2_Designer_Session::load($this->_iDesign, Ext_TC_System::getInterfaceLanguage(), $aSelectedIds, $aAdditionalDialogPKs);

		//if($oDialog === false){

			$oWDBasic = $this->getWDBasic($iSelectedId);

			$oDesign = Ext_TC_Gui2_Design::getInstance($this->_iDesign);
			$this->sDesignerSection = $oDesign->section;
			$oDialog = $this->createDialog($oDesign->getDialogTitle(), $oDesign->getDialogTitle(true));

			$this->setOption('special_save_data', []);

			$aTabs = $this->getDesignTabs($oDesign);

			foreach((array)$aTabs as $oTab){
				/* @var Ext_TC_Gui2_Design_Tab $oTab */

				$oDialogTab = $oDialog->createTab($oTab->getName());

				$aElements = $oTab->getMainElements();

				foreach((array)$aElements as $oElement){
					/* @var Ext_TC_Gui2_Design_Tab_Element $oElement */
					$oElement->selected_id = $iSelectedId;
					$oDialogElement = $oElement->generateGuiDialogElement($oDialog, $this, $oWDBasic);
					$oDialogTab->setElement($oDialogElement);
				}

				$oDialog->setElement($oDialogTab);
			}

		$oDialog->setOption('additional_dialog_keys', $aAdditionalDialogPKs);

		return $oDialog;
	}

	/**
	 * @param Ext_TC_Gui2_Design $oDesign
	 * @return Ext_TC_Gui2_Design_Tab[]
	 */
	protected function getDesignTabs(Ext_TC_Gui2_Design $oDesign) {
		$aTabs = $oDesign->getJoinedObjectChilds('tabs');
		return $aTabs;
	}

	/**
	 * generate all Filter Bars
	 * @param $sApplication
	 * @param string $sSet
	 * @param Ext_Gui2_Config_Parser|null $oParser
	 */
	public function setDesignFilterBar($sApplication, $sSet = '', Ext_Gui2_Config_Parser $oParser = null) {

        $oFilterset = Ext_TC_Gui2_Filterset::search($sApplication);
        $aBars = $oFilterset->getBars();

        foreach($aBars as $oBar) {
			
            $aUserGroups = (array)$oBar->usergroups;			
			$bAccess = $this->_checkDesignFilterBarAccess($aUserGroups);
			
            if($bAccess) {
                $aElements = $oBar->getElements();
                if(!empty($aElements)) {
                    $oBar = $this->createBar();
					$oBar->width = '100%';
					
					$iCountSuccess = 0;
					
					foreach($aElements as $oElement) {

						// Parser setzen, damit nicht jedes einzelne Element den Parser selbst neu holt
						if(
							$oParser !== null &&
							$oFilterset->application === $oParser->getName(false) // Parser kann auch ein anderer sein (Selection ruft Gui2_Factory auf…)
						) {
							$oElement->setParser($oParser);
						}

						$bSuccess = false;
						if($oElement) {
							$bSuccess = $oElement->setGuiFilterElement($oBar, $sSet);
						}
						
						$iCountSuccess += (int)$bSuccess;
                    }
					
					if($iCountSuccess > 0) {
						// Wenn wegen Set Überprüfung kein einziges Element gesetzt werden konnte, auch nicht die Bar einfügen
                    	$this->setBar($oBar);
                	}
            	}
        	}

        }
   
	}
	
	/**
	 * prüft, ob Filtersets gesetzt werden sollen
	 * 
	 * @param array $aUserGroups
	 * @return boolean
	 */
	protected function _checkDesignFilterBarAccess(array $aUserGroups) {
		$bAccess = Ext_TC_User::hasGroup($aUserGroups);
		return $bAccess;
	}
	
	public function searchDesignId($sSection){
		$iDesign = Ext_TC_Gui2_Design::searchBySection($sSection);
		return $iDesign;
	}
	
	
	/**
	 * @param string $sTitle
	 * @param string $sTitleNew
	 * @return Ext_Gui2_Dialog 
	 */
	public function createDialog($sTitle='', $sTitleNew='', $sTitleMultiple = '') {
		$oDialog = parent::createDialog($sTitle, $sTitleNew, $sTitleMultiple);
		return $oDialog;
	}

	public function addInterfaceLanguageColumn($sColumn, $sJoinAlias, $sTitel, $bResize=false) {
		
		$aLanguages = Ext_TC_Factory::executeStatic('Ext_TC_Util', 'getTranslationLanguages');
		
		$sInterfaceLanguage = Ext_TC_System::getInterfaceLanguage();
		
		$aNameLang = array();
		foreach($aLanguages as $aLang){
			if(substr($aLang['iso'], 0, 2) == $sInterfaceLanguage) {
				$aNameLang[] = $aLang;
				break;
			}
		}

		return $this->addLanguageColumns($aNameLang, $sColumn, $sJoinAlias, $sTitel, $bResize);
	}

	public function addLanguageColumns($aLanguages, $sColumn, $sJoinAlias, $sTitel, $bResize=false) {

		foreach((array)$aLanguages as $aLanguage) {

			$oColumn				= $this->createColumn();
			$oColumn->db_column		= $sColumn.'_'.$aLanguage['iso'];
			$oColumn->select_column	= $sJoinAlias;
			$oColumn->db_alias		= $aLanguage['iso'];
			$oColumn->title			= '<img src="'.Ext_TC_Util::getFlagIcon($aLanguage['iso']).'" /> '.$sTitel;
			$oColumn->mouseover_title = $sTitel.' – '.$aLanguage['name'];
			$oColumn->width			= Ext_TC_Util::getTableColumnWidth('name');
			$oColumn->width_resize	= $bResize;
			$oColumn->format		= new Ext_TC_Gui2_Format_Column_Language($aLanguage, $sColumn);
			$oColumn->sortable		= 0; // Da Joins fehlen, würde dies eine Exception werfen
			$oColumn->i18n = [ // Daten analog zu YML-Configs / Parser
				'jointable' => $sJoinAlias,
				'pass_language' => false, // Irrelevant
				'all' => true, // Müsste man eigentlich abgleichen, sollte aber nicht so wichtig sein
				'show_flag' => true,
				'original_title' => $sTitel, // Für Export
				'language' => $aLanguage['iso'] // Kommt bei YML-Config vom Parser
			];
			$this->setColumn($oColumn);
		}

	}

	/**
	 * name_en, name_de, etc.
	 *
	 * @param array $aLanguages
	 * @param string $sColumn
	 * @param string $sTitle
	 * @param bool $bOnlySystemLanguage
	 * @throws Exception
	 */
	public function addStaticLanguageColumns($aLanguages, $sColumn, $sTitle, $bResize = false, $bOnlySystemLanguage = true) {

		$aLanguages = collect($aLanguages);

		if (
			$bOnlySystemLanguage &&
			$aLanguages->pluck('iso')->contains(System::getInterfaceLanguage())
		) {
			$aLanguages = $aLanguages->where('iso', System::getInterfaceLanguage());
		}

		foreach ($aLanguages as $aLanguage) {
			$oColumn = $this->createColumn();
			$oColumn->db_column = $sColumn.'_'.$aLanguage['iso'];
			$oColumn->title = '<img src="'.Ext_TC_Util::getFlagIcon($aLanguage['iso']).'" /> '.$sTitle;
			$oColumn->mouseover_title = $sTitle.' – '.$aLanguage['name'];
			$oColumn->width = Ext_TC_Util::getTableColumnWidth('name');
			$oColumn->width_resize = $bResize;
			$this->setColumn($oColumn);
		}

	}

	/*
	 * Fügt die GUI Standard-Spalten Hinzu
	 */
	public function addDefaultColumns($mFormat = null, $mWidth = null) {

		$oDefaultColumn = $this->getDefaultColumn();
                
		if(!empty($mFormat)) {
			$oDefaultColumn->changeDefaultConfig('created', array(
				'format' => $mFormat,
				'width' => $mWidth
			));
			$oDefaultColumn->changeDefaultConfig('changed', array(
				'format' => $mFormat,
				'width' => $mWidth
			));
		}

		$aColumns = (array)$oDefaultColumn->getColumns();

		foreach($aColumns as $oColumn) {
			$this->setDefaultColumnGroup($oColumn);
			$this->setColumn($oColumn);
		}

	}

	/**
	 * Objekt um die getDefaultColumns zu konfigurieren
	 * @return Ext_TC_Gui2_DefaultColumn
	 */
	public function getDefaultColumn()
	{
		if(
			is_object($this->_oDefaultColumn) &&
			$this->_oDefaultColumn instanceof Ext_TC_Gui2_DefaultColumn
		){
			$oDefaultColumn = $this->_oDefaultColumn;
		}else{
			$oDefaultColumn = new Ext_TC_Gui2_DefaultColumn();
		}

		return $oDefaultColumn;
	}

	public function setDefaultColumn(Ext_TC_Gui2_DefaultColumn $oDefaultColumn)
	{
		$this->_oDefaultColumn = $oDefaultColumn;
	}

	/**
	 * @param Ext_Gui2_Head $oColumn
	 */
	protected function setDefaultColumnGroup(Ext_Gui2_Head $oColumn) {

		if(!$this->getOption('gui_has_column_group')) {
			return;
		}

		$oColFlexGroup = $this->createColumnGroup();
		$oColFlexGroup->title = L10N::t('Status', Ext_Gui2::$sAllGuiListL10N);
		$oColumn->group = $oColFlexGroup;

	}
	
	public function addValidUntilColumn($sDbAlias='')
	{
		$oDefaultColumn = $this->getDefaultColumn();
		$oDefaultColumn->addValidUntilColumn($sDbAlias);
		$this->setDefaultColumn($oDefaultColumn);
	}

	public function display($aOptionalData = array(), $bNoJavaScript=false)
	{
		
		if(
			!empty($aOptionalData['js']) &&
			!is_array($aOptionalData['js'])
		) {
			$aOptionalData['js'] = array();
		}
		
		if(
			!empty($aOptionalData['css']) &&
			!is_array($aOptionalData['css'])
		) {
			$aOptionalData['css'] = array();
		}

		if($this->class_js == 'ATG2'){
			$this->class_js = 'CoreGUI';
		}

		parent::display($aOptionalData, $bNoJavaScript);
		
	}

	public function addCss($sCss, $sExt = 'thebing') {
		if ($sExt !== null) {
			$sFile = '/admin/extensions';
			if(!empty($sExt)) {
				$sFile .= '/'.$sExt;
			}
			$this->_aCssFiles[] = $sFile.'/'.$sCss;
		} else {
			$this->_aCssFiles[] = $sCss;
		}
	}

	public function addJs($sJs, $sExt = 'thebing') {
		if ($sExt !== null) {
			$sFile = '/admin/extensions';
			if (!empty($sExt)) {
				$sFile .= '/' . $sExt;
			}

			$this->_aJsFiles[] = $sFile . '/' . $sJs;
		} else {
			$this->_aJsFiles[] = $sJs;
		}
	}
	
	/**
	 * Ableitung der Bar für TC, damit auch diese genutzt wird.
	 * Sollte ein Typ angegeben werden, wird wieder die Barklasse
	 *  der GUI2 verwendet, wo dies dann geswiched wird.
	 * 
	 * @param string $sType
	 * @return Ext_TC_Gui2_Bar
	 */
	public function createBar($sType = '') {
		
		if(!empty($sType)) {
			return parent::createBar($sType);
		}
		
		return new Ext_TC_Gui2_Bar($this);
	}

	/**
	 *Ableitung für Ext_TC_Gui2 für Bar
	 * @param type $sHash
	 * @param type $sDataClass
	 * @param type $sViewClass
	 * @return Ext_Gui2
	 */
	public function createChildGui($sHash='', $sDataClass = '', $sViewClass = '') {

		$GLOBALS['gui2_instance_hash'] = $this->instance_hash;

		$sClass		= get_class($this);
		$oChildGui	= new $sClass($sHash, $sDataClass, $sViewClass);

		$oChildGui->parent_hash		= $this->hash;
		$oChildGui->parent_gui		= array($this->hash);
		$oChildGui->gui_description = $this->gui_description;

		return $oChildGui;

	}
	
	public function getSelectedIds() {
		return $this->_aSelectedIds;
	}

	/**
	 * Bei nicht Index Listen die Spalten Bearbeitet, Bearbeiter, Ersteller und Erstellt
	 * verändern , da diese Spalten ansonsten Fehler beim Sortieren hervorrufen.
	 * @param Ext_Gui2_Head $oColumn
	 */
	public function setColumn(Ext_Gui2_Head $oColumn) {
		
		$bIsIndex = $this->checkWDSearch();
		
		if($bIsIndex === false) {
			
			if(
				(
					$oColumn->db_column === 'created_original' ||
					$oColumn->db_column === 'changed_original'
				) && (
					$oColumn->select_column === 'created' ||
					$oColumn->select_column === 'changed'
				)
			) {
				$aColumn = explode('_', $oColumn->db_column);
				$sColumn = reset($aColumn);
				$oColumn->db_column = $sColumn;
			}
			
			if(
				(
					$oColumn->db_column === 'editor_id' ||
					$oColumn->db_column === 'creator_id'
				) && (
					$oColumn->select_column === 'editor_id' ||
					$oColumn->select_column === 'creator_id'
				)
			) {
				$oColumn->sortable = false;
			}
		}

		parent::setColumn($oColumn);

	}

	/**
	 * Dies war zuvor die fast komplett redundante tc_default.yml
	 *
	 * @inheritdoc
	 */
	public static function manipulateDefaultConfig(array &$aConfig) {

		$aConfig['class']['gui'] = 'Ext_TC_Gui2';
		$aConfig['class']['js'] = 'CoreGUI';

	}

	public function addFlexJoin($sFilterId, $iFlexFieldId, $sPrimaryKeyField, $sPrimaryKeyAlias=null) {
		
		$this->aFlexJoins[$sFilterId] = [
			'field_id'=>$iFlexFieldId,
			'primary_key'=>$sPrimaryKeyField
		];

		if($sPrimaryKeyAlias !== null) {
			$this->aFlexJoins[$sFilterId]['primary_key_alias'] = $sPrimaryKeyAlias;
		}
		
	}

	public function getFlexSections($bFilterBySet = false): array {

		$aSections = [];

		if(!empty($this->_aConfig['sSection'])) {
			$aSections = [[
				'section' => $this->_aConfig['sSection'],
				'primary_key' => 'id'
			]];
		}

		if(!empty($this->_aConfig['additional_sections'])) {
			$aSections = array_merge($aSections, $this->_aConfig['additional_sections']);
		}

		if ($bFilterBySet) {
			$aSections = array_filter($aSections, function (array $aSection) {
				return empty($aSection['set']) || in_array($this->set, $aSection['set']);
			});
		}

		return $aSections;
	}

	public function executeGuiCreatedHook() {

		parent::executeGuiCreatedHook();

		if (!$this->sidebar) {
			return;
		}

		$sections = array_column($this->getFlexSections(true), 'section');
		$fields = \Ext_TC_Flexibility::getSectionFieldData($sections, true, true);

		foreach ($fields as $field) {
			$this->setCustomFieldFilter($field);
		}

	}

	public function getDefaultError(): string{
		return $this->t('Es ist ein Fehler aufgetreten!');
	}

}
