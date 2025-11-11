<?php

namespace ElasticaAdapter\Facade;

use Elastica\Query\AbstractQuery;
use Elastica\Query\BoolQuery;
use Elastica\Query\Ids;
use Elastica\Query\MatchAll;
use Elastica\Query\MultiMatch;
use Elastica\Query\QueryString;
use Elastica\ResultSet;
use Elastica\Scroll;
use Elastica\Search;
use ElasticaAdapter\Adapter\Client;
use ElasticaAdapter\Adapter\Index;
use ElasticaAdapter\Iterator\Collection;

class Elastica
{
	/**
	 * @var Client
	 */
	protected static ?Client $oClient = null;

	/**
	 * @var Index
	 */
	protected Index $oIndex;

	/**
	 * @var ResultSet
	 */
	protected ResultSet $oLastResult;

	/**
	 * Array mit alles Suchfeldern
	 */
	protected array $aSearchFields = array('*');

	/**
	 * Array mit alles Datenfeldern
	 */
	protected array $aDataFields = array();

	/**
	 * Array mit alles Queries
	 */
	protected array $aQueries = array();

	/**
	 * Array mit alles Filter
	 */
	protected array $aFilters = array();

	/**
	 * Anzahl der Mindesttreffer wenn "Should" Queries definiert wurden
	 */
	protected int $iMinimumNumberShouldMatch = 1;

	/**
	 * Offset für die Abfrage
	 */
	protected int $iOffset = 0;

	/**
	 * Limit für die Abfrage
	 */
	protected int $iLimit = 30;

	/**
	 * Sortierungsfeld
	 */
	protected array $aSortFields = [];

	/**
	 * Wenn dieser Parameter an ist wird ein zusätzliches Array im Result zur verfügung stehen
	 * in dem Alle spalten in denen etwas gefunden wurde enthalten sind
	 * außderdem sind die gesuchten "Wortteile" im gesamt String mit einem <em></em> makiert
	 */
	protected bool $bHighlight = false;

	/**
	 * @return Client
	 */
	public static function getClient(): Client
	{
		if (self::$oClient === null) {
			self::$oClient = new Client();
		}

		return self::$oClient;
	}

	public function __construct(string $sIndexName, string $sTypeName = '')
	{
		$this->setIndex($sIndexName, $sTypeName);
	}

	public function setIndex(string $sIndexName): void
	{
		// Index erzeugen
		$this->oIndex = new Index($sIndexName);
	}

	/**
	 * @return Index
	 */
	public function getIndex(): Index
	{
		return $this->oIndex;
	}

	/**
	 * Anzahl der mindesttreffer wenn "Should" Queries definiert wurden
	 *
	 * @param int $iNumber
	 */
	public function setMinimunNumberShouldMatch(int $iNumber): void
	{
		$this->iMinimumNumberShouldMatch = $iNumber;
	}

	/**
	 * Setzt die Sortierung
	 *
	 * @param string|array $mSort
	 */
	public function setSort(string|array $mSort): void
	{
		if (is_string($mSort)) {
			$mSort = [$mSort => 'desc'];
		}

		$this->aSortFields = $mSort;
	}

	/**
	 * Wenn dieser Parameter an ist wird ein zusätzliches Array im Result zur verfügung stehen
	 * in dem Alle spalten in denen etwas gefunden wurde enthalten sind
	 * außderdem sind die gesuchten "Wortteile" im gesamt String mit einem <em></em> makiert
	 *
	 * @param bool $bHighlight
	 */
	public function setHighlight(bool $bHighlight = true): void
	{
		$this->bHighlight = $bHighlight;
	}

	/**
	 * ACHTUNG ES KANN KEIN LIMIT = 0 gesetzt werden!!
	 * erwürde sonst WIRKLICH limit 0 machen ;)
	 *
	 * @param int $iLimit
	 * @param int $iOffset
	 */
	public function setLimit(int $iLimit, int $iOffset = 0): void
	{
		if ($iLimit > 0) {
			$this->iLimit = $iLimit;
		}
		$this->iOffset = $iOffset;
	}

	/**
	 * set all Field for Data Output and the Search
	 * if the Search Field Array is empty it will search over ALL fields of the Dokument
	 *
	 * @param array $aDataFields
	 * @param array $aSearchfields
	 */
	public function setFields(array $aDataFields, array $aSearchfields = []): void
	{
		$this->aDataFields = $aDataFields;
		$this->aSearchFields = $aSearchfields;
	}

