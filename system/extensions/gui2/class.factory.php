<?php

use Illuminate\Support\Arr;

class Ext_Gui2_Factory {

	protected $sConfigFile;
	/**
	 * @var Ext_Gui2_Config_Parser 
	 */
	protected $_oConfig;
	
	protected $_sL10NPath = '';
    
    protected $_sSet = '';

	/**
	 * @var Ext_Gui2_Bar_Filter[]|Ext_Gui2_Bar_Timefilter[]
	 */
	protected $aFilters = [];

	protected $ignoreSets = false;
	protected $ignoreAccess = false;
	protected $showUnvisibleColumns = false;

	/**
	 * @param string $sConfigFile
	 * @param bool $bCanIgnoreCache
	 */
	public function __construct(string $sConfigFile, bool $bCanIgnoreCache = false, bool $ignoreSets=false, bool $ignoreAccess=false, bool $showUnvisibleColumns=false) {

		$this->ignoreSets = $ignoreSets;
		$this->ignoreAccess = $ignoreAccess;
		$this->showUnvisibleColumns = $showUnvisibleColumns;
		
		$oParser = new Ext_Gui2_Config_Parser('', $bCanIgnoreCache);
		$oParser->setConfig($sConfigFile);
		$oParser->load();

		$this->_oConfig = $oParser;

		$sDescription = $this->_createDescription();
		$this->_sL10NPath = $sDescription;
		$this->sConfigFile = $sConfigFile;
		
	}
	
	public function getConfigFileName() {
		return $this->sConfigFile;
	}
	
	public function getConfig() {
		return $this->_oConfig;
	}
	
	/**
	 * return the complete GUI Object with all Bars, Columns etc...
     * @param string $sSet
     * @param Ext_Gui2 $oParentGui
     * @param array $aGuiOptions
	 * @return Ext_Gui2
	 */
	public function createGui($sSet = '', Ext_Gui2 $oParentGui=null, $aGuiOptions = array()) {

        $this->_sSet = $sSet;

		// Set direkt über abgeleitete YML setzen, sonst geht es nur über HTML-Datei
		if(
			empty($this->_sSet) &&
			!empty($this->_oConfig->get('set'))
		) {
			$this->_sSet = $this->_oConfig->get('set');
		}

		if (!empty($this->_sSet)) {
			// Set spezifische Einstellungen setzen
			$this->_oConfig->mergeSet($this->_sSet);
		}

		$oGui = $this->_createGuiObject($oParentGui, $aGuiOptions);

        if ($oGui instanceof Ext_TC_Gui2) {
            $this->_addDesigner($oGui, $sSet);
        }

        $this->_addBars($oGui);

		$this->_addColumns($oGui);

		$this->addFilters($oGui);

        $this->_addJs($oGui);

        $this->_addCss($oGui);

		$oGui->executeGuiCreatedHook();
		$oGui->getDataObject()->executeGuiCreatedHook();
		
		return $oGui;
	}
	
	/**
	 * translate a string
	 * @param string $sTrans
	 * @return string
	 */
	public function t($sTrans){
		return L10N::t($sTrans, $this->_sL10NPath);
	}
	
    /**
     * Add all js file to the gui
     * @param Ext_Gui2 $oGui 
     */
    protected function _addJs(Ext_Gui2 $oGui){
        
        $aJs = $this->_oConfig->get('js');

        foreach($aJs as $mJsData) {
			if(is_array($mJsData)) {
				$oGui->addJs($mJsData[0], $mJsData[1]);
			} else {
				$oGui->addOptionalData(array('js'=>array($mJsData)));
			}
        }

    }
    
    /**
     * add all css files to the GUI
     * @param Ext_Gui2 $oGui 
     */
    protected function _addCss(Ext_Gui2 $oGui){
        $aCss = $this->_oConfig->get('css');

        foreach($aCss as $mCssData){
			if(is_array($mCssData)) {
				$oGui->addCss($mCssData[0], $mCssData[1]);
			} else {
				$oGui->addOptionalData(array('css'=>array($mCssData)));
			}
        }
    }

    /**
     * add all gui designer options to the gui
     * @param Ext_TC_Gui2 $oGui
     */
    protected function _addDesigner(Ext_TC_Gui2 $oGui) {
        
        $sSection   = $this->_oConfig->get(array('designer', 'dialog'));
        $sFilter    = $this->_oConfig->get(array('designer', 'filter'));
		$sFlex		= $this->_oConfig->get(array('designer', 'flex'));
		$aAdditionalFlex = $this->_oConfig->get(array('designer', 'additional_flex'));

		if(!empty($sSection)){
            $oGui->setDesignSection($sSection);
        }
        
		if(!empty($sFlex)){
			$oGui->sSection	= $sFlex;
		}

		if(!empty($aAdditionalFlex)){
			$oGui->additional_sections = $aAdditionalFlex;
		}
		
        if (!empty($sFilter) && !$oGui->sidebar) {
            $oGui->setDesignFilterBar($sFilter, '', $this->_oConfig);
        }

    }

    /**
     * group all coulmns by groupy name and only visible
     * @return array 
     */
    protected function _getGroupedColumns(){
		
		$aColumns = $this->_oConfig->getColumns();
        
        $aGrouped = array();
	
		foreach($aColumns as $aColumn){
			if(isset($aColumn['visible']) && $aColumn['visible'] !== false){
                if(!empty($aColumn['i18n'])) {
                    $aColumn['column'] .= '_'.System::getInterfaceLanguage();
                }
                $sGroup = (string)($aColumn['group'] ?? '');
                $aGrouped[$sGroup] = null;
            }
		}
        
        return $aGrouped;
    }

	protected function getFilterValues() {
		
		$aFilterValues = $this->_oConfig->get('filter_values');
		
		foreach($aFilterValues as &$aFilterValue) {
			if(
				is_array($aFilterValue) &&
				array_keys($aFilterValue) === [0, 1]
			) {
				$aFilterValue = $this->_callMethod($aFilterValue);
			}
		}
		
		return $aFilterValues;
	}


