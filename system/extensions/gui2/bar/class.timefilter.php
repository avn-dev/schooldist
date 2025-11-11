<?php

/**
 * @property string $default_from
 * @property string $default_until
 */
class Ext_Gui2_Bar_Timefilter extends Ext_Gui2_Bar_Filter_Abstract {
	
	// Konfigurationswerte setzten
	protected $_aConfig = array(
		'element_type'		=> 'timefilter',
		'width'				=> '80px',
		'name'				=> '',
		'id'				=> '',			// wird nicht genutzt! ist aber notwenidig da manche prüfungen das feld prüfen wollen
		'from_id'			=> '',
		'until_id'			=> '',
		'label'				=> '',
		'label_between'		=> '',			// Label zwischen den Felder
		'default_from'		=> '',			// vorauswahl
		'default_until'		=> '',			// vorauswahl
		'db_from_column'	=> '',			// spalte in der gesucht werden soll (arrays möglich) ( BEIDE müssen die gleiche anzahl haben )
		'db_from_alias'		=> '',			// Alias für den query (arrays möglich) ( BEIDE müssen die gleiche anzahl haben )
		'db_until_column'	=> '',			// spalte in der gesucht werden soll (arrays möglich) ( BEIDE müssen die gleiche anzahl haben )
		'db_until_alias'	=> '',			// Alias für den query (arrays möglich) ( BEIDE müssen die gleiche anzahl haben )
		'search_type'		=> 'between',	// between oder contact ( Felder müssen zwischen dem Zeitraum liegen, oder ihn "streifen" )
		'format'			=> 'Date',
		'data_function'		=> 'Date',		// Funktion zum formatieren der Input-Daten
		'access'			=> '', // recht,
		'filter_part'		=> 'where',
		'skip_query'		=> false,
		'query_value_key'	=> null,
		'use_coalesce'		=> true,
		'required'			=> false,
		'text_after'		=> '', // Text, der hinter dem Filter angezeigt wird
		'value' => [], // Neue Filter
		'filter_type' => 'timefilter',
		'initial_value' => ['', ''],
		'sidebar' => false,
		'negateable' => null, // Neue Filter: ist/ist nicht verfügbar
		'sort_order' => 1
	);

	static protected $iCount = 0;

	/**
	 * @var Ext_Gui2_View_Format_Abstract
	 */
	protected $oFormat;
	
	/**
	 *
	 * @var Ext_Gui2_Bar_Filter
	 */
	protected $_oBasedOnFilter = null;

	public function __construct($mFormat = ''){
		self::$iCount++;

		if($mFormat != ''){
			$this->format = $mFormat; 
		}

		$this->from_id = 'search_time_from_'.self::$iCount;
		$this->until_id = 'search_time_until_'.self::$iCount;

		$oObject = null;

		if(
			$this->format instanceof Ext_Gui2_View_Format_Interface
		){
			$oObject = $this->format;

		} else if(
			is_string($this->format)
		){
			$sTempView = 'Ext_Gui2_View_Format_'.$this->format;
			$oObject = Factory::getObject($sTempView);
		} else {
			throw new Exception("Please use a Ext_Gui2_View_Format_Interface Interface");
		}

		$this->oFormat = $oObject;


	}
	
	public function getElementData(){
		return $this->_aConfig;
	}

