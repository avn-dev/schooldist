<?php

/**
 * Index Generator Klasse 
 * Wielath
 */
class Ext_Gui2_Index_Generator {

	/**
	 * the current index config parser object
	 * @var Ext_Gui2_Config_Parser
	 */
	protected $_oConfig;

	/**
	 * the current index object
	 *
	 * @var \ElasticaAdapter\Adapter\Index
	 */
	protected $_oIndex;

	/**
	 * the current index name
	 * @var string
	 */
	protected string $_sIndex = '';
	
	protected $_bMappingSet = false;
	
	protected $_bDebug = false;

	/**
	 * generiert einen indexnamen mit der lizenz enthalten
	 * @param string $sConfig
	 * @return string
	 */
	public static function createIndexName($sConfig) {
		return \ElasticaAdapter\Facade\Elastica::buildIndexName($sConfig);
	}

	/**
	 * load the index config and open the index object
	 * @param type $sIndex
	 */
	public function __construct($sConfig){

		$oParser = new Ext_Gui2_Config_Parser();
		$oParser->setConfig($sConfig);
		$oParser->load();

		$this->_oConfig = $oParser;

		$sIndexName 	= (string)$oParser->get(array('index', 'name'));
		
		$this->_sIndex  = $sIndexName;

		$sIndexName     = self::createIndexName($sIndexName);

		$this->_oIndex  = new \ElasticaAdapter\Adapter\Index($sIndexName);
	}

	public function enableDebugmode(){
		$this->_bDebug = true;
	}

	/**
	 * Index löschen, neu erzeugen und alle aktiven Einträge ins ParallelProcessing packen
	 * Dabei vermindert sich die Priorität pro Monat (abhängig vom aktuellen Tag des Monats) für ältere Einträge.
	 *
	 * @param bool $bFillStack
	 */
	public function createIndexNewAndAddStack($bFillStack=true) {

		$aFields = $this->getFields(true);
		$iFields = count($aFields) * 2;

		$this->_oIndex->delete(true);
		$this->_oIndex->create([], [], $iFields);
		$this->setMapping();
		$this->_oIndex->refresh();

		if(!$bFillStack) {
			return;
		}

		$this->fillStack();
		
	}
   
	/**
	 * Füllt den Stack mit allen Einträgen
	 */
	public function fillStack() {

		$this->setMapping();

		$oCollection = $this->_getAllEntriesOverQuery();
		$dNow = new DateTime();

		foreach($oCollection as $aData) {
			$dCreated = new DateTime($aData['created']);
			$oDiff = $dNow->diff($dCreated);

			// Einträge starten ab Prio 100 (aktuellster Monat)
			$iPrio = 100 + (($oDiff->y * 12) + $oDiff->m);

			Ext_Gui2_Index_Stack::add($this->_sIndex, $aData['id'], $iPrio, false);
		}

		Ext_Gui2_Index_Stack::save(true);
		
	}
	
	/**
	 * create the complete index
	 */
	public function createIndex(){

		$this->createIndexNewAndAddStack();

		$this->updateIndex();
	}

	/**
	 * update the index with all entries of the current Stack
	 *
	 * @param int $iMaxPrio Nur Einträge abarbeiten mit Prio <= $iMaxPrio
	 * @param int $iLimit
	 * @return int
	 */
	public function updateIndex($iMaxPrio = null, $iLimit = null, $aStack = null){

		if($aStack === null) {
			$aStack = Ext_Gui2_Index_Stack::getDbCollection($this->_sIndex);
		}

		$iCount = 0;
		foreach($aStack as $aStackEntry) {

			if(
				(
					$iLimit !== null &&
					$iCount >= $iLimit
				) ||
				(
					$iMaxPrio !== null &&
					$aStackEntry['priority'] > $iMaxPrio
				)
			) {
				continue;
			}

			if($aStackEntry['index_id'] > 0) {
				$oObject = $this->getIndexEntity($aStackEntry['index_id']);

				$this->updateIndexEntry($oObject, $aStackEntry['index_id']);
				$iCount++;
			}
		}

		// Index muss refresht werden, damit Änderungen auch sichtbar sind, da das sonst erst nach ~1s passiert
		// Das ist aber nur relevant für Prio 0 Einträge (executeCache), wovon es nicht viele geben sollte
		// Vorher wurde das bei JEDEM Durchlauf von updateIndexEntry ausgeführt!
		if($iMaxPrio === 0) {
			$this->_oIndex->refresh();
		}

		Ext_Gui2_Index_Registry::disable();

		return $iCount;
	}

