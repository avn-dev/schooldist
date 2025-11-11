<?php

/**
 * Class Ext_TC_Gui2_Filterset_Bar_Element
 */
class Ext_TC_Gui2_Filterset_Bar_Element extends Ext_TC_Basic {

	const TC_GUI2_FILTERSET_FLEX_FIELDS = 'TC_GUI2_FILTERSET_FLEX_FIELDS';
	
	/**
	 * Tabellennamen
	 * @var string
	 */
    protected $_sTable = 'tc_gui2_filtersets_bars_elements';

	/**
	 * Tabellen Alias
	 * @var string
	 */
    protected $_sTableAlias = 'tc_gfbe';

	/**
	 * @var array
	 */
    protected $_aJoinTables = array(
        'i18n' => array(
            'table' => 'tc_gui2_filtersets_bars_elements_i18n',
            'primary_key_field' => 'element_id',
            'autoload' => true,
            'cloneable' => true,
            'i18n' => true
        ),
        'basedon' => array(
            'table' => 'tc_gui2_filtersets_bars_elements_basedon',
            'primary_key_field' => 'element_id',
            'foreign_key_field' => 'base_on',
            'autoload' => true,
            'cloneable' => true,
            'sort_column' => 'position',
        )
    );
    protected $_aAttributes = array(
        'timefilter_from_count' => array(
            'class' => 'WDBasic_Attribute_Type_Int'
        ),
        'timefilter_until_count' => array(
            'class' => 'WDBasic_Attribute_Type_Int'
        ),
        'timefilter_from_type' => array(
            'class' => 'WDBasic_Attribute_Type_Varchar'
        ),
        'timefilter_until_type' => array(
            'class' => 'WDBasic_Attribute_Type_Varchar'
        ),
    );

    /**
     * @var Ext_Gui2_Config_Parser
     */
    protected $_oParser;

	/**
	 * @var Ext_Gui2_Factory
	 */
	protected $oFactory;
	
	/**
	 * @var Ext_Gui2
	 */
	protected $oGui;
	
    /**
     * "Basierend auf" Daten cachen
     * 
     * @var array 
     */
    protected $_aBasedOnData = array();

	/**
	 * @inheritdoc
	 */
	public function validate($bThrowExceptions = false) {

		$mValidate = parent::validate($bThrowExceptions);

		// Mehr als ein Feld macht keinen Sinn
		if(
			$this->type === 'select' &&
			count($this->basedon) > 1
		) {
			$mValidate = [];
			$mValidate['basedon'][] = 'TOO_MANY';
		}

		return $mValidate;

	}

