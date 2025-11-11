<?php

class Ext_Gui2_Bar extends Ext_Gui2_Config_Basic {

	static public $iCount = 0;

	// Konfigurationswerte setzten
	protected $_aConfig = array(
		'id'		=> '',
		'width'		=> '100%',
		'height'	=> '',
		'position'	=> 'top',
		'visible'	=> true,
		'show_if_empty' => false,
		'access'    => '',
		'class'		=> '',
		'style'		=> '',		// z.B. für Ext_Gui2_Bar_Legend
		'number' => null,
		'data' => []
	);

	protected $_aElements = array();

	/**
	 * @var Ext_Gui2 
	 */
	protected $_oGui;

	public function __construct(Ext_Gui2 $oGui) {

		$this->_oGui = $oGui;
		
	}

	public function getGui() {
		return $this->_oGui;
	}
	
	/**
	 * @param array $aSelectedIds
	 * @param array $aRowData
	 * @param null $oGui
	 * @param null $aResultData
	 * @return array
	 */
	public function getRequestBarData($aSelectedIds = array(), $aRowData = array(), &$oGui = null, $aResultData=null){
		global $_VARS;
		
		$oData				= $oGui->_oData; /** @var Ext_Gui2_Data $oData */
		$oIconStatusActive	= $oGui->_oIconStatusActive;
		$oIconStatusVisible	= $oGui->_oIconStatusVisible;

		$aData = array();
		$aData['width'] = $this->width;
		$aData['height'] = $this->height;
		$aData['visible'] = $this->visible;
		$aData['class_name'] = $this->class;
		$aData['show_if_empty'] = $this->show_if_empty;
		$aIcons = $this->getElements();

		if(!empty($this->data)) {
			$aData['data'] = $this->data;
		}

		$aHookData = [
			'gui' => $oGui,
			'bar' => $this,
			'elements' => &$aIcons
		];

		// Hook hier ausführen, da $this->getElements() keine GUI hat
		System::wd()->executeHook('gui2_manipulate_bar_elements', $aHookData);

		$iBarItemLineCount = 0;
		$bRenumberElements = false;
		
		foreach($aIcons as $iIKey => $oElement){
			
			$aElements = $oElement->getElementData();

			// Setzt vor jedes Label einen Seperator, solange es nicht das erste Element ist
			if(
				$iBarItemLineCount > 0 && 
				$oElement instanceof Ext_Gui2_Bar_Labelgroup
			) {
				$oSeparator = $this->createSeperator();
				$aSeparator = $oSeparator->getElementData();
				$aElements['html'] = $aSeparator['html'].$aElements['html'];
			}

			// Wenn hide_empty, dann prüfen, ob die Labelgroup ausgeblendet werden muss
			if(
				$oElement instanceof Ext_Gui2_Bar_Labelgroup &&
				$oElement->hide_empty
			) {
				$oNextIcon = @$aIcons[$iIKey + 1];

				if(
					empty($oNextIcon) || (
						!empty($oNextIcon) &&
						$oNextIcon instanceof Ext_Gui2_Bar_Labelgroup
					)
				) {
					$bRenumberElements = true;
					continue;
				}
			}

			// Wenn eine Selection-Klasse angegeben wurde, werden die Select-Options aus
			// der Selektion-Klasse geholt
			if(
				$oElement->selection != '' &&
				is_object($oElement->selection) &&
				$oElement->selection instanceof Ext_Gui2_View_Selection_Filter_Abstract
			) {
				$aParentGuiIds = $oGui->getParentGuiIds();
				$aElements['select_options'] = (array)$oElement->selection->getOptions($aParentGuiIds, $oGui);
			}
			
			###########################################################
			// Da json probleme hat mit einem "leeren" key ersetzte ich ihn nur einen platzhalter
			###########################################################
			$aTemp = array();
			$iSelectKey = 0;
			
			if(!isset($aElements['select_options'])) {
				$aElements['select_options'] = [];
			}
			
			foreach((array)$aElements['select_options'] as $mKey => $mValue){
				if($mKey === '') {
					$aTemp[$iSelectKey]['key'] = 'xNullx';
				} else {
					$aTemp[$iSelectKey]['key'] = $mKey;
				}
				$aTemp[$iSelectKey]['value'] = $mValue;
				$iSelectKey++;
			}
			$aElements['select_options'] = $aTemp;
			###########################################################
			
			if(
				$oElement->element_type == 'icon' &&
				$oElement->info_text == 1
			){
				$aElements['info_text'] = '';
			} else if($oElement->element_type == 'icon'){
				unset($aElements['info_text']);
			} else if(
				$oElement->element_type == 'pagination' &&
				$oElement->limit_selection == 1
			){
				$aElements['default_limit'] = (int)$oGui->_aTableData['limit'];
			}

			$aData['bar_elements'][$iIKey] = $aElements;
            $aData['bar_elements'][$iIKey]['visible'] = 1;

            if(!$oIconStatusVisible instanceof Ext_Gui2_View_Icon_Visible){
				$iVisible									= (int)$oIconStatusVisible->getStatus($aSelectedIds, $aRowData, $oElement);
                $aData['bar_elements'][$iIKey]['visible']	= $iVisible;	
            } else{
				$iVisible									= 1;
			}
			
			if($iVisible == 1)
			{
				// Schließt aus, dass nach einem Umbruch und einem darauf folgendem Label ein Separator erscheint
				if($oElement instanceof Ext_Gui2_Bar_Break) {
					$iBarItemLineCount = 0;
				} else {
					$iBarItemLineCount++;
				}	
			}

			if(
				!empty($aSelectedIds) && 
				!empty($aRowData) && 
				$oData != null && 
				$oElement->element_type == 'icon'
			){

				/*
				 * Wenn die GUI readonly ist, dann keine icons aktivieren
				 * Export zeigt nur an, daher hier ausschliessen
				 */
				if(
					strpos($oElement->task, 'export') !== 0 &&
					$oGui->bReadOnly
				) {
					$aData['bar_elements'][$iIKey]['active'] = 0;
				} else {
					$aData['bar_elements'][$iIKey]['active'] = (int)$oIconStatusActive->getStatus($aSelectedIds, $aRowData, $oElement);
				}

				if($oElement->info_text == 1){
					$aData['bar_elements'][$iIKey]['info_text'] = $oData->getRowIconInfoText($aSelectedIds, $aRowData, $oElement);
				}
				if($oElement->dbl_click_element == 1){
					$aData['bar_elements'][$iIKey]['dbl_click_element'] = 1;
				}
				
			} else if($oElement->element_type == 'icon'){

				if(!$oIconStatusActive instanceof Ext_Gui2_View_Icon_Active){
					// Wenn die GUI readonly ist, dann keine icons aktivieren
					/*
					if($oGui->bReadOnly) {
						$aData['bar_elements'][$iIKey]['active'] = 0;
					} else {
						$aData['bar_elements'][$iIKey]['active'] = (int)$oIconStatusActive->getStatus($aSelectedIds, $aRowData, $oElement);
					}*/
					$aData['bar_elements'][$iIKey]['active'] = (int)$oIconStatusActive->getStatus($aSelectedIds, $aRowData, $oElement);
				}

				// Export zeigt nur an, daher hier ausschliessen
				if(
					strpos($oElement->task, 'export') !== 0 &&
					$oGui->bReadOnly
				) {
					$aData['bar_elements'][$iIKey]['active'] = 0;
				}
				
			}

			// Wenn eine abhängigkeit besteht => nicht aklickbar falls parent nicht markiert!
			$oParent = $oGui->getParentClass();

			if(
				is_object($oParent) &&
				$oParent instanceof Ext_Gui2 &&
                $oGui->foreign_key != "" && // es gibt auch inner guis die unabhängig von der äuseren sind ( nummerkreise z.b ) hier darf das nicht deaktiviert werden
				empty($_VARS['parent_gui_id']) &&
				$oData != null &&
				$oElement->element_type == 'icon'
			){
				$aData['bar_elements'][$iIKey]['active'] = 0;
			}			

			// Falls es Dialog Daten gibt, hole sie nur falls das Icon auch klickbar ist
			if(
                $oElement->element_type == 'icon'
			) {
				// Die Variable kann einen Zeiger auf ein Objekt beinhaltet, daher muss das erst unsetten werden
				unset($aData['bar_elements'][$iIKey]['dialog_data']);
				$aData['bar_elements'][$iIKey]['dialog_data'] = array();
				
				if($aData['bar_elements'][$iIKey]['visible'] == 1) {
				
					$sIconKey = $oElement->getKey();
					
					// Dialog data darf in aIconData nicht überschrieben werden, falls vorhanden
					$aTempDialogData = null;
					if(
						isset($oData->aIconData[$sIconKey]) &&
						$oData->aIconData[$sIconKey]['dialog_data'] instanceof \Gui2\Dialog\DialogInterface
					) {
						$aTempDialogData = $oData->aIconData[$sIconKey]['dialog_data'];
					}

					$oData->aIconData[$sIconKey] = $oElement->getConfigArray();

					// Falls vorhanden wird der zwischengespeicherte Dialog wieder gesetzt
					if($aTempDialogData instanceof \Gui2\Dialog\DialogInterface) {
						$oData->aIconData[$sIconKey]['dialog_data'] = $aTempDialogData;
					}

					// TODO Wofür wird das hier gebraucht? Die Properties gibt es so nicht
					/*
					if($oData->aIconData[$sIconKey]['dialog_data'] instanceof \Gui2\Dialog\DialogInterface) {
						$oData->aIconData[$sIconKey]['dialog_data']->action = $oElement->action;
						$oData->aIconData[$sIconKey]['dialog_data']->additional = $oElement->additional;
					}
					*/
				
				}				
				
			}

		}

		// Array muss neu nummiert werden, ansonsten stirbt das JavaScript
		if(
			$bRenumberElements &&
			!empty($aData['bar_elements'])
		) {
			$aData['bar_elements'] = array_merge($aData['bar_elements']);
		}

		return $aData;

	}
	
