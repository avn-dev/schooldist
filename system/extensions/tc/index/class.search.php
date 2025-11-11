<?php

/**
 * @author Mehmet Durmaz
 */
class Ext_TC_Index_Search extends ElasticaAdapter\Facade\Elastica implements Ext_TC_Statement_Interface
{
	public string $sFilterTypeInCondition = 'must';
	
	/**
	 * Aktuelles Bool Objekt
	 * @var int 
	 */
	protected int $_iCurrentBool = 0;
	
	/**
	 * Alle Bools die in den Should rein müssen
	 * @var array 
	 */
	protected array $_aBools = [];
	
	/**
	 *
	 * @var ?Ext_TC_Index_Search_Mapping
	 */
	protected ?Ext_TC_Index_Search_Mapping $_oIndexMapping = null;
	
	/**
	 *
	 * @param string $sIndexName
	 * @param string $sTypeName 
	 */
	public function __construct(string $sIndexName, string $sTypeName = '')
	{
		parent::__construct($sIndexName, $sTypeName);
		
		$this->_oIndexMapping = new Ext_TC_Index_Search_Mapping();
	}
	
	/**
	 *
	 * @param string $sMappingName
	 * @param Ext_TC_Index_Mapping_Abstract $oMapping 
	 */
	public function addMapping(string $sMappingName, Ext_TC_Index_Mapping_Abstract $oMapping): void
	{
		$this->_oIndexMapping->addMapping($sMappingName, $oMapping);
	}
	
	/**
	 *
	 * @param mixed $mField 
	 * @return Ext_TC_Index_Search
	 */
	public function addSelect(mixed $mField): Ext_TC_Index_Search
	{
		$aFields = (array)$mField;
		
		$this->setFields($aFields);
		
		return $this;
	}

	/**
	 * mit alias ist der Mapping alias gemeint
	 * @param string $sField
	 * @param mixed $mValue
	 * @param bool|string $sAlias
	 * @return Ext_TC_Index_Search
	 */
	public function addWhere(string $sField, mixed $mValue, bool|string $sAlias = false): Ext_TC_Index_Search
	{			
		$this->_addWherePart($sField, $mValue, $sAlias);
		
		return $this;
	}

	/**
	 * mit alias ist der Mapping alias gemeint
	 * @param string $sField
	 * @param mixed $mValue
	 * @param bool|string $sAlias
	 * @return Ext_TC_Index_Search
	 */
	public function addOrWhere(string $sField, mixed $mValue, bool|string $sAlias = false): Ext_TC_Index_Search
	{
		$aBoolInfo	= $this->createNewCondition();
		
		$this->_addWherePart($sField, $mValue, $sAlias);
		
		return $this;
	}

	/**
	 * mit alias ist der Mapping alias gemeint
	 * @param string $sField
	 * @param mixed $mValue
	 * @param bool|string $sAlias
	 * @return Ext_TC_Index_Search
	 */
	public function addAndWhere(string $sField, mixed $mValue, bool|string $sAlias = false): Ext_TC_Index_Search
	{
		$this->_addWherePart($sField, $mValue, $sAlias);
		
		return $this;
	}

	/**
	 * mit alias ist der Mapping alias gemeint
	 * @param string $sField
	 * @param mixed $mValue
	 * @param bool|string $sAlias
	 */
	protected function _addWherePart(string $sField, mixed $mValue, bool|string $sAlias = false): void
	{
		$aBoolInfo		= $this->getCurrentCondition();
	
		if (
			!is_array($aBoolInfo)
		) {
			$aBoolInfo	= $this->createNewCondition();
		}
		
		/* @var $oBool \Elastica\Query\BoolQuery */
		$oBool			= $aBoolInfo['bool'];
		
		$oMapping		= $this->_oIndexMapping->getMappingForField($sField, $sAlias);
		$sIndexName		= $this->_oIndexMapping->getIndexFieldName($sField, $sAlias);

		if (
			$oMapping &&
			!empty($sIndexName)
		) {
			$bIsLikeSearch	= $oMapping->isLikeSearch($sField);
			
			$mValue = $this->_checkConvertField($sField, $mValue, $sAlias);
			
			//Immer nur nach OriginalWert suchen
			$sIndexName = $sIndexName . '_original';

			if(
				$bIsLikeSearch
			) {
				$mValue		= $mValue . '*';
				
				$oQuery	= $this->getLikeThisQuery($mValue, array($sIndexName));
			} else {
				$oQuery = $this->getFieldQuery($sIndexName, $mValue);
			}
			
			if (
				$this->sFilterTypeInCondition == 'must'
			) {
				$oBool->addMust($oQuery);
			} else {
				$oBool->addShould($oQuery);
			}
		} else {
			//direkter Filter
			
			$oQuery = $this->getFieldQuery($sField, $mValue);
			
			$oBool->addShould($oQuery);
		}
		
	}
	
