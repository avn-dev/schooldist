<?php
 
/**
 * Filter class
 *
 * @property int $width
 * @property string $name
 * @property string $class
 * @property string|array $db_column
 * @property string|array $db_alias
 * @property string $db_type
 * @property string $db_operator
 * @property string $db_emptysearch
 * @property array $select_options
 * @property array $select_navigation array('default'=>'XXX')
 * @property bool|int $checked
 * @property Ext_Gui2_View_Format_Interface $format
 * @property mixed $access
 * @property array|string $filter_query
 * @property string $filter_join
 * @property string $filter_part
 * @property \Elastica\Query\AbstractQuery[] $filter_wdsearch
 * @property bool $skip_query
 * @property string $query_value_key
 * @property bool $required
 * @property $dependency
 * @property $visibility
 * @property $multiple
 * @property $size
 * @property $placeholder
 * @property bool $show_in_bar
 * @property ?string $additional_html
 * @property bool $simple
 */
class Ext_Gui2_Bar_Filter extends Ext_Gui2_Bar_Filter_Abstract {
	
	// Konfigurationswerte setzten
	protected $_aConfig = array(
		'element_type'		=> 'filter',
		'width'				=> '',
		'name'				=> '',
		'id'				=> '',
		'class'				=> '',
		'label'				=> '',
		'value'				=> '',			// vorauswahl
		'db_column'			=> '',			// spalte in der gesucht werden soll (arrays möglich)
		'db_alias'			=> '',			// Alias für den query (arrays möglich)
		'db_type'			=> 'string',	// Datentype
		'db_operator'		=> 'like',		// sagt wie im query abgefragt werden soll
		'db_emptysearch'	=> 0,			// Sagt ob die suche auch mit leeren werten geschehen soll
		'filter_type'		=> 'input',		// Art des Filters, input,checkbox, select
		'select_options'	=> array(),		// Array mit options ( value=>text )
		'selection'			=> '',			// Selection Klasse
		'checked'			=> 0,
		'format'			=> 'Text',
		'access'			=> '',			// recht für das icon,
		'filter_query'		=> '',
		'filter_join'		=> '',
		'filter_part'		=> 'where',
		'filter_wdsearch'	=> array(), // Elastica_Query_Abstract object or array with value => object
		'skip_query'		=> false,
		'query_value_key'	=> null,
		'select_navigation' => array('default_value' => ''), // wenn default_value gesetzt wird, ist das Select navigierbar
		'required'			=> false,
		'dependency' => false,
		'visibility' => true, // Diese Eigenschaft heißt visibility und nicht visible, weil visible durch einen Sonderfall immer auf 1 gesetzt wird...
		'multiple' => null, // Sollte nicht explizit auf true gesetzt werden, da bei neuen Filtern alle Selects normalerweise multiple sind, wenn nichts Komisches im Filter passiert!
		'size' => null,
		'placeholder' => null,
		'sidebar' => false, // Neue Filter
		'show_in_bar' => false, // Neue Filter: Immer in der Bar anzeigen
		'additional_html' => null, // Neue Filter: Zusätzliches HTML neben Filter (wenn show_in_bar)
		'initial_value' => '', // Neue Filter: Leerwert/Wert für Reset; Standard '', da <option value> und input leere Strings in Query-Param schicken
		'simple' => null, // Neue Filter: Fallback auf einfache Elemente, z.B. einfaches Select
		'negateable' => null, // Neue Filter: ist/ist nicht verfügbar
		'sort_order' => 2
	);

	static protected $iCount = 0;

	protected $oFormat;

	public function __construct($sFilterType = 'input', $mFormat = ''){

		if($mFormat != ''){
			$this->format = $mFormat;
		}

		$this->filter_type = $sFilterType;

		$this->id = 'search_'.(int)self::$iCount.'';

		self::$iCount++;

		if(
			$this->format instanceof Ext_Gui2_View_Format_Interface
		){
			$oObject = $this->format;

		} else if(
			is_string($this->format)
		){
			$sTempView = 'Ext_Gui2_View_Format_'.$this->format;
			$oObject = new $sTempView();
		} else {
			throw new Exception("Please use a Ext_Gui2_View_Format_Interface Interface");
		}

		$this->oFormat = $oObject;

	}
	
