<?php

class Ext_Gui2_Index_Registry {
	
	/**
	 *
	 * @var array
	 */
	protected static $_aRegistry = array();

	/**
	 *
	 * @var bool 
	 */
    protected static $_bEnabled = false;
    
    protected static $_bUseDatabase = true;
    
    /**
     * @var \ElasticaAdapter\Adapter\Index
     */
    protected static $_oIndex = null;
    
    
    /**
     * @var \ElasticaAdapter\Facade\Elastica
     */
    protected static $_oWDSearch = null;

    /**
	 * clear the current Registry Cache 
	 */
	public static function clear(){
		self::$_aRegistry = array();
	}

	/**
	 * get the Prio from the current Cache Array
	 * @param WDBasic $oObject
	 * @return int|null
	 * @throws ErrorException 
	 */
	public static function getPriorityFromCache(WDBasic $oObject){
                
		if(!$oObject->exist()){
			throw new ErrorException('Object ist not valid!');
		} else if(!$oObject->isActive()){
			throw new ErrorException('Object ist not active!');
		}
		
		$sObject = get_class($oObject);
		$iObject = $oObject->getId();

        //nicht casten da 0 = gültige prio
		$iPrio = self::$_aRegistry[$sObject][$iObject] ?? null;
		
		return $iPrio;
	}
	
	/**
	 * set the Object data into the cache
	 * @param WDBasic $oObject
	 * @param int $iPriority
	 * @return boolean 
     * 
     * exceptions sind auskommentiert da wir des öfternen auch im script ablauf 
     * active == 0 prüfen oder leere Objecte abfragen
     * jedoch darf das nicht in den Stack geschrieben werden
	 */
	public static function set(WDBasic $oObject, $iPriority = 100){
		
        if(!self::$_bEnabled){
            return true;
        }

		if(!$oObject->exist()){
            return false;
			//throw new ErrorException('Object ist not valid!');
		} else if(!$oObject->isActive()){
            return false;
			//throw new ErrorException('Object ist not active!');
		} else if(!is_numeric($iPriority)){
			throw new ErrorException('Priority must be a Number!');
		} else if($iPriority < 0){
			throw new ErrorException('Priority must be zero or positiv!');
		}
		
		$sObject = (string)get_class($oObject);
		$iObject = (int)$oObject->getId();

		$iCurrentPriority = self::getPriorityFromCache($oObject);
		
		if(
			$iCurrentPriority === null ||
			$iCurrentPriority > $iPriority
		){
			self::$_aRegistry[$sObject][$iObject] = $iPriority;
		}
        
        return true;
	}
	
	/**
	 * get an Array with all Index - Object - Priority Data
	 * array(
	 * 0 => array(
	 *		'index_name' => '',
	 *		'index_id' => 0,
	 *		'priority' => 0,
	 *		'object_class' => '',
	 *		'object_id' => 0
	 * ),.....
	 * )
	 * @param WDBasic $oObject
	 * @return array
	 * @throws ErrorException 
	 */
	public static function get(WDBasic $oObject){
        
		if(!$oObject->exist()){
			throw new ErrorException('Object ist not valid!');
		}
        
        $aObject['class']   = get_class($oObject);
		$aObject['id']      = $oObject->getId();
		
        $aData = self::getByArray($aObject);
        
		return $aData;

	}
    
    public static function getByArray($aObject){
        
        $sObject = $aObject['class'];
		$iObject = $aObject['id'];

        if(self::useDatabase()) {

            $aKeys = array(
                'object_class'	=> $sObject,
                'object_id'		=> $iObject
            );
            $aData = (array)DB::getJoinData('gui2_index_registry', $aKeys);
			
        } else {
			
            $oWDSearch  = self::getWDSearch();
            //$oWDSearch->addQuery(new Elastica\Query\MatchAll);
            $oWDSearch->addFieldQuery('object_class', $sObject);
            if($iObject !== null){
                $oWDSearch->addFieldQuery('object_id', $iObject);
            }
            $aData = $oWDSearch->getCollection(
				'', 
				array(
					'index_name',
					'index_id',
					'priority',
					'object_class',
					'object_id'
				)
			);

        }

        return $aData;
    }
	
