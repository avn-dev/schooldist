<?php

use Core\Entity\ParallelProcessing\Stack;

class Ext_Gui2_Index_Stack {
	
	/**
	 * @var array
	 */
	protected static $_aStack = array();

	/**
	 * Cache pro Index
	 * @var array
	 */
	protected static $_aStackCache = array();
	
	protected static $_aGetFromDB = array();
    
    /**
     * @var \ElasticaAdapter\Adapter\Index
     */
    protected static $_oIndex = null;
    
    /**
     * @var \ElasticaAdapter\Facade\Elastica
     */
    protected static $_oWDSearch = null;

	/**
	 * gibt den Aktuellen Stack für einen Index zurück
	 * @param string $sIndex
	 * @param bool $bWithDatabase
	 * @return array 
	 * @throws ErrorException 
	 */
	public static function get($sIndex, $bWithDatabase = true) {
		
		if(!is_string($sIndex)) {
			throw new ErrorException('Index Name must be a String!');
		}

        if($bWithDatabase) {

			if(!isset(self::$_aGetFromDB[$sIndex])) {
				$aStack = self::getFromDB($sIndex);

				if(!isset($aStack[$sIndex])) {
					$aStack[$sIndex] = array();
				}

				$aStack[$sIndex]= self::mergeIndexStacks($aStack[$sIndex], self::$_aStack[$sIndex]);

				self::$_aGetFromDB[$sIndex] = true;

			} else {
				$aStack = self::$_aStack;
			}
	  	
			$aStack = $aStack[$sIndex];
        } else {
            $aStack = self::$_aStack[$sIndex];
        }

		return $aStack;
	}
    
    /**
	 * gibt den Aktuellen Stack für einen Index zurück
	 * @param string $sIndex
	 * @return array 
	 * @throws ErrorException 
	 */
	public static function getDbCollection($sIndex){
		
		if(!is_string($sIndex)){
			throw new ErrorException('Index Name must be a String!');
		}
        
		$aStack = self::getDbCollectionOfIndexes();
		
		$aFinalStack = array();
        foreach($aStack as $aEntryStack) {
			$aData = json_decode($aEntryStack['data']);
			if($aData['index_name'] === $sIndex) {
				$aFinalStack[] = $aEntryStack;
			}			
				
		}
  
		return $aFinalStack;
	}
    
    /**
     * Liefert alle Gui2-Index Einträge aus dem Stack
	 * 
     * @return array 
     */
    public static function getDbCollectionOfIndexes(){
        
		$oRepository = Stack::getRepository();
		/* @var $oRepository \Core\Entity\ParallelProcessing\StackRepository */
		$aStackEntries = $oRepository->getEntries('gui2/index');

		return $aStackEntries;
    }
	
	/**
	 * merged 2 Stacks EINES Indexes
	 * achtet hierbei darauf welche Prio niedrieger ist und übernimmt diese
	 * @param array $aStack1
	 * @param array $aStack2
	 * @return array 
	 */
	public static function mergeIndexStacks($aStack1, $aStack2){
		
		if(!is_array($aStack2)){
			return $aStack1;
		}
		
		$aFinalStack = array();
		
        // Alle neuen einträge die nicht in beiden Stacks vorkommen ermitteln
		$aNewStack1 = array_diff_key($aStack1, $aStack2);
		$aNewStack2 = array_diff_key($aStack2, $aStack1);
		$aNewStack	= $aNewStack1 + $aNewStack2;

        // Alle Einträge ide in beiden Stacks vorkommen ermitteln
		$aDoubleStack1	= array_diff_key($aStack1, $aNewStack);
		$aDoubleStack2	= array_diff_key($aStack2, $aNewStack);
		$aDoubleStack	= $aDoubleStack1 + $aDoubleStack2;

		
		foreach($aDoubleStack as $iIndex => $iTemp){
			
			$iPriStack1 = $aStack1[$iIndex];
			$iPriStack2 = $aStack2[$iIndex];
			
			if(
				$iPriStack2 === null ||
				(
					$iPriStack2 > $iPriStack1 &&
					$iPriStack1 !== null
				)
			){
				$iPrio = $iPriStack1;
			} else {
				$iPrio = $iPriStack2;
			}
			
			$aFinalStack[$iIndex] = $iPrio;
			
		}
		
		$aFinalStack = $aFinalStack + $aNewStack;

		// Kein ksort, ansonsten ist das ORDER BY sinnlos
		//ksort($aFinalStack);
		
		return $aFinalStack;
	}
	