	/**
     * add all columns to the GUI
     * @param Ext_Gui2 $oGui 
     */
    protected function _addColumns(Ext_Gui2 $oGui){
		
        $aGroups = $this->_getGroupedColumns();
		$aColumns = $this->_oConfig->getColumns();
		$aFilterValues = $this->getFilterValues();		

		foreach($aGroups as $sGroupLabel => &$oGroup){
            
			$oGroup = null;
            
			if(!empty($sGroupLabel)){
				$sKey	= md5($sGroupLabel);
				$oGroup = $oGui->createColumnGroup($sKey);
				$oGroup->setConfig('title', $this->t($sGroupLabel));
			}

        }

		foreach($aColumns as $aColumn) {

            if(
//				($aColumn['visible'] ?? true) !== false &&
				(
					$this->ignoreSets === true || 
					$this->_checkSet($aColumn['set'] ?? [])
				) && // Passiert für Flex-Felder in Ext_TC_Gui2_Data::prepareColumnListByRef()
				(
					$this->ignoreAccess === true ||
					$this->_checkAccess($aColumn['access'] ?? null)
				)
            ) {

				$this->addColumnFilter($oGui, $aColumn, $aFilterValues);

				if (
					$this->showUnvisibleColumns === false &&
					!$aColumn['visible']
				) {
					continue;
				}

				if(
					isset($aColumn['i18n'])	&&
					isset($aColumn['i18n']['interface']) &&
					$aColumn['i18n']['interface'] == 'frontend'
				) {
					$sInterfaceLang = Factory::executeStatic('Util', 'getInterfaceLanguage');
				} else {
					$sInterfaceLang  = System::d('systemlanguage');
					// Fallback
					if(empty($sInterfaceLang)) {
						$interfaceLanguages = System::d('backend_languages')??[];
						$sInterfaceLang = reset($interfaceLanguages);
					}
				}

                // wenn i18n , nicht die aktuelle sprache und nicht alle sprachen eingeblendet werden sollen
                // dann spalte überspringen
                if(
                    isset($aColumn['i18n']) &&
                   $aColumn['language'] != $sInterfaceLang &&
                   empty($aColumn['i18n']['all'])
                ) {
                    continue;
                }

                $sGroupLabel = (string)($aColumn['group'] ?? '');
                $oGroupForColumn    = $aGroups[$sGroupLabel];

				$sTitleImg      = '';
                if(!empty($aColumn['i18n']['show_flag'])) {
                    $sTitleImg  = (string)$aColumn['img'];
                }
                
                $mTitle			= $aColumn['title'];
				if(is_array($mTitle)) {
					$mTitle		= $this->_callMethod($mTitle, $oGui);
				} else {
					$mTitle		= $this->t($mTitle);
				}
				$sTitle			= $mTitle;
				
				$mDescription = $aColumn['description'] ?? '';
				if(is_array($mDescription)) {
					$mDescription = $this->_callMethod($mDescription);
				} else {
					$mDescription = $this->t($mDescription);
				}
				
                $sWidth			= $aColumn['width'];

                if(
                    !is_numeric($sWidth) &&
                    strpos($sWidth, 'px') === false
                ) {
                    $sWidth	= Ext_Gui2_Util::getTableColumnWidth($aColumn['width']);
                }

                $aFormatParams		= $this->_createFormatParameters($aColumn['format_params'] ?? []);
				
				$aStyleParams		= $this->_createFormatParameters($aColumn['style_params'] ?? []);

                $sSelectColumn		= (string)($aColumn['data'] ?? '');
                if(empty($sSelectColumn)) {
                    $sSelectColumn = (string)$aColumn['column'];
                }

				$sSortableColumn = null;
                $sColumn = $aColumn['column'];
             
				// Falls durch den Index eine spezielle Spalte eingebaut wurde für das Sortieren
				// dann muss diese in der Column als db_column gesetzt werden damit das Sortieren klappt
                if(!empty($aColumn['sortable_column'])) {

					$sSortableColumn = (string)$aColumn['sortable_column'];
					// Spalte suchen und auf i18n prüfen
                    foreach($aColumns as $aTempColumn) {
                        if($aTempColumn['_column'] == $sSortableColumn){

                            if(!empty($aTempColumn['i18n'])) {
								$sSortableColumn .= '_'.$sInterfaceLang;
                            }
                            break;
                        }
                    }

                }

                $oColumn                = $oGui->createColumn();
                $oColumn->db_column		= (string)$sColumn;
                $oColumn->db_alias		= (string)($aColumn['alias'] ?? '');
                $oColumn->select_column	= $sSelectColumn;

                if($sSortableColumn !== null) {
					$oColumn->sortable_column = $sSortableColumn;
				}

				if(!empty($aColumn['order_settings'])) {
					$oColumn->order_settings = $aColumn['order_settings'];
				}
				
                $oColumn->title         = $sTitleImg.$sTitle;
                $oColumn->searchable    = 1;
                $oColumn->mouseover_title = $mDescription;
				
                if(!empty($aColumn['format'])){
                    $oColumn->format = $this->_callObject($aColumn['format'], $aFormatParams);

                    // Damit man auch Selection-Klassen als Format-Klassen nutzen kann
                    if($oColumn->format instanceof Ext_Gui2_View_Selection_Abstract) {
                        $oColumn->format = new Ext_Gui2_View_Format_Selection($oColumn->format);
                    }

                }
                if(!empty($aColumn['post_format'])){
					$aPostFormatParams = $this->_createFormatParameters($aColumn['post_format_params']);
                    $oColumn->post_format = $this->_callObject($aColumn['post_format'], $aPostFormatParams);
                }
                $oColumn->group         = $oGroupForColumn;
                $oColumn->width         = $sWidth;
				if(!empty($aColumn['resize'])) {
					$oColumn->width_resize	= (boolean)$aColumn['resize'];
				}
				if(!empty($aColumn['small'])) {
					$oColumn->small	= (boolean)$aColumn['small'];
				}
				if(isset($aColumn['sortable'])) {
					$oColumn->sortable	= (boolean)$aColumn['sortable'];
				}
				if(isset($aColumn['flexibility'])) {
					$oColumn->flexibility = (boolean)$aColumn['flexibility'];
				}
				if(!empty($aColumn['inplace_editor'])) {
					$oColumn->inplaceEditor	= (boolean)$aColumn['inplace_editor'];
				}
				if(!empty($aColumn['inplace_editor_type'])) {
					$oColumn->inplaceEditorType = (string)$aColumn['inplace_editor_type'];
				}
				if(!empty($aColumn['inplace_editor_start'])) {
					$oColumn->inplaceEditorStart = (boolean)$aColumn['inplace_editor_start'];
				} elseif(!empty($aColumn['inplaceEditorStart'])) {
					$oColumn->inplaceEditorStart = (boolean)$aColumn['inplaceEditorStart'];
				}
				if(!empty($aColumn['db_type'])) {
					$oColumn->db_type = (string)$aColumn['db_type'];
				}
                if(!empty($aColumn['style'])){
                    $oColumn->style	= $this->_callObject($aColumn['style'], $aStyleParams);
                }
                if(!empty($aColumn['event'])){
                    $oColumn->event	= $this->_callObject($aColumn['event'], [$oGui]);
                }
                if(isset($aColumn['default'])) {
                    $oColumn->default = (boolean)$aColumn['default'];
                }
                if(!empty($aColumn['overflow'])){
                    $oColumn->css_overflow	= $aColumn['overflow'];
                }
				if(!empty($aColumn['i18n'])) {
					$aColumn['i18n']['original_title'] = $sTitle; // Für Export
					$aColumn['i18n']['language'] = $aColumn['language']; // Kommt vom Parser, geht sonst verloren
					$oColumn->i18n = $aColumn['i18n'];
				}

				$oGui->setColumn($oColumn);

            }
        }
	}

	/**
	 * Column hat filtersets angegeben: Bei neuen Filtern (Sidebar) Filter generieren und sammeln
	 *
	 * @param Ext_Gui2 $oGui
	 * @param array $aColumn
	 */
	private function addColumnFilter(Ext_Gui2 $oGui, array $aColumn, array $aDefaultValues) {

		/*
		 * Workaround, damit i18n Spalten nicht mehrfach als Filter ergänzt werden
		 * @todo Das sollte besser gelöst werden ohne nachträgliche manipulation
		 */
		$columnKey = Ext_Gui2_Data::setFieldIdentifier($aColumn['_column'], $aColumn['alias']);
		
		if (
			!$oGui->sidebar ||
			empty($aColumn['filterset']['type']) ||
			isset($this->aFilters[$columnKey])
		) {
			return;
		}

		// Wert von filter_values als Default von Filter setzen
		if (isset($aDefaultValues[$aColumn['_column']])) {
			$aColumn['filterset']['default'] = $aDefaultValues[$aColumn['_column']];
		}

		$oFilterBuilder = new Ext_Gui2_Factory_Filter($oGui, $aColumn);
		$oFilter = $oFilterBuilder->create();

		$this->aFilters[$columnKey] = $oFilter;

	}