	/**
	 * fügt einen Query hinzu der "erfühlt sein muss"
	 *
	 * @param string|AbstractQuery|array $mQuery
	 * @param array $aSearchFields
	 */
	public function addMustQuery(string|AbstractQuery|array $mQuery, array $aSearchFields = []): void
	{
		$this->addQuery($mQuery, $aSearchFields, 'must');
	}

	/**
	 * fügt einen Query hinzu der "NICHT erfühlt sein darf"
	 *
	 * @param string|AbstractQuery|array $mQuery
	 * @param array $aSearchFields
	 */
	public function addMustNotQuery(string|AbstractQuery|array $mQuery, array $aSearchFields = []): void
	{
		$this->addQuery($mQuery, $aSearchFields, 'must_not');
	}

	/**
	 * fügt einen Query hinzu der "erfühlt sein darf KANN"
	 * behachtet hier setMinimunNumberShouldMatch()
	 *
	 * @param string|AbstractQuery|array $mQuery
	 * @param array $aSearchFields
	 */
	public function addShouldQuery(string|AbstractQuery|array $mQuery, array $aSearchFields = []): void
	{
		$this->addQuery($mQuery, $aSearchFields, 'should');
	}

	/**
	 * fügt einen Query hinzu
	 *
	 * @param string|AbstractQuery|array $mQuery
	 * @param array $aSearchFields
	 * @param string $sType
	 */
	public function addQuery(string|AbstractQuery|array $mQuery, array $aSearchFields = [], string $sType = 'must'): void
	{
		if (is_string($mQuery)) {
			$mQuery = $this->replaceTokenspacer($mQuery);
		}

		// WENN AND und ORs
		if (is_array($mQuery)) {
			$oBool = new BoolQuery();
			$oBool->setMinimumShouldMatch(1);

			// OR Ebene durchlaufen
			foreach((array)$mQuery as $aQueryORPart) {

				$oBoolAnd = new BoolQuery();
				$iOrParts = 0;
				//ANDS durchgehen
				foreach((array)$aQueryORPart as $sQueryANDPart) {
					if(!empty($sQueryANDPart)) {
						$sQueryANDPart = trim($sQueryANDPart);
						$sQueryANDPart = $this->rstrtrim($sQueryANDPart, ' AND');
						$oQuery = $this->getExactSearch($sQueryANDPart, $aSearchFields);
						$oBoolAnd->addMust($oQuery);
						$iOrParts++;
					}
				}
				if($iOrParts > 0) {
					$oBool->addShould($oBoolAnd);
				}
			}

			if(
				count($mQuery) == 1 &&
				$iOrParts == 1
			) {
				$mQuery = $oQuery;
			} else {
				if(
					count($mQuery) == 1 &&
					$iOrParts > 1
				) {
					$mQuery = $oBoolAnd;
				} else {
					$mQuery = $oBool;
				}
			}

		}

		if(
			empty($aSearchFields) &&
			!empty($this->aSearchFields)
		) {
			$aSearchFields = $this->aSearchFields;
		}

		if(!empty($mQuery)) {
			$this->aQueries[] = array('query' => $mQuery, 'fields' => $aSearchFields, 'type' => $sType);
		}
	}

	/**
	 * fügt einen Query hinzu der sich auf eine UID
	 * (Eindeutige Suchdocument ID) bezieht
	 *
	 * @param string $sId
	 */
	public function addUIDQuery(string $sId): void
	{
		$oQuery = new Ids();
		$oQuery->addId($sId);

		if (!empty($oQuery)) {
			$this->aQueries[] = array('query' => $oQuery, 'fields' => '_id', 'type' => 'must');
		}
	}

	/**
	 * Fügt eine Abfrage hinzu die sich auf genau ein Feld bezieht
	 *
	 * @param string $sField
	 * @param string $sQuery
	 * @param boolean $bExactSearch
	 * @param string $sType
	 */
	public function addFieldQuery(string $sField, string $sQuery, bool $bExactSearch = false, string $sType = 'must'): void
	{
		$oQuery = $this->getFieldQueryObject($sField, $sQuery, $bExactSearch);

		if (!empty($oQuery)) {
			$this->aQueries[] = ['query' => $oQuery, 'fields' => $sField, 'type' => $sType];
		}
	}

