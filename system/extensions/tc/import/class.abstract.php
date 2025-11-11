<?php

abstract class Ext_TC_Import_Abstract {
	
	/**
	 * @var Ext_TC_Import_Mapping 
	 */
	protected $_oMapping;
	protected $_aData						= array();
	protected $_sEntity						= '';
	protected $_sEntityTable				= '';
	protected $_sCSV						= '';
	protected $_iHeaderCount				= 0;
	protected $_iNumber						= 0;
	protected $_aMappingDataComplete		= array();

	protected static $_aChildMappingData	= array();
	protected static $_aMappingData			= array();
	protected static $_MappingTableCreated	= array();
	protected static $_aCount				= array();
	protected static $_aEntityCache			= array();
	
	
	public function __construct($sEntity) {
		set_time_limit(3600);
		$this->_sEntity = $sEntity;
		$this->_iNumber = (int)Ext_TC_Import_Abstract::$_aCount[$sEntity];
		Ext_TC_Import_Abstract::$_aCount[$sEntity]++;
	}

	public function getNumber(){
		return $this->_iNumber;
	}
	
	/**
	 * setzt ein Mapping
	 * @param Ext_TC_Import_Mapping $oMapping 
	 */
	public function setMapping(Ext_TC_Import_Mapping $oMapping){
		$this->_oMapping = $oMapping;
	}
		
	/**
	 * setzt ein Array mit mehreren Datensätzen
	 * @param type $aDataArray 
	 */
	public function setManyData($aDataArray){
		foreach($aDataArray as $aData){
			if(is_array($aData)){
				$this->_aData[] = $aData;
			}
		}
	}
	/**
	 * gibt das Mapping zurück
	 * @return Ext_TC_Import_Mapping 
	 */
	public function getMapping(){
		return $this->_oMapping;
	}
	
	/**
	 * setzt einen Datensatz
	 * @param type $aData 
	 */
	public function setData($aData){
		if(is_array($aData)){
			$this->_aData[] = $aData;
		}
	}
	
	/**
	 * resetet die Mapping und Daten Felder 
	 */
	public function reset(){
		$this->_aData = array();
	}
	
	/**
	 * setzt eine CSV
	 * wenn man den Import startet wird die CSV eingelesen
	 * @param type $sFile
	 * @param type $iHeaderRows
	 * @throws Exception 
	 */
	public function setCSV($sFile, $iHeaderRows = 1){
		
		if(!is_file($sFile)){
			throw new Exception('CSV File not found!');
		}
		
		$this->_iHeaderCount = $iHeaderRows;
		$this->_sCSV = $sFile;
	}
	
	public function isImportable($iColumnNumber){
		$aChilds = $this->getMapping()->getChilds();
		foreach($aChilds as $sChild => $aChild){
			if($aChild['import']->getMapping()->isImportable($iColumnNumber)){
				return $sChild;
			}
		}
	}
	