	/**
	 * Holt dan Stack für einen Index aus der DB
	 * @param string $sIndex
	 * @return array 
	 * @throws ErrorException 
	 */
	public static function getFromDB($sIndex=null, $iLimit = 0){
		
		if(
			$sIndex !== null &&
			!is_string($sIndex)
		){
			throw new ErrorException('Index Name must be a String!');
		}

		if(
			$sIndex === null ||
			!isset(self::$_aStackCache[$sIndex])
		) {

			$oRepository = Stack::getRepository();
			/* @var $oRepository \Core\Entity\ParallelProcessing\StackRepository */
			$aDBStack = $oRepository->getEntries('gui2/index');
            
			$iIteration = 0;
			foreach($aDBStack as $aStackEntry) {
				// Repository dekodiert JSON-Daten nicht
				$aData = json_decode($aStackEntry['data'], true);

				$sEntryIndex = $aData['index_name'];
				$iIndex = (int)$aData['index_id'];
				if($iIndex > 0){
					$iPrio = (int)$aData['priority'];
					self::$_aStackCache[$sEntryIndex][$iIndex] = $iPrio;
				}

				++$iIteration;
			}

			if(
				$sIndex !== null &&
				$iIteration === 0
			) {
				self::$_aStackCache[$sIndex] = array();
			}

		}

		return self::$_aStackCache;
	}

	/**
	 * get all current used Index Stacks
	 * @return array 
	 */
	public static function getAll($bWithDatabase = true){
		$aStack = self::$_aStack;

		$aFinalStack = array();

		// @TODO Irgendwas stimmt hier nicht, wenn Stack bereits DB-Einträge von einem anderen Index hat
		foreach($aStack as $sIndex => $aIndexStack){
			$aFinalStack[$sIndex] = self::get($sIndex, $bWithDatabase);
		}
 
		return $aFinalStack;
	}

	/**
	 * Fügt einen Index Eintrag in den Stack hinzu
	 * @param string $sIndex
	 * @param int $iIndex
	 * @param int $iPrio
	 * @throws ErrorException 
	 */
	public static function add($sIndex, $iIndex, $iPrio, $bWithDatabase=true) {
		
		if(!is_string($sIndex)){
			throw new ErrorException('Index name must be a string!');
		} else if(!is_numeric($iIndex)){
			throw new ErrorException('Index ID must be a number!');
		} else if($iIndex <= 0){
			throw new ErrorException('Index ID must be positive!');
		} else if(!is_numeric($iPrio)){
			throw new ErrorException('Index priority must be a number!');
		} else if($iPrio < 0){
			throw new ErrorException('Index priority must be positive or zero!');
		}
		
		/* 
		 * Die Prio wird nur aktualisiert, wenn sie höher ist (kleinerer Wert) 
		 * als ein eventuell bestehnder Wert
		 */
		$iCurrentPrio = null;
		if(isset(self::$_aStack[$sIndex][$iIndex])) {
			$iCurrentPrio = self::$_aStack[$sIndex][$iIndex];
		}

		if(
			$iCurrentPrio === null ||
			$iCurrentPrio > $iPrio
		){
			self::$_aStack[$sIndex][$iIndex] = (int)$iPrio;
		}

	}

	/**
	 * Einzelne Felder im Index SOFORT aktualisieren
	 *
	 * @TODO: Man könnte so eine Art Column-Sets ergänzen, die mehrere Felder gleichzeitig zusammenfassen
	 *
	 * @see \Ext_Gui2_Index_Generator::updateDocument()
	 * @param string $sIndex
	 * @param int $iIndexId
	 * @param array $aFields
	 */
	public static function update($sIndex, $iIndexId, array $aFields) {

		$oGenerator = new Ext_Gui2_Index_Generator($sIndex);

		$oEntity = $oGenerator->getIndexEntity($iIndexId);

		$oGenerator->updateIndexEntry($oEntity, 0, $aFields);

	}
	