	/**
	 * Gibt alle Elemente der Leiste zurück
	 * @return array
	 */
	public function getElements(){
		
		$aElements = $this->_aElements;

		return $aElements;
	}

	/**
	 * Fügt ein Element der Leiste hinzu
	 * @param $mElement
	 * @param $iPosition
	 * @throws Exception
	 */
	public function setElement($mElement, $iPosition=null) {
		
		$oNewElement = null;
		
		if(is_object($mElement)) {

			$oAccess = Access::getInstance();
			 
			if(
				$oAccess instanceof Access_Backend &&
				$oAccess->checkValidAccess() === true &&
				(
					(
						$mElement->access != "" &&
						$oAccess->hasRight($mElement->access) 
					) ||
					$mElement->access == ""
				)
			) {
				if(
					$mElement->id != '' ||
					$mElement->element_type != 'filter'
				){
					$oNewElement = $mElement;
				} else {
					throw new Exception("Filter Element has no ID");
				}
			}
			
		} else if((string)$mElement == 'break'){
			$oElement = new Ext_Gui2_Bar_Break();
			$oNewElement = $oElement;
		} else if(is_string($mElement)){
			$oHtml = $this->createHtml($mElement);
			$oNewElement = $oHtml;
		} else {
			throw new Exception("Bar Element Data unknown");
		}

		if($oNewElement) {

			if($iPosition !== null) {

				if($iPosition < 0) {
					$iPosition = count($this->_aElements) + $iPosition;
				}

				$this->_aElements = array_merge(array_slice($this->_aElements, 0, $iPosition), array($oNewElement), array_slice($this->_aElements, $iPosition));

				return;

			}

			// Alte HTML-GUIs: Alle Filter abfangen
			if ($this->_oGui->sidebar) {
				if ($oNewElement instanceof Ext_Gui2_Bar_Filter || $oNewElement instanceof Ext_Gui2_Bar_Timefilter) {
					$this->_oGui->setFilter($oNewElement);
					return;
				}
			}

			$this->_aElements[] = $oNewElement;

		}
		
	}
		
#######################################
### Methoden um Objekte zu erzeugen ###
#######################################