    public function setImportTable($sTable){
        $sSql = 'SELECT * FROM #table';
        $aSql = array('table' => $sTable);
        $aResult = (array)DB::getPreparedQueryData($sSql, $aSql);
        $aFinalData = array();
        foreach($aResult as $aRow){
            unset($aRow['id']);
            $aValues = array_values($aRow);
            $aFinalData[] = $aValues;
        }
        $this->setManyData($aFinalData);
    }
	/**
	 * startet den Import
	 * @param type $bFirst
	 * @return type
	 * @throws Exception 
	 */
	public function start($bFirst = true){
				
		if($bFirst){
			__out('Import Startet ('.$this->_sEntity.' )...'.date('H:i:s'));
		}
		
		
		$sCSV = $this->_sCSV;
		
		if(!empty($sCSV)){
			$aData = $this->_readCSV($sCSV, $this->_iHeaderCount);
			$this->setManyData($aData);
		}
		
		$sImportKey = 'import_'.$this->_sEntity;
		$bTableSuccess	= $this->_createMappingTable($bFirst);

		if($bFirst){
			DB::begin($sImportKey);
		}

		if($bFirst && !$bTableSuccess){
			throw new Exception('Error while creating import Table ');
		}
		
		$aData			= $this->_prepareImportData();
		$aEntities		= array();

		if(!empty($aData)){

			$oEntity		= $this->createEntity();

			foreach($aData as $sPrimary => $aImportData){

				$bMasterLoop	= true;
				$aChildData		= array();
				$sSql			= 'INSERT INTO `'.$oEntity->getTableName().'` SET ';
				$aSql			= array();
				$aParentFields	= array();
				$aFlexFields	= array();

				foreach($aImportData as $iSub => $aSubData){
					
					foreach($aSubData as $iColumnNumber => $mFieldValue){
						
						if(!is_numeric($iColumnNumber)){
							continue;
						}
						
						$aChilds = $this->_oMapping->getChildsForColumn($iColumnNumber);
						
						if(
							$bMasterLoop && 
							empty($aChilds) && 
							$this->_oMapping->isImportable($iColumnNumber)
						){
							$sField										= $this->_oMapping->getDataField($iColumnNumber);
							$oTransformer								= $this->_oMapping->getTransformer($iColumnNumber);
							$aParentFields[$iColumnNumber]['original']	= $mFieldValue;
							$mFieldValue								= $oTransformer->transform($mFieldValue, $aSubData);

							if(strpos($sField, 'flex_') !== 0){
								$sSql							.= ' `'.$sField.'` = ?,';
								$aSql[]							= $mFieldValue;
							} else {
								$aFlexFields[$sField]			= $mFieldValue; 
							}
							
							$aParentFields[$iColumnNumber]['final']		= $mFieldValue;
							
						} else if($aChilds) {
							
							foreach($aChilds as $sChild){
								
								$bChildUnique = $this->_oMapping->isChildUnique($sChild);

								if(
									!empty($sChild) && 
									(
										!$bChildUnique ||
										(
											$bChildUnique &&	
											$bMasterLoop		
										)
									)
								){
									$aChildData[$sChild][$iSub][$iColumnNumber] = $mFieldValue;
								}
							}
						}
	
					}
					
					if(!empty($sChild)){
						$aChildData[$sChild][$iSub]['parent'] = $aParentFields;
					}
					// nur erste ebene setzten
					// da die hauptdaten einmalig sein müssen
					$bMasterLoop = false;
				}

				foreach($aChildData as $sChild => $aChild){
					$oManipulator = $this->_oMapping->getChildDataManipulator($sChild);
					if($oManipulator){
						$aChildData[$sChild] = $oManipulator->manipulate($aChild, $sChild);
					}
				}
				
				$this->_saveParentChilds($aChildData, $oEntity);

				$aFixColumns = $this->getMapping()->getFixColumns();

				foreach($aFixColumns as $sColumn => $mValue){
					$sSql		.= ' `'.$sColumn.'` = ?,';
					$aSql[]		= $mValue;
				}

				##
				## mysqli! prepared statements
				##

				$sSql		= rtrim($sSql, ',');

				$iEntity = $this->_preparedStatement($sSql, $aSql, $sImportKey.'_start');

				##
				##
				
				## FLEXIBILITY
				##
				if(!empty($aFlexFields)){
					$aFlexData = array();
					foreach($aFlexFields as $sField => $mValue){
						$aField = explode('_', $sField);
						$iField = end($aField);
						$aFlexData[$iField] = $mValue;
					}
					Ext_TC_Flexibility::saveData($aFlexData, $iEntity, true);
				}
				##
				##
                
				$this->_saveChildChilds($aChildData, $oEntity, $iEntity);
				$this->_saveMappingData($sPrimary, $iEntity);

				$aEntities[] = $iEntity;

			}
			
			if($bFirst){
				$this->_saveMappings();
			}
			
		}
			
		if($bFirst){
			
			DB::commit($sImportKey);
			
			DB::closePreparedStatements();
						
			__out('Import fertig ('.$this->_sEntity.' ) ...'.date('H:i:s'));
			WDCache::flush();
		}
		
		
		
		return $aEntities;
	}
	