	/**
     * get the Basic Dialog for this WDBasic
     * @param Ext_TC_Gui2 $oGui
     * @return Ext_Gui2_Dialog
     */
    public static function getDialog(Ext_TC_Gui2 $oGui) {

        $oDialog = $oGui->createDialog($oGui->t('Element bearbeiten'), $oGui->t('Neues Element'));

        $oDialog->setElement($oDialog->createI18NRow($oGui->t('Label'), array(
                    'db_alias' => 'i18n',
                    'db_column' => 'label',
                    'i18n_parent_column' => 'element_id',
                    'required' => true
                        )));

        $oRow = $oDialog->createRow($oGui->t('Label anzeigen'), 'select', array(
            'db_column' => 'display_label',
            'db_alias' => 'tc_gfbe',
            'select_options' => Ext_TC_Util::getYesNoArray()
		));
        $oDialog->setElement($oRow);

        $aTypes = self::getTypes();
        $oRow = $oDialog->createRow($oGui->t('Art'), 'select', array(
            'db_column' => 'type',
            'db_alias' => 'tc_gfbe',
            'select_options' => $aTypes,
            'required' => true
                )
        );
        $oDialog->setElement($oRow);

		// Für unten, damit bei Labelgroup "Bezogen auf" ausgeblendet wird.
		unset($aTypes['labelgroup']);

        $oRow = $oDialog->createRow($oGui->t('Bezogen auf'), 'select', array(
			'db_column' => 'basedon',
			'select_options' => array(),
			'selection' => new Ext_TC_Gui2_Filterset_Bar_Element_Selection_Basedon(),
			'required' => false,
			'multiple' => 5,
			'jquery_multiple' => 1,
			'searchable' => 1,
			'sortable' => 1,
			'dependency' => array(
				array(
					'db_column' => 'type',
					'db_alias' => 'tc_gfbe'
				)
			),
			'dependency_visibility' => array(
				'db_column' => 'type',
				'db_alias' => 'tc_gfbe',
				'on_values' => array_keys($aTypes)
			)
		));

        $oDialog->setElement($oRow);

        $aTimeTypes = self::getTimeTypes();

        $oRow = $oDialog->createMultiRow($oGui->t('Standardwert "Von" Feld'), array('items' =>
            array(
                array(
                    'input' => 'input',
                    'db_column' => 'timefilter_from_count',
                    'db_alias' => 'tc_gfbe',
                    'style' => 'width: 50px',
					'format' => new \Ext_Gui2_View_Format_Null(),
                    'dependency_visibility' => array(
                        'db_column' => 'type',
                        'db_alias' => 'tc_gfbe',
                        'on_values' => array(
                            'date'
                        )
                    )
                ),
                array(
                    'input' => 'select',
                    'db_column' => 'timefilter_from_type',
                    'db_alias' => 'tc_gfbe',
                    'select_options' => $aTimeTypes,
                    'text_after' => L10N::t('vor aktuellem Datum'),
                    'style' => 'width: 100px'
                )
            )
                )
        );
        $oDialog->setElement($oRow);

        $oRow = $oDialog->createMultiRow($oGui->t('Standardwert "Bis" Feld'), array('items' =>
            array(
                array(
                    'input' => 'input',
                    'db_column' => 'timefilter_until_count',
                    'db_alias' => 'tc_gfbe',
                    'style' => 'width: 50px',
					'format' => new \Ext_Gui2_View_Format_Null(),
                    'dependency_visibility' => array(
                        'db_column' => 'type',
                        'db_alias' => 'tc_gfbe',
                        'on_values' => array(
                            'date'
                        )
                    )
                ),
                array(
                    'input' => 'select',
                    'db_column' => 'timefilter_until_type',
                    'db_alias' => 'tc_gfbe',
                    'select_options' => $aTimeTypes,
                    'text_after' => L10N::t('nach aktuellem Datum'),
                    'style' => 'width: 100px'
                )
            )
                )
        );
        $oDialog->setElement($oRow);

        return $oDialog;
    }

    public static function getTimeTypes() {
        $aTypes = array(
            'day' => L10N::t('Tage'),
            'week' => L10N::t('Wochen'),
            'month' => L10N::t('Monate')
        );
        return $aTypes;
    }

    /**
     * get all filter types
     * @return array
     */
    public static function getTypes() {

        $aTypes = array(
            'labelgroup' => L10N::t('Labelgruppe'),
            //'spacer'    => L10N::t('Seperator'), i18n ist pflicht und hier nicht benötigt daher erstmal rausgenommen
            'date' => L10N::t('Zeitfilter'),
            'input' => L10N::t('Suchfeld'),
            'select' => L10N::t('Selectfilter')
        );

        return $aTypes;
    }

    public function getName() {
        return $this->getI18NName('i18n', 'label');
    }

    /**
     * get all columns from the saved basedon
     * @return type 
     */
    public function getColumns($sSet = '') {
		$aConfigs = $this->_getConfigData();
		$aColumns = array();

        foreach ($aConfigs as $aConfig) {

            if (!empty($sSet) && !empty($aConfig['set'])) {
                $aSet = (array) $aConfig['set'];

                if (!in_array($sSet, $aSet)) {
                    continue;
                }
            }

			/*
			 * Es muss column durchsucht werden, da data normal keine Spalte 
			 * wiederspiegelt, sondern ein Wert aus dem SELECT-Teil des Queries
			 */
            if (empty($aConfig['column'])) {
                $sColumn = (string) $aConfig['data'];
            } else {
                $sColumn = (string) $aConfig['column'];
            }
            
            $aColumns[] = $sColumn;
        }

        return $aColumns;
    }