	/**
	 * Objekt im Elasticsearch-Index aktualisieren
	 *
	 * @internal
	 * @param WDBasic $oObject
	 * @param int $iOldId
	 * @param array|null $aFieldsFilter Nur bestimmte Felder aktualisieren (UPDATE)
	 */
	public function updateIndexEntry(WDBasic $oObject, $iOldId = 0, $aFieldsFilter = null) {

		if (!empty(DB::getLastTransactionPoint())) {
			throw new RuntimeException('updateIndexEntry() called while in active database transaction! ('.DB::getLastTransactionPoint().', '.get_class($oObject).')');
		}

		$sCurrentInterface = System::getInterface();
		
		System::setInterface('backend');

		Ext_Gui2_Index_Registry::enable();

		$sId = $oObject->getId();

		$this->_debug('Entity update('.$oObject->getId().')');

		if($oObject->exist() && $this->_oConfig->get(array('index', 'registry', 'generate'))) {

			if($oObject->isActive()) {

				$aFields = $this->_prepareFields($oObject, $aFieldsFilter);

				// nötig damit flex felder korrekt neu aufgenommen werden
				// wenn wir das hier immer setzen dann gehen neue felder sobald man mind. 1 Eintrag gespeichert hat
				// TODO Kann man das nicht anders lösen? Das dauert bei ts_inquiry jedes Mal schon etwa eine halbe Sekunde
				//$this->setMapping();

				$oDocument  = $this->_oIndex->createDocument($sId);

				foreach($aFields as $aField){

					$sField = $aField['field'];
					$mValue = $aField['value'];

					// Nur vorhandene Werte in Index schreiben
					if(
						$mValue !== null ||
						$aField['is_null_value']
					) {
						$oDocument->set($sField, $mValue);
					}

				}

				if($aFieldsFilter === null) {
					$this->_oIndex->addDocument($oDocument);
				} else {
					try {
						$this->updateDocument($oDocument, $aFieldsFilter);
					} catch(Elastica\Exception\ResponseException $e) {
						// Wenn Dokument z.B. nicht existiert, dann vollständig aktualisieren
						$this->updateIndexEntry($oObject, $iOldId, null);
						return;
					}
				}

			} else {
				$this->deleteIndexEntry($oObject);
			}

			// muss gemacht werden da es sonst erst nach ~1s passiert.
			// in vielen fällen ist das aber nicht schnell genug!
			// TODO beim gesammtindizieren ausklammern
			//$this->_oIndex->refresh();

			$this->_debug('Save Reqistry('.$oObject->getId().')');
			Ext_Gui2_Index_Registry::save($this->_sIndex, $sId);
			$this->_debug('Save Reqistry('.$oObject->getId().')');

		}

		$this->_debug('Entity update('.$oObject->getId().')');
		
		// Einträge die nichtmehr in der DB da sind aber im stack stehen
		// müssen gelöscht werden
		if(
			$iOldId > 0 &&
			$sId <= 0
		){
			$sId = $iOldId;
		}

		$this->_debug('Entity stack delete ('.$oObject->getId().')');
		//Ext_Gui2_Index_Stack::delete($this->_sIndex, $sId);
		$this->_debug('Entity stack delete ('.$oObject->getId().')');
		
		System::setInterface($sCurrentInterface);
		
	}

	/**
	 * Elasticsearch Update-API: Einzelne Felder von Dokument aktualisieren
	 *
	 * Hier müssen alle Felder durchlaufen werden, da gelöschte oder leere Felder,
	 * die angegeben wurden, explizit auf null gesetzt werden müssen (diese würden
	 * sonst fehlen). Zudem müssen die Felder alle auf ihre Unterfelder aufgeteilt
	 * werden, z.B. bei I18N auf diese Masse an Spalten oder auch _original
	 * berücksichtigt werden. Vermutlich fehlen hier allerdings ein paar Sonderfälle.
	 *
	 * @param \Elastica\Document $oDocument
	 * @param array $aFields
	 */
	private function updateDocument(\Elastica\Document $oDocument, array $aFields) {

		$aAllFields = $this->getFields(true);

		foreach($aFields as $sField) {

			// Felder mit bspw. I18N werden zu vielen Feldern und DIESE Sub-Felder werden benötigt
			$aFieldsWithSubFields = [];
			foreach($aAllFields as $aAllField) {
				if($sField === $aAllField['_column']) {
					$aFieldsWithSubFields[] = $aAllField['column'];

					if(!empty($aAllField['index']['add_original'])) {
						$aFieldsWithSubFields[] = $aAllField['column'].'_original';
					}
				}
			}

			// Felder ohne Wert explizit auf null setzen
			foreach($aFieldsWithSubFields as $sSubField) {
				if(!$oDocument->has($sSubField)) {
					$oDocument->set($sSubField, null);
				}
			}

		}

		$this->_oIndex->updateDocument($oDocument);

		// Index sofort refreshen, da es sonst ~1s dauert, bis der Eintrag aktualisiert auftauchen würde
		// Da z.B. createTable aber direkt nach einer Aktualisierung erfolgt, muss das sofort passieren
		$this->_oIndex->refresh();

	}

	/**
	 * @param WDBasic $oObject
	 */
	public function deleteIndexEntry(WDBasic $oObject) {
		$this->_oIndex->deleteDocuments([(string)$oObject->id]);
		Ext_Gui2_Index_Registry::delete($this->_sIndex, $oObject->id);
	}