	/**
	 * liefert ein Query-Objekt das sich auf ein Feld bezieht
	 *
	 * @param string $sField
	 * @param string $sQuery
	 * @param boolean $bExactSearch
	 * @return AbstractQuery
	 */
	public function getFieldQueryObject(string $sField, string $sQuery, bool $bExactSearch = false): AbstractQuery
	{
		if (!$bExactSearch) {
			$sQuery = $this->replaceTokenspacer($sQuery);
		}

		// WENN AND und ORs
		if (is_array($sQuery)) {
			$oBool = new BoolQuery();
			$oBool->setMinimumShouldMatch(1);

			// OR Ebene durchlaufen
			foreach ((array)$sQuery as $aQueryORPart) {

				$oBoolAnd = new BoolQuery();
				//ANDS durchgehen
				foreach((array)$aQueryORPart as $sQueryANDPart) {
					$sQueryANDPart = trim($sQueryANDPart);
					$sQueryANDPart = $this->rstrtrim($sQueryANDPart, ' AND');
					$oQuery = $this->getFieldQuery($sField, $sQueryANDPart);
					$oBoolAnd->addMust($oQuery);
				}

				$oBool->addShould($oBoolAnd);
			}

			if (
				count($sQuery) == 1 &&
				count($aQueryORPart) == 1
			) {

			} else {
				if (
					count($sQuery) == 1 &&
					count($aQueryORPart) > 1
				) {
					$oQuery = $oBoolAnd;
				} else {
					$oQuery = $oBool;
				}
			}

		} else {
			$oQuery = $this->getFieldQuery($sField, $sQuery);
		}

		return $oQuery;
	}

	/**
	 * Einfacher query welche "==" prüft!
	 * ACHTUNG KEIN LIKE!!
	 *
	 * @deprecated
	 */
	public function getFieldQuery(string $sField, string|int|bool $mQuery): QueryString
	{
		if (is_numeric($mQuery)) {
			$mQuery = (string)$mQuery;
		}
		if (is_bool($mQuery)) {
			$mQuery = $mQuery ? 'true' : 'false';
		}
		$oQuery = new QueryString($mQuery);
		$oQuery->setDefaultField($sField);

		return $oQuery;
	}

	/**
	 * fügt einen Query hinzu der eine Ähnlichkeitssuche durchführt
	 * bitte nur benutzen wenn ihr wisst was ihr macht!
	 * Ähnlichkeitssuchen können nicht erwartete Ergebnisse liefern da sie einen bestimmten Algorytmus benutzen umd
	 * die Ergebnisse zu vergleichen
	 *
	 * @param string $mQuery
	 * @param array|null $fields
	 * @return MultiMatch
	 */
	public function getLikeThisQuery(string $mQuery, ?array $fields = null): MultiMatch
	{
		$fields = $fields ?? $this->aSearchFields;

		$query = new MultiMatch();
		$query->setQuery($mQuery);
		$query->setFields($fields);
		$query->setType('best_fields');
		$query->setParam('operator', 'AND');
		$query->setParam('fuzziness', 'AUTO');
		$query->setParam('prefix_length', 3);
		$query->setParam('max_expansions', 3);

		return $query;
	}

	/**
	 *
	 * @param string $mQuery
	 * @param array $aSearchFields
	 * @return QueryString
	 */
	public function getExactSearch(string $mQuery, array $aSearchFields): QueryString
	{
		$oQuery = new QueryString($mQuery);
		$oQuery->setDefaultOperator('AND');
		$oQuery->setFuzzyMinSim(1);
		$oQuery->setUseDisMax(true);
		if (!empty($aSearchFields)) {
			$oQuery->setFields($aSearchFields);
		} else {
			$oQuery->setFields($this->aSearchFields);
		}

		return $oQuery;
	}

	/**
	 * Setzt einen Filter welcher definiert das das gefundene Doc. das angegegeben Feld haben MUSS (ihrgendeinwert)
	 *
	 * @param string $sField
	 */
	public function addExistFilter(string $sField): void
	{
		$this->aFilters[] = ['filter' => 'exist', 'value' => $sField];
	}

	/**
	 * Setzt einen Filter welcher definiert das das gefundene Doc. das angegegeben Feld haben MUSS (ihrgendeinwert)
	 *
	 * @param string $sField
	 */
	public function addNotExistFilter(string $sField): void
	{
		$this->aFilters[] = ['filter' => 'not_exist', 'value' => $sField];
	}

	/**
	 * löscht alle Queries
	 */
	public function clearQueries(): void
	{
		$this->aQueries = array();
	}

	/**
	 * @param string $sSearchString
	 * @param array $aFields
	 * @return Collection
	 */
	public function getCollection(string $sSearchString = '', array $aFields = ['*']): Collection
	{
		if (!empty($aFields)) {
			$this->setFields($aFields, $aFields);
		}

		$oCollection = new Collection($this);

		return $oCollection;
	}