    public function checkAccess() {
		
        $aConfigs = $this->_getConfigData();
        $aConfig = reset($aConfigs);
		
		$mAccess = null;
		if(isset($aConfig['access'])) {
			$mAccess = $aConfig['access'];
		}
		
        if (!empty($mAccess)) {
            $oAccess = Access::getInstance();
            return $oAccess->hasRight($mAccess); 
        }
        return true;
    }

    /**
     * get all allias from the saved basedon
     * @return array 
     */
    public function getAlias() {
        $aColumns = array();
        $aBasedOn = (array) $this->basedon;
        foreach ($aBasedOn as $sBasedon) {
            $aTemp = explode('.', $sBasedon);
            if (count($aTemp) > 1) {
                $aColumns[] = reset($aTemp);
            }
        }

        return $aColumns;
    }

    /**
     * create the gui bar elements
     * @param Ext_TC_Gui2_Bar $oBar
     * @return Ext_Gui2_Bar_Filter 
     */
    public function setGuiFilterElement(Ext_TC_Gui2_Bar $oBar, $sSet = '') {

		$this->oGui = $oBar->getGui();
		
        if (!$this->checkAccess()) {
            return null;
        }

        $aDBColumn = (array) $this->getColumns($sSet);
        $aDBAlias = (array) $this->getAlias();

        $sLabel = $this->getI18NName('i18n', 'label');

		// Fallback, falls Label nicht in der Sprache verfügbar ist
		if(empty($sLabel)) {
			$sLabel = $this->getI18NName('i18n', 'label', 'en');
		}
		
        switch ($this->type) {
            case 'labelgroup':
                $oFilter = $oBar->createLabelGroup($sLabel);
                break;
            case 'spacer':
                $oFilter = $oBar->createSeperator();
                break;
            case 'input':
            case 'checkbox':
            case 'select':

                if (empty($aDBColumn)) {
                    return false;
                }

                $oFilter = $oBar->createFilter($this->type);
                $oFilter->db_operator = 'like';
                $oFilter->value = '';
                
                if ($this->type == 'select') {

					$sColumn = reset($aDBColumn);
					
					if(
						strpos($sColumn, 'flex_') === 0 &&	
						!$this->oGui->checkWDSearch()
					) {
						
						$iFlexFieldId = str_replace(['flex_', '_original'], '', $sColumn);
						
						$oFlexField = Ext_TC_Flexibility::getInstance($iFlexFieldId);

						foreach($this->oGui->getFlexSections() as $aSectionConfig) {
							[$section, $usage] = \Illuminate\Support\Arr::wrap($aSectionConfig['section']);

							if(
								$oFlexField->getSection()->type == $section &&
								(empty($usage) || $oFlexField->usage === $usage)
							) {
								$this->oGui->addFlexJoin($oFilter->id, $iFlexFieldId, $aSectionConfig['primary_key'], $aSectionConfig['primary_key_alias']);
							}
						}

						$aDBColumn = ['value'];
						$aDBAlias = ['flex_'.$iFlexFieldId];
					}
					
                    $mDefaultValue = $this->getDefaultValue($aDBColumn);

                    $oFilter->db_operator = '=';
                    $aSelectOptions = $this->getSelectOptions($oBar->_oGui);
                    $oFilter->select_options = $aSelectOptions;
                    $oFilter->value = $mDefaultValue;
                    $oFilter->db_emptysearch = 1;
                }

                $oFilter->db_alias = $aDBAlias;
                $oFilter->db_column = $aDBColumn;
                
                if($this->display_label == 1) {
                    $oFilter->label = $sLabel;
                } elseif($this->type == 'input') {
					$oFilter->placeholder = $sLabel;
				}

                if ($this->_isHaving()) {
                    $oFilter->filter_part = 'having';
                    $oFilter->db_alias = '';
                }
                
                $aFilterQuery = $this->_getFilterQuery();
                if (!empty($aFilterQuery)) {
                    $oFilter->filter_query = $aFilterQuery;
                }

                $aFilterQuery = $this->_getFilterWDSearchQuery();
   
                if (!empty($aFilterQuery)) {
                    $oFilter->filter_wdsearch = $aFilterQuery;
                }

                $oFilter->setDesignElement($this);

                break;
            case 'date':

                if (empty($aDBColumn)) {
                    return false;
                }

                $oFormat = Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date');

                $oFilter = $oBar->createTimeFilter($oFormat);

                $oFilter->setDesignElement($this);
                
                if ($this->display_label == 1) {
                    $oFilter->label = $sLabel;
                }

                if (count($aDBColumn) > 1) {

                    $oFilterSelect = new Ext_Gui2_Bar_Timefilter_BasedOn();

                    $oFilter->addBasedOnFilter($oFilterSelect);

                    $oBar->setElement($oFilter);

                    $oBar->setElement($oFilterSelect);

                    return true; // nict weiter gehen damit nicht 2 mal gesetzt wird!
                }

                break;
            default:
                break;
        }

        $oBar->setElement($oFilter);
        return true;
    }