	/**
	 * save all Entries of the Registry Cache
	 * after save clearing the cache
	 * @param string $sIndex
	 * @param int $iIndex
	 * @return boolean
	 * @throws ErrorException 
	 */
	public static function save($sIndex, $iIndex){

        //Util::debugTime('save');
        
		if(!is_string($sIndex)){
			throw new ErrorException('Index Name must be a String!');
		} else if(!is_numeric($iIndex)){
			throw new ErrorException('Index ID must be a Number!');
		} else if($iIndex <= 0){
			throw new ErrorException('Index ID must be positive!');
		}
		
        if(self::useDatabase()){
            DB::begin('Ext_Gui2_Index_Registry::save');
        }
        
		$aRegistry = self::$_aRegistry;
        
        $oParser = new Ext_Gui2_Config_Parser($sIndex);

		try {
            
            self::delete($sIndex, $iIndex);
			
			$aData = array();

			foreach($aRegistry as $sObject => $aEntries){
				foreach($aEntries as $iObject => $iPriority){
                    
                    $iPriority = self::_checkPriority($oParser, $sObject, $iPriority);
                    
					$aData[] = array(
						'index_name' => $sIndex,
						'index_id' => (int)$iIndex,
						'priority' => (int)$iPriority,
						'object_class' => $sObject,
						'object_id' => (int)$iObject
					);
				}
			}

            if(self::useDatabase()){
                
                DB::insertMany('gui2_index_registry', $aData);
                
            } else {
       
                $oIndex = self::getIndex();
                $aDocs = array();
                foreach($aData as $aRow){
                    $oDocument = new \ElasticaAdapter\Adapter\Document();
                    foreach($aRow as $sField => $mValue){
                        $oDocument->set($sField, $mValue);
                    }
                    $aDocs[] = $oDocument;
                }
                $oIndex->addDocuments($aDocs);
                // muss nicht sofort passieren da wir eh grad indizieren die cache das genau in dem moment wo der Eintrag in den Index kommt eine
                // der neuen Abhängigkeiten verändert wird ist fast 0
                //doch nötig wegen "to many files open" fehler
                $oIndex->refresh();
                
            }
            
            if(self::useDatabase()){
                DB::commit('Ext_Gui2_Index_Registry::save');
            }
			
			self::clear();
			
			return true;
			
		} catch (Exception $exc) {
            __pout($exc);
            if(self::useDatabase()){
                DB::rollback('Ext_Gui2_Index_Registry::save');
            }
		}
        
        //Util::debugTime('save');
		
		return false;
	}
    
    /**
     * delete all registry entries for the given index entry
     * @param string $sIndex
     * @param int $iIndex
     * @throws ErrorException 
     */
    public static function delete($sIndex, $iIndex){
        
        if(!is_string($sIndex)){
			throw new ErrorException('Index Name must be a String!');
		} else if(!is_numeric($iIndex)){
			throw new ErrorException('Index ID must be a Number!');
		} else if($iIndex <= 0){
			throw new ErrorException('Index ID must be positive!');
		}
        
        if(self::useDatabase()){
            
            $sSql = "DELETE FROM 
                        #table 
                    WHERE 
                        `index_name` = :index_name AND
                        `index_id` = :index_id";

            $aSql = array(
                'table' => 'gui2_index_registry',
                'index_name' => $sIndex,
                'index_id' => (int)$iIndex
            );

            DB::executePreparedQuery($sSql, $aSql);
            
        } else {
            
            $oIndex = self::getIndex();
            $oIndex->deleteByQuery('index_name:'.$sIndex.' AND index_id:'.$iIndex);
            
        }
    }
    
    
    /**
     * Holt den Index vom Registry ( für die nicht DB Variante )
     *
     * @return \ElasticaAdapter\Adapter\Index
     */
    public static function getIndex(){
        
        if(!self::$_oIndex){
            $sIndexName = Ext_Gui2_Index_Generator::createIndexName('tc_registry');
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
            'priority'		=> array('store' => true, 'type' => 'integer', 'index' => false),
            'object_class'  => array('store' => true, 'type' => 'text', 'index' => false),
            'object_id'		=> array('store' => true, 'type' => 'integer', 'index' => false)
        );
		self::$_oIndex->createMapping($aMappingData);
        self::$_oIndex = null;
    }
    