	/**
	 * Neue Filter (Sidebar): Filter zur GUI hinzufügen
	 *
	 * @param Ext_Gui2 $oGui
	 */
	private function addFilters(Ext_Gui2 $oGui) {

		$aInputFilters = [];

		foreach ($this->aFilters as $oFilter) {

			// Alle Inputs (Suchfelder) sammeln
			if ($oFilter->filter_type === 'input') {
				$aInputFilters[] = $oFilter->db_column;
				continue;
			}

			$oGui->setFilter($oFilter);

		}

		if (!empty($aInputFilters)) {
			$oFilter = new Ext_Gui2_Bar_Filter();
			$oFilter->filter_type = 'input';
			$oFilter->db_column = $aInputFilters;
			$oFilter->id = 'search';
			$oFilter->sort_order = 0;
			$oGui->setFilter($oFilter);
		}


	}

	public function _checkAccess($mAccess){
		if(!empty($mAccess)){
			if (
				is_array($mAccess) &&
				class_exists($mAccess[0])
			) {
				return $this->_oConfig->callMethod($mAccess);
			}

			$oAccess = Access::getInstance();

			if($oAccess === null) {
				return false;
			}

			return $oAccess->hasRight($mAccess);
		}
		return true;
	}

		/**
     * check a the Set Key
     * @param string|array $mSet
     * @return boolean 
     */
    protected function _checkSet($mSet) {
		
        if(!is_array($mSet)) {
            $mSet = (array)$mSet;
        }
        
        if(
			empty($mSet) ||
			in_array($this->_sSet, $mSet) ||
			$this->_sSet == ''
        ) {
            return true;
        }
        
        return false;
    }

    /**
     * create an array with all params for the format class
     * if a param is an array it will be called as method
     * @param array $mParams
     * @return array 
     */
	protected function _createFormatParameters($mParams){
		$aBack = array();
		foreach((array)$mParams as $mParam){
			// TODO Dieser komische Edge-Case sollte entfernt oder anders gelöst werden, da Array-Argumente unmöglich sind
			if(is_array($mParam)){
				$aBack[] = $this->_callMethod($mParam);
			} else {
				$aBack[] = $mParam;
			}
		}
		return $aBack;
	}

	/**
	 * add all Bars who was defined in the Config
	 * if export or Pagination was enabled it will be add a third bar if they do not exist and
	 * add the two funktions
	 * @param Ext_Gui2 $oGui 
	 */
	protected function _addBars(Ext_Gui2 $oGui){
		
		// Wenn Zugriff nicht geprüft wird, API-Zugriff und da braucht man keine Icons
		if($this->ignoreAccess) {
			return;
		}
		
		$bLastBar = $this->_checkLastBar();
		$oLastBar = null;

		$aBars = (array)$this->_oConfig->get('bars');

        foreach ($aBars as $aBar) {
            
            if(
				empty($aBar['set']) ||
				$this->_checkSet($aBar['set'])
			) {

                if(
					(
                        isset($aBar['type']) && 
                        $aBar['type'] != 'legend'
                    ) ||
                    empty($aBar['type'])
				){
                    $oBar = $oGui->createBar($aBar['type'] ?? null);
                } else {
                    $oBar = new Ext_Gui2_Bar_Legend($oGui);
                }

                $oBar->width = '100%';
                $oBar->position = $aBar['position'];
                if(!empty($aBar['access'])){
                    $oBar->access = $aBar['access'];
                }

                if(!empty($aBar['height'])){
                    $oBar->height = $aBar['height'];
                }

                if(!empty($aBar['id'])){
                    $oBar->id = $aBar['id'];
                }

				if(isset($aBar['elements'])) {
					foreach($aBar['elements'] as $aElement){
						$this->_addElementToBar($oGui, $oBar, $aElement);
					}
				}

				if(!empty($aBar['hook'])) {
					System::wd()->executeHook($aBar['hook'], $oBar, $oGui);
				}

				if(isset($aBar['last']) && $aBar['last'] === true) {
					$oLastBar = $oBar;
				}

                $oGui->setBar($oBar);

            }
		}

		// Falls keine Pagination Zeile, dann ergänzen
		if($bLastBar){
			if($oLastBar === null) {
				$oLastBar = $oGui->createBar('');
				$oLastBar->width = '100%';
				$oLastBar->position = 'top';
				$oGui->setBar($oLastBar);
			}
			$this->_addLastBarElements($oGui, $oLastBar);
		}

	}

	/**
	 * create and add a Element to the given Bar with the given configuration
	 * @param Ext_Gui2 $oGui
	 * @param Ext_Gui2_Bar $oBar
	 * @param array $aElement
	 * @return bool
	 * @throws ErrorException
	 */
	protected function _addElementToBar(Ext_Gui2 $oGui, Ext_Gui2_Bar $oBar, $aElement){
		
        $bAdd = true;
        if(
			!empty($aElement['set']) &&
			!$this->_checkSet($aElement['set'])
		) {
			return false;
		}

		$oElement2 = null;

		if(!empty($aElement['call'])) {
			$aElement = array_merge($aElement, $this->_callMethod($aElement['call'], $oGui));
		}
		
		switch ($aElement['element']) {
			case 'labelgroup':
				$oElement = $this->_createLabelGroup($oBar, $aElement);
				break;
			case 'timefilter':
				$oElement	= $this->_createTimefilter($oBar, $aElement);
				if(!empty($aElement['based_on'])) {
					$oElement2 = $this->_createBasedOnFilter($oGui, $oBar, $aElement['based_on']);
					$oElement->addBasedOnFilter($oElement2);
				}
				break;
			case 'inputfilter':
				$oElement	= $this->_createInputFilter($oBar, $aElement);
				break;
			case 'checkboxfilter':
				$oElement	= $this->_createInputFilter($oBar, $aElement, 'checkbox');
				break;
			case 'selectfilter':
				$oElement	= $this->_createSelectFilter($oGui, $oBar, $aElement);
				break;
            case 'legend_label':
				$oElement	= $this->_createLegendLabel($oBar, $aElement);
                $bAdd       = false;
                break;
            case 'legend_info':
				$oElement	= $this->_createLegendInfo($oBar, $aElement);
                $bAdd       = false;
                break;
            case 'seperator':
                $oElement = $this->_createSeperator($oBar, $aElement);				
                break;
			case 'validfilter':
				$oElement = $oBar->createValidFilter($aElement['label'], $aElement);
				break;
			case 'csv_export':
				if(
					!empty($aElement['title']) && 
					!empty($aElement['label'])
				) {
					$oElement = $oBar->createCSVExport($aElement['title'], $aElement['label']);	
				} else {
					$oElement = $oBar->createCSVExport();
				}				
				break;
			case 'excel_export':
				if(
					!empty($aElement['title']) && 
					!empty($aElement['label'])
				) {
					$oElement = $oBar->createExcelExport($aElement['title'], $aElement['label']);
				} else {
					$oElement = $oBar->createExcelExport();
				}
				break;
			case 'icon':
			case 'icon_new':
			case 'icon_edit':
			case 'icon_delete':
			case 'icon_show':
			case 'icon_communication':
			case 'icon_deactivate':
			case 'icon_duplicate':
			case 'icon_file_manager':
			case 'icon_notes':
				$oElement	= $this->_createIcon($oGui, $oBar, $aElement);
				break;
//			case 'icon_index_refresh':
//				
//				$bChanges = $this->_checkIndexChanges($oGui);
//
//				if($bChanges){
//					$oElement	= $this->_getIndexRefreshIcon($oGui, $oBar);
//				} else {
//					$bAdd		= false;
//				}
//				
//				break;
				
			case 'pagination':
				
				$bOnlyPageCount = false;
				$bLimitSelection = false;
						
				if(isset($aElement['only_pagecount']))
				{
					$bOnlyPageCount = $aElement['only_pagecount'];
				}
				
				if(isset($aElement['limit_selection']))
				{
					$bLimitSelection = $aElement['limit_selection'];
				}
				
				$oElement	= $oBar->createPagination($bOnlyPageCount, $bLimitSelection);
				
				break;
			case 'loading_indicator':
				$oElement	= $oBar->createLoadingIndicator();
				break;
			case 'text':
				$oElement	= $this->t($aElement['text']);
				break;
		}
		
		if(
            !$oElement && 
            $bAdd
        ){
			throw new ErrorException('Error while creating Element ['.$aElement['element'].']!');
		} else if($bAdd){
            $oBar->setElement($oElement);
			if($oElement2) {
				$oBar->setElement($oElement2);
			}
        }
	}
	