    /**
     * get the Default From date
     * @param boolean $bFormated
     * @return string 
     */
    public function getDefaultFilterFrom($bFormated = false) {
        return $this->_calculateFilterDefault('from', $bFormated);
    }

    /**
     * get the Default Until date
     * @param boolean $bFormated
     * @return string 
     */
    public function getDefaultFilterUntil($bFormated = false) {
        return $this->_calculateFilterDefault('until', $bFormated);
    }

    /**
     * get the Default From or Until date
     * @param boolean $bFormated
     * @return string 
     */
    protected function _calculateFilterDefault($sType = 'from', $bFormated = false) {

        $sFieldCount = 'timefilter_' . $sType . '_count'; // timefilter_from_count, timefilter_until_count
        $sFieldType = 'timefilter_' . $sType . '_type'; // [timefilter_from_type, [timefilter_until_type

        $iCount = $this->$sFieldCount;

        if(is_null($iCount)) {
			return '';
		}

        $iCount = (int) $iCount;

        $sTimeType = (string) $this->$sFieldType;
        if (empty($sTimeType)) {
            $sTimeType = 'day';
        }
        $sTimeType = $this->_getWDDateType($sTimeType);


        $oDate = new WDDate();

        if ($sType == 'from') {
            $oDate->sub($iCount, $sTimeType);
        } else {
            $oDate->add($iCount, $sTimeType);
        }

        $sDate = $oDate->get(WDDate::DB_DATE);

        if ($bFormated) {
            $oFormat = Ext_TC_Factory::getObject('Ext_TC_Gui2_Format_Date');
            $sDate = $oFormat->formatByValue($sDate);
        }

        return $sDate;
    }

    protected function _getWDDateType($sType) {
        switch ($sType) {
            case 'day':
                return WDDate::DAY;
                break;
            case 'week':
                return WDDate::WEEK;
                break;
            case 'month':
                return WDDate::MONTH;
                break;
        }
    }

