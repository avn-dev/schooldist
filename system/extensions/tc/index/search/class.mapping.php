<?php

/**
 * @author Mehmet Durmaz
 */
class Ext_TC_Index_Search_Mapping
{
	/**
	 *
	 * @var Ext_TC_Index_Mapping_Abstract <array> 
	 */
	protected $_aMappings		= array();
	
	/**
	 * alle Felder
	 * @var array 
	 */
	protected $_aFields			= null;
	
	/**
	 *
	 * @param string $sMappingName
	 * @param Ext_TC_Index_Mapping_Abstract $oMapping 
	 */
	public function addMapping($sMappingName, Ext_TC_Index_Mapping_Abstract $oMapping)
	{
		$this->_aMappings[$sMappingName] = $oMapping;
	}
	
	/**
	 *
	 * @return array
	 */
	public function getFields()
	{
		if(
			$this->_aFields === null
		)
		{
			$this->_buildFields();
		}
		
		return $this->_aFields;
	}
	
	/**
	 * Mapping Informationen nur einmalig hier bilden
	 */
	protected function _buildFields()
	{
		$this->_aFields = array();
				
		foreach($this->_aMappings as $sMappingName => $oMapping)
		{
			$aSchema	= $oMapping->getMappingSchema(true);

			foreach($aSchema as $sIndexFieldName => $aInfo)
			{
				$sColumn	= $aInfo['db_column'];

				if(
					!isset($this->_aFields[$sColumn])
				)
				{
					$this->_aFields[$sColumn] = array();
				}

				$this->_aFields[$sColumn][$sMappingName] = $sIndexFieldName;
			}
		}
	}
	
	/**
	 * Ein Array mit Informationen, welche Spalten in welchen Mappings vorkommen & wie sie dort benannt sind
	 * @param string $sField
	 * @param string $sAlias
	 * @return array
	 */
	public function getFieldInfo($sField, $sAlias=false)
	{
		$aInfo			= array();
		$aFields		= $this->getFields();

		if(
			isset($aFields[$sField])
		)
		{
			if(
				count($aFields[$sField]) == 1
			)
			{
				$aInfo			= $aFields[$sField];
			}
			elseif(
				count($aFields[$sField]) > 1 &&
				$sAlias &&
				isset($aFields[$sField][$sAlias])
			)
			{
				$aInfo			= array_intersect_key($aFields[$sField], array($sAlias => 1)); 
			}
			else
			{
				//Wenn eine Spalte in mehreren Mappings vorkommt, den Alias übergeben, welche Spalte genau gemeint ist
				Throw new Exception("Field has not 1 match. Matches: " . count($aFields[$sField]));
			}
		}
		elseif(strpos($sField, '.') !== false)
		{
			//Direkter FeldName Übergabe mit alias Zusammen
			$aFieldInfo = explode('.', $sField);
			
			if(
				isset($aFields[$aFieldInfo[1]])
			)
			{
				$sSearch = array_search($sField, $aFields[$aFieldInfo[1]]);
				
				if(
					$sSearch
				)
				{
					$aInfo = array(
						$sSearch => 1
					);
				}
			}
		}
		
		return $aInfo;
	}
	
	/**
	 * Rausfinden in welches Mappingobjekt ein Feld zutrifft
	 * @param string $sField
	 * @param string $sAlias
	 * @return Ext_TC_Index_Mapping_Abstract 
	 */
	public function getMappingForField($sField, $sAlias=false)
	{
		$aFieldInfo		= $this->getFieldInfo($sField, $sAlias);
		
		$sMappingKey	= key($aFieldInfo);
		
		$oMapping		= $this->getMapping($sMappingKey);

		return $oMapping;
	}
	
	/**
	 *
	 * @param string $sMappingName
	 * @return Ext_TC_Index_Mapping_Abstract 
	 */
	public function getMapping($sMappingName)
	{
		$oMapping		= null;
		
		if(
			isset($this->_aMappings[$sMappingName])
		)
		{
			$oMapping	= $this->_aMappings[$sMappingName];
		}
		
		return $oMapping;
	}
	
	/**
	 * Anhand der Spalte den Indexspaltennamen rausfinden
	 * @param string $sField
	 * @param string $sAlias
	 * @return string 
	 */
	public function getIndexFieldName($sField, $sAlias=false)
	{
		$sIndexField	= null;
		$aFieldInfo		= $this->getFieldInfo($sField, $sAlias);
		
		$sIndexField	= reset($aFieldInfo);
		
		return $sIndexField;
	}
	
	/**
	 *
	 * @return Ext_TC_Index_Mapping_Abstract <array> 
	 */
	public function getMappings()
	{
		return $this->_aMappings;
	}
}