	/**
	 * bereitet ein Statement vor falls noch nicht geschehen 
	 * und führt es aus
	 * als rückgabe wert kommt die letzte eingefügte ID zurück
	 * @param type $sSql
	 * @param type $aSql
	 * @param type $sKey
	 * @return type 
	 */
	protected function _preparedStatement($sSql, $aSql, $sKey){
		$iInsertId = null;
		
		try {
			$stmt		= DB::getPreparedStatement($sSql, 'Ext_TC_Import::'.$sKey);
			$iInsertId	= DB::executePreparedStatement($stmt, $aSql);
		} catch (Exception $exc) {
			__out($sSql);
			__out($exc, 1);
		}
		
		return $iInsertId;
	}

	/**
	 * speichert Kinder welche Eltern sind
	 * @param type $aChildData
	 * @param type $oEntity 
	 */
	protected function _saveParentChilds($aChildData, $oEntity){
		// bei Kinddaten ein Kindimport definieren und starten
		// danach das erzeugte Kind hinzufügen
		foreach($aChildData as $sChild => $aChild){				
			$oImportChild	= $this->_oMapping->getChildImport($sChild);
			$oChild			= $oImportChild->createEntity();
			$aChildConfig	= $oEntity->getChildData($oChild);
			
			// Wenn es kinder sind dann muss die ID ergäntzt werden
			if(
				$aChildConfig['type'] == 'joinedobject_parent'
			){
				$oImportChild->reset();
				$oImportChild->setManyData($aChild);
				$oImportChild->start(false);
				$this->_saveChildMappingData($oImportChild);
			}
		}
	}

	/**
	 * Speichert Kinder (child + jointables)
	 * @param type $aChildData
	 * @param type $oEntity
	 * @param type $iEntity 
	 */
	protected function _saveChildChilds($aChildData, $oEntity, $iEntity){

        if((int)$iEntity <= 0){
            __out('Fehler beim Speichern von Kindern, keine Eltern ID vorhanden!', 1);
        }
        
        // bei Kinddaten ein Kindimport definieren und starten
		// danach das erzeugte Kind hinzufügen
		foreach($aChildData as $sChild => $aChild){				
			$oImportChild	= $this->_oMapping->getChildImport($sChild);
			$oChild			= $oImportChild->createEntity();
			$aChildConfig	= $oEntity->getChildData($oChild);
            $oImportChild->reset();
            $oImportChild->setManyData($aChild);
			// Wenn es kinder sind dann muss die ID ergäntzt werden
			if(
				$aChildConfig['type'] == 'joinedobject_child'
			){
				$aColumns	= $oImportChild->getMapping()->getFixColumns();
				$aColumns[$aChildConfig['data']['key']] = $iEntity;
                if(!empty($aChildConfig['data']['static_key_fields'])){
					foreach((array)$aChildConfig['data']['static_key_fields'] as $sField => $mValue){
						$aColumns[$sField] = $mValue;
					}
				}
				$oImportChild->getMapping()->setFixColumns($aColumns);
				$oImportChild->start(false);
				$this->_saveChildMappingData($oImportChild);
			}  else if($aChildConfig['type'] == 'jointable'){
				$aChildEntities = $oImportChild->start(false);
				
				$aKeys = array($aChildConfig['data']['primary_key_field']=>(int)$iEntity);
				if(!empty($aChildConfig['data']['static_key_fields'])){
					foreach((array)$aChildConfig['data']['static_key_fields'] as $sField => $mValue){
						$aKeys[$sField] = $mValue;
					}
				}
				
				if($aChildConfig['data']['check_active']) {
					$aKeys['active'] = 1;
				}
				
				$sSql = 'INSERT INTO `'.$aChildConfig['data']['table'].'` SET ';
				$aSql = array();
				
				foreach($aKeys as $sField => $mValue){
					$sSql .= ' `'.$sField.'` = ?,';
					$aSql[] = $mValue;
				}
	
				foreach($aChildEntities as $iChildEntity){
					$aSqlChild		= $aSql;
					$sSqlChild		= $sSql;
					$sSqlChild		.= ' `'.$aChildConfig['data']['foreign_key_field'].'` = ?,';
					$aSqlChild[]	= $iChildEntity;
                    $sSqlChild		= rtrim($sSqlChild, ',');
                    
					$this->_preparedStatement($sSqlChild, $aSqlChild, 'child_'.$sChild.'_'.$aChildConfig['data']['table']);
				}
				
				$this->_saveChildMappingData($oImportChild);
			}
		}
    
	}