	/**
	 * get All Based On for the Application of the Filter
	 *
	 * @param bool $bOnlySaved
	 * @return array
	 * @throws ErrorException
	 */
    public function getAllBasedOn($bOnlySaved = false) {

        $aBasedOnData = $this->_aBasedOnData;
        $sCacheKey = 'cache_' . (int) $bOnlySaved;

        if (!isset($aBasedOnData[$sCacheKey])) {

            $aBasedOn = array();
            $sType = $this->type;
            $oParser = $this->_getParser();

            if ($oParser) {

                $aColumns = $oParser->getColumns(false);
                $aSavedBasedOn = array();
                
                // Beim normalen auslesen dürfen die gespeicherten nicht beachtet werden!
                // ansonsten klappt die Selection im Filterset nicht da wenn man den Typ ändern dann ggf. Felder ausgewählt hat die es nicht mehr geben darf
                if($bOnlySaved) {

                    $aSavedBasedOn = (array) $this->basedon;
                    if(!empty($aSavedBasedOn)) {
                        // die Werte in die Schlüssen packen
                        $aSavedBasedOn = array_combine($aSavedBasedOn, $aSavedBasedOn);
                    }

                }

                foreach ($aColumns as $aColumn) {
                    if (!empty($aColumn['filterset'])) {

                        if(!empty($aColumn['access'])) {
							if (
								is_array($aColumn['access']) &&
								class_exists($aColumn['access'][0])
							) {
								$bAccess = $this->getFactory()->_checkAccess($aColumn['access']);
							} else {
								// Auf Lizenzrecht prüfen, da dies nicht über den Benutzer passieren darf (sonst werden ggf. Optionen gelöscht)
								if (Ext_TC_Util::getSystem() === 'school') {
									if ($aColumn['access'] === 'all_school') {
										$bAccess = true;
									} else {
										$bAccess = Ext_Thebing_Access::hasLicenceRight($aColumn['access']);
									}
								} else {
									$bAccess = Ext_TC_Access::hasRight($aColumn['access'][0], $aColumn['access'][1]);
								}
							}

							if(!$bAccess) {
								continue;
							}
                            
                        }

                        if ($aColumn['filterset']['type'] == $sType) {
							
							$bCheck = true;
							
							if(isset($aColumn['filterset']['check']))
							{
								$aCheck = (array)$aColumn['filterset']['check'];
								
								$bCheck = $oParser->callMethod($aCheck);
							}
							
							if($bCheck)
							{
								$sColumn = $this->_createColumnKey($aColumn);

								if (
									!$bOnlySaved ||
									(
									$bOnlySaved &&
									isset($aSavedBasedOn[$sColumn])
									)
								) {
									if(!empty($aColumn['filterset']['title'])) {
										$sTitle = $aColumn['filterset']['title'];
									} else {
										$sTitle = $aColumn['title'];
									}

									if(is_array($sTitle)) {
										$sTitle = $oParser->callMethod($sTitle);
									} else {
										$sTitle = L10N::t($sTitle);
									}

									$aBasedOn[$sColumn] = $sTitle;

									// In die gespeicherten Werte die Beschreibungen hinzufügen, da wir unten array_merge ausführen wollen
									$aSavedBasedOn[$sColumn] = $sTitle;
								}
							}
 
                        }
                    }
                }
				
                if($bOnlySaved){
                    // Mergen, damit die Reihenfolge der gespeicherten auf jeden Fall enthalten bleibt
                    $aBasedOn = array_merge($aSavedBasedOn, $aBasedOn);
                }
            } else {
                // Wenn keine Configdatei vorhanden kann hier manuell was definiert werden
            }

			// Flex-Felder
			if($sType === 'select') {
				$aFlexColumns = $this->getFlexColumns(Ext_TC_Flexibility::TYPE_SELECT);
				if(!empty($aFlexColumns)) {
					foreach($aFlexColumns as $iFlexFieldId=>$sFlexFieldLabel) {
						$aBasedOn['flex_'.$iFlexFieldId.'_original'] = $sFlexFieldLabel;
					}
				}
			}

            $this->_aBasedOnData[$sCacheKey] = $aBasedOn;
        }

        return $this->_aBasedOnData[$sCacheKey];
    }