	/**
	 * get all Entries over the Query
	 * @return Collection
	 * @throws ErrorException
	 */
	protected function _getAllEntriesOverQuery(){
		$aWhere     = (array)$this->_oConfig->get(array('index', 'where'));
		if(!empty($aWhere)){
			$aWhere     = $this->_oConfig->callMethod($aWhere);

			if(!empty($aWhere)){
				$aWhere     = Ext_Gui2_Data::buildWherePart($aWhere);
				/** @todo ltrim klappt so nicht */
				//$aWhere['sql'] = ltrim($aWhere['sql'], ' AND');
			}
		}

		$oObject    = $this->getIndexEntity(0);
		$aQuery     = $oObject->getListQueryDataForIndex();

		$sSql       = $aQuery['sql'];
		
		if(!empty($aWhere)){
			$sSql       = str_replace('WHERE ', 'WHERE '.$aWhere['sql'].' AND ', $sSql);
		}
   
		$aSql       = array_merge((array)$aQuery['data'], (array)$aWhere['data']);

		$oDB = DB::getDefaultConnection();
		$oCollection = $oDB->getCollection($sSql, $aSql);

		return $oCollection;

	}

	/**
	 * @todo hier muss noch diefiniert werden welche sprachen genommen werden
	 * getBackendLanguages wären zuviele da bei agentur z.b bei der argentureinstellung auch nur 1 oder 2 wählbar wären
	 * @return array
	 */
	protected function _getLanguages(){
		$aLangs = Factory::executeStatic('Util', 'getTranslationLanguages');
		return $aLangs;
	}

	protected $_aGetFieldsCache = array();

	/**
	 * get all Fields for the Index
	 *
	 * @see \Ext_Gui2_Config_Parser::getColumns()
	 *
	 * @param bool $bWithOriginal
	 * @return mixed
	 */
	public function getFields($bWithOriginal = false) {

		$aCache = &$this->_aGetFieldsCache;

		if(empty($aCache[$bWithOriginal])){

			$aFields        = array();
			$aColumns       = (array)$this->_oConfig->getColumns();
			$aColumns       = $this->_addFlexFields($aColumns);
			$aOriginalColumnsAdded = [];

			foreach($aColumns as $aColumn){

				if(
					// YML setzt den Wert zwar immer, aber nicht andere Stellen
					isset($aColumn['index']['add_field']) &&
					!$aColumn['index']['add_field']
				) {
					continue;
				}

				$aFields[] = $aColumn;

				if(
					$bWithOriginal &&
					!empty($aColumn['index']['add_original']) &&
					!isset($aOriginalColumnsAdded[$aColumn['_column']])
				) {
					// Bei I18N-Spalten die Spalte nur EINMAL hinzufügen
					if(empty($aColumn['index']['add_original_i18n'])) {
						$sColum = $aColumn['_column'];
						$aOriginalColumnsAdded[$sColum] = true;
					} else {
						$sColum = $aColumn['column'];
					}

					// Hier schien man mal _original als richtigen Key haben zu wollen (Filter?). Siehe ts_inquiry course_from_original usw.…
					// TODO Alle Spalten mit column:.*_original, data:.* und add_original: true müssen mal überprüft werden, da das eigentlich falsch ist!
					// Siehe auch: \Ext_TC_Gui2_Filterset_Bar_Element::_createColumnKey()
					if(!empty($aColumn['data'])) {
						$sColum = $aColumn['data'];
						unset($aColumn['data']);
					}

					$aColumn['column'] = $sColum . '_original';
					$aColumn['index']['add_original'] = false;
					if(!empty($aColumn['index']['mapping_original'])) {
						$aColumn['index']['mapping'] = $aColumn['index']['mapping_original'];
					}
					$aFields[] = $aColumn;
				}

			}

			$sRowStyleClass = $this->_oConfig->get(array('class', 'row_style_index'));
			if(!empty($sRowStyleClass)){
				$aFields[] = array(
					'column' => 'gui_row_style',
					'_column' => 'gui_row_style',
					'field' => 'gui_row_style',
					'format' => $sRowStyleClass
				);
			   //
			}

			$this->_aGetFieldsCache[$bWithOriginal] = $aFields;

		}

		return $this->_aGetFieldsCache[$bWithOriginal];
	}