	/**
	 * alle Filter wieder zurücksetzen
	 */
	public function reset()
	{
		$this->_aBools = array();
	}
	
	/**
	 *
	 * @param int $iLimit
	 * @param int $iOffset 
	 * @return Ext_TC_Index_Search
	 */
	public function addLimit(int $iLimit, int $iOffset): Ext_TC_Statement_Interface
	{
		$this->setLimit($iLimit, $iOffset);
		
		return $this;
	}

	/**
	 *
	 * @param string $sSortField
	 * @param string $sSortType
	 * @return Ext_TC_Index_Search
	 */
	public function addOrder(string $sSortField, string $sSortType): Ext_TC_Statement_Interface
	{
		//$this->order($sSortField, $sSortType);

		return $this;
	}

	/**
	 * @param string $sSearchString
	 * @return array
	 * @see Parent
	 */
	public function search($sSearchString = ''): array
	{
		//Bools beinhalten Query's die mit "AND" verbunden sind, und alle Bool's werden mit "OR" zusammengefügt
		foreach ($this->_aBools as $aBoolInfo) {
			if (
				$aBoolInfo['type'] == 'should'
			) {
				$this->addShouldQuery($aBoolInfo['bool']);
			} else {
				$this->addMustQuery($aBoolInfo['bool']);
			}
			
		}

		$mIndexResult = false;
		
		if (!empty($this->aQueries)){
			$mIndexResult	=  parent::search();	
		}	

		return $mIndexResult;
	}
	
	/**
	 *
	 * @return Ext_TC_Index_Search_Result[]
	 */
	public function getResults(): array
	{
		$aResults = array();

		$aIndexResult = $this->search();

		if (
			isset($aIndexResult['hits'])
		) {
			$aHits = (array)$aIndexResult['hits'];
			
			foreach ($aHits as $aHitData) {
				
				$aFields = array();
				
				if (isset($aHitData['fields'])){
					$aFields = $aHitData['fields'];
				} else if (isset ($aHitData['_source'])) {
					$aFields = $aHitData['_source'];
				}
				
				if (
					!empty($aFields)
				) {
					$oResult = new Ext_TC_Index_Search_Result($this->_oIndexMapping, $aFields);
					
					$aResults[] = $oResult;
				}
			}
		}

		return $aResults;
	}
	
	/**
	 * Jetzige Kondition Holen
	 * @return array
	 */
	public function getCurrentCondition(): array
	{
		$oCurrentBool = $this->getConditionByPosition($this->_iCurrentBool);
		
		return $oCurrentBool;
	}
	
	/**
	 * Kondition nach Position holen
	 * @param int $iPosition
	 * @return array 
	 */
	public function getConditionByPosition(int $iPosition): array
	{
		$oCurrentBool = false;

		if (
			isset($this->_aBools[$iPosition])
		) {
			$oCurrentBool = $this->_aBools[$iPosition];
		}
		
		return $oCurrentBool;
	}
	
	/**
	 * neue Kondition erstellen
	 * @param bool $bHasToMatch
	 * @return array 
	 */
	public function createNewCondition(bool $bHasToMatch = false): array
	{
		if (
			$bHasToMatch
		) {
			$sType = 'must';
		} else {
			$sType = 'should';
		}
		
		$this->_iCurrentBool++;
		
		$oCurrentBool = new \Elastica\Query\BoolQuery();
		
		$this->_aBools[$this->_iCurrentBool] = [
			'bool'	=> $oCurrentBool,
			'type'	=> $sType,
		];
		
		return $this->_aBools[$this->_iCurrentBool];
	}
	
	/**
	 * Ableiten um vorher bestimmte Daten fürs Filter manipulieren zu können in der Kindklasse
	 * @param Ext_TC_Index_Mapping_Abstract $oMapping
	 * @param string $sField
	 * @param mixed $mValue 
	 */
	protected function _checkConvertField(string $sField, mixed $mValue, bool|string $sAlias = false): mixed
	{
		if (
			empty($mValue)
		) {
			$mValue = '*';
		}
		
		return $mValue;
	}

}