	/**
	 * löscht einen Index Eintrag aus dem Stack
	 * @param string $sIndex
	 * @param int $iIndex
	 * @return boolean
	 * @throws ErrorException 
	 */
	public static function delete($sIndex, $iIndex){		
		return true;
//		if(!is_string($sIndex)){
//			throw new ErrorException('Index Name must be a String!');
//		} else if(!is_numeric($iIndex)){
//			throw new ErrorException('Index ID must be a Number!');
//		}
//		
//		// Wenn aktuell im Stack dann löschen
//		$aStack = self::$_aStack[$sIndex];
//		if(
//			is_array($aStack) &&
//			key_exists($iIndex, $aStack)
//		){
//			unset(self::$_aStack[$sIndex][$iIndex]);
//		}
//		
//        if(self::useDatabase()){
//            DB::begin('Ext_Gui2_Index_Stack::delete');
//        }
//        
//		try {
//            
//            if(self::useDatabase()){
//                
//                // Datenbank einträge jedoch immer löschen auch wenn es aktuell nicht im stack ist
//                // da man eine genaue ID angibt und somit ein zweck verfolgt.
//                $sSql = "DELETE FROM `gui2_indexes_stacks` WHERE `index_name` = :index_name AND `index_id` = :index_id ";
//                $aSql = array('index_name' => $sIndex, 'index_id' => (int)$iIndex);
//                DB::executePreparedQuery($sSql, $aSql);
//                DB::commit('Ext_Gui2_Index_Stack::delete');
//                
//            } else {
//                $oIndex = self::getIndex();
//                $oType  = $oIndex->getType();
//                $oType->deleteByQuery('index_name:'.$sIndex.' AND index_id:'.$iIndex);
//                
//                //TODO LOG übernehmen
//            }
//			
//			return true;
//		} catch (Exception $exc) {
//            
//            if(self::useDatabase()){
//                DB::rollback('Ext_Gui2_Index_Stack::delete');
//            }
//		}
//		
//		return false;
	}
	
	/**
	 * delete all current Used Index Stacks 
	 * @return boolean
	 */
	public static function deleteAllUsedIndexStacks($bDeleteZeroPrio = false){
		return true;
//
//        if(self::useDatabase()){
//            DB::begin('Ext_Gui2_Index_Stack::deleteAllUsedIndexStacks');
//        }
//		
//		try {
//            
//            if(self::useDatabase()){
//                
//                $aStack = (array)self::$_aStack;
//                foreach($aStack as $sIndex => $aIndexStack){
//                    $sSql = "DELETE FROM `gui2_indexes_stacks` WHERE `index_name` = :index_name";
//                    if(!$bDeleteZeroPrio){
//                        $sSql .= " AND `priority` > 0";
//                    }
//                    $aSql = array('index_name' => $sIndex);
//                    DB::executePreparedQuery($sSql, $aSql);
//                }
//                self::$_aStack = array();
//                self::$_aGetFromDB = array();
//
//                DB::commit('Ext_Gui2_Index_Stack::deleteAllUsedIndexStacks');
//            
//            } else {
//                $oIndex = self::getIndex();
//                $oType  = $oIndex->getType();
//                foreach($aStack as $sIndex => $aIndexStack){
//                    $sQuery = 'index_name:'.$sIndex;
//                    if(!$bDeleteZeroPrio){
//                        $sQuery .= " AND `priority` > 0";
//                    }
//                    $oType->deleteByQuery($sQuery);
//                }
//            }
//			
//			return true;
//			
//		} catch (Exception $exc) {
//            if(self::useDatabase()){
//                DB::rollback('Ext_Gui2_Index_Stack::deleteAllUsedIndexStacks');
//            }
//		}
//		
//		return false;
	}
	