	/**
	 * @TODO Partielle Redundanz mit \Gui2\Traits\GuiFilterTrait::setCustomFieldFilter
	 * @see \Gui2\Traits\GuiFilterTrait::setCustomFieldFilter()
	 */
	protected function _addFlexFields($aFields) {

		$sFlexSection = (string)$this->_oConfig->get(array('designer', 'flex'));
		$aAdditionalFlexSections = $this->_oConfig->get(array('designer', 'additional_flex'));
		$aFlexFormats = (array)$this->_oConfig->get(array('designer', 'flex_format'));

		// Schnittmenge, da die Felder nur in GUIs angezeigt werden und es dort nur Backendsprachen gibt
		$aLangsFrontend = Factory::executeStatic('Util', 'getLanguages');
		$aLangsBackend = Factory::executeStatic('Util', 'getLanguages', ['backend']);
		$aLangs = array_intersect_key($aLangsBackend, $aLangsFrontend);

		$aFlexSections = array();
		if(!empty($sFlexSection)){
			$aFlexSections[] = array(
				'section' => $sFlexSection,
				'call' => [],
				'set' => []
			);
		}

		if(!empty($aAdditionalFlexSections)) {
			foreach($aAdditionalFlexSections as $aAdditionalFlexSection) {
				$aFlexSections[] = array(
					'section' => $aAdditionalFlexSection['section'],
					'call' => $aAdditionalFlexSection['entity_call'] ?? [],
					'set' => $aAdditionalFlexSection['set'] ?? []
				);
			}
		}

		if(!empty($aFlexSections)) {

			foreach($aFlexSections as $aFlexSection) {

				$aFlexFields = Ext_TC_Flexibility::getSectionFieldData([$aFlexSection['section']], false, true);

				$sFormatDate = Factory::getClassName('Ext_Gui2_View_Format_Date');

				foreach($aFlexFields as $aFlexField) {

					$bAddOriginal = false;
					$sFormat = 'Ext_Gui2_View_Format_Text';
					$aFormatParams = array();
					$bFormatEmpty = false;

					// Komisches, veraltetes Mapping, was scheinbar auch in YML verwendet wird
					$sTypeOfField = match ((int)$aFlexField['type']) {
						Ext_TC_Flexibility::TYPE_TEXT => 'input',
						Ext_TC_Flexibility::TYPE_TEXTAREA => 'textarea',
						Ext_TC_Flexibility::TYPE_CHECKBOX => 'checkbox',
						Ext_TC_Flexibility::TYPE_HEADLINE => 'header',
						Ext_TC_Flexibility::TYPE_DATE => 'date',
						Ext_TC_Flexibility::TYPE_SELECT => 'dropdown',
						Ext_TC_Flexibility::TYPE_HTML => 'html',
						Ext_TC_Flexibility::TYPE_YESNO => 'yes_no',
						Ext_TC_Flexibility::TYPE_MULTISELECT => 'multiselect',
						default => false
					};

					$sColumn = 'flex_' . $aFlexField['id'];
					$sSortableColumn = $sColumn;
					$bSortOriginal = false;
					$bLang = false;
					$bPassLanguage = false;
//					$aLangs = $aLangsFrontend;
					$sI18NInterface = 'frontend';
					$sSeperator = '<br>';

					$sMappingType = 'text';

					$mDefaultValue = null;

					if(isset($aFlexFormats[$sTypeOfField])) {

						if(isset($aFlexFormats[$sTypeOfField]['format'])) {
							$sFormat = $aFlexFormats[$sTypeOfField]['format'];
							$bAddOriginal = true;
							if(isset($aFlexFormats[$sTypeOfField]['sort_original'])) {
								$bSortOriginal = $aFlexFormats[$sTypeOfField]['sort_original'];
							}
						}

						if(isset($aFlexFormats[$sTypeOfField]['format_params'])) {
							$aFormatParams = $aFlexFormats[$sTypeOfField]['format_params'];
						}

						if(isset($aFlexFormats[$sTypeOfField]['format_empty'])) {
							$bFormatEmpty = $aFlexFormats[$sTypeOfField]['format_empty'];
						}

					} elseif(
						$sTypeOfField == 'textarea' ||
						$sTypeOfField == 'html' ||
						$sTypeOfField == 'input'
					) {
						$sFormat = 'Ext_Gui2_View_Format_ToolTip';
						$aFormatParams = array('flex_' . $aFlexField['id'], true, 40, true);
						$bAddOriginal = true;
						if((int)$aFlexField['i18n'] === 1) {
							$bLang = true;
							$bPassLanguage = true;
						}

					} elseif($sTypeOfField == 'dropdown') {
						// TODO Ext_TC_Flexible_Gui2_Format_Option vs $aFormatParams (siehe multiselect)
						$sFormat = 'Ext_TC_Flexible_Gui2_Format_Option';
						$bAddOriginal = true;
						$bLang = true;
					} elseif($sTypeOfField == 'date') {
						$sFormat = $sFormatDate;
						$bAddOriginal = true;
						$bSortOriginal = true;
					} elseif($sTypeOfField == 'checkbox') {
						$sMappingType = 'boolean';
						$mDefaultValue = 0;
//						$sFormat = 'Ext_Gui2_View_Format_YesNo';
//						$bLang = true;
//						$aLangs = $aLangsBackend;
//						$sI18NInterface = 'backend';
					} elseif($sTypeOfField == 'yes_no') {
//						$sFormat = 'Ext_TC_Flexible_Gui2_Format_YesNo';
//						$bLang = true;
//						$aLangs = $aLangsBackend;
//						$sI18NInterface = 'backend';
					} elseif($sTypeOfField == 'multiselect') {
						$sFrontendLanguage = Factory::executeStatic('Ext_TC_Util', 'getInterfaceLanguage');
						$sFormat = 'Ext_Gui2_View_Format_Selection';
						$aFormatParams = [['Ext_TC_Flexibility', 'getOptions', $aFlexField['id'], $sFrontendLanguage]];
						$sSeperator = ', ';
						$bAddOriginal = true;
					}

					if($bSortOriginal) {
						$sSortableColumn = $sColumn . '_original';
					}

					$aCall = array();
					if(!empty($aFlexSection['call'])) {
						$aCall[] = $aFlexSection['call'];
					}

					$aCall[] = array('getFlexValue()', (int)$aFlexField['id'], null, $mDefaultValue);

					$sIndexMappingIndex = true;
//					if(
//						$sTypeOfField === 'textarea' ||
//						$sTypeOfField === 'html'
//					) {
//						// Nach diesen Typen kann nicht gesucht werden, da hier mehr als 32 KB UTF8-Text drin stehen kann
//						// Mit einer neueren ElasticSearch-Version könnte man hier allerdings ignore_above benutzen…
//						$sIndexMappingIndex = 'no';
//					}

					$aMapping = ['index' => $sIndexMappingIndex, 'type' => $sMappingType];

					if (in_array($sTypeOfField, ['input'])) {
						$aMappingOriginal = ['index' => true, 'type' => 'text', 'analyzer' => 'lowercase_whitespace'];
					} else {
						// 1. Nicht analysiert, da nach Flexfeldern nicht gesucht wird.
						// 2. Nicht analysiert, da ToolTips ansonsten durch strtolower() laufen.
						$aMappingOriginal = ['index' => $sIndexMappingIndex, 'type' => 'text'];
					}

					$aFieldData = array(
						'visible' => true,
						'column' => $sColumn,
						'_column' => $sColumn,
						'sortable_column' => $sSortableColumn,
						'format' => $sFormat,
						'format_params' => $aFormatParams,
						'format_empty' => $bFormatEmpty,
						'index' => array(
							'add_original' => $bAddOriginal,
							'call' => $aCall,
							'mapping' => $aMapping,
							// TODO Wenn man irgendwann mal nach den Feldern suchen können soll, muss das pro Typ angepasst werden
							'mapping_original' => $aMappingOriginal
						),
						'seperator' => $sSeperator,
						'set' => $aFlexSection['set']
					);

					if($bLang) {
						foreach(array_keys($aLangs) as $sIso) {
							$aLangField = $aFieldData;
							$sLang = $sIso;
							$aLangField['column'] = $aLangField['column'] . '_' . $sLang;
							$aLangField['sortable_column'] = $aLangField['sortable_column'] . '_' . $sLang;
							$aLangField['language'] = $sLang;
							$aLangField['i18n'] = array(
								'pass_language' => $bPassLanguage,
								'interface' => $sI18NInterface,
							);
							$aFields[] = $aLangField;
						}
					} else {
						$aFields[] = $aFieldData;
					}

				}

			}

		}

		return $aFields;

	}