	/**
	 * @TODO Sollte mal auf die DateTime-Methoden umgestellt werden
	 *
	 * @param $mFrom
	 * @param $mTo
	 * @param $oGui
	 * @return bool
	 */
	protected function _checkFromAndUntil(&$mFrom, &$mTo, &$oGui) {

		$bReturn = false;

		if(!empty($mFrom)) {
			$bErrorFrom = false;
			try {
				$mFrom = $this->oFormat->convert($mFrom);
			} catch(Exception $e) {
				$bErrorFrom = true;
			}
			$bCheckFrom = WDDate::isDate($mFrom, WDDate::DB_DATE);
			if(!$bCheckFrom) {
				$bErrorFrom = true;
			} else {
				$bErrorFrom = false;
			}
			if($bErrorFrom) {
				$sMessage = 'Fehler bei dem Zeitraum-Filter. Das "von"-Datum (%s) ist kein gültiges Datum.';
				$sMessage = L10N::t($sMessage);
				$sMessage = str_replace('%s', $mFrom, $sMessage);
				$oGui->_oData->aAlertMessages[] = $sMessage;
				$mFrom = '';
            }
		}

		if(!empty($mTo)) {
			$bErrorTo = false;
			try {
				$mTo = $this->oFormat->convert($mTo);
			} catch(Exception $e) {
				$bErrorTo = true;
			}
			$bCheckTo = WDDate::isDate($mTo, WDDate::DB_DATE);
			if(!$bCheckTo) {
				$bErrorTo = true;
			} else {
				$bErrorTo = false;
			}
			if($bErrorTo) {
				$sMessage = 'Fehler bei dem Zeitraum-Filter. Das "bis"-Datum (%s) ist kein gültiges Datum.';
				$sMessage = L10N::t($sMessage);
				$sMessage = str_replace('%s', $mTo, $sMessage);
				$oGui->_oData->aAlertMessages[] = $sMessage;
                $mTo = '';
            }
		}

		if(
			empty($mFrom) &&
			empty($mTo)
		) {
			$oGui->_oData->aAlertMessages[] = L10N::t('Der Zeitraum-Filter kann nicht angewendet werden.', $oGui->gui_description);
			$bReturn = true;
		}

		if(
			!empty($mFrom) &&
			!empty($mTo)
		) {
			$oDate = new WDDate($mFrom, WDDate::DB_DATE);
			$iCompare = $oDate->compare($mTo, WDDate::DB_DATE);
			if($iCompare === 1) {
				$oGui->_oData->aAlertMessages[] = L10N::t('Fehler bei dem Zeitraum-Filter. Das "von"-Datum ist größer als das "bis"-Datum.', $oGui->gui_description);
				$bReturn = true;
			}
		}

		return $bReturn;
	}

	public function checkFromAndUntil(&$mFrom, &$mTo, &$oGui) {
		return $this->_checkFromAndUntil($mFrom, $mTo, $oGui);
	}

	/**
	 * checkFromAndUntil() ist leider total unbrauchbar, wenn man diese im nicht-GUI-Kontext benötigt
	 *
	 * @param $mFrom
	 * @param $mTo
	 * @param $sDescription
	 * @return bool|array
	 * @deprecated
	 */
	public static function checkFromAndUntilStatic(&$mFrom, &$mTo, $sDescription) {

		$mReturn = true;

		$oSelf = new Ext_Gui2_Bar_Timefilter(new Ext_Thebing_Gui2_Format_Date(false, (int)$_SESSION['sid']));
		$oDummy = new stdClass();
		$oDummy->gui_description = $sDescription;

		$oSelf->checkFromAndUntil($mFrom, $mTo, $oDummy);

		// Rückgabewert von checkFromAndUntil() ist unbrauchbar, daher direkt auf gesetzte Fehlermeldung zurückgreifen
		if(isset($oDummy->_oData->aAlertMessages)) {
			$mReturn = reset($oDummy->_oData->aAlertMessages);
		}

		return $mReturn;

	}