	/**
	 * create an seperator
	 * @param Ext_Gui2_Bar $oBar
	 * @param array $aElement
	 * @return Ext_Gui2_Bar_Seperator
	 */
	protected function _createSeperator(Ext_Gui2_Bar $oBar, $aElement) {
		$oElement = $oBar->createSeperator();
		if(!empty($aElement['access'])) {
			$oElement->access = $aElement['access'];
		}
		
		return $oElement;
	}
	
	/**
	 * create an Icon
	 * @param Ext_Gui2 $oGui
	 * @param Ext_Gui2_Bar $oBar
	 * @param array $aElement 
	 * @return Ext_Gui2_Bar_Icon 
	 * @throws ErrorException 
	 */
	protected function _createIcon(Ext_Gui2 $oGui, Ext_Gui2_Bar $oBar, $aElement){
		$sElement = $aElement['element'];
	
		switch ($sElement) {
			case 'icon_new':
				$sLabel		= $this->t("Neuer Eintrag");
				$oDialog	= $this->_callIconDialog($aElement, $oGui);
				$oElement	= $oBar->createNewIcon($sLabel, $oDialog, $sLabel);
				break;
			case 'icon_edit':
				$sLabel		= $this->t("Editieren");
				$oDialog	= $this->_callIconDialog($aElement, $oGui);
				$oElement	= $oBar->createEditIcon($sLabel, $oDialog, $sLabel);
				break;
			case 'icon_delete':
				$sLabel		= $this->t("Löschen");
				$oElement	= $oBar->createDeleteIcon($sLabel, $sLabel);
				break;
			case 'icon_show':
				$sLabel = $this->t('Anzeigen');
				$oDialog = $this->_callIconDialog($aElement, $oGui);
				$oElement = $oBar->createShowIcon($sLabel, $oDialog);
				break;
			case 'icon_communication':
				if(empty($aElement['label'])) {
					$sLabel = $this->t("Kommunikation");
				} else {
					$sLabel = $this->t($aElement['label']);
				}
				$oElement = $oBar->createCommunicationIcon($sLabel, $aElement['application'], $aElement['access']);
				break;
			case 'icon_deactivate':
				$sLabel		= $this->t("Deaktivieren");
				$oElement	= $oBar->createDeactivateIcon($sLabel, $sLabel);
				break;
			case 'icon_duplicate':
				$sLabel		= $this->t("Duplizieren");
				$oElement	= $oBar->createCopyIcon($sLabel, $sLabel);
				break;
			case 'icon_file_manager':
				$oElement = $oBar->createFileManagerIcon();
				break;
			case 'icon_notes':
				$oElement = $oBar->createNoticesIcon();
				break;
			default:
				$oElement	= $this->_createIndividualIcon($oGui, $oBar, $aElement);
				break;
		}
		
		if(!$oElement){
			throw new ErrorException('Error while creating Icon!');
		}

        if(!empty($aElement['label'])) {
            $label = $aElement['label'];
            if (is_array($label)) {
                $label = $this->_callMethod($label);
            } else {
                $label = $this->t($label);
            }
            $oElement->label = $label;
        }

        if(!empty($aElement['img'])) {
            if (!is_array($aElement['img'])) {
                $oElement->img = (string)$aElement['img'];
            } else {
                $oElement->img = $this->_callMethod($aElement['img']);
            }
        }

        // Wenn Recht übergeben, dann muss es auch gesetzt werden
		if(!empty($aElement['access'])) {
			$oElement->access = $aElement['access'];
		}

        if(isset($aElement['additional'])){
            $oElement->additional = (string)$aElement['additional'];
        }

        if(isset($aElement['request_data'])){
            $oElement->request_data = (string)$aElement['request_data'];
        }

        if(isset($aElement['multiple_id'])){
            $oElement->multipleId = (int)$aElement['multiple_id'];
        }

		return $oElement;
	}
    
    /**
     * create an gui dialog object
     * @param array $aElement
     * @param Ext_Gui2 $oGui
     * @return Ext_Gui2_Dialog 
     */
    protected function _callIconDialog($aElement, $oGui): \Gui2\Dialog\DialogInterface {

		// Wenn kein Dialog beim Icon angegeben ist, dann wird er wohl später dynamisch erstellt, daher hier nur Dummy
		if(empty($aElement['dialog'])) {
			return Ext_Gui2_Factory_Default::getDialog($oGui);
		}
		
        $mDialog = $aElement['dialog'];

		if (
			is_string($mDialog) &&
			class_exists($mDialog) &&
			is_subclass_of($mDialog, \Gui2\Dialog\LazyFactoryInterface::class)
		) {
			return new \Gui2\Dialog\LazyDialogProxy($mDialog);
		}

		$factory = new \Gui2\Factory\DialogFactory($mDialog);

		return $factory->create($oGui);
    }