	protected function _getStyleField($aField){

		$aFieldData = array(
			'visible' => false,
			'column' => $aField['format'].'_style',
			'style' => $aField['style'],
			'index' => array(
				'add_original' => false,
				'call' => '__STYLE__',
				'mapping'   => array(
					'index' => false,
					'type'  => 'text',
					'analyser' => 'lowercase_whitespace'
				)
			)
		);

		return $aFieldData;
	}

	/**
	 * set the Mapping informations
	 */
	public function setMapping() {

		if(!$this->_bMappingSet) {

			$aFields = $this->getFields(true);
			$aStyleFields = $this->_getStyleFields();
			$aMappingData = array();

			foreach($aFields as $aField) {
				$sColumn = $aField['column'];
				if(!empty($aField['data'])) {
					$sColumn = $aField['data'];
				}
				$aMappingData[$sColumn] = $this->_createFieldMappingData($aField);
			}

			foreach($aStyleFields as $aStyleField) {
				$aMappingData[$aStyleField['column']] = $this->_createFieldMappingData($aStyleField);
			}

			$this->_oIndex->createMapping($aMappingData, $this->_oConfig->get(['index', 'source']));

			$this->_bMappingSet = true;

		}
	}

	/**
	 * create the Mapping Data for the given Fielddata
	 * @param array $aField
	 * @return array
	 */
	protected function _createFieldMappingData($aField) {

		$aMapping = array(
			'store' => true,
			'type' => 'text',
			'index' => true
		);

		if(isset($aField['index']['mapping']['type'])) {
			$aMapping['type'] = (string)$aField['index']['mapping']['type'];

			if($aMapping['type'] === 'string') {
				$aMapping['type'] = 'text';
			}
		}
		if(isset($aField['index']['mapping']['index'])) {
			$aMapping['index'] = $aField['index']['mapping']['index'];
			if (
				empty($aMapping['index']) ||
				$aMapping['index'] === 'no'
			) {
				$aMapping['index'] = false;
			}
			if (
				$aMapping['index'] === 'analyzed' ||
				$aMapping['index'] === 'not_analyzed'
			) {
				$aMapping['index'] = true;
			}
		}
		if(isset($aField['index']['mapping']['properties'])) {
			$aMapping['properties'] = (array)$aField['index']['mapping']['properties'];
		}
		if(isset($aField['index']['mapping']['index_name'])) {
			$aMapping['index_name'] = (string)$aField['index']['mapping']['index_name'];
		}

		if(isset($aField['index']['mapping']['null_value'])) {
			$aMapping['null_value'] = (string)$aField['index']['mapping']['null_value'];
		}

		if(isset($aField['index']['mapping']['fields'])) {
			$aMapping['fields'] = $aField['index']['mapping']['fields'];
		} else if ($aMapping['type'] == 'text') { // subfield mit type keyword für sortierung erzeugen
			$aMapping['fields'] = [
				'keyword' => [
					'type' => 'keyword'
				]
			];
		}


		if(isset($aField['index']['mapping']['analyzer'])) {

			if(
				!is_array($aField['index']['mapping']['analyzer']) &&
				!empty($aField['index']['mapping']['analyzer'])
			) {
				$aMapping['analyzer'] = (string)$aField['index']['mapping']['analyzer'];

			} /*else {
				if(
					!empty($aField['index']['mapping']['analyzer']['index']) &&
					!empty($aField['index']['mapping']['analyzer']['search'])
				) {
					$aMapping['index_analyzer'] = (string)$aField['index']['mapping']['analyzer']['index'];
					$aMapping['search_analyzer'] = (string)$aField['index']['mapping']['analyzer']['search'];
					unset($aMapping['analyzer']);
				}
			}*/

		}



		return $aMapping;
	}