	/**
	 * @param bool $bOnlyPageCount
	 * @param bool $bLimitSelection
	 * @return Ext_Gui2_Bar_Pagination
	 */
	public function createPagination($bOnlyPageCount = false, $bLimitSelection = false){
		return new Ext_Gui2_Bar_Pagination($bOnlyPageCount, $bLimitSelection);
	}

	/**
	 * @return Ext_Gui2_Bar_LoadingIndicator
	 */
	public function createLoadingIndicator() {
		return new Ext_Gui2_Bar_LoadingIndicator();
	}

	/**
	 * Icon Objekt erzeigen (WRAPPER)
	 * @param $sImg
	 * @param $sAction
	 * @param $sTitle
	 * @return Ext_Gui2_Bar_Icon
	 */
	public function createIcon($sImg, $sTask, $sTitle = ''){
		return new Ext_Gui2_Bar_Icon($sImg, $sTask, $sTitle);
	}

	/**
	 * Delete Icon Objekt erzeugen
	 * @param string $sTitle
	 * @param string $sLabel
	 * @return Ext_Gui2_Bar_Icon
	 */
	public function createDeleteIcon($sTitle, $sLabel = ''){

		$oIcon = $this->createIcon('fa-minus-circle', 'deleteRow', $sTitle);
		$oIcon->label = $sLabel;
		$oIcon->multipleId 	= 0;
		$oIcon->active = 0;
		$oIcon->visible = 1;

		return $oIcon;
	}