	public function getElementData(){
		return $this->_aConfig;
	}

	/**
	 *
	 * @param mixed $mValue
	 * @param \ElasticaAdapter\Facade\Elastica $oWDSearch
	 * @param Ext_Gui2 $oGui
	 */
	public function setWDSearchQuery(mixed $mValue, bool $bNegate, $oWDSearch, $aSearchColumns, $oGui) {

		if(
			$this->skip_query
		){
			return true;
		}

        $aWDSearchQueries = $this->filter_wdsearch;
		if($this->id == 'wdsearch'){
			$sSearchString = \ElasticaAdapter\Facade\Elastica::escapeTerm($mValue);
			$oWDSearch->addMustQuery($sSearchString, $aSearchColumns);
		} else if(!empty($aWDSearchQueries)) {
			if(is_array($this->filter_wdsearch)){
				if (!is_string($mValue)) {
					throw new RuntimeException('Wrong type for filter_wdsearch');
				}
				$mQueryFilter = $this->filter_wdsearch[$mValue];
				$oWDSearch->addMustQuery($mQueryFilter, $aSearchColumns);
			} else {			
				$oWDSearch->addMustQuery($this->filter_wdsearch, $aSearchColumns);
			}
			
		} else if($this->filter_query != '') {
			throw new Exception('Filter of Type "Filter Query" isnt support by WDSearch!');
		} else {

			$this->db_alias = (array)$this->db_alias;
            
            $oFinalQuery = new \Elastica\Query\BoolQuery();

			$sOperator = $this->db_operator;

			if(is_array($sOperator) && is_string($mValue)) {
				$sOperator = $this->db_operator[$mValue];
			}

			if ($bNegate) {
				$sOperator = $this->negateOperator($sOperator);
			}

			$mapping = $oWDSearch->getIndex()->getMapping();

			foreach((array)$this->db_column as $iTKey => $sColumn) {

	//			$mStrictValue = \ElasticaAdapter\Facade\Elastica::escapeTerm($mValue); // Wird nur für QueryString benötigt, darf aber nicht bei Term passieren
				$mStrictValue = $mValue;

				$oBolQuery = new \Elastica\Query\BoolQuery();

				if (
					$mStrictValue !== null &&
					$mStrictValue !== "" &&
					!is_array($mStrictValue) &&
					(
						isset($mapping['properties'][$sColumn]['type']) &&
						$mapping['properties'][$sColumn]['type'] == 'boolean'
					)
				) {
					if ($mStrictValue == 'no' || $mStrictValue == 'yes') {
						$mStrictValue = $mStrictValue == 'yes' ? true : false;
					} else {
						$mStrictValue = (bool)$mStrictValue;
					}
				}

				switch(strtolower($sOperator)){
					case 'like_strict':
						$oQuery = $oWDSearch->getFieldQuery($sColumn, ElasticaAdapter\Facade\Elastica::escapeTerm($mStrictValue));
						$oBolQuery->addMust($oQuery);
						break;
					case 'notlike_strict':
						$oQuery = $oWDSearch->getFieldQuery($sColumn, ElasticaAdapter\Facade\Elastica::escapeTerm($mStrictValue));
						$oBolQuery->addMustNot($oQuery);
						break;
					case 'like':
                      $oQuery = $oWDSearch->getFieldQuery($sColumn, ElasticaAdapter\Facade\Elastica::escapeTerm($mStrictValue.'*'));
						$oBolQuery->addMust($oQuery);
						break;
					case 'notlike':
						$oQuery = $oWDSearch->getFieldQuery($sColumn, ElasticaAdapter\Facade\Elastica::escapeTerm($mStrictValue.'*'));
						$oBolQuery->addMustNot($oQuery);
						break;
					case '>':
						$oQuery = new \Elastica\Query\Range();
						$oQuery->addField($sColumn, array('gt' => $mStrictValue));
						$oBolQuery->addMust($oQuery);
						break;
					case '>=':
						$oQuery = new \Elastica\Query\Range();
						$oQuery->addField($sColumn, array('gte' => $mStrictValue));
						$oBolQuery->addMust($oQuery);
						break;
					case '<':
						$oQuery = new \Elastica\Query\Range();
						$oQuery->addField($sColumn, array('lt' => $mStrictValue));
						$oBolQuery->addMust($oQuery);
						break;
					case '<=':
						$oQuery = new \Elastica\Query\Range();
						$oQuery->addField($sColumn, array('lte' => $mStrictValue));
						$oBolQuery->addMust($oQuery);
						break;
					case '!=':
						if ($this->multiple) {
							$oQuery = new Elastica\Query\Terms($sColumn, (array)$mStrictValue);
						} else {
							$oQuery = new \Elastica\Query\Term();
							$oQuery->setTerm($sColumn, $mStrictValue);
						}
						$oBolQuery->addMustNot($oQuery);
						break;
					case '=':
					default:
						if ($this->multiple) {
							$oQuery = new Elastica\Query\Terms($sColumn, (array)$mStrictValue);
						} else {
							$oQuery = new \Elastica\Query\Term();
							$oQuery->setTerm($sColumn, $mStrictValue);
						}
						$oBolQuery->addMust($oQuery);
						break;

				}
				$oFinalQuery->addShould($oBolQuery);
			}
            $aColumns = $this->db_column;
            if(!empty($aColumns)){
                $oWDSearch->addMustQuery($oFinalQuery);
            }
		}

	}
	