    /**
	 * create an individual Icon
	 * @param Ext_Gui2 $oGui
	 * @param Ext_Gui2_Bar $oBar
	 * @param array $aElement
	 * @return Ext_Gui2_Bar_Icon 
	 */
	protected function _createIndividualIcon(Ext_Gui2 $oGui, Ext_Gui2_Bar $oBar, $aElement) {

		$sTitle = '';
		if(isset($aElement['title'])) {
			$sTitle = $this->t($aElement['title']);
		}
		
		$sTask = (string)$aElement['task'];
        
		if(!is_array($aElement['img'])){
			$sImg = (string)$aElement['img'];
		} else {
			$sImg = $this->_callMethod($aElement['img']);
		}
		
		$oElement = $oBar->createIcon($sImg, $sTask, $sTitle);

        if(isset($aElement['action'])) {
            $oElement->action = (string)$aElement['action'];
        }
        
        if(isset($aElement['access'])) {
            $oElement->access = (array)$aElement['access'];
        }
        
        if(isset($aElement['active'])){
            $oElement->active = (int)$aElement['active'];
        }
        
        if(isset($aElement['info_text'])){
            $oElement->info_text = (int)$aElement['info_text'];
        }
        
		if(!empty($aElement['dialog'])){
			$oDialog	= $this->_callIconDialog($aElement, $oGui);
			$oElement->dialog_data = $oDialog;
		}

		//Ticket #5755
        if(isset($aElement['confirm'])){
            $oElement->confirm = (boolean)$aElement['confirm'];
        }

        if(isset($aElement['confirm_message'])){
            $oElement->confirm_message = $this->t($aElement['confirm_message']);
        }
        
        if(isset($aElement['dbl_click_element'])){
            $oElement->dbl_click_element = (bool)$aElement['dbl_click_element'];
        }
		
		if(isset($aElement['id'])){
			$oElement->id = $aElement['id'];
		}
		
		return $oElement;
	}

    /**
     * Create a legend label
     * @param Ext_Gui2_Bar_Legend $oBar
     * @param array $aElement 
     */
    protected function _createLegendLabel(Ext_Gui2_Bar_Legend $oBar, $aElement){
        $sText = $this->t($aElement['label']);
        $oBar->addTitle($sText);
    }
    
    /**
     * Create a legenend Info
     * @param Ext_Gui2_Bar_Legend $oBar
     * @param array $aElement 
     */
    protected function _createLegendInfo(Ext_Gui2_Bar_Legend $oBar, $aElement) {

        $sText = $this->t($aElement['label']);
		$aColor = (array)($aElement['info']['color'] ?? []);
		$sColor = (string)($aColor[0] ?? '');
        
		$iDep = 100;
        
		if(isset($aColor[1])){
			$iDep	= (int)$aColor[1];
		}
        
		if(!empty($sColor)) {
			$sColor = Ext_Gui2_Util::getColor($sColor, $iDep);
		}
        
        $mExampleText = false;
        
        if(
            isset($aElement['info']['text']) && 
            (
                $aElement['info']['text'] === true || 
                $aElement['info']['text'] != ""
            )
        ){
            $mExampleText = $aElement['info']['text'];
            if(!empty($aElement['info']['text_l10n'])) {
                $mExampleText = $this->t($mExampleText);
            }
        } else if(isset ($aElement['info']['background'])) {
            $mExampleText = !(boolean)$aElement['info']['background'];
        }
        
        
    
		$bItalicText = (boolean)($aElement['info']['italic'] ?? false);
		
		if(!empty($aElement['info']['img'])) {
			if(!is_array($aElement['info']['img'])){
				$sImg = (string)$aElement['info']['img'];
			} else {
				$sImg = $this->_callMethod($aElement['info']['img']);
			}
		}	
		
        $oBar->addInfo($sText, $sColor, $mExampleText, $bItalicText, $sImg ?? null);
    }

    /**
	 * create a Labelgroup
	 * @param Ext_Gui2_Bar $oBar
	 * @param array $aElement
	 * @return Ext_Gui2_Bar_Labelgroup 
	 */
	protected function _createLabelGroup(Ext_Gui2_Bar $oBar, $aElement) {
		
		$sLabel		= (string)$aElement['label'];
		$sLabel		= $this->t($sLabel);
		$sStyle		= (string)($aElement['style'] ?? '');
		$oElement	= $oBar->createLabelGroup($sLabel, $sStyle);
        $oElement->id = (string)($aElement['id'] ?? '');
		if(isset($aElement['hide_empty'])) {
			$oElement->hide_empty = $aElement['hide_empty'];
		}
		$oElement->access = $aElement['access'] ?? null;

		return $oElement;
	}

	/**
	 * create a input Filter
	 * @param Ext_Gui2_Bar $oBar
	 * @param array $aElement
	 * @return Ext_Gui2_Bar_Timefilter 
	 */
	protected function _createInputFilter(Ext_Gui2_Bar $oBar, $aElement, $sType='input') {

		if (empty($aElement['label'])) {
			$aElement['label'] = 'Suche';
		}

		$oElement					= $oBar->createFilter();
		$oElement->filter_type		= $sType;
		$oElement->db_emptysearch	= 0;
		$oElement->db_operator		= (string)($aElement['searchtype'] ?? '');
		//$oElement->label			= (string)$this->t($aElement['label']);
		$oElement->db_column		= (array)$aElement['column'];
		$oElement->db_alias			= (array)$aElement['alias'];
		$oElement->access			= ($aElement['access'] ?? null);
		$oElement->placeholder		= (string)$this->t($aElement['label']).'…';

		if(!empty($aElement['id'])) {
			$oElement->id = (string)$aElement['id'];
		}
		
		if(isset($aElement['filter_part'])) {
			$oElement->filter_part = $aElement['filter_part'];
		}
		
		if(isset($aElement['filter_query'])) {
			$oElement->filter_query = $aElement['filter_query'];
		}

		if(isset($aElement['value'])) {
			$oElement->value = $aElement['value'];
		}

		return $oElement;
	}
	
