<?php

namespace Core\Entity\ParallelProcessing;

use Core\Service\ParallelProcessingService;

class StackRepository extends \WDBasic_Repository {
	
	/**
	 * Liefert alle Einträge aus der Stacktabelle als Array
	 * 
	 * @param string $sType
	 * @param int $iLimit
	 * @return array
	 */
	public function getEntries($sType = null, $iLimit = null) {

		$aSql = array('table' => $this->_oEntity->getTableName());
		
		$sSql = " SELECT * FROM #table ";
		
		if($sType !== null) {
			$sSql .= " WHERE `type` = :type ";
			$aSql['type'] = $sType;
		}

		$sSql .= " ORDER BY `priority` ASC, `id` ASC ";

		if($iLimit !== null) {
			$sSql .= " LIMIT ". (int) $iLimit;
		}
		
		$aReturn = \DB::getQueryData($sSql, $aSql);
		
		return $aReturn;
	}
		
	/**
	 * Speichert einen Eintrag in die Stacktabelle
	 * 
	 * @param string $sType
	 * @param array $aData
	 * @param int $iPriority
	 * @return int
	 * 
	 * @todo Stringlänge vom JSON prüfen!
	 */
	public function writeToStack($sType, array $aData, $iPriority = 1, $bAddUserId=true) {

		$aEntry = $this->generateEntry($sType, $aData, $iPriority, $bAddUserId);
		$iStackId = \DB::insertData($this->_oEntity->getTableName(), $aEntry, true, true);
		
		$oLog = \Log::getLogger('stack_repository');
		$oLog->addInfo($sType, [$aEntry, \Util::getBacktrace()]);

		return $iStackId;
	}
	
	/**
	 * Speichert mehrere Einträge in die Stacktabelle
	 * 
	 * @param type $sType
	 * @param array $aEntries
	 */
	public function writeEntriesToStack($sType, array $aEntries, $bAddUserId=true) {

		$aData = array();
		
		foreach ($aEntries as $aEntry) {
			$iPriority = 1;
			if(isset($aEntry['priority'])) {
				$iPriority = (int) $aEntry['priority'];
			}
			
			$aData[] = $this->generateEntry($sType, $aEntry, $iPriority, $bAddUserId);
		}
		
		foreach($aData as $aEntry) {
			\DB::insertData($this->_oEntity->getTableName(), $aEntry, true, true);		
		}
	}
	
	/**
	 * Speichert den Task erneut in den Error-Stack
	 * 
	 * @param array $aTask
	 */
	public function rewriteTaskToStack(array $aTask) {
		
		unset($aTask['id']);

		$aTask['priority']++;
		$aTask['execution_count']++;

		\DB::insertData($this->_oEntity->getTableName(), $aTask, true, true);	
	}
	
	/**
	 * Speichert den übergebenen Task in den Error-Stack
	 * 
	 * @param array $aTask
	 * @param array $aErrorData
	 */
	public function writeTaskToErrorStack(array $aTask, array $aErrorData) {
				
		$sJson = json_encode($aErrorData);
		
		unset($aTask['id']);
		
		$aTask['error_data'] = $sJson;
		$aTask['created'] = date('Y-m-d H:i:s');
		
		\DB::insertData('core_parallel_processing_stack_error', $aTask, true, true);
	}
	
	/**
	 * Löscht einen Stack-Eintrag aus dem Stack
	 * 
	 * @param int $iId
	 */
	public function deleteStackEntry($iId) {
		$sSql = "DELETE FROM #table WHERE `id` = :id";
		\DB::executePreparedQuery($sSql, array('table' => $this->_oEntity->getTableName(), 'id' => (int) $iId));
	}
	
	/**
	 * Generiert einen Eintrag für die Stacktabelle
	 * 
	 * @param string $sType
	 * @param array $aData
	 * @param int $iPriority
	 * @return array
	 */
	public function generateEntry($sType, array $aData, $iPriority = 1, $bAddUserId=true) {

		$sJson = json_encode($aData);

		$sMD5 = md5($sJson);

		$aEntry = array(
			'created' => gmdate('Y-m-d H:i:s'),
			'type' => (string)$sType,
			'hash' => (string)$sMD5,
			'data' => $sJson,
			'priority' => (int) $iPriority
		);

		if($bAddUserId === true) {
			// Add user_id if backend session is active
			$oAccess = \Access::getInstance();

			if(
				$oAccess !== null &&
				$oAccess instanceof \Access_Backend
			) {
				$aEntry['user_id'] = $oAccess->id;
			}
		}
		
		return $aEntry;
	}

	/**
	 * Holt die Einträge gruppiert nach den Typen aus der Datenbank
	 *
	 * @return array
	 */
	public function getReport() {

		$aSql = array('table' => $this->_oEntity->getTableName());

		$sSql = "
				SELECT
					`type`,
					COUNT(`type`) `count_type`
				FROM
					#table
				GROUP BY
					`type`
		";

		$aResults = \DB::getQueryData($sSql, $aSql);

		foreach ($aResults as &$aResult) {
			$aResult['type'] = $this->getLabel($aResult['type']);
		}

		return $aResults;
	}

	/**
	 * @param string $sType
	 * @return string
	 */
	public function getLabel($sType) {

		try {

			$oParallelProcessingService = new ParallelProcessingService();
			$oTypeHandler = $oParallelProcessingService->getTypeHandler($sType);
		
			if($oTypeHandler) {
				$sLabel = $oTypeHandler->getLabel();
			} else {
				$sLabel = $sType;
			}
			
		} catch(\Throwable $e) {
			$sLabel = \L10N::t('Unbekannt', 'Framework').' ('.$sType.')';
		}

		return $sLabel;
	}

}