	/**
	 * New Icon Objekt erzeugen
	 * @param string $sTitle
	 * @param $oDialog
	 * @param string $sLabel
	 * @param string $sDialogTitel
	 * @return Ext_Gui2_Bar_Icon
	 */
	public function createNewIcon($sTitle, $oDialog, $sLabel = '', $sDialogTitel = 'Dialog - #ext_1#, #ext_2#'){

		$oIcon = $this->createIcon('fa-plus-circle', 'openDialog', $sTitle);
		$oIcon->action = 'new';
		$oIcon->label = $sLabel;
		$oIcon->multipleId 	= 0;
		//$oIcon->request_data = '&task=openDialog';
		$oIcon->dialog_title = $sDialogTitel;
		$oIcon->active = 1;
		$oIcon->visible = 1;
		$oIcon->dialog_data = $oDialog;
		$oIcon->info_text = 0;

		return $oIcon;
	}

	/**
	 * Edit Icon Objekt erzeugen
	 * @param string $sTitle
	 * @param $oDialog
	 * @param string $sLabel
	 * @param string $sDialogTitel
	 * @return Ext_Gui2_Bar_Icon
	 */
	public function createEditIcon($sTitle, $oDialog, $sLabel = '', $sDialogTitel = 'Dialog - #ext_1#, #ext_2#'){

		$oIcon = $this->createIcon('fa-pencil', 'openDialog', $sTitle);
		$oIcon->action = 'edit';
		$oIcon->label = $sLabel;
		$oIcon->multipleId 	= 0;
		//$oIcon->request_data = '&task=openDialog';
		$oIcon->dialog_title = $sDialogTitel;
		$oIcon->active = 0;
		$oIcon->visible = 1;
		$oIcon->dialog_data = $oDialog;
		$oIcon->dbl_click_element = 1;
		$oIcon->info_text = 0;

		return $oIcon;
	}

	public function createShowIcon($sTitle, $oDialog) {

		$oIcon = $this->createIcon(Ext_TC_Util::getIcon('info'), 'openDialog', $sTitle);
		$oIcon->action = 'edit';
		$oIcon->label = $this->_oGui->t('Anzeigen');
		$oIcon->multipleId 	= 0;
		$oIcon->active = 0;
		$oIcon->visible = 1;
		$oIcon->dialog_data = $oDialog;
		$oIcon->dbl_click_element = 1;
		$oIcon->info_text = 0;

		$oDialog->bReadOnly = true;
		$oDialog->save_button = false;

		return $oIcon;

	}