	/**
	 *
	 * @param type $mFrom
	 * @param type $mTo
	 * @param \ElasticaAdapter\Facade\Elastica $oWDSearch
	 * @param Ext_Gui2 $oGui
	 * @return type
	 */
	public function setWDSearchQuery($mValue, bool $bNegate, $oWDSearch, $aSearchColumns, $oGui) {

		$mFrom = $mValue[0];
		$mTo = $mValue[1];
		$bReturn = $this->_checkFromAndUntil($mFrom, $mTo, $oGui);

		if($bReturn){
			return;
		}

		$this->db_from_alias	= (array)$this->db_from_alias;
		$this->db_until_alias	= (array)$this->db_until_alias;

		$this->db_from_column	= (array)$this->db_from_column;
		$this->db_until_column	= (array)$this->db_until_column;

		if (empty($this->db_from_column) || empty($this->db_until_column)) {
			throw new RuntimeException('No db_column for filter '.$this->from_id);
		}

		$iCount = 1;
		$iMax = count($this->db_from_column);

        // wenn mehrere Columns sind es oder anweisungen
        if($iMax > 1){
            $oShould = new \Elastica\Query\BoolQuery();
        }
        
		// From/ Until müssen gleich viele Einträge haben!
		foreach((array)$this->db_from_column as $iTKey => $sColumn){

			$sSearchType = $this->search_type;

			$sUntilColumn =  (string)$this->db_until_column[$iTKey];

			// Wenn until nicht angeben dann ist es gleich from
			if(empty($sUntilColumn)){
				$sUntilColumn = $sColumn;
				// suchtyp umschreiben da wenn nur einfeld angeben ist es immer Between ist!
				$sSearchType = 'between';
			}

			$bFirstColumn = false;

			$sColumnFromName = $sColumn;
			$sColumnUntilName = $this->db_until_column[$iTKey];

			switch(strtolower($sSearchType)){
				// ein Feld muss zwischen den Zeiträumen liegen
				case 'between':

					$oQuery = new \Elastica\Query\Range();

					if(
						!empty($mFrom) &&
						!empty($mTo)
					) {
						$oQuery->addField($sColumnFromName, array('gte' => $mFrom, 'lte' => $mTo));
					} elseif(!empty($mFrom)) {
						$oQuery->addField($sColumnFromName, array('gte' => $mFrom));
					} elseif(!empty($mTo)) {
						$oQuery->addField($sColumnFromName, array('lte' => $mTo));
					}

					// Falls until nicht gleich from
					// muss until ebenfalls noch im Zeitraum liegen
					if($sColumn != $sUntilColumn){

						if(
							!empty($mFrom) &&
							!empty($mTo)
						) {
							$oQuery->addField($sColumnUntilName, array('gt' => $mFrom, 'lt' => $mTo));
						} elseif(!empty($mFrom)) {
							$oQuery->addField($sColumnUntilName, array('gt' => $mFrom));
						} elseif(!empty($mTo)) {
							$oQuery->addField($sColumnUntilName, array('lt' => $mTo));
						}
					}

					break;

				// eines der Felder muss den Zeitraum streifen
				case 'contact':
					
					if(
						!empty($mFrom) &&
						!empty($mTo)
					) {
						$oQuery = new \Elastica\Query\BoolQuery();
						
						$oQuery1 = new \Elastica\Query\Range();
						$oQuery1->addField($sColumnUntilName, array('gt' => $mFrom));
						$oQuery2 = new \Elastica\Query\Range();
						$oQuery2->addField($sColumnFromName, array('lt' => $mTo));
						$oQuery->addMust($oQuery1);
						$oQuery->addMust($oQuery2);
					} elseif(!empty($mTo)) {
						$oQuery = new \Elastica\Query\Range();
						$oQuery->addField($sColumnFromName, array('lt' => $mTo));
					} elseif(!empty($mFrom)) {
						$oQuery = new \Elastica\Query\Range();
						$oQuery->addField($sColumnUntilName, array('gt' => $mFrom));
					}

					break;

			}

			$iCount++;
            if(isset($oShould)) {
                $oShould->addShould($oQuery);
            }
		}

        if(isset($oShould)) {
           $oWDSearch->addMustQuery($oShould); 
        } else {
           $oWDSearch->addMustQuery($oQuery); 
        }
		
	
	}
	