	protected function getFlexColumns(int $iType) {

		$oBar = Ext_TC_Gui2_Filterset_Bar::getInstance($this->bar_id);
        $oFilterSet = Ext_TC_Gui2_Filterset::getInstance($oBar->set_id);

        $sApplication = $oFilterSet->application;
			
		$sCacheKey = __METHOD__.'_'.$sApplication.'_'.$iType;

		$aFlexColumns = WDCache::get($sCacheKey);

		if($aFlexColumns === null) {

			$aFlexColumns = [];

			if($this->oGui === null) {
				$oGenerator = $this->getFactory();
				$this->oGui = $oGenerator->createGui();
			}
			
			$oGui = $this->oGui;
				
			$aColumnList = [];
			$oGui->getDataObject()->prepareColumnListByRef($aColumnList);

			foreach($aColumnList as $oColumn) {
				$aMatch = [];
				if(preg_match('/flex_([0-9]+)/', $oColumn->db_column, $aMatch) === 1) {
					$oFlexField = Ext_TC_Flexibility::getInstance($aMatch[1]);
					if(
						$oFlexField->type == $iType &&
						$oFlexField->visible
					) {
						$aFlexColumns[$oFlexField->id] = $oFlexField->getName();
					}
				}
			}

			WDCache::set($sCacheKey, (60*60*24), $aFlexColumns, false, self::TC_GUI2_FILTERSET_FLEX_FIELDS);

		}
		
		return $aFlexColumns;
	}
	
	protected function _createColumnKey(&$aColumn) {

    	// Ganzen Rest rausgenommen, da die Originalspalte mittlerweile existiert
		// Zudem hat das mit Spalten auf I18N nie richtig funktioniert, da die Keys falsch abgespeichert wurden (nur ein Feld bei TA)
    	if(
			!empty($aColumn['index']['add_original']) &&
			// Workaround für columns mit _original im Key, die aber add_original haben (das muss in den YMLs korrigiert werden)
			strpos($aColumn['_column'], '_original') === false
		) {
			$aColumn['column'] = $aColumn['_column'].'_original';
		}

		// Bei sprachspalten sprache löschen da der filter immer auf die eingelogte sprache zielen wird
//		if ($aColumn['language']) {
//			$aColumn['column'] = str_replace('_'.$aColumn['language'], '', $aColumn['column']);
//		}
//
//		if (
//				$aColumn['index']['add_original'] &&
//				(
//				mb_strpos($aColumn['column'], 'original') === false
//				)
//		) {
//			$aColumn['column'] .= '_original';
//		}
//
//		if ($aColumn['language']) {
//			$aColumn['column'] = $aColumn['column'].'_'.Ext_TC_System::getInterfaceLanguage();
//		}

		$sAlias = (string)($aColumn['alias'] ?? '');

		$sKey = Ext_Gui2_Data::setFieldIdentifier($aColumn['column'], $sAlias);

		return $sKey;
	}

    /**
     * get Select options for the Filter Element
     * @return array 
     */
    public function getSelectOptions(Ext_Gui2 $oGui = null) {

        $aSelectOptions = array();
        $aConfigs = $this->_getConfigData();

        foreach ($aConfigs as $aColumn) {
            $aMethodCall = (array) $aColumn['filterset']['options'];
            if (!empty($aMethodCall)) {
                $oParser = $this->_getParser();
                $aParams = array($oGui);
                $aSelectOptions = $oParser->callMethod($aMethodCall, $aParams);
                break;
            }
        }

        // empty eintrag löschen damit wir den xNullx Eintrag reinbekommen
        if (
                isset($aSelectOptions[0]) &&
                $aSelectOptions[0] == ""
        ) {
            unset($aSelectOptions[0]);
        }
        
        // wenn label angezeigt wird müssen wir einnen leeren eintrag eintragen damit keine default filterung passiert
        // daher müssen wir schauen ob das label sichtbar ( nicht als select option ) ist und es auch noch keinen null eintrag gibt
        if ($this->display_label == 1 && !isset($aSelectOptions['xNullx'])) {
            $aSelectOptions = Ext_TC_Util::addEmptyItem($aSelectOptions, '', 'xNullx');
        // wenn das Label nicht angezeigt wird, wird es als leer eintrag angezeigt
        // in diesem fall müssen wir den ggf. vorhandenenen null eintrag löschen und ihn erseten
        } else if($this->display_label == 0) {
            unset($aSelectOptions['xNullx']);
            $sLabel = $this->getI18NName('i18n', 'label');
			
			// Fallback
			if(empty($sLabel)) {
				$sLabel = $this->getI18NName('i18n', 'label', 'en');
			}
			
            $aSelectOptions = Ext_Gui2_Util::addLabelItem($aSelectOptions, $sLabel, 'xNullx');
        }

        return $aSelectOptions;
	    
    }