    /**
     * Holt den WDSEarch vom Registry ( für die nicht DB Variante )
     *
	 * @return \ElasticaAdapter\Facade\Elastica
     */
    public static function getWDSearch(){
        
        if(!self::$_oWDSearch){
            $sIndexName = Ext_Gui2_Index_Generator::createIndexName('tc_registry');
            // Wegen Mapping
            self::getIndex();
            self::$_oWDSearch  = new \ElasticaAdapter\Facade\Elastica($sIndexName);
        }
        
        self::$_oWDSearch->setLimit(1000000);
        
        return self::$_oWDSearch;
    }
    
	/**
	 * schreibt alle Registry Einträge zu dem angebebenen Object in den Stack
	 * @param WDBasic $oObject
	 * @return bool
	 */
	public static function updateStack(WDBasic $oObject){

		if(!$oObject->exist()){
			//throw new ErrorException('Object ist not valid!');
            return false;
		}

		$aRegistry = self::get($oObject);

        // wenn registry daten da sind dann nur stack befüllen
        // ansonsten den Eintrag indizieren um die Registry befüllt zu bekommen
        // bei einem erneuten updateStack würde dann nur der Stack erweitert werden und nicht nochmal indiziert werden
		if(!empty($aRegistry)){
            foreach($aRegistry as $aEntries){
                Ext_Gui2_Index_Stack::add($aEntries['index_name'], $aEntries['index_id'], $aEntries['priority']);
            }
        }
        
		return true;
	}
 
	/**
	 * Für direkte Index-Verknüpfung aus und schreibt einen Eintrag für die Bearbeitung der Registry ins PP
	 * 
	 * @param WDBasic $oEntity
	 */
	public static function insertRegistryTask(WDBasic $oEntity) {

		$aMapping = Factory::executeStatic('Ext_Gui2', 'getIndexEntityMapping');
		if(isset($aMapping[get_class($oEntity)])) {
			Ext_Gui2_Index_Stack::add($aMapping[get_class($oEntity)], $oEntity->id, 0);
		}

		$aData = [
			'class' => get_class($oEntity),
			'id' => $oEntity->getId()
		];

		// Mit Priorität 10 eintragen, da im selben Request eh nichts passiert
		$oRepository = Core\Entity\ParallelProcessing\Stack::getRepository();
		$oRepository->writeToStack('gui2/registry', $aData, 10);

	}
    
    /**
     * enable the Registry set method
     */
    public static function enable(){
        self::$_bEnabled = true;
    }
    
    /**
     * disable the Registry  set method and clear the cache
     */
    public static function disable(){
        self::$_bEnabled = false;
        self::clear();
    }
	
    /**
     * check if the configfile has prioirty settings
     * @param Ext_Gui2_Config_Parser $oParser
     * @param type $sClass
     * @param type $iPriority
     * @return type 
     */
    protected static function _checkPriority(Ext_Gui2_Config_Parser $oParser, $sClass, $iPriority){
        $aPriority = (array)$oParser->get(array('index', 'priority'));
        if(key_exists($sClass, $aPriority)){
            $iPriority = (int)$aPriority[$sClass];
        }
        return $iPriority;
    }
    
    
    static public function getStoreOption(){
        $sStackValue    = System::d('index_registry_store');
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
        
        System::s('index_registry_store', $sStoreage);
    }
    
    public static function migrateStorage(){
        //TODO write Db -> index || index -> db
        __out('Please reset all Indexes ( Tools Html -> Reset All )');
    }
	
	public static function deleteAllDbEntries()
	{
		$sSql = "
			DELETE FROM
				`gui2_index_registry`
		";
		
		$rRes = DB::executeQuery($sSql);
		
		return $rRes;
	}
}