	public function setSqlDataByRef($mValue, bool $bNegate, &$aQueryParts, &$aSql, $iKey = 0, &$oGui = null){

		$mFrom = $mValue[0];
		$mTo = $mValue[1];

		if($this->filter_part == 'having') {
			$sWherePart =& $aQueryParts['having'];
			$sPartKeyword = 'HAVING';
		} else {
			$sWherePart =& $aQueryParts['where'];
			$sPartKeyword = 'WHERE';
		}

		$this->_checkFromAndUntil($mFrom, $mTo, $oGui);

		$sValueKey = $this->query_value_key; 

		$sValueKeyFrom = '';
		$sValueKeyUntil = '';
		if(empty($sValueKey)) {
			$sValueKeyFrom = 'filter_from_'.$iKey;
			$sValueKeyUntil = 'filter_until_'.$iKey;
		} else {
			$sValueKeyFrom = $sValueKey.'_from';
			$sValueKeyUntil = $sValueKey.'_until';
		}		
		$aSql[$sValueKeyFrom] = $mFrom;
		$aSql[$sValueKeyUntil] = $mTo;
				
		$bFirstColumn = true;
		
		$this->db_from_alias	= (array)$this->db_from_alias;
		$this->db_until_alias	= (array)$this->db_until_alias;

		$this->db_from_column	= (array)$this->db_from_column;
		$this->db_until_column	= (array)$this->db_until_column;

		// Kein Query 
		if($this->skip_query){
			return true;
		}

		if($sWherePart == '') {
			$sWherePart .= ' '.$sPartKeyword.' ( ';
		} else {
			$sWherePart .= ' AND ( ';
		}

		$iMax = max(count($this->db_from_column), count($this->db_until_column));
		// From/ Until müssen gleich viele Einträge haben!
		for($iTKey=0; $iTKey<$iMax; $iTKey++) {

			$sColumnPart = '';
			
			$sColumn = (string)$this->db_from_column[$iTKey];

			$sSearchType = $this->search_type;

			$sUntilColumn = (string)$this->db_until_column[$iTKey];

			// Wenn until nicht angeben dann ist es gleich from
			if(empty($sUntilColumn)){
				$sUntilColumn = $sColumn;
				// suchtyp umschreiben da wenn nur einfeld angeben ist es immer Between ist!
				$sSearchType = 'between'; 
			}

			// eindeutiger string
			$iColumnKey = $iKey.'_'.$iTKey;

			$bFirstColumn = false;

			$sColumnFromName = $sColumn;
			$sColumnFromString = '#column_from_'.$iColumnKey.'';
			if(!empty($this->db_from_alias[$iTKey])){
				$sColumnFromString = '#alias_from_'.$iColumnKey.'.'.$sColumnFromString;
				$aSql['alias_from_'.$iColumnKey] = $this->db_from_alias[$iTKey];
			}

			$sColumnUntilName = $this->db_until_column[$iTKey];
			$sColumnUntilString = '#column_until_'.$iColumnKey.'';
			if(!empty($this->db_until_alias[$iTKey])){
				$sColumnUntilString = '#alias_until_'.$iColumnKey.'.'.$sColumnUntilString;
				$aSql['alias_until_'.$iColumnKey] = $this->db_until_alias[$iTKey];
			}

			$aSql['column_from_'.$iColumnKey] = $sColumnFromName;
			$aSql['column_until_'.$iColumnKey] = $sColumnUntilName;

			switch(strtolower($sSearchType)) {
				// ein Feld muss zwischen den Zeiträumen liegen
				case 'between':

					if(!empty($sColumn)) {
						if(
							!empty($mFrom) &&
							!empty($mTo)
						) {
							$sColumnPart .= ' ( '.$this->_buildComparePart($sColumnFromString).' >= :'.$sValueKeyFrom.' AND
								'.$this->_buildComparePart($sColumnFromString).' <= :'.$sValueKeyUntil.' ) ';
						} elseif(!empty($mFrom)) {
							$sColumnPart .= ' ( '.$this->_buildComparePart($sColumnFromString).' >= :'.$sValueKeyFrom.' ) ';
						} elseif(!empty($mTo)) {
							$sColumnPart .= ' ( '.$this->_buildComparePart($sColumnFromString).' <= :'.$sValueKeyUntil.' ) ';
						}
						if($sColumn != $sUntilColumn) {
							$sColumnPart .= ' AND ';
						}
					}

					// Falls until nicht gleich from
					// muss until ebenfalls noch im Zeitraum liegen
					if($sColumn != $sUntilColumn) {
						if(
							!empty($mFrom) &&
							!empty($mTo)
						) {
							$sColumnPart .= ' ( '.$this->_buildComparePart($sColumnUntilString).' >= :'.$sValueKeyFrom.' AND
								'.$this->_buildComparePart($sColumnUntilString).' <= :'.$sValueKeyUntil.' ) ';
						} elseif(!empty($mFrom)) {
							$sColumnPart .= ' ( '.$this->_buildComparePart($sColumnUntilString).' >= :'.$sValueKeyFrom.' ) ';
						} elseif(!empty($mTo)) {
							$sColumnPart .= ' ( '.$this->_buildComparePart($sColumnUntilString).' <= :'.$sValueKeyUntil.' ) ';
						}
					}
					
					break;

				// ein Feld muss zwischen den Zeiträumen liegen
				case 'input_between':

					// Falls until nicht gleich from
					// muss until ebenfalls noch im Zeitraum liegen
					if($sColumn != $sUntilColumn) {
						if(
							!empty($mFrom) &&
							!empty($mTo)
						) {
							$sColumnPart .= ' (('.$this->_buildComparePart($sColumnFromString).' <= :'.$sValueKeyFrom.' OR '.$this->_buildComparePart($sColumnFromString).' = 0000-00-00) AND
								('.$this->_buildComparePart($sColumnUntilString).' >= :'.$sValueKeyUntil.' OR '.$this->_buildComparePart($sColumnUntilString).' = 0000-00-00)) ';
						} elseif(!empty($mFrom)) {
							$sColumnPart .= ' ('.$this->_buildComparePart($sColumnFromString).' <= :'.$sValueKeyFrom.' OR '.$this->_buildComparePart($sColumnFromString).' = 0000-00-00) ';
						} elseif(!empty($mTo)) {
							$sColumnPart .= ' ('.$this->_buildComparePart($sColumnUntilString).' >= :'.$sValueKeyUntil.' OR '.$this->_buildComparePart($sColumnUntilString).' = 0000-00-00) ';
						}
					}
					