	/**
	 * create a Select Filter
	 * @param Ext_Gui2 $oGui
	 * @param Ext_Gui2_Bar $oBar
	 * @param array $aElement
	 * @return Ext_Gui2_Bar_Timefilter 
	 */
	protected function _createSelectFilter(Ext_Gui2 $oGui, Ext_Gui2_Bar $oBar, $aElement){
		
		$oElement					= $oBar->createFilter();
		$oElement->filter_type		= "select";
		$oElement->db_emptysearch	= (int)($aElement['searchempty'] ?? 0);
		$oElement->db_operator		= (string)($aElement['searchtype'] ?? '');
		
		$mLabel						= ($aElement['label'] ?? '');

		if (is_array($mLabel)) {
			$sLabel = $this->_callMethod($mLabel);
		} else if(!empty($mLabel)) {
			$sLabel = $oGui->t($mLabel);
		} else {
			$sLabel = '';
		}

		$oElement->label			= $sLabel;
		$oElement->db_column		= (array)$aElement['column'];
		$oElement->db_alias			= (array)($aElement['alias'] ?? '');
		$oElement->access			= ($aElement['access'] ?? null);
		
		if(!empty($aElement['id'])) {
			$oElement->id               = (string)$aElement['id'];
		}

		// Selection/Select_Options setzen
		if(!empty($aElement['selection'])) {			
			if(class_exists($aElement['selection'])) {			
				$oSelection = new $aElement['selection']();			
				if($oSelection instanceof Ext_Gui2_View_Selection_Filter_Abstract) {
					$oElement->selection = $oSelection;
				}			
			}
		} elseif(isset($aElement['entries'])) {
			$oElement->select_options	= $this->_callMethod($aElement['entries'], $oGui);

			if(!empty($aElement['label_as_item'])) {
				$oElement->label = '';
				$oElement->select_options = Ext_Gui2_Util::addLabelItem($oElement->select_options, $sLabel);
			}
			if(!empty($aElement['label_as_null_item'])) {
				$oElement->label = '';
				$sValue = 'xNullx';
				$oElement->select_options = Ext_Gui2_Util::addLabelItem($oElement->select_options, $sLabel, $sValue);
			}
		}
		
		// Navigation setzen
		if(!empty($aElement['select_navigation'])){
			$oElement->select_navigation	= $this->_callMethod($aElement['select_navigation'], $oGui);
		}
		
		// Value setzen
		if(!empty($aElement['value'])){
			if(is_array($aElement['value']))
			{
				$mValue			= $this->_callMethod($aElement['value'], $oGui);
			}
			else
			{
				$mValue			= $aElement['value'];
			}
			$oElement->value	= $mValue;
		}
		
		if(!empty($aElement['id'])){
			$oElement->id				= $aElement['id'];
		}
		
		if(!empty($aElement['name'])){
			$oElement->name				= $aElement['name'];
		}
		
		if(!empty($aElement['skip_query'])){
			$oElement->skip_query		= $aElement['skip_query'];
		}
	
		if(isset($aElement['filter_part'])) {
			$oElement->filter_part = $aElement['filter_part'];
		}

		if(isset($aElement['dependency'])) {
			$oElement->dependency = $aElement['dependency'];
		}

		if(isset($aElement['visibility'])) {
			$oElement->visibility = $aElement['visibility'];
		}

		if(isset($aElement['multiple'])) {
			$oElement->multiple = $aElement['multiple'];
		}

		if(isset($aElement['size'])) {
			$oElement->size = $aElement['size'];
		}

		if(isset($aElement['filter_query'])) {

			// Prüfen, ob Array nummerisch ist mit zwei Werten (Methodenaufruf)
			if(
				is_array($aElement['filter_query']) &&
				count($aElement['filter_query']) === 2 &&
				isset($aElement['filter_query'][0]) &&
				$aElement['filter_query'][1]
			) {
				$oElement->filter_query = $this->_callMethod($aElement['filter_query']);
			} else {
				$oElement->filter_query	= $aElement['filter_query'];
			}
		}
		
		return $oElement;
	}
	
	/**
	 * create a Timefilter
	 * @param Ext_Gui2_Bar $oBar
	 * @param array $aElement
	 * @return Ext_Gui2_Bar_Timefilter 
	 */
	protected function _createTimefilter(Ext_Gui2_Bar $oBar, $aElement){
		
		$mDefaultFrom				= '';
		$mDefaultUntil				= '';
		
		if(isset($aElement['from']) && isset($aElement['from']['default']))
		{
			$mDefaultFrom			= $this->_callMethod($aElement['from']['default']);
		}
		
		if(isset($aElement['until']) && isset($aElement['until']['default']))
		{
			$mDefaultUntil			= $this->_callMethod($aElement['until']['default']);
		}
		
		if(isset($aElement['format']))
		{
			$oFormat					= $this->_callObject($aElement['format']);
		}

		$sTextAfter = '';
		if(!empty($aElement['text_after'])) {
			$sTextAfter = $this->t($aElement['text_after']);
		}

		$oElement	= $oBar->createTimeFilter($oFormat ?? null);
		$oElement->db_from_column	= (array)$aElement['from']['column'];
		$oElement->db_from_alias	= (array)($aElement['from']['alias'] ?? []);
		$oElement->db_until_column	= (array)$aElement['until']['column'];
		$oElement->db_until_alias	= (array)($aElement['until']['alias'] ?? []);
		$oElement->search_type		= (string)$aElement['searchtype'];
		$oElement->skip_query		= (boolean)($aElement['skip_query'] ?? false);

		$oElement->query_value_key = $aElement['query_value_key'] ?? null;

		if(empty($aElement['label'])) {
			$oElement->label = $this->t('Von');
		} else {
			$oElement->label = $this->t($aElement['label']);
		}
		
		if(empty($aElement['label_between'])) {
			$oElement->label_between = $this->t('bis');
		} else {
			$oElement->label_between = $this->t($aElement['label_between']);
		}
		
		$oElement->access			= $aElement['access'] ?? null;
		$oElement->default_from		= $mDefaultFrom;
		$oElement->default_until	= $mDefaultUntil;
		$oElement->text_after 		= $sTextAfter;

        if (isset($aElement['filter_part'])) {
            $oElement->filter_part = $aElement['filter_part'];
        }

		if(isset($aElement['use_coalesce'])) {
		    $oElement->use_coalesce = (bool)$aElement['use_coalesce'];
        }

        if(isset($aElement['data_function'])) {
            $oElement->data_function = (string)$aElement['data_function'];
        }

		if(!empty($aElement['id'])) {
			$oElement->id               = (string)$aElement['id'];
		}
	
		if(!empty($aElement['from_id'])) {
			$oElement->from_id = (string)$aElement['from_id'];
		}
	
		if(!empty($aElement['until_id'])) {
			$oElement->until_id = (string)$aElement['until_id'];
		}
	
		return $oElement;
	}

	protected function _createBasedOnFilter(Ext_Gui2 $oGui, Ext_Gui2_Bar $oBar, $aElement) {

		$oElement = new Ext_Gui2_Bar_Timefilter_BasedOn();
				
		$aSelectOptions = array();
			
		foreach($aElement as $sColumn => $aConfig) {
			
			$oElement->addColumn($sColumn, $aConfig);
			
			$sAlias = $oElement->getColumnConfig($sColumn, 'alias');			
			$sKey = Ext_Gui2_Data::setFieldIdentifier($sColumn, $sAlias);

			if(isset($aConfig['title'])) {
				$aColumn = ['title' => $aConfig['title']];
			} else {
				$aColumn = $this->_oConfig->getColumn($sColumn);
			}

			if(is_array($aColumn['title'])) {
				$aSelectOptions[$sKey] = $this->_callMethod($aColumn['title']);
			} else {
				$aSelectOptions[$sKey] = $this->t($aColumn['title']);
			}

		}
		
		$oElement->select_options = $aSelectOptions;
		
		return $oElement;
	}
	
	/**
	 * check if the third Bar is needed
	 * @return boolean 
	 */
	protected function _checkLastBar() {

		$bPagination = (boolean)$this->_oConfig->get('pagination');
		$bExport = (boolean)$this->_oConfig->get('export');

		$bLastBar = ($bPagination || $bExport);

		return $bLastBar;
	}

