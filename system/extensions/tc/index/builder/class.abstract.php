<?php

/**
 *	@author Mehmet Durmaz
 */

abstract class Ext_TC_Index_Builder_Abstract
{
	/**
	 * @var \ElasticaAdapter\Adapter\Index
	 */
	protected $_oIndex;

	/**
	 * @var Ext_TC_Index_Search_Mapping
	 */
	protected $_oIndexMapping;
	
	/**
	 * the Language for the Format Classes
	 * @var string (2)
	 */
	protected $_sFormatLanguage = '';

	/**
	 * @param \ElasticaAdapter\Adapter\Index $oIndex
	 */
	public function __construct(\ElasticaAdapter\Adapter\Index $oIndex)
	{
		$this->_oIndex	= $oIndex;
		$this->_initMappings();
	}
	
	/**
	 * Mappings definieren
	 */
	protected function _initMappings()
	{
		$oIndexMapping = new Ext_TC_Index_Search_Mapping();
		
		//Die WDBasic Objekte die gemappt werden sollen
		$aMappingObjects		= $this->_getMappingObjects();

		foreach($aMappingObjects as $sMappingName => $sBasic) {

			$oBasic		= new $sBasic();

			//Mapping Typ 'customer_index' für die WDBasic holen
			$oMapping	= $oBasic->getMapping('customer_index');
			
			if(
				$oMapping instanceof Ext_TC_Index_Mapping_Abstract
			) {
				$oIndexMapping->addMapping($sMappingName, $oMapping);
			}
		}
		
		$this->_oIndexMapping = $oIndexMapping;
	}
	

	/**
	 * Mapping bilden
	 */
	protected function _createMapping()
	{
		//Kind muss selber definieren, wie die Mapping Daten auszusehen haben
		$aMappingData = $this->getMappingData(false, true);

		$this->_oIndex->createMapping($aMappingData);
	}
	
	public function addOne($mId, $aData, $bRefresh=true)
	{
		$this->refreshOne($mId, $aData, $bRefresh);
	}
	
	/**
	 * Aktualisieren eines Dokumentes anhand der Document-ID
	 * @param mixed $mId
	 * @param array $aData
	 * @param bool $bRefresh 
	 */
	public function refreshOne($mId, $aData, $bRefresh=true)
	{				
		$this->deleteOne($mId);
		
		$oDocument = $this->_oIndex->createDocument($mId);

		foreach($aData as $sField => $mValue)
		{
			if(
				!empty($mValue)
			)
			{		
				$aFieldInfo		= explode('.', $sField);
				$sDbColumn		= $aFieldInfo[1];
				
				$oMappingForField	= $this->_oIndexMapping->getMappingForField($sField);

				if(
					$oMappingForField	
				)
				{
					if(
						is_array($mValue)
					)
					{
						$mValueOriginal		= array();
						$mValueFormatted	= array();
						
						foreach($mValue as $sValue)
						{
							$aValues = $this->_getValues($sValue, $sDbColumn, $aData, $oMappingForField);
							
							$mValueOriginal[]	= $aValues['value_original'];
							$mValueFormatted[]	= $aValues['value_format'];
						}
					}
					else
					{

						$aValues = $this->_getValues($mValue, $sDbColumn, $aData, $oMappingForField);

						$mValueOriginal		= $aValues['value_original'];
						$mValueFormatted	= $aValues['value_format'];

					}
				}
				else
				{
					$mValueOriginal			= $mValue; 
					$mValueFormatted		= $mValue;
				}
				
				//Feldnamen das Wort _original hinzufügen, damit der Index auch in der GUI funktioniert
				$sFieldNameOriginal		= $sField . '_original';
				$oDocument->set($sFieldNameOriginal, $mValueOriginal);
				$oDocument->set($sField, $mValueFormatted);
			}
		}
		
		$this->_oIndex->addDocument($oDocument);
		
		//Man sollte selber steuern können, ob die Änderung sofort gespeichert werden soll,
		//wenn mehrere aufeinmal aktualisiert werden ist es unnötig nach jedem einzelnen zu aktualisieren
		if(
			$bRefresh
		)
		{
			$this->_oIndex->refresh();
		}
	}
	
	protected function _getValues($mValue, $sField, $aData, Ext_TC_Index_Mapping_Abstract $oMapping)
	{
		//Original Wert Sonderzeichen entfernen, nur diese sind für die Suche relevant
		if(
			!WDDate::isDate($mValue, WDDate::DB_DATE) &&
			!WDDate::isDate($mValue, WDDate::DB_TIMESTAMP) && 
			!WDDate::isDate($mValue, WDDate::DB_DATETIME) &&
			$mValue != '0000-00-00'
		)
		{
			$mValueOriginal			= ElasticaAdapter\Facade\Elastica::escapeTerm($mValue);
		}
		else
		{
			$mValueOriginal			= $mValue;
		}

		//Formatierung hinzufügen
		$oFormatForField = $oMapping->getFormat($sField);

		if(
			$oFormatForField
		)
		{
			// Sprache müssen wir setzten da wir die Interface Language nicht überschreiben können
			$oFormatForField->setLanguage($this->_sFormatLanguage);
			
			$oColumn			= null;
			$mValueFormatted	= $oFormatForField->format($mValue, $oColumn, $aData);

		}
		else
		{
			$mValueFormatted	= $mValue;
		}
		
		return array(
			'value_original'	=> $mValueOriginal,
			'value_format'		=> $mValueFormatted,
		);
	}
	
	/**
	 * Mehrere Dokumente akstualisieren
	 * @param array $aData 
	 */
	public function refreshMany($aData)
	{
		//Kind kann selber document_id bestimmen
		$sPrimaryIndexField = $this->_getPrimaryIndexKeyField();

		foreach($aData as $aRow)
		{
			$mId = $aRow[$sPrimaryIndexField];

			#$this->refreshOne($mId, $aRow, false, false);
			$this->addOne($mId, $aRow, false);
		}
		
		$this->_oIndex->refresh();
	}
	
	/**
	 * Löschen eines Dokumentes anhand der ID
	 * @param mixed $mId 
	 */
	public function deleteOne($mId)
	{
		$this->_oIndex->deleteDocuments(array($mId));
	}
	
	/**
	 * Index neu bilden
	 */
	public function createAll($bDeleteIndex = true)
	{
		$options = $bDeleteIndex ? ['recreate' => true] : [];

		$this->_oIndex->create(array(), $options);
		
		if($bDeleteIndex){
			$this->_createMapping();
		}
	
		$aAllData = $this->getArrayData(false);

		$this->refreshMany($aAllData);
		
	}

	/**
	 * Kind soll bestimmen wie die Daten aufgebaut sind
	 */
	abstract public function getArrayData($aIds=false);
	
	/**
	 * Kind soll das Mapping vorgeben
	 */
	abstract public function getMappingData($bWithDbInformation=false, $bWithOriginal=false);
	
	/**
	 * Kind definiert die Spalte für Dokument_ID
	 */
	abstract protected function _getPrimaryIndexKeyField();
	
	/**
	 * WDBasic Klassen für die ein Mapping generiert werden soll
	 */
	abstract protected function _getMappingObjects();
}