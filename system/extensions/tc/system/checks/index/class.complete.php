<?php

class Ext_TC_System_Checks_Index_Complete extends GlobalChecks {

	protected $_sIndexName = '';
	protected $_sClass = '';
	
	public function getTitle() {
		$sTitle = 'Checks if the index contains all items';
		return $sTitle;
	}

	public function getDescription() {
		$sDescription = '...';
		return $sDescription;
	}

	public function executeCheck() {
		
		set_time_limit(3600);
		ini_set("memory_limit", '2048M');

		$sIndex	= \ElasticaAdapter\Facade\Elastica::buildIndexName($this->_sIndexName);
		$oIndex = new \ElasticaAdapter\Adapter\Index($sIndex);

		$oSearch = new \ElasticaAdapter\Facade\Elastica($sIndex);
		
		$oIndex = $oSearch->getIndex();
		
		$oSettings = new \Elastica\Index\Settings($oIndex);
		$oSettings->set(['max_result_window' => 999999999]);
		
		$oSearch->setFields(array('id'));
		$oSearch->setLimit(9999999,0);
		$aResult = $oSearch->search();

		$oObject = new $this->_sClass();

		$aSql = $oObject->getListQueryDataForIndex();
		$oDB = DB::getDefaultConnection();
		$oCollection = $oDB->getCollection($aSql['sql'], (array)$aSql['data']);
		
		$aDocumentIds = array();
		foreach($oCollection as $aRowData) {
			$aDocumentIds[$aRowData['id']] = 1;
		}

		__out(count($aDocumentIds));
		
		$aIndexIds = array();
		$iDeletedFromIndex = 0;
		foreach($aResult['hits'] as $aHit) {

			if(!isset($aDocumentIds[$aHit['_id']])) {
				$oIndex->deleteDocuments(array($aHit['_id']));
				$iDeletedFromIndex++;
			} else {
				$aIndexIds[$aHit['_id']] = 1;
				echo '+';flush();
			}

		}

		__out($iDeletedFromIndex);
		__out(count($aIndexIds));

		$iNew = 0;
		$aIds = [];
		foreach($aDocumentIds as $iId=>$iActive) {
			// Wenn Buchung nicht im Index ist
			if(!isset($aIndexIds[$iId])) {
				$aIds[] = $iId;
				Ext_Gui2_Index_Stack::add($this->_sIndexName, $iId, 1);
				echo '.';flush();
				$iNew++;
			}
		}

		__out($iNew);

		if (count($aIds) < 100) {
			__out($aIds);
		}

		Ext_Gui2_Index_Stack::save();

		__out('END');

		return true;

	}
		
}