	/**
	 * Speichert den gesammten aktuellen Stack in die Datenbank 
	 * holt zuerst alle aktuell benutzen Stacks.
	 * Und zwar ein merge aus DB und aktuell gesetzt,
	 * Danach löschen wir alle Stack informationen aus dem Static und DB und speichern dann alle erneut
	 * @return boolean
	 */
	public static function save($bSaveZeroPrio = false){
			
        DB::begin('Ext_Gui2_Index_Stack::save');        
		
		try {
			
			// Holen (wichtig zu erst holen dann löschen)
			//$aStacks = self::getAll();

			// Direkt statisches Array holen #7104
			// Früher war getAll() wohl zum Verhindern von doppelten Einträgen da, aber das funktioniert ohnehin nicht mehr
			$aStacks = self::$_aStack;

			// Löschen (löscht auch static daher vorher erst holen)
			//self::deleteAllUsedIndexStacks($bSaveZeroPrio);

			$aData = array();
          
			if(is_array($aStacks)){
				foreach($aStacks as $sIndex => $aIndexes){
					if(is_array($aIndexes)) {
						foreach($aIndexes as $iIndex => $iPrio){
							if(
							   $iIndex > 0 &&
							   (
								 (
									!$bSaveZeroPrio && 
									$iPrio > 0
								 ) ||
								 $bSaveZeroPrio
							   )
							){
								$aData[] = array(
									'index_name' => $sIndex,
									'index_id' => (int)$iIndex,
									'priority' => (int)$iPrio
								);
							}
						}
					}
					
				}
			}

			if(!empty($aData)){                  
				$oRepository = Stack::getRepository();
				/* @var $oRepository \Core\Entity\ParallelProcessing\StackRepository */
				$oRepository->writeEntriesToStack('gui2/index', $aData);
			}
			
            DB::commit('Ext_Gui2_Index_Stack::save');		
            			
			return true;
		} catch (Exception $exc) {
            __pout($exc);
            if(self::useDatabase()){
                DB::rollback('Ext_Gui2_Index_Stack::save');
            }
		}
		
		return false;
	}
	
	/**
	 * führ den gesammten Stack aus
	 * @param int $iMaxPrio
	 * @param int $iLimit
     * @return boolean
	 * @throws ErrorException 
	 */
	public static function execute($iMaxPrio, $iLimitOriginal = 0, $sIndex = '', $iDebug = 0){
	
		if(!is_numeric($iMaxPrio)){
			throw new ErrorException('Max. Priority must be a Number!');
		} else if($iMaxPrio < 0){
			throw new ErrorException('Max. Priority must be zero or positiv!');
		} else if(!is_numeric($iLimitOriginal)){
			throw new ErrorException('Execute Limit must be a Number!');
		} else if($iLimitOriginal < 0){
			throw new ErrorException('Execute Limit must be zero or positiv!');
		}

        if(empty($sIndex)){
            $aIndexes = self::getDbCollectionOfIndexes();
        } else {
            $aIndexes = array(array('index_name' => $sIndex));
        }
              

        $iLimit = null;
        if($iLimitOriginal > 0){
            $iLimit = $iLimitOriginal;
        }

        foreach($aIndexes as $aIndex){
  
            if(
               $iLimit !== null &&
               $iLimit < 0
            ){
                break;
            }
      
            $oGenerator = new Ext_Gui2_Index_Generator($aIndex['index_name']);
            if($iDebug){
                $oGenerator->enableDebugmode();
            }
            $iCount = $oGenerator->updateIndex($iMaxPrio, $iLimit);

            if($iLimit !== null){
                $iLimit = $iLimit - $iCount;
            }
        }
		
        return true;
	}