    /**
     * gibt die Config daten zurück
     * es wir ein array mit allen zu dem Filter zugewiesenen Columns zurückgegeben
     * @return array
     */
    protected function _getConfigData() {

        $aData = array();

		$aCurrentBasedOn = (array)$this->basedon;

        $aColumns = $this->getParserColumns();

        if ($aColumns !== false) {

            foreach ($aColumns as $aColumn) {
                
                $sColumn = $this->_createColumnKey($aColumn);
                
                if(
                    // Definieren falls noch nicht vorhanden
                    // oder überschreiben wenn zuvor nicht die aktuelle login sprache da war ( bei sprach abhängigen spalten, so das immer die Login sprache als Spalte zurück gegeben wird )
                    !isset($aData[$sColumn])
                ){

                    if (in_array($sColumn, $aCurrentBasedOn)) {
                        $aData[$sColumn] = $aColumn;
                    }
                }
                
            }
        } else {
            // Wenn keine Configdatei vorhanden kann hier manuell was definiert werden
        }

		if (!empty($aData)) {
			// Keys abgleichen damit nur Werte genommen werden die auch in $aData vorkommen
			$aCurrentBasedOn = array_intersect_key(array_flip($aCurrentBasedOn), $aData);
			// Anhand von $this->basedon sortieren damit getAlias() und getColumns() die Werte in der korrekten
			// Reihenfolge zurückliefern, ansonsten werden falsche Alias und Columns verknüpft
			$aData = array_merge($aCurrentBasedOn, $aData);
		}

        return $aData;
    }

    /**
     * prüft ob der Filter über das Having gehen soll
     * sobald min. 1 Feld des Filters über das Having geht muss alles darüber gehen!
     * @return boolean 
     */
    protected function _isHaving() {
		
        $aConfigs = $this->_getConfigData();
		
        foreach($aConfigs as $aConfig) {
            if(!empty($aConfig['filterset']['having'])) {
                return true;
            }
        }
		
        return false;
    }

    /**
     * gibt den Filter query für Select filter zurück falls vorhanden
     * da ein select nur auf ein Feld sich bezieht reseten wir hier die Columns da nur eine Columns
     * verwendet werden kann
     * @return array 
     */
    protected function _getFilterQuery() {
		
        $aConfigs = $this->_getConfigData();
        $aConfig = reset($aConfigs);

		if(empty($aConfig['filterset']['query'])) {
			return [];
		}

		$query = (array) $aConfig['filterset']['query'];

		if (class_exists(\Illuminate\Support\Arr::first($query))) {
			$query = Ext_Gui2_Config_Parser::callMethod($query);
		}

        return $query;
    }

    /**
     * gibt das Filter query object für Select filter zurück falls vorhanden
     * @return array 
     */
    protected function _getFilterWDSearchQuery() {
        $aConfigs = $this->_getConfigData();
        $aConfig = reset($aConfigs);
		
		if(empty($aConfig['filterset']['wdsearch'])) {
			$aConfig['filterset']['wdsearch'] = [];
		}
		
        $aObjects = (array)$aConfig['filterset']['wdsearch'];
        $aFinalQueries = array();
        foreach ($aObjects as $sKey => $aObject) {
            $oObject = $this->_oParser->callMethod($aObject);
            if (!($oObject instanceof \Elastica\Query\AbstractQuery)) {
                throw new Exception('The Query must be an Elastica Object!');
            }
            $aFinalQueries[$sKey] = $oObject;
        }
        return $aFinalQueries;
    }