	/**
	 * Filemanager Icon erzeugen
	 * @return Ext_Gui2_Bar_Icon
	 */
	public function createFileManagerIcon() {

		$aAccess = $this->_oGui->access;
		if(is_array($aAccess)) {
			$aAccess[1] = 'filemanager';
		}

		$oIcon = $this->createIcon('fa-upload', 'request', $this->_oGui->t('Dateiverwaltung'));
		$oIcon->access = $aAccess;
		$oIcon->action = 'filemanager';
		$oIcon->label = $this->_oGui->t('Dateiverwaltung');
		$oIcon->multipleId 	= 0;
		$oIcon->active = 0;
		$oIcon->visible = 1;
		$oIcon->info_text = 0;

		return $oIcon;
	}

	/**
	 * Notizen Icon erzeugen
	 * @return Ext_Gui2_Bar_Icon
	 */
	public function createNoticesIcon() {

		$aAccess = $this->_oGui->access;
		if(is_array($aAccess)) {
			$aAccess[1] = 'notices';
		}

		$oIcon = $this->createIcon('fa-comments', 'request', $this->_oGui->t('Notizen'));
		$oIcon->access = $aAccess;
		$oIcon->action = 'notices';
		$oIcon->label = $this->_oGui->t('Notizen');
		$oIcon->multipleId 	= 0;
		$oIcon->active = 0;
		$oIcon->visible = 1;
		$oIcon->info_text = 0;

		return $oIcon;
	}
	
	/**
	 * Icon zum ein/ausblenden des Linken Menüpunktes
	 * @return Ext_Gui2_Bar_Icon
	 */
	public function createToggleMenuIcon(){

		$oIcon = new Ext_Gui2_Bar_Icon('/admin/extensions/gui2/application_side_contract.png', 'toggleMenu', $this->_oGui->t('Linkes Menü ausblenden'));
		$oIcon->action = 'toggleMenu';
		$oIcon->multipleId 	= 0;
		//$oIcon->request_data = '&task=openDialog';
		$oIcon->active = 1;
		$oIcon->visible = 1;
		$oIcon->info_text = 0;

		return $oIcon;
	}

	/**
	 * Icon Objekt erzeigen (WRAPPER)
	 * @param string $sTitle
	 * @param string $sLabel
	 * @param $sTitle
	 * @return Ext_Gui2_Bar_Icon
	 */
	public function createCSVExport($sTitle='CSV', $sLabel = 'CSV') {

		$oIcon = new Ext_Gui2_Bar_Icon('fa-file-csv', 'export_csv', $this->_oGui->t($sTitle));
		$oIcon->label = $this->_oGui->t($sLabel);
		$oIcon->multipleId 	= 1;
		$oIcon->active = 1;
		$oIcon->visible = 1;
		$oIcon->info_text = 0;
		
		return $oIcon; 
	}

	/**
	 * Icon Objekt erzeigen (WRAPPER)
	 * @param string $sTitle
	 * @param string $sLabel
	 * @param $sTitle
	 * @return Ext_Gui2_Bar_Icon
	 */
	public function createExcelExport($sTitle='Excel', $sLabel='Excel') {

		$oIcon = new Ext_Gui2_Bar_Icon('fa-file-excel', 'export_excel', $this->_oGui->t($sTitle));
		$oIcon->label = $this->_oGui->t($sLabel);
		$oIcon->multipleId 	= 1;
		$oIcon->active = 1;
		$oIcon->visible = 1;
		$oIcon->info_text = 0;
		
		return $oIcon; 
	}