	/**
	 * Entität für diesen Generator erzeugen (Entität aus YML-Definition)
	 *
	 * @internal
	 * @param int $iEntityId
	 * @return WDBasic|Ext_TC_Basic
	 */
	public function getIndexEntity($iEntityId) {

		$sEntityClass = $this->_oConfig->get(['class', 'wdbasic']);
		$oEntity = Factory::executeStatic($sEntityClass, 'getInstance', [$iEntityId]);

		return $oEntity;

	}

	/**
	 * Prepare Field for the given Object and check if the entry are aktive
	 *
	 * @param WDBasic $oEntry
	 * @param array $aFieldsFilter
	 * @return array
	 */
	protected function _prepareFields(WDBasic $oEntry, $aFieldsFilter = null) {

		$aFinalFields   = array();

		$sInterfaceLanguage = System::getInterfaceLanguage();

		if($oEntry->isActive()){

			$aFields = $this->getFields();

			$aFieldValues = array();

			$this->_debug('Entity prepare Fields ('.$oEntry->getId().')');

			foreach($aFields as $aField) {

				if(
					$aFieldsFilter !== null &&
					!in_array($aField['_column'], $aFieldsFilter)
				) {
					continue;
				}

				$sColumn = (string)$aField['column'];
				if(!empty($aField['data'])){
					$sColumn = (string)$aField['data'];
				}
				$mValue = '';

				$aData = array();
				$aData['field'] = $sColumn;

				if(!empty($aField['index']['call'])) {

					$sFieldLanguage = $aField['language'] ?? null;
					
					if(!empty($sFieldLanguage)){
						System::setInterfaceLanguage($sFieldLanguage);
					}

					if(
						isset($aField['i18n']['pass_language']) &&
						!$aField['i18n']['pass_language']
					){
						$sFieldLanguage = null;
					}

					$this->_debug('callValue ('.$aField['column'].')');

					$mValueOriginal = $this->_callValue($oEntry, $aField['index'], $sFieldLanguage);

					$this->_debug('callValue ('.$aField['column'].')');

					if(
					   is_array($mValueOriginal) &&
					   $aField['unique_values'] == true
					){
						// array_values() muss ausgeführt werden, da es für Elasticsearch keine Lücken in den nummerischen Keys geben darf
					   	$mValueOriginal = array_values(array_unique($mValueOriginal));
					}

					$mValue = $this->_formatValue($oEntry, $mValueOriginal, $aField);

					// Boolean sicherstellen (kann auch mal null, '0', '' etc. sein)
					if ($aField['index']['mapping']['type'] === 'boolean') {
						if(is_array($mValue)) {
							$mValue = array_map(fn($v) => !!$v, $mValue);
						} else {
							$mValue = !!$mValue;
						}
					}

					// Wenn $mValue === false ist, würde dieser Wert auch als String in den Index geschrieben werden #5359
					if(
						$mValue === false &&
						// In Elasticsearch 1.4.5 wurde "" als false behandelt, in Elasticsearch 5 als true
						$aField['index']['mapping']['type'] !== 'boolean'
					) {
						$mValue = '';
					}

					System::setInterfaceLanguage($sInterfaceLanguage);
				}

				// Wenn null_value gesetzt ist, muss der Wert weitergegeben werden!
				$bNullValue = !empty($aField['index']['mapping']['null_value']);

				if(
					$mValue !== null ||
					$bNullValue
				) {

					$aData['value']         = $mValue;
					$aData['format']        = $aField['format'] ?? null;
					$aData['is_null_value']	= $bNullValue;
					$aFinalFields[$sColumn] = $aData;
					$aFieldValues[$sColumn] = $mValue;

					if(!empty($aField['index']['add_original'])) {

						if(
							empty($aField['index']['add_original_i18n']) &&
							empty($aField['data']) // Ist auch so in $this->_getFields eingebaut()
						) {
							$aData['field'] = $aField['_column'].'_original';
						} else {
							$aData['field'] = $aData['field'].'_original';
						}

						if(
							is_string($mValueOriginal) &&
							// Tooltips brauchen auch originale Werte, aber kein strtolower()
							($aField['index']['mapping_original']['index'] ?? null) !== false &&
							// v8 erlaubt kein Datum mit lowercase t
							$aField['index']['mapping_original']['type'] !== 'date'
						) {
							$mValueOriginal = strtolower($mValueOriginal);
						} elseif(
							// Wenn DateTime: Elastica macht da nichts, daher zu String umwandeln
							isset($aField['index']['mapping_original']['type']) &&
							$aField['index']['mapping_original']['type'] === 'date' &&
							$mValueOriginal instanceof DateTime
						) {
							$mValueOriginal = $mValueOriginal->format('Y-m-d');
						}
						
						$aData['value'] = $mValueOriginal;
						$aFinalFields[$aData['field']] = $aData;
						$aFieldValues[$aData['field']] = $mValueOriginal;
					}

				}
			}

			// Style der Spalte indizieren falls die Styleklasse davon ableitet
			$aStyleFields = [];
			if($aFieldsFilter === null) {
				$aStyleFields = $this->_getStyleFields();
			}

			foreach($aStyleFields as $aStyleField){
				$mStyle                                         = $this->_callStyle($oEntry, $aStyleField, $aFieldValues);
				$aFinalFields[$aStyleField['field']]['field']   = $aStyleField['field'];
				$aFinalFields[$aStyleField['field']]['value']   = $mStyle;
			}

			if(isset($aFinalFields['gui_row_style'])) {
				$aField             = $aFinalFields['gui_row_style'];
				$sRowStyleClass     = (string)$aField['format'];
				$oRowStyle          = new $sRowStyleClass();
				$oColumn            = null;
				$aField['value']    = $oRowStyle->getStyle('', $oColumn, $aFieldValues);
				$aFinalFields['gui_row_style'] = $aField;
			}

			$this->_debug('Entity prepare Fields ('.$oEntry->getId().')');

		}

		$aFinalFields = array_values($aFinalFields);

		return $aFinalFields;
	}