	/**
	 * adds the Pagination, Export an Loading Indicator to the Bar
	 * @param Ext_Gui2 $oGui 
	 * @param Ext_Gui2_Bar $oBar 
	 */
	protected function _addLastBarElements(Ext_Gui2 $oGui, Ext_Gui2_Bar $oBar) {
		
		$bPagination = (boolean)$this->_oConfig->get('pagination');
		$bOnlyPageCount = (boolean)$this->_oConfig->get('only_pagecount');
		$bLimitSelection = (boolean)$this->_oConfig->get('limit_selection');

		$bExport = (boolean)$this->_oConfig->get('export');
		
		$iPosition = count($oBar->getElements());

		if($bPagination) {
			$oPagination = $oBar->createPagination($bOnlyPageCount, $bLimitSelection);
			$oBar->setElement($oPagination, $iPosition++);
		}
		
		if($bExport) {
			
			// Import-Icon
			if(in_array(Tc\Traits\Gui2\Import::class, class_uses($oGui->getDataObject()))) {
				
				$oLabelGroup = $this->_createLabelGroup($oBar, array('label' => $this->t('Import/Export')));
				
				$oImportIcon = new Ext_Gui2_Bar_Icon('fa-upload', 'request', $oGui->t('Import'));
				$oImportIcon->action = 'import';
				$oImportIcon->label = $oGui->t('Import');
				$oImportIcon->multipleId 	= 1;
				$oImportIcon->active = 1;
				$oImportIcon->visible = 1;
				$oImportIcon->info_text = 0;
				
			} else {
				
				$oLabelGroup = $this->_createLabelGroup($oBar, array('label' => $this->t('Export')));
				
			}
			
			$oBar->setElement($oLabelGroup, $iPosition++);
			$oCsvExport = $oBar->createCSVExport();
			$oBar->setElement($oCsvExport, $iPosition++);
			$oExcelExport = $oBar->createExcelExport();
			$oBar->setElement($oExcelExport, $iPosition++);

			if(isset($oImportIcon)) {
				$oBar->setElement($oImportIcon, $iPosition++);
			}
			
		}

		// Immer ans Ende
		$oLoading = $oBar->createLoadingIndicator();
		$oBar->setElement($oLoading);

	}
    
    /**
     * get the icon for refreshing index
     * @param Ext_Gui2 $oGui
     * @param Ext_Gui2_Bar $oBar
     * @return Ext_Gui2_Bar_Icon 
     */
//    protected function _getIndexRefreshIcon(Ext_Gui2 $oGui, Ext_Gui2_Bar $oBar){
//                
//        $oLabelgroup = $this->_createLabelGroup($oBar, array('label' => 'Index'));
//        $oBar->setElement($oLabelgroup);
//        
//        $oIcon = $oBar->createIcon(Ext_Gui2_Util::getIcon('error'), 'openDialog');
//        $oIcon->action = 'executeIndexStack';
//        $oIcon->label = $this->t('Aktualisieren');
//        
//        $oDialog = $oGui->createDialog($this->t('Index aktualisieren'), $this->t('Index aktualisieren'));
//        $oDialog->height = '150';
//        $oDialog->width = '425';
//        $oDialog->bReadOnly = true;
//        
//        $sIndex = $this->_oConfig->get(array('index', 'name'));
//
//        $oStacks    = Ext_Gui2_Index_Stack::getDbCollection($sIndex);
//        $iTotal     = count($oStacks);
//        
//        $oDiv       = $this->getIndexRefreshDiv($sIndex, 0, $iTotal);
//        $oDialog->setElement($oDiv);
//        
//        $oIcon->dialog_data = $oDialog;
//        $oIcon->title = $oIcon->label;
//        $oIcon->active = 1;
//        
//        return $oIcon;
//
//    }
    
    /**
     * create the index message div
     * @param string $sIndex
     * @param int $iCompleted
     * @param int $iTotal
     * @return \Ext_Gui2_Html_Div 
     */
    public function getIndexRefreshDiv($sIndex, $iCompleted, $iTotal){
    
		$sInfoText = $this->t('Datensätze {completed} von {total} aktualisiert.');
		
		$oDiv = Ext_Gui2_Html::getIndexRefreshDiv($sIndex, $iCompleted, $iTotal, $sInfoText);
		
		return $oDiv;


    }

    /**
     * check if the index exists
     * if not create and fill stack
     * @param Ext_Gui2 $oGui 
     */
    protected function _prepareIndex(Ext_Gui2 $oGui){

		if(
           $oGui->checkWDSearch()
        ){
            $sConfig    = $this->_oConfig->get(array('index', 'name'));
            $sIndexName = Ext_Gui2_Index_Generator::createIndexName($sConfig);

            $oIndex     = new \ElasticaAdapter\Adapter\Index($sIndexName);
            $bExist     = $oIndex->exists();

            if(!$bExist){
                // Suchindizierung wird gestartet
                $oGenerator = new Ext_Gui2_Index_Generator($sConfig);
                $oGenerator->createIndexNewAndAddStack();
            }
        }
    }

    /**
     * check if the index has stack entries
     * @return boolean 
     */
    protected function _checkIndexChanges(Ext_Gui2 $oGui){
		
		// Falls Index nicht vorhanden, neu aufbauen
		$this->_prepareIndex($oGui);
		
        $sIndex = $this->_oConfig->get(array('index', 'name'));
		
		if($sIndex != "default"){            
			$aStack = Ext_Gui2_Index_Stack::getFromDB($sIndex, 1);
            $aStack = reset($aStack);
            if(!empty($aStack)){
                return true;
            }
        }
        return false;
    }

