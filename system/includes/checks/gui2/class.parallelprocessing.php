<?php

use Core\Entity\ParallelProcessing\Stack;

class Checks_Gui2_ParallelProcessing extends GlobalChecks {
	
	protected $aParserCache = array();

	public function getTitle() {
		return 'Index Stack';
	}
	
	public function getDescription() {
		return 'Updates the structure of database for index entries';
	}
	
	public function executeCheck() {

		set_time_limit(3600);
		ini_set("memory_limit", '1G');

		// Check muss nicht ausgeführt werden, wenn Tabelle nicht da
		if(!Util::checkTableExists('gui2_indexes_stacks')){
			return true;
		}		

		$bBackup = Util::backupTable('gui2_indexes_stacks');
		if(!$bBackup) {
			return false;
		}
	
		$sSql = "DELETE FROM `gui2_indexes_stacks` WHERE `index_name` = 'ts_attendance'";
		DB::executeQuery($sSql);
	
		$aStack = $this->getStackEntries();
		$oRepository = Stack::getRepository();
		/* @var $oRepository \Core\Entity\ParallelProcessing\StackRepository */
		
		$aEntries = array();
		$iCounter = 1;
		
		foreach($aStack as $sIndex => $aIds) {
			foreach($aIds as $iId => $iPrio) {
								
				$oWDBasic = $this->_getIndexEntryObject($sIndex, $iId);
				
				if(
					$oWDBasic instanceof WDBasic &&
					// Püfen ob WDBasic-Eintrag noch verwendet wird
					$oWDBasic->id > 0
				) {					
					$bAdd = true;
					if(
						$oWDBasic->hasActiveField() &&
						$oWDBasic->active == 0
					) {
						$bAdd = false;
					}
					
					if($bAdd === true) {
						$aEntries[] = array(
							'index_name' => (string) $sIndex,
							'id' => (int) $iId,
							'priority' => (int) $iPrio
						);
					}
				}
				
				if($iCounter % 100 == 0) {
					WDBasic::clearAllInstances();
				}
				
				$iCounter++;
			}
		}

		$oRepository->writeEntriesToStack('gui2/index', $aEntries); 
		
		$sSql = "DROP TABLE #table";
		DB::executePreparedQuery($sSql, array('table' => 'gui2_indexes_stacks'));

		return true;
	}
		
	/**
	 * Liefert den Gui-Parser zu einer yml-Datei
	 * 
	 * @param string $sConfig
	 * @return Ext_Gui2_Config_Parser
	 */
	protected function getParser($sConfig) {		
		if(!isset($this->aParserCache[$sConfig])) {
			$oParser = new Ext_Gui2_Config_Parser();
			$oParser->setConfig($sConfig);
			$oParser->load();
			
			$this->aParserCache[$sConfig] = $oParser;
		}
	
		return $this->aParserCache[$sConfig];
	}
	
	/**
	 * Liefert das WDBasic-Objekt zu einem Eintrag 
	 * 
	 * @param string $sIndex
	 * @param int $iIndex
	 * @return WDBasic|null
	 */
	protected function _getIndexEntryObject($sIndex, $iIndex){
		$oParser = $this->getParser($sIndex);
		
        $sWDBasic = $oParser->get(array('class', 'wdbasic'));

		$oWDBasic = null;
		if(
			!empty($sWDBasic) &&
			$sWDBasic !== 'WDBasic'
		) {
			$oWDBasic = Factory::executeStatic($sWDBasic, 'getInstance', array($iIndex));
		}
		
        return $oWDBasic;
    }
	
	/**
	 * Liefert alle Einträge aus dem Gui2-Index-Stack
	 * 
	 * @return array
	 */
	protected function getStackEntries() {
		$aStack = array();
		
		if(Ext_Gui2_Index_Stack::useDatabase()){                
			$oDB = DB::getDefaultConnection();
			$sSql = 'SELECT * FROM gui2_indexes_stacks';
			$aDBStack = $oDB->getCollection($sSql, array());
		} else{
			$oWDSearch = Ext_Gui2_Index_Stack::getWDSearch();
			$aDBStack = $oWDSearch->getCollection('', array('*'));
		}

		foreach($aDBStack as $aStackEntry){ 
			$sEntryIndex = $aStackEntry['index_name'];
			$iIndex = (int)$aStackEntry['index_id'];
			if($iIndex > 0){
				$iPrio = (int)$aStackEntry['priority'];
				$aStack[$sEntryIndex][$iIndex] = $iPrio;
			}
		}

		return $aStack;
	}
	
}