					break;

				// eines der Felder muss den Zeitraum streifen
				case 'contact':

					if(
						!empty($mFrom) &&
						!empty($mTo)
					) {
						$sColumnPart .= '(		 '.$this->_buildComparePart($sColumnFromString).' <= :'.$sValueKeyUntil.' AND
							'.$this->_buildComparePart($sColumnUntilString).' >= :'.$sValueKeyFrom.'  )';
					} elseif(!empty($mTo)) {
						$sColumnPart .= '(		 '.$this->_buildComparePart($sColumnFromString).' <= :'.$sValueKeyUntil.'  )';
					} elseif(!empty($mFrom)) {
						$sColumnPart .= '(		 '.$this->_buildComparePart($sColumnUntilString).' >= :'.$sValueKeyFrom.'  )';
					}
					
					break;
				default:
					
					break;
					
			}
			
			$aWhereParts[] = $sColumnPart;
			
		}

		$sWherePart .= implode(' OR ', $aWhereParts);
		$sWherePart .= ' ) ';

	}

	protected function _buildComparePart($sColumnString) {

		$sPartPrefix = '';
		$sPartSuffix = '';

		if($this->data_function) {
			$sPartPrefix .= $this->data_function.'(';
		}

		if($this->use_coalesce) {
			$sPartPrefix .= 'COALESCE(';
			$sPartSuffix .= ', 0000-00-00)';
		}

		if($this->data_function) {
			$sPartSuffix .= ')';
		}

		$sPart = $sPartPrefix.$sColumnString.$sPartSuffix;

		return $sPart;

	}

	/**
	 * @TODO Entfernen mit Filtersets
	 * @deprecated
	 *
	 * @return Daten vorbereiten anhand des DesignElements 
	 */
	protected function _prepareElementFromDesignElement()
	{
		$oDesignElement				= $this->_oDesignElement;
		
		$aDBColumn					= $oDesignElement->getAllBasedOn(true);
		$aDBAlias					= (array)$oDesignElement->getAlias();

		$sColumn					= key($aDBColumn);
		$sAlias						= reset($aDBAlias);
		
        // alias rausnehmen da wir den allias seperat setzten
        $aBasedOn                   = explode('.', $sColumn);

        $sColumn                    = end($aBasedOn);
        
		$aFromUntilData				= $this->getFromUntilData($sColumn);

		$sFromColumn				= $aFromUntilData['from_column'];
		$sUntilColumn				= $aFromUntilData['until_column'];

		$sSearchType				= $this->getSearchType($sColumn);

		$this->db_from_column		= $sFromColumn;
		$this->db_from_alias		= $sAlias;
		$this->db_until_column		= $sUntilColumn;
		$this->db_until_alias		= $sAlias;
		$this->search_type			= $sSearchType;
		
		// Damit es schneller läuft. Ist aber notwendig, wenn nicht beide Felder immer gefüllt sind oder auch Uhrzeit enthalten ist.
		$this->use_coalesce = false;
		$this->data_function = false;
		
		if($oDesignElement->display_label === true)
		{
			$this->label         = $sLabel;
		}
		
		$this->default_from      = $oDesignElement->getDefaultFilterFrom(true);
		$this->default_until     = $oDesignElement->getDefaultFilterUntil(true);
		$this->id				 = 'flex_filter_'.$sColumn.'_'.$sAlias;

		return true;
	}

	/**
	 * Basierend auf Select Informationen anhand des DesignElements & Zeitfilter Elements setzen
	 * 
	 * @param Ext_Gui2_Bar_Filter $oBasedOnFilter 
	 */
	public function addBasedOnFilter(Ext_Gui2_Bar_Filter $oBasedOnFilter)
	{

		$this->_oBasedOnFilter			= $oBasedOnFilter;

		if($this->_oDesignElement !== null)	{
			$aDBColumn						= $this->_oDesignElement->getAllBasedOn(true);
			$oBasedOnFilter->select_options	= $aDBColumn;	
		}

		$oBasedOnFilter->id				= $this->id.'_basedon';
		$oBasedOnFilter->label			= L10N::t('basierend auf');		
		
	}

	/**
	 * @return Ext_Gui2_Bar_Filter
	 * 
	 */
	public function getBasedOnFilter() {
		return $this->_oBasedOnFilter;
	}

	/**
	 * Falls from_column & until_column angegeben sind 
	 * 
	 * @param string $sColumn
	 * @return array 
	 */
	public function getFromUntilData($sColumn)
	{
		$sFromColumn	= $sColumn;
		$sUntilColumn	= $sColumn;

		if(
			$this->_oDesignElement !== null ||
			$this->_oBasedOnFilter !== null
		){
			if($this->_oDesignElement) {
				$sFromColumnDesignData = $this->_oDesignElement->getFilterSetOption($sColumn, 'from_column');
			} elseif(
				$this->_oBasedOnFilter !== null &&
				$this->_oBasedOnFilter instanceof Ext_Gui2_Bar_Timefilter_BasedOn
			) {
				$sFromColumnDesignData = $this->_oBasedOnFilter->getColumnConfig($sColumn, 'from_column');
				if(empty($sFromColumnDesignData)) {
					$sFromColumnDesignData = $this->_oBasedOnFilter->getColumnConfig($sColumn, 'column');
				}
			}
			
			if(!empty($sFromColumnDesignData))
			{
				$sFromColumn = $sFromColumnDesignData;
			}
			
			if($this->_oDesignElement) {
				$sUntilColumnDesignData = $this->_oDesignElement->getFilterSetOption($sColumn, 'until_column');
			} elseif(
				$this->_oBasedOnFilter !== null &&
				$this->_oBasedOnFilter instanceof Ext_Gui2_Bar_Timefilter_BasedOn
			) {
				$sUntilColumnDesignData = $this->_oBasedOnFilter->getColumnConfig($sColumn, 'until_column');
				if(empty($sUntilColumnDesignData)) {
					$sUntilColumnDesignData = $this->_oBasedOnFilter->getColumnConfig($sColumn, 'column');
				}
			}
			
			if(!empty($sUntilColumnDesignData))
			{
				$sUntilColumn = $sUntilColumnDesignData;
			}
		}
		
		return array(
			'from_column'	=> $sFromColumn,
			'until_column'	=> $sUntilColumn,
		);
	}
	
	/**
	 * Suchmodus
	 * 
	 * @param string $sColumn
	 * @return string 
	 */
	public function getSearchType($sColumn)
	{
		$sSearchType	= 'between';
		
		if(
			$this->_oDesignElement !== null ||
			$this->_oBasedOnFilter !== null
		)
		{
			if($this->_oDesignElement !== null) {
				$sSearchTypeDesignData = $this->_oDesignElement->getFilterSetOption($sColumn, 'search_type');
			} elseif(
				$this->_oBasedOnFilter !== null &&
				$this->_oBasedOnFilter instanceof Ext_Gui2_Bar_Timefilter_BasedOn
			) {
				$sSearchTypeDesignData = $this->_oBasedOnFilter->getColumnConfig($sColumn, 'search_type');
			}
			
			if(!empty($sSearchTypeDesignData))
			{
				$sSearchType = $sSearchTypeDesignData;
			}
		}
		
		return $sSearchType;
	}

	public function hasValue($mValue): bool {

		if (!is_array($mValue)) {
			throw new InvalidArgumentException('Wrong value type for timefilter: '.$mValue);
		}

		return !empty($mValue[0]) || !empty($mValue[1]);

	}

	/**
	 * @see \Ext_Gui2_Data::_setFilterElementDataByRef()
	 */
	public function convertSaveValue($value): string {

		// String: JavaScript encodeURIComponent()
		if (!is_array($value)) {
			$value = explode(',', $value);
		}

		$from = $this->convertSaveValueDate($value[0] ?? '');
		$until = $this->convertSaveValueDate($value[1] ?? '');

		$values = ['from' => $from, 'until' => $until];

		return json_encode($values);

	}

	private function convertSaveValueDate($value): ?string {

		$period = null;
		$value = $this->oFormat->convert($value);

		// Absolutes Datum zu ISO-Periode konvertieren
		try {
			$now = \Carbon\Carbon::now()->startOfDay();
			$value = \Carbon\Carbon::createFromFormat('Y-m-d', $value)->startOfDay();

			// Die Differenz von now() holen da diese auch wieder auf now() addiert wird, ansonsten kann es zu Fehlern kommen
			$diff = $now->diff($value);
			// Weder PHP noch Carbon haben eine Methode dafür
			$period = $diff->format(sprintf('P%dY%dM%dD', $diff->y, $diff->m, $diff->d));
			// Format aufräumen: Immer mit den Einheiten, damit z.B. nicht P30D (0D) zu P3 wird
			$period = str_replace(['M0D', 'Y0M', 'P0Y'], ['M', 'Y', 'P'], $period);
			if ($period === 'P') {
				// Falls Start/Ende = heute, tilgt das str_replace dennoch alle Werte
				$period = 'P0D';
			}
			if ($value < $now) {
				$period = '-'.$period;
			}
		} catch (\Carbon\Exceptions\InvalidFormatException) {

		}

		return $period;

	}

	public function prepareSaveValue($value): array {

		$value = json_decode($value, true);

		$from = $this->formatSaveValueDate(\Illuminate\Support\Arr::get($value, 'from'));
		$until = $this->formatSaveValueDate(\Illuminate\Support\Arr::get($value, 'until'));

		return [$from, $until];

	}

	public function formatSaveValueDate(?string $value): ?string {

		if (empty($value)) {
			return $this->initial_value[0]; // '' !== null
		}

		$negate = false;
		if (str_starts_with($value, '-')) {
			$negate = true;
			$value = substr($value, 1);
		}

		$interval = new DateInterval($value);
		if ($negate) {
			$interval->invert = 1;
		}

		$date = \Carbon\Carbon::now()->startOfDay()->add($interval);

		return $this->oFormat->formatByValue($date);

	}

}