	/**
	 * merkt sich die Mapping Daten
	 * @param type $mOldPrimary
	 * @param type $iEntity 
	 */
	protected function _saveMappingData($mOldPrimary, $iEntity){
		$sTable = $this->getMappingTableName();
		Ext_TC_Import_Abstract::$_aMappingData[$sTable][$iEntity] = $mOldPrimary;
	}
	
	/**
	 * merkt sich die kind mapping Daten
	 * @param self $oChildImport 
	 */
	protected function _saveChildMappingData($oChildImport){
		$sTable			= $oChildImport->getMappingTableName();
		$sTableOriginal = $oChildImport->getEntityTable();
		Ext_TC_Import_Abstract::$_aChildMappingData[$sTable] = $sTableOriginal;
	}
	
	/**
	 * speichert die gemerkten mapping Daten
	 */
	protected function _saveMappings(){
		$this->_aMappingDataComplete = Ext_TC_Import_Abstract::$_aMappingData;
			
		foreach(Ext_TC_Import_Abstract::$_aMappingData as $sTable => $aMappingData){
			
			$aData = array();
		
			foreach($aMappingData as $iNewPrimary => $mOldPrimary){
				if($mOldPrimary === null){
					$mOldPrimary = 0;
				}
				$aData[] = array(
					$iNewPrimary,
					$mOldPrimary
				);
			}
		
			if(empty($aData)){
				throw new Exception('No Mapping Infos!');
			}

			$sSql = ' INSERT INTO `'.$sTable.'` SET `new_primary` = ?, `old_primary` = ?';

			foreach($aData as $aMapping){
				$aSql = $aMapping;
				$this->_preparedStatement($sSql, $aSql, 'mapping_'.$sTable);
			}
			
			
			unset(Ext_TC_Import_Abstract::$_aMappingData[$sTable]);
		}

		$aData = array();
		foreach(Ext_TC_Import_Abstract::$_aChildMappingData as $sChildTable => $sOriginalTable){
			$aData[] = array(
				$sChildTable,
				$sOriginalTable
			);
			unset(Ext_TC_Import_Abstract::$_aChildMappingData[$sChildTable]);
		}

		if(!empty($aData)){
			$sTable = $this->getMappingChildTableName();
			$sSql = ' REPLACE INTO `'.$sTable.'` SET `child_table` = ?, `original_table` = ?';
			foreach($aData as $aMapping){
				$aSql = &$aMapping;
				$this->_preparedStatement($sSql, $aSql, 'mapping_child_'.$sTable);
			}
		}
	}

	/**
	 * gibt die aktuellen Mapping Daten für die aktuelle Import Tabelle oder der angegebenen
	 * @return type 
	 */
	public function getMappingData($sTable = ''){
		if(empty($sTable)){
			$sTable	= $this->getMappingTableName();
		}
		return (array)$this->_aMappingDataComplete[$sTable];
	}