	public function setSqlDataByRef($mValue, bool $bNegate, &$aQueryParts, &$aSql, $iKey = 0, &$oGui = null){

		if(
			$this->skip_query &&
			$this->filter_query == ''
		){
			return true;
		}

		if(strtolower($this->filter_part) == 'having') {
			$sWherePart =& $aQueryParts['having'];
			$sPartKeyword = 'HAVING';
		} else {
			$sWherePart =& $aQueryParts['where'];
			$sPartKeyword = 'WHERE';
		}

		$sValueKey = $this->query_value_key;

		if(empty($sValueKey)) {
			$sValueKey = 'filter_'.$iKey;
		}

		if(
			!empty($this->filter_join) &&
			$this->hasValue($mValue)
		) {
			$aQueryParts['from_additional'] .= $this->filter_join;
		}

		if($this->filter_query != '') {

			if(is_array($this->filter_query)){

				$mQueryFilter = $this->filter_query[$mValue];

				if(is_array($mQueryFilter)){
					$sQueryFilter	= $mQueryFilter['query'];
					$sPart			= strtolower($mQueryFilter['part']);
					if($sPart == 'having') {
						$sWherePart =& $aQueryParts['having'];
						$sPartKeyword = 'HAVING';
					}else{
						$sWherePart =& $aQueryParts['where'];
						$sPartKeyword = 'WHERE';
					}
				}else{
					$sQueryFilter = $mQueryFilter;
				}

			} else {
				$sQueryFilter = $this->filter_query;
			}

			$sQueryFilter = str_replace('{value}', ':'.$sValueKey, $sQueryFilter);

			if(
				strlen($sQueryFilter) > 0
			)
			{
				$aSql[$sValueKey] = $mValue;

				if($this->skip_query){
					return true;
				}

				if($sWherePart == ''){
					if ($bNegate) {
						throw new RuntimeException('negate is not implemented (filter_query)');
					}
					$sWherePart .= ' '.$sPartKeyword.' '.$sQueryFilter;
				} else {
					$sNot = $bNegate ? 'NOT' : '';
					$sWherePart .= ' AND '.$sNot.' ( '.$sQueryFilter.' )';
				}
			}

		} else {

			$bFirstColumn = true;

			$this->db_alias = (array)$this->db_alias;

			foreach((array)$this->db_column as $iTKey => $sColumn) {

				$sKey = $sValueKey.'_'.$iTKey;

				if($sWherePart == ''){
					$sWherePart .= ' '.$sPartKeyword.' ( ( ';
				} else if( $bFirstColumn ) {
					$sWherePart .= ' AND ( ( ';
				} else {
					$sWherePart .= ' OR ( ';
				}

				$bFirstColumn = false;

				$sAlias = $this->db_alias[$iTKey];

				if(!empty($sAlias)){
					$sWherePart .= '`'.$sAlias.'`.';
				}

				if(is_array($mValue)) {

					if ($this->db_operator !== '=') {
						throw new RuntimeException('Wrong db_operator for filter with multiple values');
					}

					$sNegateOperator = $bNegate ? 'NOT' : '';
					$sWherePart .= '`'.$sColumn.'` '.$sNegateOperator.' IN (:'.$sKey.') ';
					$aSql[$sKey] = $mValue;

				} else {

					switch(strtolower($this->db_type)){
						case 'int':
							$mStrictValue = (int)$mValue;
							break;
						case 'float':
							$mStrictValue = (float)$mValue;
							break;
						case 'string':
							$mStrictValue = (string)$mValue;
							break;
						default:
							$mStrictValue = $mValue;
							break;
					}

					$sOperator = $this->db_operator;

					if(is_array($sOperator)){
						$sOperator = $this->db_operator[$mStrictValue];
					}

					if ($bNegate) {
						$sOperator = $this->negateOperator($sOperator);
					}

					switch(strtolower($sOperator)){
						case 'like_strict':
							$sWherePart .= '`'.$sColumn.'` LIKE :'.$sKey.' ';
							$mStrictValue = str_replace('*', '%', $mStrictValue);
							$aSql[$sKey] = $mStrictValue;
							break;
						case 'notlike_strict':
							$sWherePart .= '`'.$sColumn.'` NOT LIKE :'.$sKey.' ';
							$mStrictValue = str_replace('*', '%', $mStrictValue);
							$aSql[$sKey] = $mStrictValue;
							break;
						case 'like':
							$sWherePart .= '`'.$sColumn.'` LIKE :'.$sKey.' ';
							$aSql[$sKey] = '%'.$mStrictValue.'%';
							break;
						case 'notlike':
							$sWherePart .= '`'.$sColumn.'` NOT LIKE :'.$sKey.' ';
							$aSql[$sKey] = '%'.$mStrictValue.'%';
							break;
						case '>':
							$sWherePart .= '`'.$sColumn.'` > :'.$sKey.' ';
							$aSql[$sKey] = $mStrictValue;
							break;
						case '>=':
							$sWherePart .= '`'.$sColumn.'` >= :'.$sKey.' ';
							$aSql[$sKey] = $mStrictValue;
							break;
						case '<':
							$sWherePart .= '`'.$sColumn.'` < :'.$sKey.' ';
							$aSql[$sKey] = $mStrictValue;
							break;
						case '<=':
							$sWherePart .= '`'.$sColumn.'` <= :'.$sKey.' ';
							$aSql[$sKey] = $mStrictValue;
							break;
						case '!=':
							$sWherePart .= '`'.$sColumn.'` != :'.$sKey.' ';
							$aSql[$sKey] = $mStrictValue;
							break;
						case '&':
							$sWherePart .= '`'.$sColumn.'` & :'.$sKey.' ';
							$aSql[$sKey] = $mStrictValue;
							break;
						case '=':
						default:
							$sWherePart .= '`'.$sColumn.'` = :'.$sKey.' ';
							$aSql[$sKey] = $mStrictValue;
							break;

					}

				}

				$sWherePart .= ' ) ';
			}

			$sWherePart .= ' ) ';
		}
		
	}

	private function negateOperator(string $sOperator): string {
		return match ($sOperator) {
			'=' => '!='
		};
	}

	/**
	 * Prüfen, ob dieser Filter einen Wert hat (damit der Filter im Query eingebaut wird)
	 *
	 * @param mixed $mValue
	 * @return bool
	 */
	public function hasValue($mValue): bool {

		// Neue Filter, neue Logik
		if ($this->sidebar) {
			// Wenn initial_value nicht leer, dann mit initial_value vergleichen
			// Wurde initial_value aber auch etwas anderes gesetzt, muss hier $mValue direkt überprüft werden (wie früher)
			if (
				(
					empty($this->initial_value) &&
					$mValue !== $this->initial_value
				) ||
				!empty($mValue)
			) {
				return true;
			}
		}

		if(
			$mValue != 'xNullx' && (
				!empty($mValue) ||
				$this->db_emptysearch != 0
			)
		) {
			return true;
		}

		return false;

	}

	public function convertSaveValue($value): string {

		if ($this->multiple) {
			$value = json_encode($value);
		}

		return (string)$value;

	}

	public function prepareSaveValue($value): mixed {

		if ($this->multiple) {
			return (array)json_decode($value, true);
		}

		return $value;

	}

}