	protected function getFactory() {
		
		if($this->oFactory === null) {

			$oBar = Ext_TC_Gui2_Filterset_Bar::getInstance($this->bar_id);
            $oFilterSet = Ext_TC_Gui2_Filterset::getInstance($oBar->set_id);

            $sApplication = $oFilterSet->application;

			try {
				$this->oFactory = new Ext_Gui2_Factory($sApplication);
			} catch (ErrorException $exc) {
                
            }
		}
	
		return $this->oFactory;
	}
	
    /**
     * get the config parser if the gui if the application is a config app
     * @return \Ext_Gui2_Config_Parser|null 
     */
    protected function _getParser() {

        if (!$this->_oParser) {
            
			$oFactory = $this->getFactory();
			$this->_oParser = $oFactory->getConfig();
			
        }

        return $this->_oParser;
    }

    /**
     * Alle Spalten der ConfigDatei
     * 
     * @return array 
     */
    public function getParserColumns() {
		
        $oParser = $this->_getParser();

        if($oParser) {
            
			$aColumns = $oParser->getColumns();

			$aFlexColumns = $this->getFlexColumns(Ext_TC_Flexibility::TYPE_SELECT);
			if(!empty($aFlexColumns)) {
				foreach($aFlexColumns as $iFlexFieldId=>$sFlexFieldLabel) {
					$aColumns[] = [
						'title' => $sFlexFieldLabel,
						'column' => 'flex_'.$iFlexFieldId.'_original',
						'filterset' => [
							'type' => 'select',
							'options' => [
							   'Ext_TC_Flexibility',
								'getOptions',
								$iFlexFieldId
							]
						]
					];
				}
			}

            return $aColumns;
        } else {
            return false;
        }

    }

	/**
	 * @param Ext_Gui2_Config_Parser $oParser
	 */
	public function setParser(Ext_Gui2_Config_Parser $oParser) {
		$this->_oParser = $oParser;
	}

    /**
     * Bestimmte Spalte aus dem Parser finden
     * 
     * @param string $sDbColumn
     * @return array
     */
    public function getColumn($sDbColumn) {
        $aColumn = false;

		if(strpos($sDbColumn, 'flex_') !== false) {

			$aMatch = [];
			preg_match('/flex_([0-9]+)/', $sDbColumn, $aMatch);

			$aColumn = [
				'type' => 'select',
				'options' => [ 
					'Ext_TC_Flexibility',
					'getOptions',
					$aMatch[1]
				]
			];
				
			return $aColumn;
		}
		
        $oParser = $this->_getParser();

        if ($oParser) {
            $aColumn = $oParser->getColumn($sDbColumn);
        }

        return $aColumn;
    }

    /**
     * Standartwert des Filter-Design-Elements
     * @todo Das sollte über die Pflege des Design-Filters passieren, 
     * Interfac-mäßig sich dazu was überlegen und diese Funktion überarbeiten
     * 
     * @param array $aDbColumn
     * @return mixed 
     */
    public function getDefaultValue($aDbColumn) {
        $mDefaultValue = 'xNullx';

        $mOptionDefaultValue = $this->getFilterSetOption($aDbColumn, 'default');

        if ($mOptionDefaultValue) {
            $mDefaultValue = $mOptionDefaultValue;
        }

        return $mDefaultValue;
    }

    /**
     * Filterset Option laden aus der Config
     * 
     * @param string $sDbColumn
     * @param string $sOption
     * @return mixed 
     */
    public function getFilterSetOption($sDbColumn, $sOption) {
        $mOption = false;

        $aDbColumn = (array) $sDbColumn;
        $sDbColumn = reset($aDbColumn);

        $aColumn = $this->getColumn($sDbColumn);

        if (is_array($aColumn) && isset($aColumn['filterset'])) {
            if (isset($aColumn['filterset'][$sOption])) {
                $mOption = $aColumn['filterset'][$sOption];
            }
        }

        return $mOption;
    }

}