	/**
	 * ließt das CSV
	 * @param type $sFile
	 * @param type $iHeaderRows
	 * @return type 
	 */
	protected function _readCSV($sFile, $iHeaderRows){
		
		$handle		= fopen($sFile, "r");
		$aFinalData	= array();
			
		if ($handle !== FALSE) {
			
			$row = 0;

			while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
				if($iHeaderRows == 0){
					$bEmpty = true;
					foreach($data as $value){
						if(!empty($value)){
							$bEmpty = false;
						}
					}
					if(!$bEmpty){
						$aFinalData[$row] = $data;
						$row++;
					}
				} else {
					$iHeaderRows--;
				}
			}
			
			fclose($handle);
		}
		
		return $aFinalData;
	}
	
	/**
	 * bereitet die Daten für den Import vor ( gruppieren nach primary )
	 * @return type 
	 */
	protected function _prepareImportData(){
		
		$aFinalData		= array();
		$iPrimaryKey	= 0;
		$sPrimaryColumn = $this->_oMapping->getPrimaryNumber();

		foreach($this->_aData as $aData){
			
			$sPrimaryKey	= null;
			
			if($sPrimaryColumn !== null){
				foreach($aData as $iColumnNumber => $mValue){
					// primary ermitteln
					if( $iColumnNumber == $sPrimaryColumn ){
						$sPrimaryKey = $mValue;
					}
				}
			} else {
				$sPrimaryKey = $iPrimaryKey;
				
			}
			
			$aCurrentData = array();
			$aColumnNumbers = $this->_oMapping->getColumnNumbers();
			foreach($aData as $iColumnNumber => $mValue){
				$aCurrentData[$iColumnNumber] = $mValue;
				unset($aColumnNumbers[$iColumnNumber]);
			}
			// alle nummern die nicht im array vorkommen aber definiert wurden müssen leer
			// befüllt werden um sie ggf zu manipulieren
			foreach((array)$aColumnNumbers as $iColumnNumber => $mValue){
				$aCurrentData[$iColumnNumber] = '';
			}
			
			$aFinalData[$sPrimaryKey][] = $aCurrentData;
				
			$iPrimaryKey++;
		}
		
		return $aFinalData;
	}

	/**
	 * gibt den Namen für die Mapping Informationen zurück
	 * @return string 
	 */
	public function getMappingTableName(){
		$sEntityTable	= $this->getEntityTable();
		$sImportTable	= $sEntityTable;
		$sImportTable	= 'import_'.md5($sImportTable); // damit kein name zu lang wird
		return $sImportTable;
	}
	
	/**
	 *gibt den Namen für die Kind Mapping Informationen zurück
	 * @return string 
	 */
	public function getMappingChildTableName(){		
		$sEntityTable	= $this->getEntityTable();
		$sImportTable	= $sEntityTable.'_childtables';
		$sImportTable	= 'import_'.md5($sImportTable); // damit kein name zu lang wird
		return $sImportTable;
	}

	/**
	 * erzeugt die Mapping Tabellen falls noch nicht geschehen
	 * @return type 
	 */
	protected function _createMappingTable($bFirst = false){
		
		$aCache = Ext_TC_Import_Abstract::$_MappingTableCreated;
		
		$sImportTable = $this->getMappingTableName();
			
		if(!isset($aCache[$sImportTable])){
			
			// Entity Tabelle backupen
			$sEntityTable = $this->getEntityTable();
			Ext_TC_Util::backupTable($sEntityTable);
			
			$sImportTable2 = $this->getMappingChildTableName();

			$iLength		= 11;
			if($this->_oMapping->getPrimaryType() == 'varchar'){
				$iLength	= 255;
			}
			$sField			= $this->_oMapping->getPrimaryType().'('.$iLength.')';
			$bSuccess		= true;

			try {
				$sSQL			= '
					CREATE TABLE IF NOT EXISTS #table (
						`old_primary` varchar(255) NOT NULL,
						`new_primary` int(11) NOT NULL,
						PRIMARY KEY (`old_primary`,`new_primary`),
						KEY `new_primary` (`new_primary`)
						) ENGINE=InnoDB DEFAULT CHARSET=utf8;
				';

				DB::executePreparedQuery($sSQL, array('table' => $sImportTable));
			} catch (Exception $exc) {
				__out($exc);		
				$bSuccess = false;
			}

			if($bFirst){
				try {
					$sSQL			= '
						CREATE TABLE IF NOT EXISTS #table (
						`child_table` varchar(255) NOT NULL,
						`original_table` varchar(255) NOT NULL,
						PRIMARY KEY (`child_table`, `original_table`),
						KEY `child_table` (`child_table`),
						KEY `original_table` (`original_table`)
						) ENGINE=InnoDB DEFAULT CHARSET=utf8;
					';

					DB::executePreparedQuery($sSQL, array('table' => $sImportTable2));
				} catch (Exception $exc) {	
					__out($exc);
					$bSuccess = false;
				}
			}
			
			Ext_TC_Import_Abstract::$_MappingTableCreated[$sImportTable] = true;
		}

		return Ext_TC_Import_Abstract::$_MappingTableCreated[$sImportTable];
	}
	
	/**
	 * gibt den Tabellen namen der Entity
	 * @return type 
	 */
	public function getEntityTable(){
		
		$sTable = $this->_sEntityTable;
		
		if(empty($sTable)){
			$oEntity = $this->createEntity();
			$sTable = $oEntity->getTableName();
		}
		
		return $sTable;
	}
	
	/**
	 *
	 * @param int $iEntity
	 * @return WDBasic
	 * @throws Exception 
	 */
	public function createEntity($iEntity = 0){
		
		if(!is_int($iEntity)){
			throw new Exception('Wrong Entity ID Format');
		}
		
		$sEntity = $this->_sEntity;
		
		$aCache = self::$_aEntityCache;
		
		if(!isset($aCache[$sEntity][$iEntity])){
			
			if(empty($sEntity)){
				throw new Exception('Cant create Entity');
			}

			if($iEntity > 0){
				$oEntity = $sEntity::getInstance($iEntity);
			} else {
				$oEntity = new $sEntity($iEntity);
			}
			
			self::$_aEntityCache[$sEntity][$iEntity] = $oEntity;
		}
		
		return self::$_aEntityCache[$sEntity][$iEntity];
	}
	
	/**
	 * führt einen Rollback des letzten Imports durch
	 * @return boolean 
	 */
	public function rollback(){
		
		$sTableOriginal = $this->getEntityTable();
		
		// Tabellen des Aktuellen Imports
		$sTable		= $this->getMappingTableName();
		$sTable2	= $this->getMappingChildTableName();
		
		$aTableCombis = array(
			array(
				$sTable,
				$sTableOriginal
			)
		);
		
		try {
			// Tabellen der Kindimports
			$sSql = "SELECT * FROM #table2 ";
			$aResult = DB::getPreparedQueryData($sSql, array('table2' => $sTable2));

			foreach($aResult as $aChildTables){
				$aTableCombis[] = array(
					$aChildTables['child_table'],
					$aChildTables['original_table']
				);
			}
		} catch (Exception $exc) {
			// wenn das ein fehler liefert wurde noch nie etwas importiert
			return true;
		}
		
		// alle mapping -> original tabellen kombis durchegehn
		// und eingefügte löschen
		foreach($aTableCombis as $aTableCombi){
			
			try {
				$sSql		= '

					DELETE 
						`t1`, 
						`t2` 
					FROM 
						`'.$aTableCombi[1].'` `t1` JOIN
						`'.$aTableCombi[0].'` `t2`
					WHERE
						`t1`.`id` = `t2`.`new_primary`

				';

				$aSql = array();

				//DB::executeQuery($sSql);
				
				$this->_preparedStatement($sSql, $aSql, 'delete_'.implode('_', $aTableCombi));
			
			} catch (Exception $exc) {
				//__out($exc);
			}
		}
			
		return true;
	}
	
}