	protected $_aGetStyleFieldsCache = array();
	/**
	 * gibt alle Felder zurück wo der Style Indiziert wird
	 * @return array
	 */
	protected function _getStyleFields(){

		$aCache = &$this->_aGetStyleFieldsCache;

		if(empty($aCache)){

			$aFields        = $this->getFields();

			$aStyleFields   = array();

			foreach($aFields as $aField){

				$sStyle = $aField['style'] ?? null;

				if(!empty($sStyle)){

					$oTemp = new $sStyle();

					if($oTemp instanceof Ext_Gui2_View_Style_Index_Interface){

						$aStyleField = array(
							'column'    => $aField['column'].'_style',
							'field'     => $aField['column'].'_style',
							'style'     => $aField['style'],
							'index'     => array(
								'mapping'   => array(
									'index'     => false,
									'type'      => 'text'
								)
							)
						);

						$aStyleFields[] = $aStyleField;
					}
				}
			}

			$this->_aGetStyleFieldsCache = $aStyleFields;
		}

		return $this->_aGetStyleFieldsCache;
	}


	protected $_aDebugTimes = array();


	protected function _debug($sPart){

		if($this->_bDebug){
			if(!isset($this->_aDebugTimes[$sPart])){
				$this->_aDebugTimes[$sPart] = microtime(true);
			} else {
				$iTime = microtime(true) - $this->_aDebugTimes[$sPart];
				unset($this->_aDebugTimes[$sPart]);
				// Macht Probleme mit dem neuen Sessionhandling
				__pout('Debug: Dauer von "'.$sPart.'" betrug '.$iTime);
			}
		}

	}