	/**
	 * Kopiert einen Eintrag mit all seinen Kindern (JoinedObjects)
	 * _NICHT_ benutzen mit UNIQUE-Tabellen, sondern dort dann $oDialog->save_bar_as_new=true!
	 * @param string $sTitle
	 * @param string $sLabel
	 * @author DG
	 * @since 10.05.2011
	 *
	 * Aufbau von $aOptions
	 * array(
	 *		'copy_unique' => array(
	 *			'name' => ':value - Kopie'
	 *		),
	 *		'copy_openDialog' => true,
	 *		'copy_recursive_unique' => true
	 * )
	 *
	 * bei "copy_unique" gibt mal als KEY das Feld an
	 * und als VALUE den Replace wert
	 * hier gibt es folgende Optionen
	 *
	 * ":value" => der wert im alten eintrag
	 * ":random8" => Random Wert mit 8 stellen
	 * ":random16" => Random Wert mit 16 stellen
	 * ":random32" => Random Wert mit 32 stellen
	 */
	public function createCopyIcon($sTitle, $sLabel, $aOptions = array()) {
		$oIcon = new Ext_Gui2_Bar_Icon('fa-copy', 'createCopy', $sTitle);
		$oIcon->label = $sLabel;
		$oIcon->multipleId 	= 0;
		$oIcon->active = 0;
		$oIcon->visible = 1;
		$oIcon->info_text = 0;
		$oIcon->options_serialized = serialize($aOptions);
		$oIcon->confirm = true;
		$oIcon->confirm_message = $this->_oGui->t('Möchten Sie den Eintrag wirklich duplizieren?');

		return $oIcon; 
	}

	
	/**
	 * Filter Objekt erzeigen (WRAPPER)
	 * @param string $sFilterType
	 * @param $mFormat
	 * @return Ext_Gui2_Bar_Filter
	 */
	public function createFilter($sFilterType = 'input', $mFormat = ''){
		$oFilter = new Ext_Gui2_Bar_Filter($sFilterType, $mFormat);
//		if($sFilterType == 'select'){
//			$oFilter->db_operator = '=';
//		}
		return $oFilter;
	}

	/**
	 * Filter Objekt erzeigen (WRAPPER)
	 * @param $mFormat
	 * @return Ext_Gui2_Bar_Timefilter
	 */
	public function createTimeFilter($mFormat = ''){
		return new Ext_Gui2_Bar_Timefilter($mFormat);
	}
	
	/**
	 * Html Objekt erzeigen (WRAPPER)
	 * @param string $sHtml
	 * @param string $sId
	 * @return Ext_Gui2_Bar_Html
	 */
	public function createHtml($sHtml, $sId = ''){
		return new Ext_Gui2_Bar_Html($sHtml, $sId);
	}

	/**
	 *
	 * @param string $sLabel
	 * @param string $sStyle
	 * @return \Ext_Gui2_Bar_Labelgroup 
	 */
	public function createLabelGroup($sLabel, $sStyle = ''){
		return new Ext_Gui2_Bar_Labelgroup($sLabel, $sStyle);
	}

	/**
	 *
	 * @return \Ext_Gui2_Bar_Seperator 
	 */
	public function createSeperator(){
		return new Ext_Gui2_Bar_Seperator('sub');
	}

	/**
	 *
	 * @return \Ext_Gui2_Bar_Seperator 
	 */
	public function createBarSeperator(){
		return new Ext_Gui2_Bar_Seperator('main');
	}

	public function addWDSearch(){

		$oFilter = $this->createFilter('input');
		$oFilter->id = 'wdsearch';
		$oFilter->db_operator = '';
		$oFilter->placeholder = $this->_oGui->t('Suche').'…';
		$this->setElement($oFilter);

		$oIcon = $this->createIcon('/admin/extensions/gui2/zoom.png', 'wdsearch_btn');
		$oIcon->label = $this->_oGui->t('Suche starten');
		$oIcon->info_text = 0;
		$oIcon->active = 1;
		$this->setElement($oIcon);

	}

	/**
	 * TODO Achtung: Die Methode ist Schwachsinn, da die Bars im JS generiert werden
	 *
	 * @return string
	 */
	public function generateHTML(){ 
        $sHtml = $this->__toString();
		return $sHtml;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		
        $sHistoryHtml = '';
        $oAccess = Access::getInstance();
        
        if(
           $this->access == "" ||
           $oAccess->hasRight($this->access)
        ){
        
            $sId = '';
            if($this->id != '') {
                $sId = ' id="'.$this->id.'"';
            }

            $sHistoryHtml = '<div class="divToolbar ' . $this->class . '" style="width:100%; ' . $this->style . '" '.$sId.'>
                                <div class="guiBarElement">
                                    <div class="divToolbarHtml">';

            foreach((array)$this->getElements() as $oHtml){
                if($oHtml instanceof Ext_Gui2_Bar_Html){
                    $sHistoryHtml .= $oHtml->html;
                }
            }

            $sHistoryHtml .= "		</div>
                                </div>
                            </div>";
            
        }
        
		return $sHistoryHtml;
	}
	
}