	/**
	 * @TODO Ins SequentialProcessing integrieren (mit Handler)
	 *
	 * @return bool
	 */
    public static function executeCache() {
        global $_VARS;
        
        WDBasic::clearAllInstances();
	
		// Instanzen der ALLER Klassen killen damit nicht ggf. falsche objecte ermittelt werden
		// war im zuge von #2022 der Fall keine Ahnung warum
		// aber es war ein Contact object als Instance vorhanden was den Stand vor dem speichern hatte
		// @TODO Anmerkung: Wenn DB::rollback() neue Entitäten löscht, stehen die im Cache dann immer noch mit ID und active 1
		//WDBasic::clearInstances(null, true);
        
		//$aStacks = self::getAll(false); #7104
		$aStacks = self::$_aStack;

        foreach($aStacks as $sIndex => $aStack){
            $aFinalStack = array();
            foreach((array)$aStack as $iIndex => $iPrio){
                $aFinalStack[] = array('index_name' => $sIndex, 'index_id' => $iIndex, 'priority' => $iPrio);
            }
            
            $oGenerator = new Ext_Gui2_Index_Generator($sIndex);
            if(!empty($_VARS['debug']) || !empty($_VARS['gui_debugmode'])) {
                $oGenerator->enableDebugmode();
            }

            $oGenerator->updateIndex(0, null, $aFinalStack);            
        }

        return true;

    }
    
    
    /**
     * Holt den Index vom Stack ( für die nicht DB Variante )
     *
*@return \ElasticaAdapter\Adapter\Index
     */
    public static function getIndex(){
        
        if(!self::$_oIndex){
            $sIndexName = Ext_Gui2_Index_Generator::createIndexName('tc_stack');
            self::$_oIndex = new \ElasticaAdapter\Adapter\Index($sIndexName);
        }
        
        return self::$_oIndex;
    }
    
    public static function deleteIndexAndCreateNew(){
		
		self::deleteAllDbEntries();
		
        self::getIndex();
        self::$_oIndex->delete();
        self::$_oIndex = null;
        self::getIndex();
        $aMappingData = array( //`index_name` = ?, `index_id` = ?, `priority` = ? 
                'index_name'	=> array('store' => true, 'type' => 'text', 'index' => false),
                'index_id'		=> array('store' => true, 'type' => 'integer', 'index' => false),
                'priority'		=> array('store' => true, 'type' => 'integer', 'index' => false)
            );
		self::$_oIndex->createMapping($aMappingData);
        self::$_oIndex = null;
    }
    
    /**
     * Holt den WDSEarch vom Stack ( für die nicht DB Variante )
     *
*@return \ElasticaAdapter\Facade\Elastica
     */
    public static function getWDSearch(){
        
        if(!self::$_oWDSearch){
            $sIndexName = Ext_Gui2_Index_Generator::createIndexName('tc_stack');
            // Wegen Mapping
            self::getIndex();
            self::$_oWDSearch  = new \ElasticaAdapter\Facade\Elastica($sIndexName);
        }
        
        self::$_oWDSearch->setLimit(1000000);
        
        return self::$_oWDSearch;
    }

    static public function getStoreOption(){
        $sStackValue    = System::d('index_stack_store');
        if(empty($sStackValue)){
            $sStackValue = 'db';
        }
        return $sStackValue;
    }
    
    static public function useDatabase(){
        $option = self::getStoreOption();
        if($option == 'index'){
            return false;
        }
        return true;
    }
    
    static public function saveStoreOption($sStoreage){
        $sCurrent = self::getStoreOption();
        
        if($sStoreage !== 'index'){
            $sStoreage = 'db';
        }
        
        if($sCurrent != $sStoreage){
            self::migrateStorage();
        }
        
        System::s('index_stack_store', $sStoreage);
    }
    
    public static function migrateStorage(){
        //TODO write Db -> index || index -> db
        __out('Please reset all Indexes ( Tools Html -> Reset All )');
    }
	
	public static function deleteAllDbEntries()
	{
		$sSql = "
			DELETE FROM
				`core_parallel_processing_stack`
			WHERE
				`type` = :type
		";
		
		$rRes = (bool)DB::executePreparedQuery($sSql, array('type' => 'gui2/index'));
		
		return $rRes;
	}

	/**
	 * Internen Stack leeren
	 */
	public static function clearStack() {
		self::$_aStack = array();
	}
}