	/**
	 * format a Value for the given Entry an Fielddata
	 * @param WDBasic $oEntry
	 * @param string|int|array $mValue
	 * @param array $aField
	 * @return string|int|array
	 */
	protected function _formatValue(WDBasic $oEntry, $mValue, $aField){

		$sFormat = $aField['format'] ?? null;	
		
		$bFormatEmpty = false;
		
		if(isset($aField['format_empty']))
		{
			$bFormatEmpty = $aField['format_empty'];
		}
		
		if(!empty($sFormat)){
			
			$aFormatParams = $this->_createFormatParameters((array)($aField['format_params'] ?? []));
			
			$oFormat = $this->_oConfig->callObject($sFormat, $aFormatParams);
	
		   /**
			* #5378
			* Daten für Alter dürfen hier nicht formatiert werden. Ansonsten wird das Alter fix in den Index geschrieben
			* und ist nicht mehr aktuell wenn die Person Geburtstag hatte
			*
			* siehe: Ext_Gui2::getTableData() Z. ~1216;
			*/
			if($oFormat instanceof Ext_Gui2_View_Format_Age) {
				return $mValue;
			}
			
			if(
				!is_array($mValue)  && 
				(
					$mValue !== null || 
					$bFormatEmpty
				)
			)
			{	
				$mValue = array($mValue);
			}
			
			if(
				$mValue !== null ||
				$bFormatEmpty
			){
				foreach($mValue as $iKey => $sValue){
					$oColumn = new stdClass();
					
					if(isset($aField['column'])){
						$oColumn->db_column = $aField['column'];
					}
					
					if(isset($aField['data'])){
						$oColumn->select_column = $aField['data'];
					}
					
					// TODO getIndexData() existiert in WDBasic nicht, ist das die Methode in Ext_TC_Basic?
					$mIndexData = $oEntry->getIndexData();
					$mValue[$iKey] = $oFormat->format($sValue, $oColumn, $mIndexData);
				}
			}
			
		}

		// wir müssen es komma trennen da sonst das sortieren nicht klappt
		// TODO Wenn $mValue false oder null ist, wird wegen implode() daraus ''
		if(is_array($mValue)){
			$sSeperator = $aField['seperator'];
			if($sSeperator != 'none'){
				$mValue = implode($sSeperator, $mValue);
			}
		}
  
		return $mValue;
	}

	protected function _callStyle(WDBasic $oWDBasic, $aField, $aRowData){
		$sStyle = $aField['style'];
		$oStyle = new $sStyle();
		$oColumn = null;
		$mValue = null;
		$mStyle = $oStyle->getIndexStyle($mValue, $oColumn, $aRowData);
		return $mStyle;
	}

	/**
	 * call the value for the given field of the given object
	 * @param WDBasic $oWDBasic
	 * @param array $aIndexConfig
	 * @param string|null $sPassLanguage
	 * @return array
	 */
	protected function _callValue(WDBasic $oWDBasic, $aIndexConfig, $sPassLanguage = null){

		$aObjects = array($oWDBasic);
		$aCallPath = $aIndexConfig['call'];
		$aParams = [];

		// Methoden aufrufe durchgehen
		foreach($aCallPath as $mCall) {

			$bMerge = true;
			if($mCall === end($aCallPath)) {

				if($sPassLanguage !== null) {
					$aParams = [$sPassLanguage];
				}

				// Siehe default.yml
				if(
					isset($aIndexConfig['call_return']) &&
					$aIndexConfig['call_return'] === 'no_merge'
				) {
					$bMerge = false;
				}

			}

			$aObjects = $this->_callObjectsMethod($aObjects, $mCall, $aParams, $bMerge);

		}

		// wenn nur ein eintrag dann kein Array zurückgeben
		$mBack = $aObjects;

		if(
			is_array($mBack) &&
			empty($mBack)
		) {
			$mBack = null;
		}

		if(
			is_array($mBack) &&
			count($mBack) == 1
		){
			$mBack = reset($mBack);
		}

		return $mBack;

	}

	/**
	 * call the method of all objects and give the result back as array
	 * @param array $aObjects
	 * @param array $mMethod
	 * @param array $aParams
	 * @param bool $bMerge
	 * @return array
	 */
	protected function _callObjectsMethod($aObjects, $mMethod, $aParams = array(), $bMerge = true) {

		$aValues = array();

		$sMethod = $mMethod;

		if(is_array($mMethod)) {
			$sMethod = reset($mMethod);
			unset($mMethod[0]);
			$aParams = array_merge($aParams, $mMethod);
		}
		$sMethodName= str_replace('()', '', $sMethod);

		foreach($aObjects as $oObject) {

			if(is_object($oObject)) {

				if(
					strpos($sMethod, '()') !== false 
				) {
					$mValue = call_user_func_array(array($oObject, $sMethodName), $aParams);
				} else {
					$mValue = $oObject->$sMethod;
				}

				if(!is_array($mValue)) {
					if(
						$bMerge ||
						// call_return: no_merge
						// $mValue !== null: Wenn Elasticsearch (JSON-)-Objekte erwartet, darf da kein null im Array sein
						(!$bMerge && $mValue !== null)
					) {
						$aValues[] = $mValue;
					}
				} else {
					if($bMerge) {
						$aValues = array_merge($aValues, $mValue);
					} else {
						// call_return: no_merge
						if($mValue !== null) {
							$aValues[] = $mValue;
						}
					}
				}

			}
		}

		return $aValues;
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
			if(is_array($mParam)){
				$aBack[] = $this->_oConfig->callMethod($mParam);
			} else {
				$aBack[] = $mParam;
			}
		}

		return $aBack;
	}

}