	/**
	 * start the search over the given string
	 *
	 * @param string $sSearchString
	 * @return array
	 */
	public function search(string $sSearchString = ''): array
	{
		//$this->_oIndex->open();

		$aBack = false;

		if ($sSearchString) {
			$this->addQuery($sSearchString);
		}

		if (is_object($this->oIndex)) {

			$aBack = array();

			$aResultData = $this->searchElastica();

			$aBack['hits'] = $aResultData['hits']['hits'];
			$aBack['total'] = $aResultData['hits']['total'];

		}

		//$this->_oIndex->close();

		return $aBack;
	}

	/**
	 * startet die Suche mit der Hilfe von Elastica (elasticsearch)
	 */
	protected function searchElastica(): array
	{
		global $_VARS;

		// Wenn keine Quieries angegeben sind suche nach ALLEM
		if (count($this->aQueries) <= 0) {
			$oFinalQuery = new MatchAll();
		} else {
			// Ansonsten bau einen Bool Query auf um die einzelnen Queries zu kombinieren
			$oFinalQuery = new BoolQuery();
			//$oFinalQuery->setParam('from', $this->_iOffset);
			//$oFinalQuery->setParam('size', $this->_iLimit);
			// Queries durchgehen
			foreach ($this->aQueries as $aQuery) {

				$mQuery = $aQuery['query'];
				$aSearchFields = (array)$aQuery['fields'];

				// Query Daten aufbereiten
				if (is_string($mQuery)) {
					$oQuery = $this->getExactSearch($mQuery, $aSearchFields);
					$oQuery->setDefaultOperator('AND');
				} elseif ($mQuery instanceof AbstractQuery) {
					$oQuery = $mQuery;
					if(method_exists($oQuery, 'setDefaultOperator')) {
						$oQuery->setDefaultOperator('AND');
					}
				} else {
					throw new \Exception('Unkown Query!');
				}
				// Query je nach Typ hinzufügen
				if ($aQuery['type'] == 'must') {
					$oFinalQuery->addMust($oQuery);
				} else {
					if ($aQuery['type'] == 'must_not') {
						$oFinalQuery->addMustNot($oQuery);
					} else {
						$oFinalQuery->addShould($oQuery);
						$oFinalQuery->setMinimumShouldMatch($this->iMinimumNumberShouldMatch);
					}
				}
			}
		}

		// Gesammtquery vorbereiten
		$oQuery = new \Elastica\Query();
		// Limit daten
		$oQuery->setFrom($this->iOffset);
		if ($this->iLimit > 10000) {
			$oQuery->setSize(10000);
		} else {
			$oQuery->setSize($this->iLimit);
		}
		// Highlight ?
		if ($this->bHighlight) {
			// Wenn ja  Felder hierführ definieren
			$aHighlightFields = array();
			foreach ($this->aSearchFields as $sField) {
				if ($sField != '*') {
					$aHighlightFields[$sField] = array('fragment_size' => 200, 'number_of_fragments' => 1);
				}
			}

			// DEFAULT makierung
			$aHighlight = array(
					'pre_tags' => array("<em>"),
					'post_tags' => array("</em>"),
					'fields' => $aHighlightFields
			);

			$oQuery->setHighlight($aHighlight);
		}

		// Wenn sortierung
		if (!empty($this->aSortFields)) {
			$mapping = $this->oIndex->getMapping();
			if(count($mapping) > 1) {
				$mapping = $mapping['properties'];
			} else {
				$mapping = reset($mapping);
			}
			$sortFields = [];
			// wenn subfield keyword existiert, dann für sortierung verwenden, siehe Ext_Gui2_Index_Generator
			foreach ($this->aSortFields as $fieldName => $sortDirection) {
				if (isset($mapping[$fieldName]['fields']['keyword'])) {
					$sortFields[$fieldName.'.keyword'] = $sortDirection;
				} else {
					$sortFields[$fieldName] = $sortDirection;
				}
			}
			$oQuery->setSort($sortFields);
		}

		// Felder die ausgegeben werden sollen setzen
		if (!empty($this->aDataFields)) {
			$oQuery->setStoredFields($this->aDataFields);
		}

		// Finaler Query setzten
		$oQuery->setQuery($oFinalQuery);

		if (!empty($this->aFilters )) {
			// Filter wurden hier nie benutzt, höchstens mit manuellen Querys…
			throw new \RuntimeException('Filters currently not implemented!');
		}
		// Suchen
		
		if ($this->iLimit >= 10000) {

			$oSearch = new Search(self::getClient());
			$oSearch->addIndex($this->oIndex);
			$oSearch->setQuery($oQuery);
			
			$oScroll = new Scroll($oSearch);
			
			$aResultData = [];
			foreach ($oScroll as $oResultSet) {
				$aScrollResultData = $oResultSet->getResponse()->getData();
				if (empty($aResultData)) {
					$aResultData = $aScrollResultData;
				} else {
					$aResultData['hits']['hits'] = array_merge($aResultData['hits']['hits'], $aScrollResultData['hits']['hits']);
				}
			}
			
		} else {
			$this->oLastResult = $this->oIndex->search($oQuery);
			$aResultData = $this->oLastResult->getResponse()->getData();
		}

		if (isset($_VARS['debugmode']) && $_VARS['debugmode'] == 1) {
			__pout($oQuery);
			__pout($this->oLastResult);
		}

		return $aResultData;

	}