     /**
	 * create the Gui 2 Object
	 * @return Ext_Gui2 
	 */
	protected function _createGuiObject(Ext_Gui2 $oParentGui=null, $aGuiOptions = array()) {

		$sGuiClass			= (string)$this->_oConfig->get(array('class', 'gui'));
		$sDataClass			= (string)$this->_oConfig->get(array('class', 'data'));
		$sDateFormatClass	= (string)$this->_oConfig->get(array('class', 'date_format'));
		$sJsClass           = (string)$this->_oConfig->get(array('class', 'js'));
		$sWDBasic			= (string)$this->_oConfig->get(array('class', 'wdbasic'));
		$sIconActiveClass	= (string)$this->_oConfig->get(array('class', 'icon_status'));
		$sIconVisibleClass	= (string)$this->_oConfig->get(array('class', 'icon_visible'));
		$sRowStyleClass		= (string)$this->_oConfig->get(array('class', 'row_style'));
		#$sRowStyleIndex		= (string)$this->_oConfig->get(array('class', 'row_style_index'));
		$bMultipleSelection = (boolean)$this->_oConfig->get('multiple_selection');
		$sHash				= (string)$this->_oConfig->get('hash');
		$mAccess			= $this->_oConfig->get('access');
		$iLimit				= (int)$this->_oConfig->get('limit');
		$aWhere				= (array)$this->_oConfig->get('where');
		$aI18NLanguages = (array)$this->_oConfig->get('i18n_languages');
		$aOrderby			= (array)$this->_oConfig->get('orderby');
		$sIdColumn			= (string)$this->_oConfig->get(array( 'id', 'column'));
		$sIdAlias			= (string)$this->_oConfig->get(array( 'id', 'alias'));
		$bSortable			= (boolean)$this->_oConfig->get('sortable');
		$bRowsClickable = (boolean)$this->_oConfig->get('rows_clickable');
		$bLeftframe			= (boolean)$this->_oConfig->get('leftframe');
		$sForeignKey		= (string)$this->_oConfig->get('foreign_key');
		$sForeignKeyAlias	= (string)$this->_oConfig->get('foreign_key_alias');
		$sParentPrimaryKey  = (string)$this->_oConfig->get('parent_primary_key');
		$bDecodeParentPrimaryKey = (bool)$this->_oConfig->get('decode_parent_primary_key');
		$sEncodeDataIdField	= (string)$this->_oConfig->get('encode_data_id_field');
		$aEncodeData		= (array)$this->_oConfig->get('encode_data');
		$bLoadAdminHeader	= (boolean)$this->_oConfig->get('load_admin_header');
		$sView				= (string)$this->_oConfig->get('view');

		$oMultipleCheckbox		= false;
		
		$sMultipleCheckboxClass = $this->_oConfig->get('row_multiple_checkbox');
		
		if($sMultipleCheckboxClass){
			$oMultipleCheckbox = new $sMultipleCheckboxClass();
		}

		if(empty($sHash)) {
            $sIndexName = (string)$this->_oConfig->get(array('index', 'name'));
            if(
               empty($sIndexName) || 
               $sIndexName == 'default'
            ) {
                $sIndexName = $this->_oConfig->getName();
            }
			$sHash = md5($sIndexName);
		}

		// Wenn hier eine Kind-GUI erzeugt wird
		if($oParentGui !== null) {
			$GLOBALS['gui2_instance_hash'] = $oParentGui->instance_hash;
		}

		$oGui = new $sGuiClass($sHash, $this->_callClass($sDataClass));
		/* @var $oGui Ext_Gui2 */

		// Wenn hier eine Kind-GUI erzeugt wird
		if($oParentGui !== null) {
			$oGui->setParent($oParentGui);
		}

		if(!empty($aGuiOptions)){
			foreach($aGuiOptions as $sField => $mValue){
				$oGui->setOption($sField, $mValue);
			}
		}
		
		$sDescription					= $this->_createDescription();
		if(!empty($sDescription)){
			$oGui->gui_description      = $sDescription;
		} elseif($oParentGui !== null) {
			$oGui->gui_description = $oParentGui->gui_description;
		}
        
		$sTitle							= $this->_createTitle();
		if(!empty($sTitle)){
			$oGui->gui_title			= $sTitle;
		}

		if(
			!empty($sRowStyleClass) &&
			class_exists($sRowStyleClass)
		) {
			$oGui->row_style				= new $sRowStyleClass();
		}

		$oGui->name						= $this->_oConfig->getName(false);
		$oGui->set						= $this->_sSet;
		$oGui->access					= $mAccess;
		$oGui->calendar_format			= new $sDateFormatClass();
		$oGui->row_icon_status_active	= new $sIconActiveClass();
		$oGui->row_icon_status_visible  = new $sIconVisibleClass();
		$oGui->multiple_selection		= $bMultipleSelection;
		$oGui->multiple_selection_lock = (bool)$this->_oConfig->get('multiple_selection_lock');
		$oGui->encode_data_unset_empty = (bool)$this->_oConfig->get('encode_data_unset_empty');
		$oGui->sum_row_columns = (array)$this->_oConfig->get('sum_row_columns');

        $oGui->query_id_column          = $sIdColumn;
        $oGui->query_id_alias           = $sIdAlias;
        $oGui->class_js                 = $sJsClass;
        $oGui->include_jquery           = 1;
        $oGui->include_jquery_multiselect = 1;
		$oGui->include_bootstrap_tagsinput = (bool)$this->_oConfig->get('include_tagsinput');
        $oGui->row_sortable             = $bSortable;
		$oGui->rows_clickable = $bRowsClickable;
		$oGui->foreign_key				= $sForeignKey;
        $oGui->foreign_key_alias        = $sForeignKeyAlias;
		$oGui->parent_primary_key       = $sParentPrimaryKey;
		$oGui->decode_parent_primary_key = $bDecodeParentPrimaryKey;
		if (!empty($sEncodeDataIdField)) {
			// TODO Wird dieses Feld überhaupt benutzt
			$oGui->encode_data_id_field = $sEncodeDataIdField;			
		}
		$oGui->encode_data				= $aEncodeData;
		$oGui->row_multiple_checkbox	= $oMultipleCheckbox;
		$oGui->load_admin_header		= $bLoadAdminHeader;
		$oGui->sView					= $sView;
		$oGui->created_by_config = true;
		$oGui->sidebar = (bool)$this->_oConfig->get('sidebar');
		$oGui->api = true;
		if($this->_oConfig->get('api') === false) {
			$oGui->api = false;
		}
		$oGui->setWDBasic(Factory::getClassName($sWDBasic));
		$oGui->setTableData('limit', $iLimit);
		$oGui->setTableData('where', Arr::isAssoc($aWhere) ? $aWhere : $this->_callMethod($aWhere, $oGui));
		$oGui->setTableData('orderby', Arr::isAssoc($aOrderby) ? $aOrderby : $this->_callMethod($aOrderby, $oGui));

		if(!empty($aI18NLanguages)) {
			$oGui->i18n_languages =  $this->_callMethod($aI18NLanguages, $oGui);
		}
		
		foreach((array)$this->_oConfig->get('options') as $sOption => $mValue) {
			$oGui->setOption($sOption, $mValue);
		}

		if($bLeftframe === false){
			$oGui->showLeftFrame = false;
		}
		
		$this->_addIndexOptions($oGui);

		$oGui->origin = $this->getConfigFileName();

		return $oGui;
	}
    
    /**
     * add index configuartions to the gui object if the Index are defined
     * @param Ext_Gui2 $oGui 
     */
    protected function _addIndexOptions(Ext_Gui2 $oGui){
        $sIndex = $this->_oConfig->get(array('index', 'name'));
        if($sIndex != "default"){
            $oGui->wdsearch                 = true;
            $oGui->wdsearch_auto_refresh    = false;
            $oGui->wdsearch_required        = true;
            $oGui->wdsearch_index           = $sIndex;
            $oGui->config_file				= $this->_oConfig->getName();
            $oGui->wdsearch_use_stack       = true;
        }
    }

     /**
	 * create the Description of the GUI
	 * @return string 
	 */
	protected function _createDescription(){
		$mDescription	= $this->_oConfig->get('description');

		if(is_array($mDescription)){
			$mDescription	= $this->_callMethod($mDescription);
		}
		
		return $mDescription;
	}
	
	/**
	 * create the Title of the GUI
	 * @return string 
	 */
	protected function _createTitle(){
		$mTitle	= $this->_oConfig->get('title');
		
		if(empty($mTitle)){
			return '';
		}

		if(is_array($mTitle)){
			$mTitle			= $this->_callMethod($mTitle);
		} else {
			$mTitle			= $this->t($mTitle);
		}

		return $mTitle;
	}
	
	/**
	 * @see Ext_Gui2_Config_Parser::callMethod
	 * @param array $aMethodData
	 * @param mixed $mOptionalParameter
	 * @return mixed
	 */
	protected function _callMethod($aMethodData, $mOptionalParameter = null){
		return $this->_oConfig->callMethod($aMethodData, $mOptionalParameter);
	}
	
	/**
	 * @see Ext_Gui2_Config_Parser::callObject
     * @param string $sClass
     * @param array $aParams
     * @return object $sClass
     */
	protected function _callObject($sClass, $aParams = array()){
		return $this->_oConfig->callObject($sClass, $aParams);
	}

	/**
	 * @see Ext_Gui2_Config_Parser::callClass
	 * @param string $sClass
	 * @return string
	 */
	protected function _callClass($sClass){
		return $this->_oConfig->callClass($sClass);
	}
}