	/**
	 * Strip a string from the end of a string
	 *
	 * @param string $str the input string
	 * @param ?string $remove OPTIONAL string to remove
	 *
	 * @return string the modified string
	 */
	protected function rstrtrim(string $str, ?string $remove = null): string
	{
		$str = (string)$str;
		$remove = (string)$remove;

		if (empty($remove)) {
			return rtrim($str);
		}

		$len = strlen($remove);
		$offset = strlen($str) - $len;
		while ($offset > 0 && $offset == strpos($str, $remove, $offset)) {
			$str = substr($str, 0, $offset);
			$offset = strlen($str) - $len;
		}

		return rtrim($str);

	}

	/**
	 * prepare query
	 * Leerzeichen => OR Operator (auto. durch die query klassen)
	 * Komma => AND Operator
	 *
	 * @param string $sSearchString
	 * @return string
	 */
	protected function replaceTokenspacer(string $sSearchString): string
	{
		$mSearchString = '';

		if (!empty($sSearchString) || $sSearchString === '0') {

			$sSearchString = trim($sSearchString);

			// Komma == und
			$sSearchString = str_replace(' ', ' AND ', $sSearchString);
			$sSearchString = str_replace(',', ' OR ', $sSearchString);
			$sTemp = substr(trim($sSearchString), -1);
			$sTemp2 = substr(trim($sSearchString), -3);
			$sTemp3 = substr(trim($sSearchString), -4);

			$sSearchString = preg_replace('/([ ]{2,})([^ ])/', ' $2', $sSearchString);
			$sSearchString = str_replace(' AND OR AND ', ' OR ', $sSearchString);
			$sSearchString = $this->rstrtrim($sSearchString, 'AND ');
			$sSearchString = $this->rstrtrim($sSearchString, 'AND');
			$sSearchString = $this->rstrtrim($sSearchString, 'OR ');
			$sSearchString = $this->rstrtrim($sSearchString, 'OR');

			$sSearchString = trim($sSearchString);

			$mSearchString = array();
			$aOrParts = explode(' OR ', $sSearchString);
			foreach ((array)$aOrParts as $iOrPart => $sAndParts) {
				$aAndParts = explode(' AND ', $sAndParts);
				foreach((array)$aAndParts as $iAndPart => $sSearchString) {
					$mSearchString[$iOrPart][$iAndPart] = $sSearchString;
				}
			}

			if (
				count($mSearchString) == 1 &&
				count($mSearchString[0]) == 1
			) {
				$mSearchString = $mSearchString[0][0];
			}

		}

		return $mSearchString;
	}

	/**
	 * @see \Elastica\Util::escapeTerm()
	 *
	 * @param string $sTerm
	 * @return string
	 */
	public static function escapeTerm(string $sTerm): string
	{
		// Manche Zeichen dürfen weiterhin über das Suchfeld eingegeben werden
		$aChars = array('\\', '+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', /*'"', '~', '*',*/ '?', ':', '/', '<', '>');

		foreach ($aChars as $sChar) {
			$sTerm = str_replace($sChar, '\\'.$sChar, $sTerm);
		}

		return $sTerm;

	}

	/**
	 * Präfix für Indexnamen: Lizenz
	 *
	 * Elasticsearch mag keine großen Buchstaben, daher strtolower().
	 * Minus wird im Lizenz-Key ersetzt, damit - als Trennzeichen benutzt werden kann.
	 *
	 * @param string $sName
	 * @return string
	 */
	public static function buildIndexName(string $sName): string
	{
		if (empty($sName)) {
			throw new \InvalidArgumentException('Index name is empty!');
		}

		$sLicence = strtolower(str_replace('-', '_', \System::d('license')));
		$sName = $sLicence.'-'.$sName;
		return $sName;
	}
	
	public function getQueries(): array
	{
		return $this->aQueries;
	}
	
}